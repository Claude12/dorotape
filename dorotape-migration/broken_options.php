<?php
/**
 * Catalogue-wide check for variable products whose option dropdowns do not match
 * the variations behind them. READ-ONLY — makes no changes of any kind.
 *
 * Two distinct faults, both originally found by accident rather than by any harness:
 *
 *   A) DEAD OPTION — the dropdown offers a value no variation holds. Selecting it
 *      matches nothing, so add-to-cart is refused. Where EVERY value is dead the
 *      product cannot be bought at all. ASLAN SRL96 (#736) is the known case: it
 *      offers 1370mm / 1520mm while its variations are stored against a different
 *      attribute entirely, so neither can be added to the basket.
 *
 *   B) MIS-ORDERING — the attribute is declared and the dropdown has several
 *      values, but every variation leaves it blank. Blank means "any" to
 *      WooCommerce, so every choice resolves to the FIRST variation: the customer
 *      picks one thing and orders another, at that one's price. D-Jet 100 was this,
 *      on finish. Fixed during the Orajet rebuild, so 3164 / 3162 must come back
 *      clean — they are the control on this check.
 *
 * WHY THIS IS NOT DONE WITH find_matching_product_variation()
 *
 * The obvious approach — ask the matcher whether each option resolves — is wrong
 * twice over, and the first version of this script was wrong in exactly these ways:
 *
 *   1. The matcher requires values for EVERY variation attribute at once. Feeding
 *      it one attribute at a time makes every two-dropdown product look broken.
 *      Orajet 3164 and 3162 were both reported unbuyable on the first run purely
 *      because of this.
 *   2. Testing whole combinations instead does not work either, because a ragged
 *      matrix is legitimate: Orajet 3162 deliberately has 7 variations, not 9,
 *      since matt removable exists only at 1370mm. A combination that matches
 *      nothing is normal and WooCommerce greys it out in the dropdown.
 *
 * So the question is asked per option value instead: does ANY variation hold this
 * value for this attribute? That is what decides whether a customer who selects it
 * can get anywhere, and it is unaffected by both problems above. A blank on any
 * variation acts as a wildcard for that attribute, and is honoured as such.
 *
 * Usage: php broken_options.php
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

global $wpdb;

$ids = get_posts(
	array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => -1,
		'fields'      => 'ids',
		'tax_query'   => array(
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'variable',
			),
		),
	)
);

printf( "Checking %d published variable products…\n\n", count( $ids ) );

$unbuyable = array();
$partial   = array();
$misorder  = array();
$checked   = 0;

foreach ( $ids as $pid ) {
	$product = wc_get_product( $pid );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		continue;
	}

	$declared = array();
	foreach ( $product->get_attributes() as $tax => $attr ) {
		if ( $attr->get_variation() ) {
			$declared[ $tax ] = $attr->get_slugs();
		}
	}
	$children = $product->get_children();
	if ( ! $declared || ! $children ) {
		continue;
	}
	++$checked;

	foreach ( $declared as $tax => $offered ) {
		$label     = wc_attribute_label( $tax, $product );
		$held      = array();
		$wildcards = 0;
		$absent    = 0;
		foreach ( $children as $cid ) {
			// get_post_meta() flattens two different states to '': a row that
			// exists and is empty, and no row at all. WooCommerce does not treat
			// them alike — its matcher looks for the value OR '', so an empty row
			// is a wildcard that matches every choice, while a missing row matches
			// nothing and refuses the add-to-cart. That is the difference between
			// a customer ordering the wrong item and not being able to order at
			// all, so the raw row has to be read.
			$row = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
					$cid,
					'attribute_' . $tax
				)
			);
			if ( null === $row ) {
				++$absent;
			} elseif ( '' === (string) $row ) {
				++$wildcards;
			} else {
				$held[ (string) $row ] = true;
			}
		}

		// A missing row and an empty row end up behaving the SAME on the customer
		// path, even though they differ in the database. get_available_variations()
		// fills every declared attribute in, so a variation with no row at all is
		// published to the page as '', and add-to-cart-variation.js reads '' as
		// "matches anything". The variation_id it then submits is accepted. Verified
		// over HTTP by driving that exact path: SRL96 offers 1370mm and 1520mm and
		// BOTH add SRL96-sz01 at GBP142.40.
		//
		// (Posting without a resolved variation_id always fails with "Please choose
		// product options", for every variable product including healthy ones, so it
		// proves nothing — the control has to be run to catch that.)
		$wildcards += $absent;

		// FAULT B — nothing distinguishes the variations, so every choice lands on
		// the first one. Only a fault when there is more than one thing to choose.
		if ( ! $held && $wildcards && count( $offered ) > 1 ) {
			$first      = wc_get_product( $children[0] );
			$misorder[] = array(
				'id'    => $pid,
				'title' => html_entity_decode( $product->get_name() ),
				'tax'   => $label,
				'opts'  => count( $offered ),
				'lands' => $first ? ( $first->get_sku() ?: '#' . $children[0] ) : '?',
				'price' => $first ? $first->get_regular_price() : '',
				'slug'  => get_post_field( 'post_name', $pid ),
			);
			continue;
		}

		// A blank on any variation is a genuine wildcard: every offered value
		// reaches it, so nothing here is a dead end.
		if ( $wildcards ) {
			continue;
		}

		$dead = array_values( array_diff( $offered, array_keys( $held ) ) );
		if ( ! $dead ) {
			continue;
		}

		$row = array(
			'id'    => $pid,
			'title' => html_entity_decode( $product->get_name() ),
			'tax'   => $label,
			'dead'  => $dead,
			'opts'  => count( $offered ),
			'vars'  => count( $children ),
			'slug'  => get_post_field( 'post_name', $pid ),
		);
		if ( count( $dead ) === count( $offered ) ) {
			$unbuyable[] = $row;
		} else {
			$partial[] = $row;
		}
	}
}

echo str_repeat( '=', 78 ), "\n";
printf( "checked: %d variable products with at least one option dropdown\n\n", $checked );

printf( "A1. CANNOT BE ORDERED — every value in the dropdown is dead : %d\n", count( $unbuyable ) );
foreach ( $unbuyable as $r ) {
	printf( "   #%-6d %-44s '%s' — %d values, %d variations (%s)\n", $r['id'], substr( $r['title'], 0, 44 ), $r['tax'], $r['opts'], $r['vars'], $r['why'] ?? 'no variation holds any offered value' );
	printf( "           /product/%s/\n", $r['slug'] );
}

printf( "\nA2. SOME VALUES DEAD — the rest of the product still works  : %d\n", count( $partial ) );
foreach ( $partial as $r ) {
	printf( "   #%-6d %-44s '%s': %d of %d dead\n", $r['id'], substr( $r['title'], 0, 44 ), $r['tax'], count( $r['dead'] ), $r['opts'] );
	printf( "           %s\n", implode( ', ', array_slice( $r['dead'], 0, 3 ) ) );
}

printf( "\nB. EVERY CHOICE LANDS ON THE SAME VARIATION                   : %d\n", count( $misorder ) );
echo "   NOT automatically a fault — needs a judgement per product. The chosen value\n";
echo "   IS still recorded against the order line, so where every value carries the\n";
echo "   same price and the same code this is a legitimate way to capture a choice\n";
echo "   that does not change what is picked (verified on the Smart Knives: blade\n";
echo "   type reaches the order, and every blade is the same price). It IS a fault\n";
echo "   where the values ought to differ in price or code, or where it leaves other\n";
echo "   variations unreachable — SRL96 has four sizes at four prices and only one\n";
echo "   of them can be bought.\n";
foreach ( $misorder as $r ) {
	printf( "   #%-6d %-44s '%s': %d choices all order %s at £%s\n", $r['id'], substr( $r['title'], 0, 44 ), $r['tax'], $r['opts'], $r['lands'], $r['price'] );
	printf( "           /product/%s/\n", $r['slug'] );
}

echo "\n", str_repeat( '=', 78 ), "\n";
$total = count( $unbuyable ) + count( $partial ) + count( $misorder );
printf( "%s\n", $total ? "TOTAL FINDINGS: $total" : 'CLEAN — no mismatched option dropdowns found.' );
