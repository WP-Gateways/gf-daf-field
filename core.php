<?php
/*
Plugin Name: Gravity Forms Donor-Advised Fund (DAF) Card Field Type
Plugin URI: https://wpgateways.com/
Description: Adds a Donor-Advised Fund (DAF) card field type to Gravity Forms
Version: 1.0.1
Author: WP Gateways
Author URI: https://wpgateways.com
Text Domain: gf-daf-field
Domain Path: /languages
Requires Plugins: gravityforms

	Copyright: © WP Gateways.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

define( 'GF_DAF_VERSION', '1.0.1' );
define( 'GF_DAF_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'GF_DAF_PLUGIN_URL', plugins_url( plugin_basename( GF_DAF_PLUGIN_DIR ) ) );
define( 'GF_DAF_PLUGIN_PATH', plugin_basename( __FILE__ ) );

function gf_daf_load() {
	require_once( 'class-gf-field-daf.php' );
	require_once( 'scripts.php' );
	load_plugin_textdomain( 'gf-daf-field', false, GF_DAF_PLUGIN_DIR . '/languages/' );
}
add_action( 'gform_loaded', 'gf_daf_load' );