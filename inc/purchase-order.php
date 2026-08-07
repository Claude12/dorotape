<?php
/**
 * Purchase order numbers
 *
 * Trade customers order against a PO number and expect it back on the invoice,
 * so it has to survive the whole way from checkout into Sage.
 *
 * Three plugins on this site offer a version of this field and none of them fits:
 *
 *  - Invoice Gateway renders its PO field inside its own payment box, so it only
 *    exists for customers paying on account. A card or BACS order could never
 *    carry one.
 *  - Woosage can add a labelled checkout field, but its own tooltip says not to
 *    use it if the site already has one, and that it does not work with the
 *    block based checkout. This site uses the block checkout. It does have a
 *    register_additional_checkout_field() method that would work, but the line
 *    that hooks it up is commented out in Classes/Modules/Checkout_Fields.php,
 *    so switching its field on today produces a field that never renders.
 *  - WooCommerce core has no such field.
 *
 * So the field is ours, and it is registered through
 * woocommerce_register_additional_checkout_field() - the supported route for the
 * block checkout, which the classic woocommerce_after_order_notes hook is not.
 *
 * The value is stored under Woosage's own meta key rather than ours. Woosage
 * exposes `purchase_order` on its REST order schema straight from that key
 * (Classes/REST_API/Controllers/V1/Orders.php), and its
 * "Use PO Reference for Customer Order Number" setting puts it in Sage's
 * Customer Order No. field. Writing the key it already reads means the PO
 * reaches Sage with no mapping code and nothing to keep in sync. Everything here
 * reads that one key, so there is a single source of truth.
 *
 * @package dorotape
 */

/**
 * Woosage's key, not ours. See the file header before changing it.
 */
define( 'DOROTAPE_PO_META_KEY', '_woosage_purchase_order' );

/**
 * Woosage's own save is `if ( strlen( $po ) <= 20 )` with no else, so a longer
 * value is not truncated, it is dropped on the floor without a word. Sage's
 * Customer Order No. is 20 characters too. Rejected at checkout instead, where
 * the customer can still do something about it.
 */
define( 'DOROTAPE_PO_MAX_LENGTH', 20 );

/** The registered field id. The saved meta key is Woosage's, not this. */
define( 'DOROTAPE_PO_FIELD_ID', 'dorotape/purchase-order' );

// ─── Checkout ─────────────────────────────────────────────────────────────────

/**
 * Register the checkout field.
 *
 * Skipped entirely if Woosage's own PO field has been switched on, because two
 * fields collecting one value is worse than either. Woosage is inactive today
 * and DR-15 turns it on, so this guard is what stops that day producing a
 * duplicate field nobody notices until a customer fills in the wrong one.
 */
add_action( 'woocommerce_init', function (): void {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	if ( function_exists( 'WS50' ) && WS50()->get_setting( 'purchase_order_checkout_field_label' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'         => DOROTAPE_PO_FIELD_ID,
			'label'      => __( 'Purchase order number', 'dorotape' ),
			'location'   => 'order',
			'type'       => 'text',
			'required'   => false,
			'attributes' => array(
				// WooCommerce drops any attribute outside its allowed list, and
				// these two are on it. maxLength stops most over-length values in
				// the browser; validate_callback below is what actually enforces it.
				'maxLength'    => DOROTAPE_PO_MAX_LENGTH,
				'autocomplete' => 'off',
			),
			'sanitize_callback' => function ( $value ) {
				return trim( wp_strip_all_tags( (string) $value ) );
			},
			// Supplying this replaces WooCommerce's default callback, which is the
			// one that enforces `required`. Safe only because this field is optional.
			'validate_callback' => function ( $value ) {
				if ( '' !== $value && mb_strlen( (string) $value ) > DOROTAPE_PO_MAX_LENGTH ) {
					return new WP_Error(
						'dorotape_po_too_long',
						sprintf(
							/* translators: %d: maximum number of characters. */
							__( 'Your purchase order number must be %d characters or fewer.', 'dorotape' ),
							DOROTAPE_PO_MAX_LENGTH
						)
					);
				}
			},
		)
	);
} );

