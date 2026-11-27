<?php
/**
 * Address book
 *
 * WooCommerce gives a customer exactly one billing address and one shipping
 * address. Trade customers order to several sites and invoice to a head office,
 * so they need a set of saved addresses and a way to pick one.
 *
 * Built here rather than with an address book plugin because the Sage address
 * mapping in Stage 3 (DR-18) has to read whatever this stores. A plugin's
 * internal structure is not a contract, and discovering it changed during a Sage
 * sync is not a good day.
 *
 * STORAGE. One user meta key, DOROTAPE_ADDRESS_BOOK_META, holding an array
 * keyed by a generated address id:
 *
 *   [ 'addr_65b2f1c8a9' => [
 *         'label'      => 'Head office',
 *         'first_name' => ..., 'last_name' => ...,
 *         'address_1'  => ..., 'address_2' => ..., 'city'      => ...,
 *         'state'      => ..., 'postcode'  => ..., 'country'   => ...,
 *         'phone'      => ...,
 *     ], ... ]
 *
 * Only `label` is guaranteed. Everything else comes from WooCommerce's own
 * address fields for the country, so the set moves with the store's settings:
 * company is switched off here, and phone is on. DR-18 should read the keys it
 * finds rather than assume this list.
 *
 * Two deliberate choices in that shape, both for DR-18's benefit:
 *
 *  - The field names are WooCommerce's own, unprefixed. An entry can be handed
 *    to WC_Order::set_address() as-is, and Sage's invoice and delivery address
 *    blocks map field for field with no translation layer to keep in step.
 *  - An address has no type. Any saved address can be used as the invoice or
 *    the delivery address, at checkout or as a default (Site Edits (1),
 *    General row 19, 27 November 2026). Entries saved before then still carry
 *    a `type` key, which nothing reads. Sage keeps invoice and delivery
 *    addresses apart, so DR-18 should take the role from where an address is
 *    used (the order's billing or shipping side), not from the book.
 *
 * The customer's WooCommerce billing and shipping addresses are untouched and
 * remain the defaults. The address book sits alongside them rather than
 * replacing them, so nothing breaks for a customer who never opens it.
 *
 * @package dorotape
 */

/** Single user meta key holding the whole book. See the file header. */
define( 'DOROTAPE_ADDRESS_BOOK_META', '_dt_address_book' );

/** My Account endpoint slug. */
define( 'DOROTAPE_ADDRESS_BOOK_ENDPOINT', 'address-book' );

/** Sanity cap. Nobody legitimately needs more, and it bounds the meta row. */
define( 'DOROTAPE_ADDRESS_BOOK_MAX', 50 );

// ─── Storage ──────────────────────────────────────────────────────────────────

/**
 * Every saved address for a user.
 *
 * @param int|null $user_id Defaults to the current user.
 * @return array Address id => address data.
 */
function dorotape_get_address_book( ?int $user_id = null ): array {
	$user_id = $user_id ?? get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}

	$book = get_user_meta( $user_id, DOROTAPE_ADDRESS_BOOK_META, true );

	return is_array( $book ) ? $book : array();
}

/**
 * The fields that make two addresses the same place for the same person.
 *
 * Label, phone and county are left out: a different phone number on the same
 * site is not a second address worth keeping.
 */
function dorotape_address_identity_keys(): array {
	return array( 'first_name', 'last_name', 'address_1', 'address_2', 'city', 'postcode', 'country' );
}

/**
 * Whether two addresses are the same, ignoring case and spaces, so "le1 1aa"
 * and "LE1 1AA" do not count as two.
 *
 * @param array $a
 * @param array $b
 */
function dorotape_addresses_match( array $a, array $b ): bool {
	$norm = static fn( $v ): string => strtolower( preg_replace( '/\s+/', '', (string) $v ) );

	foreach ( dorotape_address_identity_keys() as $key ) {
		if ( $norm( $a[ $key ] ?? '' ) !== $norm( $b[ $key ] ?? '' ) ) {
			return false;
		}
	}

	return true;
}

/**
 * The id of the saved address matching this one, or null.
 *
 * @param array    $address Bare WooCommerce keys.
 * @param int|null $user_id
 */
function dorotape_find_saved_address( array $address, ?int $user_id = null ): ?string {
	foreach ( dorotape_get_address_book( $user_id ) as $address_id => $saved ) {
		if ( dorotape_addresses_match( $saved, $address ) ) {
			return (string) $address_id;
		}
	}

	return null;
}

/**
 * One saved address, or null.
 *
 * @param string   $address_id
 * @param int|null $user_id
 */
function dorotape_get_address( string $address_id, ?int $user_id = null ): ?array {
	return dorotape_get_address_book( $user_id )[ $address_id ] ?? null;
}

/**
 * The fields an address of this type has, as WooCommerce defines them.
 *
 * Taken from WooCommerce rather than hardcoded so a locale that needs a
 * different set, or any field added by another ticket, is picked up here too.
 *
 * @param string $type
 * @return array Field key => field definition.
 */
