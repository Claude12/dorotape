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
 * hand. Prices come from get_price_html(), so they match the shop exactly:
 * unit suffix, POA, trade prices and all. Helpers are in inc/product-row.php.
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
				<?php
				$dt_saving = dorotape_product_row_saving( $dt_product );
				$dt_tag    = $dt_saving ? '-' . $dt_saving . '%' : (string) $dt_badge;
				$dt_meta   = dorotape_product_row_meta( $dt_product );
				$dt_price  = $dt_product->get_price_html();
				?>
				<li class="product-row-block__item">
					<a class="product-row-block__card" href="<?php echo esc_url( $dt_product->get_permalink() ); ?>">
						<div class="product-row-block__media">
							<?php if ( $dt_product->get_image_id() ) : ?>
								<?php
								// Empty alt: the product name is the link text just
								// below, so screen readers would say it twice.
								echo wp_get_attachment_image(
									$dt_product->get_image_id(),
									'medium_large',
									false,
									array(
										'class'    => 'product-row-block__image',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(min-width: 1024px) 330px, (min-width: 576px) 46vw, 74vw',
									)
								);
								?>
							<?php else : ?>
								<span class="product-row-block__image product-row-block__image--empty"></span>
							<?php endif; ?>

							<?php if ( '' !== $dt_tag ) : ?>
								<span class="product-row-block__tag"><?php echo esc_html( $dt_tag ); ?></span>
							<?php endif; ?>

							<?php if ( $dt_hover_label ) : ?>
								<span class="product-row-block__cta" aria-hidden="true"><?php echo esc_html( $dt_hover_label ); ?></span>
							<?php endif; ?>
						</div>

						<div class="product-row-block__body">
							<h3 class="product-row-block__name"><?php echo esc_html( $dt_product->get_name() ); ?></h3>

							<?php if ( $dt_meta ) : ?>
								<p class="product-row-block__meta"><?php echo esc_html( $dt_meta ); ?></p>
							<?php endif; ?>

							<?php if ( $dt_price ) : ?>
								<p class="product-row-block__price"><?php echo wp_kses_post( $dt_price ); ?></p>
							<?php endif; ?>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
