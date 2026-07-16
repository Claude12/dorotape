<?php
/**
 * Generic Sage-driven restructure — the 7901 model, catalogue-wide.
 *
 * For every variable product it tries to resolve each published variation to
 * a Sage code + kind:
 *   metre  — price equals the Sage sales price exactly (Sage sells cut rolls
 *            per metre), or the variation SKU is already an exact Sage code
 *   roll   — attribute text states a roll length L ("full roll ... x 50 mtr",
 *            "per 25m roll") and roll_price is within 0–30%% discount of
 *            Sage price × L for exactly one family code
 *   option — no metre/roll pattern; exact-price match to a distinct family
 *            code (physical size options like heat-press pillows)
 *
 * Family codes = live Sage codes matching the parent's base prefix, plus any
 * codes already on its variations. Parents classify as:
 *
 *   SIMPLE      one code total          -> simple product per metre (+ roll tier)
 *   VARIABLE    several codes, metre/roll -> one per-metre variation per code,
 *                                          roll rate becomes the qty tier
 *   RENAME      option pattern          -> rename variations in place; free the
 *                                          parent container SKU if it collides
 *   MANUAL      anything unresolved     -> listed, untouched
 *
 * Usage:
 *   php sage_restructure.php                        classify + dry-run plan
 *   php sage_restructure.php --parent=671 --apply   apply one parent
 *   php sage_restructure.php --apply                apply everything resolvable
 */

if ( 'cli' !== PHP_SAPI ) {
	die( "CLI only\n" );
}
$opts   = getopt( '', array( 'apply', 'parent:', 'limit:' ) );
$apply  = isset( $opts['apply'] );
$only   = isset( $opts['parent'] ) ? (int) $opts['parent'] : 0;
$limit  = isset( $opts['limit'] ) ? (int) $opts['limit'] : 0;

define( 'WP_IMPORTING', true );
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname( __DIR__, 4 ) . '/wp-load.php';

global $wpdb;

// ─── Sage list ────────────────────────────────────────────────────────────────

$sage = array();
$fh   = fopen( __DIR__ . '/sage_skus.csv', 'r' );
fgetcsv( $fh );
while ( ( $r = fgetcsv( $fh ) ) !== false ) {
	if ( count( $r ) < 4 || '' === trim( $r[0] ) ) {
		continue;
	}
	$desc = trim( $r[1] );
	$obs  = 100 === (int) $r[2]
		|| preg_match( '/DO NOT+M? USE|OBSOLETE|OBSELETE|DISCONTINUED|SEE CODE/i', $desc );
	if ( ! $obs ) {
		$sage[ trim( $r[0] ) ] = array( 'desc' => $desc, 'price' => round( (float) $r[3], 2 ) );
	}
}
fclose( $fh );

