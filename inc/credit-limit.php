<?php
/**
 * What we know about an account's credit (DR-10, part one)
 *
 * This file answers questions and decides nothing. It reads the figures Sage has
 * given us and works out two facts: whether this basket goes past what is left
 * of the customer's limit, and whether their account is on hold. What happens as
 * a result is inc/account-pending.php, and it is deliberately not in here: the
 * arithmetic has survived two complete reversals of the policy built on top of
 * it, so the two belong in separate files.
 *
 * DR-11 (inc/pay-on-account.php) answers the earlier question, "is this account
 * allowed credit at all", which decides whether the pay-on-account method exists
 * for them at all. Everything here assumes that has already said yes.
 *
 * WHERE THE NUMBERS COME FROM. Woosage already syncs all three figures out of
 * Sage onto the user, as `woosage_credit_limit`, `woosage_account_balance` and
 * `woosage_account_on_hold` (Classes/Admin/Settings.php declares them readonly,
 * and Classes/REST_API/Controllers/V1/Customers.php writes them). Nothing here
 * invents a second copy in ACF. The theme reads Woosage's keys directly, the
 * same way inc/pricing.php reads its role prices and inc/purchase-order.php
 * writes its PO key, so there is one source of truth and no mapping to keep in
 * step. This is worth knowing for DR-21, which was written up as "pull credit
 * limits from Sage into ACF": most of that is already done, by the connector.
 *
 * WHAT WOOSAGE DOES NOT DO. Its own credit handling is a check made AFTER the
 * order is placed: the order lands in wc-pending-checks, Sage is asked, and it
 * then moves on or into wc-failed-checks. That is a different feature answering
 * a different question. The two do not collide, and the flow built on this file
 * now looks a lot like it, which is worth remembering if that feature is ever
 * switched on: they would both be holding the same order for the same reason.
 *
 * @package dorotape
 */

/**
 * Woosage's keys, not ours. See the file header before changing them.
 */
define( 'DOROTAPE_CREDIT_LIMIT_META', 'woosage_credit_limit' );
define( 'DOROTAPE_CREDIT_BALANCE_META', 'woosage_account_balance' );
define( 'DOROTAPE_ACCOUNT_ON_HOLD_META', 'woosage_account_on_hold' );

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
 * Is this account on hold?
 *
 * Its own question, and not a variation of the limit one. The client, 11 Aug
 * 2026: "This is also true in the case of a customer being on hold due to an
 * overdue payment even though they may still have credit left on their account."
 * So an account can be well inside its limit and still not be allowed to place a
 * paid order, and the two facts have to be read separately.
 *
 * Woosage casts this one to a boolean before storing, which means it lands as
 * '1' or as an empty string rather than as 'yes'/'no'. An empty string, an
 * absent key and an uninstalled connector are all the same answer here: false,
 * nothing is enforced. That is the safe default in the same way an unset credit
 * limit is, because the alternative holds every order on the site.
 */
function dorotape_account_on_hold( ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();
	$raw     = $user_id ? get_user_meta( $user_id, DOROTAPE_ACCOUNT_ON_HOLD_META, true ) : '';

	/**
	 * Filter whether an account counts as on hold.
	 *
	 * @param bool $on_hold
	 * @param int  $user_id
	 */
	return (bool) apply_filters( 'dorotape_account_on_hold', ! empty( $raw ), $user_id );
}

/**
 * True when this order would take the customer past their limit.
 *
 * False whenever the answer is not knowable or not relevant: no limit synced,
 * not approved for credit in the first place, or nothing being bought.
 *
 * @param int|null   $user_id Defaults to the current user.
 * @param float|null $total   The figure to test. Defaults to the current basket,
 *                            which is what checkout wants. Pass an order total to
 *                            ask the same question about an order that already
 *                            exists, which is what the flag on it is recorded
 *                            from once the basket has been emptied.
 */
function dorotape_credit_exceeded( ?int $user_id = null, ?float $total = null ): bool {
	$user_id = $user_id ?? get_current_user_id();

	if ( ! $user_id || ! dorotape_can_pay_on_account( $user_id ) ) {
		return false;
	}

	$available = dorotape_credit_available( $user_id );
	if ( null === $available ) {
		return false;
	}

	$total = $total ?? dorotape_credit_cart_total();
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
