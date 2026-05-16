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
 * Adjust cart item prices based on ACF width/roll modifiers and customer discount.
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
		$price      = (float) $product->get_regular_price();

		if ( $price <= 0 ) {
			continue;
		}

		// Width modifier applied first.
		if ( ! empty( $cart_item['dorotape_width'] ) ) {
			$price = dorotape_apply_width_modifier( $price, (int) $cart_item['dorotape_width'], $product_id );
		}

		// Roll modifier applied on top of width-adjusted price.
		if ( ! empty( $cart_item['dorotape_roll_length'] ) ) {
			$price = dorotape_apply_roll_modifier( $price, (float) $cart_item['dorotape_roll_length'], $product_id );
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
 * Find the matching width option from the ACF repeater and return the adjusted price.
 *
 * @param float $price       Base product price.
 * @param int   $width_mm    Selected width in mm.
 * @param int   $product_id
 * @return float
 */
function dorotape_apply_width_modifier( float $price, int $width_mm, int $product_id ): float {
	$options = get_field( 'width_options', $product_id );
	if ( ! is_array( $options ) ) {
		return $price;
	}
	foreach ( $options as $option ) {
		if ( (int) $option['width_value'] !== $width_mm ) {
			continue;
		}
		return dorotape_calculate_modified_price(
			$price,
			$option['price_modifier_type'] ?? 'none',
			(float) ( $option['price_modifier_value'] ?? 0 )
		);
	}
	return $price;
}

/**
 * Find the matching roll option from the ACF repeater and return the adjusted price.
 *
 * @param float $price       Price (already width-adjusted).
 * @param float $roll_length Selected roll length in metres.
 * @param int   $product_id
 * @return float
 */
function dorotape_apply_roll_modifier( float $price, float $roll_length, int $product_id ): float {
	$options = get_field( 'roll_options', $product_id );
	if ( ! is_array( $options ) ) {
		return $price;
	}
	foreach ( $options as $option ) {
		if ( abs( (float) $option['roll_length'] - $roll_length ) > 0.001 ) {
			continue;
		}
		return dorotape_calculate_modified_price(
			$price,
			$option['price_modifier_type'] ?? 'none',
			(float) ( $option['price_modifier_value'] ?? 0 )
		);
	}
	return $price;
}

/**
 * Apply a price modifier (none, percentage, or fixed amount).
 *
 * @param float  $price Base price.
 * @param string $type  'none' | 'percentage' | 'fixed'.
 * @param float  $value Modifier value. Negative values reduce the price.
 * @return float
 */
function dorotape_calculate_modified_price( float $price, string $type, float $value ): float {
	switch ( $type ) {
		case 'percentage':
			return $price * ( 1 + ( $value / 100 ) );
		case 'fixed':
			return max( 0.0, $price + $value );
		default:
			return $price;
	}
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
}
add_action( 'woocommerce_checkout_create_order_line_item', 'dorotape_save_order_item_meta', 10, 4 );

// ─── Cart Validation ──────────────────────────────────────────────────────────

/**
 * Reject add-to-cart if the submitted width value is not in the product's ACF
 * width_options repeater. Prevents price manipulation via arbitrary POST values.
 *
 * Only validates when the product actually has width options defined.
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
	if ( ! isset( $_POST['dorotape_width'] ) || '' === $_POST['dorotape_width'] ) {
		return $valid;
	}

	$submitted    = absint( $_POST['dorotape_width'] );
	// phpcs:enable
	$width_options = get_field( 'width_options', $product_id );

	if ( ! is_array( $width_options ) || empty( $width_options ) ) {
		return $valid; // No options configured — allow any value.
	}

	$valid_widths = array_map( 'intval', array_column( $width_options, 'width_value' ) );

	if ( ! in_array( $submitted, $valid_widths, true ) ) {
		wc_add_notice( esc_html__( 'Please select a valid width.', 'dorotape' ), 'error' );
		return false;
	}

	return $valid;
}
add_filter( 'woocommerce_add_to_cart_validation', 'dorotape_validate_product_options', 10, 2 );
