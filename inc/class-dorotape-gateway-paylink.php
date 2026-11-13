<?php
declare( strict_types=1 );
/**
 * "Email me a secure link so I can pay using my Credit Card, Debit Card or PayPal"
 *
 * The fourth of the old site's four payment methods, for customers who would
 * rather not read card details down a telephone and would rather not enter them
 * on a checkout they have not finished reading. The order is placed and we send
 * a payment link afterwards.
 *
 * The link itself is WooCommerce's own: every order carries a pay-for-order URL
 * at $order->get_checkout_payment_url(), which is already in the customer's
 * order email and on the order in their account. So this method does not have
 * to build or store a link of its own, and the one it points at keeps working
 * if the card gateway behind it is ever swapped.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/class-dorotape-gateway-offline.php';

/**
 * Pay by emailed link.
 */
class Dorotape_Gateway_Paylink extends Dorotape_Gateway_Offline {

	public function __construct() {
		$this->id = 'dorotape_paylink';

		parent::__construct();
	}

	/**
	 * @return array{title: string, description: string, instructions: string, method_title: string, method_description: string}
	 */
	protected function defaults(): array {
		return array(
			'method_title'       => __( 'Pay by emailed link (Dorotape)', 'dorotape' ),
			'method_description' => __( 'Places the order and holds it while the customer pays through the link in their order email.', 'dorotape' ),
			'title'              => __( 'Email me a secure link so I can pay using my Credit Card, Debit Card or PayPal', 'dorotape' ),
			'description'        => __( 'We will email you a secure payment link. Your order is held until it has been paid.', 'dorotape' ),
			'instructions'       => __( 'Thank you for your order. We have emailed you a secure link so you can pay by credit card, debit card or PayPal. Your order will be dispatched once payment has been received.', 'dorotape' ),
		);
	}

	/**
	 * Mark the order as one that is paid through its link.
	 *
	 * The mark outlives the payment method on the order: once the customer opens
	 * the link and picks the card method, WooCommerce stores that method instead,
	 * and a cancelled card attempt must not leave them with a link that no longer
	 * opens.
	 *
	 * @param int $order_id
	 * @return array{result: string, redirect: string}
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			$order->update_meta_data( DOROTAPE_PAYLINK_META, 'yes' );
			$order->save();
		}

		return parent::process_payment( $order_id );
	}

	/**
	 * Put the payment link in front of the customer as well as in their email.
	 *
	 * The instructions promise a link, so the order received page should carry
	 * one rather than asking someone to go and look for it. Same URL the email
	 * uses, so there is one link and one place it can break.
	 */
	public function thankyou_page(): void {
		parent::thankyou_page();

		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : null;

		if ( ! $order instanceof WC_Order || ! dorotape_order_awaits_paylink( $order ) ) {
			return;
		}

		printf(
			'<p><a class="btn btn--primary" href="%s">%s</a></p>',
			esc_url( $order->get_checkout_payment_url() ),
			esc_html__( 'Pay for this order now', 'dorotape' )
		);
	}

	/**
	 * The link itself, in the customer's email.
	 *
	 * WooCommerce only prints a payment link in the invoice email, which is sent
	 * by hand. The on-hold email this order gets has none, so without this the
	 * customer is told a link has been emailed and never receives one.
	 *
	 * @param WC_Order $order
	 * @param bool     $sent_to_admin
	 * @param bool     $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ): void {
		parent::email_instructions( $order, $sent_to_admin, $plain_text );

		if ( $sent_to_admin || ! $order instanceof WC_Order || ! dorotape_order_awaits_paylink( $order ) ) {
			return;
		}

		$url   = $order->get_checkout_payment_url();
		$label = __( 'Pay for this order now', 'dorotape' );

		if ( $plain_text ) {
			echo esc_html( $label ) . ': ' . esc_url_raw( $url ) . PHP_EOL . PHP_EOL;
			return;
		}

		printf( '<p><a href="%s"><strong>%s</strong></a></p>', esc_url( $url ), esc_html( $label ) );
	}
}
