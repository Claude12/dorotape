<?php
declare( strict_types=1 );
/**
 * Shared background shapes for flexible-content blocks.
 *
 * The internals-pages design ("Internal Pages Design") puts a faint line motif
 * behind its sections, so it is a shared component here rather than a
 * fixture of one block: every `content_sections` layout carries a
 * `background_shape` select and calls `dorotape_background_shape()` with it.
 *
 * Triangles (src/components/site/FacetedWireframe.tsx) is the irregular
 * low-poly mesh drawn for "Industries we work with". It is a fixed 720 x 620
 * drawing sliced into a 620px band. It fades out sideways away from the edge
 * it hugs, and top and bottom. Its gradient stops are classed, not coloured
 * here, so the palette stays in base/_colors.scss.
 *
 * Filled triangles (SmarterSourcingBackground.tsx) is the solid faceted
 * surface behind "Helping you stay competitive": a full-height low-poly
 * mesh in the same ramp, generated from a fixed seed, faded into the page
 * by CSS. Added 11 Nov 2026 when the client asked for it on more sections.
 *
 * The design also had an isometric cube lattice. The client did not want it
 * (Site Edits, General row 5, 9 Nov 2026), so it was taken out on 11 Nov 2026
 * and every cube value now shows the triangles on the same side; see
 * dorotape_background_shape_value().
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

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
		'triangles-left'  => array( 'triangles', 'left' ),
		'triangles-right' => array( 'triangles', 'right' ),
		'filled-left'     => array( 'filled', 'left' ),
		'filled-right'    => array( 'filled', 'right' ),
	);
}

/**
 * Normalise a stored `background_shape` value.
 *
 * Old values map forward to the triangles on the same side rather than
 * silently losing their background: `cubes-left` and `cubes-right` from
 * before the cubes were removed, and the bare `left` and `right` Product Row
 * stored before the field was shared (which meant cubes then).
 *
 * @param mixed $value Raw field value.
 * @return string A key of dorotape_background_shapes(), or '' for none.
 */
