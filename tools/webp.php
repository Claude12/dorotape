<?php
/**
 * Make WebP copies of uploaded images, and sub-sizes where they are missing.
 *
 * Usage, from the WordPress root or anywhere:
 *   php wp-content/themes/dorotape/tools/webp.php [--dry-run] [--no-sizes]
 *       [--ids=12,34] [--limit=N] [--quality=80]
 *
 * For every JPEG, PNG and WebP in the media library (a WebP upload gets a
 * copy named file.webp.webp when re-encoding it saves enough, since some were
 * saved with almost no compression):
 *
 *  1. Sub-sizes. An image that has none (imported files, such as the old
 *     Kryptronic category tiles) gets WordPress's normal set, so it can have a
 *     srcset. The original file is kept; only new files and the attachment's
 *     metadata are written. Skip this with --no-sizes.
 *  2. WebP. The original and each sub-size get a copy next to them named
 *     file.png.webp, which inc/webp.php serves in its place. A copy is only
 *     kept if it is at least 10% smaller than the file it replaces, and it is
 *     only remade when the source is newer than it.
 *
 * It uses WordPress's own image editor when that can write WebP, and the
 * cwebp binary when it cannot (set CWEBP=/path/to/cwebp if it is not on the
 * PATH). Nothing here is specific to one site.
 *
 * Undo: delete the *.webp files it made (they are listed in its output), and
 * the site serves the originals again. Sub-sizes are ordinary WordPress
 * sub-sizes and can stay.
 *
 * @package dorotape
 */

if ( PHP_SAPI !== 'cli' ) {
	exit;
}

$opts = getopt( '', array( 'dry-run', 'no-sizes', 'ids:', 'limit:', 'quality:' ) );

require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$dry     = isset( $opts['dry-run'] );
$sizes   = ! isset( $opts['no-sizes'] );
$limit   = isset( $opts['limit'] ) ? (int) $opts['limit'] : 0;
$quality = isset( $opts['quality'] ) ? max( 1, min( 100, (int) $opts['quality'] ) ) : 80;
$ids     = isset( $opts['ids'] ) ? array_filter( array_map( 'intval', explode( ',', $opts['ids'] ) ) ) : array();

$editor_webp = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
$cwebp       = getenv( 'CWEBP' ) ?: trim( (string) shell_exec( 'command -v cwebp 2>/dev/null' ) );

if ( ! $editor_webp && ( ! $cwebp || ! is_executable( $cwebp ) ) ) {
	fwrite( STDERR, "Neither the WordPress image editor nor cwebp can write WebP here. Set CWEBP=/path/to/cwebp.\n" );
	exit( 1 );
}

echo 'WebP encoder: ' . ( $editor_webp ? 'WordPress image editor' : $cwebp ) . ", quality $quality" . ( $dry ? ', DRY RUN' : '' ) . "\n";

/**
 * Write a WebP copy of $src to $dest. Returns its size in bytes, or 0.
 */
function dorotape_tools_encode_webp( string $src, string $dest, int $quality, bool $editor_webp, string $cwebp ): int {
	if ( $editor_webp ) {
		$editor = wp_get_image_editor( $src );
		if ( is_wp_error( $editor ) ) {
			return 0;
		}
		$editor->set_quality( $quality );
		$saved = $editor->save( $dest, 'image/webp' );
		// The editor may add its own extension; make sure the copy lands at $dest.
		if ( is_wp_error( $saved ) ) {
			return 0;
		}
		if ( $saved['path'] !== $dest && is_file( $saved['path'] ) ) {
			rename( $saved['path'], $dest );
		}
	} else {
		$cmd = escapeshellarg( $cwebp ) . ' -quiet -mt -q ' . $quality . ' -m 6 -metadata none '
			. escapeshellarg( $src ) . ' -o ' . escapeshellarg( $dest ) . ' 2>&1';
		exec( $cmd, $out, $code );
		if ( 0 !== $code ) {
			@unlink( $dest );
			return 0;
		}
	}
	clearstatcache( true, $dest );
	return is_file( $dest ) ? (int) filesize( $dest ) : 0;
}

