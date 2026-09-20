<?php
declare( strict_types=1 );
/**
 * Block: Rich Text
 *
 * ACF flexible content layout `rte_block`: a single WYSIWYG field rendered
 * full width. This is the replacement for the classic editor content box on
 * pages that are built from blocks, so ordinary prose has somewhere to live.
 *
 * Rendered inside the have_rows()/the_row() loop in
 * dorotape_render_flexible_content() (inc/acf.php), so get_sub_field() reads
 * from the current row.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_content    = get_sub_field( 'content' );
$dt_background = get_sub_field( 'background_color' );
$dt_centered   = get_sub_field( 'centered' );

// An empty WYSIWYG still returns an empty string, so check for real content
// before printing a section with its full vertical padding.
if ( ! $dt_content || '' === trim( wp_strip_all_tags( $dt_content ) ) ) {
	return;
}

$dt_classes = 'rte-block';
if ( $dt_background ) {
	$dt_classes .= ' rte-block--bg-' . $dt_background;
}
if ( $dt_centered ) {
	$dt_classes .= ' rte-block--centered';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );
?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="container">
		<div class="rte-block__inner">
			<div class="rte"><?php echo wp_kses_post( $dt_content ); ?></div>
		</div>
	</div>
</section>
