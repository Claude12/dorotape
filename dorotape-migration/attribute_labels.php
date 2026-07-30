<?php
/**
 * Rename product attribute labels.
 *
 * Client: "Can we also make the 'Size / Format' option read 'Roll size'". The
 * label is what appears above the dropdown on the product page and in the
 * variation summary in the basket, so this is a display-only change.
 *
 * Only attribute_label is touched, never attribute_name. The name is the second
 * half of the taxonomy (pa_size-format) that every term relationship and every
 * variation's attribute meta key is stored against; changing it would orphan
 * 185 terms across 85 products for no benefit, because the slug is never shown
 * to a customer.
 *
 * Keyed by attribute_name rather than attribute_id — IDs are per-environment.
 *
 * Idempotent — a second run reports [ok] and writes nothing.
 *
 * Usage:
 *   php attribute_labels.php --dry-run    (default)
 *   php attribute_labels.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

/** attribute_name => new attribute_label. */
$labels = array(
	'size-format' => 'Roll size',
);

global $wpdb;
$table   = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
$changes = 0;
$missing = 0;

foreach ( $labels as $name => $wanted ) {
	$row = $wpdb->get_row(
		$wpdb->prepare( "SELECT attribute_id, attribute_label FROM {$table} WHERE attribute_name = %s", $name ) // phpcs:ignore WordPress.DB
	);

	if ( ! $row ) {
		printf( "pa_%-20s NOT FOUND — skipped\n", $name );
		++$missing;
		continue;
	}

	$needed = $wanted !== $row->attribute_label;
	printf(
		"pa_%-20s %s  #%-3d '%s' -> '%s'\n",
		$name,
		$needed ? '[CHANGE]' : '[ok]    ',
		$row->attribute_id,
		$row->attribute_label,
		$wanted
	);

	if ( ! $needed ) {
		continue;
	}
	++$changes;

	if ( $apply ) {
		$wpdb->update( $table, array( 'attribute_label' => $wanted ), array( 'attribute_id' => $row->attribute_id ) ); // phpcs:ignore WordPress.DB
	}
}

if ( $apply && $changes ) {
	// wc_get_attribute_taxonomies() serves the whole table from a transient, so
	// the rename is invisible on the front end until this is cleared.
	delete_transient( 'wc_attribute_taxonomies' );
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}
	echo "\nAttribute caches cleared.\n";
}

echo "\n" . str_repeat( '─', 74 ) . "\n";
printf( "%d change(s) needed, %d attribute(s) not found.\n", $changes, $missing );
echo $apply ? "Applied.\n" : "Dry run only — pass --apply to write.\n";
