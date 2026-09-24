<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * Everything on it comes from Theme Settings → 404 Page. See inc/404.php.
 *
 * @package dorotape
 */

get_header();
?>

	<main id="primary" class="site-main site-main--blocks">

		<?php dorotape_error_banner(); ?>

		<?php dorotape_error_destinations(); ?>

		<?php dorotape_render_error_sections(); ?>

	</main><!-- #primary -->

<?php
get_footer();
