<?php
/**
 * Apply the price breaks from Michael's PRODUCTS SOLD IN INCREMENTS sheet.
 *
 * Step 4 of the "Complex Products" email (28 Jul 2026). The sheet gives, per
 * Sage code, an increment and a "price break at" quantity — but NOT the price
 * at that break.
 *
 * Most of those prices turn out to already exist, and to already be charged.
 * They live in the _price_tiers post meta on each variation (or on the product
 * itself where it is simple), as "1-49:8.38;50:7.63" — base rate up to 49,
 * break rate from 50. scratchpad/breaks_live.py adds one increment below each
 * break and exactly at it and asserts the resulting basket total: 50 of 50
 * assertions across 25 products came out exact, so these are live, not dormant.
 *
 * Measured against the sheet, the 103 parents it covers break down as:
 *   69  break already at the sheet's quantity, with a price — nothing to do.
 *    4  break exists at a DIFFERENT quantity (MOVE) — needs a price at the new one.
 *   27  no break at all (MISSING) — needs a price outright.
 *    3  variations of one product disagree with each other (SPLIT).
 * So roughly 31 prices are outstanding, not 103.
 *
 * WHERE THIS WRITES, AND WHY IT MATTERS
 *
 * dorotape_parse_legacy_tiers_own() (inc/pricing.php) reads the clean
 * _price_tiers string first, and only falls back to ACF's flat repeater meta
 * (price_tiers / price_tiers_N_min_qty / ...) when that string is absent or has
 * been overwritten with an ACF "field_..." reference. ASLAN TF200 (#737) proves
 * which one wins: it carries BOTH a legacy string (5+ @ £35.46) and an ACF
 * repeater row (5+ @ £34.02), and the basket charges £35.46. The ACF row is
 * dead data.
 *
 * So this script writes the clean _price_tiers string — the same thing the
 * admin metabox writes — on the exact post the Sage code identifies. It does
 * NOT write the ACF repeater: that is the corrupted-migration compatibility
 * path pricing.php is explicitly waiting to delete.
 *
 * Working per Sage code rather than per parent is deliberate: the sheet is
 * per-code, prices are per-SKU (a 760mm roll and a 1600mm roll of the same
 * product do not share a rate), and it sidesteps the SPLIT cases entirely.
 *
 * Usage:
 *   php price_breaks.php                        (dry run, default)
 *   php price_breaks.php --apply
 *   php price_breaks.php --apply --allow-unreachable
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply       = in_array( '--apply', $argv, true );
$allow_unreach = in_array( '--allow-unreachable', $argv, true );
echo $apply ? "MODE: APPLY\n" : "MODE: DRY RUN (pass --apply to write)\n";
echo $allow_unreach ? "       unreachable breaks ALLOWED by flag\n\n" : "\n";

/**
 * Map every SKU to its post ID in one query.
 *
 * @return array<string,int> Uppercased SKU => post ID.
 */
function dorotape_sku_index(): array {
	global $wpdb;
	$out = array();
	foreach ( $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value <> ''" ) as $r ) {
		$out[ strtoupper( $r->meta_value ) ] = (int) $r->post_id;
	}
	return $out;
}

$csv = __DIR__ . '/price_breaks.csv';
if ( ! is_readable( $csv ) ) {
	exit( "  ! cannot read $csv\n" );
}

$fh = fopen( $csv, 'r' );
fgetcsv( $fh ); // header
$skus = dorotape_sku_index();

$written = 0;
$ok      = 0;
$pending = array();
$refused = array();
$unknown = array();
$line_no = 1;

