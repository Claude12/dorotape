<?php
/**
 * WooCommerce compatibility and hooks
 *
 * @package dorotape
 */

// Product grid defaults — 4 columns, 24 per page
add_filter( 'loop_shop_per_page', function () { return 24; }, 20 );
add_filter( 'loop_shop_columns', function () { return 4; } );

// Category archive pages show subcategories when they exist; fall back to products
// on leaf categories (Ritrama F-Sign Platinum Range, Magnetic Ferrous Vinyl, etc.).
// Overrides the WooCommerce DB option so it can't be accidentally changed via Settings UI.
add_filter( 'pre_option_woocommerce_category_archive_display', function () {
	return 'subcategories';
} );

// Main Shop page also shows top-level categories rather than a flat product list.
add_filter( 'pre_option_woocommerce_shop_page_display', function () {
	return 'subcategories';
} );

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

	$data = array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'dorotape_product_options' ),
	);

	// Pass the raw float price on product pages so JS never has to parse the
	// formatted display string (which varies with locale decimal separators).
	if ( is_product() ) {
		global $post;
		$product = wc_get_product( $post->ID );
		if ( $product ) {
			$data['basePrice'] = (float) $product->get_regular_price();
		}
	}

	wp_localize_script( 'dorotape-navigation', 'dorotapeProduct', $data );
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

// ─── Colour Swatch ────────────────────────────────────────────────────────────

/**
 * Replace the WooCommerce placeholder image with a CSS colour swatch when:
 *  1. The product has no uploaded image.
 *  2. A colour_hex post meta value exists (set by the Ritrama import).
 * Degrades gracefully — products with real images or no hex are unaffected.
 */
add_filter( 'woocommerce_product_get_image', function ( string $html, WC_Product $product ): string {
	if ( $product->get_image_id() ) {
		return $html;
	}
	$hex = get_post_meta( $product->get_id(), 'colour_hex', true );
	if ( ! $hex ) {
		return $html;
	}
	return '<div class="dt-colour-swatch" style="background-color:' . esc_attr( $hex ) . ';" '
		. 'role="img" aria-label="' . esc_attr( $product->get_name() ) . '"></div>';
}, 10, 2 );

/**
 * Replace the placeholder in the single-product gallery with a colour swatch.
 * The gallery uses woocommerce_single_product_image_thumbnail_html, not get_image,
 * so a separate hook is needed.
 */
add_filter( 'woocommerce_single_product_image_thumbnail_html', function ( string $html, $post_thumbnail_id ): string {
	if ( $post_thumbnail_id ) {
		return $html; // Product has a real image — leave it alone.
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return $html;
	}
	$hex = get_post_meta( $product->get_id(), 'colour_hex', true );
	if ( ! $hex ) {
		return $html;
	}
	return '<div class="woocommerce-product-gallery__image dt-gallery-colour-swatch">'
		. '<div class="dt-colour-swatch dt-colour-swatch--single" style="background-color:' . esc_attr( $hex ) . ';" '
		. 'role="img" aria-label="' . esc_attr( $product->get_name() ) . '"></div>'
		. '</div>';
}, 10, 2 );

// ─── Archive Roll Price ───────────────────────────────────────────────────────

/**
 * Append the full-roll price to the per-metre price on category/shop archive pages.
 * Mirrors the old site's dual-price display on product cards.
 * Only fires on archive pages — single product pages use the JS-driven price preview.
 */
add_filter( 'woocommerce_get_price_html', function ( string $price_html, WC_Product $product ): string {
	if ( is_product() || is_cart() || is_checkout() || is_account_page() ) {
		return $price_html;
	}
	if ( ! is_product_category() && ! is_shop() && ! is_search() ) {
		return $price_html;
	}

	$roll_options = get_field( 'roll_options', $product->get_id() );
	if ( ! is_array( $roll_options ) || empty( $roll_options ) ) {
		return $price_html;
	}

	// Find the first full-roll option (length > 1 metre).
	$full_roll = null;
	foreach ( $roll_options as $option ) {
		if ( (float) ( $option['roll_length'] ?? 0 ) > 1.0 ) {
			$full_roll = $option;
			break;
		}
	}
	if ( ! $full_roll ) {
		return $price_html;
	}

	$base_price = (float) $product->get_regular_price();
	$roll_price = dorotape_calculate_modified_price(
		$base_price,
		$full_roll['price_modifier_type'] ?? 'none',
		(float) ( $full_roll['price_modifier_value'] ?? 0 )
	);

	// Skip if the roll price is the same as the base (fixed-roll single-size products
	// already display the full-roll price as their base price — no need to repeat it).
	if ( abs( $roll_price - $base_price ) < 0.01 ) {
		return $price_html;
	}

	return $price_html
		. '<span class="dt-archive-roll-price">'
		. esc_html( $full_roll['roll_label'] ) . ': '
		. wc_price( $roll_price )
		. '</span>';
}, 10, 2 );
