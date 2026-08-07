<?php
/**
 * Admin interface cleanup
 *
 * @package dorotape
 */

// Remove Comments from the admin menu
function dorotape_remove_admin_menus() {
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'dorotape_remove_admin_menus' );

// Remove Comments and WP logo from the admin toolbar
function dorotape_remove_toolbar_items( WP_Admin_Bar $wp_admin_bar ): void {
	$wp_admin_bar->remove_node( 'comments' );
	$wp_admin_bar->remove_node( 'wp-logo' );
}
add_action( 'admin_bar_menu', 'dorotape_remove_toolbar_items', 999 );

// Remove default dashboard widgets that aren't useful for this project
function dorotape_remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'dorotape_remove_dashboard_widgets' );

// ─── Variation tier pricing field ────────────────────────────────────────────

/**
 * Add an editable Quantity Tiers field to each variation's edit panel.
 *
 * Variable product tiers are stored in _price_tiers post meta on each variation
 * (format: "1-24:11.00;25:9.90"). The standard WC admin has no UI for this —
 * the data came from the Kryptronic CSV migration and was otherwise invisible.
 * This field surfaces it so the client can read and change tier prices directly.
 */
add_action( 'woocommerce_product_after_variable_attributes', function ( int $loop, array $_variation_data, WP_Post $variation ): void {
	$tiers = get_post_meta( $variation->ID, '_price_tiers', true );
	?>
	<div class="form-row form-row-full dorotape-variation-tiers">
		<label for="dorotape_price_tiers_<?php echo esc_attr( $loop ); ?>">
			<?php esc_html_e( 'Quantity Tiers', 'dorotape' ); ?>
		</label>
		<input
			type="text"
			id="dorotape_price_tiers_<?php echo esc_attr( $loop ); ?>"
			name="dorotape_price_tiers[<?php echo esc_attr( $loop ); ?>]"
			value="<?php echo esc_attr( $tiers ); ?>"
			placeholder="e.g. 1-24:11.00;25:9.90"
			class="short"
			style="width:100%;max-width:360px;"
		/>
		<p class="description">
			<?php esc_html_e( 'Quantity discount tiers for this specific width/variation. Format: min:price segments separated by semicolons. For example 1-24:11.00;25:9.90 means £11.00/m for 1–24 metres, £9.90/m for 25+. Each variation can have its own tier prices (wider widths are typically higher). The tier table on the product page updates automatically when a customer selects this variation. Leave blank for no quantity discount on this variation.', 'dorotape' ); ?>
		</p>
	</div>
	<?php
}, 10, 3 );

/**
 * Save the Quantity Tiers field when a variation is saved.
 */
add_action( 'woocommerce_save_product_variation', function ( int $variation_id, int $loop ): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// Nonce verification is handled upstream by WooCommerce's variation save flow.
	if ( ! isset( $_POST['dorotape_price_tiers'][ $loop ] ) ) {
		return;
	}
	$value = sanitize_text_field( wp_unslash( $_POST['dorotape_price_tiers'][ $loop ] ) );
	// phpcs:enable
	if ( '' === $value ) {
		delete_post_meta( $variation_id, '_price_tiers' );
	} else {
		update_post_meta( $variation_id, '_price_tiers', $value );
	}
}, 10, 2 );

// ─── Simple product meta fields ──────────────────────────────────────────────

/**
 * Add Quantity Tiers and Colour Swatch fields to the simple product General tab.
 * Both read/write post_meta directly — no ACF dependency.
 *
 * Quantity Tiers: same _price_tiers format as the variation field
 *   (e.g. "1-24:11.00;25:9.90"). The pricing engine reads this on every cart
 *   calculation — changing the value here takes effect immediately.
 *
 * Colour Swatch: stores a CSS hex colour in the colour_hex post_meta key.
 *   Shown as a CSS swatch on archive cards and the product page when no
 *   product image has been uploaded.
 */
add_action( 'woocommerce_product_options_general_product_data', function (): void {
	global $post;

	echo '<div class="options_group dorotape-product-fields show_if_simple">';

	// Resolve the display value: if ACF corrupted _price_tiers with its field-key
	// reference string, reconstruct the human-readable value from ACF flat meta.
	$tiers_raw = get_post_meta( $post->ID, '_price_tiers', true );
	if ( ! $tiers_raw || str_starts_with( (string) $tiers_raw, 'field_' ) ) {
		$flat = dorotape_read_acf_flat_tiers( $post->ID );
		$tiers_raw = empty( $flat ) ? '' : implode( ';', array_map( static function ( array $t ): string {
			return $t['min_qty'] . ':' . $t['tier_price'];
		}, $flat ) );
	}

	woocommerce_wp_text_input( array(
		'id'          => 'dorotape_price_tiers_simple',
		'label'       => __( 'Quantity Tiers', 'dorotape' ),
		'value'       => $tiers_raw,
		'placeholder' => 'e.g. 1-24:11.00;25:9.90',
		'desc_tip'    => false,
		'description' => __( 'Format: min:price segments separated by semicolons. The price is £/metre at the base width; wider widths scale automatically. Example: 1-24:11.00;25:9.90 → £11.00/m for 1–24m, £9.90/m for 25m+. The table appears on the product page and the discount applies automatically in the cart. Leave blank for no quantity discount.', 'dorotape' ),
	) );

	woocommerce_wp_text_input( array(
		'id'          => 'dorotape_colour_hex',
		'label'       => __( 'Colour Swatch', 'dorotape' ),
		'value'       => get_post_meta( $post->ID, 'colour_hex', true ),
		'placeholder' => 'e.g. #cc0028',
		'desc_tip'    => false,
		'description' => __( 'CSS hex colour shown as a swatch on archive and product pages when no product image is uploaded. Format: #rrggbb, e.g. #cc0028 (red), #1a1a1a (black). Used for coloured vinyl ranges. Leave blank when a real product image is uploaded.', 'dorotape' ),
	) );

	echo '</div>';
} );

/**
 * Save Quantity Tiers and Colour Swatch from the simple product General tab.
 */
add_action( 'woocommerce_process_product_meta', function ( int $post_id ): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['dorotape_price_tiers_simple'] ) ) {
		$tiers = sanitize_text_field( wp_unslash( $_POST['dorotape_price_tiers_simple'] ) );
		if ( '' === $tiers ) {
			delete_post_meta( $post_id, '_price_tiers' );
		} else {
			update_post_meta( $post_id, '_price_tiers', $tiers );
		}
	}

	if ( isset( $_POST['dorotape_colour_hex'] ) ) {
		$hex = sanitize_hex_color( wp_unslash( $_POST['dorotape_colour_hex'] ) );
		if ( ! $hex ) {
			delete_post_meta( $post_id, 'colour_hex' );
		} else {
			update_post_meta( $post_id, 'colour_hex', $hex );
		}
	}
	// phpcs:enable
} );

// ─── Admin interface cleanup ──────────────────────────────────────────────────

// Hide admin bar on the frontend for non-admin users
add_filter( 'show_admin_bar', function( $show ) {
	return current_user_can( 'manage_options' ) ? $show : false;
} );

// Point the login logo link to the site homepage
add_filter( 'login_headerurl', function() {
	return home_url();
} );

// Redirect any attempt to access comment-edit page
function dorotape_redirect_comment_admin() {
	global $pagenow;
	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'dorotape_redirect_comment_admin' );
