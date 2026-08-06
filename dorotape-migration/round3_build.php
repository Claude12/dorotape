<?php
/**
 * Apply the target state in round3_spec.php to the catalogue.
 *
 * Michael's two completed sheets of 5 August 2026 settled 88 products. The shape
 * he asked for is the one width_restructure.php already established: drop the
 * fixed roll lengths, sell per metre in increments, and put the discount where
 * the large roll used to be. This script does that at range scale, plus the
 * renames, the option removals and the three deletions he asked for.
 *
 * WHAT IT REFUSES TO DO
 *
 * Nothing is written until every stock code in the spec has been checked against
 * sage_skus.csv. A code that is missing, marked obsolete, or priced differently
 * from what the spec declares stops the run. Where a site price legitimately
 * differs from the Sage price, the spec must say why in as many words, and this
 * script will not apply an entry whose price differs without one. That is the
 * whole point: a live stock code on the wrong line is worse than no line.
 *
 * It also asserts each product's current title before touching it, so if post
 * IDs differ on another install it stops rather than rebuild the wrong product.
 *
 * PARKING RATHER THAN DELETING
 *
 * Retired variations and deleted products are trashed, and their SKUs moved to
 * "<code>__superseded" with the original recorded in _dt_superseded_sku. That
 * frees the stock code for the line that should own it without destroying the
 * audit trail, and it is what free_duplicate_skus.php and orajet_free_slug.php
 * already do.
 *
 * Keyed by post ID with a title assertion rather than by SKU, because many of
 * these products carry SKUs that are not Sage codes at all, which is half of
 * what is being fixed.
 *
 * Idempotent: a second run reports [ok] on every line and writes nothing.
 *
 * Usage:
 *   php round3_build.php                     dry run, default
 *   php round3_build.php --id=811            one product only
 *   php round3_build.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply  = in_array( '--apply', $argv, true );
$only   = 0;
foreach ( $argv as $a ) {
	if ( preg_match( '/^--id=(\d+)$/', $a, $m ) ) {
		$only = (int) $m[1];
	}
}
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

$spec = require __DIR__ . '/round3_spec.php';

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

/**
 * Build the _price_tiers string from a base price and a set of breaks.
 *
 * Format matches what inc/pricing.php already parses: "1-24:11.00;25:9.90".
 *
 * @param float $base   Price at quantity one.
 * @param array $breaks min quantity => price, ascending.
 * @return string
 */
function r3_tiers( float $base, array $breaks ): string {
	if ( ! $breaks ) {
		return '';
	}
	ksort( $breaks );
	$mins  = array_keys( $breaks );
	$parts = array();
	$from  = 1;
	$price = $base;
	foreach ( $mins as $min ) {
		$parts[] = sprintf( '%d-%d:%.2f', $from, $min - 1, $price );
		$from    = $min;
		$price   = $breaks[ $min ];
	}
	$parts[] = sprintf( '%d:%.2f', $from, $price );
	return implode( ';', $parts );
}

// -------------------------------------------------------------- verify first

$problems = array();
$checked  = 0;

