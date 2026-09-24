<?php
declare( strict_types=1 );
/**
 * The product card, and the grid that holds a list of them.
 *
 * One card, used by the Product Row block on the homepage and by Related
 * products on a single product page. It was written for the row first; this
 * file is that markup lifted out unchanged so the two places cannot drift.
 *
 * dorotape_product_list_open() at the foot is the same idea one level up: the
 * shop, the search page, a category and Related products all put these cards
 * in the same columns, so the elements around them are written once here.
 *
 * Two link modes, because the two places need different insides:
 *
 * - 'card'  wraps the whole card in one <a>. That is the homepage row, where
 *           a card holds nothing but the link, so the largest possible
 *           target is also the simplest markup.
 * - 'title' links the image and the name separately and leaves the card an
 *           <article>. Related products needs this: it carries an add to cart
 *           button, and a button inside an anchor is invalid HTML and
 *           unusable with a keyboard.
 *
 * Prices come from get_price_html() in both, so a card matches the shop
 * exactly: unit suffix, POA, trade prices and all.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The short line under a card's product name: its most specific category,
 * e.g. "ASLAN Blockout Films" rather than the top-level range it sits in.
 *
 * @param WC_Product $product Product to describe.
 * @return string Empty when the product has no category.
 */
function dorotape_product_card_meta( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( ! is_array( $terms ) || ! $terms ) {
		return '';
	}

	$best       = null;
	$best_depth = -1;

	foreach ( $terms as $term ) {
		$depth = count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );

		if ( $depth > $best_depth ) {
			$best       = $term;
			$best_depth = $depth;
		}
	}

	return $best ? $best->name : '';
}

/**
 * The largest saving on a product that is on sale, as a whole percentage.
 * For a variable product that is the best saving across its variations.
 *
 * @param WC_Product $product Product to measure.
 * @return int 0 when the product is not on sale.
 */
function dorotape_product_card_saving( WC_Product $product ): int {
	if ( ! $product->is_on_sale() ) {
		return 0;
	}

	$pairs = array();

	if ( $product instanceof WC_Product_Variable ) {
		$prices = $product->get_variation_prices();

		foreach ( $prices['regular_price'] as $variation_id => $regular ) {
			$pairs[] = array( (float) $regular, (float) ( $prices['sale_price'][ $variation_id ] ?? $regular ) );
		}
	} else {
		$pairs[] = array( (float) $product->get_regular_price(), (float) $product->get_sale_price() );
	}

	$best = 0;

	foreach ( $pairs as [ $regular, $sale ] ) {
		if ( $regular > 0 && $sale < $regular ) {
			$best = max( $best, (int) round( ( $regular - $sale ) / $regular * 100 ) );
		}
	}

	return $best;
}

/**
 * Render one product card.
 *
 * @param WC_Product $product Product to show.
 * @param array{
 *     link?:string,
 *     badge?:string,
 *     hover_label?:string,
 *     add_to_cart?:bool,
 *     sizes?:string,
 *     modifier?:string,
 *     pills?:array<int, array{label:string, tone?:string}>
 * } $args link: 'card' (default) or 'title'. badge: fallback tag text, used
 *         only when the product is not on sale. hover_label: the pill that
 *         rises over the image on hover, 'card' mode only. add_to_cart:
 *         append WooCommerce's own loop button, 'title' mode only. sizes: the
 *         image sizes attribute, since the card is a different width in each
 *         place. modifier: extra class on the card wrapper. pills: small
 *         labels shown in place of the category line, with tone 'cyan' for
 *         the lit one; a leaf category uses these, where every card sits in
 *         the same category and naming it on each one says nothing.
 */
