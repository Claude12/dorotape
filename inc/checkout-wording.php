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
 * Hand the replacements to wp.i18n before the checkout block renders.
 *
 * Attached to wp-hooks, which every block script depends on, so it runs
 * before any of them. Only on the checkout, the one page the string is on.
 */
function dorotape_checkout_wording_script(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	wp_enqueue_script( 'wp-hooks' );
	wp_add_inline_script(
		'wp-hooks',
		sprintf(
			'(function(map){wp.hooks.addFilter("i18n.gettext_woocommerce","dorotape/checkout-wording",function(t,s){return Object.prototype.hasOwnProperty.call(map,s)?map[s]:t;});})(%s);',
			wp_json_encode( dorotape_checkout_wording() )
		)
	);
}
add_action( 'wp_enqueue_scripts', 'dorotape_checkout_wording_script' );
