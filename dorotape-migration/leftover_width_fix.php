<?php
/**
 * The eleven products left on the old part-roll / full-roll option shape.
 *
 * Michael reported Ri-Mark Event 101 Gloss Black (#1026) on 5 August 2026:
 * "this product doesn't match the rest of the colours in the range". He is
 * right, and it is not the only one. A catalogue sweep for variable products
 * still varying on pa_size-format with the old
 *
 *     "Size / Format — part roll - price per metre"
 *     "or 1220mm wide here — full 50 metre roll"
 *
 * labels returns exactly the eleven parents configured below. Every other
 * colour in each of these four ranges is already width-first on pa_width:
 * one variation per width, priced per metre, with the full-roll rate carried
 * as a quantity tier at the roll length. These eleven were skipped because
 * sage_restructure.php could not resolve one of their widths against the
 * Sage export and so classified them MANUAL rather than guess.
 *
 * WHERE THE FIGURES COME FROM
 *
 * Not from the current WP rows. Each width takes:
 *   sku    the Sage stock code Michael himself nominated in column G,
 *          "SKU / Resolution", of "Dorotape New Website vs Sage Review
 *          (reviewed).xlsx" — e.g. row 445 answers Ri-Mark Event 101 with
 *          "L1011220 / L101610". F-Sign 836 is the one product he left blank;
 *          see its note.
 *   price  see the per-entry note. Where Sage and the site disagree, 'sage'
 *          records the Sage figure and 'why' says which was taken and on what
 *          evidence. No price moves silently.
 *   tier   the per-metre rate at the roll break, reconstructed the same way
 *          the converted siblings were: roll price / roll length, 2dp.
 *
 * Roll length is 50m for the Ri-Mark ranges and 25m for F-Sign, matching each
 * range's existing tier strings.
 *
 * Usage:
 *   php leftover_width_fix.php            plan only, writes nothing
 *   php leftover_width_fix.php --apply
 *   php leftover_width_fix.php --apply --id=1026
 *
 * @package dorotape
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}

$opts  = getopt( '', array( 'apply', 'id:' ) );
$apply = isset( $opts['apply'] );
$only  = isset( $opts['id'] ) ? (int) $opts['id'] : 0;

define( 'WP_IMPORTING', true );
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname( __DIR__, 4 ) . '/wp-load.php';

/**
 * One width row.
 *
 * @param string $sku   Sage stock code.
 * @param float  $price Per-metre rate to publish.
 * @param float  $tier  Per-metre rate once the roll break is reached.
 * @param float  $sage  Sage sales price for $sku.
 * @param string $why   Required whenever $price and $sage differ.
 * @return array
 */
function lwf_width( string $sku, float $price, float $tier, float $sage, string $why = '' ): array {
	if ( abs( $price - $sage ) > 0.005 && '' === $why ) {
		die( "$sku: price {$price} differs from Sage {$sage} with no reason given\n" );
	}
	return compact( 'sku', 'price', 'tier', 'sage', 'why' );
}

/*
 * Ri-Mark Event Gloss, base colours (White, Black, Transparent).
 *
 * These three sit in a cheaper band than the coloured films: Sage prices the
 * 1220mm at 2.940 against 3.180 for the colours, and the site already sells
 * them at 2.94 / 1.47. The 1220mm rate needs no decision — site and Sage agree.
 *
 * The 610mm does. The site sells 1.47, Sage carries 1.410. Across all ~90
 * Ri-Mark Event codes in the Sage export, the 610mm price is exactly half the
 * 1220mm price in every single case except L100, L101 and L199 — these three.
 * The site's 1.47 is exactly half of 2.94 and so holds the rule the whole
 * range follows; Sage's 1.410 is a three-code outlier. The site figure is
 * taken and the outlier flagged. Taking Sage instead would cut the live 610mm
 * price by 4% on three products on the strength of a value that disagrees
 * with the other 87.
 */
