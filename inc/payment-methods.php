<?php
declare( strict_types=1 );
/**
 * The payment method selection
 *
 * The old Kryptronic checkout offered four ways to pay, in this order:
 *
 *   1. I'm happy to pay now using my Credit Card, Debit Card or PayPal
 *   2. Add this order to my account
 *   3. Call me and I will provide my payment information over the telephone
 *   4. Email me a secure link so I can pay using my Credit Card, Debit Card or PayPal
 *
 * Four different things sit behind those four lines, so this file is mostly
 * about making them look like one list:
 *
 *   1 is a real card gateway. WooPayments, chosen in DR-56.
 *   2 is Invoice Gateway, already here, already gated to approved accounts by
 *     inc/pay-on-account.php.
 *   3 and 4 are ours, in inc/class-dorotape-gateway-phone.php and
 *     inc/class-dorotape-gateway-paylink.php.
 *
 * A fifth, Pay on collection, is not on the old checkout. It stays, because the
 * collection journey in inc/collection.php is built around it, and WooCommerce
 * already restricts it to orders being collected, so nobody having goods
 * delivered ever sees it.
 *
 * ── Why the block checkout needs more than a gateway class ──
 *
 * The Cart and Checkout pages are WooCommerce Blocks, and a block checkout does
 * not render a gateway just because WooCommerce knows about it. Each method has
 * to be registered a second time, as a payment method *type*, and hand over a
 * script that calls wc.wcBlocksRegistry.registerPaymentMethod() in the browser.
 * A gateway with no type registration is simply absent from the checkout, with
 * no warning anywhere, which is the failure this file exists to avoid.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/** The two gateways this theme owns. */
const DOROTAPE_THEME_GATEWAYS = array( 'dorotape_phone', 'dorotape_paylink' );

/** WooPayments' card gateway id. */
const DOROTAPE_CARD_GATEWAY_ID = 'woocommerce_payments';

/**
 * The order the methods are listed in, top to bottom.
 *
 * The old site's order, and it is not arbitrary: paying now is first because it
 * is what most people do and what gets us paid soonest, and the two that settle
 * later follow it. Pay on collection is last because it is not on the old
 * checkout at all and only ever appears to someone collecting. Anything not
 * named here keeps its own order after these.
 */
const DOROTAPE_GATEWAY_ORDER = array(
	DOROTAPE_CARD_GATEWAY_ID,
	'igfw_invoice_gateway',
	'dorotape_phone',
	'dorotape_paylink',
	'cod',
);

// ─── The gateways ─────────────────────────────────────────────────────────────

/**
 * Register the theme's two gateways with WooCommerce.
 *
 * @param array $gateways
 * @return array
 */
add_filter( 'woocommerce_payment_gateways', function ( $gateways ): array {
	require_once get_template_directory() . '/inc/class-dorotape-gateway-phone.php';
	require_once get_template_directory() . '/inc/class-dorotape-gateway-paylink.php';

	$gateways   = (array) $gateways;
	$gateways[] = 'Dorotape_Gateway_Phone';
	$gateways[] = 'Dorotape_Gateway_Paylink';

	return $gateways;
} );

/**
 * Put the list in the old site's order.
 *
 * Not through a filter on woocommerce_available_payment_gateways, which was the
 * first thing tried here and does not work: Blocks\Payments\Api builds its
 * client side `paymentMethodSortOrder` from payment_gateways(), never from the
 * available list, so that filter can decide which methods appear and not the
 * order they appear in.
 *
 * Not by writing woocommerce_gateway_order either, which was the second thing
 * tried and also does not work. WooPayments filters that option on the way out
 * of the database, at priorities 2 and 3, to keep its fifteen sub-methods
 * together behind the card gateway. The value we store is correct and the value
 * everything reads is not, which is a confusing thing to debug.
 *
 * So: the same filter, after them. The sub-methods keep their grouping, our
 * five take the front, and because payment_gateways() is what both checkouts
 * and the admin sort by, one filter governs all three.
 *
 * The cost is that dragging the rows in WooCommerce > Settings > Payments no
 * longer sticks. That is a deliberate trade: this order is the client's spec,
 * carried over from a checkout they have used for years, and it should travel
 * with a deploy rather than live in a database someone has to remember to
 * copy. `dorotape_gateway_order` is there to change it from a child theme or
 * to switch this off by returning the list untouched.
 *
 * @param mixed $order Stored order, gateway id => position.
 * @return array<string, int>
 */
function dorotape_order_gateways( $order ): array {
	$order = (array) $order;

	// Whatever came in, in its own order, so anything we do not name keeps the
	// position it had relative to its neighbours.
	asort( $order );

	/**
	 * Filter the ids that are pinned to the top of the payment method list.
	 *
	 * @param string[] $ids
	 */
	$named = (array) apply_filters( 'dorotape_gateway_order', DOROTAPE_GATEWAY_ORDER );

	$final    = array();
	$position = 0;

	foreach ( $named as $id ) {
		$final[ $id ] = $position++;
	}

	foreach ( array_keys( $order ) as $id ) {
		if ( ! isset( $final[ $id ] ) ) {
			$final[ $id ] = $position++;
		}
	}

	return $final;
}

// Priority 20, which only has to be above the 2 and 3 WooPayments registers at.
add_filter( 'option_woocommerce_gateway_order', 'dorotape_order_gateways', 20 );
add_filter( 'default_option_woocommerce_gateway_order', 'dorotape_order_gateways', 20 );

// ─── The block checkout ───────────────────────────────────────────────────────

