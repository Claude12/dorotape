<?php
/**
 * Price on Application (POA)
 *
 * Products with no price set are treated as POA across the whole site:
 *  - Price display replaced with a "Price on Application" badge.
 *  - Add-to-cart removed on single product pages; replaced with a WPForms
 *    enquiry form.
 *  - Archive / loop cards show an "Enquire" button linking to the product.
 *  - A WooCommerce > Settings > Products section lets an admin pick which
 *    WPForms form appears on every POA product page.
 *
 * @package dorotape
 */

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * True when the product (or its parent for variations) has _price_poa=1.
 *
 * @param WC_Product $product
 * @return bool
 */
function dorotape_is_poa( WC_Product $product ): bool {
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return '1' === get_post_meta( $id, '_price_poa', true );
}

// ─── Admin toggle (Product data → Advanced) ───────────────────────────────────

/**
 * Checkbox so shop managers can mark any product as POA from the CMS.
 * Sits alongside the "Cut sizes box" toggle. Stored as _price_poa='1',
 * the same key the migration set.
 */
add_action( 'woocommerce_product_options_advanced', function (): void {
	woocommerce_wp_checkbox(
		array(
			'id'          => '_price_poa',
			'cbvalue'     => '1', // matches the migrated meta value
			'label'       => __( 'Price on Application', 'dorotape' ),
			'description' => __( 'Hide prices and replace add-to-cart with the POA enquiry form (chosen under WooCommerce → Settings → Products).', 'dorotape' ),
		)
	);
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	if ( isset( $_POST['_price_poa'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
		$product->update_meta_data( '_price_poa', '1' );
	} else {
		$product->delete_meta_data( '_price_poa' );
	}
} );

// ─── Price display ────────────────────────────────────────────────────────────

add_filter( 'woocommerce_get_price_html', function ( string $html, WC_Product $product ): string {
	if ( dorotape_is_poa( $product ) ) {
		return '<span class="dt-poa-badge">'
			. esc_html__( 'Price on Application', 'dorotape' )
			. '</span>';
	}
	return $html;
}, 10, 2 );

// ─── Single product page ──────────────────────────────────────────────────────

/**
 * Remove the add-to-cart form before the summary hook loop runs so the
 * enquiry form can take its place at the same priority.
 */
add_action( 'woocommerce_before_single_product_summary', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! dorotape_is_poa( $product ) ) {
		return;
	}
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
}, 5 );

/**
 * Render the WPForms enquiry form at the add-to-cart position (priority 30).
 *
 * Two hidden fields are pre-filled so submissions record which product was
 * enquired about. To activate this, add two Hidden fields to the WPForms form
 * with the Field Keys (Advanced tab) set to:
 *   product-name  →  pre-filled with the product title
 *   product-id    →  pre-filled with the WooCommerce product ID
 *
 * The confirmation message can then reference them via WPForms smart tags.
 */
add_action( 'woocommerce_single_product_summary', function (): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! dorotape_is_poa( $product ) ) {
		return;
	}

	$form_id = absint( get_option( 'dorotape_poa_form_id', 0 ) );
	if ( ! $form_id || ! function_exists( 'wpforms' ) ) {
		return;
	}

	echo '<div class="dt-poa-enquiry">';
	echo '<p class="dt-poa-enquiry__intro">'
		. esc_html__( 'This product is priced on application. Complete the form below and our team will be in touch.', 'dorotape' )
		. '</p>';

	// field_values pre-fills the hidden product fields automatically.
	echo do_shortcode(
		'[wpforms id="' . esc_attr( $form_id ) . '" '
		. 'field_values="product-name=' . rawurlencode( $product->get_name() ) . '&product-id=' . rawurlencode( $product->get_id() ) . '"]'
	);
	echo '</div>';
}, 30 );

// ─── Archive / loop cards ─────────────────────────────────────────────────────

/**
 * Replace the add-to-cart button on product cards with an "Enquire" link that
 * goes directly to the product page where the form lives.
 */
add_filter( 'woocommerce_loop_add_to_cart_link', function ( string $link, WC_Product $product ): string {
	if ( dorotape_is_poa( $product ) ) {
		return sprintf(
			'<a href="%s" class="button dt-btn-enquire">%s</a>',
			esc_url( get_permalink( $product->get_id() ) ),
			esc_html__( 'Enquire', 'dorotape' )
		);
	}
	return $link;
}, 10, 2 );

// ─── Admin settings ───────────────────────────────────────────────────────────

/**
 * Append a POA section to WooCommerce › Settings › Products (default section).
 *
 * Shows a select dropdown listing every published WPForms form so the admin
 * can choose the enquiry form without having to look up form IDs manually.
 */
add_filter( 'woocommerce_get_settings_products', function ( array $settings, string $section_id ): array {
	if ( '' !== $section_id ) {
		return $settings;
	}

	// Query all published WPForms forms for the select dropdown.
	$forms   = get_posts( [
		'post_type'      => 'wpforms',
		'post_status'    => 'publish',
		'numberposts'    => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );
	$options = [ '' => esc_html__( '— No form selected —', 'dorotape' ) ];
	foreach ( $forms as $form ) {
		$options[ (string) $form->ID ] = $form->post_title . '  (#' . $form->ID . ')';
	}

	$poa_settings = [
		[
			'title' => esc_html__( 'Price on Application (POA)', 'dorotape' ),
			'type'  => 'title',
			'desc'  => wp_kses_post(
				__( 'Products with no price set are shown as POA. The form below appears in place of the add&#8209;to&#8209;cart button on every POA product page.<br><br>'
					. '<strong>Form setup:</strong> in WPForms, open the chosen form and add two '
					. '<em>Hidden</em> fields. Set the <strong>Field Key</strong> (field Advanced tab) to '
					. '<code>product-name</code> and <code>product-id</code> respectively. '
					. 'Those fields will be pre&#8209;filled automatically from the product page, '
					. 'and can be referenced in your confirmation message using WPForms smart tags.',
					'dorotape'
				)
			),
			'id'    => 'dorotape_poa_section',
		],
		[
			'title'   => esc_html__( 'Enquiry Form', 'dorotape' ),
			'type'    => 'select',
			'id'      => 'dorotape_poa_form_id',
			'options' => $options,
			'default' => '',
			'desc'    => esc_html__( 'Displayed on every product with no price.', 'dorotape' ),
			'css'     => 'min-width: 320px;',
		],
		[
			'type' => 'sectionend',
			'id'   => 'dorotape_poa_section_end',
		],
	];

	// Insert the POA block before the last entry (the main sectionend).
	array_splice( $settings, -1, 0, $poa_settings );
	return $settings;
}, 10, 2 );
