<?php
declare( strict_types=1 );
/**
 * The shop page.
 *
 * /shop/ is where the homepage's primary button, both product rows' "View all"
 * links and two block CTAs land, and until now it was raw WooCommerce: Woo's
 * own wrapper, Woo's unstyled breadcrumb and a list of category tiles.
 *
 * There is no shop design, so nothing here is invented. The banner is the
 * internal banner every other internal page opens with, the band is the
 * category grid block the ranges and the leaf shops already sit in, the panel
 * is the leaf category's filter panel and the cards are dorotape_product_card().
 * The only thing written for this page is which of them to show, which is the
 * same rule inc/search.php was built under.
 *
 * It lists products rather than categories. The categories are the main
 * navigation and the homepage's own grid, so a third copy of them would have
 * been the one page on the site that cannot answer "show me everything".
 *
 * Filtering and paginating happen on the server; inc/shop-filters.php says
 * why, and why the leaf pages are right to do the opposite.
 *
 * Hooks only, no woocommerce/ template overrides, for the reason
 * inc/product-category.php gives.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also what the field group's location rule
 * matches on.
 */
const DOROTAPE_SHOP_SECTIONS_SLUG = 'dorotape-shop-page';

/**
 * The ACF post id the shop page's wording and sections are stored under.
 */
const DOROTAPE_SHOP_SECTIONS_ID = 'dorotape_shop_page';

/**
 * Register the options page under Theme Settings.
 *
 * The fifth store, built exactly like the product, category, search and error
 * ones: in PHP so it travels with the theme, and autoloaded because ACF writes
 * a wp_options row per subfield.
 *
 * The shop is a real WooCommerce Page, so there is a Pages entry for it, but
 * its content is never rendered: Woo replaces it with the archive. Putting the
 * wording here rather than there means an editor changes it where it takes
 * effect instead of in a box that does nothing.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Shop Page', 'dorotape' ),
				'menu_title'      => __( 'Shop Page', 'dorotape' ),
				'menu_slug'       => DOROTAPE_SHOP_SECTIONS_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_SHOP_SECTIONS_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save shop page', 'dorotape' ),
				'updated_message' => __( 'Shop page saved.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a text field from the Shop Page options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_shop_field( string $name, string $default = '' ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_SHOP_SECTIONS_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * True on the shop page itself.
 *
 * Not is_shop() on its own. WooCommerce claims `?s=…&post_type=product` and
 * renders it through the same archive template, so is_shop() is also true on
 * the product half of search, which inc/search.php has already painted.
 */
function dorotape_is_shop_page(): bool {
	return function_exists( 'is_shop' ) && is_shop() && ! is_search();
}

/**
 * Put the result count into an editor's wording.
 *
 * The same `{n}` convention the search page and the filter panel already use.
 * A token rather than a sprintf placeholder so a client editing the field
 * cannot break the page by dropping a `%s`.
 *
 * @param string $text  Wording from the options page.
 * @param int    $count Products found.
 */
function dorotape_shop_tokens( string $text, int $count ): string {
	return str_replace( '{n}', number_format_i18n( $count ), $text );
}

/**
 * How many products the current filters found, across every page of them.
 */
function dorotape_shop_found(): int {
	global $wp_query;

	return $wp_query instanceof WP_Query ? (int) $wp_query->found_posts : 0;
}

/**
 * The banner.
 *
 * inc/blocks/internal-banner-block.php's markup with the fields filled from
 * the options page instead of from a block, and no backdrop image, which is
 * how the search page does it too.
 */
