<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Google Calendar event to obtain Google Meet link using stored refresh token.
 * This implementation uses Google Calendar REST API and expects a refresh token stored in options.
 * NOTE: For production scenarios, implement proper OAuth flow with token storage per-account and secure handling.
 */
function codobookings_get_google_access_token() {
    $refresh = get_option( 'codobookings_google_refresh_token' );
    $client_id = get_option( 'codobookings_google_client_id' );
    $client_secret = get_option( 'codobookings_google_client_secret' );
    if ( ! $refresh || ! $client_id || ! $client_secret ) return new WP_Error( 'no_credentials', 'Google OAuth credentials not configured' );

    // Exchange refresh token for access token
    $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', array( 'body' => array( 'client_id' => $client_id, 'client_secret' => $client_secret, 'refresh_token' => $refresh, 'grant_type' => 'refresh_token' ), 'timeout'=>20 ) );
    if ( is_wp_error( $resp ) ) return $resp;
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( empty( $body['access_token'] ) ) return new WP_Error( 'token_error', 'Failed to obtain access token' );
    return $body['access_token'];
}

function codobookings_google_create_event( $booking_id ) {
    do_action( 'codobookings_before_google_create_event', $booking_id );
    $booking = get_post( $booking_id );
    if ( ! $booking ) return new WP_Error( 'not_found', 'Booking not found' );

    $calendar_id = get_post_meta( $booking_id, '_codo_calendar_id', true );
    $start = get_post_meta( $booking_id, '_codo_start', true );
    $end = get_post_meta( $booking_id, '_codo_end', true );
    $email = get_post_meta( $booking_id, '_codo_attendee_email', true );

    $access = codobookings_get_google_access_token();
    if ( is_wp_error( $access ) ) return $access;

    // The Google Calendar event body with conferenceData enabled
    $event = array(
        'summary' => get_the_title( $booking_id ),
        'start' => array( 'dateTime' => codobookings_format_iso8601_for_event( $start, $calendar_id ), 'timeZone' => get_post_meta( $calendar_id, '_codo_timezone', true ) ),
        'end' => array( 'dateTime' => codobookings_format_iso8601_for_event( $end ?: $start, $calendar_id ), 'timeZone' => get_post_meta( $calendar_id, '_codo_timezone', true ) ),
        'attendees' => array( array( 'email' => $email ) ),
        'conferenceData' => array( 'createRequest' => array( 'requestId' => 'codo-' . $booking_id ) ),
    );

    $calendarPrimary = 'primary';
    $resp = wp_remote_post( "https://www.googleapis.com/calendar/v3/calendars/{$calendarPrimary}/events?conferenceDataVersion=1", array( 'headers' => array( 'Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $event ), 'timeout' => 20 ) );
    if ( is_wp_error( $resp ) ) return $resp;
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( empty( $body['hangoutLink'] ) && empty( $body['conferenceData'] ) ) return new WP_Error( 'no_meet', 'No meeting link returned' );

    $meet_link = $body['hangoutLink'] ?? ($body['conferenceData']['entryPoints'][0]['uri'] ?? '');
    if ( $meet_link ) update_post_meta( $booking_id, '_codo_meeting_link', esc_url_raw( $meet_link ) );

    do_action( 'codobookings_google_event_created', $booking_id, $body );
    return $meet_link;
}

function codobookings_format_iso8601_for_event( $datetime_string, $calendar_id ) {
    $tz = get_post_meta( $calendar_id, '_codo_timezone', true ) ?: get_option( 'codobookings_default_timezone', wp_timezone_string() );
    try { $dt = new DateTimeImmutable( $datetime_string, new DateTimeZone( $tz ) ); return $dt->format( DateTime::ATOM ); } catch ( Exception $e ) { return gmdate( DATE_ATOM ); }
}
