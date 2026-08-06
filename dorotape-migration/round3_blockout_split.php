<?php
/**
 * Split ASLAN Blockout Film into W15 and W16, by building the missing W16 half.
 *
 * Michael's answer on the Blockout Film row was explicit:
 *
 *   "Needs to be set up as two products W15 & W16, each with 1250mm & 625mm
 *    width options (SKU's W151250, W15625, W161250, W16625) sold by the metre"
 *
 * round3_build.php did the W15 half, because a product already existed to
 * rebuild. There is no W16 product on the site at all, so it needs creating, and
 * creating a product is a different job from rebuilding one. Hence a separate
 * script rather than another spec entry.
 *
 * The W16 product is built as a copy of the finished W15 one, so it inherits the
 * category, the image, the attributes, the upsells and the Sage plugin's own
 * fields rather than having them typed in again here. Only what genuinely
 * differs is overridden: the title, the two stock codes, and W15 -> W16 in the
 * wording.
 *
 * Prices come from sage_skus.csv, never from this file. The discount shape is
 * copied from the matching W15 width, and the script refuses to run if Sage
 * prices W16 differently from W15, because then copying that shape would be a
 * guess rather than a mirror.
 *
 * Two things it deliberately does not do:
 *
 *   - It reuses the W15 photograph and the W15 datasheet, because no W16 asset
 *     exists in the media library. Both are reported at the end so they can be
 *     swapped when Dorotape supply them. A page with the range's own datasheet
 *     is better than a page with no technical information.
 *   - It does not rewrite the body copy beyond the product name. The structure,
 *     suitability and advantages already describe both faces of the film.
 *
 * Idempotent. A second run reports [ok] and writes nothing. If the W16 codes are
 * already on some other product it refuses rather than create a duplicate SKU.
 *
 * Usage:
 *   php round3_blockout_split.php            (dry run, default)
 *   php round3_blockout_split.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

// ---------------------------------------------------------------- what we want

/** The finished W15 product, used as the template. */
const SRC_ID = 1481;

/** The title the W15 half must still have, or we are not looking at what we think. */
const SRC_TITLE = 'ASLAN W15 White Blockout Film';

const NEW_TITLE = 'ASLAN W16 Black Blockout Film';
const NEW_SLUG  = 'aslan-w16-black-blockout-film';

/** Variation axis, matching the W15 half. */
const AXIS = 'pa_width';

/**
 * Width option => [ W16 stock code, the W15 code to mirror ].
 *
 * Order is the order they appear on the page, cheapest first, as on W15.
 */
$widths = array(
	'625mm'  => array( 'W16625', 'W15625' ),
	'1250mm' => array( 'W161250', 'W151250' ),
);

/** Parent-level meta inherited from the W15 product, by design rather than by accident. */
$inherit = array(
	'_tax_status',
	'_tax_class',
	'_manage_stock',
	'_backorders',
	'_sold_individually',
	'_virtual',
	'_downloadable',
	'_download_limit',
	'_download_expiry',
	'_stock',
	'_stock_status',
	'_manufacturer',
	'_upsell_ids',
	'_product_attributes',
	'_thumbnail_id',
	// The Sage plugin's own fields, so the new product syncs like its twin.
	'_sage_income_code',
	'_sage_pri',
	'_sage_alt',
	'_sage_primod',
	'_sage_altmod',
	'_sage_class_id',
	'_sage_price_tier',
);

/**
 * Deliberately NOT inherited:
 *   _kryp_seourl, _kryp_prodnum  the old Kryptronic site's identity for W15
 *   _dt_superseded_sku           the old non-Sage code 1004 that W15 gave up
 *   _sku                         a variable parent holds no code
 *   total_sales, _wc_*, _edit_lock, _product_version  WooCommerce's own
 */

// ---------------------------------------------------------------- Sage export