function dorotape_background_shape_value( $value ): string {
	$value = is_string( $value ) ? $value : '';

	$legacy = array(
		'left'        => 'triangles-left',
		'right'       => 'triangles-right',
		'cubes-left'  => 'triangles-left',
		'cubes-right' => 'triangles-right',
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
 * The magenta -> indigo -> cyan ramp, and the two fades that thin it out.
 *
 * `$edge` is where the vertical fade reaches full opacity: the triangles sit
 * in a band and fade over a quarter of it.
 *
 * The horizontal fade always runs from transparent at the outside of the
 * section to solid at the edge the shape hugs, so it is mirrored by side. The
 * colour ramp is not: the facets are one fixed drawing the design never
 * mirrors, so the ramp stays put and reads magenta first.
 *
 * @param string $id    Id prefix, unique per instance.
 * @param bool   $right Whether the shape hugs the right edge.
 * @param float  $edge  Vertical fade offset, 0 to 0.5.
 * @return string Gradient markup for <defs>.
 */
function dorotape_background_shape_gradients( string $id, bool $right, float $edge ): string {
	$from = $right ? 0 : 1;
	$to   = $right ? 1 : 0;

	return sprintf(
		'<linearGradient id="%1$s-stroke" x1="0" y1="0" x2="1" y2="1">
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
		esc_attr( (string) round( 1 - $edge, 2 ) )
	);
}

/**
 * The low-poly mesh, as inline SVG.
 *
 * One fixed drawing, so it keeps a viewBox and is
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
		dorotape_background_shape_gradients( $id, $right, 0.25 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built above from an escaped id and numbers.
		esc_attr( $id ),
		esc_attr( DOROTAPE_FACET_LINES )
	);
}

/**
 * The filled facets as SVG path data, one entry per triangle.
 *
 * A jittered grid, each cell split into two triangles on a diagonal picked at
 * random, drawn in a 1200 x 800 box. The generator is seeded, so the drawing
 * is the same on every page load and every server: it is a fixed picture,
 * just one drawn in code rather than shipped as an image.
 *
 * Each facet also gets a shade, 0.35 to 1, which becomes its fill-opacity.
 * Shading the facets apart is what makes them read as a faceted surface
 * rather than a flat gradient.
 *
 * @return array<int, array{0: string, 1: float}> [path data, shade] pairs.
 */
function dorotape_filled_facets(): array {
	static $facets = null;

	if ( null !== $facets ) {
		return $facets;
	}

	$cols = 16;
	$rows = 11;
	$cw   = 1200 / ( $cols - 1 );
	$ch   = 800 / ( $rows - 1 );
	$seed = 20261111;

	// A small LCG, not mt_rand(), so nothing else's random state is touched.
	$rand = static function () use ( &$seed ): float {
		$seed = ( $seed * 1103515245 + 12345 ) % 2147483648;
		return $seed / 2147483648;
	};

	// The points. Those on the outer edge only slide along it, so the mesh
	// always fills its box to the corners.
	$points = array();
	for ( $r = 0; $r < $rows; $r++ ) {
		for ( $c = 0; $c < $cols; $c++ ) {
			$x = $c * $cw;
			$y = $r * $ch;

			if ( $c > 0 && $c < $cols - 1 ) {
				$x += ( $rand() - 0.5 ) * $cw * 0.7;
			}

			if ( $r > 0 && $r < $rows - 1 ) {
				$y += ( $rand() - 0.5 ) * $ch * 0.7;
			}

			$points[ $r ][ $c ] = array( round( $x, 1 ), round( $y, 1 ) );
		}
	}

	$facets = array();
	$path   = static function ( array ...$p ): string {
		return sprintf( 'M%s %s L%s %s L%s %sZ', $p[0][0], $p[0][1], $p[1][0], $p[1][1], $p[2][0], $p[2][1] );
	};

	for ( $r = 0; $r < $rows - 1; $r++ ) {
		for ( $c = 0; $c < $cols - 1; $c++ ) {
			$a = $points[ $r ][ $c ];
			$b = $points[ $r ][ $c + 1 ];
			$d = $points[ $r + 1 ][ $c ];
			$e = $points[ $r + 1 ][ $c + 1 ];

			if ( $rand() < 0.5 ) {
				$facets[] = array( $path( $a, $b, $e ), round( 0.35 + $rand() * 0.65, 2 ) );
				$facets[] = array( $path( $a, $e, $d ), round( 0.35 + $rand() * 0.65, 2 ) );
			} else {
				$facets[] = array( $path( $a, $b, $d ), round( 0.35 + $rand() * 0.65, 2 ) );
				$facets[] = array( $path( $b, $e, $d ), round( 0.35 + $rand() * 0.65, 2 ) );
			}
		}
	}

	return $facets;
}

/**
 * The filled low-poly surface, as inline SVG.
 *
 * The design's "Helping you stay competitive" background
 * (SmarterSourcingBackground.tsx): solid facets coloured with the divider
 * ramp, magenta at the edge the shape hugs through indigo to cyan, over the
 * full height of the section. The fade into the page and the overall
 * strength are CSS (components/_background-shape.scss), as in the design.
 *
 * The ramp is in user space, so it runs across the whole surface and each
 * facet takes the colour of where it sits rather than the full ramp. The
 * right-hand version is the same drawing flipped, so magenta always sits at
 * the outside edge.
 *
 * @param string $id    Id prefix, unique per instance.
 * @param bool   $right Whether the shape hugs the right edge.
 * @return string SVG markup.
 */
function dorotape_background_shape_filled( string $id, bool $right ): string {
	$facets = '';
	foreach ( dorotape_filled_facets() as [ $d, $shade ] ) {
		$facets .= sprintf( '<path d="%s" fill-opacity="%s"/>', esc_attr( $d ), esc_attr( (string) $shade ) );
	}

	return sprintf(
		'<svg class="background-shape__svg" viewBox="0 0 1200 800" preserveAspectRatio="%4$s slice" focusable="false">
			<defs>
				<linearGradient id="%1$s-fill" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="1200" y2="500">
					<stop offset="0" class="background-shape__stop background-shape__stop--magenta"/>
					<stop offset="0.55" class="background-shape__stop background-shape__stop--indigo"/>
					<stop offset="1" class="background-shape__stop background-shape__stop--cyan"/>
				</linearGradient>
			</defs>
			<g%2$s fill="url(#%1$s-fill)" stroke="white" stroke-opacity="0.06" stroke-width="1" stroke-linejoin="round">%3$s</g>
		</svg>',
		esc_attr( $id ),
		$right ? ' transform="translate(1200 0) scale(-1 1)"' : '',
		$facets, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paths built above from numbers, each escaped.
		$right ? 'xMaxYMid' : 'xMinYMid'
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

	$svg = 'filled' === $motif
		? dorotape_background_shape_filled( $id, $right )
		: dorotape_background_shape_triangles( $id, $right );

	printf(
		'<div class="background-shape background-shape--%1$s" aria-hidden="true">%2$s</div>',
		esc_attr( $shape ),
		$svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG built above from an escaped id and numbers.
	);
}
