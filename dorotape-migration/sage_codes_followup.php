<?php
/**
 * Replace placeholder website codes with the real Sage stock codes Michael
 * supplied in "Dorotape New Website vs Sage Review - FollowUp.xlsx".
 *
 * WHY SO FEW ROWS
 *
 * The follow-up sheet has 135 rows and Michael answered 131 of them, so the
 * expectation was that most would apply. They do not, and the reason is
 * structural rather than careless.
 *
 * Woosage sends the WooCommerce SKU to Sage as the stock code verbatim
 * (Classes/WC.php::process_sku_for_sage uppercases it and substitutes spaces,
 * nothing more). So the SKU is the Sage code, one to one, and WooCommerce will
 * not let two variations share one SKU.
 *
 * But Sage holds one code per WIDTH, priced per metre, while the website sells
 * several LENGTHS off that width. ASLAN CT11355 is the standard case:
 *
 *     Sage    CT113551250   "ASLAN CT11355 YELLOW TRANSPARENT VINYL 1250MM"  £7.62/m
 *     Site    #3469  1250mm x 5m Roll    £38.10   = 5 x 7.62
 *     Site    #3470  1250mm x 25m Roll   £171.50  = 25 x 7.62 less 10%
 *
 * The 5m variation already holds CT113551250. Asked for the 25m variation's
 * code, Michael gave CT113551250 again, because in Sage that genuinely is the
 * code. There is no second code to give. So the answer is correct and still
 * unusable: the 25m line needs a stock code of its own created in Sage, or the
 * product needs to sell per metre instead of as two fixed rolls.
 *
 * That single pattern accounts for 50 rows. Another 29 gave two or three codes
 * for a product that has not had its size options built yet, 22 described how
 * the product works without naming a code, 11 pointed at another row, 4 are
 * blank and 13 were already done. Those are questions for Michael or build
 * work, not SKU writes, and guessing at them would put live stock codes on the
 * wrong lines. They are in round3.csv beside this file, grouped by what is
 * actually being asked.
 *
 * RESOLVED 5 AUGUST 2026
 *
 * Michael answered the structural question rather than supplying more codes:
 * drop the fixed roll sizes and sell per metre in increments, with the break
 * where the old large roll used to be. That one instruction clears the 50-row
 * pattern above, and his completed price-breaks sheet clears the rest.
 *
 * round3_decisions.csv beside this file is now the build reference: one row per
 * product, both of his sheets merged, 105 products of which 88 are fully
 * specified. It supersedes round3.csv for anything other than tracing which
 * original row an answer came from.
 *
 * WHAT IS HERE
 *
 * The three rows where the evidence is unambiguous: the target line resolves,
 * the code Michael gave is a live Sage code, it is not in use anywhere else on
 * the site, and the site price reconciles against the Sage price exactly or as
 * a whole-metre multiple.
 *
 * DFP08 is deliberately excluded even though it passes those tests. Row 23 puts
 * DFP081370 on the 50m line, but row 90 puts the same code on the 10m line and
 * asks for the product to be priced in 10m steps. Only one line can hold it,
 * and the 10m line is the better claim, so the product waits for the rebuild.
 *
 * Magnetic Tape gets two of its six lines and no more. Sage has four codes
 * there (two widths x two polarities) but the site puts widths, packs of five
 * and polarity on a single dropdown, with the two polarity lines priced £0.00.
 * The two "by the roll" lines match a Sage code exactly and are safe; the rest
 * of that product needs rebuilding before it can carry live codes.
 *
 * Keyed by the current SKU rather than post ID, so it replays on dev where the
 * IDs differ. Idempotent: a second run reports [ok] on every line.
 *
 * Usage:
 *   php sage_codes_followup.php              (dry run, default)
 *   php sage_codes_followup.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

$csv = __DIR__ . '/sage_codes_followup.csv';
if ( ! is_readable( $csv ) ) {
	exit( "Cannot read $csv\n" );
}

$handle = fopen( $csv, 'r' );
fgetcsv( $handle ); // header

$changed = 0;
$already = 0;
$blocked = array();

while ( false !== ( $row = fgetcsv( $handle ) ) ) {
	if ( ! isset( $row[0] ) || '' === trim( $row[0] ) ) {
		continue;
	}
	$old     = trim( $row[0] );
	$new     = trim( $row[1] );
	$product = $row[2] ?? '';

	$id = wc_get_product_id_by_sku( $old );

	if ( ! $id ) {
		// Either already applied, or this catalogue differs from the one analysed.
		$done = wc_get_product_id_by_sku( $new );
		if ( $done ) {
			echo "  [ok]     #$done $product already carries $new\n";
			++$already;
		} else {
			$blocked[] = sprintf( '%s: neither %s nor %s is on this catalogue', $product, $old, $new );
		}
		continue;
	}

	// Never overwrite a code that is live somewhere else. WooCommerce enforces
	// unique SKUs anyway, but failing loudly here says which line owns it.
	$holder = wc_get_product_id_by_sku( $new );
	if ( $holder && $holder !== $id ) {
		$blocked[] = sprintf(
			'%s: %s is already on #%d (%s)',
			$product,
			$new,
			$holder,
			html_entity_decode( get_the_title( $holder ) )
		);
		continue;
	}

	if ( $apply ) {
		$product_obj = wc_get_product( $id );
		if ( ! $product_obj ) {
			$blocked[] = sprintf( '%s: #%d did not load as a product', $product, $id );
			continue;
		}
		$product_obj->set_sku( $new );
		$product_obj->save();
		echo "  [write]  #$id $old -> $new\n";
	} else {
		echo "  [dry]    #$id $old -> $new\n";
	}
	++$changed;
}
fclose( $handle );

echo "\n" . str_repeat( '-', 78 ) . "\n";
printf( "%s : %d\n", $apply ? 'written        ' : 'would write    ', $changed );
printf( "already correct  : %d\n", $already );

if ( $blocked ) {
	printf( "\nBLOCKED (%d) - left untouched:\n", count( $blocked ) );
	foreach ( $blocked as $b ) {
		echo "  ! $b\n";
	}
}

echo "\nAnswered 5 August 2026, see round3_decisions.csv for the build reference:\n";
echo "  88  fully specified, being built, nothing further needed\n";
echo "  10  already correct, no action\n";
echo "   7  still with Michael (FF550 price, bungee pack price, GL16 code,\n";
echo "       smart knife new/exchange, Eco Sol Max 2 vs 3, Doro 9052)\n";

echo "\n" . ( $apply ? "Applied.\n" : "Dry run only - pass --apply to write.\n" );
