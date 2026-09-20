<?php
declare( strict_types=1 );
/**
 * Helpers for the Product Row block (inc/blocks/product-row-block.php).
 *
 * They live here rather than in the template because the homepage uses the
 * block twice, and a function declared in a template would be declared a
 * second time on the second include.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The products a row shows.
 *
 * Every source except "manual" only picks products that have an image, since
 * the row is a strip of pictures and the catalogue holds plenty without one.
 * Hand-picked products are shown as chosen, image or not.
 *
 * The automatic sources also skip anything an earlier row on the page
 * already shows, so two rows never repeat each other: "Best sellers" under
 * "New products" would otherwise overlap whenever the newest lines sell
 * well, and entirely on a shop with no sales yet, where best sellers falls
 * back to newest.
 *
 * WP_Query rather than wc_get_products(), because the latter silently drops
 * the meta_query the image check needs.
 *
 * @param string $source One of newest, featured, on_sale, best_selling, manual.
 * @param int    $count  How many to show for the automatic sources.
 * @param array  $ids    Product IDs, in order, for the manual source.
 * @return WC_Product[]
 */
function dorotape_product_row_products( string $source, int $count, array $ids ): array {
	static $shown = array();

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, $count ),
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		),
		'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_thumbnail_id',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
	);

	if ( $shown && 'manual' !== $source ) {
		$args['post__not_in'] = $shown;
	}

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'outofstock' ),
			'operator' => 'NOT IN',
		);
	}

	switch ( $source ) {
		case 'manual':
			$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
			if ( ! $ids ) {
				return array();
			}
			$args['post__in']       = $ids;
			$args['orderby']        = 'post__in';
			$args['posts_per_page'] = count( $ids );
			unset( $args['meta_query'] );
			break;

		case 'featured':
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'featured' ),
			);
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;

		case 'on_sale':
			// WP_Query ignores post__not_in alongside post__in, so the
			// products already shown come off the list here instead.
			$on_sale = array_values( array_diff( wc_get_product_ids_on_sale(), $shown ) );
			if ( ! $on_sale ) {
				return array();
			}
			$args['post__in'] = $on_sale;
			$args['orderby']  = 'date';
			$args['order']    = 'DESC';
			break;

		case 'best_selling':
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			);
			break;

		default: // newest
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}

	$query = new WP_Query( $args );

	$shown = array_merge( $shown, array_map( 'intval', $query->posts ) );

	return array_values( array_filter( array_map( 'wc_get_product', $query->posts ) ) );
}

/**
 * The short line under a card's product name: its most specific category,
 * e.g. "ASLAN Blockout Films" rather than the top-level range it sits in.
 *
 * @param WC_Product $product
 * @return string Empty when the product has no category.
 */
function dorotape_product_row_meta( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( ! is_array( $terms ) || ! $terms ) {
		return '';
	}

	$best       = null;
	$best_depth = -1;

	foreach ( $terms as $term ) {
		$depth = count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );

		if ( $depth > $best_depth ) {
			$best       = $term;
			$best_depth = $depth;
		}
	}

	return $best ? $best->name : '';
}

/**
 * The largest saving on a product that is on sale, as a whole percentage.
 * For a variable product that is the best saving across its variations.
 *
 * @param WC_Product $product
 * @return int 0 when the product is not on sale.
 */
function dorotape_product_row_saving( WC_Product $product ): int {
	if ( ! $product->is_on_sale() ) {
		return 0;
	}

	$pairs = array();

	if ( $product instanceof WC_Product_Variable ) {
		$prices = $product->get_variation_prices();

		foreach ( $prices['regular_price'] as $variation_id => $regular ) {
			$pairs[] = array( (float) $regular, (float) ( $prices['sale_price'][ $variation_id ] ?? $regular ) );
		}
	} else {
		$pairs[] = array( (float) $product->get_regular_price(), (float) $product->get_sale_price() );
	}

	$best = 0;

	foreach ( $pairs as [ $regular, $sale ] ) {
		if ( $regular > 0 && $sale < $regular ) {
			$best = max( $best, (int) round( ( $regular - $sale ) / $regular * 100 ) );
		}
	}

	return $best;
}
