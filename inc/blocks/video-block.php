<?php
declare( strict_types=1 );
/**
 * Block: Video
 *
 * ACF flexible content layout `video_block`. The "Behind the scenes" band
 * from the About page in the signed-off internal pages design: a header row
 * over a framed 16:9 embed.
 *
 * The URL goes through oEmbed rather than being pasted into an iframe, so
 * YouTube, Vimeo and anything else WordPress already knows about all work,
 * and the editor only ever has to paste the address bar.
 *
 * The player itself loads only when the video is played. Until then the
 * frame shows the provider's still with a play button, which is a link to
 * the video, so it still works with scripts off.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_url = trim( (string) get_sub_field( 'video_url' ) );

if ( '' === $dt_url ) {
	return;
}

$dt_video = dorotape_video_embed( $dt_url );
$dt_embed = $dt_video['html'];

if ( '' === $dt_embed ) {
	return;
}

// Provider markup does not go through wp_filter_content_tags(), so the iframe
// arrives with no loading attribute. This block never sits above the fold and
// a video embed pulls in a lot of third-party script, so it is deferred here,
// as loading="lazy" in the design.
if ( false === strpos( $dt_embed, ' loading=' ) ) {
	$dt_embed = str_replace( '<iframe ', '<iframe loading="lazy" ', $dt_embed );
}

// Played from the still, so start playing once the player has loaded.
if ( '' !== $dt_video['thumb'] ) {
	$dt_embed = (string) preg_replace_callback(
		'#( src=")([^"]+)#',
		static function ( array $m ): string {
			return $m[1] . esc_url( add_query_arg( 'autoplay', '1', html_entity_decode( $m[2] ) ) );
		},
		$dt_embed,
		1
	);
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

<section class="<?php echo esc_attr( $dt_classes ); ?>"<?php dorotape_animate_attr(); ?>>
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
			<?php if ( '' !== $dt_video['thumb'] ) : ?>
				<a class="video-block__play" href="<?php echo esc_url( $dt_url ); ?>" data-dt-video>
					<img src="<?php echo esc_url( $dt_video['thumb'] ); ?>" alt="" loading="lazy" decoding="async">
					<span class="video-block__play-icon"><?php echo dorotape_ui_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
					<span class="screen-reader-text">
						<?php
						/* translators: %s: video title. */
						echo esc_html( '' !== $dt_video['title'] ? sprintf( __( 'Play video: %s', 'dorotape' ), $dt_video['title'] ) : __( 'Play video', 'dorotape' ) );
						?>
					</span>
				</a>
				<template data-dt-video-embed>
					<?php
					// oEmbed markup from a provider WordPress already trusts.
					echo $dt_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed output.
					?>
				</template>
			<?php else : ?>
				<?php
				// oEmbed markup from a provider WordPress already trusts.
				echo $dt_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oEmbed output.
				?>
			<?php endif; ?>
		</div>
	</div>
</section>
