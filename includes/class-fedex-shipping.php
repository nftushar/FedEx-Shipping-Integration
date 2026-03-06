<?php
/**
 * FedEx Shipping Integration for WooCommerce
 *
 * @package FedEx_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for WooCommerce shipping method integration
 */
class FedEx_Shipping {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_shipping_method' ) );
	}

	/**
	 * Register shipping method
	 *
	 * @param array $methods Existing methods
	 * @return array Updated methods
	 */
	public function register_shipping_method( $methods ) {
		$methods['fedex_shipping'] = 'WC_FedEx_Shipping_Method';
		return $methods;
	}
}

/**
 * FedEx Shipping Method class
 */
if ( class_exists( 'WC_Shipping_Method' ) ) {

	class WC_FedEx_Shipping_Method extends WC_Shipping_Method {

		/**
		 * Constructor
		 *
		 * @param int $instance_id Shipping method instance ID
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id = 'fedex_shipping';
			$this->instance_id = absint( $instance_id );
			$this->method_title = __( 'FedEx Shipping' );
			$this->method_description = __( 'Real-time FedEx shipping rates' );
			$this->supports = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);

			$this->init();
		}

		/**
		 * Initialize settings
		 */
		public function init() {
			$this->init_form_fields();
			$this->init_settings();

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Define settings
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'          => array(
					'title'       => __( 'Enable/Disable' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable FedEx Shipping' ),
					'default'     => 'yes',
				),
				'title'            => array(
					'title'       => __( 'Method Title' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.' ),
					'default'     => __( 'FedEx Shipping' ),
					'desc_tip'    => true,
				),
				'description'      => array(
					'title'       => __( 'Method Description' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.' ),
					'default'     => __( 'Fast and reliable FedEx shipping' ),
					'desc_tip'    => true,
				),
				'default_service'  => array(
					'title'       => __( 'Default Service' ),
					'type'        => 'select',
					'description' => __( 'Default FedEx service to display' ),
					'default'     => 'FEDEX_GROUND',
					'options'     => $this->get_services(),
					'desc_tip'    => true,
				),
				'markup'           => array(
					'title'       => __( 'Markup (%)' ),
					'type'        => 'number',
					'description' => __( 'Add a percentage markup to rates' ),
					'default'     => '0',
					'desc_tip'    => true,
				),
				'free_shipping_min' => array(
					'title'       => __( 'Free Shipping Minimum ($)' ),
					'type'        => 'number',
					'description' => __( 'Offer free FedEx shipping above this order value' ),
					'default'     => '0',
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Calculate shipping costs
		 *
		 * @param array $package Cart package
		 */
		public function calculate_shipping( $package = array() ) {
			if ( ! $package ) {
				return;
			}

			// Check if credentials are configured
			$client_id = get_option( 'fedex_client_id' );
			if ( ! $client_id ) {
				return;
			}

			// Get shipment details from cart
			$shipment_data = $this->prepare_shipment_data( $package );

			if ( ! $shipment_data ) {
				return;
			}

			// Get rates from FedEx API
			$rates = FedEx_API::get_rates( $shipment_data );

			if ( ! $rates || ! isset( $rates['output']['rateReplyDetails'] ) ) {
				return;
			}

			// Parse rates and add shipping methods
			foreach ( $rates['output']['rateReplyDetails'] as $rate ) {
				if ( ! isset( $rate['serviceType'] ) ) {
					continue;
				}

				$cost = $this->parse_rate_cost( $rate );

				if ( ! $cost ) {
					continue;
				}

				// Apply markup
				$markup = floatval( $this->get_option( 'markup', 0 ) );
				if ( $markup > 0 ) {
					$cost = $cost * ( 1 + $markup / 100 );
				}

				// Check for free shipping
				$free_min = floatval( $this->get_option( 'free_shipping_min', 0 ) );
				if ( $free_min > 0 && isset( $package['contents_cost'] ) ) {
					if ( $package['contents_cost'] >= $free_min ) {
						$cost = 0;
					}
				}

				$service_type = $rate['serviceType'];
				$rate_arg = array(
					'id'        => $this->id . ':' . $service_type,
					'label'     => $this->get_service_name( $service_type ),
					'cost'      => $cost,
					'meta_data' => array(
						'service_type' => $service_type,
					),
				);

				$this->add_rate( $rate_arg );
			}
		}

		/**
		 * Prepare shipment data from cart
		 *
		 * @param array $package Cart package
		 * @return array|false Shipment data
		 */
		private function prepare_shipment_data( $package ) {
			if ( empty( $package['destination']['postcode'] ) ) {
				return false;
			}

			$shipper_postcode = get_option( 'fedex_shipper_postcode', '10001' );
			$account_number = get_option( 'fedex_account_number' );

			if ( ! $account_number ) {
				return false;
			}

			// Calculate total weight and dimensions
			$total_weight = 0;
			$item_count = 0;

			foreach ( $package['contents'] as $item ) {
				if ( $item['data']->get_weight() ) {
					$total_weight += $item['data']->get_weight() * $item['quantity'];
				}
				$item_count += $item['quantity'];
			}

			// Default dimensions if not specified
			$width = floatval( get_option( 'fedex_default_width', 10 ) );
			$height = floatval( get_option( 'fedex_default_height', 10 ) );
			$length = floatval( get_option( 'fedex_default_length', 10 ) );

			if ( $total_weight <= 0 ) {
				$total_weight = 5 * $item_count; // Default 5 lbs per item
			}

			$shipment_data = array(
				'accountNumber'     => $account_number,
				'requestedShipment' => array(
					'shipper'      => array(
						'address' => array(
							'postalCode'  => $shipper_postcode,
							'countryCode' => 'US',
						),
					),
					'recipients'   => array(
						array(
							'address' => array(
								'postalCode'  => $package['destination']['postcode'],
								'countryCode' => isset( $package['destination']['country'] ) ? $package['destination']['country'] : 'US',
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
						'length' => $length,
						'width'  => $width,
						'height' => $height,
						'units'  => 'IN',
					),
				),
			);

			return $shipment_data;
		}

		/**
		 * Parse rate cost from response
		 *
		 * @param array $rate Rate data
		 * @return float|false Cost or false
		 */
		private function parse_rate_cost( $rate ) {
			if ( ! isset( $rate['rateReplyDetails'][0]['netCharge'] ) ) {
				return false;
			}

			return floatval( $rate['rateReplyDetails'][0]['netCharge'] );
		}

		/**
		 * Get service name
		 *
		 * @param string $service_type Service type
		 * @return string Service name
		 */
		private function get_service_name( $service_type ) {
			$services = FedEx_API::get_services();
			return isset( $services[ $service_type ]['name'] ) ? $services[ $service_type ]['name'] : $service_type;
		}

		/**
		 * Get available services for dropdown
		 *
		 * @return array Services
		 */
		private function get_services() {
			$services = FedEx_API::get_services();
			$options = array();

			foreach ( $services as $key => $service ) {
				$options[ $key ] = $service['name'];
			}

			return $options;
		}
	}
}
