<?php
/**
 * Import products missed by the original Kryptronic migration.
 *
 * Reads missing_products_manifest.json (built from the July 2026 live-site
 * exports by build_manifest.py). The manifest is keyed by Kryptronic OFFER id,
 * not prodnum — duplicate prodnums (900E full roll vs part roll etc.) are what
 * caused these products to be dropped by the original SKU-keyed import.
 *
 * Entry types:
 *   simple    -> WC_Product_Simple with price + optional _price_tiers meta
 *   variable  -> WC_Product_Variable, one global attribute, priced variations;
 *                optional "choice_attributes" become variation attributes whose
 *                variations accept Any value (finish, blade type, ...)
 *   update_existing -> product already in WC (Hook & Loop POA simples):
 *                convert to variable, attach variations, clear _price_poa.
 *
 * Usage (CLI only):
 *   php import_missing_products.php --dry-run
 *   php import_missing_products.php --limit=5
 *   php import_missing_products.php
 *
 * Idempotent: entries whose SKU already resolves to a product are skipped
 * (except update_existing entries, which are skipped once already variable).
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run', 'limit::', 'manifest::']);
$dry_run = isset($opts['dry-run']);
$limit   = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$manifest_name = $opts['manifest'] ?? 'missing_products_manifest.json';

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

if (!class_exists('WooCommerce')) {
    die("WooCommerce not active\n");
}

$manifest_file = __DIR__ . '/' . basename($manifest_name);
$entries = json_decode(file_get_contents($manifest_file), true);
if (!is_array($entries)) {
    die("Cannot parse $manifest_file\n");
}

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE IMPORT\n");
echo count($entries) . " manifest entries\n\n";

/* ---------- category helpers ---------- */

/**
 * "A > B > C" -> deepest term_id, creating missing terms along the path.
 */
function ensure_category_path(string $path, bool $dry): int {
    $parent = 0;
    $term_id = 0;
    foreach (array_map('trim', explode('>', $path)) as $name) {
        if ($name === '') {
            continue;
        }
        $existing = get_terms([
            'taxonomy' => 'product_cat', 'name' => $name,
            'parent' => $parent, 'hide_empty' => false, 'number' => 1,
        ]);
        if (!empty($existing) && !is_wp_error($existing)) {
            $term_id = $existing[0]->term_id;
        } elseif ($dry) {
            echo "    [dry] would create category '$name' under #$parent\n";
            $term_id = 0;
        } else {
            $res = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
            if (is_wp_error($res)) {
                echo "    ! category '$name': " . $res->get_error_message() . "\n";
                return $term_id;
            }
            $term_id = $res['term_id'];
        }
        $parent = $term_id;
    }
    return $term_id;
}

/* ---------- attribute helpers ---------- */

/**
 * Get or create a global product attribute; returns its taxonomy name.
 * Slugs are capped at 28 chars (WooCommerce limit).
 */
function ensure_global_attribute(string $label, bool $dry): string {
    static $cache = [];
    if (isset($cache[$label])) {
        return $cache[$label];
    }
    $slug = substr(wc_sanitize_taxonomy_name($label), 0, 28);
    $slug = rtrim($slug, '-');
    $taxonomy = wc_attribute_taxonomy_name($slug);

    if (!taxonomy_exists($taxonomy)) {
        $existing_id = wc_attribute_taxonomy_id_by_name($slug);
        if (!$existing_id) {
            if ($dry) {
                echo "    [dry] would create attribute '$label' ($taxonomy)\n";
            } else {
                $res = wc_create_attribute([
                    'name' => $label, 'slug' => $slug,
                    'type' => 'select', 'order_by' => 'menu_order',
                ]);
                if (is_wp_error($res)) {
                    throw new RuntimeException("attribute '$label': " . $res->get_error_message());
                }
            }
        }
        // Register for this request so terms can be inserted immediately.
        register_taxonomy($taxonomy, ['product'], [
            'hierarchical' => false, 'show_ui' => false, 'query_var' => true,
        ]);
    }
    $cache[$label] = $taxonomy;
    return $taxonomy;
}

