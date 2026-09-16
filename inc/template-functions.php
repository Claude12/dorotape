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

/**
 * BEM classes on wp_nav_menu() output.
 *
 * wp_nav_menu() prints bare <li>, <a> and nested <ul> elements, and the only
 * way to style those without targeting raw elements is to give them classes.
 * Any menu call can pass three extra arguments, and WordPress hands unknown
 * arguments straight through to these filters:
 *
 *   'item_class'    => 'site-header__nav-item',
 *   'link_class'    => 'site-header__nav-link',
 *   'submenu_class' => 'site-header__nav-submenu',
 *
 * Items and links below the top level also get a `--sub` modifier, so a
 * dropdown link can be styled apart from the row it hangs off.
 *
 * @param string $base  The BEM class to add.
 * @param int    $depth Menu depth, 0 for the top level.
 * @return string[]
 */
function dorotape_menu_bem_classes( string $base, int $depth ): array {
	$classes = array( $base );
	if ( $depth > 0 ) {
		$classes[] = $base . '--sub';
	}
	return $classes;
}

function dorotape_menu_item_class( $classes, $menu_item, $args, $depth = 0 ) {
	if ( ! empty( $args->item_class ) ) {
		$classes = array_merge( (array) $classes, dorotape_menu_bem_classes( (string) $args->item_class, (int) $depth ) );
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'dorotape_menu_item_class', 10, 4 );

function dorotape_menu_link_class( $atts, $menu_item, $args, $depth = 0 ) {
	if ( ! empty( $args->link_class ) ) {
		$existing      = isset( $atts['class'] ) ? (string) $atts['class'] : '';
		$atts['class'] = trim( $existing . ' ' . implode( ' ', dorotape_menu_bem_classes( (string) $args->link_class, (int) $depth ) ) );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'dorotape_menu_link_class', 10, 4 );

function dorotape_menu_submenu_class( $classes, $args, $depth = 0 ) {
	if ( ! empty( $args->submenu_class ) ) {
		$classes[] = (string) $args->submenu_class;
	}
	return $classes;
}
add_filter( 'nav_menu_submenu_css_class', 'dorotape_menu_submenu_class', 10, 3 );
