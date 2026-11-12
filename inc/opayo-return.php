<?php
/**
 * Coming back from Opayo without paying.
 *
 * When a customer cancels on Opayo's card page, or the card is declined, the
 * Opayo Form plugin queues "Transaction Failed. The Error Message was 2013 :
 * The Transaction was cancelled by the customer." and sends them to checkout.
 * QA (12 Nov) found three problems with that:
 *
 * - The Checkout block does not print queued notices, so checkout said
 *   nothing and the message turned up on whatever page came next.
 * - The wording is Opayo's, not ours.
 * - Nothing was written on the order, so the admin could not see why an order
 *   sat at Pending payment.
 *
 * The plugin's code is not edited. Its return handler runs on
 * woocommerce_api_woocommerce_sagepayform at priority 10; this file reads the
 * same response first, at priority 5, and changes nothing about what the
 * plugin then does with it.
 *
 * @package Dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Opayo status of a failed return in this request, e.g. "ABORT", or ''.
 *
 * @param string|null $set Status to remember, when called from the reader.
 * @return string
 */
function dorotape_opayo_failed_status( ?string $set = null ): string {
	static $status = '';
	if ( null !== $set ) {
		$status = $set;
	}
	return $status;
}

/**
 * Note an unpaid return on its order, and remember the status for the notice.
 */
function dorotape_opayo_read_return(): void {
	if ( empty( $_REQUEST['crypt'] ) || ! function_exists( 'WC' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Opayo's signed response, checked by decryption.
		return;
	}

	$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
	$gateway  = $gateways['sagepayform'] ?? null;
	if ( ! $gateway || ! method_exists( $gateway, 'decode' ) ) {
		return;
	}

	$crypt    = str_replace( ' ', '+', (string) wp_unslash( $_REQUEST['crypt'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- as above; decrypted, not output.
	$response = $gateway->decode( $crypt );
	$status   = isset( $response['Status'] ) ? (string) $response['Status'] : '';
	$tx_code  = isset( $response['VendorTxCode'] ) ? (string) $response['VendorTxCode'] : '';

	if ( '' === $status || '' === $tx_code || in_array( $status, array( 'OK', 'AUTHENTICATED', 'REGISTERED' ), true ) ) {
		return;
	}

	dorotape_opayo_failed_status( $status );

	$order = dorotape_opayo_order_for( $tx_code );
	if ( ! $order ) {
		return;
	}

	// Reloading the return page sends the same response again; note it once.
	$seen = $tx_code . '|' . $status;
	if ( $order->get_meta( '_dt_opayo_last_failure' ) === $seen ) {
		return;
	}

	$detail = isset( $response['StatusDetail'] ) ? sanitize_text_field( (string) $response['StatusDetail'] ) : '';
	$order->add_order_note(
		sprintf(
			/* translators: 1: what happened, 2: Opayo's status code, 3: Opayo's own message. */
			__( 'Opayo: %1$s The customer was sent back to checkout and no payment was taken. Opayo status %2$s: %3$s', 'dorotape' ),
			'ABORT' === $status ? __( 'the customer cancelled the card payment.', 'dorotape' ) : __( 'the card payment did not go through.', 'dorotape' ),
			sanitize_text_field( $status ),
			$detail
		)
	);
	$order->update_meta_data( '_dt_opayo_last_failure', $seen );
	$order->save();
}
add_action( 'woocommerce_api_woocommerce_sagepayform', 'dorotape_opayo_read_return', 5 );

/**
 * The order an Opayo VendorTxCode belongs to, found the way the plugin finds it.
 *
 * @param string $tx_code VendorTxCode from the response.
 * @return WC_Order|null
 */
function dorotape_opayo_order_for( string $tx_code ): ?WC_Order {
	$orders = wc_get_orders(
		array(
			'limit'      => 1,
			'orderby'    => 'none',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- one row, on a payment return only.
				array(
					'key'   => '_sagepay_vendor_tx_code',
					'value' => $tx_code,
				),
			),
		)
	);
	if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
		return $orders[0];
	}

	if ( preg_match( '/-(\d{12})-(.+)$/', $tx_code, $m ) ) {
		$order = wc_get_order( $m[2] );
		return $order instanceof WC_Order ? $order : null;
	}

	return null;
}

/**
 * Opayo's failure notice, in plain words.
 *
 * Only the notice raised in the same request as a failed return is changed,
 * so every other error keeps its own text.
 *
 * @param string|mixed $message The notice.
 * @return string|mixed
 */
function dorotape_opayo_friendly_notice( $message ) {
	$status = dorotape_opayo_failed_status();
	if ( '' === $status || ! is_string( $message ) ) {
		return $message;
	}

	if ( 'ABORT' === $status ) {
		return __( 'Your card payment was cancelled, so nothing has been taken. Your basket is still here: choose how to pay below to try again.', 'dorotape' );
	}

	return __( 'Your card payment did not go through, so nothing has been taken. Please try again, or choose another way to pay below.', 'dorotape' );
}
add_filter( 'woocommerce_add_error', 'dorotape_opayo_friendly_notice' );

/**
 * Print queued notices above the Checkout block.
 *
 * Classic checkout printed them itself; the block leaves them in the session,
 * where they surface on the next classic page instead. Anything a payment
 * plugin queues before redirecting to checkout ends up here, not only Opayo's.
 * Printing them clears them, so they are not shown twice.
 *
 * @param string|mixed $content The block's markup.
 * @return string|mixed
 */
function dorotape_checkout_block_notices( $content ) {
	if ( is_admin() || ! function_exists( 'wc_notice_count' ) || ! WC()->session || ! wc_notice_count() ) {
		return $content;
	}

	return '<div class="woocommerce-notices-wrapper dt-checkout-notices">' . wc_print_notices( true ) . '</div>' . $content;
}
add_filter( 'render_block_woocommerce/checkout', 'dorotape_checkout_block_notices' );
