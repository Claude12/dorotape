<?php
declare( strict_types=1 );
/**
 * The top level product category page.
 *
 * Built from final designs/top level category: a banner with the category name
 * and a photograph, an intro band, the grid of ranges inside it, then the
 * shared USP strip and CTA triptych.
 *
 * Wired entirely through hooks. The theme has no woocommerce/ template
 * overrides and this does not add any, for the reason inc/single-product.php
 * gives: an override is a copy of a plugin file that stops receiving the
 * plugin's own fixes the moment it is made.
 *
 * Scope is a category that has children. That is exactly the condition the
 * `pre_option_woocommerce_category_archive_display` filter in inc/woocommerce.php
 * already assumes, because a category with children shows its ranges rather
 * than its products. A leaf category keeps the holding layer until its own
 * design is built, so nothing here half-paints a page type that has not been
 * designed yet.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The category being viewed, or null when this is not a category archive.
 */
function dorotape_category_term(): ?WP_Term {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return null;
	}

	$term = get_queried_object();

	return $term instanceof WP_Term ? $term : null;
}

/**
 * The category's children, in the order set by dragging them in Products,
 * Categories.
 *
 * WooCommerce's own function rather than a get_terms() call: it is cached per
 * parent, it pads the counts so a parent whose products all sit in
 * grandchildren still counts as stocked, and it drops the empty ones. Its
 * order comes free, because WooCommerce defaults product_cat term queries to
 * menu_order, which is the admin's drag and drop order. The design's own order
 * is curated rather than alphabetical, so that is the one to follow.
 *
 * @return array<int, object> Category objects as get_categories() returns them.
 */
function dorotape_category_children( WP_Term $term ): array {
	if ( ! function_exists( 'woocommerce_get_product_subcategories' ) ) {
		return array();
	}

	$children = woocommerce_get_product_subcategories( $term->term_id );

	return is_array( $children ) ? $children : array();
}

/**
 * True on a category archive that has ranges under it.
 */
function dorotape_is_parent_product_category(): bool {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term ) {
		return false;
	}

	return array() !== dorotape_category_children( $term );
}

/**
 * True on a category archive that lists products rather than ranges.
 *
 * The two are exclusive: a category either has children to show or it does
 * not. Woo itself makes the same split, showing subcategory tiles above the
 * loop, and the designs follow it, so one test decides which page is drawn.
 */
function dorotape_is_leaf_product_category(): bool {
	return dorotape_category_term() instanceof WP_Term && ! dorotape_is_parent_product_category();
}

/**
 * Read a field saved against the category being viewed.
 *
 * @param string $name    Field name.
 * @param string $default Value to use when the category leaves it empty.
 */
function dorotape_category_field( string $name, string $default = '' ): string {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term || ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, 'term_' . $term->term_id );

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * Put the page together.
 *
 * On template_redirect rather than at file load, so the hooks only exist on
 * the pages they belong to and Woo's own archive is left alone everywhere
 * else.
 */
add_action(
	'template_redirect',
	function (): void {
		if ( ! dorotape_is_parent_product_category() ) {
			return;
		}

		// Woo's own chrome. All of it is either drawn differently below or
		// meaningless on a page that lists categories rather than products:
		// there is nothing to sort, count or paginate, and the "Refine by"
		// panel filters attributes the ranges do not share.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
		remove_action( 'woocommerce_sidebar', 'dorotape_woocommerce_sidebar', 10 );

		// The theme's own page wrapper, the same one the product page and the
		// block pages use, so the blocks in the tail sit in the container they
		// were written for.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', 'dorotape_category_wrapper_start', 10 );
		add_action( 'woocommerce_after_main_content', 'dorotape_category_wrapper_end', 90 );

		// Banner, then whatever the category itself has been given to say.
		add_action( 'woocommerce_before_main_content', 'dorotape_category_banner', 20 );
		add_action( 'woocommerce_before_main_content', 'dorotape_category_term_sections', 30 );

		// The ranges grid replaces Woo's own subcategory tiles. Its own filter
		// appends them inside the products list; this returns the band in place
		// of that list instead, because a section is not valid inside a <ul>.
		remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );
		add_filter( 'woocommerce_product_loop_start', 'dorotape_category_ranges_loop_start' );
		add_filter( 'woocommerce_product_loop_end', 'dorotape_category_ranges_loop_end' );

		// A parent whose children are all out of stock never reaches the loop
		// at all, so the band has to be drawn from here as well.
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
		add_action( 'woocommerce_no_products_found', 'dorotape_category_ranges', 10 );

		// The tail, after the ranges and before the wrapper closes.
		add_action( 'woocommerce_after_main_content', 'dorotape_render_category_sections', 10 );
	},
	20
);

