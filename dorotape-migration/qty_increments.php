<?php
/**
 * Apply per-product quantity increments from Michael's "PRODUCTS SOLD IN
 * INCREMENTS" list (email 2026-07-28, "Complex Products").
 *
 * The sheet gives, per Sage code, the increment the product is sold in and the
 * quantity at which a price break applies. This script only writes the
 * increment (_dt_qty_step). The break needs a *price* to be worth anything and
 * the sheet does not carry one, so breaks are handled separately by
 * price_breaks.php once Michael supplies them.
 *
 * Data lives in qty_increments.csv next to this file rather than inline, so the
 * same rows can be re-read on dev without the spreadsheet being present.
 *
 * Codes are matched against _sku across BOTH products and variations, because
 * roughly half the sheet identifies a product by one of its variation SKUs. The
 * step is always stored on the PARENT: dorotape_qty_step() (inc/pricing.php)
 * resolves a variation to its parent before reading the meta, so writing it on
 * a variation would be silently ignored. This is safe here — every parent's
 * listed SKUs agree on a single increment, so there is no case where two
 * variations of one product would need different steps.
 *
 * Integer steps only. dorotape_qty_step() casts to int and returns int, and the
 * value flows into WooCommerce's quantity_input step plus the Store API's
 * QuantityLimits, so a fractional step cannot be represented anywhere in the
 * current stack. One row (S62HT, 12.5m) is therefore skipped rather than
 * written as 12 — 12 would be a wrong increment AND would make its own 25m
 * break unreachable (12, 24, 36...). It is reported as SKIPPED for a decision.
 *
 * Idempotent — a second run reports [ok] and writes nothing.
 *
 * Usage:
 *   php qty_increments.php              (dry run, default)
 *   php qty_increments.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

$csv = __DIR__ . '/qty_increments.csv';
if ( ! is_readable( $csv ) ) {
	exit( "Cannot read $csv\n" );
}

/**
 * Map every _sku in the database to its post ID, in one query.
 *
 * Doing this per-code would be 134 queries against an unindexed meta_value;
 * one pass and an in-memory lookup keeps the whole run under a second.
 *
 * @return array<string,int> Upper-cased SKU => post ID.
 */
function dorotape_sku_index(): array {
	global $wpdb;
	$out  = array();
	$rows = $wpdb->get_results(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta}
		  WHERE meta_key = '_sku' AND meta_value <> ''"
	);
	foreach ( $rows as $row ) {
		$out[ strtoupper( trim( $row->meta_value ) ) ] = (int) $row->post_id;
	}
	return $out;
}

/**
 * Does one unit of this product mean a ROLL rather than a metre?
 *
 * The sheet's figures are metres throughout. A step is a quantity multiplier,
 * so writing "5" is only correct where one unit is one metre. Where the product
 * is sold as fixed-length rolls, one unit is a whole roll and a step of 5 means
 * five rolls — e.g. ASLAN CT11355 is a 1250mm x 5m roll at £38.10, so a step of
 * 5 forces a £190.50 minimum and makes a single roll unbuyable.
 *
 * Two independent signals, either one is disqualifying:
 *
 *  (a) _dt_price_unit = 'roll' — set on the 13 whole-roll products.
 *  (b) A variation axis that encodes a LENGTH as well as a width
 *      ("1250mm x 5m roll", "transparent-1370mm-x-30m"). A per-metre product
 *      varies by width alone ("610mm", "1220mm"), so a length in the option
 *      label means the customer is choosing a roll, not a number of metres.
 *
 * (b) is what the earlier version missed: these products never carried the
 * _dt_price_unit meta, so they defaulted to 'metre' and passed the old check.
 *
 * @param int $parent Parent product ID.
 * @return string Empty if per-metre, otherwise the reason it is roll-quantity.
 */
function dorotape_roll_quantity_reason( int $parent ): string {
	if ( 'roll' === get_post_meta( $parent, '_dt_price_unit', true ) ) {
		return '_dt_price_unit=roll';
	}

	$product = wc_get_product( $parent );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return '';
	}

	global $wpdb;

	foreach ( $product->get_children() as $child_id ) {
		// Read the raw attribute_* meta rather than get_attributes(). On a
		// product whose attribute setup is broken the declared axis can be
		// empty while the real option label survives as an orphaned meta row —
		// ASLAN SRL96 declares pa_width, blank on all four children, yet still
		// carries attribute_pa_choose-roll-size-below = "1370mm-x-10m-roll".
		// get_attributes() returns only the declared axis, so it reports "" and
		// the roll would slip through.
		$values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta}
				  WHERE post_id = %d AND meta_key LIKE 'attribute\\_%%' AND meta_value <> ''",
				$child_id
			)
		);

		foreach ( $values as $value ) {
			// "<width>mm x <length>m" — the trailing (?!m) keeps "mm" out.
			if ( preg_match( '/\d{2,4}\s*mm[\s\-]*x[\s\-]*(\d+(?:\.\d+)?)\s*m(?!m)/i', (string) $value, $m ) ) {
				return sprintf( 'sold as fixed-length rolls (option "%s" is a %sm roll)', $value, $m[1] );
			}
		}
	}

	return '';
}

