<?php
declare( strict_types=1 );
/**
 * Block: Category Intro
 *
 * ACF flexible content layout `category_intro_block`. The band that opens both
 * category designs under the banner: a cyan outline pill, a heading and a
 * bolder standfirst with a call to action on the left, and a ticked checklist
 * beside them on the right.
 *
 * One block for both designs rather than two. The top level design links each
 * checklist row down to the ranges grid and gives it the aurora ring on hover;
 * the range design lists the same rows flat with nothing to click. That is the
 * only difference between them, so a row here is a link when it is given one
 * and a plain row when it is not, and neither design needs a block of its own.
 *
 * The heading uses the shared block heading size rather than the design's
 * text-3xl/5xl step, for the same reason every other block does: one section
 * heading size across the site (decided 2026-09-16).
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_eyebrow = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_lead    = trim( (string) get_sub_field( 'lead' ) );
$dt_link    = get_sub_field( 'link' );
$dt_icon    = (string) get_sub_field( 'link_icon' );
$dt_items   = get_sub_field( 'items' );
$dt_items   = is_array( $dt_items ) ? $dt_items : array();

// Rows an editor started and left empty would otherwise render as a tick with
// nothing beside it.
$dt_items = array_values(
	array_filter(
		$dt_items,
		static function ( $row ): bool {
			return is_array( $row ) && '' !== trim( (string) ( $row['label'] ?? '' ) );
		}
	)
);

if ( '' === $dt_heading && '' === $dt_lead && array() === $dt_items ) {
	return;
}

$dt_divider = (bool) get_sub_field( 'divider' );

$dt_is_first = 0 === (int) get_query_var( 'block_index', 1 );

// The first block carries the page's only <h1>. On a category page it never is
// the first: the banner above it holds the title.
$dt_heading_tag = $dt_is_first ? 'h1' : 'h2';

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'category-intro-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<?php if ( '' !== $dt_eyebrow ) : ?>
			<p class="category-intro-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
		<?php endif; ?>

		<div class="category-intro-block__grid">

			<div class="category-intro-block__lead">
				<?php if ( '' !== $dt_heading ) : ?>
					<<?php echo esc_html( $dt_heading_tag ); ?> class="category-intro-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>
				<?php endif; ?>

				<?php if ( '' !== $dt_lead ) : ?>
					<p class="category-intro-block__standfirst"><?php echo esc_html( $dt_lead ); ?></p>
				<?php endif; ?>

				<?php if ( $dt_link && ! empty( $dt_link['url'] ) ) : ?>
					<a class="btn btn--outline category-intro-block__cta" href="<?php echo esc_url( $dt_link['url'] ); ?>"<?php echo $dt_link['target'] ? ' target="' . esc_attr( $dt_link['target'] ) . '" rel="noopener"' : ''; ?>>
						<?php
						if ( '' !== $dt_icon ) {
							echo dorotape_icon( $dt_icon, 'btn__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup built from a fixed icon map.
						}
						echo esc_html( $dt_link['title'] ? $dt_link['title'] : __( 'Talk to an expert', 'dorotape' ) );
						?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( array() !== $dt_items ) : ?>
				<ul class="category-intro-block__list">
					<?php foreach ( $dt_items as $dt_item ) : ?>
						<?php
						$dt_item_link = is_array( $dt_item['link'] ?? null ) ? $dt_item['link'] : null;
						$dt_item_url  = $dt_item_link && ! empty( $dt_item_link['url'] ) ? $dt_item_link['url'] : '';
						// A row with somewhere to go is a link, and takes the
						// aurora ring on hover. A row without one is a span, so
						// nothing offers the keyboard a stop that does nothing.
						$dt_tag = '' !== $dt_item_url ? 'a' : 'span';
						?>
						<li class="category-intro-block__item">
							<<?php echo esc_html( $dt_tag ); ?> class="category-intro-block__row<?php echo '' !== $dt_item_url ? ' category-intro-block__row--link' : ''; ?>"<?php echo '' !== $dt_item_url ? ' href="' . esc_url( $dt_item_url ) . '"' : ''; ?><?php echo '' !== $dt_item_url && ! empty( $dt_item_link['target'] ) ? ' target="' . esc_attr( $dt_item_link['target'] ) . '" rel="noopener"' : ''; ?>>
								<?php echo dorotape_icon( 'check', 'category-intro-block__tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup built from a fixed icon map. ?>
								<span class="category-intro-block__label"><?php echo esc_html( $dt_item['label'] ); ?></span>
							</<?php echo esc_html( $dt_tag ); ?>>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</div>
	</div>
</section>
