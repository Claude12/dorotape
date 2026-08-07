<?php
/**
 * Delivery tariff
 *
 * The tariff the live site charges, transcribed from the two Kryptronic custom
 * shipping scripts (`UK_EU_WORLDWIDE` and `UK_POSTAGE`) and cross-checked against
 * the published Delivery page, which agrees with them exactly.
 *
 * Why this is code rather than WooCommerce settings. Three parts of the tariff
 * cannot be expressed by a native shipping method:
 *
 *  1. Over the free-delivery threshold the *set of options changes*, not just a
 *     price. On UK mainland, economy delivery disappears above 150 and the
 *     before-noon upgrade drops from 19.25 to 7.50. Flat Rate has one cost per
 *     instance and no notion of a cart subtotal.
 *  2. There are two entirely separate tariffs, chosen per product. 1,196 items
 *     ship on the main tariff and 313 on a small-items postage tariff, and each
 *     has its own free threshold (150 against 12). Free Shipping allows one
 *     minimum per instance and cannot vary it by shipping class.
 *  3. Under 1.00 the main tariff ships free as a sample.
 *
 * Keeping the numbers here also means a deploy carries them. WooCommerce settings
 * live in the options table, which git does not move, and this is the delivery
 * pricing: it should not exist only in a database somebody has to remember to
 * copy. What stays in WooCommerce is the geography, because postcodes need
 * editing by people who do not deploy.
 *
 * Collection is deliberately NOT a rate here. It stays WooCommerce's own
 * Local Pickup method, because `inc/collection.php` and the whole DR-11
 * collection journey identify a collection order by the `local_pickup` method id.
 * A lookalike rate returned from this class would read as a delivery and the
 * Ready for Collection flow would silently stop firing.
 *
 * @package dorotape
 */

/** Shipping class for the 313 small items that ship on the postage tariff. */
define( 'DOROTAPE_POSTAGE_CLASS', 'postage' );

/** Shipping method id. Stored on every order, so changing it orphans history. */
define( 'DOROTAPE_TARIFF_METHOD', 'dorotape_tariff' );

// ─── The tariff ───────────────────────────────────────────────────────────────

/**
 * Every rate the site offers, by tariff, by zone, by subtotal band.
 *
 * Bands are read in order and the first whose `max` the subtotal falls under
 * wins, so they must stay ascending. `max` is exclusive and null means no upper
 * bound. Subtotals are always excluding VAT, matching the old site: its scripts
 * commented `Total excluding VAT` and read a subtotal that carried no tax.
 *
 * Labels are sentence case where Kryptronic shouted in capitals. The wording and
 * every figure is otherwise unchanged. Capitals are harder to read, are announced
 * letter by letter by some screen readers, and DR-46 would only raise it later.
 *
 * @return array<string, array<string, array<int, array{max: float|null, rates: array<string, array{label: string, cost: float}>}>>>
 */
