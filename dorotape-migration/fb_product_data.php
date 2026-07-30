<?php
/**
 * Product-data fixes from Michael's "more complex products" feedback.
 *
 * Each fix is independent and idempotent — re-running is a no-op, so this is
 * safe to replay on dev after the theme push.
 *
 *   1. 900E sold by the whole roll, not by the metre. Currently shows "Price
 *      per metre", "/m" and the "supplied as a continuous length" note against
 *      a £120.30 whole-roll price. Setting _dt_price_unit=roll corrects all
 *      three at once (dorotape_unit_strings() drives the lot).
 *   2. ASLAN DFP07 quantity step 5 -> 10 ("this product does need to be
 *      stepped in 10's, not 5's - my bad on that").
 *   3. DFP07 variation #7314 (DFP07500, 500mm x 25m, £105) is published but
 *      its attribute term was never added to the parent, so it can't be
 *      selected or bought. Re-attach the term.
 *   4. Poli-Print 800 roll sizes sort narrow -> wide (500 / 760 / 1372 / 1600)
 *      instead of alphabetically by slug (1372, 1600, 500, 760).
 *
 * NOT included (needs Michael's sign-off first — see the reply):
 *   - Renaming the shared "Size / Format" attribute to "Roll size": that label
 *     is global and also drives Ri-Jet 100 and others, so it is a catalogue
 *     decision, not a PP800 one.
 *   - Restructuring DFP07 / Ri-Jet C50 to width-first per-metre variations:
 *     that changes Sage SKUs and belongs in width_restructure.php.
 *
 * Usage:
 *   php fb_product_data.php --dry-run    (default)
 *   php fb_product_data.php --apply
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
 * Report one check.
 *
 * @param string $label
 * @param bool   $needed
 * @param string $detail
 */
function fb_report( string $label, bool $needed, string $detail ): void {
	printf( "%-46s %s  %s\n", $label, $needed ? '[CHANGE]' : '[ok]    ', $detail );
}

// ── 1. 900E sold per roll ────────────────────────────────────────────────────
$id_900e = 6513;
$p       = wc_get_product( $id_900e );
if ( ! $p ) {
	echo "900E #$id_900e NOT FOUND — skipped\n";
} else {
	$current = get_post_meta( $id_900e, '_dt_price_unit', true );
	$needed  = 'roll' !== $current;
	fb_report( "900E #$id_900e price unit", $needed, "'" . ( $current ?: 'metre (default)' ) . "' -> 'roll'" );
	if ( $needed && $apply ) {
		$p->update_meta_data( '_dt_price_unit', 'roll' );
		$p->save();
	}
}

// ── 2. DFP07 quantity step 5 -> 10 ───────────────────────────────────────────
//
// GATED ON PURPOSE. Michael asked for steps of 10 on DFP07, but the product is
// still in its pre-Sage shape: whole-roll variations (1370mm x 10m = £84,
// 1370mm x 50m = £378) with no _dt_price_unit and no per-metre tiers. A step of
// 10 against THAT structure forces a minimum order of ten whole rolls — £840 —
// which is plainly not what was meant.
//
// The step only makes sense once DFP07 is restructured width-first per metre
// (the 7901 / Ri-Lam C30 shape), which is also what "the options need to be
// just the roll width i.e. 1370mm & 1370mm Grey Adhesive" describes: pick a
// width, order N metres. So apply the step only when the restructure has
// landed, detected by an explicit per-metre unit plus a pa_width attribute.
$id_dfp = 6569;
$p      = wc_get_product( $id_dfp );
if ( ! $p ) {
	echo "DFP07 #$id_dfp NOT FOUND — skipped\n";
} else {
	$restructured = 'metre' === (string) get_post_meta( $id_dfp, '_dt_price_unit', true )
		&& array_key_exists( 'pa_width', $p->get_attributes() );
	$current      = (string) get_post_meta( $id_dfp, '_dt_qty_step', true );

	if ( ! $restructured ) {
		fb_report(
			"DFP07 #$id_dfp quantity step",
			false,
			"HELD at '" . ( $current ?: 'none' ) . "' — still whole-roll priced; step 10 would mean a 10-roll minimum"
		);
	} else {
		$needed = '10' !== $current;
		fb_report( "DFP07 #$id_dfp quantity step", $needed, "'" . ( $current ?: 'none' ) . "' -> '10'" );
		if ( $needed && $apply ) {
			$p->update_meta_data( '_dt_qty_step', '10' );
			$p->save();
		}
	}
}