// Cross-references: Sage descs carry the old-website code in parentheses —
// "3801,WHITE NYLO FLEX PLUS (4801) 500MM" maps site base 4801 -> Sage 3801.
$xref = array();
foreach ( $sage as $code => $s ) {
	if ( preg_match( '/\(\s*(\d{3,4}[A-Z]{0,2})[\s)]/', $s['desc'], $m ) ) {
		$xref[ strtoupper( $m[1] ) ][ $code ] = $s;
	}
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

/** Human attribute text for a variation (term names + own title). */
function dt_variation_text( WC_Product_Variation $v ): string {
	$parts = array();
	foreach ( $v->get_attributes() as $tax => $slug ) {
		$term    = get_term_by( 'slug', $slug, $tax );
		$parts[] = $term ? $term->name : (string) $slug;
	}
	$parts[] = $v->get_name();
	return implode( ' | ', $parts );
}

/** Roll length in metres stated in text, widths stripped first. Null if none. */
function dt_roll_length( string $text ): ?float {
	$text = preg_replace( '/\d{2,4}\s*mm/i', '', $text );
	if ( preg_match( '/(\d{1,3}(?:\.\d)?)\s*(?:m\b|mtr|metre)/i', $text, $m ) ) {
		$len = (float) $m[1];
		return $len >= 2 ? $len : null;
	}
	return null;
}

/** Width token (mm) in text, null if none. */
function dt_width_token( string $text ): ?string {
	return preg_match( '/(\d{2,4})\s*mm/i', $text, $m ) ? $m[1] : null;
}

/**
 * cm dimension pair in text as a sorted "AxB" key ("40cm x 50cm" -> "40x50"),
 * order-insensitive so site and Sage listings match. Null when absent.
 */
function dt_cm_dims( string $text ): ?string {
	if ( preg_match( '/(\d{1,3})\s*cm\s*x\s*(\d{1,3})\s*cm/i', $text, $m ) ) {
		$d = array( (int) $m[1], (int) $m[2] );
		sort( $d );
		return implode( 'x', $d );
	}
	return null;
}

/**
 * Tier string for a metre/roll pair, or '' when the roll carries no discount.
 * Roll rate is 2dp — WooCommerce rounds unit prices to 2dp in cart totals, so
 * finer precision cannot survive. Legacy roll prices that don't divide exactly
 * shift by pennies (1216.80/50 -> £24.34/m -> £1217.00); Sage's per-metre
 * price is authoritative and unaffected.
 */
function dt_tier_string( float $metre, float $len, float $roll_price ): string {
	$rate = round( $roll_price / $len, 2 );
	if ( $rate >= $metre ) {
		return '';
	}
	return sprintf( '1-%d:%.2f;%d:%.2f', $len - 1, $metre, $len, $rate );
}

/** Family Sage codes for a parent: base-prefix matches + codes already on variations. */
function dt_family_codes( string $base, array $variation_skus, array $sage ): array {
	$codes = array();
	$base  = strtoupper( trim( $base ) );
	if ( strlen( $base ) >= 3 ) {
		foreach ( $sage as $code => $s ) {
			if ( 0 === strpos( $code, $base ) ) {
				$rest = substr( $code, strlen( $base ) );
				// size, optionally behind a 1-3 letter variant qualifier
				// (K/D/HT/AF/S...: 13101K625, 3101D610, 6501HT1200, P855AF610)
				if ( '' === $rest
					|| preg_match( '/^[A-Z]{0,3}(\d{1,4}|A[345][A-Z]{0,8})$/i', $rest )
					|| preg_match( '/^(H|AF|HT|D|K)$/i', $rest ) ) {
					$codes[ $code ] = $s;
				}
			}
		}
	}
	foreach ( $variation_skus as $vsku ) {
		if ( isset( $sage[ $vsku ] ) ) {
			$codes[ $vsku ] = $sage[ $vsku ];
		}
	}
	// old-website code cross-referenced in Sage descriptions: "(4801)" etc.
	global $xref;
	if ( isset( $xref[ $base ] ) ) {
		$codes += $xref[ $base ];
	}
	// PART codes are Sage's internal part-roll placeholders
	return array_filter( $codes, fn( $s, $c ) => ! preg_match( '/PART$/i', $c ), ARRAY_FILTER_USE_BOTH );
}

/** Option label for a code from its Sage desc: "610mm", "1220mm AirFlow", ... */
function dt_code_label( string $code, array $s, string $base ): string {
	$label = '';
	if ( preg_match( '/(\d{2,4})\s*MM/i', $s['desc'], $m ) ) {
		$label = $m[1] . 'mm';
	} elseif ( preg_match( '/(\d{3,4})M\b/i', $s['desc'], $m ) ) {
		$label = $m[1] . 'mm'; // Sage desc typo: "625M" for 625MM
	}
	foreach ( array( 'AIRFLOW' => 'AirFlow', 'OUTDOOR' => 'Outdoor', 'HIGH TACK' => 'High Tack' ) as $needle => $suffix ) {
		if ( false !== stripos( $s['desc'], $needle ) ) {
			$label = trim( $label . ' ' . $suffix );
		}
	}
	if ( 0 === strpos( strtoupper( $code ), strtoupper( $base ) . 'AF' ) && false === strpos( $label, 'AirFlow' ) ) {
		$label = trim( $label . ' AirFlow' );
	}
	if ( '' === $label ) {
		$rest  = substr( $code, strlen( $base ) );
		$label = '' !== $rest ? $rest : $code;
	}
	return $label;
}

// ─── Classify every variable parent ──────────────────────────────────────────

$parent_ids = $only
	? array( $only )
	: array_map( 'intval', $wpdb->get_col(
		"SELECT DISTINCT post_parent FROM {$wpdb->posts}
		 WHERE post_type = 'product_variation' AND post_status = 'publish'
		 ORDER BY post_parent"
	) );

$plans  = array( 'SIMPLE' => array(), 'VARIABLE' => array(), 'RENAME' => array(), 'MANUAL' => array(), 'OK' => array() );
$count  = 0;

foreach ( $parent_ids as $pid ) {
	$parent = wc_get_product( $pid );
	if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
		continue;
	}
	$vids = $parent->get_children();
	$vars = array_filter( array_map( 'wc_get_product', $vids ) );

	// already aligned? every variation SKU is a live Sage code at Sage price,
	// and no metre/roll duplication (each code used once)
	$skus    = array_map( fn( $v ) => (string) $v->get_sku(), $vars );
	$aligned = count( $skus ) === count( array_unique( $skus ) );
	foreach ( $vars as $v ) {
		$sku = (string) $v->get_sku();
		if ( ! isset( $sage[ $sku ] ) || round( (float) $v->get_price(), 2 ) !== $sage[ $sku ]['price'] ) {
			$aligned = false;
			break;
		}
	}
	if ( $aligned && $vars ) {
		$plans['OK'][ $pid ] = null;
		continue;
	}

	$base   = preg_replace( "/^Cover\s*Styl'?\s*/i", '', (string) $parent->get_sku() );
	$family = dt_family_codes( $base, $skus, $sage );
	if ( ! $family ) {
		$plans['MANUAL'][ $pid ] = array( 'why' => 'no family codes for base "' . $base . '"', 'name' => $parent->get_name() );
		continue;
	}

	$resolved   = array(); // code => ['metre' => price, 'roll_len' =>, 'roll_price' =>, 'label' =>]
	$renames    = array(); // vid => code   (option pattern)
	$unresolved = array();
	$has_roll   = false;
	$rolls      = array(); // second pass: rolls resolve after metre lines

	// pass 1: exact-code and exact-price (metre / option) variations
	foreach ( $vars as $v ) {
		$price = round( (float) $v->get_price(), 2 );
		$sku   = (string) $v->get_sku();
		$text  = dt_variation_text( $v );
		$width = dt_width_token( $text );
		$af    = false !== stripos( $text, 'airflow' );

		// candidate codes narrowed by width / airflow hints in the attr text
		$cands = array_filter( $family, function ( $s ) use ( $width, $af ) {
			if ( $width && dt_width_token( $s['desc'] ) && dt_width_token( $s['desc'] ) !== $width ) {
				return false;
			}
			return $af === ( false !== stripos( $s['desc'], 'AIRFLOW' ) );
		} );
		if ( ! $cands ) {
			$cands = $family;
		}

		// 1) exact Sage code already on the variation
		if ( isset( $sage[ $sku ] ) && $price === $sage[ $sku ]['price'] ) {
			$resolved[ $sku ]['metre'] = $price;
			$renames[ $v->get_id() ]   = $sku;
			continue;
		}
		// cm-dimension products (platens, pillows): dimensions identify the
		// code deterministically — price may be a stale website price, so
		// dimensions take priority and ambiguity is never price-guessed
		$dims = dt_cm_dims( $text );
		if ( $dims ) {
			$hits = array_keys( array_filter( $cands, fn( $s ) => dt_cm_dims( $s['desc'] ) === $dims ) );
			if ( count( $hits ) > 1 ) {
				// upper cover vs lower protector share dimensions
				$upper = (bool) preg_match( '/upper|cover\b/i', $text );
				$hits  = array_values( array_filter( $hits, fn( $c ) => $upper === (bool) preg_match( '/UPPER|COVER\b/i', $sage[ $c ]['desc'] ) ) );
			}
			if ( 1 === count( $hits ) ) {
				$resolved[ $hits[0] ]['metre'] = $sage[ $hits[0] ]['price'];
				$renames[ $v->get_id() ]       = $hits[0];
				continue;
			}
			$unresolved[] = $v->get_id() . ' ' . $sku . ' £' . $price . ' [' . $text . ']';
			continue;
		}
		// rolls wait for pass 2 so metre lines can disambiguate them
		if ( dt_roll_length( $text ) && $price > 0 ) {
			$rolls[] = array( 'v' => $v, 'price' => $price, 'text' => $text, 'cands' => $cands );
			continue;
		}
		// 2) exact price match to exactly one candidate code
		$hits = array_keys( array_filter( $cands, fn( $s ) => $s['price'] === $price ) );
		if ( 1 === count( $hits ) ) {
			$resolved[ $hits[0] ]['metre'] = $price;
			$renames[ $v->get_id() ]       = $hits[0];
			continue;
		}
		$unresolved[] = $v->get_id() . ' ' . $sku . ' £' . $price . ' [' . $text . ']';
	}

	// pass 2: rolls — price within 0–30% discount of Sage price × stated length;
	// on ties prefer the code whose metre line resolved in pass 1
	foreach ( $rolls as $roll ) {
		$len     = dt_roll_length( $roll['text'] );
		$in_band = function ( array $pool ) use ( $len, $roll ): array {
			$hits = array();
			foreach ( $pool as $code => $s ) {
				$d = $s['price'] * $len / $roll['price'];
				if ( $d >= 0.999 && $d <= 1.30 ) {
					$hits[] = $code;
				}
			}
			return $hits;
		};
		$hits = $in_band( $roll['cands'] );
		if ( ! $hits ) {
			// nominal vs actual width mismatch (site "500mm", Sage "480MM"):
			// retry without the width narrowing
			$hits = $in_band( $family );
		}
		if ( count( $hits ) > 1 ) {
			$with_metre = array_values( array_filter( $hits, fn( $c ) => isset( $resolved[ $c ]['metre'] ) ) );
			if ( 1 === count( $with_metre ) ) {
				$hits = $with_metre;
			}
		}
		if ( 1 === count( $hits ) ) {
			$resolved[ $hits[0] ]['roll_len']   = $len;
			$resolved[ $hits[0] ]['roll_price'] = $roll['price'];
			$has_roll                           = true;
			continue;
		}
		// not a metre/roll collapse — Sage may price this code per roll
		// outright ("...1372MM X 50M" £105.40): unique exact-price match
		$exact = array_keys( array_filter( $roll['cands'], fn( $s ) => $s['price'] === $roll['price'] ) );
		if ( 1 === count( $exact ) && ! isset( $resolved[ $exact[0] ] ) ) {
			$resolved[ $exact[0] ]['metre']       = $roll['price'];
			$renames[ $roll['v']->get_id() ]      = $exact[0];
			continue;
		}
		$v            = $roll['v'];
		$unresolved[] = $v->get_id() . ' ' . $v->get_sku() . ' £' . $roll['price'] . ' [' . $roll['text'] . ']';
	}

	if ( $unresolved || ! $resolved ) {
		$plans['MANUAL'][ $pid ] = array( 'why' => implode( ' ;; ', $unresolved ?: array( 'nothing resolved' ) ), 'name' => $parent->get_name() );
		continue;
	}
	// every code must be free: not on a live product outside this parent
	// (duplicate listings — e.g. SRL96 also sold as its own simple product)
	$family_vids = array_map( 'intval', $vids );
	$conflict    = null;
	foreach ( array_keys( $resolved ) as $code ) {
		$holder = $wpdb->get_var( $wpdb->prepare(
			"SELECT pm.post_id FROM {$wpdb->postmeta} pm
			 JOIN {$wpdb->posts} po ON po.ID = pm.post_id AND po.post_status = 'publish'
			 WHERE pm.meta_key = '_sku' AND pm.meta_value = %s LIMIT 1",
			$code
		) );
		if ( $holder && (int) $holder !== $pid && ! in_array( (int) $holder, $family_vids, true ) ) {
			$conflict = "code $code already on live product #$holder (duplicate listing)";
			break;
		}
	}
	if ( $conflict ) {
		$plans['MANUAL'][ $pid ] = array( 'why' => $conflict, 'name' => $parent->get_name() );
		continue;
	}
	// every resolved code needs its metre price from Sage (authoritative)
	foreach ( $resolved as $code => &$r ) {
		$r['metre'] = $sage[ $code ]['price'];
		$r['label'] = dt_code_label( $code, $sage[ $code ], strtoupper( $base ) );
	}
	unset( $r );

	if ( $has_roll ) {
		$kind = ( 1 === count( $resolved ) ) ? 'SIMPLE' : 'VARIABLE';
	} else {
		$kind = 'RENAME';
	}
	$plans[ $kind ][ $pid ] = array(
		'name'    => $parent->get_name(),
		'base'    => $base,
		'codes'   => $resolved,
		'renames' => $renames,
	);
	if ( $limit && ++$count >= $limit ) {
		break;
	}
}

