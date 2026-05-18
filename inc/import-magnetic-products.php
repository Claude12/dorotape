<?php
/**
 * Programmatic import: Magnetic Ferrous Vinyl product range (14 products).
 *
 * Idempotent — bump DOROTAPE_IMPORT_MAGNETIC_VERSION to re-run.
 * Uses the same admin_init / version-check pattern as inc/setup.php.
 *
 * Pricing model:
 *  - Per-metre products: WooCommerce price = price per linear metre; customer
 *    qty = metres ordered. No roll_options ACF rows needed.
 *  - Fixed-roll products (one size): roll_price = full roll price (= WC base).
 *  - Fixed-roll products (multiple sizes): WC price = smallest roll; each
 *    roll_options row stores the actual total price as roll_price.
 *  - Width-variable products: WC price = per-metre rate at narrowest width;
 *    wider widths store their actual price_per_metre in width_options.
 *
 * @package dorotape
 */

define( 'DOROTAPE_IMPORT_MAGNETIC_VERSION', '1.0.1' );

/**
 * Guard: only run when stored version is outdated and dependencies are present.
 */
function dorotape_maybe_run_magnetic_import(): void {
	if ( get_option( 'dorotape_import_magnetic_version' ) === DOROTAPE_IMPORT_MAGNETIC_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
		return;
	}

	dorotape_import_magnetic_products();

	update_option( 'dorotape_import_magnetic_version', DOROTAPE_IMPORT_MAGNETIC_VERSION );
}
// Disabled for dev — client will supply full product CSV.
// add_action( 'admin_init', 'dorotape_maybe_run_magnetic_import' );

// ─── Orchestration ────────────────────────────────────────────────────────────

function dorotape_import_magnetic_products(): void {
	$cat_id = dorotape_ensure_magnetic_category();
	if ( ! $cat_id ) {
		return;
	}

	foreach ( dorotape_magnetic_product_data() as $data ) {
		dorotape_upsert_magnetic_product( $data, $cat_id );
	}
}

// ─── Category ─────────────────────────────────────────────────────────────────

function dorotape_ensure_magnetic_category(): int {
	$slug     = 'magnetic-ferrous-vinyl';
	$existing = get_term_by( 'slug', $slug, 'product_cat' );

	if ( $existing ) {
		return (int) $existing->term_id;
	}

	$result = wp_insert_term(
		'Magnetic Ferrous Vinyl',
		'product_cat',
		array(
			'slug'        => $slug,
			'description' => 'Self-adhesive and non-adhesive ferrous films for magnetic graphics and display systems.',
		)
	);

	return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
}

// ─── Product upsert ───────────────────────────────────────────────────────────

/**
 * Create or update a single product and populate all ACF pricing / spec fields.
 * Matches on SKU so re-running the import updates rather than duplicates.
 */
