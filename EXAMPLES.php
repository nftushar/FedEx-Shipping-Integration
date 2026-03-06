<?php
/**
 * FedEx Plugin - Example Usage
 * 
 * This file demonstrates how to use the FedEx plugin in your own plugins or themes.
 * These are code examples - do NOT include this file in production.
 * 
 * @package FedEx_Shipping
 */

// ========================================================================
// EXAMPLE 1: Get Shipping Rates
// ========================================================================

function example_get_shipping_rates() {
	// Build shipment data
	$shipment_data = fedex_build_shipment_data( array(
		'shipper_postal_code'    => '10001', // New York
		'recipient_postal_code'  => '95014', // Apple HQ
		'weight'                 => 5,
		'weight_unit'            => 'lb',
		'length'                 => 10,
		'width'                  => 6,
		'height'                 => 4,
		'dimension_unit'         => 'in',
	) );

	// Get rates
	$rates = fedex_get_rates( $shipment_data );

	if ( $rates ) {
		echo 'Available shipping rates:' . PHP_EOL;
		foreach ( $rates['output']['rateReplyDetails'] as $rate ) {
			$service_type = $rate['serviceType'];
			$service_name = fedex_get_service_name( $service_type );
			$cost = $rate['rateReplyDetails'][0]['netCharge'];
			echo "  {$service_name}: ${cost}" . PHP_EOL;
		}
	} else {
		echo 'Failed to get rates' . PHP_EOL;
	}
}

// Usage:
// example_get_shipping_rates();


// ========================================================================
// EXAMPLE 2: Track a Shipment
// ========================================================================

function example_track_shipment( $tracking_number = '794618519047' ) {
	$tracking = fedex_track_shipment( $tracking_number );

	if ( $tracking ) {
		$results = $tracking['output']['completeTrackResults'][0];
		$track = $results['trackResults'][0];

		echo 'Tracking Number: ' . $tracking_number . PHP_EOL;
		echo 'Status: ' . $track['statusDescription'] . PHP_EOL;
		echo 'Timestamp: ' . $track['timestamp'] . PHP_EOL;
		echo 'Location: ' . $track['location']['address']['city'] . ', ' 
			. $track['location']['address']['stateOrProvinceCode'] . PHP_EOL;
	} else {
		echo 'Tracking information not found' . PHP_EOL;
	}
}

// Usage:
// example_track_shipment();


// ========================================================================
// EXAMPLE 3: Create a Shipment
// ========================================================================

function example_create_shipment() {
	$shipment_data = array(
		'accountNumber'     => fedex_get_setting( 'account_number' ),
		'requestedShipment' => array(
			'shipper'      => array(
				'contact' => array(
					'personName' => 'Shipper Name',
					'emailAddress' => 'shipper@example.com',
					'phoneNumber' => '2025551234',
				),
				'address' => array(
					'streetLines' => array( '123 Main St' ),
					'city' => 'New York',
					'stateOrProvinceCode' => 'NY',
					'postalCode' => '10001',
					'countryCode' => 'US',
				),
			),
			'recipients'   => array(
				array(
					'contact' => array(
						'personName' => 'Recipient Name',
						'emailAddress' => 'recipient@example.com',
						'phoneNumber' => '4085551234',
					),
					'address' => array(
						'streetLines' => array( '1 Infinite Loop' ),
						'city' => 'Cupertino',
						'stateOrProvinceCode' => 'CA',
						'postalCode' => '95014',
						'countryCode' => 'US',
					),
				),
			),
			'pickupType'    => 'STATION',
			'serviceType'   => 'FEDEX_2_DAY',
			'shipDateStamp' => gmdate( 'Y-m-d' ),
			'totalWeight'   => array(
				'units' => 'LB',
				'value' => 5,
			),
			'totalDimensions' => array(
				'length' => 10,
				'width'  => 6,
				'height' => 4,
				'units'  => 'IN',
			),
			'packages'      => array(
				array(
					'weight' => array(
						'units' => 'LB',
						'value' => 5,
					),
					'dimensions' => array(
						'length' => 10,
						'width'  => 6,
						'height' => 4,
						'units'  => 'IN',
					),
				),
			),
		),
	);

	$response = fedex_create_shipment( $shipment_data );

	if ( $response ) {
		echo 'Shipment created successfully!' . PHP_EOL;
		echo 'Tracking Number: ' . $response['output']['transactionShipments'][0]['masterTrackingNumber'] . PHP_EOL;
	} else {
		echo 'Failed to create shipment' . PHP_EOL;
	}
}

// Usage:
// example_create_shipment();


// ========================================================================
// EXAMPLE 4: Get Available Services
// ========================================================================

function example_get_services() {
	$services = fedex_get_services();

	echo 'Available FedEx Services:' . PHP_EOL;
	foreach ( $services as $code => $service ) {
		echo '  ' . $code . ': ' . $service['name'] . PHP_EOL;
		echo '    ' . $service['description'] . PHP_EOL;
	}
}

// Usage:
// example_get_services();


// ========================================================================
// EXAMPLE 5: Check Configuration
// ========================================================================

function example_check_fedex_config() {
	echo '=== FedEx Configuration ===' . PHP_EOL;
	echo 'Configured: ' . ( fedex_is_configured() ? 'Yes' : 'No' ) . PHP_EOL;

	if ( fedex_is_configured() ) {
		echo 'Client ID: ' . substr( fedex_get_setting( 'client_id' ), 0, 5 ) . '...' . PHP_EOL;
		echo 'Grant Type: ' . fedex_get_setting( 'grant_type' ) . PHP_EOL;
		echo 'Account Number: ' . fedex_get_setting( 'account_number' ) . PHP_EOL;
		echo 'Use Production: ' . ( fedex_get_setting( 'use_production' ) ? 'Yes' : 'No' ) . PHP_EOL;

		// Test connection
		$test = fedex_test_connection();
		echo 'Connection: ' . ( $test['success'] ? 'OK' : 'FAILED' ) . PHP_EOL;
		if ( $test['success'] ) {
			echo 'Token Expires In: ' . $test['token']['expires_in'] . ' seconds' . PHP_EOL;
		}
	}
}

