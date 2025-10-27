<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function codobookings_create_booking( $data ) {
    // Allow pre-validation hook
    do_action( 'codobookings_before_create_booking', $data );

    $defaults = array( 'title' => '', 'calendar_id' => 0, 'start' => '', 'end' => '', 'status' => 'pending', 'email' => '', 'meta' => array() );
    $data = wp_parse_args( $data, $defaults );

    if ( empty( $data['calendar_id'] ) || empty( $data['start'] ) || empty( $data['email'] ) ) {
        return new WP_Error( 'missing', 'Missing required fields' );
    }

    $postarr = array(
        'post_type' => 'codo_booking',
        'post_title' => sanitize_text_field( $data['title'] ) ?: sprintf( 'Booking: %s', sanitize_email( $data['email'] ) ),
        'post_status' => 'publish',
    );
    $id = wp_insert_post( $postarr );
    if ( is_wp_error( $id ) ) return $id;

    update_post_meta( $id, '_codo_calendar_id', absint( $data['calendar_id'] ) );
    update_post_meta( $id, '_codo_start', sanitize_text_field( $data['start'] ) );
    update_post_meta( $id, '_codo_end', sanitize_text_field( $data['end'] ) );
    update_post_meta( $id, '_codo_status', sanitize_text_field( $data['status'] ) );
    update_post_meta( $id, '_codo_attendee_email', sanitize_email( $data['email'] ) );
    update_post_meta( $id, '_codo_meta', $data['meta'] );

    do_action( 'codobookings_booking_created', $id, $data );

    // After create hook for integrations (e.g., create meeting if auto-enabled)
    do_action( 'codobookings_after_create_booking', $id, $data );

    return $id;
}

// Manage booking status transitions
add_action( 'save_post', 'codobookings_handle_booking_status_change', 20, 2 );
function codobookings_handle_booking_status_change( $post_id, $post ) {
    if ( $post->post_type !== 'codo_booking' ) return;
    $status = get_post_meta( $post_id, '_codo_status', true );
    do_action( 'codobookings_booking_status_changed', $post_id, $status );
}

// Export bookings CSV (admin action)
add_action( 'admin_post_codobookings_export_csv', 'codobookings_export_csv' );
function codobookings_export_csv() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
    $args = array( 'post_type' => 'codo_booking', 'posts_per_page' => -1 );
    $q = new WP_Query( $args );
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="codobookings_bookings.csv"' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'ID','Calendar','Start','End','Status','Email' ) );
    while ( $q->have_posts() ) { $q->the_post();
        $id = get_the_ID();
        $row = array( $id, get_the_title( get_post_meta( $id, '_codo_calendar_id', true ) ), get_post_meta( $id, '_codo_start', true ), get_post_meta( $id, '_codo_end', true ), get_post_meta( $id, '_codo_status', true ), get_post_meta( $id, '_codo_attendee_email', true ) );
        fputcsv( $out, $row );
    }
    wp_reset_postdata();
    exit;
}
