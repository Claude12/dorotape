<?php
/**
 * Orders held pending an account payment (DR-10, part two)
 *
 * WHAT THIS DOES, AND WHY IT IS THE OPPOSITE OF WHAT IT USED TO DO.
 *
 * The first build of DR-10 took the pay-on-account method away from an
 * over-limit customer and pointed them at paying by card, because that is what
 * the client asked for on 10 Aug 2026. On 11 Aug 2026 he reversed it, and the
 * reason matters more than the instruction: "we are not supposed to take orders
 * with card payments from customers with account balances that are over their
 * limit (or would take them over their credit limit)". A card payment settles the
 * order. It does not settle the account, so it leaves the overdue balance exactly
 * where it was while adding more goods on top. Paying by card was not a way round
 * the limit, it was the thing they are not allowed to do.
 *
 * So it is now the card methods that go, not the account one, and the order is
 * taken: "it would be better to accept the order with a flag at our end and a
 * message to the customer, something like 'Thank you for your order, which is on
 * our system pending an account payment being made'". That sentence is used
 * verbatim, in dorotape_account_pending_message().
 *
 * And it covers two situations, not one: "This is also true in the case of a
 * customer being on hold due to an overdue payment even though they may still
 * have credit left on their account." An account on hold is held regardless of
 * how much of the limit is unused, so the two facts are read separately.
 *
 * WHAT "CARD" MEANS HERE. It is defined by exclusion: a held customer is offered
 * pay on account and nothing else. A list of card gateway ids would be wrong
 * twice over, because the site has no card gateway connected yet (DR-4, Opayo)
 * so the list would be empty today, and it would silently stop matching the
 * moment a different one was installed. Excluding everything also happens to be
 * the more faithful reading: any other method at checkout takes money for this
 * order, and the whole point is that the money should go to the account instead.
 * `dorotape_account_pending_gateways` is there to widen that if BACS or similar
 * ever should be allowed through.
 *
 * WHY A STATUS AND NOT JUST A META FLAG. "A flag at our end" needs to be visible
 * to whoever is looking at the orders list without being told to go and look. A
 * custom status shows in the status filter row, the status column, the order
 * count badges and the My Account order list, all for free. The meta and the
 * order note underneath it are what survive the status being changed later, so
 * the reason an order was held is still on the record after it is released.
 *
 * The status itself is created in the CMS by the custom order status plugin, the
 * same as Ready for Collection. See the block above define() for what the plugin
 * does with it and which of its settings matter.
 *
 * The slug is short for two independent reasons. `post_status` is a varchar(20)
 * and WooCommerce prefixes its statuses with `wc-`, so `wc-dt-account-pending`
 * would be 21 characters and truncate on write; and the plugin's own slug field
 * allows 17. `dt-acct-pending` is 15, which clears both.
 *
 * NONE OF THIS IS TESTABLE ON DEV YET. No user has a credit limit, a balance or a
 * hold flag, none is approved for pay on account, and Woosage is not installed,
 * so every path here is currently switched off by its own guards. The filters on
 * inc/credit-limit.php are the way to exercise it before Sage is connected.
 *
 * @package dorotape
 */

/** The status slug, without WooCommerce's `wc-` prefix. See the header on length. */
define( 'DOROTAPE_ACCOUNT_PENDING_STATUS', 'dt-acct-pending' );

/** Why an order was held, kept so the reason outlives the status. */
define( 'DOROTAPE_ACCOUNT_PENDING_META', '_dorotape_account_pending' );

// ─── The status ───────────────────────────────────────────────────────────────

