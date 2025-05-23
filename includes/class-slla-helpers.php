<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SLLA_Helpers {

    public static function render_submenu_tabs( $current_tab ) {
        $tabs = array(
            'dashboard'    => __( 'Dashboard', 'simple-limit-login-attempts' ),
            'settings'     => __( 'Settings', 'simple-limit-login-attempts' ),
            'geo-blocking' => __( 'Geo-Blocking', 'simple-limit-login-attempts' ),
            'logs'         => __( 'Logs', 'simple-limit-login-attempts' ),
            'tools'        => __( 'Tools', 'simple-limit-login-attempts' ),
            'premium'      => __( 'Premium', 'simple-limit-login-attempts' ),
        );

        echo '<h2 class="nav-tab-wrapper slla-submenu">';
        foreach ( $tabs as $tab => $name ) {
            $class = ( $tab === $current_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
            $url = admin_url( 'admin.php?page=simple-limit-login-attempts' );
            if ( $tab !== 'dashboard' ) {
                $url .= '-' . $tab;
            }
            printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $name ) );
        }
        echo '</h2>';
    }

    // Additional helper functions can be added here

}

?>