foreach ( $spec as $entry ) {
	$id    = $entry['id'];
	$label = sprintf( '#%d %s', $id, $entry['expect'] );

	$post = get_post( $id );
	if ( ! $post ) {
		$problems[] = "$label: post does not exist";
		continue;
	}
	$title = html_entity_decode( $post->post_title );
	if ( $title !== $entry['expect'] && $title !== ( $entry['title'] ?? '~' ) ) {
		$problems[] = sprintf( '%s: title is actually "%s"', $label, $title );
		continue;
	}

	foreach ( $entry['options'] ?? array() as $opt_label => $opt ) {
		++$checked;
		$code = strtoupper( $opt['sku'] );
		$what = $label . ' / ' . ( '' === $opt_label ? '(single)' : $opt_label );

		if ( ! isset( $sage[ $code ] ) ) {
			$problems[] = "$what: $code is not in the Sage export";
			continue;
		}
		if ( 100 === $sage[ $code ]['cat'] || false !== stripos( $sage[ $code ]['desc'], 'DO NOT USE' ) ) {
			$problems[] = "$what: $code is marked obsolete in Sage";
			continue;
		}
		if ( abs( $sage[ $code ]['price'] - $opt['sage'] ) > 0.005 ) {
			$problems[] = sprintf(
				'%s: spec says Sage holds %s at %.2f, export says %.2f',
				$what,
				$code,
				$opt['sage'],
				$sage[ $code ]['price']
			);
			continue;
		}
		// dorotape_price_unit() in inc/pricing.php resolves a variation to its
		// parent, so a unit on a variation is meta nothing ever reads. Better to
		// refuse it than to store something that looks like a fix and is not.
		if ( ! empty( $entry['taxonomy'] ) && ! empty( $opt['unit'] ) ) {
			$problems[] = sprintf(
				'%s: declares unit "%s" on a variation, and the theme reads the unit from the parent only',
				$what,
				$opt['unit']
			);
			continue;
		}
		if ( abs( $opt['price'] - $opt['sage'] ) > 0.005 && '' === trim( $opt['why'] ) ) {
			$problems[] = sprintf(
				'%s: site price %.2f differs from Sage %.2f with no reason given',
				$what,
				$opt['price'],
				$opt['sage']
			);
			continue;
		}

		// A code must not already be live on a product we are not rebuilding.
		$holder = wc_get_product_id_by_sku( $code );
		if ( $holder ) {
			$owner = wp_get_post_parent_id( $holder ) ?: $holder;
			if ( $owner !== $id ) {
				$problems[] = sprintf(
					'%s: %s is already on #%d (%s), a product this run does not touch',
					$what,
					$code,
					$holder,
					html_entity_decode( get_the_title( $owner ) )
				);
			}
		}
	}
}

printf( "Verified %d stock codes across %d products.\n", $checked, count( $spec ) );

if ( $problems ) {
	printf( "\nREFUSING TO RUN, %d problems:\n", count( $problems ) );
	foreach ( $problems as $p ) {
		echo "  ! $p\n";
	}
	exit( "\nNothing was written. Fix the spec or the export and run again.\n" );
}
echo "All codes check out against the export.\n";

// ------------------------------------------------------------------- helpers

/**
 * Park a SKU so the stock code is freed, keeping the original recorded.
 *
 * @param int $post_id Product or variation.
 * @param bool $apply  Whether to write.
 * @return string Description of what happened, empty if nothing to do.
 */
function r3_park_sku( int $post_id, bool $apply ): string {
	$sku = get_post_meta( $post_id, '_sku', true );
	if ( '' === $sku || str_ends_with( (string) $sku, '__superseded' ) ) {
		return '';
	}
	if ( $apply ) {
		update_post_meta( $post_id, '_dt_superseded_sku', $sku );
		update_post_meta( $post_id, '_sku', '' );
		global $wpdb;
		$wpdb->update( $wpdb->wc_product_meta_lookup, array( 'sku' => '' ), array( 'product_id' => $post_id ) );
	}
	return $sku;
}

/**
 * Make sure a term exists on an attribute taxonomy and return its slug.
 *
 * @param string $taxonomy Attribute taxonomy.
 * @param string $name     Term name as the customer will see it.
 * @param bool   $apply    Whether to create it if missing.
 * @return string Slug, or empty string if it would need creating on a dry run.
 */
