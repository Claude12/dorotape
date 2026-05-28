<?php
/**
 * Programmatic import: Ritrama F-Sign Platinum Range — 60 colours.
 *
 * Builds the three-level category hierarchy and imports one WooCommerce
 * Simple product per colour, using ACF pricing repeaters.
 *
 * Category hierarchy:
 *   Sign & Display Vinyl        ← exists (setup.php)
 *     └─ Signmaking Vinyl       ← created here
 *         └─ Ritrama F-Sign Platinum Range  ← created here
 *
 * Pricing model (all products):
 *   Base price  = per-linear-metre rate at 610mm, Standard adhesive.
 *   Width 1220mm → price_per_metre = 2 × base (stored as actual £/m value).
 *   25m roll    → roll_price = £61.87 (£76.00 Anthracite; £103.50 Black 50m).
 *   The engine scales roll_price to wider widths: roll_price × (width_price / base_price).
 *   Airflow adhesive is flagged in colour data (has_airflow) but deferred to
 *   Sprint 2, when WooCommerce variation attributes are wired to the frontend.
 *
 * Bump DOROTAPE_IMPORT_RITRAMA_PLATINUM_VERSION to re-run.
 *
 * @package dorotape
 */

define( 'DOROTAPE_IMPORT_RITRAMA_PLATINUM_VERSION', '1.0.8' );

function dorotape_maybe_run_ritrama_platinum_import(): void {
	if ( get_option( 'dorotape_import_ritrama_platinum_version' ) === DOROTAPE_IMPORT_RITRAMA_PLATINUM_VERSION ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
		return;
	}

	dorotape_import_ritrama_platinum();

	update_option( 'dorotape_import_ritrama_platinum_version', DOROTAPE_IMPORT_RITRAMA_PLATINUM_VERSION );
}
// Disabled: F-Sign Platinum products are now managed via the CSV migration import.
// add_action( 'admin_init', 'dorotape_maybe_run_ritrama_platinum_import' );

// ─── Orchestration ────────────────────────────────────────────────────────────

function dorotape_import_ritrama_platinum(): void {
	$cat_id = dorotape_ensure_ritrama_platinum_categories();
	if ( ! $cat_id ) {
		return;
	}

	$range_description = '<p>The Ritrama F-Sign Platinum range is a 65 micron calendered polymeric self-adhesive signmaking vinyl designed for demanding external applications. Permanent solvent-based adhesive delivers up to 10 years outdoor durability on flat, curved, 3D convex, and uneven surfaces. Suitable for vehicle graphics, architectural signage, and advertising displays.</p>'
		. '<p>Available in 72 colours across 610mm and 1220mm widths. Select colours are available with Airflow (Easy Dot) adhesive for bubble-free dry application. Fire classified Euroclass B-s1-d0.</p>';

	$range_specs = array(
		'thickness'               => '65 micron (0.065mm)',
		'adhesive_type'           => 'Permanent solvent-based',
		'application_environment' => 'outdoor',
		'durability'              => 'Up to 10 years (8 years most colours; 5 years Anthracite P856)',
		'certifications'          => 'Fire classification B-s1-d0 (DIN EN 13501-1:2019-05)',
	);

	$hex_map = dorotape_ritrama_platinum_colour_hex();

	foreach ( dorotape_ritrama_platinum_colours() as $colour ) {
		$colour['colour_hex'] = $hex_map[ $colour['code'] ] ?? '';
		dorotape_upsert_ritrama_platinum_product( $colour, $cat_id, $range_description, $range_specs );
	}
}

// ─── Category hierarchy ───────────────────────────────────────────────────────

function dorotape_ensure_ritrama_platinum_categories(): int {
	// Top level: Sign & Display Vinyl — must already exist from setup.php.
	$top = get_term_by( 'slug', 'sign-display-vinyl', 'product_cat' );
	if ( ! $top ) {
		return 0;
	}

	// Mid level: Signmaking Vinyl.
	$mid    = get_term_by( 'slug', 'signmaking-vinyl', 'product_cat' );
	$mid_id = $mid ? (int) $mid->term_id : dorotape_insert_product_cat(
		'Signmaking Vinyl',
		'signmaking-vinyl',
		'Self-adhesive coloured vinyl for professional sign making, vehicle graphics, and display applications.',
		(int) $top->term_id
	);
	if ( ! $mid_id ) {
		return 0;
	}

	// Leaf: Ritrama F-Sign Platinum Range.
	$leaf = get_term_by( 'slug', 'ritrama-f-sign-platinum', 'product_cat' );
	if ( $leaf ) {
		return (int) $leaf->term_id;
	}

	return dorotape_insert_product_cat(
		'Ritrama F-Sign Platinum Range',
		'ritrama-f-sign-platinum',
		'10-year 65 micron polymeric signmaking vinyl in 72 colours. Fire rated B-s1-d0. Standard and Airflow adhesive options.',
		$mid_id
	);
}

function dorotape_insert_product_cat( string $name, string $slug, string $description, int $parent ): int {
	$result = wp_insert_term( $name, 'product_cat', compact( 'slug', 'description', 'parent' ) );
	return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
}

