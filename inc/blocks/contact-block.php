<?php
declare( strict_types=1 );
/**
 * Block: Contact
 *
 * ACF flexible content layout `contact_block`. The working half of a contact
 * page: the ways to reach the company on the left, the form on the right.
 *
 * There is no design for this page, so it is assembled from decisions the
 * signed-off pages already made. The two-column split, the eyebrow chip and
 * the heading sizes are intro_columns_block's; the method cards are the
 * category grid's card surface at list size; the panel is the surface the CTA
 * cards use. Nothing here invents a new shape, which is the point: a page the
 * designs never covered should still look like it came from the same set.
 *
 * The form arrives as a shortcode rather than a form picker. WPForms is what
 * the site runs today, but a shortcode field costs nothing, works with
 * whatever form plugin a site has, and is the same field the map next door
 * uses. See inc/blocks/embed-block.php.
 *
 * Rendered inside the have_rows()/the_row() loop in
 * dorotape_render_flexible_content() (inc/acf.php).
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_eyebrow      = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading      = trim( (string) get_sub_field( 'heading' ) );
$dt_intro        = trim( (string) get_sub_field( 'intro' ) );
$dt_methods      = get_sub_field( 'methods' );
$dt_note         = trim( (string) get_sub_field( 'note' ) );
$dt_form_heading = trim( (string) get_sub_field( 'form_heading' ) );
$dt_form_intro   = trim( (string) get_sub_field( 'form_intro' ) );
$dt_form_code    = trim( (string) get_sub_field( 'form_shortcode' ) );

$dt_methods = is_array( $dt_methods ) ? $dt_methods : array();

// Nothing to show is not the same as an empty section with 112px of padding
// top and bottom, which is what this would otherwise draw.
if ( '' === $dt_heading && ! $dt_methods && '' === $dt_form_code ) {
	return;
}

$dt_divider  = (bool) get_sub_field( 'divider' );
$dt_is_first = 0 === (int) get_query_var( 'block_index', 1 );

// The first block carries the page's only <h1>. The panel heading is always
// one step below whatever the column heading turned out to be, so the outline
// reads in order either way.
$dt_heading_tag = $dt_is_first ? 'h1' : 'h2';
$dt_panel_tag   = $dt_is_first ? 'h2' : 'h3';

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'contact-block' . dorotape_background_shape_class( $dt_shape );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<div class="contact-block__grid">

			<div class="contact-block__details">

				<?php if ( '' !== $dt_eyebrow ) : ?>
					<p class="contact-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $dt_heading ) : ?>
					<<?php echo esc_html( $dt_heading_tag ); ?> class="contact-block__heading"><?php echo esc_html( $dt_heading ); ?></<?php echo esc_html( $dt_heading_tag ); ?>>
				<?php endif; ?>

				<?php if ( '' !== $dt_intro ) : ?>
					<p class="contact-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>

				<?php if ( $dt_methods ) : ?>
					<ul class="contact-block__methods">
						<?php foreach ( $dt_methods as $dt_method ) : ?>
							<?php
							$dt_label = trim( (string) ( $dt_method['label'] ?? '' ) );
							$dt_value = trim( (string) ( $dt_method['value'] ?? '' ) );
							$dt_link  = $dt_method['link'] ?? null;
							$dt_url   = is_array( $dt_link ) ? trim( (string) ( $dt_link['url'] ?? '' ) ) : '';
							$dt_icon  = (string) ( $dt_method['icon'] ?? '' );

							// A row with nothing to say is a row an editor
							// started and left, not a blank card to print.
							if ( '' === $dt_label && '' === $dt_value ) {
								continue;
							}
							?>
							<li class="contact-block__method">

								<?php if ( '' !== dorotape_icon( $dt_icon ) ) : ?>
									<span class="contact-block__icon">
										<?php echo dorotape_icon( $dt_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup from the shared set. ?>
									</span>
								<?php endif; ?>

								<div class="contact-block__method-body">

									<?php if ( '' !== $dt_label ) : ?>
										<p class="contact-block__label"><?php echo esc_html( $dt_label ); ?></p>
									<?php endif; ?>

									<?php
									// An address is the one value written over
									// several lines, and it is also the one most
									// likely to carry a map link, so both branches
									// keep its line breaks rather than running
									// them together into a single line.
									?>
									<?php if ( '' !== $dt_value && '' !== $dt_url ) : ?>
										<a class="contact-block__value" href="<?php echo esc_url( $dt_url ); ?>"<?php echo ! empty( $dt_link['target'] ) ? ' target="' . esc_attr( (string) $dt_link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo nl2br( esc_html( $dt_value ) ); ?></a>
									<?php elseif ( '' !== $dt_value ) : ?>
										<p class="contact-block__value"><?php echo nl2br( esc_html( $dt_value ) ); ?></p>
									<?php endif; ?>

								</div>

							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( '' !== $dt_note ) : ?>
					<p class="contact-block__note"><?php echo esc_html( $dt_note ); ?></p>
				<?php endif; ?>

			</div>

			<?php if ( '' !== $dt_form_code || '' !== $dt_form_heading ) : ?>
				<div class="contact-block__panel">

					<?php if ( '' !== $dt_form_heading ) : ?>
						<<?php echo esc_html( $dt_panel_tag ); ?> class="contact-block__panel-heading"><?php echo esc_html( $dt_form_heading ); ?></<?php echo esc_html( $dt_panel_tag ); ?>>
					<?php endif; ?>

					<?php if ( '' !== $dt_form_intro ) : ?>
						<p class="contact-block__panel-intro"><?php echo esc_html( $dt_form_intro ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $dt_form_code ) : ?>
						<div class="contact-block__form">
							<?php echo do_shortcode( $dt_form_code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output, escaped by the plugin that renders it. ?>
						</div>
					<?php endif; ?>

				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
