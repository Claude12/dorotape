<?php
/**
 * Pricing unit and quick-add flags for the products that need them.
 *
 * These settings existed locally with no script behind them: fb_product_data.php
 * only ever set 900E, and the other twelve were written by an ad-hoc run that
 * left nothing to replay. A dev deployment would therefore have taken the theme
 * code across and left those twelve still reading "Price per metre" and "/m"
 * against a whole-roll price — exactly the fault Michael reported. This script
 * closes that gap so every per-product setting the 28 Jul round relies on has a
 * repeatable source.
 *
 * Two settings are covered:
 *
 *   _dt_price_unit = 'roll'  — drives the headline wording, the "/roll" price
 *     suffix, the "Price per roll" table heading and the suppression of the
 *     "supplied as a continuous length" note, all from dorotape_unit_strings().
 *     Applied to the 13 products sold by the whole roll.
 *
 *   _dt_quick_add  = '1'     — the Hook & Loop mix-and-match grid, where the
 *     10-roll discount is reached on the combined quantity across hook and loop.
 *
 * Products are keyed by SKU, not post ID. Post IDs are per-environment and dev's
 * may not match local; the SKUs are the Sage codes and are the same everywhere.
 *
 * Idempotent — a second run reports [ok] on every line and writes nothing.
 *
 * Usage:
 *   php fb_price_units.php --dry-run    (default)
 *   php fb_price_units.php --apply
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
 * Products sold by the whole roll rather than by the metre.
 *
 * SKU => human label, for the report only.
 */
$roll_products = array(
	'PP800'                => 'Poli Print 800',
	'PP805'                => 'Poli Print 805',
	'PP810'                => 'Poli Print 810',
	'PP1200'               => 'Poli Print 1200',
	'PP1230'               => 'Poli Print 1230',
	'DJ100'                => 'D-Jet 100',
	'DJ100R'               => 'D-Jet 100R',
	'DIGBA440'             => 'Digi-Banner 440',
	'DIGI-FAB'             => 'Digi-Fab',
	'DIGPA200'             => 'Digi-Paper 200',
	'900E'                 => '900E Application Tape',
	'HOOKB20 / LOOPB20'    => 'Black Hook and Loop Tape',
	'HOOKW20 / LOOPW20'    => 'White Hook and Loop Tape',
);

/** Quick-add (mix-and-match) parents. */
$quick_add_products = array(
	'HOOKB20 / LOOPB20' => 'Black Hook and Loop Tape',
	'HOOKW20 / LOOPW20' => 'White Hook and Loop Tape',
);

$changes = 0;
$missing = 0;

/**
 * Set one meta value on the product with the given SKU, reporting either way.
 *
 * @param string $sku
 * @param string $label
 * @param string $meta_key
 * @param string $wanted
 * @param bool   $apply
 * @param int    $changes Running change count, by reference.
 * @param int    $missing Running not-found count, by reference.
 */
function fb_set_product_meta( string $sku, string $label, string $meta_key, string $wanted, bool $apply, int &$changes, int &$missing ): void {
	$id = wc_get_product_id_by_sku( $sku );
	if ( ! $id ) {
		printf( "%-28s %-16s NOT FOUND (sku %s) — skipped\n", $label, $meta_key, $sku );
		++$missing;
		return;
	}

	$product = wc_get_product( $id );
	if ( ! $product ) {
		printf( "%-28s %-16s NOT LOADABLE (#%d) — skipped\n", $label, $meta_key, $id );
		++$missing;
		return;
	}

	$current = (string) get_post_meta( $id, $meta_key, true );
	$needed  = $wanted !== $current;

	printf(
		"%-28s %-16s %s  #%-6d '%s' -> '%s'\n",
		$label,
		$meta_key,
		$needed ? '[CHANGE]' : '[ok]    ',
		$id,
		'' === $current ? '(unset)' : $current,
		$wanted
	);

	if ( $needed ) {
		++$changes;
		if ( $apply ) {
			$product->update_meta_data( $meta_key, $wanted );
			$product->save();
		}
	}
}

echo "── Sold by the whole roll ───────────────────────────────────────────────\n";
foreach ( $roll_products as $sku => $label ) {
	fb_set_product_meta( $sku, $label, '_dt_price_unit', 'roll', $apply, $changes, $missing );
}

echo "\n── Quick-add (mix and match) ────────────────────────────────────────────\n";
foreach ( $quick_add_products as $sku => $label ) {
	fb_set_product_meta( $sku, $label, '_dt_quick_add', '1', $apply, $changes, $missing );
}

echo "\n" . str_repeat( '─', 74 ) . "\n";
printf( "%d change(s) needed, %d product(s) not found.\n", $changes, $missing );

if ( $missing > 0 ) {
	echo "WARNING: a missing SKU means this environment's catalogue differs — check before relying on the result.\n";
}

echo $apply ? "Applied.\n" : "Dry run only — pass --apply to write.\n";
