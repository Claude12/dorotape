<?php
declare( strict_types=1 );
/**
 * Block checkout registration for the theme's own gateways.
 *
 * One class, used twice: the two methods differ only in their id, and
 * everything the block checkout wants to know - is it on, what does it say,
 * which script registers it - can be answered from that id alone.
 *
 * See the note in inc/payment-methods.php for why this exists at all.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * A Dorotape gateway, as the block checkout sees it.
 */
final class Dorotape_Blocks_Payment_Method extends AbstractPaymentMethodType {

	/**
	 * The gateway id, which is also this type's name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * @param string $name Gateway id.
	 */
	public function __construct( string $name ) {
		$this->name = $name;
	}

	/**
	 * Read the gateway's settings.
	 *
	 * Called by the registry before anything else is asked of this class.
	 */
	public function initialize(): void {
		$this->settings = (array) get_option( 'woocommerce_' . $this->name . '_settings', array() );
	}

	/**
	 * The gateway itself, or null if WooCommerce has not built it.
	 *
	 * Everything below asks the gateway rather than reading
	 * woocommerce_<id>_settings directly, and it has to. Until someone opens
	 * the settings screen and presses save, that option does not exist: a
	 * gateway's defaults live in its form_fields and are applied in memory by
	 * init_settings(). Reading the option gives you an empty string for the
	 * title on a fresh install, which is a payment method with no label on it.
	 */
	private function gateway(): ?WC_Payment_Gateway {
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		$gateway  = $gateways[ $this->name ] ?? null;

		return $gateway instanceof WC_Payment_Gateway ? $gateway : null;
	}

	/**
	 * Is the method switched on?
	 */
	public function is_active(): bool {
		$gateway = $this->gateway();

		return $gateway && 'yes' === $gateway->enabled;
	}

	/**
	 * The script that registers this method in the browser.
	 *
	 * Both methods share one script, and WordPress will not enqueue the same
	 * handle twice, so returning it from both types costs nothing.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles(): array {
		return array( dorotape_register_payment_blocks_script() );
	}

	/**
	 * What the script is told about this method.
	 *
	 * Reaches the browser as wc.wcSettings.getSetting( '<id>_data' ).
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data(): array {
		$gateway = $this->gateway();

		return array(
			'title'       => $gateway ? $gateway->get_title() : '',
			'description' => $gateway ? $gateway->get_description() : '',
			'supports'    => $this->get_supported_features(),
		);
	}

	/**
	 * @return string[]
	 */
	public function get_supported_features(): array {
		$gateway = $this->gateway();

		return $gateway
			? array_values( array_filter( $gateway->supports, array( $gateway, 'supports' ) ) )
			: array( 'products' );
	}
}
