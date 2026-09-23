<?php
declare( strict_types=1 );
/**
 * Block: Product Row
 *
 * ACF flexible content layout `product_row_block`. Built from the product
 * rows in the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/ProductRow.tsx), which the
 * homepage uses twice ("New products", "Most popular this season"): an
 * eyebrow and heading with a "View all" link, over a sideways-scrolling
 * strip of product cards, with an optional background shape behind.
 *
 * The design's products are placeholders; these are live WooCommerce
 * products, chosen by rule (newest, featured, on sale, best sellers) or by
 * hand. Which products a row shows is decided in inc/product-row.php; the card
 * itself is inc/product-card.php, shared with Related products on the single
 * product page.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_products = dorotape_product_row_products(
	(string) get_sub_field( 'source' ),
	(int) get_sub_field( 'count' ),
	(array) get_sub_field( 'products' )
);

// A product row with no products is an empty shell; hide it.
if ( ! $dt_products ) {
	return;
}

$dt_divider     = get_sub_field( 'divider' );
$dt_eyebrow     = get_sub_field( 'eyebrow' );
$dt_heading     = get_sub_field( 'heading' );
$dt_link        = get_sub_field( 'link' );
$dt_badge       = get_sub_field( 'badge' );
$dt_hover_label = get_sub_field( 'hover_label' );

// The row used to carry its own cube pattern. It now shares the block-wide
// control, which still understands the old 'left' and 'right' values.
$dt_shape = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );

$dt_classes = 'product-row-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container product-row-block__container">

		<?php if ( $dt_eyebrow || $dt_heading || ! empty( $dt_link['url'] ) ) : ?>
			<div class="product-row-block__header">
				<div>
					<?php if ( $dt_eyebrow ) : ?>
						<p class="product-row-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $dt_heading ) : ?>
						<h2 class="product-row-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $dt_link['url'] ) && ! empty( $dt_link['title'] ) ) : ?>
					<a class="product-row-block__view-all" href="<?php echo esc_url( $dt_link['url'] ); ?>"<?php echo ! empty( $dt_link['target'] ) ? ' target="' . esc_attr( $dt_link['target'] ) . '" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $dt_link['title'] ); ?>
						<svg class="product-row-block__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<ul class="product-row-block__track">
			<?php foreach ( $dt_products as $dt_product ) : ?>
				<li class="product-row-block__item">
					<?php
					dorotape_product_card(
						$dt_product,
						array(
							'badge'       => (string) $dt_badge,
							'hover_label' => (string) $dt_hover_label,
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
