<?php
declare( strict_types=1 );
/**
 * Shared background shapes for flexible-content blocks.
 *
 * The internals-pages design ("Internal Pages Design") moves two line motifs
 * around behind its sections, so they are a shared component here rather than
 * a fixture of one block: every `content_sections` layout carries a
 * `background_shape` select and calls `dorotape_background_shape()` with it.
 *
 * Cubes (src/components/site/CubePattern.tsx) is the isometric lattice. It is
 * an SVG <pattern> in user-space units, so the cube is always 79.66 x 92 and a
 * taller section means more cubes, never bigger ones. This replaced the older
 * viewBox-scaled version the homepage shipped with, where the cube stretched
 * with the section.
 *
 * Triangles (src/components/site/FacetedWireframe.tsx) is the irregular
 * low-poly mesh drawn for "Industries we work with". It is a fixed 720 x 620
 * drawing sliced into a 620px band, so unlike the cubes it does not repeat.
 *
 * Both fade out sideways away from the edge they hug, and top and bottom.
 * Their gradient stops are classed, not coloured here, so the palette stays in
 * base/_colors.scss.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Half a cube's width.
 */
const DOROTAPE_CUBE_R = 39.83;

/**
 * Half a cube's height.
 */
const DOROTAPE_CUBE_V = 46.0;

/**
 * The low-poly mesh, straight from the design's FACET_LINES.
 *
 * Drawn in a 720 x 620 box: the folds run left to right, and the second half
 * of the list is the cross-bracing that closes the facets.
 */
const DOROTAPE_FACET_LINES = 'M-24 42 L82 18 L158 84 L246 26 L344 72 L426 8 L520 58 L622 20 L744 76 ' .
	'M-16 166 L72 112 L158 84 L218 178 L344 72 L388 190 L520 58 L574 154 L744 76 ' .
	'M-24 268 L72 112 L106 238 L218 178 L284 286 L388 190 L476 266 L574 154 L640 252 L744 190 ' .
	'M-18 374 L106 238 L156 354 L284 286 L340 404 L476 266 L522 382 L640 252 L746 348 ' .
	'M-20 500 L156 354 L230 474 L340 404 L414 526 L522 382 L604 486 L746 348 ' .
	'M34 642 L230 474 L300 610 L414 526 L500 632 L604 486 L714 600 ' .
	'M82 18 L72 112 L-16 166 M82 18 L158 84 M158 84 L106 238 M218 178 L156 354 ' .
	'M246 26 L218 178 M344 72 L284 286 M388 190 L340 404 M426 8 L388 190 ' .
	'M520 58 L476 266 M574 154 L522 382 M622 20 L574 154 M640 252 L604 486 ' .
	'M106 238 L-18 374 M284 286 L230 474 M476 266 L414 526 M746 190 L640 252 ' .
	'M156 354 L34 642 M340 404 L300 610 M522 382 L500 632 M746 348 L604 486 ' .
	'M72 112 L218 178 M106 238 L284 286 M156 354 L340 404 M230 474 L414 526 ' .
	'M344 72 L520 58 M388 190 L574 154 M476 266 L640 252 M522 382 L746 348';

/**
 * The shapes an editor can pick, as ACF value => [motif, side].
 *
 * The value doubles as the BEM modifier, so a new shape here needs a matching
 * `.background-shape--{value}` rule in components/_background-shape.scss.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function dorotape_background_shapes(): array {
	return array(
		'cubes-left'      => array( 'cubes', 'left' ),
		'cubes-right'     => array( 'cubes', 'right' ),
		'triangles-left'  => array( 'triangles', 'left' ),
		'triangles-right' => array( 'triangles', 'right' ),
	);
}

/**
 * Normalise a stored `background_shape` value.
 *
 * Product Row carried this field before it was shared, with the values `left`
 * and `right` meaning the cube lattice. Pages saved then still hold those, so
 * they map forward here rather than silently losing their background.
 *
 * @param mixed $value Raw field value.
 * @return string A key of dorotape_background_shapes(), or '' for none.
 */
function dorotape_background_shape_value( $value ): string {
	$value = is_string( $value ) ? $value : '';

	$legacy = array(
		'left'  => 'cubes-left',
		'right' => 'cubes-right',
	);

	$value = $legacy[ $value ] ?? $value;

	return isset( dorotape_background_shapes()[ $value ] ) ? $value : '';
}

/**
 * The class to add to a section that is showing a shape.
 *
 * The shape is absolutely positioned, so the section has to be the containing
 * block. Blocks append this to their own root class.
 *
 * @param string $shape A normalised shape value.
 * @return string ' has-background-shape', or '' when there is no shape.
 */
