<?php
/**
 * Dynamic pricing — width and roll modifiers driven by ACF product fields,
 * plus customer-specific discounts pulled from Sage-synced user meta.
 *
 * Data flow:
 *   1. Customer selects width/roll on the product page (POST fields).
 *   2. dorotape_add_to_cart_meta() stores selections as cart item meta.
 *   3. dorotape_validate_product_options() confirms the selection is valid.
 *   4. dorotape_dynamic_pricing() fires on every cart recalculation and
 *      adjusts the line item price using the ACF width_options / roll_options
 *      repeater fields plus any customer-level discount.
 *   5. dorotape_cart_item_display_meta() surfaces the selections in the cart UI.
 *   6. dorotape_save_order_item_meta() persists them to the order record.
 *
 * @package dorotape
 */

// ─── Price Calculation ────────────────────────────────────────────────────────

/**
 * Adjust cart item prices based on ACF width/roll prices and customer discount.
 *
 * @param WC_Cart $cart
 */
function dorotape_dynamic_pricing( WC_Cart $cart ): void {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	// Guard against infinite recursion from nested WC recalculations.
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}

	$customer_discount = dorotape_get_customer_discount( get_current_user_id() );

	foreach ( $cart->get_cart() as $cart_item ) {
		$product    = $cart_item['data'];
		$product_id = $cart_item['product_id'];
		$base_price = (float) $product->get_regular_price();

		if ( $base_price <= 0 ) {
			continue;
		}

		// For variations, ACF fields and _price_tiers meta live on the variation post.
		$lookup_id = ! empty( $cart_item['variation_id'] )
			? (int) $cart_item['variation_id']
			: $product_id;

		// Width price resolved first (falls back to base if no match or blank).
		$width_price = $base_price;
		if ( ! empty( $cart_item['dorotape_width'] ) ) {
			$width_price = dorotape_get_width_price( (int) $cart_item['dorotape_width'], $lookup_id, $base_price );
		}

		// Roll: fixed rolls use roll_price; per-metre rows get quantity tier pricing.
		// When no roll is selected (no dorotape_roll_length in cart), also apply tiers.
		$price = $width_price;
		if ( ! empty( $cart_item['dorotape_roll_length'] ) ) {
			$roll_length = (float) $cart_item['dorotape_roll_length'];
			if ( dorotape_is_per_metre_roll( $roll_length, $lookup_id ) ) {
				$price = dorotape_get_tier_price( (int) $cart_item['quantity'], $lookup_id, $width_price, $base_price );
			} else {
				$price = dorotape_get_roll_price( $roll_length, $lookup_id, $width_price, $base_price );
			}
		} else {
			$price = dorotape_get_tier_price( (int) $cart_item['quantity'], $lookup_id, $width_price, $base_price );
		}

		// Customer-level discount applied last.
		if ( $customer_discount > 0 ) {
			$price *= ( 1 - ( $customer_discount / 100 ) );
		}

		$product->set_price( round( $price, wc_get_price_decimals() ) );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'dorotape_dynamic_pricing', 20, 1 );

/**
 * Retrieve the ACF customer discount rate for a logged-in user.
 * Returns 0 for guests or users with no discount configured.
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

/**
 * Return the per-metre price for the given width.
 * Uses the stored ACF price_per_metre value; falls back to base_price when blank/zero.
 *
 * @param int   $width_mm    Selected width in mm.
 * @param int   $product_id
 * @param float $base_price  WooCommerce regular price (base/narrowest width).
 * @return float
 */
function dorotape_get_width_price( int $width_mm, int $product_id, float $base_price ): float {
	$options = get_field( 'width_options', $product_id );
	if ( ! is_array( $options ) ) {
		return $base_price;
	}
	foreach ( $options as $option ) {
		if ( (int) $option['width_value'] !== $width_mm ) {
			continue;
		}
		$price = (float) ( $option['price_per_metre'] ?? 0 );
		return $price > 0 ? $price : $base_price;
	}
	return $base_price;
}

