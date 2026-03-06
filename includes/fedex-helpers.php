<?php
/**
 * FedEx Plugin Helper Functions
 *
 * @package FedEx_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get FedEx shipping rates
 *
 * Helper function to get shipping rates for a given shipment
 *
 * @param array $shipment_data Shipment details
 * @return array|false Rates or false
 */
function fedex_get_rates( $shipment_data ) {
	return FedEx_API::get_rates( $shipment_data );
}

/**
 * Track FedEx shipment
 *
 * Helper function to track a shipment
 *
 * @param string $tracking_number Tracking number
 * @return array|false Tracking info or false
 */
function fedex_track_shipment( $tracking_number ) {
	return FedEx_API::track_shipment( $tracking_number );
}

/**
 * Create FedEx shipment
 *
 * Helper function to create a shipment
 *
 * @param array $shipment_data Shipment details
 * @return array|false Shipment response or false
 */
function fedex_create_shipment( $shipment_data ) {
	return FedEx_API::create_shipment( $shipment_data );
}

/**
 * Get FedEx access token
 *
 * Helper function to get current access token
 *
 * @return string|false Access token or false
 */
function fedex_get_access_token() {
	return FedEx_OAuth::get_access_token();
}

/**
 * Refresh FedEx token
 *
 * Helper function to refresh the OAuth token
 *
 * @return bool True if successful
 */
function fedex_refresh_token() {
	return FedEx_OAuth::refresh_token();
}

/**
 * Get available FedEx services
 *
 * Helper function to get list of services
 *
 * @return array Available services
 */
function fedex_get_services() {
	return FedEx_API::get_services();
}

/**
 * Get FedEx service name
 *
 * Helper function to get display name for a service code
 *
 * @param string $service_code Service code (e.g., FEDEX_GROUND)
 * @return string Service name
 */
function fedex_get_service_name( $service_code ) {
	$services = fedex_get_services();
	return isset( $services[ $service_code ]['name'] ) ? $services[ $service_code ]['name'] : $service_code;
}

/**
 * Get FedEx setting
 *
 * Wrapper to get a FedEx plugin setting
 *
 * @param string $setting Setting name
 * @param mixed  $default Default value if not found
 * @return mixed Setting value
 */
function fedex_get_setting( $setting, $default = null ) {
	$option = get_option( 'fedex_' . $setting, $default );
	return apply_filters( 'fedex_get_setting_' . $setting, $option );
}

/**
 * Update FedEx setting
 *
 * Wrapper to update a FedEx plugin setting
 *
 * @param string $setting Setting name
 * @param mixed  $value Setting value
 * @return bool True if updated
 */
function fedex_update_setting( $setting, $value ) {
	return update_option( 'fedex_' . $setting, $value );
}

/**
 * Calculate dimensional weight
 *
 * Calculate dimensional weight for a package
 *
 * @param float $length Length in inches
 * @param float $width Width in inches
 * @param float $height Height in inches
 * @param float $divisor Divisor (default 166 for ground, 139 for air)
 * @return float Dimensional weight in pounds
 */
function fedex_calculate_dimensional_weight( $length, $width, $height, $divisor = 166 ) {
	$volume = $length * $width * $height;
	return ceil( $volume / $divisor );
}

/**
 * Get billable weight
 *
 * Get the billable weight (actual or dimensional, whichever is greater)
 *
 * @param float $actual_weight Actual weight in pounds
 * @param float $length Length in inches
 * @param float $width Width in inches
 * @param float $height Height in inches
 * @param float $divisor Divisor (default 166)
 * @return float Billable weight in pounds
 */
function fedex_get_billable_weight( $actual_weight, $length, $width, $height, $divisor = 166 ) {
	$dimensional_weight = fedex_calculate_dimensional_weight( $length, $width, $height, $divisor );
	return max( $actual_weight, $dimensional_weight );
}

/**
 * Convert weight to pounds
 *
 * Convert weight from various units to pounds
 *
 * @param float  $weight Weight value
 * @param string $unit Unit (g, kg, oz, lb)
 * @return float Weight in pounds
 */
function fedex_convert_to_pounds( $weight, $unit = 'lb' ) {
	switch ( strtolower( $unit ) ) {
		case 'g':
		case 'gram':
		case 'grams':
			return $weight / 453.592;
		case 'kg':
		case 'kilogram':
		case 'kilograms':
			return $weight * 2.20462;
		case 'oz':
		case 'ounce':
		case 'ounces':
			return $weight / 16;
		case 'lb':
		case 'lbs':
		case 'pound':
		case 'pounds':
		default:
			return $weight;
	}
}

