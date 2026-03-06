<?php
/**
 * FedEx Admin Settings Page
 *
 * @package FedEx\Admin
 */

namespace FedEx\Admin;

use FedEx\Services\ApiService;
use FedEx\Config\Configuration;

/**
 * Settings page for FedEx admin
 */
class SettingsPage {

	/**
	 * API service
	 *
	 * @var ApiService
	 */
	private $api;

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param ApiService     $api    API service
	 * @param Configuration  $config Configuration
	 */
	public function __construct( ApiService $api, Configuration $config ) {
		$this->api = $api;
		$this->config = $config;

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings() {
		$settings = array(
			'client_id',
			'client_secret',
			'grant_type',
			'child_key',
			'child_secret',
			'account_number',
			'meter_number',
			'environment',
		);

		foreach ( $settings as $setting ) {
			register_setting( 'fedex_settings', 'fedex_' . $setting );
		}
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			'FedEx Shipping',
			'FedEx Shipping',
			'manage_options',
			'fedex-settings',
			array( $this, 'render_settings' ),
			'dashicons-truck',
			56
		);

		add_submenu_page(
			'fedex-settings',
			'Get Rates',
			'Get Rates',
			'manage_options',
			'fedex-rates',
			array( $this, 'render_rates' )
		);

		add_submenu_page(
			'fedex-settings',
			'Track Shipment',
			'Track Shipment',
			'manage_options',
			'fedex-track',
			array( $this, 'render_track' )
		);
	}

	/**
	 * Render main page
	 *
	 * @return void
	 */
	public function render_page() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'fedex-settings' ) {
			$this->render_settings();
		}
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Handle test connection
		if ( isset( $_POST['fedex_test_connection'] ) && $this->verify_nonce() ) {
			$this->config->get_options_from_post();
			$connected = $this->api->test_connection();
			$this->show_notice( $connected );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'fedex_settings' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="fedex_environment">Environment</label></th>
						<td>
							<select id="fedex_environment" name="fedex_environment">
								<option value="sandbox" <?php selected( $this->config->get_environment(), 'sandbox' ); ?>>Sandbox (Testing)</option>
								<option value="production" <?php selected( $this->config->get_environment(), 'production' ); ?>>Production (Live)</option>
							</select>
							<p class="description">Sandbox uses testing API, Production uses live API</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_client_id">Client ID (API Key)</label></th>
						<td>
							<input type="text" id="fedex_client_id" name="fedex_client_id" value="<?php echo esc_attr( $this->config->get_client_id() ); ?>" class="regular-text" required />
							<p class="description">From FedEx Developer Portal</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_client_secret">Client Secret (Secret Key)</label></th>
						<td>
							<input type="password" id="fedex_client_secret" name="fedex_client_secret" value="<?php echo esc_attr( $this->config->get_client_secret() ); ?>" class="regular-text" required />
							<p class="description">From FedEx Developer Portal</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_grant_type">Grant Type</label></th>
						<td>
							<select id="fedex_grant_type" name="fedex_grant_type">
								<option value="client_credentials" <?php selected( $this->config->get_grant_type(), 'client_credentials' ); ?>>Client Credentials</option>
								<option value="csp_credentials" <?php selected( $this->config->get_grant_type(), 'csp_credentials' ); ?>>CSP Credentials</option>
								<option value="client_pc_credentials" <?php selected( $this->config->get_grant_type(), 'client_pc_credentials' ); ?>>Parent Child Credentials</option>
							</select>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_child_key">Child Key (optional)</label></th>
						<td>
							<input type="text" id="fedex_child_key" name="fedex_child_key" value="<?php echo esc_attr( $this->config->get_child_key() ); ?>" class="regular-text" />
							<p class="description">Required for CSP/Parent-Child accounts</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_child_secret">Child Secret (optional)</label></th>
						<td>
							<input type="password" id="fedex_child_secret" name="fedex_child_secret" value="<?php echo esc_attr( $this->config->get_child_secret() ); ?>" class="regular-text" />
							<p class="description">Required for CSP/Parent-Child accounts</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_account_number">Account Number</label></th>
						<td>
							<input type="text" id="fedex_account_number" name="fedex_account_number" value="<?php echo esc_attr( $this->config->get_account_number() ); ?>" class="regular-text" />
						</td>
					</tr>

					<tr>
						<th><label for="fedex_meter_number">Meter Number</label></th>
						<td>
							<input type="text" id="fedex_meter_number" name="fedex_meter_number" value="<?php echo esc_attr( $this->config->get_meter_number() ); ?>" class="regular-text" />
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />
			<h3>Test Connection</h3>
			<form method="post" action="">
				<?php wp_nonce_field( 'fedex_test' ); ?>
				<input type="hidden" name="fedex_test_connection" value="1" />
				<button type="submit" class="button button-secondary">Test OAuth Connection</button>
			</form>

			<hr />
			<h3>API Endpoints</h3>
			<p>
				<strong>Sandbox:</strong><br>
				OAuth: <code>https://apis-sandbox.fedex.com/oauth/token</code><br>
				API: <code>https://apis-sandbox.fedex.com</code>
			</p>
			<p>
				<strong>Production:</strong><br>
				OAuth: <code>https://apis.fedex.com/oauth/token</code><br>
				API: <code>https://apis.fedex.com</code>
			</p>
		</div>
		<?php
	}

	/**
	 * Render rates page
	 *
	 * @return void
	 */
	public function render_rates() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>Rate calculation tool coming soon</p>
		</div>
		<?php
	}

	/**
	 * Render track page
	 *
	 * @return void
	 */
	public function render_track() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>Shipment tracking tool coming soon</p>
		</div>
		<?php
	}

	/**
	 * Verify nonce
	 *
	 * @return bool
	 */
	private function verify_nonce() {
		return isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'fedex_test' );
	}

	/**
	 * Show notice
	 *
	 * @param bool $success Success status
	 * @return void
	 */
	private function show_notice( $success ) {
		if ( $success ) {
			echo '<div class="notice notice-success"><p>✓ Connection successful!</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>✗ Connection failed.</p></div>';
		}
	}
}
