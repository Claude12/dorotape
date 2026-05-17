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

		</div><!-- .header-inner -->
	</header><!-- #masthead -->