$sage_csv = __DIR__ . '/sage_skus.csv';
if ( ! is_readable( $sage_csv ) ) {
	exit( "Cannot read $sage_csv\n" );
}
$sage = array();
$fh   = fopen( $sage_csv, 'r' );
fgetcsv( $fh );
while ( false !== ( $row = fgetcsv( $fh ) ) ) {
	if ( ! isset( $row[0] ) || '' === trim( $row[0] ) ) {
		continue;
	}
	$sage[ strtoupper( trim( $row[0] ) ) ] = array(
		'desc'  => $row[1] ?? '',
		'cat'   => (int) ( $row[2] ?? 0 ),
		'price' => (float) ( $row[3] ?? 0 ),
	);
}
fclose( $fh );
printf( "Sage export: %d stock codes.\n", count( $sage ) );

// ------------------------------------------------------------------ the guards

$problems = array();

$src = get_post( SRC_ID );
if ( ! $src || 'product' !== $src->post_type ) {
	exit( sprintf( "#%d is not a product. Nothing to copy from.\n", SRC_ID ) );
}
if ( SRC_TITLE !== html_entity_decode( $src->post_title ) ) {
	$problems[] = sprintf(
		'#%d is titled "%s", expected "%s". Run round3_build.php first.',
		SRC_ID,
		html_entity_decode( $src->post_title ),
		SRC_TITLE
	);
}

$src_product = wc_get_product( SRC_ID );
if ( ! $src_product || ! $src_product->is_type( 'variable' ) ) {
	$problems[] = sprintf( '#%d is not a variable product.', SRC_ID );
}

/** width => the W15 variation that holds that width. */
$src_variations = array();
if ( $src_product ) {
	foreach ( $src_product->get_children() as $vid ) {
		$slug = (string) get_post_meta( $vid, 'attribute_' . AXIS, true );
		if ( '' !== $slug ) {
			$src_variations[ $slug ] = (int) $vid;
		}
	}
}

/** What we intend to write, filled in and checked before anything is created. */
$plan = array();

foreach ( $widths as $width => list( $code, $mirror ) ) {
	$upper = strtoupper( $code );

	if ( ! isset( $sage[ $upper ] ) ) {
		$problems[] = "$code is not in the Sage export.";
		continue;
	}
	$row = $sage[ $upper ];
	if ( 100 === $row['cat'] || false !== stripos( $row['desc'], 'DO NOT USE' ) ) {
		$problems[] = "$code is marked obsolete in Sage: {$row['desc']}";
		continue;
	}
	// Cheap sanity check that the code is the product we think it is.
	if ( false === stripos( $row['desc'], 'W16' ) ) {
		$problems[] = "$code does not look like a W16 line in Sage: {$row['desc']}";
		continue;
	}

	if ( ! isset( $src_variations[ $width ] ) ) {
		$problems[] = "The W15 product has no $width option to mirror.";
		continue;
	}
	$twin       = $src_variations[ $width ];
	$twin_sku   = (string) get_post_meta( $twin, '_sku', true );
	$twin_price = (float) get_post_meta( $twin, '_regular_price', true );
	$twin_tiers = (string) get_post_meta( $twin, '_price_tiers', true );

	if ( strtoupper( $twin_sku ) !== strtoupper( $mirror ) ) {
		$problems[] = sprintf( 'The W15 %s option holds %s, expected %s.', $width, '' === $twin_sku ? '(none)' : $twin_sku, $mirror );
		continue;
	}
	// The discount shape is copied from W15, so the base price has to match or
	// copying it is a guess. Sage prices both faces the same today.
	if ( abs( $twin_price - $row['price'] ) > 0.005 ) {
		$problems[] = sprintf(
			'Sage prices %s at %.2f but %s at %.2f, so the W15 discount shape cannot simply be mirrored. Needs a human.',
			$code,
			$row['price'],
			$mirror,
			$twin_price
		);
		continue;
	}

	// The customer-facing label has to be one the site already uses, or the copy
	// would create a second term meaning the same width.
	$term = get_term_by( 'slug', $width, AXIS );
	if ( ! $term ) {
		$problems[] = sprintf( 'No %s term "%s" on the site.', AXIS, $width );
		continue;
	}

	$plan[ $width ] = array(
		'code'   => $code,
		'price'  => $row['price'],
		'tiers'  => $twin_tiers,
		'desc'   => $row['desc'],
		'mirror' => $mirror,
	);
}

