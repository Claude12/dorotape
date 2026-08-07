<?php
/**
 * Stock availability and lead-time messaging (DR-8)
 *
 * Sage is the source of truth for stock, so _manage_stock is deliberately off on
 * every product and every product reads "in stock". WooCommerce prints an
 * availability line only when it has something to say - a managed figure, "on
 * backorder", or "out of stock" - so with stock management off it prints nothing
 * at all, and the customer is told nothing about lead times.
 *
 * The old site had the same model and solved it the same way, which is why this
 * is messaging rather than inventory. Of its 1,510 inventory rows 1,373 were
 * LOCALUNL (local and unlimited, i.e. not tracked); only 44 were genuinely
 * tracked and 88 were dropship. Rather than a global rule it carried two
 * per-item strings, and this file reproduces both:
 *
 *   customdispover -> _dt_stock_note        the availability line for an item
 *                                          that can be bought but is not on the
 *                                          shelf. Dropship lead times, mostly:
 *                                          "2-3 days" on 88 items, "Delivery in
 *                                          10-12 days" on 16.
 *   customdispoos  -> _dt_out_of_stock_note replaces "Out of stock" when the
 *                                          item cannot be bought at all:
 *                                          "Back in stock soon", "COMING SOON".
 *
 * Two settings do NOT need porting, because WooCommerce already behaves the way
 * the old site was configured:
 *
 *   ecom.showinstocklevel = 1 printed the exact figure rather than a vague "in
 *   stock". That is what woocommerce_stock_format = '' (the default) does.
 *
 *   ecom.lowinventoryalertstatus = 0 with a level of 0 means there was no low
 *   stock threshold and no low stock message. WooCommerce only produces one if
 *   woocommerce_stock_format is set to 'low_amount', which it is not. So there
 *   is nothing to switch off on the customer-facing side.
 *
 * Where a product IS stock managed the real figure wins and the note is added
 * after it, never instead of it. That way the day a Sage sync starts writing
 * levels, the levels appear on their own without this file being touched.
 *
 * @package dorotape
 */

/** Availability line for an item that is buyable but not on the shelf. */
define( 'DOROTAPE_STOCK_NOTE_META', '_dt_stock_note' );

/** Replacement for "Out of stock" on an item that cannot be bought. */
define( 'DOROTAPE_OUT_OF_STOCK_NOTE_META', '_dt_out_of_stock_note' );

// ─── Reading the notes ────────────────────────────────────────────────────────

/**
 * Read a stock note for a product, falling back to its parent.
 *
 * A variation inherits the parent's note unless it has been given its own, so a
 * range with one lead time needs the string entered once. An empty string on the
 * variation is inheritance, not an override - use the parent field to clear it
 * for the whole range.
 *
 * @param WC_Product $product Product or variation.
 * @param string     $key     One of the DOROTAPE_*_NOTE_META constants.
 * @return string Note, or '' when neither the product nor its parent has one.
 */
function dorotape_stock_note( WC_Product $product, string $key ): string {
	$note = (string) $product->get_meta( $key );

	if ( '' === trim( $note ) && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent instanceof WC_Product ) {
			$note = (string) $parent->get_meta( $key );
		}
	}

	return trim( $note );
}

// ─── Rendering ────────────────────────────────────────────────────────────────

/**
 * Put the note into the availability line WooCommerce renders on the product
 * page, so it appears everywhere stock already appears - single product,
 * grouped product rows, and the variation JSON that swaps the line when a
 * variation is chosen - without overriding a single template.
 *
 * Out of stock replaces, everything else appends. Replacing is right for the
 * out-of-stock note because these are Michael's own wordings for that state
 * ("Back in stock soon"), phrased to stand in for "Out of stock" rather than to
 * follow it. Appending is right everywhere else because a real figure, or "on
 * backorder", is information the note does not carry.
 *
 * @param array      $availability WooCommerce's text and CSS class.
 * @param WC_Product $product      Product being rendered.
 * @return array
 */
add_filter( 'woocommerce_get_availability', function ( array $availability, WC_Product $product ): array {
	if ( ! $product->is_in_stock() ) {
		$note = dorotape_stock_note( $product, DOROTAPE_OUT_OF_STOCK_NOTE_META );
		if ( '' !== $note ) {
			$availability['availability'] = $note;
			$availability['class']        = trim( $availability['class'] . ' dt-stock-note' );
		}
		return $availability;
	}

	$note = dorotape_stock_note( $product, DOROTAPE_STOCK_NOTE_META );
	if ( '' === $note ) {
		return $availability;
	}

	$existing                     = trim( (string) $availability['availability'] );
	$availability['availability'] = '' === $existing ? $note : $existing . '<br>' . $note;

	/*
	 * WooCommerce's own class is left in place, because anything else keying off
	 * in-stock keeps working. The extra class is what the stylesheet needs:
	 * get_availability_class() returns in-stock for every purchasable product
	 * whether or not it had any text to go with it, so the class alone cannot
	 * tell "12 in stock" from a bare lead time. dt-stock-note-only says the note
	 * is the whole line, which the stylesheet then colours neutral rather than
	 * letting a 10-12 day wait borrow the in-stock green.
	 */
	$classes = array( (string) $availability['class'], 'dt-stock-note' );
	if ( '' === $existing ) {
		$classes[] = 'dt-stock-note-only';
	}
	$availability['class'] = trim( implode( ' ', array_filter( $classes ) ) );

	return $availability;
}, 10, 2 );

