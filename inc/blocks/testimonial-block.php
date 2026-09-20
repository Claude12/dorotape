<?php
declare( strict_types=1 );
/**
 * Block: Testimonials
 *
 * ACF flexible content layout `testimonial_block`. Built from the testimonial
 * band in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/Testimonial.tsx): one centred
 * customer quote over angular magenta shards, rotating every 7 seconds, with
 * a row of bars to pick a quote.
 *
 * Every quote is in the markup. They share one grid cell, so the section is
 * always as tall as the longest quote and the content below never jumps as
 * they rotate; the inactive ones are hidden with `visibility`, which also
 * keeps them out of the accessibility tree. The rotation is assets/js/lib/
 * testimonial.js; without it the first quote simply stays.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_rows = get_sub_field( 'quotes' );

$dt_quotes = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_text = trim( (string) ( $dt_row['text'] ?? '' ) );

	if ( '' === $dt_text ) {
		continue;
	}

	// Role and company share a line, joined only when both are filled in.
	$dt_meta = array_filter(
		array(
			trim( (string) ( $dt_row['role'] ?? '' ) ),
			trim( (string) ( $dt_row['company'] ?? '' ) ),
		)
	);

	$dt_quotes[] = array(
		'text' => $dt_text,
		'name' => trim( (string) ( $dt_row['name'] ?? '' ) ),
		'meta' => implode( ' · ', $dt_meta ),
	);
}

if ( ! $dt_quotes ) {
	return;
}

$dt_count = count( $dt_quotes );

$dt_divider = (bool) get_sub_field( 'divider' );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'testimonial-block js-testimonial' . dorotape_background_shape_class( $dt_shape );

// The shards are tinted from one hue token, so the choice is a single class
// on the block rather than six per-shard colours.
$dt_shards = (string) get_sub_field( 'shard_colour' );
if ( in_array( $dt_shards, array( 'indigo', 'cyan', 'aurora' ), true ) ) {
	$dt_classes .= ' testimonial-block--shards-' . $dt_shards;
}
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="testimonial-block__art" aria-hidden="true">
		<?php for ( $dt_i = 1; $dt_i <= 6; $dt_i++ ) : ?>
			<span class="testimonial-block__shard testimonial-block__shard--<?php echo esc_attr( (string) $dt_i ); ?>"></span>
		<?php endfor; ?>
	</div>

	<div class="container">
		<div class="testimonial-block__inner">
			<svg class="testimonial-block__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/><path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/></svg>

			<div class="testimonial-block__slides">
				<?php foreach ( $dt_quotes as $dt_index => $dt_quote ) : ?>
					<figure class="testimonial-block__slide js-testimonial-slide<?php echo 0 === $dt_index ? ' is-active' : ''; ?>">
						<blockquote class="testimonial-block__quote">
							<p>"<?php echo esc_html( $dt_quote['text'] ); ?>"</p>
						</blockquote>

						<?php if ( $dt_quote['name'] || $dt_quote['meta'] ) : ?>
							<figcaption class="testimonial-block__caption">
								<?php if ( $dt_quote['name'] ) : ?>
									<span class="testimonial-block__name"><?php echo esc_html( $dt_quote['name'] ); ?></span>
								<?php endif; ?>

								<?php if ( $dt_quote['meta'] ) : ?>
									<span class="testimonial-block__meta"><?php echo esc_html( $dt_quote['meta'] ); ?></span>
								<?php endif; ?>
							</figcaption>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( $dt_count > 1 ) : ?>
				<div class="testimonial-block__dots">
					<?php for ( $dt_i = 0; $dt_i < $dt_count; $dt_i++ ) : ?>
						<button
							type="button"
							class="testimonial-block__dot js-testimonial-dot<?php echo 0 === $dt_i ? ' is-active' : ''; ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %d: testimonial number. */ __( 'Testimonial %d', 'dorotape' ), $dt_i + 1 ) ); ?>"
							aria-pressed="<?php echo 0 === $dt_i ? 'true' : 'false'; ?>"
						></button>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
