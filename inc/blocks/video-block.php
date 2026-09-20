<?php
declare( strict_types=1 );
/**
 * Block: Video
 *
 * ACF flexible content layout `video_block`. The "Behind the scenes" band
 * from the About page in the signed-off internal pages design: a header row
 * over a framed 16:9 embed.
 *
 * The URL goes through wp_oembed_get() rather than being pasted into an
 * iframe, so YouTube, Vimeo and anything else WordPress already knows about
 * all work, and the editor only ever has to paste the address bar.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_url = trim( (string) get_sub_field( 'video_url' ) );

if ( '' === $dt_url ) {
	return;
}

$dt_embed = wp_oembed_get( $dt_url, array( 'width' => 1200 ) );

if ( ! $dt_embed ) {
	return;
}

$dt_eyebrow = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_intro   = trim( (string) get_sub_field( 'intro' ) );
$dt_divider = (bool) get_sub_field( 'divider' );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'video-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<?php if ( '' !== $dt_eyebrow || '' !== $dt_heading || '' !== $dt_intro ) : ?>
			<div class="video-block__header">
				<div class="video-block__header-lead">
					<?php if ( '' !== $dt_eyebrow ) : ?>
						<p class="video-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $dt_heading ) : ?>
						<h2 class="video-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $dt_intro ) : ?>
					<p class="video-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="video-block__frame">
			<?php
			// oEmbed markup from a provider WordPress already trusts.
			echo $dt_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_oembed_get() output.
			?>
		</div>
	</div>
</section>