/**
 * The same, for a category that lists products.
 *
 * The shared half of the page is shared literally: the wrapper, the banner,
 * the term's own sections and the tail are the parent page's functions,
 * called from here unchanged. Only the band in the middle differs, which is
 * the whole of the difference between the two designs.
 */
add_action(
	'template_redirect',
	function (): void {
		if ( ! dorotape_is_leaf_product_category() ) {
			return;
		}

		// Woo's own chrome, and the theme's select based filter bar with it.
		// Sorting, the result count and pagination all describe a paginated
		// loop, and this page has none: it renders the category once and
		// filters it in the browser.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_before_shop_loop', 'dorotape_render_filter_bar', 15 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
		remove_action( 'woocommerce_no_products_found', 'dorotape_render_filter_bar', 5 );
		remove_action( 'woocommerce_sidebar', 'dorotape_woocommerce_sidebar', 10 );

		add_action( 'woocommerce_before_main_content', 'dorotape_category_wrapper_start', 10 );
		add_action( 'woocommerce_after_main_content', 'dorotape_category_wrapper_end', 90 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

		add_action( 'woocommerce_before_main_content', 'dorotape_category_banner', 20 );
		add_action( 'woocommerce_before_main_content', 'dorotape_category_term_sections', 30 );

		/*
		 * The archive loop is emptied and the shop drawn in its place.
		 *
		 * Woo's loop paginates, and nothing on this page can: a filter that
		 * only searched the visible page would be worse than no filter. So the
		 * main query is given nothing to find, which sends Woo down its
		 * "no products" path, and the shop is rendered there from its own
		 * query instead. It is the same hook the ranges band is drawn on one
		 * file up, so both category designs replace the loop the same way.
		 */
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
		add_action( 'woocommerce_no_products_found', 'dorotape_category_shop', 10 );

		add_action( 'woocommerce_after_main_content', 'dorotape_render_category_sections', 10 );
	},
	20
);

/**
 * Give a leaf category's archive query nothing to return.
 *
 * Hooked at file load, not from the template_redirect block above, because the
 * main query has already run by the time template_redirect fires: a filter
 * registered there would never be reached. The leaf test is therefore made
 * against the query rather than the page conditionals, which are not set yet
 * either.
 *
 * @param WP_Query $query The product query WooCommerce built.
 */
function dorotape_category_empty_loop( WP_Query $query ): void {
	if ( ! $query->is_main_query() || ! $query->is_tax( 'product_cat' ) ) {
		return;
	}

	$term = $query->get_queried_object();

	// A category with ranges under it keeps Woo's loop: the top level design
	// draws its own band from woocommerce_product_loop_start instead.
	if ( ! $term instanceof WP_Term || dorotape_category_children( $term ) ) {
		return;
	}

	$query->set( 'post__in', array( 0 ) );
	$query->set( 'no_found_rows', true );
}

add_action( 'woocommerce_product_query', 'dorotape_category_empty_loop' );

/**
 * Open the page.
 */
function dorotape_category_wrapper_start(): void {
	echo '<main id="primary" class="site-main site-main--blocks">';
}

/**
 * Close it.
 */
function dorotape_category_wrapper_end(): void {
	echo '</main>';
}

/**
 * The banner.
 *
 * The internal banner component with a photograph beside the text, which is
 * the only thing the category design adds to it. Reusing the component rather
 * than drawing a second banner keeps the eyebrow, heading, standfirst and
 * breadcrumb identical to every other internal page, which is worth more than
 * matching the design's own top-right wash, drawn on this page and nowhere
 * else.
 */
function dorotape_category_banner(): void {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	/*
	 * The eyebrow names where you are, one level up. The design puts the
	 * parent category above a child's heading ("Signmaking Vinyl" over the
	 * Optima range) and the generic word above a top level one, which has no
	 * parent to name. Deriving it saves the client typing it on every
	 * category, and the ACF field still wins where they want something else.
	 */
	$parent  = $term->parent > 0 ? get_term( $term->parent, 'product_cat' ) : null;
	$default = $parent instanceof WP_Term
		? $parent->name
		: dorotape_category_page_field( 'category_eyebrow', __( 'Products', 'dorotape' ) );

	$eyebrow = dorotape_category_field( 'category_banner_eyebrow', $default );
	$title   = dorotape_category_field( 'category_banner_title', $term->name );
	$intro   = dorotape_category_field( 'category_banner_intro', trim( wp_strip_all_tags( $term->description ) ) );

	// The category thumbnail set in Products, Categories is the fallback, so a
	// category that was never given a banner image still shows the picture the
	// client already uploaded for it.
	$image_id = function_exists( 'get_field' ) ? (int) get_field( 'category_banner_image', 'term_' . $term->term_id ) : 0;
	if ( 0 === $image_id ) {
		$image_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	}

	$shape   = dorotape_background_shape_value( dorotape_category_field( 'category_banner_shape', 'cubes-right' ) );
	$classes = 'internal-banner-block internal-banner-block--overlap internal-banner-block--media';
	$classes .= dorotape_background_shape_class( $shape );
	?>
	<section class="<?php echo esc_attr( $classes ); ?>">
		<?php dorotape_background_shape( $shape ); ?>

		<div class="container">
			<div class="internal-banner-block__grid">

				<div class="internal-banner-block__inner">
					<?php if ( '' !== $eyebrow ) : ?>
						<p class="internal-banner-block__eyebrow">
							<span class="internal-banner-block__eyebrow-dot" aria-hidden="true"></span>
							<?php echo esc_html( $eyebrow ); ?>
						</p>
					<?php endif; ?>

					<h1 class="internal-banner-block__heading"><?php echo esc_html( $title ); ?></h1>

					<?php if ( '' !== $intro ) : ?>
						<p class="internal-banner-block__intro"><?php echo esc_html( $intro ); ?></p>
					<?php endif; ?>

					<?php dorotape_breadcrumb_nav( 'internal-banner-block__breadcrumb' ); ?>
				</div>

				<?php if ( $image_id > 0 ) : ?>
					<figure class="internal-banner-block__media">
						<?php
						// Empty alt: the heading beside it already names the
						// category, so the picture is decoration.
						echo wp_get_attachment_image(
							$image_id,
							'large',
							false,
							array(
								'class'         => 'internal-banner-block__image',
								'alt'           => '',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
								'sizes'         => '(min-width: 1024px) 50vw, 100vw',
							)
						);
						?>
					</figure>
				<?php endif; ?>

			</div>

			<div class="aurora-rule internal-banner-block__rule" aria-hidden="true"></div>
		</div>
	</section>
	<?php
}

/**
 * Whatever sections the category itself has been given, between the banner and
 * the ranges.
 *
 * Starts the block counter at 1 because the banner above is the block above
 * the fold, so nothing here should claim eager image loading or the page's
 * <h1>.
 */
/**
 * Whether this category has any sections of its own between the banner and the
 * grid below it.
 *
 * The grid draws a rule above itself to divide it from what came before. With
 * nothing in between it lands a hundred or so pixels under the banner's own
 * rule, and the pair read as two lines around an empty band rather than as one
 * divider. Most categories have not been given an intro yet, so this is the
 * common case rather than the edge case.
 */
function dorotape_category_has_term_sections(): bool {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term || ! function_exists( 'get_field' ) ) {
		return false;
	}

	return (bool) get_field( 'content_sections', 'term_' . $term->term_id );
}

