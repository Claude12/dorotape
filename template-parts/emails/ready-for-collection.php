<?php
/**
 * "Ready for collection" email, HTML
 *
 * Resolved through the theme's own template-parts directory rather than a
 * woocommerce/ override folder. See Dorotape_Email_Ready_For_Collection.
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
		esc_html__( 'Order %s is ready and waiting for you to collect.', 'dorotape' ),
		esc_html( $order->get_order_number() )
	);
?></p>

<?php if ( $collection_address ) : ?>
	<h2><?php esc_html_e( 'Where to collect', 'dorotape' ); ?></h2>
	<p><?php echo wp_kses( $collection_address, array( 'br' => array() ) ); ?></p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
