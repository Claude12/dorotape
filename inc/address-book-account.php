<?php
/**
 * Address book: the My Account screens
 *
 * Storage and the read/write API are in inc/address-book.php. This file is only
 * the customer-facing management screen.
 *
 * Built on a My Account endpoint rather than a page template, so it inherits the
 * account navigation, the login requirement and the theme's styling for free.
 * My Account is the [woocommerce_my_account] shortcode on this site, not the
 * block, so the classic endpoint mechanism is the right one here. See the
 * README's "Cart and checkout" section before assuming the same of checkout.
 *
 * @package dorotape
 */

// ─── Endpoint ─────────────────────────────────────────────────────────────────

add_action( 'init', function (): void {
	add_rewrite_endpoint( DOROTAPE_ADDRESS_BOOK_ENDPOINT, EP_ROOT | EP_PAGES );
} );

/**
 * Flush rewrite rules when the endpoint's rule is actually missing.
 *
 * The obvious version is a hand-bumped version number in an option: flush when
 * it does not match, write it, done. That is what was here, and it 404'd on dev
 * while working locally, because the thing it records is whether *this code* has
 * run before - not whether the rewrite rule exists. The two come apart easily
 * and on this project they were always going to. The database is moved between
 * environments by hand, so the option travels with it and arrives already
 * claiming the work is done; meanwhile the destination regenerates its own
 * rewrite rules, or a migration plugin flushes them, and the endpoint is gone
 * with nothing left to notice. Bumping the number fixes it exactly once and
 * leaves the next database move to break it again.
 *
 * So ask the question directly instead. The rewrite rules are an autoloaded
 * option and already in memory, so looking for the endpoint in them costs
 * nothing and is true regardless of what has happened to the site.
 */
add_action( 'init', function (): void {
	$rules = get_option( 'rewrite_rules' );

	// Plain permalinks. There are no rules to carry the endpoint and none to
	// flush - WooCommerce falls back to query vars, which work already.
	if ( ! is_array( $rules ) || ! $rules ) {
		return;
	}

	foreach ( $rules as $rewrite ) {
		if ( str_contains( (string) $rewrite, DOROTAPE_ADDRESS_BOOK_ENDPOINT . '=' ) ) {
			return; // Present, which is every request but the first.
		}
	}

	// Missing. Rate limit the repair: if the endpoint ever fails to register at
	// all, this would otherwise flush on every single request, and a rewrite
	// flush is one of the more expensive things WordPress does. Once an hour
	// turns that failure mode into something slow rather than something fatal.
	if ( get_transient( 'dorotape_address_book_flush' ) ) {
		return;
	}

	set_transient( 'dorotape_address_book_flush', 1, HOUR_IN_SECONDS );
	flush_rewrite_rules( false );
}, 11 );

/**
 * Tidy away the option the check above replaced.
 *
 * Autoloaded, so the test is a memory read on every request and a database
 * write on exactly one.
 */
add_action( 'init', function (): void {
	if ( false !== get_option( 'dorotape_address_book_rewrites' ) ) {
		delete_option( 'dorotape_address_book_rewrites' );
	}
}, 12 );

/**
 * @param array $vars
 * @return array
 */
add_filter( 'woocommerce_get_query_vars', function ( array $vars ): array {
	$vars[ DOROTAPE_ADDRESS_BOOK_ENDPOINT ] = DOROTAPE_ADDRESS_BOOK_ENDPOINT;
	return $vars;
} );

/**
 * Put the tab directly after Addresses, which is the thing it extends.
 *
 * @param array $items
 * @return array
 */
add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
	$out = array();

	foreach ( $items as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'edit-address' === $key ) {
			$out[ DOROTAPE_ADDRESS_BOOK_ENDPOINT ] = __( 'Address book', 'dorotape' );
		}
	}

	if ( ! isset( $out[ DOROTAPE_ADDRESS_BOOK_ENDPOINT ] ) ) {
		$out[ DOROTAPE_ADDRESS_BOOK_ENDPOINT ] = __( 'Address book', 'dorotape' );
	}

	return $out;
}, 20 );

