<?php
/**
 * Free the canonical Orajet 3164 slug from an abandoned draft duplicate.
 *
 * Two earlier, incomplete attempts at this rename are still in the database:
 * #6627 (draft, "Orajet 3164 - 4 Year Digital Vinyl") and #6623 (trashed,
 * "Orajet 3162 - 4 Year Removable Digital Vinyl"). Both carry placeholder SKUs
 * (Orajet_3164-sz01 ...) rather than the real Sage codes, all of their
 * variations are in the trash, and both still have the empty-finish problem —
 * so neither is the version to keep. orajet_restructure.php builds the real one
 * on the live products #6592 / #6596.
 *
 * #6623 is trashed and its slug carries the __trashed suffix, so it is not in
 * the way. #6627 is a *draft*, and a draft still owns its slug: it was holding
 * orajet-3164-4-year-digital-vinyl, which is why #6592 was pushed onto
 * orajet-3164-4-year-digital-vinyl-2.
 *
 * The draft is renamed, NOT deleted. Deleting someone else's unfinished work to
 * win a slug is not this script's call to make; parking it under a clearly
 * suffixed slug frees the canonical URL and leaves the draft intact and
 * findable in wp-admin for whoever created it to delete or finish.
 *
 * Run this BEFORE orajet_restructure.php on a fresh environment. Running it
 * after simply means restructure needs one more pass to take the freed slug.
 *
 * Idempotent — a second run reports [ok] and writes nothing.
 *
 * Usage:
 *   php orajet_free_slug.php            (dry run, default)
 *   php orajet_free_slug.php --apply
 *
 * @package dorotape
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( 'cli' !== PHP_SAPI ) {
	exit( "CLI only.\n" );
}

$apply = in_array( '--apply', $argv, true );
echo $apply ? "MODE: APPLY\n\n" : "MODE: DRY RUN (pass --apply to write)\n\n";

$draft_id = 6627;
$wanted   = 'orajet-3164-4-year-digital-vinyl';
$parked   = 'orajet-3164-4-year-digital-vinyl-superseded-draft';

$draft = get_post( $draft_id );
if ( ! $draft ) {
	exit( "  #$draft_id not found — nothing to do\n" );
}

printf(
	"  draft #%d  status=%s  slug=%s\n  title: %s\n\n",
	$draft_id,
	$draft->post_status,
	$draft->post_name,
	html_entity_decode( $draft->post_title )
);

// Only ever touch a draft. If it has been published in the meantime that is a
// real product and a human needs to decide, not this script.
if ( 'draft' !== $draft->post_status ) {
	exit( "  ! #$draft_id is '{$draft->post_status}', not a draft — refusing to touch it\n" );
}

if ( $draft->post_name !== $wanted ) {
	echo "  [ok]    draft is not holding '$wanted' (nothing to free)\n";
	exit( 0 );
}

if ( $apply ) {
	wp_update_post(
		array(
			'ID'        => $draft_id,
			'post_name' => $parked,
		)
	);
	printf( "  [write] draft slug %s -> %s\n", $wanted, $parked );
	echo "          draft left in place, still a draft, nothing deleted\n";
} else {
	printf( "  [dry]   draft slug %s -> %s\n", $wanted, $parked );
}
