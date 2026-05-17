<?php
/**
 * Product filter bar — displayed below the sort-by control on leaf category pages.
 * Only shown on pages that render products directly (not subcategory landing pages).
 * Dropdown options are built dynamically from meta values that exist in the current
 * category so empty options never appear.
 *
 * @package dorotape
 */

// ─── Filter bar display ───────────────────────────────────────────────────────

// Priority 35 — fires after result count (20) and sort-by (30).
add_action( 'woocommerce_before_shop_loop', 'dorotape_render_product_filters', 35 );

function dorotape_render_product_filters(): void {
	// Only on product category pages.
	if ( ! is_product_category() ) {
		return;
	}

	// Skip categories that show subcategories rather than products.
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	$children = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $term->term_id,
		'hide_empty' => true,
		'fields'     => 'ids',
	) );
	if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
		return;
	}

	$cat_id = (int) $term->term_id;

	// Build dynamic options — only values that exist for products in this category.
	$colour_options   = dorotape_filter_meta_options( 'colour_group', $cat_id );
	$finish_options   = dorotape_filter_meta_options( 'finish', $cat_id );
	$adhesive_options = dorotape_filter_adhesive_options( $cat_id );

	// Nothing to filter — hide the bar entirely.
	if ( empty( $colour_options ) && empty( $finish_options ) && count( $adhesive_options ) < 2 ) {
		return;
	}

	$current_adhesive = sanitize_key( $_GET['filter_adhesive'] ?? '' );
	$current_colour   = sanitize_key( $_GET['filter_colour']   ?? '' );
	$current_finish   = sanitize_key( $_GET['filter_finish']   ?? '' );
	$has_active       = $current_adhesive || $current_colour || $current_finish;

	// Preserve non-filter URL params (e.g. orderby) when clearing.
	$base_params = array_filter( $_GET, function ( $key ) {
		return ! in_array( $key, array( 'filter_adhesive', 'filter_colour', 'filter_finish' ), true );
	}, ARRAY_FILTER_USE_KEY );
	$clear_url = add_query_arg( $base_params, strtok( $_SERVER['REQUEST_URI'], '?' ) );

	?>
	<div class="dt-filter-bar">
		<form class="dt-filter-form" method="get" action="">
			<?php foreach ( $base_params as $key => $val ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>">
			<?php endforeach; ?>

			<?php if ( count( $adhesive_options ) >= 2 ) : ?>
			<div class="dt-filter-group">
				<label class="dt-filter-label" for="filter_adhesive">Adhesive</label>
				<select name="filter_adhesive" id="filter_adhesive" class="dt-filter-select<?php echo $current_adhesive ? ' dt-filter-active' : ''; ?>">
					<option value="">All Adhesives</option>
					<?php
					$adhesive_labels = array( 'standard' => 'Standard Adhesive', 'airflow' => 'Airflow Adhesive' );
					foreach ( $adhesive_options as $val ) :
					?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_adhesive, $val ); ?>>
						<?php echo esc_html( $adhesive_labels[ $val ] ?? ucfirst( $val ) ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $colour_options ) ) : ?>
			<div class="dt-filter-group">
				<label class="dt-filter-label" for="filter_colour">Colour</label>
				<select name="filter_colour" id="filter_colour" class="dt-filter-select<?php echo $current_colour ? ' dt-filter-active' : ''; ?>">
					<option value="">All Colours</option>
					<?php
					$colour_labels = array(
						'white'       => 'White',       'black'   => 'Black',
						'reds'        => 'Reds',        'blues'   => 'Blues',
						'greens'      => 'Greens',      'yellows' => 'Yellows',
						'oranges'     => 'Oranges',     'greys'   => 'Greys',
						'beiges'      => 'Beiges',      'browns'  => 'Browns',
						'transparent' => 'Transparent / Clear',
						'colours'     => 'Other Colours',
					);
					// Sort by natural label order defined above.
					$ordered = array_intersect( array_keys( $colour_labels ), $colour_options );
					foreach ( $ordered as $val ) :
					?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_colour, $val ); ?>>
						<?php echo esc_html( $colour_labels[ $val ] ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $finish_options ) ) : ?>
			<div class="dt-filter-group">
				<label class="dt-filter-label" for="filter_finish">Finish</label>
				<select name="filter_finish" id="filter_finish" class="dt-filter-select<?php echo $current_finish ? ' dt-filter-active' : ''; ?>">
					<option value="">All Finishes</option>
					<?php
					$finish_labels = array( 'gloss' => 'Gloss', 'matt' => 'Matt', 'satin' => 'Satin', 'clear' => 'Clear' );
					$ordered       = array_intersect( array_keys( $finish_labels ), $finish_options );
					foreach ( $ordered as $val ) :
					?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_finish, $val ); ?>>
						<?php echo esc_html( $finish_labels[ $val ] ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<div class="dt-filter-actions">
				<button type="submit" class="dt-filter-btn">Filter</button>
				<?php if ( $has_active ) : ?>
				<a href="<?php echo esc_url( $clear_url ); ?>" class="dt-filter-clear">Clear filters</a>
				<?php endif; ?>
			</div>
		</form>
	</div>
	<?php
}

