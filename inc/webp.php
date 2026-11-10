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
 * The copies are made as soon as an image is uploaded, cropped or has its
 * sizes regenerated (dorotape_webp_on_metadata below), and deleted with it.
 * tools/webp.php does the same for the whole library and also gives
 * sub-sizes to images that were imported without any (the old Kryptronic
 * category tiles were 630px PNGs shown at 200px); it only converts what is
 * new or changed, so it is safe to run again at any time.
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

// ── Making the copies ──────────────────────────────────────────────────────
//
// New uploads get their WebP copies straight away, so nobody has to remember
// to run tools/webp.php after adding images. The tool uses the same functions
// and is still there for the whole library or a re-run.

/**
 * What can write WebP here: 'editor' for WordPress's own image editor, a path
 * to the cwebp binary, or '' when neither can (then nothing is converted and
 * the site keeps serving the originals).
 */
function dorotape_webp_encoder(): string {
	static $encoder = null;

	if ( null !== $encoder ) {
		return $encoder;
	}

	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $encoder = 'editor';
	}

	if ( ! function_exists( 'exec' ) ) {
		return $encoder = '';
	}

	$candidates = array( (string) getenv( 'CWEBP' ) );
	if ( function_exists( 'shell_exec' ) ) {
		$candidates[] = trim( (string) shell_exec( 'command -v cwebp 2>/dev/null' ) );
	}
	// The web server's PATH is often shorter than a login shell's.
	array_push( $candidates, '/Applications/XAMPP/xamppfiles/bin/cwebp', '/opt/homebrew/bin/cwebp', '/usr/local/bin/cwebp', '/usr/bin/cwebp' );

	foreach ( array_filter( $candidates ) as $candidate ) {
		if ( @is_file( $candidate ) && @is_executable( $candidate ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- open_basedir can forbid the check.
			return $encoder = $candidate;
		}
	}

	return $encoder = '';
}

/**
 * Write a WebP copy of $src to $dest.
 *
 * @return int The copy's size in bytes, or 0 when it could not be made.
 */
function dorotape_webp_encode( string $src, string $dest, int $quality = 80 ): int {
	$encoder = dorotape_webp_encoder();

	if ( 'editor' === $encoder ) {
		$editor = wp_get_image_editor( $src );
		if ( is_wp_error( $editor ) ) {
			return 0;
		}
		$editor->set_quality( $quality );
		$saved = $editor->save( $dest, 'image/webp' );
		if ( is_wp_error( $saved ) ) {
			return 0;
		}
		// The editor may add its own extension; make sure the copy lands at $dest.
		if ( $saved['path'] !== $dest && is_file( $saved['path'] ) ) {
			rename( $saved['path'], $dest );
		}
	} elseif ( '' !== $encoder ) {
		$cmd = escapeshellarg( $encoder ) . ' -quiet -mt -q ' . $quality . ' -m 6 -metadata none '
			. escapeshellarg( $src ) . ' -o ' . escapeshellarg( $dest ) . ' 2>&1';
		exec( $cmd, $out, $code );
		if ( 0 !== $code ) {
			@unlink( $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return 0;
		}
	} else {
		return 0;
	}

	clearstatcache( true, $dest );
	return is_file( $dest ) ? (int) filesize( $dest ) : 0;
}

/**
 * Make or refresh the WebP copy of one image file (file.jpg -> file.jpg.webp).
 *
 * A copy is only kept when it is at least 10% smaller than the file; a stale
 * copy that is no longer worth it is removed so it stops being served.
 *
 * @return array{status: string, before: int, after: int} status is 'made',
 *               'fresh' (copy already up to date), 'kept' (original is
 *               better), or 'skipped' (not an image file, or no encoder).
 */
function dorotape_webp_copy( string $src, int $quality = 80 ): array {
	$result = array( 'status' => 'skipped', 'before' => 0, 'after' => 0 );

	if ( ! is_file( $src ) || ! preg_match( '/\.(jpe?g|png|webp)$/i', $src ) || '' === dorotape_webp_encoder() ) {
		return $result;
	}

	$dest = $src . '.webp';
	if ( is_file( $dest ) && filemtime( $dest ) >= filemtime( $src ) ) {
		$result['status'] = 'fresh';
		return $result;
	}

	$orig = (int) filesize( $src );
	$tmp  = $dest . '.tmp.webp';
	$size = dorotape_webp_encode( $src, $tmp, $quality );

	if ( $size && $size < $orig * 0.9 ) {
		rename( $tmp, $dest );
		return array( 'status' => 'made', 'before' => $orig, 'after' => $size );
	}

	@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( is_file( $dest ) ) {
		unlink( $dest );
	}
	$result['status'] = 'kept';
	return $result;
}

/**
 * Every file of an attachment that is served: the attached file and its
 * sub-sizes (not the unscaled original WordPress keeps of a big upload).
 *
 * @param string $file Attached file path.
 * @param mixed  $meta Attachment metadata.
 * @return string[]
 */
function dorotape_webp_attachment_files( string $file, $meta ): array {
	$files = array( $file );
	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
		$dir = dirname( $file );
		foreach ( $meta['sizes'] as $size ) {
			if ( ! empty( $size['file'] ) ) {
				$files[] = $dir . '/' . $size['file'];
			}
		}
	}
	return array_values( array_unique( $files ) );
}

/**
 * Make WebP copies whenever an image's files change: on upload, when it is
 * cropped or rotated in the media editor, and when its sizes are regenerated.
 *
 * WordPress saves the metadata once for the original and again after each
 * sub-size during an upload, so each call converts what is new since the last
 * one, and a file whose WebP was not worth keeping is only tried once.
 *
 * @param mixed $meta Attachment metadata being saved.
 * @param int   $id   Attachment ID.
 * @return mixed The metadata, unchanged.
 */
function dorotape_webp_on_metadata( $meta, $id ) {
	static $tried = array();

	if ( ! in_array( get_post_mime_type( (int) $id ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		return $meta;
	}

	$file = get_attached_file( (int) $id );
	if ( ! $file || '' === dorotape_webp_encoder() ) {
		return $meta;
	}

	foreach ( dorotape_webp_attachment_files( $file, $meta ) as $src ) {
		if ( isset( $tried[ $src ] ) ) {
			continue;
		}
		$tried[ $src ] = true;
		dorotape_webp_copy( $src );
	}

	return $meta;
}
add_filter( 'wp_update_attachment_metadata', 'dorotape_webp_on_metadata', 20, 2 );

/**
 * When WordPress deletes an image file, delete its WebP copy with it, so
 * removed media leaves nothing behind.
 *
 * @param string $file Path being deleted.
 * @return string The path, unchanged.
 */
function dorotape_webp_on_delete( $file ) {
	if ( is_string( $file ) && preg_match( '/\.(jpe?g|png|webp)$/i', $file ) && is_file( $file . '.webp' ) ) {
		@unlink( $file . '.webp' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	return $file;
}
add_filter( 'wp_delete_file', 'dorotape_webp_on_delete' );
