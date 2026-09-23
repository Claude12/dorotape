<?php
declare( strict_types=1 );
/**
 * Block: Category Grid
 *
 * ACF flexible content layout `category_grid_block`. Built from "Shop by
 * category" in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/CategoryGrid.tsx): a header
 * row (heading left, short intro right) above a grid of product category
 * cards, each an image over the category name and a "View range" pill.
 *
 * Each card points at a real product category, so its link can never go
 * stale. The category's own name and image (Products > Categories) are used
 * unless the card overrides them, which keeps the homepage in step with the
 * shop by default.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_divider      = get_sub_field( 'divider' );
$dt_heading      = get_sub_field( 'heading' );
$dt_intro        = get_sub_field( 'intro' );
$dt_button_label = get_sub_field( 'button_label' );
$dt_rows         = get_sub_field( 'categories' );

// Resolve each row to a live category up front, so a card whose category has
// since been deleted drops out instead of rendering a dead link.
$dt_cards = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_term = get_term( absint( $dt_row['category'] ?? 0 ), 'product_cat' );

	if ( ! $dt_term instanceof WP_Term ) {
		continue;
	}

	$dt_link = get_term_link( $dt_term );

	if ( is_wp_error( $dt_link ) ) {
		continue;
	}

	$dt_image_id = absint( $dt_row['image']['ID'] ?? 0 );

	if ( ! $dt_image_id ) {
		$dt_image_id = absint( get_term_meta( $dt_term->term_id, 'thumbnail_id', true ) );
	}

	$dt_cards[] = array(
		'title'    => '' !== trim( (string) ( $dt_row['label'] ?? '' ) ) ? (string) $dt_row['label'] : $dt_term->name,
		'url'      => $dt_link,
		'image_id' => $dt_image_id,
	);
}

// A category grid with no categories is an empty shell; hide it.
if ( ! $dt_cards ) {
	return;
}

$dt_loading = 0 === (int) get_query_var( 'block_index', 1 ) ? 'eager' : 'lazy';

$dt_classes = 'category-grid-block';
if ( $dt_divider ) {
	$dt_classes .= ' category-grid-block--divider';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );
?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<?php if ( $dt_divider ) : ?>
		<div class="aurora-rule category-grid-block__rule" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="container">

		<?php if ( $dt_heading || $dt_intro ) : ?>
			<div class="category-grid-block__header">
				<?php if ( $dt_heading ) : ?>
					<h2 class="category-grid-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
				<?php endif; ?>

				<?php if ( $dt_intro ) : ?>
					<p class="category-grid-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<ul class="category-grid-block__grid">
			<?php foreach ( $dt_cards as $dt_card ) : ?>
				<li class="category-grid-block__item">
					<a class="category-grid-block__card" href="<?php echo esc_url( $dt_card['url'] ); ?>">
						<?php if ( $dt_card['image_id'] ) : ?>
							<?php
							// Empty alt: the image sits inside a link whose text
							// already names the category, so describing it again
							// would make screen readers say the name twice.
							echo wp_get_attachment_image(
								$dt_card['image_id'],
								'medium_large',
								false,
								array(
									'class'    => 'category-grid-block__image',
									'alt'      => '',
									'loading'  => $dt_loading,
									'decoding' => 'async',
									'sizes'    => '(min-width: 1024px) 25vw, 50vw',
								)
							);
							?>
						<?php else : ?>
							<span class="category-grid-block__image category-grid-block__image--empty" aria-hidden="true"></span>
						<?php endif; ?>

						<div class="category-grid-block__body">
							<h3 class="category-grid-block__title"><?php echo esc_html( $dt_card['title'] ); ?></h3>

							<?php if ( $dt_button_label ) : ?>
								<span class="btn btn--sm btn--outline category-grid-block__button"><?php echo esc_html( $dt_button_label ); ?><?php echo dorotape_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?></span>
							<?php endif; ?>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
