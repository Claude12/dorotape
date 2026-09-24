<?php
declare( strict_types=1 );
/**
 * Block: Embed
 *
 * ACF flexible content layout `embed_block`. One field that takes either a
 * shortcode or an iframe, in a band with the usual optional heading, so a map,
 * a booking widget or a data sheet viewer can go on a page without a block
 * being written for each one.
 *
 * Built for the contact page's map and kept generic on purpose. A Google Maps
 * block would be a block that only ever does Google Maps; this does the map
 * and everything after it, and it is the same shortcode field the contact
 * block puts the form in.
 *
 * What it will render:
 *
 * - Anything containing a `[` goes through do_shortcode(), so a plugin's own
 *   output comes out untouched, as it does everywhere else in WordPress.
 * - Anything else goes through wp_kses() with an iframe allowed and https as
 *   the only protocol. Pasted embed code is third-party markup an editor has
 *   copied from somewhere, so it is filtered rather than trusted: the tag list
 *   below is what a map or a video embed needs and nothing more, and a
 *   `javascript:` or `http:` src does not survive it.
 *
 * The aspect ratio is a choice rather than the pasted height attribute,
 * because a fixed height is a letterbox on a phone and a stripe on a desktop.
 *
 * Rendered inside the have_rows()/the_row() loop in
 * dorotape_render_flexible_content() (inc/acf.php).
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_code = trim( (string) get_sub_field( 'embed_code' ) );

if ( '' === $dt_code ) {
	return;
}

if ( str_contains( $dt_code, '[' ) ) {
	$dt_markup = do_shortcode( $dt_code );
} else {
	$dt_markup = wp_kses(
		$dt_code,
		array(
			'iframe' => array(
				'src'             => true,
				'title'           => true,
				'width'           => true,
				'height'          => true,
				'loading'         => true,
				'allow'           => true,
				'allowfullscreen' => true,
				'referrerpolicy'  => true,
				'frameborder'     => true,
				'class'           => true,
			),
			'div'    => array( 'class' => true ),
			'p'      => array( 'class' => true ),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		),
		array( 'https' )
	);
}

// Everything the editor pasted was stripped, which means it was neither a
// shortcode nor markup this block will render. An empty framed box would
// read as a broken map rather than as nothing having been entered.
if ( '' === trim( $dt_markup ) ) {
	return;
}

$dt_eyebrow = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_intro   = trim( (string) get_sub_field( 'intro' ) );
$dt_divider = (bool) get_sub_field( 'divider' );
$dt_full    = 'full' === (string) get_sub_field( 'width' );
$dt_ratio   = (string) get_sub_field( 'ratio' );

$dt_has_header = '' !== $dt_eyebrow || '' !== $dt_heading || '' !== $dt_intro;

$dt_is_first    = 0 === (int) get_query_var( 'block_index', 1 );
$dt_heading_tag = $dt_is_first ? 'h1' : 'h2';

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'embed-block';
if ( $dt_full ) {
	$dt_classes .= ' embed-block--full';
}
$dt_classes .= dorotape_background_shape_class( $dt_shape );

// `--ratio` does the positioning, the ratio class supplies the number. Two
// classes rather than one so an embed that brings its own height ("auto")
// simply drops both and is left to size itself.
$dt_frame = 'embed-block__frame';
if ( '' !== $dt_ratio && 'auto' !== $dt_ratio ) {
	$dt_frame .= ' embed-block__frame--ratio embed-block__frame--' . $dt_ratio;
}
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<?php if ( $dt_has_header ) : ?>
		<div class="container">
			<div class="embed-block__header">

				<?php if ( '' !== $dt_eyebrow ) : ?>
					<p class="embed-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $dt_heading ) : ?>
					<<?php echo esc_html( $dt_heading_tag ); ?> class="embed-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>
				<?php endif; ?>

				<?php if ( '' !== $dt_intro ) : ?>
					<p class="embed-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>

			</div>
		</div>
	<?php endif; ?>

	<?php if ( $dt_full ) : ?>
		<div class="<?php echo esc_attr( $dt_frame ); ?>">
			<?php echo $dt_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered above by wp_kses(), or shortcode output escaped by the plugin that renders it. ?>
		</div>
	<?php else : ?>
		<div class="container">
			<div class="<?php echo esc_attr( $dt_frame ); ?>">
				<?php echo $dt_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered above by wp_kses(), or shortcode output escaped by the plugin that renders it. ?>
			</div>
		</div>
	<?php endif; ?>
</section>
