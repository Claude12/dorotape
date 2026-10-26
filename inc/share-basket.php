<?php
declare( strict_types=1 );
/**
 * Share a basket as a link.
 *
 * "If I add 1220mm x 15m of L101 to my wishlist and then go into that list, it
 * only shows the product and the price per metre, not the width or the
 * quantity (or cut widths) ... someone is on site but needs someone in the
 * office to place an order. The other option would be to see if we can
 * whatsapp a basket instead of a wishlist?" (Site Edits, October 2026,
 * Wishlist A3).
 *
 * The wishlist is YITH's, and it stores a product and nothing else, so making
 * it carry a width, a length and a list of cuts would mean rewriting the
 * plugin's data. The basket already holds all three: the width is the
 * variation, the length or roll count is the quantity, and the cuts are the
 * dt_cut_sizes note inc/cutsize.php puts on the line. So the basket is what
 * is shared. The link carries the lines themselves; whoever opens it gets
 * them added to their own basket, exactly as they were.
 *
 * The link holds the lines rather than pointing at a stored copy, so nothing
 * is kept on the server and a link never expires. It is signed, because the
 * cut note is free text that reaches the order and the warehouse: a link this
 * site did not make is ignored, rather than letting anyone put any wording on
 * a line.
 *
 * Prices are not in the link. Whoever opens it is priced as themselves, the
 * same as if they had added the lines by hand.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The query parameter a shared link carries.
 */
const DOROTAPE_SHARE_BASKET_PARAM = 'dt_basket';

/**
 * Most lines a link will add. A basket is a handful; this only stops a forged
 * or mangled link doing a lot of work before its signature is checked.
 */
const DOROTAPE_SHARE_BASKET_MAX_LINES = 50;

/**
 * Signature for a payload, short enough to keep the link readable.
 *
 * @param string $payload Encoded lines.
 */
function dorotape_share_basket_signature( string $payload ): string {
	return substr( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ), 0, 16 );
}

/**
 * The link for the current basket, or null when the basket is empty.
 *
 * Each line is [product or variation id, quantity, cut note], with the note
 * left off when there is none.
 */
function dorotape_share_basket_url(): ?string {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return null;
	}

	$lines = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$line = array(
			(int) ( $item['variation_id'] ?: $item['product_id'] ),
			(int) $item['quantity'],
		);
		if ( ! empty( $item['dt_cut_sizes'] ) ) {
			$line[] = (string) $item['dt_cut_sizes'];
		}
		$lines[] = $line;
	}

	$payload = rtrim( strtr( base64_encode( (string) wp_json_encode( $lines ) ), '+/', '-_' ), '=' );

	return add_query_arg(
		DOROTAPE_SHARE_BASKET_PARAM,
		$payload . '.' . dorotape_share_basket_signature( $payload ),
		wc_get_cart_url()
	);
}

/**
 * Lines from a shared link, or null when it is not one this site made.
 *
 * @param string $value The parameter's value.
 * @return array<int, array{0: int, 1: int, 2?: string}>|null
 */
function dorotape_share_basket_decode( string $value ): ?array {
	$parts = explode( '.', $value );
	if ( 2 !== count( $parts ) || ! hash_equals( dorotape_share_basket_signature( $parts[0] ), $parts[1] ) ) {
		return null;
	}

	$lines = json_decode( (string) base64_decode( strtr( $parts[0], '-_', '+/' ) ), true );

	return is_array( $lines ) ? array_slice( $lines, 0, DOROTAPE_SHARE_BASKET_MAX_LINES ) : null;
}

/**
 * Add a shared link's lines to the visitor's basket, then drop the parameter.
 *
 * Added through WC_Cart::add_to_cart() directly rather than the add to cart
 * form, so the cut box's validation in inc/cutsize.php, which splits a posted
 * form into a line per roll, does not run again on lines that are already
 * split. Lines are added to whatever is in the basket, not swapped for it: the
 * person in the office may have a basket of their own going.
 *
 * On wp_loaded, where WooCommerce handles its own ?add-to-cart links, so the
 * session and the cart are both up.
 */
