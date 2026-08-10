<?php
/**
 * Credit limits at checkout (DR-10)
 *
 * DR-11 answers "is this account allowed credit at all", which decides whether
 * the pay-on-account method exists. This file answers the separate question
 * "does THIS basket fit inside what is left of their limit", which decides
 * whether they may use it today. See inc/pay-on-account.php, which says in as
 * many words that the check belongs on its own hook rather than inside
 * dorotape_can_pay_on_account().
 *
 * The behaviour is the client's, confirmed 10 Aug 2026: "a message to them that
 * lets them know that they are over their limit plus an option to pay by card".
 * So neither of the two options the spec offered. The order is not silently
 * accepted and flagged for someone to catch later, and it is not hard-blocked
 * either. The customer is told plainly and pointed at another way to pay.
 *
 * WHERE THE NUMBERS COME FROM. Woosage already syncs both figures out of Sage
 * onto the user, as `woosage_credit_limit` and `woosage_account_balance`
 * (Classes/Admin/Settings.php declares them readonly, and
 * Classes/REST_API/Controllers/V1/Customers.php writes them). Nothing here
 * invents a second copy in ACF. The theme reads Woosage's keys directly, the
 * same way inc/pricing.php reads its role prices and inc/purchase-order.php
 * writes its PO key, so there is one source of truth and no mapping to keep in
 * step. This is worth knowing for DR-21, which was written up as "pull credit
 * limits from Sage into ACF": most of that is already done, by the connector.
 *
 * WHAT WOOSAGE DOES NOT DO. Its own credit handling is a check made AFTER the
 * order is placed: the order lands in wc-pending-checks, Sage is asked, and it
 * then moves on or into wc-failed-checks. That is a different feature answering
 * a different question, and it is not what was asked for here, which happens
 * before the order exists. The two do not collide: this removes a gateway at
 * checkout, that one moves an order afterwards.
 *
 * @package dorotape
 */

/**
 * Woosage's keys, not ours. See the file header before changing them.
 */
define( 'DOROTAPE_CREDIT_LIMIT_META', 'woosage_credit_limit' );
define( 'DOROTAPE_CREDIT_BALANCE_META', 'woosage_account_balance' );

// ─── The figures ──────────────────────────────────────────────────────────────

/**
 * The customer's credit limit, or null when Sage has never told us one.
 *
 * Null and zero are deliberately different answers. Zero is a real limit: Sage
 * says this account may owe nothing, so any basket breaches it. Null means the
 * sync has not run, or Woosage is not installed, and in that case nothing is
 * enforced. Treating an absent value as a zero limit would take pay on account
 * away from every approved customer the moment this file shipped, which would
 * undo DR-11.
 */
function dorotape_credit_limit( ?int $user_id = null ): ?float {
	$user_id = $user_id ?? get_current_user_id();
	$raw     = $user_id ? get_user_meta( $user_id, DOROTAPE_CREDIT_LIMIT_META, true ) : '';
	$limit   = is_numeric( $raw ) ? (float) $raw : null;

	/**
	 * Filter the credit limit. Return null to switch enforcement off for a user.
	 */
	$limit = apply_filters( 'dorotape_credit_limit', $limit, $user_id );

	return null === $limit ? null : (float) $limit;
}

/**
 * What the account already owes.
 *
 * ASSUMPTION WORTH CHECKING against real Sage data: a positive balance means
 * money owed to Dorotape, so it eats into the limit. Sign conventions are the
 * usual place this sort of thing goes wrong, and it cannot be settled from the
 * plugin source alone, so it is filterable and flagged here rather than left to
 * be discovered by a customer.
 */
function dorotape_credit_balance( ?int $user_id = null ): float {
	$user_id = $user_id ?? get_current_user_id();
	$raw     = $user_id ? get_user_meta( $user_id, DOROTAPE_CREDIT_BALANCE_META, true ) : '';

	return (float) apply_filters( 'dorotape_credit_balance', is_numeric( $raw ) ? (float) $raw : 0.0, $user_id );
}

/**
 * How much credit is left, or null when there is no limit to measure against.
 * Can be negative, when an account is already past its limit before it orders.
 */
function dorotape_credit_available( ?int $user_id = null ): ?float {
	$limit = dorotape_credit_limit( $user_id );

	return null === $limit ? null : $limit - dorotape_credit_balance( $user_id );
}

/**
 * The current basket total, including shipping and tax as it stands.
 *
 * Filterable so the figure being tested against the limit can be substituted:
 * useful for exercising the thresholds without a live session, and a way out if
 * it turns out Sage should be shown the goods total rather than the gross.
 */
function dorotape_credit_cart_total(): float {
	$cart  = function_exists( 'WC' ) && WC() ? WC()->cart : null;
	$total = $cart ? (float) $cart->get_total( 'edit' ) : 0.0;

	return (float) apply_filters( 'dorotape_credit_cart_total', $total );
}

/**
 * True when this basket would take the customer past their limit.
 *
 * False whenever the answer is not knowable or not relevant: no limit synced,
 * not approved for credit in the first place, or an empty basket.
 */