/**
 * Return the final price for a given roll length, scaled to the selected width.
 *
 * Stored roll_price is the total at the base (narrowest) width.
 * Wider widths scale proportionally: roll_price × (width_price / base_price).
 * A blank/zero roll_price means "per metre" — return width_price unchanged.
 *
 * @param float $roll_length  Selected roll length in metres.
 * @param int   $product_id
 * @param float $width_price  Already-resolved per-metre price for selected width.
 * @param float $base_price   WooCommerce regular price (base/narrowest width).
 * @return float
 */
function dorotape_get_roll_price( float $roll_length, int $product_id, float $width_price, float $base_price ): float {
	$options = get_field( 'roll_options', $product_id );
	if ( ! is_array( $options ) ) {
		return $width_price;
	}
	foreach ( $options as $option ) {
		if ( abs( (float) $option['roll_length'] - $roll_length ) > 0.001 ) {
			continue;
		}
		$roll_price = (float) ( $option['roll_price'] ?? 0 );
		if ( $roll_price <= 0 ) {
			return $width_price; // Per-metre row — no roll discount/multiplier.
		}
		if ( $base_price <= 0 ) {
			return $roll_price;
		}
		return $roll_price * ( $width_price / $base_price );
	}
	return $width_price;
}

/**
 * Return true when the given roll_length matches a "Per Metre" row
 * (roll_price is blank/zero), or when no matching row is found.
 * Used by the pricing engine to decide whether quantity tiers apply.
 *
 * @param float $roll_length Selected roll length in metres.
 * @param int   $product_id
 * @return bool
 */
function dorotape_is_per_metre_roll( float $roll_length, int $product_id ): bool {
	$options = get_field( 'roll_options', $product_id );
	if ( ! is_array( $options ) ) {
		return true;
	}
	foreach ( $options as $option ) {
		if ( abs( (float) $option['roll_length'] - $roll_length ) <= 0.001 ) {
			return (float) ( $option['roll_price'] ?? 0 ) <= 0;
		}
	}
	return true;
}

/**
 * Parse the legacy Kryptronic _price_tiers post meta into our tier array format.
 *
 * Meta format: "1-24:11.00;25:9.90" — each segment is "min[-max]:price".
 * Only min_qty and tier_price are extracted; the upper bound is unused because
 * tiers are evaluated highest-first (first match wins).
 *
 * @param int $product_id Variation ID or product ID.
 * @return array Array of arrays with 'min_qty' and 'tier_price' keys.
 */
/**
 * Fallback: read tier data from ACF's flat repeater storage.
 *
 * When ACF was active and update_field('price_tiers') was called, it stored:
 *   _price_tiers    = 'field_dorotape_price_tiers'  (field-key reference — corrupt)
 *   price_tiers     = N                              (row count)
 *   price_tiers_0_min_qty    = 25
 *   price_tiers_0_tier_price = 9.90
 * This function recovers the tiers from those flat meta keys.
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

function dorotape_parse_legacy_tiers( int $product_id ): array {
	$raw = get_post_meta( $product_id, '_price_tiers', true );

	// ACF overwrote _price_tiers with its internal field-key reference string
	// (e.g. "field_dorotape_price_tiers") when products were saved while the
	// ACF field group was active. Detect this and fall back to ACF's flat
	// repeater meta (price_tiers_N_min_qty / price_tiers_N_tier_price) which
	// the migration script populated before the corruption occurred.
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
 * Return the tiered per-metre price for the given quantity.
 * Tiers are stored at the base width; wider widths scale proportionally.
 * Falls back to width_price when no tiers are configured or none match.
 *
 * @param int   $qty         Cart line quantity (metres ordered).
 * @param int   $product_id
 * @param float $width_price Already-resolved per-metre price for selected width.
 * @param float $base_price  WooCommerce regular price (base/narrowest width).
 * @return float
 */
function dorotape_get_tier_price( int $qty, int $product_id, float $width_price, float $base_price ): float {
	// ACF price_tiers repeater takes priority (manually managed / our imports).
	// Fall back to _price_tiers post meta (format from Kryptronic CSV migration).
	$tiers = get_field( 'price_tiers', $product_id );
	if ( ! is_array( $tiers ) || empty( $tiers ) ) {
		$tiers = dorotape_parse_legacy_tiers( $product_id );
	}
	if ( empty( $tiers ) ) {
		return $width_price;
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
			if ( $tier_price <= 0 ) {
				continue;
			}
			if ( $base_price <= 0 ) {
				return $tier_price;
			}
			return $tier_price * ( $width_price / $base_price );
		}
	}

	return $width_price;
}

