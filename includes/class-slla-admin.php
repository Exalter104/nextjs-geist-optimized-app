<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SLLA_Admin {

    private static $instance = null;

    private $max_attempts_option = 'slla_max_attempts';
    private $lockout_duration_option = 'slla_lockout_duration';
    private $safelist_ips_option = 'slla_safelist_ips';
    private $denylist_ips_option = 'slla_denylist_ips';
    private $gdpr_compliance_option = 'slla_gdpr_compliance';
    private $premium_activated_option = 'slla_premium_activated';

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into login failed and login form
        add_action( 'wp_login_failed', array( $this, 'handle_failed_login' ) );
        add_filter( 'wp_authenticate_user', array( $this, 'check_user_lockout' ), 10, 2 );
        add_filter( 'login_errors', array( $this, 'custom_login_error_message' ) );

        // Enqueue admin styles and scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX handler for test email
        add_action( 'wp_ajax_slla_send_test_email', array( $this, 'ajax_send_test_email' ) );
    }

    public function enqueue_admin_assets( $hook ) {
        // Load assets only on plugin admin pages
        if ( strpos( $hook, 'simple-limit-login-attempts' ) !== false ) {
            wp_enqueue_style( 'slla-admin-css', plugins_url( '../assets/css/slla-admin.css', __FILE__ ), array(), SLLA_VERSION );
            wp_enqueue_script( 'slla-settings-ajax', plugins_url( '../assets/js/slla-settings-ajax.js', __FILE__ ), array( 'jquery' ), SLLA_VERSION, true );
        }
    }

    private function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            return sanitize_text_field( trim( $ip_list[0] ) );
        } else {
            return sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
        }
    }

    public function handle_failed_login( $username ) {
        $ip = $this->get_client_ip();

        // Check safelist
        $safelist_ips = $this->get_ip_list_option( $this->safelist_ips_option );
        if ( in_array( $ip, $safelist_ips ) ) {
            return;
        }

        // Check denylist
        $denylist_ips = $this->get_ip_list_option( $this->denylist_ips_option );
        if ( in_array( $ip, $denylist_ips ) ) {
            // Immediately block
            $this->block_ip( $ip );
            return;
        }

        // Track failed attempts using transient
        $transient_key = 'slla_login_attempts_' . md5( $ip );
        $attempts = get_transient( $transient_key );
        if ( ! $attempts ) {
            $attempts = 1;
        } else {
            $attempts++;
        }

        set_transient( $transient_key, $attempts, $this->get_lockout_duration() * 60 );

        // Check if max attempts exceeded
        if ( $attempts >= $this->get_max_attempts() ) {
            $this->block_ip( $ip );
            // Optionally send notification if premium activated (handled elsewhere)
        }
    }

    private function block_ip( $ip ) {
        // Add IP to denylist option
        $denylist_ips = $this->get_ip_list_option( $this->denylist_ips_option );
        if ( ! in_array( $ip, $denylist_ips ) ) {
            $denylist_ips[] = $ip;
            update_option( $this->denylist_ips_option, implode( "\n", $denylist_ips ) );
        }
    }

    private function get_ip_list_option( $option_name ) {
        $ips = get_option( $option_name, '' );
        $ips_array = array_filter( array_map( 'trim', explode( "\n", $ips ) ) );
        return $ips_array;
    }

    private function get_max_attempts() {
        return absint( get_option( $this->max_attempts_option, 5 ) );
    }

    private function get_lockout_duration() {
        return absint( get_option( $this->lockout_duration_option, 15 ) );
    }

    public function check_user_lockout( $user, $password ) {
        if ( is_wp_error( $user ) ) {
            return $user;
        }

        $ip = $this->get_client_ip();

        // Check denylist
        $denylist_ips = $this->get_ip_list_option( $this->denylist_ips_option );
        if ( in_array( $ip, $denylist_ips ) ) {
            return new WP_Error( 'slla_locked_out', __( 'Your IP has been locked out due to too many failed login attempts.', 'simple-limit-login-attempts' ) );
        }

        return $user;
    }

    public function custom_login_error_message( $error ) {
        $custom_message = get_option( 'slla_custom_error_message', '' );
        if ( ! empty( $custom_message ) ) {
            return esc_html( $custom_message );
        }
        return $error;
    }

    public function ajax_send_test_email() {
        check_ajax_referer( 'slla_ajax_nonce', 'nonce' );

        // Simulate sending test email
        error_log( 'Simple Limit Login Attempts: Test email sent (simulated).' );

        wp_send_json_success( array( 'message' => 'Test email sent (simulated).' ) );
    }
}

?>
