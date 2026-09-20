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

// Lucide paths, matching the design's icon set. Kept local to the template:
// they are markup for this block alone and nothing else needs to read them.
$dt_icons = array(
	'truck'     => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
	'lightbulb' => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
	'calendar'  => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>',
	'swatch'    => '<path d="M11 17a4 4 0 0 1-8 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2Z"/><path d="M16.7 13H19a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H7"/><path d="M 7 17h.01"/><path d="m11 8 2.3-2.3a2.4 2.4 0 0 1 3.404.004L18.6 7.6a2.4 2.4 0 0 1 .026 3.434L9.9 19.8"/>',
	'globe'     => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
	'shield'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
	'award'     => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/>',
);
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
					<?php if ( isset( $dt_icons[ $dt_icon ] ) ) : ?>
						<svg class="usp-strip-block__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
							<?php
							// Trusted markup from the local map above, not editor input.
							echo $dt_icons[ $dt_icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hard-coded SVG paths.
							?>
						</svg>
					<?php endif; ?>
					<span class="usp-strip-block__label"><?php echo esc_html( $dt_label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
