<?php
/**
 * Backfill sidebar-filter attributes from the Kryptronic filter slots.
 *
 * The live site tags products with five filter slots (ecom_prod
 * xprodfilterone..five -> ecom_prodfilter names). The original import mapped
 * these to global attributes but only a handful of products received terms
 * (colour-family reached ONE product). This script assigns them all:
 *
 *   slot 1 -> pa_adhesive-type    slot 4 -> pa_colour-family
 *   slot 2 -> pa_material-grade   slot 5 -> pa_finish
 *   slot 3 -> pa_suitability
 *
 * Also registers the attribute on each product's _product_attributes array
 * (visible on the product page, not used for variations) and installs
 * "Filter by attribute" widgets (Colour Family, Finish, Adhesive Type) in
 * the shop sidebar so category pages get the old site's filtering.
 *
 * Usage:
 *   php backfill_filter_attributes.php --dry-run
 *   php backfill_filter_attributes.php
 *
 * Idempotent: terms are replaced per product per taxonomy; widgets are only
 * added if not already present.
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run']);
$dry_run = isset($opts['dry-run']);

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

global $wpdb;

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE BACKFILL\n");

const SLOT_TO_TAX = [
    'xprodfilterone'   => 'pa_adhesive-type',
    'xprodfiltertwo'   => 'pa_material-grade',
    'xprodfilterthree' => 'pa_suitability',
    'xprodfilterfour'  => 'pa_colour-family',
    'xprodfilterfive'  => 'pa_finish',
];

/* ---------- load Kryptronic data (latin-1) ---------- */

function read_latin1_csv(string $path): array {
    $rows = [];
    $fh = fopen($path, 'r');
    $header = array_map(fn($h) => mb_convert_encoding($h, 'UTF-8', 'ISO-8859-1'), fgetcsv($fh));
    while (($row = fgetcsv($fh)) !== false) {
        $row = array_map(fn($v) => mb_convert_encoding((string) $v, 'UTF-8', 'ISO-8859-1'), $row);
        if (count($row) === count($header)) {
            $rows[] = array_combine($header, $row);
        }
    }
    fclose($fh);
    return $rows;
}

$filter_names = []; // filter id -> display name
foreach (read_latin1_csv(__DIR__ . '/ecom_prodfilter_dataexport.csv') as $r) {
    $filter_names[trim($r['id'])] = trim($r['filtername']);
}

// prodnum -> [taxonomy -> set of term names] (union across duplicate prodnums)
$filters_by_prodnum = [];
foreach (read_latin1_csv(__DIR__ . '/ecom_prod_dataexport.csv') as $r) {
    $pn = trim($r['prodnum']);
    if ($pn === '') {
        continue;
    }
    foreach (SLOT_TO_TAX as $col => $tax) {
        foreach (explode(',', $r[$col] ?? '') as $fid) {
            $fid = trim($fid);
            if ($fid === '' || !isset($filter_names[$fid])) {
                continue;
            }
            $filters_by_prodnum[$pn][$tax][$filter_names[$fid]] = true;
        }
    }
}
echo count($filters_by_prodnum) . " live products carry filter data\n";

/* ---------- match to WC products ---------- */

// WC product id -> prodnum (prefer _kryp_prodnum, fall back to _sku)
$wc_products = $wpdb->get_results("
    SELECT p.ID, MAX(CASE WHEN pm.meta_key='_kryp_prodnum' THEN pm.meta_value END) kryp,
           MAX(CASE WHEN pm.meta_key='_sku' THEN pm.meta_value END) sku
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ('_kryp_prodnum','_sku')
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    GROUP BY p.ID
");

$assigned = $matched = 0;
foreach ($wc_products as $row) {
    $pn = trim((string) $row->kryp) ?: trim((string) $row->sku);
    if ($pn === '' || !isset($filters_by_prodnum[$pn])) {
        continue;
    }
    $matched++;
    $pid = (int) $row->ID;
    $product_attributes = get_post_meta($pid, '_product_attributes', true);
    if (!is_array($product_attributes)) {
        $product_attributes = [];
    }
    $attrs_changed = false;

    foreach ($filters_by_prodnum[$pn] as $tax => $names) {
        if (!taxonomy_exists($tax)) {
            register_taxonomy($tax, ['product'], ['hierarchical' => false, 'show_ui' => false]);
        }
        $term_ids = [];
        foreach (array_keys($names) as $name) {
            $term = get_term_by('name', $name, $tax);
            if (!$term && !$dry_run) {
                $res = wp_insert_term($name, $tax);
                if (!is_wp_error($res)) {
                    $term = get_term($res['term_id'], $tax);
                }
            }
            if ($term) {
                $term_ids[] = (int) $term->term_id;
            }
        }
        if (!$term_ids && !$dry_run) {
            continue;
        }
        if (!$dry_run) {
            wp_set_object_terms($pid, $term_ids, $tax);
        }
        if (!isset($product_attributes[$tax])) {
            $product_attributes[$tax] = [
                'name'         => $tax,
                'value'        => '',
                'position'     => count($product_attributes),
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => 1,
            ];
            $attrs_changed = true;
        }
        $assigned++;
    }
    if ($attrs_changed && !$dry_run) {
        update_post_meta($pid, '_product_attributes', $product_attributes);
    }
}
echo "matched $matched WC products; $assigned attribute assignments " . ($dry_run ? 'would be made' : 'made') . "\n";

/* ---------- shop sidebar filter widgets ---------- */

$widgets = get_option('widget_woocommerce_layered_nav', ['_multiwidget' => 1]);
$sidebars = wp_get_sidebars_widgets();
$sidebars['sidebar-shop'] = $sidebars['sidebar-shop'] ?? [];

$wanted = [
    ['title' => 'Colour',        'attribute' => 'colour-family'],
    ['title' => 'Finish',        'attribute' => 'finish'],
    ['title' => 'Adhesive Type', 'attribute' => 'adhesive-type'],
];
$existing_attrs = array_column(array_filter($widgets, 'is_array'), 'attribute');

$added = 0;
foreach ($wanted as $w) {
    if (in_array($w['attribute'], $existing_attrs, true)) {
        continue;
    }
    if ($dry_run) {
        echo "  [dry] would add 'Filter by {$w['title']}' widget to shop sidebar\n";
        $added++;
        continue;
    }
    $keys = array_filter(array_keys($widgets), 'is_int');
    $n = $keys ? max($keys) + 1 : 2;
    $widgets[$n] = [
        'title'        => $w['title'],
        'attribute'    => $w['attribute'],
        'display_type' => 'list',
        'query_type'   => 'and',
    ];
    $sidebars['sidebar-shop'][] = "woocommerce_layered_nav-$n";
    $added++;
}
if (!$dry_run && $added) {
    update_option('widget_woocommerce_layered_nav', $widgets);
    wp_set_sidebars_widgets($sidebars);
}
echo "$added filter widgets " . ($dry_run ? 'would be added' : 'added') . "\n";

if (!$dry_run) {
    wc_delete_product_transients();
    delete_transient('wc_attribute_taxonomies');
    echo "transients cleared\n";
}
