<?php
declare( strict_types=1 );
/**
 * How much more to spend for free delivery.
 *
 * "Can we get a pop-up to say when you spend £X you'll get free delivery
 * (maybe difficult for different regions)" (Site Edits, October 2026, Checkout
 * A4). The regions are why the figure is not typed in anywhere: each zone has
 * its own threshold in the tariff (inc/shipping.php), and the small-items
 * postage tariff has another, so the figure is read from the same bands the
 * delivery rates are priced from. Change the tariff and this follows.
 *
 * It shows in three places:
 *
 *  - Under the basket confirmation toast, so it is said at the moment
 *    something is added. A product page's add reloads the page and reads it
 *    from dorotapeToastData; a card's add is over AJAX and reads it from the
 *    cart fragments.
 *  - Above the basket and the checkout, kept current as quantities, coupons
 *    and the delivery address change through the Store API's cart data
 *    (assets/js/lib/free-delivery.js).
 *
 * Nothing shows for a zone that is quoted rather than priced, which is Europe
 * and the rest of the world, or when a coupon already makes delivery free.
 *
 * Amounts are excluding VAT, like the tariff and like the prices on the site.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where a basket with no address yet is assumed to be going. Most orders are.
 */
const DOROTAPE_FREE_DELIVERY_DEFAULT_ZONE = 'mainland';

/**
 * The subtotal from which a zone delivers free, on one tariff.
 *
 * The first band, after a paid one, that offers a delivery at no cost. The
 * free sample band under 1.00 and the quote rates for Europe and the world
 * also cost nothing, so they are not counted as free delivery.
 *
 * @param string $tariff main | postage.
 * @param string $zone   Key from dorotape_shipping_zone_choices().
 * @return float|null Null when the zone never delivers free.
 */
function dorotape_free_delivery_threshold( string $tariff, string $zone ): ?float {
	$bands = dorotape_shipping_tariff()[ $tariff ][ $zone ] ?? array();
	$floor = null;

	foreach ( $bands as $band ) {
		$free = false;

		foreach ( $band['rates'] as $id => $rate ) {
			if ( ! in_array( $id, array( 'sample', 'quote' ), true ) && (float) $rate['cost'] <= 0.0 ) {
				$free = true;
				break;
			}
		}

		if ( $free && null !== $floor ) {
			return $floor;
		}

		$floor = null === $band['max'] ? null : (float) $band['max'];
	}

	return null;
}

/**
 * The tariff zone a package is delivered to.
 *
 * WooCommerce matches the address to a shipping zone, and that zone's tariff
 * method says which part of the tariff it charges.
 *
 * @param array $package WooCommerce shipping package.
 */
function dorotape_package_tariff_zone( array $package ): string {
	$zone = wc_get_shipping_zone( $package );

	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( DOROTAPE_TARIFF_METHOD === $method->id ) {
			return (string) $method->get_option( 'tariff_zone', DOROTAPE_FREE_DELIVERY_DEFAULT_ZONE );
		}
	}

	return DOROTAPE_FREE_DELIVERY_DEFAULT_ZONE;
}

/**
 * The zone as it reads in a sentence: "free delivery to {region}".
 *
 * @param string $zone Key from dorotape_shipping_zone_choices().
 */
function dorotape_free_delivery_region( string $zone ): string {
	$regions = array(
		'mainland'  => __( 'UK mainland', 'dorotape' ),
		'highlands' => __( 'the Highlands and Islands', 'dorotape' ),
		'ni'        => __( 'Northern Ireland', 'dorotape' ),
		'iom'       => __( 'the Isle of Man', 'dorotape' ),
		'channel'   => __( 'the Channel Islands', 'dorotape' ),
	);

	return $regions[ $zone ] ?? '';
}

/**
 * Where the current basket stands.
 *
 * @return array{message:string, free:bool, progress:int}|null Null when there
 *         is nothing to say.
 */
