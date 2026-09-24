<?php
declare( strict_types=1 );
/**
 * Facets for the shop page.
 *
 * The leaf category pages solve this problem the opposite way round, and the
 * difference is worth stating once here rather than being rediscovered.
 *
 * A leaf category holds at most 93 products, so inc/category-filters.php
 * renders the whole of it and filters it in the browser: no network, instant
 * results, live counts. The shop holds 995. Rendering all of them measured
 * 3.0 seconds of PHP, 2.3MB of HTML and 217MB of memory, on the page the
 * homepage's primary button points at. So the shop filters on the server and
 * paginates, and the panel is a real form that reloads the page.
 *
 * What that buys back is that almost none of it is new code. WooCommerce
 * already reads `filter_<attribute>=slug,slug` off the query string and adds
 * it to the product query itself, in WC_Query::get_tax_query(), with no widget
 * and no configuration. So the attribute facets are presentation only: this
 * file works out which attributes are worth offering and draws them in the
 * same panel markup the leaf pages use, and Woo does the filtering.
 *
 * Two things Woo does not do are here. Filtering by category on the shop page,
 * which is `filter_cat` and is applied in dorotape_shop_filter_query(). And
 * the counts beside each value, which are counted against what the other
 * facets have already narrowed things to, so a number never promises results
 * that ticking it would not produce.
 *
 * Which attributes are offered is a coverage rule rather than a list. The rule
 * the leaf pages use, "anything at least two products disagree about", is
 * right inside one category and useless across the catalogue: applied to all
 * 995 products it returns 24 facets, including Roland Wipers, which three
 * products carry. An attribute earns a place on the shop only if a tenth of
 * the catalogue answers it.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The share of the catalogue an attribute must cover to be offered as a facet.
 *
 * A tenth. Below that the filter is about a corner of the shop rather than the
 * shop, and the customer who wants that corner is already in a category.
 */
const DOROTAPE_SHOP_FACET_SHARE = 0.1;

/**
 * The most values a facet may offer.
 *
 * The same ceiling the leaf panel uses, for the same reason: past about forty
 * checkboxes the list is a worse way to find something than the search box.
 */
const DOROTAPE_SHOP_FACET_MAX = 40;

/**
 * The query string key the category facet reads.
 *
 * Deliberately in Woo's `filter_` namespace so it reads as one of the same
 * family in a URL. Woo's own parser walks every `filter_` key, resolves this
 * one to the attribute "cat", finds no `pa_cat` taxonomy and skips it, so the
 * two never collide.
 */
const DOROTAPE_SHOP_CAT_PARAM = 'filter_cat';

/**
 * How long the facet shortlist is kept.
 *
 * Working out which attributes clear the coverage bar is a query per attribute
 * across 35 of them. The answer changes when the catalogue is restocked, not
 * between two page views, so it is cached and thrown away whenever a product
 * is saved.
 */
const DOROTAPE_SHOP_FACET_CACHE = 'dorotape_shop_facet_taxonomies';

/**
 * Two values in one facet mean either, not both.
 *
 * WooCommerce's layered nav defaults to AND inside a single attribute, so
 * ticking Beiges and Black asked for products that are beige *and* black and
 * returned nothing. Every shop reads a second tick in the same list as
 * widening the results, it is what the Category facet already does here, and
 * it is what the leaf category panel does in the browser; this is the one
 * place the shop disagreed with all three.
 *
 * Set through Woo's own filter rather than by hanging `query_type_*=or` off
 * every link, so the URLs stay short and a hand typed one behaves the same.
 */
add_filter( 'woocommerce_layered_nav_default_query_type', 'dorotape_shop_layered_nav_query_type' );

/**
 * The query type for a facet with more than one value ticked.
 */
function dorotape_shop_layered_nav_query_type(): string {
	return 'or';
}

/**
 * Turn `filter_x[]=a&filter_x[]=b` into the `filter_x=a,b` WooCommerce reads.
 *
 * The panel is a plain form of checkboxes, which is what makes it work with no
 * JavaScript, and a form can only submit repeated keys or an array. Woo's
 * layered nav has always read one comma separated value instead. Rather than
 * give the shop a private parameter format and reimplement the filtering that
 * WC_Query::get_tax_query() already does, the array form is folded into the
 * comma form before anything reads it.
 *
 * On init at priority 5: the main query, and with it Woo's parser, runs much
 * later. Only keys Woo already treats as filters are touched, and only on the
 * front end.
 */
