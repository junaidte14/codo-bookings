<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'codobookings_admin_menu' );
function codobookings_admin_menu() {
    add_menu_page(
        __( 'CodoBookings', 'codobookings' ),
        __( 'CodoBookings', 'codobookings' ),
        'manage_options',
        'codobookings_dashboard',
        'codobookings_dashboard',
        'dashicons-calendar-alt',
        26
    );

    add_submenu_page(
        'codobookings_dashboard',
        __( 'Dashboard', 'codobookings' ),
        __( 'Dashboard', 'codobookings' ),
        'manage_options',
        'codobookings_dashboard',
        'codobookings_dashboard',
        0
    );

    add_submenu_page(
        'codobookings_dashboard',
        __( 'Settings', 'codobookings' ),
        __( 'Settings', 'codobookings' ),
        'manage_options',
        'codobookings_settings',
        'codobookings_settings_page',
        10
    );
}
