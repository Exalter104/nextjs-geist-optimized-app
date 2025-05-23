<?php
/*
Plugin Name: Advanced Security Login Protector
Plugin URI: https://example.com/advanced-security-login-protector
Description: A WordPress security plugin to limit login attempts, block IPs, send notifications, and provide admin controls.
Version: 1.0.0
Author: Your Name
Author URI: https://example.com
License: GPL2
Text Domain: adv-sec-login-protector
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Adv_Sec_Login_Protector {

    private $max_attempts_option = 'asl_max_login_attempts';
    private $lockout_time_option = 'asl_lockout_time';
    private $blocked_ips_option = 'asl_blocked_ips';
    private $whitelisted_ips_option = 'asl_whitelisted_ips';
    private $login_attempts_option = 'asl_login_attempts';
    private $notification_email_option = 'asl_notification_email';

    public function __construct() {
        // Set default options on activation
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );

        // Add actions and filters
        add_action( 'wp_login_failed', array( $this, 'handle_failed_login' ) );
        add_action( 'wp_login', array( $this, 'handle_successful_login' ), 10, 2 );
        add_action( 'init', array( $this, 'check_ip_block' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
    }

    public function activate_plugin() {
        if ( get_option( $this->max_attempts_option ) === false ) {
            update_option( $this->max_attempts_option, 5 );
        }
        if ( get_option( $this->lockout_time_option ) === false ) {
            update_option( $this->lockout_time_option, 15 ); // minutes
        }
        if ( get_option( $this->blocked_ips_option ) === false ) {
            update_option( $this->blocked_ips_option, array() );
        }
        if ( get_option( $this->whitelisted_ips_option ) === false ) {
            update_option( $this->whitelisted_ips_option, array() );
        }
        if ( get_option( $this->login_attempts_option ) === false ) {
            update_option( $this->login_attempts_option, array() );
        }
        if ( get_option( $this->notification_email_option ) === false ) {
            update_option( $this->notification_email_option, get_option('admin_email') );
        }
    }

    private function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            return trim( $ip_list[0] );
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    public function handle_failed_login( $username ) {
        $ip = $this->get_client_ip();

        // Check if IP is whitelisted
        $whitelisted_ips = get_option( $this->whitelisted_ips_option, array() );
        if ( in_array( $ip, $whitelisted_ips ) ) {
            return;
        }

        // Get current attempts
        $attempts = get_option( $this->login_attempts_option, array() );

        if ( isset( $attempts[ $ip ] ) ) {
            $attempts[ $ip ]['count']++;
            $attempts[ $ip ]['last_attempt'] = time();
        } else {
            $attempts[ $ip ] = array(
                'count' => 1,
                'last_attempt' => time(),
                'blocked_until' => 0,
            );
        }

        $max_attempts = intval( get_option( $this->max_attempts_option, 5 ) );
        $lockout_time = intval( get_option( $this->lockout_time_option, 15 ) ) * 60;

        // Check if should block
        if ( $attempts[ $ip ]['count'] >= $max_attempts ) {
            $attempts[ $ip ]['blocked_until'] = time() + $lockout_time;

            // Add to blocked IPs list
            $blocked_ips = get_option( $this->blocked_ips_option, array() );
            $blocked_ips[ $ip ] = $attempts[ $ip ]['blocked_until'];
            update_option( $this->blocked_ips_option, $blocked_ips );

            // Send notification email
            $this->send_lockout_email( $ip, $attempts[ $ip ]['count'] );
        }

        update_option( $this->login_attempts_option, $attempts );
    }

    public function handle_successful_login( $user_login, $user ) {
        $ip = $this->get_client_ip();

        // Reset attempts on successful login
        $attempts = get_option( $this->login_attempts_option, array() );
        if ( isset( $attempts[ $ip ] ) ) {
            unset( $attempts[ $ip ] );
            update_option( $this->login_attempts_option, $attempts );
        }
    }

    public function check_ip_block() {
        if ( is_user_logged_in() ) {
            return;
        }

        $ip = $this->get_client_ip();

        // Check whitelist
        $whitelisted_ips = get_option( $this->whitelisted_ips_option, array() );
        if ( in_array( $ip, $whitelisted_ips ) ) {
            return;
        }

        $blocked_ips = get_option( $this->blocked_ips_option, array() );

        if ( isset( $blocked_ips[ $ip ] ) ) {
            if ( time() < $blocked_ips[ $ip ] ) {
                wp_die( __( 'Your IP has been temporarily blocked due to too many failed login attempts. Please try again later.', 'adv-sec-login-protector' ) );
            } else {
                // Unblock IP after lockout time
                unset( $blocked_ips[ $ip ] );
                update_option( $this->blocked_ips_option, $blocked_ips );

                // Also reset attempts
                $attempts = get_option( $this->login_attempts_option, array() );
                if ( isset( $attempts[ $ip ] ) ) {
                    unset( $attempts[ $ip ] );
                    update_option( $this->login_attempts_option, $attempts );
                }
            }
        }
    }

    public function send_lockout_email( $ip, $attempts ) {
        $to = get_option( $this->notification_email_option, get_option('admin_email') );
        $subject = __( 'IP Blocked Due to Failed Login Attempts', 'adv-sec-login-protector' );
        $message = sprintf(
            __( 'The IP address %s has been blocked after %d failed login attempts.', 'adv-sec-login-protector' ),
            $ip,
            $attempts
        );
        wp_mail( $to, $subject, $message );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Security Login Protector', 'adv-sec-login-protector' ),
            __( 'Login Protector', 'adv-sec-login-protector' ),
            'manage_options',
            'adv-sec-login-protector',
            array( $this, 'admin_page' ),
            'dashicons-shield-alt',
            60
        );
    }

    public function register_settings() {
        register_setting( 'adv_sec_login_protector_settings', $this->max_attempts_option );
        register_setting( 'adv_sec_login_protector_settings', $this->lockout_time_option );
        register_setting( 'adv_sec_login_protector_settings', $this->notification_email_option );
        register_setting( 'adv_sec_login_protector_settings', $this->whitelisted_ips_option );
    }

    public function admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'adv-sec-login-protector' ) );
        }

        // Handle form submission
        if ( isset( $_POST['asl_reset'] ) && check_admin_referer( 'asl_reset_action', 'asl_reset_nonce' ) ) {
            update_option( $this->blocked_ips_option, array() );
            update_option( $this->login_attempts_option, array() );
            echo '<div class="updated"><p>' . __( 'Blocked IPs and login attempts have been reset.', 'adv-sec-login-protector' ) . '</p></div>';
        }

        $max_attempts = get_option( $this->max_attempts_option, 5 );
        $lockout_time = get_option( $this->lockout_time_option, 15 );
        $notification_email = get_option( $this->notification_email_option, get_option('admin_email') );
        $whitelisted_ips = get_option( $this->whitelisted_ips_option, array() );
        $blocked_ips = get_option( $this->blocked_ips_option, array() );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Security Login Protector Settings', 'adv-sec-login-protector' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'adv_sec_login_protector_settings' );
                do_settings_sections( 'adv_sec_login_protector_settings' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Max Login Attempts', 'adv-sec-login-protector' ); ?></th>
                        <td><input type="number" name="<?php echo esc_attr( $this->max_attempts_option ); ?>" value="<?php echo esc_attr( $max_attempts ); ?>" min="1" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Lockout Time (minutes)', 'adv-sec-login-protector' ); ?></th>
                        <td><input type="number" name="<?php echo esc_attr( $this->lockout_time_option ); ?>" value="<?php echo esc_attr( $lockout_time ); ?>" min="1" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Notification Email', 'adv-sec-login-protector' ); ?></th>
                        <td><input type="email" name="<?php echo esc_attr( $this->notification_email_option ); ?>" value="<?php echo esc_attr( $notification_email ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Whitelisted IPs (comma separated)', 'adv-sec-login-protector' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( $this->whitelisted_ips_option ); ?>" value="<?php echo esc_attr( implode( ',', $whitelisted_ips ) ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2><?php esc_html_e( 'Blocked IPs', 'adv-sec-login-protector' ); ?></h2>
            <?php if ( ! empty( $blocked_ips ) ) : ?>
                <ul>
                    <?php foreach ( $blocked_ips as $ip => $blocked_until ) : ?>
                        <li><?php echo esc_html( $ip ); ?> - <?php echo esc_html( date( 'Y-m-d H:i:s', $blocked_until ) ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e( 'No IPs are currently blocked.', 'adv-sec-login-protector' ); ?></p>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'asl_reset_action', 'asl_reset_nonce' ); ?>
                <input type="submit" name="asl_reset" class="button button-secondary" value="<?php esc_attr_e( 'Reset Blocked IPs and Attempts', 'adv-sec-login-protector' ); ?>" />
            </form>
        </div>
        <?php
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'adv_sec_login_protector_dashboard_widget',
            __( 'Login Protector Status', 'adv-sec-login-protector' ),
            array( $this, 'dashboard_widget_display' )
        );
    }

    public function dashboard_widget_display() {
        $blocked_ips = get_option( $this->blocked_ips_option, array() );
        $count_blocked = count( $blocked_ips );
        ?>
        <p><?php printf( __( 'Currently blocked IPs: %d', 'adv-sec-login-protector' ), $count_blocked ); ?></p>
        <?php
    }
}

new Adv_Sec_Login_Protector();

?>
