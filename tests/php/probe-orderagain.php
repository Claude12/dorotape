<?php
/**
 * DR-29: does Order again put back what was actually ordered?
 *
 * WooCommerce core rebuilds the basket in
 * WC_Cart_Session::populate_cart_from_order(). That method is private, so this
 * calls it through reflection rather than paraphrasing what it does, because a
 * paraphrase would only ever prove that the paraphrase agrees with itself.
 *
 * Four checks:
 *   1. cut sizes survive the round trip, and so do the separate lines
 *   2. a quantity the step rule would now refuse is corrected, not dropped
 *   3. the control: the step rule still refuses a bad quantity on a normal add
 *   4. checking out a re-ordered basket writes the cut sizes onto the new order
 *
 * Check 3 is the one that matters most. The fix relaxes the step validator
 * during a re-order, so without a control a fix that had quietly deleted the
 * rule altogether would still look like a pass.
 *
 * Run: php tests/php/probe-orderagain.php
 * Exits non-zero if anything fails. Every order and user it creates is deleted
 * again, including when a check fails.
 *
 * @package dorotape
 */

// ─── Boot WordPress from wherever the theme happens to be installed ──────────

$dir      = __DIR__;
$wp_load  = null;
$previous = null;
while ( $dir !== $previous ) {
	if ( file_exists( $dir . '/wp-load.php' ) ) {
		$wp_load = $dir . '/wp-load.php';
		break;
	}
	$previous = $dir;
	$dir      = dirname( $dir );
}
if ( ! $wp_load ) {
	fwrite( STDERR, "Could not find wp-load.php above " . __DIR__ . "\n" );
	exit( 1 );
}
require_once $wp_load;

if ( ! class_exists( 'WooCommerce' ) ) {
	fwrite( STDERR, "WooCommerce is not active\n" );
	exit( 1 );
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

$created  = array();
$user_id  = 0;
$failures = array();
$skipped  = array();

/**
 * Report a check and remember it if it failed.
 *
 * @param bool   $ok
 * @param string $what
 */
function dt_check( bool $ok, string $what ): void {
	global $failures;
	echo '  ' . ( $ok ? 'pass' : 'FAIL' ) . '  ' . $what . "\n";
	if ( ! $ok ) {
		$failures[] = $what;
	}
}

/**
 * Build a completed order for the probe customer.
 *
 * @param int   $user_id
 * @param array $lines Each: product, qty, and optionally cut.
 * @return WC_Order
 */
function dt_make_order( int $user_id, array $lines ): WC_Order {
	$order = wc_create_order( array( 'customer_id' => $user_id ) );
	foreach ( $lines as $line ) {
		$item_id = $order->add_product( wc_get_product( $line['product'] ), $line['qty'] );
		if ( ! empty( $line['cut'] ) ) {
			$item = $order->get_item( $item_id );
			// Written exactly as inc/cutsize.php writes it at checkout.
			$item->add_meta_data( 'Cut Sizes', $line['cut'], true );
			$item->add_meta_data( '_dt_cut_sizes_note', $line['cut'], true );
			$item->save();
		}
	}
	$order->calculate_totals();
	$order->set_status( 'completed' );
	$order->save();
	return $order;
}

/**
 * Run core's own basket rebuild for an order.
 *
 * @param int $order_id
 * @return array
 */
function dt_reorder( int $order_id ): array {
	// Core only reaches this method from a request carrying ?order_again=, and
	// it has verified the nonce by then. Set it so anything asking "are we in a
	// re-order?" sees what it would see in a real request.
	$_GET['order_again'] = $order_id;
	$method = new ReflectionMethod( 'WC_Cart_Session', 'populate_cart_from_order' );
	$method->setAccessible( true );
	$cart = $method->invoke( new WC_Cart_Session( WC()->cart ), $order_id, array() );
	unset( $_GET['order_again'] );
	return (array) $cart;
}

/**
 * Drain the notice queue and return it as plain lines.
 *
 * @return string[]
 */
function dt_notices(): array {
	$out = array();
	foreach ( wc_get_notices() as $type => $list ) {
		foreach ( $list as $notice ) {
			$out[] = trim( wp_strip_all_tags( is_array( $notice ) ? $notice['notice'] : $notice ) );
		}
	}
	wc_clear_notices();
	return $out;
}

/**
 * First simple, in-stock, published product matching a meta key, or null.
 *
 * Discovered rather than hardcoded so this runs against any copy of the site.
 *
 * @param string $meta_key
 * @param bool   $numeric_over_one Require the value to be a number above 1.
 * @return WC_Product|null
 */
function dt_find_product( string $meta_key, bool $numeric_over_one = false ): ?WC_Product {
	global $wpdb;
	$where = $numeric_over_one ? 'AND m.meta_value+0 > 1' : '';
	$ids   = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s $where
			 WHERE p.post_type = 'product' AND p.post_status = 'publish'
			 LIMIT 100",
			$meta_key
		)
	);
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'simple' === $product->get_type() && $product->is_in_stock() ) {
			return $product;
		}
	}
	return null;
}

