<?php
declare( strict_types=1 );
/**
 * Base class for the two "we will take payment separately" methods.
 *
 * The old Kryptronic checkout offered four ways to pay, and two of them do not
 * take money at the checkout at all: one asks us to ring the customer for card
 * details, the other asks us to email them a payment link. Both place the order
 * and leave it awaiting payment, so apart from their wording they behave
 * identically, and that behaviour lives here rather than twice over.
 *
 * Modelled on WooCommerce's own WC_Gateway_BACS, which is the canonical shape
 * for a gateway that confirms an order without charging for it: the same
 * on-hold status, the same filter over it, the same stock call, the same
 * instructions on the thank you page and in the customer's email. Following it
 * exactly rather than inventing a flow means anything that already understands
 * BACS - reporting, the Sage push in Stage 3, a future payment plugin -
 * understands these too.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * An order placed now, paid for later, by arrangement.
 */
abstract class Dorotape_Gateway_Offline extends WC_Payment_Gateway {

	/**
	 * What the customer is told once the order is placed.
	 *
	 * @var string
	 */
	public $instructions = '';

	/**
	 * The defaults a fresh install starts with.
	 *
	 * Separate from init_form_fields() so the subclasses only have to answer
	 * what they are, not how a settings screen is built.
	 *
	 * @return array{title: string, description: string, instructions: string, method_title: string, method_description: string}
	 */
	abstract protected function defaults(): array;

	public function __construct() {
		$copy = $this->defaults();

		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = $copy['method_title'];
		$this->method_description = $copy['method_description'];

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * The settings screen.
	 *
	 * The wording is a setting rather than a constant because it is the
	 * client's sentence, not ours, and changing it should not need a deploy.
	 * The defaults are the old site's wording so a fresh install is already
	 * right and nobody has to retype it.
	 */
	public function init_form_fields(): void {
		$copy = $this->defaults();

		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'dorotape' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this payment method', 'dorotape' ),
				'default' => 'yes',
			),
			'title'        => array(
				'title'       => __( 'Title', 'dorotape' ),
				'type'        => 'text',
				'description' => __( 'The line the customer picks at checkout.', 'dorotape' ),
				'default'     => $copy['title'],
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'dorotape' ),
				'type'        => 'textarea',
				'description' => __( 'Shown under the title once this method is selected.', 'dorotape' ),
				'default'     => $copy['description'],
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'dorotape' ),
				'type'        => 'textarea',
				'description' => __( 'Shown on the order received page and in the order email.', 'dorotape' ),
				'default'     => $copy['instructions'],
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Place the order without taking any money for it.
	 *
	 * On hold rather than processing, because processing means paid and these
	 * are not. The filter is here for the Sage work in Stage 3, which may want
	 * pending instead so an unpaid order never reaches the warehouse.
	 *
	 * @param int $order_id
	 * @return array{result: string, redirect: string}
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			/**
			 * Filter the status an order takes when it is awaiting payment.
			 *
			 * @param string   $status
			 * @param WC_Order $order
			 */
			$order->update_status(
				apply_filters( 'dorotape_offline_payment_order_status', 'on-hold', $order ),
				$this->awaiting_note()
			);
		} else {
			$order->payment_complete();
		}

		// Idempotent: it checks the order's own stock-reduced flag first, and
		// WooCommerce fires the same call again on the on-hold transition.
		wc_reduce_stock_levels( $order_id );

		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * The order note left when the order is placed.
	 */
	protected function awaiting_note(): string {
		/* translators: %s: the payment method's title. */
		return sprintf( __( 'Awaiting payment: %s.', 'dorotape' ), $this->title );
	}

	/**
	 * Instructions on the order received page.
	 */
	public function thankyou_page(): void {
		if ( '' === trim( (string) $this->instructions ) ) {
			return;
		}

		echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
	}

	/**
	 * The same instructions in the customer's email.
	 *
	 * Only on the customer's own mail, and only while the order is still
	 * waiting: a completed order telling someone we are about to ring them for
	 * card details is worse than saying nothing.
	 *
	 * @param WC_Order $order
	 * @param bool     $sent_to_admin
	 * @param bool     $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ): void {
		if ( $sent_to_admin || '' === trim( (string) $this->instructions ) ) {
			return;
		}

		if ( $this->id !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) ) {
			return;
		}

		echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
	}
}
