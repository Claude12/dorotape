<?php
/**
 * The header for our theme
 *
 * @package dorotape
 */
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

	<header id="masthead" class="site-header">
		<div class="header-inner">

			<div class="site-branding">
				<?php
				the_custom_logo();
				if ( ! has_custom_logo() ) :
					?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-name" rel="home">
						<?php bloginfo( 'name' ); ?>
					</a>
				<?php endif; ?>
			</div><!-- .site-branding -->

			<nav id="site-navigation" class="nav-primary" aria-label="<?php esc_attr_e( 'Primary menu', 'dorotape' ); ?>">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<?php esc_html_e( 'Menu', 'dorotape' ); ?>
				</button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_id'        => 'primary-menu',
						'container'      => false,
					)
				);
				?>
			</nav><!-- #site-navigation -->

			<nav id="secondary-navigation" class="nav-secondary" aria-label="<?php esc_attr_e( 'Secondary menu', 'dorotape' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'secondary',
						'menu_id'        => 'secondary-menu',
						'container'      => false,
					)
				);
				?>
			</nav><!-- #secondary-navigation -->

			<div class="dt-header-search" id="dt-header-search">
				<button type="button" class="dt-header-search__toggle"
					aria-expanded="false" aria-controls="dt-header-search-form"
					aria-label="<?php esc_attr_e( 'Search products', 'dorotape' ); ?>">
					<svg class="dt-header-search__icon" viewBox="0 0 24 24" width="20" height="20"
						fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
						<circle cx="11" cy="11" r="7"></circle>
						<line x1="16.5" y1="16.5" x2="21" y2="21"></line>
					</svg>
				</button>
				<div id="dt-header-search-form" class="dt-header-search__form">
					<?php
					// Client: the old box only searched on submit — "not showing matches
					// as a predictive and narrowing list". FiboSearch (the plugin they
					// installed) does the live suggestions, so the theme hands the whole
					// control over to it rather than trying to reproduce them.
					//
					// The fallback below only runs if the plugin is deactivated or the
					// shortcode is renamed; without it the header would lose its search
					// entirely and silently.
					if ( shortcode_exists( 'fibosearch' ) ) {
						echo do_shortcode( '[fibosearch]' );
					} else {
						?>
						<form class="dt-header-search__fallback" role="search" method="get"
							action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<label class="screen-reader-text" for="dt-header-search-input"><?php esc_html_e( 'Search products', 'dorotape' ); ?></label>
							<input id="dt-header-search-input" class="dt-header-search__input" type="search" name="s"
								value="<?php echo esc_attr( get_search_query() ); ?>"
								placeholder="<?php esc_attr_e( 'Search products…', 'dorotape' ); ?>" autocomplete="off">
							<input type="hidden" name="post_type" value="product">
							<button type="submit" class="dt-header-search__submit"><?php esc_html_e( 'Search', 'dorotape' ); ?></button>
						</form>
						<?php
					}
					?>
				</div>
			</div><!-- .dt-header-search -->

		</div><!-- .header-inner -->
	</header><!-- #masthead -->