/**
 * The status is not registered here. It belongs to the plugin.
 *
 * bp-custom-order-status-for-woocommerce already owns Ready for Collection (see
 * inc/collection.php), and this is the same kind of thing, so it is created the
 * same way: an `order_status` post in the CMS, not a register_post_status() call
 * in the theme. One mechanism for one concept. The theme was doing the
 * registration itself for about a day, and the argument for moving it is the same
 * argument used for the top menu two commits earlier, which is that the client
 * should be able to see and change their own furniture without a deploy.
 *
 * What the plugin does with it, from src/Status.php, so nobody has to go and read
 * it again:
 *
 *  - registerPostOrderStatus() calls register_post_status() for every
 *    `order_status` post, with both admin-list flags on. That is what makes held
 *    orders visible in the orders list, and it is not optional: ListTable's All
 *    view intersects wc_get_order_statuses() with the post stati that have
 *    show_in_admin_all_list, so an unregistered status hides orders rather than
 *    merely mislabelling them
 *  - addStatusToFilter() array_merges the plugin's statuses onto the end of
 *    wc_order_statuses, so this one appears after Cancelled and Refunded rather
 *    than next to On hold where it belongs by meaning. That is the one thing lost
 *    in the move, and it is cosmetic
 *  - bp_add_order_statuses_to_editable() makes the order editable while it is
 *    held, if the status was created with Edit Mode on. A held order has not been
 *    paid for and has not been picked, so accounts may well need to take a line
 *    off it before releasing it. Leave that setting on
 *  - wcbvCustomStatusIsPaid() adds the status to woocommerce_order_is_paid_statuses
 *    if it was created with Paid Status on, and set_payment_date_on_status_change()
 *    then stamps date_paid. Leave that setting OFF. The entire meaning of this
 *    status is that a payment has not been made, and turning it on would tell
 *    reporting, and the Sage push in Stage 3, that the money is in
 *
 * The settings therefore matter as much as the slug, and none of them deploy.
 * See the go-live checklist in the README.
 */

/**
 * Whether the status actually exists in this database.
 *
 * It lives in the database, so it does not travel with a deploy, and a fresh
 * environment or a restored backup can easily have the code without the row.
 */
function dorotape_account_pending_status_exists(): bool {
	return array_key_exists( 'wc-' . DOROTAPE_ACCOUNT_PENDING_STATUS, wc_get_order_statuses() );
}

/**
 * Say so in the admin when it does not.
 *
 * Without this the failure is silent and looks like nothing at all: orders come
 * in, no flag appears, and the reason is a missing row nobody thought to check.
 */
add_action(
	'admin_notices',
	function (): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || dorotape_account_pending_status_exists() ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Dorotape: the "Pending account payment" order status is missing.', 'dorotape' ),
			esc_html(
				sprintf(
					/* translators: %s: order status slug. */
					__( 'Orders that should be held for an account payment will fall back to On hold with no flag and no note. Create an Order Status with the slug %s, Edit Mode on and Paid Status off.', 'dorotape' ),
					DOROTAPE_ACCOUNT_PENDING_STATUS
				)
			)
		);
	}
);

/**
 * Deliberately NOT added to woocommerce_order_is_paid_statuses.
 *
 * inc/collection.php has to add its status to that list, because an order is
 * already paid for by the time it is ready to collect. This one is the opposite:
 * the whole meaning of it is that a payment has not been made. Adding it would
 * tell reporting, and the Sage push in Stage 3, that the money is in.
 *
 * There are now two ways to get that wrong, because the plugin's Paid Status
 * setting on the status itself does the same thing through the same filter. The
 * omission here is deliberate and so is that setting being off.
 *
 * Also deliberately not in woocommerce_valid_order_statuses_for_payment, so My
 * Account shows no "Pay" button against a held order. There is nothing for the
 * customer to pay for here: the payment that is wanted is against the account,
 * not against this order, and a Pay button would invite the exact card payment
 * the client has just said they cannot accept.
 */

// ─── Deciding whether to hold ─────────────────────────────────────────────────

/**
 * Why this customer's order would be held, or an empty string if it would not.
 *
 * Returns 'on-hold' or 'over-limit'. On hold is tested first because it is the
 * stronger fact and does not depend on the basket: an account on hold is held
 * whatever the order comes to, so there is no point measuring it against a limit
 * it may well be inside.
 *
 * Everything is off unless the customer is approved for pay on account, which is
 * what dorotape_credit_exceeded() already checks and what makes the on-hold arm
 * safe: a hold flag on a cash customer's account is not a reason to hold their
 * card payment, because they were never buying on credit in the first place.
 *
 * @param int|null   $user_id Defaults to the current user.
 * @param float|null $total   Defaults to the current basket.
 */
function dorotape_account_pending_reason( ?int $user_id = null, ?float $total = null ): string {
	$user_id = $user_id ?? get_current_user_id();
	$reason  = '';

	if ( $user_id && dorotape_can_pay_on_account( $user_id ) ) {
		if ( dorotape_account_on_hold( $user_id ) ) {
			$reason = 'on-hold';
		} elseif ( dorotape_credit_exceeded( $user_id, $total ) ) {
			$reason = 'over-limit';
		}
	}

	/**
	 * Filter the hold decision. Return an empty string to let an order straight
	 * through, which is how a member of staff placing an order by hand for a
	 * customer who has just paid can be waved past.
	 *
	 * @param string     $reason  '', 'on-hold' or 'over-limit'.
	 * @param int        $user_id
	 * @param float|null $total
	 */
	return (string) apply_filters( 'dorotape_account_pending_reason', $reason, $user_id, $total );
}

