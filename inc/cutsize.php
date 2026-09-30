<?php
/**
 * Cut Sizes field
 *
 * Products migrated from Kryptronic with a CUTSIZE option group carry
 * _dt_cutsize_enabled=1 post meta (application tapes, digital print rolls...).
 * On the old site this was a free-text box on the product page; the customer
 * lists the cut sizes they need and the warehouse cuts the roll to match.
 *
 * Flow: a table of rolls inside the add-to-cart form -> one cart line per
 * roll (identical cut lists merge) -> cart/checkout display -> order line
 * item meta for admin, emails, and Sage sync.
 *
 * @package dorotape
 */

// ─── Wording (Theme Settings > Cut Sizes) ─────────────────────────────────────

/**
 * The options page slug, which is also what the field group's location rule
 * matches on.
 */
const DOROTAPE_CUTSIZE_SLUG = 'dorotape-cut-sizes';

/**
 * The ACF post id the cut-size wording is stored under.
 */
const DOROTAPE_CUTSIZE_ID = 'dorotape_cut_sizes';

/**
 * Register the options page under Theme Settings.
 *
 * Registered in PHP rather than as an ACF UI options page so it travels with
 * the theme: a fresh install has the page the moment the theme is active, with
 * no JSON to sync and no database row to import. Autoloaded because ACF writes
 * a wp_options row per field and the box is on the site's busiest page type.
 */
add_action(
	'acf/init',
	function (): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'      => __( 'Cut Sizes', 'dorotape' ),
				'menu_title'      => __( 'Cut Sizes', 'dorotape' ),
				'menu_slug'       => DOROTAPE_CUTSIZE_SLUG,
				'parent_slug'     => 'theme-settings',
				'post_id'         => DOROTAPE_CUTSIZE_ID,
				'capability'      => 'edit_posts',
				'autoload'        => true,
				'update_button'   => __( 'Save cut size wording', 'dorotape' ),
				'updated_message' => __( 'Cut size wording saved. Every product with the box now uses it.', 'dorotape' ),
			)
		);
	}
);

/**
 * Read a line of wording from the Cut Sizes options page.
 *
 * The default is the wording the site shipped with, so the box reads correctly
 * on a fresh install, before an editor has opened the page, and on any install
 * without ACF. An editor clearing a field gets the default back rather than a
 * blank label, which is the useful behaviour for a field that names a column.
 *
 * @param string $name    Field name.
 * @param string $default Wording to use until an editor saves something else.
 */
function dorotape_cutsize_text( string $name, string $default ): string {
	$value = function_exists( 'get_field' ) ? get_field( $name, DOROTAPE_CUTSIZE_ID ) : null;

	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $default;
}

/**
 * sprintf() on an editable format, without letting a typo take a page down.
 *
 * PHP 8 throws when a format asks for more arguments than it is handed, and
 * these formats are now editable: an editor adding a third %s to a two-value
 * line would fatal every product page with the box on it. On a throw the
 * shipped format is used instead, which always matches the values the site
 * has. Both arguments arrive escaped, because the caller knows whether its
 * output is going into markup or into a notice.
 *
 * @param string $template Format from the field.
 * @param string $fallback The shipped format.
 * @param mixed  ...$args  Values for the placeholders.
 */
function dorotape_cutsize_sprintf( string $template, string $fallback, ...$args ): string {
	try {
		return vsprintf( $template, $args );
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
		// The editor's format wants a value the site does not have.
	}

	try {
		return vsprintf( $fallback, $args );
	} catch ( \Throwable $e ) {
		return $fallback;
	}
}

/**
 * The quick-add sizes, as a list of millimetre values.
 *
 * Authored as a comma-separated line rather than a repeater: it is three or
 * four numbers, and a row-per-number editor is more work to fill in than the
 * thing it is editing. Anything that is not a positive whole number is dropped,
 * so a stray comma or a trailing "mm" cannot render an empty button.
 *
 * @return int[]
 */
function dorotape_cutsize_presets(): array {
	$raw = dorotape_cutsize_text( 'cutsize_presets', '150, 305, 610' );

	return array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) ) );
}

