<?php
declare( strict_types=1 );
/**
 * The search results page.
 *
 * Two routes reach it, and they are the same page.
 *
 * The header's search form posts `post_type=product`, which WooCommerce turns
 * into a product archive: that is the customer path, and the one that matters.
 * A bare `?s=` from a browser's site search, an old link or a screen reader's
 * quick search lands on search.php instead and finds pages as well as
 * products. Both get the same banner, the same band and the same cards,
 * because a customer cannot tell which of the two they are on and should not
 * have to.
 *
 * There is no search design, so nothing here is invented. The banner is the
 * internal banner every other internal page opens with, the band is the
 * category grid block the ranges and the shop already sit in, the product
 * cards are dorotape_product_card() and the page cards are the category grid
 * card. The only thing written for this page is which of them to show.
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
const DOROTAPE_SEARCH_SECTIONS_SLUG = 'dorotape-search-page';

/**
 * The ACF post id the search page's wording and sections are stored under.
 */
const DOROTAPE_SEARCH_SECTIONS_ID = 'dorotape_search_page';

/**
 * Register the options page under Theme Settings.
 *
 * The fourth store, built exactly like the product and category ones: in PHP
 * so it travels with the theme, and autoloaded because ACF writes a
 * wp_options row per subfield and a search is asking for all of them.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Search Page', 'dorotape' ),
				'menu_title'      => __( 'Search Page', 'dorotape' ),
				'menu_slug'       => DOROTAPE_SEARCH_SECTIONS_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_SEARCH_SECTIONS_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save search page', 'dorotape' ),
				'updated_message' => __( 'Search page saved.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a text field from the Search Page options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_search_field( string $name, string $default = '' ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_SEARCH_SECTIONS_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * Put the search term and the result count into an editor's wording.
 *
 * The same `{n}` convention the filter panel's result line already uses, plus
 * `{query}` for what was typed. Tokens rather than sprintf placeholders so a
 * client editing the field cannot break the page by dropping a `%s`.
 *
 * @param string $text  Wording from the options page.
 * @param int    $count Results found.
 */
function dorotape_search_tokens( string $text, int $count ): string {
	return str_replace(
		array( '{n}', '{query}' ),
		array( number_format_i18n( $count ), get_search_query() ),
		$text
	);
}

/**
 * How many results the current search found, across every page of them.
 */
function dorotape_search_found(): int {
	global $wp_query;

	return $wp_query instanceof WP_Query ? (int) $wp_query->found_posts : 0;
}

/**
 * True on the product half of search: the route the header's form submits to.
 *
 * WooCommerce claims `?s=…&post_type=product` and renders it through its own
 * archive template, so the page conditionals say is_shop() here as well as
 * is_search(). Testing the query var rather than is_shop() keeps this false
 * on the shop page itself.
 */
function dorotape_is_product_search(): bool {
	return is_search() && 'product' === get_query_var( 'post_type' );
}

/**
 * The banner.
 *
 * inc/blocks/internal-banner-block.php's markup with the fields filled from
 * the options page instead of from a block, and no backdrop image: there is
 * one picture that could go here and it would be the same on every search.
 */