/**
 * @param string $title
 * @param string $endpoint
 * @return string
 */
add_filter( 'woocommerce_endpoint_' . DOROTAPE_ADDRESS_BOOK_ENDPOINT . '_title', function (): string {
	return __( 'Address book', 'dorotape' );
} );

// ─── Actions ──────────────────────────────────────────────────────────────────

/**
 * Handle add, edit, delete and set-default before anything is rendered, so a
 * successful action can redirect and not be repeatable with a refresh.
 */
add_action( 'template_redirect', function (): void {
	if ( ! is_account_page() || ! isset( $_POST['dorotape_address_action'] ) || ! is_user_logged_in() ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['dorotape_address_action'] ) );

	if ( ! isset( $_POST['dorotape_address_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dorotape_address_nonce'] ) ), 'dorotape_address_' . $action ) ) {
		wc_add_notice( __( 'That form had expired. Please try again.', 'dorotape' ), 'error' );
		return;
	}

	$address_id = isset( $_POST['address_id'] ) ? sanitize_text_field( wp_unslash( $_POST['address_id'] ) ) : '';
	$base_url   = wc_get_account_endpoint_url( DOROTAPE_ADDRESS_BOOK_ENDPOINT );

	if ( 'delete' === $action ) {
		if ( dorotape_delete_address( $address_id ) ) {
			wc_add_notice( __( 'Address deleted.', 'dorotape' ) );
		} else {
			wc_add_notice( __( 'That address could not be found.', 'dorotape' ), 'error' );
		}
		wp_safe_redirect( $base_url );
		exit;
	}

	if ( 'set_default' === $action ) {
		if ( dorotape_set_default_address( $address_id ) ) {
			wc_add_notice( __( 'Default address updated. Checkout will start with this one.', 'dorotape' ) );
		} else {
			wc_add_notice( __( 'That address could not be found.', 'dorotape' ), 'error' );
		}
		wp_safe_redirect( $base_url );
		exit;
	}

	if ( 'save' !== $action ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
	$posted = wp_unslash( $_POST );
	$result = dorotape_save_address( $posted, '' !== $address_id ? $address_id : null );

	if ( is_wp_error( $result ) ) {
		foreach ( $result->get_error_messages() as $message ) {
			wc_add_notice( $message, 'error' );
		}
		return; // Fall through to render, so the form keeps what was typed.
	}

	wc_add_notice( __( 'Address saved.', 'dorotape' ) );
	wp_safe_redirect( $base_url );
	exit;
} );

// ─── Rendering ────────────────────────────────────────────────────────────────

/**
 * The endpoint body.
 */
add_action( 'woocommerce_account_' . DOROTAPE_ADDRESS_BOOK_ENDPOINT . '_endpoint', function (): void {
	$editing = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
	$adding  = isset( $_GET['add'] );

	if ( $adding || '' !== $editing ) {
		dorotape_render_address_form( $editing );
		return;
	}

	dorotape_render_address_list();
} );

/**
 * The list of saved addresses.
 */
function dorotape_render_address_list(): void {
	$book = dorotape_get_address_book();

	printf(
		'<p>%s</p>',
		esc_html__( 'Addresses saved here can be chosen at checkout. Your default billing and shipping addresses are managed under Addresses.', 'dorotape' )
	);

	if ( ! $book ) {
		printf( '<p>%s</p>', esc_html__( 'You have not saved any addresses yet.', 'dorotape' ) );
	} else {
		echo '<ul class="dorotape-address-book">';

		foreach ( $book as $address_id => $address ) {
			$edit_url = add_query_arg( 'edit', rawurlencode( $address_id ), wc_get_account_endpoint_url( DOROTAPE_ADDRESS_BOOK_ENDPOINT ) );

			echo '<li class="dorotape-address-book__item">';
			printf(
				'<h3 class="dorotape-address-book__label">%s <span class="dorotape-address-book__type">%s</span></h3>',
				esc_html( $address['label'] ),
				'billing' === $address['type']
					? esc_html__( 'Invoice', 'dorotape' )
					: esc_html__( 'Delivery', 'dorotape' )
			);
			printf( '<address>%s</address>', wp_kses( dorotape_format_address( $address ), array( 'br' => array() ) ) );

			printf( '<a class="button" href="%s">%s</a> ', esc_url( $edit_url ), esc_html__( 'Edit', 'dorotape' ) );
			dorotape_address_action_button( 'set_default', $address_id, __( 'Make default', 'dorotape' ) );
			dorotape_address_action_button( 'delete', $address_id, __( 'Delete', 'dorotape' ), true );

			echo '</li>';
		}

		echo '</ul>';
	}

	printf(
		'<p><a class="button" href="%s">%s</a></p>',
		esc_url( add_query_arg( 'add', '1', wc_get_account_endpoint_url( DOROTAPE_ADDRESS_BOOK_ENDPOINT ) ) ),
		esc_html__( 'Add an address', 'dorotape' )
	);
}