/**
 * The wording cut-size-rows.js writes into the page as the customer types.
 *
 * The box already carries its roll widths on data attributes, so the strings
 * travel the same way rather than through a second script handle and a
 * wp_localize_script call: one place to look, and nothing to enqueue. The
 * placeholders are PHP's own (%1$s, %2$s), so a field reads the same whether
 * the server or the browser is the one filling it in; the JS has a small
 * formatter for them.
 *
 * @return array<string,string>
 */
function dorotape_cutsize_js_strings(): array {
	return array(
		/* translators: 1: width left over in mm, 2: roll width in mm */
		'left'     => dorotape_cutsize_text( 'cutsize_msg_left', '%1$smm of the %2$smm width left over.' ),
		/* translators: %s: how much wider than the roll the cuts are, in mm */
		'over'     => dorotape_cutsize_text( 'cutsize_msg_over', '%smm more than the roll holds.' ),
		/* translators: 1: total of the cuts in mm, 2: roll width in mm */
		'rowError' => dorotape_cutsize_text( 'cutsize_msg_row_error', 'Cuts add up to %1$smm, wider than the roll (%2$smm)' ),
		/* translators: %s: width left on the roll, in mm */
		'rollLeft' => dorotape_cutsize_text( 'cutsize_msg_roll_left', '%smm left' ),
		/* translators: %s: how much wider than the roll the cuts are, in mm */
		'rollOver' => dorotape_cutsize_text( 'cutsize_msg_roll_over', '%smm over' ),
		/* translators: 1: number of rolls with cut sizes entered, 2: quantity ordered */
		'tooMany'  => dorotape_cutsize_text( 'cutsize_msg_too_many', 'You have entered cut sizes for %1$s rolls but are only ordering %2$s. Remove a roll, or order more.' ),
		/* translators: %s: roll number */
		'rollLabel' => dorotape_cutsize_text( 'cutsize_label_roll', 'Roll %s' ),
	);
}

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * True when the product (or its parent for variations) has the cut-size box.
 *
 * @param WC_Product $product
 * @return bool
 */
function dorotape_has_cutsize( WC_Product $product ): bool {
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return '1' === get_post_meta( $id, '_dt_cutsize_enabled', true );
}

/**
 * True when the quantity ordered is a count of physical rolls, so the box can
 * speak of "roll 1, roll 2" and the add can be split into a line per roll.
 *
 * False on a product priced by the metre, where the quantity is a length: the
 * site has no way to know how many physical rolls 50m becomes (_dt_roll_length_m
 * is unset right across the catalogue), so there is one roll's width to describe
 * and nothing to number. Also false on a stepped product, where a per-roll line
 * of quantity 1 is not a valid multiple and WooCommerce rounds it up.
 *
 * The box template and woocommerce_add_to_cart_validation both read this, so
 * what the customer is offered and what the server will do with it cannot drift
 * apart. dorotape_qty_step() resolves a variation to its parent itself, so a
 * variable parent and any of its variations answer alike.
 *
 * @param WC_Product $product Simple product, variable parent or variation.
 * @return bool
 */
function dorotape_cutsize_per_roll( WC_Product $product ): bool {
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return 'roll' === dorotape_price_unit( $id ) && 1 === dorotape_qty_step( $product );
}

// ─── Admin toggle (Product data → Advanced) ───────────────────────────────────

/**
 * Checkbox so shop managers can enable the cut-size box on any product.
 * Stored as _dt_cutsize_enabled post meta ('1' when on), the same key the
 * migration set for products that had the box on the old site.
 */
add_action( 'woocommerce_product_options_advanced', function (): void {
	woocommerce_wp_checkbox(
		array(
			'id'          => '_dt_cutsize_enabled',
			'cbvalue'     => '1', // matches the migrated meta value
			'label'       => __( 'Cut sizes box', 'dorotape' ),
			'description' => __( 'Show a "Please enter cut sizes if required" text box on the product page. The customer\'s note appears on the order.', 'dorotape' ),
		)
	);
} );

/**
 * Persist the checkbox. WooCommerce posts 'yes' when ticked; we store '1'
 * to stay compatible with the migrated meta and delete when off.
 *
 * @param WC_Product $product
 */
add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	if ( isset( $_POST['_dt_cutsize_enabled'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
		$product->update_meta_data( '_dt_cutsize_enabled', '1' );
	} else {
		$product->delete_meta_data( '_dt_cutsize_enabled' );
	}
} );

