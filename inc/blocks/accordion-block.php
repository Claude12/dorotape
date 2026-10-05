<?php
declare( strict_types=1 );
/**
 * Block: Accordion / FAQ
 *
 * ACF flexible content layout `accordion_block`: an optional heading and
 * intro over a list of items that open and close, each a title over rich
 * text and an optional video. Built for the guide pages that were
 * accordions on the old site (Glossary, Application Advice, Problem
 * Solving), where one long page of headings is a lot to scroll on a phone.
 *
 * Each item is a native <details>, so it opens and closes with no script,
 * stays keyboard operable, and the browser's find-in-page searches the
 * closed panels too. Every item carries an id made from its title, and
 * assets/js/lib/accordion.js opens the item a link points at.
 *
 * All accordions on a page share one details `name`, so the browser keeps a
 * single item open across the page: opening one closes whichever was open,
 * in this block or another. Only the first accordion on the page can start
 * with its first item open, since a second open item would close it.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_rows = get_sub_field( 'items' );

$dt_items = array();

foreach ( is_array( $dt_rows ) ? $dt_rows : array() as $dt_row ) {
	$dt_title = trim( (string) ( $dt_row['title'] ?? '' ) );

	if ( '' === $dt_title ) {
		continue;
	}

	$dt_items[] = array(
		'title'   => $dt_title,
		'content' => (string) ( $dt_row['content'] ?? '' ),
		'video'   => trim( (string) ( $dt_row['video_url'] ?? '' ) ),
	);
}

if ( ! $dt_items ) {
	return;
}

$dt_heading    = trim( (string) get_sub_field( 'heading' ) );
$dt_intro      = (string) get_sub_field( 'intro' );
$dt_open_first = (bool) get_sub_field( 'open_first' ) && empty( $GLOBALS['dorotape_accordion_ids'] );

// The first block carries the page's only <h1>. Item titles sit one level
// under the block heading, or take its place when there is none.
$dt_heading_tag = 0 === (int) get_query_var( 'block_index', 1 ) ? 'h1' : 'h2';
$dt_title_tag   = '' !== $dt_heading ? 'h3' : 'h2';

// Ids are unique across the page, not only this block, because a page can
// stack several accordions and two of them may share a question.
if ( ! isset( $GLOBALS['dorotape_accordion_ids'] ) ) {
	$GLOBALS['dorotape_accordion_ids'] = array();
}

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'accordion-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( get_sub_field( 'divider' ) ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>"<?php dorotape_animate_attr(); ?>>
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="container">
		<div class="accordion-block__inner">
			<?php if ( '' !== $dt_heading ) : ?>
				<<?php echo esc_html( $dt_heading_tag ); ?> class="accordion-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>
			<?php endif; ?>

			<?php if ( '' !== trim( wp_strip_all_tags( $dt_intro ) ) ) : ?>
				<div class="accordion-block__intro rte"><?php echo wp_kses_post( $dt_intro ); ?></div>
			<?php endif; ?>

			<div class="accordion-block__list">
				<?php foreach ( $dt_items as $dt_index => $dt_item ) : ?>
					<?php
					$dt_base = sanitize_title( $dt_item['title'] );
					$dt_base = '' !== $dt_base ? $dt_base : 'item';
					$dt_id   = $dt_base;

					for ( $dt_n = 2; isset( $GLOBALS['dorotape_accordion_ids'][ $dt_id ] ); ++$dt_n ) {
						$dt_id = $dt_base . '-' . $dt_n;
					}
					$GLOBALS['dorotape_accordion_ids'][ $dt_id ] = true;

					$dt_frame = '' !== $dt_item['video'] ? dorotape_video_frame( $dt_item['video'] ) : '';
					?>
					<details class="accordion-block__item" id="<?php echo esc_attr( $dt_id ); ?>" name="dorotape-accordion"<?php echo $dt_open_first && 0 === $dt_index ? ' open' : ''; ?>>
						<summary class="accordion-block__summary">
							<<?php echo esc_html( $dt_title_tag ); ?> class="accordion-block__title"><?php echo esc_html( $dt_item['title'] ); ?></<?php echo esc_html( $dt_title_tag ); ?>>
							<?php echo dorotape_ui_icon( 'chevron-down', 'accordion-block__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						</summary>

						<div class="accordion-block__panel">
							<?php if ( '' !== trim( wp_strip_all_tags( $dt_item['content'], true ) ) || false !== strpos( $dt_item['content'], '<img' ) ) : ?>
								<div class="rte"><?php echo wp_kses_post( $dt_item['content'] ); ?></div>
							<?php endif; ?>

							<?php if ( '' !== $dt_frame ) : ?>
								<div class="accordion-block__video">
									<?php echo $dt_frame; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in dorotape_video_frame(), oEmbed markup inside. ?>
								</div>
							<?php endif; ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
