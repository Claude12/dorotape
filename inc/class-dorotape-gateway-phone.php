<?php
declare( strict_types=1 );
/**
 * "Call me and I will provide my payment information over the telephone"
 *
 * The third of the old site's four payment methods. The order is placed, we
 * ring the customer, and the card is taken over the phone by whoever is on the
 * sales desk. Nothing is charged here, so the order waits on hold.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/class-dorotape-gateway-offline.php';

/**
 * Pay by telephone.
 */
class Dorotape_Gateway_Phone extends Dorotape_Gateway_Offline {

	public function __construct() {
		$this->id = 'dorotape_phone';

		parent::__construct();
	}

	/**
	 * The old site's wording, kept to the word.
	 *
	 * The customer-facing title is the one sentence on this screen we should
	 * not improve on: people recognise their own checkout, and this is the
	 * line they have been picking for years.
	 *
	 * @return array{title: string, description: string, instructions: string, method_title: string, method_description: string}
	 */
	protected function defaults(): array {
		return array(
			'method_title'       => __( 'Pay by telephone (Dorotape)', 'dorotape' ),
			'method_description' => __( 'Places the order and holds it while a member of the sales team rings the customer for card details.', 'dorotape' ),
			'title'              => __( 'Call me and I will provide my payment information over the telephone', 'dorotape' ),
			'description'        => __( 'We will call you on the number above during office hours, Monday to Friday, to take payment. Your order is held until then.', 'dorotape' ),
			'instructions'       => __( 'Thank you for your order. We will call you during office hours, Monday to Friday, to take payment over the telephone. Your order will be dispatched once payment has been taken.', 'dorotape' ),
		);
	}
}
