<?php
/**
 * Plugin Name: CF7 Conditional Email Routing
 * Description: Routes email to different recipients based on form field values.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: conditional-email-routing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'CERCF7_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CERCF7_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if Contact Form 7 is active
 */
function cercf7_check_dependencies() {
    if ( ! defined( 'WPCF7_PLUGIN' ) ) {
        add_action( 'admin_notices', 'cercf7_dependency_notice' );
    } else {
        require_once CERCF7_PLUGIN_DIR . 'includes/class-cercf7-conditional-routing.php';
        CERCF7_Conditional_Email_Routing::get_instance();
    }
}
add_action( 'plugins_loaded', 'cercf7_check_dependencies' );

/**
 * Display admin notice if Contact Form 7 is not active
 */
function cercf7_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            esc_html_e( 
                'Conditional Email Routing requires the Contact Form 7 plugin to be installed and activated. Please install and activate Contact Form 7 to use this plugin.', 
                'conditional-email-routing' 
            );
            ?>
        </p>
    </div>
    <?php
}
