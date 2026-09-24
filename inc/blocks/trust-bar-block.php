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
 * The logos are white on black. The stylesheet screens them onto the page, so
 * the black drops out and only the wordmark shows; they sit in greyscale until
 * hovered.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_label = get_sub_field( 'label' );
$dt_rows  = get_sub_field( 'logos' );

$dt_logos = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_image_id = absint( $dt_row['image']['ID'] ?? 0 );

	if ( ! $dt_image_id ) {
		continue;
	}

	$dt_logos[] = array(
		'image_id' => $dt_image_id,
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
$dt_classes = 'trust-bar-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="container">

		<?php if ( $dt_label ) : ?>
			<p class="trust-bar-block__label"><?php echo esc_html( $dt_label ); ?></p>
		<?php endif; ?>

		<ul class="trust-bar-block__logos">
			<?php foreach ( $dt_logos as $dt_logo ) : ?>
				<?php
				$dt_image = wp_get_attachment_image(
					$dt_logo['image_id'],
					'thumbnail',
					false,
					array(
						'class'    => 'trust-bar-block__image',
						'alt'      => $dt_logo['name'],
						'loading'  => $dt_loading,
						'decoding' => 'async',
						'sizes'    => '(min-width: 768px) 80px, 56px',
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

	</div>
</section>
