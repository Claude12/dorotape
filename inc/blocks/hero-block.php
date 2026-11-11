<?php
declare( strict_types=1 );
/**
 * Block: Hero
 *
 * ACF flexible content layout `hero_block`. Built from the homepage hero in
 * the signed-off design (v2 Final Homepage Design/src/components/site/Hero.tsx):
 * an eyebrow chip, the page heading, an intro paragraph and two buttons on
 * the left, a row of headline figures on the right, all over a drifting
 * magenta texture washed into the navy canvas.
 *
 * With two or more photos in Background Slideshow the texture is replaced by
 * those photos, fading one into the next under a darker gradient so the copy
 * stays readable; only the pictures move. assets/js/lib/hero-slideshow.js
 * runs it, and without that script (or with reduced motion asked for) the
 * first photo simply stays put.
 *
 * As the first block on a page it runs up underneath the header, the way the
 * design's fixed header sits over it, so the texture shows through the
 * translucent utility bar. Anywhere further down the page it is an ordinary
 * section. See .hero-block--overlap in assets/scss/components/_hero-block.scss.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_background = get_sub_field( 'background_image' );
$dt_eyebrow    = get_sub_field( 'eyebrow' );
$dt_dot        = dorotape_eyebrow_colour( get_sub_field( 'eyebrow_colour' ), 'magenta' );
$dt_heading    = get_sub_field( 'heading' );
$dt_intro      = get_sub_field( 'intro' );
$dt_primary    = get_sub_field( 'primary_link' );
$dt_secondary  = get_sub_field( 'secondary_link' );
$dt_stats      = get_sub_field( 'stats' );
$dt_slides     = get_sub_field( 'background_slides' );

// The heading is what the hero is for. Without one the rest has nothing to
// hang off, so the block hides rather than rendering a tall empty wash.
if ( ! $dt_heading ) {
	return;
}

$dt_is_first = 0 === (int) get_query_var( 'block_index', 1 );

// The first block carries the page's only <h1>. A hero placed lower down is
// a section like any other and must not add a second one.
$dt_heading_tag = $dt_is_first ? 'h1' : 'h2';

$dt_classes = 'hero-block';
if ( $dt_is_first ) {
	$dt_classes .= ' hero-block--overlap';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );

$dt_stats = is_array( $dt_stats ) ? $dt_stats : array();

$dt_slides = is_array( $dt_slides ) ? array_values( array_filter( array_map( 'intval', $dt_slides ) ) ) : array();
if ( count( $dt_slides ) < 2 ) {
	$dt_slides = array();
}
if ( $dt_slides ) {
	$dt_classes .= ' hero-block--slides';
}
?>

<section class="<?php echo esc_attr( $dt_classes ); ?>">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<?php if ( $dt_slides ) : ?>
		<?php
		$dt_pause_label = get_sub_field( 'slides_pause_label' );
		$dt_play_label  = get_sub_field( 'slides_play_label' );
		$dt_pause_label = $dt_pause_label ? $dt_pause_label : __( 'Pause background images', 'dorotape' );
		$dt_play_label  = $dt_play_label ? $dt_play_label : __( 'Play background images', 'dorotape' );
		$dt_show_label  = trim( (string) get_sub_field( 'slides_show_label' ) );
		$dt_show_label  = $dt_show_label ? $dt_show_label : __( 'Show background image', 'dorotape' );
		?>
		<div class="hero-block__backdrop hero-block__slides" data-hero-slides aria-hidden="true">
			<?php foreach ( $dt_slides as $dt_i => $dt_slide_id ) : ?>
				<?php
				$dt_lead = 0 === $dt_i;
				// Decorative, like the single image. Only the first photo is
				// fetched with the page; the others carry their sources in
				// data- attributes and the script fills each one in a slide
				// ahead, so a visitor who never waits downloads one photo.
				$dt_img = wp_get_attachment_image(
					$dt_slide_id,
					'full',
					false,
					array(
						'class'         => 'hero-block__backdrop-image',
						'alt'           => '',
						'loading'       => $dt_lead && $dt_is_first ? 'eager' : 'lazy',
						'fetchpriority' => $dt_lead && $dt_is_first ? 'high' : 'auto',
						'decoding'      => 'async',
						'sizes'         => '100vw',
					)
				);
				if ( ! $dt_img ) {
					continue;
				}
				if ( ! $dt_lead ) {
					$dt_img = preg_replace( '/\s(src|srcset)=/', ' data-$1=', $dt_img );
				}
				?>
				<div class="hero-block__slide<?php echo $dt_lead ? ' is-active' : ''; ?>">
					<?php echo $dt_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by wp_get_attachment_image. ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="container hero-block__controls" data-hero-controls hidden>
			<div class="hero-block__progress">
				<?php foreach ( $dt_slides as $dt_i => $dt_slide_id ) : ?>
					<button type="button" class="hero-block__progress-button" data-hero-goto="<?php echo esc_attr( (string) $dt_i ); ?>" aria-label="<?php echo esc_attr( sprintf( '%1$s %2$d of %3$d', $dt_show_label, $dt_i + 1, count( $dt_slides ) ) ); ?>"<?php echo 0 === $dt_i ? ' aria-current="true"' : ''; ?>>
						<span class="hero-block__progress-bar<?php echo 0 === $dt_i ? ' is-active' : ''; ?>"></span>
					</button>
				<?php endforeach; ?>
			</div>
			<button type="button" class="hero-block__toggle" data-hero-toggle aria-label="<?php echo esc_attr( $dt_pause_label ); ?>" data-label-pause="<?php echo esc_attr( $dt_pause_label ); ?>" data-label-play="<?php echo esc_attr( $dt_play_label ); ?>">
				<svg class="hero-block__toggle-pause" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><rect x="2" y="1.5" width="2.5" height="9" rx="1" fill="currentColor"/><rect x="7.5" y="1.5" width="2.5" height="9" rx="1" fill="currentColor"/></svg>
				<svg class="hero-block__toggle-play" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M3 1.8v8.4a.6.6 0 0 0 .9.5l6.6-4.2a.6.6 0 0 0 0-1L3.9 1.3a.6.6 0 0 0-.9.5Z" fill="currentColor"/></svg>
			</button>
		</div>
	<?php elseif ( ! empty( $dt_background['ID'] ) ) : ?>
		<div class="hero-block__backdrop" aria-hidden="true">
			<?php
			// Decorative: the alt is emptied on purpose so screen readers
			// skip it, whatever the media library says.
			echo wp_get_attachment_image(
				(int) $dt_background['ID'],
				'full',
				false,
				array(
					'class'         => 'hero-block__backdrop-image',
					'alt'           => '',
					'loading'       => $dt_is_first ? 'eager' : 'lazy',
					'fetchpriority' => $dt_is_first ? 'high' : 'auto',
					'decoding'      => 'async',
					'sizes'         => '100vw',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<div class="container hero-block__inner">
		<div class="hero-block__lead">
			<?php if ( $dt_eyebrow ) : ?>
				<p class="hero-block__eyebrow">
					<span class="hero-block__eyebrow-dot hero-block__eyebrow-dot--<?php echo esc_attr( $dt_dot ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $dt_eyebrow ); ?>
				</p>
			<?php endif; ?>

			<<?php echo esc_html( $dt_heading_tag ); ?> class="hero-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>

			<?php if ( $dt_intro ) : ?>
				<p class="hero-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $dt_primary['url'] ) || ! empty( $dt_secondary['url'] ) ) : ?>
				<div class="hero-block__actions">
					<?php if ( ! empty( $dt_primary['url'] ) ) : ?>
						<a class="btn btn--lg" href="<?php echo esc_url( $dt_primary['url'] ); ?>" target="<?php echo esc_attr( $dt_primary['target'] ? $dt_primary['target'] : '_self' ); ?>">
							<?php echo esc_html( $dt_primary['title'] ); ?>
							<?php echo dorotape_arrow_icon( 'btn__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $dt_secondary['url'] ) ) : ?>
						<a class="btn btn--lg btn--ghost" href="<?php echo esc_url( $dt_secondary['url'] ); ?>" target="<?php echo esc_attr( $dt_secondary['target'] ? $dt_secondary['target'] : '_self' ); ?>">
							<?php echo esc_html( $dt_secondary['title'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $dt_stats ) : ?>
			<dl class="hero-block__stats">
				<?php foreach ( $dt_stats as $dt_stat ) : ?>
					<?php
					if ( empty( $dt_stat['value'] ) ) {
						continue;
					}
					?>
					<div class="hero-block__stat">
						<dt class="hero-block__stat-value"><?php echo esc_html( $dt_stat['value'] ); ?></dt>
						<?php if ( ! empty( $dt_stat['label'] ) ) : ?>
							<dd class="hero-block__stat-label"><?php echo esc_html( $dt_stat['label'] ); ?></dd>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>

	<div class="aurora-rule" aria-hidden="true"></div>
</section>
