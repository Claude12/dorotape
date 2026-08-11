<?php
/**
 * "Your order is pending an account payment" email
 *
 * @package dorotape
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Dorotape_Email_Account_Pending' ) ) {
	return;
}

/**
 * Sent when an order is accepted but held for an account payment.
 *
 * This email is not optional decoration. WooCommerce sends a customer email on
 * its own statuses only, so an order that goes straight into a status the theme
 * invented gets no confirmation at all: the customer clicks Place order, lands on
 * the thank-you page, and then hears nothing. That is worse than the behaviour
 * this whole feature replaced.
 *
 * Templates live in the theme's own template-parts/emails/ rather than in a
 * woocommerce/ override directory. The theme deliberately has no such directory
 * (see README), and a custom email does not need one: passing an explicit default
 * path to wc_get_template_html() resolves it without one, while still letting a
 * child theme override it the usual way. Same arrangement as
 * Dorotape_Email_Ready_For_Collection.
 */
class Dorotape_Email_Account_Pending extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'dorotape_account_pending';
		$this->customer_email = true;
		$this->title          = __( 'Order pending account payment', 'dorotape' );
		$this->description    = __( 'Sent to the customer when their order has been accepted on account but is held until a payment is made against the account.', 'dorotape' );

		$this->template_html  = 'emails/account-pending.php';
		$this->template_plain = 'emails/plain/account-pending.php';
		$this->template_base  = get_template_directory() . '/template-parts/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// The status transition. WC_Emails only fires a _notification action for
		// hooks listed in woocommerce_email_actions, which inc/account-pending.php
		// adds this one to. Without that, this method is simply never called.
		add_action( 'woocommerce_order_status_' . DOROTAPE_ACCOUNT_PENDING_STATUS . '_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * No mention of a payment being owed. The subject line is read on a phone in
	 * front of other people, and the detail belongs inside.
	 */
	public function get_default_subject() {
		return __( 'Your order {order_number} is on our system', 'dorotape' );
	}

	/**
	 * Default heading.
	 */
	public function get_default_heading() {
		return __( 'Thank you for your order', 'dorotape' );
	}

	/**
	 * Content shown below the order table, editable in WooCommerce settings.
	 *
	 * Left without a phone number or an email address. Nobody has told us which
	 * one to send account queries to, and an invented contact is worse than
	 * none. This is the first thing to fill in once they say.
	 */
	public function get_default_additional_content() {
		return __( 'If you have any questions about your account, please get in touch with us and we will be happy to help.', 'dorotape' );
	}

	/**
	 * Send it.
	 *
	 * @param int      $order_id
	 * @param WC_Order $order
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->recipient                      = $order->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * HTML body.
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'pending_message'    => dorotape_account_pending_message(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Plain text body.
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'pending_message'    => dorotape_account_pending_message(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}
}
