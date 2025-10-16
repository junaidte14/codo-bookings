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
 * Check time slot conflict considering duration (in minutes)
 *
 * Returns true if the requested slot overlaps any existing booking.
 */
function codo_is_time_slot_conflict( $date, $time, $duration = 0 ) {
    // Convert requested slot to start/end timestamps
    $req_start = codo_datetime_to_timestamp( $date, $time );
    if ( $req_start <= 0 ) {
        return false; // invalid date/time
    }
    $req_end = $req_start + ( intval( $duration ) * 60 );

    // Query all bookings on the same date (publish/pending)
    $q = new WP_Query( array(
        'post_type'      => 'codo_booking',
        'post_status'    => array( 'publish', 'pending' ),
        'meta_query'     => array(
            array(
                'key'   => '_codo_date',
                'value' => $date,
            ),
        ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );

    if ( ! $q->have_posts() ) {
        return false;
    }

    foreach ( $q->posts as $existing_id ) {
        $ex_time     = get_post_meta( $existing_id, '_codo_time', true );
        $ex_duration = intval( get_post_meta( $existing_id, '_codo_duration', true ) );
        if ( empty( $ex_time ) ) {
            continue;
        }
        $ex_start = codo_datetime_to_timestamp( $date, $ex_time );
        $ex_end   = $ex_start + ( $ex_duration * 60 );

        // Overlap condition:
        // If requested start < existing end AND requested end > existing start => overlap
        if ( $req_start < $ex_end && $req_end > $ex_start ) {
            return true;
        }
    }

    return false; // no conflicts
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
