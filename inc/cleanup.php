<?php
/**
 * WordPress cleanup and hardening
 *
 * @package dorotape
 */

// ─── Editor ───────────────────────────────────────────────────────────────────

/**
 * Classic editor everywhere, except pages that are genuinely built from blocks.
 *
 * ACF handles normal content editing, so the block editor is off by default and
 * that is deliberate. But Cart and Checkout are the WooCommerce blocks, and a
 * blanket __return_false meant opening Checkout in wp-admin gave a classic
 * textarea full of raw `<!-- wp:woocommerce/checkout -->` comments. One stray
 * keystroke or an autop pass there takes down the checkout, and the damage is
 * not obvious in the editor that caused it.
 *
 * So: post type stays false, which makes classic the default for everything and
 * every new page. The per-post filter runs after it and re-enables blocks only
 * where the content is already block markup.
 *
 * The test is deliberately "is this a WooCommerce block page", not the broader
 * has_blocks(). Four other pages would pass has_blocks() without being block
 * built in any meaningful way: Privacy Policy and Refund and Returns are core
 * boilerplate, and My account and Wishlist are single shortcodes that WordPress
 * wrapped in a wp:shortcode comment. Handing those to the block editor would
 * contradict the rule above for no benefit.
 *
 * Note what this says about My account: it is [woocommerce_my_account], the
 * classic shortcode. The woocommerce_account_* hooks work there. Only Cart and
 * Checkout are block territory.
 */
add_filter( 'use_block_editor_for_post_type', '__return_false' );

add_filter( 'use_block_editor_for_post', function ( bool $use, $post ): bool {
	if ( ! $post instanceof WP_Post ) {
		return $use;
	}

	// Woo's own pages by id, so they stay editable even if the content is emptied.
	if ( function_exists( 'wc_get_page_id' ) ) {
		$woo_pages = array_filter(
			array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ) ),
			fn( $id ) => $id > 0
		);
		if ( in_array( $post->ID, $woo_pages, true ) ) {
			return true;
		}
	}

	// Any other page actually built from Woo blocks, so this keeps holding if
	// more of the store is converted later, or on another site using this theme.
	return has_block( 'woocommerce/cart', $post )
		|| has_block( 'woocommerce/checkout', $post );
}, 10, 2 );

// Remove core block patterns. Nothing here builds pages from patterns, including
// the Woo pages above, which carry two blocks placed by WooCommerce itself.
add_action( 'after_setup_theme', function() {
	remove_theme_support( 'core-block-patterns' );
}, 20 );

// Clean wp_head — remove WordPress fingerprints and unused links
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );

// Strip WP version number from all script and style URLs
function dorotape_strip_wp_ver( string $src ): string {
	if ( strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) !== false ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'dorotape_strip_wp_ver', 9999 );
add_filter( 'script_loader_src', 'dorotape_strip_wp_ver', 9999 );

// Disable XML-RPC (reduces attack surface)
add_filter( 'xmlrpc_enabled', '__return_false' );

// Remove jQuery Migrate on the frontend — not needed for custom theme
function dorotape_remove_jquery_migrate( WP_Scripts $scripts ): void {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
}
add_action( 'wp_default_scripts', 'dorotape_remove_jquery_migrate' );

// Disable comments entirely (not required on this site)
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

function dorotape_disable_comment_feed() {
	if ( is_comment_feed() ) {
		wp_die( esc_html__( 'Comments are closed.', 'dorotape' ), '', array( 'response' => 403 ) );
	}
}
add_action( 'template_redirect', 'dorotape_disable_comment_feed' );

// Remove user endpoints from REST API to prevent account enumeration
add_filter( 'rest_endpoints', function( $endpoints ) {
	if ( isset( $endpoints['/wp/v2/users'] ) ) {
		unset( $endpoints['/wp/v2/users'] );
	}
	if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );
