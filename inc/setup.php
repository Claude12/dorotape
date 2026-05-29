<?php
/**
 * One-time site setup — WooCommerce product categories and global attributes.
 *
 * Bump DOROTAPE_SETUP_VERSION to re-run on next admin_init.
 *
 * @package dorotape
 */

define( 'DOROTAPE_SETUP_VERSION', '1.0.0' );

/**
 * Run setup when stored version doesn't match the current constant.
 * Bails early if WooCommerce isn't active yet.
 */
function dorotape_maybe_run_setup(): void {
	if ( get_option( 'dorotape_setup_version' ) === DOROTAPE_SETUP_VERSION ) {
		return;
	}
	if ( ! function_exists( 'wc_create_attribute' ) ) {
		return;
	}

	dorotape_setup_product_categories();
	dorotape_setup_product_attributes();

	update_option( 'dorotape_setup_version', DOROTAPE_SETUP_VERSION );

	// Flush rewrite rules on the next init — deferred to avoid doing it mid-request.
	set_transient( 'dorotape_flush_rewrite_rules', true, MINUTE_IN_SECONDS * 5 );
}
add_action( 'admin_init', 'dorotape_maybe_run_setup' );

/**
 * Flush rewrite rules once after setup, then remove the transient.
 */
function dorotape_maybe_flush_rewrite_rules(): void {
	if ( get_transient( 'dorotape_flush_rewrite_rules' ) ) {
		flush_rewrite_rules( false );
		delete_transient( 'dorotape_flush_rewrite_rules' );
	}
}
add_action( 'init', 'dorotape_maybe_flush_rewrite_rules', 99 );

// ─── Product Categories ───────────────────────────────────────────────────────

/**
 * Create the 8 top-level WooCommerce product categories defined in the sitemap.
 * Idempotent — skips any category whose slug already exists.
 * Subcategories are added once product data is confirmed with the client.
 */
function dorotape_setup_product_categories(): void {
	$categories = array(
		array(
			'name'        => 'Sign & Display Vinyl',
			'slug'        => 'sign-display-vinyl',
			'description' => 'Self-adhesive vinyl films for sign making and display applications.',
		),
		array(
			'name'        => 'Glass Decoration Films',
			'slug'        => 'glass-decoration-films',
			'description' => 'Decorative and functional self-adhesive films for glass surfaces.',
		),
		array(
			'name'        => 'Application Tape',
			'slug'        => 'application-tape',
			'description' => 'Transfer tapes and application tools for vinyl installation.',
		),
		array(
			'name'        => 'Digital Print Media',
			'slug'        => 'digital-print-media',
			'description' => 'Printable self-adhesive media for digital printing applications.',
		),
		array(
			'name'        => 'Laminating Films',
			'slug'        => 'laminating-films',
			'description' => 'Protective and decorative laminating films for printed materials.',
		),
		array(
			'name'        => 'Stencil Masking Films',
			'slug'        => 'stencil-masking-films',
			'description' => 'Precision stencil and masking films for industrial and craft applications.',
		),
		array(
			'name'        => 'Garment Decoration Films',
			'slug'        => 'garment-decoration-films',
			'description' => 'Heat transfer and decoration films for textile and garment applications.',
		),
		array(
			'name'        => 'Tools & Accessories',
			'slug'        => 'tools-accessories',
			'description' => 'Professional tools and accessories for film application.',
		),
	);

	foreach ( $categories as $category ) {
		if ( term_exists( $category['slug'], 'product_cat' ) ) {
			continue;
		}

		wp_insert_term(
			$category['name'],
			'product_cat',
			array(
				'slug'        => $category['slug'],
				'description' => $category['description'],
			)
		);
	}
}

// ─── Global Product Attributes ────────────────────────────────────────────────

/**
 * Create the global WooCommerce product attributes used across all product types.
 *
 * - Colour   → Product identifier (each colour = a product)
 * - Width    → Drives ACF-based dynamic pricing (width_options repeater)
 * - Roll Type → Drives ACF-based dynamic pricing (roll_options repeater)
 * - Finish   → Descriptive attribute (Gloss, Matt, Satin, etc.)
 *
 * Idempotent — checks existing attributes by slug before creating.
 */
function dorotape_setup_product_attributes(): void {
	// Note: 'width' (pa_width) and 'roll-type' (pa_roll-type) were removed here
	// after the ACF-based pricing model was replaced by CSV import + _price_tiers meta.
	// The existing DB records for those two attributes should be deleted manually
	// via WooCommerce → Attributes in the admin.
	$attributes = array(
		array(
			'name'         => 'Colour',
			'slug'         => 'colour',
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		),
		array(
			'name'         => 'Finish',
			'slug'         => 'finish',
			'type'         => 'select',
			'order_by'     => 'name',
			'has_archives' => false,
		),
	);

	$existing_slugs = array_column( wc_get_attribute_taxonomies(), 'attribute_name' );

	foreach ( $attributes as $attribute ) {
		if ( in_array( $attribute['slug'], $existing_slugs, true ) ) {
			continue;
		}

		$result = wc_create_attribute( $attribute );

		if ( is_wp_error( $result ) ) {
			// Log silently — non-fatal, admin can create manually if needed.
			error_log( 'Dorotape setup: failed to create attribute "' . $attribute['slug'] . '" — ' . $result->get_error_message() );
		}
	}

	// Immediately register new attribute taxonomies so WooCommerce is aware this request.
	if ( class_exists( 'WC_Post_Types' ) ) {
		WC_Post_Types::register_taxonomies();
	}
}
