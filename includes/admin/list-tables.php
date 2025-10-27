<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'manage_codo_calendar_posts_columns', 'codobookings_calendar_columns' );
function codobookings_calendar_columns( $cols ) {
    $cols = array(
        'cb' => $cols['cb'],
        'title' => __( 'Calendar', 'codobookings' ),
        'shortcode' => __( 'Shortcode', 'codobookings' ),
        'timezone' => __( 'Timezone', 'codobookings' ),
        'slots' => __( 'Slots', 'codobookings' ),
        'date' => $cols['date'],
    );
    return $cols;
}
add_action( 'manage_codo_calendar_posts_custom_column', 'codobookings_calendar_columns_data', 10, 2 );
function codobookings_calendar_columns_data( $column, $post_id ) {
    if ( $column === 'shortcode' ) {
        echo '<code>[codo_calendar id=' . esc_attr( $post_id ) . ']</code>';
    }
    if ( $column === 'timezone' ) {
        echo esc_html( get_post_meta( $post_id, '_codo_timezone', true ) ?: get_option( 'codobookings_default_timezone', wp_timezone_string() ) );
    }
    if ( $column === 'slots' ) {
        $slots = get_post_meta( $post_id, '_codo_slots', true );
        if ( is_array( $slots ) ) echo count( $slots ) . ' ' . __( 'rules', 'codobookings' );
        else echo '-';
    }
}

// Filters
add_action( 'restrict_manage_posts', 'codobookings_calendar_filters' );
function codobookings_calendar_filters( $post_type ) {
    if ( $post_type !== 'codo_calendar' ) return;
    $selected = isset( $_GET['codo_tz_filter'] ) ? sanitize_text_field( $_GET['codo_tz_filter'] ) : '';
    echo '<select name="codo_tz_filter"><option value="">'.__( 'All timezones', 'codobookings' ).'</option>';
    foreach ( timezone_identifiers_list() as $tz ) printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $tz ), selected( $selected, $tz, false ) );
    echo '</select>';
}
add_filter( 'pre_get_posts', 'codobookings_calendar_filter_query' );
function codobookings_calendar_filter_query( $query ) {
    if ( is_admin() && $query->is_main_query() && function_exists('get_current_screen') && get_current_screen() && get_current_screen()->post_type === 'codo_calendar' ) {
        if ( ! empty( $_GET['codo_tz_filter'] ) ) {
            $tz = sanitize_text_field( wp_unslash( $_GET['codo_tz_filter'] ) );
            $meta_query = array( array( 'key' => '_codo_timezone', 'value' => $tz ) );
            $query->set( 'meta_query', $meta_query );
        }
    }
}

// Bookings list extra columns
add_filter( 'manage_codo_booking_posts_columns', 'codobookings_booking_columns' );
function codobookings_booking_columns( $cols ) {
    $cols = array(
        'cb' => $cols['cb'],
        'title' => __( 'Booking', 'codobookings' ),
        'calendar' => __( 'Calendar', 'codobookings' ),
        'start' => __( 'Start', 'codobookings' ),
        'end' => __( 'End', 'codobookings' ),
        'status' => __( 'Status', 'codobookings' ),
    );
    return $cols;
}
add_action( 'manage_codo_booking_posts_custom_column', 'codobookings_booking_columns_data', 10, 2 );
function codobookings_booking_columns_data( $column, $post_id ) {
    if ( $column === 'calendar' ) echo get_the_title( get_post_meta( $post_id, '_codo_calendar_id', true ) );
    if ( $column === 'start' ) echo esc_html( get_post_meta( $post_id, '_codo_start', true ) );
    if ( $column === 'end' ) echo esc_html( get_post_meta( $post_id, '_codo_end', true ) );
    if ( $column === 'status' ) echo esc_html( get_post_meta( $post_id, '_codo_status', true ) );
}