function dorotape_address_book_fields( string $type ): array {
	$fields = WC()->countries->get_address_fields( '', $type . '_' );
	$out    = array();

	foreach ( $fields as $key => $field ) {
		// Strip WooCommerce's billing_/shipping_ prefix: the book stores bare
		// keys so an entry can be reused for either purpose without rewriting.
		$out[ str_replace( $type . '_', '', $key ) ] = $field;
	}

	// Email belongs to the account, not to a place goods are sent.
	unset( $out['email'] );

	return $out;
}

/**
 * Create or update an address.
 *
 * @param array       $data       Raw address data, unsanitised.
 * @param string|null $address_id Existing id to update, or null to create.
 * @param int|null    $user_id
 * @return string|WP_Error The address id.
 */
function dorotape_save_address( array $data, ?string $address_id = null, ?int $user_id = null ) {
	$user_id = $user_id ?? get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'dorotape_no_user', __( 'You must be logged in to save an address.', 'dorotape' ) );
	}

	$book = dorotape_get_address_book( $user_id );

	if ( null !== $address_id && ! isset( $book[ $address_id ] ) ) {
		return new WP_Error( 'dorotape_no_address', __( 'That address no longer exists.', 'dorotape' ) );
	}

	if ( null === $address_id && count( $book ) >= DOROTAPE_ADDRESS_BOOK_MAX ) {
		return new WP_Error(
			'dorotape_address_book_full',
			sprintf(
				/* translators: %d: maximum number of saved addresses. */
				__( 'You can save up to %d addresses. Delete one before adding another.', 'dorotape' ),
				DOROTAPE_ADDRESS_BOOK_MAX
			)
		);
	}

	// No type: any saved address serves as either (see the file header).
	// Delivery and invoice have the same fields here once email is set
	// aside, so the delivery set is the one used to read and check it.
	$address = array(
		'label' => sanitize_text_field( (string) ( $data['label'] ?? '' ) ),
	);

	foreach ( array_keys( dorotape_address_book_fields( 'shipping' ) ) as $key ) {
		$address[ $key ] = sanitize_text_field( (string) ( $data[ $key ] ?? '' ) );
	}

	$errors = dorotape_validate_address( $address, 'shipping' );
	if ( $errors->has_errors() ) {
		return $errors;
	}

	if ( '' !== $address['postcode'] ) {
		$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] ?? '' );
	}

	if ( '' === $address['label'] ) {
		$address['label'] = dorotape_address_fallback_label( $address );
	}

	$address_id          = $address_id ?? uniqid( 'addr_', false );
	$book[ $address_id ] = $address;

	update_user_meta( $user_id, DOROTAPE_ADDRESS_BOOK_META, $book );

	/**
	 * Fires after an address book entry is written.
	 *
	 * @param string $address_id
	 * @param array  $address
	 * @param int    $user_id
	 */
	do_action( 'dorotape_address_saved', $address_id, $address, $user_id );

	return $address_id;
}

/**
 * A label for an entry the customer did not name, so the checkout picker is
 * never a list of blanks.
 *
 * Every key is optional on purpose. Which fields exist comes from WooCommerce,
 * and WooCommerce lets a store switch some of them off: company is hidden on
 * this site, for instance. Assuming a key is present is how this function
 * started life throwing a notice.
 *
 * @param array $address
 */
function dorotape_address_fallback_label( array $address ): string {
	foreach ( array( 'company', 'address_1', 'city', 'postcode' ) as $key ) {
		$candidate = trim( (string) ( $address[ $key ] ?? '' ) );

		if ( '' !== $candidate ) {
			return $candidate;
		}
	}

	return __( 'Saved address', 'dorotape' );
}

/**
 * Check the required fields for the country actually chosen.
 *
 * @param array  $address
 * @param string $type
 */
function dorotape_validate_address( array $address, string $type ): WP_Error {
	$errors = new WP_Error();
	$fields = WC()->countries->get_address_fields( $address['country'] ?? '', $type . '_' );

	foreach ( $fields as $key => $field ) {
		$bare = str_replace( $type . '_', '', $key );

		if ( 'email' === $bare || empty( $field['required'] ) ) {
			continue;
		}

		if ( '' === trim( (string) ( $address[ $bare ] ?? '' ) ) ) {
			$errors->add(
				'dorotape_address_required',
				sprintf(
					/* translators: %s: field label. */
					__( '%s is a required field.', 'dorotape' ),
					$field['label']
				)
			);
		}
	}

	// The same postcode test the checkout applies. Without it an address the
	// checkout would refuse could be saved here and then picked at checkout.
	$postcode = trim( (string) ( $address['postcode'] ?? '' ) );
	$country  = (string) ( $address['country'] ?? '' );

	if ( '' !== $postcode && '' !== $country && ! WC_Validation::is_postcode( $postcode, $country ) ) {
		$errors->add(
			'dorotape_address_postcode',
			'GB' === $country
				? __( 'Please enter a valid UK postcode.', 'dorotape' )
				: __( 'Please enter a valid postcode.', 'dorotape' )
		);
	}

	return $errors;
}

/**
 * Delete an address.
 *
 * @param string   $address_id
 * @param int|null $user_id
 */
