<?php
/**
 * Pre-staging fix: encoding artefacts in product post_content and post_excerpt.
 *
 * Artefacts come from Windows-1252 / Latin-1 bytes that were imported over a
 * mismatched connection charset, leaving double-encoded sequences in the DB
 * (e.g. Â® instead of ®, Âµ instead of µ).
 *
 * Run via browser while logged in as WP admin.
 *   ?dry=1   — (DEFAULT) show what would change, write nothing.
 *   ?live=1  — apply changes to the database.
 *
 * After a live run: WooCommerce → Tools → Clear Transients.
 * Delete this file (or move dorotape-migration/ out of webroot) before staging.
 */

require_once dirname( __FILE__, 5 ) . '/wp-load.php';

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    http_response_code( 403 );
    exit( 'Admin login required. Visit /wp-admin/ first.' );
}

$live    = isset( $_GET['live'] ) && '1' === $_GET['live'];
$dry_run = ! $live;

header( 'Content-Type: text/plain; charset=utf-8' );

echo "Dorotape pre-staging encoding fix\n";
echo 'Mode: ' . ( $dry_run
    ? 'DRY RUN — no changes (add ?live=1 to apply)'
    : 'LIVE — writing to database' ) . "\n";
echo str_repeat( '-', 60 ) . "\n\n";

global $wpdb;

/*
 * Windows-1252 bytes double-encoded as Latin-1 over a UTF-8 connection.
 * Keys are the corrupted byte sequences found in the DB; values are the
 * correct UTF-8 replacement characters.
 *
 * Order matters: longer/more-specific patterns first.
 */
$replacements = [
    'Â®'            => '®',        // U+00AE  registered trademark
    'Âµ'            => 'µ',        // U+00B5  micro sign
    'Â' . chr( 0x97 ) => "\u{2014}", // U+2014  em dash          (CP1252 0x97)
    'Â' . chr( 0x91 ) => "\u{2018}", // U+2018  left single quote (CP1252 0x91)
    'Â' . chr( 0x92 ) => "\u{2019}", // U+2019  right single quote (CP1252 0x92)
];

$posts = $wpdb->get_results(
    "SELECT ID, post_excerpt, post_content
       FROM {$wpdb->posts}
      WHERE post_status = 'publish'
        AND post_type IN ('product', 'product_variation')",
    ARRAY_A
);

echo 'Published products checked: ' . count( $posts ) . "\n\n";

$content_count = 0;
$excerpt_count = 0;
$updated_ids   = [];

foreach ( $posts as $post ) {
    $id          = (int) $post['ID'];
    $new_content = $post['post_content'];
    $new_excerpt = $post['post_excerpt'];

    // Apply encoding fixes to both fields.
    foreach ( $replacements as $find => $replace ) {
        $new_content = str_replace( $find, $replace, $new_content );
        $new_excerpt = str_replace( $find, $replace, $new_excerpt );
    }

    /*
     * Fix literal \n (backslash + n) in short descriptions.
     * This is a Kryptronic CSV import artifact where a line break was stored
     * as the two characters \ and n rather than an actual newline.
     * Scope: post_excerpt only — product content intentionally uses HTML.
     */
    $new_excerpt = str_replace( '\\n', "\n", $new_excerpt );

    $content_changed = $new_content !== $post['post_content'];
    $excerpt_changed = $new_excerpt !== $post['post_excerpt'];

    if ( ! $content_changed && ! $excerpt_changed ) {
        continue;
    }

    $sku = $wpdb->get_var( $wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta}
          WHERE post_id  = %d
            AND meta_key = '_sku'
          LIMIT 1",
        $id
    ) );

    if ( $content_changed ) {
        echo "  [content] ID={$id}  SKU={$sku}\n";
        $content_count++;
    }
    if ( $excerpt_changed ) {
        echo "  [excerpt] ID={$id}  SKU={$sku}\n";
        $excerpt_count++;
    }

    if ( $live ) {
        $wpdb->update(
            $wpdb->posts,
            [
                'post_content' => $new_content,
                'post_excerpt' => $new_excerpt,
            ],
            [ 'ID' => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
        clean_post_cache( $id );
        $updated_ids[] = $id;
    }
}

echo "\n" . str_repeat( '-', 60 ) . "\n";
echo "post_content fixed : {$content_count}\n";
echo "post_excerpt fixed : {$excerpt_count}\n";

if ( $dry_run ) {
    echo "\nDRY RUN — no changes written.\n";
    echo "Add ?live=1 to apply.\n";
} else {
    echo "\nLIVE run complete. " . count( $updated_ids ) . " post(s) updated.\n";
    echo "Next step: WooCommerce → Tools → Clear Transients.\n";
}