// Never create a second row for a stock code. This is the bug we spent the
// morning clearing off 900E, DFP071370, DFP07500 and SRL96.
$existing_parent = 0;
foreach ( $plan as $width => $p ) {
	$holder = (int) wc_get_product_id_by_sku( $p['code'] );
	if ( ! $holder ) {
		// The lookup table can lag behind, so check the meta directly too.
		global $wpdb;
		$holder = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} m
				 JOIN {$wpdb->posts} p ON p.ID = m.post_id
				 WHERE m.meta_key = '_sku' AND m.meta_value = %s AND p.post_status <> 'trash'
				 LIMIT 1",
				$p['code']
			)
		);
	}
	if ( ! $holder ) {
		continue;
	}
	$owner = wp_get_post_parent_id( $holder ) ?: $holder;
	if ( $existing_parent && $existing_parent !== $owner ) {
		$problems[] = sprintf( '%s is on #%d but the other W16 code is on a different product.', $p['code'], $holder );
		continue;
	}
	$existing_parent = $owner;
	$plan[ $width ]['holder'] = $holder;
}

if ( $existing_parent && NEW_TITLE !== html_entity_decode( (string) get_the_title( $existing_parent ) ) ) {
	$problems[] = sprintf(
		'The W16 codes are already on #%d "%s", which is not the product this script builds. Left alone.',
		$existing_parent,
		html_entity_decode( (string) get_the_title( $existing_parent ) )
	);
}

if ( $problems ) {
	printf( "\nREFUSING TO RUN, %d problems:\n", count( $problems ) );
	foreach ( $problems as $p ) {
		echo "  ! $p\n";
	}
	exit( "\nNothing was written.\n" );
}

printf( "Checked %d stock codes against the export and against the W15 half.\n\n", count( $plan ) );

// --------------------------------------------------------------------- the work

$lines   = array();
$touched = array();

// --- the parent

$dest_id = $existing_parent;

$content = str_replace( 'ASLAN W15', 'ASLAN W16', $src->post_content );
$excerpt = str_replace( 'ASLAN W15', 'ASLAN W16', $src->post_excerpt );

if ( ! $dest_id ) {
	$lines[] = sprintf( 'create  product "%s"', NEW_TITLE );
	if ( $apply ) {
		$dest_id = wp_insert_post(
			array(
				'post_title'     => NEW_TITLE,
				'post_name'      => NEW_SLUG,
				'post_content'   => $content,
				'post_excerpt'   => $excerpt,
				'post_status'    => 'publish',
				'post_type'      => 'product',
				'post_author'    => $src->post_author,
				'menu_order'     => $src->menu_order,
				'comment_status' => $src->comment_status,
				'ping_status'    => $src->ping_status,
			),
			true
		);
		if ( is_wp_error( $dest_id ) ) {
			exit( 'Could not create the product: ' . $dest_id->get_error_message() . "\n" );
		}
		$touched[] = $dest_id;
	}
} else {
	// Printed rather than counted as a change, so a settled product reports [ok].
	printf( "         exists  product #%d \"%s\"\n", $dest_id, NEW_TITLE );

	$want = array( 'post_content' => $content, 'post_excerpt' => $excerpt );
	$diff = array();
	foreach ( $want as $field => $value ) {
		if ( get_post_field( $field, $dest_id ) !== $value ) {
			$diff[ $field ] = $value;
		}
	}
	if ( $diff ) {
		$lines[] = 'copy    ' . implode( ', ', array_keys( $diff ) ) . ' from the W15 product';
		if ( $apply ) {
			wp_update_post( array( 'ID' => $dest_id ) + $diff );
			$touched[] = $dest_id;
		}
	}
}