function r3_term_slug( string $taxonomy, string $name, bool $apply ): string {
	$term = get_term_by( 'name', $name, $taxonomy );
	if ( ! $term ) {
		// Reuse rather than duplicate. Many existing term names differ from the
		// name we want only by case or by punctuation, most often a long dash:
		// "500mm wide — per metre" against "500mm wide per metre". Matching on the
		// slug catches those, and the dash gets tidied out of the label below.
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $t ) {
			if ( 0 === strcasecmp( $t->name, $name ) || $t->slug === sanitize_title( $name ) ) {
				$term = $t;
				break;
			}
		}
	}
	if ( $term ) {
		// Long dashes read as machine-written and these labels are on the shop
		// front, so straighten any we are already touching. Recorded because a
		// term can be shared, so this can reword an option on another product.
		if ( preg_match( '/[\x{2013}\x{2014}]/u', $term->name ) ) {
			global $stats;
			$stats['renamed'][ $term->slug ] = sprintf( '%s -> %s (on %d)', $term->name, $name, $term->count );
			if ( $apply ) {
				wp_update_term( $term->term_id, $taxonomy, array( 'name' => $name ) );
			}
		}
		return $term->slug;
	}
	if ( ! $apply ) {
		return '';
	}
	$new = wp_insert_term( $name, $taxonomy );
	if ( is_wp_error( $new ) ) {
		return '';
	}
	return get_term( $new['term_id'], $taxonomy )->slug;
}

// ----------------------------------------------------------------- the build

$stats = array(
	'products'  => 0,
	'unchanged' => 0,
	'writes'    => 0,
	'created'   => 0,
	'retired'   => 0,
	'freed'     => array(),
	'flags'     => array(),
	'renamed'   => array(),
);

