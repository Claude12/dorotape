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

// ─── Product page JS data ─────────────────────────────────────────────────────

/**
 * Localise currency and decimal settings for the product page tier table JS.
 * scaffold.js reads dorotapeProduct.currencySymbol and .priceDecimals to format
 * prices when rebuilding the tier table tbody on variation change.
 */
function dorotape_product_js_data(): void {
	if ( ! is_product() ) {
		return;
	}
	wp_localize_script(
		'dorotape-navigation',
		'dorotapeProduct',
		array(
			'currencySymbol' => get_woocommerce_currency_symbol(),
			'priceDecimals'  => wc_get_price_decimals(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'dorotape_product_js_data', 20 );

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


// ─── Variable product discount hint ──────────────────────────────────────────

/**
 * Show a "volume discounts available" nudge on variable products before the
 * user has selected a variation. Rendered at priority 12 so it sits between
 * the price range (10) and the hidden tier table (15).
 * JS hides this once a variation is chosen and restores it on reset.
 */
add_action( 'woocommerce_single_product_summary', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	// Scan all variations to find the best saving % and the qualifying min qty.
	$max_saving  = 0;
	$min_qty_for = 0;
	foreach ( $product->get_children() as $var_id ) {
		$var_obj = wc_get_product( (int) $var_id );
		if ( ! $var_obj instanceof WC_Product_Variation ) {
			continue;
		}
		$base = (float) $var_obj->get_regular_price();
		if ( $base <= 0 ) {
			continue;
		}
		foreach ( dorotape_parse_legacy_tiers( (int) $var_id ) as $tier ) {
			if ( (int) $tier['min_qty'] <= 1 || (float) $tier['tier_price'] <= 0 ) {
				continue;
			}
			$saving = (int) round( ( ( $base - (float) $tier['tier_price'] ) / $base ) * 100 );
			if ( $saving > $max_saving ) {
				$max_saving  = $saving;
				$min_qty_for = (int) $tier['min_qty'];
			}
		}
	}

	if ( $max_saving <= 0 ) {
		return;
	}

	// Build a human-readable label from the product's variation attribute(s).
	$attr_labels = array_map(
		static function ( string $attr_name ): string {
			return wc_attribute_label( $attr_name );
		},
		array_keys( $product->get_variation_attributes() )
	);
	$attr_label = implode( ' / ', $attr_labels );

	printf(
		'<div class="dt-discount-hint" id="dt_discount_hint">'
			. '<span class="dt-discount-hint__badge">%1$s%%</span>'
			. '<span class="dt-discount-hint__body">'
				. '<strong class="dt-discount-hint__headline">%2$s</strong>'
				. '<span class="dt-discount-hint__sub">%3$s</span>'
			. '</span>'
		. '</div>',
		esc_html( $max_saving ),
		esc_html( sprintf(
			/* translators: %d: discount percentage */
			__( 'Save up to %d%% with a volume order', 'dorotape' ),
			$max_saving
		) ),
		esc_html( sprintf(
			/* translators: 1: minimum quantity, 2: attribute label e.g. "Size / Width" */
			__( 'Order %1$dm or more — select a %2$s below to see full pricing', 'dorotape' ),
			$min_qty_for,
			$attr_label
		) )
	);
}, 12 );

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
 * Detect which variation is selected via URL attribute parameters (server-side).
 * Returns null when the URL does not fully specify a variation.
 *
 * @param WC_Product_Variable $product
 * @return WC_Product_Variation|null
 */
function dorotape_get_selected_variation( WC_Product_Variable $product ): ?WC_Product_Variation {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$attr_names = array_keys( $product->get_variation_attributes() );
	if ( empty( $attr_names ) ) {
		return null;
	}
	$selected = array();
	foreach ( $attr_names as $attr_name ) {
		$key = 'attribute_' . $attr_name;
		if ( ! empty( $_GET[ $key ] ) ) {
			$selected[ $attr_name ] = sanitize_title( wp_unslash( $_GET[ $key ] ) );
		}
	}
	// phpcs:enable
	if ( count( $selected ) !== count( $attr_names ) ) {
		return null;
	}
	$data_store = WC_Data_Store::load( 'product' );
	$var_id     = $data_store->find_matching_product_variation( $product, $selected );
	if ( ! $var_id ) {
		return null;
	}
	$variation = wc_get_product( $var_id );
	return ( $variation instanceof WC_Product_Variation ) ? $variation : null;
}

/**
 * Render a tier pricing table for variable products directly in PHP.
 *
 * The table is always present in the HTML — no JS event-timing dependency.
 * Initial prices come from the URL-selected variation, or the first variation
 * with tiers if no URL selection is present.
 *
 * All variation tier data is also embedded as data-variation-tiers JSON so
 * that the lightweight JS handler can swap prices when the user changes the
 * variation dropdown — still zero AJAX.
 */
add_action( 'woocommerce_single_product_summary', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	// Build the tier map for all variations, loading each object once.
	// Includes base_price so JS never has to rely on display_regular_price parsing.
	$variation_tiers = array(); // { varId: { base_price, tiers: [{min_qty, tier_price}] } }
	$var_objects     = array(); // cached WC_Product_Variation objects
	foreach ( $product->get_children() as $var_id ) {
		$tiers = array_values( array_filter(
			dorotape_parse_legacy_tiers( (int) $var_id ),
			static function ( array $t ): bool { return (int) $t['min_qty'] > 1; }
		) );
		if ( empty( $tiers ) ) {
			continue;
		}
		$var_obj = wc_get_product( (int) $var_id );
		if ( ! $var_obj instanceof WC_Product_Variation ) {
			continue;
		}
		$var_objects[ (int) $var_id ]  = $var_obj;
		$variation_tiers[ (int) $var_id ] = array(
			'base_price' => (float) $var_obj->get_regular_price(),
			'tiers'      => array_map(
				static function ( array $t ): array {
					return array(
						'min_qty'    => (int) $t['min_qty'],
						'tier_price' => (float) $t['tier_price'],
					);
				},
				$tiers
			),
		);
	}
	if ( empty( $variation_tiers ) ) {
		return;
	}

	// Prefer URL-selected variation; fall back to the first variation with tiers.
	$url_var = dorotape_get_selected_variation( $product );
	$init_id = ( $url_var && isset( $variation_tiers[ $url_var->get_id() ] ) )
		? $url_var->get_id()
		: (int) array_key_first( $variation_tiers );
	$init_var = $var_objects[ $init_id ];

	$init_tiers = $variation_tiers[ $init_id ]['tiers'];
	$base_price = $variation_tiers[ $init_id ]['base_price'];

	usort( $init_tiers, static function ( array $a, array $b ): int {
		return (int) $a['min_qty'] - (int) $b['min_qty'];
	} );

	echo '<div class="dt-tier-pricing" id="dt_variable_tier_table"'
		. ' data-variation-tiers="' . esc_attr( wp_json_encode( $variation_tiers ) ) . '">';
	echo '<h3 class="dt-tier-pricing__title">' . esc_html__( 'Quantity Pricing', 'dorotape' ) . '</h3>';
	echo '<table class="dt-tier-pricing__table">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Quantity', 'dorotape' ) . '</th>';
	echo '<th>' . esc_html__( 'Price per metre', 'dorotape' ) . '</th>';
	echo '<th>' . esc_html__( 'Save', 'dorotape' ) . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	echo '<tr class="dt-tier-pricing__row dt-tier-pricing__row--base" data-min="0">';
	echo '<td>1m+</td>';
	echo '<td>' . wp_kses_post( wc_price( $base_price ) ) . '/m</td>';
	echo '<td>&mdash;</td>';
	echo '</tr>';

	foreach ( $init_tiers as $tier ) {
		$min_qty    = (int) $tier['min_qty'];
		$tier_price = (float) $tier['tier_price'];
		if ( $tier_price <= 0 ) {
			continue;
		}
		$saving = $base_price > 0
			? (int) round( ( ( $base_price - $tier_price ) / $base_price ) * 100 )
			: 0;
		echo '<tr class="dt-tier-pricing__row" data-min="' . esc_attr( $min_qty ) . '">';
		echo '<td>' . esc_html( $min_qty . 'm+' ) . '</td>';
		echo '<td>' . wp_kses_post( wc_price( $tier_price ) ) . '/m</td>';
		echo '<td>' . ( $saving > 0 ? esc_html( $saving . '% off' ) : '&mdash;' ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '<p class="dt-tier-pricing__note">' . esc_html__( 'Quantity discounts apply automatically. Enter your required length in the quantity field.', 'dorotape' ) . '</p>';
	echo '</div>';
}, 15 );


// ─── Custom product tabs (from Kryptronic tabonecontent / tabtwocontent) ─────

/**
 * Register up to two custom tabs per product if _dt_tab_one_content meta is set.
 * Content was imported from Kryptronic tabonecontent / tabtwocontent fields.
 */
add_filter( 'woocommerce_product_tabs', function ( array $tabs ): array {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return $tabs;
	}
	$id = $product->get_id();

	$tab_defs = array(
		array(
			'name_key'    => '_dt_tab_one_name',
			'content_key' => '_dt_tab_one_content',
			'tab_id'      => 'dt_tab_one',
			'priority'    => 50,
		),
		array(
			'name_key'    => '_dt_tab_two_name',
			'content_key' => '_dt_tab_two_content',
			'tab_id'      => 'dt_tab_two',
			'priority'    => 55,
		),
	);

	foreach ( $tab_defs as $def ) {
		$content = get_post_meta( $id, $def['content_key'], true );
		if ( ! $content ) {
			continue;
		}
		$title = get_post_meta( $id, $def['name_key'], true ) ?: esc_html__( 'Additional Information', 'dorotape' );
		$tabs[ $def['tab_id'] ] = array(
			'title'    => esc_html( $title ),
			'priority' => $def['priority'],
			'callback' => static function () use ( $content ) {
				echo '<div class="dt-product-tab">';
				echo wp_kses_post( $content );
				echo '</div>';
			},
		);
	}

	return $tabs;
} );

// ─── Grouped variation dropdown ───────────────────────────────────────────────

/**
 * Tidy a Kryptronic option-group prefix into an optgroup label.
 * "or 1220mm wide here" → "1220mm wide", "Select here for 1520mm wide" →
 * "1520mm wide". Unrecognised prefixes pass through unchanged.
 *
 * @param string $prefix
 * @return string
 */
function dorotape_optgroup_label( string $prefix ): string {
	$prefix = preg_replace( '/^or\s+/i', '', trim( $prefix ) );
	$prefix = preg_replace( '/^select here for\s+/i', '', $prefix );
	$prefix = preg_replace( '/\s+here$/i', '', $prefix );
	return ucfirst( trim( $prefix ) );
}

/**
 * Rebuild long variation dropdowns with <optgroup> sections.
 *
 * Migrated products flatten several Kryptronic option groups into one
 * attribute whose labels keep the group prefix: "or 1220mm wide here — part
 * roll - price per metre". With 8–19 such options (Rainbow Silver 7901,
 * F-Sign Platinum) the flat select is hard to scan. This splits each label
 * on the em-dash into group → option and emits optgroups; labels without a
 * prefix are listed first. Selects with fewer than two groups are untouched.
 */
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', function ( string $html, array $args ): string {
	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];

	if ( empty( $options ) || ! $product instanceof WC_Product || count( $options ) < 4 ) {
		return $html;
	}

	// slug => display label, preserving term order for taxonomy attributes.
	$choices = array();
	if ( taxonomy_exists( $attribute ) ) {
		foreach ( wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) ) as $term ) {
			if ( in_array( $term->slug, $options, true ) ) {
				$choices[ $term->slug ] = $term->name;
			}
		}
		// Fall back to raw slugs for values without a term.
		foreach ( $options as $slug ) {
			if ( ! isset( $choices[ $slug ] ) ) {
				$choices[ $slug ] = $slug;
			}
		}
	} else {
		foreach ( $options as $option ) {
			$choices[ $option ] = $option;
		}
	}

	// Split "group — option" labels. \x{2014} = em dash.
	$grouped   = array(); // group label => [slug => option label]
	$ungrouped = array();
	foreach ( $choices as $slug => $label ) {
		$parts = preg_split( '/\s+\x{2014}\s+/u', $label, 2 );
		if ( 2 === count( $parts ) ) {
			$grouped[ dorotape_optgroup_label( $parts[0] ) ][ $slug ] = trim( $parts[1] );
		} else {
			$ungrouped[ $slug ] = $label;
		}
	}

	if ( count( $grouped ) < 2 ) {
		return $html; // Nothing worth grouping.
	}

	$name     = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
	$id       = $args['id'] ? $args['id'] : sanitize_title( $attribute );
	$class    = $args['class'];
	$selected = $args['selected'];

	$show_option_none      = (bool) $args['show_option_none'];
	$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' );

	$out = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" '
		. 'data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" '
		. 'data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
	$out .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

	$render_option = static function ( string $slug, string $label ) use ( $selected ): string {
		return '<option value="' . esc_attr( $slug ) . '" '
			. selected( $selected, $slug, false ) . '>'
			. esc_html( $label ) . '</option>';
	};

	foreach ( $ungrouped as $slug => $label ) {
		$out .= $render_option( $slug, $label );
	}
	foreach ( $grouped as $group_label => $group_options ) {
		$out .= '<optgroup label="' . esc_attr( $group_label ) . '">';
		foreach ( $group_options as $slug => $label ) {
			$out .= $render_option( $slug, $label );
		}
		$out .= '</optgroup>';
	}

	$out .= '</select>';
	return $out;
}, 10, 2 );

