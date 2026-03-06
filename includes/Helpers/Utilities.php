<?php
/**
 * FedEx Helper Functions
 *
 * @package FedEx\Helpers
 */

namespace FedEx\Helpers;

use FedEx\Container;

/**
 * Utility functions for FedEx plugin
 */
class Utilities {

	/**
	 * Convert weight to pounds
	 *
	 * @param float  $weight Weight
	 * @param string $unit   Unit (g, kg, oz, lb)
	 * @return float
	 */
	public static function convert_to_pounds( $weight, $unit = 'lb' ) {
		switch ( strtolower( $unit ) ) {
			case 'g':
			case 'gram':
				return $weight / 453.592;
			case 'kg':
			case 'kilogram':
				return $weight * 2.20462;
			case 'oz':
			case 'ounce':
				return $weight / 16;
			default:
				return $weight;
		}
	}

	/**
	 * Convert to inches
	 *
	 * @param float  $dimension Dimension
	 * @param string $unit      Unit (mm, cm, in, ft)
	 * @return float
	 */
	public static function convert_to_inches( $dimension, $unit = 'in' ) {
		switch ( strtolower( $unit ) ) {
			case 'mm':
			case 'millimeter':
				return $dimension / 25.4;
			case 'cm':
			case 'centimeter':
				return $dimension / 2.54;
			case 'ft':
			case 'foot':
				return $dimension * 12;
			default:
				return $dimension;
		}
	}

	/**
	 * Calculate dimensional weight
	 *
	 * @param float $length Length (in)
	 * @param float $width  Width (in)
	 * @param float $height Height (in)
	 * @param float $divisor Divisor (default 166)
	 * @return float
	 */
	public static function calculate_dimensional_weight( $length, $width, $height, $divisor = 166 ) {
		$volume = $length * $width * $height;
		return ceil( $volume / $divisor );
	}

	/**
	 * Get billable weight
	 *
	 * @param float $actual Actual weight
	 * @param float $length Length (in)
	 * @param float $width  Width (in)
	 * @param float $height Height (in)
	 * @param float $divisor Divisor (default 166)
	 * @return float
	 */
	public static function get_billable_weight( $actual, $length, $width, $height, $divisor = 166 ) {
		$dimensional = self::calculate_dimensional_weight( $length, $width, $height, $divisor );
		return max( $actual, $dimensional );
	}

	/**
	 * Build shipment data
	 *
	 * @param array $args Shipment arguments
	 * @return array
	 */
	public static function build_shipment_data( array $args = array() ) {
		$defaults = array(
			'account_number'      => '',
			'shipper_postal_code' => '10001',
			'shipper_country'     => 'US',
			'recipient_postal'    => '95014',
			'recipient_country'   => 'US',
			'weight'              => 5,
			'weight_unit'         => 'lb',
			'length'              => 10,
			'width'               => 6,
			'height'              => 4,
			'dimension_unit'      => 'in',
			'pickup_type'         => 'STATION',
			'ship_date'           => gmdate( 'Y-m-d' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$weight = self::convert_to_pounds( $args['weight'], $args['weight_unit'] );
		$length = self::convert_to_inches( $args['length'], $args['dimension_unit'] );
		$width = self::convert_to_inches( $args['width'], $args['dimension_unit'] );
		$height = self::convert_to_inches( $args['height'], $args['dimension_unit'] );

		return array(
			'accountNumber'     => $args['account_number'],
			'requestedShipment' => array(
				'shipper'      => array(
					'address' => array(
						'postalCode'  => $args['shipper_postal_code'],
						'countryCode' => $args['shipper_country'],
					),
				),
				'recipients'   => array(
					array(
						'address' => array(
							'postalCode'  => $args['recipient_postal'],
							'countryCode' => $args['recipient_country'],
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
	 * @param string $action Action
	 * @param mixed  $data   Data
	 * @param string $level  Level
	 * @return void
	 */
	public static function log( $action, $data = null, $level = 'info' ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$message = '[FedEx] ' . $action;
			if ( $data ) {
				$message .= ': ' . wp_json_encode( $data );
			}
			error_log( $message );
		}
	}
}
