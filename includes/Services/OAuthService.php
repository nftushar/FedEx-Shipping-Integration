<?php
/**
 * FedEx OAuth Service
 *
 * @package FedEx\Services
 */

namespace FedEx\Services;

use FedEx\Config\Configuration;
use FedEx\Exceptions\OAuthException;

/**
 * OAuth service for token management
 */
class OAuthService {

	/**
	 * Configuration instance
	 *
	 * @var Configuration
	 */
	private $config;

	/**
	 * Token cache key
	 *
	 * @var string
	 */
	private const TOKEN_CACHE_KEY = 'fedex_oauth_token';

	/**
	 * Token expiration buffer (seconds)
	 *
	 * @var int
	 */
	private const TOKEN_BUFFER = 60;

	/**
	 * Constructor
	 *
	 * @param Configuration $config Configuration instance
	 */
	public function __construct( Configuration $config ) {
		$this->config = $config;
	}

	/**
	 * Get OAuth token
	 *
	 * @return array Token data
	 * @throws OAuthException
	 */
	public function get_token() {
		// Check cache first
		$cached = $this->get_cached_token();
		if ( $cached ) {
			return $cached;
		}

		// Request new token
		$token = $this->request_token();

		// Cache token
		if ( $token ) {
			$this->cache_token( $token );
		}

		return $token;
	}

	/**
	 * Get access token string
	 *
	 * @return string Access token
	 * @throws OAuthException
	 */
	public function get_access_token() {
		$token = $this->get_token();
		return $token['access_token'] ?? null;
	}

	/**
	 * Request token from FedEx API
	 *
	 * @return array Token data
	 * @throws OAuthException
	 */
	private function request_token() {
		if ( ! $this->config->is_configured() ) {
			throw new OAuthException( 'FedEx credentials not configured' );
		}

		$body = $this->build_token_request_body();
		$endpoint = $this->config->get_oauth_endpoint();

		$response = wp_remote_post(
			$endpoint,
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
			throw new OAuthException( 'OAuth request failed: ' . $response->get_error_message() );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $status ) {
			throw new OAuthException( 'OAuth error (' . $status . '): ' . $body );
		}

		$data = json_decode( $body, true );

		if ( ! isset( $data['access_token'] ) ) {
			throw new OAuthException( 'No access token in response' );
		}

		return $data;
	}

	/**
	 * Build token request body
	 *
	 * @return array
	 */
	private function build_token_request_body() {
		$body = array(
			'grant_type'    => $this->config->get_grant_type(),
			'client_id'     => $this->config->get_client_id(),
			'client_secret' => $this->config->get_client_secret(),
		);

		// Add child credentials if needed
		$child_key = $this->config->get_child_key();
		$child_secret = $this->config->get_child_secret();

		if ( $child_key && $child_secret ) {
			$body['child_key'] = $child_key;
			$body['child_secret'] = $child_secret;
		}

		return $body;
	}

	/**
	 * Get cached token
	 *
	 * @return array|null
	 */
	private function get_cached_token() {
		return get_transient( self::TOKEN_CACHE_KEY );
	}

	/**
	 * Cache token
	 *
	 * @param array $token Token data
	 * @return void
	 */
	private function cache_token( array $token ) {
		$expires_in = isset( $token['expires_in'] ) ? $token['expires_in'] - self::TOKEN_BUFFER : 3540;
		set_transient( self::TOKEN_CACHE_KEY, $token, max( $expires_in, 60 ) );
	}

	/**
	 * Clear cached token
	 *
	 * @return void
	 */
	public function clear_cache() {
		delete_transient( self::TOKEN_CACHE_KEY );
	}

	/**
	 * Refresh token
	 *
	 * @return array Token data
	 * @throws OAuthException
	 */
	public function refresh_token() {
		$this->clear_cache();
		return $this->get_token();
	}
}
