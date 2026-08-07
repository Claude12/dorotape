<?php
/**
 * Collection journey
 *
 * Order flow for customers collecting in person rather than having goods
 * delivered: Pending, Processing, Ready for Collection, Completed.
 *
 * The Ready for Collection status itself already exists, registered by
 * bp-custom-order-status-for-woocommerce. Its slug is `ready-for-collect`, not
 * `ready-for-collection`, which is worth knowing before writing a hook name that
 * silently never fires. Everything around the status is here:
 *
 *  - the status counting as paid, which it did not
 *  - the customer email at that status, which did not exist
 *  - on-site messaging setting the expectation that an email is coming
 *  - Local Pickup as a shipping method, which is configuration rather than code
 *    and lives in bin/shipping-collection.php
 *
 * @package dorotape
 */

/** Registered by the custom order status plugin. Note: "collect", not "collection". */
define( 'DOROTAPE_COLLECTION_STATUS', 'ready-for-collect' );

/** WooCommerce's built-in collection shipping method. */
define( 'DOROTAPE_COLLECTION_METHOD', 'local_pickup' );

// ─── The status ───────────────────────────────────────────────────────────────

/**
 * Count Ready for Collection as paid.
 *
 * The flow reaches it from Processing, so the money is already in. Left out,
 * $order->is_paid() returns false the moment an order is marked ready, which
 * quietly affects reporting, anything gating on payment, and the Sage push in
 * Stage 3. It is a one-line omission that is very hard to spot from the symptom.
 *
 * @param array $statuses
 * @return array
 */
add_filter( 'woocommerce_order_is_paid_statuses', function ( array $statuses ): array {
	$statuses[] = DOROTAPE_COLLECTION_STATUS;
	return array_unique( $statuses );
} );

/**
 * Let WooCommerce fire a notification action for this status.
 *
 * WC_Emails::init_transactional_emails() only wires up `_notification` actions
 * for hooks in this list. The email class listens for
 * woocommerce_order_status_ready-for-collect_notification, which does not exist
 * until this filter adds it.
 *
 * @param array $actions
 * @return array
 */
add_filter( 'woocommerce_email_actions', function ( array $actions ): array {
	$actions[] = 'woocommerce_order_status_' . DOROTAPE_COLLECTION_STATUS;
	return $actions;
} );

// ─── The email ────────────────────────────────────────────────────────────────

/**
 * Register the email so it appears in WooCommerce > Settings > Emails and can be
 * edited, disabled or previewed there like any other.
 *
 * @param array $emails
 * @return array
 */
add_filter( 'woocommerce_email_classes', function ( array $emails ): array {
	require_once get_template_directory() . '/inc/emails/class-dorotape-email-ready-for-collection.php';
	$emails['Dorotape_Email_Ready_For_Collection'] = new Dorotape_Email_Ready_For_Collection();
	return $emails;
} );

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Is this order being collected rather than delivered?
 *
 * Reads the shipping method actually stored on the order, so it stays correct
 * for an order placed before any later change to the shipping setup.
 *
 * @param WC_Order|int|false $order
 */
function dorotape_order_is_collection( $order ): bool {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return false;
	}

	foreach ( $order->get_shipping_methods() as $method ) {
		if ( DOROTAPE_COLLECTION_METHOD === $method->get_method_id() ) {
			return true;
		}
	}

	return false;
}

/**
 * Where the customer collects from: the store's own address.
 *
 * Local Pickup can carry a per-method address, so that is preferred when set and
 * the store base address is the fallback. Returned formatted for display.
 */
function dorotape_get_collection_address(): string {
	$countries = WC()->countries;

	if ( ! $countries ) {
		return '';
	}

	return $countries->get_formatted_address(
		array(
			'address_1' => $countries->get_base_address(),
			'address_2' => $countries->get_base_address_2(),
			'city'      => $countries->get_base_city(),
			'state'     => $countries->get_base_state(),
			'postcode'  => $countries->get_base_postcode(),
			'country'   => $countries->get_base_country(),
		)
	);
}

/**
 * Flatten a notice to plain text.
 *
 * WC_Countries::get_formatted_address() joins lines with `<br/>`, not `<br>`, so
 * a plain str_replace on `<br>` alone leaves the address run together as one
 * unreadable line in every plain text email.
 */
function dorotape_notice_to_plain( string $html ): string {
	return trim( wp_strip_all_tags( preg_replace( '/<br\s*\/?>/i', "\n", $html ) ) );
}

/**
 * The message shown while an order is on its way to being collectable.
 *
 * Stops here rather than promising a date. Nothing on the site knows when the
 * warehouse will have it ready, and a guessed date is worse than none.
 */
function dorotape_collection_notice( WC_Order $order ): string {
	if ( DOROTAPE_COLLECTION_STATUS === $order->get_status() ) {
		$address = dorotape_get_collection_address();
		return $address
			? sprintf(
				/* translators: %s: store address, already formatted with line breaks. */
				__( 'Your order is ready to collect from:<br>%s', 'dorotape' ),
				$address
			)
			: __( 'Your order is ready to collect.', 'dorotape' );
	}

	if ( in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded', 'failed' ), true ) ) {
		return '';
	}

	return __( 'You have chosen to collect this order. You will receive an email when it is ready for collection.', 'dorotape' );
}

// ─── Customer facing ──────────────────────────────────────────────────────────

/**
 * Notice on the order received page and the My Account order view.
 *
 * Both screens run woocommerce_order_details_after_order_table, so one hook
 * covers the thank-you page and the order the customer opens a week later.
 *
 * @param WC_Order $order
 */
add_action( 'woocommerce_order_details_after_order_table', function ( $order ): void {
	if ( ! $order instanceof WC_Order || ! dorotape_order_is_collection( $order ) ) {
		return;
	}

	$notice = dorotape_collection_notice( $order );
	if ( '' === $notice ) {
		return;
	}

	printf(
		'<p class="dorotape-collection-notice">%s</p>',
		wp_kses( $notice, array( 'br' => array() ) )
	);
}, 5 );

/**
 * Same message in every order email, so a customer who only reads their inbox
 * still knows to wait for the collection email rather than expecting a courier.
 *
 * @param WC_Order $order
 * @param bool     $sent_to_admin
 * @param bool     $plain_text
 */
add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin = false, $plain_text = false ): void {
	if ( $sent_to_admin || ! $order instanceof WC_Order || ! dorotape_order_is_collection( $order ) ) {
		return;
	}

	// The dedicated collection email says this far better, so skip it there.
	if ( DOROTAPE_COLLECTION_STATUS === $order->get_status() ) {
		return;
	}

	$notice = dorotape_collection_notice( $order );
	if ( '' === $notice ) {
		return;
	}

	if ( $plain_text ) {
		printf( "\n%s\n", dorotape_notice_to_plain( $notice ) );
		return;
	}

	printf( '<p>%s</p>', wp_kses( $notice, array( 'br' => array() ) ) );
}, 20, 3 );

// ─── Admin ────────────────────────────────────────────────────────────────────

/**
 * Mark collection orders on the admin orders list, so the warehouse can tell at
 * a glance which orders are waiting for someone to walk in.
 *
 * @param string            $column
 * @param WC_Order|int|null $order
 */
add_action( 'manage_woocommerce_page_wc-orders_custom_column', function ( string $column, $order = null ): void {
	if ( 'order_status' !== $column || ! dorotape_order_is_collection( $order ) ) {
		return;
	}
	printf(
		'<br><small class="dorotape-collection-flag">%s</small>',
		esc_html__( 'Collection', 'dorotape' )
	);
}, 20, 2 );