/**
 * The same question asked about an order that already exists.
 *
 * Used to record the flag after checkout, when the basket has been emptied and
 * the order total is the only figure left to measure.
 */
function dorotape_account_pending_order_reason( WC_Order $order ): string {
	return dorotape_account_pending_reason( $order->get_customer_id(), (float) $order->get_total() );
}

/**
 * Is this order being held right now?
 *
 * Reads the status rather than the meta, on purpose. The meta is a record of why
 * an order was held and stays on it forever; the status is whether it still is.
 * Customer-facing messages have to follow the status, or a released order goes on
 * telling the customer it is waiting for a payment that has already been made.
 *
 * @param WC_Order|int|false $order
 */
function dorotape_order_is_account_pending( $order ): bool {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );

	return $order && DOROTAPE_ACCOUNT_PENDING_STATUS === $order->get_status();
}

// ─── Taking the card methods away ─────────────────────────────────────────────

/**
 * Leave pay on account, remove everything else.
 *
 * Priority 120, after DR-11's gate at 100 and after all three of Invoice
 * Gateway's own callbacks (10, 20, 99). See inc/pay-on-account.php for why that
 * plugin's habit of re-adding itself makes the priority load-order sensitive.
 *
 * The removal is a real one and not a hidden button. The block checkout reads
 * this filter twice: once to decide what to display, and again in
 * StoreApi\Routes\V1\Checkout::get_request_payment_method() to validate what was
 * submitted, which refuses an unavailable gateway with a 400. So a request naming
 * a card gateway directly is turned down too.
 *
 * Two guards that matter more than they look. If the account gateway is not in
 * the list there is nothing to fall back to, so nothing is removed: the
 * alternative is a checkout with no payment methods at all, which is a dead end
 * for the customer and would happen the moment Invoice Gateway was deactivated.
 * And admin is left alone, same as DR-11, so a shop manager taking a phone order
 * still sees every method.
 *
 * @param array $gateways Available gateways, keyed by id.
 * @return array
 */
add_filter(
	'woocommerce_available_payment_gateways',
	function ( $gateways ): array {
		if ( ! is_array( $gateways ) ) {
			return (array) $gateways;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		if ( ! isset( $gateways[ DOROTAPE_ACCOUNT_GATEWAY_ID ] ) ) {
			return $gateways;
		}

		if ( '' === dorotape_account_pending_reason() ) {
			return $gateways;
		}

		/**
		 * Filter which methods a held customer may still use.
		 *
		 * @param array $allowed  Gateway ids.
		 * @param array $gateways Everything that was on offer.
		 */
		$allowed = (array) apply_filters(
			'dorotape_account_pending_gateways',
			array( DOROTAPE_ACCOUNT_GATEWAY_ID ),
			$gateways
		);

		$kept = array_intersect_key( $gateways, array_flip( $allowed ) );

		// A filter that empties the list would strand the customer. Ignore it.
		return $kept ? $kept : $gateways;
	},
	120
);

// ─── Holding the order ────────────────────────────────────────────────────────

/**
 * Send the order to the held status instead of the gateway's normal one.
 *
 * Invoice Gateway hands its own status through a filter before applying it
 * (IGFW_Invoice_Gateway::process_payment(), which reads igfw_default_order_status
 * and then filters it), so this is one hook rather than a fight with whatever the
 * gateway does afterwards. Nothing here changes for a customer who is not held:
 * the gateway's configured status is returned untouched.
 *
 * The basket is still loaded at this point. process_payment() empties it two
 * lines further down, after the status has been set, so the total being measured
 * is the one being ordered.
 *
 * The trade-off in using a plugin's own filter is that a different account
 * gateway would not fire it. That is noted in the README rather than guarded
 * against, because the fallbacks worth having all involve intercepting status
 * changes the shop's own staff make, and getting that wrong is worse than the
 * problem.
 *
 * The existence check is not defensive tidiness. The status lives in the database
 * now, and WC_Abstract_Order::set_status() silently substitutes `pending` for any
 * status that is not in wc_get_order_statuses(). Pending needs payment, so a
 * missing status would put a Pay button on exactly the order the client has just
 * said must not be paid by card. Returning the gateway's own status instead sends
 * it to On hold, which is unflagged but harmless, and the admin notice above says
 * why. Blocking the card methods is unaffected either way: that rule is the
 * client's, and it does not get to depend on a row existing.
 *
 * @param string $status
 * @return string
 */
add_filter(
	'igfw_invoice_gateway_default_order_status',
	function ( $status ) {
		if ( '' === dorotape_account_pending_reason() || ! dorotape_account_pending_status_exists() ) {
			return $status;
		}

		return DOROTAPE_ACCOUNT_PENDING_STATUS;
	}
);

/**
 * Record why, the moment the order lands in the status.
 *
 * On the transition rather than at order creation, so the record is only written
 * about something that actually happened. An order flagged at creation that then
 * took a different route would carry a note saying it was held when it was not.
 *
 * Runs once. The meta is its own guard, so an order released to Processing and
 * later put back does not collect a second note explaining the first hold.
 *
 * A status set by hand in admin reaches here with no reason to find, because the
 * customer's account is not over anything. That is recorded as 'manual' and left
 * without a note: whoever set it knows why, and a note guessing at their reason
 * would be worse than silence.
 *
 * @param int           $order_id
 * @param WC_Order|null $order
 */
add_action(
	'woocommerce_order_status_' . DOROTAPE_ACCOUNT_PENDING_STATUS,
	function ( $order_id, $order = null ): void {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );

		if ( ! $order || $order->get_meta( DOROTAPE_ACCOUNT_PENDING_META ) ) {
			return;
		}

		$reason = dorotape_account_pending_order_reason( $order );

		$order->update_meta_data( DOROTAPE_ACCOUNT_PENDING_META, $reason ? $reason : 'manual' );
		$order->save();

		if ( $reason ) {
			$order->add_order_note( dorotape_account_pending_note( $order, $reason ) );
		}
	},
	10,
	2
);

