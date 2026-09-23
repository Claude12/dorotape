<?php
declare( strict_types=1 );
/**
 * Block: USP Strip
 *
 * ACF flexible content layout `usp_strip_block`. From UspStrip.tsx in the
 * signed-off internal pages design: a single row of short promises, each an
 * outline icon beside a label.
 *
 * The icons are a fixed set rather than an upload, because a stroked SVG
 * inherits currentColor and stays crisp; an editor choosing one from the
 * select cannot break the row's weight the way a stray PNG would.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_items = (array) get_sub_field( 'items' );

if ( ! $dt_items ) {
	return;
}

$dt_divider = (bool) get_sub_field( 'divider' );
$dt_plain   = (bool) get_sub_field( 'plain' );

$dt_classes = 'usp-strip-block';
if ( $dt_plain ) {
	$dt_classes .= ' usp-strip-block--plain';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<ul class="usp-strip-block__list">
			<?php foreach ( $dt_items as $dt_item ) : ?>
				<?php
				$dt_label = trim( (string) ( $dt_item['label'] ?? '' ) );
				$dt_icon  = (string) ( $dt_item['icon'] ?? '' );

				if ( '' === $dt_label ) {
					continue;
				}
				?>
				<li class="usp-strip-block__item">
					<?php
					// Trusted markup from the shared set in inc/icons.php, not editor input.
					echo dorotape_icon( $dt_icon, 'usp-strip-block__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hard-coded SVG paths.
					?>
					<span class="usp-strip-block__label"><?php echo esc_html( $dt_label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
