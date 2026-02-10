<?php
/**
 * Plugin Name: Describr
 * Description: The best membership and user profile plugin for WordPress. Describr makes it easy to create beautiful user profiles on the front end. Describr is extensible and makes it easy for you to create websites where users can become members. Describr gives you powerful tools to add capabilities to roles and restrict content.
 * Version: 3.1.3
 * Requires at least: 6.0
 * Requires PHP: 7.0
 * Author: profiletoggler
 * Author URI: https://facebook.com/plzr17
 * Plugin URI: https://github.com/describr/versions/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: describr
 */

//Exit if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DESCRIBR', 'Describr' );
define( 'DESCRIBR_VERSION', '3.1.3' );
define( 'DESCRIBR_DIR', plugin_dir_path( __FILE__ ) );
define( 'DESCRIBR_URL', plugin_dir_url( __FILE__ ) );
define( 'DESCRIBR_FILE', plugin_basename( __FILE__ ) );
define( 'DESCRIBR_TEMPLATE', DESCRIBR_DIR . 'templates' . DIRECTORY_SEPARATOR );

require_once DESCRIBR_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-config.php';
require_once DESCRIBR_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-init.php';