function dorotape_delete_address( string $address_id, ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}

	$book = dorotape_get_address_book( $user_id );

	if ( ! isset( $book[ $address_id ] ) ) {
		return false;
	}

	unset( $book[ $address_id ] );
	update_user_meta( $user_id, DOROTAPE_ADDRESS_BOOK_META, $book );

	do_action( 'dorotape_address_deleted', $address_id, $user_id );

	return true;
}

/**
 * Copy a saved address onto the customer's WooCommerce default.
 *
 * This is what makes a saved address the one checkout starts with, and it is
 * why the book stores WooCommerce's own field names.
 *
 * Any saved address can be made either default, whatever type it was saved
 * as: Scott asked to pick the default delivery and invoice addresses
 * separately from the one list (19 November 2026).
 *
 * @param string      $address_id
 * @param int|null    $user_id
 * @param string|null $as 'shipping' or 'billing'; delivery when omitted.
 */
function dorotape_set_default_address( string $address_id, ?int $user_id = null, ?string $as = null ): bool {
	$user_id = $user_id ?? get_current_user_id();
	$address = dorotape_get_address( $address_id, $user_id );

	if ( ! $address || ! $user_id ) {
		return false;
	}

	$customer = new WC_Customer( $user_id );
	$type     = 'billing' === $as ? 'billing' : 'shipping';

	foreach ( array_keys( dorotape_address_book_fields( $type ) ) as $key ) {
		$setter = "set_{$type}_{$key}";
		if ( method_exists( $customer, $setter ) ) {
			$customer->$setter( $address[ $key ] ?? '' );
		}
	}

	$customer->save();

	return true;
}

// ─── Keeping the defaults in the book ────────────────────────────────────────

/**
 * Add the customer's default delivery and invoice addresses to the book if
 * they are not in it already.
 *
 * A default changed to a new address, under Addresses or by typing one at
 * checkout, used to replace the old default and leave no copy anywhere, so
 * the customer had to type it again next time it was needed (Site Edits (1),
 * General row 18, 27 November 2026). The book now keeps every address that
 * has been a default. An amended default is a new entry, and the version
 * before it stays saved until the customer deletes it.
 *
 * Quietly does nothing when the book is full or the address is incomplete:
 * the change the customer actually asked for has already been made.
 *
 * @param int      $user_id
 * @param string[] $types Which defaults to look at.
 */
function dorotape_remember_default_addresses( int $user_id, array $types = array( 'shipping', 'billing' ) ): void {
	if ( ! $user_id ) {
		return;
	}

	$customer = new WC_Customer( $user_id );

	foreach ( $types as $type ) {
		$address = array();

		foreach ( array_keys( dorotape_address_book_fields( $type ) ) as $key ) {
			$getter = "get_{$type}_{$key}";
			if ( method_exists( $customer, $getter ) ) {
				$address[ $key ] = (string) $customer->$getter();
			}
		}

		if ( '' === trim( $address['address_1'] ?? '' ) || null !== dorotape_find_saved_address( $address, $user_id ) ) {
			continue;
		}

		dorotape_save_address( $address, null, $user_id );
	}
}

// Addresses > Edit, WooCommerce's own form for each default.
add_action( 'woocommerce_customer_save_address', function ( $user_id ): void {
	dorotape_remember_default_addresses( (int) $user_id );
}, 20 );

/**
 * After checkout, which writes what was typed there back to the customer's
 * defaults. On a collection order the delivery side is whatever was left in
 * the hidden form, or the collection point, not somewhere the customer sends
 * goods, so only the invoice address is looked at.
 *
 * @param WC_Order|false $order
 */
function dorotape_remember_checkout_addresses( $order ): void {
	if ( ! $order instanceof WC_Order || ! $order->get_customer_id() ) {
		return;
	}

	$types = array( 'billing' );

	$collecting = false;
	foreach ( $order->get_shipping_methods() as $method ) {
		if ( in_array( $method->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) ) {
			$collecting = true;
		}
	}

	if ( $order->has_shipping_address() && ! $collecting ) {
		$types[] = 'shipping';
	}

	dorotape_remember_default_addresses( (int) $order->get_customer_id(), $types );
}

// The block checkout fires the first, the classic one the second.
add_action( 'woocommerce_store_api_checkout_order_processed', 'dorotape_remember_checkout_addresses' );
add_action( 'woocommerce_checkout_order_processed', static function ( $order_id ): void {
	dorotape_remember_checkout_addresses( wc_get_order( $order_id ) );
} );

/**
 * Format an address for display, using WooCommerce's per-country rules.
 *
 * @param array $address
 */
function dorotape_format_address( array $address ): string {
	return WC()->countries->get_formatted_address(
		array(
			'first_name' => $address['first_name'] ?? '',
			'last_name'  => $address['last_name'] ?? '',
			'company'    => $address['company'] ?? '',
			'address_1'  => $address['address_1'] ?? '',
			'address_2'  => $address['address_2'] ?? '',
			'city'       => $address['city'] ?? '',
			'state'      => $address['state'] ?? '',
			'postcode'   => $address['postcode'] ?? '',
			'country'    => $address['country'] ?? '',
		)
	);
}
