<?php
declare( strict_types=1 );
/**
 * The template for a site-wide search.
 *
 * Reached by a bare `?s=`: a browser's own site search, an old link, or a
 * screen reader's quick search. The header's form posts `post_type=product`
 * and is handled by WooCommerce's archive instead, repainted in
 * inc/search.php, and everything this page draws comes from there so the two
 * routes are one page.
 *
 * Results are split into products and everything else rather than interleaved.
 * A page and a product are different things to find and are worth different
 * cards, and a customer scanning for a material should not have to read past
 * the delivery policy to reach it.
 *
 * @package dorotape
 */

get_header();

$dt_products = array();
$dt_pages    = array();

/*
 * The main query, bucketed once. Pagination still counts both kinds together,
 * which is what makes the page numbers underneath honest: page two is the
 * next twelve results, not the next twelve of one kind.
 */
while ( have_posts() ) :
	the_post();

	if ( 'product' === get_post_type() && function_exists( 'wc_get_product' ) ) {
		$dt_product = wc_get_product( get_the_ID() );

		if ( $dt_product instanceof WC_Product ) {
			$dt_products[] = $dt_product;
			continue;
		}
	}

	$dt_pages[] = get_the_ID();
endwhile;

$dt_button = dorotape_search_field( 'search_button', __( 'Read more', 'dorotape' ) );
?>

<main id="primary" class="site-main site-main--blocks">

	<?php dorotape_search_banner(); ?>

	<?php if ( ! $dt_products && ! $dt_pages ) : ?>

		<?php dorotape_search_empty(); ?>

	<?php else : ?>

		<?php
		/*
		 * One band, two grids. Products and pages are different enough to earn
		 * their own heading and their own card, but not their own section: two
		 * bands means two of the corner glows and two of the dividing borders,
		 * and the seam between them reads as a break in the page rather than as
		 * a change of subject.
		 */
		?>
		<section class="<?php echo esc_attr( dorotape_search_band_classes() ); ?>" animate="fade-in-up" animate-offset="0">
			<?php dorotape_search_band_shape(); ?>

			<div class="container">

				<?php if ( $dt_products ) : ?>
					<?php dorotape_search_band_header( dorotape_search_field( 'search_products_heading', __( 'Products', 'dorotape' ) ) ); ?>

					<div class="category-shop__products">
						<ul class="category-shop__grid category-shop__grid--wide">
							<?php foreach ( $dt_products as $dt_product ) : ?>
								<li class="category-shop__item">
									<?php
									/*
									 * Set per card because this is not the
									 * WooCommerce loop: it is what makes the add to
									 * cart button pick the right label and behaviour
									 * for each product type.
									 */
									$GLOBALS['product'] = $dt_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required by WooCommerce loop templates.

									dorotape_product_card( $dt_product, dorotape_search_card_args( $dt_product ) );
									?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php unset( $GLOBALS['product'] ); ?>
				<?php endif; ?>

				<?php if ( $dt_pages ) : ?>
					<?php
					// --stacked only when it follows the product grid: on its own it
					// is the band's first heading and needs no space above it.
					dorotape_search_band_header(
						dorotape_search_field( 'search_pages_heading', __( 'Pages and guides', 'dorotape' ) ),
						(bool) $dt_products
					);
					?>

					<ul class="category-grid-block__grid">
						<?php foreach ( $dt_pages as $dt_page_id ) : ?>
							<?php
							dorotape_link_card(
								array(
									'url'      => (string) get_permalink( $dt_page_id ),
									'title'    => (string) get_the_title( $dt_page_id ),
									'button'   => $dt_button,
									'image_id' => (int) get_post_thumbnail_id( $dt_page_id ),
								)
							);
							?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php dorotape_search_pagination(); ?>

			</div>
		</section>

	<?php endif; ?>

	<?php dorotape_render_search_sections(); ?>

</main>

<?php
get_footer();
