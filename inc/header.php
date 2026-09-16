<?php
declare( strict_types=1 );
/**
 * Site header helpers.
 *
 * Small data and markup helpers for header.php, kept out of the template so
 * the template stays readable markup. The header's content (logo, notice,
 * phone, trade account link, quote button, search placeholders) is edited on
 * the Theme Settings options page and read through dorotape_setting() in
 * inc/acf.php. The category and quick links are WordPress menus.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon, matching the lucide set the design uses.
 *
 * These are inlined rather than loaded as a sprite or an icon font because
 * there are six of them and they all sit in the header: a separate request
 * for six icons in the critical path costs more than the ~1KB of markup.
 * `currentcolor` lets each one inherit the hover colour of its link.
 *
 * @param string $name One of phone, user, cart, search, menu, close.
 * @param string $class CSS class for the <svg> element.
 * @return string Escaped-safe SVG markup, or an empty string for an unknown name.
 */
function dorotape_header_icon( string $name, string $class = '' ): string {
	$paths = array(
		'phone'  => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>',
		'user'   => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'cart'   => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
		'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'menu'   => '<path d="M4 12h16"/><path d="M4 6h16"/><path d="M4 18h16"/>',
		'close'  => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
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
 * The delivery notice in the utility bar.
 *
 * @return string Empty when the field is empty, which hides the notice.
 */
function dorotape_header_notice(): string {
	return trim( (string) dorotape_setting( 'header_notice' ) );
}

/**
 * Sales phone number for the utility bar and the drawer.
 *
 * The editor types the number the way it should read; the tel: link keeps
 * only the digits and a leading plus, and drops the "(0)" of a UK number
 * written in international form, which is not dialled after +44.
 *
 * @return array{display:string,href:string} Empty strings when no number is set.
 */
function dorotape_header_phone(): array {
	$display = trim( (string) dorotape_setting( 'header_phone' ) );
	$digits  = preg_replace( '/(?!^\+)[^\d]/', '', str_replace( '(0)', '', $display ) );

	if ( '' === $display || '' === (string) $digits ) {
		return array(
			'display' => '',
			'href'    => '',
		);
	}

	return array(
		'display' => $display,
		'href'    => 'tel:' . $digits,
	);
}

/**
 * Number of items currently in the WooCommerce cart.
 *
 * Guarded because the header renders on sites and requests where WooCommerce
 * has not booted its session (WP-CLI, the REST API, a deactivated plugin).
 *
 * @return int
 */
function dorotape_header_cart_count(): int {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * URL for a WooCommerce page, falling back to the home page.
 *
 * @param string $which One of cart, account.
 * @return string
 */
function dorotape_header_wc_url( string $which ): string {
	if ( 'cart' === $which && function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}

	if ( 'account' === $which && function_exists( 'wc_get_page_permalink' ) ) {
		return (string) wc_get_page_permalink( 'myaccount' );
	}

	return home_url( '/' );
}

/**
 * The header product search field.
 *
 * Rendered twice (inline on tablet and up, and inside the mobile drawer), so
 * it lives in one function rather than being duplicated in the template and
 * drifting. Each copy needs its own input id for its <label>.
 *
 * The two copies carry different placeholders, both from Theme Settings:
 * the full one inline, a shorter one in the narrow drawer.
 *
 * @param string $id          Unique id for the input.
 * @param string $placeholder Placeholder text for the field. Empty leaves
 *                            FiboSearch's own placeholder setting in charge.
 * @return void
 */
function dorotape_header_search_field( string $id, string $placeholder ): void {
	?>
	<div class="site-header__search">
		<?php
		// The client's note on the old header was that it "only searched on
		// submit, not showing matches as a predictive and narrowing list".
		// FiboSearch is the plugin they installed to do exactly that, so the
		// theme hands the box over to it and only styles the frame.
		//
		// The fallback runs when the plugin is deactivated. Without it the
		// header would lose its search silently.
		if ( shortcode_exists( 'fibosearch' ) ) {
			// FiboSearch has one site-wide placeholder setting and no shortcode
			// attribute for it, but it reads its labels through a filter on
			// every render. Filtering for this one render gives each copy its
			// own text without changing the plugin's setting anywhere else.
			$dt_set_placeholder = static function ( $labels ) use ( $placeholder ) {
				$labels['search_placeholder'] = $placeholder;
				return $labels;
			};
			if ( '' !== $placeholder ) {
				add_filter( 'dgwt/wcas/labels', $dt_set_placeholder, PHP_INT_MAX );
			}
			echo do_shortcode( '[fibosearch]' );
			remove_filter( 'dgwt/wcas/labels', $dt_set_placeholder, PHP_INT_MAX );
		} else {
			?>
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Search products', 'dorotape' ); ?>
				</label>
				<input
					id="<?php echo esc_attr( $id ); ?>"
					class="site-header__search-field"
					type="search"
					name="s"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					autocomplete="off"
				>
				<input type="hidden" name="post_type" value="product">
			</form>
			<?php
			// Only the fallback needs an icon drawn for it. FiboSearch renders
			// its own magnifier inside a submit button, and echoing this one as
			// well put two of them in the same field.
			echo dorotape_header_icon( 'search', 'site-header__search-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup from dorotape_header_icon().
		}
		?>
	</div>
	<?php
}