/**
 * Return the tier that was applied for a given quantity, or null if none matched.
 * Used by cart/order meta display so customers can see which discount was applied.
 *
 * @param int   $qty        Cart line quantity (metres).
 * @param int   $product_id
 * @return array|null Tier row (min_qty, tier_price) or null.
 */
function dorotape_find_applied_tier( int $qty, int $product_id ): ?array {
	$tiers = get_field( 'price_tiers', $product_id );
	if ( ! is_array( $tiers ) || empty( $tiers ) ) {
		$tiers = dorotape_parse_legacy_tiers( $product_id );
	}
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
 * Capture dorotape_width and dorotape_roll_length from the add-to-cart POST.
 *
 * @param array $cart_item_data Existing cart item data.
 * @param int   $product_id
 * @param int   $variation_id
 * @return array
 */
function dorotape_add_to_cart_meta( array $cart_item_data, int $product_id, int $variation_id ): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// Nonce verification handled upstream by WooCommerce add-to-cart flow.
	if ( isset( $_POST['dorotape_width'] ) && '' !== $_POST['dorotape_width'] ) {
		$cart_item_data['dorotape_width'] = absint( $_POST['dorotape_width'] );
	}
	if ( isset( $_POST['dorotape_roll_length'] ) && '' !== $_POST['dorotape_roll_length'] ) {
		$cart_item_data['dorotape_roll_length'] = (float) sanitize_text_field( wp_unslash( $_POST['dorotape_roll_length'] ) );
	}
	// phpcs:enable
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'dorotape_add_to_cart_meta', 10, 3 );

/**
 * Display width and roll selections in cart, checkout, and email order summaries.
 *
 * @param array $item_data Existing display meta.
 * @param array $cart_item
 * @return array
 */