function dorotape_free_delivery_status(): ?array {
	$cart = function_exists( 'WC' ) ? WC()->cart : null;

	if ( ! $cart || $cart->is_empty() || ! $cart->needs_shipping() || dorotape_shipping_is_free_by_coupon() ) {
		return null;
	}

	$package = $cart->get_shipping_packages()[0] ?? null;

	if ( ! $package ) {
		return null;
	}

	$zone      = dorotape_package_tariff_zone( $package );
	$threshold = dorotape_free_delivery_threshold( dorotape_package_tariff( $package ), $zone );

	if ( null === $threshold ) {
		return null;
	}

	// The same figure the tariff method prices from: after discounts,
	// before VAT.
	$subtotal  = (float) ( $package['contents_cost'] ?? 0 );
	$remaining = $threshold - $subtotal;
	$free      = $remaining <= 0;

	$message = $free
		? dorotape_woo_pages_field( 'woo_free_delivery_reached', __( 'Your order qualifies for free delivery to {region}.', 'dorotape' ) )
		: dorotape_woo_pages_field( 'woo_free_delivery_remaining', __( 'Spend {amount} more (ex VAT) for free delivery to {region}.', 'dorotape' ) );

	$amount = html_entity_decode( wp_strip_all_tags( wc_price( max( 0, $remaining ) ) ), ENT_QUOTES, 'UTF-8' );

	return array(
		'message'  => strtr(
			$message,
			array(
				'{amount}' => $amount,
				'{region}' => dorotape_free_delivery_region( $zone ),
			)
		),
		'free'     => $free,
		'progress' => (int) min( 100, max( 0, round( $subtotal / $threshold * 100 ) ) ),
	);
}

/**
 * The sentence alone, for the toast.
 */
function dorotape_free_delivery_message(): string {
	$status = dorotape_free_delivery_status();

	return $status ? $status['message'] : '';
}

/**
 * The notice above the basket and the checkout.
 *
 * Rendered with the page so it is there before the blocks' script has run,
 * and rendered empty and hidden when there is nothing to say, so the script
 * has somewhere to write when the basket changes.
 */
function dorotape_free_delivery_notice(): string {
	$status = dorotape_free_delivery_status();

	ob_start();
	?>
	<div class="free-delivery<?php echo $status && $status['free'] ? ' is-free' : ''; ?>" data-free-delivery role="status" aria-live="polite"<?php echo $status ? '' : ' hidden'; ?>>
		<?php echo dorotape_icon( 'truck', 'free-delivery__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		<div class="free-delivery__body">
			<p class="free-delivery__text"><?php echo esc_html( $status['message'] ?? '' ); ?></p>
			<span class="free-delivery__bar" aria-hidden="true"><span class="free-delivery__fill" style="width: <?php echo (int) ( $status['progress'] ?? 0 ); ?>%"></span></span>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( ! in_array( $block['blockName'] ?? '', array( 'woocommerce/cart', 'woocommerce/checkout' ), true ) ) {
			return $content;
		}

		return dorotape_free_delivery_notice() . $content;
	},
	20,
	2
);

/**
 * The same answer on the Store API's cart, which is what the basket and the
 * checkout blocks re-read whenever anything in them changes.
 *
 * Registered on init rather than woocommerce_blocks_loaded, which has fired
 * before a theme loads. The Store API reads extensions per request, so this
 * is still in time.
 */
add_action(
	'init',
	function (): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => 'dorotape',
				'data_callback'   => static fn (): array => array( 'free_delivery' => dorotape_free_delivery_status() ),
				'schema_callback' => static fn (): array => array(
					'free_delivery' => array(
						'description' => __( 'How much more to spend for free delivery, or null.', 'dorotape' ),
						'type'        => array( 'object', 'null' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
				'schema_type'     => ARRAY_A,
			)
		);
	}
);

/**
 * Hand the sentence to a card's AJAX add, with the basket count.
 *
 * The key matches no element, so WooCommerce's fragment refresh replaces
 * nothing with it; assets/js/lib/toast.js reads it from the added_to_cart event.
 *
 * @param array<string, string> $fragments Fragments keyed by jQuery selector.
 * @return array<string, string>
 */
function dorotape_free_delivery_fragment( array $fragments ): array {
	$fragments['dorotape_free_delivery'] = dorotape_free_delivery_message();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'dorotape_free_delivery_fragment' );
