<?php
/**
 * The header for our theme.
 *
 * Built from the signed-off design's SiteHeader component
 * (v2 Final Homepage Design/src/components/site/SiteHeader.tsx).
 *
 * Three stacked rows:
 *   utility  - delivery notice + phone/account/basket, from tablet up
 *   main     - logo, search, quick links, quote button, always
 *   nav      - the product categories, from 1280px up
 * Below 1280px the categories move into a drawer under the main row, which
 * also carries the search field and the utility links.
 *
 * Styling is assets/scss/layout/_header.scss, behaviour assets/js/lib/header.js.
 * Helpers are inc/header.php.
 *
 * @package dorotape
 */

$dorotape_phone      = dorotape_header_phone();
$dorotape_cart_count = dorotape_header_cart_count();
$dorotape_account    = dorotape_header_wc_url( 'account' );
$dorotape_cart       = dorotape_header_wc_url( 'cart' );
$dorotape_notice     = dorotape_header_notice();
$dorotape_trade      = dorotape_setting_link( 'header_trade_account' );
$dorotape_quote      = dorotape_setting_link( 'header_quote' );
$dorotape_logo       = dorotape_site_logo();
$dorotape_search     = (string) dorotape_setting( 'header_search_placeholder' );
$dorotape_search_sm  = (string) dorotape_setting( 'header_search_placeholder_short' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'dorotape' ); ?></a>

	<header id="masthead" class="site-header js-site-header">

		<div class="site-header__utility">
			<div class="container site-header__utility-inner">
				<?php if ( '' !== $dorotape_notice ) : ?>
					<p class="site-header__notice"><?php echo esc_html( $dorotape_notice ); ?></p>
				<?php endif; ?>

				<div class="site-header__utility-links">
					<?php if ( '' !== $dorotape_phone['display'] ) : ?>
						<a class="site-header__utility-link" href="<?php echo esc_url( $dorotape_phone['href'] ); ?>">
							<?php echo dorotape_header_icon( 'phone', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( $dorotape_phone['display'] ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $dorotape_trade ) : ?>
						<a class="site-header__utility-link" href="<?php echo esc_url( $dorotape_trade['url'] ); ?>"<?php echo $dorotape_trade['target'] ? ' target="' . esc_attr( $dorotape_trade['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo dorotape_header_icon( 'user', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( $dorotape_trade['title'] ); ?>
						</a>
					<?php endif; ?>

					<?php
					// The design shows "Login" because it has no concept of a
					// session. Once someone is signed in that label is wrong and
					// the link takes them somewhere they already are, so it
					// becomes their account instead.
					?>
					<a class="site-header__utility-link site-header__utility-link--strong" href="<?php echo esc_url( $dorotape_account ); ?>">
						<?php echo dorotape_header_icon( 'user', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php is_user_logged_in() ? esc_html_e( 'My account', 'dorotape' ) : esc_html_e( 'Login', 'dorotape' ); ?>
					</a>

					<a class="site-header__utility-link site-header__utility-link--strong" href="<?php echo esc_url( $dorotape_cart ); ?>">
						<?php echo dorotape_header_icon( 'cart', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						printf(
							/* translators: %d: number of items in the basket. */
							esc_html__( 'Basket (%d)', 'dorotape' ),
							(int) $dorotape_cart_count
						);
						?>
					</a>
				</div>
			</div>
		</div><!-- .site-header__utility -->

		<div class="site-header__main">
			<div class="container site-header__main-inner">

				<div class="site-header__branding">
					<?php
					echo $dorotape_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in dorotape_site_logo().
					if ( '' === $dorotape_logo ) :
						?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__name" rel="home">
							<?php bloginfo( 'name' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="site-header__centre">
					<?php dorotape_header_search_field( 'dt-search-desktop', $dorotape_search ); ?>

					<?php
					// The design's Applications / Sustainability / Support links.
					// Those pages do not exist yet, so this renders whatever is
					// in the Secondary menu and nothing at all when that slot is
					// empty: a hard-coded trio would be three 404s.
					if ( has_nav_menu( 'secondary' ) ) :
						?>
						<nav class="site-header__quick-links" aria-label="<?php esc_attr_e( 'Secondary menu', 'dorotape' ); ?>">
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'secondary',
									'menu_id'        => 'secondary-menu',
									'menu_class'     => 'site-header__quick-links-list',
									'item_class'     => 'site-header__quick-links-item',
									'link_class'     => 'site-header__quick-link',
									'container'      => false,
									'depth'          => 1,
									'fallback_cb'    => false,
								)
							);
							?>
						</nav>
					<?php endif; ?>
				</div>

				<div class="site-header__actions">
					<button type="button"
						class="site-header__icon-button site-header__search-toggle js-header-search-toggle"
						aria-controls="site-header-drawer" aria-expanded="false"
						aria-label="<?php esc_attr_e( 'Search products', 'dorotape' ); ?>">
						<?php echo dorotape_header_icon( 'search', 'site-header__button-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>

					<?php if ( $dorotape_quote ) : ?>
						<a class="site-header__quote" href="<?php echo esc_url( $dorotape_quote['url'] ); ?>"<?php echo $dorotape_quote['target'] ? ' target="' . esc_attr( $dorotape_quote['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $dorotape_quote['title'] ); ?>
						</a>
					<?php endif; ?>

					<button type="button"
						class="site-header__icon-button site-header__menu-toggle js-header-menu-toggle"
						aria-controls="site-header-drawer" aria-expanded="false"
						aria-label="<?php esc_attr_e( 'Menu', 'dorotape' ); ?>"
						data-label-open="<?php esc_attr_e( 'Close menu', 'dorotape' ); ?>"
						data-label-closed="<?php esc_attr_e( 'Menu', 'dorotape' ); ?>">
						<?php
						// Both icons ship and CSS shows one at a time, so the
						// button reads as "close" while the drawer is open
						// without JavaScript having to rewrite any markup.
						echo dorotape_header_icon( 'menu', 'site-header__button-icon site-header__toggle-icon--menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo dorotape_header_icon( 'close', 'site-header__button-icon site-header__toggle-icon--close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</button>
				</div>

			</div>
		</div><!-- .site-header__main -->

		<?php
		// An unassigned menu location must render nothing. wp_nav_menu()'s
		// default fallback_cb is wp_page_menu(), which lists every published
		// page alphabetically, so an empty slot was putting Cart, Checkout, My
		// account, Wishlist and the policy pages into the site navigation.
		// Hence fallback_cb => false, and has_nav_menu() around the wrapper so
		// an empty slot does not leave an empty <nav> landmark behind either.
		if ( has_nav_menu( 'primary' ) ) :
			?>
			<nav class="site-header__nav js-header-nav" aria-label="<?php esc_attr_e( 'Product categories', 'dorotape' ); ?>">
				<div class="container site-header__nav-inner">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'menu_id'        => 'primary-menu',
							'menu_class'     => 'site-header__nav-list',
							'item_class'     => 'site-header__nav-item',
							'link_class'     => 'site-header__nav-link',
							'submenu_class'  => 'site-header__nav-submenu',
							'container'      => false,
							'fallback_cb'    => false,
						)
					);
					?>
				</div>
			</nav>
		<?php endif; ?>

		<?php
		// One drawer for both the menu button and the phone search button, so
		// there is a single open state and no way to get two panels on screen.
		// It is always in the DOM: building it on demand would mean the search
		// field is not there for the browser to autofill or restore.
		?>
		<div class="site-header__drawer js-header-drawer" id="site-header-drawer">
			<div class="container">
				<?php dorotape_header_search_field( 'dt-search-drawer', '' !== $dorotape_search_sm ? $dorotape_search_sm : $dorotape_search ); ?>

				<?php
				if ( has_nav_menu( 'primary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'menu_id'        => 'primary-menu-drawer',
							'menu_class'     => 'site-header__drawer-list',
							'item_class'     => 'site-header__drawer-item',
							'link_class'     => 'site-header__drawer-link',
							'submenu_class'  => 'site-header__drawer-submenu',
							'container'      => false,
							'fallback_cb'    => false,
						)
					);
				}
				?>

				<div class="site-header__drawer-utility">
					<?php if ( '' !== $dorotape_phone['display'] ) : ?>
						<a class="site-header__utility-link" href="<?php echo esc_url( $dorotape_phone['href'] ); ?>">
							<?php echo dorotape_header_icon( 'phone', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( $dorotape_phone['display'] ); ?>
						</a>
					<?php endif; ?>

					<a class="site-header__utility-link" href="<?php echo esc_url( $dorotape_account ); ?>">
						<?php echo dorotape_header_icon( 'user', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php is_user_logged_in() ? esc_html_e( 'My account', 'dorotape' ) : esc_html_e( 'Login', 'dorotape' ); ?>
					</a>

					<a class="site-header__utility-link" href="<?php echo esc_url( $dorotape_cart ); ?>">
						<?php echo dorotape_header_icon( 'cart', 'site-header__utility-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						printf(
							/* translators: %d: number of items in the basket. */
							esc_html__( 'Basket (%d)', 'dorotape' ),
							(int) $dorotape_cart_count
						);
						?>
					</a>
				</div>
			</div>
		</div><!-- .site-header__drawer -->

	</header><!-- #masthead -->