/**
 * Get or create a term in an attribute taxonomy; returns its slug.
 */
function ensure_attribute_term(string $taxonomy, string $value, bool $dry): string {
    $term = get_term_by('name', $value, $taxonomy);
    if ($term) {
        return $term->slug;
    }
    if ($dry) {
        echo "    [dry] would create term '" . substr($value, 0, 50) . "' in $taxonomy\n";
        return sanitize_title(substr($value, 0, 190));
    }
    $res = wp_insert_term($value, $taxonomy);
    if (is_wp_error($res)) {
        // "term exists" race or slug clash — fall back to lookup by slug
        $term = get_term_by('slug', sanitize_title(substr($value, 0, 190)), $taxonomy);
        if ($term) {
            return $term->slug;
        }
        echo "    ! term '$value': " . $res->get_error_message() . "\n";
        return '';
    }
    $term = get_term($res['term_id'], $taxonomy);
    return $term->slug;
}

/* ---------- import ---------- */

$created = $updated = $skipped = $errors = 0;
$done = 0;

foreach ($entries as $e) {
    if ($limit && $done >= $limit) {
        break;
    }

    $sku = $e['sku'];
    $existing_id = wc_get_product_id_by_sku($sku);
    $is_update = !empty($e['update_existing']);

    if ($existing_id && !$is_update) {
        echo "SKIP   $sku (exists as #$existing_id)\n";
        $skipped++;
        continue;
    }
    if ($is_update) {
        if (!$existing_id) {
            echo "ERROR  $sku marked update_existing but not found\n";
            $errors++;
            continue;
        }
        $current = wc_get_product($existing_id);
        if ($current && $current->is_type('variable') && count($current->get_children()) > 0) {
            echo "SKIP   $sku (already converted to variable)\n";
            $skipped++;
            continue;
        }
    }

    $done++;
    $is_variable = $e['type'] === 'variable';
    $label = $is_update ? 'UPDATE' : 'CREATE';
    echo sprintf(
        "%s %s %-22s %s%s\n",
        $label,
        $is_variable ? 'VAR' : 'SIM',
        $sku,
        substr($e['name'], 0, 55),
        $is_variable ? ' (' . count($e['variations']) . ' variations)' : ''
    );

    if ($dry_run) {
        foreach ($e['categories'] as $c) {
            ensure_category_path($c, true);
        }
        if ($is_variable) {
            ensure_global_attribute($e['attribute'], true);
        }
        continue;
    }

    try {
        $main_slugs = [];
        if ($is_update) {
            // Convert existing simple product in place, preserving content.
            wp_set_object_terms($existing_id, 'variable', 'product_type');
            // Flush caches or wc_get_product may return a stale WC_Product_Simple
            // whose save() would revert the type (bit the first conversion run).
            clean_post_cache($existing_id);
            wp_cache_flush();
            $product = wc_get_product($existing_id); // reload as WC_Product_Variable
            if (!$product->is_type('variable')) {
                throw new RuntimeException('type conversion did not stick');
            }
        } else {
            $product = $is_variable ? new WC_Product_Variable() : new WC_Product_Simple();
            $product->set_name($e['name']);
            $product->set_sku($sku);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            if ($e['short_desc']) {
                $product->set_short_description($e['short_desc']);
            }
            if ($e['long_desc']) {
                $product->set_description($e['long_desc']);
            }
            $cat_ids = [];
            foreach ($e['categories'] as $c) {
                if ($tid = ensure_category_path($c, false)) {
                    $cat_ids[] = $tid;
                }
            }
            if ($cat_ids) {
                $product->set_category_ids($cat_ids);
            }
        }

        $product->set_manage_stock(false);
        $product->set_stock_status('instock');

        if (!$is_variable) {
            $product->set_regular_price((string) $e['regular_price']);
            $product->set_price((string) $e['regular_price']);
        }

        /* attributes */
        if ($is_variable) {
            $attributes = [];
            $position = 0;

            $main_tax = ensure_global_attribute($e['attribute'], false);
            $main_slugs = [];
            foreach ($e['variations'] as $v) {
                $slug = ensure_attribute_term($main_tax, $v['label'], false);
                $main_slugs[$v['label']] = $slug;
            }
            $attr = new WC_Product_Attribute();
            $attr->set_id(wc_attribute_taxonomy_id_by_name($main_tax));
            $attr->set_name($main_tax);
            $attr->set_options(array_values(array_unique(
                array_map(fn($v) => get_term_by('slug', $main_slugs[$v['label']], $main_tax)->term_id, $e['variations'])
            )));
            $attr->set_position($position++);
            $attr->set_visible(true);
            $attr->set_variation(true);
            $attributes[] = $attr;

            foreach ($e['choice_attributes'] ?? [] as $ca) {
                $tax = ensure_global_attribute($ca['name'], false);
                $term_ids = [];
                foreach ($ca['values'] as $val) {
                    $slug = ensure_attribute_term($tax, $val, false);
                    $t = get_term_by('slug', $slug, $tax);
                    if ($t) {
                        $term_ids[] = $t->term_id;
                    }
                }
                $attr = new WC_Product_Attribute();
                $attr->set_id(wc_attribute_taxonomy_id_by_name($tax));
                $attr->set_name($tax);
                $attr->set_options($term_ids);
                $attr->set_position($position++);
                $attr->set_visible(true);
                $attr->set_variation(true); // variations leave it "Any"
                $attributes[] = $attr;
            }
            $product->set_attributes($attributes);
        }

        $product_id = $product->save();

        /* shared meta */
        update_post_meta($product_id, '_kryp_prodnum', $e['prodnum']);
        update_post_meta($product_id, '_sage_income_code', 'income-service');
        if ($e['manufacturer']) {
            update_post_meta($product_id, '_manufacturer', $e['manufacturer']);
        }
        if (!empty($e['cutsize'])) {
            update_post_meta($product_id, '_dt_cutsize_enabled', '1');
        }
        if (!empty($e['image'])) {
            update_post_meta($product_id, '_dt_live_image_url', $e['image']);
        }
        if (!$is_variable && $e['price_tiers']) {
            update_post_meta($product_id, '_price_tiers', $e['price_tiers']);
        }
        if ($is_update) {
            delete_post_meta($product_id, '_price_poa');
            delete_post_meta($product_id, '_price');           // stale simple price
            delete_post_meta($product_id, '_regular_price');
        }

        /* variations */
        if ($is_variable) {
            $main_tax = ensure_global_attribute($e['attribute'], false);
            foreach ($e['variations'] as $v) {
                $var = new WC_Product_Variation();
                $var->set_parent_id($product_id);
                $var->set_sku($v['sku']);
                $var->set_regular_price((string) $v['price']);
                $var->set_price((string) $v['price']);
                $var->set_manage_stock(false);
                $var->set_stock_status('instock');
                $var->set_status('publish');
                $var->set_attributes([$main_tax => $main_slugs[$v['label']]]);
                $var_id = $var->save();
                if (!empty($v['tiers'])) {
                    update_post_meta($var_id, '_price_tiers', $v['tiers']);
                }
            }
            WC_Product_Variable::sync($product_id);
        }

        $is_update ? $updated++ : $created++;
    } catch (Throwable $t) {
        echo "ERROR  $sku: " . $t->getMessage() . "\n";
        $errors++;
    }
}

echo "\ncreated=$created updated=$updated skipped=$skipped errors=$errors\n";
if (!$dry_run && ($created || $updated)) {
    wc_delete_product_transients();
    delete_transient('wc_attribute_taxonomies');
    echo "product transients cleared\n";
}
