<?php
/**
 * Target state for the 88 products Michael specified on 5 August 2026.
 *
 * This file is data only. round3_build.php reads it and does the writing.
 *
 * HOW TO READ AN ENTRY
 *
 *   id        the parent product post ID
 *   expect    its current title, asserted before anything is written, so the
 *             script stops rather than rebuild the wrong product if IDs move
 *   title     new title, when Michael asked for a rename
 *   unit      _dt_price_unit for the parent: metre, roll or item
 *   step      _dt_qty_step for the parent, 0 to clear it
 *   taxonomy  the variation axis. Omit it for a single-option product, which is
 *             built as a simple product, the shape Gold Turbo (#594) and
 *             Kernowjet (#443) already use.
 *   options   one per line the customer can buy:
 *               label  term name on the axis, '' for a simple product
 *               sku    the Sage stock code, which is what woosage sends
 *               price  the site price
 *               sage   the price the same code carries in the Sage export
 *               why    required whenever price and sage differ, so no
 *                      discrepancy can pass silently
 *               breaks quantity tiers as min => price
 *               unit   per-option override, for products that mix metres and
 *                      sheets on one dropdown
 *   keep      published variation IDs to leave alone. Anything published and not
 *             in options or keep is retired.
 *   note      why this entry looks the way it does.
 *
 * WHAT IS DELIBERATELY NOT HERE
 *
 * Options are only removed where Michael said to remove them. Where his sheet
 * simply listed fewer widths than the site sells, the extra widths stay and the
 * build reports them, because a spreadsheet listing two of five widths is not an
 * instruction to delete three sellable lines. Poly Canvas is the case in point:
 * his sheet gave 500mm and 760mm, so those two get his prices and 1067mm,
 * 1370mm and 1520mm are left exactly as they are.
 *
 * @package dorotape
 */

/**
 * Per-metre rate and one break, the shape most of the range takes.
 *
 * @param string $sku   Sage stock code.
 * @param float  $rate  Per-metre price.
 * @param int    $min   Quantity the break starts at.
 * @param float  $break Discounted per-metre price.
 * @param float  $sage  Sage price, defaults to the rate.
 * @param string $why   Required if the rate differs from Sage.
 * @return array
 */
function dt_r3_metre( string $sku, float $rate, int $min = 0, float $break = 0.0, float $sage = -1.0, string $why = '' ): array {
	return array(
		'sku'    => $sku,
		'price'  => $rate,
		'sage'   => $sage < 0 ? $rate : $sage,
		'why'    => $why,
		'breaks' => $min ? array( $min => $break ) : array(),
	);
}

$aslan_ct = array(); // The 19 transparent colours, all identical but for the code.
foreach ( array(
	11355 => array( 811, 'Yellow' ),
	11356 => array( 812, 'Golden Yellow' ),
	11357 => array( 813, 'Orange' ),
	11359 => array( 814, 'Dark Red' ),
	11360 => array( 815, 'Violet' ),
	11364 => array( 816, 'Mint Green' ),
	11368 => array( 817, 'Dove Grey' ),
	11369 => array( 818, 'Medium Grey' ),
	11371 => array( 819, 'Bright Yellow' ),
	11373 => array( 820, 'Bright Red' ),
	11374 => array( 821, 'Red' ),
	11377 => array( 822, 'Magenta' ),
	11379 => array( 823, 'Ultra Blue' ),
	11381 => array( 824, 'Azure Blue' ),
	11383 => array( 826, 'Light Blue' ),
	11385 => array( 827, 'Medium Green' ),
	11388 => array( 828, 'Apple Green' ),
	11392 => array( 829, 'Blue' ),
	11395 => array( 830, 'Purple' ),
) as $code => list( $id, $colour ) ) {
	$aslan_ct[] = array(
		'id'      => $id,
		'expect'  => "ASLAN CT$code $colour Transparent Vinyl",
		'unit'    => 'metre',
		'step'    => 5,
		'options' => array( '' => dt_r3_metre( "CT{$code}1250", 7.62, 25, 6.86 ) ),
		'note'    => 'Michael: sell per metre in 5m steps with a break at 25m. The 5m and 25m fixed rolls go. Break rate is the old 25m roll price of 171.50 over 25.',
	);
}

$glitter = array(); // One code per colour, one width, already per metre on the site.
foreach ( array(
	986 => array( 'GL9', 'Black' ),
	972 => array( 'GL11', 'Blue' ),
	979 => array( 'GL17', 'Butter' ),
	971 => array( 'GL10', 'Copper' ),
	982 => array( 'GL5', 'Gold' ),
	974 => array( 'GL12', 'Green' ),
	975 => array( 'GL13', 'Magenta' ),
	977 => array( 'GL15', 'Navy Blue' ),
	985 => array( 'GL8', 'Red' ),
	980 => array( 'GL18', 'Salmon' ),
	984 => array( 'GL7', 'Silver' ),
	983 => array( 'GL6', 'Yellow' ),
) as $id => list( $code, $colour ) ) {
	$glitter[] = array(
		'id'      => $id,
		'expect'  => "$colour Glitter",
		'unit'    => 'metre',
		'step'    => 0,
		'options' => array(
			'' => dt_r3_metre( $code, 34.64, 30, 32.90, 34.64, '' ),
		),
		'note'    => 'Per metre with a break at 30m. Break rate is the old 30m roll price of 987.00 over 30. The fixed 30m roll line goes.',
	);
}