function dorotape_shop_banner(): void {
	$count   = dorotape_shop_found();
	$eyebrow = dorotape_shop_field( 'shop_eyebrow', __( 'Shop', 'dorotape' ) );
	$heading = dorotape_shop_field( 'shop_heading', __( 'Every material we stock', 'dorotape' ) );
	$intro   = dorotape_shop_tokens(
		dorotape_shop_field(
			'shop_intro',
			/* translators: {n} is replaced with the number of products. */
			__( '{n} products across signage, print, garment decoration and the tools that go with them. Filter by category, colour, finish or width to narrow it down.', 'dorotape' )
		),
		$count
	);

	$shape   = dorotape_background_shape_value( dorotape_shop_field( 'shop_banner_shape', 'cubes-right' ) );
	$classes = 'internal-banner-block internal-banner-block--overlap' . dorotape_background_shape_class( $shape );
	?>
	<section class="<?php echo esc_attr( $classes ); ?>">
		<?php dorotape_background_shape( $shape ); ?>

		<div class="container">
			<div class="internal-banner-block__inner">

				<?php if ( '' !== $eyebrow ) : ?>
					<p class="internal-banner-block__eyebrow">
						<span class="internal-banner-block__eyebrow-dot" aria-hidden="true"></span>
						<?php echo esc_html( $eyebrow ); ?>
					</p>
				<?php endif; ?>

				<h1 class="internal-banner-block__heading"><?php echo esc_html( $heading ); ?></h1>

				<?php if ( '' !== $intro ) : ?>
					<p class="internal-banner-block__intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>

				<?php dorotape_breadcrumb_nav( 'internal-banner-block__breadcrumb' ); ?>

			</div>
		</div>
	</section>
	<?php
}

/**
 * The classes on the results band.
 *
 * The same shell as the ranges grid, the leaf shop and the search results,
 * with the same soft glow.
 *
 * Tight, like the search page and unlike a category page. The band's top
 * padding is sized for a page that puts something between it and the banner,
 * which a category does: a hairline and usually an intro. The shop puts
 * nothing there, so the full step left the heading floating a long way under
 * the breadcrumb with empty navy in between.
 */
function dorotape_shop_band_classes(): string {
	$shape = dorotape_background_shape_value( dorotape_shop_field( 'shop_results_shape', 'cubes-right' ) );

	return 'category-grid-block category-grid-block--glow-soft category-grid-block--shop category-grid-block--tight' . dorotape_background_shape_class( $shape );
}

/**
 * The band's background shape, for callers that have opened the section
 * themselves.
 */
function dorotape_shop_band_shape(): void {
	dorotape_background_shape( dorotape_background_shape_value( dorotape_shop_field( 'shop_results_shape', 'cubes-right' ) ) );
}

/**
 * The band header.
 *
 * Carries data-filter-top for the same reason the leaf one does: it is where
 * a customer is put back when a filter changes the grid under them.
 */
function dorotape_shop_band_header(): void {
	$heading = dorotape_shop_field( 'shop_products_heading', __( 'All products', 'dorotape' ) );

	if ( '' === $heading ) {
		return;
	}
	?>
	<div class="category-grid-block__header" data-filter-top>
		<h2 class="category-grid-block__heading"><?php echo esc_html( $heading ); ?></h2>
	</div>
	<?php
}

/**
 * The toolbar above the grid: what was found, and how to sort it.
 *
 * The filter bar component the category archives already use, so the sort
 * control on the shop is the same control in the same box as the one on a
 * category. It is a form of its own rather than part of the panel because it
 * has to keep working when the panel is closed on a phone.
 */
