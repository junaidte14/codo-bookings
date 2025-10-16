<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add custom columns for codo_booking CPT
 */
function codo_booking_columns( $columns ) {
    $new = array();
    $new['cb']        = $columns['cb'];
    $new['title']     = __( 'Booking', 'codo-bookings' );
    $new['booked_by'] = __( 'Booked By', 'codo-bookings' );
    $new['date_time'] = __( 'Date / Time', 'codo-bookings' );
    $new['status']    = __( 'Status', 'codo-bookings' );
    $new['date']      = $columns['date'];

    return $new;
}
add_filter( 'manage_codo_booking_posts_columns', 'codo_booking_columns' );

/**
 * Fill custom columns
 */
function codo_booking_custom_column( $column, $post_id ) {
    switch ( $column ) {
        case 'booked_by':
            $user_id = get_post_field( 'post_author', $post_id );
            $user = get_userdata( $user_id );
            if ( $user ) {
                echo esc_html( $user->display_name ) . ' (ID: ' . intval( $user_id ) . ')';
            } else {
                echo esc_html__( 'Guest / N/A', 'codo-bookings' );
            }
            break;

        case 'date_time':
            $date = get_post_meta( $post_id, '_codo_date', true );
            $time = get_post_meta( $post_id, '_codo_time', true );
            echo esc_html( $date . ' ' . $time );
            break;

        case 'status':
            $status = get_post_meta( $post_id, '_codo_status', true );
            echo esc_html( ucfirst( $status ) );
            break;
    }
}
add_action( 'manage_codo_booking_posts_custom_column', 'codo_booking_custom_column', 10, 2 );

/**
 * Make columns sortable (optional)
 */
function codo_booking_sortable_columns( $columns ) {
    $columns['date_time'] = 'date_time';
    $columns['status']    = 'status';
    return $columns;
}
add_filter( 'manage_edit-codo_booking_sortable_columns', 'codo_booking_sortable_columns' );
