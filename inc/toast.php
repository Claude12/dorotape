<?php
declare( strict_types=1 );
/**
 * Toasts: the site's transient feedback.
 *
 * "Added to your basket", "Saved to your wishlist", "Coupon applied". Short
 * confirmations that used to print as a band at the top of the page, which on
 * the product page pushed the hero down and on the wishlist page stacked two
 * of them above the title.
 *
 * Two routes feed the same stack, because this site adds to the basket two
 * different ways:
 *
 *   server  A single product posts its form and the page reloads, so the
 *           confirmation is already in WooCommerce's notice queue by the time
 *           anything renders. dorotape_toast_capture() takes the success
 *           notices out of that queue before the page prints them and hands
 *           them to the front end as JSON instead.
 *
 *   client  A card in a grid adds over AJAX and the page never reloads, so
 *           there is no queue to read. assets/js/lib/toast.js listens for the
 *           events WooCommerce and YITH fire and raises a toast itself.
 *
 * Only success notices are taken. An error belongs beside the field that
 * caused it, where the person is already looking, so WooCommerce keeps
 * printing those where it always did.
 *
 * Markup is dorotape_toast_host(), styling assets/scss/components/_toast.scss.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Success notices lifted out of WooCommerce's queue, waiting for the footer.
 *
 * @var array<int, array{text:string, type:string}>
 */
$GLOBALS['dorotape_toasts'] = array();

/**
 * Take the success notices before the page can print them.
 *
 * On template_redirect, which is after WC_Form_Handler has run the add-to-cart
 * POST and queued its message, and before any template has had the chance to
 * call woocommerce_output_all_notices().
 *
 * Errors and info notices are put straight back, so WooCommerce's own output
 * still has them.
 */
function dorotape_toast_capture(): void {
	if ( is_admin() || ! function_exists( 'wc_get_notices' ) || ! WC()->session ) {
		return;
	}

	$notices = wc_get_notices();

	if ( empty( $notices['success'] ) ) {
		return;
	}

	foreach ( $notices['success'] as $notice ) {
		$text = is_array( $notice ) ? (string) ( $notice['notice'] ?? '' ) : (string) $notice;

		// Plugins write their confirmations as a sentence with a button in
		// it. The toast raises its own action, in its own place, with the
		// rest of the site's buttons, so an anchor goes out whole rather
		// than leaving its label stranded mid-sentence. WooCommerce's own
		// is already gone by here, removed in
		// dorotape_toast_add_to_cart_message().
		$text = (string) preg_replace( '#<a\b[^>]*>.*?</a>#is', '', $text );
		$text = trim( wp_strip_all_tags( $text ) );

		if ( '' === $text ) {
			continue;
		}

		$GLOBALS['dorotape_toasts'][] = array(
			'text' => $text,
			'type' => 'success',
		);
	}

	unset( $notices['success'] );
	wc_set_notices( $notices );
}
add_action( 'template_redirect', 'dorotape_toast_capture', 100 );

/**
 * The stack every toast is appended to, plus whatever the page arrived with.
 *
 * One host for the whole site, rendered empty on pages with nothing to say so
 * that a toast raised later in the session has somewhere to go without the
 * script building a container first.
 *
 * aria-live="polite" on the host rather than on each toast: the region has to
 * be in the document before the content changes for a screen reader to
 * announce it, and a live region created at the same moment as its first
 * message is not announced at all.
 */
function dorotape_toast_host(): void {
	$queued = (array) ( $GLOBALS['dorotape_toasts'] ?? array() );

	?>
	<div
		class="toast-host"
		data-toast-host
		role="status"
		aria-live="polite"
		aria-atomic="false"
		aria-label="<?php esc_attr_e( 'Notifications', 'dorotape' ); ?>"
	></div>
	<?php

	if ( empty( $queued ) ) {
		return;
	}

	// A JSON script block rather than a data attribute: the text is a
	// sentence with quotes and ampersands in it, and this is the one place
	// WordPress gives for handing structured data to a script without
	// inlining it into JavaScript.
	?>
	<script type="application/json" data-toast-queue>
		<?php echo wp_json_encode( array_values( $queued ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode escapes for this context. ?>
	</script>
	<?php
}
add_action( 'wp_footer', 'dorotape_toast_host', 20 );

/**
 * The basket confirmation, in the site's own words and without the button.
 *
 * WooCommerce writes "has been added to your cart" and puts a View cart button
 * inside the sentence. This site says basket everywhere else, from the header
 * link down, and the toast carries its own View basket action, so the message
 * is rebuilt here rather than unpicked afterwards.
 *
 * It is a filter on the message rather than a string replacement on the
 * finished notice because "cart" is a translated word: a site running in
 * another language would not match it.
 *
 * @param string             $message  WooCommerce's own message.
 * @param array<int, int>    $products Quantity added, keyed by product id.
 * @return string
 */
function dorotape_toast_add_to_cart_message( string $message, array $products ): string {
	$titles = array();
	$count  = 0;

	foreach ( $products as $product_id => $qty ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			continue;
		}

		$count   += $qty;
		$titles[] = sprintf( _x( '&ldquo;%s&rdquo;', 'product name in the basket confirmation', 'dorotape' ), $product->get_name() );
	}

	if ( empty( $titles ) ) {
		return $message;
	}

	// More than one product in one submit is the quick-add grid on a product
	// page. Naming all of them makes a toast several lines tall, so past one
	// it is the number that matters.
	if ( count( $titles ) > 1 ) {
		return sprintf(
			/* translators: %d: number of items added. */
			_n( '%d item has been added to your basket.', '%d items have been added to your basket.', $count, 'dorotape' ),
			$count
		);
	}

	$title = $titles[0];

	if ( $count > 1 ) {
		return sprintf(
			/* translators: 1: quantity, 2: product name. */
			esc_html__( '%1$d x %2$s has been added to your basket.', 'dorotape' ),
			$count,
			$title
		);
	}

	return sprintf(
		/* translators: %s: product name. */
		esc_html__( '%s has been added to your basket.', 'dorotape' ),
		$title
	);
}
add_filter( 'wc_add_to_cart_message_html', 'dorotape_toast_add_to_cart_message', 10, 2 );

/**
 * The two URLs a toast can offer to send someone to.
 *
 * Localised rather than written into the bundle because both are editable
 * pages: a site that renames /cart/ would otherwise get a toast pointing at
 * nothing, and the bundle is shared across installs.
 */
function dorotape_toast_js_data(): void {
	wp_localize_script(
		'dorotape-design-system',
		'dorotapeToastData',
		array(
			'cartUrl'     => dorotape_header_wc_url( 'cart' ),
			'wishlistUrl' => dorotape_header_wishlist_url(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'dorotape_toast_js_data', 20 );

/**
 * Keep the header's basket count honest when a card adds over AJAX.
 *
 * WooCommerce replaces every element matching a fragment's key with the markup
 * it is given. The count is its own span for exactly this reason: the basket
 * link is written twice in header.php, once in the utility bar and once in the
 * drawer, and they are not identical, so the link itself cannot be the
 * fragment. The span inside them is.
 *
 * @param array<string, string> $fragments Fragments keyed by jQuery selector.
 * @return array<string, string>
 */
function dorotape_toast_cart_fragment( array $fragments ): array {
	$fragments['span.site-header__utility-count--cart'] = sprintf(
		'<span class="site-header__utility-count site-header__utility-count--cart">%d</span>',
		dorotape_header_cart_count()
	);

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'dorotape_toast_cart_fragment' );
