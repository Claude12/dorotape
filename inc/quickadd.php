<?php
/**
 * Quick Add layout
 *
 * For variable products flagged with _dt_quick_add=1 (CMS-managed via
 * Product data → Advanced), the standard variation dropdown is replaced with
 * a table: one row per option with its own quantity box and a single
 * "Add to basket" button. Built for Hook & Loop, where customers buy e.g.
 * 6 hook + 6 loop in one go — each option lands in the basket (and in Sage)
 * as its own line under its own SKU, so no new Sage codes are needed.
 *
 * Quantity tier pricing still applies per line via the cart engine.
 *
 * @package dorotape
 */

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * True when the product uses the quick-add layout.
 *
 * @param WC_Product $product
 * @return bool
 */
function dorotape_is_quick_add( WC_Product $product ): bool {
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return '1' === get_post_meta( $id, '_dt_quick_add', true ) && $product->is_type( 'variable' );
}

// ─── Admin toggle (Product data → Advanced) ───────────────────────────────────

add_action( 'woocommerce_product_options_advanced', function (): void {
	woocommerce_wp_checkbox(
		array(
			'id'          => '_dt_quick_add',
			'cbvalue'     => '1',
			'label'       => __( 'Quick add layout', 'dorotape' ),
			'description' => __( 'Variable products only: show every option with its own quantity box and one add-to-basket button, instead of a dropdown.', 'dorotape' ),
		)
	);
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	if ( isset( $_POST['_dt_quick_add'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
		$product->update_meta_data( '_dt_quick_add', '1' );
	} else {
		$product->delete_meta_data( '_dt_quick_add' );
	}
} );

// ─── Product page: replace the variations form ────────────────────────────────

/**
 * Swap the default add-to-cart template for the quick-add table.
 */
add_action( 'woocommerce_before_single_product_summary', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! dorotape_is_quick_add( $product ) ) {
		return;
	}
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_single_product_summary', 'dorotape_quick_add_total', 11 );
	add_action( 'woocommerce_single_product_summary', 'dorotape_quick_add_form', 30 );
}, 5 );

/**
 * Combined-quantity box next to the headline price.
 *
 * Client: "is it possible to show the quantity box so people are aware that they
 * can mix and match to get the 10 roll discount?" Every other product on the
 * site has a quantity box beside its price; the quick-add layout replaced it
 * with per-option boxes down in the grid, so the page lost the one control
 * people look for — and with it any hint that hook and loop are counted
 * together.
 *
 * This puts a box back in the expected place. It is a running total, not an
 * input: the real quantities are still the per-option ones, and a single number
 * here could not say how it splits between them. So it is read-only, filled in
 * by scaffold.js as the grid is used. It carries no name attribute and so posts
 * nothing.
 *
 * Just the label and the number. The mix-and-match rule and the progress towards
 * the break belong to the grid, above dorotape_quick_add_form()'s table — they
 * were stated here as well and read as duplication on screen.
 *
 * Rendered at priority 11, immediately after woocommerce_template_single_price.
 */
function dorotape_quick_add_total(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$mix = dorotape_quick_add_mix( dorotape_quick_add_rows( $product ) );
	if ( $mix['min'] < 2 || $mix['price'] <= 0 ) {
		return; // No combined break to work towards — the box would say nothing.
	}

	$strings = function_exists( 'dorotape_unit_strings' )
		? dorotape_unit_strings( dorotape_price_unit( $product->get_id() ) )
		: array();
	$label = $strings['total'] ?? __( 'Total quantity', 'dorotape' );
	?>
	<div class="dt-quickadd-total" data-mix-min="<?php echo esc_attr( $mix['min'] ); ?>">
		<div class="dt-quickadd-total__row">
			<label class="dt-quickadd-total__label" for="dt-quickadd-total">
				<?php echo esc_html( $label ); ?>
			</label>
			<input type="text" id="dt-quickadd-total" class="dt-quickadd-total__input"
				value="0" readonly tabindex="-1" inputmode="none">
		</div>
		<?php
		// The mix-and-match rule and the running "N more to go" line are NOT
		// repeated here. They are stated once, immediately above the grid, where
		// the customer is actually typing — carrying them here as well put the
		// same two sentences on screen twice within a few hundred pixels.
		?>
	</div>
	<?php
}

/**
 * Build the quick-add rows for a product: one per purchasable variation.
 *
 * Split out of the form so the combined-total box above the grid can be built
 * from exactly the same data — it needs the tier breaks to know what the target
 * quantity is, and two independent readings of that would be a bug waiting to
 * happen.
 *
 * @param WC_Product $product
 * @return array<int, array{id:int,sku:string,label:string,price:float,tier:string,tiers:array}>
 */
