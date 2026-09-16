<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package dorotape
 */

get_header();
?>

	<?php
	// Block-built pages drop the main column's max-width and padding
	// so each block can paint its own full-bleed background. The blocks bring
	// their own .container for the inner measure.
	$dt_is_blocks   = function_exists( 'dorotape_has_flexible_content' ) && dorotape_has_flexible_content( get_queried_object_id() );
	$dt_main_class  = 'site-main';
	$dt_main_class .= $dt_is_blocks ? ' site-main--blocks' : '';
	?>

	<main id="primary" class="<?php echo esc_attr( $dt_main_class ); ?>">

		<?php
		while ( have_posts() ) :
			the_post();

			/*
			 * Pages are built either way round, so both paths stay live:
			 * a page with ACF flexible-content blocks renders those, and a
			 * page without them falls back to the classic editor content
			 * this site still uses for ordinary pages. Removing the
			 * fallback would blank every existing page that has no blocks.
			 */
			if ( $dt_is_blocks ) {
				dorotape_render_flexible_content();
			} else {
				get_template_part( 'template-parts/content', 'page' );
			}

		endwhile; // End of the loop.
		?>

	</main><!-- #main -->

<?php
get_footer();