printf(
	"\nClassified: OK %d | SIMPLE %d | VARIABLE %d | RENAME %d | MANUAL %d\n\n",
	count( $plans['OK'] ), count( $plans['SIMPLE'] ), count( $plans['VARIABLE'] ), count( $plans['RENAME'] ), count( $plans['MANUAL'] )
);

// ─── Dry-run plan output ──────────────────────────────────────────────────────

if ( ! $apply ) {
	foreach ( array( 'SIMPLE', 'VARIABLE', 'RENAME' ) as $kind ) {
		echo "── $kind " . str_repeat( '─', 50 ) . "\n";
		$i = 0;
		foreach ( $plans[ $kind ] as $pid => $p ) {
			printf( "#%d %s\n", $pid, $p['name'] );
			foreach ( $p['codes'] as $code => $r ) {
				$tier = '';
				if ( isset( $r['roll_len'] ) ) {
					$t    = dt_tier_string( $r['metre'], $r['roll_len'], $r['roll_price'] );
					$tier = $t ? " tiers $t" : ' (roll = metre rate, no tier)';
				}
				printf( "    %-14s %-18s £%.2f/m%s\n", $code, '[' . ( $r['label'] ?? '' ) . ']', $r['metre'], $tier );
			}
			if ( ++$i >= 8 && count( $plans[ $kind ] ) > 10 ) {
				echo '    ... ' . ( count( $plans[ $kind ] ) - $i ) . " more parents\n";
				break;
			}
		}
	}
	echo "── MANUAL " . str_repeat( '─', 50 ) . "\n";
	foreach ( array_slice( $plans['MANUAL'], 0, 25, true ) as $pid => $p ) {
		printf( "#%d %s | %s\n", $pid, $p['name'], mb_substr( $p['why'], 0, 140 ) );
	}
	if ( count( $plans['MANUAL'] ) > 25 ) {
		echo '... ' . ( count( $plans['MANUAL'] ) - 25 ) . " more\n";
	}
	echo "\nDry run. Pass --apply (optionally --parent=ID) to execute SIMPLE/VARIABLE/RENAME plans.\n";
	exit( 0 );
}

