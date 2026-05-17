<?php
/**
 * Admin interface cleanup
 *
 * @package dorotape
 */

// Remove Comments from the admin menu
function dorotape_remove_admin_menus() {
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'dorotape_remove_admin_menus' );

// Remove Comments and WP logo from the admin toolbar
function dorotape_remove_toolbar_items( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
	$wp_admin_bar->remove_node( 'wp-logo' );
}
add_action( 'admin_bar_menu', 'dorotape_remove_toolbar_items', 999 );

// Remove default dashboard widgets that aren't useful for this project
function dorotape_remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'dorotape_remove_dashboard_widgets' );

// Hide admin bar on the frontend for non-admin users
add_filter( 'show_admin_bar', function( $show ) {
	return current_user_can( 'manage_options' ) ? $show : false;
} );

// Point the login logo link to the site homepage
add_filter( 'login_headerurl', function() {
	return home_url();
} );

// Redirect any attempt to access comment-edit page
function dorotape_redirect_comment_admin() {
	global $pagenow;
	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'dorotape_redirect_comment_admin' );