foreach ( $spec as $entry ) {
	$id = $entry['id'];
	if ( $only && $only !== $id ) {
		continue;
	}
	++$stats['products'];

	$lines = array();
	$title = html_entity_decode( get_post( $id )->post_title );
	printf( "\n#%-5d %s\n", $id, $title );

	// ------------------------------------------------------------- deletions
	if ( ! empty( $entry['retire_product'] ) ) {
		$post = get_post( $id );
		if ( 'trash' === $post->post_status ) {
			echo "   [ok]     already in the trash\n";
			++$stats['unchanged'];
		} else {
			$freed = array();
			foreach ( get_posts( array(
				'post_parent' => $id,
				'post_type'   => 'product_variation',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			) ) as $vid ) {
				$was = r3_park_sku( $vid, $apply );
				if ( '' !== $was ) {
					$freed[] = $was;
				}
			}
			$was = r3_park_sku( $id, $apply );
			if ( '' !== $was ) {
				$freed[] = $was;
			}
			if ( $apply ) {
				wp_trash_post( $id );
			}
			printf( "   %s trashed%s\n", $apply ? '[write] ' : '[dry]   ', $freed ? ', freeing ' . implode( ', ', $freed ) : '' );
			++$stats['writes'];
			++$stats['retired'];
			foreach ( $freed as $f ) {
				if ( isset( $sage[ strtoupper( $f ) ] ) ) {
					$stats['freed'][] = $f;
				}
			}
		}
		if ( ! empty( $entry['note'] ) ) {
			echo '   note: ' . $entry['note'] . "\n";
		}
		continue;
	}

	$is_variable = ! empty( $entry['taxonomy'] );
	$taxonomy    = $entry['taxonomy'] ?? '';
	$keep        = $entry['keep'] ?? array();
	$options     = $entry['options'] ?? array();

	// Every published variation this product currently has.
	global $wpdb;
	$current = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'product_variation' AND post_status = 'publish' ORDER BY menu_order, ID",
		$id
	) );

	$target_skus = array();
	foreach ( $options as $opt ) {
		$target_skus[ strtoupper( $opt['sku'] ) ] = true;
	}

	// --- retire whatever is not wanted, first, so the codes come free.
	$retire = array();
	foreach ( $current as $vid ) {
		if ( in_array( (int) $vid, array_map( 'intval', $keep ), true ) ) {
			continue;
		}
		$vsku = strtoupper( (string) get_post_meta( $vid, '_sku', true ) );
		if ( $is_variable && isset( $target_skus[ $vsku ] ) ) {
			continue; // This one is being kept and updated below.
		}
		// On a simple product every variation goes, including the one holding the
		// stock code, which has to give it up before the parent can take it.
		$retire[] = (int) $vid;
	}
	foreach ( $entry['retire'] ?? array() as $vid ) {
		if ( ! in_array( (int) $vid, $retire, true ) && in_array( (string) $vid, $current, true ) ) {
			$retire[] = (int) $vid;
		}
	}

	foreach ( $retire as $vid ) {
		$was = r3_park_sku( $vid, $apply );
		if ( $apply ) {
			wp_update_post( array( 'ID' => $vid, 'post_status' => 'trash' ) );
		}
		$lines[] = sprintf(
			'retire  #%-6d %s%s',
			$vid,
			r3_variation_label( $vid ),
			'' !== $was ? "  frees $was" : ''
		);
		++$stats['retired'];
		if ( '' !== $was && isset( $sage[ strtoupper( $was ) ] ) ) {
			$stats['freed'][] = $was;
		}
	}

	// --- a variable parent must not hold a SKU of its own.
	//
	// WooCommerce never sends a variable parent's SKU to Sage, so at best it is
	// dead data. At worst it is the bug we have just finished clearing off three
	// other codes: SRL96 sits on this parent AND belongs on its 1370mm width, so
	// leaving it would put one stock code on two rows and make
	// wc_get_product_id_by_sku() a coin toss. Done here, before the options are
	// written, so the code is free by the time the variation claims it.
	if ( $is_variable || $keep ) {
		$was = r3_park_sku( $id, $apply );
		if ( '' !== $was ) {
			$code   = strtoupper( $was );
			$known  = isset( $sage[ $code ] );
			$stale  = $known && ( 100 === $sage[ $code ]['cat'] || false !== stripos( $sage[ $code ]['desc'], 'DO NOT USE' ) );
			if ( $stale ) {
				$why = ', and Sage marks it DO NOT USE, so it is not replaced with an invented code';
			} elseif ( $known ) {
				$why = ', and it is a live Sage code that belongs on a variation';
			} else {
				$why = ', a variable parent SKU is never sent to Sage';
			}
			$lines[] = sprintf( 'sku     parent %s parked%s', $was, $why );
			if ( $known && ! $stale ) {
				$stats['freed'][] = $was;
			}
		}
	}

	// --- the options themselves
	$slugs = array();
	foreach ( $options as $opt_label => $opt ) {
		$code   = strtoupper( $opt['sku'] );
		$tiers  = r3_tiers( (float) $opt['price'], $opt['breaks'] );
		$holder = 0;
		foreach ( array_merge( $current, $keep ) as $vid ) {
			if ( strtoupper( (string) get_post_meta( $vid, '_sku', true ) ) === $code ) {
				$holder = (int) $vid;
				break;
			}
		}

		if ( ! $is_variable ) {
			// Simple product: the parent carries the code, price and tiers.
			$changes = r3_write_pricing( $id, $code, (float) $opt['price'], $tiers, $opt['unit'] ?? '', $apply );
			$lines   = array_merge( $lines, $changes );
			continue;
		}

		$slug = r3_term_slug( $taxonomy, (string) $opt_label, $apply );
		if ( '' === $slug ) {
			$slug = sanitize_title( (string) $opt_label );
			$lines[] = sprintf( 'term    %s / %s would be created', $taxonomy, $opt_label );
		}
		$slugs[] = $slug;

		if ( ! $holder ) {
			// Match on the attribute term instead, in case the code is new.
			foreach ( $current as $vid ) {
				if ( in_array( (int) $vid, $retire, true ) ) {
					continue;
				}
				if ( (string) get_post_meta( $vid, 'attribute_' . $taxonomy, true ) === $slug ) {
					$holder = (int) $vid;
					break;
				}
			}
		}

		if ( ! $holder ) {
			if ( $apply ) {
				$holder = wp_insert_post( array(
					'post_title'  => "Variation: $opt_label",
					'post_name'   => "product-$id-variation-" . sanitize_title( (string) $opt_label ),
					'post_status' => 'publish',
					'post_parent' => $id,
					'post_type'   => 'product_variation',
					'menu_order'  => count( $slugs ),
				) );
			}
			$lines[] = sprintf( 'create  %-34s %s at %.2f', $opt_label, $code, $opt['price'] );
			++$stats['created'];
			if ( ! $apply ) {
				continue;
			}
		}

		if ( $apply ) {
			update_post_meta( $holder, 'attribute_' . $taxonomy, $slug );
		}
		$lines = array_merge(
			$lines,
			r3_write_pricing( $holder, $code, (float) $opt['price'], $tiers, $opt['unit'] ?? '', $apply, (string) $opt_label )
		);
	}

	// --- parent level: unit, step, attributes, type
	$lines = array_merge( $lines, r3_write_parent(
		$id,
		$entry,
		$is_variable,
		$taxonomy,
		$slugs,
		$keep,
		$apply
	) );

	if ( $lines ) {
		foreach ( $lines as $l ) {
			printf( "   %s %s\n", $apply ? '[write] ' : '[dry]   ', $l );
		}
		++$stats['writes'];
		if ( $apply ) {
			if ( $is_variable ) {
				WC_Product_Variable::sync( $id );
			}
			wc_delete_product_transients( $id );
		}
	} else {
		echo "   [ok]     already matches the spec\n";
		++$stats['unchanged'];
	}

	if ( ! empty( $entry['note'] ) ) {
		echo '   note: ' . $entry['note'] . "\n";
		if ( false !== strpos( $entry['note'], 'INFERRED REMOVAL' ) || false !== strpos( $entry['note'], 'DELIBERATELY NOT REMOVED' ) || false !== strpos( $entry['note'], 'NOTE FOR MICHAEL' ) ) {
			$stats['flags'][] = sprintf( '#%d %s', $id, $title );
		}
	}
}