/**
 * Money as plain text, for an order note.
 *
 * wc_price() returns markup and a `&pound;` entity, and an order note is stored
 * as text and escaped on the way out, so both have to go or the accounts team
 * reads "&pound;1,234.00".
 */
function dorotape_account_pending_amount( float $amount ): string {
	return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
}

/**
 * The internal note. Written for whoever has to act on it.
 *
 * The figures are included because they are the first thing anyone will ask and
 * because they move: the balance this decision was made against is not the
 * balance that will be on the account tomorrow, so the note is the only place the
 * numbers as they stood are kept.
 */
function dorotape_account_pending_note( WC_Order $order, string $reason ): string {
	$user_id = $order->get_customer_id();
	$limit   = dorotape_credit_limit( $user_id );

	$why = 'on-hold' === $reason
		? __( 'the account is on hold following an overdue payment', 'dorotape' )
		: __( 'this order takes the account past its credit limit', 'dorotape' );

	return sprintf(
		/* translators: 1: the reason, lower case mid-sentence, 2: credit limit, 3: account balance, 4: order total. */
		__( 'Held pending an account payment: %1$s. Credit limit %2$s, account balance %3$s, this order %4$s. No card payment has been taken.', 'dorotape' ),
		$why,
		null === $limit ? __( 'not set', 'dorotape' ) : dorotape_account_pending_amount( $limit ),
		dorotape_account_pending_amount( dorotape_credit_balance( $user_id ) ),
		dorotape_account_pending_amount( (float) $order->get_total() )
	);
}

// ─── Telling the customer ─────────────────────────────────────────────────────

/**
 * The message, in the client's own words.
 *
 * Kept as he wrote it on 11 Aug 2026, and kept the same on the order received
 * page, in the My Account order view and in the email, so a customer who reads
 * two of the three is not left working out whether they say different things.
 *
 * It does not name the reason. That is on purpose: "your account is on hold"
 * lands very differently when a buyer is looking at the screen with a colleague
 * beside them, and the accounts contact who needs to know already will be told by
 * the person they owe the money to. Before the order is placed the reason IS
 * given, in dorotape_account_pending_checkout_notice(), because there it is the
 * explanation for why the payment methods look different.
 */
function dorotape_account_pending_message(): string {
	return __( 'Thank you for your order, which is on our system pending an account payment being made.', 'dorotape' );
}

