<?php
/**
 * Plugin Name:       Mercury Bootstrapper
 * Plugin URI:        https://github.com/TristanTom/mercury-bootstrapper
 * Description:       Automates the baseline setup of a fresh WordPress site for Mercury Media projects.
 * Version:           0.8.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mercury Media
 * Author URI:        https://mercurymedia.ee
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mercury-bootstrapper
 * Update URI:        https://github.com/TristanTom/mercury-bootstrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MERCURY_BOOTSTRAPPER_VERSION', '0.8.0' );
define( 'MERCURY_BOOTSTRAPPER_FILE', __FILE__ );
define( 'MERCURY_BOOTSTRAPPER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MERCURY_BOOTSTRAPPER_URL', plugin_dir_url( __FILE__ ) );
define( 'MERCURY_BOOTSTRAPPER_SLUG', 'mercury-bootstrapper' );

require_once MERCURY_BOOTSTRAPPER_DIR . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( 'Mercury_Bootstrapper_Plugin', 'instance' ) );
