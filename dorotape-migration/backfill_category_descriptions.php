<?php
/**
 * Backfill product category descriptions from the Kryptronic category export.
 *
 * The old site's category pages open with rich intro content (brand logo,
 * product highlights, datasheet links) stored in ecom_cat.description. The
 * original migration created the category tree but never imported this, so
 * every archive page is bare. WooCommerce renders the term description above
 * the product grid natively — no template change needed.
 *
 * Transformations:
 *   - strip the <head>…</head> wrapper (title text would leak into the page)
 *   - rewrite relative media/… asset paths to the live site so images render
 *     (flagged for localisation later, same as product description assets)
 *
 * Only fills categories whose description is currently EMPTY — never
 * overwrites content entered through the CMS.
 *
 * Usage:
 *   php backfill_category_descriptions.php --dry-run
 *   php backfill_category_descriptions.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$opts    = getopt('', ['dry-run']);
$dry_run = isset($opts['dry-run']);

define('WP_IMPORTING', true);
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

echo ($dry_run ? "DRY RUN — no writes\n" : "LIVE BACKFILL\n");

$fh = fopen(__DIR__ . '/ecom_cat_dataexport.csv', 'r');
$header = array_map(fn($h) => mb_convert_encoding($h, 'UTF-8', 'ISO-8859-1'), fgetcsv($fh));

$updated = $skipped_filled = $skipped_nomatch = $skipped_empty = 0;
while (($row = fgetcsv($fh)) !== false) {
    $row = array_map(fn($v) => mb_convert_encoding((string) $v, 'UTF-8', 'ISO-8859-1'), $row);
    if (count($row) !== count($header)) {
        continue;
    }
    $r = array_combine($header, $row);

    $ms = trim($r['xmultisite']);
    if ($ms !== '' && strpos($ms, 'DEFAULT') === false) {
        continue; // Craftstick-only category
    }
    $name = trim($r['name']);
    $desc = trim($r['description']);
    if ($name === '' || $desc === '') {
        $skipped_empty++;
        continue;
    }

    $term = get_term_by('name', $name, 'product_cat');
    if (!$term) {
        $skipped_nomatch++;
        continue;
    }
    if (trim($term->description) !== '') {
        $skipped_filled++;
        continue;
    }

    // Strip <head> block, <html>/<body> wrappers; rewrite relative asset paths.
    $desc = preg_replace('#<head\b[^>]*>.*?</head>#is', '', $desc);
    $desc = preg_replace('#</?(html|body)[^>]*>#i', '', $desc);
    $desc = preg_replace(
        '#(src|href)=(["\'])(?:\./)?media/#i',
        '$1=$2https://www.dorotape.co.uk/media/',
        $desc
    );
    $desc = trim($desc);

    if ($dry_run) {
        echo "  [dry] '{$name}' (#{$term->term_id}) — " . strlen($desc) . " chars\n";
        $updated++;
        continue;
    }
    $res = wp_update_term($term->term_id, 'product_cat', ['description' => $desc]);
    if (is_wp_error($res)) {
        echo "  ! {$name}: " . $res->get_error_message() . "\n";
    } else {
        $updated++;
    }
}
fclose($fh);

echo "updated=$updated | already had content=$skipped_filled | no matching WC category=$skipped_nomatch | no live description=$skipped_empty\n";
if (!$dry_run) {
    clean_taxonomy_cache('product_cat');
    echo "taxonomy cache cleared\n";
}