function dorotape_shipping_tariff(): array {
	static $tariff = null;

	if ( null !== $tariff ) {
		return $tariff;
	}

	$quote_eu = array(
		'quote' => array(
			'label' => __( 'European delivery - we will contact you to arrange payment. You are responsible for any import duties, local taxes and customs fees at the destination.', 'dorotape' ),
			'cost'  => 0.0,
		),
	);

	$quote_world = array(
		'quote' => array(
			'label' => __( 'Worldwide delivery - we will contact you to arrange payment. You are responsible for any import duties, local taxes and customs fees at the destination.', 'dorotape' ),
			'cost'  => 0.0,
		),
	);

	$free_sample = array(
		'sample' => array(
			'label' => __( 'Free sample delivery', 'dorotape' ),
			'cost'  => 0.0,
		),
	);

	$tariff = array(

		/* ---------- main tariff: 1,196 items ---------- */
		'main' => array(

			'mainland' => array(
				array(
					'max'   => 1.0,
					'rates' => $free_sample,
				),
				array(
					'max'   => 150.0,
					'rates' => array(
						'nextday'  => array( 'label' => __( 'Next day delivery', 'dorotape' ), 'cost' => 11.75 ),
						'prenoon'  => array( 'label' => __( 'Next day delivery, before noon', 'dorotape' ), 'cost' => 19.25 ),
						'economy'  => array( 'label' => __( 'Economy delivery (up to 3 days)', 'dorotape' ), 'cost' => 8.99 ),
					),
				),
				array(
					'max'   => null,
					'rates' => array(
						'nextday' => array( 'label' => __( 'Free next day delivery', 'dorotape' ), 'cost' => 0.0 ),
						'prenoon' => array( 'label' => __( 'Upgrade to before noon next day delivery', 'dorotape' ), 'cost' => 7.50 ),
					),
				),
			),

			'highlands' => array(
				array( 'max' => 1.0, 'rates' => $free_sample ),
				array(
					'max'   => 200.0,
					'rates' => array(
						'std' => array( 'label' => __( '2-3 day delivery', 'dorotape' ), 'cost' => 15.50 ),
					),
				),
				array(
					'max'   => null,
					'rates' => array(
						'std' => array( 'label' => __( 'Free 2-3 day delivery', 'dorotape' ), 'cost' => 0.0 ),
					),
				),
			),

			'iom' => array(
				array(
					'max'   => 300.0,
					'rates' => array(
						'std' => array( 'label' => __( '2-3 day delivery', 'dorotape' ), 'cost' => 17.50 ),
					),
				),
				array(
					'max'   => null,
					'rates' => array(
						'std' => array( 'label' => __( 'Free 2-3 day delivery', 'dorotape' ), 'cost' => 0.0 ),
					),
				),
			),

			'ni' => array(
				array(
					'max'   => 300.0,
					'rates' => array(
						'std' => array( 'label' => __( '2-3 day delivery', 'dorotape' ), 'cost' => 19.75 ),
					),
				),
				array(
					'max'   => null,
					'rates' => array(
						'std' => array( 'label' => __( 'Free 2-3 day delivery', 'dorotape' ), 'cost' => 0.0 ),
					),
				),
			),

			'channel' => array(
				array(
					'max'   => 450.0,
					'rates' => array(
						'std' => array( 'label' => __( '3-4 day delivery', 'dorotape' ), 'cost' => 42.50 ),
					),
				),
				array(
					'max'   => null,
					'rates' => array(
						'std' => array( 'label' => __( 'Free 3-4 day delivery', 'dorotape' ), 'cost' => 0.0 ),
					),
				),
			),

			'eu'    => array( array( 'max' => null, 'rates' => $quote_eu ) ),
			'world' => array( array( 'max' => null, 'rates' => $quote_world ) ),
		),

		/* ---------- postage tariff: 313 small items ---------- */
		'postage' => array(

			'mainland' => array(
				array(
					'max'   => 12.0,
					'rates' => array(
						'post'    => array( 'label' => __( 'Postage (2nd class)', 'dorotape' ), 'cost' => 1.00 ),
						'courier' => array( 'label' => __( 'Upgrade to courier delivery (1-2 days, tracked, signature required)', 'dorotape' ), 'cost' => 8.00 ),
					),
				),
				array(
					'max'   => null,
					'rates' => array(
						'post'    => array( 'label' => __( 'Free 2nd class postage', 'dorotape' ), 'cost' => 0.0 ),
						'courier' => array( 'label' => __( 'Upgrade to courier delivery (1-2 days, tracked, signature required)', 'dorotape' ), 'cost' => 7.00 ),
					),
				),
			),

			/*
			 * The postage tariff groups the offshore UK zones with the EU rather
			 * than giving them a rate, which is why these four are quote-only
			 * here but priced on the main tariff above. That is the old site's
			 * grouping, not a transcription slip.
			 */
			'highlands' => array( array( 'max' => null, 'rates' => $quote_eu ) ),
			'iom'       => array( array( 'max' => null, 'rates' => $quote_eu ) ),
			'ni'        => array( array( 'max' => null, 'rates' => $quote_eu ) ),
			'channel'   => array( array( 'max' => null, 'rates' => $quote_eu ) ),
			'eu'        => array( array( 'max' => null, 'rates' => $quote_eu ) ),
			'world'     => array( array( 'max' => null, 'rates' => $quote_world ) ),
		),
	);

	return $tariff;
}