$index = dorotape_sku_index();

$handle = fopen( $csv, 'r' );
fgetcsv( $handle ); // header

$planned  = array(); // parent id => array( step, codes[] )
$unmatched = array();
$skipped   = array();
$roll_cleanup = array(); // parent id => reason, for steps wrongly written before

while ( false !== ( $row = fgetcsv( $handle ) ) ) {
	if ( ! isset( $row[0] ) || '' === trim( $row[0] ) ) {
		continue;
	}
	$code = strtoupper( trim( $row[0] ) );
	$incr = trim( $row[1] );

	if ( ! isset( $index[ $code ] ) ) {
		$unmatched[] = $code;
		continue;
	}

	// Fractional increments cannot survive the int cast in dorotape_qty_step().
	if ( (string) (int) $incr !== $incr ) {
		$skipped[] = "$code (increment $incr — fractional, unsupported)";
		continue;
	}

	$post   = get_post( $index[ $code ] );
	$parent = ( $post && 'product_variation' === $post->post_type )
		? (int) wp_get_post_parent_id( $index[ $code ] )
		: (int) $index[ $code ];

	if ( ! $parent ) {
		$unmatched[] = $code;
		continue;
	}

	// The sheet's figures are metres throughout — including for products that
	// happen to be sold as whole rolls. Digi-Fab is the case in point: it is
	// _dt_price_unit=roll and its roll IS 15m (£139.80 / 15 = £9.32 per metre),
	// so the "15" against it describes the roll, not a quantity of rolls.
	// Writing 15 as the step on a roll-priced product would force a 225m
	// minimum order. Skip and report; the unit itself needs deciding first.
	$roll_reason = dorotape_roll_quantity_reason( $parent );
	if ( '' !== $roll_reason ) {
		$skipped[] = sprintf(
			'%s (parent %d — %s; sheet gives metres, increment %s not applied)',
			$code,
			$parent,
			$roll_reason,
			$incr
		);
		// An earlier run wrote this step before the roll check covered option
		// labels, so the replay has to undo it rather than merely not repeat it.
		$roll_cleanup[ $parent ] = $roll_reason;
		continue;
	}

	if ( isset( $planned[ $parent ] ) && $planned[ $parent ]['step'] !== (int) $incr ) {
		// Guarded rather than assumed: if the sheet ever gains a product whose
		// variations disagree, a per-parent meta is the wrong model and this
		// must be seen, not averaged away.
		$skipped[] = "$code (parent $parent already claimed step {$planned[ $parent ]['step']}, sheet says $incr)";
		continue;
	}

	$planned[ $parent ]['step']    = (int) $incr;
	$planned[ $parent ]['codes'][] = $code;
}
fclose( $handle );

$written = 0;
$already = 0;

foreach ( $planned as $parent => $plan ) {
	$current = get_post_meta( $parent, '_dt_qty_step', true );
	$title   = html_entity_decode( get_the_title( $parent ) );
	$label   = sprintf( '#%-6d %-46s', $parent, mb_substr( $title, 0, 46 ) );

	if ( (int) $current === $plan['step'] ) {
		echo "  [ok]    $label step {$plan['step']} already set\n";
		++$already;
		continue;
	}

	$from = ( '' === $current ) ? 'none' : $current;
	if ( $apply ) {
		update_post_meta( $parent, '_dt_qty_step', $plan['step'] );
		echo "  [write] $label step $from -> {$plan['step']}\n";
	} else {
		echo "  [dry]   $label step $from -> {$plan['step']}\n";
	}
	++$written;
}

$removed = 0;
foreach ( $roll_cleanup as $parent => $reason ) {
	$current = get_post_meta( $parent, '_dt_qty_step', true );
	if ( '' === $current || (int) $current <= 1 ) {
		continue;
	}
	$title = html_entity_decode( get_the_title( $parent ) );
	$label = sprintf( '#%-6d %-46s', $parent, mb_substr( $title, 0, 46 ) );
	if ( $apply ) {
		delete_post_meta( $parent, '_dt_qty_step' );
		echo "  [remove] $label step $current removed — $reason\n";
	} else {
		echo "  [dry-rm] $label step $current would be removed — $reason\n";
	}
	++$removed;
}

echo "\n";
printf( "parents targeted : %d\n", count( $planned ) );
printf( "%s : %d\n", $apply ? 'roll steps removed' : 'roll steps to remove', $removed );
printf( "%s : %d\n", $apply ? 'written        ' : 'would write    ', $written );
printf( "already correct  : %d\n", $already );

if ( $skipped ) {
	printf( "\nSKIPPED (%d) — need a decision, not a default:\n", count( $skipped ) );
	foreach ( $skipped as $s ) {
		echo "  ! $s\n";
	}
}

if ( $unmatched ) {
	printf( "\nNO SKU MATCH (%d):\n  %s\n", count( $unmatched ), implode( ', ', $unmatched ) );
}
