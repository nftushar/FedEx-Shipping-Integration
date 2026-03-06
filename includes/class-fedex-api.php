<?php
/**
 * FedEx API Integration
 *
 * @package FedEx_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for FedEx API requests
 */
class FedEx_API {

	/**
	 * Make authenticated API request
	 *
	 * @param string $endpoint API endpoint (without domain)
	 * @param array  $data Request body data
	 * @param string $method HTTP method (GET, POST, PUT, etc.)
	 * @return array|false Response data or false
	 */
	public static function request( $endpoint, $data = array(), $method = 'POST' ) {
		$access_token = FedEx_OAuth::get_access_token();

		if ( ! $access_token ) {
			return false;
		}

		$url = FEDEX_API_ENDPOINT . $endpoint;

		$args = array(
			'headers'   => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'method'    => $method,
			'timeout'   => 30,
			'sslverify' => true,
		);

		if ( ! empty( $data ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'FedEx API Error: ' . $response->get_error_message() );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( ! in_array( $status_code, array( 200, 201, 204 ), true ) ) {
			error_log( 'FedEx API Error: ' . $status_code . ' - ' . $body );
			return false;
		}

		if ( empty( $body ) ) {
			return true;
		}

		return json_decode( $body, true );
	}

	/**
	 * Get shipping rates
	 *
	 * @param array $shipment_data Shipment information
	 * @return array|false Rates or false
	 */
	public static function get_rates( $shipment_data ) {
		return self::request( '/ship/v1/rates', $shipment_data );
	}

	/**
	 * Create shipment
	 *
	 * @param array $shipment_data Shipment information
	 * @return array|false Shipment response or false
	 */
	public static function create_shipment( $shipment_data ) {
		return self::request( '/ship/v1/shipments', $shipment_data );
	}

	/**
	 * Get shipment details
	 *
	 * @param string $tracking_number Tracking number
	 * @return array|false Shipment details or false
	 */
	public static function get_shipment_details( $tracking_number ) {
		return self::request( '/ship/v1/shipments/' . $tracking_number, array(), 'GET' );
	}

	/**
	 * Track shipment
	 *
	 * @param string $tracking_number Tracking number
	 * @return array|false Tracking information or false
	 */
	public static function track_shipment( $tracking_number ) {
		return self::request( '/track/v1/tracking/shipments/' . $tracking_number, array(), 'GET' );
	}

	/**
	 * Cancel shipment
	 *
	 * @param string $shipment_id Shipment ID
	 * @return bool|array True on success, array with error on failure
	 */
	public static function cancel_shipment( $shipment_id ) {
		return self::request( '/ship/v1/shipments/' . $shipment_id, array(), 'DELETE' );
	}

	/**
	 * Get available services
	 *
	 * @return array|false Available services
	 */
	public static function get_services() {
		$services = get_transient( 'fedex_available_services' );

		if ( false === $services ) {
			// FedEx services list
			$services = array(
				'FEDEX_OVERNIGHT' => array(
					'name'        => 'FedEx Overnight',
					'description' => 'Deliver by 8am',
				),
				'FEDEX_2ND_DAY_AM' => array(
					'name'        => 'FedEx 2nd Day America',
					'description' => 'Deliver by 12pm',
				),
				'FEDEX_2_DAY' => array(
					'name'        => 'FedEx 2 Day',
					'description' => 'Typical 2 day delivery',
				),
				'FEDEX_EXPRESS_SAVER' => array(
					'name'        => 'FedEx Express Saver',
					'description' => 'Deliver in 3 business days',
				),
				'FEDEX_GROUND' => array(
					'name'        => 'FedEx Ground',
					'description' => 'Ground delivery',
				),
				'FEDEX_FREIGHT' => array(
					'name'        => 'FedEx Freight',
					'description' => 'Freight service',
				),
			);

			set_transient( 'fedex_available_services', $services, WEEK_IN_SECONDS );
		}

		return $services;
	}
}
