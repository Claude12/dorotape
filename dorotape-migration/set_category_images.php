<?php
/**
 * Pre-staging fix: set thumbnail images on WooCommerce product categories.
 *
 * Reads category_image_map.csv (same directory as this script), registers
 * each image as a WordPress attachment (file stays in its current location
 * under uploads/kryptronic-media/), then sets the thumbnail_id term meta
 * that WooCommerce uses for category images.
 *
 * Usage:
 *   ?dry=1   — (DEFAULT) show what would change, write nothing.
 *   ?live=1  — apply changes.
 *
 * Safe to re-run: categories that already have a thumbnail_id are skipped.
 * Delete this file (or move dorotape-migration/ out of webroot) before staging.
 */

require_once dirname( __FILE__, 5 ) . '/wp-load.php';

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    http_response_code( 403 );
    exit( 'Admin login required. Visit /wp-admin/ first.' );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$live    = isset( $_GET['live'] ) && '1' === $_GET['live'];
$dry_run = ! $live;

header( 'Content-Type: text/plain; charset=utf-8' );

echo "Dorotape category image setter\n";
echo 'Mode: ' . ( $dry_run
    ? 'DRY RUN — no changes (add ?live=1 to apply)'
    : 'LIVE — writing to database' ) . "\n";
echo str_repeat( '-', 60 ) . "\n\n";

$cat_base_path = WP_CONTENT_DIR . '/uploads/kryptronic-media/media/ecom/cat/';
$cat_base_url  = content_url( '/uploads/kryptronic-media/media/ecom/cat/' );
$map_file      = __DIR__ . '/category_image_map.csv';

if ( ! file_exists( $map_file ) ) {
    exit( 'category_image_map.csv not found next to this script.' );
}

// Parse mapping CSV
$mapping = [];
if ( ( $fh = fopen( $map_file, 'r' ) ) !== false ) {
    $headers = fgetcsv( $fh ); // skip header row
    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
        if ( count( $row ) >= 3 ) {
            $mapping[] = [
                'term_id' => (int) $row[0],
                'wc_name' => $row[1],
                'catimg'  => trim( $row[2] ),
            ];
        }
    }
    fclose( $fh );
}

echo 'Mapping entries: ' . count( $mapping ) . "\n\n";

$set_count     = 0;
$skipped_count = 0;
$error_count   = 0;

foreach ( $mapping as $entry ) {
    $term_id = $entry['term_id'];
    $catimg  = $entry['catimg'];
    $name    = $entry['wc_name'];

    // Verify term exists
    $term = get_term( $term_id, 'product_cat' );
    if ( ! $term || is_wp_error( $term ) ) {
        echo "  [SKIP]  ID={$term_id} — term not found in WC\n";
        $skipped_count++;
        continue;
    }

    // Skip if thumbnail already set
    $existing = get_term_meta( $term_id, 'thumbnail_id', true );
    if ( $existing ) {
        echo "  [SKIP]  ID={$term_id} \"{$term->name}\" — thumbnail already set (attach {$existing})\n";
        $skipped_count++;
        continue;
    }

    $file_path = $cat_base_path . $catimg;
    $file_url  = $cat_base_url  . rawurlencode( $catimg );

    if ( ! file_exists( $file_path ) ) {
        echo "  [ERROR] ID={$term_id} \"{$term->name}\" — file not found: {$catimg}\n";
        $error_count++;
        continue;
    }

    echo "  [SET]   ID={$term_id} \"{$term->name}\" → {$catimg}\n";
    $set_count++;

    if ( $live ) {
        $mime = wp_check_filetype( $file_path );

        $attach_id = wp_insert_attachment(
            [
                'post_mime_type' => $mime['type'],
                'post_title'     => sanitize_file_name( pathinfo( $catimg, PATHINFO_FILENAME ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'guid'           => $file_url,
            ],
            $file_path
        );

        if ( is_wp_error( $attach_id ) ) {
            echo "         ERROR creating attachment: " . $attach_id->get_error_message() . "\n";
            $error_count++;
            $set_count--;
            continue;
        }

        $meta = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $meta );

        update_term_meta( $term_id, 'thumbnail_id', $attach_id );
    }
}

echo "\n" . str_repeat( '-', 60 ) . "\n";
echo "Set:     {$set_count}\n";
echo "Skipped: {$skipped_count}\n";
echo "Errors:  {$error_count}\n";

if ( $dry_run ) {
    echo "\nDRY RUN — no changes written. Add ?live=1 to apply.\n";
} else {
    echo "\nDone. WooCommerce → Tools → Clear Transients recommended.\n";
}
