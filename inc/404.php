<?php
declare( strict_types=1 );
/**
 * The 404 page.
 *
 * There is no 404 design, so nothing here is invented. It is the search page's
 * shape with the results replaced by destinations an editor picks: the
 * internal banner every other internal page opens with, then the ranges grid
 * band, then the shared flexible tail. A customer who has hit a dead link
 * should not also arrive somewhere that looks like a different website.
 *
 * Everything on it is a field. The wording, the two background shapes and the
 * destination cards live on a Theme Settings sub-page, built the same way the
 * product, category and search stores are: registered in PHP so it travels
 * with the theme, and autoloaded because ACF writes a wp_options row per
 * subfield.
 *
 * The one thing it does not carry is a search box. The header's is on the page
 * already, at every width, and a second one forty pixels below it is a choice
 * the customer should not have to make.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The options page slug, which is also what the field group's location rule
 * matches on.
 */
const DOROTAPE_ERROR_SECTIONS_SLUG = 'dorotape-error-page';

/**
 * The ACF post id the 404 page's wording and sections are stored under.
 */
const DOROTAPE_ERROR_SECTIONS_ID = 'dorotape_error_page';

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
				'page_title'      => __( '404 Page', 'dorotape' ),
				'menu_title'      => __( '404 Page', 'dorotape' ),
				'menu_slug'       => DOROTAPE_ERROR_SECTIONS_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_ERROR_SECTIONS_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save 404 page', 'dorotape' ),
				'updated_message' => __( '404 page saved.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a text field from the 404 Page options page.
 *
 * @param string $name    Field name.
 * @param string $default Value to use before an editor has saved the page.
 */
function dorotape_error_field( string $name, string $default = '' ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_ERROR_SECTIONS_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * The banner.
 *
 * inc/blocks/internal-banner-block.php's markup with the fields filled from
 * the options page, and no backdrop image: a picture behind an apology is the
 * wrong kind of confident.
 */
function dorotape_error_banner(): void {
	$eyebrow = dorotape_error_field( 'error_eyebrow', __( 'Error 404', 'dorotape' ) );
	$heading = dorotape_error_field( 'error_heading', __( 'We cannot find that page', 'dorotape' ) );
	$intro   = dorotape_error_field(
		'error_intro',
		__( 'The link may be out of date, or the page may have moved. Search at the top of this page, or carry on from one of the places below.', 'dorotape' )
	);

	$shape   = dorotape_background_shape_value( dorotape_error_field( 'error_banner_shape', 'cubes-right' ) );
	$classes = 'internal-banner-block internal-banner-block--overlap' . dorotape_background_shape_class( $shape );
	?>
	<section class="<?php echo esc_attr( $classes ); ?>">
		<?php dorotape_background_shape( $shape ); ?>

		<div class="container">
			<div class="internal-banner-block__inner">

				<?php if ( '' !== $eyebrow ) : ?>
					<p class="internal-banner-block__eyebrow">
						<span class="internal-banner-block__eyebrow-dot" aria-hidden="true"></span>
						<?php echo esc_html( $eyebrow ); ?>
					</p>
				<?php endif; ?>

				<h1 class="internal-banner-block__heading"><?php echo esc_html( $heading ); ?></h1>

				<?php if ( '' !== $intro ) : ?>
					<p class="internal-banner-block__intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>

			</div>
		</div>
	</section>
	<?php
}

/**
 * The destinations an editor has chosen, as card arguments.
 *
 * Falls back to the two links that exist on every WooCommerce site, so a 404
 * is never a dead end on a site where nobody has filled the field in yet.
 *
 * @return array<int, array{url: string, title: string, image_id: int}>
 */
function dorotape_error_links(): array {
	$rows  = function_exists( 'get_field' ) ? get_field( 'error_links', DOROTAPE_ERROR_SECTIONS_ID ) : null;
	$links = array();

	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$link = is_array( $row ) ? ( $row['error_link'] ?? null ) : null;

			if ( ! is_array( $link ) ) {
				continue;
			}

			$url   = trim( (string) ( $link['url'] ?? '' ) );
			$title = trim( (string) ( $link['title'] ?? '' ) );

			if ( '' === $url || '' === $title ) {
				continue;
			}

			$links[] = array(
				'url'      => $url,
				'title'    => $title,
				'image_id' => (int) ( $row['error_image'] ?? 0 ),
			);
		}
	}

	if ( $links ) {
		return $links;
	}

	$links[] = array(
		'url'      => home_url( '/' ),
		'title'    => __( 'Home', 'dorotape' ),
		'image_id' => 0,
	);

	$shop = function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : '';

	if ( '' !== $shop ) {
		$links[] = array(
			'url'      => $shop,
			'title'    => __( 'All products', 'dorotape' ),
			'image_id' => 0,
		);
	}

	return $links;
}

/**
 * The band of destinations.
 *
 * The ranges grid, with the same soft glow the search results band uses and
 * the same reduced top padding: like search, nothing sits between it and the
 * banner, so the full step would leave the heading floating.
 */
function dorotape_error_destinations(): void {
	$links = dorotape_error_links();

	if ( ! $links ) {
		return;
	}

	$shape   = dorotape_background_shape_value( dorotape_error_field( 'error_links_shape', 'cubes-right' ) );
	$classes = 'category-grid-block category-grid-block--glow-soft category-grid-block--tight' . dorotape_background_shape_class( $shape );
	$heading = dorotape_error_field( 'error_links_heading', __( 'Where to go next', 'dorotape' ) );
	$button  = dorotape_error_field( 'error_button', __( 'Go there', 'dorotape' ) );
	?>
	<section class="<?php echo esc_attr( $classes ); ?>" animate="fade-in-up" animate-offset="0">
		<?php dorotape_background_shape( $shape ); ?>

		<div class="container">

			<?php if ( '' !== $heading ) : ?>
				<div class="category-grid-block__header">
					<h2 class="category-grid-block__heading"><?php echo esc_html( $heading ); ?></h2>
				</div>
			<?php endif; ?>

			<ul class="category-grid-block__grid">
				<?php foreach ( $links as $dt_link ) : ?>
					<?php
					dorotape_link_card(
						array(
							'url'      => $dt_link['url'],
							'title'    => $dt_link['title'],
							'button'   => $button,
							'image_id' => $dt_link['image_id'],
						)
					);
					?>
				<?php endforeach; ?>
			</ul>

		</div>
	</section>
	<?php
}

/**
 * True when an editor has put sections on the 404 page's options page.
 */
function dorotape_has_error_sections(): bool {
	return dorotape_has_flexible_content( DOROTAPE_ERROR_SECTIONS_ID );
}

/**
 * The shared tail, after the destinations.
 *
 * Starts the block counter at 1 for the same reason the search tail does: the
 * banner above is the block above the fold.
 */
function dorotape_render_error_sections(): void {
	if ( ! dorotape_has_error_sections() ) {
		return;
	}

	dorotape_render_flexible_content( DOROTAPE_ERROR_SECTIONS_ID, 1 );
}
