<?php
/**
 * FedEx Admin Settings
 *
 * @package FedEx_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for admin settings
 */
class FedEx_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_submenu_pages' ) );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// Register settings group
		register_setting( 'fedex_settings', 'fedex_client_id' );
		register_setting( 'fedex_settings', 'fedex_client_secret' );
		register_setting( 'fedex_settings', 'fedex_grant_type' );
		register_setting( 'fedex_settings', 'fedex_child_key' );
		register_setting( 'fedex_settings', 'fedex_child_secret' );
		register_setting( 'fedex_settings', 'fedex_account_number' );
		register_setting( 'fedex_settings', 'fedex_meter_number' );
		register_setting( 'fedex_settings', 'fedex_default_packaging' );
		register_setting( 'fedex_settings', 'fedex_enabled_services' );
		register_setting( 'fedex_settings', 'fedex_use_production' );
	}

	/**
	 * Add submenu pages
	 */
	public function add_submenu_pages() {
		add_submenu_page(
			'fedex-shipping',
			'Settings',
			'Settings',
			'manage_options',
			'fedex-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'fedex-shipping',
			'Get Rates',
			'Get Rates',
			'manage_options',
			'fedex-get-rates',
			array( $this, 'render_get_rates_page' )
		);

		add_submenu_page(
			'fedex-shipping',
			'Track Shipment',
			'Track Shipment',
			'manage_options',
			'fedex-track',
			array( $this, 'render_track_page' )
		);
	}

	/**
	 * Render main page
	 */
	public static function render_main_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="card">
				<h2>FedEx Shipping Integration</h2>
				<p>Welcome to the FedEx Shipping Integration plugin for WordPress. This plugin allows you to integrate FedEx shipping services with your WooCommerce store.</p>
				<h3>Getting Started:</h3>
				<ol>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=fedex-settings' ) ); ?>">Configure your FedEx API credentials</a></li>
					<li>Set up available shipping services</li>
					<li>Test the integration by <a href="<?php echo esc_url( admin_url( 'admin.php?page=fedex-get-rates' ) ); ?>">getting shipping rates</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=fedex-track' ) ); ?>">Track shipments</a></li>
				</ol>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Handle test connection
		if ( isset( $_POST['fedex_test_connection'] ) && isset( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'fedex_test_nonce' ) ) {
				wp_die( 'Security check failed' );
			}

			FedEx_OAuth::clear_cache();
			$token = FedEx_OAuth::get_token();

			if ( $token ) {
				echo '<div class="notice notice-success"><p>✓ Connection successful! Token expires in ' . esc_html( $token['expires_in'] ) . ' seconds.</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>✗ Connection failed. Please check your credentials.</p></div>';
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'fedex_settings' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="fedex_client_id">Client ID (API Key)</label></th>
						<td>
							<input type="text" id="fedex_client_id" name="fedex_client_id" value="<?php echo esc_attr( get_option( 'fedex_client_id' ) ); ?>" class="regular-text" required />
							<p class="description">Your FedEx API Key from the Developer Portal</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_client_secret">Client Secret (Secret Key)</label></th>
						<td>
							<input type="password" id="fedex_client_secret" name="fedex_client_secret" value="<?php echo esc_attr( get_option( 'fedex_client_secret' ) ); ?>" class="regular-text" required />
							<p class="description">Your FedEx Secret Key from the Developer Portal</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_grant_type">Grant Type</label></th>
						<td>
							<select id="fedex_grant_type" name="fedex_grant_type">
								<option value="client_credentials" <?php selected( get_option( 'fedex_grant_type' ), 'client_credentials' ); ?>>Client Credentials</option>
								<option value="csp_credentials" <?php selected( get_option( 'fedex_grant_type' ), 'csp_credentials' ); ?>>CSP Credentials (Integrator)</option>
								<option value="client_pc_credentials" <?php selected( get_option( 'fedex_grant_type' ), 'client_pc_credentials' ); ?>>Parent Child Credentials</option>
							</select>
							<p class="description">Select your authentication type</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_child_key">Child Key (optional)</label></th>
						<td>
							<input type="text" id="fedex_child_key" name="fedex_child_key" value="<?php echo esc_attr( get_option( 'fedex_child_key' ) ); ?>" class="regular-text" />
							<p class="description">Required for CSP and Parent Child customers</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_child_secret">Child Secret (optional)</label></th>
						<td>
							<input type="password" id="fedex_child_secret" name="fedex_child_secret" value="<?php echo esc_attr( get_option( 'fedex_child_secret' ) ); ?>" class="regular-text" />
							<p class="description">Required for CSP and Parent Child customers</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_account_number">FedEx Account Number</label></th>
						<td>
							<input type="text" id="fedex_account_number" name="fedex_account_number" value="<?php echo esc_attr( get_option( 'fedex_account_number' ) ); ?>" class="regular-text" />
							<p class="description">Your FedEx account number</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_meter_number">FedEx Meter Number</label></th>
						<td>
							<input type="text" id="fedex_meter_number" name="fedex_meter_number" value="<?php echo esc_attr( get_option( 'fedex_meter_number' ) ); ?>" class="regular-text" />
							<p class="description">Your FedEx meter number</p>
						</td>
					</tr>

					<tr>
						<th><label for="fedex_use_production">Use Production Server</label></th>
						<td>
							<input type="checkbox" id="fedex_use_production" name="fedex_use_production" value="1" <?php checked( get_option( 'fedex_use_production' ), 1 ); ?> />
							<p class="description">Uncheck to use sandbox. Default is sandbox (unchecked).</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />

			<h3>Test Connection</h3>
			<form method="post" action="">
				<?php wp_nonce_field( 'fedex_test_nonce' ); ?>
				<input type="hidden" name="fedex_test_connection" value="1" />
				<button type="submit" class="button button-secondary">Test OAuth Connection</button>
			</form>

			<hr />

			<h3>API Documentation</h3>
			<p><a href="https://developer.fedex.com" target="_blank">FedEx Developer Portal</a></p>
			<p><a href="https://developer.fedex.com/api/en-us/docs/" target="_blank">FedEx API Docs</a></p>
		</div>
		<?php
	}

	/**
	 * Render get rates page
	 */
	public function render_get_rates_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$rates = null;

		if ( isset( $_POST['fedex_get_rates'] ) && isset( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'fedex_rates_nonce' ) ) {
				wp_die( 'Security check failed' );
			}

			$shipment_data = array(
				'accountNumber'     => get_option( 'fedex_account_number' ),
				'requestedShipment' => array(
					'shipper'      => array(
						'address' => array(
							'postalCode'  => sanitize_text_field( wp_unslash( $_POST['ship_postal_code'] ) ),
							'countryCode' => 'US',
						),
					),
					'recipients'   => array(
						array(
							'address' => array(
								'postalCode'  => sanitize_text_field( wp_unslash( $_POST['recipient_postal_code'] ) ),
								'countryCode' => 'US',
							),
						),
					),
					'pickupType'   => 'STATION',
					'shipDateStamp' => gmdate( 'Y-m-d' ),
					'totalWeight'  => array(
						'units' => 'LB',
						'value' => floatval( sanitize_text_field( wp_unslash( $_POST['weight'] ) ) ),
					),
					'totalDimensions' => array(
						'length' => floatval( sanitize_text_field( wp_unslash( $_POST['length'] ) ) ),
						'width'  => floatval( sanitize_text_field( wp_unslash( $_POST['width'] ) ) ),
						'height' => floatval( sanitize_text_field( wp_unslash( $_POST['height'] ) ) ),
						'units'  => 'IN',
					),
				),
			);

			$rates = FedEx_API::get_rates( $shipment_data );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'fedex_rates_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="ship_postal_code">Shipper Postal Code</label></th>
						<td><input type="text" id="ship_postal_code" name="ship_postal_code" value="10001" class="regular-text" required /></td>
					</tr>

					<tr>
						<th><label for="recipient_postal_code">Recipient Postal Code</label></th>
						<td><input type="text" id="recipient_postal_code" name="recipient_postal_code" value="95014" class="regular-text" required /></td>
					</tr>

					<tr>
						<th><label for="weight">Weight (LB)</label></th>
						<td><input type="number" id="weight" name="weight" value="5" step="0.1" class="regular-text" required /></td>
					</tr>

					<tr>
						<th><label for="length">Length (IN)</label></th>
						<td><input type="number" id="length" name="length" value="10" step="0.1" class="regular-text" required /></td>
					</tr>

					<tr>
						<th><label for="width">Width (IN)</label></th>
						<td><input type="number" id="width" name="width" value="6" step="0.1" class="regular-text" required /></td>
					</tr>

					<tr>
						<th><label for="height">Height (IN)</label></th>
						<td><input type="number" id="height" name="height" value="4" step="0.1" class="regular-text" required /></td>
					</tr>
				</table>

				<input type="hidden" name="fedex_get_rates" value="1" />
				<button type="submit" class="button button-primary">Get Rates</button>
			</form>

			<?php if ( ! is_null( $rates ) ) : ?>
				<hr />
				<h3>Results</h3>
				<pre><?php echo esc_html( wp_json_encode( $rates, JSON_PRETTY_PRINT ) ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render track page
	 */
	public function render_track_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$tracking_info = null;

		if ( isset( $_POST['fedex_track_shipment'] ) && isset( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'fedex_track_nonce' ) ) {
				wp_die( 'Security check failed' );
			}

			$tracking_number = sanitize_text_field( wp_unslash( $_POST['tracking_number'] ) );
			$tracking_info = FedEx_API::track_shipment( $tracking_number );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'fedex_track_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="tracking_number">Tracking Number</label></th>
						<td><input type="text" id="tracking_number" name="tracking_number" class="regular-text" required /></td>
					</tr>
				</table>

				<input type="hidden" name="fedex_track_shipment" value="1" />
				<button type="submit" class="button button-primary">Track Shipment</button>
			</form>

			<?php if ( ! is_null( $tracking_info ) ) : ?>
				<hr />
				<h3>Tracking Information</h3>
				<pre><?php echo esc_html( wp_json_encode( $tracking_info, JSON_PRETTY_PRINT ) ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}
}