function dorotape_category_term_sections(): void {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	dorotape_render_flexible_content( 'term_' . $term->term_id, 1 );
}

/**
 * The ranges grid, in place of the products list.
 *
 * Returned rather than echoed because this is a filter on the markup that
 * opens the loop. It also zeroes the loop, the way WooCommerce's own
 * subcategory function does, so the archive does not then try to list products
 * under the tiles or paginate them.
 *
 * @param string $loop_html The <ul> that would have opened the products list.
 */
function dorotape_category_ranges_loop_start( string $loop_html ): string {
	// Once only. A later loop on the same page, in a block in the tail, must
	// still be allowed to open normally.
	remove_filter( 'woocommerce_product_loop_start', 'dorotape_category_ranges_loop_start' );

	global $wp_query;

	wc_set_loop_prop( 'total', 0 );

	if ( $wp_query->is_main_query() ) {
		$wp_query->post_count    = 0;
		$wp_query->max_num_pages = 0;
	}

	ob_start();
	dorotape_category_ranges();

	return (string) ob_get_clean();
}

/**
 * Swallow the tag that would have closed the products list.
 *
 * @param string $loop_html The closing </ul>.
 */
function dorotape_category_ranges_loop_end( string $loop_html ): string {
	remove_filter( 'woocommerce_product_loop_end', 'dorotape_category_ranges_loop_end' );

	return '';
}

