<?php
/**
 * Admin email copies.
 *
 * WordPress sends its own admin notices (new user, password changed, data
 * requests, update results) to the single Settings > General admin address.
 * This copies those emails to everyone on the WooCommerce "New order"
 * recipient list, so one setting (WooCommerce > Settings > Emails > New order)
 * decides who receives all admin email.
 *
 * @package Dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recipients from the WooCommerce New order email setting.
 *
 * @return string[]
 */
function dorotape_admin_email_recipients() {
	$settings = get_option( 'woocommerce_new_order_settings', array() );
	$list     = isset( $settings['recipient'] ) && $settings['recipient'] ? $settings['recipient'] : '';
	return array_filter( array_map( 'trim', explode( ',', $list ) ), 'is_email' );
}

add_filter(
	'wp_mail',
	function ( $args ) {
		$admin = strtolower( (string) get_option( 'admin_email' ) );
		$extra = dorotape_admin_email_recipients();
		if ( ! $admin || ! $extra ) {
			return $args;
		}
		$to    = is_array( $args['to'] ) ? $args['to'] : explode( ',', (string) $args['to'] );
		$to    = array_filter( array_map( 'trim', $to ) );
		$lower = array_map( 'strtolower', $to );
		if ( ! in_array( $admin, $lower, true ) ) {
			return $args;
		}
		foreach ( $extra as $address ) {
			if ( ! in_array( strtolower( $address ), $lower, true ) ) {
				$to[]    = $address;
				$lower[] = strtolower( $address );
			}
		}
		$args['to'] = $to;
		return $args;
	}
);
