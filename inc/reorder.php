<?php
/**
 * Re-order from a past order
 *
 * WooCommerce core already ships an Order again button, so this file is not a
 * reimplementation of it. Core's version rebuilds the basket in
 * WC_Cart_Session::populate_cart_from_order(), and it knows nothing about the
 * two things this shop puts on a line: cut sizes and quantity steps. Left
 * alone it gets both wrong, quietly, which is the worst way to get them wrong.
 *
 * Measured on a real completed order before any of this was written:
 *
 *   ordered      25 rolls cut "500mm, 720mm" and 50 rolls cut "300mm, 300mm,
 *                610mm", 75 rolls in total, every one of them cut to size
 *   re-ordered   one line, 50 uncut rolls
 *
 * Two separate faults produced that. Core seeds each line's cart item data as
 * an empty array and only the filter below can fill it, so the cut sizes were
 * dropped; and because both lines then carried identical (empty) cart item
 * data, generate_cart_id() gave them the same key and the second assignment
 * overwrote the first instead of adding to it, so 25 rolls vanished as well.
 * The customer was told "1 item from your previous order is currently
 * unavailable" and "The cart has been filled with the items from your previous
 * order" in the same breath, neither of which described what had happened.
 *
 * Carrying the cut sizes across fixes both, because the note is part of the
 * hash that generate_cart_id() builds: distinct notes give distinct keys, so
 * the lines stay apart on their own.
 *
 * The third fault is the quantity step, handled further down.
 *
 * @package dorotape
 */

/**
 * True while core is rebuilding the cart from a past order.
 *
 * Core verifies the nonce itself in WC_Cart_Session::get_cart_from_session()
 * before it does any of this work, so the presence of the parameter is enough
 * here and re-checking it would only give a false sense of a second gate.
 *
 * @return bool
 */
function dorotape_is_order_again(): bool {
	return isset( $_GET['order_again'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- core verifies before reaching this path.
}

/**
 * Carry the cut sizes from the old order line onto the new cart line.
 *
 * Read from the hidden _dt_cut_sizes_note key rather than the visible "Cut
 * Sizes" one, because that label is translatable: an order placed while the
 * site was in one locale would stop matching if the locale ever changed.
 * cutsize.php writes both for exactly this reason.
 *
 * @param array                 $cart_item_data Data core will build the line from.
 * @param WC_Order_Item_Product $item           The line on the old order.
 * @param WC_Order              $order          The old order.
 * @return array
 */
add_filter( 'woocommerce_order_again_cart_item_data', function ( $cart_item_data, $item, $order ) {
	if ( ! $item instanceof WC_Order_Item_Product ) {
		return $cart_item_data;
	}

	$note = $item->get_meta( '_dt_cut_sizes_note', true );
	if ( $note ) {
		$cart_item_data['dt_cut_sizes'] = $note;
	}

	return $cart_item_data;
}, 10, 3 );

/**
 * Round a re-ordered quantity up to a valid multiple of the step.
 *
 * A re-order can carry a quantity the step rule would now refuse: the step may
 * have been introduced or changed since, or the order may have been raised by
 * staff in admin, where the rule does not apply. Refusing it is what core does
 * by default, and the result is that the line silently disappears and the
 * customer is told the product is "currently unavailable", which is false. The
 * product is available; the quantity is stale.
 *
 * So the line is kept and the quantity is rounded up, never down, since
 * rounding down would hand somebody less than they asked for. This is a
 * basket, not a purchase, and it says plainly what it changed, so a customer
 * who wanted the smaller amount can still edit it before paying.
 *
 * @param array  $cart_item The assembled cart line.
 * @param string $cart_id   Unused, required by the WooCommerce hook signature.
 * @return array
 */
add_filter( 'woocommerce_add_order_again_cart_item', function ( $cart_item, $cart_id ) {
	$product = $cart_item['data'] ?? null;
	if ( ! $product instanceof WC_Product ) {
		return $cart_item;
	}

	$step = dorotape_qty_step( $product );
	$qty  = (int) $cart_item['quantity'];
	if ( $step <= 1 || $qty <= 0 || 0 === $qty % $step ) {
		return $cart_item;
	}

	$rounded               = (int) ceil( $qty / $step ) * $step;
	$cart_item['quantity'] = $rounded;

	wc_add_notice(
		sprintf(
			/* translators: 1: product name, 2: quantity on the previous order, 3: adjusted quantity, 4: step */
			esc_html__( '%1$s was ordered as %2$d last time, which is no longer a quantity we can supply. It has gone into your basket as %3$d, the next multiple of %4$d.', 'dorotape' ),
			$product->get_name(),
			$qty,
			$rounded,
			$step
		),
		'notice'
	);

	return $cart_item;
}, 10, 2 );

/**
 * Put the Order again button on the orders list, not just inside one order.
 *
 * Core hangs it off woocommerce_order_details_after_order_table, which only
 * renders on the single order view, so re-ordering costs a customer two clicks
 * and a page load to reach a button they cannot see from the list. Trade
 * customers here re-order the same consumables repeatedly, so it belongs next
 * to View on the list as well.
 *
 * The same status and capability rules core applies are applied here, read
 * through core's own filter so the two cannot drift apart.
 *
 * @param array    $actions Buttons for this row.
 * @param WC_Order $order   The order the row is for.
 * @return array
 */
add_filter( 'woocommerce_my_account_my_orders_actions', function ( $actions, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $actions;
	}

	/** This filter is documented in woocommerce/includes/wc-template-functions.php */
	$valid = apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) );
	if ( ! $order->has_status( $valid ) || ! current_user_can( 'order_again', $order->get_id() ) ) {
		return $actions;
	}

	$actions['order-again'] = array(
		'url'  => wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ), 'woocommerce-order_again' ),
		'name' => __( 'Order again', 'dorotape' ),
	);

	return $actions;
}, 10, 2 );