/**
 * Human label for a variation, for the log.
 *
 * @param int $vid Variation ID.
 * @return string
 */
function r3_variation_label( int $vid ): string {
	global $wpdb;
	$vals = $wpdb->get_col( $wpdb->prepare(
		"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'attribute_%%'",
		$vid
	) );
	$vals = array_filter( $vals );
	return str_pad( substr( $vals ? implode( ' / ', $vals ) : '(no options)', 0, 34 ), 34 );
}

/**
 * Write SKU, price, tiers and unit onto a product or variation.
 *
 * @param int    $post_id Product or variation.
 * @param string $sku     Sage stock code.
 * @param float  $price   Site price.
 * @param string $tiers   _price_tiers string, empty for none.
 * @param string $unit    _dt_price_unit override, empty to leave to the parent.
 * @param bool   $apply   Whether to write.
 * @param string $label   Option label, for the log.
 * @return array Lines describing what changed.
 */
function r3_write_pricing( int $post_id, string $sku, float $price, string $tiers, string $unit, bool $apply, string $label = '' ): array {
	$out  = array();
	$name = '' === $label ? '(single)' : $label;

	$was_sku = (string) get_post_meta( $post_id, '_sku', true );
	if ( strtoupper( $was_sku ) !== $sku ) {
		$out[] = sprintf( 'sku     %-34s %s -> %s', $name, '' === $was_sku ? '(none)' : $was_sku, $sku );
		if ( $apply ) {
			update_post_meta( $post_id, '_sku', $sku );
		}
	}

	$was_price = (float) get_post_meta( $post_id, '_regular_price', true );
	if ( abs( $was_price - $price ) > 0.005 ) {
		$out[] = sprintf( 'price   %-34s %.2f -> %.2f', $name, $was_price, $price );
		if ( $apply ) {
			update_post_meta( $post_id, '_regular_price', (string) $price );
			update_post_meta( $post_id, '_price', (string) $price );
		}
	}

	$was_tiers = (string) get_post_meta( $post_id, '_price_tiers', true );
	if ( $was_tiers !== $tiers ) {
		$out[] = sprintf( 'tiers   %-34s %s -> %s', $name, '' === $was_tiers ? '(none)' : $was_tiers, '' === $tiers ? '(none)' : $tiers );
		if ( $apply ) {
			if ( '' === $tiers ) {
				delete_post_meta( $post_id, '_price_tiers' );
			} else {
				update_post_meta( $post_id, '_price_tiers', $tiers );
			}
		}
	}

	if ( '' !== $unit ) {
		$was_unit = (string) get_post_meta( $post_id, '_dt_price_unit', true );
		if ( $was_unit !== $unit ) {
			$out[] = sprintf( 'unit    %-34s %s -> %s', $name, '' === $was_unit ? '(inherit)' : $was_unit, $unit );
			if ( $apply ) {
				update_post_meta( $post_id, '_dt_price_unit', $unit );
			}
		}
	}

	if ( $apply ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->wc_product_meta_lookup,
			array( 'sku' => $sku, 'min_price' => $price, 'max_price' => $price ),
			array( 'product_id' => $post_id )
		);
	}
	return $out;
}

