<?php
/**
 * D-Jet 100 / 100R -> Orajet 3164 / 3162: build the real size x finish matrix
 * and rename the products.
 *
 * Michael, 2026-07-28 ("Complex Products"): "We think it work best to amend the
 * product title from D-Jet 100 / D-Jet 100R to Orajet 3164 / Orajet 3162 along
 * with relevant changes to the product descriptions using the current sizes,
 * finish & SKU's as set out below." This implements that — his stated first
 * preference, not the PP800-style split he offered as the alternative.
 *
 * It also fixes a live mis-ordering bug. Both products already offer a finish
 * dropdown with three real options, but every variation carries an EMPTY finish
 * attribute, so all three options resolve to the same variation, the same SKU
 * and the same price. Ordering "Clear Gloss" at 1370mm on the D-Jet 100 today
 * puts DJ1001370 in the basket, which Sage calls GLOSS WHITE. Filling in the
 * matrix is what fixes that, so it happens here rather than as a separate job.
 *
 * PRICES ARE NOT INVENTED. Every price below is the Sales Price from
 * sage_skus.csv (the Sage stock export) for that exact stock code, and the
 * three codes already on the site agree with what the site currently charges.
 * Price turns out to track size only, not finish. The five existing variations
 * are reused and re-labelled rather than deleted and rebuilt, so their IDs,
 * order history and any stock data survive.
 *
 * The 3162 matrix is deliberately ragged — 7 rows, not 9. Michael's table lists
 * matt removable at 1370mm only, and that is copied exactly rather than
 * squared off. 3162 also gains a 760mm size the product does not currently
 * carry, again because his table lists it.
 *
 * NO REDIRECTS DURING THE DEV PHASE (Claudius, 30 Jul 2026). Renaming a slug makes
 * WordPress record the previous one in _wp_old_slug, and core's
 * wp_old_slug_redirect() then 301s the old URL to the new one — automatically, with
 * no plugin involved. That is unwanted here: redirects are an SEO decision to be
 * taken later, against the Kryptronic URLs customers actually have indexed, not the
 * interim WordPress ones. So this script deletes _wp_old_slug on both products after
 * renaming them.
 *
 * Consequence, and it is deliberate: /product/d-jet-100-4-year-digital-vinyl/ now
 * returns 404 rather than redirecting. Restore by removing the cleanup below and
 * re-running, or by re-inserting the meta.
 *
 * Description edits are text-only. No src or href on either product contains
 * "D-Jet" (checked before writing this), and the datasheet PDFs are already
 * named ORAJET_3164.pdf / ORAJET_3162.pdf, so replacing the visible product name
 * cannot break an asset link.
 *
 * Idempotent — a second run reports [ok] throughout and writes nothing.
 *
 * Usage:
 *   php orajet_restructure.php            (dry run, default)
 *   php orajet_restructure.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

const DT_SIZE_TAX   = 'pa_size-format';
const DT_FINISH_TAX = 'pa_choose-finish-below';

/**
 * The two products, exactly as Michael's tables set them out.
 *
 * 'matrix' rows are [ sku, size term slug, finish term slug, price ].
 */
