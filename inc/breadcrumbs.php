<?php
declare( strict_types=1 );
/**
 * The breadcrumb trail.
 *
 * Rank Math owns the trail whenever it is active. It already prints the
 * BreadcrumbList schema on every page, so anything the theme worked out for
 * itself would eventually disagree with what search engines are being told,
 * and the two would drift apart the first time a product's primary category
 * changed.
 *
 * Only the data comes from Rank Math. The markup stays here because the
 * design draws the trail as a list separated by chevron icons
 * (Internal Pages Design/src/components/site/InternalBanner.tsx), not as
 * Rank Math's own paragraph of links.
 *
 * With Rank Math switched off, or its Breadcrumbs setting disabled, the trail
 * falls back to the page's own ancestors so the theme still works on a site
 * that does not run it.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The trail for the current page, outermost crumb first.
 *
 * The last crumb is the current page and always comes back without a URL, so
 * templates render it as text rather than a link. That matches both the
 * design and Rank Math's own output.
 *
 * @return array<int, array{label: string, url: string}> Crumbs, or empty when there is nothing to show.
 */
function dorotape_breadcrumb_trail(): array {
	$crumbs = dorotape_rank_math_crumbs();

	if ( ! $crumbs ) {
		$crumbs = dorotape_ancestor_crumbs();
	}

	if ( ! $crumbs ) {
		return array();
	}

	$crumbs[ array_key_last( $crumbs ) ]['url'] = '';

	return $crumbs;
}

/**
 * The trail as Rank Math sees it.
 *
 * Rank Math hands back a list of [ label, url, ... ] arrays. Breadcrumbs::get()
 * returns false rather than an object when the setting is switched off, which
 * is the signal to fall back.
 *
 * @return array<int, array{label: string, url: string}> Crumbs, or empty when Rank Math is not supplying them.
 */
function dorotape_rank_math_crumbs(): array {
	if ( ! class_exists( '\RankMath\Frontend\Breadcrumbs' ) ) {
		return array();
	}

	$breadcrumbs = \RankMath\Frontend\Breadcrumbs::get();

	if ( ! $breadcrumbs ) {
		return array();
	}

	$crumbs = array();

	foreach ( (array) $breadcrumbs->get_crumbs() as $crumb ) {
		$label = trim( (string) ( $crumb[0] ?? '' ) );

		if ( '' === $label ) {
			continue;
		}

		$crumbs[] = array(
			'label' => $label,
			'url'   => (string) ( $crumb[1] ?? '' ),
		);
	}

	return $crumbs;
}

/**
 * The trail worked out from the page's own ancestors.
 *
 * The fallback for a site without Rank Math. Pages are the only post type the
 * theme puts a banner on, so ancestors are the whole story.
 *
 * @return array<int, array{label: string, url: string}> Crumbs, or empty outside a singular view.
 */
function dorotape_ancestor_crumbs(): array {
	$post_id = get_the_ID();

	if ( ! $post_id ) {
		return array();
	}

	$crumbs = array(
		array(
			'label' => __( 'Home', 'dorotape' ),
			'url'   => home_url( '/' ),
		),
	);

	foreach ( array_reverse( (array) get_post_ancestors( $post_id ) ) as $ancestor ) {
		$crumbs[] = array(
			'label' => (string) get_the_title( $ancestor ),
			'url'   => (string) get_permalink( $ancestor ),
		);
	}

	$crumbs[] = array(
		'label' => (string) get_the_title( $post_id ),
		'url'   => '',
	);

	return $crumbs;
}
