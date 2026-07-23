<?php
/**
 * Dynamic pricing — quantity-break tiers from _price_tiers post meta,
 * plus customer-specific discounts from Sage-synced ACF user fields.
 *
 * Data flow:
 *   1. dorotape_dynamic_pricing() fires on every cart recalculation and
 *      applies the quantity-break tier price for the cart line quantity,
 *      followed by any customer-level percentage discount.
 *   2. dorotape_cart_item_display_meta() surfaces the active tier discount
 *      label in the cart, checkout, and email order summaries.
 *   3. dorotape_save_order_item_meta() persists the tier label to the order
 *      record for admin, emails, and Sage sync.
 *
 * Tier storage: _price_tiers post meta on each product or variation.
 * Format: "1-24:11.00;25:9.90" — min[-max]:price segments separated by
 * semicolons. Each variation stores its own tier prices.
 *
 * @package dorotape
 */

// ─── Pricing unit ─────────────────────────────────────────────────────────────

/**
 * The unit a product is sold and tier-priced in: 'metre' (default), 'roll',
 * or 'item'. Stored as _dt_price_unit post meta (parent product only) and
 * CMS-managed via Product data → Advanced. Vinyl sold by the metre keeps the
 * historical default; roll goods (Hook & Loop) and accessories override it so
 * tier tables and discount labels stop saying "per metre".
 *
 * @param int $product_id Parent product ID (variations resolve to parent).
 * @return string 'metre' | 'roll' | 'item'
 */
function dorotape_price_unit( int $product_id ): string {
	$parent = wp_get_post_parent_id( $product_id );
	$unit   = get_post_meta( $parent ? $parent : $product_id, '_dt_price_unit', true );
	return in_array( $unit, array( 'roll', 'item' ), true ) ? $unit : 'metre';
}

/**
 * Display strings for a pricing unit.
 *
 * @param string $unit 'metre' | 'roll' | 'item'
 * @return array {header, suffix, qty_suffix, note}
 */
function dorotape_unit_strings( string $unit ): array {
	switch ( $unit ) {
		case 'roll':
			return array(
				'header'     => __( 'Price per roll', 'dorotape' ),
				'suffix'     => '', // £9.90 reads fine without /roll
				'qty_suffix' => '+',
				'note'       => __( 'Quantity discounts apply automatically based on the number of rolls ordered.', 'dorotape' ),
			);
		case 'item':
			return array(
				'header'     => __( 'Price each', 'dorotape' ),
				'suffix'     => '',
				'qty_suffix' => '+',
				'note'       => __( 'Quantity discounts apply automatically based on the quantity ordered.', 'dorotape' ),
			);
		default:
			return array(
				'header'     => __( 'Price per metre', 'dorotape' ),
				'suffix'     => '/m',
				'qty_suffix' => 'm+',
				'note'       => __( 'Quantity discounts apply automatically. Enter your required length in the quantity field. Material is supplied as a continuous length.', 'dorotape' ),
			);
	}
}

// ─── Admin field (Product data → Advanced) ────────────────────────────────────

