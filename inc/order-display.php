<?php
declare( strict_types=1 );
/**
 * How an order reads once it has been placed
 *
 * Two fixes from Site Edits 2 (Woocommerce rows 5 and 6, November 2026):
 *
 *  - The payment method. At checkout each method is a sentence the customer
 *    picks ("I'm happy to pay now using my Credit Card, Debit Card or
 *    PayPal"), and WooCommerce stores that sentence on the order. On the thank
 *    you page, in the emails and in My Account it then read as
 *    "Payment method: I'm happy to pay now...". After the order is placed it
 *    shows a short name instead. The checkout keeps the full sentences,
 *    because those come from the gateway, not the order.
 *
 *  - The email logo. The logo has white lettering, made for the site's dark
 *    header, so on WooCommerce's white email half of it disappeared. The logo
 *    now sits on a navy band in the site's own header colour.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Short payment method names, by gateway id.
 *
 * @return array<string, string>
 */
function dorotape_payment_method_short_titles(): array {
	return array(
		DOROTAPE_CARD_GATEWAY_ID => __( 'Card or PayPal', 'dorotape' ),
		'igfw_invoice_gateway'   => __( 'On account', 'dorotape' ),
		'dorotape_phone'         => __( 'Card payment by phone', 'dorotape' ),
		'dorotape_paylink'       => __( 'Secure payment link by email', 'dorotape' ),
		'cod'                    => __( 'Pay on collection', 'dorotape' ),
	);
}

/**
 * Show the short name wherever an order's payment method is displayed.
 *
 * The hook only runs for the "view" context, so what is stored on the order
 * is untouched, and an editor saving the order in admin keeps the original.
 *
 * @param string   $title
 * @param WC_Order $order
 * @return string
 */
add_filter( 'woocommerce_order_get_payment_method_title', function ( $title, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $title;
	}

	$short = dorotape_payment_method_short_titles();

	return $short[ $order->get_payment_method() ] ?? $title;
}, 10, 2 );

/**
 * The navy band behind the logo in every WooCommerce email.
 *
 * WooCommerce inlines this onto the elements, so it holds in email clients
 * that ignore a style block.
 *
 * @param string $css
 * @return string
 */
add_filter( 'woocommerce_email_styles', function ( $css ): string {
	return (string) $css . '
#template_header_image { background-color: #131d2e; padding: 24px 32px; }
#template_header_image p { margin: 0; }
';
}, 20 );