/**
 * The zones the tariff knows about, for the method's own settings dropdown.
 *
 * @return array<string, string>
 */
function dorotape_shipping_zone_choices(): array {
	return array(
		'mainland'  => __( 'UK mainland (England, Wales, Scotland mainland)', 'dorotape' ),
		'highlands' => __( 'Scottish Highlands and Islands', 'dorotape' ),
		'ni'        => __( 'Northern Ireland', 'dorotape' ),
		'iom'       => __( 'Isle of Man', 'dorotape' ),
		'channel'   => __( 'Channel Islands', 'dorotape' ),
		'eu'        => __( 'Europe', 'dorotape' ),
		'world'     => __( 'Rest of the world', 'dorotape' ),
	);
}

// ─── Which tariff a basket uses ────────────────────────────────────────────────

/**
 * Main tariff or postage tariff for this package?
 *
 * The old site chose per product, so a mixed basket has no answer in the data.
 * The rule here is that any main-tariff item puts the whole basket on the main
 * tariff, and the postage tariff applies only when everything in it is a small
 * item. That is the safe direction: the reverse would let one letter-sized
 * sticker carry a roll of tape to the Highlands for a pound.
 *
 * Stated as an assumption rather than a fact, and worth Michael confirming.
 *
 * @param array $package WooCommerce shipping package.
 */
function dorotape_package_tariff( array $package ): string {
	if ( empty( $package['contents'] ) ) {
		return 'main';
	}

	foreach ( $package['contents'] as $item ) {
		if ( empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
			continue;
		}

		if ( DOROTAPE_POSTAGE_CLASS !== $item['data']->get_shipping_class() ) {
			return 'main';
		}
	}

	return 'postage';
}

/**
 * The band whose rates apply at this subtotal.
 *
 * @param array $bands Ascending list of bands.
 * @return array<string, array{label: string, cost: float}>
 */
function dorotape_tariff_band( array $bands, float $subtotal ): array {
	foreach ( $bands as $band ) {
		if ( null === $band['max'] || $subtotal < $band['max'] ) {
			return $band['rates'];
		}
	}

	// Unreachable while the last band has a null max, which is asserted by the
	// tests. Returning empty rather than throwing keeps checkout usable if a
	// later edit drops it, and the missing rate is obvious on screen.
	return array();
}

// ─── The shipping method ──────────────────────────────────────────────────────

/**
 * Register the method so it can be added to a zone like any other.
 *
 * @param array $methods
 * @return array
 */
add_filter( 'woocommerce_shipping_methods', function ( array $methods ): array {
	require_once get_template_directory() . '/inc/class-dorotape-shipping-tariff.php';
	$methods[ DOROTAPE_TARIFF_METHOD ] = 'Dorotape_Shipping_Tariff';
	return $methods;
} );

/**
 * Create the postage shipping class if it does not exist.
 *
 * The class is a term, so unlike a WooCommerce setting it does travel usefully:
 * this runs once on activation and on a version bump rather than on every load.
 */
add_action( 'admin_init', function (): void {
	if ( term_exists( DOROTAPE_POSTAGE_CLASS, 'product_shipping_class' ) ) {
		return;
	}

	wp_insert_term(
		__( 'Postage (small items)', 'dorotape' ),
		'product_shipping_class',
		array(
			'slug'        => DOROTAPE_POSTAGE_CLASS,
			'description' => __( 'Small items that ship on the postage tariff rather than by courier.', 'dorotape' ),
		)
	);
} );

/**
 * Is a free delivery coupon in play for this basket? (DR-25)
 *
 * The tariff method calls this before pricing its rates. WooCommerce's own
 * answer to a free shipping coupon is to unlock WC_Shipping_Free_Shipping, a
 * separate method that has to be added to each zone and that would sit next to
 * the tariff offering a second, cheaper choice. Asking the question here instead
 * keeps one method per zone and means the free shipping coupons work on the
 * zones that already exist, with nothing to remember to add to a new one.
 *
 * @return bool True when at least one applied coupon grants free delivery.
 */
function dorotape_shipping_is_free_by_coupon(): bool {
	if ( ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_coupons() as $coupon ) {
		if ( $coupon->get_free_shipping() ) {
			return true;
		}
	}

	return false;
}
