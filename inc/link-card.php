<?php
declare( strict_types=1 );
/**
 * The link card.
 *
 * The ranges grid's card with its insides given to it rather than read off a
 * term: a picture, a title and a button, wrapped in one anchor. Two places
 * need it and neither has a term to draw from. The search page uses it for
 * the pages and guides a search turned up, and the 404 page uses it for the
 * destinations an editor has chosen.
 *
 * It is deliberately markup only. Where the link, the title and the picture
 * come from is the caller's business; what a card looks like is this file's,
 * so the two callers cannot drift apart.
 *
 * The one thing it decides for itself is what to do without a picture. A
 * search result is a page an editor wrote months ago and a 404 destination is
 * a link they typed, so neither reliably has one, and a grid of flat grey
 * panels reads as broken rather than plain. It falls back to Theme Settings >
 * General > Card fallback image, and only shows the empty panel when even
 * that is unset.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The picture a card should draw, its own or the site's fallback.
 *
 * Returns 0 only when neither exists, which is the caller's signal to draw the
 * empty panel. Checking the attachment is really an image keeps a PDF dropped
 * into the field from rendering as a broken <img>.
 *
 * @param int $image_id The card's own attachment id, or 0.
 */
function dorotape_card_image_id( int $image_id = 0 ): int {
	if ( $image_id && wp_attachment_is_image( $image_id ) ) {
		return $image_id;
	}

	$fallback = (int) dorotape_setting( 'card_fallback_image' );

	return $fallback && wp_attachment_is_image( $fallback ) ? $fallback : 0;
}

/**
 * One card.
 *
 * Prints nothing without a URL and a title: a card with no destination is a
 * decoration a keyboard can still land on.
 *
 * @param array<string, mixed> $args {
 *     @type string $url      Where the card goes. Required.
 *     @type string $title    The card's own text. Required.
 *     @type string $button   Button label, or '' for no button.
 *     @type int    $image_id Attachment id, or 0 to fall back to the setting.
 *     @type string $sizes    sizes attribute for the picture.
 * }
 */
function dorotape_link_card( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'url'      => '',
			'title'    => '',
			'button'   => '',
			'image_id' => 0,
			'sizes'    => '(min-width: 1024px) 25vw, 50vw',
		)
	);

	$url   = trim( (string) $args['url'] );
	$title = trim( (string) $args['title'] );

	if ( '' === $url || '' === $title ) {
		return;
	}

	$button   = trim( (string) $args['button'] );
	$image_id = dorotape_card_image_id( (int) $args['image_id'] );
	?>
	<li class="category-grid-block__item">
		<a class="category-grid-block__card" href="<?php echo esc_url( $url ); ?>">

			<?php if ( $image_id ) : ?>
				<?php
				// Empty alt: the title below is the link's own text, so a
				// screen reader would otherwise read the destination twice.
				echo wp_get_attachment_image(
					$image_id,
					'medium_large',
					false,
					array(
						'class'    => 'category-grid-block__image',
						'alt'      => '',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => esc_attr( (string) $args['sizes'] ),
					)
				);
				?>
			<?php else : ?>
				<span class="category-grid-block__image category-grid-block__image--empty" aria-hidden="true"></span>
			<?php endif; ?>

			<div class="category-grid-block__body">
				<h3 class="category-grid-block__title"><?php echo esc_html( $title ); ?></h3>

				<?php if ( '' !== $button ) : ?>
					<span class="btn btn--sm btn--outline category-grid-block__button">
						<?php echo esc_html( $button ); ?>
						<?php echo dorotape_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
					</span>
				<?php endif; ?>
			</div>

		</a>
	</li>
	<?php
}
