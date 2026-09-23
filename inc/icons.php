<?php
declare( strict_types=1 );
/**
 * The shared Lucide icon set.
 *
 * The design draws every inline icon from Lucide at stroke-width 1.5 on a
 * 24x24 box. The paths lived inside inc/blocks/usp-strip-block.php while the
 * USP strip was the only thing that needed them; the product page's assurance
 * list needs the same set, so they moved here rather than being copied.
 *
 * Adding an icon here makes it available to every select that offers the set,
 * because the ACF choices are generated from the same map.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon key => inner SVG markup, verbatim from Lucide.
 *
 * @return array<string, string>
 */
function dorotape_icon_paths(): array {
	return array(
		'truck'     => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
		'lightbulb' => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
		'calendar'  => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>',
		'swatch'    => '<path d="M11 17a4 4 0 0 1-8 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2Z"/><path d="M16.7 13H19a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H7"/><path d="M 7 17h.01"/><path d="m11 8 2.3-2.3a2.4 2.4 0 0 1 3.404.004L18.6 7.6a2.4 2.4 0 0 1 .026 3.434L9.9 19.8"/>',
		'globe'     => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
		'shield'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
		'award'     => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/>',
		'check'     => '<path d="M20 6 9 17l-5-5"/>',
		'clock'     => '<path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/>',
		'scissors'  => '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
	);
}

/**
 * The same set as ACF select choices, key => human label.
 *
 * Field groups list their own choices in JSON, so this is the reference the
 * JSON is written from rather than something ACF reads at runtime.
 *
 * @return array<string, string>
 */
function dorotape_icon_choices(): array {
	$labels = array();

	foreach ( array_keys( dorotape_icon_paths() ) as $key ) {
		$labels[ $key ] = ucfirst( $key );
	}

	return $labels;
}

/**
 * One icon as an <svg>, or an empty string when the key is not in the set.
 *
 * @param string $name   Icon key.
 * @param string $class  Class attribute for the <svg>.
 * @param string $stroke Stroke width. The design uses 1.5 everywhere.
 * @return string Markup, safe to echo.
 */
function dorotape_icon( string $name, string $class = '', string $stroke = '1.5' ): string {
	$paths = dorotape_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg%1$s viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		'' !== $class ? ' class="' . esc_attr( $class ) . '"' : '',
		esc_attr( $stroke ),
		$paths[ $name ]
	);
}
