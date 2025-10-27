<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AJAX: Fetch Calendar Data
 */
add_action('wp_ajax_codo_get_calendar', 'codo_get_calendar');
add_action('wp_ajax_nopriv_codo_get_calendar', 'codo_get_calendar');

function codo_get_calendar() {
    $id = absint($_POST['calendar_id'] ?? 0);
    if ( ! $id ) wp_send_json_error(['message' => 'Invalid calendar ID']);

    $post = get_post($id);
    if ( ! $post || $post->post_type !== 'codo_calendar' )
        wp_send_json_error(['message' => 'Calendar not found']);

    $recurrence = get_post_meta($id, '_codo_recurrence', true);
    if ( ! in_array($recurrence, ['none','weekly'], true) )
        $recurrence = 'none';

    $slots_meta = get_post_meta($id, '_codo_weekly_slots', true);
    if ( ! is_array($slots_meta) ) $slots_meta = [];

    $slots = [];

    foreach ($slots_meta as $day_name => $day_slots) {
        if (!is_array($day_slots)) continue;
        $day_name_lower = strtolower($day_name);

        foreach ($day_slots as $slot) {
            if (empty($slot['start']) || empty($slot['end'])) continue;

            $normalized = [
                'day'        => $day_name_lower,
                'start'      => sanitize_text_field($slot['start']),
                'end'        => sanitize_text_field($slot['end']),
                'recurrence' => $recurrence,
            ];

            // Only for one-time slots, include 'date' if stored
            if ($recurrence === 'none' && !empty($slot['date'])) {
                $normalized['date'] = sanitize_text_field($slot['date']);
            }

            $slots[] = $normalized;
        }
    }

    wp_send_json_success([
        'id'         => $id,
        'title'      => $post->post_title,
        'recurrence' => $recurrence,
        'slots'      => $slots,
    ]);
}

/**
 * AJAX: Create Booking
 */
add_action( 'wp_ajax_codobookings_create_booking', 'codobookings_ajax_create_booking' );
add_action( 'wp_ajax_nopriv_codobookings_create_booking', 'codobookings_ajax_create_booking' );

function codobookings_ajax_create_booking() {
    check_ajax_referer( 'codobookings_nonce', 'nonce' );

    $params = wp_unslash( $_POST );
    $calendar_id = absint( $params['calendar_id'] ?? 0 );
    $start       = sanitize_text_field( $params['start'] ?? '' );
    $end         = sanitize_text_field( $params['end'] ?? '' );
    $email       = sanitize_email( $params['email'] ?? '' );

    if ( ! $calendar_id || ! $start || ! $email ) {
        wp_send_json_error( 'Missing required fields' );
    }

    $calendar_post = get_post( $calendar_id );
    if ( ! $calendar_post || $calendar_post->post_type !== 'codo_calendar' ) {
        wp_send_json_error( 'Invalid calendar' );
    }

    $capacity = get_post_meta( $calendar_id, '_codo_default_capacity', true );
    $capacity = $capacity ? absint( $capacity ) : 1;

    $tz = get_post_meta( $calendar_id, '_codo_timezone', true );
    if ( ! $tz ) $tz = get_option( 'codobookings_default_timezone', wp_timezone_string() );

    try {
        $start_dt = new DateTimeImmutable( $start, new DateTimeZone( $tz ) );
        $start_utc = $start_dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

        $end_utc = '';
        if ( $end ) {
            $end_dt = new DateTimeImmutable( $end, new DateTimeZone( $tz ) );
            $end_utc = $end_dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
        }
    } catch ( Exception $e ) {
        wp_send_json_error( 'Invalid date/time format' );
    }

    global $wpdb;
    $existing = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_codo_start_utc' AND meta_value = %s",
            $start_utc
        )
    );
    if ( count( $existing ) >= $capacity ) {
        wp_send_json_error( 'Slot full' );
    }

    $check = apply_filters( 'codobookings_before_booking_create', true, $params );
    if ( is_wp_error( $check ) ) wp_send_json_error( $check->get_error_message() );
    if ( ! $check ) wp_send_json_error( 'Booking validation failed' );

    $booking_data = [
        'title'       => sprintf( 'Booking - %s', $email ),
        'calendar_id' => $calendar_id,
        'start'       => $start_utc,
        'end'         => $end_utc,
        'status'      => 'pending',
        'email'       => $email,
        'meta'        => [],
    ];
    $booking_id = codobookings_create_booking( $booking_data );

    if ( is_wp_error( $booking_id ) ) {
        wp_send_json_error( $booking_id->get_error_message() );
    }

    do_action( 'codobookings_after_ajax_create_booking', $booking_id, $params );

    wp_send_json_success( [ 'booking_id' => $booking_id ] );
}
