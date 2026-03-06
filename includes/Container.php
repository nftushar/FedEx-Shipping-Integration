<?php
/**
 * FedEx Service Container
 *
 * @package FedEx
 */

namespace FedEx;

/**
 * Service Container for dependency injection
 */
class Container {

	/**
	 * Container instance
	 *
	 * @var Container
	 */
	private static $instance;

	/**
	 * Registered services
	 *
	 * @var array
	 */
	private $services = array();

	/**
	 * Private constructor to prevent instantiation
	 */
	private function __construct() {
		// Prevent direct instantiation
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {
		// Prevent cloning
	}

	/**
	 * Get singleton instance
	 *
	 * @return Container
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register service
	 *
	 * @param string   $name    Service name
	 * @param callable $factory Factory function
	 * @return void
	 */
	public function register( $name, callable $factory ) {
		$this->services[ $name ] = $factory;
	}

	/**
	 * Get service
	 *
	 * @param string $name Service name
	 * @return mixed
	 */
	public function get( $name ) {
		if ( ! isset( $this->services[ $name ] ) ) {
			throw new \Exception( "Service '{$name}' not found in container" );
		}

		$factory = $this->services[ $name ];
		return $factory( $this );
	}

	/**
	 * Bootstrap the plugin
	 *
	 * @return void
	 */
	public function bootstrap() {
		// Register configuration
		$this->register( 'config', function() {
			return new Config\Configuration();
		} );

		// Register OAuth service
		$this->register( 'oauth', function( $container ) {
			return new Services\OAuthService( $container->get( 'config' ) );
		} );

		// Register API service
		$this->register( 'api', function( $container ) {
			return new Services\ApiService( $container->get( 'config' ), $container->get( 'oauth' ) );
		} );

		// Initialize hooks
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	public function init_hooks() {
		// Register activation hooks
		register_activation_hook( \FEDEX_PLUGIN_PATH . '/fedex-plugin.php', array( 'FedEx\\Core\\Bootstrap', 'activate' ) );
		register_deactivation_hook( \FEDEX_PLUGIN_PATH . '/fedex-plugin.php', array( 'FedEx\\Core\\Bootstrap', 'deactivate' ) );

		// Initialize admin
		if ( is_admin() ) {
			new Admin\SettingsPage( $this->get( 'api' ), $this->get( 'config' ) );
		}

		// Initialize WooCommerce integration
		if ( class_exists( 'WC_Shipping_Method' ) ) {
			new WooCommerce\ShippingMethod( $this->get( 'api' ) );
		}
	}
}
