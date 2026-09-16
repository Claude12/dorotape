<?php
declare( strict_types=1 );
/**
 * Block: 50/50 Block
 *
 * ACF flexible content layout `image_text_block`. Built from the "About
 * DoroTape" intro strip in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/IntroStrip.tsx): a section
 * header row (eyebrow chip and heading on the left, intro paragraph on the
 * right) above a two-column grid of image/heading/text/link cards.
 *
 * The earlier version of this block described the two halves as separate
 * left_/right_ field pairs, which could only ever produce image-beside-text.
 * The design puts an image AND text in each half, so the halves are now a
 * repeater: one description of a column, used twice.
 *
 * Rendered inside the have_rows()/the_row() loop in
 * dorotape_render_flexible_content() (inc/acf.php), so get_sub_field() reads
 * from the current row.
 *
 * This is the reference block. New blocks are built by copying its shape:
 * read fields at the top, early-return when there is nothing to show, escape
 * at echo time, keep every size/colour on a token.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_background = get_sub_field( 'background_color' );
$dt_eyebrow    = get_sub_field( 'eyebrow' );
$dt_heading    = get_sub_field( 'heading' );
$dt_intro      = get_sub_field( 'intro' );
$dt_columns    = get_sub_field( 'columns' );

// Nothing to render if the row was added but never filled in. The block hides
// rather than printing an empty section with its full vertical padding.
if ( ! $dt_heading && ! $dt_eyebrow && ! $dt_intro && ! $dt_columns ) {
	return;
}

// Only the first block on the page is above the fold.
$dt_loading = 0 === (int) get_query_var( 'block_index', 1 ) ? 'eager' : 'lazy';

$dt_classes = 'image-text-block';
if ( $dt_background ) {
	$dt_classes .= ' image-text-block--bg-' . $dt_background;
}

// One column fills the row; two split it. The modifier is on the grid rather
// than resolved in CSS with :only-child, because the column count also
// decides the image crop height.
$dt_count = is_array( $dt_columns ) ? count( $dt_columns ) : 0;
?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<div class="container">

		<?php if ( $dt_eyebrow || $dt_heading || $dt_intro ) : ?>
			<div class="image-text-block__header">
				<div class="image-text-block__header-lead">
					<?php if ( $dt_eyebrow ) : ?>
						<p class="image-text-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $dt_heading ) : ?>
						<h2 class="image-text-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
					<?php endif; ?>
				</div>

				<?php if ( $dt_intro ) : ?>
					<p class="image-text-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $dt_columns ) : ?>
			<div class="image-text-block__grid image-text-block__grid--<?php echo esc_attr( (string) $dt_count ); ?>">
				<?php foreach ( $dt_columns as $dt_column ) : ?>
					<article class="image-text-block__column">

						<?php if ( ! empty( $dt_column['image']['ID'] ) ) : ?>
							<div class="image-text-block__media">
								<?php
								// wp_get_attachment_image() over a raw <img>: it emits
								// srcset/sizes and the alt text set in the media
								// library, so a half-width column does not download
								// the full-size original on a phone.
								echo wp_get_attachment_image(
									(int) $dt_column['image']['ID'],
									'large',
									false,
									array(
										'class'    => 'image-text-block__image',
										'loading'  => $dt_loading,
										'decoding' => 'async',
										'sizes'    => 2 === $dt_count ? '(min-width: 768px) 50vw, 100vw' : '100vw',
									)
								);
								?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $dt_column['heading'] ) ) : ?>
							<h3 class="image-text-block__column-heading"><?php echo esc_html( $dt_column['heading'] ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $dt_column['text'] ) ) : ?>
							<div class="rte image-text-block__column-text"><?php echo wp_kses_post( $dt_column['text'] ); ?></div>
						<?php endif; ?>

						<?php if ( ! empty( $dt_column['link']['url'] ) ) : ?>
							<a class="image-text-block__link" href="<?php echo esc_url( $dt_column['link']['url'] ); ?>" target="<?php echo esc_attr( $dt_column['link']['target'] ? $dt_column['link']['target'] : '_self' ); ?>">
								<?php echo esc_html( $dt_column['link']['title'] ); ?>
								<svg class="image-text-block__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
									<path d="M5 12h14M12 5l7 7-7 7" />
								</svg>
							</a>
						<?php endif; ?>

					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</section>