// ─── Dynamic option helpers ───────────────────────────────────────────────────

/**
 * Return distinct non-empty values for a simple string meta key, scoped to
 * all products in $cat_id and its descendant categories.
 *
 * @return string[]
 */
function dorotape_filter_meta_options( string $meta_key, int $cat_id ): array {
	global $wpdb;

	$cat_ids     = array_merge( array( $cat_id ), get_term_children( $cat_id, 'product_cat' ) ?: array() );
	$ids_escaped = implode( ',', array_map( 'intval', $cat_ids ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT pm.meta_value
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p  ON p.ID  = pm.post_id
		INNER JOIN {$wpdb->term_relationships} tr  ON tr.object_id         = pm.post_id
		INNER JOIN {$wpdb->term_taxonomy}      tt  ON tt.term_taxonomy_id  = tr.term_taxonomy_id
		WHERE p.post_type   = 'product'
		AND   p.post_status = 'publish'
		AND   pm.meta_key   = %s
		AND   pm.meta_value != ''
		AND   tt.taxonomy   = 'product_cat'
		AND   tt.term_id    IN ($ids_escaped)",
		$meta_key
	) );
}

/**
 * Return which adhesive option slugs ('standard', 'airflow') have at least one
 * product in the given category. ACF stores checkbox values as a serialised array.
 *
 * @return string[]
 */
function dorotape_filter_adhesive_options( int $cat_id ): array {
	global $wpdb;

	$cat_ids     = array_merge( array( $cat_id ), get_term_children( $cat_id, 'product_cat' ) ?: array() );
	$ids_escaped = implode( ',', array_map( 'intval', $cat_ids ) );

	$found = array();
	foreach ( array( 'standard', 'airflow' ) as $value ) {
		$like = '%"' . $value . '"%';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT pm.post_id)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p  ON p.ID  = pm.post_id
			INNER JOIN {$wpdb->term_relationships} tr  ON tr.object_id         = pm.post_id
			INNER JOIN {$wpdb->term_taxonomy}      tt  ON tt.term_taxonomy_id  = tr.term_taxonomy_id
			WHERE p.post_type   = 'product'
			AND   p.post_status = 'publish'
			AND   pm.meta_key   = 'adhesive_properties'
			AND   pm.meta_value LIKE %s
			AND   tt.taxonomy   = 'product_cat'
			AND   tt.term_id    IN ($ids_escaped)",
			$like
		) );

		if ( $count > 0 ) {
			$found[] = $value;
		}
	}

	return $found;
}

// ─── Query hook ───────────────────────────────────────────────────────────────

add_action( 'woocommerce_product_query', 'dorotape_apply_product_filters' );

function dorotape_apply_product_filters( WP_Query $query ): void {
	$adhesive = sanitize_key( $_GET['filter_adhesive'] ?? '' );
	$colour   = sanitize_key( $_GET['filter_colour']   ?? '' );
	$finish   = sanitize_key( $_GET['filter_finish']   ?? '' );

	if ( ! $adhesive && ! $colour && ! $finish ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );

	if ( $adhesive ) {
		$meta_query[] = array(
			'key'     => 'adhesive_properties',
			'value'   => '"' . $adhesive . '"',
			'compare' => 'LIKE',
		);
	}
	if ( $colour ) {
		$meta_query[] = array(
			'key'     => 'colour_group',
			'value'   => $colour,
			'compare' => '=',
		);
	}
	if ( $finish ) {
		$meta_query[] = array(
			'key'     => 'finish',
			'value'   => $finish,
			'compare' => '=',
		);
	}

	$query->set( 'meta_query', $meta_query );
}