// ─── Apply ────────────────────────────────────────────────────────────────────

require_once ABSPATH . 'wp-admin/includes/post.php';

$log = fopen( __DIR__ . '/sage_restructure_log.csv', 'a' );
fputcsv( $log, array( 'timestamp', 'parent_id', 'action', 'detail' ) );

/** Free the SKUs of variations about to be trashed, then trash them. */
function dt_retire_variations( array $vids, $log, int $pid ): void {
	foreach ( $vids as $vid ) {
		$old = get_post_meta( $vid, '_sku', true );
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'retire-variation', "#$vid sku=$old" ) );
		update_post_meta( $vid, '_sku', '' );
		if ( function_exists( 'wc_update_product_lookup_tables_column' ) ) {
			global $wpdb;
			$wpdb->update( "{$wpdb->prefix}wc_product_meta_lookup", array( 'sku' => '' ), array( 'product_id' => $vid ) );
		}
		wp_trash_post( $vid );
	}
}

$width_tax = null;
function dt_option_attribute(): string {
	global $width_tax;
	if ( $width_tax ) {
		return $width_tax;
	}
	$slug     = 'width';
	$taxonomy = wc_attribute_taxonomy_name( $slug ); // pa_width — created by the 7901 pilot
	if ( ! taxonomy_exists( $taxonomy ) ) {
		if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
			$res = wc_create_attribute( array( 'name' => 'Width', 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order' ) );
			if ( is_wp_error( $res ) ) {
				die( 'attribute: ' . $res->get_error_message() . "\n" );
			}
		}
		register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false ) );
	}
	$width_tax = $taxonomy;
	return $taxonomy;
}

