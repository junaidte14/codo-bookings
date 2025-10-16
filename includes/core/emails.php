<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Send booking notifications to admin and user
 *
 * @param int $booking_id The ID of the booking (CPT)
 */
function codo_send_booking_notifications( $booking_id ) {
    if ( empty( $booking_id ) ) return;

    $post = get_post( $booking_id );
    if ( ! $post || $post->post_type !== 'codo_booking' ) return;

    // Get booking meta
    $date     = get_post_meta( $booking_id, '_codo_date', true );
    $time     = get_post_meta( $booking_id, '_codo_time', true );
    $user_id  = $post->post_author;
    $level_id = get_post_meta( $post->ID, '_codo_pmpro_level_id', true );
    $level_id = $level_id ? intval( $level_id ) : 0;
    $level_title = $level_id > 0 && function_exists('pmpro_getLevel') ? pmpro_getLevel( $level_id )->name : esc_html__( 'General Bookings', 'codo-bookings' );

    $user = get_userdata( $user_id );
    if ( ! $user ) return;

    $user_email  = $user->user_email;
    $user_name   = $user->display_name ?: $user->user_login;
    $admin_email = get_option( 'admin_email' );

    // Email subject and message
    $subject_admin = sprintf( __( 'New Booking: %s', 'codo-bookings' ), $level_title );
    $subject_user  = sprintf( __( 'Your Booking Confirmation: %s', 'codo-bookings' ), $level_title );

    $message_admin = sprintf(
        "A new booking has been created.\n\nBooking Details:\nService: %s\nDate: %s\nTime: %s\nUser: %s (%s)\n\nBooking ID: %d",
        $level_title,
        $date,
        $time,
        $user_name,
        $user_email,
        $booking_id
    );

    $message_user = sprintf(
        "Hello %s,\n\nYour booking has been successfully recorded.\n\nBooking Details:\nService: %s\nDate: %s\nTime: %s\n\n\nThank you for booking with us!\n\nBooking ID: %d",
        $user_name,
        $level_title,
        $date,
        $time,
        $booking_id
    );

    // Send emails
    wp_mail( $admin_email, $subject_admin, $message_admin );
    wp_mail( $user_email, $subject_user, $message_user );
}


/**
 * Optional: Customize emails from WordPress for better formatting
 * Example: send as HTML
 */
function codo_wp_mail_html_headers( $headers = '' ) {
    if ( empty( $headers ) ) {
        $headers = array();
    } elseif ( is_string( $headers ) ) {
        $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
    }

    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    return $headers;
}

/**
 * Example usage (if sending HTML emails):
 * wp_mail( $to, $subject, $message, codo_wp_mail_html_headers() );
 */