// ─── Colour group helper ──────────────────────────────────────────────────────

/**
 * Map a Ritrama colour code to a colour group slug used by the filter bar.
 * Covers all 60 colours in the range so the full import works without changes.
 */
function dorotape_ritrama_colour_group( string $code ): string {
	if ( 'P801' === $code ) {
		return 'black';
	}
	$num = (int) substr( $code, 1 );
	if ( $num >= 804 && $num <= 809 ) return 'yellows';
	if ( $num >= 810 && $num <= 812 ) return 'oranges';
	if ( $num >= 813 && $num <= 822 ) return 'reds';
	if ( $num >= 823 && $num <= 826 ) return 'colours'; // pinks / purples / magentas
	if ( $num >= 827 && $num <= 840 ) return 'blues';
	if ( $num >= 841 && $num <= 851 ) return 'greens';
	if ( $num >= 852 && $num <= 854 ) return 'browns';
	if ( $num >= 855 && $num <= 868 ) return 'greys';
	return 'colours';
}

// ─── Product upsert ───────────────────────────────────────────────────────────

function dorotape_upsert_ritrama_platinum_product(
	array $colour,
	int $cat_id,
	string $range_description,
	array $range_specs
): int {
	$sku         = 'RIT-' . $colour['code'];
	$existing_id = wc_get_product_id_by_sku( $sku );

	if ( $existing_id ) {
		$product = wc_get_product( $existing_id );
		if ( ! $product ) {
			return 0;
		}
	} else {
		$product = new WC_Product_Simple();
	}

	$roll_label = 50 === $colour['roll_metres'] ? '50m Roll' : '25m Roll';

	$product->set_name( 'F-Sign Platinum ' . $colour['code'] . ' ' . $colour['name'] );
	$product->set_sku( $sku );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( (string) $colour['price_per_m'] );
	$product->set_category_ids( array( $cat_id ) );
	$product->set_short_description(
		'Ritrama F-Sign Platinum ' . $colour['code'] . ' ' . $colour['name']
		. '. 65µm polymeric sign vinyl, up to 10-year outdoor durability.'
		. ' Available in 610mm and 1220mm widths, per metre or full ' . strtolower( $roll_label ) . '.'
		. ( $colour['has_airflow'] ? ' Airflow (Easy Dot) adhesive also available.' : '' )
	);
	$product->set_description( $range_description );

	$product_id = $product->save();
	if ( ! $product_id ) {
		return 0;
	}

	// Width options — field keys bypass ACF name-lookup so values always save.
	// 1220mm price = 2× the 610mm base; 610mm left blank (engine uses WC price).
	update_field(
		'field_dorotape_width_options',
		array(
			array(
				'field_dorotape_width_value' => 610,
				'field_dorotape_width_label' => '610mm',
				'field_dorotape_width_price' => 0,
			),
			array(
				'field_dorotape_width_value' => 1220,
				'field_dorotape_width_label' => '1220mm',
				'field_dorotape_width_price' => round( $colour['price_per_m'] * 2, 2 ),
			),
		),
		$product_id
	);

	// Roll options — roll_price is the total at the base (610mm) width.
	// The engine scales to wider widths: roll_price × (width_price / base_price).
	update_field(
		'field_dorotape_roll_options',
		array(
			array(
				'field_dorotape_roll_label'  => 'Per Metre',
				'field_dorotape_roll_length' => 1,
				'field_dorotape_roll_price'  => 0,
			),
			array(
				'field_dorotape_roll_label'  => $roll_label,
				'field_dorotape_roll_length' => (float) $colour['roll_metres'],
				'field_dorotape_roll_price'  => $colour['roll_price'],
			),
		),
		$product_id
	);

	// Range-level spec fields — same values for every colour in the range.
	foreach ( $range_specs as $field => $value ) {
		update_field( $field, $value, $product_id );
	}

	// Colour swatch — saved via ACF so the colour picker field in the admin
	// shows the correct value. The template reads it via get_post_meta(),
	// which works whether saved through ACF or directly.
	if ( ! empty( $colour['colour_hex'] ) ) {
		update_field( 'colour_hex', sanitize_hex_color( $colour['colour_hex'] ), $product_id );
	}

	// Filter meta — written via update_post_meta() so they are saved regardless
	// of whether the ACF field group has been synced in the current environment.
	// The filter bar and meta_query both read raw post meta, not ACF fields.
	update_post_meta( $product_id, 'colour_group', dorotape_ritrama_colour_group( $colour['code'] ) );
	update_post_meta( $product_id, 'finish', 'gloss' );
	// Store adhesive as a serialised array so the LIKE query in product-filters.php
	// can match individual values (e.g. LIKE '%"airflow"%').
	update_post_meta( $product_id, 'adhesive_properties', $colour['has_airflow'] ? array( 'standard', 'airflow' ) : array( 'standard' ) );

	return $product_id;
}

// ─── Colour data ──────────────────────────────────────────────────────────────

