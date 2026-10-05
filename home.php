<?php
declare( strict_types=1 );
/**
 * The blog: the page set as Posts page in Settings > Reading.
 *
 * Everything it draws is in inc/blog.php, shared with the category pages.
 *
 * @package dorotape
 */

get_header();

dorotape_blog_listing();

get_footer();
