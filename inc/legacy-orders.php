<?php
/**
 * Orders brought across from the old website
 *
 * The last 12 months of Kryptronic orders are imported by
 * dorotape-migration/import_orders.php, each carrying its old order id
 * (DT202510062 and the like) in _kryptronic_order_id. That id is the one on
 * the customer's confirmation email, their invoice and Sage, so it is the
 * number shown for those orders everywhere WooCommerce prints one, and the
 * admin order search finds an order by it. Orders placed on this site keep
 * WooCommerce's own number.
 *
 * @package dorotape
 */

add_filter( 'woocommerce_order_number', function ( $number, $order ) {
	$legacy = $order instanceof WC_Order ? $order->get_meta( '_kryptronic_order_id' ) : '';
	return $legacy ? $legacy : $number;
}, 10, 2 );

// HPOS order search: match the old order id as well as the address indexes.
add_filter( 'woocommerce_order_table_search_query_meta_keys', function ( $keys ) {
	$keys[] = '_kryptronic_order_id';
	return $keys;
} );