$base_gloss = array();
foreach ( array(
	1025 => array( 'L100', 'Ri-Mark Event 100 Gloss White' ),
	1026 => array( 'L101', 'Ri-Mark Event 101 Gloss Black' ),
	1068 => array( 'L199', 'Ri-Mark Event 199 Gloss Transparent' ),
) as $id => list( $code, $title ) ) {
	$base_gloss[] = array(
		'id'       => $id,
		'expect'   => $title,
		'unit'     => 'metre',
		'roll_len' => 50,
		'widths'   => array(
			'610mm'  => lwf_width(
				"{$code}610",
				1.47,
				1.30,
				1.410,
				'Site price held. 1.47 is exactly half the 1220mm rate, which is how every other Ri-Mark Event colour is priced in both WP and Sage; Sage 1.410 here is one of only three codes in the range that break that rule. Flagged for Michael.'
			),
			'1220mm' => lwf_width( "{$code}1220", 2.94, 2.60, 2.940 ),
		),
		'note'     => 'Tiers from the retired 50m roll lines: 65.00/50 = 1.30, 130.00/50 = 2.60.',
	);
}

/*
 * Ri-Mark Event Gloss, standard colours.
 *
 * Nothing to decide: the site already carries 1.59 / 3.18 and rolls of
 * 70.00 / 140.00, Sage agrees to the penny, and the resulting tiers are
 * character for character what the forty-odd converted colours in the range
 * already store. Shape change only, no price movement.
 */
$std_gloss = array();
foreach ( array(
	1045 => array( 'L145', 'Ri-Mark Event 145 Gloss Brown' ),
	1047 => array( 'L151', 'Ri-Mark Event 151 Gloss Perfect Purple' ),
) as $id => list( $code, $title ) ) {
	$std_gloss[] = array(
		'id'       => $id,
		'expect'   => $title,
		'unit'     => 'metre',
		'roll_len' => 50,
		'widths'   => array(
			'610mm'  => lwf_width( "{$code}610", 1.59, 1.40, 1.590 ),
			'1220mm' => lwf_width( "{$code}1220", 3.18, 2.80, 3.180 ),
		),
		'note'     => 'Identical to Ri-Mark Event 103 (#1027) and every other converted colour in the range.',
	);
}

/*
 * Ri-Mark Event Matt.
 *
 * 304 needs no decision — site and Sage both say 1.75 / 3.50.
 *
 * 366 is the same shape but Sage carries M366610 at 0.000, an unpriced code
 * rather than a cheaper one: every other Matt 610mm code in the export is
 * 1.750, and all thirty-odd converted Matt colours publish 1.75 with a 1.54
 * tier. The site already sells 366 at 1.75. Sage's zero is a gap, so the
 * range rate stands and nothing about this product's price changes.
 */
$matt = array(
	array(
		'id'       => 1079,
		'expect'   => 'Ri-Mark Event 304 Matt Grey',
		'unit'     => 'metre',
		'roll_len' => 50,
		'widths'   => array(
			'610mm'  => lwf_width( 'M304610', 1.75, 1.54, 1.750 ),
			'1220mm' => lwf_width( 'M3041220', 3.50, 3.08, 3.500 ),
		),
		'note'     => 'Tiers from the retired roll lines: 77.00/50 = 1.54, 154.00/50 = 3.08.',
	),
	array(
		'id'       => 1108,
		'expect'   => 'Ri-Mark Event 366 Matt Vivid Blue',
		'unit'     => 'metre',
		'roll_len' => 50,
		'widths'   => array(
			'610mm'  => lwf_width(
				'M366610',
				1.75,
				1.54,
				0.000,
				'Sage carries M366610 unpriced at 0.000. Every other Matt 610mm code is 1.750 and the site already sells this one at 1.75, so the range rate stands. Sage gap flagged for Michael.'
			),
			'1220mm' => lwf_width( 'M3661220', 3.50, 3.08, 3.500 ),
		),
		'note'     => 'Same figures as Matt 304 above; the two share a price band.',
	),
);

