<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add custom booking settings fields to PMPro Membership Level edit page
 */
function codo_pmpro_level_settings_fields( $level ) {
    // PMPro passes a level object; extract ID
    $level_id = is_object( $level ) ? intval( $level->id ) : intval( $level );
    $allow_booking = get_option( 'codo_pmpro_level_' . $level_id . '_allow_booking', 0 );

    ?>
    <h3><?php esc_html_e( 'CodoBookings Settings', 'codo-bookings' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e( 'Enable Bookings for this Level', 'codo-bookings' ); ?></th>
            <td>
                <input type="checkbox" name="codo_allow_booking" value="1" <?php checked( $allow_booking, 1 ); ?> />
                <p class="description"><?php esc_html_e( 'Allow users of this membership level to create bookings.', 'codo-bookings' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'pmpro_membership_level_after_general_information', 'codo_pmpro_level_settings_fields' );


/**
 * Save CodoBookings settings for PMPro membership level
 */
function codo_save_pmpro_level_settings( $level_id ) {
    if ( isset( $_REQUEST['codo_allow_booking'] ) ) {
        update_option( 'codo_pmpro_level_' . $level_id . '_allow_booking', intval( $_REQUEST['codo_allow_booking'] ) );
    } else {
        update_option( 'codo_pmpro_level_' . $level_id . '_allow_booking', 0 );
    }
}
add_action( 'pmpro_save_membership_level', 'codo_save_pmpro_level_settings' );


/**
 * Helper function: Check if a level allows bookings
 */
function codo_pmpro_level_allows_booking( $level_id ) {
    $option_name = 'codo_pmpro_level_' . $level_id . '_allow_booking';
    $value = get_option( $option_name, null );

    // If option doesn't exist, return false
    if ( $value === null ) {
        return false;
    }

    return ( intval( $value ) === 1 );
}