/**
 * Returns 10 representative dev colours for the Ritrama F-Sign Platinum range.
 * Covers standard pricing, special pricing (Black, Anthracite), and airflow flag.
 * Replace with the full 60-colour dataset once the client CSV is confirmed.
 *
 * Pricing notes:
 *   Standard (£2.75/m): 25m roll = £61.87
 *   P856 Anthracite (£3.37/m): 25m roll = £76.00
 *   P801 Black (£2.30/m): 50m roll = £103.50
 */
function dorotape_ritrama_platinum_colours(): array {
	$c = 'dorotape_ritrama_colour'; // local alias for readability

	return array(
		$c( 'P804', 'Pastel Yellow' ),             // standard
		$c( 'P811', 'Orange' ),                    // standard
		$c( 'P813', 'Poppy Red' ),                 // standard
		$c( 'P819', 'Red',             true ),     // airflow available
		$c( 'P823', 'Process Magenta', true ),     // airflow available
		$c( 'P827', 'Midnight Blue' ),             // standard
		$c( 'P838', 'Ocean Blue' ),                // standard
		$c( 'P844', 'Forest Green' ),              // standard
		$c( 'P801', 'Black', true, array(          // special: £2.30/m, 50m roll = £103.50
			'price_per_m' => 2.30,
			'roll_metres' => 50,
			'roll_price'  => 103.50,
		) ),
		$c( 'P856', 'Anthracite', true, array(     // special: £3.37/m, 25m roll = £76.00
			'price_per_m' => 3.37,
			'roll_price'  => 76.00,
		) ),
	);
}

/**
 * Colour entry factory — merges defaults with any per-colour overrides.
 *
 * @param string $code      Ritrama colour code (e.g. 'P806').
 * @param string $name      Colour name (e.g. 'Bright Yellow').
 * @param bool   $airflow   Whether an Airflow adhesive variant exists (Sprint 2).
 * @param array  $overrides Any fields that differ from the range defaults.
 */
function dorotape_ritrama_colour(
	string $code,
	string $name,
	bool $airflow = false,
	array $overrides = array()
): array {
	return array_merge(
		array(
			'code'        => $code,
			'name'        => $name,
			'price_per_m' => 2.75,
			'roll_metres' => 25,
			'roll_price'  => 61.87, // Standard 25m roll at 610mm: £2.75 × 22.498 ≈ £61.87
			'has_airflow' => $airflow,
			'colour_hex'  => '',
		),
		$overrides
	);
}

// ─── Colour hex map ───────────────────────────────────────────────────────────

/**
 * Approximate sRGB hex values for each Ritrama F-Sign Platinum colour.
 * Used to render CSS colour swatches in place of missing product images.
 * Values are representative; exact RAL/Pantone matches should be uploaded
 * as product images in Sprint 2.
 */
function dorotape_ritrama_platinum_colour_hex(): array {
	return array(
		// Yellows & Oranges
		'P804' => '#f5e882',
		'P805' => '#f5d000',
		'P806' => '#ffe000',
		'P807' => '#ffc800',
		'P808' => '#c89000',
		'P809' => '#cc7800',
		'P810' => '#f08020',
		'P811' => '#e86010',
		'P812' => '#d03800',
		// Reds
		'P813' => '#d41020',
		'P814' => '#cc0000',
		'P815' => '#c82020',
		'P816' => '#bc1010',
		'P817' => '#a80000',
		'P818' => '#7c0000',
		'P819' => '#c80028',
		'P820' => '#780020',
		'P821' => '#600018',
		'P822' => '#500010',
		// Pinks, Purples & Magentas
		'P823' => '#cc0058',
		'P824' => '#800050',
		'P825' => '#500038',
		'P826' => '#500078',
		// Blues
		'P827' => '#080830',
		'P828' => '#081050',
		'P829' => '#182868',
		'P830' => '#0c2070',
		'P832' => '#003068',
		'P833' => '#0032b8',
		'P834' => '#0038c0',
		'P835' => '#0060b8',
		'P836' => '#1048a0',
		'P837' => '#0048b8',
		'P838' => '#0068b8',
		'P839' => '#0048a0',
		'P840' => '#4090c0',
		// Teals & Greens
		'P841' => '#005870',
		'P842' => '#003850',
		'P843' => '#006050',
		'P844' => '#185020',
		'P845' => '#183818',
		'P846' => '#007838',
		'P847' => '#008838',
		'P848' => '#20b838',
		'P849' => '#38a020',
		'P850' => '#58b818',
		'P851' => '#88b818',
		// Browns & Beiges
		'P852' => '#e8d8b8',
		'P853' => '#582818',
		'P854' => '#3c1408',
		// Black
		'P801' => '#1a1a1a',
		// Greys
		'P855' => '#787888',
		'P856' => '#2c2c34',
		'P857' => '#888890',
		'P858' => '#484850',
		'P859' => '#686870',
		'P860' => '#606070',
		'P868' => '#c8c8c8',
		'P861' => '#888888',
		'P862' => '#b0b0b8',
	);
}
