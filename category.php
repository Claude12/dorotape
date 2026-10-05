<?php
declare( strict_types=1 );
/**
 * A blog category: the blog's listing, narrowed to one category.
 *
 * Everything it draws is in inc/blog.php, shared with the blog page.
 *
 * @package dorotape
 */

get_header();

dorotape_blog_listing();

get_footer();
