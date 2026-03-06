<?php
/**
 * Plugin Name: FedEx Shipping Integration
 * Plugin URI: https://your-website.com
 * Description: Integrate FedEx shipping services with your WordPress store using OAuth 2.0
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * License: GPL v2 or later
 * Text Domain: fedex-shipping
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package FedEx
 */

namespace FedEx;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'FEDEX_PLUGIN_VERSION', '2.0.0' );
define( 'FEDEX_PLUGIN_PATH', __DIR__ );
define( 'FEDEX_PLUGIN_URL', plugins_url( '', __FILE__ ) );

// Load Composer autoloader if it exists
if ( file_exists( FEDEX_PLUGIN_PATH . '/vendor/autoload.php' ) ) {
	require_once FEDEX_PLUGIN_PATH . '/vendor/autoload.php';
} else {
	// Fallback to built-in autoloader if Composer not used
	require_once FEDEX_PLUGIN_PATH . '/includes/Autoloader.php';
	Autoloader::register();
}

// Bootstrap the plugin
Container::getInstance()->bootstrap();
