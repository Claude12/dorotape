<?php
declare( strict_types=1 );
/**
 * Block: Text + Image
 *
 * ACF flexible content layout `text_image_block`. Built from the "Supplying
 * specialist self-adhesive films" section in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/SpecialistFilms.tsx): a
 * heading, body copy and up to two buttons beside one large photo. Stacked
 * on phones, text first; side by side from tablet, the photo on either side.
 * The second button is optional and ghost styled, for the About page's
 * "contact us or email us" pairing.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_heading  = trim( (string) get_sub_field( 'heading' ) );
$dt_text     = (string) get_sub_field( 'text' );
$dt_button   = get_sub_field( 'button' );
$dt_button_2 = get_sub_field( 'button_secondary' );
$dt_image    = get_sub_field( 'image' );

if ( '' === $dt_heading && '' === trim( wp_strip_all_tags( $dt_text ) ) && empty( $dt_image['ID'] ) ) {
	return;
}

$dt_classes = 'text-image-block';
if ( 'left' === get_sub_field( 'image_side' ) ) {
	$dt_classes .= ' text-image-block--image-left';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );

$dt_has_primary   = ! empty( $dt_button['url'] ) && ! empty( $dt_button['title'] );
$dt_has_secondary = ! empty( $dt_button_2['url'] ) && ! empty( $dt_button_2['title'] );

// A second action is usually a direct line rather than another page, so an
// address gets an envelope where a page gets the arrow.
$dt_secondary_is_email = $dt_has_secondary && 0 === stripos( (string) $dt_button_2['url'], 'mailto:' );
?>

<?php if ( get_sub_field( 'divider' ) ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="container">
		<div class="text-image-block__grid">

			<div class="text-image-block__content">
				<?php if ( $dt_heading ) : ?>
					<h2 class="text-image-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
				<?php endif; ?>

				<?php if ( trim( wp_strip_all_tags( $dt_text ) ) ) : ?>
					<div class="rte text-image-block__text"><?php echo wp_kses_post( $dt_text ); ?></div>
				<?php endif; ?>

				<?php if ( $dt_has_primary || $dt_has_secondary ) : ?>
					<div class="text-image-block__actions">
						<?php if ( $dt_has_primary ) : ?>
							<a class="btn btn--lg btn--cyan" href="<?php echo esc_url( $dt_button['url'] ); ?>"<?php echo $dt_button['target'] ? ' target="' . esc_attr( $dt_button['target'] ) . '" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $dt_button['title'] ); ?>
								<?php echo dorotape_arrow_icon( 'btn__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
							</a>
						<?php endif; ?>

						<?php if ( $dt_has_secondary ) : ?>
							<a class="btn btn--lg btn--ghost" href="<?php echo esc_url( $dt_button_2['url'] ); ?>"<?php echo $dt_button_2['target'] ? ' target="' . esc_attr( $dt_button_2['target'] ) . '" rel="noopener"' : ''; ?>>
								<?php if ( $dt_secondary_is_email ) : ?>
									<svg class="btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
								<?php endif; ?>
								<?php echo esc_html( $dt_button_2['title'] ); ?>
								<?php if ( ! $dt_secondary_is_email ) : ?>
									<?php echo dorotape_arrow_icon( 'btn__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
								<?php endif; ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $dt_image['ID'] ) ) : ?>
				<div class="text-image-block__media">
					<?php
					// The photo is cropped to a fixed height (460px from
					// tablet), so on a narrow tablet column it needs more
					// width than the column has. WordPress's sizes="auto"
					// would size it by the column alone, so it is off here.
					add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
					echo wp_get_attachment_image(
						(int) $dt_image['ID'],
						'large',
						false,
						array(
							'class'    => 'text-image-block__image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(min-width: 1400px) 648px, (min-width: 768px) 614px, 100vw',
						)
					);
					remove_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
					?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
