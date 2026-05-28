<?php
/**
 * Dev cleanup — removes test-import products created during theme development.
 *
 * v1.0.0: removed dev magnetic and partial Ritrama imports.
 * v1.0.1: removes all remaining RIT-* products now that canonical P-* SKUs
 *         are in the database from the CSV migration import. Also removes the
 *         dev-only Ritrama and Signmaking Vinyl categories if empty.
 *
 * Safe to delete this file once confirmed clean.
 *
 * @package dorotape
 */

define( 'DOROTAPE_DEV_CLEANUP_VERSION', '1.0.1' );

function dorotape_maybe_run_dev_cleanup(): void {
	if ( get_option( 'dorotape_dev_cleanup_version' ) === DOROTAPE_DEV_CLEANUP_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	dorotape_dev_cleanup_rit_products();

	update_option( 'dorotape_dev_cleanup_version', DOROTAPE_DEV_CLEANUP_VERSION );
}
add_action( 'admin_init', 'dorotape_maybe_run_dev_cleanup' );

/**
 * Delete all products whose SKU starts with 'RIT-'.
 * These were created by import-ritrama-platinum.php during development.
 * The canonical F-Sign Platinum products (P800–P868) came via CSV import.
 */
function dorotape_dev_cleanup_rit_products(): void {
	$ids = get_posts( array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_sku',
				'value'   => 'RIT-',
				'compare' => 'LIKE',
			),
		),
	) );

	foreach ( $ids as $id ) {
		$product = wc_get_product( (int) $id );
		if ( $product && str_starts_with( $product->get_sku(), 'RIT-' ) ) {
			$product->delete( true );
		}
	}

	// Remove the dev-only category hierarchy if it's now empty.
	foreach ( array( 'ritrama-f-sign-platinum', 'signmaking-vinyl' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term ) {
			continue;
		}
		$count = (int) $term->count;
		if ( $count === 0 ) {
			wp_delete_term( $term->term_id, 'product_cat' );
		}
	}
}