while ( $line = fgetcsv( $fh ) ) {
	++$line_no;
	if ( ! array_filter( $line ) ) {
		continue;
	}

	$code  = isset( $line[0] ) ? trim( $line[0] ) : '';
	$qty   = isset( $line[1] ) ? trim( $line[1] ) : '';
	$price = isset( $line[2] ) ? trim( $line[2] ) : '';
	if ( '' === $code ) {
		continue;
	}

	$key = strtoupper( $code );
	if ( ! isset( $skus[ $key ] ) ) {
		$unknown[] = "$code (line $line_no)";
		continue;
	}

	$id     = $skus[ $key ];
	$parent = wp_get_post_parent_id( $id );
	$owner  = $parent ?: $id;
	$label  = sprintf( '%-16s (#%d)', $code, $id );

	if ( '' === $price ) {
		$pending[] = sprintf( '%s — break at %s, price not supplied', $label, $qty );
		continue;
	}
	if ( ! is_numeric( $qty ) || ! is_numeric( $price ) ) {
		$refused[] = "$label — non-numeric break ($qty / $price)";
		continue;
	}
	if ( (float) $qty != (int) $qty ) {
		$refused[] = sprintf( '%s — break quantity %s is fractional; the tier format takes whole units', $label, $qty );
		continue;
	}
	$qty = (int) $qty;
	if ( $qty < 2 ) {
		$refused[] = sprintf( '%s — break quantity %d leaves no base band', $label, $qty );
		continue;
	}

	// The base rate this SKU charges below the break.
	$base = get_post_meta( $id, '_regular_price', true );
	if ( '' === $base || (float) $base <= 0 ) {
		$refused[] = sprintf( '%s — no regular price to build the base band from', $label );
		continue;
	}
	$base = (float) $base;

	if ( (float) $price >= $base ) {
		$refused[] = sprintf( '%s — break price %s is not below the base rate %.2f', $label, $price, $base );
		continue;
	}

	// A break the customer can never land on exactly. Ri-Lam C30 is the case in
	// point: the sheet puts VEH* on a 5m increment AND the break at 46m, and 46
	// is not a multiple of 5 — verified in the basket, 45m and 50m add, 46m is
	// rejected. The discount is still obtainable, but from 50m, not 46m. That
	// is a pricing decision, so it needs the explicit flag.
	$step = get_post_meta( $owner, '_dt_qty_step', true );
	if ( is_numeric( $step ) && (int) $step > 1 && 0 !== $qty % (int) $step ) {
		$first = (int) ( ceil( $qty / (int) $step ) * (int) $step );
		$msg   = sprintf(
			'%s — break %d is not a multiple of the %sm increment, so the discount actually starts at %d',
			$label,
			$qty,
			$step,
			$first
		);
		if ( ! $allow_unreach ) {
			$refused[] = $msg . ' (pass --allow-unreachable to write it anyway)';
			continue;
		}
		printf( "  [warn]  %s\n", $msg );
	}

	// The clean format pricing.php parses, and the admin metabox writes.
	// Both rates are normalised to 2dp so a re-run compares equal instead of
	// rewriting "8.4" as "8.40" forever.
	$tier_string = sprintf(
		'1-%d:%s;%d:%s',
		$qty - 1,
		number_format( $base, 2, '.', '' ),
		$qty,
		number_format( (float) $price, 2, '.', '' )
	);

	$current = get_post_meta( $id, '_price_tiers', true );
	if ( $current === $tier_string ) {
		++$ok;
		printf( "  [ok]    %s — already %s\n", $label, $tier_string );
		continue;
	}

	if ( $current && ! str_starts_with( (string) $current, 'field_' ) ) {
		printf( "  [note]  %s — replacing %s\n", $label, $current );
	}

	if ( $apply ) {
		update_post_meta( $id, '_price_tiers', $tier_string );
		++$written;
		printf( "  [write] %s — %s\n", $label, $tier_string );
	} else {
		++$written;
		printf( "  [dry]   %s — %s\n", $label, $tier_string );
	}
}
fclose( $fh );

echo "\n", str_repeat( '─', 78 ), "\n";
printf( "already correct:                 %d\n", $ok );
printf( $apply ? "tiers written:                   %d\n" : "tiers that would be written:     %d\n", $written );
printf( "waiting on a break price:        %d\n", count( $pending ) );
printf( "refused (need a decision):       %d\n", count( $refused ) );

if ( $refused ) {
	echo "\nREFUSED\n";
	foreach ( $refused as $r ) {
		echo "  - $r\n";
	}
}

if ( $unknown ) {
	printf( "\nSage codes in the CSV with no matching product (%d)\n", count( $unknown ) );
	foreach ( $unknown as $u ) {
		echo "  - $u\n";
	}
}

if ( $pending ) {
	printf( "\nWAITING ON MICHAEL — %d code(s) have a break quantity but no price\n", count( $pending ) );
	foreach ( array_slice( $pending, 0, 8 ) as $p ) {
		echo "  - $p\n";
	}
	if ( count( $pending ) > 8 ) {
		printf( "  ... and %d more (see price_breaks.csv)\n", count( $pending ) - 8 );
	}
}

if ( $apply && $written ) {
	echo "\n  Tier strings changed — clear the WooCommerce product cache if prices look stale.\n";
}