$done = array( 'SIMPLE' => 0, 'VARIABLE' => 0, 'RENAME' => 0 );

// SIMPLE: one code — parent becomes a simple per-metre product
foreach ( $plans['SIMPLE'] as $pid => $p ) {
	try {
	$code = array_key_first( $p['codes'] );
	$r    = $p['codes'][ $code ];
	$parent = wc_get_product( $pid );

	dt_retire_variations( $parent->get_children(), $log, $pid );

	// drop variation attributes + their term links
	foreach ( $parent->get_attributes() as $key => $attr ) {
		if ( $attr instanceof WC_Product_Attribute && $attr->get_variation() ) {
			wp_set_object_terms( $pid, array(), $key );
		}
	}
	wp_set_object_terms( $pid, 'simple', 'product_type' );
	clean_post_cache( $pid );
	wp_cache_flush();

	$simple = wc_get_product( $pid );
	if ( ! $simple->is_type( 'simple' ) ) {
		echo "  FAIL #$pid did not convert to simple\n";
		continue;
	}
	$attrs = array_filter(
		$simple->get_attributes(),
		fn( $a ) => $a instanceof WC_Product_Attribute && ! $a->get_variation()
	);
	$simple->set_attributes( $attrs );
	$simple->set_sku( $code );
	$simple->set_regular_price( (string) $r['metre'] );
	$simple->set_price( (string) $r['metre'] );
	$simple->set_manage_stock( false );
	$simple->set_stock_status( 'instock' );
	$simple->save();

	if ( isset( $r['roll_len'] ) ) {
		$t = dt_tier_string( $r['metre'], $r['roll_len'], $r['roll_price'] );
		if ( $t ) {
			update_post_meta( $pid, '_price_tiers', $t );
		} else {
			delete_post_meta( $pid, '_price_tiers' );
		}
	}
	update_post_meta( $pid, '_dt_price_unit', 'metre' );
	fputcsv( $log, array( gmdate( 'c' ), $pid, 'simple', "$code @{$r['metre']}/m" ) );
	$done['SIMPLE']++;
	if ( 0 === $done['SIMPLE'] % 25 ) {
		echo "  simple ...{$done['SIMPLE']}\n";
	}
	} catch ( Throwable $e ) {
		echo "  SIMPLE FAIL #$pid: " . $e->getMessage() . "\n";
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'FAIL', $e->getMessage() ) );
	}
}

