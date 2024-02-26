<?php
/**
 * Plugin Name:     Evaporation
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          Charles Fulton
 * Author URI:      YOUR SITE HERE
 * Text Domain:     evaporation
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Evaporation
 */

require( 'includes/class-base.php' );

add_action( 'init', array( 'Evaporation\Base' , 'init_action' ) );