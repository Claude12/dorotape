<?php
/**
 * Cut Sizes field
 *
 * Products migrated from Kryptronic with a CUTSIZE option group carry
 * _dt_cutsize_enabled=1 post meta (application tapes, digital print rolls...).
 * On the old site this was a free-text box on the product page; the customer
 * lists the cut sizes they need and the warehouse cuts the roll to match.
 *
 * Flow: textarea inside the add-to-cart form -> cart item data (distinct cart
 * lines per note) -> cart/checkout display -> order line item meta for admin,
 * emails, and Sage sync.
 *
 * @package dorotape
 */

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
 * Roll width in mm for a product/variation, parsed from the variation's
 * attribute label or the product name ("Roll size 1220mm x 91.4m" -> 1220,
 * "1370mm x 50m Roll" -> 1370). Cut pieces cannot exceed the roll width.
 *
 * @param WC_Product $product Product or variation.
 * @return int|null Width in mm, or null when no width is stated.
 */
function dorotape_cutsize_max_width( WC_Product $product ): ?int {
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
 * Convert a cut row to millimetres.
 *
 * @param float  $size
 * @param string $unit 'mm' | 'cm' | 'in'
 * @return float
 */
function dorotape_cutsize_to_mm( float $size, string $unit ): float {
	if ( 'cm' === $unit ) {
		return $size * 10;
	}
	if ( 'in' === $unit ) {
		return $size * 25.4;
	}
	return $size;
}

/**
 * Parse and sanitise the posted cut patterns. Each pattern holds zero or
 * more cuts plus how many of the ordered rolls it applies to — decoupled
 * from the order quantity so a large order doesn't need one row per roll
 * (see dt_cut_rows[pattern][cut][size|unit] and dt_cut_qty[pattern], built/
 * rebuilt by scaffold.js as patterns are added/removed).
 *
 * @return array[] Each entry: ['qty' => int, 'cuts' => array of ['size' => float, 'unit' => 'mm'|'cm'|'in', 'mm' => float]]
 */
function dorotape_cutsize_posted_patterns(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC add-to-cart flow.
	if ( empty( $_POST['dt_cut_rows'] ) || ! is_array( $_POST['dt_cut_rows'] ) ) {
		return array();
	}
	$posted_qty = isset( $_POST['dt_cut_qty'] ) && is_array( $_POST['dt_cut_qty'] ) ? wp_unslash( $_POST['dt_cut_qty'] ) : array();

	$patterns = array();
	foreach ( array_slice( wp_unslash( $_POST['dt_cut_rows'] ), 0, 100, true ) as $g => $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		$cuts = array();
		foreach ( array_slice( $group, 0, 20, true ) as $row ) {
			$size = isset( $row['size'] ) ? (float) $row['size'] : 0;
			// Cuts are millimetres, full stop — client request: "can we remove all
			// cm & inch options so only mm is used". The form no longer offers a
			// unit, so any posted one is either a stale cached page or forged; in
			// both cases honouring it would put a "60cm" note on a picking list
			// that the rest of the system reads as mm. Ignore it and treat the
			// number as mm, which is exactly what the field now asks for.
			$unit = 'mm';
			if ( $size > 0 ) {
				$cuts[] = array(
					'size' => $size,
					'unit' => $unit,
					'mm'   => dorotape_cutsize_to_mm( $size, $unit ),
				);
			}
		}
		$qty = isset( $posted_qty[ $g ] ) ? max( 0, (int) $posted_qty[ $g ] ) : 0;
		if ( $qty > 0 ) {
			$patterns[] = array(
				'qty'  => $qty,
				'cuts' => $cuts, // may be empty: these rolls aren't being cut
			);
		}
	}
	return $patterns;
	// phpcs:enable
}

// ─── Product page field ───────────────────────────────────────────────────────

/**
 * Structured cut rows inside the add-to-cart form. Each row is a *cut
 * pattern* — one or more cut sizes plus how many of the ordered rolls get
 * that pattern — rather than one row per roll, so a qty-100 order doesn't
 * need a 100-row table: e.g. "80 rolls -> 500mm, 20 rolls -> uncut" is two
 * rows regardless of quantity. Pattern quantities must add up to the order
 * quantity (validated live by scaffold.js and again server-side). Laid out
 * as a table, modelled on a courier parcel form (client request). Replaces
 * the old free-text box. data-max-width / data-max-widths let scaffold.js
 * validate each pattern's cuts against the roll width.
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
	<div class="dt-cutsize" id="dt_cutsize"
		<?php echo $max_width ? 'data-max-width="' . esc_attr( $max_width ) . '"' : ''; ?>
		<?php echo $width_json ? "data-max-widths='" . esc_attr( $width_json ) . "'" : ''; ?>>
		<button type="button" class="dt-cutsize__toggle" aria-expanded="false" aria-controls="dt_cutsize_panel">
			<span class="dt-cutsize__toggle-icon" aria-hidden="true">+</span>
			<span class="dt-cutsize__toggle-text"><?php esc_html_e( 'Do you need your rolls cutting?', 'dorotape' ); ?></span>
			<span class="dt-cutsize__toggle-hint"><?php esc_html_e( 'Optional', 'dorotape' ); ?></span>
		</button>
		<div class="dt-cutsize__panel" id="dt_cutsize_panel" hidden>
		<p class="dt-cutsize__hint">
			<?php esc_html_e( 'Set how many rolls need each cut pattern below — the quantities must add up to your total order quantity. Add another cut within a pattern if a roll needs splitting into more than one length.', 'dorotape' ); ?>
			<span class="dt-cutsize__max" style="<?php echo $max_width ? '' : 'display:none'; ?>">
				<?php
				/* translators: %s: roll width, e.g. 1220mm */
				printf( esc_html__( 'Maximum cut width: %s.', 'dorotape' ), '<strong>' . esc_html( $max_width ? $max_width . 'mm' : '' ) . '</strong>' );
				?>
			</span>
		</p>
		<div class="dt-cutsize__table-wrap">
			<table class="dt-cutsize__table">
				<thead>
					<tr>
						<th class="dt-cutsize__qty-col"><?php esc_html_e( 'Qty', 'dorotape' ); ?></th>
						<th><?php esc_html_e( 'Cut size (mm)', 'dorotape' ); ?></th>
						<th class="dt-cutsize__action-col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'dorotape' ); ?></span></th>
					</tr>
				</thead>
				<tbody class="dt-cutsize__body">
					<tr class="dt-cutsize__row" data-group="0" data-cut="0">
						<td class="dt-cutsize__qty-cell" rowspan="1">
							<input type="number" class="dt-cutsize__qty" name="dt_cut_qty[0]"
								min="1" step="1" value="1"
								aria-label="<?php esc_attr_e( 'Number of rolls for this cut pattern', 'dorotape' ); ?>">
							<button type="button" class="dt-cutsize__removegroup" aria-label="<?php esc_attr_e( 'Remove this cut pattern', 'dorotape' ); ?>">&times;</button>
						</td>
						<td>
							<input type="number" class="dt-cutsize__size" name="dt_cut_rows[0][0][size]"
								min="1" step="1" placeholder="<?php esc_attr_e( 'Size in mm', 'dorotape' ); ?>"
								aria-label="<?php esc_attr_e( 'Cut size in millimetres', 'dorotape' ); ?>">
							<span class="dt-cutsize__unit-suffix" aria-hidden="true">mm</span>
							<span class="dt-cutsize__row-error" role="alert"></span>
						</td>
						<td class="dt-cutsize__actions">
							<button type="button" class="dt-cutsize__addcut" aria-label="<?php esc_attr_e( 'Add another cut to this pattern', 'dorotape' ); ?>">+</button>
							<button type="button" class="dt-cutsize__remove" aria-label="<?php esc_attr_e( 'Remove cut', 'dorotape' ); ?>">&times;</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="dt-cutsize__footer">
			<button type="button" class="dt-cutsize__addgroup"><?php esc_html_e( '+ Add another cut pattern', 'dorotape' ); ?></button>
			<p class="dt-cutsize__alloc" aria-live="polite"></p>
		</div>
		</div><!-- .dt-cutsize__panel -->
	</div>
	<?php
} );