function dorotape_product_card( WC_Product $product, array $args = array() ): void {
	$dt_link        = 'title' === ( $args['link'] ?? 'card' ) ? 'title' : 'card';
	$dt_saving      = dorotape_product_card_saving( $product );
	$dt_tag         = $dt_saving ? '-' . $dt_saving . '%' : (string) ( $args['badge'] ?? '' );
	$dt_meta        = dorotape_product_card_meta( $product );
	$dt_price       = $product->get_price_html();
	$dt_hover_label = 'card' === $dt_link ? (string) ( $args['hover_label'] ?? '' ) : '';
	$dt_add_to_cart = 'title' === $dt_link && ! empty( $args['add_to_cart'] );
	$dt_sizes       = (string) ( $args['sizes'] ?? '(min-width: 1024px) 330px, (min-width: 576px) 46vw, 74vw' );
	$dt_pills       = is_array( $args['pills'] ?? null ) ? $args['pills'] : array();
	$dt_classes     = 'product-card' . ( ! empty( $args['modifier'] ) ? ' ' . $args['modifier'] : '' );
	$dt_url         = $product->get_permalink();
	// Thirty-nine of the catalogue's products carry no photo. A grid of flat
	// grey panels reads as broken, so they borrow the same Theme Settings
	// picture the link cards use rather than each card type deciding alone.
	$dt_image_id    = dorotape_card_image_id( (int) $product->get_image_id() );
	$dt_name        = $product->get_name();
	?>

	<?php if ( 'card' === $dt_link ) : ?>
		<div class="<?php echo esc_attr( $dt_classes ); ?>">
			<a class="product-card__link" href="<?php echo esc_url( $dt_url ); ?>">
	<?php else : ?>
		<article class="<?php echo esc_attr( $dt_classes ); ?>">
			<?php
			/*
			 * The image repeats the link on the name below it, so it is
			 * hidden from assistive technology and taken out of the tab
			 * order: a keyboard user would otherwise tab twice through
			 * every card to reach the add to cart button.
			 */
			?>
			<a class="product-card__media-link" href="<?php echo esc_url( $dt_url ); ?>" tabindex="-1" aria-hidden="true">
	<?php endif; ?>

				<div class="product-card__media">
					<?php if ( $dt_image_id ) : ?>
						<?php
						// Empty alt: the product name is the link text just
						// below, so screen readers would say it twice.
						echo wp_get_attachment_image(
							$dt_image_id,
							'medium_large',
							false,
							array(
								'class'    => 'product-card__image',
								'alt'      => '',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'sizes'    => $dt_sizes,
							)
						);
						?>
					<?php else : ?>
						<span class="product-card__image product-card__image--empty"></span>
					<?php endif; ?>

					<?php if ( '' !== $dt_tag ) : ?>
						<span class="product-card__tag"><?php echo esc_html( $dt_tag ); ?></span>
					<?php endif; ?>

					<?php if ( '' !== $dt_hover_label ) : ?>
						<span class="product-card__cta" aria-hidden="true"><?php echo esc_html( $dt_hover_label ); ?></span>
					<?php endif; ?>
				</div>

	<?php if ( 'title' === $dt_link ) : ?>
			</a>
	<?php endif; ?>

				<div class="product-card__body">
					<h3 class="product-card__name">
						<?php if ( 'title' === $dt_link ) : ?>
							<a class="product-card__name-link" href="<?php echo esc_url( $dt_url ); ?>"><?php echo esc_html( $dt_name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $dt_name ); ?>
						<?php endif; ?>
					</h3>

					<?php if ( $dt_pills ) : ?>
						<ul class="product-card__pills">
							<?php foreach ( $dt_pills as $dt_pill ) : ?>
								<li class="product-card__pill<?php echo 'cyan' === ( $dt_pill['tone'] ?? '' ) ? ' product-card__pill--cyan' : ''; ?>">
									<?php echo esc_html( $dt_pill['label'] ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php elseif ( '' !== $dt_meta ) : ?>
						<p class="product-card__meta"><?php echo esc_html( $dt_meta ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $dt_price ) : ?>
						<p class="product-card__price"><?php echo wp_kses_post( $dt_price ); ?></p>
					<?php endif; ?>

					<?php if ( $dt_add_to_cart ) : ?>
						<?php
						/*
						 * WooCommerce's own loop button, so the label and the
						 * behaviour stay right for every product type: "Add to
						 * cart" with AJAX on a simple product, "Select options"
						 * on a variable one, "Read more" when it cannot be
						 * bought. Writing our own would mean re-deciding all of
						 * that, and getting it wrong on the cut-size, quick-add
						 * and price-on-application products that make up much
						 * of this catalogue.
						 *
						 * It reads the global, which the loop this runs inside
						 * has already set to this product.
						 */
						woocommerce_template_loop_add_to_cart( array( 'class' => 'button product-card__button' ) );
						?>
					<?php endif; ?>
				</div>

	<?php if ( 'card' === $dt_link ) : ?>
			</a>
		</div>
	<?php else : ?>
		</article>
	<?php endif; ?>
	<?php
}

/**
 * Build an attribute string from a name => value map.
 *
 * A value of '' writes the name on its own, which is what `hidden` and the
 * `data-` flags the filter script looks for need.
 *
 * @param array<string, string> $attributes Attributes to write.
 * @return string Leading space included when there is anything to write.
 */
function dorotape_product_list_attributes( array $attributes ): string {
	$out = '';

	foreach ( $attributes as $name => $value ) {
		$out .= ' ' . esc_attr( $name );

		if ( '' !== $value ) {
			$out .= '="' . esc_attr( $value ) . '"';
		}
	}

	return $out;
}

/**
 * Open a grid of product cards.
 *
 * The one place this markup is written. The shop, the search page, a category
 * and Related products all showed the same cards in the same columns, and
 * each had its own copy of the two elements around them, which is how the
 * shop ended up stepping to three columns at a different width from the rest.
 *
 * Cards go inside as <li class="product-list__item">, or with the class from
 * dorotape_product_list_item_class() where WooCommerce owns the element.
 *
 * @param array{panel?:bool, attributes?:array<string, string>} $args
 *     panel:      true when the 280px filter panel sits beside the grid, which
 *                 makes it three columns rather than four.
 *     attributes: extra attributes for the <ul>, e.g. the filter script's.
 * @return string
 */
function dorotape_product_list_open( array $args = array() ): string {
	$panel      = (bool) ( $args['panel'] ?? false );
	$attributes = (array) ( $args['attributes'] ?? array() );

	$classes = 'product-list__items' . ( $panel ? ' product-list__items--panel' : '' );

	return '<div class="product-list"><ul class="' . esc_attr( $classes ) . '"' . dorotape_product_list_attributes( $attributes ) . '>';
}

/**
 * Close it.
 *
 * @param string $after Markup to put after the grid and inside the wrapper,
 *                      which is where the empty message belongs when it ships
 *                      hidden alongside a full grid.
 * @return string
 */
function dorotape_product_list_close( string $after = '' ): string {
	return '</ul>' . $after . '</div>';
}

/**
 * The class for one card's <li>.
 *
 * Given its own function because WooCommerce writes that element itself in
 * its loop, so two of the four callers add the class through a filter rather
 * than writing the tag.
 */
function dorotape_product_list_item_class(): string {
	return 'product-list__item';
}

/**
 * The message shown in place of the grid.
 *
 * No results, an empty category, or a filter combination that matches
 * nothing. It is the same box in all of them.
 *
 * @param string                $message    Already-translated text.
 * @param array<string, string> $attributes Extra attributes, e.g. `hidden`.
 * @return string
 */
function dorotape_product_list_message( string $message, array $attributes = array() ): string {
	return '<p class="product-list__empty"' . dorotape_product_list_attributes( $attributes ) . '>' . esc_html( $message ) . '</p>';
}
