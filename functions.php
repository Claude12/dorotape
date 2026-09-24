<?php
/**
 * Dorotape theme functions and definitions
 *
 * @package dorotape
 */

define( 'DOROTAPE_VERSION', '1.0.4' );

/**
 * Cache-buster for a theme asset: the file's own last-modified time.
 * Rebuilding dist/js/main.js or dist/css/style.css (or editing any other
 * enqueued asset) then invalidates browser caches on the next request — no need to remember to bump
 * DOROTAPE_VERSION, which is how a fixed quick-add bug once still looked
 * "not working" from a stale cached copy.
 */
function dorotape_asset_version( string $relative_path ): string {
	$path = get_template_directory() . $relative_path;
	return file_exists( $path ) ? (string) filemtime( $path ) : DOROTAPE_VERSION;
}

function dorotape_setup() {
	load_theme_textdomain( 'dorotape', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	// No custom-logo support: the logo is set in Theme Settings (ACF), so the
	// Customizer does not offer a second control that the templates ignore.

	// WooCommerce
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 768,
			'single_image_width'    => 1024,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 1,
				'max_columns'     => 6,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	/*
	 * The footer has three link columns and a legal row rather than one menu.
	 * footer-products is optional: with nothing assigned to it the Products
	 * column renders the primary menu, which is what the design shows (the
	 * same eight categories as the header).
	 *
	 * 'footer' stays registered for the single-menu footer that shipped
	 * before this rebuild. Nothing is assigned to it, and it can be dropped
	 * once that is confirmed on the live site too.
	 */
	register_nav_menus(
		array(
			'primary'         => esc_html__( 'Primary Navigation', 'dorotape' ),
			'secondary'       => esc_html__( 'Secondary Navigation', 'dorotape' ),
			'footer-products' => esc_html__( 'Footer: Products', 'dorotape' ),
			'footer-support'  => esc_html__( 'Footer: Support', 'dorotape' ),
			'footer-about'    => esc_html__( 'Footer: About', 'dorotape' ),
			'footer-legal'    => esc_html__( 'Footer: Legal', 'dorotape' ),
			'footer'          => esc_html__( 'Footer Navigation (legacy)', 'dorotape' ),
		)
	);

	add_image_size( 'dorotape-hero', 1920, 800, true );
	add_image_size( 'dorotape-product-card', 600, 600, true );
	add_image_size( 'dorotape-product-thumb', 300, 300, true );
	add_image_size( 'dorotape-blog-card', 800, 500, true );
}
add_action( 'after_setup_theme', 'dorotape_setup' );

function dorotape_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'dorotape_content_width', 1280 );
}
add_action( 'after_setup_theme', 'dorotape_content_width', 0 );

function dorotape_scripts() {
	/*
	 * Design system. Compiled from assets/scss by `cd assets && npx gulp build`.
	 * Fonts are self-hosted, so there is no Google Fonts request: the @font-face
	 * lives in this stylesheet and the woff2 in /fonts/.
	 *
	 * This is the whole front end. style.css only carries the theme metadata
	 * and is not enqueued; the Sprint 1 scaffold (css/scaffold.css) has been
	 * removed, and what the client signed off on from it now lives in
	 * assets/scss/components/woo/.
	 *
	 * It loads after any plugin stylesheet that registers on the default
	 * priority, which is what lets the FiboSearch overrides in
	 * layout/_header.scss win on source order at equal specificity rather
	 * than with !important. Moving this enqueue earlier would break those.
	 */
	wp_enqueue_style(
		'dorotape-design-system',
		get_template_directory_uri() . '/dist/css/style.css',
		array(),
		dorotape_asset_version( '/dist/css/style.css' )
	);

	/*
	 * All frontend JS. Bundled by webpack from assets/js/main.js, which boots
	 * each feature module in assets/js/lib/ independently so a throw in one
	 * cannot stop the others from starting.
	 *
	 * jQuery is a real dependency, not a convenience: the WooCommerce feature
	 * modules listen for WC's own jQuery events (show_variation, reset_data,
	 * updated_wc_div) and there is no native equivalent to hook.
	 */
	wp_enqueue_script(
		'dorotape-design-system',
		get_template_directory_uri() . '/dist/js/main.js',
		array( 'jquery' ),
		dorotape_asset_version( '/dist/js/main.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'dorotape_scripts' );

// Strip Gutenberg block library CSS from the frontend
function dorotape_remove_block_styles() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
}
add_action( 'wp_enqueue_scripts', 'dorotape_remove_block_styles', 100 );

// Disable WooCommerce default stylesheet — custom CSS only
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// Remove WP emoji scripts
function dorotape_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'dorotape_disable_emojis' );

require get_template_directory() . '/inc/icons.php';
require get_template_directory() . '/inc/acf.php';
require get_template_directory() . '/inc/product-sections.php';
require get_template_directory() . '/inc/category-sections.php';
require get_template_directory() . '/inc/cleanup.php';
require get_template_directory() . '/inc/admin.php';
require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/header.php';
require get_template_directory() . '/inc/footer.php';
require get_template_directory() . '/inc/background-shape.php';
require get_template_directory() . '/inc/product-card.php';
require get_template_directory() . '/inc/product-row.php';
require get_template_directory() . '/inc/breadcrumbs.php';
require get_template_directory() . '/inc/rollsize.php';
require get_template_directory() . '/inc/pricing.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/single-product.php';
require get_template_directory() . '/inc/product-category.php';
require get_template_directory() . '/inc/category-filters.php';
require get_template_directory() . '/inc/link-card.php';
require get_template_directory() . '/inc/search.php';
require get_template_directory() . '/inc/404.php';
require get_template_directory() . '/inc/stock.php';
require get_template_directory() . '/inc/poa.php';
require get_template_directory() . '/inc/cutsize.php';
require get_template_directory() . '/inc/quickadd.php';
require get_template_directory() . '/inc/reorder.php';
require get_template_directory() . '/inc/purchase-order.php';
require get_template_directory() . '/inc/vat.php';
require get_template_directory() . '/inc/pay-on-account.php';
require get_template_directory() . '/inc/credit-limit.php';
require get_template_directory() . '/inc/account-pending.php';
require get_template_directory() . '/inc/shipping.php';
require get_template_directory() . '/inc/collection.php';
require get_template_directory() . '/inc/dispatch.php';
require get_template_directory() . '/inc/address-book.php';
require get_template_directory() . '/inc/address-book-account.php';
require get_template_directory() . '/inc/address-book-checkout.php';
require get_template_directory() . '/inc/rewards.php';