add_action(
	'init',
	function (): void {
		if ( is_admin() ) {
			return;
		}

		foreach ( $_GET as $dt_key => $dt_value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a public filter URL, not acting on it.
			if ( ! is_string( $dt_key ) || 0 !== strpos( $dt_key, 'filter_' ) || ! is_array( $dt_value ) ) {
				continue;
			}

			$_GET[ $dt_key ] = implode( ',', array_map( 'sanitize_title', array_map( 'strval', wp_unslash( $dt_value ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	},
	5
);

/**
 * The attribute taxonomies worth offering as facets, in display order.
 *
 * Ordered by the filter bar's curated list first, so Adhesive Properties,
 * Colour and Finish keep the places a customer has seen them in elsewhere on
 * the site, and everything else follows by how much of the catalogue it
 * covers: the broadest filter is the most likely to be the one being looked
 * for.
 *
 * @return array<int, string> Taxonomy names, e.g. pa_finish.
 */
function dorotape_shop_facet_taxonomies(): array {
	$cached = get_transient( DOROTAPE_SHOP_FACET_CACHE );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	$total = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'"
	);

	if ( $total < 1 ) {
		return array();
	}

	$floor    = (int) ceil( $total * DOROTAPE_SHOP_FACET_SHARE );
	$excluded = dorotape_category_filter_excluded();
	$ranked   = array();

	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		if ( in_array( $attribute->attribute_name, $excluded, true ) ) {
			continue;
		}

		$taxonomy = 'pa_' . $attribute->attribute_name;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counting coverage across every attribute; the result is the transient above.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT tt.term_id ) AS values_used, COUNT( DISTINCT p.ID ) AS products
				 FROM {$wpdb->term_taxonomy} tt
				 INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'
				 WHERE tt.taxonomy = %s",
				$taxonomy
			)
		);

		if ( ! $row ) {
			continue;
		}

		$values   = (int) $row->values_used;
		$products = (int) $row->products;

		if ( $products < $floor || $values < 2 || $values > DOROTAPE_SHOP_FACET_MAX ) {
			continue;
		}

		$ranked[ $taxonomy ] = $products;
	}

	$curated = array_keys( dorotape_filter_bar_attributes() );

	uksort(
		$ranked,
		static function ( string $a, string $b ) use ( $curated, $ranked ): int {
			$rank_a = array_search( str_replace( 'pa_', '', $a ), $curated, true );
			$rank_b = array_search( str_replace( 'pa_', '', $b ), $curated, true );
			$rank_a = false === $rank_a ? PHP_INT_MAX : $rank_a;
			$rank_b = false === $rank_b ? PHP_INT_MAX : $rank_b;

			if ( $rank_a !== $rank_b ) {
				return $rank_a <=> $rank_b;
			}

			// Broadest first, then alphabetically so the order never wobbles
			// between two attributes that happen to cover the same count.
			return $ranked[ $a ] === $ranked[ $b ] ? strcmp( $a, $b ) : $ranked[ $b ] <=> $ranked[ $a ];
		}
	);

	$taxonomies = array_keys( $ranked );

	set_transient( DOROTAPE_SHOP_FACET_CACHE, $taxonomies, DAY_IN_SECONDS );

	return $taxonomies;
}

/**
 * Forget the shortlist when the catalogue changes.
 */
function dorotape_shop_flush_facets(): void {
	delete_transient( DOROTAPE_SHOP_FACET_CACHE );
}

add_action( 'woocommerce_update_product', 'dorotape_shop_flush_facets' );
add_action( 'woocommerce_new_product', 'dorotape_shop_flush_facets' );
add_action( 'woocommerce_product_import_inserted_product_object', 'dorotape_shop_flush_facets' );

/**
 * The top level categories offered in the Category facet.
 *
 * Every one of them, which is what the client asked for. Empty categories are
 * still dropped, since a filter that can only ever return nothing is not a
 * filter.
 *
 * @return array<int, WP_Term>
 */
