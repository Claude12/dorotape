<?php
declare( strict_types=1 );
/**
 * The basket, the checkout and the wishlist.
 *
 * These three are WordPress pages, not WooCommerce archives: the basket and
 * the checkout hold WooCommerce's block markup, the wishlist holds YITH's
 * shortcode. So there is nothing to hook into the way the shop and the
 * category pages are assembled. What they get instead is the page chrome
 * every other internal page already has, from template-parts/content-woo.php,
 * and stylesheets that dress the plugins' own markup in the brand's colours
 * (assets/scss/components/woo/).
 *
 * No woocommerce/ template overrides, for the reason inc/product-category.php
 * gives: an override pins the markup to one WooCommerce version, and the cart
 * and checkout blocks change shape between releases.
 *
 * The account screens are the same shape of problem and are in
 * inc/account-pages.php, which has nine sets of wording to hold and an
 * options page of its own. The three functions below hand over to it: what is
 * shared is the chrome, and there is one copy of that.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also what the field group's location rule
 * matches on.
 */
const DOROTAPE_WOO_PAGES_SLUG = 'dorotape-woo-pages';

/**
 * The ACF post id the three pages' wording is stored under.
 */
const DOROTAPE_WOO_PAGES_ID = 'dorotape_woo_pages';

/**
 * Register the options page under Theme Settings.
 *
 * Built like the shop, search and 404 stores: in PHP so it travels with the
 * theme, autoloaded because ACF writes a wp_options row per field and these
 * are read on every basket and checkout view.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Basket &amp; Checkout', 'dorotape' ),
				'menu_title'      => __( 'Basket &amp; Checkout', 'dorotape' ),
				'menu_slug'       => DOROTAPE_WOO_PAGES_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_WOO_PAGES_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save basket &amp; checkout', 'dorotape' ),
				'updated_message' => __( 'Basket &amp; checkout saved.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a text field from the Basket & Checkout options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_woo_pages_field( string $name, string $default = '' ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_WOO_PAGES_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * Which of these pages is being viewed, if any.
 *
 * The order matters. WooCommerce counts the order received page as part of
 * the checkout, and it is the one page of the four where the work is already
 * done, so it is asked about first and gets its own words.
 *
 * @return string cart | checkout | order | account | wishlist, or '' for
 *                anything else.
 */
function dorotape_woo_page(): string {
	if ( ! function_exists( 'is_cart' ) ) {
		return '';
	}

	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return 'order';
	}

	if ( is_cart() ) {
		return 'cart';
	}

	if ( is_checkout() ) {
		return 'checkout';
	}

	if ( function_exists( 'yith_wcwl_is_wishlist_page' ) && yith_wcwl_is_wishlist_page() ) {
		return 'wishlist';
	}

	if ( function_exists( 'dorotape_account_view' ) && '' !== dorotape_account_view() ) {
		return 'account';
	}

	return '';
}

/**
 * What each page is titled before an editor has changed it.
 *
 * Written here rather than only in the field group's defaults so the pages
 * read properly on a fresh install, which is how every other options-backed
 * page in the theme does it.
 *
 * One line each. These pages used to open on the same banner the internal
 * pages wear, with an eyebrow and a standfirst above the fold; a basket is a
 * job someone came here to finish, not a page to be introduced, so the banner
 * came off and the heading is all that is left of it.
 *
 * @return array<string, string>
 */
function dorotape_woo_pages_defaults(): array {
	return array(
		'cart'     => __( 'Your basket', 'dorotape' ),
		'checkout' => __( 'Delivery and payment', 'dorotape' ),
		'order'    => __( 'Thank you for your order', 'dorotape' ),
		'wishlist' => __( 'Saved for later', 'dorotape' ),
	);
}

/**
 * This page's heading, for the header template-parts/content-woo.php prints.
 */
function dorotape_woo_page_heading(): string {
	$page = dorotape_woo_page();

	if ( '' === $page ) {
		return '';
	}

	if ( 'account' === $page ) {
		return dorotape_account_heading();
	}

	$defaults = dorotape_woo_pages_defaults();

	return dorotape_woo_pages_field( 'woo_' . $page . '_heading', $defaults[ $page ] ?? '' );
}

/**
 * The classes on the band the page's own content sits in.
 *
 * The same shell the shop grid and the search results sit in, so a basket
 * reads as the same kind of page as the one it was filled from.
 */
function dorotape_woo_page_band_classes(): string {
	$page = dorotape_woo_page();

	if ( 'account' === $page ) {
		return dorotape_account_band_classes();
	}

	$shape = dorotape_background_shape_value( dorotape_woo_pages_field( 'woo_' . $page . '_content_shape', 'none' ) );

	return 'category-grid-block category-grid-block--glow-soft category-grid-block--tight woo-page woo-page--' . $page . dorotape_background_shape_class( $shape );
}

/**
 * The band's background shape, for the template that opened the section.
 */
function dorotape_woo_page_band_shape(): void {
	$page = dorotape_woo_page();

	if ( 'account' === $page ) {
		dorotape_account_band_shape();

		return;
	}

	dorotape_background_shape( dorotape_background_shape_value( dorotape_woo_pages_field( 'woo_' . $page . '_content_shape', 'none' ) ) );
}

/**
 * The empty basket's "New in store" products, as the site's own cards.
 *
 * The Cart block's empty state holds WooCommerce's Newest Products block,
 * which draws its own unstyled grid and shows the grey placeholder for every
 * product without a photo. Its output is swapped for the card grid Related
 * products uses, fed by the Product Row's "newest" source, which only picks
 * products that have a photo. The heading above it stays in the page content.
 *
 * @param string $html  The block's own output.
 * @param array  $block The parsed block.
 * @return string
 */
function dorotape_empty_cart_products( string $html, array $block ): string {
	if ( ! is_cart() ) {
		return $html;
	}

	$count    = (int) ( $block['attrs']['columns'] ?? 4 ) * (int) ( $block['attrs']['rows'] ?? 1 );
	$products = dorotape_product_row_products( 'newest', max( 1, $count ), array() );

	if ( ! $products ) {
		return $html;
	}

	// WooCommerce's loop button reads the global product.
	$original = $GLOBALS['product'] ?? null;

	ob_start();
	echo dorotape_product_list_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper.
	foreach ( $products as $dt_item ) {
		$GLOBALS['product'] = $dt_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
		echo '<li class="' . esc_attr( dorotape_product_list_item_class() ) . '">';
		dorotape_product_card(
			$dt_item,
			array(
				'link'        => 'title',
				'add_to_cart' => true,
				'modifier'    => 'product-card--grid',
				'sizes'       => '(min-width: 1024px) 320px, (min-width: 576px) 46vw, calc(100vw - 48px)',
			)
		);
		echo '</li>';
	}
	echo dorotape_product_list_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper.
	$GLOBALS['product'] = $original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring it.

	return (string) ob_get_clean();
}
add_filter( 'render_block_woocommerce/product-new', 'dorotape_empty_cart_products', 10, 2 );