add_action( 'woocommerce_product_options_advanced', function (): void {
	global $post;
	woocommerce_wp_select(
		array(
			'id'          => '_dt_price_unit',
			'label'       => __( 'Sold per', 'dorotape' ),
			'value'       => dorotape_price_unit( $post->ID ),
			'options'     => array(
				'metre' => __( 'Metre (length entered as quantity)', 'dorotape' ),
				'roll'  => __( 'Roll', 'dorotape' ),
				'item'  => __( 'Item / each', 'dorotape' ),
			),
			'description' => __( 'Controls the wording of the quantity pricing table and discount labels ("per metre" vs "per roll").', 'dorotape' ),
		)
	);
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	if ( ! isset( $_POST['_dt_price_unit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
		return;
	}
	$unit = wc_clean( wp_unslash( $_POST['_dt_price_unit'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( in_array( $unit, array( 'roll', 'item' ), true ) ) {
		$product->update_meta_data( '_dt_price_unit', $unit );
	} else {
		$product->delete_meta_data( '_dt_price_unit' ); // metre = default
	}
} );

// ─── Quantity input step ──────────────────────────────────────────────────────

/**
 * Optional quantity-input increment for products the customer should order in
 * batches of N (e.g. ASLAN DFP07 UltraTack — 5m/10m at a time) rather than
 * one at a time. Stored as _dt_qty_step post meta (parent product only,
 * variations resolve to parent); '1' or empty means the default WooCommerce
 * per-unit stepping.
 */
add_action( 'woocommerce_product_options_advanced', function (): void {
	global $post;
	woocommerce_wp_select(
		array(
			'id'          => '_dt_qty_step',
			'label'       => __( 'Quantity step', 'dorotape' ),
			'value'       => get_post_meta( $post->ID, '_dt_qty_step', true ) ?: '1',
			'options'     => array(
				'1'  => __( 'None (order any quantity)', 'dorotape' ),
				'5'  => __( 'Jumps of 5', 'dorotape' ),
				'10' => __( 'Jumps of 10', 'dorotape' ),
			),
			'description' => __( 'Forces the quantity box on the product page to increment in steps (e.g. 5, 10, 15...) instead of one at a time.', 'dorotape' ),
		)
	);
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	if ( ! isset( $_POST['_dt_qty_step'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
		return;
	}
	$step = wc_clean( wp_unslash( $_POST['_dt_qty_step'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( in_array( $step, array( '5', '10' ), true ) ) {
		$product->update_meta_data( '_dt_qty_step', $step );
	} else {
		$product->delete_meta_data( '_dt_qty_step' ); // '1' = default, nothing to store
	}
} );

/**
 * Resolve a product/variation's quantity step, parent meta wins.
 *
 * @param WC_Product $product
 * @return int
 */
function dorotape_qty_step( WC_Product $product ): int {
	$id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	$step = (int) get_post_meta( $id, '_dt_qty_step', true );
	return $step > 1 ? $step : 1;
}

add_filter( 'woocommerce_quantity_input_step', function ( $step, $product ) {
	return $product instanceof WC_Product ? dorotape_qty_step( $product ) : $step;
}, 10, 2 );

/**
 * Start the quantity box at the step value (5, 10...) rather than 1, so the
 * default input is already a valid multiple of the step.
 */
add_filter( 'woocommerce_quantity_input_args', function ( array $args, $product ) {
	if ( $product instanceof WC_Product ) {
		$step = dorotape_qty_step( $product );
		if ( $step > 1 ) {
			$args['min_value'] = max( (int) $args['min_value'], $step );
			if ( empty( $_POST['quantity'] ) && (int) $args['input_value'] < $step ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- display default only.
				$args['input_value'] = $step;
			}
		}
	}
	return $args;
}, 10, 2 );

// ─── Role-based pricing (Woosage / Sage price lists) ─────────────────────────

/**
 * True when the current user has a Sage price-list (role-based) price for
 * this product. Role prices are written by Woosage as _v_rbp_role_{role}
 * meta (Rymera Simple Role Based Pricing) and REPLACE the website price —
 * quantity-break tiers and website discounts must not stack on top.
 *
 * @param WC_Product $product Product or variation as priced in the cart.
 * @return bool
 */
function dorotape_user_has_role_price( WC_Product $product ): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	foreach ( (array) wp_get_current_user()->roles as $role ) {
		if ( is_numeric( $product->get_meta( '_v_rbp_role_' . $role ) ) ) {
			return true;
		}
	}
	return false;
}

// ─── Price Calculation ────────────────────────────────────────────────────────

/**
 * Apply quantity-break tier pricing and customer discount to each cart line.
 *
 * @param WC_Cart $cart
 */
function dorotape_dynamic_pricing( WC_Cart $cart ): void {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	// Guard against infinite recursion from nested WC recalculations.
	// A re-entrancy flag rather than did_action(): totals legitimately
	// recalculate more than once per request (each add-to-cart, block cart),
	// and a fire-count guard skips repricing for lines added after the
	// second calculation.
	static $running = false;
	if ( $running ) {
		return;
	}
	$running = true;

	$customer_discount = dorotape_get_customer_discount( get_current_user_id() );

	// Quick-add products (Hook & Loop) share one combined quantity across all
	// their rows for picking a tier — 5 Hook + 5 Loop unlocks the 10+ rate for
	// both — even though each row keeps its own SKU, price, and cart line.
	$combined_qty = array(); // parent product_id => total qty across its rows
	foreach ( $cart->get_cart() as $item ) {
		// dorotape_is_quick_add() checks is_type('variable'), so it must be
		// given the parent product — $item['data'] here is the variation.
		$parent = wc_get_product( $item['product_id'] );
		if ( $parent && function_exists( 'dorotape_is_quick_add' ) && dorotape_is_quick_add( $parent ) ) {
			$pid                   = $item['product_id'];
			$combined_qty[ $pid ] = ( $combined_qty[ $pid ] ?? 0 ) + (int) $item['quantity'];
		}
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		$product    = $cart_item['data'];
		$product_id = $cart_item['product_id'];
		$base_price = (float) $product->get_regular_price();

		if ( $base_price <= 0 ) {
			continue;
		}

		// Sage price-list customers: the role price replaces the website
		// price outright — never stack tiers or website discount on it.
		if ( dorotape_user_has_role_price( $product ) ) {
			continue;
		}

		// Variations store their own _price_tiers on the variation post.
		$lookup_id = ! empty( $cart_item['variation_id'] )
			? (int) $cart_item['variation_id']
			: $product_id;

		$qty = isset( $combined_qty[ $product_id ] ) ? $combined_qty[ $product_id ] : (int) $cart_item['quantity'];

		$price = dorotape_get_tier_price( $qty, $lookup_id, $base_price );

		if ( $customer_discount > 0 ) {
			$price *= ( 1 - ( $customer_discount / 100 ) );
		}

		$product->set_price( round( $price, wc_get_price_decimals() ) );
	}

	$running = false;
}
add_action( 'woocommerce_before_calculate_totals', 'dorotape_dynamic_pricing', 20, 1 );

/**
 * Retrieve the ACF customer discount rate for a logged-in user.
 * Returns 0 for guests or users with no discount configured.
 * The customer_discount_rate field lives on the user record (Sage sync),
 * not on products — ACF is user-only in this theme.
 *
 * @param int $user_id
 * @return float Percentage discount (0–99.99).
 */
function dorotape_get_customer_discount( int $user_id ): float {
	if ( ! $user_id ) {
		return 0.0;
	}
	$rate = get_field( 'customer_discount_rate', 'user_' . $user_id );
	if ( ! is_numeric( $rate ) ) {
		return 0.0;
	}
	$rate = (float) $rate;
	return ( $rate > 0 && $rate < 100 ) ? $rate : 0.0;
}

// ─── Tier Parsing ─────────────────────────────────────────────────────────────

/**
 * Fallback: recover tier data from ACF's flat repeater meta.
 *
 * During a migration window, ACF overwrote _price_tiers with its internal
 * field-key reference string (e.g. "field_dorotape_price_tiers"). The import
 * script had already written flat meta keys before that corruption. This
 * function reads those flat keys to recover the tiers.
 *
 * ~118 products still carry the corrupted _price_tiers value. Once they have
 * all been re-saved through the admin (which writes clean _price_tiers), this
 * function and dorotape_parse_legacy_tiers's fallback branch can be removed.
 *
 * @param int $product_id
 * @return array
 */
function dorotape_read_acf_flat_tiers( int $product_id ): array {
	$count = (int) get_post_meta( $product_id, 'price_tiers', true );
	if ( $count <= 0 ) {
		return array();
	}
	$tiers = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$min_qty    = (int)   get_post_meta( $product_id, "price_tiers_{$i}_min_qty",    true );
		$tier_price = (float) get_post_meta( $product_id, "price_tiers_{$i}_tier_price", true );
		if ( $min_qty > 0 && $tier_price > 0 ) {
			$tiers[] = array( 'min_qty' => $min_qty, 'tier_price' => $tier_price );
		}
	}
	return $tiers;
}

/**
 * Parse _price_tiers post meta into a tier array, falling back to the parent
 * product's tiers when a variation has none of its own.
 *
 * Some variations (e.g. Poli Print 800, Orajet 3268) were never given their
 * own _price_tiers, which used to mean no tiers at all for that variation —
 * the pricing table silently didn't render and the price never updated with
 * quantity. Since tiers are near-always shared across a product's variations,
 * falling back to the parent's tiers keeps the table (and live price update)
 * working instead of quietly disappearing.
 *
 * @param int $product_id Variation ID or product ID.
 * @return array Array of arrays with 'min_qty' and 'tier_price' keys.
 */
function dorotape_parse_legacy_tiers( int $product_id ): array {
	$tiers = dorotape_parse_legacy_tiers_own( $product_id );
	if ( ! empty( $tiers ) ) {
		return $tiers;
	}

	$parent_id = wp_get_post_parent_id( $product_id );
	return $parent_id ? dorotape_parse_legacy_tiers_own( $parent_id ) : array();
}

/**
 * Parse _price_tiers post meta for a single post, no parent fallback.
 *
 * Meta format: "1-24:11.00;25:9.90" — each segment is "min[-max]:price".
 * Falls back to the flat ACF meta recovery path for the ~118 products whose
 * _price_tiers was corrupted with an ACF field-key reference string.
 *
 * @param int $product_id Variation ID or product ID.
 * @return array Array of arrays with 'min_qty' and 'tier_price' keys.
 */
function dorotape_parse_legacy_tiers_own( int $product_id ): array {
	$raw = get_post_meta( $product_id, '_price_tiers', true );

	if ( ! $raw || ! is_string( $raw ) || str_starts_with( $raw, 'field_' ) ) {
		return dorotape_read_acf_flat_tiers( $product_id );
	}

	$tiers = array();
	foreach ( explode( ';', $raw ) as $segment ) {
		$segment = trim( $segment );
		if ( '' === $segment ) {
			continue;
		}
		$parts = explode( ':', $segment, 2 );
		if ( 2 !== count( $parts ) ) {
			continue;
		}
		$price   = (float) trim( str_replace( ',', '.', $parts[1] ) );
		$min_qty = (int) explode( '-', trim( $parts[0] ) )[0];
		if ( $price <= 0 || $min_qty <= 0 ) {
			continue;
		}
		$tiers[] = array(
			'min_qty'    => $min_qty,
			'tier_price' => $price,
		);
	}
	return $tiers;
}

/**
 * Return the tiered price for the given quantity.
 * Falls back to base_price when no tiers are configured or none match.
 *
 * @param int   $qty        Cart line quantity (metres ordered).
 * @param int   $product_id Variation ID or product ID.
 * @param float $base_price WooCommerce regular price for this product/variation.
 * @return float
 */
function dorotape_get_tier_price( int $qty, int $product_id, float $base_price ): float {
	$tiers = dorotape_parse_legacy_tiers( $product_id );
	if ( empty( $tiers ) ) {
		return $base_price;
	}

	// Sort descending so the first match is the highest applicable tier.
	usort(
		$tiers,
		static function ( array $a, array $b ): int {
			return (int) $b['min_qty'] - (int) $a['min_qty'];
		}
	);

	foreach ( $tiers as $tier ) {
		if ( $qty >= (int) $tier['min_qty'] ) {
			$tier_price = (float) ( $tier['tier_price'] ?? 0 );
			if ( $tier_price > 0 ) {
				return $tier_price;
			}
		}
	}

	return $base_price;
}

/**
 * Return the tier that was applied for a given quantity, or null if none matched.
 * Used by cart/order meta display so customers can see which discount was applied.
 *
 * @param int $qty        Cart line quantity (metres).
 * @param int $product_id
 * @return array|null Tier row (min_qty, tier_price) or null.
 */
function dorotape_find_applied_tier( int $qty, int $product_id ): ?array {
	$tiers = dorotape_parse_legacy_tiers( $product_id );
	if ( empty( $tiers ) ) {
		return null;
	}
	usort(
		$tiers,
		static function ( array $a, array $b ): int {
			return (int) $b['min_qty'] - (int) $a['min_qty'];
		}
	);
	foreach ( $tiers as $tier ) {
		if ( $qty >= (int) $tier['min_qty'] && (float) ( $tier['tier_price'] ?? 0 ) > 0 ) {
			return $tier;
		}
	}
	return null;
}

// ─── Cart Item Meta ───────────────────────────────────────────────────────────

/**
 * Display the active quantity-tier discount label in cart, checkout, and emails.
 *
 * @param array $item_data Existing display meta.
 * @param array $cart_item
 * @return array
 */
function dorotape_cart_item_display_meta( array $item_data, array $cart_item ): array {
	$product_id = $cart_item['product_id'];
	$lookup_id  = ! empty( $cart_item['variation_id'] )
		? (int) $cart_item['variation_id']
		: $product_id;

	$qty  = (int) ( $cart_item['quantity'] ?? 1 );
	$tier = dorotape_find_applied_tier( $qty, $lookup_id );

	if ( $tier ) {
		$product    = $cart_item['data'] ?? wc_get_product( $product_id );
		$base_price = $product ? (float) $product->get_regular_price() : 0.0;
		$tier_price = (float) $tier['tier_price'];
		$saving_pct = ( $base_price > 0 )
			? (int) round( ( ( $base_price - $tier_price ) / $base_price ) * 100 )
			: 0;
		$unit  = dorotape_unit_strings( dorotape_price_unit( (int) $product_id ) );
		$label = (int) $tier['min_qty'] . $unit['qty_suffix'] . ' rate';
		if ( $saving_pct > 0 ) {
			$label .= ' · save ' . $saving_pct . '%';
		}
		$item_data[] = array(
			'name'  => esc_html__( 'Qty Discount', 'dorotape' ),
			'value' => esc_html( $label ),
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'dorotape_cart_item_display_meta', 10, 2 );

/**
 * Persist the tier discount label to the order record.
 * Appears on the admin order screen, confirmation emails, and Sage sync records.
 *
 * @param WC_Order_Item_Product $item
 * @param string                $cart_item_key Unused — required by WC hook signature.
 * @param array                 $values
 * @param WC_Order              $order         Unused — required by WC hook signature.
 */
function dorotape_save_order_item_meta(
	WC_Order_Item_Product $item,
	string $cart_item_key,
	array $values,
	WC_Order $order
): void {
	$product_id = $values['product_id'] ?? 0;
	$lookup_id  = ! empty( $values['variation_id'] )
		? (int) $values['variation_id']
		: $product_id;

	if ( ! $lookup_id ) {
		return;
	}

	$qty  = (int) ( $values['quantity'] ?? 1 );
	$tier = dorotape_find_applied_tier( $qty, $lookup_id );

	if ( $tier ) {
		$product    = wc_get_product( $product_id );
		$base_price = $product ? (float) $product->get_regular_price() : 0.0;
		$tier_price = (float) $tier['tier_price'];
		$saving_pct = ( $base_price > 0 )
			? (int) round( ( ( $base_price - $tier_price ) / $base_price ) * 100 )
			: 0;
		$unit  = dorotape_unit_strings( dorotape_price_unit( (int) $product_id ) );
		$label = (int) $tier['min_qty'] . $unit['qty_suffix'] . ' rate';
		if ( $saving_pct > 0 ) {
			$label .= ' · save ' . $saving_pct . '%';
		}
		$item->add_meta_data(
			esc_html__( 'Qty Discount', 'dorotape' ),
			$label,
			true
		);
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'dorotape_save_order_item_meta', 10, 4 );
