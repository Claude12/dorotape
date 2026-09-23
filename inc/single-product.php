<?php
declare( strict_types=1 );
/**
 * The single product page.
 *
 * Layout only. What the page does was built and signed off in Sprint 1 and is
 * spread across inc/pricing.php, inc/cutsize.php, inc/quickadd.php,
 * inc/poa.php, inc/stock.php and inc/rollsize.php; this file puts sections
 * around it so the page reads like the rest of the site.
 *
 * The theme has no woocommerce/ template overrides and this does not add any.
 * Everything here is hooks, so a WooCommerce update cannot leave the page
 * running a template three versions behind.
 *
 * The page is four bands:
 *
 *   1. Hero      gallery and summary, side by side on a radial wash
 *   2. Tabs      WooCommerce's own product data tabs
 *   3. Related   the shared product card, in a grid
 *   4. Sections  authored once in Theme Settings > Product Pages, the same
 *                on every product (inc/product-sections.php)
 *
 * Bands 2 and 3 disappear entirely when they have nothing to show, rule and
 * all, rather than leaving an empty stripe: plenty of this catalogue has no
 * description and no related products.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the page's layout hooks.
 *
 * On template_redirect, because is_product() is what decides and it is not
 * answerable any earlier. Everything is scoped to the single product page:
 * the shop, cart, checkout and account pages keep WooCommerce's own wrapper
 * and hooks untouched.
 */
add_action(
	'template_redirect',
	function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		// ── The content column ───────────────────────────────────────────
		//
		// WooCommerce's wrapper lands on the width-capped .site-main, which
		// would stop every band painting its own full-bleed background. This
		// is page.php's wrapper instead, in its block-built form.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', 'dorotape_product_wrapper_start', 10 );
		add_action( 'woocommerce_after_main_content', 'dorotape_product_wrapper_end', 10 );

		// The breadcrumb moves into the summary column, beside the title,
		// where the design puts it.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

		// Notices are the one thing above the hero, so they need a measure of
		// their own now that the column has none.
		remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );
		add_action( 'woocommerce_before_single_product', 'dorotape_product_notices', 10 );

		// ── Band 1: hero ────────────────────────────────────────────────
		add_action( 'woocommerce_before_single_product_summary', 'dorotape_product_hero_open', 1 );
		add_action( 'woocommerce_before_single_product_summary', 'dorotape_product_media_close', 25 );
		add_action( 'woocommerce_after_single_product_summary', 'dorotape_product_hero_close', 1 );

		// The summary column opens on the category and the trail, and closes
		// the buying decision with the assurance lines.
		add_action( 'woocommerce_single_product_summary', 'dorotape_product_category_pill', 1 );
		add_action( 'woocommerce_single_product_summary', 'dorotape_product_breadcrumb', 2 );
		add_action( 'woocommerce_single_product_summary', 'dorotape_product_assurances', 32 );

		// The design draws the SKU and category line as a definition list, one
		// row per fact. WooCommerce prints it as spans with the label left as a
		// bare text node, which leaves nothing to style the label apart from its
		// value with, and puts the separating comma inside the label's own run.
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		add_action( 'woocommerce_single_product_summary', 'dorotape_product_meta', 40 );

		// ── Band 2: tabs ────────────────────────────────────────────────
		add_action( 'woocommerce_after_single_product_summary', 'dorotape_product_tabs_open', 2 );
		add_action( 'woocommerce_after_single_product_summary', 'dorotape_product_tabs_close', 11 );

		// ── Band 3: upsells and related ─────────────────────────────────
		//
		// WooCommerce renders both through content-product.php, which would
		// mean a second product card to keep in step with the homepage's.
		// These render the shared one instead.
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', 'dorotape_product_upsells', 15 );
		add_action( 'woocommerce_after_single_product_summary', 'dorotape_product_related', 20 );

		// ── Band 4: the shared sections ─────────────────────────────────
		add_action( 'woocommerce_after_single_product', 'dorotape_render_product_sections', 10 );
	}
);

/**
 * Read a field from the Product Page group on the Product Pages options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_product_page_field( string $name, string $default = '' ): string {
	$value = dorotape_product_section_field( $name );

	return is_string( $value ) && '' !== $value ? $value : $default;
}

// ─── The content column ──────────────────────────────────────────────────

/**
 * Open the main column, in the form page.php uses for a block-built page: no
 * max-width and no padding, because each band paints its own background out
 * to the edge and brings its own .container inside.
 */
function dorotape_product_wrapper_start(): void {
	echo '<main id="primary" class="site-main site-main--blocks">';
}

/**
 * Close it.
 */
