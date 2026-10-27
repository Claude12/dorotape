<?php
declare( strict_types=1 );
/**
 * Block: Case Studies
 *
 * ACF flexible content layout `case_studies_block`. Built from "Our films,
 * out in the wild" in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/CaseStudies.tsx): an
 * eyebrow and heading over a grid of project photos, three to a row from
 * tablet up, where a "wide" project takes two places.
 *
 * A project with no link is still shown, as a plain card without the arrow.
 *
 * With "Filter by category" on, a row of buttons over the grid shows one
 * category's projects at a time, like the News page's (Site Edits, October
 * 2026). The buttons come from the projects' own category text, so there is
 * no list to keep up. It filters in the page (assets/js/lib/case-filter.js):
 * the projects are all here already, and the buttons stay hidden without
 * JavaScript, which leaves the full grid.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_rows = get_sub_field( 'projects' );

$dt_projects = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_title = trim( (string) ( $dt_row['title'] ?? '' ) );

	if ( '' === $dt_title ) {
		continue;
	}

	$dt_projects[] = array(
		'image'    => (int) ( $dt_row['image']['ID'] ?? 0 ),
		'category' => trim( (string) ( $dt_row['category'] ?? '' ) ),
		'title'    => $dt_title,
		'wide'     => ! empty( $dt_row['wide'] ),
		'url'      => (string) ( $dt_row['link'] ?? '' ),
	);
}

if ( ! $dt_projects ) {
	return;
}

$dt_eyebrow = get_sub_field( 'eyebrow' );
$dt_heading = get_sub_field( 'heading' );

// One button per category, in the order the projects first use them. Two
// spellings of the same word count once.
$dt_filters = array();

if ( get_sub_field( 'filter' ) ) {
	foreach ( $dt_projects as $dt_project ) {
		$dt_slug = sanitize_title( $dt_project['category'] );

		if ( '' !== $dt_slug && ! isset( $dt_filters[ $dt_slug ] ) ) {
			$dt_filters[ $dt_slug ] = $dt_project['category'];
		}
	}

	// One category is nothing to choose between.
	if ( count( $dt_filters ) < 2 ) {
		$dt_filters = array();
	}
}

$dt_all_label = trim( (string) get_sub_field( 'filter_all_label' ) );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'case-studies-block' . dorotape_background_shape_class( $dt_shape );

// The magenta glow the "How can we help you?" panel had, moved onto the grid
// (Site Edits, October 2026).
if ( 'magenta' === get_sub_field( 'background_color' ) ) {
	$dt_classes .= ' case-studies-block--bg-magenta';
}
?>

<?php if ( get_sub_field( 'divider' ) ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>"<?php dorotape_animate_attr(); ?>>
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="container">

		<?php if ( $dt_eyebrow || $dt_heading ) : ?>
			<div class="case-studies-block__header">
				<?php if ( $dt_eyebrow ) : ?>
					<p class="case-studies-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( $dt_heading ) : ?>
					<h2 class="case-studies-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $dt_filters ) : ?>
			<div class="blog-filters case-studies-block__filters js-case-filter" hidden>
				<ul class="blog-filters__list" role="list">
					<li>
						<button type="button" class="blog-filters__link is-current" data-filter="" aria-pressed="true"><?php echo esc_html( '' !== $dt_all_label ? $dt_all_label : __( 'All projects', 'dorotape' ) ); ?></button>
					</li>
					<?php foreach ( $dt_filters as $dt_slug => $dt_name ) : ?>
						<li>
							<button type="button" class="blog-filters__link" data-filter="<?php echo esc_attr( $dt_slug ); ?>" aria-pressed="false"><?php echo esc_html( $dt_name ); ?></button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<ul class="case-studies-block__grid"<?php echo $dt_filters ? ' aria-live="polite"' : ''; ?>>
			<?php foreach ( $dt_projects as $dt_project ) : ?>
				<?php $dt_tag = $dt_project['url'] ? 'a' : 'div'; ?>
				<li class="case-studies-block__item<?php echo $dt_project['wide'] ? ' case-studies-block__item--wide' : ''; ?>"<?php echo $dt_filters ? ' data-category="' . esc_attr( sanitize_title( $dt_project['category'] ) ) . '"' : ''; ?>>
					<<?php echo esc_html( $dt_tag ); ?> class="case-studies-block__card"<?php if ( $dt_project['url'] ) : ?> href="<?php echo esc_url( $dt_project['url'] ); ?>"<?php endif; ?>>
						<div class="case-studies-block__media">
							<?php if ( $dt_project['image'] ) : ?>
								<?php
								// Decorative: the title below says what the photo is.
								// From tablet a normal card is taller than it is wide
								// (320px high), so the landscape photo is cropped to
								// fill it and needs ~466px of width whatever the
								// card's own width. WordPress's sizes="auto" would
								// size it by the card's width instead, so it is off
								// for these images.
								add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
								echo wp_get_attachment_image(
									$dt_project['image'],
									$dt_project['wide'] ? 'large' : 'medium_large',
									false,
									array(
										'class'    => 'case-studies-block__image',
										'alt'      => '',
										'loading'  => 0 === (int) get_query_var( 'block_index', 1 ) ? 'eager' : 'lazy',
										'decoding' => 'async',
										'sizes'    => $dt_project['wide'] ? '(min-width: 1400px) 895px, (min-width: 768px) 64vw, 100vw' : '(min-width: 768px) 466px, 100vw',
									)
								);
								remove_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
								?>
							<?php else : ?>
								<span class="case-studies-block__image case-studies-block__image--empty"></span>
							<?php endif; ?>
						</div>

						<div class="case-studies-block__body">
							<div>
								<?php if ( $dt_project['category'] ) : ?>
									<p class="case-studies-block__category"><?php echo esc_html( $dt_project['category'] ); ?></p>
								<?php endif; ?>

								<h3 class="case-studies-block__title"><?php echo esc_html( $dt_project['title'] ); ?></h3>
							</div>

							<?php if ( $dt_project['url'] ) : ?>
								<svg class="case-studies-block__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
							<?php endif; ?>
						</div>
					</<?php echo esc_html( $dt_tag ); ?>>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
