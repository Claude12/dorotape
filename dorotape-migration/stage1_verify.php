<?php
/**
 * Stage 1 verification sweep (read-only).
 *
 * Checks the catalogue after the Sage SKU renames + restructure:
 *  1. duplicate live SKUs
 *  2. malformed _price_tiers strings
 *  3. tier consistency: first tier price must equal the product's regular price
 *  4. published variable parents with zero published variations (broken)
 *  5. restructure log vs DB: every 'simple' log row is a simple product with
 *     the logged SKU; every 'variation' log row exists, published, right SKU
 *  6. per-metre products: _dt_price_unit=metre implies price > 0
 *  7. aligned items: SKU in Sage implies price equals Sage price (report count)
 *  8. new variations: stock status / purchasability
 *
 * Usage: /Applications/XAMPP/xamppfiles/bin/php stage1_verify.php
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../../../../wp-load.php';

global $wpdb;
$fail = 0;
$warn = 0;

function check( string $label, bool $ok, string $detail = '' ): void {
	global $fail;
	printf( "%s %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail ? " — $detail" : '' );
	if ( ! $ok ) {
		$fail++;
	}
}

// ── 1. duplicate live SKUs ───────────────────────────────────────────────────
$dupes = $wpdb->get_results(
	"SELECT pm.meta_value AS sku, COUNT(*) c FROM {$wpdb->postmeta} pm
	 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
	 WHERE pm.meta_key = '_sku' AND pm.meta_value <> ''
	 GROUP BY pm.meta_value HAVING c > 1"
);
check( 'no duplicate live SKUs', 0 === count( $dupes ), count( $dupes ) . ' dupes' );

// ── 2+3. tier strings ────────────────────────────────────────────────────────
$tier_rows = $wpdb->get_results(
	"SELECT pm.post_id, pm.meta_value AS tiers, price.meta_value AS regular
	 FROM {$wpdb->postmeta} pm
	 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = pm.post_id AND price.meta_key = '_regular_price'
	 WHERE pm.meta_key = '_price_tiers' AND pm.meta_value <> ''"
);
$malformed  = array();
$mismatched = array();
foreach ( $tier_rows as $t ) {
	if ( str_starts_with( $t->tiers, 'field_' ) ) {
		continue; // known legacy ACF-corrupted rows, handled by fallback
	}
	// malformed = the runtime parser drops segments (spaces/commas are fine)
	$parsed = dorotape_parse_legacy_tiers( (int) $t->post_id );
	$segs   = count( array_filter( array_map( 'trim', explode( ';', $t->tiers ) ) ) );
	if ( count( $parsed ) < $segs ) {
		$malformed[] = $t->post_id . ' [' . $t->tiers . '] parsed=' . count( $parsed ) . "/$segs";
		continue;
	}
	// when the FIRST band starts at qty 1 its price must equal regular price
	if ( $parsed && 1 === (int) $parsed[0]['min_qty'] && null !== $t->regular && '' !== $t->regular
		&& abs( (float) $parsed[0]['tier_price'] - (float) $t->regular ) >= 0.005 ) {
		$mismatched[] = $t->post_id . ' tiers=' . $t->tiers . ' regular=' . $t->regular;
	}
}
check( 'tier strings well-formed', 0 === count( $malformed ), count( $malformed ) . ' malformed: ' . implode( ', ', array_slice( $malformed, 0, 5 ) ) );
check( 'first tier equals regular price', 0 === count( $mismatched ), count( $mismatched ) . ' off: ' . implode( ' | ', array_slice( $mismatched, 0, 5 ) ) );

// ── 4. broken variable parents ───────────────────────────────────────────────
$broken = $wpdb->get_col(
	"SELECT p.ID FROM {$wpdb->posts} p
	 JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
	 JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_type'
	 JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug = 'variable'
	 WHERE p.post_type = 'product' AND p.post_status = 'publish'
	   AND NOT EXISTS (
	     SELECT 1 FROM {$wpdb->posts} v
	     WHERE v.post_parent = p.ID AND v.post_type = 'product_variation' AND v.post_status = 'publish'
	   )"
);
check( 'no published variable parents without variations', 0 === count( $broken ), implode( ',', array_slice( $broken, 0, 10 ) ) );

// ── 5. restructure log vs DB ─────────────────────────────────────────────────
$log_simple    = array();
$log_variation = array();
if ( file_exists( __DIR__ . '/sage_restructure_log.csv' ) ) {
	$fh = fopen( __DIR__ . '/sage_restructure_log.csv', 'r' );
	while ( ( $r = fgetcsv( $fh ) ) !== false ) {
		if ( count( $r ) < 4 ) {
			continue;
		}
		if ( 'simple' === $r[2] && preg_match( '/^(\S+) @/', $r[3], $m ) ) {
			$log_simple[ (int) $r[1] ] = $m[1];
		}
		if ( 'variation' === $r[2] && preg_match( '/^#(\d+) (\S+) /', $r[3], $m ) ) {
			$log_variation[ (int) $m[1] ] = $m[2];
		}
	}
	fclose( $fh );
}
$bad_simple = array();
foreach ( $log_simple as $pid => $code ) {
	$p = wc_get_product( $pid );
	if ( ! $p || ! $p->is_type( 'simple' ) || $p->get_sku() !== $code || (float) $p->get_price() <= 0 ) {
		$bad_simple[] = "#$pid ($code): " . ( $p ? $p->get_type() . '/' . $p->get_sku() . '/£' . $p->get_price() : 'missing' );
	}
}
check( 'converted simple products intact (' . count( $log_simple ) . ')', 0 === count( $bad_simple ), implode( ' | ', array_slice( $bad_simple, 0, 5 ) ) );

$bad_var = array();
foreach ( $log_variation as $vid => $code ) {
	$v = wc_get_product( $vid );
	if ( ! $v || ! $v->is_type( 'variation' ) || 'publish' !== get_post_status( $vid )
		|| $v->get_sku() !== $code || (float) $v->get_price() <= 0 || ! $v->is_purchasable() ) {
		$bad_var[] = "#$vid ($code)";
	}
}
check( 'created variations intact (' . count( $log_variation ) . ')', 0 === count( $bad_var ), implode( ', ', array_slice( $bad_var, 0, 10 ) ) );

// ── 6. per-metre products have prices ────────────────────────────────────────
$metre_ids = $wpdb->get_col(
	"SELECT pm.post_id FROM {$wpdb->postmeta} pm
	 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
	 WHERE pm.meta_key = '_dt_price_unit' AND pm.meta_value = 'metre'"
);
$unpriced_metre = array();
foreach ( $metre_ids as $pid ) {
	$p = wc_get_product( (int) $pid );
	if ( ! $p ) {
		continue;
	}
	$price = $p->is_type( 'variable' ) ? $p->get_variation_price( 'min' ) : $p->get_price();
	if ( (float) $price <= 0 ) {
		$unpriced_metre[] = $pid;
	}
}
check( 'per-metre products priced (' . count( $metre_ids ) . ')', 0 === count( $unpriced_metre ), implode( ',', array_slice( $unpriced_metre, 0, 10 ) ) );

// ── 7. Sage price agreement for aligned SKUs ─────────────────────────────────
$sage = array();
$fh   = fopen( __DIR__ . '/sage_skus.csv', 'r' );
fgetcsv( $fh );
while ( ( $r = fgetcsv( $fh ) ) !== false ) {
	if ( count( $r ) >= 4 && '' !== trim( $r[0] ) ) {
		$sage[ trim( $r[0] ) ] = round( (float) $r[3], 2 );
	}
}
fclose( $fh );
$aligned = $wpdb->get_results(
	"SELECT pm.post_id, pm.meta_value AS sku, price.meta_value AS price
	 FROM {$wpdb->postmeta} pm
	 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
	 LEFT JOIN {$wpdb->postmeta} price ON price.post_id = pm.post_id AND price.meta_key = '_price'
	 WHERE pm.meta_key = '_sku' AND pm.meta_value <> ''"
);
$match = 0;
$diff  = array();
foreach ( $aligned as $a ) {
	if ( ! isset( $sage[ $a->sku ] ) ) {
		continue;
	}
	// variable parents: price meta is min of variations, skip
	$p = wc_get_product( (int) $a->post_id );
	if ( ! $p || $p->is_type( 'variable' ) ) {
		continue;
	}
	if ( $sage[ $a->sku ] <= 0 || abs( (float) $a->price - $sage[ $a->sku ] ) < 0.005 ) {
		$match++;
	} else {
		$diff[] = $a->sku . ' site=' . $a->price . ' sage=' . $sage[ $a->sku ];
	}
}
printf( "INFO aligned SKUs with Sage price agreement: %d, differing: %d (%s)\n", $match, count( $diff ), implode( ' | ', array_slice( $diff, 0, 6 ) ) );

printf( "\n%s — %d failure(s)\n", $fail ? 'PROBLEMS FOUND' : 'ALL CHECKS PASSED', $fail );