// ─── Roll width detection ─────────────────────────────────────────────────────

/**
 * Roll width in mm for a product/variation.
 *
 * Prefers the _dt_roll_width_mm field (see inc/rollsize.php). Falls back to
 * parsing the variation's attribute label or the product name ("Roll size
 * 1220mm x 91.4m" -> 1220, "1370mm x 50m Roll" -> 1370), which is where the
 * width lived before the field existed and still does on most products, so
 * the title stays load-bearing until a width is entered against the product.
 *
 * Cut pieces cannot exceed the roll width.
 *
 * @param WC_Product $product Product or variation.
 * @return int|null Width in mm, or null when no width is stated.
 */
function dorotape_cutsize_max_width( WC_Product $product ): ?int {
	$stated = dorotape_roll_width_mm( $product->get_id() );
	if ( $stated ) {
		return $stated;
	}

	$haystack = $product->get_name();
	if ( $product->is_type( 'variation' ) ) {
		$haystack = implode( ' ', $product->get_attributes() ) . ' ' . $haystack;
	}
	if ( preg_match( '/(\d{2,4})\s*mm/i', $haystack, $m ) ) {
		$width = (int) $m[1];
		return $width >= 50 ? $width : null; // ignore stray small numbers
	}
	return null;
}

/**
 * The picking-list note for one roll, e.g. "2 x 500mm, 1 x 220mm".
 *
 * @param array[] $cuts As built by dorotape_cutsize_posted_rolls().
 * @return string
 */
function dorotape_cutsize_note( array $cuts ): string {
	// Not a Theme Settings field, unlike the rest of the box's wording. This
	// string is not page copy: it is what the warehouse cuts from and what
	// Woosage posts to Sage as the line's addon text. An editor rewording it
	// would silently change the data a picker reads off a works order, so it
	// stays where changing it takes a developer and a deploy.
	$parts = array();
	foreach ( $cuts as $cut ) {
		$parts[] = sprintf(
			/* translators: 1: how many cuts at this size, 2: the size in mm, e.g. 500 */
			__( '%1$d x %2$smm', 'dorotape' ),
			$cut['qty'],
			// Drop trailing zeros: 500mm, 22.5mm
			rtrim( rtrim( number_format( $cut['size'], 1, '.', '' ), '0' ), '.' )
		);
	}
	return implode( ', ', $parts );
}

/**
 * Parse and sanitise the posted rolls. Each row of the table is one physical
 * roll being cut, holding one or more cut sizes and how many cuts are wanted
 * at each size (see dt_cut_rows[roll][cut][size|qty], named and renamed by
 * assets/js/lib/cut-size-rows.js as rolls and cuts are added and removed).
 *
 * Rolls with nothing entered are dropped here, so the caller can treat the
 * count of what comes back as "rolls being cut" and the rest of the order
 * quantity as rolls supplied whole.
 *
 * @return array[] Each entry: ['cuts' => array of ['size' => float, 'qty' => int, 'mm' => float], 'mm' => float, 'note' => string]
 */
function dorotape_cutsize_posted_rolls(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC add-to-cart flow.
	if ( empty( $_POST['dt_cut_rows'] ) || ! is_array( $_POST['dt_cut_rows'] ) ) {
		return array();
	}

	$rolls = array();
	foreach ( array_slice( wp_unslash( $_POST['dt_cut_rows'] ), 0, 100, true ) as $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		$cuts  = array();
		$total = 0.0;
		foreach ( array_slice( $group, 0, 20, true ) as $row ) {
			$size = isset( $row['size'] ) ? (float) $row['size'] : 0;
			// One cut is the default: a size with no quantity beside it is a
			// customer who filled in the box the form asked them to and left
			// the one they were not thinking about, not an empty row.
			$qty = isset( $row['qty'] ) ? min( 100, (int) $row['qty'] ) : 1;
			// Cuts are millimetres, full stop - client request: "can we remove all
			// cm & inch options so only mm is used". The form no longer offers a
			// unit, so any posted one is either a stale cached page or forged; in
			// both cases honouring it would put a "60cm" note on a picking list
			// that the rest of the system reads as mm. Ignore it and treat the
			// number as mm, which is exactly what the field now asks for.
			if ( $size > 0 && $qty > 0 ) {
				$cuts[] = array(
					'size' => $size,
					'qty'  => $qty,
					'mm'   => $size * $qty,
				);
				$total += $size * $qty;
			}
		}
		if ( $cuts ) {
			$rolls[] = array(
				'cuts' => $cuts,
				'mm'   => $total,
				'note' => dorotape_cutsize_note( $cuts ),
			);
		}
	}
	return $rolls;
	// phpcs:enable
}

