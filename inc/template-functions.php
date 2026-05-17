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
	return $classes;
}
add_filter( 'body_class', 'dorotape_body_classes' );