/**
 * Convert dimensions to inches
 *
 * Convert dimensions from various units to inches
 *
 * @param float  $dimension Dimension value
 * @param string $unit Unit (mm, cm, in, ft)
 * @return float Dimension in inches
 */
function fedex_convert_to_inches( $dimension, $unit = 'in' ) {
	switch ( strtolower( $unit ) ) {
		case 'mm':
		case 'millimeter':
		case 'millimeters':
			return $dimension / 25.4;
		case 'cm':
		case 'centimeter':
		case 'centimeters':
			return $dimension / 2.54;
		case 'ft':
		case 'foot':
		case 'feet':
			return $dimension * 12;
		case 'in':
		case 'inch':
		case 'inches':
		default:
			return $dimension;
	}
}

/**
 * Build shipment data array
 *
 * Helper function to build properly formatted shipment data
 *
 * @param array $args Shipment arguments
 * @return array Formatted shipment data
 */
function fedex_build_shipment_data( $args = array() ) {
	$defaults = array(
		'account_number'           => fedex_get_setting( 'account_number' ),
		'shipper_postal_code'      => '10001',
		'shipper_country_code'     => 'US',
		'recipient_postal_code'    => '95014',
		'recipient_country_code'   => 'US',
		'weight'                   => 5,
		'weight_unit'              => 'lb',
		'length'                   => 10,
		'width'                    => 6,
		'height'                   => 4,
		'dimension_unit'           => 'in',
		'pickup_type'              => 'STATION',
		'ship_date'                => gmdate( 'Y-m-d' ),
	);

	$args = wp_parse_args( $args, $defaults );

	// Convert units if needed
	$weight = fedex_convert_to_pounds( $args['weight'], $args['weight_unit'] );
	$length = fedex_convert_to_inches( $args['length'], $args['dimension_unit'] );
	$width = fedex_convert_to_inches( $args['width'], $args['dimension_unit'] );
	$height = fedex_convert_to_inches( $args['height'], $args['dimension_unit'] );

	return array(
		'accountNumber'     => $args['account_number'],
		'requestedShipment' => array(
			'shipper'      => array(
				'address' => array(
					'postalCode'  => $args['shipper_postal_code'],
					'countryCode' => $args['shipper_country_code'],
				),
			),
			'recipients'   => array(
				array(
					'address' => array(
						'postalCode'  => $args['recipient_postal_code'],
						'countryCode' => $args['recipient_country_code'],
					),
				),
			),
			'pickupType'   => $args['pickup_type'],
			'shipDateStamp' => $args['ship_date'],
			'totalWeight'  => array(
				'units' => 'LB',
				'value' => $weight,
			),
			'totalDimensions' => array(
				'length' => $length,
				'width'  => $width,
				'height' => $height,
				'units'  => 'IN',
			),
		),
	);
}

/**
 * Log FedEx action
 *
 * Log FedEx actions for debugging
 *
 * @param string $action Action name
 * @param mixed  $data Data to log
 * @param string $level Log level (info, warning, error)
 */
function fedex_log( $action, $data = null, $level = 'info' ) {
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		$message = '[FedEx] ' . $action;
		if ( $data ) {
			$message .= ': ' . wp_json_encode( $data );
		}
		error_log( $message, 0 );
	}
}

/**
 * Check if FedEx is configured
 *
 * Check if FedEx API credentials are configured
 *
 * @return bool True if configured
 */
function fedex_is_configured() {
	$client_id = fedex_get_setting( 'client_id' );
	$client_secret = fedex_get_setting( 'client_secret' );
	return ! empty( $client_id ) && ! empty( $client_secret );
}

/**
 * Test FedEx connection
 *
 * Test FedEx OAuth connection
 *
 * @return array Array with 'success' bool and 'message' string
 */
function fedex_test_connection() {
	FedEx_OAuth::clear_cache();
	$token = FedEx_OAuth::get_token();

	if ( $token ) {
		return array(
			'success' => true,
			'message' => 'Connection successful! Token expires in ' . $token['expires_in'] . ' seconds.',
			'token'   => $token,
		);
	}

	return array(
		'success' => false,
		'message' => 'Connection failed. Please check your credentials.',
	);
}
