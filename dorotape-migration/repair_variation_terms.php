<?php
/**
 * Repair variation attribute terms and parent term relationships.
 *
 * Two defects from the original CSV import:
 *   1. ~99 variation attribute slugs have NO term in their taxonomy, so the
 *      dropdown shows the raw slug ("or-1220mm-wide-here-part-roll-airflow-...").
 *   2. Variable-product parents were never related to their variations' terms.
 *      WooCommerce's dropdown only maps slugs to readable term names via the
 *      PARENT's term relationships — without them every taxonomy attribute
 *      renders raw slugs, across ~200 products (all Ritrama ranges etc.).
 *
 * Readable labels are recovered from the migration CSVs (variation SKU ->
 * "Attribute 1 value(s)"), falling back to de-slugified text.
 *
 * Usage:
 *   php repair_variation_terms.php --dry-run
 *   php repair_variation_terms.php
 *
 * Idempotent: existing terms are never modified; parent relationships are
 * replaced with the correct set each run.
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

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE REPAIR\n");

/* ---------- label map: variation SKU -> attribute label ---------- */

$label_by_sku = [];
foreach (['wc_options_variations.csv', 'wc_variations_import.csv'] as $csv) {
    $path = __DIR__ . '/' . $csv;
    if (!file_exists($path)) {
        continue;
    }
    $fh = fopen($path, 'r');
    $header = fgetcsv($fh);
    $sku_i = array_search('SKU', $header);
    $val_i = array_search('Attribute 1 value(s)', $header);
    while (($row = fgetcsv($fh)) !== false) {
        $sku = trim($row[$sku_i] ?? '');
        $val = trim($row[$val_i] ?? '');
        if ($sku !== '' && $val !== '' && !isset($label_by_sku[$sku])) {
            $label_by_sku[$sku] = $val;
        }
    }
    fclose($fh);
}
echo count($label_by_sku) . " variation labels loaded from CSVs\n";

/* ---------- pass 1: create missing terms ---------- */

// Every taxonomy attribute value used by a published variation, with one
// example variation SKU so we can recover the label.
$rows = $wpdb->get_results("
    SELECT SUBSTRING(pm.meta_key, 11) AS taxonomy, pm.meta_value AS slug,
           MIN(p.ID) AS example_id
    FROM {$wpdb->postmeta} pm
    JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        AND p.post_type = 'product_variation' AND p.post_status = 'publish'
    WHERE pm.meta_key LIKE 'attribute\_pa\_%' AND pm.meta_value != ''
    GROUP BY taxonomy, slug
");

$created = $had = $failed = 0;
foreach ($rows as $r) {
    if (!taxonomy_exists($r->taxonomy)) {
        register_taxonomy($r->taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false]);
    }
    if (get_term_by('slug', $r->slug, $r->taxonomy)) {
        $had++;
        continue;
    }

    $example_sku = get_post_meta((int) $r->example_id, '_sku', true);
    $label = $label_by_sku[$example_sku] ?? '';
    if ($label === '') {
        // De-slugify fallback: hyphens to spaces. Imperfect but readable.
        $label = ucfirst(str_replace('-', ' ', $r->slug));
    }

    if ($dry_run) {
        echo "  [dry] create {$r->taxonomy} term '" . substr($label, 0, 60) . "' (slug {$r->slug})\n";
        $created++;
        continue;
    }
    $res = wp_insert_term($label, $r->taxonomy, ['slug' => $r->slug]);
    if (is_wp_error($res)) {
        // Same name, different slug already exists — force a unique name.
        $res = wp_insert_term($label . ' ', $r->taxonomy, ['slug' => $r->slug]);
    }
    if (is_wp_error($res)) {
        echo "  ! {$r->taxonomy}/{$r->slug}: " . $res->get_error_message() . "\n";
        $failed++;
    } else {
        $created++;
    }
}
echo "terms: $had existed, $created created, $failed failed\n";

/* ---------- pass 2: parent term relationships ---------- */

// For each variable parent: relate it to every term its variations use.
$parents = $wpdb->get_results("
    SELECT p.post_parent AS parent_id, SUBSTRING(pm.meta_key, 11) AS taxonomy,
           GROUP_CONCAT(DISTINCT pm.meta_value) AS slugs
    FROM {$wpdb->postmeta} pm
    JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        AND p.post_type = 'product_variation' AND p.post_status = 'publish'
    WHERE pm.meta_key LIKE 'attribute\_pa\_%' AND pm.meta_value != '' AND p.post_parent > 0
    GROUP BY p.post_parent, taxonomy
");

$related = $already = 0;
foreach ($parents as $row) {
    if (!taxonomy_exists($row->taxonomy)) {
        register_taxonomy($row->taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false]);
    }
    $term_ids = [];
    foreach (explode(',', $row->slugs) as $slug) {
        $t = get_term_by('slug', $slug, $row->taxonomy);
        if ($t) {
            $term_ids[] = (int) $t->term_id;
        }
    }
    if (!$term_ids) {
        continue;
    }
    sort($term_ids);
    $current = wp_get_object_terms((int) $row->parent_id, $row->taxonomy, ['fields' => 'ids']);
    if (!is_wp_error($current)) {
        sort($current);
        if ($current === $term_ids) {
            $already++;
            continue;
        }
    }
    if ($dry_run) {
        $related++;
        continue;
    }
    wp_set_object_terms((int) $row->parent_id, $term_ids, $row->taxonomy);
    $related++;
}
echo "parent relationships: $already already correct, $related " . ($dry_run ? 'would be set' : 'set') . "\n";

if (!$dry_run) {
    wc_delete_product_transients();
    delete_transient('wc_attribute_taxonomies');
    echo "transients cleared\n";
}
