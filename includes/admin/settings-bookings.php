<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register CodoBookings settings section and fields.
 */
function codo_bookings_register_settings() {
    // Register settings.
    register_setting( 'codo_bookings_settings_group', 'codo_bookings_enable_calendar' );
    register_setting( 'codo_bookings_settings_group', 'codo_bookings_available_days' );
    register_setting( 'codo_bookings_settings_group', 'codo_bookings_available_time_slots' );
}
add_action( 'admin_menu', 'codo_bookings_register_settings' );

function codo_bookings_settings_page() {
    // Save on submit
    if ( isset( $_POST['codo_bookings_save'] ) && check_admin_referer( 'codo_bookings_settings_save' ) ) {
        // enable calendar
        update_option( 'codo_bookings_enable_calendar', isset( $_POST['codo_bookings_enable_calendar'] ) ? 1 : 0 );

        // weekday slots: expect array like ['Mon' => '09:00,10:00', ...]
        $weekdays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        $weekday_slots = [];
        foreach ( $weekdays as $d ) {
            $key = 'slots_' . $d;
            $val = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
            // ensure CSV of times (we'll validate lightly)
            $parts = array_filter( array_map( 'trim', explode( ',', $val ) ) );
            $valid_times = [];
            foreach ( $parts as $p ) {
                // simple HH:MM validation
                if ( preg_match( '/^\d{1,2}:\d{2}$/', $p ) ) {
                    $valid_times[] = $p;
                }
            }
            $weekday_slots[ $d ] = implode( ',', $valid_times );
        }
        update_option( 'codo_bookings_weekday_slots', $weekday_slots );

        echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'codo-bookings' ) . '</p></div>';
    }

    $enable_calendar = get_option( 'codo_bookings_enable_calendar', 0 );
    $weekday_slots = get_option( 'codo_bookings_weekday_slots', [] );

    // default empty strings
    $defaults = [
        'Mon' => '09:00,10:00,11:00,14:00,15:00',
        'Tue' => '09:00,10:00,11:00,14:00,15:00',
        'Wed' => '09:00,10:00,11:00,14:00,15:00',
        'Thu' => '09:00,10:00,11:00,14:00,15:00',
        'Fri' => '09:00,10:00,11:00,14:00,15:00',
        'Sat' => '',
        'Sun' => '',
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CodoBookings Settings', 'codo-bookings' ); ?></h1>

        <form method="post">
            <?php wp_nonce_field( 'codo_bookings_settings_save' ); ?>

            <h2><?php esc_html_e( 'Display & Calendar', 'codo-bookings' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enable Calendar View', 'codo-bookings' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="codo_bookings_enable_calendar" value="1" <?php checked( 1, $enable_calendar ); ?>> <?php esc_html_e( 'Use calendar UI for date/time selection', 'codo-bookings' ); ?></label>
                    </td>
                </tr>

            </table>

            <h2><?php esc_html_e( 'Availability (per weekday)', 'codo-bookings' ); ?></h2>
            <p><?php esc_html_e( 'Enter comma-separated start-times for each weekday. Example: 09:00,10:00,11:30', 'codo-bookings' ); ?></p>

            <table class="form-table">
                <?php
                $days_order = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];
                foreach ( $days_order as $short => $full ) :
                    $value = isset( $weekday_slots[ $short ] ) ? $weekday_slots[ $short ] : $defaults[ $short ];
                ?>
                    <tr>
                        <th><?php echo esc_html( $full ); ?></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr( 'slots_' . $short ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Comma-separated times (24-hour format). Leave empty for unavailable.', 'codo-bookings' ); ?></p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <p>
                <button type="submit" name="codo_bookings_save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'codo-bookings' ); ?></button>
            </p>
        </form>
    </div>
    <?php
}