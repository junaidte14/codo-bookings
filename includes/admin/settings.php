<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function codobookings_dashboard() {
    echo '<div class="wrap"><h1>CodoBookings</h1><p>Welcome to CodoBookings. Manage calendars, bookings and settings from here.</p></div>';
}

add_action( 'admin_init', 'codobookings_register_settings' );
function codobookings_register_settings() {
    register_setting( 'codobookings_options', 'codobookings_default_timezone', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => wp_timezone_string() ) );
    register_setting( 'codobookings_options', 'codobookings_default_meeting_app', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'google_calendar' ) );
    register_setting( 'codobookings_options', 'codobookings_notify_before_minutes', array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 30 ) );

    // Google OAuth settings
    register_setting( 'codobookings_options', 'codobookings_google_client_id', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'codobookings_options', 'codobookings_google_client_secret', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'codobookings_options', 'codobookings_google_refresh_token', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
}

function codobookings_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Add message when settings updated
    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
        add_settings_error(
            'codobookings_messages',
            'codobookings_message',
            __( 'Settings saved successfully.', 'codobookings' ),
            'updated'
        );
    }

    // Show all settings messages
    settings_errors( 'codobookings_messages' );
    ?>
    <div class="wrap">
        <h1><?php _e( 'CodoBookings Settings', 'codobookings' ); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'codobookings_options' );
                do_settings_sections( 'codobookings_options' );
            ?>
            <table class="form-table">
                <tr>
                    <th><?php _e( 'Default timezone', 'codobookings' ); ?></th>
                    <td>
                        <select name="codobookings_default_timezone">
                            <?php foreach ( timezone_identifiers_list() as $tz ) : ?>
                                <option value="<?php echo esc_attr( $tz ); ?>" <?php selected( get_option( 'codobookings_default_timezone', wp_timezone_string() ), $tz ); ?>>
                                    <?php echo esc_html( $tz ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Default meeting app', 'codobookings' ); ?></th>
                    <td>
                        <select name="codobookings_default_meeting_app">
                            <option value="google_calendar" <?php selected( get_option( 'codobookings_default_meeting_app' ), 'google_calendar' ); ?>>
                                Google Meet (via Calendar)
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Notify before (minutes)', 'codobookings' ); ?></th>
                    <td>
                        <input type="number" name="codobookings_notify_before_minutes"
                            value="<?php echo esc_attr( get_option( 'codobookings_notify_before_minutes', 30 ) ); ?>">
                    </td>
                </tr>

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
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
