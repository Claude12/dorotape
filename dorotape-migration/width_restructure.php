<?php
/**
 * Width-first restructure (Michael's model, piloted on 7901 Rainbow Silver).
 *
 * Converts part-roll/full-roll variation pairs into ONE variation per width:
 *   - priced PER METRE (the old part-roll rate)
 *   - the full-roll rate becomes an automatic quantity tier at roll length
 *     (e.g. "1-49:11.54;50:10.16" — 50m+ = £508/50)
 *   - variation SKU = the Sage per-width item code (79011220 pattern from
 *     Michael's email; PROVISIONAL until verified against the Sage export)
 *
 * A customer ordering 60m at 1220mm enters qty 60 and pays 60 × £10.16
 * (£609.60) on one order line with one Sage code — matching how orders are
 * keyed into Sage manually. The theme's tier table / live price / cart
 * repricing engine already handles the rest.
 *
 * Preserves the parent's non-variation attributes (colour family, finish...)
 * and swaps only the variation attribute to "Width". Old variations are
 * TRASHED (restorable). Set the config per product below.
 *
 * Usage:
 *   php width_restructure.php --sku=7901 --dry-run
 *   php width_restructure.php --sku=7901
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts     = getopt('', ['dry-run', 'sku:']);
$dry_run  = isset($opts['dry-run']);
$only_sku = trim($opts['sku'] ?? '');

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

/* ---------- restructure configs ---------- */
// widths: label => [sage_sku, per-metre price, full-roll price, roll length m]
$configs = [
    '7901' => [
        'attribute' => 'Width',
        'unit'      => 'metre',
        'widths'    => [
            '610mm'  => ['7901610',  5.77,  254.00, 50],
            '760mm'  => ['7901760',  7.21,  317.50, 50],
            '1220mm' => ['79011220', 11.54, 508.00, 50],
            '1520mm' => ['79011520', 14.42, 635.00, 50],
        ],
    ],
];

if ($only_sku === '' || !isset($configs[$only_sku])) {
    die("Pass --sku= one of: " . implode(', ', array_keys($configs)) . "\n");
}
$cfg = $configs[$only_sku];

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE RESTRUCTURE\n");

$parent_id = wc_get_product_id_by_sku($only_sku);
$parent    = $parent_id ? wc_get_product($parent_id) : null;
if (!$parent || !$parent->is_type('variable')) {
    die("$only_sku is not a variable product\n");
}
printf("%s  #%d  %s\n", $only_sku, $parent_id, $parent->get_name());

/* ---------- attribute helpers (same pattern as import_missing_products) ---- */

function ensure_global_attribute(string $label, bool $dry): string {
    $slug = rtrim(substr(wc_sanitize_taxonomy_name($label), 0, 28), '-');
    $taxonomy = wc_attribute_taxonomy_name($slug);
    if (!taxonomy_exists($taxonomy)) {
        if (!wc_attribute_taxonomy_id_by_name($slug)) {
            if ($dry) {
                echo "  [dry] would create attribute '$label' ($taxonomy)\n";
            } else {
                $res = wc_create_attribute(['name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order']);
                if (is_wp_error($res)) {
                    die("attribute '$label': " . $res->get_error_message() . "\n");
                }
            }
        }
        register_taxonomy($taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false]);
    }
    return $taxonomy;
}

function ensure_attribute_term(string $taxonomy, string $value, bool $dry): ?WP_Term {
    $term = get_term_by('name', $value, $taxonomy);
    if ($term) {
        return $term;
    }
    if ($dry) {
        echo "  [dry] would create term '$value' in $taxonomy\n";
        return null;
    }
    $res = wp_insert_term($value, $taxonomy);
    if (is_wp_error($res)) {
        die("term '$value': " . $res->get_error_message() . "\n");
    }
    return get_term($res['term_id'], $taxonomy);
}

/* ---------- plan ---------- */

$old_vids = $parent->get_children();
echo "  trash " . count($old_vids) . " old variations, create " . count($cfg['widths']) . " per-metre width variations:\n";
foreach ($cfg['widths'] as $label => [$sku, $per_m, $roll_price, $roll_len]) {
    $roll_rate = round($roll_price / $roll_len, 2);
    $max       = $roll_len - 1;
    printf("    %-8s %-10s £%.2f/m, tiers 1-%d:%.2f;%d:%.2f  (roll £%.2f)\n",
        $label, $sku, $per_m, $max, $per_m, $roll_len, $roll_rate, $roll_price);
}

$tax = ensure_global_attribute($cfg['attribute'], $dry_run);

if ($dry_run) {
    foreach (array_keys($cfg['widths']) as $label) {
        ensure_attribute_term($tax, $label, true);
    }
    echo "dry run complete\n";
    exit;
}

/* ---------- execute ---------- */

// 1. Trash old variations.
foreach ($old_vids as $vid) {
    wp_trash_post($vid);
}

// 2. Terms.
$term_ids = [];
$term_by_label = [];
foreach (array_keys($cfg['widths']) as $label) {
    $t = ensure_attribute_term($tax, $label, false);
    $term_ids[] = (int) $t->term_id;
    $term_by_label[$label] = $t;
}

// 3. Parent attributes: keep non-variation attributes, swap the variation one.
$attributes = [];
foreach ($parent->get_attributes() as $key => $attr) {
    if ($attr instanceof WC_Product_Attribute && !$attr->get_variation()) {
        $attributes[] = $attr; // colour family, finish, adhesive... untouched
    } else {
        // drop old variation attribute; also clear its parent term links
        wp_set_object_terms($parent_id, [], $key);
    }
}
$width_attr = new WC_Product_Attribute();
$width_attr->set_id(wc_attribute_taxonomy_id_by_name($tax));
$width_attr->set_name($tax);
$width_attr->set_options($term_ids);
$width_attr->set_position(0);
$width_attr->set_visible(true);
$width_attr->set_variation(true);
array_unshift($attributes, $width_attr);
$parent->set_attributes($attributes);
$parent->save();

// 4. New variations.
foreach ($cfg['widths'] as $label => [$sku, $per_m, $roll_price, $roll_len]) {
    $roll_rate = round($roll_price / $roll_len, 2);
    $tiers     = sprintf('1-%d:%.2f;%d:%.2f', $roll_len - 1, $per_m, $roll_len, $roll_rate);

    $var = new WC_Product_Variation();
    $var->set_parent_id($parent_id);
    $var->set_sku($sku);
    $var->set_regular_price((string) $per_m);
    $var->set_price((string) $per_m);
    $var->set_manage_stock(false);
    $var->set_stock_status('instock');
    $var->set_status('publish');
    $var->set_attributes([$tax => $term_by_label[$label]->slug]);
    $vid = $var->save();
    update_post_meta($vid, '_price_tiers', $tiers);
    echo "  created #$vid $sku ($label) £$per_m/m tiers=$tiers\n";
}

// 5. Unit + sync.
update_post_meta($parent_id, '_dt_price_unit', $cfg['unit']);
WC_Product_Variable::sync($parent_id);
wc_delete_product_transients();
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
}

$parent = wc_get_product($parent_id);
echo "done: £" . $parent->get_variation_price('min') . "–£" . $parent->get_variation_price('max')
    . "/m across " . count($parent->get_children()) . " widths\n";
echo "NOTE: variation SKUs are PROVISIONAL pending the Sage stock-code export.\n";