/**
 * Register the theme's gateways as block payment method types.
 *
 * Without this the two methods exist, validate and can take an order, and are
 * invisible on the checkout.
 */
add_action( 'woocommerce_blocks_payment_method_type_registration', function ( $registry ): void {
	if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once get_template_directory() . '/inc/class-dorotape-blocks-payment-method.php';

	foreach ( DOROTAPE_THEME_GATEWAYS as $id ) {
		$registry->register( new Dorotape_Blocks_Payment_Method( $id ) );
	}
} );

/**
 * The script that registers both methods in the browser.
 *
 * Registered rather than enqueued: WooCommerce Blocks asks each payment method
 * type for its handles and enqueues them itself, on the pages that need them.
 * Enqueueing it here as well would load it on every page of the site to do
 * nothing.
 *
 * Deliberately not part of assets/js/main.js. That bundle is webpack output
 * loaded everywhere; this is eight lines of registry calls that only make sense
 * once wc-blocks-registry is on the page, and bundling it would mean either
 * shipping the registry's dependencies to every page or loading the checkout's
 * payment methods too late to be registered.
 *
 * @return string The script handle.
 */
function dorotape_register_payment_blocks_script(): string {
	$handle = 'dorotape-checkout-payment-methods';

	if ( wp_script_is( $handle, 'registered' ) ) {
		return $handle;
	}

	$relative = '/assets/js/blocks/checkout-payment-methods.js';

	wp_register_script(
		$handle,
		get_template_directory_uri() . $relative,
		array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
		dorotape_asset_version( $relative ),
		true
	);

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( $handle, 'dorotape' );
	}

	return $handle;
}

// ─── Wording for the gateways we did not write ────────────────────────────────

/**
 * The lines the old checkout used, for the methods that come from plugins.
 *
 * Our own two carry their wording in their class defaults, so they need nothing
 * here. These three are someone else's gateways whose titles live in the
 * options table, which a deploy does not move.
 *
 * @return array<string, array<string, string>>
 */
function dorotape_payment_method_copy(): array {
	return array(
		DOROTAPE_CARD_GATEWAY_ID => array(
			'title'       => __( "I'm happy to pay now using my Credit Card, Debit Card or PayPal", 'dorotape' ),
			'description' => __( 'Pay securely by card or PayPal. Your card details are handled by our payment provider and never touch this website.', 'dorotape' ),
		),
		'igfw_invoice_gateway'   => array(
			'title'       => __( 'Add this order to my account', 'dorotape' ),
			'description' => __( 'Pay against your account. We will invoice you and payment is due 30 days end of month, unless otherwise agreed in writing.', 'dorotape' ),
		),
		'cod'                    => array(
			'title'       => __( 'Pay on collection', 'dorotape' ),
			'description' => __( 'Pay when you collect your order from us.', 'dorotape' ),
		),
	);
}

/**
 * Write that wording in once, and never again.
 *
 * "Once" is the whole point. These are editable settings, so an editor changing
 * a line in WooCommerce must not find it reverted on the next admin page load.
 * The flag records that the theme has had its say; after that the database is
 * the authority. Dropping the option is how you ask for the defaults back.
 *
 * On admin_init rather than init, like the postage shipping class in
 * inc/shipping.php, so it costs a front end request nothing.
 */
function dorotape_seed_payment_method_copy(): void {
	if ( get_option( 'dorotape_payment_copy_seeded' ) ) {
		return;
	}

	foreach ( dorotape_payment_method_copy() as $id => $copy ) {
		$key      = 'woocommerce_' . $id . '_settings';
		$settings = get_option( $key );

		if ( ! is_array( $settings ) ) {
			continue;
		}

		update_option( $key, array_merge( $settings, $copy ) );
	}

	update_option( 'dorotape_payment_copy_seeded', 1, true );
}
add_action( 'admin_init', 'dorotape_seed_payment_method_copy' );

/**
 * Name the collection point on the shipping options list.
 *
 * "Local pickup" is WooCommerce's phrase for the mechanism. The old site told
 * people where they would be collecting from and that it costs nothing, which
 * is the only thing anyone needs to decide by. Sentence case rather than the
 * old site's capitals, like every other rate in inc/shipping.php.
 *
 * The title is per zone instance, so this finds the instance wherever it has
 * been put rather than hard coding an id that differs between environments.
 * Once, for the same reason as the wording above.
 */
function dorotape_seed_collection_title(): void {
	if ( get_option( 'dorotape_pickup_title_seeded' ) || ! class_exists( 'WC_Shipping_Zones' ) ) {
		return;
	}

	$zones   = WC_Shipping_Zones::get_zones();
	$zones[] = array( 'zone_id' => 0 );

	foreach ( $zones as $zone_data ) {
		$zone = WC_Shipping_Zones::get_zone( (int) $zone_data['zone_id'] );

		if ( ! $zone ) {
			continue;
		}

		foreach ( $zone->get_shipping_methods() as $method ) {
			if ( DOROTAPE_COLLECTION_METHOD !== $method->id ) {
				continue;
			}

			update_option(
				'woocommerce_' . $method->id . '_' . $method->instance_id . '_settings',
				array_merge(
					(array) get_option( 'woocommerce_' . $method->id . '_' . $method->instance_id . '_settings', array() ),
					array( 'title' => __( 'Collect from Doro Tape, LE16 9NP', 'dorotape' ) )
				)
			);
		}
	}

	update_option( 'dorotape_pickup_title_seeded', 1, true );
}
add_action( 'admin_init', 'dorotape_seed_collection_title' );
