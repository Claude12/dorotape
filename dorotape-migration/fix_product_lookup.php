<?php
/**
 * Rebuild wc_product_meta_lookup rows that have fallen out of step with the meta.
 *
 * WooCommerce keeps a flat table, wc_product_meta_lookup, holding each product's
 * SKU, price and stock so the shop can sort and filter without touching postmeta.
 * It is a cache, but it is the cache wc_get_product_id_by_sku() and
 * wc_product_has_unique_sku() read. When it is stale, WooCommerce cannot see a
 * stock code that is plainly in the database.
 *
 * Two ways it went stale here, both ours:
 *
 *   1. Rows that were never created. A variation inserted with wp_insert_post()
 *      and filled in with update_post_meta() never goes through WooCommerce's
 *      CRUD, so no row is written. $wpdb->update() on a row that does not exist
 *      changes nothing and reports success, which is why this went unnoticed.
 *      round3_build.php created 78 variations this way.
 *
 *   2. Rows left holding an old SKU. Editing _sku directly leaves the lookup
 *      table pointing the code at the row that used to own it. Mutoh Smart
 *      Knives is the live example: the table says MSK is #6617, while #6617
 *      actually holds MSK300V and MSK belongs to #6620. Any sync matching on
 *      MSK would update the wrong product. That is the same failure we cleared
 *      off 900E, DFP071370 and DFP07500, arriving by a different route.
 *
 * The repair uses WooCommerce's own row builder rather than hand-written SQL, by
 * subclassing the data store to reach it, so the columns stay right as
 * WooCommerce adds them. postmeta is not touched at all: the meta is the truth
 * here and the table is what gets corrected.
 *
 * Trashed products keep their rows. A trashed row holding a code is worth
 * knowing about, so those are reported, not rewritten, and the codes we parked
 * earlier read as <code>__superseded which is exactly the point of parking them.
 *
 * Idempotent. A second run finds nothing to do.
 *
 * Usage:
 *   php fix_product_lookup.php            (dry run, default)
 *   php fix_product_lookup.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

/**
 * Reach WooCommerce's own lookup-row builder, which is protected.
 *
 * Nothing is reimplemented: get_data_for_lookup_table() and update_lookup_table()
 * are WooCommerce's, so the column list and the REPLACE semantics are whatever
 * this version of WooCommerce says they are.
 */
class DT_Lookup_Repair extends WC_Product_Data_Store_CPT {

	/**
	 * The row WooCommerce would write for this product.
	 *
	 * @param int $id Product or variation.
	 * @return array
	 */
	public function row_for( int $id ): array {
		return $this->get_data_for_lookup_table( $id, 'wc_product_meta_lookup' );
	}

	/**
	 * Write it.
	 *
	 * @param int $id Product or variation.
	 */
	public function write( int $id ): void {
		wp_cache_delete( 'lookup_table', 'object_' . $id );
		$this->update_lookup_table( $id, 'wc_product_meta_lookup' );
	}
}

global $wpdb;
$repair = new DT_Lookup_Repair();
$table  = $wpdb->wc_product_meta_lookup;

// ------------------------------------------------------------------ find them

// Live products and variations with no row at all.
$missing = $wpdb->get_col(
	"SELECT p.ID FROM {$wpdb->posts} p
	 WHERE p.post_type IN ('product','product_variation')
	   AND p.post_status <> 'trash'
	   AND NOT EXISTS ( SELECT 1 FROM {$table} l WHERE l.product_id = p.ID )
	 ORDER BY p.ID"
);

// Rows whose SKU is not the product's SKU. Both directions matter: a row holding
// a code the product gave up is the one that misdirects a lookup.
$wrong = $wpdb->get_results(
	"SELECT l.product_id, l.sku AS lookup_sku, COALESCE(m.meta_value,'') AS real_sku, p.post_type, p.post_status
	 FROM {$table} l
	 JOIN {$wpdb->posts} p ON p.ID = l.product_id
	 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = l.product_id AND m.meta_key = '_sku'
	 WHERE p.post_status <> 'trash'
	   AND l.sku <> COALESCE(m.meta_value,'')
	 ORDER BY l.product_id"
);

