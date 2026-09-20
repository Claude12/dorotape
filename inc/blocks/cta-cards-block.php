<?php
declare( strict_types=1 );
/**
 * Block: CTA Cards
 *
 * ACF flexible content layout `cta_cards_block`. Built from the three
 * signpost cards in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/CtaTriptych.tsx):
 * Applications, Sustainability and Support, each an image over a title, a
 * sentence and a "Learn more" label, the whole card one link.
 *
 * A card with no link is still shown, as a plain card without the label.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_rows = get_sub_field( 'cards' );

$dt_cards = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_title = trim( (string) ( $dt_row['title'] ?? '' ) );

	if ( '' === $dt_title ) {
		continue;
	}

	$dt_link = is_array( $dt_row['link'] ?? null ) ? $dt_row['link'] : array();

	$dt_cards[] = array(
		'image'  => (int) ( $dt_row['image']['ID'] ?? 0 ),
		'title'  => $dt_title,
		'text'   => trim( (string) ( $dt_row['text'] ?? '' ) ),
		'url'    => (string) ( $dt_link['url'] ?? '' ),
		'label'  => trim( (string) ( $dt_link['title'] ?? '' ) ),
		'target' => (string) ( $dt_link['target'] ?? '' ),
	);
}

if ( ! $dt_cards ) {
	return;
}

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'cta-cards-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( get_sub_field( 'divider' ) ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="container">
		<ul class="cta-cards-block__grid">
			<?php foreach ( $dt_cards as $dt_card ) : ?>
				<?php $dt_tag = $dt_card['url'] ? 'a' : 'div'; ?>
				<li class="cta-cards-block__item">
					<<?php echo esc_html( $dt_tag ); ?> class="cta-cards-block__card"<?php if ( $dt_card['url'] ) : ?> href="<?php echo esc_url( $dt_card['url'] ); ?>"<?php echo $dt_card['target'] ? ' target="' . esc_attr( $dt_card['target'] ) . '" rel="noopener"' : ''; ?><?php endif; ?>>
						<div class="cta-cards-block__media">
							<?php if ( $dt_card['image'] ) : ?>
								<?php
								// Decorative: the title below says what the card is.
								echo wp_get_attachment_image(
									$dt_card['image'],
									'medium_large',
									false,
									array(
										'class'    => 'cta-cards-block__image',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(min-width: 1400px) 434px, (min-width: 768px) 31vw, 100vw',
									)
								);
								?>
							<?php else : ?>
								<span class="cta-cards-block__image cta-cards-block__image--empty"></span>
							<?php endif; ?>
						</div>

						<div class="cta-cards-block__body">
							<div>
								<h3 class="cta-cards-block__title"><?php echo esc_html( $dt_card['title'] ); ?></h3>

								<?php if ( $dt_card['text'] ) : ?>
									<p class="cta-cards-block__text"><?php echo esc_html( $dt_card['text'] ); ?></p>
								<?php endif; ?>
							</div>

							<?php if ( $dt_card['url'] && $dt_card['label'] ) : ?>
								<span class="cta-cards-block__label">
									<?php echo esc_html( $dt_card['label'] ); ?>
									<svg class="cta-cards-block__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
								</span>
							<?php endif; ?>
						</div>
					</<?php echo esc_html( $dt_tag ); ?>>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
