<?php
/**
 * "Your order is ready for collection" email
 *
 * @package dorotape
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Dorotape_Email_Ready_For_Collection' ) ) {
	return;
}

/**
 * Sent when an order moves to Ready for Collection.
 *
 * Templates live in the theme's own template-parts/emails/ rather than in a
 * woocommerce/ override directory. The theme deliberately has no such directory
 * (see README), and a custom email does not need one: passing an explicit
 * default path to wc_get_template_html() resolves it without one, while still
 * letting a child theme override it the usual way.
 */
class Dorotape_Email_Ready_For_Collection extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'dorotape_ready_for_collection';
		$this->customer_email = true;
		$this->title          = __( 'Ready for collection', 'dorotape' );
		$this->description    = __( 'Sent to the customer when their order is marked Ready for Collection, telling them where and when to collect it.', 'dorotape' );

		$this->template_html  = 'emails/ready-for-collection.php';
		$this->template_plain = 'emails/plain/ready-for-collection.php';
		$this->template_base  = get_template_directory() . '/template-parts/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// The status transition. WC_Emails only fires a _notification action for
		// hooks listed in woocommerce_email_actions, which inc/collection.php adds
		// this one to. Without that, this method is simply never called.
		add_action( 'woocommerce_order_status_' . DOROTAPE_COLLECTION_STATUS . '_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 */
	public function get_default_subject() {
		return __( 'Your order {order_number} is ready for collection', 'dorotape' );
	}

	/**
	 * Default heading.
	 */
	public function get_default_heading() {
		return __( 'Your order is ready for collection', 'dorotape' );
	}

	/**
	 * Content shown below the order table, editable in WooCommerce settings.
	 */
	public function get_default_additional_content() {
		return __( 'Please bring your order number with you. If anyone else is collecting on your behalf, let us know in advance.', 'dorotape' );
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
				'order'               => $this->object,
				'email_heading'       => $this->get_heading(),
				'additional_content'  => $this->get_additional_content(),
				'collection_address'  => dorotape_get_collection_address(),
				'sent_to_admin'       => false,
				'plain_text'          => false,
				'email'               => $this,
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
				'order'               => $this->object,
				'email_heading'       => $this->get_heading(),
				'additional_content'  => $this->get_additional_content(),
				'collection_address'  => dorotape_get_collection_address(),
				'sent_to_admin'       => false,
				'plain_text'          => true,
				'email'               => $this,
			),
			'',
			$this->template_base
		);
	}
}