function dorotape_product_wrapper_end(): void {
	echo '</main>';
}

/**
 * WooCommerce's notices, given the measure the column no longer has.
 */
function dorotape_product_notices(): void {
	echo '<div class="container single-product__notices">';
	woocommerce_output_all_notices();
	echo '</div>';
}

// ─── Band 1: hero ────────────────────────────────────────────────────────

/**
 * Open the hero: the wash, the background shape, and the two-column grid the
 * gallery and the summary sit in.
 *
 * The gallery column is opened here and closed at priority 25, so the sale
 * flash (10) and the gallery itself (20) share one grid cell. The summary is
 * the second cell, and WooCommerce prints its wrapper itself.
 */
function dorotape_product_hero_open(): void {
	?>
	<section class="product-hero<?php echo esc_attr( dorotape_background_shape_class( dorotape_product_page_field( 'product_hero_shape', 'cubes-right' ) ) ); ?>">
		<div class="product-hero__wash" aria-hidden="true"></div>
		<?php dorotape_background_shape( dorotape_product_page_field( 'product_hero_shape', 'cubes-right' ) ); ?>

		<div class="container product-hero__inner">
			<div class="product-hero__grid">
				<div class="product-hero__media">
	<?php
}

/**
 * Close the gallery column, before WooCommerce opens the summary.
 */
function dorotape_product_media_close(): void {
	echo '</div>';
}

/**
 * Close the hero.
 */
function dorotape_product_hero_close(): void {
	echo '</div></div></section>';
}

/**
 * The category chip above the title.
 *
 * The same "most specific category" the product card shows, so a product is
 * labelled the same way in a row on the homepage and on its own page.
 */
function dorotape_product_category_pill(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$category = dorotape_product_card_meta( $product );

	if ( '' === $category ) {
		return;
	}

	printf( '<p class="product-hero__category">%s</p>', esc_html( $category ) );
}

/**
 * The breadcrumb trail, in the summary column rather than above the page.
 *
 * Same markup as the internal page banner's (inc/breadcrumbs.php), so both
 * come from Rank Math and both look the same.
 */
function dorotape_product_breadcrumb(): void {
	dorotape_breadcrumb_nav( 'product-hero__breadcrumb' );
}

/**
 * The ticked lines under the add to cart button.
 *
 * Authored once in Theme Settings > Product Pages, so the delivery promise
 * cannot end up saying two different things on two products.
 *
 * Stock is not one of them. It has its own three-state line with its own
 * left rule (components/woo/_stock.scss), which a tick would flatten.
 */
function dorotape_product_assurances(): void {
	$rows = dorotape_product_section_field( 'product_assurances' );

	if ( ! is_array( $rows ) || ! $rows ) {
		return;
	}

	echo '<ul class="product-assurances">';

	foreach ( $rows as $row ) {
		$icon = isset( $row['icon'] ) ? (string) $row['icon'] : 'check';
		$text = isset( $row['text'] ) ? (string) $row['text'] : '';

		if ( '' === $text ) {
			continue;
		}

		echo '<li class="product-assurances__item">';
		// Trusted markup from the shared set in inc/icons.php, not editor input.
		echo dorotape_icon( $icon, 'product-assurances__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hard-coded SVG paths.
		echo '<span>' . esc_html( $text ) . '</span></li>';
	}

	echo '</ul>';
}


/**
 * The SKU and category line under the buying controls.
 *
 * Replaces woocommerce_template_single_meta so the label and the value are
 * separate elements, as the design has them: the label extrabold, the value
 * regular, and the comma between two categories part of the value rather than
 * of the label. Tags are carried through when a product has them, because they
 * are content the shop already holds even though the design page had none.
 */
function dorotape_product_meta(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$rows = array();

	if ( $product->get_sku() ) {
		$rows[] = array( __( 'SKU:', 'dorotape' ), esc_html( $product->get_sku() ) );
	}

	$categories = wc_get_product_category_list( $product->get_id(), ', ' );

	if ( $categories ) {
		$rows[] = array(
			_n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'dorotape' ),
			$categories,
		);
	}

	$tags = wc_get_product_tag_list( $product->get_id(), ', ' );

	if ( $tags ) {
		$rows[] = array(
			_n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'dorotape' ),
			$tags,
		);
	}

	if ( ! $rows ) {
		return;
	}

	echo '<dl class="dt-product-meta">';

	foreach ( $rows as list( $label, $value ) ) {
		echo '<div class="dt-product-meta__row">';
		echo '<dt class="dt-product-meta__label">' . esc_html( $label ) . '</dt>';
		// Term lists are WooCommerce's own anchor markup, already escaped.
		echo '<dd class="dt-product-meta__value">' . $value . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_get_product_*_list() escapes.
		echo '</div>';
	}

	echo '</dl>';
}

