<?php
declare( strict_types=1 );
/**
 * The blog: WordPress's own posts and categories.
 *
 * Articles are ordinary posts, written in the classic editor like any other
 * WordPress blog, filed under ordinary categories, with a featured image for
 * the card and an excerpt for the card's summary. The page set as Posts page
 * in Settings > Reading is the listing (home.php), each category has its own
 * listing (category.php), and an article is single.php.
 *
 * Only two things are the theme's. The wording around the articles (the
 * banner, the card button, the labels on an article) lives on Theme Settings >
 * Blog Page, the way the search page's and the 404's does, because the
 * listing has no page body of its own to hold it: WordPress hides the editor
 * on the Posts page. And the photos at the top of an article are an ACF
 * gallery (acf-json/group_dorotape_blog_post.json) rather than images in the
 * text, since every article on the old site opened with a run of them and a
 * gallery is something an editor can reorder without touching the copy.
 *
 * There is no blog design, so nothing here is invented: the banner is the
 * internal banner, the band and cards are the category grid's, and the text
 * is the Rich Text block's measure and styles.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also what the field groups' location rules
 * match on.
 */
const DOROTAPE_BLOG_SECTIONS_SLUG = 'dorotape-blog-page';

/**
 * The ACF post id the blog's wording and sections are stored under.
 */
const DOROTAPE_BLOG_SECTIONS_ID = 'dorotape_blog_page';

/**
 * Register the options page under Theme Settings.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Blog Page', 'dorotape' ),
				'menu_title'      => __( 'Blog Page', 'dorotape' ),
				'menu_slug'       => DOROTAPE_BLOG_SECTIONS_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_BLOG_SECTIONS_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save blog page', 'dorotape' ),
				'updated_message' => __( 'Blog page saved.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a text field from the Blog Page options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_blog_field( string $name, string $default = '' ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_BLOG_SECTIONS_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * True on the blog listing or one of its category pages.
 */
function dorotape_is_blog_listing(): bool {
	return ( is_home() && ! is_front_page() ) || is_category();
}

/**
 * Twelve articles a page, a whole number of rows at three across and two.
 *
 * Settings > Reading's own number is left alone because the site-wide search
 * pages by it too.
 *
 * @param WP_Query $query The query being set up.
 */
function dorotape_blog_per_page( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_home() || $query->is_category() ) {
		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'dorotape_blog_per_page' );

/**
 * The page the blog lives on, for links back to it.
 */
function dorotape_blog_url(): string {
	$page_id = (int) get_option( 'page_for_posts' );

	return $page_id ? (string) get_permalink( $page_id ) : home_url( '/' );
}

/**
 * The categories worth a filter button: the ones with articles in them.
 *
 * WordPress's Uncategorized is left out even when something lands in it,
 * because a button labelled Uncategorized tells a reader nothing.
 *
 * @return array<int, WP_Term>
 */
