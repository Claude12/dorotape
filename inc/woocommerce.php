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

// ─── Product Options AJAX ─────────────────────────────────────────────────────

/**
 * Localise the AJAX URL and nonce for product option requests.
 * The product.js script (enqueued in Sprint 2) consumes dorotapeProduct.ajaxUrl
 * and dorotapeProduct.nonce to fetch width/roll options dynamically.
 */
function dorotape_product_ajax_data(): void {
	if ( ! is_product() && ! is_shop() && ! is_product_category() ) {
		return;
	}
	wp_localize_script(
		'dorotape-navigation',
		'dorotapeProduct',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dorotape_product_options' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'dorotape_product_ajax_data', 20 );

/**
 * AJAX: return sanitised width and roll options for a given product ID.
 * Used by the product page JS to populate and conditionally show selectors.
 * Accessible to both logged-in and guest users (products are publicly visible).
 */
function dorotape_ajax_get_product_options(): void {
	check_ajax_referer( 'dorotape_product_options', 'nonce' );

	$product_id = absint( $_POST['product_id'] ?? 0 );

	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid product.', 'dorotape' ) ), 400 );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => __( 'Product not found.', 'dorotape' ) ), 404 );
	}

	$width_options = get_field( 'width_options', $product_id );
	$roll_options  = get_field( 'roll_options', $product_id );

	$width_data = array();
	if ( is_array( $width_options ) ) {
		foreach ( $width_options as $row ) {
			$width_data[] = array(
				'value'          => absint( $row['width_value'] ),
				'label'          => ! empty( $row['width_label'] )
					? sanitize_text_field( $row['width_label'] )
					: absint( $row['width_value'] ) . 'mm',
				'modifier_type'  => sanitize_key( $row['price_modifier_type'] ?? 'none' ),
				'modifier_value' => (float) ( $row['price_modifier_value'] ?? 0 ),
			);
		}
	}

	$roll_data = array();
	if ( is_array( $roll_options ) ) {
		foreach ( $roll_options as $row ) {
			$roll_data[] = array(
				'length'         => (float) $row['roll_length'],
				'label'          => sanitize_text_field( $row['roll_label'] ),
				'modifier_type'  => sanitize_key( $row['price_modifier_type'] ?? 'none' ),
				'modifier_value' => (float) ( $row['price_modifier_value'] ?? 0 ),
			);
		}
	}

	wp_send_json_success(
		array(
			'width_options' => $width_data,
			'roll_options'  => $roll_data,
		)
	);
}
add_action( 'wp_ajax_dorotape_get_product_options', 'dorotape_ajax_get_product_options' );
add_action( 'wp_ajax_nopriv_dorotape_get_product_options', 'dorotape_ajax_get_product_options' );
