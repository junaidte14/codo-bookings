<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check whether a user (by ID) with a given membership level is eligible to book.
 * If $required_level is provided, check specifically for that level.
 */
function codo_user_can_book( $user_id = null, $required_level = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    if ( ! $user_id ) {
        return false;
    }

    // If PMPro is present, require an active membership or specific level
    if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
        if ( $required_level ) {
            return pmpro_hasMembershipLevel( $required_level, $user_id );
        }
        // Any active level
        $level = pmpro_getMembershipLevelForUser( $user_id );
        return ( ! empty( $level ) );
    }

    // If PMPro absent — be conservative: disallow booking
    return false;
}

/**
 * Convert date + time to Unix timestamp (server timezone)
 */
function codo_datetime_to_timestamp( $date, $time ) {
    // $date in YYYY-MM-DD, $time in HH:MM
    $datetime = trim( $date . ' ' . $time );
    $ts = strtotime( $datetime );
    return ( $ts === false ) ? 0 : intval( $ts );
}

/**
 * Check if a time slot is already booked for a given date.
 *
 * Returns true if the requested slot is already taken (publish or pending),
 * ignoring cancelled bookings.
 *
 * @param string $date Date in Y-m-d format.
 * @param string $time Time in H:i format.
 * @return bool True if conflict exists, false otherwise.
 */
function codo_is_time_slot_conflict( $date, $time ) {
    if ( empty( $date ) || empty( $time ) ) {
        return false;
    }

    // Query all bookings on the same date
    $bookings = get_posts([
        'post_type'      => 'codo_booking',
        'post_status'    => ['publish', 'pending'],
        'numberposts'    => -1,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => '_codo_date',
                'value' => $date,
            ],
            [
                'key'   => '_codo_status',
                'value' => 'cancelled',
                'compare' => '!=',
            ],
        ],
        'fields'         => 'ids',
    ]);

    if ( empty( $bookings ) ) {
        return false;
    }

    foreach ( $bookings as $booking_id ) {
        $booked_time = get_post_meta( $booking_id, '_codo_time', true );

        // If exact same time already exists → conflict
        if ( trim( $booked_time ) === trim( $time ) ) {
            return true;
        }
    }

    return false; // no conflict found
}


/**
 * Count how many active bookings a user has that are linked to a given PMPro level (or any level if $level_id is null).
 * Only counts bookings with status 'publish' or 'pending'.
 */
function codo_user_bookings_count_for_level( $user_id, $level_id = null ) {
    $meta_query = array();
    if ( $level_id ) {
        $meta_query[] = array(
            'key'   => '_codo_pmpro_level_id',
            'value' => intval( $level_id ),
        );
    }

    $query_args = array(
        'post_type'      => 'codo_booking',
        'post_status'    => array( 'publish', 'pending' ),
        'author'         => intval( $user_id ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    $q = new WP_Query( $query_args );
    return intval( $q->found_posts );
}

/**
 * Simple helper to format date/time for display
 */
function codo_format_booking_datetime( $date, $time ) {
    return esc_html( $date . ' ' . $time );
}