// ─── Product page field ───────────────────────────────────────────────────────

/**
 * Structured cut rows inside the add-to-cart form. Each row group is one
 * physical roll being cut, numbered down the left, holding the sizes that
 * roll is cut into and how many cuts are wanted at each size: "roll 1 ->
 * 2 x 500mm and 1 x 220mm". That is the client's own model of the job
 * ("select which roll you would like to cut/convert/slit"), and it is what
 * the warehouse reads off the picking list.
 *
 * You cannot set cuts for more rolls than you are ordering; the rest of the
 * order quantity is supplied whole. Checked live by cut-size-rows.js and again
 * server-side. Laid out as a table, modelled on a courier parcel form
 * (client request). Replaces the old free-text box. data-max-width /
 * data-max-widths let cut-size-rows.js validate each roll's cuts against the
 * roll width.
 *
 * Hooked after the quantity input (woocommerce_before_add_to_cart_button
 * fires before it in both simple.php and variation-add-to-cart-button.php)
 * so the box sits below the quantity box, above the Add to basket button.
 */
add_action( 'woocommerce_after_add_to_cart_quantity', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! dorotape_has_cutsize( $product ) ) {
		return;
	}

	/* Per-roll entry or one roll's width. See dorotape_cutsize_per_roll(): the
	   same call decides what the server does with the post, so the form cannot
	   offer a split the cart would refuse to make. */
	$per_roll   = dorotape_cutsize_per_roll( $product );
	$max_width  = null;
	$width_json = '';
	if ( $product->is_type( 'variable' ) ) {
		$map = array();
		foreach ( $product->get_children() as $vid ) {
			$var = wc_get_product( $vid );
			if ( $var ) {
				$map[ $vid ] = dorotape_cutsize_max_width( $var );
			}
		}
		$width_json = wp_json_encode( $map );
	} else {
		$max_width = dorotape_cutsize_max_width( $product );
	}
	?>
	<div class="dt-cutsize<?php echo $per_roll ? '' : ' dt-cutsize--one-roll'; ?>" id="dt_cutsize"
		data-strings='<?php echo esc_attr( (string) wp_json_encode( dorotape_cutsize_js_strings() ) ); ?>'
		<?php echo $per_roll ? '' : 'data-single-roll="1"'; ?>
		<?php echo $max_width ? 'data-max-width="' . esc_attr( $max_width ) . '"' : ''; ?>
		<?php echo $width_json ? "data-max-widths='" . esc_attr( $width_json ) . "'" : ''; ?>>
		<button type="button" class="dt-cutsize__toggle" aria-expanded="false" aria-controls="dt_cutsize_panel">
			<span class="dt-cutsize__toggle-icon" aria-hidden="true">+</span>
			<span class="dt-cutsize__toggle-text">
				<?php
				echo esc_html(
					$per_roll
						? dorotape_cutsize_text( 'cutsize_toggle_rolls', 'Do you need your rolls cutting?' )
						: dorotape_cutsize_text( 'cutsize_toggle_single', 'Do you need this cutting to width?' )
				);
				?>
			</span>
			<span class="dt-cutsize__toggle-hint"><?php echo esc_html( dorotape_cutsize_text( 'cutsize_toggle_hint', 'Optional' ) ); ?></span>
		</button>
		<div class="dt-cutsize__panel" id="dt_cutsize_panel" hidden>
		<p class="dt-cutsize__hint">
			<?php
			echo esc_html(
				$per_roll
					? dorotape_cutsize_text( 'cutsize_hint_rolls', 'Select which roll you would like to cut/convert/slit. Enter size you would like and how many at that size. Use the + to add more cut sizes to that roll.' )
					: dorotape_cutsize_text( 'cutsize_hint_single', 'Tell us how to cut/convert/slit across the width. Enter the size you would like and how many at that size. Use the + to add more cut sizes.' )
			);
			?>
			<span class="dt-cutsize__max" style="<?php echo $max_width ? '' : 'display:none'; ?>">
				<?php
				/* translators: %s: roll width, e.g. 1220mm */
				$max_note = 'Maximum cuts total up to %s.';
				echo dorotape_cutsize_sprintf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both formats and the value are escaped above.
					esc_html( dorotape_cutsize_text( 'cutsize_max_note', $max_note ) ),
					esc_html( $max_note ),
					'<strong>' . esc_html( $max_width ? $max_width . 'mm' : '' ) . '</strong>'
				);
				?>
			</span>
		</p>
			<?php
			/**
			 * Quick add: the sizes customers ask for most, offered above the
			 * table. Manual entry is always available alongside; these only
			 * fill the box in. The bar stays in view as the table grows (see
			 * _cut-size.scss) because a customer entering four rolls was
			 * having to scroll back up to reach it.
			 *
			 * @param int[]      $presets Sizes in mm.
			 * @param WC_Product $product
			 */
			$presets = apply_filters( 'dorotape_cutsize_presets', dorotape_cutsize_presets(), $product );
			$presets = array_values( array_unique( array_filter( array_map( 'absint', (array) $presets ) ) ) );
			if ( $presets ) :
				?>
			<div class="dt-cutsize__presets">
				<span class="dt-cutsize__presets-label"><?php echo esc_html( dorotape_cutsize_text( 'cutsize_presets_label', 'Quick add:' ) ); ?></span>
				<?php foreach ( $presets as $preset ) : ?>
					<button type="button" class="dt-cutsize__preset" data-size="<?php echo esc_attr( $preset ); ?>">
						<?php
						/* translators: %d: cut size in millimetres */
						printf( esc_html__( '%dmm', 'dorotape' ), (int) $preset );
						?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		<div class="dt-cutsize__table-wrap">
			<table class="dt-cutsize__table">
				<thead>
					<tr>
						<?php /* Hidden by .dt-cutsize--one-roll: with one roll there is nothing to number, and dropping the column buys back a quarter of the table's width on a phone. */ ?>
						<th class="dt-cutsize__roll-col"><?php echo esc_html( dorotape_cutsize_text( 'cutsize_col_roll', 'Roll' ) ); ?></th>
						<th><?php echo esc_html( dorotape_cutsize_text( 'cutsize_col_size', 'Cut size (mm)' ) ); ?></th>
						<th class="dt-cutsize__cutqty-col"><?php echo esc_html( dorotape_cutsize_text( 'cutsize_col_qty', 'Qty' ) ); ?></th>
						<th class="dt-cutsize__action-col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'dorotape' ); ?></span></th>
					</tr>
				</thead>
				<tbody class="dt-cutsize__body">
					<tr class="dt-cutsize__row" data-group="0" data-cut="0">
						<td class="dt-cutsize__roll-cell" rowspan="1">
							<span class="dt-cutsize__roll-num">1</span>
							<button type="button" class="dt-cutsize__removegroup" aria-label="<?php esc_attr_e( 'Remove this roll', 'dorotape' ); ?>">&times;</button>
							<?php /* Filled by cut-size-rows.js: what is left on this roll after its cuts, beside the boxes being typed into. */ ?>
							<span class="dt-cutsize__roll-left"></span>
						</td>
						<td>
							<input type="number" class="dt-cutsize__size" name="dt_cut_rows[0][0][size]"
								min="1" step="1" placeholder="<?php echo esc_attr( dorotape_cutsize_text( 'cutsize_size_placeholder', 'Size in mm' ) ); ?>"
								aria-label="<?php esc_attr_e( 'Cut size in millimetres', 'dorotape' ); ?>">
							<span class="dt-cutsize__unit-suffix" aria-hidden="true">mm</span>
							<span class="dt-cutsize__row-error" role="alert"></span>
						</td>
						<td>
							<input type="number" class="dt-cutsize__cutqty" name="dt_cut_rows[0][0][qty]"
								min="1" step="1" value="1"
								aria-label="<?php esc_attr_e( 'How many cuts at this size', 'dorotape' ); ?>">
						</td>
						<td class="dt-cutsize__actions">
							<button type="button" class="dt-cutsize__addcut" aria-label="<?php esc_attr_e( 'Add another cut size to this roll', 'dorotape' ); ?>">+</button>
							<button type="button" class="dt-cutsize__remove" aria-label="<?php esc_attr_e( 'Remove cut', 'dorotape' ); ?>">&times;</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="dt-cutsize__diagram" id="dt_cutsize_diagram" hidden>
			<p class="dt-cutsize__diagram-title">
				<?php
				echo esc_html(
					$per_roll
						? dorotape_cutsize_text( 'cutsize_diagram_title_rolls', 'How each roll will be cut' )
						: dorotape_cutsize_text( 'cutsize_diagram_title_single', 'How your roll will be cut' )
				);
				?>
			</p>
			<div class="dt-cutsize__diagram-rolls"></div>
			<p class="dt-cutsize__diagram-note">
				<?php
				echo esc_html(
					$per_roll
						? dorotape_cutsize_text( 'cutsize_diagram_note_rolls', 'Each bar is one roll across its width. Shaded blocks are your cuts, the pale end is what is left on the roll.' )
						: dorotape_cutsize_text( 'cutsize_diagram_note_single', 'The bar is the roll across its width. Shaded blocks are your cuts, the pale end is what is left on the roll.' )
				);
				?>
			</p>
		</div>
		<div class="dt-cutsize__footer">
			<?php if ( $per_roll ) : ?>
				<button type="button" class="dt-cutsize__addgroup"><?php echo esc_html( dorotape_cutsize_text( 'cutsize_add_roll', '+ CLICK HERE FOR NEXT ROLL TO CUT/CONVERT/SLIT' ) ); ?></button>
			<?php endif; ?>
			<p class="dt-cutsize__alloc" aria-live="polite"></p>
		</div>
		</div><!-- .dt-cutsize__panel -->
	</div>
	<?php
} );