/*
 * Ri-Mark Optima metallics (Gold, Copper, Silver).
 *
 * Sage prices these above the standard Optima colours: 2.750 / 5.500 against
 * 2.560 / 5.120. That price band is why round3_spec.php built 402, 412, 420
 * and 485 but not these three — Michael's round-3 sheet listed only the
 * standard colours.
 *
 * The site currently sells 2.64 / 5.50, and here the Sage figure is taken
 * rather than the site's. The precedent is direct: Optima 402 (#1377) and 412
 * (#1384) carried exactly these same site prices — 2.64 / 5.50, rolls
 * 119.00 / 248.00 — before round 3 rebuilt them at the Sage rate. Sage is also
 * self-consistent here (2.750 is exactly half of 5.500, the rule the whole
 * Optima range follows) whereas the site's 2.64 is not. So 630mm moves from
 * 2.64 to 2.75, a 4.2% rise, matching how the other forty-four colours in the
 * range were already handled. Flagged for Michael.
 *
 * Tiers use the range's roll discount of 0.9023 (2.31/2.56 on the standard
 * colours): 5.50 -> 4.96 and 2.75 -> 2.48. The 1260mm figure corroborates
 * independently — the retired 248.00 roll over 50m is 4.96 exactly. The
 * retired 630mm roll of 119.00 implies 2.38, but that is a 13.5% discount
 * against 9.8% on the other width of the same product, and 2.48 keeps 630mm
 * at half of 1260mm as everywhere else in the range.
 */
$optima = array();
foreach ( array(
	1413 => array( 'SC471', 'Ri-Mark Optima 471 Gold' ),
	1415 => array( 'SC474', 'Ri-Mark Optima 474 Copper' ),
	1420 => array( 'SC481', 'Ri-Mark Optima 481 Silver' ),
) as $id => list( $code, $title ) ) {
	$optima[] = array(
		'id'       => $id,
		'expect'   => $title,
		'unit'     => 'metre',
		'roll_len' => 50,
		'widths'   => array(
			'630mm'  => lwf_width(
				"{$code}630",
				2.75,
				2.48,
				2.750,
				''
			),
			'1260mm' => lwf_width( "{$code}1260", 5.50, 4.96, 5.500 ),
		),
		'note'     => 'Sage rate taken over the site\'s 2.64, as on Optima 402/412 in round 3. Live 630mm price rises 4.2%.',
	);
}

/*
 * F-Sign Platinum 836 Permanent Blue.
 *
 * The only entry here Michael did not answer — rows 249-255 of the reviewed
 * sheet leave column G blank for this product. It is included anyway because
 * nothing about it is ambiguous:
 *
 *   - Sage carries P836610 at 3.010 and P8361220 at 6.020.
 *   - All nine other colours in the range publish exactly 3.01 / 6.02 with
 *     tiers 1-24:3.01;25:2.71 and 1-24:6.02;25:5.42.
 *   - This product's own retired roll lines were 67.75 and 135.50 over 25m,
 *     which come to 2.71 and 5.42 — the sibling tiers to the penny.
 *
 * Three independent sources agree, so no price is being invented. The 1220mm
 * variation already exists at 6.02; the 610mm was lost and is restored.
 *
 * The retired AirFlow lines are not rebuilt. Sage has no P836AF codes, unlike
 * 833/834/835/839 which do, so there is nothing to sell them against — and
 * 830, 832, 837 and 838 have no AirFlow option either. Flagged for Michael in
 * case 836 is meant to.
 */
