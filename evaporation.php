<?php
/**
 * Plugin Name:     Evaporation
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Evaporation is a cache invalidation plugin for WordPress sites that use AWS CloudFront as a full-site cache.
 * Author:          Charles Fulton
 * Author URI:      https://github.com/LafColITS
 * Text Domain:     evaporation
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Evaporation
 */

// Load AWS SDK.
require 'vendor/autoload.php';
require 'includes/class-base.php';

add_action( 'init', array( 'Evaporation\Base', 'init_action' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/cli/evaporation-command.php';
}
