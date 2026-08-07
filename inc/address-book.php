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
 *         'type'       => 'billing',      // or 'shipping'
 *         'first_name' => ..., 'last_name' => ...,
 *         'address_1'  => ..., 'address_2' => ..., 'city'      => ...,
 *         'state'      => ..., 'postcode'  => ..., 'country'   => ...,
 *         'phone'      => ...,
 *     ], ... ]
 *
 * Only `label` and `type` are guaranteed. Everything else comes from
 * WooCommerce's own address fields for the country and type, so the set moves
 * with the store's settings: company is switched off here, and phone is on for
 * both types. DR-18 should read the keys it finds rather than assume this list.
 *
 * Two deliberate choices in that shape, both for DR-18's benefit:
 *
 *  - The field names are WooCommerce's own, unprefixed. An entry can be handed
 *    to WC_Order::set_address() as-is, and Sage's invoice and delivery address
 *    blocks map field for field with no translation layer to keep in step.
 *  - `type` keeps invoice and delivery addresses separate, which is Sage's model
 *    too. An address is one or the other, never implicitly both.
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
 * Saved addresses of one type.
 *
 * @param string   $type    'billing' or 'shipping'.
 * @param int|null $user_id
 * @return array
 */
function dorotape_get_address_book_by_type( string $type, ?int $user_id = null ): array {
	return array_filter(
		dorotape_get_address_book( $user_id ),
		static fn( array $address ): bool => ( $address['type'] ?? '' ) === $type
	);
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

	$type = in_array( $data['type'] ?? '', array( 'billing', 'shipping' ), true ) ? $data['type'] : 'shipping';

	$address = array(
		'label' => sanitize_text_field( (string) ( $data['label'] ?? '' ) ),
		'type'  => $type,
	);

	foreach ( array_keys( dorotape_address_book_fields( $type ) ) as $key ) {
		$address[ $key ] = sanitize_text_field( (string) ( $data[ $key ] ?? '' ) );
	}

	$errors = dorotape_validate_address( $address, $type );
	if ( $errors->has_errors() ) {
		return $errors;
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
 * @param string   $address_id
 * @param int|null $user_id
 */
function dorotape_set_default_address( string $address_id, ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();
	$address = dorotape_get_address( $address_id, $user_id );

	if ( ! $address || ! $user_id ) {
		return false;
	}

	$customer = new WC_Customer( $user_id );
	$type     = $address['type'];

	foreach ( array_keys( dorotape_address_book_fields( $type ) ) as $key ) {
		$setter = "set_{$type}_{$key}";
		if ( method_exists( $customer, $setter ) ) {
			$customer->$setter( $address[ $key ] ?? '' );
		}
	}

	$customer->save();

	return true;
}

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
