<?php
/**
 * Merge the 3 duplicate White Static Cling Vinyl simple products into one
 * variable product, per Sage's live export (sage_skus.csv):
 *   - 4520 (Poli-Print 150 White Static Cling) is only discontinued at
 *     1400mm, replaced there by Ritrama Ri-Jet M150 (already its own
 *     product, SKU RJM1501400 / #6502 — untouched by this script).
 *   - 685mm and 1370mm of 4520 are still live Sage codes: 4520685, 45201370.
 *
 * Keeps #6499 (canonical slug /white-static-cling-vinyl/, cutsize-enabled)
 * as the parent, converts it to variable, adds the two width variations,
 * then trashes the two now-redundant simple products (#6665, #6666 — both
 * confirmed zero order line items before this script ran).
 *
 * Usage:
 *   php merge_white_static_cling.php --dry-run
 *   php merge_white_static_cling.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run']);
$dry_run = isset($opts['dry-run']);

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

$parent_id = 6499;
$widths = [
    // label => [sku, price, old duplicate product id]
    '685mm'  => ['4520685',  2.18, 6665],
    '1370mm' => ['45201370', 4.36, 6666],
];

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE MERGE\n");

$parent = wc_get_product($parent_id);
if (!$parent || !$parent->is_type('simple')) {
    die("#$parent_id is not a simple product (already merged?)\n");
}
printf("parent: #%d %s (%s)\n", $parent_id, $parent->get_name(), $parent->get_sku());

foreach ($widths as $label => [$sku, $price, $old_id]) {
    $old = wc_get_product($old_id);
    if (!$old) {
        die("expected duplicate product #$old_id ($label) not found\n");
    }
    $order_count = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare(
        "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}woocommerce_order_itemmeta
         WHERE meta_key = '_product_id' AND meta_value = %s",
        $old_id
    ));
    if ($order_count > 0) {
        die("#$old_id ($label) has $order_count order line item(s) — aborting, needs manual review\n");
    }
    printf("  %-8s sku=%-10s price=£%.2f  (from duplicate #%d, 0 orders confirmed)\n", $label, $sku, $price, $old_id);
}

/* ---------- attribute helpers (same pattern as width_restructure.php) ---- */

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

$tax = wc_attribute_taxonomy_name('width'); // pa_width, already exists catalogue-wide

if ($dry_run) {
    foreach (array_keys($widths) as $label) {
        ensure_attribute_term($tax, $label, true);
    }
    echo "  [dry] would union categories: Digital Print Window Film + Static Cling Vinyl\n";
    echo "  [dry] would convert #$parent_id to variable, add " . count($widths) . " variations\n";
    echo "  [dry] would trash #6665, #6666\n";
    echo "dry run complete\n";
    exit;
}

/* ---------- execute ---------- */

// 1. Categories: union of the parent's + both duplicates'.
$cat_ids = wp_get_post_terms($parent_id, 'product_cat', ['fields' => 'ids']);
foreach ([6665, 6666] as $old_id) {
    $cat_ids = array_merge($cat_ids, wp_get_post_terms($old_id, 'product_cat', ['fields' => 'ids']));
}
$cat_ids = array_values(array_unique($cat_ids));
wp_set_object_terms($parent_id, $cat_ids, 'product_cat');

// 2. Terms.
$term_ids = [];
$term_by_label = [];
foreach (array_keys($widths) as $label) {
    $t = ensure_attribute_term($tax, $label, false);
    $term_ids[] = (int) $t->term_id;
    $term_by_label[$label] = $t;
}

// 3. Convert to variable, set attributes (keep the parent's existing
//    non-variation attributes — adhesive type / material grade /
//    suitability — untouched; add Width as the variation attribute).
wp_set_object_terms($parent_id, 'variable', 'product_type');
$parent = wc_get_product($parent_id); // reload as WC_Product_Variable

// Clear the parent's own SKU (a variable parent doesn't carry one; the
// widths below claim it instead) via the product object, not raw
// update_post_meta — wc_product_meta_lookup won't otherwise pick up the
// change and the new variation's set_sku() will falsely see it as taken.
$parent->set_sku('');
$parent->save();
$GLOBALS['wpdb']->update("{$GLOBALS['wpdb']->prefix}wc_product_meta_lookup", ['sku' => ''], ['product_id' => $parent_id]);

$attributes = [];
foreach ($parent->get_attributes() as $attr) {
    if ($attr instanceof WC_Product_Attribute) {
        $attributes[] = $attr;
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
foreach ($widths as $label => [$sku, $price, $old_id]) {
    $var = new WC_Product_Variation();
    $var->set_parent_id($parent_id);
    $var->set_sku($sku);
    $var->set_regular_price((string) $price);
    $var->set_price((string) $price);
    $var->set_manage_stock(false);
    $var->set_stock_status('instock');
    $var->set_status('publish');
    $var->set_attributes([$tax => $term_by_label[$label]->slug]);
    $vid = $var->save();
    echo "  created #$vid $sku ($label) £$price\n";
}

// 5. Trash the now-redundant duplicates (restorable, not deleted).
foreach ([6665, 6666] as $old_id) {
    wp_trash_post($old_id);
    echo "  trashed #$old_id\n";
}

// 6. Sync + clear caches.
WC_Product_Variable::sync($parent_id);
wc_delete_product_transients($parent_id);
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
}

$parent = wc_get_product($parent_id);
echo "done: £" . $parent->get_variation_price('min') . "-£" . $parent->get_variation_price('max')
    . " across " . count($parent->get_children()) . " widths\n";
