<?php
declare( strict_types=1 );
/**
 * Shared content sections for every product page.
 *
 * The product page design ends on the same tail as the internal pages: a USP
 * strip and the CTA triptych below Related products. Those are block layouts
 * already, but a product is not a Page, so there is nowhere to author them.
 *
 * They are authored once on an options page and rendered on every product,
 * which is the point: the tail is site furniture, not per-product content, and
 * an editor who can change it per product would eventually make 995 product
 * pages disagree with each other.
 *
 * The same `content_sections` field group drives it. The group gains this
 * options page as a second location rule rather than being cloned, so the
 * block library stays defined exactly once and a new layout appears here and
 * on Pages together.
 *
 * Reading: the options page has its own post_id rather than sharing 'option'
 * with Theme Settings, so `content_sections` here cannot collide with anything
 * stored there, and a later "category page sections" store can follow the same
 * pattern without either overwriting the other.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also what the field group's location rule
 * matches on.
 */
const DOROTAPE_PRODUCT_SECTIONS_SLUG = 'dorotape-product-sections';

/**
 * The ACF post id the sections are stored under.
 */
const DOROTAPE_PRODUCT_SECTIONS_ID = 'dorotape_product_sections';

/**
 * Register the options page under Theme Settings.
 *
 * Registered in PHP rather than as an ACF UI options page so it travels with
 * the theme: a fresh install has the page the moment the theme is active, with
 * no JSON to sync and no database row to import.
 *
 * autoload is on. ACF stores every subfield as its own wp_options row, so a
 * two-block tail is some sixty non-autoloaded reads on a page type that is the
 * most visited on the site. Autoloading folds them into the one query WordPress
 * already makes; the rows are short strings and the bundle grows by a few KB.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'Product Page Sections', 'dorotape' ),
				'menu_title'  => __( 'Product Pages', 'dorotape' ),
				'menu_slug'   => DOROTAPE_PRODUCT_SECTIONS_SLUG,
				'parent_slug' => 'theme-settings',
				'post_id'     => DOROTAPE_PRODUCT_SECTIONS_ID,
				'capability'  => 'edit_posts',
				'autoload'    => true,
				'update_button'   => __( 'Save sections', 'dorotape' ),
				'updated_message' => __( 'Product page sections saved. Every product page now shows them.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a field from the Product Page Sections options page.
 *
 * The counterpart to dorotape_setting(), for the second options store.
 *
 * @param string $name Field name.
 * @return mixed The field value, or null when ACF is not active.
 */
function dorotape_product_section_field( string $name ) {
	return function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_PRODUCT_SECTIONS_ID ) : null;
}

/**
 * True when an editor has put at least one section on the options page.
 */
function dorotape_has_product_sections(): bool {
	return dorotape_has_flexible_content( DOROTAPE_PRODUCT_SECTIONS_ID );
}

/**
 * Render the shared sections.
 *
 * The block counter starts at 1 because these sections are never the first
 * thing on the page: blocks read it to pick eager image loading for the block
 * above the fold, and on a product page that block is the gallery.
 */
function dorotape_render_product_sections(): void {
	if ( ! dorotape_has_product_sections() ) {
		return;
	}

	dorotape_render_flexible_content( DOROTAPE_PRODUCT_SECTIONS_ID, 1 );
}
