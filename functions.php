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
	 * One location per place a menu appears, named for that place so an
	 * editor in Appearance > Menus can tell where each one shows.
	 *
	 * primary is the header's main menu (inc/header-menu.php): sections on
	 * the menu bar from 1024px, the burger menu below. shortcuts is the short
	 * list of category links at the right of that bar. The footer has three
	 * link columns and a legal row, one location each.
	 */
	register_nav_menus(
		array(
			'primary'         => esc_html__( 'Header: Main menu (menu bar on desktop, burger menu on mobile)', 'dorotape' ),
			'shortcuts'       => esc_html__( 'Header: Category shortcuts (right of the menu bar; about 4 fit, extras hide on smaller screens)', 'dorotape' ),
			'footer-products' => esc_html__( 'Footer: Products column', 'dorotape' ),
			'footer-support'  => esc_html__( 'Footer: Support column', 'dorotape' ),
			'footer-about'    => esc_html__( 'Footer: About column', 'dorotape' ),
			'footer-legal'    => esc_html__( 'Footer: Legal links (bottom row)', 'dorotape' ),
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
	 * The basket, checkout, wishlist and account screens' own CSS, about a
	 * third of the theme's, which no other page uses. Built from
	 * assets/scss/woo-pages.scss; after the main sheet so it keeps the
	 * cascade position it had inside it.
	 */
	if ( function_exists( 'dorotape_woo_page' ) && '' !== dorotape_woo_page() ) {
		wp_enqueue_style(
			'dorotape-woo-pages',
			get_template_directory_uri() . '/dist/css/woo-pages.css',
			array( 'dorotape-design-system' ),
			dorotape_asset_version( '/dist/css/woo-pages.css' )
		);
	}

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

/*
 * Marks the page as JS-capable before anything paints. The scroll reveal in
 * utilities/_animations.scss only hides an [animate] section under `.js`, so
 * without JS (or while main.js has failed) the content is simply visible
 * instead of stuck at opacity 0.
 */
function dorotape_js_class() {
	echo "<script>document.documentElement.classList.add('js')</script>\n";
}
add_action( 'wp_head', 'dorotape_js_class', 0 );

/*
 * The one font file, fetched alongside the stylesheet rather than after it
 * has been parsed, so text paints in Nunito sooner and swaps less. The URL
 * must match the @font-face src in base/_fonts.scss exactly (no version
 * query), or the browser downloads it twice.
 */
function dorotape_preload_font() {
	printf(
		"<link rel=\"preload\" href=\"%s\" as=\"font\" type=\"font/woff2\" crossorigin>\n",
		esc_url( get_template_directory_uri() . '/fonts/nunito-variable.woff2' )
	);
}
add_action( 'wp_head', 'dorotape_preload_font', 1 );

/*
 * Strip Gutenberg's CSS from the frontend. classic-theme-styles is the
 * default button and file block styling, which the theme replaces.
 *
 * global-styles is the preset sheet (about 9KB inline). Classic-editor pages
 * never read it, so it goes there. A page built from blocks keeps it: the
 * WooCommerce Cart and Checkout blocks size their text from its font-size
 * presets, and without them the basket's product names grow a step. WordPress
 * can enqueue it again from the footer, hence that hook goes too.
 */
function dorotape_remove_block_styles() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'classic-theme-styles' );

	if ( ! ( is_singular() && has_blocks( get_queried_object_id() ) ) ) {
		wp_dequeue_style( 'global-styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}
}
add_action( 'wp_enqueue_scripts', 'dorotape_remove_block_styles', 100 );

/*
 * YITH Wishlist enqueues its React add-to-wishlist button on every page,
 * which pulls in react, react-dom, lodash, moment and a dozen wp-* packages
 * (about 300KB of JS) and fires a REST call that 401s for guests. This site
 * only shows that button on the single product page, so everywhere else it
 * goes. The wishlist page itself runs on YITH's separate jQuery script and
 * keeps it. Filter `dorotape_load_wishlist_button` to true on any other page
 * that starts showing the button.
 */
function dorotape_trim_wishlist_assets() {
	if ( apply_filters( 'dorotape_load_wishlist_button', function_exists( 'is_product' ) && is_product() ) ) {
		return;
	}
	wp_dequeue_script( 'yith-wcwl-add-to-wishlist' );
	wp_dequeue_style( 'yith-wcwl-add-to-wishlist' );
}
add_action( 'wp_enqueue_scripts', 'dorotape_trim_wishlist_assets', 100 );

/*
 * On the product page that button asks YITH for the visitor's lists, and YITH
 * answers a guest who has never added anything with a 401, which shows as a
 * console error on every product view. Such a guest has no lists, so answer
 * that one case with the empty list YITH itself would return. Signed-in users
 * and guests with a wishlist session still go to YITH as before.
 */
function dorotape_wishlist_guest_lists( $result, $server, $request ) {
	if (
		null !== $result
		|| 'GET' !== $request->get_method()
		|| ! preg_match( '#^/yith/wishlist/v1/lists/?$#', $request->get_route() )
		|| is_user_logged_in()
		|| ! function_exists( 'YITH_WCWL_Session' )
		|| YITH_WCWL_Session()->maybe_get_session_id()
	) {
		return $result;
	}
	return rest_ensure_response( array( 'lists' => array() ) );
}
add_filter( 'rest_pre_dispatch', 'dorotape_wishlist_guest_lists', 10, 3 );

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
require get_template_directory() . '/inc/webp.php';
require get_template_directory() . '/inc/admin.php';
require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/header.php';
require get_template_directory() . '/inc/header-menu.php';
require get_template_directory() . '/inc/toast.php';
require get_template_directory() . '/inc/footer.php';
require get_template_directory() . '/inc/background-shape.php';
require get_template_directory() . '/inc/product-card.php';
require get_template_directory() . '/inc/product-row.php';
require get_template_directory() . '/inc/breadcrumbs.php';
require get_template_directory() . '/inc/page-banner.php';
require get_template_directory() . '/inc/rollsize.php';
require get_template_directory() . '/inc/pricing.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/single-product.php';
require get_template_directory() . '/inc/product-category.php';
require get_template_directory() . '/inc/category-filters.php';
require get_template_directory() . '/inc/link-card.php';
require get_template_directory() . '/inc/shop-filters.php';
require get_template_directory() . '/inc/shop.php';
require get_template_directory() . '/inc/search.php';
require get_template_directory() . '/inc/404.php';
require get_template_directory() . '/inc/blog.php';
require get_template_directory() . '/inc/woo-pages.php';
require get_template_directory() . '/inc/account-pages.php';
require get_template_directory() . '/inc/stock.php';
require get_template_directory() . '/inc/poa.php';
require get_template_directory() . '/inc/cutsize.php';
require get_template_directory() . '/inc/quickadd.php';
require get_template_directory() . '/inc/reorder.php';
require get_template_directory() . '/inc/legacy-orders.php';
require get_template_directory() . '/inc/purchase-order.php';
require get_template_directory() . '/inc/vat.php';
require get_template_directory() . '/inc/pay-on-account.php';
require get_template_directory() . '/inc/payment-methods.php';
require get_template_directory() . '/inc/credit-limit.php';
require get_template_directory() . '/inc/account-pending.php';
require get_template_directory() . '/inc/shipping.php';
require get_template_directory() . '/inc/collection.php';
require get_template_directory() . '/inc/dispatch.php';
require get_template_directory() . '/inc/address-book.php';
require get_template_directory() . '/inc/address-book-account.php';
require get_template_directory() . '/inc/address-book-checkout.php';
require get_template_directory() . '/inc/rewards.php';