/**
 * Write the parent: title, unit, step, product type and the variation axis.
 *
 * For a variable product the parent's own _price_tiers is left alone. Tiers live
 * on the variations, and blanking the parent's value only makes the ACF fallback
 * in inc/pricing.php resurrect the old one.
 *
 * @param int    $id          Parent product.
 * @param array  $entry       Spec entry.
 * @param bool   $is_variable Whether it ends up variable.
 * @param string $taxonomy    Variation axis, empty for simple.
 * @param array  $slugs       Term slugs on that axis.
 * @param array  $keep        Variation IDs left alone.
 * @param bool   $apply       Whether to write.
 * @return array Lines describing what changed.
 */
function r3_write_parent( int $id, array $entry, bool $is_variable, string $taxonomy, array $slugs, array $keep, bool $apply ): array {
	$out = array();

	if ( ! empty( $entry['title'] ) ) {
		$was = html_entity_decode( get_post( $id )->post_title );
		if ( $was !== $entry['title'] ) {
			$out[] = sprintf( 'title   %s -> %s', $was, $entry['title'] );
			if ( $apply ) {
				wp_update_post( array( 'ID' => $id, 'post_title' => $entry['title'] ) );
			}
		}
	}

	$unit = $entry['unit'] ?? '';
	if ( '' !== $unit ) {
		$was = (string) get_post_meta( $id, '_dt_price_unit', true );
		if ( $was !== $unit ) {
			$out[] = sprintf( 'unit    parent %s -> %s', '' === $was ? '(none)' : $was, $unit );
			if ( $apply ) {
				update_post_meta( $id, '_dt_price_unit', $unit );
			}
		}
	}

	if ( array_key_exists( 'step', $entry ) ) {
		$step = (int) $entry['step'];
		$was  = (int) get_post_meta( $id, '_dt_qty_step', true );
		if ( $was !== $step ) {
			$out[] = sprintf( 'step    parent %s -> %s', $was ?: '(none)', $step ?: '(none)' );
			if ( $apply ) {
				if ( $step ) {
					update_post_meta( $id, '_dt_qty_step', $step );
				} else {
					delete_post_meta( $id, '_dt_qty_step' );
				}
			}
		}
	}

	// Product type.
	$want = $is_variable || $keep ? 'variable' : 'simple';
	$prod = wc_get_product( $id );
	if ( $prod && $prod->get_type() !== $want ) {
		$out[] = sprintf( 'type    %s -> %s', $prod->get_type(), $want );
		if ( $apply ) {
			wp_set_object_terms( $id, $want, 'product_type' );
		}
	}

	// The variation axis, and no other attribute left claiming to be one.
	$atts    = get_post_meta( $id, '_product_attributes', true );
	$atts    = is_array( $atts ) ? $atts : array();
	$changed = false;

	foreach ( $atts as $key => $att ) {
		$is_axis = ( $att['name'] === $taxonomy );
		$flag    = $is_axis && $is_variable ? 1 : 0;
		if ( (int) $att['is_variation'] !== $flag ) {
			$out[]                       = sprintf( 'attr    %s is_variation %d -> %d', $att['name'], $att['is_variation'], $flag );
			$atts[ $key ]['is_variation'] = $flag;
			$changed                      = true;
		}
	}

	if ( $is_variable && ! isset( $atts[ $taxonomy ] ) ) {
		$out[]              = sprintf( 'attr    %s added as the variation axis', $taxonomy );
		$atts[ $taxonomy ]  = array(
			'name'         => $taxonomy,
			'value'        => '',
			'position'     => 0,
			'is_visible'   => 1,
			'is_variation' => 1,
			'is_taxonomy'  => 1,
		);
		$changed            = true;
	}

	if ( $changed && $apply ) {
		update_post_meta( $id, '_product_attributes', $atts );
	}

	if ( $is_variable && $slugs ) {
		// Terms already on the product, plus whatever the kept variations use.
		foreach ( $keep as $vid ) {
			$s = (string) get_post_meta( $vid, 'attribute_' . $taxonomy, true );
			if ( '' !== $s ) {
				$slugs[] = $s;
			}
		}
		$slugs = array_values( array_unique( $slugs ) );
		$have  = wp_list_pluck( wp_get_object_terms( $id, $taxonomy ), 'slug' );
		if ( array_diff( $slugs, $have ) || array_diff( $have, $slugs ) ) {
			$out[] = sprintf( 'terms   %s: %s -> %s', $taxonomy, implode( ', ', $have ) ?: '(none)', implode( ', ', $slugs ) );
			if ( $apply ) {
				wp_set_object_terms( $id, $slugs, $taxonomy );
			}
		}
	}

	return $out;
}

