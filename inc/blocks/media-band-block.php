<?php
declare( strict_types=1 );
/**
 * Block: Media Band
 *
 * ACF flexible content layout `media_band_block`. A single full-width image
 * in the design's rounded, hairline-bordered frame, used on the About page
 * to break the copy with the building sign and again with the floor shot.
 *
 * Deliberately just an image: the design's caption work lives in
 * text_image_block, and duplicating it here would give editors two places to
 * do the same job.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_image = get_sub_field( 'image' );

if ( empty( $dt_image['ID'] ) ) {
	return;
}

$dt_divider  = (bool) get_sub_field( 'divider' );
$dt_tall     = (bool) get_sub_field( 'tall' );
$dt_is_first = 0 === (int) get_query_var( 'block_index', 1 );

$dt_classes = 'media-band-block';
if ( $dt_tall ) {
	$dt_classes .= ' media-band-block--tall';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );

// The frame is wider than any single column, so WordPress's automatic sizes
// attribute would under-request. The band is container width throughout.
add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

$dt_markup = wp_get_attachment_image(
	(int) $dt_image['ID'],
	'full',
	false,
	array(
		'class'         => 'media-band-block__image',
		'loading'       => $dt_is_first ? 'eager' : 'lazy',
		'fetchpriority' => $dt_is_first ? 'high' : 'auto',
		'decoding'      => 'async',
		'sizes'         => '(min-width: 1440px) 1400px, 100vw',
	)
);

remove_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<figure class="media-band-block__frame">
			<?php
			// Built by wp_get_attachment_image() above, already escaped.
			echo $dt_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core generated markup.
			?>
		</figure>
	</div>
</section>
