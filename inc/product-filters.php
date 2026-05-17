<?php
/**
 * Product filter bar — displayed above the product grid on category/shop/search pages.
 * Filters by colour group, finish, and adhesive properties stored in ACF meta fields.
 * Uses URL params so filters are shareable and work with browser back/forward.
 *
 * @package dorotape
 */

// ─── Filter bar display ───────────────────────────────────────────────────────

add_action( 'woocommerce_before_shop_loop', 'dorotape_render_product_filters', 15 );

function dorotape_render_product_filters(): void {
	if ( ! is_product_category() && ! is_shop() && ! is_search() ) {
		return;
	}

	$current_adhesive = sanitize_key( $_GET['filter_adhesive'] ?? '' );
	$current_colour   = sanitize_key( $_GET['filter_colour']   ?? '' );
	$current_finish   = sanitize_key( $_GET['filter_finish']   ?? '' );

	$has_active = $current_adhesive || $current_colour || $current_finish;

	// Base URL without filter params (preserves orderby etc.)
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

			<div class="dt-filter-group">
				<label class="dt-filter-label" for="filter_adhesive">Adhesive</label>
				<select name="filter_adhesive" id="filter_adhesive" class="dt-filter-select<?php echo $current_adhesive ? ' dt-filter-active' : ''; ?>">
					<option value="">All Adhesives</option>
					<option value="standard" <?php selected( $current_adhesive, 'standard' ); ?>>Standard Adhesive</option>
					<option value="airflow"  <?php selected( $current_adhesive, 'airflow' ); ?>>Airflow Adhesive</option>
				</select>
			</div>

			<div class="dt-filter-group">
				<label class="dt-filter-label" for="filter_colour">Colour</label>
				<select name="filter_colour" id="filter_colour" class="dt-filter-select<?php echo $current_colour ? ' dt-filter-active' : ''; ?>">
					<option value="">All Colours</option>
					<option value="white"       <?php selected( $current_colour, 'white' ); ?>>White</option>
					<option value="black"       <?php selected( $current_colour, 'black' ); ?>>Black</option>
					<option value="reds"        <?php selected( $current_colour, 'reds' ); ?>>Reds</option>
					<option value="blues"       <?php selected( $current_colour, 'blues' ); ?>>Blues</option>
					<option value="greens"      <?php selected( $current_colour, 'greens' ); ?>>Greens</option>
					<option value="yellows"     <?php selected( $current_colour, 'yellows' ); ?>>Yellows</option>
					<option value="oranges"     <?php selected( $current_colour, 'oranges' ); ?>>Oranges</option>
					<option value="greys"       <?php selected( $current_colour, 'greys' ); ?>>Greys</option>
					<option value="beiges"      <?php selected( $current_colour, 'beiges' ); ?>>Beiges</option>
					<option value="browns"      <?php selected( $current_colour, 'browns' ); ?>>Browns</option>
					<option value="transparent" <?php selected( $current_colour, 'transparent' ); ?>>Transparent / Clear</option>
					<option value="colours"     <?php selected( $current_colour, 'colours' ); ?>>Other Colours</option>
				</select>
			</div>

			<div class="dt-filter-group">
				<label class="dt-filter-label" for="filter_finish">Finish</label>
				<select name="filter_finish" id="filter_finish" class="dt-filter-select<?php echo $current_finish ? ' dt-filter-active' : ''; ?>">
					<option value="">All Finishes</option>
					<option value="gloss" <?php selected( $current_finish, 'gloss' ); ?>>Gloss</option>
					<option value="matt"  <?php selected( $current_finish, 'matt' ); ?>>Matt</option>
					<option value="satin" <?php selected( $current_finish, 'satin' ); ?>>Satin</option>
					<option value="clear" <?php selected( $current_finish, 'clear' ); ?>>Clear</option>
				</select>
			</div>

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
		// ACF checkbox stores serialised array — search for the value string within it.
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
