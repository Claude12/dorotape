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
			<?php esc_html_e( 'Select which roll you would like to cut/convert/slit. Enter size you would like and how many at that size. Use the + to add more cut sizes to that roll.', 'dorotape' ); ?>
			<span class="dt-cutsize__max" style="<?php echo $max_width ? '' : 'display:none'; ?>">
				<?php
				/* translators: %s: roll width, e.g. 1220mm */
				printf( esc_html__( 'Maximum cuts total up to %s.', 'dorotape' ), '<strong>' . esc_html( $max_width ? $max_width . 'mm' : '' ) . '</strong>' );
				?>
			</span>
		</p>
			<?php
			/**
			 * Quick-add cut sizes offered above the table. Manual entry is
			 * always available alongside; these only fill the box in.
			 *
			 * @param int[]      $presets Sizes in mm.
			 * @param WC_Product $product
			 */
			$presets = apply_filters( 'dorotape_cutsize_presets', array( 150, 305, 610 ), $product );
			$presets = array_values( array_unique( array_filter( array_map( 'absint', (array) $presets ) ) ) );
			if ( $presets ) :
				?>
			<div class="dt-cutsize__presets">
				<span class="dt-cutsize__presets-label"><?php esc_html_e( 'Common cut sizes:', 'dorotape' ); ?></span>
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
						<th class="dt-cutsize__roll-col"><?php esc_html_e( 'Roll', 'dorotape' ); ?></th>
						<th><?php esc_html_e( 'Cut size (mm)', 'dorotape' ); ?></th>
						<th class="dt-cutsize__cutqty-col"><?php esc_html_e( 'Qty', 'dorotape' ); ?></th>
						<th class="dt-cutsize__action-col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'dorotape' ); ?></span></th>
					</tr>
				</thead>
				<tbody class="dt-cutsize__body">
					<tr class="dt-cutsize__row" data-group="0" data-cut="0">
						<td class="dt-cutsize__roll-cell" rowspan="1">
							<span class="dt-cutsize__roll-num">1</span>
							<button type="button" class="dt-cutsize__removegroup" aria-label="<?php esc_attr_e( 'Remove this roll', 'dorotape' ); ?>">&times;</button>
						</td>
						<td>
							<input type="number" class="dt-cutsize__size" name="dt_cut_rows[0][0][size]"
								min="1" step="1" placeholder="<?php esc_attr_e( 'Size in mm', 'dorotape' ); ?>"
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
		<div class="dt-cutsize__footer">
			<button type="button" class="dt-cutsize__addgroup"><?php esc_html_e( '+ CLICK HERE FOR NEXT ROLL TO CUT/CONVERT/SLIT', 'dorotape' ); ?></button>
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
	if ( count( $rolls ) > $quantity ) {
		wc_add_notice(
			sprintf(
				/* translators: 1: number of rolls with cut sizes entered, 2: quantity ordered */
				esc_html__( 'You have entered cut sizes for %1$d rolls but are only ordering %2$d. Remove a roll, or order more.', 'dorotape' ),
				count( $rolls ),
				$quantity
			),
			'error'
		);
		return false;
	}

	$item      = wc_get_product( $variation_id ? $variation_id : $product_id );
	$max_width = $item ? dorotape_cutsize_max_width( $item ) : null;

	foreach ( $rolls as $i => $roll ) {
		if ( $max_width && $roll['mm'] > $max_width ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: roll number, 2: total of that roll's cuts in mm, 3: roll width in mm */
					esc_html__( 'Roll %1$d: cuts add up to %2$smm, wider than the roll (%3$smm). Please adjust.', 'dorotape' ),
					$i + 1,
					esc_html( rtrim( rtrim( number_format( $roll['mm'], 1, '.', '' ), '0' ), '.' ) ),
					esc_html( $max_width )
				),
				'error'
			);
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
		wc_add_notice(
			sprintf(
				/* translators: %d: number of separate cart lines added */
				esc_html__( 'Added to your basket in %d lines, one per set of cut sizes.', 'dorotape' ),
				$lines_added
			),
			'success'
		);
	} elseif ( 1 === $lines_added ) {
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
