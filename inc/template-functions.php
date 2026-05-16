<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package dorotape
 */

function dorotape_body_classes( $classes ) {
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}
	return $classes;
}
add_filter( 'body_class', 'dorotape_body_classes' );