// ─── Band 2: tabs ────────────────────────────────────────────────────────

/**
 * Start capturing the tabs.
 *
 * Buffered rather than wrapped directly, because WooCommerce prints nothing
 * at all when a product has no description and no attributes, and an empty
 * band with a rule under it looks like a bug.
 */
function dorotape_product_tabs_open(): void {
	ob_start();
}

/**
 * Print the tabs band, or throw it away if it came out empty.
 */
function dorotape_product_tabs_close(): void {
	$tabs = trim( (string) ob_get_clean() );

	if ( '' === $tabs ) {
		return;
	}

	printf(
		'<section class="product-tabs"><div class="container product-tabs__inner">%s</div></section>',
		$tabs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's own tab markup.
	);

	echo '<div class="aurora-rule" aria-hidden="true"></div>';
}

// ─── Band 3: upsells and related ─────────────────────────────────────────

/**
 * One grid of product cards under a heading.
 *
 * Shared by upsells and related products, which differ only in their heading
 * and in which products they list.
 *
 * @param WC_Product[] $products Products to show.
 * @param string       $eyebrow  Small magenta line above the heading.
 * @param string       $heading  Section heading.
 * @param string       $shape    Background shape value, '' for none.
 */
function dorotape_product_grid( array $products, string $eyebrow, string $heading, string $shape = '' ): void {
	if ( ! $products ) {
		return;
	}

	// WooCommerce's loop button reads the global, and so does anything a
	// plugin has hooked onto it, so the global is what moves through the loop
	// and is put back afterwards.
	global $product;
	$original = $product;
	?>
	<section class="product-grid<?php echo esc_attr( dorotape_background_shape_class( $shape ) ); ?>">
		<?php dorotape_background_shape( $shape ); ?>

		<div class="container product-grid__inner">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="product-grid__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $heading ) : ?>
				<h2 class="product-grid__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>

			<ul class="product-grid__items">
				<?php foreach ( $products as $dt_item ) : ?>
					<?php $product = $dt_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below. ?>
					<li class="product-grid__item">
						<?php
						dorotape_product_card(
							$dt_item,
							array(
								'link'        => 'title',
								'add_to_cart' => true,
								'modifier'    => 'product-card--grid',
								'sizes'       => '(min-width: 1024px) 320px, (min-width: 576px) 46vw, calc(100vw - 48px)',
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
	$product = $original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring it.

	echo '<div class="aurora-rule" aria-hidden="true"></div>';
}

/**
 * Products the shop has marked as an upsell on this one.
 *
 * Unheaded in WooCommerce's own template; given the same treatment as
 * related products here so the two cannot look like different features.
 */
function dorotape_product_upsells(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$ids = $product->get_upsell_ids();

	if ( ! $ids ) {
		return;
	}

	dorotape_product_grid(
		dorotape_product_grid_products( $ids, 4 ),
		__( 'We also recommend', 'dorotape' ),
		__( 'You may also like', 'dorotape' ),
		''
	);
}

/**
 * Products in the same category, from WooCommerce's own related products.
 */
function dorotape_product_related(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	/** This filter is documented in woocommerce/includes/wc-template-functions.php */
	$limit = (int) apply_filters( 'woocommerce_output_related_products_args', array( 'posts_per_page' => 4 ) )['posts_per_page'];
	$ids   = wc_get_related_products( $product->get_id(), max( 1, $limit ) );

	dorotape_product_grid(
		dorotape_product_grid_products( $ids, max( 1, $limit ) ),
		dorotape_product_page_field( 'product_related_eyebrow', __( 'You may also need', 'dorotape' ) ),
		dorotape_product_page_field( 'product_related_heading', __( 'Related products', 'dorotape' ) ),
		dorotape_product_page_field( 'product_related_shape', 'cubes-left' )
	);
}

/**
 * Turn a list of product IDs into visible, purchasable-looking products.
 *
 * Four per row, so anything hidden from the catalogue is dropped before the
 * count rather than leaving a hole in the grid.
 *
 * @param int[] $ids   Product IDs.
 * @param int   $limit How many to keep.
 * @return WC_Product[]
 */
function dorotape_product_grid_products( array $ids, int $limit ): array {
	$products = array();

	foreach ( $ids as $id ) {
		if ( count( $products ) >= $limit ) {
			break;
		}

		$item = wc_get_product( (int) $id );

		if ( $item instanceof WC_Product && $item->is_visible() ) {
			$products[] = $item;
		}
	}

	return $products;
}
