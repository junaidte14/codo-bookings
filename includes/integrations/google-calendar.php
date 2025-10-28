<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Google Calendar Integration
 */

/**
 * =============================================
 *  Google Calendar Settings Extension
 * =============================================
 */

// Register Google API settings
add_action( 'admin_init', 'codobookings_register_google_settings' );
function codobookings_register_google_settings() {
    register_setting( 'codobookings_options', 'codobookings_google_client_id', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting( 'codobookings_options', 'codobookings_google_client_secret', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting( 'codobookings_options', 'codobookings_google_refresh_token', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}

// Register the tab itself
add_filter( 'codobookings_settings_tabs', 'codobookings_add_google_calendar_tab' );
function codobookings_add_google_calendar_tab( $tabs ) {
    $tabs['google_calendar'] = [
        'label'    => __( 'Google Calendar', 'codobookings' ),
        'callback' => 'codobookings_render_google_calendar_tab',
    ];
    return $tabs;
}

// Render the tab
function codobookings_render_google_calendar_tab() {
    ?>
    <table class="form-table">
        <tr><th colspan="2"><h2><?php _e( 'Google API (OAuth)', 'codobookings' ); ?></h2></th></tr>
        <tr>
            <th><?php _e( 'Client ID', 'codobookings' ); ?></th>
            <td>
                <input type="text" name="codobookings_google_client_id"
                    value="<?php echo esc_attr( get_option( 'codobookings_google_client_id' ) ); ?>" style="width:100%">
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Client Secret', 'codobookings' ); ?></th>
            <td>
                <input type="text" name="codobookings_google_client_secret"
                    value="<?php echo esc_attr( get_option( 'codobookings_google_client_secret' ) ); ?>" style="width:100%">
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Refresh Token', 'codobookings' ); ?></th>
            <td>
                <input type="text" name="codobookings_google_refresh_token"
                    value="<?php echo esc_attr( get_option( 'codobookings_google_refresh_token' ) ); ?>" style="width:100%">
                <p class="description">
                    <?php
                        printf(
                            __( 'Need help? Follow <a href="%s" target="_blank">Google API Console OAuth instructions</a> to create credentials and get your refresh token.', 'codobookings' ),
                            esc_url( 'https://developers.google.com/calendar/api/quickstart/js' )
                        );
                    ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

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