function dorotape_shop_categories(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * The category slugs the URL is asking for.
 *
 * @return array<int, string>
 */
function dorotape_shop_chosen_categories(): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a public filter URL.
	$raw = isset( $_GET[ DOROTAPE_SHOP_CAT_PARAM ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ DOROTAPE_SHOP_CAT_PARAM ] ) ) : '';

	if ( '' === $raw ) {
		return array();
	}

	return array_values( array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) ) );
}

/**
 * The attribute facets the URL is asking for, as Woo itself reads them.
 *
 * @return array<string, array<string, mixed>>
 */
function dorotape_shop_chosen_attributes(): array {
	return class_exists( 'WC_Query' ) ? WC_Query::get_layered_nav_chosen_attributes() : array();
}

/**
 * True when anything is filtered.
 */
function dorotape_shop_is_filtered(): bool {
	return (bool) dorotape_shop_chosen_categories() || (bool) dorotape_shop_chosen_attributes();
}

/**
 * Apply the category facet to the shop query.
 *
 * The attribute facets need nothing here: WC_Query has already put them into
 * the same query's tax_query by the time this runs. A top level category is
 * filtered with its children included, because someone ticking "Sign & Display
 * Vinyl" means the range, not the handful of products filed directly on the
 * parent term.
 *
 * @param WP_Query $query The product query WooCommerce built.
 */
function dorotape_shop_filter_query( WP_Query $query ): void {
	if ( ! $query->is_main_query() || ! dorotape_is_shop_page() ) {
		return;
	}

	$slugs = dorotape_shop_chosen_categories();

	if ( ! $slugs ) {
		return;
	}

	$tax_query   = (array) $query->get( 'tax_query' );
	$tax_query[] = array(
		'taxonomy'         => 'product_cat',
		'field'            => 'slug',
		'terms'            => $slugs,
		'operator'         => 'IN',
		'include_children' => true,
	);

	$query->set( 'tax_query', $tax_query );
}

add_action( 'woocommerce_product_query', 'dorotape_shop_filter_query' );

/**
 * The products the current filters match, as ids.
 *
 * Used for the counts beside each value, not for rendering: the grid is the
 * paginated main query. Ids only, so this is the cheap half of the query
 * WooCommerce is running anyway.
 *
 * @param string $ignore A taxonomy whose own filter to leave out.
 * @return array<int, int>
 */
function dorotape_shop_matching_ids( string $ignore = '' ): array {
	static $cache = array();

	if ( isset( $cache[ $ignore ] ) ) {
		return $cache[ $ignore ];
	}

	$tax_query = array(
		'relation' => 'AND',
		// The same visibility rule the shop loop applies, so a product hidden
		// from the catalogue is not counted into a facet that would never show
		// it.
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		),
	);

	$categories = dorotape_shop_chosen_categories();

	if ( $categories && 'product_cat' !== $ignore ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $categories,
			'operator'         => 'IN',
			'include_children' => true,
		);
	}

	foreach ( dorotape_shop_chosen_attributes() as $taxonomy => $data ) {
		if ( $taxonomy === $ignore || empty( $data['terms'] ) ) {
			continue;
		}

		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $data['terms'],
			'operator' => 'and' === ( $data['query_type'] ?? 'or' ) ? 'AND' : 'IN',
		);
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	$cache[ $ignore ] = array_map( 'intval', $ids );

	return $cache[ $ignore ];
}

/**
 * Count how many of a set of products carry each value of some taxonomies.
 *
 * One grouped query for every facet at once. The alternative, asking
 * WordPress per term, is a query per checkbox on a panel that has about a
 * hundred of them.
 *
 * @param array<int, int>    $product_ids Products to count within.
 * @param array<int, string> $taxonomies  Taxonomies to count.
 * @return array<string, array<string, array{slug: string, name: string, count: int}>>
 */
