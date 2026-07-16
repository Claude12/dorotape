<?php
/**
 * Sage SKU alignment audit (read-only).
 *
 * Compares every purchasable website SKU (simple products + variations)
 * against Michael's Sage stock-code export (sage_skus.csv) and reports:
 *   - exact matches (and price agreement with Sage sales price)
 *   - matches against DO-NOT-USE / obsolete Sage codes
 *   - website SKUs with no Sage code, grouped by failure pattern
 *
 * Writes sage_sku_audit.csv next to this script with one row per site SKU.
 *
 * Usage: /Applications/XAMPP/xamppfiles/bin/php sage_sku_audit.php
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../../../../wp-load.php';

// ─── Load the Sage export ─────────────────────────────────────────────────────

$sage = array(); // code => ['desc' =>, 'cat' =>, 'price' =>, 'obsolete' => bool]
$fh   = fopen( __DIR__ . '/sage_skus.csv', 'r' );
$head = fgetcsv( $fh ); // Stock Code, Description, Cat, Sales Price (BOM on first col)
while ( ( $row = fgetcsv( $fh ) ) !== false ) {
	if ( count( $row ) < 4 || '' === trim( $row[0] ) ) {
		continue;
	}
	$code = trim( $row[0] );
	$desc = trim( $row[1] );
	$sage[ $code ] = array(
		'desc'     => $desc,
		'cat'      => (int) $row[2],
		'price'    => (float) $row[3],
		// Cat 100 is the client's obsolete bucket; descriptions confirm.
		'obsolete' => 100 === (int) $row[2]
			|| preg_match( '/DO NOT+M? USE|OBSOLETE|OBSELETE|DISCONTINUED|SEE CODE|SEE \d|SEE [A-Z]{2,}/i', $desc ),
	);
}
fclose( $fh );
echo 'Sage rows: ' . count( $sage ) . ' (' . count( array_filter( $sage, fn( $s ) => $s['obsolete'] ) ) . " obsolete/do-not-use)\n";

// ─── Load every purchasable site SKU ──────────────────────────────────────────

global $wpdb;
$rows = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_parent, p.post_title, sku.meta_value AS sku,
	        price.meta_value AS price
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = p.ID AND price.meta_key = '_price'
	 WHERE p.post_status = 'publish'
	   AND ( ( p.post_type = 'product' ) OR ( p.post_type = 'product_variation' ) )
	 ORDER BY p.ID"
);

// Variable parents are container SKUs — website-only by agreement, skip them.
$variable_parent_ids = $wpdb->get_col(
	"SELECT DISTINCT post_parent FROM {$wpdb->posts}
	 WHERE post_type = 'product_variation' AND post_status = 'publish'"
);
$variable_parent_ids = array_map( 'intval', $variable_parent_ids );

$out = fopen( __DIR__ . '/sage_sku_audit.csv', 'w' );
fputcsv( $out, array( 'product_id', 'type', 'site_sku', 'site_price', 'status', 'sage_price', 'sage_desc', 'note' ) );

$stats = array(
	'match'          => 0,
	'match_obsolete' => 0,
	'missing'        => 0,
	'no_sku'         => 0,
	'price_match'    => 0,
	'price_diff'     => 0,
);
$missing_patterns = array();
$price_diffs      = array();
$obsolete_hits    = array();

foreach ( $rows as $r ) {
	if ( 'product' === $r->post_type && in_array( (int) $r->ID, $variable_parent_ids, true ) ) {
		continue; // container SKU, excluded from marker file by agreement
	}
	$sku = trim( (string) $r->sku );
	if ( '' === $sku ) {
		$stats['no_sku']++;
		fputcsv( $out, array( $r->ID, $r->post_type, '', $r->price, 'NO_SKU', '', '', $r->post_title ) );
		continue;
	}

	if ( isset( $sage[ $sku ] ) ) {
		$s = $sage[ $sku ];
		if ( $s['obsolete'] ) {
			$stats['match_obsolete']++;
			$obsolete_hits[] = array( $r->ID, $sku, $s['desc'] );
			fputcsv( $out, array( $r->ID, $r->post_type, $sku, $r->price, 'MATCH_OBSOLETE', $s['price'], $s['desc'], '' ) );
			continue;
		}
		$stats['match']++;
		$site_price = (float) $r->price;
		$diff       = abs( $site_price - $s['price'] );
		if ( $diff < 0.005 || $s['price'] <= 0 ) {
			$stats['price_match']++;
			fputcsv( $out, array( $r->ID, $r->post_type, $sku, $r->price, 'MATCH', $s['price'], $s['desc'], $s['price'] <= 0 ? 'sage price 0' : '' ) );
		} else {
			$stats['price_diff']++;
			$price_diffs[] = array( $r->ID, $sku, $site_price, $s['price'] );
			fputcsv( $out, array( $r->ID, $r->post_type, $sku, $r->price, 'MATCH_PRICE_DIFF', $s['price'], $s['desc'], sprintf( 'diff %.2f', $site_price - $s['price'] ) ) );
		}
	} else {
		$stats['missing']++;
		// classify the failure pattern
		if ( preg_match( "/^Cover Styl/i", $sku ) || preg_match( "/^COVERSTYL/i", $sku ) ) {
			$pat = 'cover-styl-prefix';
		} elseif ( preg_match( '/-sz\d+/i', $sku ) ) {
			$pat = 'legacy -szNN suffix';
		} elseif ( preg_match( '/[\'\x{2019}]/u', $sku ) ) {
			$pat = 'apostrophe in SKU';
		} elseif ( preg_match( '/\s/', $sku ) ) {
			$pat = 'contains space';
		} else {
			$pat = 'plain code not in Sage';
		}
		$missing_patterns[ $pat ] = ( $missing_patterns[ $pat ] ?? 0 ) + 1;
		fputcsv( $out, array( $r->ID, $r->post_type, $sku, $r->price, 'NOT_IN_SAGE', '', '', $pat ) );
	}
}
fclose( $out );

echo "\n=== Site SKUs vs Sage export ===\n";
foreach ( $stats as $k => $v ) {
	printf( "%-16s %d\n", $k, $v );
}
echo "\nMissing-from-Sage patterns:\n";
arsort( $missing_patterns );
foreach ( $missing_patterns as $pat => $n ) {
	printf( "  %-28s %d\n", $pat, $n );
}
echo "\nObsolete-code matches (site sells a DO-NOT-USE Sage code): " . count( $obsolete_hits ) . "\n";
foreach ( array_slice( $obsolete_hits, 0, 15 ) as $h ) {
	printf( "  #%d %s -> %s\n", $h[0], $h[1], $h[2] );
}
echo "\nPrice differences (first 20 of " . count( $price_diffs ) . "):\n";
foreach ( array_slice( $price_diffs, 0, 20 ) as $d ) {
	printf( "  #%d %-20s site %.2f vs sage %.2f\n", $d[0], $d[1], $d[2], $d[3] );
}
echo "\nFull detail: sage_sku_audit.csv\n";