function dorotape_quick_add_rows( WC_Product $product ): array {
	$rows = array();
	foreach ( $product->get_children() as $vid ) {
		$var = wc_get_product( $vid );
		if ( ! $var || ! $var->is_purchasable() ) {
			continue;
		}
		// Human label from the variation's attribute terms.
		$labels = array();
		foreach ( $var->get_attributes() as $tax => $slug ) {
			$term     = get_term_by( 'slug', $slug, $tax );
			$labels[] = $term ? $term->name : $slug;
		}
		// Quantity-break tiers for the price cell (updates live as qty changes)
		// and its note. Sage price-list customers pay their role price flat —
		// no tiers surfaced for them at all.
		$tier_note = '';
		$tiers     = array();
		if ( function_exists( 'dorotape_parse_legacy_tiers' ) && ! dorotape_user_has_role_price( $var ) ) {
			$best = null;
			foreach ( dorotape_parse_legacy_tiers( $vid ) as $tier ) {
				if ( (int) $tier['min_qty'] > 1 && (float) $tier['tier_price'] > 0 ) {
					// Fixed 2dp string, not a float: this server's serialize_precision
					// setting expands rounded floats back to their full binary form
					// in JSON (e.g. 8.91 -> 8.910000000000000142...); a formatted
					// string sidesteps that regardless of php.ini on any environment.
					$tiers[] = array(
						'min_qty'    => (int) $tier['min_qty'],
						'tier_price' => number_format( (float) $tier['tier_price'], 2, '.', '' ),
					);
					if ( null === $best || (float) $tier['tier_price'] < (float) $best['tier_price'] ) {
						$best = $tier;
					}
				}
			}
			if ( $best ) {
				$tier_note = sprintf(
					/* translators: 1: minimum quantity, 2: discounted price */
					esc_html__( '%1$d+: %2$s each', 'dorotape' ),
					(int) $best['min_qty'],
					wp_strip_all_tags( wc_price( (float) $best['tier_price'] ) )
				);
			}
		}
		$rows[] = array(
			'id'    => $vid,
			'sku'   => $var->get_sku(),
			'label' => implode( ' / ', $labels ),
			'price' => wc_get_price_to_display( $var ),
			'tier'  => $tier_note,
			'tiers' => $tiers,
		);
	}
	return $rows;
}

/**
 * The combined-quantity discount break across all rows, if there is one.
 *
 * The discount tier is picked on the COMBINED quantity across every row (5 hook
 * + 5 loop = the 10-roll rate), which isn't obvious from a table of separate
 * lines. The lowest break across the rows is the one to aim a customer at.
 *
 * @param array $rows Rows from dorotape_quick_add_rows().
 * @return array{min:int,price:float} min of 0 when no break applies.
 */
function dorotape_quick_add_mix( array $rows ): array {
	$min   = 0;
	$price = 0.0;
	foreach ( $rows as $row ) {
		foreach ( $row['tiers'] as $tier ) {
			if ( 0 === $min || (int) $tier['min_qty'] < $min ) {
				$min   = (int) $tier['min_qty'];
				$price = (float) $tier['tier_price'];
			}
		}
	}
	return array( 'min' => $min, 'price' => $price );
}

/**
 * One row per variation: option name, price (with any quantity-break note),
 * quantity box. One button adds every row with qty > 0 as separate lines.
 */
