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

// ─── ACF locale fix ───────────────────────────────────────────────────────────

// On servers where PHP locale uses comma as decimal separator, ACF number fields
// can receive "61,87" from the browser and (float)'61,87' silently truncates to 61.
// Normalise all dorotape price sub-fields at save time: replace any comma with a dot
// before ACF casts to float, so £61.87 is stored correctly regardless of locale.
$dorotape_price_field_keys = array(
	'field_dorotape_width_price',
	'field_dorotape_roll_price',
	'field_dorotape_tier_price',
);
foreach ( $dorotape_price_field_keys as $_key ) {
	add_filter(
		'acf/update_value/key=' . $_key,
		function ( $value ) {
			if ( is_string( $value ) ) {
				$value = str_replace( ',', '.', $value );
			}
			return $value;
		},
		5
	);
}
unset( $_key );

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
				'price_per_metre' => (float) ( $row['price_per_metre'] ?? 0 ),
			);
		}
	}

	$roll_data = array();
	if ( is_array( $roll_options ) ) {
		foreach ( $roll_options as $row ) {
			$roll_data[] = array(
				'length'     => (float) $row['roll_length'],
				'label'      => sanitize_text_field( $row['roll_label'] ),
				'roll_price' => (float) ( $row['roll_price'] ?? 0 ),
			);
		}
	}

	$price_tiers = get_field( 'price_tiers', $product_id );
	$tier_data   = array();
	if ( is_array( $price_tiers ) ) {
		foreach ( $price_tiers as $tier ) {
			$tier_data[] = array(
				'min_qty'    => (int) $tier['min_qty'],
				'tier_price' => (float) ( $tier['tier_price'] ?? 0 ),
			);
		}
		// Sort ascending for display.
		usort( $tier_data, static function ( array $a, array $b ): int {
			return $a['min_qty'] - $b['min_qty'];
		} );
	}

	wp_send_json_success(
		array(
			'width_options' => $width_data,
			'roll_options'  => $roll_data,
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

	$product_id   = $product->get_id();
	$roll_options = get_field( 'roll_options', $product_id );

	// Detect per-metre products:
	//   (a) Magnetic imports flag _dorotape_per_metre = '1'.
	//   (b) Ritrama products have a roll_options row with roll_length == 1 (the "Per Metre" option).
	$is_per_metre = '1' === get_post_meta( $product_id, '_dorotape_per_metre', true );
	if ( ! $is_per_metre && is_array( $roll_options ) ) {
		foreach ( $roll_options as $option ) {
			if ( abs( (float) ( $option['roll_length'] ?? 0 ) - 1.0 ) < 0.001 ) {
				$is_per_metre = true;
				break;
			}
		}
	}

	if ( $is_per_metre ) {
		$price_html .= '<span class="dt-per-metre">/m</span>';
	}

	// Append full-roll price line when the product has roll sizes > 1 m.
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
	$roll_price = (float) ( $full_roll['roll_price'] ?? 0 );

	// Blank/zero roll_price means per-metre — nothing to append.
	if ( $roll_price <= 0 ) {
		return $price_html;
	}

	// Skip when the stored roll price equals the base (single fixed-roll products
	// already show the full-roll price as their WC price — no need to repeat it).
	if ( abs( $roll_price - $base_price ) < 0.01 ) {
		return $price_html;
	}

	return $price_html
		. '<span class="dt-archive-roll-price">'
		. esc_html( $full_roll['roll_label'] ) . ': '
		. wc_price( $roll_price )
		. '</span>';
}, 10, 2 );

// ─── Archive "Select Options" button ─────────────────────────────────────────

/**
 * Replace the "Add to cart" button with a "Select Options →" link on archive
 * pages for products that have configurable width or roll options.
 * Products with no ACF options (e.g. sample cards) keep the normal button.
 */
add_filter( 'woocommerce_loop_add_to_cart_link', function ( string $html, WC_Product $product ): string {
	// Variable products already render a "Select Options" link natively.
	if ( $product->is_type( 'variable' ) ) {
		return $html;
	}

	$id            = $product->get_id();
	$width_options = get_field( 'width_options', $id );

	$has_options = ( ! empty( $width_options ) && count( $width_options ) > 1 )
		|| ! empty( get_field( 'roll_options', $id ) );

	if ( ! $has_options ) {
		return $html;
	}

	return sprintf(
		'<a href="%s" class="button dt-select-options-btn">%s</a>',
		esc_url( get_permalink( $id ) ),
		esc_html__( 'Select Options →', 'dorotape' )
	);
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

	// ACF price_tiers first (our managed products); fall back to legacy _price_tiers meta.
	$tiers = get_field( 'price_tiers', $post->ID );
	if ( ! is_array( $tiers ) || empty( $tiers ) ) {
		$tiers = dorotape_parse_legacy_tiers( $post->ID );
	}
	if ( empty( $tiers ) ) {
		return;
	}

	// Sort ascending by min_qty for display.
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
	echo '<p class="dt-tier-pricing__note">' . esc_html__( 'Quantity discounts apply to per-metre orders. Enter your required length in the quantity field.', 'dorotape' ) . '</p>';
	echo '</div>';
}, 15 );

// ─── Downloads tab ────────────────────────────────────────────────────────────

/**
 * Add a Downloads tab to the single product page when at least one of the
 * three document fields (data_sheet, application_guide, safety_data_sheet)
 * has a file attached. Tab is hidden entirely when no files exist.
 */
add_filter( 'woocommerce_product_tabs', function ( array $tabs ): array {
	if ( ! is_product() ) {
		return $tabs;
	}

	global $post;
	$id = $post->ID;

	$files = array(
		__( 'Technical Data Sheet', 'dorotape' ) => get_field( 'data_sheet', $id ),
		__( 'Application Guide',    'dorotape' ) => get_field( 'application_guide', $id ),
		__( 'Safety Data Sheet',    'dorotape' ) => get_field( 'safety_data_sheet', $id ),
	);

	// Only add the tab if at least one file is attached.
	$files = array_filter( $files );
	if ( empty( $files ) ) {
		return $tabs;
	}

	$tabs['dorotape_downloads'] = array(
		'title'    => __( 'Downloads', 'dorotape' ),
		'priority' => 40,
		'callback' => function () use ( $files ) {
			echo '<h2>' . esc_html__( 'Downloads', 'dorotape' ) . '</h2>';
			echo '<ul class="dt-downloads-list">';
			foreach ( $files as $label => $file ) {
				$ext      = strtoupper( pathinfo( $file['filename'], PATHINFO_EXTENSION ) );
				$size_kb  = $file['filesize'] ? round( $file['filesize'] / 1024 ) . ' KB' : '';
				echo '<li class="dt-download-item">';
				echo '<span class="dt-download-icon">' . esc_html( $ext ) . '</span>';
				echo '<span class="dt-download-meta">';
				echo '<span class="dt-download-label">' . esc_html( $label ) . '</span>';
				if ( $size_kb ) {
					echo '<span class="dt-download-size">' . esc_html( $size_kb ) . '</span>';
				}
				echo '</span>';
				echo '<a href="' . esc_url( $file['url'] ) . '" class="button dt-download-btn" target="_blank" rel="noopener">'
					. esc_html__( 'View', 'dorotape' ) . '</a>';
				echo '</li>';
			}
			echo '</ul>';
		},
	);

	return $tabs;
} );