// ─── Validation + per-roll cart split ─────────────────────────────────────────

/**
 * Each roll in the box is one physical roll with its own list of cuts, so a
 * single "quantity" add-to-cart cannot stay one cart line: rolls cut
 * differently have to arrive as different lines, the same way two different
 * free-text notes already forced separate lines under the old cart_item_data
 * approach. Rolls cut identically merge back into one line on their own,
 * because WooCommerce keys a cart line on its item data and ours is the same
 * string for both, so "10 rolls all cut the same" is still one line.
 *
 * This hook does both jobs: validates that no more rolls are being cut than
 * are being ordered, and that each roll's cuts fit across its width, then,
 * once everything passes, adds the lines itself and returns false to stop
 * WooCommerce's own single-line add running afterwards. WC_Cart::add_to_cart()
 * doesn't itself re-trigger this filter, so the manual calls below don't
 * recurse back into this callback.
 */
add_filter( 'woocommerce_add_to_cart_validation', function ( bool $passed, int $product_id, $quantity, $variation_id = 0 ): bool {
	// An earlier validator already rejected this add (e.g. the quantity-step
	// check in pricing.php, which runs at priority 5). This callback adds cart
	// lines itself, so it must not run on a failed add or it would quietly
	// basket the very quantity that was just refused.
	if ( ! $passed ) {
		return false;
	}

	$parent = wc_get_product( $product_id );
	if ( ! $parent || ! dorotape_has_cutsize( $parent ) ) {
		return $passed;
	}

	$rolls = dorotape_cutsize_posted_rolls();
	if ( ! $rolls ) {
		return $passed; // nothing entered anywhere: add as a normal single line
	}

	$quantity = (int) $quantity;
	$item     = wc_get_product( $variation_id ? $variation_id : $product_id );

	/* Splitting the order into a line per cut roll only means something where
	   the quantity ordered is a count of rolls. On a product priced per metre
	   the quantity is a length, so "roll 1 of 50" reads as nothing, and the
	   arithmetic below is worse than meaningless: each cut roll is added as
	   quantity 1, which is not a valid multiple of a stepped product's
	   quantity, and WooCommerce rounds every line up to the next one that is.
	   50m entered with cuts on two rolls reached the basket as 25 + 25 + 50 =
	   100m, twice what was asked for and twice the price (RJM1501400, step 25).
	   A step above 1 breaks the same way on a roll-priced product, so both are
	   tested rather than just the unit.

	   Where the split does not apply the cuts still travel with the order:
	   one line, the quantity the customer actually chose, and every roll's
	   sizes on the note the warehouse reads. */
	$splittable = dorotape_cutsize_per_roll( $item ? $item : $parent );

	if ( $splittable && count( $rolls ) > $quantity ) {
		// The same wording the box shows live, from the same field, so the
		// message does not change its mind between typing and submitting.
		$strings = dorotape_cutsize_js_strings();
		wc_add_notice(
			dorotape_cutsize_sprintf(
				esc_html( $strings['tooMany'] ),
				esc_html__( 'You have entered cut sizes for %1$s rolls but are only ordering %2$s. Remove a roll, or order more.', 'dorotape' ),
				count( $rolls ),
				$quantity
			),
			'error'
		);
		return false;
	}

	$max_width = $item ? dorotape_cutsize_max_width( $item ) : null;

	foreach ( $rolls as $i => $roll ) {
		if ( $max_width && $roll['mm'] > $max_width ) {
			$total = esc_html( rtrim( rtrim( number_format( $roll['mm'], 1, '.', '' ), '0' ), '.' ) );
			// "Roll 1:" is noise when there is only ever one roll to be wrong about.
			/* translators: 1: roll number, 2: total of that roll's cuts in mm, 3: roll width in mm */
			$numbered = 'Roll %1$s: cuts add up to %2$smm, wider than the roll (%3$smm). Please adjust.';
			/* translators: 1: total of the cuts in mm, 2: roll width in mm */
			$single   = 'Your cuts add up to %1$smm, wider than the roll (%2$smm). Please adjust.';

			$notice = count( $rolls ) > 1
				? dorotape_cutsize_sprintf(
					esc_html( dorotape_cutsize_text( 'cutsize_error_width_numbered', $numbered ) ),
					esc_html( $numbered ),
					$i + 1,
					$total,
					esc_html( $max_width )
				)
				: dorotape_cutsize_sprintf(
					esc_html( dorotape_cutsize_text( 'cutsize_error_width', $single ) ),
					esc_html( $single ),
					$total,
					esc_html( $max_width )
				);
			wc_add_notice( $notice, 'error' );
			return false;
		}
	}

	// Rebuild the posted attribute_* selections the same way WC's own variable
	// handler does (class-wc-form-handler.php add_to_cart_handler_variable()) -
	// the variation's own get_attributes() isn't enough here: an attribute the
	// variation itself leaves as "Any" comes back empty from that, and only the
	// customer's actual form selection can supply it.
	$var_attributes = array();
	foreach ( $_REQUEST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC add-to-cart flow.
		if ( 'attribute_' === substr( (string) $key, 0, 10 ) ) {
			$var_attributes[ sanitize_title( wp_unslash( $key ) ) ] = wp_unslash( $value );
		}
	}

	// Keyed by cart item key, not counted: rolls cut the same way come back
	// with the same key and are one line between them, so counting the calls
	// would claim more lines than the customer can see.
	$keys = array();

	// Not a per-roll order: one line, the full quantity, all the cuts on it.
	if ( ! $splittable ) {
		$notes = array();
		foreach ( $rolls as $i => $roll ) {
			$notes[] = count( $rolls ) > 1
				/* translators: 1: roll number, 2: that roll's cut sizes */
				? sprintf( esc_html__( 'Roll %1$d: %2$s', 'dorotape' ), $i + 1, $roll['note'] )
				: $roll['note'];
		}
		$key = WC()->cart->add_to_cart(
			$product_id,
			$quantity,
			$variation_id ?: 0,
			$var_attributes,
			array( 'dt_cut_sizes' => implode( '; ', $notes ) )
		);
		if ( $key ) {
			wc_add_notice( esc_html( dorotape_cutsize_text( 'cutsize_added_one', 'Added to your basket.' ) ), 'success' );
		}
		return false;
	}

	foreach ( $rolls as $roll ) {
		$key = WC()->cart->add_to_cart(
			$product_id,
			1,
			$variation_id ?: 0,
			$var_attributes,
			array( 'dt_cut_sizes' => $roll['note'] )
		);
		if ( $key ) {
			$keys[ $key ] = true;
		}
	}

	$uncut = $quantity - count( $rolls ); // the balance of the order, supplied whole
	if ( $uncut > 0 ) {
		$key = WC()->cart->add_to_cart( $product_id, $uncut, $variation_id ?: 0, $var_attributes );
		if ( $key ) {
			$keys[ $key ] = true;
		}
	}

	$lines_added = count( $keys );

	if ( $lines_added > 1 ) {
		/* translators: %s: number of separate cart lines added */
		$added_many = 'Added to your basket in %s lines, one per set of cut sizes.';
		wc_add_notice(
			dorotape_cutsize_sprintf(
				esc_html( dorotape_cutsize_text( 'cutsize_added_many', $added_many ) ),
				esc_html( $added_many ),
				$lines_added
			),
			'success'
		);
	} elseif ( 1 === $lines_added ) {
		wc_add_notice( esc_html( dorotape_cutsize_text( 'cutsize_added_one', 'Added to your basket.' ) ), 'success' );
	}

	return false; // we've handled the add (in full or in part) ourselves
}, 10, 4 );

