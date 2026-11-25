<?php
declare( strict_types=1 );
/**
 * Only orders placed on this site go to Sage.
 *
 * Woosage sends every processing or completed order since its "Retrieve
 * orders from" date that it has not exported yet. The first sync on dev sent
 * the orders imported from the old site, which were already in Sage, and
 * Sage allocated stock to them and set up accounts for their customers
 * (Michael, 19 November 2026).
 *
 * Two kinds of order must never go:
 * - orders imported from the old site, which Sage already has;
 * - orders copied in with a database push from another copy of the site, such
 *   as local test orders arriving on dev, which carry no export flag either.
 *
 * So every new order is stamped with the site it was placed on, and Woosage
 * only gets orders whose stamp matches the site it runs on. The stamp is a
 * hash of the home URL, so the address swap a push does cannot rewrite it.
 * Orders with no stamp (everything placed before this went in) are skipped.
 * Skipped orders show "Skipped" in the Exported column and are never retried.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

const DOROTAPE_ORDER_SITE_META = '_dorotape_order_site';

/**
 * This site's stamp.
 */
function dorotape_order_site_stamp(): string {
	return md5( untrailingslashit( (string) get_option( 'home' ) ) );
}

add_action(
	'woocommerce_new_order',
	static function ( $order_id, $order = null ): void {
		$order = $order instanceof WC_Abstract_Order ? $order : wc_get_order( $order_id );

		if ( ! $order instanceof WC_Abstract_Order || $order->get_meta( DOROTAPE_ORDER_SITE_META ) ) {
			return;
		}

		$order->update_meta_data( DOROTAPE_ORDER_SITE_META, dorotape_order_site_stamp() );
		$order->save_meta_data();
	},
	10,
	2
);

/**
 * Why an order must not go to Sage, or an empty string when it may.
 *
 * @param mixed $order The order Woosage is about to send.
 */
function dorotape_sage_order_block_reason( $order ): string {
	if ( ! $order instanceof WC_Abstract_Order ) {
		return '';
	}

	if ( 'kryptronic-import' === $order->get_created_via() ) {
		return 'as it was imported from the old website and is already in Sage';
	}

	if ( dorotape_order_site_stamp() !== $order->get_meta( DOROTAPE_ORDER_SITE_META ) ) {
		return 'as it was not placed on this site (copied in from another copy of the site, or placed before 19 November 2026)';
	}

	return '';
}

add_filter(
	'woosage_process_order',
	static fn ( $process, $order ) => '' !== dorotape_sage_order_block_reason( $order ) ? false : $process,
	10,
	2
);

add_filter(
	'woosage_no_process_order_reason',
	static function ( $reason, $order ) {
		$mine = dorotape_sage_order_block_reason( $order );

		return '' !== $mine ? $mine : $reason;
	},
	10,
	2
);