$config = array(
	6592 => array(
		'title'   => 'Orajet 3164 – 4 Year Digital Vinyl',
		'slug'    => 'orajet-3164-4-year-digital-vinyl',
		'replace' => array( 'D-Jet 100R' => 'Orajet 3164', 'D-Jet 100' => 'Orajet 3164' ),
		'matrix'  => array(
			array( 'DJ100760',       '760mm-x-50m',    'gloss-white-grey-adhesive', '81.30' ),
			array( 'DJ100M760',      '760mm-x-50m',    'matt-white-grey-adhesive',  '81.30' ),
			array( 'DJ100CLEAR760',  '760mm-x-50m',    'clear-gloss',               '81.30' ),
			array( 'DJ1001370',      '1370mm-x-50m-4', 'gloss-white-grey-adhesive', '146.40' ),
			array( 'DJ100M1370',     '1370mm-x-50m-4', 'matt-white-grey-adhesive',  '146.40' ),
			array( 'DJ100CLEAR1370', '1370mm-x-50m-4', 'clear-gloss',               '146.40' ),
			array( 'DJ1001600',      '1600mm-x-50m-2', 'gloss-white-grey-adhesive', '185.00' ),
			array( 'DJ100M1600',     '1600mm-x-50m-2', 'matt-white-grey-adhesive',  '185.00' ),
			array( 'DJ100CLEAR1600', '1600mm-x-50m-2', 'clear-gloss',               '185.00' ),
		),
	),
	6596 => array(
		'title'   => 'Orajet 3162 – Removable 4 Year Digital Vinyl',
		'slug'    => 'orajet-3162-removable-4-year-digital-vinyl',
		'replace' => array( 'D-Jet 100R' => 'Orajet 3162', 'D-Jet 100' => 'Orajet 3162' ),
		'matrix'  => array(
			array( 'DJ100R760',       '760mm-x-50m',    'gloss-white', '81.30' ),
			array( 'DJ100RCLEAR760',  '760mm-x-50m',    'clear-gloss', '81.30' ),
			array( 'DJ100R1370',      '1370mm-x-50m-4', 'gloss-white', '146.40' ),
			array( 'DJ100RMATT1370',  '1370mm-x-50m-4', 'matt-white',  '146.40' ),
			array( 'DJ100RCLEAR1370', '1370mm-x-50m-4', 'clear-gloss', '146.40' ),
			array( 'DJ100R1600',      '1600mm-x-50m-2', 'gloss-white', '185.00' ),
			array( 'DJ100RCLEAR1600', '1600mm-x-50m-2', 'clear-gloss', '185.00' ),
		),
	),
);

/**
 * Look up every _sku in one query so a variation can be found by SKU without
 * a per-row meta query.
 *
 * @return array<string,int>
 */
