<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SLLA_Admin_Settings {

    private static $instance = null;

    private $premium_activated_option = 'slla_premium_activated';

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'simple-limit-login-attempts' ) !== false ) {
            wp_enqueue_style( 'slla-admin-css', plugins_url( '../assets/css/slla-admin.css', __FILE__ ), array(), SLLA_VERSION );
            wp_enqueue_script( 'slla-settings-ajax', plugins_url( '../assets/js/slla-settings-ajax.js', __FILE__ ), array( 'jquery' ), SLLA_VERSION, true );
            wp_localize_script( 'slla-settings-ajax', 'slla_ajax_obj', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'slla_ajax_nonce' ),
            ) );
        }
    }

    public function add_admin_menu() {
        $capability = 'manage_options';
        $menu_slug = 'simple-limit-login-attempts';

        add_menu_page(
            __( 'Simple Limit Login Attempts', 'simple-limit-login-attempts' ),
            __( 'Simple Limit Login Attempts', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug,
            array( $this, 'render_dashboard_page' ),
            'dashicons-shield-alt',
            60
        );

        add_submenu_page(
            $menu_slug,
            __( 'Dashboard', 'simple-limit-login-attempts' ),
            __( 'Dashboard', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug,
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            $menu_slug,
            __( 'Settings', 'simple-limit-login-attempts' ),
            __( 'Settings', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug . '-settings',
            array( $this, 'render_settings_page' )
        );

        add_submenu_page(
            $menu_slug,
            __( 'Geo-Blocking', 'simple-limit-login-attempts' ),
            __( 'Geo-Blocking', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug . '-geo-blocking',
            array( $this, 'render_geo_blocking_page' )
        );

        add_submenu_page(
            $menu_slug,
            __( 'Logs', 'simple-limit-login-attempts' ),
            __( 'Logs', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug . '-logs',
            array( $this, 'render_logs_page' )
        );

        add_submenu_page(
            $menu_slug,
            __( 'Tools', 'simple-limit-login-attempts' ),
            __( 'Tools', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug . '-tools',
            array( $this, 'render_tools_page' )
        );

        add_submenu_page(
            $menu_slug,
            __( 'Premium', 'simple-limit-login-attempts' ),
            __( 'Premium', 'simple-limit-login-attempts' ),
            $capability,
            $menu_slug . '-premium',
            array( $this, 'render_premium_page' )
        );
    }

    public function register_settings() {
        // Register all settings here
        register_setting( 'slla_settings_group', 'slla_max_attempts', array( 'sanitize_callback' => 'absint', 'default' => 5 ) );
        register_setting( 'slla_settings_group', 'slla_lockout_duration', array( 'sanitize_callback' => 'absint', 'default' => 15 ) );
        register_setting( 'slla_settings_group', 'slla_safelist_ips', array( 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_denylist_ips', array( 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_gdpr_compliance', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_custom_error_message', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );

        // Premium options
        register_setting( 'slla_settings_group', 'slla_email_notifications', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_strong_password', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_block_countries', array( 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_enable_2fa', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_ipstack_api_key', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_allowed_countries', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'PK' ) );
        register_setting( 'slla_settings_group', 'slla_twilio_account_sid', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_twilio_auth_token', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_twilio_phone_number', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_admin_phone_number', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
        register_setting( 'slla_settings_group', 'slla_enable_email_notifications', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_enable_sms_notifications', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_weekly_summary_email', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_enable_auto_updates', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_premium_activated', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
        register_setting( 'slla_settings_group', 'slla_license_key', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
    }

    public function render_dashboard_page() {
        ?>
        <div class="wrap slla-dashboard-wrap">
            <h1><?php esc_html_e( 'Simple Limit Login Attempts - Dashboard', 'simple-limit-login-attempts' ); ?></h1>
            <p><?php esc_html_e( 'Basic AI Insights: High risk from IP 192.168.1.100 due to repeated attempts.', 'simple-limit-login-attempts' ); ?></p>
            <!-- Additional dashboard content here -->
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Simple Limit Login Attempts - Settings', 'simple-limit-login-attempts' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'slla_settings_group' );
                do_settings_sections( 'slla_settings_group' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="slla_max_attempts"><?php esc_html_e( 'Max Login Attempts', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><input name="slla_max_attempts" type="number" id="slla_max_attempts" value="<?php echo esc_attr( get_option( 'slla_max_attempts' ) ); ?>" class="small-text" min="1" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slla_lockout_duration"><?php esc_html_e( 'Lockout Duration (minutes)', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><input name="slla_lockout_duration" type="number" id="slla_lockout_duration" value="<?php echo esc_attr( get_option( 'slla_lockout_duration' ) ); ?>" class="small-text" min="1" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slla_safelist_ips"><?php esc_html_e( 'Safelist IPs (one per line)', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><textarea name="slla_safelist_ips" id="slla_safelist_ips" rows="5" cols="50"><?php echo esc_textarea( get_option( 'slla_safelist_ips' ) ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slla_denylist_ips"><?php esc_html_e( 'Denylist IPs (one per line)', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><textarea name="slla_denylist_ips" id="slla_denylist_ips" rows="5" cols="50"><?php echo esc_textarea( get_option( 'slla_denylist_ips' ) ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'GDPR Compliance', 'simple-limit-login-attempts' ); ?></th>
                        <td><input name="slla_gdpr_compliance" type="checkbox" id="slla_gdpr_compliance" value="1" <?php checked( 1, get_option( 'slla_gdpr_compliance' ) ); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slla_custom_error_message"><?php esc_html_e( 'Custom Error Message', 'simple-limit-login-attempts' ); ?></label></th>
                        <td>
                            <input name="slla_custom_error_message" type="text" id="slla_custom_error_message" value="<?php echo esc_attr( get_option( 'slla_custom_error_message' ) ); ?>" maxlength="255" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'This message will be shown on failed login attempts.', 'simple-limit-login-attempts' ); ?></p>
                            <p id="slla-error-preview" style="font-weight:bold;"></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_geo_blocking_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Geo-Blocking Settings', 'simple-limit-login-attempts' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'slla_settings_group' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="slla_ipstack_api_key"><?php esc_html_e( 'IPstack API Key', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><input name="slla_ipstack_api_key" type="text" id="slla_ipstack_api_key" value="<?php echo esc_attr( get_option( 'slla_ipstack_api_key' ) ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slla_allowed_countries"><?php esc_html_e( 'Allowed Countries (comma separated)', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><input name="slla_allowed_countries" type="text" id="slla_allowed_countries" value="<?php echo esc_attr( get_option( 'slla_allowed_countries' ) ); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_logs_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Login Attempts Logs', 'simple-limit-login-attempts' ); ?></h1>
            <p><?php esc_html_e( 'Logs will be displayed here (simulated).', 'simple-limit-login-attempts' ); ?></p>
            <!-- Implement logs display -->
        </div>
        <?php
    }

    public function render_tools_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Tools', 'simple-limit-login-attempts' ); ?></h1>
            <p><?php esc_html_e( 'Tools for premium users.', 'simple-limit-login-attempts' ); ?></p>
            <!-- Implement tools like unblock admin -->
        </div>
        <?php
    }

    public function render_premium_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Premium Features Activation', 'simple-limit-login-attempts' ); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'slla_premium_activate', 'slla_premium_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="slla_license_key"><?php esc_html_e( 'License Key', 'simple-limit-login-attempts' ); ?></label></th>
                        <td><input name="slla_license_key" type="text" id="slla_license_key" value="<?php echo esc_attr( get_option( 'slla_license_key' ) ); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Activate Premium', 'simple-limit-login-attempts' ) ); ?>
            </form>
            <?php
            if ( isset( $_POST['slla_license_key'] ) && check_admin_referer( 'slla_premium_activate', 'slla_premium_nonce' ) ) {
                $this->handle_license_activation( sanitize_text_field( $_POST['slla_license_key'] ) );
            }
            ?>
        </div>
        <?php
    }

    private function handle_license_activation( $license_key ) {
        $valid_keys = array(
            'PREMIUM-KEY-12345' => 1,
            'PREMIUMPLUS-KEY-67890' => 2,
        );

        if ( array_key_exists( $license_key, $valid_keys ) ) {
            update_option( 'slla_premium_activated', $valid_keys[ $license_key ] );
            update_option( 'slla_license_key', $license_key );
            echo '<div class="updated"><p>' . esc_html__( 'License activated successfully!', 'simple-limit-login-attempts' ) . '</p></div>';
            // Redirect to settings page after activation
            echo '<script>window.location.href = "admin.php?page=simple-limit-login-attempts-settings";</script>';
        } else {
            echo '<div class="error"><p>' . esc_html__( 'Invalid license key.', 'simple-limit-login-attempts' ) . '</p></div>';
        }
    }
}

?>