function dorotape_shop_term_counts( array $product_ids, array $taxonomies ): array {
	global $wpdb;

	if ( ! $product_ids || ! $taxonomies ) {
		return array();
	}

	// Safe to interpolate: every element came back through intval().
	$ids   = implode( ',', array_map( 'intval', $product_ids ) );
	$slots = implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One grouped count in place of a query per checkbox.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT tt.taxonomy, t.slug, t.name, COUNT( DISTINCT tr.object_id ) AS total
			 FROM {$wpdb->term_relationships} tr
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			 WHERE tr.object_id IN ( {$ids} ) AND tt.taxonomy IN ( {$slots} )
			 GROUP BY tt.taxonomy, t.term_id, t.slug, t.name",
			array_values( $taxonomies )
		)
	);

	$counts = array();

	foreach ( (array) $rows as $row ) {
		$counts[ $row->taxonomy ][ $row->slug ] = array(
			'slug'  => (string) $row->slug,
			'name'  => (string) $row->name,
			'count' => (int) $row->total,
		);
	}

	return $counts;
}

/**
 * How many of a set of products sit under each top level category.
 *
 * Counted through the tree rather than off the term a product is filed on:
 * almost every product is in a leaf category three levels down, so counting
 * the terms as assigned would put a zero beside every heading in the list.
 * Counted per product rather than per relationship for the same reason in
 * reverse, since a product in two ranges of the same parent is one product.
 *
 * @param array<int, int> $product_ids Products to count within.
 * @return array<int, int> Top level term id => products.
 */
function dorotape_shop_category_counts( array $product_ids ): array {
	global $wpdb;

	if ( ! $product_ids ) {
		return array();
	}

	// Safe to interpolate: every element came back through intval().
	$ids = implode( ',', array_map( 'intval', $product_ids ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Rolling leaf categories up to their top level parents.
	$rows = $wpdb->get_results(
		"SELECT tr.object_id, tt.term_id
		 FROM {$wpdb->term_relationships} tr
		 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		 WHERE tt.taxonomy = 'product_cat' AND tr.object_id IN ( {$ids} )"
	);

	$seen   = array();
	$counts = array();

	foreach ( (array) $rows as $row ) {
		$term_id = (int) $row->term_id;
		$top     = dorotape_shop_top_level_ancestor( $term_id );

		if ( ! $top ) {
			continue;
		}

		$key = $top . ':' . (int) $row->object_id;

		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ]     = true;
		$counts[ $top ]   = ( $counts[ $top ] ?? 0 ) + 1;
	}

	return $counts;
}

/**
 * The top of the tree a category sits in.
 *
 * @param int $term_id Any product category.
 * @return int The top level ancestor's id, or 0.
 */
function dorotape_shop_top_level_ancestor( int $term_id ): int {
	static $resolved = array();

	if ( isset( $resolved[ $term_id ] ) ) {
		return $resolved[ $term_id ];
	}

	$ancestors = get_ancestors( $term_id, 'product_cat', 'taxonomy' );
	$top       = $ancestors ? (int) end( $ancestors ) : $term_id;

	$resolved[ $term_id ] = $top;

	return $top;
}

/**
 * The facets to draw, with their values and counts.
 *
 * Counts are taken against what the *other* facets have narrowed things to,
 * never against the facet's own selection. Counting a facet against itself is
 * the classic faceted search trap: tick Gloss and every other finish reads 0,
 * so the list says there is nothing else to see when in fact adding Matt would
 * widen the results.
 *
 * A value with no products left is kept and marked, rather than removed, so
 * the panel does not reflow under the pointer as things are ticked.
 *
 * @return array<int, array<string, mixed>>
 */
function dorotape_shop_facets(): array {
	$taxonomies = dorotape_shop_facet_taxonomies();
	$chosen     = dorotape_shop_chosen_attributes();
	$facets     = array();

	// One count pass for every facet that is not itself filtered, and one more
	// per facet that is. Usually one query; never more than a handful.
	$shared = dorotape_shop_term_counts( dorotape_shop_matching_ids(), $taxonomies );

	foreach ( $taxonomies as $taxonomy ) {
		$selected = isset( $chosen[ $taxonomy ]['terms'] ) ? (array) $chosen[ $taxonomy ]['terms'] : array();
		$counts   = $selected
			? ( dorotape_shop_term_counts( dorotape_shop_matching_ids( $taxonomy ), array( $taxonomy ) )[ $taxonomy ] ?? array() )
			: ( $shared[ $taxonomy ] ?? array() );

		// Every value the attribute has, not only the ones still reachable, so
		// a ticked value never vanishes from under the pointer.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
			)
		);

		if ( ! is_array( $terms ) || count( $terms ) < 2 ) {
			continue;
		}

		$values = array();

		foreach ( $terms as $term ) {
			$count = $counts[ $term->slug ]['count'] ?? 0;
			$on    = in_array( $term->slug, $selected, true );

			// Nothing left and not already ticked: there is no URL this value
			// leads anywhere useful from.
			if ( 0 === $count && ! $on ) {
				continue;
			}

			$values[] = array(
				'slug'     => $term->slug,
				'name'     => $term->name,
				'count'    => $count,
				'selected' => $on,
			);
		}

		if ( count( $values ) < 2 ) {
			continue;
		}

		/*
		 * Numbers in the name first, so 630mm comes before 1260mm rather than
		 * after it, matching the order the variation dropdowns and the leaf
		 * panel already put widths in.
		 */
		usort(
			$values,
			static function ( array $a, array $b ): int {
				$cmp = dorotape_size_sort_key( $a['name'] ) <=> dorotape_size_sort_key( $b['name'] );

				return 0 !== $cmp ? $cmp : strcasecmp( $a['name'], $b['name'] );
			}
		);

		$facets[] = array(
			'taxonomy' => $taxonomy,
			'param'    => 'filter_' . str_replace( 'pa_', '', $taxonomy ),
			'label'    => wc_attribute_label( $taxonomy ),
			'terms'    => $values,
		);
	}

	return $facets;
}