add_action(
	'wp_loaded',
	function (): void {
		if ( empty( $_GET[ DOROTAPE_SHARE_BASKET_PARAM ] ) || ! function_exists( 'WC' ) || ! WC()->cart ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a shared link, checked by its signature.
			return;
		}

		$lines = dorotape_share_basket_decode( sanitize_text_field( wp_unslash( $_GET[ DOROTAPE_SHARE_BASKET_PARAM ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( null === $lines ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		// A first-time visitor has no session cookie yet, and without one the
		// basket built here would be gone on the redirect.
		if ( WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$added   = 0;
		$missing = 0;

		foreach ( $lines as $line ) {
			$product  = is_array( $line ) ? wc_get_product( (int) ( $line[0] ?? 0 ) ) : null;
			$quantity = is_array( $line ) ? max( 1, (int) ( $line[1] ?? 1 ) ) : 0;

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				++$missing;
				continue;
			}

			$data = array();
			if ( ! empty( $line[2] ) ) {
				$data['dt_cut_sizes'] = sanitize_text_field( (string) $line[2] );
			}

			$key = $product->is_type( 'variation' )
				? WC()->cart->add_to_cart( $product->get_parent_id(), $quantity, $product->get_id(), $product->get_variation_attributes(), $data )
				: WC()->cart->add_to_cart( $product->get_id(), $quantity, 0, array(), $data );

			if ( $key ) {
				++$added;
			} else {
				++$missing;
			}
		}

		// add_to_cart() raises its own notice when it refuses a line; the count
		// below already says so, and two notices for one line is noise.
		wc_clear_notices();

		if ( $added ) {
			wc_add_notice(
				str_replace(
					'{count}',
					(string) $added,
					dorotape_woo_pages_field( 'woo_share_added', __( '{count} items from a shared basket have been added to your basket.', 'dorotape' ) )
				),
				'success'
			);
		}

		if ( $missing ) {
			wc_add_notice(
				str_replace(
					'{count}',
					(string) $missing,
					dorotape_woo_pages_field( 'woo_share_missing', __( '{count} items in the shared basket are no longer available, so they were left out.', 'dorotape' ) )
				),
				'notice'
			);
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	},
	20
);

/**
 * The message a share sends, with the link in it.
 *
 * @param string $url The basket link.
 */
function dorotape_share_basket_message( string $url ): string {
	$message = dorotape_woo_pages_field( 'woo_share_message', __( 'Here is a Dorotape basket ready to order: {link}', 'dorotape' ) );

	return str_contains( $message, '{link}' ) ? str_replace( '{link}', $url, $message ) : $message . ' ' . $url;
}

/**
 * The share buttons under the basket.
 *
 * Real links rather than buttons that build one on click, so WhatsApp and
 * email open the way any link does and no popup blocker is involved. The
 * basket changes without a page load, so assets/js/lib/share-basket.js
 * rewrites the three from the Store API's cart, where the current link is
 * published below. Rendered hidden when the basket is empty.
 */
function dorotape_share_basket_markup(): string {
	$url     = dorotape_share_basket_url();
	$message = $url ? dorotape_share_basket_message( $url ) : '';
	$subject = dorotape_woo_pages_field( 'woo_share_subject', __( 'Dorotape basket', 'dorotape' ) );
	$copy    = dorotape_woo_pages_field( 'woo_share_copy', __( 'Copy link', 'dorotape' ) );

	ob_start();
	?>
	<div class="share-basket" data-share-basket data-subject="<?php echo esc_attr( $subject ); ?>"<?php echo $url ? '' : ' hidden'; ?>>
		<p class="share-basket__title"><?php echo esc_html( dorotape_woo_pages_field( 'woo_share_heading', __( 'Share this basket', 'dorotape' ) ) ); ?></p>
		<ul class="share-basket__list">
			<li>
				<a class="share-basket__button" data-share="whatsapp" href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $message ) ); ?>" target="_blank" rel="noopener">
					<?php echo dorotape_ui_icon( 'message-circle', 'share-basket__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<?php echo esc_html( dorotape_woo_pages_field( 'woo_share_whatsapp', __( 'WhatsApp', 'dorotape' ) ) ); ?>
				</a>
			</li>
			<li>
				<a class="share-basket__button" data-share="email" href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $message ), array( 'mailto' ) ); ?>">
					<?php echo dorotape_ui_icon( 'mail', 'share-basket__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<?php echo esc_html( dorotape_woo_pages_field( 'woo_share_email', __( 'Email', 'dorotape' ) ) ); ?>
				</a>
			</li>
			<li>
				<button type="button" class="share-basket__button" data-share="copy" data-url="<?php echo esc_url( (string) $url ); ?>" data-copied="<?php echo esc_attr( dorotape_woo_pages_field( 'woo_share_copied', __( 'Link copied', 'dorotape' ) ) ); ?>">
					<?php echo dorotape_ui_icon( 'link', 'share-basket__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span class="share-basket__label"><?php echo esc_html( $copy ); ?></span>
				</button>
			</li>
		</ul>
	</div>
	<?php
	return (string) ob_get_clean();
}

add_filter(
	'render_block',
	function ( $content, $block ) {
		if ( 'woocommerce/cart' !== ( $block['blockName'] ?? '' ) ) {
			return $content;
		}

		return $content . dorotape_share_basket_markup();
	},
	20,
	2
);

/**
 * The current link and message on the Store API's cart, which the basket
 * re-reads after every change. Registered on init for the reason
 * inc/free-delivery.php gives.
 */
add_action(
	'init',
	function (): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => 'dorotape_share',
				'data_callback'   => static function (): array {
					$url = dorotape_share_basket_url();

					return array(
						'url'     => $url,
						'message' => $url ? dorotape_share_basket_message( $url ) : null,
					);
				},
				'schema_callback' => static fn (): array => array(
					'url'     => array(
						'description' => __( 'A link that adds this basket to whoever opens it, or null.', 'dorotape' ),
						'type'        => array( 'string', 'null' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'message' => array(
						'description' => __( 'The share message with the link in it, or null.', 'dorotape' ),
						'type'        => array( 'string', 'null' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
				'schema_type'     => ARRAY_A,
			)
		);
	}
);
