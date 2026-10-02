<?php
/**
 * Template part for the basket, checkout, order received, wishlist and
 * account pages.
 *
 * These are WordPress pages whose content is a WooCommerce block, a
 * WooCommerce shortcode or a YITH shortcode, so there is nothing of ours
 * inside them to style from a block template. What this part does is put the
 * same chrome around them that every other internal page has: a heading and a
 * breadcrumb, then the plugin's own markup inside the band the shop grid and
 * the search results sit in.
 *
 * No banner. The internal pages open on one, with an eyebrow, a standfirst
 * and a background shape; these pages are a job someone came here to finish,
 * and a screenful of introduction above the first field is in the way of it.
 * The heading stays, because a page with no h1 has no outline, and so does
 * the breadcrumb, which is the way back out.
 *
 * The page title is not printed either: these pages are titled "Cart" and
 * "Checkout" in the admin, which is WooCommerce's wording rather than the
 * site's. My Account is one page wearing nine faces, and its heading changes
 * with the face: inc/account-pages.php works out which one is being looked at.
 *
 * The chrome here is shared. inc/woo-pages.php answers for the basket, the
 * checkout, the order received page and the wishlist, and hands the account
 * screens to inc/account-pages.php.
 *
 * @package dorotape
 */

$dt_heading = dorotape_woo_page_heading();
?>

<section class="<?php echo esc_attr( dorotape_woo_page_band_classes() ); ?>">
	<?php dorotape_woo_page_band_shape(); ?>

	<div class="container">
		<header class="woo-page__header">
			<?php dorotape_breadcrumb_nav( 'woo-page__breadcrumb' ); ?>

			<?php if ( '' !== $dt_heading ) : ?>
				<h1 class="woo-page__title"><?php echo esc_html( $dt_heading ); ?></h1>
			<?php endif; ?>
		</header>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'woo-page__inner' ); ?>>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
	</div>
</section>