$fsign = array(
	array(
		'id'       => 1251,
		'expect'   => 'F-Sign Platinum 836 Permanent Blue',
		'unit'     => 'metre',
		'roll_len' => 25,
		'widths'   => array(
			'610mm'  => lwf_width( 'P836610', 3.01, 2.71, 3.010 ),
			'1220mm' => lwf_width( 'P8361220', 6.02, 5.42, 6.020 ),
		),
		'note'     => 'Not answered in the reviewed sheet; built from Sage, the nine sibling colours and its own retired roll prices, which all agree.',
	),
);

/*
 * Image Flex Pro Nylon 4035.
 *
 * Found on the second pass, after the eleven above were applied. It is the same
 * complaint in a different range: a catalogue-wide count of variation attribute
 * keys shows 294 products varying on pa_width and this one still on
 * pa_size-width, offering "500mm width / 750mm width" where its three direct
 * siblings — Image Flex Turbo Print Sub Block 4010 (#1008), Pro Soft White 4030
 * (#1009) and Turbo Print 4036 (#1010) — offer "500mm / 750mm" on pa_width. It
 * had also never been given a pricing unit, so its listing reads "From £8.57"
 * where the siblings read "From £X per metre".
 *
 * No price decision to make. Sage carries IMAPN500 at 8.570 and IMAPN750 at
 * 12.860, the site already publishes exactly those, and the tiers it already
 * stores (7.71 and 11.57) are the range's 10% roll discount to the penny, the
 * same one the siblings use. Shape and unit only.
 *
 * The 1500mm code IMAPN1500 is not built: Sage carries it at 0.000, as it does
 * the siblings' wide codes IMAPSB4010 and IMAPSW4030, and none of those three
 * products offer that width either.
 */
$image_flex = array(
	array(
		'id'       => 1007,
		'expect'   => 'Image Flex Pro Nylon 4035',
		'unit'     => 'metre',
		'roll_len' => 25,
		'widths'   => array(
			'500mm' => lwf_width( 'IMAPN500', 8.57, 7.71, 8.570 ),
			'750mm' => lwf_width( 'IMAPN750', 12.86, 11.57, 12.860 ),
		),
		'note'     => 'Moves pa_size-width to pa_width and sets the missing metre unit. Prices unchanged.',
	),
);

$configs = array_merge( $base_gloss, $std_gloss, $matt, $optima, $fsign, $image_flex );

// ─── Sage export, for verifying every code exists and is priced as recorded ──

$sage = array();
$fh   = fopen( __DIR__ . '/sage_skus.csv', 'r' );
fgetcsv( $fh );
while ( ( $r = fgetcsv( $fh ) ) !== false ) {
	if ( count( $r ) < 4 || '' === trim( $r[0] ) ) {
		continue;
	}
	$sage[ trim( $r[0] ) ] = round( (float) $r[3], 3 );
}
fclose( $fh );

/** pa_width, created by the earlier restructures. */
function lwf_width_taxonomy(): string {
	static $tax = null;
	if ( $tax ) {
		return $tax;
	}
	$taxonomy = wc_attribute_taxonomy_name( 'width' );
	if ( ! taxonomy_exists( $taxonomy ) ) {
		if ( ! wc_attribute_taxonomy_id_by_name( 'width' ) ) {
			die( "pa_width does not exist — run the earlier width restructure first\n" );
		}
		register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false ) );
	}
	$tax = $taxonomy;
	return $tax;
}

/**
 * Free a retired variation's SKU before trashing it, so the new variation can
 * claim the same code. Mirrors dt_retire_variations() in sage_restructure.php.
 */
function lwf_retire( array $vids, $log, int $pid ): void {
	global $wpdb;
	foreach ( $vids as $vid ) {
		$old = get_post_meta( $vid, '_sku', true );
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'retire-variation', "#$vid sku=$old" ) );
		update_post_meta( $vid, '_sku', '' );
		$wpdb->update( "{$wpdb->prefix}wc_product_meta_lookup", array( 'sku' => '' ), array( 'product_id' => $vid ) );
		wp_trash_post( $vid );
	}
}

// ─── Verify before writing ───────────────────────────────────────────────────

