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
						return array( 'unit_suffix' => '', 'unit' => '' );
					}
					$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

					return array(
						'unit_suffix' => trim( dorotape_price_unit_suffix( $id ) ),
						// A variation's own unit wins, as on the product page.
						'unit'        => dorotape_price_unit_meta( $product->get_id() ),
					);
				},
				'schema_callback' => static fn (): array => array(
					'unit_suffix' => array(
						'description' => __( 'How the line is priced, e.g. "per metre", or empty.', 'dorotape' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'unit'        => array(
						'description' => __( 'The unit the quantity is in: metre, roll, item, or empty.', 'dorotape' ),
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
		$words = array(
			'metre' => array(
				'short' => __( 'm', 'dorotape' ),
				'one'   => __( 'metre', 'dorotape' ),
				'many'  => __( 'metres', 'dorotape' ),
			),
			'roll'  => array(
				'short' => '',
				'one'   => __( 'roll', 'dorotape' ),
				'many'  => __( 'rolls', 'dorotape' ),
			),
		);

		wp_add_inline_script(
			'dorotape-basket-units',
			str_replace( '__WORDS__', (string) wp_json_encode( $words ), <<<'JS'
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

	// The checkout summary's quantity badge: "40" over the picture, read out
	// as "40 items", on a line of 40 metres (dev QA, 25 Nov). A metre line now
	// shows "40m", read as "40 metres"; a roll line is read as rolls.
	var words = __WORDS__;
	var queued = false;
	function badges() {
		queued = false;
		var items = (data.select('wc/store/cart').getCartData() || {}).items || [];
		document.querySelectorAll('.wc-block-components-order-summary-item').forEach(function (row, i) {
			var item = items[i];
			var unit = item && item.extensions && item.extensions.dorotape && item.extensions.dorotape.unit;
			var spans = row.querySelectorAll('.wc-block-components-order-summary-item__quantity > span');
			if (!item || !words[unit] || spans.length < 2) return;
			var shown = String(item.quantity) + words[unit].short;
			var said = String(item.quantity) + ' ' + (1 === item.quantity ? words[unit].one : words[unit].many);
			if (spans[0].textContent !== shown) spans[0].textContent = shown;
			if (spans[1].textContent !== said) spans[1].textContent = said;
			// WooCommerce's hidden line price, "Total price for 10 <name> items: £99.00"
			// (dev retest, 25 Nov), becomes "Total price for 10 metres of <name>: £99.00".
			row.querySelectorAll('.screen-reader-text').forEach(function (sr) {
				var m = /^Total price for \S+ ([\s\S]*?) items?:/.exec(sr.textContent);
				if (!m) return;
				sr.textContent = 'Total price for ' + said + ' of ' + m[1] + ':' + sr.textContent.slice(m[0].length);
			});
		});
	}
	data.subscribe(function () {
		if (queued) return;
		queued = true;
		window.requestAnimationFrame(badges);
	});

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
			)
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