// ─── Cart plumbing ────────────────────────────────────────────────────────────

/**
 * Show the note under the line item in cart and checkout.
 *
 * @param array $item_data
 * @param array $cart_item
 * @return array
 */
add_filter( 'woocommerce_get_item_data', function ( array $item_data, array $cart_item ): array {
	// "Cut Sizes" is also the order item meta key the admin screen, the emails
	// and Sage look the value up by, so it is a key as much as a label and is
	// not editable for the same reason dorotape_cutsize_note() is not.
	if ( ! empty( $cart_item['dt_cut_sizes'] ) ) {
		$item_data[] = array(
			'name'  => esc_html__( 'Cut Sizes', 'dorotape' ),
			'value' => esc_html( $cart_item['dt_cut_sizes'] ),
		);
	}
	return $item_data;
}, 10, 2 );

/**
 * Persist to the order line item — visible in admin and emails via the
 * translated label key below. Also stored under a stable, hidden key so the
 * Sage export hook further down can read it without depending on a
 * translatable string.
 *
 * @param WC_Order_Item_Product $item
 * @param string                $cart_item_key Unused — required by WC hook signature.
 * @param array                 $values
 */
add_action( 'woocommerce_checkout_create_order_line_item', function (
	WC_Order_Item_Product $item,
	string $cart_item_key,
	array $values
): void {
	if ( ! empty( $values['dt_cut_sizes'] ) ) {
		$item->add_meta_data( esc_html__( 'Cut Sizes', 'dorotape' ), $values['dt_cut_sizes'], true );
		$item->add_meta_data( '_dt_cut_sizes_note', $values['dt_cut_sizes'], true );
	}
}, 10, 3 );