$problems = array();
$flags    = array();
$moves    = array();

foreach ( $configs as $cfg ) {
	$id      = $cfg['id'];
	$product = wc_get_product( $id );

	if ( ! $product ) {
		$problems[] = "#$id does not exist";
		continue;
	}
	if ( $product->get_name() !== $cfg['expect'] ) {
		$problems[] = sprintf( '#%d is "%s", expected "%s"', $id, $product->get_name(), $cfg['expect'] );
		continue;
	}
	foreach ( $cfg['widths'] as $label => $w ) {
		if ( ! isset( $sage[ $w['sku'] ] ) ) {
			$problems[] = "#$id $label: {$w['sku']} is not in the Sage export";
			continue;
		}
		if ( abs( $sage[ $w['sku'] ] - $w['sage'] ) > 0.005 ) {
			$problems[] = sprintf(
				'#%d %s: %s is %.3f in the Sage export, this file records %.3f',
				$id,
				$label,
				$w['sku'],
				$sage[ $w['sku'] ],
				$w['sage']
			);
		}
		if ( $w['tier'] >= $w['price'] ) {
			$problems[] = "#$id $label: tier {$w['tier']} is not below the base rate {$w['price']}";
		}
		if ( '' !== $w['why'] ) {
			$flags[] = sprintf(
				'#%d %-38s %-8s publishing %.2f, Sage %.3f — %s',
				$id,
				$cfg['expect'],
				$label,
				$w['price'],
				$w['sage'],
				$w['why']
			);
		}
		// Whether or not Sage agrees, say so when the shopper-facing rate moves.
		// Read from the live rows rather than trusting the notes above.
		foreach ( $product->get_children() as $vid ) {
			if ( get_post_meta( $vid, '_sku', true ) !== $w['sku'] || 'publish' !== get_post_status( $vid ) ) {
				continue;
			}
			$live = (float) get_post_meta( $vid, '_regular_price', true );
			if ( abs( $live - $w['price'] ) > 0.005 ) {
				$moves[] = sprintf(
					'#%d %-38s %-8s £%.2f -> £%.2f per metre (%+.1f%%)',
					$id,
					$cfg['expect'],
					$label,
					$live,
					$w['price'],
					( ( $w['price'] - $live ) / $live ) * 100
				);
			}
		}
		// A code already live on another product would collide on save.
		$owner = wc_get_product_id_by_sku( $w['sku'] );
		if ( $owner && wp_get_post_parent_id( $owner ) !== $id && $owner !== $id ) {
			$problems[] = "#$id $label: {$w['sku']} is already on #$owner";
		}
	}
}

if ( $problems ) {
	echo "Refusing to run:\n";
	foreach ( $problems as $p ) {
		echo "  - $p\n";
	}
	exit( 1 );
}

// ─── Plan / apply ────────────────────────────────────────────────────────────

$log = $apply
	? fopen( __DIR__ . '/leftover_width_fix_log.csv', 'a' )
	: fopen( 'php://memory', 'w' );

$tax   = lwf_width_taxonomy();
$count = 0;

