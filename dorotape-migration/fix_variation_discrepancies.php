<?php
/**
 * Reconcile WooCommerce variations with the live-site DEFAULT offers.
 *
 * Input: variation_discrepancies.csv (from audit_variations.py) — one row per
 * difference between a WC variable product and the July 2026 live export:
 *   EXTRA   -> variation the live site doesn't sell. Mostly Craftstick sheet /
 *              cut-length options merged in by the prodnum-keyed migration,
 *              plus discontinued sizes (7901's 1370mm). Trashed (restorable).
 *   MISSING -> live option with no WC variation. Created with the live label
 *              and price on the parent's variation attribute.
 *
 * Usage:
 *   php fix_variation_discrepancies.php --dry-run
 *   php fix_variation_discrepancies.php --sku=7901     # one product only
 *   php fix_variation_discrepancies.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts     = getopt('', ['dry-run', 'sku::']);
$dry_run  = isset($opts['dry-run']);
$only_sku = isset($opts['sku']) ? trim($opts['sku']) : '';

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE FIX\n");

/* ---------- read discrepancies ---------- */

$by_parent = []; // parent_id => ['sku', 'extra' => [vid...], 'missing' => [[label, price]...]]
$fh = fopen(__DIR__ . '/variation_discrepancies.csv', 'r');
$header = fgetcsv($fh);
while (($row = fgetcsv($fh)) !== false) {
    [$sku, $parent_id, $kind, $vid, $label, $price] = array_pad($row, 6, '');
    if ($only_sku !== '' && $sku !== $only_sku) {
        continue;
    }
    $p = (int) $parent_id;
    $by_parent[$p]['sku'] = $sku;
    if ($kind === 'EXTRA') {
        $by_parent[$p]['extra'][] = (int) $vid;
    } else {
        $by_parent[$p]['missing'][] = [$label, (float) $price];
    }
}
fclose($fh);
echo count($by_parent) . " products to reconcile\n\n";

/* ---------- helpers ---------- */

/**
 * The parent's variation attribute taxonomy (first is_variation taxonomy attr).
 */
function dorotape_variation_taxonomy(int $parent_id): string {
    $attrs = get_post_meta($parent_id, '_product_attributes', true);
    if (is_array($attrs)) {
        foreach ($attrs as $key => $a) {
            if (!empty($a['is_variation']) && !empty($a['is_taxonomy'])) {
                return $key;
            }
        }
    }
    return '';
}

/* ---------- reconcile ---------- */

$trashed = $created = $errors = 0;
foreach ($by_parent as $parent_id => $work) {
    $parent = wc_get_product($parent_id);
    if (!$parent || !$parent->is_type('variable')) {
        echo "SKIP #$parent_id ({$work['sku']}) — not a variable product\n";
        continue;
    }
    $tax = dorotape_variation_taxonomy($parent_id);
    printf(
        "%-18s #%-6d trash %d, create %d\n",
        $work['sku'], $parent_id,
        count($work['extra'] ?? []),
        count($work['missing'] ?? [])
    );

    if ($dry_run) {
        continue;
    }

    foreach ($work['extra'] ?? [] as $vid) {
        if (get_post_type($vid) === 'product_variation') {
            wp_trash_post($vid);
            $trashed++;
        }
    }

    foreach ($work['missing'] ?? [] as [$label, $price]) {
        if ($tax === '' || $price <= 0) {
            echo "  ! cannot create '$label' (tax='$tax', price=$price)\n";
            $errors++;
            continue;
        }
        if (!taxonomy_exists($tax)) {
            register_taxonomy($tax, ['product'], ['hierarchical' => false, 'show_ui' => false]);
        }
        $term = get_term_by('name', $label, $tax);
        if (!$term) {
            $res = wp_insert_term($label, $tax);
            if (is_wp_error($res)) {
                echo "  ! term '$label': " . $res->get_error_message() . "\n";
                $errors++;
                continue;
            }
            $term = get_term($res['term_id'], $tax);
        }
        try {
            $var = new WC_Product_Variation();
            $var->set_parent_id($parent_id);
            $var->set_regular_price((string) $price);
            $var->set_price((string) $price);
            $var->set_manage_stock(false);
            $var->set_stock_status('instock');
            $var->set_status('publish');
            $var->set_attributes([$tax => $term->slug]);
            $var->save();
            $created++;
        } catch (Throwable $t) {
            echo "  ! variation '$label': " . $t->getMessage() . "\n";
            $errors++;
        }
    }

    // Refresh parent term relationships to the surviving variations' terms.
    if ($tax !== '') {
        $term_ids = [];
        foreach ($parent->get_children() as $vid) {
            if (get_post_status($vid) !== 'publish') {
                continue;
            }
            $slug = get_post_meta($vid, 'attribute_' . $tax, true);
            $t = $slug ? get_term_by('slug', $slug, $tax) : false;
            if ($t) {
                $term_ids[] = (int) $t->term_id;
            }
        }
        wp_set_object_terms($parent_id, array_values(array_unique($term_ids)), $tax);
    }
    WC_Product_Variable::sync($parent_id);
}

echo "\ntrashed=$trashed created=$created errors=$errors\n";
if (!$dry_run) {
    wc_delete_product_transients();
    echo "transients cleared\n";
}