/**
 * A small single-button form, so destructive actions are never a bare link that
 * a browser or a crawler can follow on its own.
 *
 * @param string $action
 * @param string $address_id
 * @param string $label
 * @param bool   $confirm
 */
function dorotape_address_action_button( string $action, string $address_id, string $label, bool $confirm = false ): void {
	printf(
		'<form method="post" class="dorotape-address-book__action">
			%s
			<input type="hidden" name="dorotape_address_action" value="%s">
			<input type="hidden" name="address_id" value="%s">
			<button type="submit" class="button"%s>%s</button>
		</form>',
		wp_nonce_field( 'dorotape_address_' . $action, 'dorotape_address_nonce', true, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_attr( $action ),
		esc_attr( $address_id ),
		$confirm ? ' data-dorotape-confirm="' . esc_attr__( 'Delete this address?', 'dorotape' ) . '"' : '',
		esc_html( $label )
	);
}

/**
 * The add and edit form.
 *
 * @param string $address_id Empty when adding.
 */
function dorotape_render_address_form( string $address_id ): void {
	$address = '' !== $address_id ? dorotape_get_address( $address_id ) : null;

	if ( '' !== $address_id && null === $address ) {
		printf( '<p>%s</p>', esc_html__( 'That address could not be found.', 'dorotape' ) );
		return;
	}

	// Keep whatever was typed if validation sent us back here.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- display only, all output escaped.
	$posted  = isset( $_POST['dorotape_address_action'] ) ? wp_unslash( $_POST ) : array();
	$address = $address ?? array( 'type' => 'shipping', 'label' => '' );
	$type    = sanitize_key( $posted['type'] ?? $address['type'] ?? 'shipping' );
	$value   = static fn( string $key ): string => (string) ( $posted[ $key ] ?? $address[ $key ] ?? '' );

	echo '<form method="post" class="dorotape-address-form">';
	wp_nonce_field( 'dorotape_address_save', 'dorotape_address_nonce' );
	echo '<input type="hidden" name="dorotape_address_action" value="save">';
	printf( '<input type="hidden" name="address_id" value="%s">', esc_attr( $address_id ) );

	woocommerce_form_field(
		'label',
		array(
			'type'        => 'text',
			'label'       => __( 'Name this address', 'dorotape' ),
			'placeholder' => __( 'e.g. Head office, Site B', 'dorotape' ),
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
		),
		$value( 'label' )
	);

	woocommerce_form_field(
		'type',
		array(
			'type'     => 'select',
			'label'    => __( 'Address type', 'dorotape' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'options'  => array(
				'shipping' => __( 'Delivery address', 'dorotape' ),
				'billing'  => __( 'Invoice address', 'dorotape' ),
			),
		),
		$type
	);

	foreach ( dorotape_address_book_fields( $type ) as $key => $field ) {
		woocommerce_form_field( $key, $field, $value( $key ) );
	}

	printf(
		'<p><button type="submit" class="button">%s</button> <a href="%s">%s</a></p>',
		esc_html__( 'Save address', 'dorotape' ),
		esc_url( wc_get_account_endpoint_url( DOROTAPE_ADDRESS_BOOK_ENDPOINT ) ),
		esc_html__( 'Cancel', 'dorotape' )
	);

	echo '</form>';
}