// Usage:
// example_check_fedex_config();


// ========================================================================
// EXAMPLE 6: Weight and Dimension Conversions
// ========================================================================

function example_conversions() {
	echo '=== Unit Conversions ===' . PHP_EOL;

	// Weight conversions
	echo 'Weight Conversions:' . PHP_EOL;
	echo '  1 kg = ' . fedex_convert_to_pounds( 1, 'kg' ) . ' lbs' . PHP_EOL;
	echo '  16 oz = ' . fedex_convert_to_pounds( 16, 'oz' ) . ' lbs' . PHP_EOL;
	echo '  100 grams = ' . fedex_convert_to_pounds( 100, 'g' ) . ' lbs' . PHP_EOL;

	// Dimension conversions
	echo 'Dimension Conversions:' . PHP_EOL;
	echo '  25.4 mm = ' . fedex_convert_to_inches( 25.4, 'mm' ) . ' inches' . PHP_EOL;
	echo '  10 cm = ' . fedex_convert_to_inches( 10, 'cm' ) . ' inches' . PHP_EOL;
	echo '  2 feet = ' . fedex_convert_to_inches( 2, 'ft' ) . ' inches' . PHP_EOL;

	// Dimensional weight
	echo 'Dimensional Weight:' . PHP_EOL;
	$dim_weight = fedex_calculate_dimensional_weight( 10, 10, 10, 166 );
	echo '  10x10x10 inches = ' . $dim_weight . ' lbs (divisor 166)' . PHP_EOL;

	// Billable weight
	echo 'Billable Weight:' . PHP_EOL;
	$billable = fedex_get_billable_weight( 2, 10, 10, 10, 166 );
	echo '  Actual: 2 lbs, Dimensional: 6 lbs = ' . $billable . ' lbs billable' . PHP_EOL;
}

// Usage:
// example_conversions();


// ========================================================================
// EXAMPLE 7: In a WooCommerce Filter Hook
// ========================================================================

function example_woocommerce_filter() {
	// This would go in your theme's functions.php or plugin code
	
	add_filter( 'woocommerce_package_rates', 'filter_fedex_rates', 10, 2 );

	function filter_fedex_rates( $rates, $package ) {
		// Only modify FedEx rates
		foreach ( $rates as $id => $rate ) {
			if ( 'fedex_shipping' === $rate->method_id ) {
				// Add 10% markup
				$rate->cost = $rate->cost * 1.1;
				
				// Add a custom label
				if ( isset( $rate->meta_data['service_type'] ) ) {
					$service_name = fedex_get_service_name( $rate->meta_data['service_type'] );
					$rate->label = 'Express Delivery via ' . $service_name;
				}
			}
		}
		return $rates;
	}
}

// Usage:
// example_woocommerce_filter();


// ========================================================================
// EXAMPLE 8: Logging
// ========================================================================

function example_logging() {
	// Initialize debug logging
	fedex_log( 'Plugin initialized' );
	fedex_log( 'Testing authentication', array( 'client_id' => '***' ) );
	fedex_log( 'Rate request sent', array( 'weight' => 5, 'service' => 'FEDEX_GROUND' ), 'info' );
	fedex_log( 'API error occurred', array( 'error' => 'Connection timeout' ), 'error' );
}

// Note: Logging only works if WP_DEBUG_LOG is enabled in wp-config.php
// Add this line to wp-config.php: define( 'WP_DEBUG_LOG', true );


// ========================================================================
// EXAMPLE 9: Custom Shipment Data Builder
// ========================================================================

function example_custom_shipment_builder( $wc_order ) {
	// Build shipment from WooCommerce order
	$shipping_address = $wc_order->get_shipping_address_1();
	$shipping_city = $wc_order->get_shipping_city();
	$shipping_state = $wc_order->get_shipping_state();
	$shipping_postcode = $wc_order->get_shipping_postcode();

	// Calculate total weight from items
	$total_weight = 0;
	foreach ( $wc_order->get_items() as $item ) {
		$product = $item->get_product();
		$total_weight += $product->get_weight() * $item->get_quantity();
	}

	// Build shipment data
	$shipment_data = fedex_build_shipment_data( array(
		'shipper_postal_code'    => fedex_get_setting( 'shipper_postcode', '10001' ),
		'recipient_postal_code'  => $shipping_postcode,
		'recipient_country_code' => 'US',
		'weight'                 => max( $total_weight, 1 ),
		'weight_unit'            => get_option( 'woocommerce_weight_unit', 'lb' ),
	) );

	return $shipment_data;
}

// Usage with WooCommerce:
// $order = wc_get_order( 123 );
// $shipment_data = example_custom_shipment_builder( $order );
// $rates = fedex_get_rates( $shipment_data );


// ========================================================================
// HOW TO USE THESE EXAMPLES
// ========================================================================

/*

1. Verify FedEx plugin is activated
2. Make sure API credentials are configured in admin
3. Copy and paste example function into your theme's functions.php 
   or your own plugin file
4. Call the function: example_get_shipping_rates();

Example: Add to functions.php and call via:
  - WP-CLI: wp eval 'example_get_shipping_rates();'
  - Plugin: Create admin notice that calls function
  - Theme: Add to hooks/filters

All examples use the helper functions defined in fedex-helpers.php

*/
