<?php
declare( strict_types=1 );
/**
 * Sustainability lozenges: rPET, PVC Free, Recycled and Recyclable.
 *
 * The descriptions mark these with the old site's logo pictures, which the
 * badge row under Add to basket used to show as they were. Paul asked for
 * them as lozenges like the ink compatibility ones, a green icon and the
 * name, so they sit with the inks over the brand logo instead (Paul
 * Houghton, 17 November 2026). The makers' own marks (vegan, Oeko-Tex, FSC
 * and the rest) stay in the badge row as pictures.
 *
 * Which products carry one is still read from the description, so nothing
 * has to be re-entered. The Product Page settings can rename one or give it
 * another icon (product_eco_badges).
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every lozenge, in the order they show.
 *
 * @return array<string, array{label:string, file:string, icon:string, image:int}>
 */
function dorotape_eco_badges(): array {
	static $badges = null;

	if ( null !== $badges ) {
		return $badges;
	}

	// Icons verbatim from Lucide (recycle, leaf, refresh-cw, refresh-ccw-dot),
	// like the rest of the theme's icons.
	$badges = array(
		'rpet'       => array(
			'label' => __( 'rPET', 'dorotape' ),
			'file'  => 'rPET_Logo',
			'icon'  => '<path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/><path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/><path d="m14 16-3 3 3 3"/><path d="M8.293 13.596 7.196 9.5 3.1 10.598"/><path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843"/><path d="m13.378 9.633 4.096 1.098 1.097-4.096"/>',
		),
		'pvc_free'   => array(
			'label' => __( 'PVC Free', 'dorotape' ),
			'file'  => 'PVC_free_logo',
			'icon'  => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
		),
		'recycled'   => array(
			'label' => __( 'Recycled', 'dorotape' ),
			'file'  => 'Recycled-Paper-Green-Logo',
			'icon'  => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
		),
		'recyclable' => array(
			'label' => __( 'Recyclable', 'dorotape' ),
			'file'  => 'Recyclable-Paper-Green-Logo',
			'icon'  => '<path d="M3 2v6h6"/><path d="M21 12A9 9 0 0 0 6 5.3L3 8"/><path d="M21 22v-6h-6"/><path d="M3 12a9 9 0 0 0 15 6.7l3-2.7"/><circle cx="12" cy="12" r="1"/>',
		),
	);

	foreach ( $badges as $key => $badge ) {
		$badges[ $key ]['image'] = 0;
	}

	$rows = function_exists( 'dorotape_product_section_field' ) ? dorotape_product_section_field( 'product_eco_badges' ) : null;

	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$key = (string) ( $row['badge'] ?? '' );

		if ( ! isset( $badges[ $key ] ) ) {
			continue;
		}

		$label = trim( (string) ( $row['label'] ?? '' ) );

		if ( '' !== $label ) {
			$badges[ $key ]['label'] = $label;
		}

		$badges[ $key ]['image'] = (int) ( $row['image'] ?? 0 );
	}

	return $badges;
}

/**
 * The lozenge a badge picture stands for.
 *
 * @param string $src The picture's address, from the description.
 * @return string Key from dorotape_eco_badges(), or empty for a maker's mark.
 */
function dorotape_eco_badge_key( string $src ): string {
	$file = (string) preg_replace( '#\.[a-z]+$#i', '', wp_basename( $src ) );

	foreach ( dorotape_eco_badges() as $key => $badge ) {
		if ( 0 === strcasecmp( $file, $badge['file'] ) ) {
			return $key;
		}
	}

	return '';
}

/**
 * The lozenges a product carries, in the order they show.
 *
 * @param int $product_id Product.
 * @return string[] Keys from dorotape_eco_badges().
 */
function dorotape_product_eco_badges( int $product_id ): array {
	$found = array();

	foreach ( dorotape_product_description_parts( $product_id )['badges'] as $badge ) {
		$key = dorotape_eco_badge_key( (string) $badge['src'] );

		if ( '' !== $key ) {
			$found[ $key ] = true;
		}
	}

	return array_values( array_filter( array_keys( dorotape_eco_badges() ), static fn ( $key ) => isset( $found[ $key ] ) ) );
}

/**
 * The lozenges on the product page, over the brand logo.
 */
function dorotape_product_eco_chips(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$keys = dorotape_product_eco_badges( $product->get_id() );

	if ( ! $keys ) {
		return;
	}

	$badges = dorotape_eco_badges();

	echo '<ul class="ink-list eco-list" aria-label="' . esc_attr( dorotape_product_page_field( 'product_badges_label', __( 'Certifications', 'dorotape' ) ) ) . '">';

	foreach ( $keys as $key ) {
		$badge = $badges[ $key ];
		$url   = $badge['image'] > 0 ? (string) wp_get_attachment_image_url( $badge['image'], 'thumbnail' ) : '';
		$icon  = '' !== $url
			? '<img class="eco-list__icon" src="' . esc_url( $url ) . '" alt="" width="18" height="18" loading="lazy" decoding="async">'
			: '<svg class="eco-list__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $badge['icon'] . '</svg>';

		printf(
			'<li class="ink-list__item eco-list__item">%1$s<span class="ink-list__label">%2$s</span></li>',
			$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped parts and the fixed icon set.
			esc_html( $badge['label'] )
		);
	}

	echo '</ul>';
}