function dorotape_credit_exceeded( ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();

	if ( ! $user_id || ! dorotape_can_pay_on_account( $user_id ) ) {
		return false;
	}

	$available = dorotape_credit_available( $user_id );
	if ( null === $available ) {
		return false;
	}

	$total = dorotape_credit_cart_total();
	if ( $total <= 0 ) {
		return false;
	}

	/**
	 * Filter the over-limit decision. The last word for anything that needs to
	 * wave an order through, such as a member of staff placing it by hand.
	 */
	return (bool) apply_filters(
		'dorotape_credit_exceeded',
		( $total - $available ) > 0.009, // A penny of tolerance, so rounding alone never trips it.
		$user_id,
		$total,
		$available
	);
}

// ─── Taking the payment method away ───────────────────────────────────────────

/**
 * Priority 110, so this runs after DR-11's gate at 100 and well after Invoice
 * Gateway re-adds itself at 10, 20 and 99. Removing the gateway here does two
 * jobs at once, because the block checkout reads this filter twice: once to
 * decide what to display, and again in StoreApi\Routes\V1\Checkout to validate
 * the method that was submitted. So the option disappears from the page AND a
 * request naming it directly is refused. See inc/pay-on-account.php.
 */
add_filter(
	'woocommerce_available_payment_gateways',
	function ( $gateways ): array {
		if ( ! is_array( $gateways ) || ! isset( $gateways[ DOROTAPE_ACCOUNT_GATEWAY_ID ] ) ) {
			return (array) $gateways;
		}

		// Leave the admin order screens alone, same reasoning as DR-11.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		if ( dorotape_credit_exceeded() ) {
			unset( $gateways[ DOROTAPE_ACCOUNT_GATEWAY_ID ] );
		}

		return $gateways;
	},
	110
);

// ─── Telling the customer why ─────────────────────────────────────────────────

/**
 * Whether anything else is left to pay with once the account option has gone.
 *
 * The message is written from what the customer can actually see, not from what
 * the spec assumes. Until DR-4 connects Opayo there may be no card option on
 * the site at all, and a message offering one would simply be untrue.
 */
function dorotape_credit_has_alternative(): bool {
	static $checking = false;

	if ( $checking || ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return false;
	}

	$checking  = true;
	$available = WC()->payment_gateways()->get_available_payment_gateways();
	$checking  = false;

	unset( $available[ DOROTAPE_ACCOUNT_GATEWAY_ID ] );

	return ! empty( $available );
}

/**
 * The over-limit message, or an empty string when there is nothing to say.
 */
function dorotape_credit_message(): string {
	if ( ! dorotape_credit_exceeded() ) {
		return '';
	}

	$available = (float) dorotape_credit_available();
	$total     = dorotape_credit_cart_total();

	$explanation = $available > 0
		? sprintf(
			/* translators: 1: credit still available, 2: basket total */
			esc_html__( 'You have %1$s of credit left on your account and this order comes to %2$s.', 'dorotape' ),
			wp_kses_post( wc_price( $available ) ),
			wp_kses_post( wc_price( $total ) )
		)
		: sprintf(
			/* translators: %s: the customer's credit limit */
			esc_html__( 'Your account is already at its credit limit of %s.', 'dorotape' ),
			wp_kses_post( wc_price( (float) dorotape_credit_limit() ) )
		);

	$next_step = dorotape_credit_has_alternative()
		? esc_html__( 'You can still place this order by choosing one of the other payment methods below.', 'dorotape' )
		// No contact number is written into this string. Nobody has given us one
		// to use, and an invented one is worse than none. This branch only
		// happens while the site has no card gateway at all, so it should stop
		// occurring the moment DR-4 lands.
		: esc_html__( 'Please get in touch and we will arrange payment another way.', 'dorotape' );

	return sprintf(
		'<strong>%s</strong> %s %s',
		esc_html__( 'This order is over your credit limit.', 'dorotape' ),
		$explanation,
		$next_step
	);
}

/**
 * Put the message above the checkout.
 *
 * Rendered server side into the block's markup rather than added with
 * wc_add_notice, because neither notice route reaches the block checkout in a
 * useful state. An error notice raised during a Store API request is converted
 * into a 400 by StoreApi\Utilities\NoticeHandler, and the
 * woocommerce_store_api_cart_errors action throws a 409 and stops checkout
 * dead. Both of those block the order, which is the outcome the client
 * explicitly did not want.
 *
 * KNOWN LIMITATION, the same one inc/address-book-checkout.php carries and for
 * the same reason: this is static HTML written at page load, while the checkout
 * itself is React. If the total moves after the page renders, usually by
 * picking a different shipping method, the message does not move with it. What
 * does stay live is the enforcement, because the gateway list is refetched on
 * every cart update, so the account option appears and disappears correctly
 * either way and the worst case is a message that is briefly out of date.
 * Fixing it properly needs JavaScript driving the wc/store/cart data store, and
 * the theme has no build step. Worth revisiting if the checkout ever gets a
 * bundled script.
 */
add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( ! isset( $block['blockName'] ) || 'woocommerce/checkout' !== $block['blockName'] ) {
			return $content;
		}

		$message = dorotape_credit_message();
		if ( '' === $message ) {
			return $content;
		}

		return '<div class="wc-block-components-notices dorotape-credit-notice">'
			. '<div class="wc-block-components-notice-banner is-info" role="alert">'
			. '<div class="wc-block-components-notice-banner__content">' . $message . '</div>'
			. '</div></div>'
			. $content;
	},
	10,
	2
);
