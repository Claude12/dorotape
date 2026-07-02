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

// ─── Product page field ───────────────────────────────────────────────────────

/**
 * Render the textarea inside the add-to-cart form so it submits with it.
 * Copy mirrors the old site's wording.
 */
add_action( 'woocommerce_before_add_to_cart_button', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! dorotape_has_cutsize( $product ) ) {
		return;
	}
	$value = isset( $_POST['dt_cut_sizes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dt_cut_sizes'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	?>
	<div class="dt-cutsize">
		<label class="dt-cutsize__label" for="dt_cut_sizes">
			<?php esc_html_e( 'Please enter cut sizes if required', 'dorotape' ); ?>
		</label>
		<p class="dt-cutsize__hint">
			<?php esc_html_e( "Please mark clearly whether these sizes are in mm, cm or inches. Please leave blank if you don't need this material cutting.", 'dorotape' ); ?>
		</p>
		<textarea id="dt_cut_sizes" name="dt_cut_sizes" class="dt-cutsize__input"
			rows="3" maxlength="500"><?php echo esc_textarea( $value ); ?></textarea>
	</div>
	<?php
} );

// ─── Cart plumbing ────────────────────────────────────────────────────────────

/**
 * Attach the note to the cart item. Different notes hash to different cart
 * lines automatically, so two cuts of the same roll don't merge.
 *
 * @param array $cart_item_data
 * @param int   $product_id
 * @return array
 */
add_filter( 'woocommerce_add_cart_item_data', function ( array $cart_item_data, int $product_id ): array {
	if ( empty( $_POST['dt_cut_sizes'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $cart_item_data;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product || ! dorotape_has_cutsize( $product ) ) {
		return $cart_item_data;
	}
	$note = sanitize_textarea_field( wp_unslash( $_POST['dt_cut_sizes'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$note = mb_substr( trim( $note ), 0, 500 );
	if ( '' !== $note ) {
		$cart_item_data['dt_cut_sizes'] = $note;
	}
	return $cart_item_data;
}, 10, 2 );

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
 * Persist to the order line item — visible in admin, emails, and Sage sync.
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
	}
}, 10, 3 );
