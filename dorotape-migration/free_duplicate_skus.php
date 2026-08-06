<?php
/**
 * Free three Sage stock codes held hostage by trashed duplicate products.
 *
 * Three codes each sit on two rows, a trashed copy and the real published one:
 *
 *     900E        #6506 trash    #6513 publish
 *     DFP071370   #6572 trash    #7345 publish
 *     DFP07500    #7314 trash    #7344 publish
 *
 * A trashed product keeps its SKU, and WooCommerce's meta lookup table keeps
 * indexing it, so wc_get_product_id_by_sku() can return the trashed row. That
 * is what made ASLAN DFP07 look like it had unassigned codes and put it on
 * Michael's snag list, which he rightly queried ("Not sure why this is on the
 * list at all"). It was our data, not his answer.
 *
 * Leaving them also means any future sync has three codes resolving to two rows
 * apiece, with nothing guaranteeing which one wins.
 *
 * The trashed copies are PARKED, not deleted: the SKU moves to
 * "<code>__superseded" and the original is recorded in _dt_superseded_sku so the
 * change is traceable and reversible. Deleting a colleague's trashed work to win
 * a stock code is not this script's call, and a parked SKU frees the code just as
 * well.
 *
 * Guards: refuses to touch any row that is not in the trash, and refuses to act
 * unless the published twin actually exists and holds the code. If either is not
 * true the situation is not the one described above and a human should look.
 *
 * Idempotent — a second run reports [ok] and writes nothing.
 *
 * Usage:
 *   php free_duplicate_skus.php            (dry run, default)
 *   php free_duplicate_skus.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

/** code => [ trashed id that must give it up, published id that must keep it ] */
$pairs = array(
	'900E'      => array( 6506, 6513 ),
	'DFP071370' => array( 6572, 7345 ),
	'DFP07500'  => array( 7314, 7344 ),
);

$freed   = 0;
$already = 0;
$refused = array();

foreach ( $pairs as $code => list( $trash_id, $keep_id ) ) {
	$parked = $code . '__superseded';

	$trash_post = get_post( $trash_id );
	$keep_post  = get_post( $keep_id );

	printf( "%s\n", $code );

	if ( ! $trash_post ) {
		echo "  [ok]     #$trash_id no longer exists, nothing holding the code\n\n";
		++$already;
		continue;
	}

	$trash_sku = get_post_meta( $trash_id, '_sku', true );
	$keep_sku  = $keep_post ? get_post_meta( $keep_id, '_sku', true ) : '';

	printf(
		"  trashed  #%-5d status=%-8s sku=%s\n",
		$trash_id,
		$trash_post->post_status,
		'' !== $trash_sku ? $trash_sku : '(none)'
	);
	printf(
		"  keeping  #%-5d status=%-8s sku=%s  %s\n",
		$keep_id,
		$keep_post ? $keep_post->post_status : 'MISSING',
		'' !== $keep_sku ? $keep_sku : '(none)',
		$keep_post ? html_entity_decode( get_the_title( $keep_post->post_parent ?: $keep_id ) ) : ''
	);

	if ( $parked === $trash_sku ) {
		echo "  [ok]     already parked\n\n";
		++$already;
		continue;
	}

	if ( $trash_sku !== $code ) {
		echo "  [ok]     trashed row does not hold $code, nothing to free\n\n";
		++$already;
		continue;
	}

	// Only ever park something that is actually in the trash.
	if ( 'trash' !== $trash_post->post_status ) {
		$refused[] = "$code: #$trash_id is '{$trash_post->post_status}', not trashed";
		echo "  !        refusing: #$trash_id is not in the trash\n\n";
		continue;
	}

	// Never free a code the published twin is not actually claiming.
	if ( ! $keep_post || $keep_sku !== $code ) {
		$refused[] = "$code: #$keep_id does not hold the code";
		echo "  !        refusing: the published row does not hold $code\n\n";
		continue;
	}

	if ( $apply ) {
		update_post_meta( $trash_id, '_dt_superseded_sku', $code );
		update_post_meta( $trash_id, '_sku', $parked );
		// Keep the lookup table in step, or the old value keeps resolving.
		if ( class_exists( 'WC_Product_Data_Store_CPT' ) ) {
			$store = new WC_Product_Data_Store_CPT();
			if ( method_exists( $store, 'update_product_lookup_tables_column' ) ) {
				$store->update_product_lookup_tables_column( $trash_id, 'sku' );
			}
		}
		global $wpdb;
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'sku' => $parked ), array( 'product_id' => $trash_id ) );
		printf( "  [write]  #%d %s -> %s\n\n", $trash_id, $code, $parked );
	} else {
		printf( "  [dry]    #%d %s -> %s\n\n", $trash_id, $code, $parked );
	}
	++$freed;
}

echo str_repeat( '-', 78 ) . "\n";
printf( "%s : %d\n", $apply ? 'freed          ' : 'would free     ', $freed );
printf( "already clean    : %d\n", $already );

if ( $refused ) {
	printf( "\nREFUSED (%d) - left untouched:\n", count( $refused ) );
	foreach ( $refused as $r ) {
		echo "  ! $r\n";
	}
}

if ( $apply ) {
	wc_delete_product_transients();
	echo "\nApplied. Re-run to confirm every line reports [ok].\n";
} else {
	echo "\nDry run only - pass --apply to write.\n";
}
