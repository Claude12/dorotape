<?php
declare( strict_types=1 );
/**
 * The internal page banner, rendered from one place.
 *
 * Every internal page opens the same way: an eyebrow with a dot, a heading, a
 * standfirst, a breadcrumb, and a background shape behind the lot. The markup
 * comes from inc/blocks/internal-banner-block.php, where an editor fills it in
 * per page; the pages that have no block to fill, because WooCommerce or the
 * template hierarchy owns them, fill it from their own options page instead.
 *
 * That second kind had copied the block's markup once per page: the shop, the
 * search results, the 404, and then the basket, the checkout and the wishlist
 * would have made six. They are identical but for which fields they read and
 * whether a breadcrumb belongs under them, so the markup lives here and each
 * page passes its words in.
 *
 * A category page is not one of these. It puts a photograph beside the text
 * and draws its own grid for it (inc/product-category.php).
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The eyebrow dot's colour, as a value the dot's modifier class can use.
 *
 * The client's rule (Site Edits, October 2026): magenta where the banner
 * leads to products, cyan where it is information. Blocks offer it as the
 * `eyebrow_colour` select; rows saved before that field existed have no value
 * and take the block's own default, which is the colour most of its pages
 * want (the hero sells, the internal banner mostly informs).
 *
 * @param mixed  $value   The select's stored value, or anything else.
 * @param string $default magenta | cyan, used when $value is neither.
 * @return string magenta | cyan.
 */
function dorotape_eyebrow_colour( $value, string $default ): string {
	return in_array( $value, array( 'magenta', 'cyan' ), true ) ? $value : $default;
}

/**
 * Print the banner.
 *
 * @param array $args {
 *     Everything the banner says and how it is dressed.
 *
 *     @type string $eyebrow    Small label above the heading. Empty hides it.
 *     @type string $heading    The h1. Always printed: a page with no title is
 *                              a page with no outline.
 *     @type string $intro      Standfirst under the heading. Empty hides it.
 *     @type string $shape      Background shape name, as the Theme Settings
 *                              select stores it. Normalised here, so a caller
 *                              can hand over a raw field value.
 *     @type bool   $breadcrumb Whether a breadcrumb belongs under the text.
 *                              False on the 404, which is not anywhere.
 *     @type bool   $centred    Centre the text over a centred column below,
 *                              as a blog article has.
 *     @type string $dot        Eyebrow dot colour: magenta for the product
 *                              pages (shop, search), cyan for the rest.
 * }
 */
function dorotape_page_banner( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'    => '',
			'heading'    => '',
			'intro'      => '',
			'shape'      => 'cubes-right',
			'breadcrumb' => true,
			'centred'    => false,
			'dot'        => 'cyan',
		)
	);

	$shape   = dorotape_background_shape_value( (string) $args['shape'] );
	$classes = 'internal-banner-block internal-banner-block--overlap' . dorotape_background_shape_class( $shape );
	if ( $args['centred'] ) {
		$classes .= ' internal-banner-block--centred';
	}
	?>
	<section class="<?php echo esc_attr( $classes ); ?>">
		<?php dorotape_background_shape( $shape ); ?>

		<div class="container">
			<div class="internal-banner-block__inner">

				<?php if ( '' !== $args['eyebrow'] ) : ?>
					<p class="internal-banner-block__eyebrow">
						<span class="internal-banner-block__eyebrow-dot internal-banner-block__eyebrow-dot--<?php echo esc_attr( dorotape_eyebrow_colour( $args['dot'], 'cyan' ) ); ?>" aria-hidden="true"></span>
						<?php echo esc_html( (string) $args['eyebrow'] ); ?>
					</p>
				<?php endif; ?>

				<h1 class="internal-banner-block__heading"><?php echo esc_html( (string) $args['heading'] ); ?></h1>

				<?php if ( '' !== $args['intro'] ) : ?>
					<p class="internal-banner-block__intro"><?php echo esc_html( (string) $args['intro'] ); ?></p>
				<?php endif; ?>

				<?php if ( $args['breadcrumb'] ) : ?>
					<?php dorotape_breadcrumb_nav( 'internal-banner-block__breadcrumb' ); ?>
				<?php endif; ?>

			</div>
		</div>
	</section>
	<?php
}
