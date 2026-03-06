<?php
/**
 * FedEx OAuth Token Manager
 *
 * @package FedEx_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for managing FedEx OAuth tokens
 */
class FedEx_OAuth {

	/**
	 * Get OAuth token
	 *
	 * @return array|false Token data or false on failure
	 */
	public static function get_token() {
		// Check if token is cached and still valid
		$cached_token = get_transient( 'fedex_oauth_token' );
		
		if ( $cached_token ) {
			return $cached_token;
		}

		// Get credentials from options
		$client_id = get_option( 'fedex_client_id' );
		$client_secret = get_option( 'fedex_client_secret' );

		if ( ! $client_id || ! $client_secret ) {
			return false;
		}

		// Request new token
		$token = self::request_token( $client_id, $client_secret );

		if ( $token ) {
			// Cache token for 59 minutes (expires_in is usually 3600 seconds)
			$expires_in = isset( $token['expires_in'] ) ? $token['expires_in'] - 60 : 3540;
			set_transient( 'fedex_oauth_token', $token, $expires_in );
			return $token;
		}

		return false;
	}

	/**
	 * Request OAuth token from FedEx API
	 *
	 * @param string $client_id Client ID
	 * @param string $client_secret Client Secret
	 * @return array|false Token response or false
	 */
	private static function request_token( $client_id, $client_secret ) {
		$grant_type = get_option( 'fedex_grant_type', 'client_credentials' );

		$body = array(
			'grant_type'    => $grant_type,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);

		// Add child credentials if using csp_credentials
		if ( 'csp_credentials' === $grant_type ) {
			$child_key = get_option( 'fedex_child_key' );
			$child_secret = get_option( 'fedex_child_secret' );

			if ( $child_key && $child_secret ) {
				$body['child_key'] = $child_key;
				$body['child_secret'] = $child_secret;
			}
		}

		$response = wp_remote_post(
			FEDEX_OAUTH_ENDPOINT,
			array(
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'      => http_build_query( $body ),
				'timeout'   => 30,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'FedEx OAuth Error: ' . $response->get_error_message() );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $status_code ) {
			error_log( 'FedEx OAuth Error: ' . $status_code . ' - ' . $body );
			return false;
		}

		$token_data = json_decode( $body, true );

		if ( ! isset( $token_data['access_token'] ) ) {
			error_log( 'FedEx OAuth Error: No access token in response' );
			return false;
		}

		return $token_data;
	}

	/**
	 * Get access token string
	 *
	 * @return string|false Access token or false
	 */
	public static function get_access_token() {
		$token = self::get_token();
		return $token ? $token['access_token'] : false;
	}

	/**
	 * Refresh token by clearing cache
	 *
	 * @return bool
	 */
	public static function refresh_token() {
		delete_transient( 'fedex_oauth_token' );
		return (bool) self::get_token();
	}

	/**
	 * Clear cached token
	 */
	public static function clear_cache() {
		delete_transient( 'fedex_oauth_token' );
	}
}
