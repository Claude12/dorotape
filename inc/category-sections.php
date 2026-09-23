<?php
declare( strict_types=1 );
/**
 * Shared content sections for every product category page.
 *
 * The category page design ends on the same tail as the product page and the
 * internal pages: a USP strip and the CTA triptych. Those are block layouts
 * already, but a category is a term, not a Page, so there is nowhere to
 * author them.
 *
 * They are authored once on an options page and rendered on every category,
 * which is the point: the tail is site furniture, not per-category content,
 * and an editor who can change it per category would eventually make a
 * hundred category pages disagree with each other.
 *
 * This is the "category page sections" store inc/product-sections.php
 * anticipated, built the same way and for the same reasons. It has its own
 * post_id rather than sharing one with Theme Settings or with the product
 * store, so `content_sections` here cannot collide with either and the two
 * tails can be changed apart when the client wants them to differ.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also what the field group's location rule
 * matches on.
 */
const DOROTAPE_CATEGORY_SECTIONS_SLUG = 'dorotape-category-sections';

/**
 * The ACF post id the sections are stored under.
 */
const DOROTAPE_CATEGORY_SECTIONS_ID = 'dorotape_category_sections';

/**
 * Register the options page under Theme Settings.
 *
 * Registered in PHP rather than as an ACF UI options page so it travels with
 * the theme, and autoloaded for the same reason the product store is: ACF
 * writes a wp_options row per subfield, and a category page is asking for
 * them on every request.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Category Page Sections', 'dorotape' ),
				'menu_title'      => __( 'Category Pages', 'dorotape' ),
				'menu_slug'       => DOROTAPE_CATEGORY_SECTIONS_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_CATEGORY_SECTIONS_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save sections', 'dorotape' ),
				'updated_message' => __( 'Category page sections saved. Every category page now shows them.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a field from the Category Page Sections options page.
 *
 * The counterpart to dorotape_setting() and dorotape_product_section_field(),
 * for the third options store.
 *
 * @param string $name Field name.
 * @return mixed The field value, or null when ACF is not active.
 */
function dorotape_category_section_field( string $name ) {
	return function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_CATEGORY_SECTIONS_ID ) : null;
}

/**
 * Read a text field from the same page, with a fallback for a fresh install.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_category_page_field( string $name, string $default = '' ): string {
	$value = dorotape_category_section_field( $name );

	return is_string( $value ) && '' !== $value ? $value : $default;
}

/**
 * True when an editor has put at least one section on the options page.
 */
function dorotape_has_category_sections(): bool {
	return dorotape_has_flexible_content( DOROTAPE_CATEGORY_SECTIONS_ID );
}

/**
 * Render the shared sections.
 *
 * The block counter starts at 1 because these sections are never the first
 * thing on the page: blocks read it to pick eager image loading for the block
 * above the fold, and on a category page that block is the banner.
 */
function dorotape_render_category_sections(): void {
	if ( ! dorotape_has_category_sections() ) {
		return;
	}

	dorotape_render_flexible_content( DOROTAPE_CATEGORY_SECTIONS_ID, 1 );
}
