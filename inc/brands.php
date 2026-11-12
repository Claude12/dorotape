<?php
declare( strict_types=1 );
/**
 * Brand pages.
 *
 * WooCommerce's own Brands taxonomy (product_brand, /brand/<name>/), filled
 * from the manufacturer the old site recorded on each product, so a brand
 * logo in the strip, the footer or on Manufacturers opens every product of
 * that make (Michael, Site Edits Partners A3, 12 November 2026). Before this
 * the logos ran a text search, which missed most of a range whose product
 * names do not say the maker, and four logos had no link at all.
 *
 * The page itself is the product search page with a brand banner: the
 * dispatcher in inc/search.php takes both. The banner's wording lives on the
 * Search Page options page with the search wording, as `{brand}` and `{n}`
 * tokens, and a brand given a description in Products, Brands shows that
 * instead.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * True on a brand's product list.
 */
function dorotape_is_brand_archive(): bool {
	return is_tax( 'product_brand' );
}

/**
 * The brand page banner.
 */
function dorotape_brand_banner(): void {
	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$count = dorotape_search_found();
	$swap  = static fn( string $text ): string => str_replace(
		array( '{brand}', '{n}' ),
		array( $term->name, number_format_i18n( $count ) ),
		$text
	);

	$intro = trim( wp_strip_all_tags( (string) $term->description ) );

	if ( '' === $intro ) {
		$intro = $swap(
			dorotape_search_field(
				'brand_intro',
				/* translators: {n} is the number of products, {brand} the brand's name. */
				__( '{n} {brand} products in our range.', 'dorotape' )
			)
		);
	}

	dorotape_page_banner(
		array(
			'eyebrow'    => $swap( dorotape_search_field( 'brand_eyebrow', __( 'Our brands', 'dorotape' ) ) ),
			'heading'    => $term->name,
			'intro'      => $intro,
			'shape'      => dorotape_search_field( 'search_banner_shape', 'triangles-right' ),
			'breadcrumb' => true,
			'dot'        => 'magenta',
		)
	);
}

/**
 * The crumb a brand page sits under: the Manufacturers page, where the full
 * set of logos is, or the shop when there is no such page.
 *
 * @return array{label: string, url: string}
 */
function dorotape_brand_parent_crumb(): array {
	$page = get_page_by_path( 'manufacturers' );

	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return array(
			'label' => (string) get_the_title( $page ),
			'url'   => (string) get_permalink( $page ),
		);
	}

	return dorotape_shop_crumb();
}

/**
 * Rank Math's trail for a brand page.
 *
 * Rank Math puts the taxonomy's own name ("Product brands") between Home and
 * the brand, as a crumb with no page behind it. The brand logos live on
 * Manufacturers, so that is the crumb, in the visible trail and in the
 * BreadcrumbList schema alike.
 *
 * @param array $crumbs Rank Math crumbs, each [ label, url, ... ].
 * @return array
 */
function dorotape_brand_rank_math_crumbs( $crumbs ) {
	if ( ! is_array( $crumbs ) || ! dorotape_is_brand_archive() || count( $crumbs ) < 2 ) {
		return $crumbs;
	}

	$parent = dorotape_brand_parent_crumb();

	return array(
		reset( $crumbs ),
		array( $parent['label'], $parent['url'] ),
		end( $crumbs ),
	);
}
add_filter( 'rank_math/frontend/breadcrumb/items', 'dorotape_brand_rank_math_crumbs', 20 );
