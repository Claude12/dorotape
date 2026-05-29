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
		return;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => __( 'Product not found.', 'dorotape' ) ), 404 );
		return;
	}

	// Width and roll options are no longer managed as product-level fields.
	// This endpoint is now called only for variation IDs (variable product tier table).
	$raw_tiers = dorotape_parse_legacy_tiers( $product_id );
	$tier_data = array();
	foreach ( $raw_tiers as $tier ) {
		if ( (int) $tier['min_qty'] <= 1 ) {
			continue; // Skip base-rate row — JS renders it explicitly.
		}
		$tier_data[] = array(
			'min_qty'    => (int) $tier['min_qty'],
			'tier_price' => (float) $tier['tier_price'],
		);
	}
	usort( $tier_data, static function ( array $a, array $b ): int {
		return $a['min_qty'] - $b['min_qty'];
	} );

	wp_send_json_success(
		array(
			'width_options' => array(),
			'roll_options'  => array(),
			'price_tiers'   => $tier_data,
		)
	);
}
add_action( 'wp_ajax_dorotape_get_product_options', 'dorotape_ajax_get_product_options' );
add_action( 'wp_ajax_nopriv_dorotape_get_product_options', 'dorotape_ajax_get_product_options' );

// ─── Category product count ───────────────────────────────────────────────────

// Show product count only on leaf categories (no child categories).
// On intermediate categories the count is misleading — clicking shows another
// subcategory page, not a product grid.
add_filter( 'woocommerce_subcategory_count_html', function ( string $html, object $category ): string {
	$children = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $category->term_id,
		'hide_empty' => true,
		'fields'     => 'ids',
	) );
	if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
		return ''; // Has child categories — suppress the count.
	}
	return $html; // Leaf category — show the count.
}, 10, 2 );

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


// ─── Single product tier table ────────────────────────────────────────────────

/**
 * Render a "Quantity Pricing" table on the single product page when the product
 * has price_tiers configured. Output is plain HTML so it degrades without JS.
 * Placed at priority 15 (after title/price, before add-to-cart form).
 */
add_action( 'woocommerce_single_product_summary', function (): void {
	global $post, $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	// Variable products show a price range (£11–£22) natively; tier pricing is
	// per-variation and complex to display before a variation is selected.
	if ( $product->is_type( 'variable' ) ) {
		return;
	}

	$tiers = dorotape_parse_legacy_tiers( $post->ID );
	// Strip the base-rate row (min_qty=1) — we render that explicitly.
	$tiers = array_values( array_filter( $tiers, static function ( array $t ): bool {
		return (int) $t['min_qty'] > 1;
	} ) );
	if ( empty( $tiers ) ) {
		return;
	}

	usort( $tiers, static function ( array $a, array $b ): int {
		return (int) $a['min_qty'] - (int) $b['min_qty'];
	} );

	$base_price = (float) $product->get_regular_price();

	echo '<div class="dt-tier-pricing">';
	echo '<h3 class="dt-tier-pricing__title">' . esc_html__( 'Quantity Pricing', 'dorotape' ) . '</h3>';
	echo '<table class="dt-tier-pricing__table">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Quantity', 'dorotape' ) . '</th>';
	echo '<th>' . esc_html__( 'Price per metre', 'dorotape' ) . '</th>';
	echo '<th>' . esc_html__( 'Save', 'dorotape' ) . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	// First row: standard price (1m+). data-min="0" = no tier active.
	echo '<tr class="dt-tier-pricing__row dt-tier-pricing__row--base" data-min="0">';
	echo '<td>1m+</td>';
	echo '<td>' . wp_kses_post( wc_price( $base_price ) ) . '/m</td>';
	echo '<td>—</td>';
	echo '</tr>';

	foreach ( $tiers as $tier ) {
		$min_qty    = (int) $tier['min_qty'];
		$tier_price = (float) ( $tier['tier_price'] ?? 0 );
		if ( $tier_price <= 0 ) {
			continue;
		}
		$saving = $base_price > 0 ? round( ( ( $base_price - $tier_price ) / $base_price ) * 100 ) : 0;

		echo '<tr class="dt-tier-pricing__row" data-min="' . esc_attr( $min_qty ) . '">';
		echo '<td>' . esc_html( $min_qty . 'm+' ) . '</td>';
		echo '<td>' . wp_kses_post( wc_price( $tier_price ) ) . '/m</td>';
		echo '<td>' . ( $saving > 0 ? esc_html( $saving . '% off' ) : '—' ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '<p class="dt-tier-pricing__note">' . esc_html__( 'Quantity discounts apply automatically. Enter your required length in the quantity field.', 'dorotape' ) . '</p>';
	echo '</div>';
}, 15 );

/**
 * Variable product: render a tier pricing placeholder at the same priority.
 * JS (initVariableProductTiers) replaces the content with the real table once
 * a variation is selected, and restores the placeholder when it is cleared.
 * The placeholder ensures something meaningful is shown without JS too.
 */
add_action( 'woocommerce_single_product_summary', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return;
	}
	echo '<div class="dt-tier-pricing" id="dt_variable_tier_placeholder">';
	echo '<h3 class="dt-tier-pricing__title">' . esc_html__( 'Quantity Pricing', 'dorotape' ) . '</h3>';
	echo '<p class="dt-tier-pricing__note">' . esc_html__( 'Quantity discounts available on this product. Select a size above to view pricing.', 'dorotape' ) . '</p>';
	echo '</div>';
}, 15 );

