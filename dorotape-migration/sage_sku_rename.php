<?php
/**
 * Apply high-confidence Sage SKU renames from sage_sku_automap.csv.
 *
 * Only processes rows whose confidence is "high (price match)" — the proposed
 * Sage code exists, is not obsolete, and the site price equals the Sage sales
 * price to the penny. Everything else (check-price, restructure candidates)
 * is handled by later, separate passes.
 *
 * Guards:
 *  - current SKU must still equal the CSV's old_sku (stale-row protection)
 *  - proposed SKU must not be in use by any other live product/variation
 *  - no two rows in the batch may propose the same SKU
 *
 * Every change is logged to sage_sku_rename_log.csv (old -> new, product id)
 * so the batch is reversible without the DB backup.
 *
 * Usage:
 *   /Applications/XAMPP/xamppfiles/bin/php sage_sku_rename.php            (dry run)
 *   /Applications/XAMPP/xamppfiles/bin/php sage_sku_rename.php --apply
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}
$apply = in_array( '--apply', $argv, true );
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../../../../wp-load.php';

global $wpdb;

// ─── Collect the high-confidence batch ────────────────────────────────────────

// variable parents carry website-only container SKUs — never rename to Sage codes
$variable_parent_ids = array_map( 'intval', $wpdb->get_col(
	"SELECT DISTINCT post_parent FROM {$wpdb->posts}
	 WHERE post_type = 'product_variation' AND post_status = 'publish'"
) );

$rows = array();
$fh   = fopen( __DIR__ . '/sage_sku_automap.csv', 'r' );
fgetcsv( $fh ); // header
while ( ( $r = fgetcsv( $fh ) ) !== false ) {
	if ( count( $r ) < 9 || 'high (price match)' !== $r[8] ) {
		continue;
	}
	if ( 'product' === $r[1] && in_array( (int) $r[0], $variable_parent_ids, true ) ) {
		continue; // container SKU
	}
	$rows[] = array(
		'id'       => (int) $r[0],
		'type'     => $r[1],
		'old'      => trim( $r[2] ),
		'new'      => trim( $r[3] ),
		'strategy' => $r[7],
	);
}
fclose( $fh );
echo 'High-confidence rows: ' . count( $rows ) . "\n";

// ─── Guards ───────────────────────────────────────────────────────────────────

// batch-internal duplicates: two rows proposing the same target code
$targets = array_count_values( array_column( $rows, 'new' ) );
$dupes   = array_keys( array_filter( $targets, fn( $n ) => $n > 1 ) );

// SKUs already used by live products (outside this batch's own old SKUs)
$batch_ids = array_column( $rows, 'id' );

$skipped = array();
$queue   = array();
foreach ( $rows as $row ) {
	if ( in_array( $row['new'], $dupes, true ) ) {
		$skipped[] = array( $row['id'], $row['old'], $row['new'], 'duplicate target within batch' );
		continue;
	}
	$current = get_post_meta( $row['id'], '_sku', true );
	if ( $current !== $row['old'] ) {
		$skipped[] = array( $row['id'], $row['old'], $row['new'], "stale row: current SKU is '$current'" );
		continue;
	}
	if ( $current === $row['new'] ) {
		continue; // already aligned, nothing to do
	}
	// live holder of the target SKU that isn't this product
	$holder = $wpdb->get_var( $wpdb->prepare(
		"SELECT pm.post_id FROM {$wpdb->postmeta} pm
		 JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
		 WHERE pm.meta_key = '_sku' AND pm.meta_value = %s AND pm.post_id <> %d
		 LIMIT 1",
		$row['new'], $row['id']
	) );
	if ( $holder && ! in_array( (int) $holder, $batch_ids, true ) ) {
		$skipped[] = array( $row['id'], $row['old'], $row['new'], "target SKU already on live product #$holder" );
		continue;
	}
	if ( $holder ) {
		// held by another batch member being renamed away — safe only if that
		// member is processed first; order the queue so vacators run early.
		$row['vacate_first'] = (int) $holder;
	}
	$queue[] = $row;
}

// rows whose target is currently held by another batch member run last, so
// the holder has been renamed away by the time they claim the code
usort( $queue, fn( $a, $b ) => isset( $a['vacate_first'] ) <=> isset( $b['vacate_first'] ) );

echo 'Queued renames:  ' . count( $queue ) . "\n";
echo 'Skipped:         ' . count( $skipped ) . "\n\n";
if ( $skipped ) {
	echo "Skips:\n";
	foreach ( $skipped as $s ) {
		printf( "  #%d %s -> %s | %s\n", ...$s );
	}
	echo "\n";
}

// ─── Apply / dry run ──────────────────────────────────────────────────────────

if ( ! $apply ) {
	echo "DRY RUN (pass --apply to write). Sample of queue:\n";
	foreach ( array_slice( $queue, 0, 25 ) as $q ) {
		printf( "  #%d [%s] %s -> %s (%s)\n", $q['id'], $q['type'], $q['old'], $q['new'], $q['strategy'] );
	}
	echo '  ... ' . max( 0, count( $queue ) - 25 ) . " more\n";
	exit( 0 );
}

$log = fopen( __DIR__ . '/sage_sku_rename_log.csv', 'a' );
fputcsv( $log, array( 'timestamp', 'product_id', 'type', 'old_sku', 'new_sku' ) );

$done = 0;
$fail = 0;
foreach ( $queue as $q ) {
	$product = wc_get_product( $q['id'] );
	if ( ! $product ) {
		$fail++;
		echo "  FAIL #{$q['id']} not loadable\n";
		continue;
	}
	try {
		$product->set_sku( $q['new'] );
		$product->save();
		fputcsv( $log, array( gmdate( 'c' ), $q['id'], $q['type'], $q['old'], $q['new'] ) );
		$done++;
	} catch ( WC_Data_Exception $e ) {
		$fail++;
		printf( "  FAIL #%d %s -> %s | %s\n", $q['id'], $q['old'], $q['new'], $e->getMessage() );
	}
	if ( 0 === $done % 100 ) {
		echo "  ...$done\n";
	}
}
fclose( $log );

printf( "\nApplied: %d, failed: %d. Log: sage_sku_rename_log.csv\n", $done, $fail );