// ─── Admin fields (Product data → Inventory) ──────────────────────────────────

/**
 * Field definitions, shared by the product and variation forms so the label and
 * the explanation cannot drift apart between the two.
 *
 * @return array<string, array{label: string, description: string, placeholder: string}>
 */
function dorotape_stock_note_fields(): array {
	return array(
		DOROTAPE_STOCK_NOTE_META        => array(
			'label'       => __( 'Availability note', 'dorotape' ),
			'placeholder' => __( 'e.g. Delivery in 10-12 days', 'dorotape' ),
			'description' => __( 'Shown on the product page under the price for a product that can be ordered but is not held in stock. Use it for a lead time. Leave blank for anything shipped from stock.', 'dorotape' ),
		),
		DOROTAPE_OUT_OF_STOCK_NOTE_META => array(
			'label'       => __( 'Out of stock note', 'dorotape' ),
			'placeholder' => __( 'e.g. Back in stock soon', 'dorotape' ),
			'description' => __( 'Replaces "Out of stock" when the product is marked out of stock. Leave blank to use the standard wording.', 'dorotape' ),
		),
	);
}

/**
 * The two notes on the Inventory tab, for simple and variable products. On a
 * variable product these are the range-wide defaults every variation inherits.
 */
add_action( 'woocommerce_product_options_inventory_product_data', function (): void {
	global $post;

	echo '<div class="options_group dorotape-stock-notes">';

	foreach ( dorotape_stock_note_fields() as $key => $field ) {
		woocommerce_wp_textarea_input(
			array(
				'id'          => $key,
				'label'       => $field['label'],
				'placeholder' => $field['placeholder'],
				'description' => $field['description'],
				'desc_tip'    => false,
				'value'       => (string) get_post_meta( $post->ID, $key, true ),
				'rows'        => 2,
			)
		);
	}

	echo '</div>';
} );

/**
 * Persist the product-level notes. Deleted rather than stored empty, so an
 * emptied field reads the same as one that was never filled in.
 *
 * @param WC_Product $product
 */
add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	foreach ( array_keys( dorotape_stock_note_fields() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
			continue;
		}

		$note = trim( wp_kses_post( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.

		if ( '' === $note ) {
			$product->delete_meta_data( $key );
		} else {
			$product->update_meta_data( $key, $note );
		}
	}
} );

/**
 * Per-variation overrides, for a range where one size has a different lead time
 * from the rest. The placeholder says "inherited" rather than repeating the
 * example, so an empty field reads as inheritance and not as a blank.
 *
 * @param int     $loop           Row index in the variations list.
 * @param array   $variation_data Unused; WooCommerce passes legacy meta.
 * @param WP_Post $variation      The variation post.
 */
add_action( 'woocommerce_variation_options_inventory', function ( int $loop, array $variation_data, WP_Post $variation ): void {
	foreach ( dorotape_stock_note_fields() as $key => $field ) {
		woocommerce_wp_textarea_input(
			array(
				'id'            => $key . '_' . $loop,
				'name'          => $key . '[' . $loop . ']',
				'label'         => $field['label'],
				'placeholder'   => __( 'Inherited from the product', 'dorotape' ),
				'description'   => $field['description'],
				'desc_tip'      => false,
				'value'         => (string) get_post_meta( $variation->ID, $key, true ),
				'rows'          => 2,
				'wrapper_class' => 'form-row form-row-full',
			)
		);
	}
}, 10, 3 );

/**
 * Persist the per-variation overrides.
 *
 * @param int $variation_id
 * @param int $loop
 */
add_action( 'woocommerce_save_product_variation', function ( int $variation_id, int $loop ): void {
	foreach ( array_keys( dorotape_stock_note_fields() ) as $key ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC verified.
		if ( ! isset( $_POST[ $key ][ $loop ] ) ) {
			continue;
		}

		$note = trim( wp_kses_post( wp_unslash( $_POST[ $key ][ $loop ] ) ) );
		// phpcs:enable

		if ( '' === $note ) {
			delete_post_meta( $variation_id, $key );
		} else {
			update_post_meta( $variation_id, $key, $note );
		}
	}
}, 10, 2 );
