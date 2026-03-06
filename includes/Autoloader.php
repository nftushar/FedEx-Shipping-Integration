<?php
/**
 * FedEx Plugin Autoloader
 *
 * @package FedEx
 */

namespace FedEx;

/**
 * PSR-4 Autoloader for FedEx plugin
 */
class Autoloader {

	/**
	 * Register autoloader
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load class
	 *
	 * @param string $class Fully qualified class name
	 * @return bool
	 */
	public static function load( $class ) {
		// Only load FedEx namespace classes
		if ( strpos( $class, 'FedEx\\' ) !== 0 ) {
			return false;
		}

		// Remove namespace and convert to path
		$file = str_replace( 'FedEx\\', '', $class );
		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $file );
		$file = \FEDEX_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $file . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}

		return false;
	}
}