/**
 * The Category facet, in the same shape as the attribute ones.
 *
 * @return array<string, mixed>|null
 */
function dorotape_shop_category_facet(): ?array {
	$categories = dorotape_shop_categories();

	if ( count( $categories ) < 2 ) {
		return null;
	}

	$chosen = dorotape_shop_chosen_categories();

	// Categories are counted against the other facets only, for the reason
	// dorotape_shop_facets() gives about counting a facet against itself.
	$counts = dorotape_shop_category_counts( dorotape_shop_matching_ids( 'product_cat' ) );
	$values = array();

	foreach ( $categories as $term ) {
		$count = (int) ( $counts[ $term->term_id ] ?? 0 );
		$on    = in_array( $term->slug, $chosen, true );

		if ( 0 === $count && ! $on ) {
			continue;
		}

		$values[] = array(
			'slug'     => $term->slug,
			'name'     => $term->name,
			'count'    => $count,
			'selected' => $on,
		);
	}

	if ( count( $values ) < 2 ) {
		return null;
	}

	return array(
		'taxonomy' => 'product_cat',
		'param'    => DOROTAPE_SHOP_CAT_PARAM,
		'label'    => __( 'Category', 'dorotape' ),
		'terms'    => $values,
	);
}

/**
 * Every value the URL is filtering by, with the URL that takes it off again.
 *
 * Read from the query string rather than from the rendered facets. A facet
 * leaves the panel once it is down to a single value, so a customer can be
 * filtered by something the panel no longer draws: built from the facets, the
 * chips would have gone missing at exactly the moment they are the only thing
 * explaining a short grid.
 *
 * @return array<int, array{param:string,facet:string,slug:string,name:string,remove:string}>
 */
function dorotape_shop_active_filters(): array {
	$active = array();

	foreach ( dorotape_shop_chosen_categories() as $dt_slug ) {
		$dt_term = get_term_by( 'slug', $dt_slug, 'product_cat' );

		$active[] = array(
			'param' => DOROTAPE_SHOP_CAT_PARAM,
			'facet' => __( 'Category', 'dorotape' ),
			'slug'  => (string) $dt_slug,
			'name'  => $dt_term instanceof WP_Term ? $dt_term->name : (string) $dt_slug,
		);
	}

	foreach ( dorotape_shop_chosen_attributes() as $dt_taxonomy => $dt_data ) {
		foreach ( (array) ( $dt_data['terms'] ?? array() ) as $dt_slug ) {
			$dt_term = get_term_by( 'slug', (string) $dt_slug, (string) $dt_taxonomy );

			$active[] = array(
				'param' => 'filter_' . str_replace( 'pa_', '', (string) $dt_taxonomy ),
				'facet' => wc_attribute_label( (string) $dt_taxonomy ),
				'slug'  => (string) $dt_slug,
				'name'  => $dt_term instanceof WP_Term ? $dt_term->name : (string) $dt_slug,
			);
		}
	}

	foreach ( $active as $dt_index => $dt_filter ) {
		$active[ $dt_index ]['remove'] = dorotape_shop_remove_url( $dt_filter['param'], $dt_filter['slug'] );
	}

	return $active;
}