foreach ( $configs as $cfg ) {
	$id = $cfg['id'];
	if ( $only && $only !== $id ) {
		continue;
	}
	$parent = wc_get_product( $id );

	printf( "#%d %s\n", $id, $cfg['expect'] );
	foreach ( $parent->get_children() as $vid ) {
		$v = wc_get_product( $vid );
		if ( $v && 'publish' === get_post_status( $vid ) ) {
			printf( "    retire  %-58s £%s\n", $v->get_name(), $v->get_regular_price() );
		}
	}
	foreach ( $cfg['widths'] as $label => $w ) {
		printf(
			"    build   %-10s %-12s £%.2f/m   tiers 1-%d:%.2f;%d:%.2f\n",
			$label,
			$w['sku'],
			$w['price'],
			$cfg['roll_len'] - 1,
			$w['price'],
			$cfg['roll_len'],
			$w['tier']
		);
	}
	echo "    note    {$cfg['note']}\n";

	if ( ! $apply ) {
		++$count;
		continue;
	}

	// A parent SKU equal to one of the width codes would block the variation.
	$parent_sku = (string) $parent->get_sku();
	if ( isset( $cfg['widths'][ $parent_sku ] ) ) {
		$parent->set_sku( $parent_sku . '-GROUP' );
		$parent->save();
		fputcsv( $log, array( gmdate( 'c' ), $id, 'vacate-container', "$parent_sku -> $parent_sku-GROUP" ) );
	}

	lwf_retire( $parent->get_children(), $log, $id );

	// Width terms, ordered narrow to wide.
	$term_ids = array();
	$terms    = array();
	$order    = 0;
	foreach ( $cfg['widths'] as $label => $w ) {
		$term = get_term_by( 'name', $label, $tax );
		if ( ! $term ) {
			$res = wp_insert_term( $label, $tax );
			if ( is_wp_error( $res ) ) {
				die( "term $label: " . $res->get_error_message() . "\n" );
			}
			$term = get_term( $res['term_id'], $tax );
		}
		update_term_meta( $term->term_id, 'order', ++$order );
		update_term_meta( $term->term_id, 'order_' . $tax, $order );
		$term_ids[]      = (int) $term->term_id;
		$terms[ $label ] = $term;
	}

	// Keep the parent's descriptive attributes (colour family, finish...),
	// drop whatever it used to vary on.
	$attributes = array();
	foreach ( $parent->get_attributes() as $key => $attr ) {
		if ( $attr instanceof WC_Product_Attribute && ! $attr->get_variation() ) {
			$attributes[] = $attr;
		} else {
			wp_set_object_terms( $id, array(), $key );
		}
	}
	$wa = new WC_Product_Attribute();
	$wa->set_id( wc_attribute_taxonomy_id_by_name( 'width' ) );
	$wa->set_name( $tax );
	$wa->set_options( $term_ids );
	$wa->set_position( 0 );
	$wa->set_visible( true );
	$wa->set_variation( true );
	array_unshift( $attributes, $wa );
	$parent->set_attributes( $attributes );
	$parent->save();

	foreach ( $cfg['widths'] as $label => $w ) {
		$var = new WC_Product_Variation();
		$var->set_parent_id( $id );
		$var->set_sku( $w['sku'] );
		$var->set_regular_price( (string) $w['price'] );
		$var->set_price( (string) $w['price'] );
		$var->set_manage_stock( false );
		$var->set_stock_status( 'instock' );
		$var->set_status( 'publish' );
		$var->set_attributes( array( $tax => $terms[ $label ]->slug ) );
		$vid = $var->save();

		update_post_meta(
			$vid,
			'_price_tiers',
			sprintf(
				'1-%d:%.2f;%d:%.2f',
				$cfg['roll_len'] - 1,
				$w['price'],
				$cfg['roll_len'],
				$w['tier']
			)
		);
		fputcsv( $log, array( gmdate( 'c' ), $id, 'variation', "#$vid {$w['sku']} [$label] @{$w['price']}/m" ) );
	}

	update_post_meta( $id, '_dt_price_unit', $cfg['unit'] );
	WC_Product_Variable::sync( $id );
	++$count;
}

fclose( $log );

if ( $flags ) {
	echo "\nFor Michael — every point where this run did not simply follow Sage:\n";
	foreach ( $flags as $f ) {
		echo "  $f\n";
	}
}

if ( $moves ) {
	echo "\nLive per-metre prices this run changes — sign these off before it goes up:\n";
	foreach ( $moves as $m ) {
		echo "  $m\n";
	}
} else {
	echo "\nNo live per-metre price changes.\n";
}

echo "\n" . ( $apply ? "Applied to $count products.\n" : "Plan only — $count products. Pass --apply to write.\n" );
