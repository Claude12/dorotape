<?php
declare( strict_types=1 );
/**
 * The account screens.
 *
 * Everything behind /my-account/: the sign-in page, the dashboard, orders, a
 * single order, downloads, addresses, account details and saved payment
 * methods. WooCommerce renders all of them from one shortcode, so they are a
 * single WordPress page wearing nine different faces, and each face needs its
 * own words at the top of it.
 *
 * The chrome is the same as the basket's and the checkout's: the shared
 * banner, then the plugin's markup inside the band every internal page uses.
 * inc/woo-pages.php owns that chrome and hands over to this file when the
 * page being viewed is an account page. The wording is on its own options
 * page because there are nine sets of it and the basket's page is already
 * four tabs long.
 *
 * No woocommerce/ template overrides here either, for the reason given in
 * inc/woo-pages.php. The markup is the plugin's; the paint is ours, in
 * assets/scss/components/woo/_account.scss.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also the field group's location rule.
 */
const DOROTAPE_ACCOUNT_SLUG = 'dorotape-account-pages';

/**
 * The ACF post id the account wording is stored under.
 */
const DOROTAPE_ACCOUNT_ID = 'dorotape_account_pages';

/**
 * Register the options page under Theme Settings.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Account pages', 'dorotape' ),
				'menu_title'      => __( 'Account pages', 'dorotape' ),
				'menu_slug'       => DOROTAPE_ACCOUNT_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_ACCOUNT_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save account pages', 'dorotape' ),
				'updated_message' => __( 'Account pages saved.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a text field from the Account pages options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_account_field( string $name, string $default = '' ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_ACCOUNT_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * Which account screen is being viewed, if any.
 *
 * The order matters twice over. Lost password is asked about before the login
 * check because it is reached while logged out and is not the sign-in form,
 * and view-order is asked about before orders because WooCommerce treats a
 * single order as a child of the list.
 *
 * @return string One of login, lost-password, dashboard, orders, view-order,
 *                downloads, addresses, address-book, details,
 *                payment-methods, or '' when this is not an account page.
 */
function dorotape_account_view(): string {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return '';
	}

	if ( is_wc_endpoint_url( 'lost-password' ) ) {
		return 'lost-password';
	}

	if ( ! is_user_logged_in() ) {
		return 'login';
	}

	$endpoints = array(
		'view-order'      => 'view-order',
		'orders'          => 'orders',
		'downloads'       => 'downloads',
		'edit-address'    => 'addresses',
		'address-book'    => 'address-book',
		'edit-account'    => 'details',
		'payment-methods' => 'payment-methods',
	);

	foreach ( $endpoints as $endpoint => $view ) {
		if ( is_wc_endpoint_url( $endpoint ) ) {
			return $view;
		}
	}

	return 'dashboard';
}

/**
 * The field name prefix for a view.
 *
 * The views are named with hyphens because that is what they wear as a CSS
 * modifier; ACF field names are underscored.
 *
 * @param string $view A dorotape_account_view() value.
 */
function dorotape_account_field_prefix( string $view ): string {
	return 'account_' . str_replace( '-', '_', $view );
}

/**
 * What each screen is titled before an editor has changed it.
 *
 * Written here as well as in the field group's defaults so the pages read
 * properly on a fresh install, which is how every other options-backed page
 * in the theme does it.
 *
 * One line each. These pages used to open on the same banner the internal
 * pages wear, with an eyebrow and a standfirst above the fold; a basket is a
 * job someone came here to finish, not a page to be introduced, so the banner
 * came off and the heading is all that is left of it.
 *
 * @return array<string, string>
 */