/**
 * The shop URL with one value taken out of one facet, the rest left alone.
 *
 * @param string $param The filter_* parameter the value belongs to.
 * @param string $slug  The value to drop.
 */
function dorotape_shop_remove_url( string $param, string $slug ): string {
	if ( DOROTAPE_SHOP_CAT_PARAM === $param ) {
		$values = dorotape_shop_chosen_categories();
	} else {
		$chosen = dorotape_shop_chosen_attributes();
		$values = (array) ( $chosen[ 'pa_' . substr( $param, strlen( 'filter_' ) ) ]['terms'] ?? array() );
	}

	$values = array_values( array_diff( array_map( 'strval', $values ), array( $slug ) ) );

	return dorotape_shop_url( array( $param => $values ? implode( ',', $values ) : null ) );
}

/**
 * A shop URL with some filters changed.
 *
 * Always back to page one: a filter that left you on page seven of a result
 * set that now has three pages is a 404 dressed as a feature.
 *
 * @param array<string, string|null> $changes Parameters to set, or null to drop.
 * @return string
 */
function dorotape_shop_url( array $changes = array() ): string {
	$base = wc_get_page_permalink( 'shop' );

	if ( ! is_string( $base ) || '' === $base ) {
		return home_url( '/' );
	}

	$args = array();

	foreach ( dorotape_shop_chosen_attributes() as $taxonomy => $data ) {
		if ( ! empty( $data['terms'] ) ) {
			$args[ 'filter_' . str_replace( 'pa_', '', $taxonomy ) ] = implode( ',', (array) $data['terms'] );
		}
	}

	$categories = dorotape_shop_chosen_categories();

	if ( $categories ) {
		$args[ DOROTAPE_SHOP_CAT_PARAM ] = implode( ',', $categories );
	}

	$orderby = dorotape_shop_orderby();

	if ( '' !== $orderby ) {
		$args['orderby'] = $orderby;
	}

	foreach ( $changes as $key => $value ) {
		if ( null === $value || '' === $value ) {
			unset( $args[ $key ] );
			continue;
		}

		$args[ $key ] = $value;
	}

	$url = $args ? add_query_arg( array_map( 'rawurlencode', $args ), $base ) : $base;

	// Land on the results rather than the top of the page. The band carries
	// id="products", so this is the no-JavaScript answer to the same problem
	// the leaf pages solve by scrolling.
	return $url . '#products';
}

/**
 * The sort order in play, if it is one WooCommerce offers.
 */
function dorotape_shop_orderby(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a public sort URL.
	$raw = isset( $_GET['orderby'] ) ? sanitize_title( wp_unslash( (string) $_GET['orderby'] ) ) : '';

	return array_key_exists( $raw, dorotape_shop_orderby_options() ) ? $raw : '';
}

/**
 * The sort orders offered, which are WooCommerce's own.
 *
 * @return array<string, string>
 */
function dorotape_shop_orderby_options(): array {
	$options = (array) apply_filters(
		'woocommerce_catalog_orderby',
		array(
			'menu_order' => __( 'Default sorting', 'dorotape' ),
			'popularity' => __( 'Most popular', 'dorotape' ),
			'date'       => __( 'Newest first', 'dorotape' ),
			'price'      => __( 'Price: low to high', 'dorotape' ),
			'price-desc' => __( 'Price: high to low', 'dorotape' ),
		)
	);

	// Ratings are not collected on this site, so the option would sort by a
	// column that is zero for every product.
	unset( $options['rating'] );

	return array_map( 'strval', $options );
}