$query = array(
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ),
	'posts_per_page' => $limit > 0 ? $limit : -1,
	'fields'         => 'ids',
	'orderby'        => 'ID',
	'order'          => 'ASC',
);
if ( $ids ) {
	$query['post__in'] = $ids;
}
$attachments = get_posts( $query );

$stats = array(
	'attachments' => count( $attachments ),
	'missing'     => 0,
	'sized'       => 0,
	'made'        => 0,
	'kept_orig'   => 0,
	'fresh'       => 0,
	'before'      => 0,
	'after'       => 0,
);

foreach ( $attachments as $id ) {
	$file = get_attached_file( $id );
	if ( ! $file || ! is_file( $file ) ) {
		++$stats['missing'];
		continue;
	}

	$meta = wp_get_attachment_metadata( $id );

	// 1. Sub-sizes for an image that has none and is big enough to need them.
	// A WebP original can only be resized where the editor reads WebP.
	$can_size = $editor_webp || 'image/webp' !== get_post_mime_type( $id );
	if ( $sizes && $can_size && is_array( $meta ) && empty( $meta['sizes'] ) && ! empty( $meta['width'] ) && (int) $meta['width'] > 300 ) {
		if ( $dry ) {
			echo "would size  #$id " . wp_basename( $file ) . "\n";
		} else {
			$new = wp_generate_attachment_metadata( $id, $file );
			if ( is_array( $new ) && ! empty( $new['sizes'] ) ) {
				wp_update_attachment_metadata( $id, $new );
				$meta = $new;
				++$stats['sized'];
				echo "sized       #$id " . wp_basename( $file ) . ' (' . count( $new['sizes'] ) . " sizes)\n";
			}
		}
	}

	// 2. WebP copies of the original and every sub-size.
	$files = array( $file );
	if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
		$dir = dirname( $file );
		foreach ( $meta['sizes'] as $size ) {
			if ( ! empty( $size['file'] ) ) {
				$files[] = $dir . '/' . $size['file'];
			}
		}
	}

	foreach ( array_unique( $files ) as $src ) {
		if ( ! is_file( $src ) || ! preg_match( '/\.(jpe?g|png|webp)$/i', $src ) ) {
			continue;
		}
		$dest = $src . '.webp';
		if ( is_file( $dest ) && filemtime( $dest ) >= filemtime( $src ) ) {
			++$stats['fresh'];
			continue;
		}

		$orig = (int) filesize( $src );
		if ( $dry ) {
			echo "would webp  " . str_replace( ABSPATH, '', $src ) . ' (' . round( $orig / 1024 ) . "KB)\n";
			continue;
		}

		$tmp  = $dest . '.tmp.webp';
		$size = dorotape_tools_encode_webp( $src, $tmp, $quality, $editor_webp, (string) $cwebp );
		if ( $size && $size < $orig * 0.9 ) {
			rename( $tmp, $dest );
			++$stats['made'];
			$stats['before'] += $orig;
			$stats['after']  += $size;
			echo 'webp        ' . str_replace( ABSPATH, '', $dest ) . ' ' . round( $orig / 1024 ) . 'KB -> ' . round( $size / 1024 ) . "KB\n";
		} else {
			@unlink( $tmp );
			// A stale copy that is no longer worth it must not keep being served.
			if ( is_file( $dest ) ) {
				unlink( $dest );
			}
			++$stats['kept_orig'];
		}
	}
}

printf(
	"\n%d attachments, %d files missing on disk, %d given sub-sizes.\n%d WebP copies made (%s -> %s), %d already up to date, %d left as the original (WebP not 10%% smaller).\n",
	$stats['attachments'],
	$stats['missing'],
	$stats['sized'],
	$stats['made'],
	size_format( $stats['before'], 1 ),
	size_format( $stats['after'], 1 ),
	$stats['fresh'],
	$stats['kept_orig']
);