// ── 3. DFP07 orphan variation #7314 ──────────────────────────────────────────
$orphan   = 7314;
$var      = wc_get_product( $orphan );
$taxonomy = 'pa_select-length-of-roll';
if ( ! $var ) {
	echo "Variation #$orphan NOT FOUND — skipped\n";
} else {
	$attrs = $var->get_variation_attributes();
	$slug  = '';
	foreach ( $attrs as $key => $value ) {
		if ( false !== strpos( $key, $taxonomy ) ) {
			$slug = $value;
		}
	}
	$assigned = wp_list_pluck( wc_get_product_terms( $id_dfp, $taxonomy, array( 'fields' => 'all' ) ), 'slug' );
	$needed   = $slug && ! in_array( $slug, $assigned, true );
	fb_report(
		"DFP07 orphan variation #$orphan",
		$needed,
		$needed ? "term '$slug' missing from parent — re-attach" : "term '$slug' already on parent"
	);

	if ( $needed && $apply ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term ) {
			// Append, never replace: wp_set_object_terms() with append=true
			// leaves the four existing roll-length terms in place.
			wp_set_object_terms( $id_dfp, array( (int) $term->term_id ), $taxonomy, true );

			// The parent's own attribute option list is stored separately from
			// the term relationships and must be extended to match, or the
			// dropdown still won't offer it.
			$product_attrs = $p->get_attributes();
			if ( isset( $product_attrs[ $taxonomy ] ) ) {
				$options = $product_attrs[ $taxonomy ]->get_options();
				if ( ! in_array( (int) $term->term_id, array_map( 'intval', $options ), true ) ) {
					$options[] = (int) $term->term_id;
					$product_attrs[ $taxonomy ]->set_options( $options );
					$p->set_attributes( $product_attrs );
					$p->save();
				}
			}
			WC_Product_Variable::sync( $id_dfp );
			echo "    re-attached '{$term->name}' (term {$term->term_id})\n";
		} else {
			echo "    !! term '$slug' does not exist in $taxonomy — needs manual review\n";
		}
	}
}

// ── 4. Poli-Print 800 roll sizes ordered narrow -> wide ──────────────────────
// Terms sort by the 'order' term meta the theme already uses for pa_width;
// without it WooCommerce falls back to name order, which sorts the slugs as
// strings (1372, 1600, 500, 760).
$id_pp800 = 6647;
$pp_tax   = 'pa_size-format';
$terms    = wc_get_product_terms( $id_pp800, $pp_tax, array( 'fields' => 'all' ) );
if ( ! $terms ) {
	echo "PP800 #$id_pp800: no $pp_tax terms — skipped\n";
} else {
	// Sort by the leading width in mm parsed from the term name.
	$sortable = array();
	foreach ( $terms as $t ) {
		$width      = preg_match( '/(\d+)\s*mm/i', $t->name, $m ) ? (int) $m[1] : PHP_INT_MAX;
		$sortable[] = array( 'term' => $t, 'width' => $width );
	}
	usort( $sortable, function ( $a, $b ) { return $a['width'] <=> $b['width']; } );

	$position = 0;
	foreach ( $sortable as $entry ) {
		++$position;
		$t       = $entry['term'];
		$current = get_term_meta( $t->term_id, 'order', true );
		$needed  = (string) $position !== (string) $current;
		fb_report(
			"PP800 order '{$t->name}'",
			$needed,
			"'" . ( '' === $current ? 'unset' : $current ) . "' -> $position"
		);
		if ( $needed && $apply ) {
			update_term_meta( $t->term_id, 'order', $position );
		}
	}
}

echo "\n" . ( $apply ? "Applied.\n" : "Dry run only — pass --apply to write.\n" );
