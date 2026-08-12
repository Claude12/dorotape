<?php
/**
 * "Order dispatched" email, HTML
 *
 * Resolved through the theme's own template-parts directory rather than a
 * woocommerce/ override folder. See Dorotape_Email_Dispatched.
 *
 * DR-31's courier tracking has a place here already: it hooks
 * `woocommerce_email_order_meta`, fired below, which is where the shipment
 * details belong on both this email and every other one carrying an order.
 *
 * @package dorotape
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string   $additional_content
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php
	printf(
		/* translators: %s: customer first name. */
		esc_html__( 'Hi %s,', 'dorotape' ),
		esc_html( $order->get_billing_first_name() )
	);
?></p>

<p><?php
	printf(
		/* translators: %s: order number. */
		esc_html__( 'Order %s has left us and is on its way to you.', 'dorotape' ),
		esc_html( $order->get_order_number() )
	);
?></p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
