<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * === BASIC EMAIL NOTIFICATIONS FOR CODOBOOKINGS ===
 * Sends emails to admin and users after bookings are created or updated.
 * Fully extensible: templates, subjects, and recipients can be overridden by extensions.
 */

/**
 * New Booking Notification
 */
add_action( 'codobookings_booking_created', 'codobookings_send_new_booking_emails', 10, 2 );
function codobookings_send_new_booking_emails( $booking_id, $data ) {
    // Allow future extension to modify or skip
    $send_admin = apply_filters(
        'codobookings_send_admin_email_enabled',
        get_option( 'codobookings_send_admin_email', 'yes' ) ?: 'yes',
        $booking_id,
        $data
    );

    $send_user = apply_filters(
        'codobookings_send_user_email_enabled',
        get_option( 'codobookings_send_user_email', 'yes' ) ?: 'yes',
        $booking_id,
        $data
    );

    $calendar_id    = $data['calendar_id'];
    $attendee_email = sanitize_email( $data['email'] );
    $admin_email    = apply_filters( 'codobookings_admin_email_recipient', get_option( 'admin_email' ), $booking_id, $data );

    $calendar_title = get_the_title( $calendar_id );
    $booking_title  = get_the_title( $booking_id );
    $start_time     = get_post_meta( $booking_id, '_codo_start', true );
    $end_time       = get_post_meta( $booking_id, '_codo_end', true );
    $status         = ucfirst( get_post_meta( $booking_id, '_codo_status', true ) );
    $recurrence     = ucfirst( get_post_meta( $booking_id, '_codo_recurrence', true ) );

    // === Email content ===
    $details_html = apply_filters( 'codobookings_email_details_html', "
        <h2 style='margin:0 0 10px;'>".esc_html( $booking_title )."</h2>
        <p><strong>".__('Calendar:', 'codobookings')."</strong> ".esc_html($calendar_title)."</p>
        <p><strong>".__('Start:', 'codobookings')."</strong> ".esc_html($start_time)."</p>
        <p><strong>".__('End:', 'codobookings')."</strong> ".esc_html($end_time)."</p>
        <p><strong>".__('Status:', 'codobookings')."</strong> ".esc_html($status)."</p>
        <p><strong>".__('Recurrence:', 'codobookings')."</strong> ".esc_html($recurrence)."</p>
        <p>".__('Thank you for using our booking service!', 'codobookings')."</p>
    ", $booking_id, $data );

    $headers = apply_filters( 'codobookings_email_headers', [ 'Content-Type: text/html; charset=UTF-8' ], $booking_id, $data );

    // === Admin Email ===
    if ( $send_admin === 'yes' ) {
        $subject = apply_filters( 'codobookings_admin_email_subject',
            sprintf( __( 'New booking received – %s', 'codobookings' ), $calendar_title ),
            $booking_id, $data
        );
        $message = apply_filters( 'codobookings_admin_email_message',
            "<h1>".__('New Booking Created', 'codobookings')."</h1>".$details_html,
            $booking_id, $data
        );

        wp_mail( $admin_email, $subject, $message, $headers );
    }

    // === User Email ===
    if ( $send_user === 'yes' && is_email( $attendee_email ) ) {
        $subject = apply_filters( 'codobookings_user_email_subject',
            sprintf( __( 'Your booking confirmation – %s', 'codobookings' ), $calendar_title ),
            $booking_id, $data
        );
        $message = apply_filters( 'codobookings_user_email_message',
            "<h1>".__('Your booking has been received!', 'codobookings')."</h1>".$details_html,
            $booking_id, $data
        );

        wp_mail( $attendee_email, $subject, $message, $headers );
    }

    do_action( 'codobookings_emails_sent', $booking_id, $data, $attendee_email, $admin_email );
}

/**
 * === STATUS CHANGE NOTIFICATIONS ===
 */
add_action( 'codobookings_booking_status_changed', 'codobookings_send_status_change_email', 10, 2 );
function codobookings_send_status_change_email( $booking_id, $status ) {
    // ✅ Skip if status is "pending"
    if ( strtolower( trim( $status ) ) === 'pending' ) {
        return;
    }
    $send_user  = apply_filters( 'codobookings_send_user_email_enabled',  get_option( 'codobookings_send_user_email', 'yes' ), $booking_id );
    $send_admin = apply_filters( 'codobookings_send_admin_email_enabled', get_option( 'codobookings_send_admin_email', 'yes' ), $booking_id );

    $attendee_email = get_post_meta( $booking_id, '_codo_attendee_email', true );
    $calendar_id    = get_post_meta( $booking_id, '_codo_calendar_id', true );
    $calendar_title = get_the_title( $calendar_id );
    $admin_email    = apply_filters( 'codobookings_admin_email_recipient', get_option( 'admin_email' ), $booking_id );

    $subject = apply_filters( 'codobookings_status_email_subject',
        sprintf( __( 'Booking #%d status updated to %s', 'codobookings' ), $booking_id, ucfirst($status) ),
        $booking_id, $status
    );

    $headers = apply_filters( 'codobookings_email_headers', [ 'Content-Type: text/html; charset=UTF-8' ], $booking_id );

    $message_html = apply_filters( 'codobookings_status_email_message', "
        <h1>".__('Booking Status Updated', 'codobookings')."</h1>
        <p><strong>".__('Calendar:', 'codobookings')."</strong> ".esc_html($calendar_title)."</p>
        <p><strong>".__('Booking ID:', 'codobookings')."</strong> #".intval($booking_id)."</p>
        <p><strong>".__('New Status:', 'codobookings')."</strong> ".esc_html( ucfirst($status) )."</p>
        <p>".__('Thank you for staying with us!', 'codobookings')."</p>
    ", $booking_id, $status );

    if ( $send_user === 'yes' && is_email( $attendee_email ) ) {
        wp_mail( $attendee_email, $subject, $message_html, $headers );
    }

    if ( $send_admin === 'yes' ) {
        wp_mail( $admin_email, '[Admin] ' . $subject, $message_html, $headers );
    }

    do_action( 'codobookings_status_email_sent', $booking_id, $status );
}
