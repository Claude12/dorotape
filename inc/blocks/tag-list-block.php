<?php
declare( strict_types=1 );
/**
 * Block: Tag List
 *
 * ACF flexible content layout `tag_list_block`. The "Industries we work
 * with" band from the About page in the signed-off internal pages design: a
 * heading over a wrapping field of pill links.
 *
 * A tag without a link still renders, as a pill that simply is not
 * clickable. The list reads as a statement of coverage first and a set of
 * routes second, so a missing destination should not drop the industry.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_tags = (array) get_sub_field( 'tags' );

if ( ! $dt_tags ) {
	return;
}

$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_intro   = trim( (string) get_sub_field( 'intro' ) );
$dt_divider = (bool) get_sub_field( 'divider' );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'tag-list-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<?php if ( '' !== $dt_heading ) : ?>
			<h2 class="tag-list-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
		<?php endif; ?>

		<?php if ( '' !== $dt_intro ) : ?>
			<p class="tag-list-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
		<?php endif; ?>

		<ul class="tag-list-block__list">
			<?php foreach ( $dt_tags as $dt_tag ) : ?>
				<?php
				$dt_label = trim( (string) ( $dt_tag['label'] ?? '' ) );
				$dt_link  = $dt_tag['link'] ?? array();

				if ( '' === $dt_label ) {
					continue;
				}
				?>
				<li class="tag-list-block__item">
					<?php if ( ! empty( $dt_link['url'] ) ) : ?>
						<a class="tag-list-block__tag tag-list-block__tag--link" href="<?php echo esc_url( $dt_link['url'] ); ?>"<?php echo ! empty( $dt_link['target'] ) ? ' target="' . esc_attr( $dt_link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $dt_label ); ?></a>
					<?php else : ?>
						<span class="tag-list-block__tag"><?php echo esc_html( $dt_label ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
