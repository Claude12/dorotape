<?php
/**
 * Second pass (read-only): estimate how many misaligned site SKUs can be
 * auto-mapped to a real Sage code.
 *
 * Strategies, in order:
 *  1. -szNN variations: candidate = parent base SKU + size token parsed from
 *     the variation's attributes ("610mm" -> 610, "A4" -> A4, "1220mm x 91.4m"
 *     -> 1220 ...). Checked against the Sage list.
 *  2. Cover Styl' products: extract the short code token (AA14, NE47, B1...)
 *     from the SKU/title and check against Sage.
 *  3. Plain codes: try trivial normalisations (strip spaces/apostrophes).
 *
 * Writes sage_sku_automap.csv with proposed old->new mappings + confidence.
 *
 * Usage: /Applications/XAMPP/xamppfiles/bin/php sage_sku_automap.php
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../../../../wp-load.php';

$sage = array();
$fh   = fopen( __DIR__ . '/sage_skus.csv', 'r' );
fgetcsv( $fh );
while ( ( $row = fgetcsv( $fh ) ) !== false ) {
	if ( count( $row ) < 4 || '' === trim( $row[0] ) ) {
		continue;
	}
	$desc = trim( $row[1] );
	$sage[ trim( $row[0] ) ] = array(
		'desc'     => $desc,
		'price'    => (float) $row[3],
		'obsolete' => 100 === (int) $row[2]
			|| preg_match( '/DO NOT+M? USE|OBSOLETE|OBSELETE|DISCONTINUED|SEE CODE/i', $desc ),
	);
}
fclose( $fh );

/**
 * Size tokens from a variation's attribute values + own title.
 * "610mm" => 610 ; "A4 Sheet" => A4 ; "1220mm x 91.4m" => 1220 ;
 * "305mm x 500mm" => 305 ; "625mm" => 625 ; "A3" => A3.
 */
function dt_size_tokens( array $strings ): array {
	$tokens = array();
	foreach ( $strings as $s ) {
		if ( preg_match( '/\bA[345]\b/i', $s, $m ) ) {
			$tokens[] = strtoupper( $m[0] );
		}
		// first mm number is the width by site convention
		if ( preg_match( '/(\d{2,4})\s*mm/i', $s, $m ) ) {
			$tokens[] = $m[1];
		}
		// bare 3-4 digit number attribute values ("610", "1220")
		if ( preg_match( '/^(\d{3,4})$/', trim( $s ), $m ) ) {
			$tokens[] = $m[1];
		}
	}
	return array_values( array_unique( $tokens ) );
}

global $wpdb;

/**
 * Fallback: when a base code has exactly ONE live Sage code whose remainder
 * looks like a size (digits or A3/A4/A3LIGHT...), that code is the match.
 * Base must be >= 4 chars to avoid absurd prefixes like "100".
 *
 * @return string|null
 */
function dt_unique_prefix_match( string $base, array $sage ): ?string {
	if ( strlen( $base ) < 4 ) {
		return null;
	}
	$hits = array();
	foreach ( $sage as $code => $s ) {
		if ( $s['obsolete'] || 0 !== strpos( $code, $base ) ) {
			continue;
		}
		$rest = substr( $code, strlen( $base ) );
		if ( '' === $rest || preg_match( '/^(\d{2,4}|A[345][A-Z]*|SHEET|PART)$/i', $rest ) ) {
			$hits[] = $code;
		}
		if ( count( $hits ) > 1 ) {
			return null;
		}
	}
	// PART codes are Sage's own part-roll placeholders, not sellable widths
	return ( 1 === count( $hits ) && ! preg_match( '/PART$/i', $hits[0] ) ) ? $hits[0] : null;
}

$out = fopen( __DIR__ . '/sage_sku_automap.csv', 'w' );
fputcsv( $out, array( 'product_id', 'type', 'old_sku', 'proposed_sku', 'sage_desc', 'sage_price', 'site_price', 'strategy', 'confidence' ) );

$counts = array();
$tally  = function ( $strategy, $ok ) use ( &$counts ) {
	$key = $strategy . ( $ok ? ':mapped' : ':unmapped' );
	$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
};

