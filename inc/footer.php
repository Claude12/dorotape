<?php
declare( strict_types=1 );
/**
 * Site footer helpers.
 *
 * Same pattern as inc/header.php: the markup helpers live here so footer.php
 * stays readable. The footer's content (logo, blurb, social profiles,
 * newsletter strip, headings, badges and legal line) is edited on the Theme
 * Settings options page and read through dorotape_setting() in inc/acf.php.
 * Every getter below returns an empty value when its field is empty, so the
 * section it feeds collapses rather than rendering a placeholder. The link
 * columns are WordPress menus, assigned in Appearance > Menus.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon, matching the lucide set the design uses.
 *
 * Kept separate from dorotape_header_icon() because the two templates need
 * different glyphs and neither should carry the other's payload.
 *
 * @param string $name  One of instagram, linkedin, facebook, youtube, arrow-right.
 * @param string $class CSS class for the <svg> element.
 * @return string SVG markup, or an empty string for an unknown name.
 */
function dorotape_footer_icon( string $name, string $class = '' ): string {
	$paths = array(
		'instagram'   => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
		'linkedin'    => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>',
		'facebook'    => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'youtube'     => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
		'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $class ),
		$paths[ $name ]
	);
}

/**
 * The newsletter strip: its copy and the WPForms form it renders.
 *
 * Empty unless a published form is chosen and WPForms is active, which hides
 * the whole strip. A sign-up box with nowhere to send the address is worse
 * than no box.
 *
 * @return array{heading:string,body:string,form_id:int}|array{}
 */
function dorotape_footer_newsletter(): array {
	$newsletter = (array) dorotape_setting( 'footer_newsletter' );
	$form_id    = absint( $newsletter['form'] ?? 0 );

	if ( ! $form_id || ! function_exists( 'wpforms_display' ) || 'publish' !== get_post_status( $form_id ) ) {
		return array();
	}

	return array(
		'heading' => (string) ( $newsletter['heading'] ?? '' ),
		'body'    => (string) ( $newsletter['body'] ?? '' ),
		'form_id' => $form_id,
	);
}

/**
 * Cap the newsletter email at 255 characters, as the design's field does.
 *
 * WPForms has no length setting on its email field, so the attribute is added
 * here, and only to the form chosen for the footer.
 *
 * @param array $properties Field properties.
 * @param array $field      Field settings.
 * @param array $form_data  Form data.
 * @return array
 */
function dorotape_footer_newsletter_maxlength( array $properties, array $field, array $form_data ): array {
	$newsletter = (array) dorotape_setting( 'footer_newsletter' );

	if ( absint( $form_data['id'] ?? 0 ) === absint( $newsletter['form'] ?? 0 ) ) {
		$properties['inputs']['primary']['attr']['maxlength'] = 255;
	}

	return $properties;
}
add_filter( 'wpforms_field_properties_email', 'dorotape_footer_newsletter_maxlength', 10, 3 );

/**
 * The short company description under the logo.
 *
 * @return string
 */
function dorotape_footer_blurb(): string {
	return (string) dorotape_setting( 'footer_blurb' );
}

/**
 * Social profile links, in the design's order.
 *
 * @return array<int,array{network:string,label:string,url:string}>
 */
function dorotape_footer_socials(): array {
	$urls     = (array) dorotape_setting( 'footer_socials' );
	$networks = array(
		'instagram' => 'Instagram',
		'linkedin'  => 'LinkedIn',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
	);
	$socials  = array();

	foreach ( $networks as $network => $label ) {
		if ( ! empty( $urls[ $network ] ) ) {
			$socials[] = array(
				'network' => $network,
				'label'   => $label,
				'url'     => (string) $urls[ $network ],
			);
		}
	}

	return $socials;
}

/**
 * Accreditation or payment badge images.
 *
 * @param string $group Either 'accreditations' or 'payments'.
 * @return int[] Attachment IDs in display order.
 */
function dorotape_footer_badges( string $group ): array {
	$ids = (array) dorotape_setting( "footer_{$group}" );

	return array_values( array_filter( array_map( 'absint', $ids ) ) );
}

/**
 * The text after the copyright year, e.g. the registered company name.
 *
 * Falls back to the site name so the bar never reads as a bare year.
 *
 * @return string
 */
function dorotape_footer_legal_text(): string {
	$text = trim( (string) dorotape_setting( 'footer_legal_text' ) );

	return '' !== $text ? $text : get_bloginfo( 'name' ) . '.';
}

/**
 * Render one of the footer's link columns.
 *
 * Prints nothing at all when the menu location has no menu assigned, so an
 * unconfigured column collapses out of the grid rather than leaving a heading
 * over empty space.
 *
 * @param string $location Registered nav menu location.
 * @param string $heading  Column heading from Theme Settings. Empty prints the menu without one.
 * @param string $accent   Accent modifier for the link hover colour, either
 *                         'magenta' or 'cyan'.
 * @return void
 */
function dorotape_footer_column( string $location, string $heading, string $accent = 'cyan' ): void {
	if ( ! has_nav_menu( $location ) ) {
		return;
	}

	printf( '<div class="site-footer__column site-footer__column--%s">', esc_attr( $accent ) );

	if ( '' !== $heading ) {
		printf( '<p class="site-footer__column-heading">%s</p>', esc_html( $heading ) );
	}

	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => 'site-footer__menu',
			'item_class'     => 'site-footer__menu-item',
			'link_class'     => 'site-footer__menu-link',
			'depth'          => 1,
			'fallback_cb'    => false,
		)
	);

	echo '</div>';
}