// ------------------------------------------------------------------- summary

echo "\n" . str_repeat( '-', 78 ) . "\n";
printf( "products in run   : %d\n", $stats['products'] );
printf( "already correct   : %d\n", $stats['unchanged'] );
printf( "%s : %d\n", $apply ? 'products written  ' : 'products to write ', $stats['writes'] );
printf( "variations created: %d\n", $stats['created'] );
printf( "variations retired: %d\n", $stats['retired'] );

if ( $stats['renamed'] ) {
	printf(
		"\nOption labels with a long dash straightened (%d), with how many products each is used on:\n",
		count( $stats['renamed'] )
	);
	foreach ( $stats['renamed'] as $slug => $change ) {
		printf( "  %-38s %s\n", $slug, $change );
	}
}

$freed = array_values( array_unique( $stats['freed'] ) );
if ( $freed ) {
	printf( "\nSage codes freed for the line that should own them (%d):\n  %s\n", count( $freed ), implode( ', ', $freed ) );
}

if ( $stats['flags'] ) {
	printf( "\nWORTH A SECOND LOOK BEFORE THIS GOES OUT (%d):\n", count( $stats['flags'] ) );
	foreach ( array_unique( $stats['flags'] ) as $f ) {
		echo "  * $f\n";
	}
	echo "  Read the note under each. They are the places where Michael's answer\n";
	echo "  was inferred rather than stated, or where we found a price that\n";
	echo "  disagrees with his sheet.\n";
}

echo "\n" . ( $apply ? "Applied. Re-run to confirm every line reports [ok].\n" : "Dry run only, pass --apply to write.\n" );
