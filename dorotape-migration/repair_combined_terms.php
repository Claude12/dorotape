<?php
/**
 * repair_combined_terms.php
 *
 * Repairs taxonomy terms created as one combined pipe-separated string
 * e.g. "0.50m Length | 1.00m Length | Handle - Self-adhesive"
 * because the WooCommerce CSV importer splits Attribute N value(s) on
 * comma, not on pipe.
 *
 * Connects directly to MySQL (no WordPress bootstrap needed).
 *
 * Usage (from any directory):
 *   php wp-content/themes/dorotape/dorotape-migration/repair_combined_terms.php
 */

// -- Database config (matches wp-config.php) ----------------------------------
$db_host = '127.0.0.1';
$db_port = 3306;
$db_user = 'root';
$db_pass = '';
$db_name = 'dorotape_db';
$prefix  = 'wp_';
// -----------------------------------------------------------------------------

$db = new mysqli( $db_host, $db_user, $db_pass, $db_name, $db_port );
if ( $db->connect_error ) {
	die( 'Connection failed: ' . $db->connect_error . "\n" );
}
$db->set_charset( 'utf8mb4' );

function log_line( $msg ) {
	echo $msg . "\n";
}

function db_escape( mysqli $db, $val ) {
	return $db->real_escape_string( $val );
}

// -- Find all combined terms in pa_* taxonomies --------------------------------
$t      = $prefix . 'terms';
$tt     = $prefix . 'term_taxonomy';
$tr     = $prefix . 'term_relationships';
$pm     = $prefix . 'postmeta';