/**
 * Surface the cut-size note to Sage.
 *
 * Woosage's REST response is rebuilt strictly from its declared schema
 * (see Classes/REST_API/Controllers/V1/Controller.php get_field_value()) —
 * plain order-item meta, including ours above, is discarded and never
 * reaches Sage on its own. The plugin's documented extension point for this
 * (woosage50/examples/custom-line-item-addons.php) is a
 * `rest_request_after_callbacks` filter that injects into each line item's
 * `addons` array, which is what the old single-text-field cut size note
 * used to arrive as on the previous system — mirrored here so Sage still
 * gets it as one field per line.
 */
add_filter( 'rest_request_after_callbacks', function ( $response, $handler, WP_REST_Request $request ) {
	if ( is_wp_error( $response ) || false === strpos( $request->get_route(), 'woosage/v1/orders' ) ) {
		return $response;
	}

	$data = $response->get_data();
	if ( ! is_array( $data ) ) {
		return $response;
	}

	foreach ( $data as &$order ) {
		if ( empty( $order['line_items'] ) || ! is_array( $order['line_items'] ) ) {
			continue;
		}
		foreach ( $order['line_items'] as &$line_item ) {
			if ( empty( $line_item['item_id'] ) ) {
				continue;
			}
			$wc_item = WC_Order_Factory::get_order_item( $line_item['item_id'] );
			if ( ! $wc_item ) {
				continue;
			}
			$note = $wc_item->get_meta( '_dt_cut_sizes_note', true );
			if ( $note ) {
				$line_item['addons'][] = array(
					'name'     => __( 'Cut Sizes', 'dorotape' ),
					'response' => $note,
					'price'    => 0,
				);
			}
		}
	}

	$response->set_data( $data );
	return $response;
}, 10, 3 );
