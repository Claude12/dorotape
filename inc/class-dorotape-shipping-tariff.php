<?php
/**
 * The Doro Tape delivery tariff as a WooCommerce shipping method.
 *
 * One class serves every zone. Which part of the tariff an instance charges comes
 * from its own `tariff_zone` setting rather than from the zone's name, so renaming
 * a zone in the admin cannot quietly change what it costs to deliver there.
 *
 * The rates themselves are in inc/shipping.php. This class only decides which
 * band of them applies and hands them to WooCommerce.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

class Dorotape_Shipping_Tariff extends WC_Shipping_Method {

	/**
	 * @param int $instance_id Zone method instance.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = DOROTAPE_TARIFF_METHOD;
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Doro Tape tariff', 'dorotape' );
		$this->method_description = __( 'The published delivery tariff. Rates and thresholds are in the theme so a deploy carries them; choose below which part of the tariff this zone charges.', 'dorotape' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Load settings and save them from the zone modal.
	 */
	public function init(): void {
		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled', 'yes' );

		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	/**
	 * Per-instance settings.
	 */
	public function init_form_fields(): void {
		$this->instance_form_fields = array(
			'title'       => array(
				'title'       => __( 'Method title', 'dorotape' ),
				'type'        => 'text',
				'description' => __( 'Shown only when a rate cannot be named more precisely. Individual rates carry their own labels.', 'dorotape' ),
				'default'     => __( 'Delivery', 'dorotape' ),
				'desc_tip'    => true,
			),
			'tariff_zone' => array(
				'title'       => __( 'Tariff', 'dorotape' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'Which part of the published tariff this zone charges. The zone above decides who it applies to; this decides what they pay.', 'dorotape' ),
				'default'     => 'world',
				'options'     => dorotape_shipping_zone_choices(),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Offer the rates for this zone and basket.
	 *
	 * @param array $package
	 */
	public function calculate_shipping( $package = array() ): void {
		$zone = $this->get_option( 'tariff_zone', 'world' );

		$tariff = dorotape_shipping_tariff();
		$which  = dorotape_package_tariff( $package );

		if ( empty( $tariff[ $which ][ $zone ] ) ) {
			return;
		}

		/*
		 * contents_cost is the sum of line totals excluding tax, which is what
		 * the old site's scripts read and what the published thresholds are
		 * quoted against. cart_subtotal would include tax and would tip a
		 * 130.00 basket over the 150 threshold.
		 */
		$subtotal = isset( $package['contents_cost'] ) ? (float) $package['contents_cost'] : 0.0;

		$rates = dorotape_tariff_band( $tariff[ $which ][ $zone ], $subtotal );

		foreach ( $rates as $key => $rate ) {
			$this->add_rate(
				array(
					'id'      => $this->get_rate_id( $key ),
					'label'   => $rate['label'],
					'cost'    => $rate['cost'],
					'package' => $package,
					'meta_data' => array(
						// Which tariff produced this, so an order that looks
						// mispriced later can be traced without guesswork.
						'Tariff' => $which . '/' . $zone,
					),
				)
			);
		}
	}
}
