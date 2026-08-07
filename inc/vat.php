<?php
/**
 * VAT numbers
 *
 * WHAT THIS FIELD DOES, AND WHAT IT DELIBERATELY DOES NOT DO
 *
 * The spec asks for "VAT exemption on a validated business VAT number". That
 * cannot happen on this site, and building it would produce a switch that can
 * never fire:
 *
 *  - DR-2 charges VAT to GB and IM only. A domestic UK business-to-business
 *    supply is never zero rated for holding a VAT number, so on the only two
 *    destinations that are taxed, a VAT number must not change the price.
 *  - Every other destination is already zero rated by the DR-2 rate table, so
 *    there is no VAT left for a number to remove.
 *
 * So the number never changes what anyone pays. What it does do is two real
 * jobs, and both are worth the field:
 *
 *  1. HMRC evidence. A zero rated export has to be evidenced, and the
 *     customer's VAT registration is part of that evidence pack.
 *  2. Sage coding. Woosage reads `vat_number` off the order
 *     (Classes/WC.php:236-249) and, when the number's first two characters
 *     differ from its local_country_code setting and the line carries no tax,
 *     posts the line to Sage as tax code T4 rather than the default
 *     (Classes/WC.php:993-999 and 1103-1109). T4 is the EC zero rated code. So
 *     this field decides how these sales land on the VAT return.
 *
 * Two consequences of that Woosage logic drive the design below:
 *
 *  - The country prefix is mandatory. Woosage takes the tax code from
 *    substr($vat, 0, 2). A number typed without its prefix gives Woosage two
 *    digits, which never equal GB, so every order would be coded T4 whether it
 *    deserved it or not. Rejecting a prefix-less number at checkout is what
 *    stops that.
 *  - The field is offered on EU destinations only. Woosage's rule is "not GB",
 *    not "in the EC", so a Norwegian or Swiss number would also produce T4 -
 *    wrong, because neither is in the EC. Limiting where the field appears is
 *    the cheapest way to keep T4 honest without patching the plugin.
 *
 * The meta key is Woosage's own `vat_number`, not a _dt_ one, for the same
 * reason inc/purchase-order.php writes Woosage's PO key: the value reaches Sage
 * with no mapping code and there is one source of truth. Woosage has no VAT
 * checkout field of its own (its Classes/WC.php carries a "TODO make compatible
 * with other VAT plugins"), so unlike the PO field there is no duplicate to
 * guard against.
 *
 * @package dorotape
 */

/**
 * Woosage's key, not ours. See the file header before changing it.
 */
define( 'DOROTAPE_VAT_META_KEY', 'vat_number' );

/** The registered field id. The saved meta key is Woosage's, not this. */
define( 'DOROTAPE_VAT_FIELD_ID', 'dorotape/vat-number' );

/** Our own record of what VIES said. Prefixed, because this one is ours. */
define( 'DOROTAPE_VAT_CHECK_META_KEY', '_dt_vat_check' );

/** The cron hook that performs the VIES lookup after the order is placed. */
define( 'DOROTAPE_VAT_CHECK_HOOK', 'dorotape_vat_check_order' );

// ─── What a VAT number looks like ─────────────────────────────────────────────

/**
 * Where the field is offered: the 27 EU member states, by ISO country code.
 *
 * Note Greece is GR as a country and EL as a VAT prefix. Both appear in this
 * file and they are not interchangeable.
 *
 * @return string[]
 */
function dorotape_vat_countries(): array {
	return array(
		'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
		'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT',
		'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
	);
}

/**
 * The published format for each VAT prefix, without the prefix itself.
 *
 * GB and XI are here even though the field is offered on EU destinations only,
 * because the number's country and the delivery country need not match: a UK or
 * Northern Irish business can perfectly well have goods delivered to Dublin.
 *
 * @return array<string, string>
 */
function dorotape_vat_patterns(): array {
	return array(
		'AT' => 'U\d{8}',
		'BE' => '[01]\d{9}',
		'BG' => '\d{9,10}',
		'CY' => '\d{8}[A-Z]',
		'CZ' => '\d{8,10}',
		'DE' => '\d{9}',
		'DK' => '\d{8}',
		'EE' => '\d{9}',
		'EL' => '\d{9}',
		'ES' => '[A-Z0-9]\d{7}[A-Z0-9]',
		'FI' => '\d{8}',
		'FR' => '[A-Z0-9]{2}\d{9}',
		'HR' => '\d{11}',
		'HU' => '\d{8}',
		'IE' => '(?:\d{7}[A-W]|[7-9][A-Z*+]\d{5}[A-W]|\d{7}[A-W][AH])',
		'IT' => '\d{11}',
		'LT' => '(?:\d{9}|\d{12})',
		'LU' => '\d{8}',
		'LV' => '\d{11}',
		'MT' => '\d{8}',
		'NL' => '\d{9}B\d{2}',
		'PL' => '\d{10}',
		'PT' => '\d{9}',
		'RO' => '\d{2,10}',
		'SE' => '\d{12}',
		'SI' => '\d{8}',
		'SK' => '\d{10}',
		// Not in VIES. GB left it after Brexit; XI is in VIES for goods, but is
		// checked through the GB register, so neither can be relied on here.
		'GB' => '(?:\d{9}|\d{12}|GD\d{3}|HA\d{3})',
		'XI' => '(?:\d{9}|\d{12}|GD\d{3}|HA\d{3})',
	);
}