function dorotape_upsert_magnetic_product( array $data, int $cat_id ): int {
	$existing_id = wc_get_product_id_by_sku( $data['sku'] );

	if ( $existing_id ) {
		$product = wc_get_product( $existing_id );
		if ( ! $product ) {
			return 0;
		}
	} else {
		$product = new WC_Product_Simple();
	}

	$product->set_name( $data['name'] );
	$product->set_sku( $data['sku'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( (string) $data['price'] );
	$product->set_category_ids( array( $cat_id ) );

	if ( isset( $data['short_description'] ) ) {
		$product->set_short_description( $data['short_description'] );
	}
	if ( isset( $data['description'] ) ) {
		$product->set_description( $data['description'] );
	}

	$product_id = $product->save();
	if ( ! $product_id ) {
		return 0;
	}

	// ACF pricing repeaters — field keys used so ACF skips name-lookup.
	if ( ! empty( $data['width_options'] ) ) {
		$width_rows = array_map( static function ( array $r ): array {
			return array(
				'field_dorotape_width_value' => $r['width_value'] ?? 0,
				'field_dorotape_width_label' => $r['width_label'] ?? '',
				'field_dorotape_width_price' => $r['price_per_metre'] ?? 0,
			);
		}, $data['width_options'] );
		update_field( 'field_dorotape_width_options', $width_rows, $product_id );
	}
	if ( ! empty( $data['roll_options'] ) ) {
		$roll_rows = array_map( static function ( array $r ): array {
			return array(
				'field_dorotape_roll_label'  => $r['roll_label'] ?? '',
				'field_dorotape_roll_length' => $r['roll_length'] ?? 1,
				'field_dorotape_roll_price'  => $r['roll_price'] ?? 0,
			);
		}, $data['roll_options'] );
		update_field( 'field_dorotape_roll_options', $roll_rows, $product_id );
	}

	// ACF spec tab fields.
	foreach ( ( $data['specs'] ?? array() ) as $field_name => $field_value ) {
		update_field( $field_name, $field_value, $product_id );
	}

	// Flag per-metre products so the archive price display can append "/m".
	if ( ! empty( $data['per_metre'] ) ) {
		update_post_meta( $product_id, '_dorotape_per_metre', '1' );
	}

	return $product_id;
}

// ─── Product data ─────────────────────────────────────────────────────────────

/**
 * Returns the full product dataset for the Magnetic Ferrous Vinyl category.
 *
 * ACF field names match group_dorotape_product_options.json and
 * group_dorotape_product_specs.json exactly.
 *
 * Width values are in mm; roll lengths in metres; prices in GBP.
 */
function dorotape_magnetic_product_data(): array {
	return array(

		// ── Fixed-roll, single size ───────────────────────────────────────────

		array(
			'sku'               => 'DORO-9054',
			'name'              => 'Doro 9054 — Magnetic Ferro Vinyl SA White Gloss',
			'price'             => 146.00,
			'short_description' => 'Self-adhesive white gloss magnetic ferrous vinyl. 1250mm × 10m roll.',
			'description'       => '<p>Doro 9054 is a self-adhesive white gloss ferrous film that accepts standard magnetic graphics. Ideal for creating magnetic whiteboards, display panels, and signage substrates. Supplied as a 1250mm × 10m roll.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1250,
					'width_label'    => '1250mm',
					'price_per_metre' => 0,
				),
			),
			'roll_options'      => array(
				array(
					'roll_label'  => '10m Roll',
					'roll_length' => 10,
					'roll_price'  => 146.00,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

		array(
			'sku'               => 'DORO-9056',
			'name'              => 'Doro 9056 — Ferrous Vinyl PVC-Free PET',
			'price'             => 263.50,
			'short_description' => 'PVC-free PET ferrous film. 1270mm × 30m roll.',
			'description'       => '<p>Doro 9056 is a 0.3mm PVC-free PET-based ferrous film suited to eco-conscious magnetic display projects. Supplied as a 1270mm × 30m roll.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1270,
					'width_label'    => '1270mm',
					'price_per_metre' => 0,
				),
			),
			'roll_options'      => array(
				array(
					'roll_label'  => '30m Roll',
					'roll_length' => 30,
					'roll_price'  => 263.50,
				),
			),
			'specs'             => array(
				'thickness'               => '0.3mm',
				'application_environment' => 'indoor_outdoor',
			),
		),

		array(
			'sku'               => 'FF600',
			'name'              => 'FF600 — Ferrous Film Plain Grey SA',
			'price'             => 105.90,
			'short_description' => 'Self-adhesive plain grey ferrous film. 1000mm × 10m roll.',
			'description'       => '<p>FF600 is a self-adhesive plain grey ferrous film that creates a magnetic-receptive surface on non-magnetic substrates. Suitable for wallcovering and display panel applications. Supplied as a 1000mm × 10m roll.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1000,
					'width_label'    => '1000mm',
					'price_per_metre' => 0,
				),
			),
			'roll_options'      => array(
				array(
					'roll_label'  => '10m Roll',
					'roll_length' => 10,
					'roll_price'  => 105.90,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

		array(
			'sku'               => 'ASLAN-FF480',
			'name'              => 'ASLAN FF480 — Printable SA Ferrous Film',
			'price'             => 267.00,
			'short_description' => 'Printable self-adhesive ferrous film for digital printing. 1010mm × 12m roll.',
			'description'       => '<p>ASLAN FF480 is a self-adhesive ferrous film with a white printable surface compatible with eco-solvent, solvent, and UV flatbed printers. Creates a magnetic-receptive substrate for printed display systems. Supplied as a 1010mm × 12m roll.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1010,
					'width_label'    => '1010mm',
					'price_per_metre' => 0,
				),
			),
			'roll_options'      => array(
				array(
					'roll_label'  => '12m Roll',
					'roll_length' => 12,
					'roll_price'  => 267.00,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

		array(
			'sku'               => 'ASLAN-MEMO-CS',
			'name'              => 'ASLAN Memoboard Colour Selectors',
			'price'             => 1.50,
			'short_description' => 'Sample colour selectors for the ASLAN Memoboard ferrous film range.',
			'description'       => '<p>Colour selector samples for the ASLAN Memoboard range of ferrous films. Request samples to choose the right colour before committing to a full roll order.</p>',
			'specs'             => array(),
		),

		// ── Per-metre products (customer qty = metres ordered) ────────────────
		// No roll_options rows — the dynamic pricing hook does not modify the
		// cart price; WooCommerce quantity × base_price gives the correct total.

		array(
			'sku'               => 'DORO-9050',
			'name'              => 'Doro 9050 — Magnetic Ferro Vinyl SA White Gloss 630mm',
			'per_metre'         => true,
			'price'             => 8.99,
			'short_description' => 'Self-adhesive white gloss magnetic ferrous vinyl, 630mm wide. Price per linear metre.',
			'description'       => '<p>Doro 9050 is a self-adhesive white gloss ferrous film at 630mm width. Sold by the linear metre — set cart quantity to the number of metres required. Thickness: 0.85mm. Adhesion: 53 g/cm².</p>',
			'width_options'     => array(
				array(
					'width_value'    => 630,
					'width_label'    => '630mm',
					'price_per_metre' => 0,
				),
			),
			'specs'             => array(
				'thickness'               => '0.85mm',
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

		array(
			'sku'               => 'DORO-9052-WG',
			'name'              => 'Doro 9052 — Magnetic Ferro Vinyl SA White Gloss 1000mm',
			'per_metre'         => true,
			'price'             => 11.47,
			'short_description' => 'Self-adhesive white gloss ferrous film, 1000mm wide. Fire rated B-s1-d0. Price per linear metre.',
			'description'       => '<p>Doro 9052 White Gloss is a self-adhesive ferrous vinyl at 1000mm width. Thickness: 0.6mm. Adhesion: 28 g/cm². Fire classification: B-s1-d0. Sold by the linear metre.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1000,
					'width_label'    => '1000mm',
					'price_per_metre' => 0,
				),
			),
			'specs'             => array(
				'thickness'               => '0.6mm',
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
				'certifications'          => 'Fire classification B-s1-d0',
			),
		),

		array(
			'sku'               => 'ASLAN-FF490',
			'name'              => 'ASLAN FF490 — Matt Dry Wipe SA Ferrous Film 1370mm',
			'per_metre'         => true,
			'price'             => 58.54,
			'short_description' => 'Matt dry-wipe self-adhesive ferrous film, 1370mm wide. Price per linear metre.',
			'description'       => '<p>ASLAN FF490 combines a matt dry-wipe surface with a self-adhesive ferrous backing. Accepts standard dry-wipe markers and magnetic accessories. 1370mm wide. Sold by the linear metre.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1370,
					'width_label'    => '1370mm',
					'price_per_metre' => 0,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

		array(
			'sku'               => 'ASLAN-FF540',
			'name'              => 'ASLAN FF540 — Blackboard SA Ferrous Film 1250mm (PVC-Free)',
			'per_metre'         => true,
			'price'             => 53.70,
			'short_description' => 'PVC-free blackboard self-adhesive ferrous film, 1250mm wide. Price per linear metre.',
			'description'       => '<p>ASLAN FF540 is a PVC-free self-adhesive ferrous film with a blackboard surface. Accepts chalk markers and magnetic accessories. 1250mm wide. Sold by the linear metre.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1250,
					'width_label'    => '1250mm',
					'price_per_metre' => 0,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

		// ── Fixed-roll, multiple roll sizes ───────────────────────────────────
		// Base price = smallest roll. Larger rolls use a 'fixed' modifier
		// equal to the additional cost (larger_price − base_price).

		array(
			'sku'               => 'GREEN-POWER-4',
			'name'              => 'Green Power 4 — Non-SA Ferrous Film PVC-Free 1270mm',
			'price'             => 72.50,
			'short_description' => 'PVC-free non-adhesive ferrous film, 1270mm wide. Available in 5m or 10m rolls.',
			'description'       => '<p>Green Power 4 is a PVC-free non-adhesive ferrous film for eco-conscious magnetic display systems. Thickness: 0.5mm. Adhesion: 55 g/cm². 1270mm wide, available in 5m or 10m rolls.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1270,
					'width_label'    => '1270mm',
					'price_per_metre' => 0,
				),
			),
			'roll_options'      => array(
				array(
					'roll_label'  => '5m Roll',
					'roll_length' => 5,
					'roll_price'  => 72.50,
				),
				array(
					'roll_label'  => '10m Roll',
					'roll_length' => 10,
					'roll_price'  => 145.00,
				),
			),
			'specs'             => array(
				'thickness'               => '0.5mm',
				'application_environment' => 'indoor_outdoor',
			),
		),

		array(
			'sku'               => 'FF200',
			'name'              => 'FF200 — Printable Ferrous Film Non-SA Iron-Based 1260mm',
			'price'             => 190.00,
			'short_description' => 'Iron-based PVC-free non-adhesive printable ferrous film, 1260mm wide. Available in 25m or 50m rolls.',
			'description'       => '<p>FF200 is an iron-based PVC-free non-adhesive ferrous film with a white printable surface. Compatible with eco-solvent and UV inkjet printers. 1260mm wide, available in 25m or 50m rolls.</p>',
			'width_options'     => array(
				array(
					'width_value'    => 1260,
					'width_label'    => '1260mm',
					'price_per_metre' => 0,
				),
			),
			'roll_options'      => array(
				array(
					'roll_label'  => '25m Roll',
					'roll_length' => 25,
					'roll_price'  => 190.00,
				),
				array(
					'roll_label'  => '50m Roll',
					'roll_length' => 50,
					'roll_price'  => 361.00,
				),
			),
			'specs'             => array(
				'application_environment' => 'indoor',
			),
		),

		// ── Width-variable, per-metre products ────────────────────────────────
		// Pricing scales linearly with width. Base price = per-metre rate at
		// the narrowest width. The width_options 'percentage' modifier covers
		// the price difference at wider widths.
		// Customer qty = metres; roll_options are not needed.

		array(
			'sku'               => 'DORO-9052-WM',
			'name'              => 'Doro 9052 — Magnetic Ferro Vinyl SA White Matt',
			'per_metre'         => true,
			'price'             => 11.47,
			'short_description' => 'Self-adhesive white matt ferrous film. Available in 1000mm (per metre) and 1260mm × 10m. Fire rated B-s1-d0.',
			'description'       => '<p>Doro 9052 White Matt is a self-adhesive matt ferrous vinyl. Thickness: 0.6mm. Adhesion: 28 g/cm². Fire classification: B-s1-d0.</p>
<ul>
<li>1000mm — sold by the linear metre at £11.47/m</li>
<li>1260mm — typically supplied as a 10m roll at £129.30</li>
</ul>',
			'width_options'     => array(
				array(
					'width_value'    => 1000,
					'width_label'    => '1000mm',
					'price_per_metre' => 0,        // Use WC base price (£11.47/m).
				),
				array(
					'width_value'    => 1260,
					'width_label'    => '1260mm',
					'price_per_metre' => 12.93,
				),
			),
			'specs'             => array(
				'thickness'               => '0.6mm',
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
				'certifications'          => 'Fire classification B-s1-d0',
			),
		),

		array(
			'sku'               => 'ASLAN-FF410',
			'name'              => 'ASLAN FF410 — PVC-Free SA Ferrous Film',
			'per_metre'         => true,
			'price'             => 17.47,
			'short_description' => 'PVC-free self-adhesive ferrous film. Fire rated C-s1-d0. Available in 1010mm and 1370mm widths.',
			'description'       => '<p>ASLAN FF410 is a PVC-free self-adhesive ferrous film with fire classification C-s1-d0.</p>
<ul>
<li>1010mm — £17.47/m (£209.60 per 12m roll)</li>
<li>1370mm — £23.70/m (£213.30 per 9m roll)</li>
</ul>',
			'width_options'     => array(
				array(
					'width_value'    => 1010,
					'width_label'    => '1010mm',
					'price_per_metre' => 0,        // Use WC base price (£17.47/m).
				),
				array(
					'width_value'    => 1370,
					'width_label'    => '1370mm',
					'price_per_metre' => 23.70,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor_outdoor',
				'certifications'          => 'Fire classification C-s1-d0',
			),
		),

		array(
			'sku'               => 'ASLAN-FF550',
			'name'              => 'ASLAN FF550 — Dry Wipe Whiteboard SA Ferrous Film',
			'per_metre'         => true,
			'price'             => 40.37,
			'short_description' => 'Whiteboard dry-wipe self-adhesive ferrous film. Available in 1010mm and 1370mm widths.',
			'description'       => '<p>ASLAN FF550 is a self-adhesive ferrous film with a white dry-wipe whiteboard surface. Accepts standard dry-wipe markers and magnetic accessories.</p>
<ul>
<li>1010mm — £40.37/m (£484.40 per 12m roll)</li>
<li>1370mm — £52.69/m (£158.07 per 3m roll)</li>
</ul>',
			'width_options'     => array(
				array(
					'width_value'    => 1010,
					'width_label'    => '1010mm',
					'price_per_metre' => 0,        // Use WC base price (£40.37/m).
				),
				array(
					'width_value'    => 1370,
					'width_label'    => '1370mm',
					'price_per_metre' => 52.69,
				),
			),
			'specs'             => array(
				'adhesive_type'           => 'Permanent Self-Adhesive',
				'application_environment' => 'indoor',
			),
		),

	);
}
