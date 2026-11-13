<?php
declare( strict_types=1 );
/**
 * WooCommerce's own checkout wording, reworded.
 *
 * The checkout is the WooCommerce block, whose text is translated in the
 * browser by wp.i18n, so the gettext_woocommerce filter PHP-side never sees
 * it. wp.i18n runs its own filter of the same name, which is what this hooks.
 *
 * Keyed on WooCommerce's source string, so the replacement holds whatever
 * the site is called and survives a WooCommerce update unless the source
 * string itself changes, in which case the checkout simply shows
 * WooCommerce's wording again.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Source string => what the checkout shows instead.
 *
 * "Create an account with Dorotape" read as applying for a trade account
 * with a credit limit, when all the box does is keep the customer's details
 * for next time (Site Edits, October 2026).
 *
 * @return array<string, string>
 */
function dorotape_checkout_wording(): array {
	return array(
		'Create an account with %s' => __( 'Save my details for future use', 'dorotape' ),
	);
}

/**
 * Hand the replacements to wp.i18n before the basket and checkout render.
 *
 * Attached to wp-hooks, which every block script depends on, so it runs
 * before any of them.
 *
 * The second filter is for delivery that is quoted afterwards. Its rate costs
 * nothing at this point, so WooCommerce printed "Free" beside it and in the
 * totals, on an order the customer will be charged delivery for. While the
 * chosen rate is a quoted one, "Free" reads as the wording from Woo pages
 * instead. It asks the cart store each time the text is drawn, so it follows a
 * change of delivery address without a reload.
 */
function dorotape_checkout_wording_script(): void {
	if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
		return;
	}

	$quoted = function_exists( 'dorotape_woo_pages_field' )
		? (string) dorotape_woo_pages_field( 'woo_delivery_quoted_label', __( 'To be quoted', 'dorotape' ) )
		: __( 'To be quoted', 'dorotape' );

	wp_enqueue_script( 'wp-hooks' );
	wp_add_inline_script(
		'wp-hooks',
		sprintf(
			'(function(map,quoted){'
			. 'wp.hooks.addFilter("i18n.gettext_woocommerce","dorotape/checkout-wording",function(t,s){return Object.prototype.hasOwnProperty.call(map,s)?map[s]:t;});'
			. 'function isQuoted(){try{var st=wp.data&&wp.data.select("wc/store/cart");if(!st||!st.getCartData){return false;}var p=st.getCartData().shippingRates||[];'
			. 'for(var i=0;i<p.length;i++){var r=p[i].shipping_rates||[];for(var j=0;j<r.length;j++){if(r[j].selected&&/:quote$/.test(String(r[j].rate_id))){return true;}}}}catch(e){}return false;}'
			. 'wp.hooks.addFilter("i18n.gettext_woocommerce","dorotape/quoted-delivery",function(t,s){return ("Free"===s||"free"===s||"FREE"===s)&&isQuoted()?quoted:t;});'
			. '})(%s,%s);',
			wp_json_encode( dorotape_checkout_wording() ),
			wp_json_encode( $quoted )
		)
	);
}
add_action( 'wp_enqueue_scripts', 'dorotape_checkout_wording_script' );
