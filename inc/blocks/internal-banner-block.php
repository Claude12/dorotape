<?php
declare( strict_types=1 );
/**
 * Block: Internal Banner
 *
 * ACF flexible content layout `internal_banner_block`. Built from the About
 * page banner in the signed-off internal pages design
 * (Internal Pages Design/src/components/site/InternalBanner.tsx): an eyebrow
 * chip with a magenta dot, the page heading, a standfirst and a breadcrumb,
 * over a drifting magenta texture washed into the navy canvas.
 *
 * The shorter counterpart to hero_block: same overlap under the sticky
 * header when it is the first block, no figures and no buttons. Internal
 * pages open with this, the homepage opens with the hero.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_background = get_sub_field( 'background_image' );
$dt_eyebrow    = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading    = trim( (string) get_sub_field( 'heading' ) );
$dt_intro      = trim( (string) get_sub_field( 'intro' ) );
$dt_breadcrumb = (bool) get_sub_field( 'breadcrumb' );

// The heading is what the banner is for. Without one the rest has nothing to
// hang off, so the block hides rather than rendering a tall empty wash.
if ( '' === $dt_heading ) {
	return;
}

$dt_is_first = 0 === (int) get_query_var( 'block_index', 1 );

// The first block carries the page's only <h1>. A banner placed lower down
// is a section like any other and must not add a second one.
$dt_heading_tag = $dt_is_first ? 'h1' : 'h2';

$dt_classes = 'internal-banner-block';
if ( $dt_is_first ) {
	$dt_classes .= ' internal-banner-block--overlap';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );

// The trail is built from the page's own ancestors rather than a breadcrumb
// plugin, so it keeps working on any site this theme is dropped onto.
$dt_trail = array();

if ( $dt_breadcrumb ) {
	$dt_trail[] = array(
		'label' => __( 'Home', 'dorotape' ),
		'url'   => home_url( '/' ),
	);

	foreach ( array_reverse( (array) get_post_ancestors( get_the_ID() ) ) as $dt_ancestor ) {
		$dt_trail[] = array(
			'label' => (string) get_the_title( $dt_ancestor ),
			'url'   => (string) get_permalink( $dt_ancestor ),
		);
	}

	$dt_trail[] = array(
		'label' => (string) get_the_title(),
		'url'   => '',
	);
}
?>

<section class="<?php echo esc_attr( $dt_classes ); ?>">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<?php if ( ! empty( $dt_background['ID'] ) ) : ?>
		<div class="internal-banner-block__backdrop" aria-hidden="true">
			<?php
			echo wp_get_attachment_image(
				(int) $dt_background['ID'],
				'full',
				false,
				array(
					'class'         => 'internal-banner-block__backdrop-image',
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

	<div class="container">
		<div class="internal-banner-block__inner">

			<?php if ( '' !== $dt_eyebrow ) : ?>
				<p class="internal-banner-block__eyebrow">
					<span class="internal-banner-block__eyebrow-dot" aria-hidden="true"></span>
					<?php echo esc_html( $dt_eyebrow ); ?>
				</p>
			<?php endif; ?>

			<<?php echo esc_html( $dt_heading_tag ); ?> class="internal-banner-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>

			<?php if ( '' !== $dt_intro ) : ?>
				<p class="internal-banner-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
			<?php endif; ?>

			<?php if ( $dt_trail ) : ?>
				<nav class="internal-banner-block__breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'dorotape' ); ?>">
					<ol class="internal-banner-block__trail">
						<?php foreach ( $dt_trail as $dt_index => $dt_crumb ) : ?>
							<?php if ( $dt_index ) : ?>
								<li class="internal-banner-block__separator" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m9 18 6-6-6-6"/></svg>
								</li>
							<?php endif; ?>

							<li class="internal-banner-block__crumb">
								<?php if ( $dt_crumb['url'] ) : ?>
									<a class="internal-banner-block__crumb-link" href="<?php echo esc_url( $dt_crumb['url'] ); ?>"><?php echo esc_html( $dt_crumb['label'] ); ?></a>
								<?php else : ?>
									<span class="internal-banner-block__crumb-current" aria-current="page"><?php echo esc_html( $dt_crumb['label'] ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

		</div>
	</div>
</section>
