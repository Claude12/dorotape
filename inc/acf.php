<?php
declare( strict_types=1 );
/**
 * ACF setup: local JSON path, flexible-content renderer, Theme Settings
 * readers, dependency notice.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Local JSON save point.
 *
 * Explicit even though it matches ACF's own default, so the sync location is
 * documented rather than implied.
 */
add_filter(
	'acf/settings/save_json',
	function () {
		return get_stylesheet_directory() . '/acf-json';
	}
);

if ( ! function_exists( 'dorotape_render_flexible_content' ) ) :
	/**
	 * Renders the "content_sections" ACF flexible content field for a post.
	 *
	 * Runs the field through ACF's own have_rows()/the_row() loop rather than
	 * pulling the raw array via get_field(), so ACF's row context is active
	 * inside each block template. That is what makes get_sub_field() work
	 * there. Each layout name (underscores) maps to a template file of the
	 * same name (hyphens) in inc/blocks/, e.g. `image_text_block` =>
	 * inc/blocks/image-text-block.php.
	 *
	 * To add a block: (1) add a layout to the `content_sections` field group
	 * in ACF, (2) create the matching inc/blocks/{name}.php template using
	 * get_sub_field() for every field, (3) add a matching SCSS partial under
	 * assets/scss/components/ and @use it in style.scss.
	 *
	 * @param int|string|null $post_id Post to read the field from. Defaults to the current post.
	 */
	function dorotape_render_flexible_content( $post_id = null ): void {
		if ( ! function_exists( 'have_rows' ) ) {
			return;
		}

		$block_index = 0;

		while ( have_rows( 'content_sections', $post_id ) ) :
			the_row();

			$template = str_replace( '_', '-', (string) get_row_layout() );

			// Block templates read this to decide eager vs lazy image loading:
			// only the first block on the page is above the fold.
			set_query_var( 'block_index', $block_index );
			get_template_part( 'inc/blocks/' . $template );

			++$block_index;
		endwhile;

		// The per-block `divider` switch only ever draws a rule above its own
		// block, so the last section has nothing to hang a closing rule from.
		// The About page ends on one, the homepage does not, so it is a
		// page-level choice rather than something the renderer decides.
		if ( $block_index > 0 && get_field( 'content_sections_end_rule', $post_id ) ) {
			echo '<div class="aurora-rule" aria-hidden="true"></div>';
		}
	}
endif;

if ( ! function_exists( 'dorotape_has_flexible_content' ) ) :
	/**
	 * True when the given post has at least one flexible-content block.
	 *
	 * Templates use this to decide between the block renderer and the classic
	 * editor content. Dorotape keeps the classic editor active (unlike a
	 * blocks-only build), so a page with no blocks must still render its
	 * post_content rather than coming out empty.
	 *
	 * @param int|string|null $post_id Post to check. Defaults to the current post.
	 */
	function dorotape_has_flexible_content( $post_id = null ): bool {
		if ( ! function_exists( 'have_rows' ) ) {
			return false;
		}

		$sections = get_field( 'content_sections', $post_id );

		return is_array( $sections ) && ! empty( $sections );
	}
endif;

/**
 * Read a field from the Theme Settings options page.
 *
 * Site-wide content (logo, header copy, footer content) lives there, in
 * acf-json/group_dorotape_theme_settings.json, the way page content lives in
 * `content_sections`.
 *
 * @param string $name Field name on the Theme Settings options page.
 * @return mixed The field value, or null when ACF is not active.
 */
function dorotape_setting( string $name ) {
	return function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
}

/**
 * A link field from Theme Settings, normalised.
 *
 * @param string $name Link field name on the Theme Settings options page.
 * @return array{url:string,title:string,target:string}|array{} Empty when the URL or the label is missing.
 */
function dorotape_setting_link( string $name ): array {
	$link  = (array) dorotape_setting( $name );
	$url   = (string) ( $link['url'] ?? '' );
	$title = (string) ( $link['title'] ?? '' );

	if ( '' === $url || '' === $title ) {
		return array();
	}

	return array(
		'url'    => $url,
		'title'  => $title,
		'target' => (string) ( $link['target'] ?? '' ),
	);
}

/**
 * The site logo from Theme Settings, linked to the home page.
 *
 * Keeps WordPress's own `custom-logo-link` / `custom-logo` classes so the
 * header and footer styles written against the_custom_logo() still apply.
 * The image's alt text names the link; the site name stands in when it has
 * none, so the link is never unlabelled.
 *
 * @return string Logo markup, or an empty string when no logo is set.
 */
function dorotape_site_logo(): string {
	$logo_id = absint( dorotape_setting( 'site_logo' ) );

	if ( ! $logo_id ) {
		return '';
	}

	$alt   = trim( (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) );
	$image = wp_get_attachment_image(
		$logo_id,
		'full',
		false,
		array(
			'class'    => 'custom-logo',
			'alt'      => '' !== $alt ? $alt : get_bloginfo( 'name', 'display' ),
			'loading'  => false,
			'decoding' => 'async',
		)
	);

	if ( '' === $image ) {
		return '';
	}

	return sprintf(
		'<a href="%s" class="custom-logo-link" rel="home"%s>%s</a>',
		esc_url( home_url( '/' ) ),
		is_front_page() ? ' aria-current="page"' : '',
		$image
	);
}

/**
 * Warn admins if Advanced Custom Fields Pro isn't active, because every block
 * template reads its content through ACF and renders blank without it.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'activate_plugins' ) || class_exists( 'ACF' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'The Dorotape theme requires Advanced Custom Fields (Pro) to be installed and active. Page content sections are driven by ACF flexible content fields.', 'dorotape' )
		);
	}
);

/**
 * Hide the classic editor on pages that are built from ACF blocks.
 *
 * Pages are authored with the `content_sections` flexible content field, and
 * the Rich Text block (`rte_block`) is where ordinary prose goes, so the
 * editor box above the block list is one more place content could hide.
 *
 * It is removed only from pages whose editor is already empty. Anything with
 * existing content keeps its editor, which covers the WooCommerce Cart and
 * Checkout block pages, My Account with its shortcode, and the older classic
 * pages (terms, privacy, delivery, returns) until their copy is moved into
 * Rich Text blocks. Emptying a page's editor and reloading is what retires it.
 */
function dorotape_hide_empty_page_editor(): void {
	$dt_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $dt_screen || 'page' !== $dt_screen->id || 'post' !== $dt_screen->base ) {
		return;
	}

	// Reading the post being edited off the request. This only decides whether
	// a metabox is shown, so there is nothing to verify a nonce against.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$dt_post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;

	if ( $dt_post_id ) {
		$dt_post = get_post( $dt_post_id );

		if ( $dt_post && '' !== trim( (string) $dt_post->post_content ) ) {
			return;
		}
	}

	remove_post_type_support( 'page', 'editor' );
}
add_action( 'current_screen', 'dorotape_hide_empty_page_editor' );