// ─── 1. -szNN variations ─────────────────────────────────────────────────────

$vars = $wpdb->get_results(
	"SELECT p.ID, p.post_parent, p.post_title, sku.meta_value AS sku, price.meta_value AS price
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = p.ID AND price.meta_key = '_price'
	 WHERE p.post_type = 'product_variation' AND p.post_status = 'publish'
	   AND sku.meta_value LIKE '%-sz%'"
);
echo '-szNN variations: ' . count( $vars ) . "\n";

foreach ( $vars as $v ) {
	$parent_sku = get_post_meta( $v->post_parent, '_sku', true );
	// base: strip the -szNN from the variation, or use parent sku when clean
	$base = preg_replace( '/-sz\d+.*$/i', '', $v->sku );
	if ( '' === $base && $parent_sku && false === strpos( $parent_sku, '/' ) ) {
		$base = $parent_sku;
	}

	// attribute values for this variation + own title + PARENT title (the
	// width usually lives in the parent product name, e.g. "... 1370mm x 25m")
	$attrs   = array();
	$product = wc_get_product( $v->ID );
	if ( $product ) {
		foreach ( $product->get_attributes() as $tax => $slug ) {
			$term    = get_term_by( 'slug', $slug, str_replace( 'attribute_', '', $tax ) );
			$attrs[] = $term ? $term->name : (string) $slug;
		}
	}
	$attrs[] = $v->post_title;
	$attrs[] = get_the_title( $v->post_parent );

	$candidates = array();
	foreach ( dt_size_tokens( $attrs ) as $tok ) {
		$candidates[] = $base . $tok;
	}
	$candidates[] = $base; // single-size product whose Sage code is just the base

	$hit      = null;
	$strategy = 'szNN+size-token';
	foreach ( $candidates as $c ) {
		if ( isset( $sage[ $c ] ) && ! $sage[ $c ]['obsolete'] ) {
			$hit = $c;
			break;
		}
	}
	if ( ! $hit && '' !== $base ) {
		$hit = dt_unique_prefix_match( $base, $sage );
		if ( $hit ) {
			$strategy = 'szNN+unique-prefix';
		}
	}
	if ( $hit ) {
		$site_price = (float) $v->price;
		$sage_price = $sage[ $hit ]['price'];
		if ( abs( $site_price - $sage_price ) < 0.005 ) {
			$conf = 'high (price match)';
		} elseif ( $sage_price > 0 && $site_price > $sage_price * 2 ) {
			// site holds a full-roll price against a per-metre Sage price
			$conf = sprintf( 'restructure candidate (~%.0fm roll)', $site_price / $sage_price );
		} else {
			$conf = 'check price';
		}
		fputcsv( $out, array( $v->ID, 'variation', $v->sku, $hit, $sage[ $hit ]['desc'], $sage_price, $v->price, $strategy, $conf ) );
		$tally( 'szNN', true );
	} else {
		fputcsv( $out, array( $v->ID, 'variation', $v->sku, '', '', '', $v->price, 'szNN+size-token', 'NO MATCH' ) );
		$tally( 'szNN', false );
	}
}

// ─── 2. Cover Styl' ──────────────────────────────────────────────────────────

$cs = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_title, sku.meta_value AS sku, MIN(price.meta_value + 0) AS price
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = p.ID AND price.meta_key = '_price'
	 WHERE p.post_status = 'publish' AND p.post_type IN ('product','product_variation')
	   AND (sku.meta_value LIKE 'Cover%' OR sku.meta_value LIKE 'COVERSTYL%')
	 GROUP BY p.ID"
);
echo "Cover Styl' SKUs: " . count( $cs ) . "\n";

