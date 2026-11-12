<?php
declare( strict_types=1 );
/**
 * Order again: a gallery of everything a customer has bought before.
 *
 * Michael asked for an "Order again" gallery built from order history (Site
 * Edits, Checkout A10, 12 November 2026). Trade customers here buy the same
 * films and consumables over and over, and the per-order Order again button
 * (inc/reorder.php) only helps when they want a whole past order repeated.
 * This tab shows each product they have bought, once, most recently bought
 * first, as the shop's own product cards, so one product can go back into
 * the basket on its own.
 *
 * It reads the customer's own orders, so the 12 months of orders brought
 * across from the old site fill it from the first day. A product that has
 * since been withdrawn, hidden or deleted is left out.
 *
 * Built like the reward points tab (inc/rewards-account.php): a classic My
 * Account endpoint with the same rewrite repair. The wording is on the
 * Account pages options page with the other account screens.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

const DOROTAPE_ORDER_AGAIN_ENDPOINT = 'order-again';

/**
 * How many products the gallery shows at most.
 */
const DOROTAPE_ORDER_AGAIN_LIMIT = 48;

// ─── Endpoint ─────────────────────────────────────────────────────────────────

add_action(
	'init',
	function (): void {
		add_rewrite_endpoint( DOROTAPE_ORDER_AGAIN_ENDPOINT, EP_ROOT | EP_PAGES );
	}
);

/**
 * Put the rewrite rule back if it is missing. See the address book's version
 * for why this checks the rules rather than an option.
 */
add_action(
	'init',
	function (): void {
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) || ! $rules ) {
			return;
		}
		foreach ( $rules as $rewrite ) {
			if ( str_contains( (string) $rewrite, DOROTAPE_ORDER_AGAIN_ENDPOINT . '=' ) ) {
				return;
			}
		}
		$flag = 'dorotape_order_again_flush_' . md5( home_url() );
		if ( get_transient( $flag ) ) {
			return;
		}
		set_transient( $flag, 1, HOUR_IN_SECONDS );
		flush_rewrite_rules( false );
	},
	11
);

add_filter(
	'woocommerce_get_query_vars',
	function ( array $vars ): array {
		$vars[ DOROTAPE_ORDER_AGAIN_ENDPOINT ] = DOROTAPE_ORDER_AGAIN_ENDPOINT;
		return $vars;
	}
);

/**
 * The tab's name in the account menu and on the dashboard card.
 */
function dorotape_order_again_label(): string {
	return dorotape_account_field( 'account_order_again_menu', __( 'Order again', 'dorotape' ) );
}

/**
 * The tab sits straight after Orders. Run after the reward points tab, which
 * also places itself after Orders, so this one ends up next to Orders.
 */
add_filter(
	'woocommerce_account_menu_items',
	function ( array $items ): array {
		$out = array();
		foreach ( $items as $key => $item ) {
			$out[ $key ] = $item;
			if ( 'orders' === $key ) {
				$out[ DOROTAPE_ORDER_AGAIN_ENDPOINT ] = dorotape_order_again_label();
			}
		}
		if ( ! isset( $out[ DOROTAPE_ORDER_AGAIN_ENDPOINT ] ) ) {
			$out[ DOROTAPE_ORDER_AGAIN_ENDPOINT ] = dorotape_order_again_label();
		}

		return $out;
	},
	25
);

add_filter(
	'woocommerce_endpoint_' . DOROTAPE_ORDER_AGAIN_ENDPOINT . '_title',
	static fn (): string => dorotape_order_again_label()
);

// ─── Products ─────────────────────────────────────────────────────────────────

/**
 * The products a customer has bought, most recently bought first.
 *
 * A variation is shown as its parent product, where the size is chosen. Only
 * orders that went through count: a cancelled, failed or unpaid order is not
 * something the customer bought.
 *
 * @param int $user_id Customer.
 * @return array<int, array{product: WC_Product, date: ?WC_DateTime}> Keyed by product id.
 */
function dorotape_order_again_products( int $user_id ): array {
	if ( $user_id <= 0 || ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$orders = wc_get_orders(
		array(
			'customer_id' => $user_id,
			'status'      => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
			'type'        => 'shop_order',
			'orderby'     => 'date',
			'order'       => 'DESC',
			'limit'       => 200,
		)
	);

	$found = array();

	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$id = (int) ( $item->get_product_id() ?: $item->get_variation_id() );

			if ( ! $id || isset( $found[ $id ] ) ) {
				continue;
			}

			$product = wc_get_product( $id );

			if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
				continue;
			}

			$found[ $id ] = array(
				'product' => $product,
				'date'    => $order->get_date_created(),
			);

			if ( count( $found ) >= DOROTAPE_ORDER_AGAIN_LIMIT ) {
				return $found;
			}
		}
	}

	return $found;
}

// ─── Screen ───────────────────────────────────────────────────────────────────

add_action(
	'woocommerce_account_' . DOROTAPE_ORDER_AGAIN_ENDPOINT . '_endpoint',
	function (): void {
		$items = dorotape_order_again_products( get_current_user_id() );

		if ( ! $items ) {
			printf(
				'<p class="dt-order-again__empty">%s</p><p><a class="button" href="%s">%s</a></p>',
				esc_html( dorotape_account_field( 'account_order_again_empty', __( 'Products you order will appear here, ready to order again.', 'dorotape' ) ) ),
				esc_url( dorotape_shop_crumb()['url'] ),
				esc_html( dorotape_account_field( 'account_order_again_empty_button', __( 'Browse products', 'dorotape' ) ) )
			);
			return;
		}

		$intro    = dorotape_account_field( 'account_order_again_intro', __( 'Everything you have bought from us, most recent first.', 'dorotape' ) );
		$last     = dorotape_account_field( 'account_order_again_last', __( 'Last ordered {date}', 'dorotape' ) );
		$original = $GLOBALS['product'] ?? null;

		echo '<div class="dt-order-again">';

		if ( '' !== $intro ) {
			printf( '<p class="dt-order-again__intro">%s</p>', esc_html( $intro ) );
		}

		echo dorotape_product_list_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper.

		foreach ( $items as $entry ) {
			// WooCommerce's loop button reads the global product.
			$GLOBALS['product'] = $entry['product']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
			$date               = $entry['date'] ? wc_format_datetime( $entry['date'] ) : '';

			echo '<li class="' . esc_attr( dorotape_product_list_item_class() ) . '">';
			dorotape_product_card(
				$entry['product'],
				array(
					'link'        => 'title',
					'add_to_cart' => true,
					'modifier'    => 'product-card--grid',
					'sizes'       => '(min-width: 1024px) 260px, (min-width: 576px) 46vw, calc(100vw - 48px)',
					'pills'       => '' !== $date && '' !== $last ? array( array( 'label' => str_replace( '{date}', $date, $last ) ) ) : array(),
				)
			);
			echo '</li>';
		}

		echo dorotape_product_list_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper.
		echo '</div>';

		$GLOBALS['product'] = $original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring it.
	}
);
