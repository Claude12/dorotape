<?php
declare( strict_types=1 );
/**
 * Block: Trust Bar
 *
 * ACF flexible content layout `trust_bar_block`. Built from "Brands you can
 * trust" in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/TrustBar.tsx): a small
 * uppercase label over a centred, wrapping row of brand logos.
 *
 * The logos come from the Brands list in Theme Settings, so the homepage, About
 * Us and Our Partners show the same brands, or from the block's own rows for a
 * one-off list. Rows saved before the choice existed have no value, and ACF
 * fills in the default, Theme Settings.
 *
 * By default the logos are white on the page, as the design draws them. The
 * real logos are colour PNGs, so an SVG filter turns each one white, with its
 * light background dropping out; a brand with a proper white version in
 * Theme Settings uses that instead. On a phone the white row is a strip that
 * scrolls sideways under arrows rather than four rows of logos (Site Edits,
 * October 2026). With "Original colour logos" on, they keep their colours and
 * each sits on a white tile, so dark wordmarks stay readable.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_label  = get_sub_field( 'label' );
$dt_colour = (bool) get_sub_field( 'colour_logos' );
$dt_rows   = 'custom' === get_sub_field( 'logo_source' ) ? get_sub_field( 'logos' ) : dorotape_setting( 'brands' );

$dt_logos = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_image_id = absint( $dt_row['image']['ID'] ?? 0 );
	$dt_white_id = absint( $dt_row['white_image']['ID'] ?? 0 );

	if ( ! $dt_image_id ) {
		continue;
	}

	$dt_logos[] = array(
		'image_id' => ! $dt_colour && $dt_white_id ? $dt_white_id : $dt_image_id,
		'white'    => ! $dt_colour && $dt_white_id,
		'name'     => (string) ( $dt_row['name'] ?? '' ),
		'link'     => (string) ( $dt_row['link'] ?? '' ),
	);
}

if ( ! $dt_logos ) {
	return;
}

$dt_loading = 0 === (int) get_query_var( 'block_index', 1 ) ? 'eager' : 'lazy';
$dt_divider = (bool) get_sub_field( 'divider' );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'trust-bar-block' . dorotape_background_shape_class( $dt_shape ) . ( $dt_colour ? ' trust-bar-block--colour' : ' trust-bar-block--white' );

?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>"<?php dorotape_animate_attr(); ?>>
	<?php dorotape_background_shape( $dt_shape ); ?>

	<?php
	if ( ! $dt_colour ) {
		dorotape_logo_white_filter( 'trust-bar-block__filter' );
	}
	?>

	<div class="container<?php echo $dt_colour ? '' : ' js-scroller'; ?>">

		<?php if ( $dt_label ) : ?>
			<p class="trust-bar-block__label"><?php echo esc_html( $dt_label ); ?></p>
		<?php endif; ?>

		<ul class="trust-bar-block__logos<?php echo $dt_colour ? '' : ' js-scroller-track'; ?>">
			<?php foreach ( $dt_logos as $dt_logo ) : ?>
				<?php
				$dt_image = wp_get_attachment_image(
					$dt_logo['image_id'],
					'medium', // The logos are wide; the square thumbnail would crop them.
					false,
					array(
						'class'    => 'trust-bar-block__image' . ( $dt_logo['white'] ? ' trust-bar-block__image--white' : '' ),
						'alt'      => $dt_logo['name'],
						'loading'  => $dt_loading,
						'decoding' => 'async',
						'sizes'    => '200px',
					)
				);
				?>
				<li class="trust-bar-block__item">
					<?php if ( $dt_logo['link'] ) : ?>
						<a class="trust-bar-block__link" href="<?php echo esc_url( $dt_logo['link'] ); ?>">
							<?php echo $dt_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output. ?>
						</a>
					<?php else : ?>
						<?php echo $dt_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output. ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
		if ( ! $dt_colour ) {
			dorotape_scroller_nav( 'trust-bar-block__nav' );
		}
		?>

	</div>
</section>