/**
 * Draw the ranges grid.
 *
 * The category grid block's own markup and styling, fed by the category's
 * children instead of by repeater rows an editor fills in by hand. The design
 * for this band and the one on the homepage are the same band, so they are the
 * same component; a category that gains a range gets a new tile without anyone
 * editing anything.
 */
function dorotape_category_ranges(): void {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$children = dorotape_category_children( $term );

	if ( array() === $children ) {
		return;
	}

	$heading = dorotape_category_field(
		'category_ranges_heading',
		/* translators: %s: category name. */
		sprintf( __( 'Shop %s', 'dorotape' ), $term->name )
	);

	$note  = dorotape_category_page_field( 'category_ranges_note', __( 'stocked in the UK and dispatched across the UK and Ireland.', 'dorotape' ) );
	$count = count( $children );
	$intro = dorotape_category_field(
		'category_ranges_intro',
		trim(
			sprintf(
				/* translators: 1: number of ranges, 2: a closing phrase such as "stocked in the UK." */
				_n( '%1$d range in this category, %2$s', '%1$d ranges in this category, %2$s', $count, 'dorotape' ),
				$count,
				$note
			)
		)
	);

	$button = dorotape_category_page_field( 'category_ranges_button', __( 'View all', 'dorotape' ) );

	$shape   = dorotape_background_shape_value( dorotape_category_page_field( 'category_ranges_shape', 'cubes-right' ) );
	// --glow-soft because a category can run to twenty ranges: the homepage's
	// own corner glow is sized for the two rows it has there.
	$classes  = 'category-grid-block category-grid-block--glow-soft';
	$has_sections = dorotape_category_has_term_sections();
	$classes     .= $has_sections ? ' category-grid-block--divider' : '';
	$classes .= dorotape_background_shape_class( $shape );
	?>
	<section id="ranges" class="<?php echo esc_attr( $classes ); ?>" animate="fade-in-up">
		<?php dorotape_background_shape( $shape ); ?>
		<?php if ( $has_sections ) : ?>
			<div class="aurora-rule category-grid-block__rule" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="container">

			<?php if ( '' !== $heading || '' !== $intro ) : ?>
				<div class="category-grid-block__header">
					<?php if ( '' !== $heading ) : ?>
						<h2 class="category-grid-block__heading"><?php echo esc_html( $heading ); ?></h2>
					<?php endif; ?>

					<?php if ( '' !== $intro ) : ?>
						<p class="category-grid-block__intro"><?php echo esc_html( $intro ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<ul class="category-grid-block__grid">
				<?php foreach ( $children as $child ) : ?>
					<?php $image_id = (int) get_term_meta( (int) $child->term_id, 'thumbnail_id', true ); ?>
					<li class="category-grid-block__item">
						<a class="category-grid-block__card" href="<?php echo esc_url( (string) get_term_link( (int) $child->term_id, 'product_cat' ) ); ?>">
							<?php if ( $image_id > 0 ) : ?>
								<?php
								// Empty alt for the same reason as the block: the
								// link's own text names the range already.
								echo wp_get_attachment_image(
									$image_id,
									'medium_large',
									false,
									array(
										'class'    => 'category-grid-block__image',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(min-width: 1024px) 25vw, 50vw',
									)
								);
								?>
							<?php else : ?>
								<span class="category-grid-block__image category-grid-block__image--empty" aria-hidden="true"></span>
							<?php endif; ?>

							<div class="category-grid-block__body">
								<h3 class="category-grid-block__title"><?php echo esc_html( $child->name ); ?></h3>

								<?php if ( '' !== $button ) : ?>
									<span class="btn btn--sm btn--outline category-grid-block__button"><?php echo esc_html( $button ); ?><?php echo dorotape_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?></span>
								<?php endif; ?>
							</div>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

		</div>
	</section>
	<?php
}
