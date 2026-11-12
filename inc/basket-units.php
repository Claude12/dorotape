<?php
/**
 * Units and counts in the basket, the checkout and on orders.
 *
 * - Each basket line's price says its unit ("£11.79 per metre"), the same
 *   unit the product page shows (QA, 12 Nov).
 * - The header's "Basket (n)" follows the Cart block when a line is removed
 *   there. The block talks to the Store API, so WooCommerce's classic AJAX
 *   fragments, which keep the count right everywhere else, never fire.
 * - An order counts lines, not quantities, so 3m plus one knife is "2 items",
 *   not "4 items". The basket already counts this way (inc/header.php).
 *
 * @package Dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hand each basket line's unit to the Cart and Checkout blocks.
 *
 * Registered on init for the same reason as inc/free-delivery.php: a theme
 * loads after woocommerce_blocks_loaded has fired.
 */
add_action(
	'init',
	function (): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
				'namespace'       => 'dorotape',
				'data_callback'   => static function ( $cart_item ): array {
					$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
					if ( ! $product instanceof WC_Product ) {
						return array( 'unit_suffix' => '' );
					}
					$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

					return array( 'unit_suffix' => trim( dorotape_price_unit_suffix( $id ) ) );
				},
				'schema_callback' => static fn (): array => array(
					'unit_suffix' => array(
						'description' => __( 'How the line is priced, e.g. "per metre", or empty.', 'dorotape' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
				'schema_type'     => ARRAY_A,
			)
		);
	}
);

/**
 * The small script that puts the unit on each line and keeps the header count
 * in step, on the basket and checkout pages only.
 *
 * It hangs off the blocks' own scripts, so it runs only where they run, after
 * them, and does nothing if WooCommerce renames them.
 */
add_action(
	'wp_enqueue_scripts',
	function (): void {
		if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) {
			return;
		}
		if ( ! wp_script_is( 'wc-blocks-checkout', 'registered' ) ) {
			return;
		}

		wp_register_script( 'dorotape-basket-units', '', array( 'wc-blocks-checkout', 'wc-blocks-data-store', 'wp-data' ), wp_get_theme()->get( 'Version' ), true );
		wp_enqueue_script( 'dorotape-basket-units' );
		wp_add_inline_script(
			'dorotape-basket-units',
			<<<'JS'
(function () {
	var checkout = window.wc && window.wc.blocksCheckout;
	if (checkout && checkout.registerCheckoutFilters) {
		checkout.registerCheckoutFilters('dorotape-units', {
			// Despite its name this one formats the unit price, the line's price per
			// metre or roll. cartItemPrice formats the line total, which has no unit.
			subtotalPriceFormat: function (value, extensions) {
				var unit = extensions && extensions.dorotape && extensions.dorotape.unit_suffix;
				return unit ? value + ' ' + unit : value;
			}
		});
	}

	var data = window.wp && window.wp.data;
	if (!data || !data.select('wc/store/cart')) return;
	var last = null;
	data.subscribe(function () {
		var cart = data.select('wc/store/cart').getCartData();
		if (!cart || 'number' !== typeof cart.itemsCount || cart.itemsCount === last) return;
		last = cart.itemsCount;
		document.querySelectorAll('.site-header__utility-count--cart').forEach(function (el) {
			el.textContent = String(last);
		});
	});
})();
JS
		);
	}
);

/**
 * Count an order's lines, not the sum of their quantities.
 *
 * WooCommerce adds quantities, and a roll sold by the metre carries its
 * metres as the quantity, so 3m of vinyl and one knife read as "4 items" in
 * My Account and the admin. Only the product line count is changed; other
 * item types (shipping, fees) are passed through.
 *
 * @param int      $count     WooCommerce's count.
 * @param string   $item_type The item type asked for.
 * @param WC_Order $order     The order.
 * @return int
 */
function dorotape_order_line_count( $count, $item_type, $order ) {
	if ( ! $order instanceof WC_Abstract_Order || ! in_array( $item_type, array( '', 'line_item' ), true ) ) {
		return $count;
	}

	return count( $order->get_items( 'line_item' ) );
}
add_filter( 'woocommerce_get_item_count', 'dorotape_order_line_count', 10, 3 );
