<?php
/**
 * The template for displaying the footer
 *
 * Built from the signed-off design's SiteFooter component. Helpers live in
 * inc/footer.php and content comes from the Theme Settings options page; a
 * section whose content is empty prints nothing rather than a placeholder.
 *
 * @package dorotape
 */

$dt_newsletter     = dorotape_footer_newsletter();
$dt_blurb          = dorotape_footer_blurb();
$dt_socials        = dorotape_footer_socials();
$dt_accreditations = dorotape_footer_badges( 'accreditations' );
$dt_payments       = dorotape_footer_badges( 'payments' );
$dt_logo           = dorotape_site_logo();
$dt_headings       = (array) dorotape_setting( 'footer_column_headings' );
$dt_acc_heading    = (string) dorotape_setting( 'footer_accreditations_heading' );
$dt_pay_heading    = (string) dorotape_setting( 'footer_payments_heading' );
?>

	<footer id="colophon" class="site-footer" animate="fade-in-up">
		<div class="container">

			<?php if ( $dt_newsletter ) : ?>
				<?php // The gradient border is a 2px padded wrapper, not a border, so it can carry the aurora gradient. ?>
				<div class="site-footer__signup">
					<div class="site-footer__signup-inner">
						<div>
							<?php if ( '' !== $dt_newsletter['heading'] ) : ?>
								<h2 class="site-footer__signup-heading"><?php echo esc_html( $dt_newsletter['heading'] ); ?></h2>
							<?php endif; ?>
							<?php if ( '' !== $dt_newsletter['body'] ) : ?>
								<p class="site-footer__signup-body"><?php echo esc_html( $dt_newsletter['body'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="site-footer__signup-form">
							<?php wpforms_display( $dt_newsletter['form_id'], false, false ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="site-footer__top">

				<div class="site-footer__brand">
					<?php if ( '' !== $dt_logo ) : ?>
						<div class="site-footer__logo"><?php echo $dt_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in dorotape_site_logo(). ?></div>
					<?php else : ?>
						<p class="site-footer__logo site-footer__logo--text"><?php bloginfo( 'name' ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $dt_blurb ) : ?>
						<p class="site-footer__blurb"><?php echo esc_html( $dt_blurb ); ?></p>
					<?php endif; ?>

					<?php if ( $dt_socials ) : ?>
						<ul class="site-footer__socials">
							<?php foreach ( $dt_socials as $dt_social ) : ?>
								<li class="site-footer__social-item">
									<a class="site-footer__social" href="<?php echo esc_url( $dt_social['url'] ); ?>"
										rel="noopener" target="_blank">
										<?php
										echo dorotape_footer_icon( $dt_social['network'], 'site-footer__social-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup from dorotape_footer_icon().
										?>
										<span class="screen-reader-text">
											<?php echo esc_html( $dt_social['label'] ); ?>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<?php
				// The Products column shares the header's category menu by
				// design: same eight labels, same destinations. Assigning a
				// menu to footer-products overrides that.
				dorotape_footer_column( has_nav_menu( 'footer-products' ) ? 'footer-products' : 'primary', (string) ( $dt_headings['products'] ?? '' ), 'magenta' );
				dorotape_footer_column( 'footer-support', (string) ( $dt_headings['support'] ?? '' ), 'cyan' );
				dorotape_footer_column( 'footer-about', (string) ( $dt_headings['about'] ?? '' ), 'cyan' );
				?>

			</div>

			<?php if ( $dt_accreditations || $dt_payments ) : ?>
				<div class="site-footer__badges">
					<div class="aurora-rule"></div>
					<div class="site-footer__badges-grid">

						<?php if ( $dt_accreditations ) : ?>
							<div>
								<?php if ( '' !== $dt_acc_heading ) : ?>
									<p class="site-footer__column-heading site-footer__column-heading--badges"><?php echo esc_html( $dt_acc_heading ); ?></p>
								<?php endif; ?>
								<div class="site-footer__badge-row">
									<?php foreach ( $dt_accreditations as $dt_id ) : ?>
										<?php echo wp_get_attachment_image( $dt_id, 'medium', false, array( 'class' => 'site-footer__badge', 'loading' => 'lazy' ) ); ?>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ( $dt_payments ) : ?>
							<div>
								<?php if ( '' !== $dt_pay_heading ) : ?>
									<p class="site-footer__column-heading site-footer__column-heading--badges"><?php echo esc_html( $dt_pay_heading ); ?></p>
								<?php endif; ?>
								<?php // The card and gateway marks are supplied as dark-on-white artwork, so this row keeps a white plate under them. ?>
								<div class="site-footer__badge-row site-footer__badge-row--plate">
									<?php foreach ( $dt_payments as $dt_id ) : ?>
										<?php echo wp_get_attachment_image( $dt_id, 'medium', false, array( 'class' => 'site-footer__badge site-footer__badge--payment', 'loading' => 'lazy' ) ); ?>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

					</div>
				</div>
			<?php endif; ?>

			<div class="site-footer__legal">
				<p class="site-footer__copyright">
					&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( dorotape_footer_legal_text() ); ?>
				</p>

				<?php if ( has_nav_menu( 'footer-legal' ) ) : ?>
					<nav class="site-footer__legal-nav" aria-label="<?php esc_attr_e( 'Legal', 'dorotape' ); ?>">
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'footer-legal',
								'container'      => false,
								'menu_class'     => 'site-footer__legal-menu',
								'item_class'     => 'site-footer__legal-item',
								'link_class'     => 'site-footer__legal-link',
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
						?>
					</nav>
				<?php endif; ?>
			</div>

		</div><!-- .container -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