$optima = array(); // Built to match Ri-Mark Optima 405 (#1380), which Michael pointed at.
foreach ( array(
	1377 => array( 'SC402', 'Anthracite' ),
	1384 => array( 'SC412', 'Yellow' ),
	1388 => array( 'SC420', 'Orange' ),
	1424 => array( 'SC485', 'Green' ),
) as $id => list( $code, $colour ) ) {
	$num     = substr( $code, 2 );
	$optima[] = array(
		'id'       => $id,
		'expect'   => "Ri-Mark Optima $num $colour",
		'unit'     => 'metre',
		'step'     => 0,
		'taxonomy' => 'pa_width',
		'options'  => array(
			'630mm'  => dt_r3_metre( "{$code}630", 2.56, 50, 2.31 ),
			'1260mm' => dt_r3_metre( "{$code}1260", 5.12, 50, 4.62 ),
		),
		'note'     => 'Copied line for line from Ri-Mark Optima 405 (#1380), which is already built this way. The four part-roll and full-roll options go.',
	);
}

return array_merge(
	$aslan_ct,
	$glitter,
	$optima,
	array(

		// ---------------------------------------------------------------- ASLAN
		array(
			'id'       => 662,
			'expect'   => 'ASLAN 85K',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => dt_r3_metre( '85K625', 3.15, 50, 2.84 ),
				'1250mm' => dt_r3_metre( '85K1250', 6.30, 50, 5.67 ),
			),
			'note'     => 'Was a simple product priced at the 625mm rate with the 1250mm width shown only as text. Both widths are now buyable.',
		),
		array(
			'id'       => 835,
			'expect'   => 'ASLAN D160 Diffuser',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'675mm'  => dt_r3_metre( 'D160675', 7.04, 50, 6.34 ),
				'1350mm' => dt_r3_metre( 'D1601350', 14.08, 50, 12.67 ),
			),
			'note'     => 'The site already had 6.34 but at 25m. Michael asked for the break at 50m, so it moves at the same rate.',
		),
		array(
			'id'       => 747,
			'expect'   => 'ASLAN EL300 Dry Apply Glass Etch',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'630mm'  => dt_r3_metre( 'EL300630', 5.85, 50, 5.27 ),
				'1260mm' => dt_r3_metre( 'EL3001260', 11.70, 50, 10.53 ),
				'1520mm' => dt_r3_metre( 'EL3001520', 14.14, 50, 12.73 ),
			),
			'note'     => 'Three widths, was simple at the 630mm rate.',
		),
		array(
			'id'       => 720,
			'expect'   => 'ASLAN S62',
			'unit'     => 'metre',
			'step'     => 10,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'416mm'  => array(
					'sku'    => 'S62416',
					'price'  => 8.00,
					'sage'   => 8.00,
					'why'    => '',
					'breaks' => array( 30 => 7.04, 60 => 6.80, 120 => 6.40 ),
				),
				'625mm'  => array(
					'sku'    => 'S62625',
					'price'  => 12.00,
					'sage'   => 12.00,
					'why'    => '',
					'breaks' => array( 30 => 10.56, 60 => 10.20, 120 => 9.60 ),
				),
				'1250mm' => array(
					'sku'    => 'S621250',
					'price'  => 24.00,
					'sage'   => 24.00,
					'why'    => '',
					'breaks' => array( 30 => 21.12, 60 => 20.40, 120 => 19.20 ),
				),
			),
			'note'     => 'Michael offered by the roll or in 10m steps with his existing levels. Taken as 10m steps to match the rest of the range. His levels of 12, 15 and 20 per cent off convert to breaks at 30m, 60m and 120m. The old roll tiers of 1-2, 3-5, 6-11, 12 were per roll of 10m.',
		),
		array(
			'id'       => 1344,
			'expect'   => 'ASLAN S64',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => dt_r3_metre( 'S64625', 9.52, 25, 8.57 ),
				'1250mm' => dt_r3_metre( 'S641250', 19.04, 25, 17.14 ),
			),
			'note'     => 'Was simple at the 1250mm rate with 625mm shown only as text.',
		),
		array(
			'id'       => 1345,
			'expect'   => 'ASLAN S66',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => dt_r3_metre( 'S66625', 7.00, 25, 6.30 ),
				'1250mm' => dt_r3_metre( 'S661250', 14.00, 25, 12.60 ),
			),
			'note'     => 'Was simple at the 625mm rate.',
		),
		array(
			'id'       => 1366,
			'expect'   => 'ASLAN S68',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => dt_r3_metre( 'S68625', 5.87, 25, 5.28 ),
				'1250mm' => dt_r3_metre( 'S681250', 11.74, 25, 10.57 ),
			),
			'note'     => 'Was simple at the 1250mm rate.',
		),
		array(
			'id'       => 1343,
			'expect'   => 'ASLAN S41',
			'unit'     => 'roll',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => array(
					'sku'    => 'S41625',
					'price'  => 122.25,
					'sage'   => 4.89,
					'why'    => 'Sage prices S41 per metre. Michael asked for it by the roll, and the roll is 25m: 25 x 4.89 = 122.25.',
					'breaks' => array(),
				),
				'1250mm' => array(
					'sku'    => 'S411250',
					'price'  => 244.50,
					'sage'   => 9.78,
					'why'    => 'Sage prices S41 per metre. Michael asked for it by the roll, and the roll is 25m: 25 x 9.78 = 244.50, which is the price the site already showed.',
					'breaks' => array(),
				),
			),
			'note'     => 'Roll length of 25m was worked out from the site price of 244.50 against the Sage rate of 9.78.',
		),
		array(
			'id'       => 947,
			'expect'   => 'ASLAN FF410 Self-adhesive Ferrous Film',
			'unit'     => 'roll',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'1010mm' => array(
					'sku'    => 'FF410FERRO',
					'price'  => 218.00,
					'sage'   => 218.00,
					'why'    => '',
					'breaks' => array(),
				),
				'1370mm' => array(
					'sku'    => 'FF410FERRO1370',
					'price'  => 221.90,
					'sage'   => 221.90,
					'why'    => '',
					'breaks' => array(),
				),
			),
			'note'     => 'Two rolls of different lengths, 1010mm x 12m and 1370mm x 9m. Was simple at the 1370mm price with the 1010mm roll not buyable.',
		),
		array(
			'id'      => 1142,
			'expect'  => 'ASLAN MP326 Floor Protect Laminate',
			'unit'    => 'metre',
			'step'    => 5,
			'options' => array( '' => dt_r3_metre( 'MP3261370', 12.32, 25, 8.62 ) ),
			'note'    => 'One width, so it becomes a simple per-metre product. Break rate is the site 25m roll price of 215.60 over 25.',
		),
		array(
			'id'      => 734,
			'expect'  => 'ASLAN SL99 Graffiti Repellent Laminate',
			'unit'    => 'metre',
			'step'    => 10,
			'options' => array( '' => dt_r3_metre( 'SL991370', 17.48, 50, 15.73 ) ),
			'note'    => 'One width. Break rate is the site 50m roll price of 786.60 over 50. The parent price was showing 0.00.',
		),
		array(
			'id'       => 736,
			'expect'   => 'ASLAN SRL96 rPET Anti-Graffiti Laminate',
			'unit'     => 'metre',
			'step'     => 10,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'1370mm' => array(
					'sku'    => 'SRL96',
					'price'  => 14.68,
					'sage'   => 14.24,
					'why'    => 'Michael gave a new price of 14.68 on his sheet. Sage still shows 14.24.',
					'breaks' => array( 50 => 13.21 ),
				),
				'1520mm' => array(
					'sku'    => 'SRL961520',
					'price'  => 16.34,
					'sage'   => 15.86,
					'why'    => 'Michael gave a new price of 16.34 on his sheet. Sage still shows 15.86.',
					'breaks' => array( 50 => 14.70 ),
				),
			),
			'note'     => 'Michael wrote SRL961370, but the 1370mm code in Sage is plain SRL96. He also wrote 1502 where we read 1520. The two 1520mm variations currently have no SKU at all.',
		),
		array(
			'id'       => 885,
			'expect'   => 'ASLAN DFP08 UltraTack Matt',
			'unit'     => 'metre',
			'step'     => 10,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'1370mm'               => dt_r3_metre( 'DFP081370', 8.40, 50, 7.56 ),
				'1370mm Grey Adhesive' => dt_r3_metre( 'DFP08G1370', 8.74, 50, 7.87 ),
			),
			'note'     => 'Built to match DFP07 exactly, as Michael asked. He answered DFP08; the Sage code is DFP081370. The two grey adhesive variations currently have no SKU.',
		),
		array(
			'id'      => 737,
			'expect'  => 'ASLAN TF200 Clear PET Super Tack Application Tape',
			'unit'    => 'roll',
			'step'    => 0,
			'options' => array(
				'' => array(
					'sku'    => 'TF200',
					'price'  => 40.60,
					'sage'   => 39.40,
					'why'    => 'Michael gave a new price of 40.60. Sage still shows 39.40.',
					'breaks' => array( 5 => 36.54 ),
				),
			),
			'note'    => 'Sold by the roll of 1370mm x 10m. The 10m quantity step it carried made no sense for a roll product and is cleared.',
		),
		array(
			'id'       => 1481,
			'expect'   => 'ASLAN Blockout Film',
			'title'    => 'ASLAN W15 White Blockout Film',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => dt_r3_metre( 'W15625', 11.44, 25, 10.30 ),
				'1250mm' => dt_r3_metre( 'W151250', 22.88, 25, 20.60 ),
			),
			'note'     => 'Michael asked for this to split into W15 and W16. This entry is the W15 half; round3_blockout_split.php creates the W16 product, and the entry below holds it to the same shape from then on. The old SKU 1004 is not a Sage code. He gave no break, so the 25m break of 20.60 the site already had is kept and 10.30 is the same ten per cent on the 625mm rate.',
		),
		array(
			// The W16 half. Created by round3_blockout_split.php rather than here,
			// because this file rebuilds products that exist and W16 did not. It is
			// listed anyway so one file still describes the finished state of every
			// product, and so a later run notices if anything drifts off it.
			'id'       => 7436,
			'expect'   => 'ASLAN W16 Black Blockout Film',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'625mm'  => dt_r3_metre( 'W16625', 11.44, 25, 10.30 ),
				'1250mm' => dt_r3_metre( 'W161250', 22.88, 25, 20.60 ),
			),
			'note'     => 'Sage prices W16 exactly as W15, so the widths and the 25m break match its twin. The page still uses the W15 photograph and datasheet; no W16 asset exists yet.',
		),

		// ------------------------------------------------------------- per metre
		array(
			'id'       => 808,
			'expect'   => 'D-View Micro Perforated Vinyl',
			'unit'     => 'metre',
			'step'     => 5,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'1370mm' => dt_r3_metre( 'D-VIEW1370', 10.18, 50, 9.67 ),
				'1520mm' => dt_r3_metre( 'D-VIEW1520', 11.94, 50, 11.34 ),
			),
			'note'     => 'The four fixed roll options go. The 5m lines had placeholder SKUs.',
		),
		array(
			'id'       => 6580,
			'expect'   => 'Digi-Fab',
			'unit'     => 'metre',
			'step'     => 15,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'1067mm' => dt_r3_metre( 'DIGI-FAB1067', 9.32, 30, 8.39 ),
				'1370mm' => dt_r3_metre( 'DIGI-FAB1370', 11.98, 30, 10.78 ),
				'1524mm' => dt_r3_metre( 'DIGI-FAB1524', 13.30, 30, 11.97 ),
			),
			'note'     => 'INFERRED REMOVAL: his sheet lists three width options, so the 500mm line comes off. He did not say to remove it in as many words, so it is flagged rather than assumed.',
		),
		array(
			'id'      => 1304,
			'expect'  => 'FF200 Printable Ferrous Film (Non adhesive) :: 1260mm Wide',
			'title'   => 'FF200 Printable Ferrous Film (Non adhesive) :: 1270mm Wide',
			'unit'    => 'metre',
			'step'    => 25,
			'options' => array( '' => dt_r3_metre( 'FF200FERRO', 7.60, 50, 7.22 ) ),
			'note'    => 'Sage calls this 1270mm and the site said 1260mm, so the title is corrected to match Sage. FF200FERRO25 exists but is marked obsolete.',
		),
		array(
			'id'       => 987,
			'expect'   => 'GlassApeel',
			'unit'     => 'metre',
			'step'     => 10,
			'taxonomy' => 'pa_finish',
			'options'  => array(
				'Transparent' => dt_r3_metre( 'GLASSAPPCLEAR', 9.30, 30, 8.37 ),
				'White'       => dt_r3_metre( 'GLASSAPPWHITE', 10.50, 30, 9.45 ),
				'Frosted'     => dt_r3_metre( 'GLASSAPPFROST', 9.30, 30, 8.37 ),
				'Solar UV'    => dt_r3_metre( 'GLASSAPPSOLAR', 12.75, 30, 11.47 ),
			),
			'note'     => 'One product, four finishes, all 1370mm. The eight fixed 10m and 30m roll options collapse to four per-metre lines with the 30m break.',
		),
		array(
			'id'       => 1009,
			'expect'   => 'Image Flex Pro Soft White 4030',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'500mm' => dt_r3_metre( 'IMAPSW500', 6.64, 25, 5.98 ),
				'750mm' => dt_r3_metre( 'IMAPSW750', 9.96, 25, 8.96 ),
			),
			'note'     => 'Was simple at the 750mm rate carrying SKU 4030, which is not a Sage code. IMAPSW4030 exists in Sage at 0.00 and is not used.',
		),
		array(
			'id'       => 1010,
			'expect'   => 'Image Flex Turbo Print 4036',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'500mm' => dt_r3_metre( 'IMATP500', 7.04, 25, 6.34 ),
				'750mm' => dt_r3_metre( 'IMATP750', 10.56, 25, 9.50 ),
			),
			'note'     => 'Was simple at the 500mm rate carrying SKU 4036, which is not a Sage code.',
		),
		array(
			'id'       => 1008,
			'expect'   => 'Image Flex Turbo Print Sub Block 4010',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'500mm' => dt_r3_metre( 'IMAPSB500', 8.65, 25, 7.79 ),
				'750mm' => dt_r3_metre( 'IMAPSB750', 12.99, 25, 11.69 ),
			),
			'note'     => 'Was simple at the 750mm rate carrying SKU 4010, which is not a Sage code.',
		),
		array(
			'id'       => 1321,
			'expect'   => 'Reeded Glass Film :: 1520mm Wide',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_finish',
			'options'  => array(
				'6mm Flutes'  => dt_r3_metre( 'RGF061520', 18.80, 50, 16.92 ),
				'12mm Flutes' => dt_r3_metre( 'RGF121520', 18.80, 50, 16.92 ),
			),
			'note'     => 'Two flute sizes at the same price. Was simple carrying RGF, which is not a Sage code. The 16.92 break was already stored on the product, so no new number was needed from Michael.',
		),
		array(
			'id'       => 1428,
			'expect'   => 'Slate Charcoal Non-reflective Film :: 1524mm Width',
			'title'    => 'Automotive Charcoal Window Tint :: 1524mm Width',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_option',
			'options'  => array(
				'SC05' => dt_r3_metre( 'SC051524', 9.30, 30, 8.37 ),
				'SC35' => dt_r3_metre( 'SC351524', 9.30, 30, 8.37 ),
				'SC50' => dt_r3_metre( 'SC501524', 9.30, 30, 8.37 ),
			),
			'note'     => 'Renamed as Michael asked. The SKU was the literal string "SC05 / SC35 / SC50", which woosage would have sent to Sage as one unusable code. Sage also holds 508mm versions of all three; the 760mm versions are marked obsolete. Neither is being built, since his answer says three options.',
		),
		array(
			'id'       => 647,
			'expect'   => 'Yellow Fluorescent',
			'title'    => 'Oracal 7510 Yellow Fluorescent',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'630mm'  => dt_r3_metre( 'ORA7510630', 14.93, 50, 13.44 ),
				'1260mm' => dt_r3_metre( 'ORA75101260', 29.86, 50, 26.87 ),
			),
			'note'     => 'Michael emailed ORA75101220 and ORA7510610, neither of which is in the export. The pair on his sheet are, and match the site prices to the penny, so his sheet wins. The break is the ten per cent he used everywhere else. The four part-roll and full-roll options go.',
		),
		array(
			'id'      => 1502,
			'expect'  => 'Ri-Lam C30 Ultra Clear Vehicle Wrap Laminate',
			'unit'    => 'metre',
			'step'    => 5,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'750mm'  => dt_r3_metre( 'VEH750', 7.36, 50, 6.62 ),
				'1370mm' => dt_r3_metre( 'VEH1370', 12.04, 50, 10.84 ),
				'1524mm' => dt_r3_metre( 'VEH1524', 13.36, 50, 12.02 ),
			),
			'note'     => 'The real fault here was the parent SKU: plain VEH, which Sage marks DO NOT USE, OBSOLETE, and the only obsolete code in use anywhere on the catalogue. A variable parent SKU is never sent to Sage, so it is cleared rather than replaced with an invented code. The three widths keep their own valid codes. Their breaks were sitting at 46m on two of the three; they move to 50m to match the rest of the range.',
		),
		array(
			'id'      => 6502,
			'expect'  => 'Ritrama Ri-Jet M150 White Static Cling Vinyl',
			'unit'    => 'metre',
			'step'    => 25,
			'options' => array( '' => dt_r3_metre( 'RJM1501400', 4.36, 50, 3.92 ) ),
			'note'    => 'Already per metre in 25m steps. Only the 50m break was missing.',
		),
		array(
			'id'      => 602,
			'expect'  => 'Silver Turbo Heat Transfer Vinyl',
			'unit'    => 'metre',
			'step'    => 0,
			'taxonomy' => 'pa_size-format',
			'options'  => array(
				'500mm wide per metre' => dt_r3_metre( '4930500', 6.99, 25, 6.15 ),
				// Existing labels, kept word for word so nothing visible changes on
				// options Michael has not ruled on. Only the long dash goes.
				'Size / Format - 305mm x 500mm' => dt_r3_metre( '4930305', 2.60 ),
				'Size / Format - 200mm x 500mm' => dt_r3_metre( '4930200', 1.75 ),
				'Size / Format - A4 Sheet'      => dt_r3_metre( '4930A4', 1.32 ),
			),
			'note'     => 'Michael asked for this to match Gold Turbo (#594), which is a simple per-metre product at 6.99 with a 25m break at 6.15. The 25m roll line becomes that break and the 500mm x 1000mm line goes, as it has no Sage code. NOTE FOR MICHAEL: the three sheet sizes STAY, because whether sheet sizes should be on sale at all is one of the open questions and Gold Turbo has none. They are listed here rather than left untouched so their codes get checked. Be aware that the sheet prices will read "per metre" on the page: the theme decides that once for the whole product, and the product is now per metre. It is only wording, the prices and codes are right, and it goes away whichever way he answers, either the sheets come off or the product stops being per metre. Not worth a theme change to guess ahead of him.',
		),

		// ---------------------------------------------------- widths plus sheets
		array(
			'id'       => 1422,
			'expect'   => 'Ritrama PTA Silver Etch',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_option',
			'options'  => array(
				'610mm per metre'  => dt_r3_metre( 'PTA610', 4.26, 50, 3.83 ),
				'1220mm per metre' => dt_r3_metre( 'PTA1220', 8.52, 50, 7.67 ),
				'1520mm per metre' => dt_r3_metre( 'PTA1520', 10.60, 50, 9.54 ),
				'305mm x 500mm sheet' => dt_r3_metre( 'PTA305', 3.50 ),
				'203mm x 500mm sheet' => dt_r3_metre( 'PTA203', 2.30 ),
				'A4 sheet' => dt_r3_metre( 'PTAA4', 0.75 ),
			),
			'note'     => 'Was simple carrying plain PTA, which is not a Sage code, at 8.52, the 1220mm rate. Michael gave no discount, so the 50 metre break is the ten per cent used across the range. NOTE FOR MICHAEL: the site showed 8.52 for what it called 1520mm, but in Sage PTA1520 is 10.60 and 8.52 is PTA1220. PTAAF1220 at 9.76 is the separate Airflow product (#1306) and is untouched. The three cut sheet sizes are existing lines with correct Sage codes and they stay. Their prices will read "per metre" on the page because the theme settles that once for the whole product; the figures and codes are right, and it resolves whichever way Michael answers the sheet-sizes question.',
		),
		array(
			'id'       => 1280,
			'expect'   => 'Ritrama PTAS Dark Silver Etch',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_option',
			'options'  => array(
				'610mm per metre'  => dt_r3_metre( 'PTAS610', 4.26, 50, 3.83 ),
				'1220mm per metre' => dt_r3_metre( 'PTAS1220', 8.52, 50, 7.67 ),
				'1520mm per metre' => dt_r3_metre( 'PTAS1520', 10.60, 50, 9.54 ),
				'305mm x 500mm sheet' => dt_r3_metre( 'PTAS305', 3.50 ),
				'203mm x 500mm sheet' => dt_r3_metre( 'PTAS203', 2.30 ),
				'A4 sheet' => dt_r3_metre( 'PTASA4', 0.75 ),
			),
			'note'     => 'Was simple carrying plain PTAS at 4.26, the 610mm rate. Same ten per cent break as the rest of the range. The three cut sheet sizes are existing lines with correct Sage codes and they stay. Their prices will read "per metre" on the page because the theme settles that once for the whole product; the figures and codes are right, and it resolves whichever way Michael answers the sheet-sizes question.',
		),
		array(
			'id'       => 869,
			'expect'   => 'Ritrama PTF Dusted Etch',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_option',
			'options'  => array(
				'610mm per metre'  => dt_r3_metre( 'PTF610', 4.00, 50, 3.60 ),
				'1220mm per metre' => dt_r3_metre( 'PTF1220', 8.00, 50, 7.20 ),
				'1520mm per metre' => dt_r3_metre( 'PTF1520', 9.96, 50, 8.96 ),
				'305mm x 500mm sheet' => dt_r3_metre( 'PTF305', 4.90 ),
				'203mm x 500mm sheet' => dt_r3_metre( 'PTF203', 2.30 ),
				'A4 sheet' => dt_r3_metre( 'PTFA4', 0.75 ),
			),
			'note'     => 'Was simple carrying plain PTF at 9.96, the 1520mm rate. Michael listed 1520, 1220 and 610; Sage also holds PTF500 at 5.25, which is left out because his answer names three widths. The three cut sheet sizes are existing lines with correct Sage codes and they stay. Their prices will read "per metre" on the page because the theme settles that once for the whole product; the figures and codes are right, and it resolves whichever way Michael answers the sheet-sizes question.',
		),

		// -------------------------------------------------------- by the roll
		array(
			'id'       => 1307,
			'expect'   => 'Regusign P20',
			'title'    => 'Regusign P20 Stencil Paper',
			'unit'     => 'roll',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'600mm'  => array(
					'sku'    => 'P20600',
					'price'  => 116.95,
					'sage'   => 116.95,
					'why'    => '',
					'breaks' => array(),
				),
				'1200mm' => array(
					'sku'    => 'P201200',
					'price'  => 233.90,
					'sage'   => 233.90,
					'why'    => '',
					'breaks' => array(),
				),
			),
			'note'     => 'Both rolls are 50m. Was simple at the 1200mm price carrying plain P20, which is not a Sage code.',
		),
		array(
			'id'       => 1308,
			'expect'   => 'Regusign PF2',
			'title'    => 'Regusign PF2 High Tack Stencil Paper',
			'unit'     => 'roll',
			'step'     => 0,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'600mm'  => array(
					'sku'    => 'PF2-600',
					'price'  => 101.95,
					'sage'   => 101.95,
					'why'    => '',
					'breaks' => array(),
				),
				'1200mm' => array(
					'sku'    => 'PF2-1200',
					'price'  => 203.90,
					'sage'   => 203.90,
					'why'    => '',
					'breaks' => array(),
				),
			),
			'note'     => 'Both rolls are 25m. Was simple at the 1200mm price carrying plain PF2, which is not a Sage code.',
		),
		array(
			'id'       => 1128,
			'expect'   => 'Magnetic Tape',
			'unit'     => 'roll',
			'step'     => 0,
			'taxonomy' => 'pa_option',
			'options'  => array(
				'12.7mm Type A' => array(
					'sku'    => 'MAGTAP3',
					'price'  => 14.20,
					'sage'   => 14.20,
					'why'    => '',
					'breaks' => array( 5 => 13.35 ),
				),
				'12.7mm Type B' => array(
					'sku'    => 'MAGTAP',
					'price'  => 14.20,
					'sage'   => 14.20,
					'why'    => '',
					'breaks' => array( 5 => 13.35 ),
				),
				'25.4mm Type A' => array(
					'sku'    => 'MAGTAP4',
					'price'  => 28.40,
					'sage'   => 28.40,
					'why'    => '',
					'breaks' => array( 5 => 26.70 ),
				),
				'25.4mm Type B' => array(
					'sku'    => 'MAGTAP2',
					'price'  => 28.40,
					'sage'   => 28.40,
					'why'    => '',
					'breaks' => array( 5 => 26.70 ),
				),
			),
			'note'     => 'Two widths by two polarities, all 30m rolls. The site had width, packs of five and polarity mixed on one dropdown, with the two polarity lines priced 0.00 and no way to pick a polarity for a real roll. The packs of five become the 5 roll break, at the site rates of 13.35 and 26.70 per roll. The parent SKU was plain MT, which is not a Sage code.',
		),

		// -------------------------------------------------------------- each
		array(
			'id'      => 694,
			'expect'  => 'Anti-static Cleaner',
			'unit'    => 'item',
			'step'    => 0,
			'options' => array(
				'' => array(
					'sku'    => 'ANT',
					'price'  => 11.40,
					'sage'   => 11.40,
					'why'    => '',
					'breaks' => array( 12 => 10.03 ),
				),
			),
			'note'    => 'Sold each, 500ml. The 12 x 500ml option becomes the 12 break at the rate the site already charged, 120.36 over 12.',
		),
		/*
		 * BANNER HEMMING TAPE (#6540) IS NOT HERE, AND THAT IS THE POINT.
		 *
		 * Michael gave its code as 46525HEMTAPE, which is not in the export. The
		 * obvious read was that he meant 46525, DORO-FIX 465 DOUBLE SIDED TAPE
		 * 25MM X 50M at 12.73, because that is the site price to the penny.
		 *
		 * It is not that simple. 46525 is already live and correct on a different
		 * product: Doro-Fix 465 :: 25mm x 50m (#6568), same 12.73, same 1-4 and 5
		 * break, unit already set to roll. Both listings were created on the same
		 * day in different categories, one under Banner Finishing & Fixing and one
		 * under Doro-Fix 465 :: PET Tape.
		 *
		 * So this is one stock item on two product pages, and WooCommerce will not
		 * let two products share a SKU. Moving 46525 onto #6540 would have taken it
		 * off the listing that already has it right.
		 *
		 * Michael has to say which he means: same tape sold under two names, in
		 * which case one listing goes or they merge, or a genuinely different tape
		 * that needs a stock code of its own. It has moved to the open questions.
		 */
		array(
			'id'      => 1129,
			'expect'  => 'Masking Tape :: 25mm x 50m',
			'unit'    => 'item',
			'step'    => 0,
			'options' => array(
				'' => array(
					'sku'    => 'MT1',
					'price'  => 1.00,
					'sage'   => 1.00,
					'why'    => '',
					'breaks' => array( 36 => 0.87 ),
				),
			),
			'note'    => 'The carton of 36 becomes the 36 break at the rate the site already charged, 31.32 over 36.',
		),
		array(
			'id'      => 1130,
			'expect'  => 'Masking Tape :: 50mm x 50m',
			'unit'    => 'item',
			'step'    => 0,
			'options' => array(
				'' => array(
					'sku'    => 'MT2',
					'price'  => 2.00,
					'sage'   => 2.00,
					'why'    => '',
					'breaks' => array( 24 => 1.74 ),
				),
			),
			'note'    => 'The carton of 24 becomes the 24 break, 41.76 over 24.',
		),
		array(
			'id'      => 781,
			'expect'  => 'Blue Masking Tape :: 50mm x 50m',
			'unit'    => 'item',
			'step'    => 0,
			'options' => array(
				'' => array(
					'sku'    => 'MTBLUE2',
					'price'  => 3.60,
					'sage'   => 3.60,
					'why'    => '',
					'breaks' => array( 24 => 3.24 ),
				),
			),
			'note'    => 'The carton of 24 becomes the 24 break, 77.76 over 24 = 3.24. Sage also holds MTBLUE1 at 1.80 for the 25mm width, which the site does not sell.',
		),
		array(
			'id'      => 1293,
			'expect'  => 'Pack of 5 Plastic Squeegees',
			'title'   => 'Plastic Squeegees',
			'unit'    => 'item',
			'step'    => 5,
			'options' => array(
				'' => array(
					'sku'    => 'S',
					'price'  => 0.69,
					'sage'   => 0.69,
					'why'    => '',
					'breaks' => array( 250 => 0.53 ),
				),
			),
			'note'    => 'Sage sells these singly at 0.69, code S. The site sold a pack of 5 at 3.45 and a pack of 250 at 132.50, so an order booked one pack rather than five squeegees. Now priced each in steps of 5, which keeps the pack of 5 and makes the 250 break work at 0.53 each.',
		),
		array(
			'id'       => 6633,
			'expect'   => 'Plotter Pens',
			'unit'     => 'item',
			'step'     => 0,
			'taxonomy' => 'pa_to-fit-cutter',
			'options'  => array(
				// "To fit ..." is the wording already on this attribute, so keep it.
				'To fit Roland / Mutoh cutters' => array(
					'sku'    => 'PLO1',
					'price'  => 11.50,
					'sage'   => 11.50,
					'why'    => '',
					'breaks' => array(),
				),
				'To fit Graphtec cutters'       => array(
					'sku'    => 'PLO',
					'price'  => 14.30,
					'sage'   => 14.30,
					'why'    => '',
					'breaks' => array(),
				),
				'To fit Summagraphics D series cutters' => array(
					'sku'    => 'PLO2',
					'price'  => 11.50,
					'sage'   => 11.50,
					'why'    => '',
					'breaks' => array(),
				),
			),
			'note'     => 'Sage puts Roland and Mutoh on one pen, PLO1, so the two separate site options both carried the same code, which WooCommerce cannot allow. They merge into one line. PLO2 for Summagraphics was not on sale at all. The parent SKU was the literal string "PLO PLO1 PLO2", which woosage would have sent to Sage as PLO_PLO1_PLO2.',
		),
		array(
			'id'       => 1510,
			'expect'   => 'Stabilo Aquarellable Pencils',
			'title'    => 'Chinagraph Aquarellable Pencils',
			'unit'     => 'item',
			'step'     => 0,
			'taxonomy' => 'pa_colour',
			'options'  => array(
				'Black'  => array(
					'sku'    => 'CHIBLACK',
					'price'  => 16.90,
					'sage'   => 16.90,
					'why'    => '',
					'breaks' => array(),
				),
				'Blue'   => array(
					'sku'    => 'CHIBLUE',
					'price'  => 16.90,
					'sage'   => 16.90,
					'why'    => '',
					'breaks' => array(),
				),
				'White'  => array(
					'sku'    => 'CHIW',
					'price'  => 16.90,
					'sage'   => 16.90,
					'why'    => '',
					'breaks' => array(),
				),
				'Yellow' => array(
					'sku'    => 'CHIYELLOW',
					'price'  => 16.90,
					'sage'   => 16.90,
					'why'    => '',
					'breaks' => array(),
				),
				'Mixed pack, four colours' => array(
					'sku'    => 'CHIMIX',
					'price'  => 16.90,
					'sage'   => 16.90,
					'why'    => '',
					'breaks' => array(),
				),
			),
			'note'     => 'Renamed as Michael asked. Every option is a pack of 12 at 16.90. The old SKU was plain CHI, which is not a Sage code.',
		),

		// ------------------------------------------------- option removals only
		array(
			'id'       => 6552,
			'expect'   => 'Universal Canvas',
			'unit'     => 'metre',
			'step'     => 25,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'1067mm' => dt_r3_metre( 'CANUNI1067', 6.54, 30, 5.95 ),
				'1370mm' => dt_r3_metre( 'CANUNI1370', 8.44, 30, 7.68 ),
				'1520mm' => dt_r3_metre( 'CANUNI1520', 9.34, 30, 8.50 ),
			),
			'note'     => 'Already built correctly. The only change Michael asked for is that the 500mm and 760mm options come off, which frees CANUNI500 and CANUNI760.',
		),
		array(
			'id'       => 801,
			'expect'   => 'Ritrama CF01 Pink Fluorescent',
			'unit'     => 'metre',
			'step'     => 0,
			'taxonomy' => 'pa_size-format',
			'options'  => array(
				// Michael asked for two width options, so the width goes in the label.
				// The old one for CF01610 was "part roll - price per mtr" with no width
				// in it at all, which is the thing he was objecting to.
				'610mm wide per metre'          => dt_r3_metre( 'CF01610', 7.69 ),
				'1220mm wide per metre'         => dt_r3_metre( 'CF011220', 15.38 ),
				// Cut sizes keep their exact wording, minus the long dash.
				'Size / Format - 500mm x 1m'    => dt_r3_metre( 'CF01500', 7.55 ),
				'Size / Format - 305mm x 1m'    => dt_r3_metre( 'CF01305', 4.25 ),
				'Size / Format - 203mm x 1m'    => dt_r3_metre( 'CF01203', 2.85 ),
				"or 'Sheet' size here - A4"     => dt_r3_metre( 'CF01A4', 0.95 ),
			),
			'retire'   => array( 2148 ),
			'note'     => 'INFERRED REMOVAL: his email says two width options rather than roll and per metre, so the full 25m roll line (#2148, no Sage code) comes off. Everything else is an existing line with a correct Sage code, listed here so the codes get checked and so the two widths get labels that actually say the width. Three of the four cut sizes are 1m lengths, so "per metre" reads correctly on them anyway; only the A4 is odd, and the same open question covers it as covers Silver Turbo.',
		),
		array(
			'id'       => 6543,
			'expect'   => 'Poly Canvas',
			'unit'     => 'metre',
			'step'     => 25,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'500mm' => dt_r3_metre( 'CANPOL500', 1.61 ),
				'760mm' => dt_r3_metre( 'CANPOL760', 2.41 ),
			),
			'keep'     => array( 7283, 7284, 7285 ),
			'note'     => 'His sheet gave only 500mm and 760mm and repeated the same price at 50m, so those two get no break. DELIBERATELY NOT REMOVED: 1067mm, 1370mm and 1520mm are left exactly as they are. His sheet listing two widths is not an instruction to delete three sellable lines, and Universal Canvas shows he says so explicitly when he means it.',
		),
		array(
			'id'      => 1334,
			'expect'  => 'Ri-Jet C50 Ultimate Slide & Tack Airflow',
			'unit'    => 'metre',
			'step'    => 5,
			'taxonomy' => 'pa_width',
			'options'  => array(
				'760mm'  => dt_r3_metre( 'RJ50STA/F760', 7.36, 50, 6.62 ),
				'1370mm' => dt_r3_metre( 'RJ50STA/F1370', 12.04, 50, 10.84 ),
				'1524mm' => dt_r3_metre( 'RJ50STA/F1524', 13.36, 50, 12.02 ),
			),
			'note'     => 'Already rebuilt to exactly this shape in an earlier pass, so this entry should report no change. It is kept in the spec so the target is recorded rather than assumed.',
		),

		// ---------------------------------------------------------- deletions
		array(
			'id'     => 1125,
			'expect' => 'M8 Bright Yellow',
			'retire_product' => true,
			'note'   => 'Michael asked for this to go. Its SKU was the literal string "Cover Styl\' M87" on the parent and M8 on the full roll line.',
		),
		array(
			'id'     => 531,
			'expect' => 'Green Flock Heat Transfer Vinyl',
			'retire_product' => true,
			'note'   => 'Michael asked for this to go. Its SKU was 400, which is not a Sage code.',
		),
		array(
			'id'     => 925,
			'expect' => 'Easy Dot PET Silver L-UV',
			'retire_product' => true,
			'note'   => 'Michael asked for this to go. It is already in the trash, so this entry should report no change.',
		),
	)
);
