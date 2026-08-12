<?php
/**
 * The dispatched stage
 *
 * Spec 5.8 asks for a notification when an order is dispatched. There is no
 * Dispatched order status, and there should not be one: Sage already owns that
 * signal. When the goods leave, Sage pushes the order back through the Woosage
 * REST API, which does exactly this:
 *
 *     $order->set_status( 'completed', 'Order marked as completed (dispatched) in Sage.', true );
 *
 * (Classes/REST_API/Controllers/V1/Orders.php). So Completed *is* dispatched
 * here, set by the warehouse's own system rather than by anybody clicking in WP
 * admin. Adding a Dispatched status would create a second, emptier version of a
 * fact Sage is already reporting, and every order would have to be dragged
 * through it by hand to keep the two in step. This file changes what Completed
 * *says* instead of adding a stage in front of it.
 *
 * The wording has to split two ways, because Completed arrives on both routes:
 *
 *   delivery   goods handed to a courier. "Dispatched" is right, and this is
 *              where DR-31's tracking details will belong.
 *   collection the customer has been and picked the order up. Nothing was
 *              dispatched, and the stock email currently tells them their order
 *              is "on its way", which is plainly wrong for somebody who has just
 *              carried it out of the building.
 *
 * So a delivery order gets the theme's own dispatch email *instead of*
 * WooCommerce's completed one, and a collection order keeps WooCommerce's email
 * with its wording corrected. Never both: the point is one message per stage.
 *
 * @package dorotape
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is this an order that gets dispatched, as opposed to collected?
 *
 * Three answers, not two. An order with a Local Pickup line is collected, an
 * order with some other shipping line is dispatched, and an order carrying no
 * shipping line at all is neither as far as this file is concerned.
 *
 * That last case is the one worth being careful about. Nothing was shipped, so
 * "dispatched" would be a claim about a van that does not exist, and defaulting
 * it to true purely because it is not a collection would put that claim in front
 * of a customer. It falls back to WooCommerce's own completed email, which says
 * something true about any order.
 *
 * @param WC_Order|int $order
 * @return bool
 */
function dorotape_order_is_dispatched( $order ): bool {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );

	if ( ! $order ) {
		return false;
	}

	if ( ! $order->get_shipping_methods() ) {
		return false;
	}

	return ! dorotape_order_is_collection( $order );
}

// ─── One message per stage ────────────────────────────────────────────────────

/**
 * Hold back WooCommerce's completed email on the orders that get ours.
 *
 * `woocommerce_email_enabled_{$id}` is checked inside send_notification(), after
 * trigger() has set $this->object, so the order is available here and the
 * decision can be made per order rather than per shop.
 *
 * The instanceof check is not a formality. The same filter runs from the email
 * settings screen and from the preview, where there is no order, and answering
 * "disabled" there would show a shop manager an email switched off that they
 * never switched off and cannot meaningfully switch back on. With no order to
 * judge, the shop's own setting stands.
 *
 * @param bool           $enabled
 * @param WC_Order|mixed $order
 * @return bool
 */
add_filter(
	'woocommerce_email_enabled_customer_completed_order',
	function ( $enabled, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $enabled;
		}

		return $enabled && ! dorotape_order_is_dispatched( $order );
	},
	10,
	2
);

// ─── Collection orders: the same email, telling the truth ─────────────────────

/**
 * Has the shop manager written their own wording for this field?
 *
 * WC_Email reads `subject` and `heading` from the shop's settings and falls back
 * to the class default. Both arrive at the filters below already resolved, so
 * there is no way to tell a manager's carefully chosen subject from the stock one
 * without comparing against the default and seeing whether it moved.
 *
 * It matters because the corrections below only exist to stop the *stock* wording
 * making a false claim. If somebody has since written their own, replacing it for
 * half the orders would be a change nobody asked for and nothing on screen would
 * explain, so theirs stands.
 *
 * @param string   $value
 * @param WC_Email $email
 * @param string   $method get_default_subject or get_default_heading
 * @return bool
 */
function dorotape_email_wording_is_stock( string $value, WC_Email $email, string $method ): bool {
	return $value === $email->format_string( $email->$method() );
}

/**
 * Say "complete", not "on its way", to somebody who came and collected it.
 *
 * WooCommerce 11 ships this email as "Your order from {site_title} is on its
 * way!" over "Good things are heading your way!", which reads as a courier
 * update. For a collection order both sentences are false.
 *
 * @param string         $subject
 * @param WC_Order|mixed $order
 * @param WC_Email       $email
 * @return string
 */
add_filter(
	'woocommerce_email_subject_customer_completed_order',
	function ( $subject, $order, $email ) {
		if ( ! $order instanceof WC_Order || ! dorotape_order_is_collection( $order ) ) {
			return $subject;
		}

		if ( ! dorotape_email_wording_is_stock( $subject, $email, 'get_default_subject' ) ) {
			return $subject;
		}

		return sprintf(
			/* translators: %s: order number. */
			__( 'Order %s is complete', 'dorotape' ),
			$order->get_order_number()
		);
	},
	10,
	3
);

/**
 * The same correction, in the heading.
 *
 * @param string         $heading
 * @param WC_Order|mixed $order
 * @param WC_Email       $email
 * @return string
 */
add_filter(
	'woocommerce_email_heading_customer_completed_order',
	function ( $heading, $order, $email ) {
		if ( ! $order instanceof WC_Order || ! dorotape_order_is_collection( $order ) ) {
			return $heading;
		}

		if ( ! dorotape_email_wording_is_stock( $heading, $email, 'get_default_heading' ) ) {
			return $heading;
		}

		return __( 'Thanks for collecting your order', 'dorotape' );
	},
	10,
	3
);

// ─── Delivery orders: the theme's own dispatch email ──────────────────────────

/**
 * Register it.
 *
 * No `woocommerce_email_actions` entry is needed, unlike the collection email:
 * `woocommerce_order_status_completed` is already in WooCommerce's own list
 * (WC_Emails::init_transactional_emails()), so the `_notification` action the
 * class hooks is fired for it. And it is the plain status hook rather than a
 * from_to transition, so it fires whichever status the order came from, which
 * includes Ready for Collection and Sage's own route into Completed.
 *
 * @param array $emails
 * @return array
 */
add_filter(
	'woocommerce_email_classes',
	function ( array $emails ): array {
		require_once get_template_directory() . '/inc/emails/class-dorotape-email-dispatched.php';
		$emails['Dorotape_Email_Dispatched'] = new Dorotape_Email_Dispatched();

		return $emails;
	}
);