function dorotape_shop_toolbar(): void {
	$count   = dorotape_shop_found();
	$orderby = dorotape_shop_orderby();
	$options = dorotape_shop_orderby_options();
	?>
	<form class="dt-filter-bar" data-shop-sort method="get" action="<?php echo esc_url( (string) wc_get_page_permalink( 'shop' ) ); ?>">
		<span class="dt-filter-bar__title">
			<?php
			printf(
				/* translators: %s: number of products. */
				esc_html( _n( '%s product', '%s products', $count, 'dorotape' ) ),
				esc_html( number_format_i18n( $count ) )
			);
			?>
		</span>

		<?php
		/*
		 * The filters already in play, carried through the sort so changing
		 * the order does not quietly clear them.
		 */
		?>
		<?php foreach ( dorotape_shop_carried_filters() as $dt_name => $dt_value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $dt_name ); ?>" value="<?php echo esc_attr( $dt_value ); ?>">
		<?php endforeach; ?>

		<label class="dt-filter-bar__field">
			<span class="dt-filter-bar__label"><?php esc_html_e( 'Sort by', 'dorotape' ); ?></span>
			<select class="dt-filter-bar__select" name="orderby">
				<?php foreach ( $options as $dt_key => $dt_label ) : ?>
					<option value="<?php echo esc_attr( $dt_key ); ?>" <?php selected( $orderby, $dt_key ); ?>>
						<?php echo esc_html( $dt_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<button type="submit" class="dt-filter-bar__submit btn btn--sm btn--outline"><?php esc_html_e( 'Apply', 'dorotape' ); ?></button>

		<?php if ( dorotape_shop_is_filtered() ) : ?>
			<a class="dt-filter-bar__clear" href="<?php echo esc_url( dorotape_shop_clear_url() ); ?>">
				<?php esc_html_e( 'Clear filters', 'dorotape' ); ?>
			</a>
		<?php endif; ?>
	</form>
	<?php
}

/**
 * The active filters, as form fields one form can hand to another.
 *
 * @return array<string, string>
 */
function dorotape_shop_carried_filters(): array {
	$carried = array();

	foreach ( dorotape_shop_chosen_attributes() as $taxonomy => $data ) {
		if ( ! empty( $data['terms'] ) ) {
			$carried[ 'filter_' . str_replace( 'pa_', '', $taxonomy ) ] = implode( ',', (array) $data['terms'] );
		}
	}

	$categories = dorotape_shop_chosen_categories();

	if ( $categories ) {
		$carried[ DOROTAPE_SHOP_CAT_PARAM ] = implode( ',', $categories );
	}

	return $carried;
}

/**
 * The shop with every filter dropped, but the sort order kept.
 */
function dorotape_shop_clear_url(): string {
	$base    = (string) wc_get_page_permalink( 'shop' );
	$orderby = dorotape_shop_orderby();
	$url     = '' !== $orderby ? add_query_arg( 'orderby', rawurlencode( $orderby ), $base ) : $base;

	return $url . '#products';
}

/**
 * Does this facet hold a ticked value?
 *
 * @param array{terms:array<int, array<string, mixed>>} $facet One facet.
 */
function dorotape_shop_facet_has_selection( array $facet ): bool {
	foreach ( (array) ( $facet['terms'] ?? array() ) as $dt_term ) {
		if ( ! empty( $dt_term['selected'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * The strip of what is currently filtered, at the top of the panel.
 *
 * Each chip is a link to the same page without that one value, so taking a
 * filter off never means hunting for the box it came from: with the facets
 * shut by default, that box may be three collapsed headings away, and on the
 * empty state it may be a facet the panel has stopped drawing at all.
 */
function dorotape_shop_active_chips(): void {
	$active = dorotape_shop_active_filters();

	if ( ! $active ) {
		return;
	}
	?>
	<div class="category-filters__active">
		<p class="category-filters__active-label"><?php esc_html_e( 'Filtering by', 'dorotape' ); ?></p>

		<ul class="category-filters__chips">
			<?php foreach ( $active as $dt_filter ) : ?>
				<li>
					<a class="category-filters__chip" href="<?php echo esc_url( $dt_filter['remove'] ); ?>">
						<span class="category-filters__chip-label"><?php echo esc_html( $dt_filter['name'] ); ?></span>
						<?php echo dorotape_ui_icon( 'x', 'category-filters__chip-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
						<span class="screen-reader-text">
							<?php
							printf(
								/* translators: 1: facet name, such as Finish. 2: value name, such as Gloss. */
								esc_html__( 'Remove the %1$s filter %2$s', 'dorotape' ),
								esc_html( $dt_filter['facet'] ),
								esc_html( $dt_filter['name'] )
							);
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * The filter panel.
 *
 * The leaf category panel's markup and classes, so the two pages are the same
 * component, with three differences that follow from filtering on the server.
 *
 * The inputs are named, because this form is submitted rather than read by a
 * script. There is no search box: the leaf panel has one because that page
 * shows a whole category at once and nothing else could find an item number
 * inside it, whereas searching the whole catalogue is what the header's box
 * already does, and a second one here would be the same search with a worse
 * name. And there is no in stock switch, because 994 of the 995 products are
 * in stock and a control that hides one product is a control that looks broken.
 *
 * It ships open. Below the two column breakpoint the panel is a drawer, and a
 * drawer with no script to open it is a panel a customer cannot reach, so the
 * script closes it on load rather than the markup shipping it closed.
 */
function dorotape_shop_panel(): void {
	$facets = dorotape_shop_panel_facets();

	/*
	 * Nothing to offer and nothing to undo. A combination that matches nothing
	 * is the second of those, not the first: it leaves no facet with two
	 * values to draw, and returning here took the chips and the Clear link
	 * away with them, which is the one screen that cannot afford to lose
	 * either. So it stays open for as long as a filter is on.
	 */
	if ( ! $facets && ! dorotape_shop_is_filtered() ) {
		return;
	}

	$count = dorotape_shop_found();
	?>
	<aside class="category-filters is-open" data-shop-filters>
		<form class="category-filters__panel" id="dt-shop-filters" method="get" action="<?php echo esc_url( (string) wc_get_page_permalink( 'shop' ) ); ?>">

			<?php
			/*
			 * The sort order rides along as a hidden field for the same reason
			 * the toolbar carries the filters: neither control should silently
			 * undo the other.
			 */
			?>
			<?php if ( '' !== dorotape_shop_orderby() ) : ?>
				<input type="hidden" name="orderby" value="<?php echo esc_attr( dorotape_shop_orderby() ); ?>">
			<?php endif; ?>

			<?php dorotape_shop_active_chips(); ?>

			<?php foreach ( $facets as $dt_index => $dt_facet ) : ?>
				<?php
				$dt_pills = count( $dt_facet['terms'] ) <= 4;
				/*
				 * Seven facets unfolded is four thousand pixels of checkboxes,
				 * so they ship shut. The first one is open because a panel of
				 * nothing but headings does not read as a filter, and any facet
				 * holding a selection is open because the value that is doing
				 * the filtering should be visible where it was chosen.
				 */
				$dt_open = 0 === $dt_index || dorotape_shop_facet_has_selection( $dt_facet );
				?>
				<details class="category-filters__group category-filters__group--fold"<?php echo $dt_open ? ' open' : ''; ?>>
					<summary class="category-filters__legend category-filters__summary">
						<span><?php echo esc_html( $dt_facet['label'] ); ?></span>
						<?php echo dorotape_ui_icon( 'chevron-down', 'category-filters__chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
					</summary>

					<?php if ( $dt_pills ) : ?>
						<div class="category-filters__pills">
							<?php foreach ( $dt_facet['terms'] as $dt_term ) : ?>
								<label class="category-filters__pill">
									<input
										type="checkbox"
										class="category-filters__pill-input"
										name="<?php echo esc_attr( $dt_facet['param'] ); ?>[]"
										value="<?php echo esc_attr( $dt_term['slug'] ); ?>"
										<?php checked( $dt_term['selected'] ); ?>
									>
									<span class="category-filters__pill-label"><?php echo esc_html( $dt_term['name'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<ul class="category-filters__list">
							<?php foreach ( $dt_facet['terms'] as $dt_term ) : ?>
								<li>
									<label class="category-filters__check<?php echo 0 === $dt_term['count'] ? ' is-empty' : ''; ?>">
										<input
											type="checkbox"
											class="category-filters__checkbox"
											name="<?php echo esc_attr( $dt_facet['param'] ); ?>[]"
											value="<?php echo esc_attr( $dt_term['slug'] ); ?>"
											<?php checked( $dt_term['selected'] ); ?>
										>
										<span class="category-filters__check-label"><?php echo esc_html( $dt_term['name'] ); ?></span>
										<?php
										/*
										 * Counted against the other facets only,
										 * so the number beside a value is always
										 * what ticking it would actually leave
										 * on screen. See dorotape_shop_facets().
										 */
										?>
										<span class="category-filters__count"><?php echo esc_html( number_format_i18n( $dt_term['count'] ) ); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</details>
			<?php endforeach; ?>

			<div class="category-filters__footer">
				<p class="category-filters__result">
					<?php
					printf(
						/* translators: %s: number of products. */
						esc_html( _n( '%s product', '%s products', $count, 'dorotape' ) ),
						esc_html( number_format_i18n( $count ) )
					);
					?>
				</p>

				<?php if ( dorotape_shop_is_filtered() ) : ?>
					<a class="category-filters__clear" href="<?php echo esc_url( dorotape_shop_clear_url() ); ?>">
						<?php echo dorotape_ui_icon( 'x', 'category-filters__clear-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
						<?php esc_html_e( 'Clear filters', 'dorotape' ); ?>
					</a>
				<?php endif; ?>
				<?php
				/*
				 * The way to apply what has been ticked without a script, and
				 * the way to apply it inside the drawer, where submitting on
				 * every tick would shut the panel the customer is still using.
				 * It lives in the footer so that it pins to the bottom of the
				 * panel's scroll area with the count and the Clear link, rather
				 * than sitting a hundred checkboxes below them.
				 */
				?>
				<?php if ( $facets ) : ?>
					<button type="submit" class="category-filters__apply btn btn--sm">
						<?php esc_html_e( 'Apply filters', 'dorotape' ); ?>
					</button>
				<?php endif; ?>
			</div>

		</form>
	</aside>
	<?php
}

/**
 * Every facet the panel draws, Category first.
 *
 * Cached for the request because the wrapper has to ask whether there is a
 * panel before it can decide how many columns the shop has, and the answer
 * costs a handful of queries.
 *
 * @return array<int, array<string, mixed>>
 */
function dorotape_shop_panel_facets(): array {
	static $facets = null;

	if ( null !== $facets ) {
		return $facets;
	}

	$facets   = dorotape_shop_facets();
	$category = dorotape_shop_category_facet();

	if ( $category ) {
		array_unshift( $facets, $category );
	}

	return $facets;
}

/**
 * Open the shop's columns, with the drawer handle and the panel.
 *
 * Shared by the band that has products in it and the one that does not, so a
 * filter combination that matched nothing keeps the controls that caused it.
 * Without a panel there is no column for one, which is the same rule the leaf
 * shop applies when a category has no attributes to filter on.
 */
function dorotape_shop_columns_start(): void {
	$active = count( dorotape_shop_chosen_categories() );

	foreach ( dorotape_shop_chosen_attributes() as $dt_data ) {
		$active += count( (array) ( $dt_data['terms'] ?? array() ) );
	}

	/*
	 * Facets, or failing that something to undo. A combination that matches
	 * nothing leaves no facet with two values to offer, so the panel used to
	 * disappear at exactly the moment it was needed: an empty grid, no word
	 * about what had been filtered, and no way back but the browser's own
	 * button. The chips are inside the panel, so the panel has to stay.
	 */
	$has_panel = (bool) dorotape_shop_panel_facets() || $active > 0;
	?>
	<div class="category-shop<?php echo $has_panel ? '' : ' category-shop--plain'; ?>">
		<?php if ( $has_panel ) : ?>
			<?php
			/*
			 * The drawer handle, below the two column breakpoint. It ships
			 * hidden because without a script there is nothing to open: the
			 * panel is already on the page and already open.
			 */
			?>
			<button type="button" class="category-shop__toggle btn btn--sm btn--outline" data-shop-filter-toggle aria-expanded="true" aria-controls="dt-shop-filters" hidden>
				<?php esc_html_e( 'Filters', 'dorotape' ); ?>
				<span class="category-shop__toggle-count" data-shop-filter-active <?php echo $active ? '' : 'hidden'; ?>><?php echo esc_html( (string) $active ); ?></span>
			</button>

			<?php dorotape_shop_panel(); ?>
		<?php endif; ?>
	<?php
}

/**
 * True when an editor has put sections on the shop page's options page.
 */
function dorotape_has_shop_sections(): bool {
	return dorotape_has_flexible_content( DOROTAPE_SHOP_SECTIONS_ID );
}

/**
 * The shared tail, after the results.
 *
 * Starts the block counter at 1 for the same reason the category and search
 * tails do: the banner above is the block above the fold.
 */
function dorotape_render_shop_sections(): void {
	if ( ! dorotape_has_shop_sections() ) {
		return;
	}

	dorotape_render_flexible_content( DOROTAPE_SHOP_SECTIONS_ID, 1 );
}

// ─────────────────────────────────────────────────────────────────────────────
// The page, assembled on WooCommerce's own archive template.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Put the shop page together.
 *
 * On template_redirect so the hooks only exist on this page, and at the same
 * priority the category and search dispatchers use.
 */
add_action(
	'template_redirect',
	function (): void {
		if ( ! dorotape_is_shop_page() ) {
			return;
		}

		// Woo's own chrome. The breadcrumb is drawn in the banner, the count
		// and the sort are in the toolbar, and the layered nav panel below the
		// grid is replaced by the filter panel beside it.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_before_shop_loop', 'dorotape_render_filter_bar', 15 );

		/*
		 * Woo prints its notices on this hook at 10, which is after the band
		 * and the two columns have opened at 5. The wrapper it prints is a
		 * <div>, so it became a third item in a two column grid and took the
		 * products' track, leaving the cards 78px wide. It is printed by hand
		 * inside the band instead, above the toolbar where a "added to basket"
		 * message belongs.
		 */
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
		remove_action( 'woocommerce_no_products_found', 'dorotape_render_filter_bar', 5 );
		remove_action( 'woocommerce_sidebar', 'dorotape_woocommerce_sidebar', 10 );

		// The theme's own wrapper, so the blocks in the tail sit in the
		// container they were written for.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', 'dorotape_category_wrapper_start', 10 );
		add_action( 'woocommerce_after_main_content', 'dorotape_category_wrapper_end', 90 );

		add_action( 'woocommerce_before_main_content', 'dorotape_shop_banner', 20 );

		// The band around the loop. Opened before it; the two column shop
		// closes at priority 5 of the after hook so that Woo's pagination, at
		// 10, lands under both columns rather than in the panel's one.
		add_action( 'woocommerce_before_shop_loop', 'dorotape_shop_loop_before', 5 );
		add_action( 'woocommerce_after_shop_loop', 'dorotape_shop_columns_end', 5 );
		add_action( 'woocommerce_after_shop_loop', 'dorotape_shop_loop_after', 90 );

		// The grid itself, in place of Woo's <ul class="products">.
		add_filter( 'woocommerce_product_loop_start', 'dorotape_shop_loop_start' );
		add_filter( 'woocommerce_product_loop_end', 'dorotape_shop_loop_end' );
		add_filter( 'woocommerce_post_class', 'dorotape_search_post_class', 10, 2 );

		// One card per product, ours, in place of Woo's stack of loop partials.
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		add_action( 'woocommerce_before_shop_loop_item', 'dorotape_search_product_card', 10 );

		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
		add_action( 'woocommerce_no_products_found', 'dorotape_shop_empty', 10 );

		add_action( 'woocommerce_after_main_content', 'dorotape_render_shop_sections', 10 );
	},
	20
);

/**
 * Open the band, the toolbar and the two columns.
 */
function dorotape_shop_loop_before(): void {
	?>
	<section id="products" class="<?php echo esc_attr( dorotape_shop_band_classes() ); ?>" animate="fade-in-up" animate-offset="0">
		<?php dorotape_shop_band_shape(); ?>
		<div class="container">
			<?php woocommerce_output_all_notices(); ?>
			<?php dorotape_shop_band_header(); ?>
			<?php dorotape_shop_toolbar(); ?>
			<?php dorotape_shop_columns_start(); ?>
	<?php
}

/**
 * Close the two columns, before the pagination.
 */
function dorotape_shop_columns_end(): void {
	echo '</div>';
}

/**
 * Close the band.
 */
function dorotape_shop_loop_after(): void {
	echo '</div></section>';
}

/**
 * The grid, in place of Woo's products list.
 *
 * Three columns rather than the search page's four, because the panel has
 * taken the first 280px of the row.
 *
 * @param string $loop_html The <ul> that would have opened the list.
 */
function dorotape_shop_loop_start( string $loop_html ): string {
	remove_filter( 'woocommerce_product_loop_start', 'dorotape_shop_loop_start' );

	return '<div class="category-shop__products"><ul class="category-shop__grid">';
}

/**
 * Close it.
 *
 * @param string $loop_html The </ul> that would have closed the list.
 */
function dorotape_shop_loop_end( string $loop_html ): string {
	remove_filter( 'woocommerce_product_loop_end', 'dorotape_shop_loop_end' );

	return '</ul></div>';
}

/**
 * The band shown when a filter combination matches nothing.
 *
 * The panel is drawn here too, and it is the point of the screen: a dead end
 * that does not carry the controls that caused it leaves the back button as
 * the only way out.
 */
function dorotape_shop_empty(): void {
	$message = dorotape_shop_field(
		'shop_empty',
		__( 'No products match those filters. Try clearing one of them, or call us on 01858 431642 and we will point you to the right material.', 'dorotape' )
	);
	?>
	<section id="products" class="<?php echo esc_attr( dorotape_shop_band_classes() ); ?>" animate="fade-in-up">
		<?php dorotape_shop_band_shape(); ?>
		<div class="container">
			<?php woocommerce_output_all_notices(); ?>
			<?php dorotape_shop_band_header(); ?>
			<?php dorotape_shop_toolbar(); ?>
			<?php dorotape_shop_columns_start(); ?>
				<div class="category-shop__products">
					<p class="category-shop__empty"><?php echo esc_html( $message ); ?></p>
				</div>
			</div>
		</div>
	</section>
	<?php
}