/**
 * Strip the punctuation people type and upper-case the rest.
 *
 * "de 811 907 980" and "DE811907980" are the same number, and a customer who
 * copied theirs off a letterhead should not be told it is wrong.
 */
function dorotape_vat_normalise( string $value ): string {
	return strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $value ) );
}

/**
 * Split a normalised number into prefix and body, or return an empty array.
 *
 * @return array{0: string, 1: string}|array{}
 */
function dorotape_vat_split( string $normalised ): array {
	if ( strlen( $normalised ) < 3 ) {
		return array();
	}
	$prefix = substr( $normalised, 0, 2 );
	if ( ! isset( dorotape_vat_patterns()[ $prefix ] ) ) {
		return array();
	}
	return array( $prefix, substr( $normalised, 2 ) );
}

/**
 * Why a number is unusable, or an empty string if the format is fine.
 *
 * This is a format check, not a registration check. It runs at checkout because
 * it is instant and offline; whether the number is real is settled afterwards by
 * VIES, which must never hold a customer up.
 */
function dorotape_vat_format_error( string $value ): string {
	$normalised = dorotape_vat_normalise( $value );
	if ( '' === $normalised ) {
		return '';
	}

	$parts = dorotape_vat_split( $normalised );
	if ( ! $parts ) {
		return __( 'Please start your VAT number with its two-letter country code, for example DE811907980.', 'dorotape' );
	}

	list( $prefix, $body ) = $parts;

	if ( ! preg_match( '/^' . dorotape_vat_patterns()[ $prefix ] . '$/', $body ) ) {
		return sprintf(
			/* translators: %s: two-letter VAT country prefix, e.g. DE. */
			__( 'That does not look like a %s VAT number. Please check it and try again.', 'dorotape' ),
			$prefix
		);
	}

	return '';
}

// ─── Checkout ─────────────────────────────────────────────────────────────────

/**
 * Register the checkout field.
 *
 * `hidden` is a JSON Schema rule evaluated against WooCommerce's DocumentObject,
 * which is how a block checkout does conditional fields - the classic
 * show-and-hide hooks do not fire here at all. The rule reads "hidden when the
 * delivery country is not an EU member state", so the field appears and
 * disappears as the customer changes country, with no JavaScript of ours.
 *
 * The field is optional. A customer without a VAT number, or one who would
 * rather not give it, must still be able to buy.
 */
add_action( 'woocommerce_init', function (): void {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'       => DOROTAPE_VAT_FIELD_ID,
			'label'    => __( 'VAT number, including country code (optional)', 'dorotape' ),
			'location' => 'order',
			'type'     => 'text',
			'required' => false,
			'hidden'   => array(
				'customer' => array(
					'properties' => array(
						'shipping_address' => array(
							'properties' => array(
								'country' => array(
									'not' => array( 'enum' => dorotape_vat_countries() ),
								),
							),
						),
					),
				),
			),
			/*
			 * WooCommerce allows maxLength, readOnly, pattern, autocomplete,
			 * autocapitalize, title, and anything aria- or data- prefixed. It
			 * drops the rest with a _doing_it_wrong, so placeholder is not
			 * available and the example lives in the label instead.
			 *
			 * `title` and not `aria-label`: aria-label would replace the field's
			 * accessible name, so a screen reader would announce this whole
			 * sentence in place of "VAT number" and the user would never hear
			 * what the box is for. title adds to the name rather than replacing
			 * it.
			 *
			 * No `pattern`. The browser would test it against what was typed,
			 * spaces and all, and reject "de 811 907 980" before the sanitiser
			 * ever gets to normalise it. The format check below is the gate.
			 */
			'attributes' => array(
				'autocomplete'   => 'off',
				'autocapitalize' => 'characters',
				'title'          => __( 'Your EU VAT number, including its country code. We record it on your invoice as evidence for this zero rated export. It does not change the price.', 'dorotape' ),
			),
			'sanitize_callback' => function ( $value ) {
				return dorotape_vat_normalise( (string) $value );
			},
			// This replaces WooCommerce's default callback, which is the one that
			// enforces `required`. Safe only because this field is optional.
			'validate_callback' => function ( $value ) {
				$error = dorotape_vat_format_error( (string) $value );
				if ( '' !== $error ) {
					return new WP_Error( 'dorotape_vat_invalid', $error );
				}
			},
		)
	);
} );

