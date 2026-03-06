<?php
/**
 * FedEx Plugin Bootstrap
 *
 * @package FedEx\Core
 */

namespace FedEx\Core;

/**
 * Bootstrap class for plugin activation/deactivation
 */
class Bootstrap {

	/**
	 * Activate plugin
	 *
	 * @return void
	 */
	public static function activate() {
		// Create tables, set default options, etc.
		if ( ! get_option( 'fedex_activated' ) ) {
			update_option( 'fedex_activated', true );
			update_option( 'fedex_environment', 'sandbox' );
			update_option( 'fedex_grant_type', 'client_credentials' );
		}
	}

	/**
	 * Deactivate plugin
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Delete all FedEx plugin data and options
		global $wpdb;
		
		// Delete all FedEx options from database
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'fedex_%'" );
		
		// Clear any cached transients
		delete_transient( 'fedex_oauth_token' );
		
		// Remove activation flag
		delete_option( 'fedex_activated' );
	}

	/**
	 * Uninstall plugin
	 *
	 * @return void
	 */
	public static function uninstall() {
		// Delete all FedEx plugin options
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'fedex_%'" );
	}
}
