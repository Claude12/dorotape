<?php
/**
 * Pay on account eligibility
 *
 * Invoice Gateway offers itself to everyone. The spec says only customers Sage
 * has approved for credit may pay on account, and the plugin has no user or role
 * level restriction of its own: its is_available() only tests virtual orders and
 * shipping methods, so there is nothing to configure and this gate has to exist.
 *
 * The flag is an ACF user field, pay_on_account_approved, off by default and
 * maintained by hand until the Sage sync takes it over in Stage 3. Nothing here
 * needs to change when that happens; the sync writes the same field.
 *
 * This is a real gate, not a hidden button. The block checkout reads the same
 * woocommerce_available_payment_gateways filter twice: once to decide what to
 * display, and again in StoreApi\Routes\V1\Checkout::get_request_payment_method()
 * to validate what was submitted, which rejects an unavailable gateway with a
 * 400. So a request naming igfw_invoice_gateway directly is refused too.
 *
 * @package dorotape
 */

/** Invoice Gateway's gateway id. */
define( 'DOROTAPE_ACCOUNT_GATEWAY_ID', 'igfw_invoice_gateway' );

/**
 * May this user pay on account?
 *
 * False for guests, which is deliberate. Credit is extended to an account, and
 * an unauthenticated visitor is not one.
 *
 * @param int|null $user_id Defaults to the current user.
 */
function dorotape_can_pay_on_account( ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();

	if ( ! $user_id ) {
		return false;
	}

	/**
	 * Filter whether a user may pay on account.
	 *
	 * The credit limit check in DR-10 is a separate question and belongs on its
	 * own hook, not in here. This one answers "is this account allowed credit at
	 * all", which is what decides whether the payment method exists.
	 *
	 * @param bool $approved
	 * @param int  $user_id
	 */
	return (bool) apply_filters(
		'dorotape_can_pay_on_account',
		(bool) get_field( 'pay_on_account_approved', 'user_' . $user_id ),
		$user_id
	);
}

/**
 * Remove the gateway for anyone not approved.
 *
 * @param array $gateways Available gateways, keyed by id.
 * @return array
 */
add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ): array {
	if ( ! is_array( $gateways ) || ! isset( $gateways[ DOROTAPE_ACCOUNT_GATEWAY_ID ] ) ) {
		return (array) $gateways;
	}

	// Leave the admin order screens alone. A shop manager taking a phone order or
	// changing an order's payment method is not the customer-facing checkout this
	// gate is about, and hiding it there looks like the plugin is broken.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}

	if ( ! dorotape_can_pay_on_account() ) {
		unset( $gateways[ DOROTAPE_ACCOUNT_GATEWAY_ID ] );
	}

	return $gateways;
} );

// ─── Admin ────────────────────────────────────────────────────────────────────

/**
 * Column on the Users list.
 *
 * The flag is set by hand until Stage 3, so "who is approved" has to be
 * answerable without opening every user in turn.
 *
 * @param array $columns
 * @return array
 */
add_filter( 'manage_users_columns', function ( array $columns ): array {
	$columns['dorotape_account'] = __( 'Pay on account', 'dorotape' );
	return $columns;
} );

/**
 * @param string $output
 * @param string $column
 * @param int    $user_id
 * @return string
 */
add_filter( 'manage_users_custom_column', function ( $output, $column, $user_id ) {
	if ( 'dorotape_account' !== $column ) {
		return $output;
	}

	return dorotape_can_pay_on_account( (int) $user_id )
		? '<span aria-hidden="true">&#10003;</span> ' . esc_html__( 'Approved', 'dorotape' )
		: '<span style="color:#888">' . esc_html__( 'No', 'dorotape' ) . '</span>';
}, 10, 3 );
