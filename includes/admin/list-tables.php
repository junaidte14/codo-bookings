<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// --- Calendar Columns ---
add_filter( 'manage_codo_calendar_posts_columns', 'codobookings_calendar_columns' );
function codobookings_calendar_columns( $cols ) {
    $cols = array(
        'cb'        => $cols['cb'],
        'title'     => __( 'Calendar', 'codobookings' ),
        'shortcode' => __( 'Shortcode', 'codobookings' ),
        'recurrence'     => __( 'Recurrence', 'codobookings' ),
        'date'      => $cols['date'],
    );
    return $cols;
}

add_action( 'manage_codo_calendar_posts_custom_column', 'codobookings_calendar_columns_data', 10, 2 );
function codobookings_calendar_columns_data( $column, $post_id ) {
    if ( $column === 'shortcode' ) {
        echo '<code>[codo_calendar id="' . esc_attr( $post_id ) . '"]</code>';
    }
    if ( $column === 'recurrence' ) {
        $recurrence = get_post_meta( $post_id, '_codo_recurrence', true );
        if ( $recurrence == 'weekly' ) {
            echo 'Weekly Bookings';
        } else {
            echo 'None (One-Time Bookings)';
        }
    }
}

// --- Bookings Columns ---
add_filter( 'manage_codo_booking_posts_columns', 'codobookings_booking_columns' );
function codobookings_booking_columns( $cols ) {
    $cols = array(
        'cb'        => $cols['cb'],
        'title'     => __( 'Booking', 'codobookings' ),
        'calendar'  => __( 'Calendar', 'codobookings' ),
        'datetime'     => __( 'Date/Time (UTC / Local)', 'codobookings' ),
        'status'    => __( 'Status', 'codobookings' ),
    );
    return $cols;
}

add_action( 'manage_codo_booking_posts_custom_column', 'codobookings_booking_columns_data', 10, 2 );
function codobookings_booking_columns_data( $column, $post_id ) {
    // Calendar column
    if ( $column === 'calendar' ) {
        echo get_the_title( get_post_meta( $post_id, '_codo_calendar_id', true ) );
        return;
    }

    // Status column
    if ( $column === 'status' ) {
        echo esc_html( get_post_meta( $post_id, '_codo_status', true ) );
        return;
    }

    // Combine Start & End into a single column called 'time' (adjust your columns filter accordingly)
    if ( $column === 'datetime' ) {
        $calendar_id = get_post_meta( $post_id, '_codo_calendar_id', true );
        if ( ! $calendar_id ) {
            echo '-';
            return;
        }

        $recurrence = get_post_meta( $post_id, '_codo_recurrence', true ); // 'none' or 'weekly'
        $rec_day    = get_post_meta( $post_id, '_codo_day', true );

        $start_utc = get_post_meta( $post_id, '_codo_start', true );
        $end_utc   = get_post_meta( $post_id, '_codo_end', true );

        $wp_tz = new DateTimeZone( wp_timezone_string() );

        // ---- One-time booking ----
        if ( $recurrence === 'none' ) {
            if ( !$start_utc ) {
                echo '-';
                return;
            }

            $start_dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start_utc, new DateTimeZone('UTC'));
            $end_dt   = $end_utc ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end_utc, new DateTimeZone('UTC')) : null;

            $start_local = $start_dt->setTimezone( $wp_tz );
            $end_local   = $end_dt ? $end_dt->setTimezone( $wp_tz ) : null;

            echo '<div>';
            // UTC
            $utc_date = $start_dt->format('Y-m-d');
            $utc_start = $start_dt->format('H:i');
            $utc_end   = $end_dt ? $end_dt->format('H:i') : '';
            echo '<strong>UTC:</strong> ' . esc_html("{$utc_date} {$utc_start}");
            if ( $utc_end ) echo ' - ' . esc_html($utc_end);
            echo '<br>';
            // Local
            $local_date = $start_local->format('Y-m-d');
            $local_start = $start_local->format('H:i');
            $local_end   = $end_local ? $end_local->format('H:i') : '';
            echo '<strong>Local:</strong> ' . esc_html("{$local_date} {$local_start}");
            if ( $local_end ) echo ' - ' . esc_html($local_end);
            echo '</div>';
        }

        // ---- Weekly booking ----
        else if ( $recurrence === 'weekly' ) {
            if ( !$start_utc ) {
                echo esc_html("Every {$rec_day}");
                return;
            }

            $start_dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start_utc, new DateTimeZone('UTC'));
            $end_dt   = $end_utc ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end_utc, new DateTimeZone('UTC')) : null;

            $start_local = $start_dt->setTimezone( $wp_tz );
            $end_local   = $end_dt ? $end_dt->setTimezone( $wp_tz ) : null;

            echo '<div>';
            echo '<strong>UTC:</strong> Every ' . esc_html( ucfirst($rec_day) ) . ' ' . esc_html($start_dt->format('H:i'));
            if ( $end_dt ) echo ' - ' . esc_html($end_dt->format('H:i'));
            echo '<br>';
            echo '<strong>Local:</strong> Every ' . esc_html( ucfirst($rec_day) ) . ' ' . esc_html($start_local->format('H:i'));
            if ( $end_local ) echo ' - ' . esc_html($end_local->format('H:i'));
            echo '</div>';
        }
    }
}
