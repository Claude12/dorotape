<?php
/**
 * Address book: choosing one at checkout
 *
 * Checkout on this site is the WooCommerce block, so a saved-address picker is
 * a registered additional checkout field rather than anything hooked onto the
 * classic form. See the README's "Cart and checkout" section.
 *
 * How it fits together:
 *
 *  1. A select field is registered per address type, but only for a logged-in
 *     customer who actually has saved addresses of that type. Nobody else sees
 *     an empty dropdown.
 *  2. The customer picks one. The value stored is the address book id.
 *  3. woocommerce_store_api_checkout_update_order_from_request fires after the
 *     Store API has populated the order from the request and after additional
 *     fields have been persisted, so it is the first point where the choice and
 *     the order both exist. The saved address is copied onto the order there.
 *  4. assets/js/blocks/checkout-address-book.js writes the picked address into
 *     the checkout's cart store as it is chosen, so the address card changes
 *     in front of the customer and delivery is priced for where the goods are
 *     actually going. Step 3 stays as the last word on the server, for a
 *     browser where the script did not run.
 *
 * @package dorotape
 */

/** Field ids, by address book type. */
function dorotape_address_field_id( string $type ): string {
	return 'dorotape/' . ( 'billing' === $type ? 'invoice' : 'delivery' ) . '-address';
}

/** The value meaning "use the address typed into the form". */
define( 'DOROTAPE_ADDRESS_FORM_VALUE', 'form' );

// ─── Registration ─────────────────────────────────────────────────────────────

add_action( 'woocommerce_init', function (): void {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) || ! is_user_logged_in() ) {
		return;
	}

	foreach ( array( 'shipping', 'billing' ) as $type ) {
		$saved = dorotape_get_address_book_by_type( $type );

		if ( ! $saved ) {
			continue;
		}

		$options = array(
			array(
				'value' => DOROTAPE_ADDRESS_FORM_VALUE,
				'label' => __( 'Use the address entered above', 'dorotape' ),
			),
		);

		foreach ( $saved as $address_id => $address ) {
			$options[] = array(
				'value' => $address_id,
				// The whole address, so two sites with similar names can be told
				// apart before one is picked.
				'label' => $address['label'] . ': ' . dorotape_notice_to_plain( dorotape_format_address( $address ) ),
			);
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => dorotape_address_field_id( $type ),
				'label'    => 'billing' === $type
					? __( 'Use a saved invoice address', 'dorotape' )
					: __( 'Use a saved delivery address', 'dorotape' ),
				'location' => 'order',
				'type'     => 'select',
				'required' => false,
				'placeholder' => __( 'Choose a saved address', 'dorotape' ),
				// The address itself is already on the order, emails and
				// thank you page under its own heading. Shown again here it
				// read as a second, oddly labelled copy of the same thing.
				'show_in_order_confirmation' => false,
				'options'  => $options,
				'validate_callback' => function ( $value ) use ( $type ) {
					if ( '' === $value || DOROTAPE_ADDRESS_FORM_VALUE === $value ) {
						return;
					}
					if ( ! dorotape_get_address( (string) $value ) ) {
						return new WP_Error(
							'dorotape_unknown_address',
							__( 'That saved address is no longer available. Please choose another.', 'dorotape' )
						);
					}
					$address = dorotape_get_address( (string) $value );
					if ( ( $address['type'] ?? '' ) !== $type ) {
						return new WP_Error(
							'dorotape_wrong_address_type',
							__( 'That address cannot be used for this purpose.', 'dorotape' )
						);
					}
				},
			)
		);
	}
} );

// ─── Filling the form as the choice is made ──────────────────────────────────

/**
 * Load the script that fills the form, with the customer's own book.
 *
 * Only on the checkout and only for someone with something saved, which is the
 * same condition the picker is registered under.
 */
add_action( 'wp_enqueue_scripts', function (): void {
	if ( ! is_user_logged_in() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
		return;
	}

	$book = dorotape_get_address_book();

	if ( ! $book ) {
		return;
	}

	$relative = '/assets/js/blocks/checkout-address-book.js';

	wp_enqueue_script(
		'dorotape-checkout-address-book',
		get_template_directory_uri() . $relative,
		array( 'wp-data' ),
		dorotape_asset_version( $relative ),
		true
	);

	wp_localize_script(
		'dorotape-checkout-address-book',
		'dorotapeAddressBook',
		array(
			'book'      => $book,
			'formValue' => DOROTAPE_ADDRESS_FORM_VALUE,
			'fields'    => array(
				'shipping' => dorotape_address_field_id( 'shipping' ),
				'billing'  => dorotape_address_field_id( 'billing' ),
			),
		)
	);
} );

// ─── Applying the choice ──────────────────────────────────────────────────────

/**
 * Copy any chosen saved address onto the order.
 *
 * Runs after the Store API has set the order's addresses from the request and
 * persisted the additional fields, so reading the choice back off the order is
 * safe and the overwrite is the last word.
 *
 * @param WC_Order $order
 */
add_action( 'woocommerce_store_api_checkout_update_order_from_request', function ( $order ): void {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$customer_id = $order->get_customer_id();

	if ( ! $customer_id ) {
		return;
	}

	foreach ( array( 'shipping', 'billing' ) as $type ) {
		$choice = $order->get_meta( '_wc_other/' . dorotape_address_field_id( $type ), true );

		if ( ! $choice || DOROTAPE_ADDRESS_FORM_VALUE === $choice ) {
			continue;
		}

		// Re-read against the owning customer rather than the current user, so a
		// value cannot be pointed at somebody else's address book.
		$address = dorotape_get_address( (string) $choice, $customer_id );

		if ( ! $address || ( $address['type'] ?? '' ) !== $type ) {
			continue;
		}

		dorotape_apply_address_to_order( $order, $address, $type );
	}
}, 10, 1 );

/**
 * Write one address book entry onto an order.
 *
 * The book stores WooCommerce's own field names precisely so this is a copy
 * rather than a mapping. Which fields exist is WooCommerce's decision, not
 * ours, so anything without a matching setter on the order is skipped.
 *
 * @param WC_Order $order
 * @param array    $address
 * @param string   $type
 */
function dorotape_apply_address_to_order( WC_Order $order, array $address, string $type ): void {
	foreach ( array_keys( dorotape_address_book_fields( $type ) ) as $key ) {
		$setter = "set_{$type}_{$key}";

		if ( method_exists( $order, $setter ) ) {
			$order->$setter( $address[ $key ] ?? '' );
		}
	}

	$order->add_order_note(
		sprintf(
			/* translators: 1: address type, 2: saved address label. */
			esc_html__( 'The %1$s address was taken from the customer\'s saved address "%2$s".', 'dorotape' ),
			'billing' === $type ? esc_html__( 'invoice', 'dorotape' ) : esc_html__( 'delivery', 'dorotape' ),
			esc_html( $address['label'] )
		)
	);
}