/**
 * Copy the value onto Woosage's meta key.
 *
 * Same shape as the PO field: rides along in the save WooCommerce is already
 * doing, and the instanceof keeps it on the order rather than on the customer,
 * because the VAT number belongs to the transaction being evidenced.
 *
 * @param string  $key       Field id being saved.
 * @param mixed   $value     Field value.
 * @param string  $group     Field group (shipping|billing|other).
 * @param WC_Data $wc_object Object being saved.
 */
add_action( 'woocommerce_set_additional_field_value', function ( $key, $value, $group, $wc_object ): void {
	if ( DOROTAPE_VAT_FIELD_ID !== $key || ! $wc_object instanceof WC_Order ) {
		return;
	}
	$wc_object->update_meta_data( DOROTAPE_VAT_META_KEY, dorotape_vat_normalise( (string) $value ) );
}, 10, 4 );

// ─── Reading ──────────────────────────────────────────────────────────────────

/**
 * The VAT number on an order, or an empty string.
 *
 * @param WC_Order|int|false $order Order, id, or anything falsy.
 */
function dorotape_get_vat_number( $order ): string {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return '';
	}
	return dorotape_vat_normalise( (string) $order->get_meta( DOROTAPE_VAT_META_KEY, true ) );
}

/**
 * What VIES said about it, as array{status, name, checked}.
 *
 * status is one of: valid, invalid, unavailable, unsupported, pending.
 *
 * @param WC_Order|int|false $order
 * @return array{status: string, name: string, checked: string}
 */
function dorotape_get_vat_check( $order ): array {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	$empty = array(
		'status'  => 'pending',
		'name'    => '',
		'checked' => '',
	);
	if ( ! $order ) {
		return $empty;
	}
	$stored = $order->get_meta( DOROTAPE_VAT_CHECK_META_KEY, true );
	return is_array( $stored ) ? wp_parse_args( $stored, $empty ) : $empty;
}

/**
 * A sentence a human can read, for the admin screen.
 */
function dorotape_vat_check_label( array $check ): string {
	switch ( $check['status'] ) {
		case 'valid':
			return '' === $check['name']
				? __( 'Confirmed registered by VIES', 'dorotape' )
				: sprintf(
					/* translators: %s: registered business name returned by VIES. */
					__( 'Confirmed registered by VIES, as %s', 'dorotape' ),
					$check['name']
				);
		case 'invalid':
			return __( 'VIES does not recognise this number. Check it before treating the sale as zero rated.', 'dorotape' );
		case 'unsupported':
			return __( 'Cannot be checked automatically. GB numbers left VIES after Brexit; verify on the HMRC service if you need to.', 'dorotape' );
		case 'unavailable':
			return __( 'VIES could not be reached. The number is stored and unverified.', 'dorotape' );
		default:
			return __( 'Not checked yet.', 'dorotape' );
	}
}

// ─── VIES ─────────────────────────────────────────────────────────────────────

/**
 * Ask VIES about one number.
 *
 * Every failure - a timeout, a 500, an unparseable body, a member state whose
 * own system is down - comes back as "unavailable" rather than "invalid",
 * because "we could not check" and "this is fake" are very different things to
 * tell the accounts team.
 *
 * @return array{status: string, name: string}
 */
function dorotape_vies_lookup( string $prefix, string $body ): array {
	$response = wp_remote_get(
		sprintf(
			'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/%s/vat/%s',
			rawurlencode( $prefix ),
			rawurlencode( $body )
		),
		array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array(
			'status' => 'unavailable',
			'name'   => '',
		);
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || ! array_key_exists( 'isValid', $data ) ) {
		return array(
			'status' => 'unavailable',
			'name'   => '',
		);
	}

	/*
	 * VIES answers 200 with a userError even when it has not actually checked
	 * anything - MS_UNAVAILABLE, TIMEOUT, SERVICE_UNAVAILABLE and friends all
	 * arrive this way, with isValid false. Reading that as "invalid" would
	 * accuse a real business of giving a fake number because Malta's server was
	 * having a bad afternoon.
	 */
	$user_error = isset( $data['userError'] ) ? strtoupper( (string) $data['userError'] ) : 'VALID';
	if ( ! in_array( $user_error, array( 'VALID', 'INVALID', '' ), true ) ) {
		return array(
			'status' => 'unavailable',
			'name'   => '',
		);
	}

	$name = isset( $data['name'] ) ? trim( (string) $data['name'] ) : '';

	return array(
		// VIES returns the literal string "---" for a name it will not disclose.
		'status' => $data['isValid'] ? 'valid' : 'invalid',
		'name'   => '---' === $name ? '' : $name,
	);
}