function dorotape_background_shape_class( string $shape ): string {
	return $shape ? ' has-background-shape' : '';
}

/**
 * One cube, centred on the given point.
 *
 * A hexagon with three edges meeting in its centre, which is what reads as an
 * isometric cube.
 *
 * @param float $cx Centre x.
 * @param float $cy Centre y.
 * @return string Path data.
 */
function dorotape_cube_path( float $cx, float $cy ): string {
	$r = DOROTAPE_CUBE_R;
	$v = DOROTAPE_CUBE_V;

	return sprintf(
		'M%1$.2F,%2$.2F L%3$.2F,%4$.2F L%3$.2F,%5$.2F L%1$.2F,%6$.2F L%7$.2F,%5$.2F L%7$.2F,%4$.2F Z ' .
		'M%1$.2F,%8$.2F L%1$.2F,%2$.2F M%1$.2F,%8$.2F L%7$.2F,%5$.2F M%1$.2F,%8$.2F L%3$.2F,%5$.2F ',
		$cx,
		$cy - $v,
		$cx + $r,
		$cy - $v / 2,
		$cy + $v / 2,
		$cy + $v,
		$cx - $r,
		$cy
	);
}

/**
 * The repeating tile behind the cube lattice.
 *
 * Two cubes make a seamless tile, but each is drawn across the neighbouring
 * repeats as well, so the fragments clipped at the tile edge reassemble into a
 * continuous lattice instead of showing a seam.
 *
 * @return string Path data for one tile.
 */
function dorotape_cube_tile_path(): string {
	$tile_w = DOROTAPE_CUBE_R * 2;
	$row_h  = DOROTAPE_CUBE_V * 1.5;
	$tile_h = $row_h * 2;

	$centres = array(
		array( 0.0, 0.0 ),
		array( DOROTAPE_CUBE_R, $row_h ),
	);

	$d = '';

	foreach ( $centres as [ $bx, $by ] ) {
		for ( $i = -1; $i <= 1; $i++ ) {
			for ( $j = -1; $j <= 1; $j++ ) {
				$d .= dorotape_cube_path( $bx + $i * $tile_w, $by + $j * $tile_h );
			}
		}
	}

	return trim( $d );
}

/**
 * The magenta -> indigo -> cyan ramp, and the two fades that thin it out.
 *
 * Shared by both motifs. `$edge` is where the vertical fade reaches full
 * opacity: the cubes fill their section and fade only at the very top and
 * bottom, the triangles sit in a band and fade over a quarter of it.
 *
 * The horizontal fade always runs from transparent at the outside of the
 * section to solid at the edge the shape hugs, so it is mirrored by side.
 * `$flip_stroke` says whether the colour ramp mirrors with it. The cubes are
 * generated per instance and the design flips them, so the ramp ends on cyan
 * at whichever edge they hug. The facets are one fixed drawing and the design
 * never mirrors them, so their ramp stays put and reads magenta first.
 *
 * @param string $id          Id prefix, unique per instance.
 * @param bool   $right       Whether the shape hugs the right edge.
 * @param float  $edge        Vertical fade offset, 0 to 0.5.
 * @param bool   $flip_stroke Whether the colour ramp mirrors with the side.
 * @return string Gradient markup for <defs>.
 */
function dorotape_background_shape_gradients( string $id, bool $right, float $edge, bool $flip_stroke = true ): string {
	$from = $right ? 0 : 1;
	$to   = $right ? 1 : 0;

	$stroke_from = $flip_stroke ? $from : 0;
	$stroke_to   = $flip_stroke ? $to : 1;

	return sprintf(
		'<linearGradient id="%1$s-stroke" x1="%6$d" y1="0" x2="%7$d" y2="1">
			<stop offset="0" class="background-shape__stop background-shape__stop--magenta"/>
			<stop offset="0.55" class="background-shape__stop background-shape__stop--indigo"/>
			<stop offset="1" class="background-shape__stop background-shape__stop--cyan"/>
		</linearGradient>
		<linearGradient id="%1$s-fade-x" x1="%2$d" y1="0" x2="%3$d" y2="0">
			<stop offset="0" stop-color="white" stop-opacity="0"/>
			<stop offset="0.45" stop-color="white" stop-opacity="0.55"/>
			<stop offset="1" stop-color="white" stop-opacity="1"/>
		</linearGradient>
		<linearGradient id="%1$s-fade-y" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="white" stop-opacity="0"/>
			<stop offset="%4$s" stop-color="white" stop-opacity="1"/>
			<stop offset="%5$s" stop-color="white" stop-opacity="1"/>
			<stop offset="1" stop-color="white" stop-opacity="0"/>
		</linearGradient>
		<mask id="%1$s-mask-x">
			<rect width="100%%" height="100%%" fill="url(#%1$s-fade-x)"/>
		</mask>
		<mask id="%1$s-mask-y">
			<rect width="100%%" height="100%%" fill="url(#%1$s-fade-y)"/>
		</mask>',
		esc_attr( $id ),
		$from,
		$to,
		esc_attr( (string) round( $edge, 2 ) ),
		esc_attr( (string) round( 1 - $edge, 2 ) ),
		$stroke_from,
		$stroke_to
	);
}