foreach ( $cs as $c ) {
	// code token: letters+digits chunk after the brand words, e.g. AA14, NE47, B1, X52
	$hit = null;
	if ( preg_match( "/Cover\s*Styl'?\s*-?\s*([A-Z]{1,2}\d{1,3}[A-Z]?)\b/i", $c->sku . ' ' . $c->post_title, $m ) ) {
		$tok = strtoupper( $m[1] );
		foreach ( array( $tok, $tok . 'H' ) as $cand ) { // B1 -> B1H special case
			if ( isset( $sage[ $cand ] ) && ! $sage[ $cand ]['obsolete']
				&& false !== stripos( $sage[ $cand ]['desc'], 'COVER STYL' ) ) {
				$hit = $cand;
				break;
			}
		}
	}
	if ( $hit ) {
		$conf = ( abs( (float) $c->price - $sage[ $hit ]['price'] ) < 0.005 ) ? 'high (price match)' : 'check price';
		fputcsv( $out, array( $c->ID, $c->post_type, $c->sku, $hit, $sage[ $hit ]['desc'], $sage[ $hit ]['price'], $c->price, 'cover-styl-token', $conf ) );
		$tally( 'coverstyl', true );
	} else {
		fputcsv( $out, array( $c->ID, $c->post_type, $c->sku, '', '', '', $c->price, 'cover-styl-token', 'NO MATCH' ) );
		$tally( 'coverstyl', false );
	}
}

// ─── 3. Plain codes not in Sage: trivial normalisation ───────────────────────

$plain = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_title, sku.meta_value AS sku, price.meta_value AS price
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = p.ID AND price.meta_key = '_price'
	 WHERE p.post_status = 'publish' AND p.post_type IN ('product','product_variation')
	   AND sku.meta_value NOT LIKE '%-sz%'
	   AND sku.meta_value NOT LIKE 'Cover%' AND sku.meta_value NOT LIKE 'COVERSTYL%'
	   AND sku.meta_value <> ''"
);
$variable_parent_ids = array_map( 'intval', $wpdb->get_col(
	"SELECT DISTINCT post_parent FROM {$wpdb->posts}
	 WHERE post_type = 'product_variation' AND post_status = 'publish'"
) );

foreach ( $plain as $p ) {
	if ( 'product' === $p->post_type && in_array( (int) $p->ID, $variable_parent_ids, true ) ) {
		continue; // container SKU
	}
	$sku = trim( $p->sku );
	if ( isset( $sage[ $sku ] ) ) {
		continue; // already fine (counted in first audit)
	}
	// candidates: normalised code, then code + size token from the product
	// title (site simples often drop the width Sage carries: 4781 -> 4781500)
	$candidates = array();
	$norm       = strtoupper( preg_replace( "/[\\s'\x{2019}]+/u", '', $sku ) );
	if ( $norm !== $sku ) {
		$candidates[] = $norm;
	}
	$title_source = $p->post_title;
	if ( 'product_variation' === $p->post_type ) {
		$title_source .= ' ' . get_the_title( (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $p->ID ) ) );
	}
	foreach ( dt_size_tokens( array( $title_source ) ) as $tok ) {
		$candidates[] = $sku . $tok;
		$candidates[] = $norm . $tok;
	}
	$hit      = null;
	$strategy = 'plain+size-token';
	foreach ( array_unique( $candidates ) as $c ) {
		if ( isset( $sage[ $c ] ) && ! $sage[ $c ]['obsolete'] ) {
			$hit = $c;
			break;
		}
	}
	if ( ! $hit ) {
		$hit = dt_unique_prefix_match( $norm, $sage );
		if ( $hit ) {
			$strategy = 'plain+unique-prefix';
		}
	}
	if ( $hit ) {
		$conf = ( abs( (float) $p->price - $sage[ $hit ]['price'] ) < 0.005 ) ? 'high (price match)' : 'check price';
		fputcsv( $out, array( $p->ID, $p->post_type, $sku, $hit, $sage[ $hit ]['desc'], $sage[ $hit ]['price'], $p->price, $strategy, $conf ) );
		$tally( 'plain', true );
	} else {
		fputcsv( $out, array( $p->ID, $p->post_type, $sku, '', '', '', $p->price, 'plain+size-token', 'NO MATCH' ) );
		$tally( 'plain', false );
	}
}

fclose( $out );

echo "\n=== Auto-map coverage ===\n";
ksort( $counts );
foreach ( $counts as $k => $v ) {
	printf( "%-24s %d\n", $k, $v );
}
echo "\nDetail: sage_sku_automap.csv\n";