// ─── Product search includes SKUs ─────────────────────────────────────────────

/**
 * Frontend product searches also match SKU / item number.
 *
 * Trade customers search by item number (900E, HOOKB20, 13117) as they did on
 * the old site. WordPress search only scans title/content, so extend the WHERE
 * with an EXISTS on _sku meta — matching the product's own SKU or any of its
 * variations' SKUs (variation match surfaces the parent product).
 */
add_filter( 'posts_search', function ( string $search, WP_Query $query ): string {
	global $wpdb;

	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || '' === $search ) {
		return $search;
	}
	if ( 'product' !== $query->get( 'post_type' ) ) {
		return $search;
	}

	$term = $query->get( 's' );
	if ( '' === trim( (string) $term ) ) {
		return $search;
	}
	$like = '%' . $wpdb->esc_like( trim( $term ) ) . '%';

	$sku_clause = $wpdb->prepare(
		" OR EXISTS (
			SELECT 1 FROM {$wpdb->postmeta} skum
			JOIN {$wpdb->posts} skup ON skup.ID = skum.post_id
			WHERE skum.meta_key = '_sku' AND skum.meta_value LIKE %s
			AND ( skup.ID = {$wpdb->posts}.ID OR skup.post_parent = {$wpdb->posts}.ID )
		) ",
		$like
	);

	// Splice the SKU clause into WP's search-terms block so it ORs with the
	// combined title/excerpt/content conditions. The block is followed by an
	// " AND (posts.post_password = '')" clause — cut there, not at the end,
	// or the OR would land inside the password check.
	$pw_marker = " AND ({$wpdb->posts}.post_password";
	$pw_pos    = strpos( $search, $pw_marker );
	$head      = false === $pw_pos ? $search : substr( $search, 0, $pw_pos );
	$tail      = false === $pw_pos ? '' : substr( $search, $pw_pos );

	$pos = strrpos( $head, ')' );
	if ( false === $pos ) {
		return $search;
	}
	return substr( $head, 0, $pos ) . $sku_clause . substr( $head, $pos ) . $tail;
}, 10, 2 );
