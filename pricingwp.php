<?php
/**
 * Plugin Name: Pricing
 * Version: 1.0.0
 * Plugin URI: jimeneziglesiasfernando@gmail.com
 * Description: Modify automatically the price of products based on the trend of sales.
 * Author: Fernando Jimenez Iglesias
 * Author URI: jimeneziglesiasfernando@gmail.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: wordpress-plugin-template
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/pricing.php';
require_once 'includes/pricing-settings.php';

// Load plugin libraries.
require_once 'includes/lib/pricing-admin-api.php';
require_once 'includes/lib/pricing-post-type.php';
require_once 'includes/lib/pricing-taxonomy.php';

/**
 * Returns the main instance of PricingWP to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object PricingWP
 */
function pricing_wp() {
	$instance = Pricing::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Pricing_Settings::instance( $instance );
	}

	return $instance;
}

pricing_wp();
