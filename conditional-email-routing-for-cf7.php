<?php
/**
 * Plugin Name: Conditional Email Routing For Contact Form 7
 * Requires Plugins: contact-form-7
 * Description: Routes email to different recipients based on form field values.
 * Version: 1.2
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: WDRaihan
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: conditional-email-routing-for-contact-form-7
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'CERCF7_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CERCF7_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain for translations
 */
add_action( 'plugins_loaded', 'cercf7_load_textdomain' );
function cercf7_load_textdomain(){
	load_plugin_textdomain( 'conditional-email-routing-for-contact-form-7', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Check if Contact Form 7 is active
 */
function cercf7_check_dependencies() {
    if ( defined( 'WPCF7_PLUGIN' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if ( !is_plugin_active( 'conditional-email-routing-for-cf7-pro/conditional-email-routing-for-cf7-pro.php' ) ) {
            // The pro version is not active
            require_once CERCF7_PLUGIN_DIR . 'includes/class-cercf7-conditional-routing.php';
        	CERCF7_Conditional_Email_Routing::get_instance();
        }
    }
}
add_action( 'plugins_loaded', 'cercf7_check_dependencies' );
