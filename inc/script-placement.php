<?php
/**
 * jQuery at the foot of the page instead of the head.
 *
 * jQuery was the one script in <head> that held up the first paint
 * (Lighthouse mobile, 26 Nov 2026: about 150ms on every page tested). Every
 * script that needs it, directly or through another script, moves down with
 * it, so the order they run in does not change: jQuery first, then the
 * scripts that use it. WooCommerce's head scripts were already deferred, so
 * they still run after the page is read, exactly as before.
 *
 * jQuery prints at the very start of the footer, before anything else
 * there. A few footer scripts use jQuery without declaring it (the Cart
 * block checks for it as it loads), and the footer newsletter loader
 * (inc/newsletter-assets.php) only defers WPForms once jQuery is out.
 *
 * Nothing in the page body may call jQuery inline any more. Checked on
 * 26 Nov 2026: nothing does. A plugin that adds such a script would need
 * this switched off with the `dorotape_jquery_in_footer` filter.
 *
 * The cookie banner script (CookieYes) stays in the head on purpose: it has
 * to run before anything else so it can hold tracking scripts back until a
 * visitor consents.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to move jQuery on this request.
 */
function dorotape_jquery_in_footer_enabled(): bool {
	return ! is_admin() && (bool) apply_filters( 'dorotape_jquery_in_footer', true );
}

/**
 * Move jQuery, and every script that needs it, to the footer group.
 *
 * Runs on wp_head after the scripts are enqueued (wp_enqueue_scripts fires
 * at wp_head 1) and before the head scripts print (wp_head 9).
 */
function dorotape_move_jquery_to_footer(): void {
	$scripts = wp_scripts();
	if ( ! dorotape_jquery_in_footer_enabled() || ! isset( $scripts->registered['jquery-core'] ) ) {
		return;
	}

	$needs = array(
		'jquery'      => true,
		'jquery-core' => true,
	);
	do {
		$added = false;
		foreach ( $scripts->registered as $handle => $script ) {
			if ( isset( $needs[ $handle ] ) ) {
				continue;
			}
			foreach ( (array) $script->deps as $dep ) {
				if ( isset( $needs[ $dep ] ) ) {
					$needs[ $handle ] = true;
					$added            = true;
					break;
				}
			}
		}
	} while ( $added );

	foreach ( array_keys( $needs ) as $handle ) {
		$scripts->add_data( $handle, 'group', 1 );
	}
}
add_action( 'wp_head', 'dorotape_move_jquery_to_footer', 2 );

/**
 * Print jQuery first thing in the footer.
 */
function dorotape_print_jquery_first(): void {
	if ( dorotape_jquery_in_footer_enabled() && wp_script_is( 'jquery', 'enqueued' ) && ! wp_script_is( 'jquery', 'done' ) ) {
		wp_scripts()->do_items( array( 'jquery' ) );
	}
}
add_action( 'wp_footer', 'dorotape_print_jquery_first', 1 );