// VARIABLE: several codes — per-metre variation per code on pa_width
foreach ( $plans['VARIABLE'] as $pid => $p ) {
	try {
	$parent = wc_get_product( $pid );
	$tax    = dt_option_attribute();

	// a container SKU that IS one of the codes blocks the new variation
	$parent_sku = (string) $parent->get_sku();
	if ( isset( $p['codes'][ $parent_sku ] ) ) {
		$parent->set_sku( $parent_sku . '-GROUP' );
		$parent->save();
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'vacate-container', "$parent_sku -> $parent_sku-GROUP" ) );
	}

	dt_retire_variations( $parent->get_children(), $log, $pid );

	$term_ids = array();
	$terms    = array();
	$order    = 0;
	// stable order: numeric width ascending, AirFlow after standard
	uasort( $p['codes'], function ( $a, $b ) {
		$aw = (int) $a['label'];
		$bw = (int) $b['label'];
		return $aw === $bw ? strcmp( $a['label'], $b['label'] ) : $aw <=> $bw;
	} );
	foreach ( $p['codes'] as $code => $r ) {
		$term = get_term_by( 'name', $r['label'], $tax );
		if ( ! $term ) {
			$res = wp_insert_term( $r['label'], $tax );
			if ( is_wp_error( $res ) ) {
				die( "term {$r['label']}: " . $res->get_error_message() . "\n" );
			}
			$term = get_term( $res['term_id'], $tax );
		}
		update_term_meta( $term->term_id, 'order', ++$order );
		update_term_meta( $term->term_id, 'order_' . $tax, $order );
		$term_ids[]       = (int) $term->term_id;
		$terms[ $code ]   = $term;
	}

	$attributes = array();
	foreach ( $parent->get_attributes() as $key => $attr ) {
		if ( $attr instanceof WC_Product_Attribute && ! $attr->get_variation() ) {
			$attributes[] = $attr;
		} else {
			wp_set_object_terms( $pid, array(), $key );
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

	foreach ( $p['codes'] as $code => $r ) {
		$var = new WC_Product_Variation();
		$var->set_parent_id( $pid );
		$var->set_sku( $code );
		$var->set_regular_price( (string) $r['metre'] );
		$var->set_price( (string) $r['metre'] );
		$var->set_manage_stock( false );
		$var->set_stock_status( 'instock' );
		$var->set_status( 'publish' );
		$var->set_attributes( array( $tax => $terms[ $code ]->slug ) );
		$vid = $var->save();
		if ( isset( $r['roll_len'] ) ) {
			$t = dt_tier_string( $r['metre'], $r['roll_len'], $r['roll_price'] );
			if ( $t ) {
				update_post_meta( $vid, '_price_tiers', $t );
			}
		}
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'variation', "#$vid $code [{$r['label']}] @{$r['metre']}/m" ) );
	}
	update_post_meta( $pid, '_dt_price_unit', 'metre' );
	WC_Product_Variable::sync( $pid );
	$done['VARIABLE']++;
	if ( 0 === $done['VARIABLE'] % 25 ) {
		echo "  variable ...{$done['VARIABLE']}\n";
	}
	} catch ( Throwable $e ) {
		echo "  VARIABLE FAIL #$pid: " . $e->getMessage() . "\n";
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'FAIL', $e->getMessage() ) );
	}
}