function dorotape_blog_categories(): array {
	$terms = get_categories(
		array(
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
			'orderby'    => 'name',
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * The category an article is filed under first, for its eyebrow and card.
 *
 * Rank Math's primary category when one is set, so the card, the eyebrow and
 * the breadcrumb agree, otherwise the first by name.
 *
 * @param int $post_id Article.
 */
function dorotape_post_category( int $post_id ): ?WP_Term {
	$primary = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );

	if ( $primary ) {
		$term = get_term( $primary, 'category' );

		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	$terms = get_the_category( $post_id );

	return $terms ? $terms[0] : null;
}

/**
 * The article's date, in the site's date format.
 *
 * @param int $post_id Article.
 */
function dorotape_post_date( int $post_id ): string {
	return (string) get_the_date( '', $post_id );
}

/**
 * The banner on the listing and on a category page.
 */
function dorotape_blog_banner(): void {
	$heading = dorotape_blog_field( 'blog_heading', __( 'Our blogs', 'dorotape' ) );
	$intro   = dorotape_blog_field( 'blog_intro' );

	if ( is_category() ) {
		$term    = get_queried_object();
		$heading = $term instanceof WP_Term ? $term->name : $heading;
		$intro   = $term instanceof WP_Term ? trim( wp_strip_all_tags( $term->description ) ) : '';
	}

	dorotape_page_banner(
		array(
			'eyebrow' => dorotape_blog_field( 'blog_eyebrow', __( 'Blog', 'dorotape' ) ),
			'heading' => $heading,
			'intro'   => $intro,
			'shape'   => dorotape_blog_field( 'blog_banner_shape', 'cubes-right' ),
		)
	);
}

/**
 * The classes on the band the cards sit in.
 *
 * The search page's band: the category grid shell with its soft glow, tight
 * under the banner.
 */
function dorotape_blog_band_classes(): string {
	$shape = dorotape_background_shape_value( dorotape_blog_field( 'blog_results_shape', 'cubes-right' ) );

	return 'category-grid-block category-grid-block--glow-soft category-grid-block--tight blog-band' . dorotape_background_shape_class( $shape );
}

/**
 * The band's background shape.
 */
function dorotape_blog_band_shape(): void {
	dorotape_background_shape( dorotape_background_shape_value( dorotape_blog_field( 'blog_results_shape', 'cubes-right' ) ) );
}

/**
 * The category buttons above the cards.
 *
 * Links rather than a script filter: each category is its own page with its
 * own address, which is what the old site had and what search engines have
 * indexed.
 */
function dorotape_blog_filters(): void {
	$terms = dorotape_blog_categories();

	if ( count( $terms ) < 2 ) {
		return;
	}

	$current = is_category() ? (int) get_queried_object_id() : 0;
	$links   = array(
		array(
			'label'   => dorotape_blog_field( 'blog_all_label', __( 'All articles', 'dorotape' ) ),
			'url'     => dorotape_blog_url(),
			'current' => 0 === $current,
		),
	);

	foreach ( $terms as $term ) {
		$links[] = array(
			'label'   => $term->name,
			'url'     => (string) get_term_link( $term ),
			'current' => $current === $term->term_id,
		);
	}
	?>
	<nav class="blog-filters" aria-label="<?php esc_attr_e( 'Blog categories', 'dorotape' ); ?>">
		<ul class="blog-filters__list">
			<?php foreach ( $links as $link ) : ?>
				<li>
					<a class="blog-filters__link<?php echo $link['current'] ? ' is-current' : ''; ?>" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo $link['current'] ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $link['label'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * One article card.
 *
 * The category grid's card, with the article's category and date over the
 * title and its excerpt under it. The picture falls back the way a link
 * card's does (inc/link-card.php).
 *
 * @param int    $post_id     Article.
 * @param string $heading_tag h2 on a listing, where the cards are the page's
 *                            content, h3 under an article's related heading.
 */
function dorotape_post_card( int $post_id, string $heading_tag = 'h2' ): void {
	$image_id = dorotape_card_image_id( (int) get_post_thumbnail_id( $post_id ) );
	$category = dorotape_post_category( $post_id );
	$button   = dorotape_blog_field( 'blog_button', __( 'Read article', 'dorotape' ) );
	$excerpt  = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
	$tag      = 'h3' === $heading_tag ? 'h3' : 'h2';
	?>
	<li class="category-grid-block__item">
		<a class="category-grid-block__card post-card" href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">

			<?php if ( $image_id ) : ?>
				<?php
				echo wp_get_attachment_image(
					$image_id,
					'medium_large',
					false,
					array(
						'class'    => 'category-grid-block__image post-card__image',
						'alt'      => '',
						'loading'  => dorotape_card_loading(),
						'decoding' => 'async',
						'sizes'    => '(min-width: 1024px) 30vw, (min-width: 768px) 45vw, 90vw',
					)
				);
				?>
			<?php else : ?>
				<span class="category-grid-block__image category-grid-block__image--empty post-card__image" aria-hidden="true"></span>
			<?php endif; ?>

			<div class="category-grid-block__body post-card__body">
				<p class="post-card__meta">
					<?php if ( $category ) : ?>
						<span class="post-card__category"><?php echo esc_html( $category->name ); ?></span>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( (string) get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( dorotape_post_date( $post_id ) ); ?></time>
				</p>

				<<?php echo esc_html( $tag ); ?> class="category-grid-block__title post-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></<?php echo esc_html( $tag ); ?>>

				<?php if ( '' !== $excerpt ) : ?>
					<p class="post-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $button ) : ?>
					<span class="btn btn--outline category-grid-block__button post-card__button">
						<?php echo esc_html( $button ); ?>
						<?php echo dorotape_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
					</span>
				<?php endif; ?>
			</div>

		</a>
	</li>
	<?php
}

/**
 * Card excerpts: a sentence or two, not the 55 words WordPress cuts to.
 *
 * Only reached for an article with no excerpt of its own. Every imported
 * article has one, but a new one written without it would otherwise put a
 * paragraph and a half on its card.
 *
 * @param int $length Words.
 */
function dorotape_blog_excerpt_length( int $length ): int {
	return is_admin() ? $length : 28;
}
add_filter( 'excerpt_length', 'dorotape_blog_excerpt_length' );

/**
 * Pagination, in the same shape the search pages use.
 */
function dorotape_blog_pagination(): void {
	global $wp_query;

	$total = $wp_query instanceof WP_Query ? (int) $wp_query->max_num_pages : 0;

	if ( $total < 2 ) {
		return;
	}

	$links = paginate_links(
		array(
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'total'     => $total,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'type'      => 'list',
		)
	);

	if ( ! $links ) {
		return;
	}

	echo '<nav class="woocommerce-pagination" aria-label="' . esc_attr__( 'Blog pages', 'dorotape' ) . '">'
		. wp_kses_post( $links )
		. '</nav>';
}

/**
 * The listing: the blog page and each category page.
 */
function dorotape_blog_listing(): void {
	?>
	<main id="primary" class="site-main site-main--blocks">

		<?php dorotape_blog_banner(); ?>

		<section class="<?php echo esc_attr( dorotape_blog_band_classes() ); ?>">
			<?php dorotape_blog_band_shape(); ?>

			<div class="container">
				<?php dorotape_blog_filters(); ?>

				<?php if ( have_posts() ) : ?>
					<ul class="category-grid-block__grid post-grid">
						<?php
						while ( have_posts() ) :
							the_post();
							dorotape_post_card( get_the_ID() );
						endwhile;
						?>
					</ul>

					<?php dorotape_blog_pagination(); ?>
				<?php else : ?>
					<?php echo dorotape_product_list_message( dorotape_blog_field( 'blog_empty', __( 'There are no articles here yet. Have a look at the rest of the blog.', 'dorotape' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper. ?>
				<?php endif; ?>
			</div>
		</section>

		<?php
		if ( dorotape_has_flexible_content( DOROTAPE_BLOG_SECTIONS_ID ) ) {
			dorotape_render_flexible_content( DOROTAPE_BLOG_SECTIONS_ID, 1 );
		}
		?>

	</main>
	<?php
}

/**
 * The photos at the top of an article.
 *
 * The gallery when it has any, otherwise the featured image on its own, so an
 * article written without photos in the gallery still opens with its picture.
 *
 * @param int $post_id Article.
 * @return array<int, int> Attachment ids.
 */
function dorotape_post_photos( int $post_id ): array {
	$ids = function_exists( 'get_field' ) ? get_field( 'gallery', $post_id ) : array();
	$ids = array_values( array_filter( array_map( 'intval', is_array( $ids ) ? $ids : array() ), 'wp_attachment_is_image' ) );

	if ( ! $ids && has_post_thumbnail( $post_id ) ) {
		$ids = array( (int) get_post_thumbnail_id( $post_id ) );
	}

	return $ids;
}

/**
 * The products featured under an article (its Products featured field), in
 * the order the editor set. Ones since unpublished or hidden from the
 * catalogue drop out rather than leave a gap.
 *
 * @param int $post_id Article being read.
 * @return array<int, WC_Product>
 */
function dorotape_post_products( int $post_id ): array {
	if ( ! function_exists( 'get_field' ) || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$ids      = get_field( 'products', $post_id );
	$products = array();

	foreach ( is_array( $ids ) ? $ids : array() as $id ) {
		$product = wc_get_product( (int) $id );

		if ( $product && 'publish' === $product->get_status() && $product->is_visible() ) {
			$products[] = $product;
		}
	}

	return $products;
}

/**
 * Up to three other articles for under an article, its own category first.
 *
 * @param int $post_id Article being read.
 * @return array<int, int> Post ids.
 */
function dorotape_related_posts( int $post_id ): array {
	$category = dorotape_post_category( $post_id );
	$ids      = array();

	if ( $category ) {
		$ids = get_posts(
			array(
				'cat'            => $category->term_id,
				'post__not_in'   => array( $post_id ),
				'posts_per_page' => 3,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
	}

	if ( count( $ids ) < 3 ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post__not_in'   => array_merge( array( $post_id ), $ids ),
					'posts_per_page' => 3 - count( $ids ),
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);
	}

	return array_map( 'intval', $ids );
}

/**
 * A YouTube or Vimeo link in an article plays in the Video block's frame.
 *
 * WordPress turns a video address on a line of its own into the provider's
 * player; this swaps that player for the theme's still-and-play frame, which
 * loads the player only when it is played, as every other video on the site
 * does.
 *
 * @param string|false $html The provider's markup.
 * @param string       $url  The address in the text.
 */
function dorotape_post_embed( $html, string $url ) {
	if ( ! is_singular( 'post' ) || ! is_string( $html ) || false === strpos( $html, '<iframe' ) ) {
		return $html;
	}

	$frame = dorotape_video_frame( $url );

	return '' !== $frame ? '<div class="post-article__video">' . $frame . '</div>' : $html;
}
add_filter( 'embed_oembed_html', 'dorotape_post_embed', 10, 2 );
