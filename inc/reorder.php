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
	$qty  = dorotape_stock_amount( $cart_item['quantity'] );
	if ( $step <= 1 || $qty <= 0 || dorotape_is_qty_multiple( $qty, $step ) ) {
		return $cart_item;
	}

	$rounded               = dorotape_stock_amount( ceil( $qty / $step ) * $step );
	$cart_item['quantity'] = $rounded;

	wc_add_notice(
		sprintf(
			/* translators: 1: product name, 2: quantity on the previous order, 3: adjusted quantity, 4: step */
			esc_html__( '%1$s was ordered as %2$s last time, which is no longer a quantity we can supply. It has gone into your basket as %3$s, the next multiple of %4$s.', 'dorotape' ),
			$product->get_name(),
			dorotape_format_qty( $qty ),
			dorotape_format_qty( $rounded ),
			dorotape_format_qty( $step )
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

/**
 * Offer Order again on any order that went through, not only completed ones.
 *
 * Core allows completed orders only, so a customer whose last order was still
 * processing or on hold had no button at all. That is how Scott Bale tested
 * it and asked for one (19 November 2026). Core's own button on the order
 * view reads the same filter, so both places follow.
 *
 * @param string[] $statuses
 * @return string[]
 */
add_filter( 'woocommerce_valid_order_statuses_for_order_again', function ( $statuses ) {
	return array_values( array_unique( array_merge( (array) $statuses, array( 'completed', 'processing', 'on-hold' ) ) ) );
} );

/**
 * Pay on the order view as well as on the orders list.
 *
 * The list gets its Pay button from wc_get_account_orders_actions(); the
 * single order view had none (Scott Bale, 19 November 2026). Same actions
 * and the same rules, so the two cannot disagree. View is left out because
 * this is the view, and Order again because core prints its own button
 * under the order.
 *
 * @param int $order_id
 */
add_action( 'woocommerce_view_order', function ( $order_id ): void {
	$order = wc_get_order( $order_id );

	if ( ! $order instanceof WC_Order || ! function_exists( 'wc_get_account_orders_actions' ) ) {
		return;
	}

	$actions = array_diff_key( wc_get_account_orders_actions( $order ), array_flip( array( 'view', 'order-again' ) ) );

	if ( ! isset( $actions['pay'] ) ) {
		return;
	}

	echo '<div class="dt-order-actions">';
	foreach ( $actions as $key => $action ) {
		printf(
			'<a href="%1$s" class="woocommerce-button button %2$s">%3$s</a>',
			esc_url( $action['url'] ),
			esc_attr( sanitize_html_class( $key ) ),
			esc_html( $action['name'] )
		);
	}
	echo '</div>';
}, 5 );

/**
 * Order again adds to the basket instead of replacing it.
 *
 * Core empties the basket first, so anything the customer had already put in
 * was lost without a word (dev QA, 25 November 2026). Now the old order goes in
 * alongside it.
 *
 * Core keys each line by product and options, so a product already in the
 * basket on the same terms would be overwritten by the old order's line rather
 * than added to. The basket as it stood is noted here, before core starts, and
 * the next filter adds the two quantities together.
 *
 * @return bool
 */
add_filter( 'woocommerce_empty_cart_when_order_again', function (): bool {
	$GLOBALS['dorotape_basket_before_order_again'] = WC()->session ? (array) WC()->session->get( 'cart', array() ) : array();
	$GLOBALS['dorotape_order_again_merged']        = 0;

	return false;
} );

/**
 * Add the basket's quantity to the old order's line when both are the same
 * product on the same terms.
 *
 * After the quantity step filter above, so the old order's amount is already
 * a whole number of steps, and so is the basket's, so the sum is too.
 *
 * @param array  $cart_item
 * @param string $cart_id
 * @return array
 */
add_filter( 'woocommerce_add_order_again_cart_item', function ( $cart_item, $cart_id ) {
	$before = $GLOBALS['dorotape_basket_before_order_again'] ?? array();

	if ( isset( $before[ $cart_id ]['quantity'] ) ) {
		$cart_item['quantity'] = dorotape_stock_amount( (float) $cart_item['quantity'] + (float) $before[ $cart_id ]['quantity'] );
		$GLOBALS['dorotape_order_again_merged'] = ( $GLOBALS['dorotape_order_again_merged'] ?? 0 ) + 1;
	}

	return $cart_item;
}, 20, 2 );

/**
 * Core's notices, put right for a basket that was added to.
 *
 * Core counts lines that failed to go in as "lines on the old order minus new
 * lines in the basket", so a line that joined one already there was reported
 * as unavailable. The count is corrected, and the notice dropped when nothing
 * was missing. "The cart has been filled" no longer describes what happened.
 */
add_filter( 'woocommerce_add_error', function ( $message ) {
	$merged = (int) ( $GLOBALS['dorotape_order_again_merged'] ?? 0 );

	if ( ! dorotape_is_order_again() || $merged < 1 || ! is_string( $message ) || ! preg_match( '/^(\d+) items? from your previous order/', $message, $m ) ) {
		return $message;
	}

	$missing = (int) $m[1] - $merged;

	if ( $missing < 1 ) {
		return '';
	}

	return sprintf(
		/* translators: %d item count */
		_n(
			'%d item from your previous order is currently unavailable and could not be added to your cart.',
			'%d items from your previous order are currently unavailable and could not be added to your cart.',
			$missing,
			'woocommerce'
		),
		$missing
	);
} );

add_filter( 'woocommerce_add_success', function ( $message ) {
	if ( dorotape_is_order_again() && __( 'The cart has been filled with the items from your previous order.', 'woocommerce' ) === $message ) {
		return __( 'The items from your previous order have been added to your basket.', 'dorotape' );
	}

	return $message;
} );

/**
 * When every line joined one already in the basket, core counts nothing added
 * and says nothing at all, so the customer lands on the basket with no word of
 * what changed. Say it here instead.
 *
 * @param int   $order_id
 * @param array $order_items
 * @param array $cart
 */
add_action( 'woocommerce_ordered_again', function ( $order_id, $order_items, $cart ): void {
	$before = $GLOBALS['dorotape_basket_before_order_again'] ?? array();

	if ( (int) ( $GLOBALS['dorotape_order_again_merged'] ?? 0 ) > 0 && count( (array) $cart ) <= count( $before ) ) {
		wc_add_notice( __( 'The items from your previous order have been added to your basket.', 'dorotape' ) );
	}
}, 10, 3 );