function dorotape_account_defaults(): array {
	return array(
		'login'           => __( 'Sign in', 'dorotape' ),
		'lost-password'   => __( 'Reset your password', 'dorotape' ),
		'dashboard'       => __( 'Your account', 'dorotape' ),
		'orders'          => __( 'Your orders', 'dorotape' ),
		'view-order'      => __( 'Order details', 'dorotape' ),
		'downloads'       => __( 'Your downloads', 'dorotape' ),
		'addresses'       => __( 'Your addresses', 'dorotape' ),
		'address-book'    => __( 'Your address book', 'dorotape' ),
		'details'         => __( 'Your details', 'dorotape' ),
		'payment-methods' => __( 'Saved payment methods', 'dorotape' ),
	);
}

/**
 * This screen's heading.
 */
function dorotape_account_heading(): string {
	$view = dorotape_account_view();

	if ( '' === $view ) {
		return '';
	}

	$defaults = dorotape_account_defaults();

	return dorotape_account_field(
		dorotape_account_field_prefix( $view ) . '_heading',
		$defaults[ $view ] ?? ''
	);
}

/**
 * The classes on the band the account screens sit in.
 *
 * One shape for all nine, because they are one page: the background would
 * otherwise change under someone moving from their orders to their addresses.
 * The view rides along as a modifier so a screen that needs its own layout,
 * such as the two columns of the sign-in page, can ask for it.
 */
function dorotape_account_band_classes(): string {
	$view  = dorotape_account_view();
	$shape = dorotape_background_shape_value( dorotape_account_field( 'account_content_shape', 'none' ) );

	return 'category-grid-block category-grid-block--glow-soft category-grid-block--tight woo-page woo-page--account woo-page--account-' . $view . dorotape_background_shape_class( $shape );
}

/**
 * The band's background shape, for the template that opened the section.
 */
function dorotape_account_band_shape(): void {
	dorotape_background_shape( dorotape_background_shape_value( dorotape_account_field( 'account_content_shape', 'none' ) ) );
}

/**
 * The shortcut grid under the dashboard's greeting.
 *
 * WooCommerce's dashboard is two sentences with the destinations buried in
 * them as links, which on a wide screen is a paragraph floating in an empty
 * column. The same destinations are drawn here as cards, in the shape the
 * ranges grid uses, so the dashboard looks like a page rather than a note.
 *
 * Hooked rather than templated: woocommerce_account_dashboard fires inside
 * the plugin's own dashboard.php, so nothing is overridden and the file keeps
 * updating with WooCommerce, which is the rule the rest of these pages follow.
 *
 * The labels are WooCommerce's own menu, not wording of ours, so a plugin
 * adding an endpoint gets a card for free and nothing has to be kept in step
 * by hand. Logging out is not a shortcut, and the dashboard is the page we
 * are already on, so neither gets a card.
 */
function dorotape_account_dashboard_links(): void {
	if ( ! function_exists( 'wc_get_account_menu_items' ) ) {
		return;
	}

	$items = wc_get_account_menu_items();

	unset( $items['dashboard'], $items['customer-logout'] );

	if ( empty( $items ) ) {
		return;
	}
	?>
	<nav class="account-shortcuts" aria-label="<?php esc_attr_e( 'Account shortcuts', 'dorotape' ); ?>">
		<?php foreach ( $items as $dt_endpoint => $dt_label ) : ?>
			<a class="account-shortcuts__card" href="<?php echo esc_url( wc_get_account_endpoint_url( $dt_endpoint ) ); ?>">
				<span class="account-shortcuts__label"><?php echo esc_html( $dt_label ); ?></span>
				<?php echo dorotape_arrow_icon( 'link__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
}
add_action( 'woocommerce_account_dashboard', 'dorotape_account_dashboard_links', 20 );

/**
 * Drop Downloads from the menu, and so from the cards above, for anyone with
 * nothing to download. Nothing in the shop is downloadable, so for now that is
 * everyone, but the tab comes back by itself if that ever changes.
 *
 * @param array $items
 * @return array
 */
add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
	if ( isset( $items['downloads'] ) && ! wc_get_customer_available_downloads( get_current_user_id() ) ) {
		unset( $items['downloads'] );
	}
	return $items;
} );