// Type, category and attribute terms. Worked out even on a dry run, where there
// is no product yet, so the run shows everything it would write rather than just
// "create product" and a shrug.
{
	$taxonomies = array(
		'product_type' => array( 'variable' ),
		'product_cat'  => wp_list_pluck( wp_get_object_terms( SRC_ID, 'product_cat' ), 'slug' ),
		AXIS           => array_keys( $plan ),
		'pa_size-width' => wp_list_pluck( wp_get_object_terms( SRC_ID, 'pa_size-width' ), 'slug' ),
		'pa_suitability' => wp_list_pluck( wp_get_object_terms( SRC_ID, 'pa_suitability' ), 'slug' ),
	);
	foreach ( $taxonomies as $taxonomy => $slugs ) {
		$slugs = array_values( array_filter( $slugs ) );
		if ( ! $slugs ) {
			continue;
		}
		$have = $dest_id ? wp_list_pluck( wp_get_object_terms( $dest_id, $taxonomy ), 'slug' ) : array();
		sort( $have );
		$want = $slugs;
		sort( $want );
		if ( $have !== $want ) {
			$lines[] = sprintf( 'terms   %-16s %s -> %s', $taxonomy, $have ? implode( ',', $have ) : '(none)', implode( ',', $slugs ) );
			if ( $apply ) {
				wp_set_object_terms( $dest_id, $slugs, $taxonomy, false );
				$touched[] = $dest_id;
			}
		}
	}

	// Inherited meta, plus the handful that differs.
	$meta = array();
	foreach ( $inherit as $key ) {
		$value = get_post_meta( SRC_ID, $key, true );
		if ( '' !== $value && null !== $value ) {
			$meta[ $key ] = $value;
		}
	}
	// _regular_price and _price are deliberately absent. On a variable product
	// WooCommerce owns them: the parent has no regular price of its own and
	// _price is the cheapest option, set by ::sync(). Writing them here only got
	// undone on save, and the theme never reads either one for a variable
	// product (inc/woocommerce.php:261 returns early) so there is nothing to gain.
	//
	// The parent's _price_tiers is set, and matches the widest option, because
	// inc/pricing.php falls back to it. Tiers that actually drive the page live
	// on the options.
	$prices = wp_list_pluck( $plan, 'price' );
	$widest = array_search( max( $prices ), $prices, true );
	$meta['_dt_price_unit'] = 'metre';
	$meta['_price_tiers']   = $plan[ $widest ]['tiers'];
	$meta['_sku']           = '';

	foreach ( $meta as $key => $value ) {
		$was = $dest_id ? get_post_meta( $dest_id, $key, true ) : '';
		if ( maybe_serialize( $was ) === maybe_serialize( $value ) ) {
			continue;
		}
		$show = is_array( $value ) ? '(' . count( $value ) . ' items)' : (string) $value;
		$lines[] = sprintf( 'meta    %-18s %s -> %s', $key, '' === $was ? '(none)' : ( is_array( $was ) ? '(array)' : (string) $was ), '' === $show ? '(empty)' : $show );
		if ( $apply ) {
			update_post_meta( $dest_id, $key, $value );
			$touched[] = $dest_id;
		}
	}
}

// --- the two width options