function dorotape_search_banner(): void {
	$count   = dorotape_search_found();
	$eyebrow = dorotape_search_field( 'search_eyebrow', __( 'Search', 'dorotape' ) );
	$heading = dorotape_search_tokens(
		dorotape_search_field( 'search_heading', __( 'Results for “{query}”', 'dorotape' ) ),
		$count
	);
	$intro = dorotape_search_tokens(
		dorotape_search_field(
			'search_intro',
			/* translators: {n} is replaced with the number of results. */
			__( '{n} matches across our products, ranges and guides.', 'dorotape' )
		),
		$count
	);

	// Nothing found: the count sentence would read "0 matches across our
	// products", which is the empty message's job and reads worse than it.
	if ( 0 === $count ) {
		$intro = '';
	}

	$shape   = dorotape_background_shape_value( dorotape_search_field( 'search_banner_shape', 'cubes-right' ) );
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
 * The same shell as the ranges grid and the leaf shop, with the same soft
 * glow: a search can return one card or forty, and the homepage's corner
 * glow is sized for the two rows it has there.
 */
function dorotape_search_band_classes(): string {
	$shape = dorotape_background_shape_value( dorotape_search_field( 'search_results_shape', 'cubes-right' ) );

	return 'category-grid-block category-grid-block--glow-soft category-grid-block--tight' . dorotape_background_shape_class( $shape );
}

/**
 * The band's background shape, for callers that have opened the section
 * themselves.
 */
function dorotape_search_band_shape(): void {
	dorotape_background_shape( dorotape_background_shape_value( dorotape_search_field( 'search_results_shape', 'cubes-right' ) ) );
}

/**
 * The band header: a heading, no standfirst.
 *
 * The banner above has already said how many results there are, and saying it
 * again forty pixels lower is noise rather than reassurance.
 *
 * @param string $heading Band heading.
 * @param bool   $stacked True for a second heading inside the same band, which
 *                        needs space above it to stand off the grid it follows.
 */
function dorotape_search_band_header( string $heading, bool $stacked = false ): void {
	if ( '' === $heading ) {
		return;
	}

	$classes = 'category-grid-block__header' . ( $stacked ? ' category-grid-block__header--stacked' : '' );
	?>
	<div class="<?php echo esc_attr( $classes ); ?>">
		<h2 class="category-grid-block__heading"><?php echo esc_html( $heading ); ?></h2>
	</div>
	<?php
}

/**
 * The arguments every result card is drawn with.
 *
 * The leaf category grid's arguments, so a product looks the same in a search
 * as it does inside its own category, plus the category pill: a search crosses
 * categories, and naming each card's own one is the single most useful thing a
 * result can say.
 *
 * @param WC_Product $product Product being drawn.
 * @return array<string, mixed>
 */
function dorotape_search_card_args( WC_Product $product ): array {
	$pills = array();
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( is_array( $terms ) && $terms ) {
		$pills[] = array(
			'label' => $terms[0]->name,
			'tone'  => 'cyan',
		);
	}

	return array(
		'link'        => 'title',
		'add_to_cart' => true,
		'pills'       => $pills,
		'modifier'    => 'product-card--grid',
		'sizes'       => '(min-width: 1280px) 300px, (min-width: 576px) 44vw, 88vw',
	);
}

/**
 * The band shown when a search found nothing.
 *
 * The same shell, so the page does not change shape between a search that
 * worked and one that did not.
 */
function dorotape_search_empty(): void {
	$message = dorotape_search_tokens(
		dorotape_search_field(
			'search_empty',
			__( 'Nothing matched “{query}”. Try a shorter term or an item number, or call us on 01858 431642 and we will point you to the right material.', 'dorotape' )
		),
		0
	);
	?>
	<section class="<?php echo esc_attr( dorotape_search_band_classes() ); ?>" animate="fade-in-up">
		<?php dorotape_search_band_shape(); ?>
		<div class="container">
			<p class="category-shop__empty"><?php echo esc_html( $message ); ?></p>
		</div>
	</section>
	<?php
}

/**
 * Pagination, in WooCommerce's own shape.
 *
 * templates/loop/pagination.php markup rather than the_posts_pagination()'s,
 * so the generic route and the product route paginate with the same element
 * and the same styling instead of two lists that happen to look similar.
 */
function dorotape_search_pagination(): void {
	global $wp_query;

	$total = $wp_query instanceof WP_Query ? (int) $wp_query->max_num_pages : 0;

	if ( $total < 2 ) {
		return;
	}

	$links = paginate_links(
		array(
			'base'      => esc_url_raw( add_query_arg( 'paged', '%#%' ) ),
			'format'    => '',
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'total'     => $total,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'type'      => 'list',
			'end_size'  => 3,
			'mid_size'  => 3,
		)
	);

	if ( ! $links ) {
		return;
	}

	echo '<nav class="woocommerce-pagination" aria-label="' . esc_attr__( 'Search results pages', 'dorotape' ) . '">'
		. wp_kses_post( $links )
		. '</nav>';
}

/**
 * True when an editor has put sections on the search page's options page.
 */
function dorotape_has_search_sections(): bool {
	return dorotape_has_flexible_content( DOROTAPE_SEARCH_SECTIONS_ID );
}

/**
 * The shared tail, after the results.
 *
 * Starts the block counter at 1 for the same reason the category tail does:
 * the banner above is the block above the fold.
 */
function dorotape_render_search_sections(): void {
	if ( ! dorotape_has_search_sections() ) {
		return;
	}

	dorotape_render_flexible_content( DOROTAPE_SEARCH_SECTIONS_ID, 1 );
}

// ─────────────────────────────────────────────────────────────────────────────
// The product route: WooCommerce's archive template, repainted.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Put the product search page together.
 *
 * On template_redirect so the hooks only exist on this page, and at the same
 * priority the two category dispatchers use.
 */
add_action(
	'template_redirect',
	function (): void {
		if ( ! dorotape_is_product_search() ) {
			return;
		}

		// Woo's own chrome. The breadcrumb is drawn in the banner, the result
		// count is in the banner's standfirst, the shop page's description
		// belongs to the shop page and there is nothing to sort: a search is
		// already ordered by how well each product matched.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_sidebar', 'dorotape_woocommerce_sidebar', 10 );

		// The theme's own wrapper, so the blocks in the tail sit in the
		// container they were written for.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', 'dorotape_category_wrapper_start', 10 );
		add_action( 'woocommerce_after_main_content', 'dorotape_category_wrapper_end', 90 );

		add_action( 'woocommerce_before_main_content', 'dorotape_search_banner', 20 );

		// The band around the loop. Opened before it and closed after the
		// pagination, which Woo prints at priority 10, so the page numbers sit
		// inside the band with the cards they belong to.
		add_action( 'woocommerce_before_shop_loop', 'dorotape_search_loop_before', 5 );
		add_action( 'woocommerce_after_shop_loop', 'dorotape_search_loop_after', 90 );

		// The grid itself, in place of Woo's <ul class="products">.
		add_filter( 'woocommerce_product_loop_start', 'dorotape_search_loop_start' );
		add_filter( 'woocommerce_product_loop_end', 'dorotape_search_loop_end' );
		add_filter( 'woocommerce_post_class', 'dorotape_search_post_class', 10, 2 );

		// One card per result, ours, in place of Woo's stack of loop partials.
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
		add_action( 'woocommerce_no_products_found', 'dorotape_search_empty', 10 );

		add_action( 'woocommerce_after_main_content', 'dorotape_render_search_sections', 10 );
	},
	20
);

/**
 * Open the results band.
 */
function dorotape_search_loop_before(): void {
	?>
	<section class="<?php echo esc_attr( dorotape_search_band_classes() ); ?>" animate="fade-in-up" animate-offset="0">
		<?php dorotape_search_band_shape(); ?>
		<div class="container">
			<?php dorotape_search_band_header( dorotape_search_field( 'search_products_heading', __( 'Products', 'dorotape' ) ) ); ?>
	<?php
}

/**
 * Close it.
 */
function dorotape_search_loop_after(): void {
	echo '</div></section>';
}

/**
 * The grid, in place of Woo's products list.
 *
 * The leaf category's grid class, so a search result sits in the same columns
 * at the same gaps as the same product does inside its category.
 *
 * @param string $loop_html The <ul> that would have opened the list.
 */
function dorotape_search_loop_start( string $loop_html ): string {
	remove_filter( 'woocommerce_product_loop_start', 'dorotape_search_loop_start' );

	return '<div class="category-shop__products"><ul class="category-shop__grid category-shop__grid--wide">';
}

/**
 * Close it.
 *
 * @param string $loop_html The </ul> that would have closed the list.
 */
function dorotape_search_loop_end( string $loop_html ): string {
	remove_filter( 'woocommerce_product_loop_end', 'dorotape_search_loop_end' );

	return '</ul></div>';
}

/**
 * Put the grid item class on Woo's own <li>.
 *
 * content-product.php owns that element and the theme has no override of it,
 * so the class is added through the filter Woo provides rather than by
 * copying the template to change one attribute.
 *
 * @param array<int, string> $classes Classes Woo worked out.
 * @param WC_Product|null    $product The product, when Woo passed one.
 * @return array<int, string>
 */
function dorotape_search_post_class( array $classes, $product = null ): array {
	$classes[] = 'category-shop__item';

	return $classes;
}

/**
 * One result card, for WooCommerce's own loop.
 */
function dorotape_search_product_card(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	dorotape_product_card( $product, dorotape_search_card_args( $product ) );
}