/**
 * Queue the check when an order carrying a VAT number is placed.
 *
 * Deliberately not done inside checkout. A VIES lookup crosses the internet to
 * a service that is regularly slow and occasionally down; doing it inline would
 * mean the European Commission's uptime decides whether Doro Tape can take an
 * order. The number is already stored and the order is already placed by the
 * time this runs, so the worst a VIES outage can do is leave a verdict unfilled.
 *
 * @param WC_Order $order
 */
add_action( 'woocommerce_store_api_checkout_order_processed', function ( $order ): void {
	if ( ! $order instanceof WC_Order || '' === dorotape_get_vat_number( $order ) ) {
		return;
	}
	if ( ! wp_next_scheduled( DOROTAPE_VAT_CHECK_HOOK, array( $order->get_id() ) ) ) {
		wp_schedule_single_event( time(), DOROTAPE_VAT_CHECK_HOOK, array( $order->get_id() ) );
	}
} );

/**
 * Perform the queued check and record the verdict on the order.
 *
 * @param int $order_id
 */
add_action( DOROTAPE_VAT_CHECK_HOOK, function ( $order_id ): void {
	$order = wc_get_order( (int) $order_id );
	if ( ! $order ) {
		return;
	}

	$number = dorotape_get_vat_number( $order );
	$parts  = '' === $number ? array() : dorotape_vat_split( $number );
	if ( ! $parts ) {
		return;
	}

	list( $prefix, $body ) = $parts;

	// GB and XI are not answerable through VIES, so say so rather than leaving a
	// verdict that looks like it is still coming.
	$result = in_array( $prefix, array( 'GB', 'XI' ), true )
		? array(
			'status' => 'unsupported',
			'name'   => '',
		)
		: dorotape_vies_lookup( $prefix, $body );

	$order->update_meta_data(
		DOROTAPE_VAT_CHECK_META_KEY,
		array(
			'status'  => $result['status'],
			'name'    => $result['name'],
			'checked' => gmdate( 'c' ),
		)
	);

	/*
	 * An order note as well as the meta, so the verdict shows up in the place
	 * anyone investigating an order looks first, and stays there as a dated
	 * record of what VIES said on the day - which is the point of evidence.
	 */
	$order->add_order_note(
		sprintf(
			/* translators: 1: VAT number, 2: outcome sentence. */
			__( 'VAT number %1$s: %2$s', 'dorotape' ),
			$number,
			dorotape_vat_check_label( $result + array( 'name' => '' ) )
		)
	);

	$order->save();
} );

// ─── Admin ────────────────────────────────────────────────────────────────────

/**
 * Show the number and its verdict on the order edit screen, beside the order
 * details rather than buried in the custom fields box.
 *
 * @param WC_Order $order
 */
add_action( 'woocommerce_admin_order_data_after_order_details', function ( $order ): void {
	$number = dorotape_get_vat_number( $order );
	if ( '' === $number ) {
		return;
	}

	$check = dorotape_get_vat_check( $order );

	printf(
		'<p class="form-field form-field-wide"><strong>%s</strong><br>%s<br><span class="description">%s</span></p>',
		esc_html__( 'VAT number', 'dorotape' ),
		esc_html( $number ),
		esc_html( dorotape_vat_check_label( $check ) )
	);
} );

// ─── Customer facing ──────────────────────────────────────────────────────────

/**
 * Emails and the My Account order view.
 *
 * WooCommerce renders registered checkout fields on the order confirmation page
 * only, so both of these are done here. The customer's own VAT number belongs on
 * the paperwork they file, which is the whole reason we asked for it.
 *
 * @param WC_Order $order
 * @param bool     $sent_to_admin
 * @param bool     $plain_text
 */
add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin = false, $plain_text = false ): void {
	$number = dorotape_get_vat_number( $order );
	if ( '' === $number ) {
		return;
	}

	if ( $plain_text ) {
		printf( "%s: %s\n\n", esc_html__( 'VAT number', 'dorotape' ), esc_html( $number ) );
		return;
	}

	printf(
		'<p><strong>%s:</strong> %s</p>',
		esc_html__( 'VAT number', 'dorotape' ),
		esc_html( $number )
	);
}, 10, 3 );

/**
 * @param WC_Order $order
 */
add_action( 'woocommerce_order_details_after_order_table', function ( $order ): void {
	$number = dorotape_get_vat_number( $order );
	if ( '' === $number ) {
		return;
	}
	printf(
		'<p class="dorotape-order__vat"><strong>%s:</strong> %s</p>',
		esc_html__( 'VAT number', 'dorotape' ),
		esc_html( $number )
	);
} );
