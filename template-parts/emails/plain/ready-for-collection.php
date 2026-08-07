<?php
/**
 * "Ready for collection" email, plain text
 *
 * @package dorotape
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string   $additional_content
 * @var string   $collection_address
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

/* translators: %s: customer first name. */
printf( esc_html__( 'Hi %s,', 'dorotape' ), esc_html( $order->get_billing_first_name() ) );
echo "\n\n";

/* translators: %s: order number. */
printf( esc_html__( 'Order %s is ready and waiting for you to collect.', 'dorotape' ), esc_html( $order->get_order_number() ) );
echo "\n\n";

if ( $collection_address ) {
	echo esc_html__( 'Where to collect', 'dorotape' ) . "\n";
	echo esc_html( dorotape_notice_to_plain( $collection_address ) ) . "\n\n";
}

echo "----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
