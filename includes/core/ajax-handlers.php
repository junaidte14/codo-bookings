<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_codo_get_calendar', 'codo_get_calendar');
add_action('wp_ajax_nopriv_codo_get_calendar', 'codo_get_calendar');

function codo_get_calendar() {
    // ✅ Verify nonce first
    check_ajax_referer('codobookings_nonce', 'nonce');

    $calendar_id = absint($_POST['calendar_id'] ?? 0);
    if (!$calendar_id) {
        wp_send_json_error(['message' => 'Invalid calendar ID']);
    }

    $calendar_post = get_post($calendar_id);
    if (!$calendar_post || $calendar_post->post_type !== 'codo_calendar') {
        wp_send_json_error(['message' => 'Calendar not found']);
    }

    $recurrence = get_post_meta($calendar_id, '_codo_recurrence', true) ?: 'none';
    $slots_meta = get_post_meta($calendar_id, '_codo_weekly_slots', true);
    if (!is_array($slots_meta)) $slots_meta = [];

    $slots = [];
    $bookings_list = [];
    $site_tz = wp_timezone();

    // --- Fetch all bookings ---
    $booking_query = new WP_Query([
        'post_type'      => 'codo_booking',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_codo_calendar_id',
                'value'   => $calendar_id,
                'compare' => '=',
                'type'    => 'NUMERIC'
            ],
            [
                'key'     => '_codo_status',
                'value'   => 'cancelled',
                'compare' => '!='
            ],
        ],
        'fields' => 'ids',
    ]);

    foreach ($booking_query->posts as $booking_id) {
        $start_raw = get_post_meta($booking_id, '_codo_start', true);
        $end_raw   = get_post_meta($booking_id, '_codo_end', true);
        $day_meta  = strtolower(trim(get_post_meta($booking_id, '_codo_day', true)));
        $type   = get_post_meta($booking_id, '_codo_recurrence', true);

        if (!$start_raw || !$end_raw || !$day_meta) continue;

        $is_datetime = preg_match('/\d{4}-\d{2}-\d{2}/', $start_raw); // detect one-time

        try {
            $start_utc = new DateTime($start_raw, new DateTimeZone('UTC'));
            $end_utc   = new DateTime($end_raw, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            continue;
        }

        $bookings_list[] = [
            'type'    => $type,
            'weekday' => $day_meta,
            'start'   => $start_utc->format('Y-m-d H:i:s'),
            'end'     => $end_utc->format('Y-m-d H:i:s'),
        ];
    }

    $bookings = [];
    foreach ($booking_query->posts as $booking_id) {
        $start = get_post_meta($booking_id, '_codo_start', true);
        $end   = get_post_meta($booking_id, '_codo_end', true);
        $day = get_post_meta($booking_id, '_codo_day', true); // weekly day, optional

        if ($start && $end) {
            $bookings[] = [
                'start'   => new DateTime($start),
                'end'     => new DateTime($end),
                'day' => $day,
            ];
        }
    }

    foreach ($slots_meta as $day_name => $day_slots) {
        if (!is_array($day_slots)) continue;
        $day_lower = strtolower($day_name);

        foreach ($day_slots as $slot) {
            if (empty($slot['start']) || empty($slot['end'])) continue;

            $slot_available = true;

            foreach ($bookings as $booking) {
                // --- Weekly booking ---
                if ($recurrence == 'weekly') {
                    $booking_day_lower = strtolower($booking['day']);
                    if ($booking_day_lower !== $day_lower) continue;

                    // Remove only if slot matches booking time exactly
                    $booking_start_time = $booking['start']->format('H:i');
                    $booking_end_time   = $booking['end']->format('H:i');

                    if ($slot['start'] === $booking_start_time && $slot['end'] === $booking_end_time) {
                        $slot_available = false;
                        break;
                    }

                }
            }

            if (!$slot_available) continue;
            
            $slots[] = [
                'day'    => $day_lower,
                'start'  => sanitize_text_field($slot['start']),
                'end'    => sanitize_text_field($slot['end']),
            ];
        }
    }

    wp_send_json_success([
        'id'         => $calendar_id,
        'title'      => $calendar_post->post_title,
        'recurrence' => $recurrence,
        'slots'      => $slots,         // available slots
        'bookings'   => $bookings_list, // Concrete booked instances
    ]);
}

/**
 * AJAX: Create Booking
 */
add_action( 'wp_ajax_codobookings_create_booking', 'codobookings_ajax_create_booking' );
add_action( 'wp_ajax_nopriv_codobookings_create_booking', 'codobookings_ajax_create_booking' );

function codobookings_ajax_create_booking() {
    check_ajax_referer( 'codobookings_nonce', 'nonce' );

    // Sanitize and validate incoming POST data
    $calendar_id = isset( $_POST['calendar_id'] ) ? absint( $_POST['calendar_id'] ) : 0;
    $start       = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
    $end         = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
    $email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $day         = isset( $_POST['day'] ) ? sanitize_text_field( wp_unslash( $_POST['day'] ) ) : '';

    if ( ! $calendar_id || ! $start || ! $email ) {
        wp_send_json_error( 'Missing required fields (calendar, start time, or email).' );
    }

    // Validate calendar
    $calendar_post = get_post( $calendar_id );
    if ( ! $calendar_post || $calendar_post->post_type !== 'codo_calendar' || $calendar_post->post_status !== 'publish' ) {
        wp_send_json_error( 'Invalid calendar ID.' );
    }

    // Get recurrence from calendar meta
    $recurrence = get_post_meta( $calendar_id, '_codo_recurrence', true );

    // Validate datetime format
    try {
        $start_dt = new DateTimeImmutable( $start, new DateTimeZone('UTC') );
        $end_dt   = $end ? new DateTimeImmutable( $end, new DateTimeZone('UTC') ) : null;
    } catch ( Exception $e ) {
        wp_send_json_error( 'Invalid date/time format. Use UTC format: YYYY-MM-DD HH:MM:SS' );
    }

    // Build booking data array
    $booking_data = [
        'title'       => sprintf( 'Booking - %s', $email ),
        'calendar_id' => $calendar_id,
        'start'       => $start_dt->format('Y-m-d H:i:s'),
        'end'         => $end_dt ? $end_dt->format('Y-m-d H:i:s') : '',
        'recurrence'  => sanitize_text_field( $recurrence ),
        'day'         => sanitize_text_field( $day ),
        'status'      => 'pending',
        'email'       => $email,
        'meta'        => [],
    ];

    // Create booking (existing functionality)
    $booking_id = codobookings_create_booking( $booking_data );

    if ( is_wp_error( $booking_id ) ) {
        wp_send_json_error( $booking_id->get_error_message() );
    }

    // Trigger hook with sanitized booking data only
    do_action( 'codobookings_after_ajax_create_booking', $booking_id, $booking_data );

    wp_send_json_success( [
        'booking_id' => $booking_id,
        'message'    => 'Booking confirmed successfully!',
    ] );
}
