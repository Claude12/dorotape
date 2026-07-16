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
	add_action( 'woocommerce_single_product_summary', 'dorotape_quick_add_form', 30 );
}, 5 );

/**
 * One row per variation: option name, price (with any quantity-break note),
 * quantity box. One button adds every row with qty > 0 as separate lines.
 */
function dorotape_quick_add_form(): void {
	global $product;

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
	if ( ! $rows ) {
		return;
	}
	?>
	<form class="dt-quickadd" method="post" action="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
		<?php wp_nonce_field( 'dt_quick_add_' . $product->get_id(), 'dt_quick_add_nonce' ); ?>
		<input type="hidden" name="dt_quick_add" value="<?php echo esc_attr( $product->get_id() ); ?>">
		<table class="dt-quickadd__table">
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
