<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add CodoBookings menu under WP Admin
 * We will add a top-level menu for Bookings (admins only)
 */
function codo_admin_menu() {
    add_menu_page(
        __( 'Bookings', 'codo-bookings' ),
        __( 'Bookings', 'codo-bookings' ),
        'manage_options',
        'codo-bookings',
        'codo_admin_dashboard_page',
        'dashicons-calendar-alt',
        6
    );

    // Link to CPT list as submenu
    add_submenu_page(
        'codo-bookings',
        __( 'All Bookings', 'codo-bookings' ),
        __( 'All Bookings', 'codo-bookings' ),
        'manage_options',
        'edit.php?post_type=codo_booking'
    );

    add_submenu_page(
        'codo-bookings',
        __( 'Settings', 'codo-bookings' ),
        __( 'Settings', 'codo-bookings' ),
        'manage_options',
        'codo-bookings-settings',
        'codo_bookings_settings_page'
    );
}
add_action( 'admin_menu', 'codo_admin_menu' );

function codo_admin_dashboard_page() {
    echo '<div class="wrap"><h1>' . esc_html__( 'CodoBookings Dashboard', 'codo-bookings' ) . '</h1>';
    echo '<p>' . esc_html__( 'Quick links:', 'codo-bookings' ) . '</p>';
    echo '<ul>';
    echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=codo_booking' ) ) . '">' . esc_html__( 'Manage Bookings', 'codo-bookings' ) . '</a></li>';
    echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=codo_booking&post_status=publish' ) ) . '">' . esc_html__( 'Published Bookings', 'codo-bookings' ) . '</a></li>';
    echo '</ul>';
    echo '</div>';
}

function codo_admin_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CodoBookings Settings', 'codo-bookings' ); ?></h1>
        <p><?php esc_html_e( 'Settings for CodoBookings will appear here. Settings are modular and can control integration options, email templates, and limits per membership level.', 'codo-bookings' ); ?></p>
    </div>
    <?php
}
