<?php
/**
 * The template for displaying the footer
 *
 * @package dorotape
 */
?>

	<footer id="colophon" class="site-footer">
		<div class="footer-inner">

			<nav class="nav-footer" aria-label="<?php esc_attr_e( 'Footer menu', 'dorotape' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'menu_id'        => 'footer-menu',
						'container'      => false,
						'depth'          => 1,
					)
				);
				?>
			</nav>

			<p class="site-copyright">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.
				<?php esc_html_e( 'All rights reserved.', 'dorotape' ); ?>
			</p>

		</div><!-- .footer-inner -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
