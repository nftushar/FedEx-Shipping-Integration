<?php
/**
 * FedEx Admin Settings Page
 *
 * @package FedEx\Admin
 */

namespace FedEx\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page for FedEx OAuth configuration
 */
class SettingsPage {

	/**
	 * Menu slug
	 *
	 * @var string
	 */
	private $menu_slug = 'fedex-settings';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add WordPress admin menu
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			'FedEx OAuth Settings',           // Page title
			'FedEx OAuth',                    // Menu title
			'manage_options',                 // Capability
			$this->menu_slug,                 // Menu slug
			array( $this, 'render_page' ),   // Callback
			'dashicons-share',                // Icon
			99                                // Position
		);
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'fedex_settings_group', 'fedex_client_id' );
		register_setting( 'fedex_settings_group', 'fedex_client_secret' );
		register_setting( 'fedex_settings_group', 'fedex_environment' );
		register_setting( 'fedex_settings_group', 'fedex_grant_type' );
	}

	/**
	 * Render settings page HTML
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'fedex-shipping' ) );
		}

		$client_id = get_option( 'fedex_client_id' );
		$client_secret = get_option( 'fedex_client_secret' );
		$environment = get_option( 'fedex_environment', 'sandbox' );
		$grant_type = get_option( 'fedex_grant_type', 'client_credentials' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'FedEx OAuth Settings', 'fedex-shipping' ); ?></h1>
			<p><?php esc_html_e( 'Configure your FedEx OAuth 2.0 credentials for API authentication.', 'fedex-shipping' ); ?></p>

			<form method="POST" action="options.php">
				<?php settings_fields( 'fedex_settings_group' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="fedex_environment">
								<?php esc_html_e( 'Environment', 'fedex-shipping' ); ?>
							</label>
						</th>
						<td>
							<select name="fedex_environment" id="fedex_environment">
								<option value="sandbox" <?php selected( $environment, 'sandbox' ); ?>>
									<?php esc_html_e( 'Sandbox (Testing)', 'fedex-shipping' ); ?>
								</option>
								<option value="production" <?php selected( $environment, 'production' ); ?>>
									<?php esc_html_e( 'Production (Live)', 'fedex-shipping' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Select sandbox for testing, production for live API calls.', 'fedex-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="fedex_grant_type">
								<?php esc_html_e( 'Grant Type', 'fedex-shipping' ); ?>
							</label>
						</th>
						<td>
							<select name="fedex_grant_type" id="fedex_grant_type">
								<option value="client_credentials" <?php selected( $grant_type, 'client_credentials' ); ?>>
									<?php esc_html_e( 'Client Credentials', 'fedex-shipping' ); ?>
								</option>
								<option value="csp_credentials" <?php selected( $grant_type, 'csp_credentials' ); ?>>
									<?php esc_html_e( 'CSP Credentials', 'fedex-shipping' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Usually client_credentials unless you have CSP account.', 'fedex-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="fedex_client_id">
								<?php esc_html_e( 'Client ID', 'fedex-shipping' ); ?>
							</label>
						</th>
						<td>
							<input type="text" name="fedex_client_id" id="fedex_client_id" 
								value="<?php echo esc_attr( $client_id ); ?>" 
								class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Your FedEx OAuth 2.0 Client ID', 'fedex-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="fedex_client_secret">
								<?php esc_html_e( 'Client Secret', 'fedex-shipping' ); ?>
							</label>
						</th>
						<td>
							<input type="password" name="fedex_client_secret" id="fedex_client_secret" 
								value="<?php echo esc_attr( $client_secret ); ?>" 
								class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Your FedEx OAuth 2.0 Client Secret', 'fedex-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'How to Get Credentials', 'fedex-shipping' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Visit: https://developer.fedex.com', 'fedex-shipping' ); ?></li>
				<li><?php esc_html_e( 'Create a developer account', 'fedex-shipping' ); ?></li>
				<li><?php esc_html_e( 'Create an application', 'fedex-shipping' ); ?></li>
				<li><?php esc_html_e( 'Generate OAuth 2.0 credentials', 'fedex-shipping' ); ?></li>
				<li><?php esc_html_e( 'Paste Client ID and Secret above', 'fedex-shipping' ); ?></li>
			</ol>
		</div>
		<?php
	}
}