/**
 * Copy the value onto Woosage's meta key.
 *
 * The action fires before WooCommerce writes its own `_wc_other/…` key, and on
 * the same order object, so this rides along in the same save rather than
 * costing a second write.
 *
 * The object is a WC_Customer as well as a WC_Order in other flows, hence the
 * instanceof - a PO belongs to one order, never to the customer as a default.
 *
 * @param string               $key       Field id being saved.
 * @param mixed                $value     Field value.
 * @param string               $group     Field group (shipping|billing|other).
 * @param WC_Data              $wc_object Object being saved.
 */
add_action( 'woocommerce_set_additional_field_value', function ( $key, $value, $group, $wc_object ): void {
	if ( DOROTAPE_PO_FIELD_ID !== $key || ! $wc_object instanceof WC_Order ) {
		return;
	}
	$wc_object->update_meta_data( DOROTAPE_PO_META_KEY, (string) $value );
}, 10, 4 );

// ─── Reading ──────────────────────────────────────────────────────────────────

/**
 * The PO number on an order, or an empty string.
 *
 * @param WC_Order|int|false $order Order, id, or anything falsy.
 */
function dorotape_get_purchase_order( $order ): string {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return '';
	}
	return trim( (string) $order->get_meta( DOROTAPE_PO_META_KEY, true ) );
}

// ─── Admin ────────────────────────────────────────────────────────────────────

/**
 * Show it on the order edit screen, beside the order number rather than buried
 * in the custom fields box.
 *
 * @param WC_Order $order
 */
add_action( 'woocommerce_admin_order_data_after_order_details', function ( $order ): void {
	$po = dorotape_get_purchase_order( $order );
	if ( '' === $po ) {
		return;
	}
	printf(
		'<p class="form-field form-field-wide"><strong>%s</strong><br>%s</p>',
		esc_html__( 'Purchase order number', 'dorotape' ),
		esc_html( $po )
	);
} );

/**
 * A column on the orders list, because "which order was PO 4471 again?" is the
 * question this field exists to answer.
 *
 * Registered for both the HPOS orders screen and the legacy post-type one. This
 * site runs HPOS, but the theme should not break if that is ever turned off.
 *
 * @param array $columns
 * @return array
 */
function dorotape_purchase_order_column( array $columns ): array {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$out['dorotape_po'] = __( 'PO number', 'dorotape' );
		}
	}
	if ( ! isset( $out['dorotape_po'] ) ) {
		$out['dorotape_po'] = __( 'PO number', 'dorotape' );
	}
	return $out;
}
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'dorotape_purchase_order_column' );
add_filter( 'manage_edit-shop_order_columns', 'dorotape_purchase_order_column' );

/**
 * @param string            $column
 * @param WC_Order|int|null $order HPOS passes the order, the legacy screen passes nothing.
 */
function dorotape_purchase_order_column_value( string $column, $order = null ): void {
	if ( 'dorotape_po' !== $column ) {
		return;
	}
	$po = dorotape_get_purchase_order( $order ?: get_the_ID() );
	echo '' === $po ? '&ndash;' : esc_html( $po );
}
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'dorotape_purchase_order_column_value', 10, 2 );
add_action( 'manage_shop_order_posts_custom_column', 'dorotape_purchase_order_column_value', 10, 2 );

// ─── Customer facing ──────────────────────────────────────────────────────────

/**
 * Emails.
 *
 * WooCommerce renders registered checkout fields on the order confirmation page
 * only. Emails and the My Account order view are not covered, so both are done
 * here rather than left to look like they work until someone checks an invoice.
 *
 * @param WC_Order $order
 * @param bool     $sent_to_admin
 * @param bool     $plain_text
 */
add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin = false, $plain_text = false ): void {
	$po = dorotape_get_purchase_order( $order );
	if ( '' === $po ) {
		return;
	}

	if ( $plain_text ) {
		printf( "%s: %s\n\n", esc_html__( 'Purchase order number', 'dorotape' ), esc_html( $po ) );
		return;
	}

	printf(
		'<p><strong>%s:</strong> %s</p>',
		esc_html__( 'Purchase order number', 'dorotape' ),
		esc_html( $po )
	);
}, 10, 3 );

/**
 * My Account, order detail view.
 *
 * @param WC_Order $order
 */
add_action( 'woocommerce_order_details_after_order_table', function ( $order ): void {
	$po = dorotape_get_purchase_order( $order );
	if ( '' === $po ) {
		return;
	}
	printf(
		'<p class="dorotape-order__po"><strong>%s:</strong> %s</p>',
		esc_html__( 'Purchase order number', 'dorotape' ),
		esc_html( $po )
	);
} );
