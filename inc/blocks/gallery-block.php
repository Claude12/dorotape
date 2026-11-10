<?php
declare( strict_types=1 );
/**
 * Block: Gallery
 *
 * ACF flexible content layout `gallery_block`. A header row over the same
 * photo gallery a blog article has (inc/photo-gallery.php): one photo large,
 * every photo as a thumbnail beside it, moving on by itself every 5 seconds.
 * Asked for in the 28 October meeting (Meeting 28 Oct A27) so any page can
 * carry a set of photos.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_images = get_sub_field( 'images' );
$dt_ids    = array();

foreach ( is_array( $dt_images ) ? $dt_images : array() as $dt_image ) {
	$dt_ids[] = is_array( $dt_image ) ? (int) ( $dt_image['ID'] ?? 0 ) : (int) $dt_image;
}

$dt_ids = array_values( array_filter( $dt_ids ) );

if ( ! $dt_ids ) {
	return;
}

$dt_eyebrow = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_intro   = trim( (string) get_sub_field( 'intro' ) );
$dt_divider = (bool) get_sub_field( 'divider' );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'gallery-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>"<?php dorotape_animate_attr(); ?>>
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<?php if ( '' !== $dt_eyebrow || '' !== $dt_heading || '' !== $dt_intro ) : ?>
			<div class="gallery-block__header">
				<div class="gallery-block__header-lead">
					<?php if ( '' !== $dt_eyebrow ) : ?>
						<p class="gallery-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $dt_heading ) : ?>
						<h2 class="gallery-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $dt_intro ) : ?>
					<p class="gallery-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		dorotape_photo_gallery(
			$dt_ids,
			array(
				'class'       => 'gallery-block__gallery',
				'sizes_main'  => '(min-width: 1440px) 1060px, (min-width: 576px) 76vw, 92vw',
				'sizes_solo'  => '(min-width: 1440px) 1360px, 92vw',
				'sizes_thumb' => '(min-width: 1440px) 270px, (min-width: 576px) 22vw, 31vw',
			)
		);
		?>
	</div>
</section>