function dorotape_quick_add_form(): void {
	global $product;

	$rows = dorotape_quick_add_rows( $product );
	if ( ! $rows ) {
		return;
	}

	$mix       = dorotape_quick_add_mix( $rows );
	$mix_min   = $mix['min'];
	$mix_price = $mix['price'];
	?>
	<form class="dt-quickadd" method="post" action="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
		<?php wp_nonce_field( 'dt_quick_add_' . $product->get_id(), 'dt_quick_add_nonce' ); ?>
		<input type="hidden" name="dt_quick_add" value="<?php echo esc_attr( $product->get_id() ); ?>">
		<?php if ( $mix_min > 1 && $mix_price > 0 ) : ?>
			<p class="dt-quickadd__mix" data-mix-min="<?php echo esc_attr( $mix_min ); ?>">
				<span class="dt-quickadd__mix-lead">
					<?php
					printf(
						/* translators: 1: combined quantity needed, 2: discounted unit price */
						esc_html__( 'Mix and match: any %1$d in total gets them all at %2$s each.', 'dorotape' ),
						(int) $mix_min,
						esc_html( wp_strip_all_tags( wc_price( $mix_price ) ) )
					);
					?>
				</span>
				<?php // The page's one live region. It used to sit on the copy of
					// this line beside the Total rolls box, which was silent to
					// avoid a double announcement; that copy is gone, so the
					// announcement belongs here. ?>
				<span class="dt-quickadd__mix-live" aria-live="polite"></span>
			</p>
		<?php endif; ?>
		<table class="dt-quickadd__table" data-unit="<?php echo esc_attr( dorotape_price_unit( $product->get_id() ) ); ?>">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Option', 'dorotape' ); ?></th>
					<th><?php esc_html_e( 'Price', 'dorotape' ); ?></th>
					<th class="dt-quickadd__qty-col"><?php esc_html_e( 'Qty', 'dorotape' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td>
							<span class="dt-quickadd__sku"><?php echo esc_html( $row['sku'] ); ?></span>
							<span class="dt-quickadd__label"><?php echo esc_html( $row['label'] ); ?></span>
						</td>
						<td class="dt-quickadd__price-cell"
							data-base="<?php echo esc_attr( $row['price'] ); ?>"
							data-tiers="<?php echo esc_attr( wp_json_encode( $row['tiers'] ) ); ?>">
							<span class="dt-quickadd__price"><?php echo wp_kses_post( wc_price( $row['price'] ) ); ?></span>
							<?php if ( $row['tier'] ) : ?>
								<span class="dt-quickadd__tier"><?php echo esc_html( $row['tier'] ); ?></span>
							<?php endif; ?>
							<span class="dt-quickadd__line" hidden></span>
						</td>
						<td class="dt-quickadd__qty-col">
							<input type="number" name="dt_qty[<?php echo esc_attr( $row['id'] ); ?>]"
								class="dt-quickadd__qty-input"
								min="0" max="9999" step="1" value="0" inputmode="numeric"
								aria-label="<?php echo esc_attr( sprintf( __( 'Quantity of %s', 'dorotape' ), $row['label'] ) ); ?>">
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button type="submit" class="single_add_to_cart_button button alt dt-quickadd__submit">
			<?php esc_html_e( 'Add to basket', 'dorotape' ); ?>
		</button>
	</form>
	<?php
}

// ─── POST handler ─────────────────────────────────────────────────────────────

/**
 * Add every quick-add row with a quantity to the basket as its own line.
 * Runs on wp_loaded so the redirect happens before any output — at priority
 * 30, AFTER WooCommerce loads the cart from the session (10) and runs its own
 * form handlers (20). At the default priority the first added line gets lost:
 * it races WC's session/cart bootstrap and is overwritten mid-request.
 */
add_action( 'wp_loaded', function (): void {
	if ( empty( $_POST['dt_quick_add'] ) || empty( $_POST['dt_quick_add_nonce'] ) ) {
		return;
	}
	$parent_id = absint( $_POST['dt_quick_add'] );
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_quick_add_nonce'] ) ), 'dt_quick_add_' . $parent_id ) ) {
		return;
	}
	$parent = wc_get_product( $parent_id );
	if ( ! $parent || ! dorotape_is_quick_add( $parent ) ) {
		return;
	}

	$children = array_map( 'intval', $parent->get_children() );
	$added    = 0;
	$qtys     = isset( $_POST['dt_qty'] ) && is_array( $_POST['dt_qty'] ) ? wp_unslash( $_POST['dt_qty'] ) : array();

	foreach ( $qtys as $vid => $qty ) {
		$vid = absint( $vid );
		$qty = absint( $qty );
		if ( ! $qty || ! in_array( $vid, $children, true ) ) {
			continue; // qty 0 or a variation that isn't ours
		}
		$var = wc_get_product( $vid );
		if ( ! $var instanceof WC_Product_Variation ) {
			continue;
		}
		// Same validation gate core form handlers apply before each add.
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $parent_id, $qty, $vid, $var->get_attributes() ) ) {
			continue;
		}
		if ( WC()->cart->add_to_cart( $parent_id, $qty, $vid, $var->get_attributes() ) ) {
			$added += $qty;
		}
	}

	if ( $added > 0 ) {
		wc_add_notice(
			sprintf(
				/* translators: %d: number of items */
				_n( '%d item added to your basket.', '%d items added to your basket.', $added, 'dorotape' ),
				$added
			),
			'success'
		);
	} else {
		wc_add_notice( esc_html__( 'Enter a quantity against at least one option.', 'dorotape' ), 'notice' );
	}

	wp_safe_redirect( get_permalink( $parent_id ) );
	exit;
}, 30 );