// ─── Validation + per-pattern cart split ───────────────────────────────────────

/**
 * Each cut pattern can carry its own quantity and its own cut sizes, so a
 * single "quantity" add-to-cart can't stay one cart line — it has to become
 * one line per pattern (qty = that pattern's allocation), the same way two
 * different free-text notes already forced separate lines under the old
 * cart_item_data approach.
 *
 * This hook does both jobs: validates the pattern quantities add up to the
 * order quantity and each pattern's cuts against the roll width, then —
 * once everything passes — adds the lines itself and returns false to stop
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

	$patterns = dorotape_cutsize_posted_patterns();
	$any_cuts = false;
	foreach ( $patterns as $pattern ) {
		if ( $pattern['cuts'] ) {
			$any_cuts = true;
			break;
		}
	}
	if ( ! $any_cuts ) {
		return $passed; // nothing entered anywhere: add as a normal single line
	}

	$quantity  = (int) $quantity;
	$allocated = array_sum( wp_list_pluck( $patterns, 'qty' ) );
	if ( $allocated !== $quantity ) {
		wc_add_notice(
			sprintf(
				/* translators: 1: total allocated across cut patterns, 2: quantity ordered */
				esc_html__( 'Cut pattern quantities add up to %1$d, but you\'re ordering %2$d. Please adjust so they match.', 'dorotape' ),
				$allocated,
				$quantity
			),
			'error'
		);
		return false;
	}

	$item      = wc_get_product( $variation_id ? $variation_id : $product_id );
	$max_width = $item ? dorotape_cutsize_max_width( $item ) : null;

	foreach ( $patterns as $i => $pattern ) {
		if ( ! $pattern['cuts'] ) {
			continue; // these rolls aren't being cut
		}
		$sum = array_sum( wp_list_pluck( $pattern['cuts'], 'mm' ) );
		if ( $max_width && $sum > $max_width ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: pattern number, 2: total of that pattern's cuts in mm, 3: roll width in mm */
					esc_html__( 'Cut pattern %1$d: cuts add up to %2$smm, wider than the roll (%3$smm). Please adjust.', 'dorotape' ),
					$i + 1,
					esc_html( rtrim( rtrim( number_format( $sum, 1, '.', '' ), '0' ), '.' ) ),
					esc_html( $max_width )
				),
				'error'
			);
			return false;
		}
	}

	// Rebuild the posted attribute_* selections the same way WC's own variable
	// handler does (class-wc-form-handler.php add_to_cart_handler_variable()) —
	// the variation's own get_attributes() isn't enough here: an attribute the
	// variation itself leaves as "Any" comes back empty from that, and only the
	// customer's actual form selection can supply it.
	$var_attributes = array();
	foreach ( $_REQUEST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC add-to-cart flow.
		if ( 'attribute_' === substr( (string) $key, 0, 10 ) ) {
			$var_attributes[ sanitize_title( wp_unslash( $key ) ) ] = wp_unslash( $value );
		}
	}

	$lines_added = 0;
	$plain_qty   = 0; // rolls with a qty allocation but no cuts, merged into one line

	foreach ( $patterns as $pattern ) {
		if ( ! $pattern['cuts'] ) {
			$plain_qty += $pattern['qty'];
			continue;
		}
		$parts = array();
		foreach ( $pattern['cuts'] as $cut ) {
			// Drop trailing zeros: 500mm, 22.5mm
			$parts[] = rtrim( rtrim( number_format( $cut['size'], 1, '.', '' ), '0' ), '.' ) . $cut['unit'];
		}
		$cart_item_data = array(
			'dt_cut_sizes' => sprintf(
				/* translators: 1: number of cuts, 2: list of sizes */
				_n( '%2$s (1 cut)', '%2$s (%1$d cuts)', count( $parts ), 'dorotape' ),
				count( $parts ),
				implode( ', ', $parts )
			),
		);
		if ( WC()->cart->add_to_cart( $product_id, $pattern['qty'], $variation_id ?: 0, $var_attributes, $cart_item_data ) ) {
			++$lines_added;
		}
	}

	if ( $plain_qty > 0 && WC()->cart->add_to_cart( $product_id, $plain_qty, $variation_id ?: 0, $var_attributes ) ) {
		++$lines_added;
	}

	if ( $lines_added > 1 ) {
		wc_add_notice(
			sprintf(
				/* translators: %d: number of separate cart lines added */
				esc_html__( 'Added to your basket in %d lines (one per cut pattern).', 'dorotape' ),
				$lines_added
			),
			'success'
		);
	} elseif ( $lines_added === 1 ) {
		wc_add_notice( esc_html__( 'Added to your basket.', 'dorotape' ), 'success' );
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
