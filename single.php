<?php
declare( strict_types=1 );
/**
 * A blog article.
 *
 * The internal banner with the article's category and date, the photos, the
 * text at the Rich Text block's measure, all centred on the page, the
 * previous and next articles with a link back to the blog (inc/post-nav.php),
 * and three more articles. The wording around the article is on Theme Settings > Blog
 * Page (inc/blog.php).
 *
 * Any other post type without a template of its own still gets the plain
 * Underscores markup it had.
 *
 * @package dorotape
 */

get_header();

if ( 'post' !== get_post_type() ) :
	?>
	<main id="primary" class="site-main">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );
		endwhile;
		?>
	</main>
	<?php
	get_footer();
	return;
endif;

while ( have_posts() ) :
	the_post();

	$dt_id       = get_the_ID();
	$dt_category = dorotape_post_category( $dt_id );
	$dt_photos   = dorotape_post_photos( $dt_id );
	$dt_related  = dorotape_related_posts( $dt_id );
	$dt_products = dorotape_post_products( $dt_id );
	$dt_date     = str_replace(
		'{date}',
		dorotape_post_date( $dt_id ),
		dorotape_blog_field( 'post_date_label', __( 'Posted {date}', 'dorotape' ) )
	);
	?>

	<main id="primary" class="site-main site-main--blocks">

		<?php
		dorotape_page_banner(
			array(
				'eyebrow' => $dt_category ? $dt_category->name : dorotape_blog_field( 'blog_eyebrow', __( 'Blog', 'dorotape' ) ),
				'heading' => get_the_title(),
				'intro'   => $dt_date,
				'shape'   => dorotape_blog_field( 'blog_banner_shape', 'cubes-right' ),
				'centred' => true,
			)
		);
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-article' ); ?>>
			<div class="container">
				<div class="post-article__inner">

					<?php if ( $dt_photos ) : ?>
						<?php
						// One photo large with every photo as a thumbnail beside it, as the old
						// site's articles had them (Michael, 28 October 2026). A thumbnail
						// swaps the large one (lib/post-gallery.js); without scripts it opens
						// the full-size file instead.
						$dt_photo_label = dorotape_blog_field( 'post_photo_label', __( 'Show photo {number}', 'dorotape' ) );
						$dt_has_thumbs  = count( $dt_photos ) > 1;
						?>
						<div class="post-gallery<?php echo $dt_has_thumbs ? ' post-gallery--thumbs' : ''; ?>" data-post-gallery>
							<div class="post-gallery__main">
								<?php foreach ( $dt_photos as $dt_index => $dt_photo ) : ?>
									<?php
									$dt_attrs = array(
										'class'    => 'post-gallery__photo',
										'loading'  => 0 === $dt_index ? 'eager' : 'lazy',
										'decoding' => 'async',
										'sizes'    => $dt_has_thumbs ? '(min-width: 880px) 620px, 75vw' : '(min-width: 880px) 800px, 92vw',
									);
									if ( 0 !== $dt_index ) {
										$dt_attrs['hidden'] = 'hidden';
									}
									echo wp_get_attachment_image( $dt_photo, 'large', false, $dt_attrs );
									?>
								<?php endforeach; ?>
							</div>

							<?php if ( $dt_has_thumbs ) : ?>
								<ul class="post-gallery__thumbs">
									<?php foreach ( $dt_photos as $dt_index => $dt_photo ) : ?>
										<li>
											<a
												class="post-gallery__thumb<?php echo 0 === $dt_index ? ' is-active' : ''; ?>"
												href="<?php echo esc_url( (string) wp_get_attachment_image_url( $dt_photo, 'full' ) ); ?>"
												aria-label="<?php echo esc_attr( str_replace( '{number}', (string) ( $dt_index + 1 ), $dt_photo_label ) ); ?>"
												<?php echo 0 === $dt_index ? 'aria-current="true"' : ''; ?>
											>
												<?php
												echo wp_get_attachment_image(
													$dt_photo,
													'medium',
													false,
													array(
														'class'    => 'post-gallery__thumb-image',
														'alt'      => '',
														'loading'  => 'lazy',
														'decoding' => 'async',
														'sizes'    => '(min-width: 880px) 180px, 22vw',
													)
												);
												?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="rte post-article__content">
						<?php the_content(); ?>
					</div>

				</div>
			</div>
		</article>

		<?php
		// Previous is the older article, next the newer one, as WordPress has it.
		$dt_prev = get_previous_post();
		$dt_next = get_next_post();

		dorotape_post_nav(
			array(
				'prev'       => $dt_prev instanceof WP_Post ? $dt_prev->ID : 0,
				'next'       => $dt_next instanceof WP_Post ? $dt_next->ID : 0,
				'prev_label' => dorotape_blog_field( 'post_prev_label', __( 'Previous article', 'dorotape' ) ),
				'next_label' => dorotape_blog_field( 'post_next_label', __( 'Next article', 'dorotape' ) ),
				'back'       => array(
					'url'   => dorotape_blog_url(),
					'title' => dorotape_blog_field( 'post_back_label', __( 'Back to the blog', 'dorotape' ) ),
				),
			)
		);
		?>

		<?php if ( $dt_products ) : ?>
			<section class="product-row-block post-products">
				<div class="container product-row-block__container">
					<div class="product-row-block__header">
						<div>
							<h2 class="product-row-block__heading"><?php echo esc_html( dorotape_blog_field( 'post_products_heading', __( 'Products featured in this blog', 'dorotape' ) ) ); ?></h2>
						</div>
					</div>

					<ul class="product-row-block__track">
						<?php foreach ( $dt_products as $dt_product ) : ?>
							<li class="product-row-block__item">
								<?php dorotape_product_card( $dt_product ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $dt_related ) : ?>
			<section class="<?php echo esc_attr( dorotape_blog_band_classes() . ' blog-band--related' ); ?>">
				<?php dorotape_blog_band_shape(); ?>
				<div class="container">
					<div class="category-grid-block__header">
						<h2 class="category-grid-block__heading"><?php echo esc_html( dorotape_blog_field( 'post_related_heading', __( 'More from the blog', 'dorotape' ) ) ); ?></h2>
					</div>
					<ul class="category-grid-block__grid post-grid">
						<?php foreach ( $dt_related as $dt_related_id ) : ?>
							<?php dorotape_post_card( $dt_related_id, 'h3' ); ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
