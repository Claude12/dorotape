<?php
/**
 * Dorotape theme functions and definitions
 *
 * @package dorotape
 */

define( 'DOROTAPE_VERSION', '1.0.4' );

/**
 * Cache-buster for a theme asset: the file's own last-modified time. Editing
 * scaffold.js/scaffold.css (or any enqueued asset) then invalidates browser
 * caches on the next request automatically — no need to remember to bump
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
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 100,
			'width'       => 300,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);

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

	register_nav_menus(
		array(
			'primary'   => esc_html__( 'Primary Navigation', 'dorotape' ),
			'secondary' => esc_html__( 'Secondary Navigation', 'dorotape' ),
			'footer'    => esc_html__( 'Footer Navigation', 'dorotape' ),
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
	wp_enqueue_style( 'dorotape-style', get_stylesheet_uri(), array(), dorotape_asset_version( '/style.css' ) );

	// TEMP Sprint 1 scaffold styles — remove when real design system lands.
	wp_enqueue_style(
		'dorotape-scaffold',
		get_template_directory_uri() . '/css/scaffold.css',
		array( 'dorotape-style' ),
		dorotape_asset_version( '/css/scaffold.css' )
	);

	wp_enqueue_script( 'dorotape-navigation', get_template_directory_uri() . '/js/navigation.js', array(), dorotape_asset_version( '/js/navigation.js' ), true );

	// TEMP Sprint 1 scaffold JS — remove when real frontend lands.
	wp_enqueue_script(
		'dorotape-scaffold',
		get_template_directory_uri() . '/js/scaffold.js',
		array( 'dorotape-navigation', 'jquery' ),
		dorotape_asset_version( '/js/scaffold.js' ),
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

require get_template_directory() . '/inc/cleanup.php';
require get_template_directory() . '/inc/admin.php';
require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/pricing.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/poa.php';
require get_template_directory() . '/inc/cutsize.php';
require get_template_directory() . '/inc/quickadd.php';
require get_template_directory() . '/inc/purchase-order.php';
require get_template_directory() . '/inc/pay-on-account.php';
require get_template_directory() . '/inc/collection.php';
