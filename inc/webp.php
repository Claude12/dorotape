<?php
/**
 * WebP copies of uploaded images
 *
 * Next to a JPEG or PNG in uploads there may be a WebP copy of it, named by
 * adding .webp to the whole file name (photo.png -> photo.png.webp). An image
 * uploaded as WebP can have one too (tile.webp -> tile.webp.webp) when it was
 * saved with little compression and a re-encode is much smaller. Where
 * that copy exists, the image tags WordPress builds point at it instead, in
 * both src and srcset. Where it does not, nothing changes, so the originals
 * are never touched and deleting the .webp files puts everything back.
 *
 * The copies are made by tools/webp.php, which also gives sub-sizes to
 * images that were imported without any (the old Kryptronic category tiles
 * were 630px PNGs shown at 200px). Run it again after adding images; it only
 * converts what is new or changed.
 *
 * Only tags built by WordPress are affected (wp_get_attachment_image and
 * everything on top of it, WooCommerce included). A raw attachment URL from
 * wp_get_attachment_url() keeps the original, which is what a download link
 * or an og:image wants anyway.
 *
 * @package dorotape
 */

/**
 * The WebP copy's URL for an uploads image URL, or the URL unchanged.
 *
 * @param string $url Image URL.
 */
function dorotape_webp_url( string $url ): string {
	static $uploads = null;
	static $seen    = [];

	if ( ! preg_match( '/\.(jpe?g|png|webp)$/i', $url ) ) {
		return $url;
	}

	if ( isset( $seen[ $url ] ) ) {
		return $seen[ $url ];
	}

	if ( null === $uploads ) {
		$uploads = wp_get_upload_dir();
	}

	// Compare without the scheme: srcset URLs can come back as https on an
	// http baseurl and the other way round.
	$base = preg_replace( '#^https?:#', '', $uploads['baseurl'] );
	$path = preg_replace( '#^https?:#', '', $url );

	$webp = $url;
	if ( 0 === strpos( $path, $base . '/' ) ) {
		$file = $uploads['basedir'] . substr( $path, strlen( $base ) );
		if ( is_file( $file . '.webp' ) ) {
			$webp = $url . '.webp';
		}
	}

	$seen[ $url ] = $webp;
	return $webp;
}

/**
 * Point an attachment's src at its WebP copy.
 *
 * @param array|false $image [url, width, height, is_intermediate].
 * @return array|false
 */
function dorotape_webp_image_src( $image ) {
	if ( is_array( $image ) && ! empty( $image[0] ) ) {
		$image[0] = dorotape_webp_url( (string) $image[0] );
	}
	return $image;
}

/**
 * Point every srcset candidate at its WebP copy.
 *
 * @param array $sources Sources keyed by width.
 */
function dorotape_webp_srcset( $sources ): array {
	if ( ! is_array( $sources ) ) {
		return array();
	}
	foreach ( $sources as $width => $source ) {
		if ( ! empty( $source['url'] ) ) {
			$sources[ $width ]['url'] = dorotape_webp_url( (string) $source['url'] );
		}
	}
	return $sources;
}

if ( ! is_admin() ) {
	add_filter( 'wp_get_attachment_image_src', 'dorotape_webp_image_src', 20 );
	add_filter( 'wp_calculate_image_srcset', 'dorotape_webp_srcset', 20 );
}
