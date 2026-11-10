<?php
declare( strict_types=1 );
/**
 * Photo gallery.
 *
 * One photo large with every photo as a thumbnail beside it, as the old
 * site's articles had them (Michael, 28 October 2026). A thumbnail swaps the
 * large one (lib/post-gallery.js); without scripts it opens the full-size
 * file instead.
 *
 * Shared by the blog article (single.php) and the Gallery block
 * (inc/blocks/gallery-block.php), so both look and behave the same.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the gallery.
 *
 * @param int[] $ids  Attachment IDs, in order.
 * @param array $args {
 *     Optional.
 *
 *     @type string $label       Thumbnail label for screen readers, with {number}.
 *     @type string $sizes_main  `sizes` for the large photo when there are thumbnails.
 *     @type string $sizes_solo  `sizes` for a lone photo.
 *     @type string $sizes_thumb `sizes` for the thumbnails.
 *     @type string $class       Extra classes on the wrapper.
 * }
 */
function dorotape_photo_gallery( array $ids, array $args = array() ): void {
	$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

	if ( ! $ids ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'label'       => dorotape_blog_field( 'post_photo_label', __( 'Show photo {number}', 'dorotape' ) ),
			'sizes_main'  => '(min-width: 880px) 620px, (min-width: 576px) 75vw, 92vw',
			'sizes_solo'  => '(min-width: 880px) 800px, 92vw',
			'sizes_thumb' => '(min-width: 880px) 180px, (min-width: 576px) 22vw, 31vw',
			'class'       => '',
		)
	);

	$has_thumbs = count( $ids ) > 1;
	$classes    = trim( 'post-gallery' . ( $has_thumbs ? ' post-gallery--thumbs' : '' ) . ' ' . $args['class'] );
	?>
	<div class="<?php echo esc_attr( $classes ); ?>" data-post-gallery>
		<div class="post-gallery__main">
			<?php
			foreach ( $ids as $index => $id ) {
				$attrs = array(
					'class'    => 'post-gallery__photo',
					'loading'  => 0 === $index ? 'eager' : 'lazy',
					'decoding' => 'async',
					'sizes'    => $has_thumbs ? $args['sizes_main'] : $args['sizes_solo'],
				);
				if ( 0 !== $index ) {
					$attrs['hidden'] = 'hidden';
				}
				echo wp_get_attachment_image( $id, 'large', false, $attrs );
			}
			?>
		</div>

		<?php if ( $has_thumbs ) : ?>
			<ul class="post-gallery__thumbs">
				<?php foreach ( $ids as $index => $id ) : ?>
					<li>
						<a
							class="post-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( (string) wp_get_attachment_image_url( $id, 'full' ) ); ?>"
							aria-label="<?php echo esc_attr( str_replace( '{number}', (string) ( $index + 1 ), (string) $args['label'] ) ); ?>"
							<?php echo 0 === $index ? 'aria-current="true"' : ''; ?>
						>
							<?php
							echo wp_get_attachment_image(
								$id,
								'medium',
								false,
								array(
									'class'    => 'post-gallery__thumb-image',
									'alt'      => '',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'sizes'    => $args['sizes_thumb'],
								)
							);
							?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}
