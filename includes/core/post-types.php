<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function codobookings_register_post_types() {
    // Calendars
    $labels = array(
        'name'                  => __( 'Calendars', 'codobookings' ),
        'singular_name'         => __( 'Calendar', 'codobookings' ),
        'menu_name'             => __( 'Calendars', 'codobookings' ),
        'name_admin_bar'        => __( 'Calendar', 'codobookings' ),
        'add_new'               => __( 'Add New', 'codobookings' ),
        'add_new_item'          => __( 'Add New Calendar', 'codobookings' ),
        'edit_item'             => __( 'Edit Calendar', 'codobookings' ),
        'new_item'              => __( 'New Calendar', 'codobookings' ),
        'view_item'             => __( 'View Calendar', 'codobookings' ),
        'view_items'            => __( 'View Calendars', 'codobookings' ),
        'search_items'          => __( 'Search Calendars', 'codobookings' ),
        'not_found'             => __( 'No calendars found.', 'codobookings' ),
        'not_found_in_trash'    => __( 'No calendars found in Trash.', 'codobookings' ),
        'all_items'             => __( 'Calendars', 'codobookings' ),
        'archives'              => __( 'Calendar Archives', 'codobookings' ),
        'attributes'            => __( 'Calendar Attributes', 'codobookings' ),
        'insert_into_item'      => __( 'Insert into calendar', 'codobookings' ),
        'uploaded_to_this_item' => __( 'Uploaded to this calendar', 'codobookings' ),
        'filter_items_list'     => __( 'Filter calendars list', 'codobookings' ),
        'items_list_navigation' => __( 'Calendars list navigation', 'codobookings' ),
        'items_list'            => __( 'Calendars list', 'codobookings' ),
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'codobookings_dashboard',
        'menu_name'         => __( 'Calendars', 'codobookings' ),
        'supports' => array( 'title', 'editor', 'thumbnail' ),
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'has_archive' => false,
    );
    $args = apply_filters( 'codobookings_calendar_post_type_args', $args );
    register_post_type( 'codo_calendar', $args );

    // ✅ Custom Taxonomy for Calendar Category
    $taxonomy_labels = array(
        'name'              => __( 'Calendar Categories', 'codobookings' ),
        'singular_name'     => __( 'Calendar Category', 'codobookings' ),
        'search_items'      => __( 'Search Calendar Categories', 'codobookings' ),
        'all_items'         => __( 'All Calendar Categories', 'codobookings' ),
        'edit_item'         => __( 'Edit Calendar Category', 'codobookings' ),
        'update_item'       => __( 'Update Calendar Category', 'codobookings' ),
        'add_new_item'      => __( 'Add New Calendar Category', 'codobookings' ),
        'new_item_name'     => __( 'New Calendar Category Name', 'codobookings' ),
        'menu_name'         => __( 'Calendar Categories', 'codobookings' ),
    );

    $taxonomy_args = array(
        'hierarchical'      => true,
        'labels'            => $taxonomy_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_menu' => true,
        'query_var'         => true,
        'rewrite'           => false, // set to false if this is admin-only
    );

    register_taxonomy( 'codo_calendar_category', array( 'codo_calendar' ), $taxonomy_args );

    // Bookings
    $labels2 = array(
        'name'                  => _x( 'Bookings', 'Post Type General Name', 'codobookings' ),
        'singular_name'         => _x( 'Booking', 'Post Type Singular Name', 'codobookings' ),
        'menu_name'             => __( 'Bookings', 'codobookings' ),
        'name_admin_bar'        => __( 'Booking', 'codobookings' ),
        'add_new'               => __( 'Add New', 'codobookings' ),
        'add_new_item'          => __( 'Add New Booking', 'codobookings' ),
        'edit_item'             => __( 'Edit Booking', 'codobookings' ),
        'new_item'              => __( 'New Booking', 'codobookings' ),
        'view_item'             => __( 'View Booking', 'codobookings' ),
        'view_items'            => __( 'View Bookings', 'codobookings' ),
        'search_items'          => __( 'Search Bookings', 'codobookings' ),
        'not_found'             => __( 'No bookings found.', 'codobookings' ),
        'not_found_in_trash'    => __( 'No bookings found in Trash.', 'codobookings' ),
        'all_items'             => __( 'Bookings', 'codobookings' ),
        'archives'              => __( 'Booking Archives', 'codobookings' ),
        'attributes'            => __( 'Booking Attributes', 'codobookings' ),
        'insert_into_item'      => __( 'Insert into booking', 'codobookings' ),
        'uploaded_to_this_item' => __( 'Uploaded to this booking', 'codobookings' ),
        'filter_items_list'     => __( 'Filter bookings list', 'codobookings' ),
        'items_list_navigation' => __( 'Bookings list navigation', 'codobookings' ),
        'items_list'            => __( 'Bookings list', 'codobookings' ),
        'parent_item_colon'     => __( 'Parent Booking:', 'codobookings' ),
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