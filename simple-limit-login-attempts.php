<?php
/*
Plugin Name: Simple Limit Login Attempts
Plugin URI: https://example.com/simple-limit-login-attempts
Description: Enhances WordPress login security by limiting failed login attempts with free and premium features.
Version: 1.0.0
Author: Your Name
Author URI: https://example.com
License: GPL2
Text Domain: simple-limit-login-attempts
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SLLA_VERSION', '1.0.0' );
define( 'SLLA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLLA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once SLLA_PLUGIN_DIR . 'includes/class-slla-admin.php';
require_once SLLA_PLUGIN_DIR . 'includes/class-slla-admin-settings.php';
require_once SLLA_PLUGIN_DIR . 'includes/class-slla-helpers.php';

// Initialize the plugin
function slla_init_plugin() {
    // Initialize admin functionality
    if ( is_admin() ) {
        SLLA_Admin::get_instance();
        SLLA_Admin_Settings::get_instance();
    }
}
add_action( 'plugins_loaded', 'slla_init_plugin' );

// Activation and deactivation hooks
function slla_activate_plugin() {
    // Set default options
    if ( get_option( 'slla_max_attempts' ) === false ) {
        update_option( 'slla_max_attempts', 5 );
    }
    if ( get_option( 'slla_lockout_duration' ) === false ) {
        update_option( 'slla_lockout_duration', 15 );
    }
    if ( get_option( 'slla_safelist_ips' ) === false ) {
        update_option( 'slla_safelist_ips', '' );
    }
    if ( get_option( 'slla_denylist_ips' ) === false ) {
        update_option( 'slla_denylist_ips', '' );
    }
    if ( get_option( 'slla_gdpr_compliance' ) === false ) {
        update_option( 'slla_gdpr_compliance', 0 );
    }
    if ( get_option( 'slla_premium_activated' ) === false ) {
        update_option( 'slla_premium_activated', 0 );
    }
}
register_activation_hook( __FILE__, 'slla_activate_plugin' );

function slla_deactivate_plugin() {
    // Cleanup if needed
}
register_deactivation_hook( __FILE__, 'slla_deactivate_plugin' );

?>