$result = $db->query( "
	SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.taxonomy
	FROM {$t} t
	JOIN {$tt} tt ON tt.term_id = t.term_id
	WHERE tt.taxonomy LIKE 'pa_%'
	  AND t.name LIKE '%|%'
	ORDER BY tt.taxonomy, t.name
" );

$combined = $result->fetch_all( MYSQLI_ASSOC );

if ( empty( $combined ) ) {
	log_line( 'No combined terms found - nothing to repair.' );
	exit( 0 );
}

log_line( 'Found ' . count( $combined ) . ' combined term(s) to repair.' );
log_line( '' );

$repaired = 0;
$skipped  = 0;

foreach ( $combined as $term ) {
	$taxonomy      = $term['taxonomy'];
	$combined_id   = (int) $term['term_id'];
	$combined_ttid = (int) $term['term_taxonomy_id'];
	$labels        = array_filter( array_map( 'trim', explode( '|', $term['name'] ) ) );

	if ( count( $labels ) < 2 ) {
		log_line( '  SKIP [' . $taxonomy . '] "' . $term['name'] . '" - only one part.' );
		$skipped++;
		continue;
	}

	log_line( '  [' . $taxonomy . '] "' . $term['name'] . '"' );
	log_line( '    -> ' . implode( ', ', $labels ) );

	// -- 1. Get or create each individual term ---------------------------------
	$individual_ttids = array();
	foreach ( $labels as $label ) {
		$slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', $label ), '-' ) );
		$esc_name = db_escape( $db, $label );
		$esc_slug = db_escape( $db, $slug );
		$esc_tax  = db_escape( $db, $taxonomy );

		// Check by name first, then slug
		$row = $db->query(
			"SELECT t.term_id, tt.term_taxonomy_id FROM {$t} t
			 JOIN {$tt} tt ON tt.term_id = t.term_id
			 WHERE tt.taxonomy = '{$esc_tax}' AND t.name = '{$esc_name}' LIMIT 1"
		)->fetch_assoc();

		if ( ! $row ) {
			$row = $db->query(
				"SELECT t.term_id, tt.term_taxonomy_id FROM {$t} t
				 JOIN {$tt} tt ON tt.term_id = t.term_id
				 WHERE tt.taxonomy = '{$esc_tax}' AND t.slug = '{$esc_slug}' LIMIT 1"
			)->fetch_assoc();
		}

		if ( $row ) {
			$individual_ttids[] = (int) $row['term_taxonomy_id'];
			log_line( '      term_id ' . $row['term_id'] . ' (exists) "' . $label . '"' );
		} else {
			// Ensure slug is unique
			$base_slug = $esc_slug;
			$suffix    = 2;
			while ( $db->query( "SELECT term_id FROM {$t} WHERE slug = '{$esc_slug}' LIMIT 1" )->num_rows > 0 ) {
				$esc_slug = $base_slug . '-' . $suffix++;
			}
			$db->query( "INSERT INTO {$t} (name, slug, term_group) VALUES ('{$esc_name}', '{$esc_slug}', 0)" );
			$new_term_id = (int) $db->insert_id;
			$db->query( "INSERT INTO {$tt} (term_id, taxonomy, description, parent, count) VALUES ({$new_term_id}, '{$esc_tax}', '', 0, 0)" );
			$new_ttid = (int) $db->insert_id;
			$individual_ttids[] = $new_ttid;
			log_line( '      term_id ' . $new_term_id . ' (created, slug "' . $esc_slug . '") "' . $label . '"' );
		}
	}

	if ( empty( $individual_ttids ) ) {
		log_line( '    SKIP - no terms resolved.' );
		$skipped++;
		continue;
	}

	// -- 2. Find objects using the combined term and reassign ------------------
	$objects = $db->query(
		"SELECT object_id FROM {$tr} WHERE term_taxonomy_id = {$combined_ttid}"
	)->fetch_all( MYSQLI_ASSOC );

	log_line( '    Reassigning ' . count( $objects ) . ' object(s).' );

	foreach ( $objects as $obj ) {
		$oid = (int) $obj['object_id'];

		// Add individual terms (ignore duplicates)
		foreach ( $individual_ttids as $ttid ) {
			$db->query(
				"INSERT IGNORE INTO {$tr} (object_id, term_taxonomy_id, term_order)
				 VALUES ({$oid}, {$ttid}, 0)"
			);
			if ( $db->affected_rows > 0 ) {
				// Increment term count
				$db->query( "UPDATE {$tt} SET count = count + 1 WHERE term_taxonomy_id = {$ttid}" );
			}
		}

		// Remove combined term assignment
		$db->query( "DELETE FROM {$tr} WHERE object_id = {$oid} AND term_taxonomy_id = {$combined_ttid}" );
	}

	// Decrement combined term count
	$db->query( "UPDATE {$tt} SET count = 0 WHERE term_taxonomy_id = {$combined_ttid}" );

	// -- 3. Fix variation attribute meta slugs --------------------------------
	$attr_key = db_escape( $db, 'attribute_' . $taxonomy );
	foreach ( $labels as $label ) {
		$old_slug = strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', $label ), '-' ) );
		// Get the actual slug from the term we just created/found
		$esc_name = db_escape( $db, $label );
		$esc_tax  = db_escape( $db, $taxonomy );
		$term_row = $db->query(
			"SELECT t.slug FROM {$t} t
			 JOIN {$tt} tt ON tt.term_id = t.term_id
			 WHERE tt.taxonomy = '{$esc_tax}' AND t.name = '{$esc_name}' LIMIT 1"
		)->fetch_assoc();
		if ( ! $term_row ) {
			continue;
		}
		$new_slug = $term_row['slug'];
		if ( $old_slug === $new_slug ) {
			continue;
		}
		$esc_old = db_escape( $db, $old_slug );
		$esc_new = db_escape( $db, $new_slug );
		$db->query( "UPDATE {$pm} SET meta_value = '{$esc_new}' WHERE meta_key = '{$attr_key}' AND meta_value = '{$esc_old}'" );
		if ( $db->affected_rows > 0 ) {
			log_line( '    Meta: "' . $old_slug . '" -> "' . $new_slug . '" (' . $db->affected_rows . ' row(s))' );
		}
	}

	// -- 4. Delete the combined term -------------------------------------------
	$db->query( "DELETE FROM {$tr} WHERE term_taxonomy_id = {$combined_ttid}" );
	$db->query( "DELETE FROM {$tt} WHERE term_taxonomy_id = {$combined_ttid}" );
	$db->query( "DELETE FROM {$t} WHERE term_id = {$combined_id} AND NOT EXISTS (SELECT 1 FROM {$tt} WHERE term_id = {$combined_id})" );

	log_line( '    Deleted combined term ID ' . $combined_id . '.' );
	$repaired++;
	log_line( '' );
}

$db->close();

log_line( '---------------------------------------------' );
log_line( 'Repaired : ' . $repaired );
log_line( 'Skipped  : ' . $skipped );
log_line( 'Done.' );
