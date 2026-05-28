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
function dorotape_remove_toolbar_items( $wp_admin_bar ) {
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
add_action( 'woocommerce_product_after_variable_attributes', function ( int $loop, array $variation_data, WP_Post $variation ): void {
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
			<?php esc_html_e( 'Optional. Format: min[-max]:price segments separated by semicolons. Example: 1-24:11.00;25:9.90 gives £11.00 for qty 1–24 and £9.90 for 25+. Leave blank for no quantity discount.', 'dorotape' ); ?>
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