// Rows for products that are gone entirely. Reported, not touched: a stray row
// is harmless next to deleting something on a guess.
$orphans = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$table} l
	 WHERE NOT EXISTS ( SELECT 1 FROM {$wpdb->posts} p WHERE p.ID = l.product_id )"
);

printf( "Rows missing        : %d\n", count( $missing ) );
printf( "Rows with a stale SKU: %d\n", count( $wrong ) );
printf( "Rows with no product : %d (left alone)\n\n", $orphans );

// ---------------------------------------------------------------- the stale ones

if ( $wrong ) {
	echo "Stale SKUs, table versus meta:\n";
	foreach ( $wrong as $r ) {
		$title = html_entity_decode( (string) get_the_title( wp_get_post_parent_id( $r->product_id ) ?: $r->product_id ) );
		printf(
			"  #%-6d %-18s table=%-24s meta=%-24s %s\n",
			$r->product_id,
			'product' === $r->post_type ? 'product' : 'variation',
			'' === $r->lookup_sku ? '(empty)' : $r->lookup_sku,
			'' === $r->real_sku ? '(empty)' : $r->real_sku,
			$title
		);
	}
	echo "\n";
}

// -------------------------------------------------------------------- repair

$ids     = array_values( array_unique( array_merge( array_map( 'intval', $missing ), wp_list_pluck( $wrong, 'product_id' ) ) ) );
$written = 0;
$failed  = array();

foreach ( $ids as $id ) {
	$id   = (int) $id;
	$want = $repair->row_for( $id );
	if ( ! $want ) {
		$failed[] = "#$id: WooCommerce returned no row data";
		continue;
	}

	if ( $apply ) {
		$repair->write( $id );
		$now = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE product_id = %d", $id ), ARRAY_A );
		if ( ! $now ) {
			$failed[] = "#$id: no row after writing";
			continue;
		}
		// Check the row landed rather than trust that it did.
		if ( (string) $now['sku'] !== (string) $want['sku'] ) {
			$failed[] = sprintf( '#%d: sku is "%s" after writing, wanted "%s"', $id, $now['sku'], $want['sku'] );
			continue;
		}
	}
	++$written;
}

printf(
	"%s %d rows: sku, min/max price, stock and tax columns rebuilt from the meta.\n",
	$apply ? 'Rebuilt' : 'Would rebuild',
	$written
);

// ------------------------------------------------------------------- verify

// The whole point of the table being right is that a stock code resolves to one
// product. Worth proving, not assuming, and worth knowing either way.
$dupes = $wpdb->get_results(
	"SELECT l.sku, COUNT(*) AS n, GROUP_CONCAT(l.product_id) AS ids
	 FROM {$table} l
	 JOIN {$wpdb->posts} p ON p.ID = l.product_id
	 WHERE l.sku <> '' AND p.post_status <> 'trash'
	 GROUP BY l.sku HAVING n > 1
	 ORDER BY l.sku"
);

$still = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->posts} p
	 WHERE p.post_type IN ('product','product_variation')
	   AND p.post_status <> 'trash'
	   AND ( NOT EXISTS ( SELECT 1 FROM {$table} l WHERE l.product_id = p.ID )
	      OR EXISTS ( SELECT 1 FROM {$table} l LEFT JOIN {$wpdb->postmeta} m
	                  ON m.post_id = l.product_id AND m.meta_key = '_sku'
	                  WHERE l.product_id = p.ID AND l.sku <> COALESCE(m.meta_value,'') ) )"
);

echo "\n" . str_repeat( '-', 78 ) . "\n";
printf( "Live rows still out of step : %d\n", $still );
printf( "Codes on more than one live row: %d\n", count( $dupes ) );

foreach ( $dupes as $d ) {
	printf( "  ! %s on #%s\n", $d->sku, str_replace( ',', ', #', $d->ids ) );
}

if ( $failed ) {
	printf( "\nPROBLEMS (%d):\n", count( $failed ) );
	foreach ( $failed as $f ) {
		echo "  ! $f\n";
	}
}

if ( $apply ) {
	wc_delete_product_transients();
	echo "\nApplied. Re-run to confirm nothing is left to rebuild.\n";
} else {
	echo "\nDry run only - pass --apply to write.\n";
}