// RENAME: option products — rename variations in place, free colliding parent SKU
foreach ( $plans['RENAME'] as $pid => $p ) {
	try {
	$parent     = wc_get_product( $pid );
	$parent_sku = (string) $parent->get_sku();
	// container SKU colliding with a real Sage code must vacate it first
	if ( in_array( $parent_sku, array_values( $p['renames'] ), true ) ) {
		$parent->set_sku( $parent_sku . '-GROUP' );
		$parent->save();
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'vacate-container', "$parent_sku -> $parent_sku-GROUP" ) );
	}
	foreach ( $p['renames'] as $vid => $code ) {
		$v = wc_get_product( $vid );
		if ( ! $v || $v->get_sku() === $code ) {
			continue;
		}
		try {
			$old = $v->get_sku();
			$v->set_sku( $code );
			$v->save();
			fputcsv( $log, array( gmdate( 'c' ), $pid, 'rename', "#$vid $old -> $code" ) );
		} catch ( WC_Data_Exception $e ) {
			echo "  RENAME FAIL #$vid -> $code: " . $e->getMessage() . "\n";
		}
	}
	$done['RENAME']++;
	} catch ( Throwable $e ) {
		echo "  RENAME FAIL #$pid: " . $e->getMessage() . "\n";
		fputcsv( $log, array( gmdate( 'c' ), $pid, 'FAIL', $e->getMessage() ) );
	}
}

fclose( $log );
wc_delete_product_transients();
if ( function_exists( 'w3tc_flush_all' ) ) {
	w3tc_flush_all();
}
printf( "\nApplied: SIMPLE %d, VARIABLE %d, RENAME %d. Log: sage_restructure_log.csv\n", $done['SIMPLE'], $done['VARIABLE'], $done['RENAME'] );
