<?php
/**
 * FedEx API Service
 *
 * @package FedEx\Services
 */

namespace FedEx\Services;

use FedEx\Config\Configuration;
use FedEx\Exceptions\ApiException;

/**
 * API service for FedEx requests
 */
class ApiService {

	/**
	 * Configuration instance
	 *
	 * @var Configuration
	 */
	private $config;

	/**
	 * OAuth service instance
	 *
	 * @var OAuthService
	 */
	private $oauth;

	/**
	 * Constructor
	 *
	 * @param Configuration $config Configuration instance
	 * @param OAuthService  $oauth  OAuth service instance
	 */
	public function __construct( Configuration $config, OAuthService $oauth ) {
		$this->config = $config;
		$this->oauth = $oauth;
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint Endpoint (without domain)
	 * @param array  $data     Request body
	 * @param string $method   HTTP method
	 * @return array|true Response data
	 * @throws ApiException
	 */
	public function request( $endpoint, array $data = array(), $method = 'POST' ) {
		try {
			$access_token = $this->oauth->get_access_token();
		} catch ( \Exception $e ) {
			throw new ApiException( 'Failed to get access token: ' . $e->getMessage() );
		}

		if ( ! $access_token ) {
			throw new ApiException( 'No access token available' );
		}

		$url = $this->config->get_api_endpoint() . $endpoint;

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
			throw new ApiException( 'API request failed: ' . $response->get_error_message() );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( ! in_array( $status, array( 200, 201, 204 ), true ) ) {
			throw new ApiException( 'API error (' . $status . '): ' . $body );
		}

		if ( empty( $body ) ) {
			return true;
		}

		return json_decode( $body, true );
	}

	/**
	 * Get shipping rates
	 *
	 * @param array $shipment_data Shipment details
	 * @return array Rates
	 * @throws ApiException
	 */
	public function get_rates( array $shipment_data ) {
		return $this->request( '/ship/v1/rates', $shipment_data );
	}

	/**
	 * Create shipment
	 *
	 * @param array $shipment_data Shipment details
	 * @return array Shipment response
	 * @throws ApiException
	 */
	public function create_shipment( array $shipment_data ) {
		return $this->request( '/ship/v1/shipments', $shipment_data );
	}

	/**
	 * Track shipment
	 *
	 * @param string $tracking_number Tracking number
	 * @return array Tracking info
	 * @throws ApiException
	 */
	public function track_shipment( $tracking_number ) {
		return $this->request( '/track/v1/tracking/shipments/' . urlencode( $tracking_number ), array(), 'GET' );
	}

	/**
	 * Cancel shipment
	 *
	 * @param string $shipment_id Shipment ID
	 * @return true
	 * @throws ApiException
	 */
	public function cancel_shipment( $shipment_id ) {
		return $this->request( '/ship/v1/shipments/' . urlencode( $shipment_id ), array(), 'DELETE' );
	}

	/**
	 * Get available services
	 *
	 * @return array Services
	 */
	public function get_services() {
		$cache_key = 'fedex_services_list';
		$services = get_transient( $cache_key );

		if ( false === $services ) {
			$services = array(
				'FEDEX_OVERNIGHT'      => array(
					'name'        => 'FedEx Overnight',
					'description' => 'Deliver by 8am',
				),
				'FEDEX_2ND_DAY_AM'     => array(
					'name'        => 'FedEx 2nd Day America',
					'description' => 'Deliver by 12pm',
				),
				'FEDEX_2_DAY'          => array(
					'name'        => 'FedEx 2 Day',
					'description' => 'Typical 2 day delivery',
				),
				'FEDEX_EXPRESS_SAVER'  => array(
					'name'        => 'FedEx Express Saver',
					'description' => 'Deliver in 3 business days',
				),
				'FEDEX_GROUND'         => array(
					'name'        => 'FedEx Ground',
					'description' => 'Ground delivery',
				),
				'FEDEX_FREIGHT'        => array(
					'name'        => 'FedEx Freight',
					'description' => 'Freight service',
				),
			);

			set_transient( $cache_key, $services, WEEK_IN_SECONDS );
		}

		return apply_filters( 'fedex_services_list', $services );
	}

	/**
	 * Get service name
	 *
	 * @param string $service_code Service code
	 * @return string Service name
	 */
	public function get_service_name( $service_code ) {
		$services = $this->get_services();
		return $services[ $service_code ]['name'] ?? $service_code;
	}

	/**
	 * Test connection
	 *
	 * @return bool True if connected
	 */
	public function test_connection() {
		try {
			$this->oauth->refresh_token();
			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
