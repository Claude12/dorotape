<?php
/**
 * Dev cleanup — removes bulk-imported products not needed during development.
 *
 * Removes all Magnetic Ferrous Vinyl products and all but 10 Ritrama colours.
 * Client will supply a full product CSV for the real import.
 *
 * Safe to delete this file once the real products are imported.
 *
 * @package dorotape
 */

define( 'DOROTAPE_DEV_CLEANUP_VERSION', '1.0.0' );

function dorotape_maybe_run_dev_cleanup(): void {
	if ( get_option( 'dorotape_dev_cleanup_version' ) === DOROTAPE_DEV_CLEANUP_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	dorotape_dev_cleanup_magnetic();
	dorotape_dev_cleanup_ritrama();

	update_option( 'dorotape_dev_cleanup_version', DOROTAPE_DEV_CLEANUP_VERSION );
}
add_action( 'admin_init', 'dorotape_maybe_run_dev_cleanup' );

/**
 * Delete all Magnetic Ferrous Vinyl products and the category.
 */
function dorotape_dev_cleanup_magnetic(): void {
	$term = get_term_by( 'slug', 'magnetic-ferrous-vinyl', 'product_cat' );
	if ( ! $term ) {
		return;
	}

	$ids = get_posts( array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		) ),
	) );

	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}

	wp_delete_term( $term->term_id, 'product_cat' );
}

/**
 * Delete all Ritrama F-Sign Platinum products except the 10 dev colours.
 */
function dorotape_dev_cleanup_ritrama(): void {
	$keep = array(
		'RIT-P804', // Pastel Yellow  — standard pricing
		'RIT-P811', // Orange         — standard pricing
		'RIT-P813', // Poppy Red      — standard pricing
		'RIT-P819', // Red            — has airflow flag
		'RIT-P823', // Process Magenta — has airflow flag
		'RIT-P827', // Midnight Blue  — standard pricing
		'RIT-P838', // Ocean Blue     — standard pricing
		'RIT-P844', // Forest Green   — standard pricing
		'RIT-P801', // Black          — special: £2.30/m, 50m roll, 4400% modifier
		'RIT-P856', // Anthracite     — special: £3.37/m, 2155.19% modifier
	);

	$term = get_term_by( 'slug', 'ritrama-f-sign-platinum', 'product_cat' );
	if ( ! $term ) {
		return;
	}

	$ids = get_posts( array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		) ),
	) );

	foreach ( $ids as $id ) {
		$product = wc_get_product( (int) $id );
		if ( $product && ! in_array( $product->get_sku(), $keep, true ) ) {
			wp_delete_post( (int) $id, true );
		}
	}
}
