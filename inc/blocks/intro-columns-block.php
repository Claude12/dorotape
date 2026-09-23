<?php
declare( strict_types=1 );
/**
 * Block: Intro Columns
 *
 * ACF flexible content layout `intro_columns_block`. The "Who we are" split
 * from the About page in the signed-off internal pages design: eyebrow,
 * heading and a standfirst on the left, the longer body copy and an onward
 * link on the right.
 *
 * Two columns rather than one wide measure, because the design leans on the
 * standfirst carrying the weight while the detail sits beside it. On phones
 * it stacks in reading order.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_eyebrow = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_lead    = trim( (string) get_sub_field( 'lead' ) );
$dt_text    = (string) get_sub_field( 'text' );
$dt_link    = get_sub_field( 'link' );

if ( '' === $dt_heading && '' === $dt_lead && '' === trim( wp_strip_all_tags( $dt_text ) ) ) {
	return;
}

$dt_divider = (bool) get_sub_field( 'divider' );

$dt_is_first = 0 === (int) get_query_var( 'block_index', 1 );

// The first block carries the page's only <h1>.
$dt_heading_tag = $dt_is_first ? 'h1' : 'h2';

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'intro-columns-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<div class="intro-columns-block__grid">

			<div class="intro-columns-block__lead">
				<?php if ( '' !== $dt_eyebrow ) : ?>
					<p class="intro-columns-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $dt_heading ) : ?>
					<<?php echo esc_html( $dt_heading_tag ); ?> class="intro-columns-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>
				<?php endif; ?>

				<?php if ( '' !== $dt_lead ) : ?>
					<p class="intro-columns-block__standfirst"><?php echo esc_html( $dt_lead ); ?></p>
				<?php endif; ?>
			</div>

			<div class="intro-columns-block__body">
				<?php if ( '' !== trim( wp_strip_all_tags( $dt_text ) ) ) : ?>
					<div class="intro-columns-block__text rte">
						<?php echo wp_kses_post( $dt_text ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $dt_link && ! empty( $dt_link['url'] ) ) : ?>
					<a class="link link--underline intro-columns-block__link" href="<?php echo esc_url( $dt_link['url'] ); ?>"<?php echo $dt_link['target'] ? ' target="' . esc_attr( $dt_link['target'] ) . '" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $dt_link['title'] ? $dt_link['title'] : __( 'Read more', 'dorotape' ) ); ?>
						<svg class="link__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
					</a>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