/**
 * On the order received page and the My Account order view.
 *
 * Both screens run woocommerce_order_details_after_order_table, so one hook
 * covers the thank-you page and the order the customer opens a week later. Same
 * hook and same reasoning as inc/collection.php.
 *
 * @param WC_Order $order
 */
add_action(
	'woocommerce_order_details_after_order_table',
	function ( $order ): void {
		if ( ! $order instanceof WC_Order || ! dorotape_order_is_account_pending( $order ) ) {
			return;
		}

		printf(
			'<p class="dorotape-account-pending-notice">%s</p>',
			esc_html( dorotape_account_pending_message() )
		);
	},
	5
);

/**
 * The notice above the checkout, before the order is placed.
 *
 * This one does name the reason, because without it the customer is looking at a
 * checkout where the card option has silently disappeared and no explanation is
 * on the page. It is the difference between a rule and a fault.
 *
 * Rendered server side into the block's markup rather than through wc_add_notice,
 * because neither notice route reaches the block checkout in a useful state: an
 * error notice raised during a Store API request is turned into a 400 by
 * StoreApi\Utilities\NoticeHandler, and woocommerce_store_api_cart_errors throws
 * a 409 and stops checkout dead. Both of those block the order, and taking the
 * order is now the entire point.
 *
 * KNOWN LIMITATION, the same one inc/address-book-checkout.php carries: this is
 * static HTML written at page load while the checkout itself is React, so if the
 * total moves afterwards, usually by picking a different shipping method, the
 * message does not move with it. It matters less than it did. The on-hold case
 * cannot go stale at all, because it does not depend on the total; the over-limit
 * case can, and the worst outcome is a message about a threshold the basket has
 * just dropped back under, while the payment methods themselves stay correct
 * because the gateway list is refetched on every cart update. Fixing it properly
 * needs JavaScript driving the wc/store/cart data store, and the theme has no
 * build step.
 */
function dorotape_account_pending_checkout_notice(): string {
	$reason = dorotape_account_pending_reason();

	if ( '' === $reason ) {
		return '';
	}

	if ( 'on-hold' === $reason ) {
		$explanation = esc_html__( 'Your account is on hold while a payment is overdue.', 'dorotape' );
	} else {
		$available = (float) dorotape_credit_available();
		$total     = dorotape_credit_cart_total();

		$explanation = $available > 0
			? sprintf(
				/* translators: 1: credit still available, 2: basket total. */
				esc_html__( 'You have %1$s of credit left on your account and this order comes to %2$s.', 'dorotape' ),
				wp_kses_post( wc_price( $available ) ),
				wp_kses_post( wc_price( $total ) )
			)
			: sprintf(
				/* translators: %s: the customer's credit limit. */
				esc_html__( 'Your account is already at its credit limit of %s.', 'dorotape' ),
				wp_kses_post( wc_price( (float) dorotape_credit_limit() ) )
			);
	}

	return sprintf(
		'<strong>%s</strong> %s %s',
		esc_html__( 'This order will be held for an account payment.', 'dorotape' ),
		$explanation,
		esc_html__( 'You can still place it. The order goes on to your account and we will start work on it once a payment has been made, so there is nothing to pay here.', 'dorotape' )
	);
}

/**
 * Put that notice above the checkout block.
 *
 * @param string $content
 * @param array  $block
 * @return string
 */
add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( ! isset( $block['blockName'] ) || 'woocommerce/checkout' !== $block['blockName'] ) {
			return $content;
		}

		$message = dorotape_account_pending_checkout_notice();
		if ( '' === $message ) {
			return $content;
		}

		return '<div class="wc-block-components-notices dorotape-account-pending-notice">'
			. '<div class="wc-block-components-notice-banner is-info" role="alert">'
			. '<div class="wc-block-components-notice-banner__content">' . $message . '</div>'
			. '</div></div>'
			. $content;
	},
	10,
	2
);

// ─── Emails ───────────────────────────────────────────────────────────────────