function dt_sku_index(): array {
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

$index   = dt_sku_index();
$problem = array();

// ── Pre-flight: every term the matrix names must already exist. ──────────────
// Creating them silently would risk inventing a finish that does not match
// Michael's wording; if one is missing that is a question, not a default.
foreach ( $config as $pid => $cfg ) {
	foreach ( $cfg['matrix'] as $row ) {
		foreach ( array( DT_SIZE_TAX => $row[1], DT_FINISH_TAX => $row[2] ) as $tax => $slug ) {
			if ( ! get_term_by( 'slug', $slug, $tax ) ) {
				$problem[] = "missing term '$slug' in $tax (needed by {$row[0]})";
			}
		}
	}
}
if ( $problem ) {
	echo "PRE-FLIGHT FAILED:\n";
	foreach ( array_unique( $problem ) as $p ) {
		echo "  ! $p\n";
	}
	exit( 1 );
}
echo "pre-flight: every size and finish term exists\n\n";

foreach ( $config as $pid => $cfg ) {
	$product = wc_get_product( $pid );
	if ( ! $product ) {
		echo "  ! #$pid not found\n";
		continue;
	}

	printf( "=== #%d %s ===\n", $pid, html_entity_decode( get_the_title( $pid ) ) );

	// ── 1. Attach every term the matrix uses to the product. ────────────────
	foreach ( array( DT_SIZE_TAX => 1, DT_FINISH_TAX => 2 ) as $tax => $col ) {
		$want = array();
		foreach ( $cfg['matrix'] as $row ) {
			$term = get_term_by( 'slug', $row[ $col ], $tax );
			$want[ $term->term_id ] = $term->term_id;
		}
		$have = wp_get_object_terms( $pid, $tax, array( 'fields' => 'ids' ) );
		sort( $have );
		$want_sorted = array_values( $want );
		sort( $want_sorted );

		if ( $have === $want_sorted ) {
			printf( "  [ok]    %-24s %d terms already attached\n", $tax, count( $want_sorted ) );
		} elseif ( $apply ) {
			wp_set_object_terms( $pid, $want_sorted, $tax );
			printf( "  [write] %-24s %d -> %d terms\n", $tax, count( $have ), count( $want_sorted ) );
		} else {
			printf( "  [dry]   %-24s %d -> %d terms\n", $tax, count( $have ), count( $want_sorted ) );
		}
	}

	// ── 2. Both axes must be variation axes; leave other attributes alone. ──
	$attrs   = $product->get_attributes();
	$changed = false;
	foreach ( array( DT_SIZE_TAX => 1, DT_FINISH_TAX => 2 ) as $tax => $col ) {
		$ids = array();
		foreach ( $cfg['matrix'] as $row ) {
			$term                 = get_term_by( 'slug', $row[ $col ], $tax );
			$ids[ $term->term_id ] = $term->term_id;
		}
		$ids = array_values( $ids );
		sort( $ids );

		// Compare before touching, so a second run is genuinely a no-op rather
		// than a rewrite of identical values reported as a change.
		$existing = isset( $attrs[ $tax ] ) ? $attrs[ $tax ] : null;
		if ( $existing ) {
			$have = array_map( 'intval', $existing->get_options() );
			sort( $have );
			if ( $have === $ids && $existing->get_variation() && $existing->get_visible() ) {
				continue;
			}
		}

		$attr = $existing ? $existing : new WC_Product_Attribute();
		$attr->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
		$attr->set_name( $tax );
		$attr->set_options( $ids );
		$attr->set_visible( true );
		$attr->set_variation( true );
		$attrs[ $tax ] = $attr;
		$changed       = true;
	}
	if ( ! $changed ) {
		echo "  [ok]    parent attributes: both axes already variation axes\n";
	} elseif ( $apply ) {
		$product->set_attributes( $attrs );
		$product->save();
		echo "  [write] parent attributes: both axes set to variation\n";
	} else {
		echo "  [dry]   parent attributes: both axes set to variation\n";
	}

	// ── 3. The matrix itself. ───────────────────────────────────────────────
	$expected = array();
	foreach ( $cfg['matrix'] as $row ) {
		list( $sku, $size, $finish, $price ) = $row;
		$key                                 = strtoupper( $sku );
		$expected[ $key ]                    = true;

		$vid       = isset( $index[ $key ] ) ? $index[ $key ] : 0;
		$is_new    = false;
		$variation = $vid ? wc_get_product( $vid ) : null;

		if ( $variation && 'variation' !== $variation->get_type() ) {
			echo "  ! $sku is attached to a non-variation post ($vid) — skipped\n";
			continue;
		}
		if ( $variation && $variation->get_parent_id() !== $pid ) {
			echo "  ! $sku belongs to product {$variation->get_parent_id()}, not $pid — skipped\n";
			continue;
		}

		if ( ! $variation ) {
			$is_new = true;
			if ( ! $apply ) {
				printf( "  [dry]   create   %-16s %-14s %-26s £%s\n", $sku, $size, $finish, $price );
				continue;
			}
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $pid );
		}

		$want_attrs = array( DT_SIZE_TAX => $size, DT_FINISH_TAX => $finish );
		$same       = ! $is_new
			&& $variation->get_attributes() === $want_attrs
			&& (string) $variation->get_regular_price() === (string) $price
			&& strtoupper( (string) $variation->get_sku() ) === $key;

		if ( $same ) {
			printf( "  [ok]     %-16s %-14s %-26s £%s\n", $sku, $size, $finish, $price );
			continue;
		}

		if ( ! $apply ) {
			printf( "  [dry]   update   %-16s %-14s %-26s £%s\n", $sku, $size, $finish, $price );
			continue;
		}

		$variation->set_attributes( $want_attrs );
		$variation->set_sku( $sku );
		$variation->set_regular_price( $price );
		$variation->set_price( $price );
		$variation->set_status( 'publish' );
		$variation->save();
		$index[ $key ] = $variation->get_id();

		printf(
			"  [write] %-8s %-16s %-14s %-26s £%s\n",
			$is_new ? 'create' : 'update',
			$sku,
			$size,
			$finish,
			$price
		);
	}

	// ── 4. Anything left over is reported, never silently deleted. ──────────
	foreach ( $product->get_children() as $child_id ) {
		$child = wc_get_product( $child_id );
		if ( ! $child ) {
			continue;
		}
		if ( ! isset( $expected[ strtoupper( (string) $child->get_sku() ) ] ) ) {
			printf( "  ! leftover variation #%d sku=%s not in the matrix — left in place for review\n", $child_id, $child->get_sku() ?: '(none)' );
		}
	}

	// ── 5. Title, slug and description. ─────────────────────────────────────
	$post     = get_post( $pid );
	$new_content = $post->post_content;
	$new_excerpt = $post->post_excerpt;
	foreach ( $cfg['replace'] as $from => $to ) {
		$new_content = str_replace( $from, $to, $new_content );
		$new_excerpt = str_replace( $from, $to, $new_excerpt );
	}

	// A slug already held by another post makes wp_update_post silently append
	// "-2", which is how #6592 first landed on orajet-3164-4-year-digital-vinyl-2:
	// an abandoned draft duplicate (#6627) was squatting the clean slug. Say so
	// rather than accepting the suffix, because the suffixed URL is the one
	// customers and search engines would end up with.
	$squatter = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT ID, post_status, post_type FROM {$wpdb->posts}
			  WHERE post_name = %s AND ID <> %d LIMIT 1",
			$cfg['slug'],
			$pid
		)
	);
	if ( $squatter ) {
		printf(
			"  ! slug '%s' is held by #%d (%s, %s) — free it or this product keeps a -2 suffix\n",
			$cfg['slug'],
			$squatter->ID,
			$squatter->post_type,
			$squatter->post_status
		);
	}

	$needs = array();
	if ( html_entity_decode( $post->post_title ) !== $cfg['title'] ) {
		$needs['post_title'] = $cfg['title'];
	}
	if ( $post->post_name !== $cfg['slug'] && ! $squatter ) {
		$needs['post_name'] = $cfg['slug'];
	}
	if ( $new_content !== $post->post_content ) {
		$needs['post_content'] = $new_content;
	}
	if ( $new_excerpt !== $post->post_excerpt ) {
		$needs['post_excerpt'] = $new_excerpt;
	}

	if ( ! $needs ) {
		echo "  [ok]    title, slug and description already renamed\n";
	} elseif ( $apply ) {
		wp_update_post( array_merge( array( 'ID' => $pid ), $needs ) );
		printf( "  [write] renamed: %s\n", implode( ', ', array_keys( $needs ) ) );
		printf( "          title -> %s\n          slug  -> %s\n", $cfg['title'], $cfg['slug'] );
	} else {
		printf( "  [dry]   would rename: %s\n", implode( ', ', array_keys( $needs ) ) );
		printf( "          title -> %s\n          slug  -> %s\n", $cfg['title'], $cfg['slug'] );
	}

	// Drop the redirect bookkeeping wp_update_post just created. Unconditional on
	// apply rather than tied to $needs, so a replay onto an environment where an
	// earlier version of this script already renamed the product still clears it.
	if ( $apply ) {
		$stale = get_post_meta( $pid, '_wp_old_slug', false );
		if ( $stale ) {
			delete_post_meta( $pid, '_wp_old_slug' );
			printf( "  [write] removed %d redirect record(s): %s\n", count( $stale ), implode( ', ', $stale ) );
		}
	} elseif ( get_post_meta( $pid, '_wp_old_slug', false ) ) {
		printf( "  [dry]   would remove redirect record(s): %s\n", implode( ', ', get_post_meta( $pid, '_wp_old_slug', false ) ) );
	}

	if ( $apply ) {
		WC_Product_Variable::sync( $pid );
		wc_delete_product_transients( $pid );
	}
	echo "\n";
}

if ( $apply ) {
	wc_delete_product_transients();
	echo "done — variable price ranges resynced, product transients cleared\n";
} else {
	echo "dry run complete — nothing written\n";
}
