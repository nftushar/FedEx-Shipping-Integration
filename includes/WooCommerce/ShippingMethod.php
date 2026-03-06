<?php
/**
 * FedEx WooCommerce Integration
 *
 * @package FedEx\WooCommerce
 */

namespace FedEx\WooCommerce;

use FedEx\Services\ApiService;

/**
 * WooCommerce shipping method
 */
class ShippingMethod {

	/**
	 * API service
	 *
	 * @var ApiService
	 */
	private $api;

	/**
	 * Constructor
	 *
	 * @param ApiService $api API service
	 */
	public function __construct( ApiService $api ) {
		$this->api = $api;

		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_method' ) );
	}

	/**
	 * Register shipping method
	 *
	 * @param array $methods Existing methods
	 * @return array
	 */
	public function register_method( $methods ) {
		$methods['fedex_shipping'] = 'FedEx\\WooCommerce\\FedExShippingMethod';
		return $methods;
	}
}

/**
 * FedEx shipping method class
 */
if ( class_exists( 'WC_Shipping_Method' ) ) {

	class FedExShippingMethod extends \WC_Shipping_Method {

		/**
		 * Constructor
		 *
		 * @param int $instance_id Instance ID
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id = 'fedex_shipping';
			$this->instance_id = absint( $instance_id );
			$this->method_title = __( 'FedEx Shipping', 'fedex-shipping' );
			$this->method_description = __( 'Real-time FedEx shipping rates', 'fedex-shipping' );
			$this->supports = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);

			$this->init();
		}

		/**
		 * Initialize
		 *
		 * @return void
		 */
		private function init() {
			$this->init_form_fields();
			$this->init_settings();

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Init form fields
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'          => array(
					'title'   => __( 'Enable/Disable', 'fedex-shipping' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable FedEx Shipping', 'fedex-shipping' ),
					'default' => 'yes',
				),
				'title'            => array(
					'title'       => __( 'Method Title', 'fedex-shipping' ),
					'type'        => 'text',
					'description' => __( 'Title displayed to customers', 'fedex-shipping' ),
					'default'     => __( 'FedEx Shipping', 'fedex-shipping' ),
					'desc_tip'    => true,
				),
				'description'      => array(
					'title'       => __( 'Method Description', 'fedex-shipping' ),
					'type'        => 'textarea',
					'description' => __( 'Description displayed to customers', 'fedex-shipping' ),
					'default'     => __( 'Fast and reliable FedEx shipping', 'fedex-shipping' ),
					'desc_tip'    => true,
				),
				'markup'           => array(
					'title'       => __( 'Markup (%)', 'fedex-shipping' ),
					'type'        => 'number',
					'description' => __( 'Percentage markup on rates', 'fedex-shipping' ),
					'default'     => '0',
					'desc_tip'    => true,
				),
				'free_shipping_min' => array(
					'title'       => __( 'Free Shipping Minimum ($)', 'fedex-shipping' ),
					'type'        => 'number',
					'description' => __( 'Offer free FedEx shipping above this order value', 'fedex-shipping' ),
					'default'     => '0',
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Calculate shipping
		 *
		 * @param array $package Cart package
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			if ( empty( $package ) ) {
				return;
			}

			// Get rates from API
			try {
				$api = \FedEx\Container::getInstance()->get( 'api' );
				$shipment_data = $this->prepare_shipment_data( $package );

				if ( ! $shipment_data ) {
					return;
				}

				$rates = $api->get_rates( $shipment_data );

				if ( ! isset( $rates['output']['rateReplyDetails'] ) ) {
					return;
				}

				// Add rates
				foreach ( $rates['output']['rateReplyDetails'] as $rate ) {
					$cost = $this->parse_rate_cost( $rate );

					if ( ! $cost ) {
						continue;
					}

					// Apply markup
					$markup = floatval( $this->get_option( 'markup', 0 ) );
					if ( $markup > 0 ) {
						$cost = $cost * ( 1 + $markup / 100 );
					}

					// Free shipping check
					$free_min = floatval( $this->get_option( 'free_shipping_min', 0 ) );
					if ( $free_min > 0 && isset( $package['contents_cost'] ) && $package['contents_cost'] >= $free_min ) {
						$cost = 0;
					}

					$service_type = $rate['serviceType'];
					$this->add_rate( array(
						'id'    => $this->id . ':' . $service_type,
						'label' => $api->get_service_name( $service_type ),
						'cost'  => $cost,
					) );
				}
			} catch ( \Exception $e ) {
				// Silent fail
			}
		}

		/**
		 * Prepare shipment data from cart
		 *
		 * @param array $package Cart package
		 * @return array|null
		 */
		private function prepare_shipment_data( array $package ) {
			if ( empty( $package['destination']['postcode'] ) ) {
				return null;
			}

			$config = \FedEx\Container::getInstance()->get( 'config' );

			$total_weight = 0;
			foreach ( $package['contents'] as $item ) {
				if ( $item['data']->get_weight() ) {
					$total_weight += $item['data']->get_weight() * $item['quantity'];
				}
			}

			if ( $total_weight <= 0 ) {
				$total_weight = 1;
			}

			return array(
				'accountNumber'     => $config->get_account_number(),
				'requestedShipment' => array(
					'shipper'      => array(
						'address' => array(
							'postalCode'  => '10001',
							'countryCode' => 'US',
						),
					),
					'recipients'   => array(
						array(
							'address' => array(
								'postalCode'  => $package['destination']['postcode'],
								'countryCode' => $package['destination']['country'] ?? 'US',
							),
						),
					),
					'pickupType'   => 'STATION',
					'shipDateStamp' => gmdate( 'Y-m-d' ),
					'totalWeight'  => array(
						'units' => 'LB',
						'value' => $total_weight,
					),
					'totalDimensions' => array(
						'length' => 10,
						'width'  => 6,
						'height' => 4,
						'units'  => 'IN',
					),
				),
			);
		}

		/**
		 * Parse rate cost
		 *
		 * @param array $rate Rate data
		 * @return float|null
		 */
		private function parse_rate_cost( array $rate ) {
			if ( ! isset( $rate['rateReplyDetails'][0]['netCharge'] ) ) {
				return null;
			}

			return floatval( $rate['rateReplyDetails'][0]['netCharge'] );
		}
	}
}
