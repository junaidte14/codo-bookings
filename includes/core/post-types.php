<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function codobookings_register_post_types() {
    $cap = apply_filters( 'codobookings_capability', 'manage_options' );

    // Calendars
    $labels = array(
        'name' => __( 'Calendars', 'codobookings' ),
        'singular_name' => __( 'Calendar', 'codobookings' ),
        'menu_name' => __( 'Calendars', 'codobookings' ),
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'codobookings_dashboard',
        'supports' => array( 'title', 'editor' ),
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'has_archive' => false,
    );
    $args = apply_filters( 'codobookings_calendar_post_type_args', $args );
    register_post_type( 'codo_calendar', $args );


    // Bookings
    $labels2 = array(
        'name' => __( 'Bookings', 'codobookings' ),
        'singular_name' => __( 'Booking', 'codobookings' ),
    );
    $args2 = array(
        'labels' => $labels2,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'codobookings_dashboard',
        'supports' => array( 'title' ),
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'has_archive' => false,
    );
    $args2 = apply_filters( 'codobookings_booking_post_type_args', $args2 );
    register_post_type( 'codo_booking', $args2 );
}
add_action( 'init', 'codobookings_register_post_types' );