// ─── Set up ──────────────────────────────────────────────────────────────────

$user_id = wp_insert_user(
	array(
		'user_login' => 'dt_probe_' . wp_generate_password( 8, false ),
		'user_pass'  => wp_generate_password(),
		'user_email' => 'dt_probe_' . wp_generate_password( 8, false ) . '@example.invalid',
		'role'       => 'customer',
	)
);
if ( is_wp_error( $user_id ) ) {
	fwrite( STDERR, "Could not create the probe customer: " . $user_id->get_error_message() . "\n" );
	exit( 1 );
}
wp_set_current_user( $user_id );
WC()->initialize_session();
WC()->initialize_cart();

echo "DR-29: re-ordering a past order\n\n";

try {
	// ── 1. Cut sizes ─────────────────────────────────────────────────────────
	echo "Cut sizes\n";
	$cut_product = dt_find_product( '_dt_cutsize_enabled' );
	if ( ! $cut_product ) {
		echo "  skipped, no simple in-stock product has the cut-size box\n";
		$skipped[] = 'cut sizes';
		$cart      = array();
	} else {
		// Order at valid multiples of this product's own step, so the step rule
		// cannot be what fails here and the cut sizes are the only variable.
		$step  = dorotape_qty_step( $cut_product );
		$qty_a = $step;
		$qty_b = $step * 2;

		$order = dt_make_order(
			$user_id,
			array(
				array( 'product' => $cut_product->get_id(), 'qty' => $qty_a, 'cut' => '500mm, 720mm (2 cuts)' ),
				array( 'product' => $cut_product->get_id(), 'qty' => $qty_b, 'cut' => '300mm, 300mm, 610mm (3 cuts)' ),
			)
		);
		$created[] = $order->get_id();
		echo "  " . $cut_product->get_name() . "\n";
		echo "  ordered 2 lines, " . ( $qty_a + $qty_b ) . " rolls, both cut to size\n";

		$cart      = dt_reorder( $order->get_id() );
		$total_qty = array_sum( wp_list_pluck( $cart, 'quantity' ) );
		$with_cuts = count( array_filter( $cart, fn( $line ) => ! empty( $line['dt_cut_sizes'] ) ) );
		echo "  got     " . count( $cart ) . " line(s), $total_qty roll(s), $with_cuts cut to size\n";
		foreach ( dt_notices() as $notice ) {
			echo "          \"$notice\"\n";
		}

		dt_check( 2 === count( $cart ), 'the two cut patterns stay on separate lines' );
		dt_check( $qty_a + $qty_b === $total_qty, 'no rolls are lost' );
		dt_check( 2 === $with_cuts, 'both lines still carry their cut sizes' );
	}

	// ── 2. A stale quantity against the step rule ────────────────────────────
	echo "\nQuantity step on a re-order\n";
	$step_product = dt_find_product( '_dt_qty_step', true );
	if ( ! $step_product ) {
		echo "  skipped, no simple in-stock product has a quantity step\n";
		$skipped[] = 'quantity step';
	} else {
		$step = dorotape_qty_step( $step_product );
		$odd  = $step + 1;

		$order = dt_make_order( $user_id, array( array( 'product' => $step_product->get_id(), 'qty' => $odd ) ) );
		$created[] = $order->get_id();
		echo "  " . $step_product->get_name() . "\n";
		echo "  ordered $odd, which is not a multiple of $step\n";

		$stepped = dt_reorder( $order->get_id() );
		$line    = reset( $stepped );
		echo "  got     " . ( $line ? $line['quantity'] : 'nothing, the line was dropped' ) . "\n";
		$notices = dt_notices();
		foreach ( $notices as $notice ) {
			echo "          \"$notice\"\n";
		}

		dt_check( 1 === count( $stepped ), 'the line survives instead of vanishing' );
		dt_check( $line && $step * 2 === (int) $line['quantity'], "$odd is rounded up to " . ( $step * 2 ) . ', not down' );
		dt_check(
			(bool) array_filter( $notices, fn( $n ) => false !== stripos( $n, 'basket as' ) ),
			'the customer is told the quantity changed'
		);
		dt_check(
			! array_filter( $notices, fn( $n ) => false !== stripos( $n, 'currently unavailable' ) ),
			'the customer is not wrongly told the product is unavailable'
		);

		// ── 3. The control ───────────────────────────────────────────────────
		echo "\nThe control: the step rule outside a re-order\n";
		$bad  = apply_filters( 'woocommerce_add_to_cart_validation', true, $step_product->get_id(), $odd, 0 );
		dt_notices();
		$good = apply_filters( 'woocommerce_add_to_cart_validation', true, $step_product->get_id(), $step * 2, 0 );
		dt_notices();
		dt_check( ! $bad, "an ordinary add to basket of $odd is still refused" );
		dt_check( (bool) $good, 'an ordinary add to basket of ' . ( $step * 2 ) . ' is still accepted' );
	}

	// ── 4. The round trip back onto an order ─────────────────────────────────
	echo "\nChecking out a re-ordered basket\n";
	if ( ! $cart ) {
		echo "  skipped, nothing in the basket to check out\n";
		$skipped[] = 'round trip';
	} else {
		// Restoring the note to the basket is only half of it. If checkout does
		// not write it back onto the new order, the warehouse still cuts nothing.
		$new_order = wc_create_order( array( 'customer_id' => $user_id ) );
		$created[] = $new_order->get_id();
		foreach ( $cart as $line ) {
			$item = new WC_Order_Item_Product();
			$item->set_product( $line['data'] );
			$item->set_quantity( $line['quantity'] );
			// The hook inc/cutsize.php registers, called as checkout calls it.
			do_action( 'woocommerce_checkout_create_order_line_item', $item, 'probe', $line, $new_order );
			$new_order->add_item( $item );
		}
		$new_order->save();

		$carried = 0;
		foreach ( $new_order->get_items() as $item ) {
			$hidden = $item->get_meta( '_dt_cut_sizes_note', true );
			$shown  = $item->get_meta( 'Cut Sizes', true );
			echo "  qty " . $item->get_quantity() . ": " . ( $shown ?: '(no cut sizes)' ) . "\n";
			if ( $hidden && $shown ) {
				++$carried;
			}
		}
		dt_check(
			count( $new_order->get_items() ) === $carried,
			'every line reaches the new order with its cut sizes, for admin, the email and Sage'
		);
	}
} finally {
	// ─── Clean up, including when a check above failed ───────────────────────
	foreach ( $created as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->delete( true );
		}
	}
	if ( $user_id && ! is_wp_error( $user_id ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
	}
	echo "\ncleaned up " . count( $created ) . " order(s) and the probe customer\n";
}

if ( $skipped ) {
	echo "skipped: " . implode( ', ', $skipped ) . "\n";
}
if ( $failures ) {
	echo "\n" . count( $failures ) . " check(s) failed:\n";
	foreach ( $failures as $failure ) {
		echo "  - $failure\n";
	}
	exit( 1 );
}
echo "all passed\n";