/**
 * WooCommerce's own emails that have to be told this status exists.
 *
 * This is the part of a custom status that goes wrong silently. Every one of
 * WooCommerce's transactional emails lists the exact transitions it fires on, and
 * a status invented by a theme is in none of those lists, so the default outcome
 * is not a slightly wrong email, it is no email at all: the customer places an
 * order and never hears another word, the office never hears about it, and
 * releasing the order later tells the customer nothing either. All three are
 * missing until they are wired up by hand.
 *
 * Keyed by the transition, minus the `woocommerce_order_status_` prefix:
 *
 *  - into the status from pending, which is a new order arriving, so the office
 *    gets WooCommerce's normal New Order email
 *  - out of it into processing, which is the order being released after a payment
 *    has landed, so the customer gets the normal Processing email they would have
 *    had if the order had never been held
 *  - out of it into cancelled, so a held order being written off is not the one
 *    kind of cancellation nobody is told about
 *
 * Completed needs nothing: that email fires on the bare status action, which
 * WooCommerce already lists.
 *
 * The theme's own email, for the moment the order is held, is not in here. It
 * listens for the plain status action from its own constructor, the same way
 * Dorotape_Email_Ready_For_Collection does.
 */
function dorotape_account_pending_email_transitions(): array {
	$status = DOROTAPE_ACCOUNT_PENDING_STATUS;

	return array(
		'pending_to_' . $status  => 'WC_Email_New_Order',
		$status . '_to_processing' => 'WC_Email_Customer_Processing_Order',
		$status . '_to_cancelled'  => 'WC_Email_Cancelled_Order',
	);
}

/**
 * Let WooCommerce fire notification actions for all of it.
 *
 * WC_Emails::init_transactional_emails() only wires up a `_notification` action
 * for hooks in this list, so without this every email class below is listening
 * for something that is never fired.
 *
 * @param array $actions
 * @return array
 */
add_filter(
	'woocommerce_email_actions',
	function ( array $actions ): array {
		$actions[] = 'woocommerce_order_status_' . DOROTAPE_ACCOUNT_PENDING_STATUS;

		foreach ( array_keys( dorotape_account_pending_email_transitions() ) as $transition ) {
			$actions[] = 'woocommerce_order_status_' . $transition;
		}

		return $actions;
	}
);

/**
 * Register the theme's email, and point WooCommerce's at the new transitions.
 *
 * The existing emails are attached here rather than anywhere else because this is
 * the first moment they exist as objects. WC_Emails::instance() builds them and
 * fires this filter, and only then does send_transactional_email() fire the
 * `_notification` action, so an action added from inside this callback is in place
 * in time. Reusing WooCommerce's own objects rather than copying them into the
 * theme means they keep the one set of settings a shop manager can edit, and the
 * wording cannot drift from the email the same customer gets on an ordinary order.
 *
 * @param array $emails
 * @return array
 */
add_filter(
	'woocommerce_email_classes',
	function ( array $emails ): array {
		require_once get_template_directory() . '/inc/emails/class-dorotape-email-account-pending.php';
		$emails['Dorotape_Email_Account_Pending'] = new Dorotape_Email_Account_Pending();

		foreach ( dorotape_account_pending_email_transitions() as $transition => $class ) {
			if ( ! isset( $emails[ $class ] ) ) {
				continue;
			}

			add_action(
				'woocommerce_order_status_' . $transition . '_notification',
				array( $emails[ $class ], 'trigger' ),
				10,
				2
			);
		}

		return $emails;
	}
);

// ─── Admin ────────────────────────────────────────────────────────────────────

/**
 * Say why, under the status on the orders list.
 *
 * The status already says an order is held. This says which of the two reasons it
 * was, which is the difference between "chase the balance" and "the account is on
 * hold, this is not going anywhere until finance clear it", and saves opening the
 * order to read the note.
 *
 * The hook is the HPOS one. `manage_woocommerce_page_wc-orders_custom_column` is
 * the orders screen this site actually uses; the legacy
 * `manage_shop_order_posts_custom_column` would never fire.
 *
 * @param string            $column
 * @param WC_Order|int|null $order
 */
add_action(
	'manage_woocommerce_page_wc-orders_custom_column',
	function ( string $column, $order = null ): void {
		if ( 'order_status' !== $column ) {
			return;
		}

		$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
		if ( ! $order || ! dorotape_order_is_account_pending( $order ) ) {
			return;
		}

		// A status set by hand carries no reason worth repeating in a column.
		$reason = (string) $order->get_meta( DOROTAPE_ACCOUNT_PENDING_META );
		if ( 'on-hold' !== $reason && 'over-limit' !== $reason ) {
			return;
		}

		$label = 'on-hold' === $reason
			? __( 'Account on hold', 'dorotape' )
			: __( 'Over credit limit', 'dorotape' );

		printf( '<br><small class="dorotape-account-pending-flag">%s</small>', esc_html( $label ) );
	},
	20,
	2
);
