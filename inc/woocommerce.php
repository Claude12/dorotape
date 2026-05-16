<?php
/**
 * WooCommerce compatibility and hooks
 *
 * @package dorotape
 */

// Product grid defaults — 4 columns, 24 per page
add_filter( 'loop_shop_per_page', function () { return 24; }, 20 );
add_filter( 'loop_shop_columns', function () { return 4; } );

// Breadcrumb delimiter and home label
add_filter(
	'woocommerce_breadcrumb_defaults',
	function ( $defaults ) {
		$defaults['delimiter'] = ' &rsaquo; ';
		$defaults['home']      = esc_html__( 'Home', 'dorotape' );
		return $defaults;
	}
);

// Register shop sidebar widget area for product filters (width, roll type, colour)
function dorotape_woocommerce_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Shop Sidebar', 'dorotape' ),
			'id'            => 'sidebar-shop',
			'description'   => esc_html__( 'Product filters and shop widgets.', 'dorotape' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'dorotape_woocommerce_widgets_init' );

// Swap the default WooCommerce sidebar for our shop sidebar
function dorotape_woocommerce_sidebar() {
	if ( is_active_sidebar( 'sidebar-shop' ) ) {
		dynamic_sidebar( 'sidebar-shop' );
	}
}
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_action( 'woocommerce_sidebar', 'dorotape_woocommerce_sidebar', 10 );

// Remove the default WooCommerce page title on archives (we render our own)
add_filter( 'woocommerce_show_page_title', '__return_false' );

// Add WooCommerce body class support
add_filter( 'body_class', function ( $classes ) {
	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		$classes[] = 'woocommerce-active';
	}
	return $classes;
} );