foreach ( $plan as $width => $p ) {
	$vid = isset( $p['holder'] ) ? (int) $p['holder'] : 0;

	if ( ! $vid && $dest_id ) {
		foreach ( get_children( array( 'post_parent' => $dest_id, 'post_type' => 'product_variation', 'post_status' => 'any' ) ) as $child ) {
			if ( (string) get_post_meta( $child->ID, 'attribute_' . AXIS, true ) === $width ) {
				$vid = (int) $child->ID;
				break;
			}
		}
	}

	if ( ! $vid ) {
		$lines[] = sprintf( 'create  %-8s %s at %.2f  (mirrors %s)', $width, $p['code'], $p['price'], $p['mirror'] );
		if ( $apply ) {
			$vid = wp_insert_post(
				array(
					'post_title'  => NEW_TITLE,
					'post_name'   => "product-$dest_id-variation-$width",
					'post_status' => 'publish',
					'post_parent' => $dest_id,
					'post_type'   => 'product_variation',
					'menu_order'  => 1 + array_search( $width, array_keys( $plan ), true ),
				),
				true
			);
			if ( is_wp_error( $vid ) ) {
				exit( 'Could not create the variation: ' . $vid->get_error_message() . "\n" );
			}
			update_post_meta( $vid, 'attribute_' . AXIS, $width );
			$touched[] = $vid;
		}
	}

	$vmeta = array(
		'attribute_' . AXIS => $width,
		'_sku'              => $p['code'],
		'_regular_price'    => (string) $p['price'],
		'_price'            => (string) $p['price'],
		'_price_tiers'      => $p['tiers'],
	);
	foreach ( $vmeta as $key => $value ) {
		$was = $vid ? (string) get_post_meta( $vid, $key, true ) : '';
		if ( $was === (string) $value ) {
			continue;
		}
		$lines[] = sprintf( '%-8s %-18s %s -> %s', $width, $key, '' === $was ? '(none)' : $was, $value );
		if ( $apply ) {
			update_post_meta( $vid, $key, $value );
			$touched[] = $vid;
		}
	}
}

// --- report and save through WooCommerce

if ( ! $lines ) {
	echo "[ok]     W16 is already built and matches the W15 half.\n";
} else {
	foreach ( $lines as $l ) {
		printf( "%s %s\n", $apply ? '[write]' : '[dry]  ', $l );
	}
}

if ( $apply && $touched ) {
	// Saved through WooCommerce so the stock code lands in the product lookup
	// table. update_post_meta() alone leaves wc_get_product_id_by_sku() blind to
	// it, which is how a duplicate code gets in unnoticed later.
	//
	// Every value this script cares about is snapshotted first and compared
	// after, so a CRUD save quietly normalising something cannot pass unseen.
	$keys  = array( '_sku', '_regular_price', '_price', '_price_tiers', '_dt_price_unit', 'attribute_' . AXIS );
	$ids   = array_values( array_unique( array_merge( array( $dest_id ), get_children( array( 'post_parent' => $dest_id, 'post_type' => 'product_variation', 'post_status' => 'any', 'fields' => 'ids' ) ) ) ) );
	$before = array();
	foreach ( $ids as $id ) {
		foreach ( $keys as $key ) {
			$before[ $id ][ $key ] = (string) get_post_meta( $id, $key, true );
		}
	}

	foreach ( $ids as $id ) {
		$obj = wc_get_product( $id );
		if ( $obj ) {
			$obj->save();
		}
	}
	WC_Product_Variable::sync( $dest_id );
	wc_delete_product_transients( $dest_id );

	$drift = array();
	foreach ( $ids as $id ) {
		foreach ( $keys as $key ) {
			$now = (string) get_post_meta( $id, $key, true );
			if ( $now !== $before[ $id ][ $key ] ) {
				$drift[] = sprintf( '#%d %s: %s -> %s', $id, $key, $before[ $id ][ $key ], $now );
			}
		}
	}
	if ( $drift ) {
		echo "\nWooCommerce changed these on save, check them:\n";
		foreach ( $drift as $d ) {
			echo "  ! $d\n";
		}
	}
}

echo "\n" . str_repeat( '-', 78 ) . "\n";

if ( $dest_id ) {
	printf( "W16 product: #%d  %s\n", $dest_id, get_permalink( $dest_id ) ?: NEW_SLUG );
	foreach ( $plan as $width => $p ) {
		printf( "  %-8s %-9s £%-8.2f %s\n", $width, $p['code'], $p['price'], $p['tiers'] );
	}
}

echo "\nStill needs a human, both reported rather than guessed:\n";
printf(
	"  ! image     the W15 photograph (#%s) is on the W16 page. No W16 image exists in the media library.\n",
	get_post_meta( SRC_ID, '_thumbnail_id', true )
);
echo "  ! datasheet the page links ASLAN_W15.pdf. No W16 datasheet has been supplied.\n";

if ( $apply ) {
	echo "\nApplied. Re-run to confirm it reports [ok].\n";
} else {
	echo "\nDry run only - pass --apply to write.\n";
}