/**
 * The cube lattice, as inline SVG.
 *
 * No viewBox: the <pattern> is in user-space units, so the cube keeps its size
 * whatever the section's height and the lattice simply repeats further.
 *
 * @param string $id    Id prefix, unique per instance.
 * @param bool   $right Whether the shape hugs the right edge.
 * @return string SVG markup.
 */
function dorotape_background_shape_cubes( string $id, bool $right ): string {
	return sprintf(
		'<svg class="background-shape__svg" fill="none" focusable="false">
			<defs>
				%1$s
				<pattern id="%2$s-tile" width="%3$s" height="%4$s" patternUnits="userSpaceOnUse">
					<path d="%5$s" stroke="white" stroke-width="1.4"/>
				</pattern>
				<mask id="%2$s-mask-cubes">
					<rect width="100%%" height="100%%" fill="url(#%2$s-tile)"/>
				</mask>
			</defs>
			<g mask="url(#%2$s-mask-y)">
				<g mask="url(#%2$s-mask-x)">
					<rect width="100%%" height="100%%" fill="url(#%2$s-stroke)" mask="url(#%2$s-mask-cubes)" opacity="0.56"/>
				</g>
			</g>
		</svg>',
		dorotape_background_shape_gradients( $id, $right, 0.1 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built above from an escaped id and numbers.
		esc_attr( $id ),
		esc_attr( (string) round( DOROTAPE_CUBE_R * 2, 2 ) ),
		esc_attr( (string) round( DOROTAPE_CUBE_V * 3, 2 ) ),
		esc_attr( dorotape_cube_tile_path() )
	);
}

/**
 * The low-poly mesh, as inline SVG.
 *
 * Unlike the cubes this is one fixed drawing, so it keeps a viewBox and is
 * sliced to the band. It is anchored to whichever edge it hugs rather than
 * flipped, so the stroke gradient is not mirrored with it: the ramp runs
 * magenta at the top left to cyan at the bottom right whichever side it sits
 * on, as the design's one faceted wireframe does.
 *
 * @param string $id    Id prefix, unique per instance.
 * @param bool   $right Whether the shape hugs the right edge.
 * @return string SVG markup.
 */
function dorotape_background_shape_triangles( string $id, bool $right ): string {
	return sprintf(
		'<svg class="background-shape__svg" viewBox="0 0 720 620" preserveAspectRatio="%1$s slice" fill="none" focusable="false">
			<defs>%2$s</defs>
			<g mask="url(#%3$s-mask-y)">
				<path d="%4$s" stroke="url(#%3$s-stroke)" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round" opacity="0.62" mask="url(#%3$s-mask-x)"/>
			</g>
		</svg>',
		$right ? 'xMaxYMid' : 'xMinYMid',
		dorotape_background_shape_gradients( $id, $right, 0.25, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built above from an escaped id and numbers.
		esc_attr( $id ),
		esc_attr( DOROTAPE_FACET_LINES )
	);
}

/**
 * Render the background shape a block's `background_shape` field asked for.
 *
 * Echoes nothing when the field is empty or holds something unrecognised, so a
 * block can call it unconditionally.
 *
 * @param mixed $value Raw `background_shape` field value.
 */
function dorotape_background_shape( $value ): void {
	$shape = dorotape_background_shape_value( $value );

	if ( ! $shape ) {
		return;
	}

	static $instance = 0;
	++$instance;

	[ $motif, $side ] = dorotape_background_shapes()[ $shape ];

	$id    = 'dt-shape-' . $instance;
	$right = 'right' === $side;

	$svg = 'cubes' === $motif
		? dorotape_background_shape_cubes( $id, $right )
		: dorotape_background_shape_triangles( $id, $right );

	printf(
		'<div class="background-shape background-shape--%1$s" aria-hidden="true">%2$s</div>',
		esc_attr( $shape ),
		$svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG built above from an escaped id and numbers.
	);
}
