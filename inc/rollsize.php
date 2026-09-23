<?php
/**
 * Roll size as product data.
 *
 * The roll width used to live only in the product title, where
 * dorotape_cutsize_max_width() found it with a regex. That made the title
 * load-bearing: editing it for readability or SEO silently removed the cut
 * box's maximum width and its validation.
 *
 * These fields give the width and length a real home. Nothing is migrated:
 * the title regex stays as the fallback, so every product that has always
 * relied on it carries on working untouched.
 *
 * @package Dorotape
 */

defined( 'ABSPATH' ) || exit;

// ─── Getters ──────────────────────────────────────────────────────────────────

/**
 * Roll width in millimetres, or null when not set.
 *
 * Variations inherit from their parent, matching dorotape_price_unit().
 *
 * @param int $product_id
 * @return int|null
 */
function dorotape_roll_width_mm( int $product_id ): ?int {
	$width = get_post_meta( $product_id, '_dt_roll_width_mm', true );

	if ( '' === $width || null === $width ) {
		$parent = wp_get_post_parent_id( $product_id );
		if ( $parent ) {
			$width = get_post_meta( $parent, '_dt_roll_width_mm', true );
		}
	}

	$width = (int) $width;

	return $width > 0 ? $width : null;
}

/**
 * Roll length in metres, or null when not set. Kept as a string so 91.4 does
 * not become 91.400000000000006 on the way to the page.
 *
 * @param int $product_id
 * @return string|null
 */
function dorotape_roll_length_m( int $product_id ): ?string {
	$length = get_post_meta( $product_id, '_dt_roll_length_m', true );

	if ( '' === $length || null === $length ) {
		$parent = wp_get_post_parent_id( $product_id );
		if ( $parent ) {
			$length = get_post_meta( $parent, '_dt_roll_length_m', true );
		}
	}

	$length = trim( (string) $length );

	if ( '' === $length || (float) $length <= 0 ) {
		return null;
	}

	// 91.40 -> 91.4, but 100 stays 100: trailing zeros only mean nothing to
	// the right of a decimal point, and a 100m roll read back as "1m".
	return false !== strpos( $length, '.' ) ? rtrim( rtrim( $length, '0' ), '.' ) : $length;
}

/**
 * Human roll size, e.g. "1220mm x 91.4m". Returns '' when neither is set, so
 * callers can concatenate without checking.
 *
 * @param int $product_id
 * @return string
 */
function dorotape_roll_size_label( int $product_id ): string {
	$width  = dorotape_roll_width_mm( $product_id );
	$length = dorotape_roll_length_m( $product_id );

	if ( $width && $length ) {
		return sprintf( '%dmm x %sm', $width, $length );
	}
	if ( $width ) {
		return sprintf( '%dmm', $width );
	}
	if ( $length ) {
		return sprintf( '%sm', $length );
	}

	return '';
}

/**
 * Quantity pricing table header, with the roll size appended where we hold it.
 *
 * "Price per roll" becomes "Price per roll (1220mm x 91.4m)", which is where
 * the client wants the size to live once it comes out of the product title.
 *
 * @param array $unit_strings Result of dorotape_unit_strings().
 * @param int   $product_id
 * @return string
 */
function dorotape_price_header( array $unit_strings, int $product_id ): string {
	$header = $unit_strings['header'];
	$size   = dorotape_roll_size_label( $product_id );

	if ( '' === $size ) {
		return $header;
	}

	/* translators: 1: column header e.g. "Price per roll", 2: roll size e.g. "1220mm x 91.4m" */
	return sprintf( __( '%1$s (%2$s)', 'dorotape' ), $header, $size );
}

// ─── Admin fields (Product data → Advanced) ───────────────────────────────────

add_action( 'woocommerce_product_options_advanced', function (): void {
	global $post;

	woocommerce_wp_text_input(
		array(
			'id'                => '_dt_roll_width_mm',
			'label'             => __( 'Roll width (mm)', 'dorotape' ),
			'value'             => get_post_meta( $post->ID, '_dt_roll_width_mm', true ),
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '1',
				'step' => '1',
			),
			'description'       => __( 'Sets the maximum cut width and shows beside the price column. Leave blank to keep reading the width from the product title.', 'dorotape' ),
			'desc_tip'          => true,
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'                => '_dt_roll_length_m',
			'label'             => __( 'Roll length (m)', 'dorotape' ),
			'value'             => get_post_meta( $post->ID, '_dt_roll_length_m', true ),
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => '0.1',
			),
			'description'       => __( 'Display only. Shown beside the price column as part of the roll size.', 'dorotape' ),
			'desc_tip'          => true,
		)
	);
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC verified.
	if ( isset( $_POST['_dt_roll_width_mm'] ) ) {
		$width = (int) wc_clean( wp_unslash( $_POST['_dt_roll_width_mm'] ) );
		if ( $width > 0 ) {
			$product->update_meta_data( '_dt_roll_width_mm', $width );
		} else {
			$product->delete_meta_data( '_dt_roll_width_mm' );
		}
	}

	if ( isset( $_POST['_dt_roll_length_m'] ) ) {
		$length = wc_format_decimal( wc_clean( wp_unslash( $_POST['_dt_roll_length_m'] ) ) );
		if ( '' !== $length && (float) $length > 0 ) {
			$product->update_meta_data( '_dt_roll_length_m', $length );
		} else {
			$product->delete_meta_data( '_dt_roll_length_m' );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
} );
