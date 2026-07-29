<?php
/**
 * Repair product-description image URLs the Kryptronic migration missed.
 *
 * The migration rewrote media references to the uploaded kryptronic-media
 * folder, but two forms slipped through and render as broken images (client
 * report: "the ink icons are not displaying on the PP800 page yet they are on
 * the Ri-Jet 100 page"):
 *
 *   1. src="media/ecom/..."                  — relative, resolves against the
 *                                              product URL and 404s
 *   2. https://www.dorotape.co.uk/media/...  — absolute to the OLD live site
 *
 * Both point at files that were imported and do exist on disk, so this only
 * has to rewrite the URL, never fetch anything.
 *
 * Idempotent: rewritten content no longer matches either pattern, so re-running
 * is a no-op. Safe to replay on dev.
 *
 * Usage:
 *   php fb_media_urls.php --dry-run     list what would change (default)
 *   php fb_media_urls.php --apply       write the changes
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

global $wpdb;

// Imported media lives under uploads/kryptronic-media/media/... — note the
// second "media" segment: it is part of the imported tree, not a duplicate.
// This is the exact prefix the correctly-rewritten products (e.g. Ri-Jet 100)
// already use, so matching it is what makes the broken ones render.
$upload  = wp_upload_dir();
$base    = trailingslashit( $upload['baseurl'] ) . 'kryptronic-media/media/';
$basedir = trailingslashit( $upload['basedir'] ) . 'kryptronic-media/media/';

$posts = $wpdb->get_results(
	"SELECT ID, post_title, post_content FROM {$wpdb->posts}
	 WHERE post_type = 'product' AND post_status IN ('publish','draft','private')
	   AND ( post_content LIKE '%\"media/ecom%'
	      OR post_content LIKE '%www.dorotape.co.uk/media/%' )"
);

echo 'Products with unrewritten media URLs: ' . count( $posts ) . "\n\n";

$changed = 0;
$missing = array();

foreach ( $posts as $post ) {
	$before = $post->post_content;

	// 1. Relative "media/ecom/..." in src/href — anchor on the opening quote so
	//    an already-absolute URL ending in .../media/ecom/... is never touched.
	$after = preg_replace_callback(
		'#(src|href)=(["\'])media/#i',
		function ( $m ) use ( $base ) {
			return $m[1] . '=' . $m[2] . $base;
		},
		$before
	);

	// 2. Absolute old-site URLs.
	$after = str_replace(
		array( 'https://www.dorotape.co.uk/media/', 'http://www.dorotape.co.uk/media/' ),
		$base,
		$after
	);

	if ( $after === $before ) {
		continue;
	}

	// Report any rewritten target that isn't actually on disk, so a broken
	// image never gets silently swapped for a differently broken one.
	if ( preg_match_all( '#' . preg_quote( $base, '#' ) . '([^"\']+)#', $after, $hits ) ) {
		foreach ( array_unique( $hits[1] ) as $rel ) {
			$path = $basedir . rawurldecode( $rel );
			if ( ! file_exists( $path ) ) {
				$missing[] = "#{$post->ID} $rel";
			}
		}
	}

	++$changed;
	printf( "#%-6d %s\n", $post->ID, html_entity_decode( $post->post_title ) );

	if ( $apply ) {
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $after,
			)
		);
	}
}

echo "\n" . ( $apply ? 'Updated' : 'Would update' ) . ": $changed product(s)\n";

if ( $missing ) {
	echo "\nWARNING — rewritten but file not found on disk (" . count( $missing ) . "):\n";
	foreach ( array_slice( array_unique( $missing ), 0, 40 ) as $m ) {
		echo "  $m\n";
	}
} else {
	echo "All rewritten targets exist on disk.\n";
}
