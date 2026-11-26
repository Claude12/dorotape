<?php
/**
 * Stylesheets that load without holding up the first paint.
 *
 * Some plugin stylesheets style things nobody sees until they act: the
 * product gallery's full-screen lightbox (PhotoSwipe) only shows once someone
 * taps the zoom icon. Loaded the normal way they still block rendering
 * (Lighthouse, 26 Nov 2026: photoswipe on product pages). These are printed
 * with media="print" and switched to "all" once downloaded, which fetches
 * them straight away at full priority for the swap but without making the
 * browser wait for them before painting. A <noscript> copy covers visitors
 * without JavaScript.
 *
 * Only add a handle here if nothing it styles is visible on page load, or the
 * page will flash unstyled.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles to load non-blocking.
 *
 * @return string[]
 */
function dorotape_non_blocking_style_handles(): array {
	return (array) apply_filters(
		'dorotape_non_blocking_styles',
		array(
			'photoswipe',
			'photoswipe-default-skin',
		)
	);
}

/**
 * Rewrite the <link> of each listed handle.
 *
 * @param string $tag    The <link> markup.
 * @param string $handle Style handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Media attribute.
 * @return string
 */
function dorotape_non_blocking_style_tag( $tag, $handle, $href, $media ) {
	if ( is_admin() || ! in_array( $handle, dorotape_non_blocking_style_handles(), true ) ) {
		return $tag;
	}

	// Leave a sheet meant for another medium (print, a media query) alone.
	if ( $media && 'all' !== $media ) {
		return $tag;
	}

	$tags    = new WP_HTML_Tag_Processor( $tag );
	$changed = false;
	while ( $tags->next_tag( 'link' ) ) {
		if ( 'stylesheet' === strtolower( (string) $tags->get_attribute( 'rel' ) ) ) {
			$tags->set_attribute( 'media', 'print' );
			$tags->set_attribute( 'onload', "this.media='all'" );
			$changed = true;
		}
	}

	if ( ! $changed ) {
		return $tag;
	}

	return $tags->get_updated_html() . '<noscript>' . trim( $tag ) . "</noscript>\n";
}
add_filter( 'style_loader_tag', 'dorotape_non_blocking_style_tag', 10, 4 );

/**
 * Keep the lightbox markup hidden until its stylesheet arrives.
 *
 * PhotoSwipe's container sits at the end of every product page and only its
 * own stylesheet hides it. That rule now has to be in the theme's sheet too,
 * or the empty lightbox would show for a moment at the foot of the page.
 * PhotoSwipe adds .pswp--open when it opens, so this never fights it.
 */
function dorotape_hide_lightbox_until_styled(): void {
	if ( wp_style_is( 'photoswipe', 'enqueued' ) && in_array( 'photoswipe', dorotape_non_blocking_style_handles(), true ) ) {
		wp_add_inline_style( 'dorotape-design-system', '.pswp:not(.pswp--open){display:none}' );
	}
}
add_action( 'wp_enqueue_scripts', 'dorotape_hide_lightbox_until_styled', 100 );
