<?php
/**
 * FedEx Configuration Management
 *
 * @package FedEx\Config
 */

namespace FedEx\Config;

/**
 * Application configuration
 */
class Configuration {

	/**
	 * Environment (sandbox or production)
	 *
	 * @var string
	 */
	private $environment;

	/**
	 * API endpoints
	 *
	 * @var array
	 */
	private $endpoints = array(
		'sandbox'    => array(
			'oauth' => 'https://apis-sandbox.fedex.com/oauth/token',
			'api'   => 'https://apis-sandbox.fedex.com',
		),
		'production' => array(
			'oauth' => 'https://apis.fedex.com/oauth/token',
			'api'   => 'https://apis.fedex.com',
		),
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->environment = $this->get_option( 'environment', 'sandbox' );
	}

	/**
	 * Get environment
	 *
	 * @return string
	 */
	public function get_environment() {
		return $this->environment;
	}

	/**
	 * Set environment
	 *
	 * @param string $environment sandbox or production
	 * @return $this
	 */
	public function set_environment( $environment ) {
		$this->environment = in_array( $environment, array( 'sandbox', 'production' ), true ) 
			? $environment 
			: 'sandbox';
		return $this;
	}

	/**
	 * Get OAuth endpoint
	 *
	 * @return string
	 */
	public function get_oauth_endpoint() {
		return $this->endpoints[ $this->environment ]['oauth'];
	}

	/**
	 * Get API endpoint
	 *
	 * @return string
	 */
	public function get_api_endpoint() {
		return $this->endpoints[ $this->environment ]['api'];
	}

	/**
	 * Get client ID
	 *
	 * @return string|null
	 */
	public function get_client_id() {
		return $this->get_option( 'client_id' );
	}

	/**
	 * Get client secret
	 *
	 * @return string|null
	 */
	public function get_client_secret() {
		return $this->get_option( 'client_secret' );
	}

	/**
	 * Get grant type
	 *
	 * @return string
	 */
	public function get_grant_type() {
		return $this->get_option( 'grant_type', 'client_credentials' );
	}

	/**
	 * Get child key
	 *
	 * @return string|null
	 */
	public function get_child_key() {
		return $this->get_option( 'child_key' );
	}

	/**
	 * Get child secret
	 *
	 * @return string|null
	 */
	public function get_child_secret() {
		return $this->get_option( 'child_secret' );
	}

	/**
	 * Get account number
	 *
	 * @return string|null
	 */
	public function get_account_number() {
		return $this->get_option( 'account_number' );
	}

	/**
	 * Get meter number
	 *
	 * @return string|null
	 */
	public function get_meter_number() {
		return $this->get_option( 'meter_number' );
	}

	/**
	 * Check if configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( $this->get_client_id() ) && ! empty( $this->get_client_secret() );
	}

	/**
	 * Get all credentials
	 *
	 * @return array
	 */
	public function get_credentials() {
		return array(
			'client_id'     => $this->get_client_id(),
			'client_secret' => $this->get_client_secret(),
			'grant_type'    => $this->get_grant_type(),
			'child_key'     => $this->get_child_key(),
			'child_secret'  => $this->get_child_secret(),
			'account_number' => $this->get_account_number(),
			'meter_number'  => $this->get_meter_number(),
		);
	}

	/**
	 * Get WordPress option with prefix
	 *
	 * @param string $name Option name
	 * @param mixed  $default Default value
	 * @return mixed
	 */
	private function get_option( $name, $default = null ) {
		return get_option( 'fedex_' . $name, $default );
	}

	/**
	 * Update WordPress option with prefix
	 *
	 * @param string $name Option name
	 * @param mixed  $value Option value
	 * @return bool
	 */
	public function update_option( $name, $value ) {
		return update_option( 'fedex_' . $name, $value );
	}

	/**
	 * Get options from POST data
	 *
	 * @return void
	 */
	public function get_options_from_post() {
		if ( isset( $_POST['fedex_environment'] ) ) {
			$this->set_environment( sanitize_text_field( wp_unslash( $_POST['fedex_environment'] ) ) );
		}

		if ( isset( $_POST['fedex_client_id'] ) ) {
			$this->update_option( 'client_id', sanitize_text_field( wp_unslash( $_POST['fedex_client_id'] ) ) );
		}

		if ( isset( $_POST['fedex_client_secret'] ) ) {
			$this->update_option( 'client_secret', sanitize_text_field( wp_unslash( $_POST['fedex_client_secret'] ) ) );
		}
	}
}
