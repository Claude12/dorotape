<?php
/**
 * Mark every product sold by the whole roll as _dt_price_unit = 'roll'.
 *
 * Michael, 3 Aug 2026: "There are still some products that are sold by the roll
 * only such as these [ATP GP412, Kernow Ultimate Lightblock 230STL] where the
 * headline price still shows as /m. Did you want us to go through and compile a
 * complete list of these?"
 *
 * They don't need to. The distinction is derivable from the Sage export, so the
 * complete list is in roll_price_units.csv next to this file — 128 products,
 * derived and checked rather than eyeballed. fb_price_units.php covers the 13
 * that were already known; this covers the rest and leaves those 13 alone.
 *
 * HOW A ROLL PRODUCT WAS IDENTIFIED
 *
 * Three signals, each sufficient on its own, all sourced from the Sage export
 * (sage_skus.csv) rather than from anything the site already believes:
 *
 *   A. Sage's own unit is the roll. Its description names a full roll size
 *      ("1370MM X 50M") and the site charges the Sage price outright.
 *
 *      Naming a roll size is NOT by itself enough — Sage descriptions often
 *      record the roll a per-metre material is cut from. ASLAN DRP07 reads
 *      "1370MM X 50M" but is priced 9.080, which is a metre rate, not a roll.
 *      The two readings are separated by price per SQUARE METRE:
 *
 *          as a roll  : price / (width_m * length_m)
 *          as a metre : price / width_m
 *
 *      Roll length is 10-100m, so the readings differ by one to two orders of
 *      magnitude and only one is ever credible:
 *
 *          ATP-GP412  £236.10  1370mm x 50m -> £3.45/m² roll | £172.34/m² metre
 *          DRP07      £  9.08  1370mm x 50m -> £0.13/m² roll | £  6.63/m² metre
 *
 *      The credible per-metre band (£2-£30/m²) is not a guess: it is the
 *      observed range of the 951 SKUs whose Sage description carries a width but
 *      no length, which therefore cannot be anything but per-metre.
 *
 *   B. Sage bills per metre but the site sells fixed-length rolls priced at
 *      rate x length — ASLAN CT11355 is £7.62/m sold as a 5m roll at £38.10.
 *
 *   C. The customer picks a fixed length off the variation axis
 *      ("1250mm-x-25m-roll"). A per-metre product varies by WIDTH alone, so a
 *      length in the option means one unit is one roll. This needs no price
 *      arithmetic, which matters because several of these products defeat both
 *      price tests: ASLAN CT11383's 5m variation is not in the Sage export at
 *      all, and its 25m roll is discounted (£171.50, not 25 x £7.62 = £190.50).
 *
 * WHAT IS DELIBERATELY NOT HERE
 *
 * Products where the evidence conflicts are excluded and listed in the report
 * rather than guessed at — see the CSV's companion notes in the summary. That
 * is four products: two whose parent mixes roll and per-metre variations
 * (Doro 9052, Ritrama CF01) and two where both readings stay credible
 * (ASLAN TF200, Roll Up Banner Stand). They need a human answer, not a default.
 *
 * Only _dt_price_unit is written. Quick-add and quantity steps are separate
 * concerns and are left exactly as they are.
 *
 * Products are keyed by SKU, not post ID: post IDs are per-environment and
 * dev's may not match local, whereas the SKUs are Sage codes and are the same
 * everywhere. A variation SKU resolves to its parent before writing, because
 * dorotape_price_unit() reads the meta from the parent.
 *
 * Idempotent — a second run reports [ok] on every line and writes nothing.
 *
 * Usage:
 *   php roll_price_units.php              (dry run, default)
 *   php roll_price_units.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

$csv = __DIR__ . '/roll_price_units.csv';
if ( ! is_readable( $csv ) ) {
	exit( "Cannot read $csv\n" );
}

$handle = fopen( $csv, 'r' );
fgetcsv( $handle ); // header

$changed = 0;
$already = 0;
$missing = array();
$conflict = array();
$seen    = array();

while ( false !== ( $row = fgetcsv( $handle ) ) ) {
	if ( ! isset( $row[0] ) || '' === trim( $row[0] ) ) {
		continue;
	}
	$sku      = trim( $row[0] );
	$title    = $row[1] ?? '';
	$basis    = $row[2] ?? '';
	$evidence = $row[3] ?? '';

	$id = wc_get_product_id_by_sku( $sku );
	if ( ! $id ) {
		$missing[] = sprintf( '%s (%s)', $sku, $title );
		continue;
	}

	// The CSV keys on whichever SKU is resolvable, which for most of these is a
	// variation's. dorotape_price_unit() reads the parent, so writing on the
	// variation would be silently ignored.
	$post   = get_post( $id );
	$parent = ( $post && 'product_variation' === $post->post_type )
		? (int) wp_get_post_parent_id( $id )
		: (int) $id;

	if ( ! $parent ) {
		$missing[] = sprintf( '%s (%s) — no parent', $sku, $title );
		continue;
	}

	if ( isset( $seen[ $parent ] ) ) {
		continue; // two SKUs of one parent; already handled
	}
	$seen[ $parent ] = true;

	$current = (string) get_post_meta( $parent, '_dt_price_unit', true );
	$label   = sprintf( '#%-6d %-52s', $parent, mb_substr( html_entity_decode( get_the_title( $parent ) ), 0, 52 ) );

	if ( 'roll' === $current ) {
		echo "  [ok]     $label already roll\n";
		++$already;
		continue;
	}

	// 'item' is a deliberate CMS choice for accessories and is not ours to
	// overwrite — report it and move on rather than assume the sweep wins.
	if ( 'item' === $current ) {
		$conflict[] = sprintf( '%s (#%d) is set to "item"; evidence says roll — %s', $title, $parent, $evidence );
		continue;
	}

	$from = '' === $current ? '(unset=metre)' : $current;
	if ( $apply ) {
		update_post_meta( $parent, '_dt_price_unit', 'roll' );
		echo "  [write]  $label $from -> roll  [$basis]\n";
	} else {
		echo "  [dry]    $label $from -> roll  [$basis]\n";
	}
	++$changed;
}
fclose( $handle );

echo "\n" . str_repeat( '─', 78 ) . "\n";
printf( "%s : %d\n", $apply ? 'written        ' : 'would write    ', $changed );
printf( "already correct  : %d\n", $already );

if ( $conflict ) {
	printf( "\nCONFLICT (%d) — an explicit setting disagrees, left untouched:\n", count( $conflict ) );
	foreach ( $conflict as $c ) {
		echo "  ! $c\n";
	}
}

if ( $missing ) {
	printf( "\nNO SKU MATCH (%d) — this catalogue differs from the one analysed:\n", count( $missing ) );
	foreach ( $missing as $m ) {
		echo "  ! $m\n";
	}
}

echo "\nNOT covered by this script — evidence conflicts, needs a decision:\n";
echo "  ! Doro 9052 White Matt Magnetic Vinyl — one 1260mm x 10m roll variation\n";
echo "    alongside two per-metre variations under the same parent.\n";
echo "  ! Ritrama CF01 Pink Fluorescent — per-metre, but one '203mm x 1m' option.\n";
echo "  ! ASLAN TF200 Application Tape — £39.40 for 1370mm x 10m reads as either\n";
echo "    £2.88/m² per roll or £28.76/m² per metre; both are credible.\n";
echo "  ! Roll Up Banner Stand 850mm — hardware, probably 'item' rather than roll.\n";

echo "\n" . ( $apply ? "Applied.\n" : "Dry run only — pass --apply to write.\n" );