function dorotape_cart_item_display_meta( array $item_data, array $cart_item ): array {
	if ( ! empty( $cart_item['dorotape_width'] ) ) {
		$item_data[] = array(
			'name'  => esc_html__( 'Width', 'dorotape' ),
			'value' => esc_html( $cart_item['dorotape_width'] ) . 'mm',
		);
	}
	if ( ! empty( $cart_item['dorotape_roll_length'] ) ) {
		$item_data[] = array(
			'name'  => esc_html__( 'Roll Length', 'dorotape' ),
			'value' => esc_html( (string) $cart_item['dorotape_roll_length'] ) . 'm',
		);
	}

	// Show quantity tier discount when: roll is not selected, or the selected roll
	// is the per-metre row (roll_price == 0). Fixed-roll prices already reflect bulk.
	$product_id  = $cart_item['product_id'];
	$lookup_id   = ! empty( $cart_item['variation_id'] )
		? (int) $cart_item['variation_id']
		: $product_id;
	$roll_length = ! empty( $cart_item['dorotape_roll_length'] )
		? (float) $cart_item['dorotape_roll_length']
		: 0.0;
	$is_per_metre = $roll_length <= 0 || dorotape_is_per_metre_roll( $roll_length, $lookup_id );

	if ( $is_per_metre ) {
		$qty  = (int) ( $cart_item['quantity'] ?? 1 );
		$tier = dorotape_find_applied_tier( $qty, $lookup_id );

		if ( $tier ) {
			$product    = $cart_item['data'] ?? wc_get_product( $product_id );
			$base_price = $product ? (float) $product->get_regular_price() : 0.0;
			$tier_price = (float) $tier['tier_price'];
			$saving_pct = ( $base_price > 0 )
				? (int) round( ( ( $base_price - $tier_price ) / $base_price ) * 100 )
				: 0;
			$label = (int) $tier['min_qty'] . 'm+ rate';
			if ( $saving_pct > 0 ) {
				$label .= ' · save ' . $saving_pct . '%';
			}
			$item_data[] = array(
				'name'  => esc_html__( 'Qty Discount', 'dorotape' ),
				'value' => esc_html( $label ),
			);
		}
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'dorotape_cart_item_display_meta', 10, 2 );

/**
 * Persist width and roll selections as order item meta so they appear on
 * order confirmation, admin order screen, and Sage sync records.
 *
 * @param WC_Order_Item_Product $item
 * @param string                $cart_item_key
 * @param array                 $values
 * @param WC_Order              $order
 */
function dorotape_save_order_item_meta(
	WC_Order_Item_Product $item,
	string $cart_item_key,
	array $values,
	WC_Order $order
): void {
	if ( ! empty( $values['dorotape_width'] ) ) {
		$item->add_meta_data(
			esc_html__( 'Width', 'dorotape' ),
			absint( $values['dorotape_width'] ) . 'mm',
			true
		);
	}
	if ( ! empty( $values['dorotape_roll_length'] ) ) {
		$item->add_meta_data(
			esc_html__( 'Roll Length', 'dorotape' ),
			(float) $values['dorotape_roll_length'] . 'm',
			true
		);
	}

	// Persist tier discount to the order record (admin screen, emails, Sage sync).
	$product_id  = $values['product_id'] ?? 0;
	$lookup_id   = ! empty( $values['variation_id'] )
		? (int) $values['variation_id']
		: $product_id;
	$roll_length = ! empty( $values['dorotape_roll_length'] )
		? (float) $values['dorotape_roll_length']
		: 0.0;
	$is_per_metre = $roll_length <= 0 || dorotape_is_per_metre_roll( $roll_length, $lookup_id );

	if ( $is_per_metre && $lookup_id ) {
		$qty  = (int) ( $values['quantity'] ?? 1 );
		$tier = dorotape_find_applied_tier( $qty, $lookup_id );

		if ( $tier ) {
			$product    = wc_get_product( $product_id );
			$base_price = $product ? (float) $product->get_regular_price() : 0.0;
			$tier_price = (float) $tier['tier_price'];
			$saving_pct = ( $base_price > 0 )
				? (int) round( ( ( $base_price - $tier_price ) / $base_price ) * 100 )
				: 0;
			$label = (int) $tier['min_qty'] . 'm+ rate';
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
}
add_action( 'woocommerce_checkout_create_order_line_item', 'dorotape_save_order_item_meta', 10, 4 );

// ─── Cart Validation ──────────────────────────────────────────────────────────

/**
 * Reject add-to-cart if submitted width or roll length are not in the product's
 * ACF repeater options. Prevents price manipulation via arbitrary POST values.
 * Validation is skipped when a product has no options configured.
 *
 * @param bool $valid
 * @param int  $product_id
 * @return bool
 */
function dorotape_validate_product_options( bool $valid, int $product_id ): bool {
	if ( ! $valid ) {
		return false;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing

	// Validate width.
	if ( isset( $_POST['dorotape_width'] ) && '' !== $_POST['dorotape_width'] ) {
		$submitted_width = absint( $_POST['dorotape_width'] );
		$width_options   = get_field( 'width_options', $product_id );

		if ( is_array( $width_options ) && ! empty( $width_options ) ) {
			$valid_widths = array_map( 'intval', array_column( $width_options, 'width_value' ) );

			if ( ! in_array( $submitted_width, $valid_widths, true ) ) {
				wc_add_notice( esc_html__( 'Please select a valid width.', 'dorotape' ), 'error' );
				return false;
			}
		}
	}

	// Validate roll length.
	if ( isset( $_POST['dorotape_roll_length'] ) && '' !== $_POST['dorotape_roll_length'] ) {
		$submitted_roll = (float) sanitize_text_field( wp_unslash( $_POST['dorotape_roll_length'] ) );
		$roll_options   = get_field( 'roll_options', $product_id );

		if ( is_array( $roll_options ) && ! empty( $roll_options ) ) {
			$valid_lengths = array_map( 'floatval', array_column( $roll_options, 'roll_length' ) );
			$match         = array_filter(
				$valid_lengths,
				static function ( float $len ) use ( $submitted_roll ): bool {
					return abs( $len - $submitted_roll ) < 0.001;
				}
			);

			if ( empty( $match ) ) {
				wc_add_notice( esc_html__( 'Please select a valid roll length.', 'dorotape' ), 'error' );
				return false;
			}
		}
	}

	// phpcs:enable

	return $valid;
}
add_filter( 'woocommerce_add_to_cart_validation', 'dorotape_validate_product_options', 10, 2 );
