<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register 'codo_booking' Custom Post Type
 */
function codo_register_booking_cpt() {

    $labels = array(
        'name'               => __( 'Bookings', 'codo-bookings' ),
        'singular_name'      => __( 'Booking', 'codo-bookings' ),
        'menu_name'          => __( 'Bookings', 'codo-bookings' ),
        'name_admin_bar'     => __( 'Booking', 'codo-bookings' ),
        'add_new'            => __( 'Add New', 'codo-bookings' ),
        'add_new_item'       => __( 'Add New Booking', 'codo-bookings' ),
        'edit_item'          => __( 'Edit Booking', 'codo-bookings' ),
        'new_item'           => __( 'New Booking', 'codo-bookings' ),
        'view_item'          => __( 'View Booking', 'codo-bookings' ),
        'all_items'          => __( 'All Bookings', 'codo-bookings' ),
        'search_items'       => __( 'Search Bookings', 'codo-bookings' ),
        'not_found'          => __( 'No bookings found.', 'codo-bookings' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false, // we'll add a menu via admin-menu.php
        'supports'           => array( 'title' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'rewrite'            => false,
        'menu_icon'          => 'dashicons-calendar-alt',
    );

    register_post_type( 'codo_booking', $args );
}
add_action( 'init', 'codo_register_booking_cpt', 20 );
