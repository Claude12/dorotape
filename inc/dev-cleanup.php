<?php
/**
 * Dev cleanup — removes test-import products and fixes import data issues.
 *
 * v1.0.0: removed dev magnetic and partial Ritrama imports.
 * v1.0.1: removes all remaining RIT-* products; removes empty dev categories.
 * v1.0.2: fixes pa_size-width attribute terms — WC importer created combined
 *         terms ("625mm Width | 1250mm Width") instead of individual terms,
 *         so variable product dropdowns were empty. Splits them into individual
 *         terms and re-assigns parent products.
 *
 * Safe to delete this file once confirmed clean.
 *
 * @package dorotape
 */

define( 'DOROTAPE_DEV_CLEANUP_VERSION', '1.0.2' );

function dorotape_maybe_run_dev_cleanup(): void {
	if ( get_option( 'dorotape_dev_cleanup_version' ) === DOROTAPE_DEV_CLEANUP_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	dorotape_dev_cleanup_rit_products();
	dorotape_fix_size_width_terms();

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
		if ( 0 === (int) $term->count ) {
			wp_delete_term( $term->term_id, 'product_cat' );
		}
	}
}

/**
 * Fix pa_size-width attribute terms.
 *
 * The WooCommerce CSV importer stored the parent product's pipe-separated
 * attribute values as a single combined term, e.g.:
 *   name: "625mm Width | 1250mm Width"  slug: "625mm-width-1250mm-width"
 *
 * WooCommerce builds the variation dropdown from the parent's assigned terms,
 * so it found one combined option rather than two individual ones — making the
 * dropdown appear empty when clicked.
 *
 * This function:
 *   1. Finds every pa_size-width term whose name contains " | ".
 *   2. Splits it into individual names and creates/retrieves each as its own term.
 *   3. Re-assigns all affected parent products to the individual terms.
 *   4. Deletes the malformed combined terms.
 *   5. Clears the WooCommerce attribute lookup cache.
 */
function dorotape_fix_size_width_terms(): void {
	$taxonomy = 'pa_size-width';

	// Fetch all terms including empty ones (combined terms have count > 0 on the parent).
	$all_terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	) );

	if ( is_wp_error( $all_terms ) || empty( $all_terms ) ) {
		return;
	}

	foreach ( $all_terms as $combined_term ) {
		if ( strpos( $combined_term->name, ' | ' ) === false ) {
			continue; // Already an individual term — leave it alone.
		}

		$individual_names = array_filter(
			array_map( 'trim', explode( ' | ', $combined_term->name ) )
		);

		// Find every product that has this combined term assigned.
		$product_ids = get_objects_in_term( $combined_term->term_id, $taxonomy );
		if ( is_wp_error( $product_ids ) ) {
			$product_ids = array();
		}

		// Create or retrieve each individual term, then assign to affected products.
		$individual_term_ids = array();
		foreach ( $individual_names as $name ) {
			$existing = get_term_by( 'slug', sanitize_title( $name ), $taxonomy );
			if ( $existing ) {
				$individual_term_ids[] = (int) $existing->term_id;
			} else {
				$result = wp_insert_term( $name, $taxonomy );
				if ( ! is_wp_error( $result ) ) {
					$individual_term_ids[] = (int) $result['term_id'];
				}
			}
		}

		// Assign individual terms to each product; remove the combined term.
		foreach ( $product_ids as $product_id ) {
			// Get existing terms for this product (excluding the combined one).
			$current = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $current ) ) {
				$current = array();
			}
			$current = array_map( 'intval', $current );
			$current = array_diff( $current, array( (int) $combined_term->term_id ) );

			$new_terms = array_unique( array_merge( $current, $individual_term_ids ) );
			wp_set_object_terms( (int) $product_id, $new_terms, $taxonomy );

			// Flush the WC product attribute lookup table for this product.
			if ( function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( (int) $product_id );
				if ( $product ) {
					// Re-save triggers WC attribute lookup regeneration.
					$product->save();
				}
			}
		}

		// Delete the malformed combined term now that products are re-assigned.
		wp_delete_term( (int) $combined_term->term_id, $taxonomy );
	}

	// Clear WooCommerce's attribute term caches.
	delete_transient( 'wc_attribute_taxonomies' );
	WC_Cache_Helper::invalidate_cache_group( 'product_' . $taxonomy );
}
