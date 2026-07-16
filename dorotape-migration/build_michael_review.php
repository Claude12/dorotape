<?php
/**
 * Build the review spreadsheet for Michael: every website item we could not
 * match to a live Sage stock code, plus matched items whose price differs.
 * Output: sage_review_for_michael.csv (columns designed for a non-developer).
 *
 * Usage: /Applications/XAMPP/xamppfiles/bin/php build_michael_review.php
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../../../../wp-load.php';

$sage = array();
$fh   = fopen( __DIR__ . '/sage_skus.csv', 'r' );
fgetcsv( $fh );
while ( ( $r = fgetcsv( $fh ) ) !== false ) {
	if ( count( $r ) >= 4 && '' !== trim( $r[0] ) ) {
		$sage[ trim( $r[0] ) ] = array( 'desc' => trim( $r[1] ), 'cat' => (int) $r[2], 'price' => round( (float) $r[3], 2 ) );
	}
}
fclose( $fh );

global $wpdb;
$variable_parent_ids = array_map( 'intval', $wpdb->get_col(
	"SELECT DISTINCT post_parent FROM {$wpdb->posts}
	 WHERE post_type = 'product_variation' AND post_status = 'publish'"
) );

$rows = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_parent, p.post_title, sku.meta_value AS sku, MIN(price.meta_value + 0) AS price
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = p.ID AND price.meta_key = '_price'
	 WHERE p.post_status = 'publish' AND p.post_type IN ('product','product_variation')
	 GROUP BY p.ID ORDER BY p.post_title"
);

$out = fopen( __DIR__ . '/sage_review_for_michael.csv', 'w' );
fputcsv( $out, array( 'Product', 'Option', 'Website code', 'Website price', 'Issue', 'Closest Sage info' ) );

$counts = array();
foreach ( $rows as $r ) {
	if ( 'product' === $r->post_type && in_array( (int) $r->ID, $variable_parent_ids, true ) ) {
		continue; // grouping-level SKU, never sent to Sage
	}
	$sku = trim( (string) $r->sku );

	$product_name = $r->post_title;
	$option       = '';
	if ( 'product_variation' === $r->post_type ) {
		$product_name = get_the_title( (int) $r->post_parent );
		$option       = trim( str_replace( $product_name, '', $r->post_title ), " -\u{2014}" );
	}

	if ( '' === $sku ) {
		fputcsv( $out, array( $product_name, $option, '(none)', $r->price, 'No code on website', '' ) );
		$counts['no code'] = ( $counts['no code'] ?? 0 ) + 1;
		continue;
	}
	if ( isset( $sage[ $sku ] ) ) {
		$s        = $sage[ $sku ];
		$obsolete = 100 === $s['cat'] || preg_match( '/DO NOT+M? USE|OBSOLETE|OBSELETE|DISCONTINUED|SEE CODE/i', $s['desc'] );
		if ( $obsolete ) {
			fputcsv( $out, array( $product_name, $option, $sku, $r->price, 'Matches an obsolete Sage code', $s['desc'] ) );
			$counts['obsolete'] = ( $counts['obsolete'] ?? 0 ) + 1;
		} elseif ( abs( (float) $r->price - $s['price'] ) >= 0.005 && $s['price'] > 0 ) {
			fputcsv( $out, array( $product_name, $option, $sku, $r->price, sprintf( 'Price differs from Sage (£%.2f)', $s['price'] ), $s['desc'] ) );
			$counts['price differs'] = ( $counts['price differs'] ?? 0 ) + 1;
		}
		continue; // aligned
	}

	// unmatched — try to say something useful about the nearest Sage code
	$hint = '';
	$base = strtoupper( preg_replace( "/^Cover\s*Styl'?\s*/i", '', preg_replace( '/-sz\d+.*$/i', '', $sku ) ) );
	if ( strlen( $base ) >= 3 ) {
		foreach ( $sage as $code => $s ) {
			if ( 0 === strpos( $code, $base ) ) {
				$hint = "$code — {$s['desc']} (£{$s['price']})";
				break;
			}
		}
	}
	$issue = ( 0 === stripos( $sku, 'Cover' ) || preg_match( "/^Cover/i", $sku ) )
		? "Cover Styl' colour not in the Sage export"
		: 'No matching Sage code found';
	fputcsv( $out, array( $product_name, $option, $sku, $r->price, $issue, $hint ) );
	$key            = ( false !== stripos( $issue, 'Cover' ) ) ? 'cover styl missing' : 'unmatched';
	$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
}
fclose( $out );

foreach ( $counts as $k => $v ) {
	printf( "%-20s %d\n", $k, $v );
}
echo "Written: sage_review_for_michael.csv\n";
