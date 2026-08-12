<?php
/**
 * "Your order has been dispatched" email
 *
 * @package dorotape
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Dorotape_Email_Dispatched' ) ) {
	return;
}

/**
 * Sent when a delivery order reaches Completed, which is what Sage marks an
 * order when it is dispatched. See inc/dispatch.php for why there is no
 * Dispatched status of our own.
 *
 * Collection orders never get this. They reach Completed too, on being collected,
 * and inc/dispatch.php leaves those to WooCommerce's completed email.
 *
 * Templates live in the theme's own template-parts/emails/ rather than in a
 * woocommerce/ override directory, the same way the collection email does. The
 * theme deliberately has no such directory (see README), and passing an explicit
 * default path to wc_get_template_html() resolves the template without one while
 * still letting a child theme override it the usual way.
 */
class Dorotape_Email_Dispatched extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'dorotape_dispatched';
		$this->customer_email = true;
		$this->title          = __( 'Order dispatched', 'dorotape' );
		$this->description    = __( 'Sent to the customer when a delivery order is dispatched, which is when Sage marks it Completed. Collection orders get the standard completed order email instead.', 'dorotape' );

		$this->template_html  = 'emails/dispatched.php';
		$this->template_plain = 'emails/plain/dispatched.php';
		$this->template_base  = get_template_directory() . '/template-parts/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Fires for a move into Completed from any previous status. WooCommerce
		// lists `woocommerce_order_status_completed` in its own email actions, so
		// unlike the collection email this needs no filter to be fired at all.
		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 */
	public function get_default_subject() {
		return __( 'Your order {order_number} has been dispatched', 'dorotape' );
	}

	/**
	 * Default heading.
	 */
	public function get_default_heading() {
		return __( 'Your order is on its way', 'dorotape' );
	}

	/**
	 * Content shown below the order table, editable in WooCommerce settings.
	 */
	public function get_default_additional_content() {
		return __( 'If anything is not right with this delivery, reply to this email quoting your order number and we will sort it out.', 'dorotape' );
	}

	/**
	 * Send it.
	 *
	 * The order-type check lives here rather than in the enabled filter used for
	 * WooCommerce's completed email. That filter is the right tool for turning an
	 * existing email off per order; using it to turn this one on would report this
	 * email as disabled on the settings screen, where there is no order to judge.
	 *
	 * @param int      $order_id
	 * @param WC_Order $order
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) && dorotape_order_is_dispatched( $order ) ) {
			$this->object                         = $order;
			$this->recipient                      = $order->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
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
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}
}
