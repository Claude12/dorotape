<?php
/**
 * One-time migration: populate ACF price_tiers repeater from _price_tiers post meta.
 *
 * All simple products imported via the WooCommerce CSV migration carry their
 * quantity tier data in _price_tiers post meta (Kryptronic format: "1-24:11.00;25:9.90").
 * The ACF price_tiers repeater was empty for those products, making tiers invisible
 * and uneditable in the admin UI.
 *
 * This migration reads _price_tiers from every simple product, parses it into the
 * ACF repeater format, and saves it — so the client can see and edit all tiers
 * through the standard ACF admin UI going forward.
 *
 * Idempotent: skips products that already have ACF price_tiers data (manual entries
 * or previously migrated) and products with no meaningful _price_tiers value.
 *
 * Safe to remove this file once the migration has run successfully.
 *
 * @package dorotape
 */

define( 'DOROTAPE_MIGRATE_PRICE_TIERS_VERSION', '1.0.0' );

function dorotape_maybe_run_price_tiers_migration(): void {
	if ( get_option( 'dorotape_migrate_price_tiers_version' ) === DOROTAPE_MIGRATE_PRICE_TIERS_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
		return;
	}

	dorotape_migrate_price_tiers();

	update_option( 'dorotape_migrate_price_tiers_version', DOROTAPE_MIGRATE_PRICE_TIERS_VERSION );
}
add_action( 'admin_init', 'dorotape_maybe_run_price_tiers_migration' );

function dorotape_migrate_price_tiers(): void {
	// Fetch all simple products that have a non-empty _price_tiers meta value.
	$product_ids = get_posts( array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'simple',
			),
		),
		'meta_query'     => array(
			array(
				'key'     => '_price_tiers',
				'value'   => '',
				'compare' => '!=',
			),
		),
	) );

	foreach ( $product_ids as $product_id ) {
		// Skip if ACF repeater already has data — don't overwrite manual entries.
		$existing = get_field( 'price_tiers', (int) $product_id );
		if ( ! empty( $existing ) ) {
			continue;
		}

		$tiers = dorotape_parse_legacy_tiers( (int) $product_id );
		if ( empty( $tiers ) ) {
			continue;
		}

		// Filter out the base-rate entry (min_qty = 1) — it equals the WC regular
		// price and adds no information to the ACF repeater.
		$tiers = array_values(
			array_filter( $tiers, static function ( array $t ): bool {
				return (int) $t['min_qty'] > 1;
			} )
		);

		if ( empty( $tiers ) ) {
			continue;
		}

		// Build the repeater rows using field keys so ACF saves correctly
		// regardless of whether the field group has been synced in this environment.
		$rows = array_map( static function ( array $t ): array {
			return array(
				'field_dorotape_tier_min_qty' => (int) $t['min_qty'],
				'field_dorotape_tier_price'   => (float) $t['tier_price'],
			);
		}, $tiers );

		update_field( 'field_dorotape_price_tiers', $rows, (int) $product_id );
	}
}
