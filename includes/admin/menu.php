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

    // 👇 Add "Calendar Categories" submenu (taxonomy link)
    add_submenu_page(
        'codobookings_dashboard',
        __( 'Categories', 'codobookings' ),
        __( 'Categories', 'codobookings' ),
        'manage_options',
        'edit-tags.php?taxonomy=codo_calendar_category&post_type=codo_calendar',
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

/**
 * ✅ Force highlight for taxonomy page under custom menu
 */
add_action( 'admin_head', function() {
    global $parent_file, $submenu_file, $current_screen;

    if (
        isset( $current_screen->taxonomy ) &&
        $current_screen->taxonomy === 'codo_calendar_category' &&
        $current_screen->post_type === 'codo_calendar'
    ) {
        $parent_file  = 'codobookings_dashboard';
        $submenu_file = 'edit-tags.php?taxonomy=codo_calendar_category&post_type=codo_calendar';
    }
});
