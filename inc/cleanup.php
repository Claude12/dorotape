<?php
/**
 * WordPress cleanup and hardening
 *
 * @package dorotape
 */

// Disable Gutenberg block editor for all post types — ACF handles all content editing
add_filter( 'use_block_editor_for_post', '__return_false' );
add_filter( 'use_block_editor_for_post_type', '__return_false' );

// Remove core block patterns (not needed without Gutenberg)
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
