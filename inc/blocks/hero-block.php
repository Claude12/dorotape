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
$dt_heading    = get_sub_field( 'heading' );
$dt_intro      = get_sub_field( 'intro' );
$dt_primary    = get_sub_field( 'primary_link' );
$dt_secondary  = get_sub_field( 'secondary_link' );
$dt_stats      = get_sub_field( 'stats' );

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
?>

<section class="<?php echo esc_attr( $dt_classes ); ?>">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<?php if ( ! empty( $dt_background['ID'] ) ) : ?>
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
					<span class="hero-block__eyebrow-dot" aria-hidden="true"></span>
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
							<svg class="btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
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
