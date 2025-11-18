<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add Main Calendar Settings Meta Box
 */
add_action( 'add_meta_boxes', 'codobookings_add_calendar_meta' );
function codobookings_add_calendar_meta() {
    add_meta_box(
        'codo_calendar_settings',
        __( 'Calendar Settings', 'codobookings' ),
        'codobookings_calendar_settings_cb',
        'codo_calendar',
        'normal',
        'high'
    );
}

/**
 * Render Main Calendar Settings Meta Box
 */
function codobookings_calendar_settings_cb( $post ) {
    wp_nonce_field( 'codobookings_save_calendar', 'codobookings_calendar_nonce' );

    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
    $slots = get_post_meta( $post->ID, '_codo_weekly_slots', true ) ?: array_fill_keys($days, array());
    $recurrence = get_post_meta( $post->ID, '_codo_recurrence', true ) ?: 'none';

    // Recurrence types with filter
    $recurrence_types = apply_filters( 'codobookings_recurrence_types', array(
        'none' => array(
            'label'       => __( 'One-time Booking', 'codobookings' ),
            'description' => __( 'A single event that occurs only once.', 'codobookings' ),
        ),
        'weekly' => array(
            'label'       => __( 'Weekly', 'codobookings' ),
            'description' => __( 'Repeats every week on the same day.', 'codobookings' ),
        ),
    ) );

    // Output the meta box HTML
    ?>
    <h4><?php esc_html_e( 'Weekly Availability', 'codobookings' ); ?></h4>
    <p>
        <button type="button" class="button" id="fill_standard_hours"><?php esc_html_e( 'Fill Standard 9–5 (Mon–Fri)', 'codobookings' ); ?></button>
        <button type="button" class="button" id="copy_monday"><?php esc_html_e( 'Copy Monday → All Days', 'codobookings' ); ?></button>
        <button type="button" class="button" id="export-json"><?php esc_html_e( 'Export JSON', 'codobookings' ); ?></button>
        <button type="button" class="button" id="import-json"><?php esc_html_e( 'Import JSON', 'codobookings' ); ?></button>
        <input type="file" id="import-file" accept="application/json" style="display:none;">
    </p>
    
    <div id="weekly-slots">
        <?php foreach ( $days as $day ) : ?>
            <div class="codo-day-section" data-day="<?php echo esc_attr( $day ); ?>">
                <h4 style="margin-bottom:5px;"><?php echo esc_html( ucfirst( $day ) ); ?></h4>
                <div class="codo-slots-wrap">
                    <?php if ( ! empty( $slots[$day] ) ) :
                        foreach ( $slots[$day] as $i => $slot ) : ?>
                            <div class="codo-slot">
                                <label><?php esc_html_e( 'Start', 'codobookings' ); ?></label>
                                <input type="time" name="codo_weekly_slots[<?php echo esc_attr( $day ); ?>][<?php echo esc_attr( $i ); ?>][start]" value="<?php echo esc_attr( $slot['start'] ?? '' ); ?>" />
                                <label><?php esc_html_e( 'End', 'codobookings' ); ?></label>
                                <input type="time" name="codo_weekly_slots[<?php echo esc_attr( $day ); ?>][<?php echo esc_attr( $i ); ?>][end]" value="<?php echo esc_attr( $slot['end'] ?? '' ); ?>" />
                                <button type="button" class="button remove-slot" aria-label="Remove Slot">×</button>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
                <button type="button" class="button add-slot"><?php esc_html_e( 'Add Slot', 'codobookings' ); ?></button>
            </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <h4><?php esc_html_e( 'Calendar Type', 'codobookings' ); ?></h4>
    <div class="codo-recurrence-options">
        <?php foreach ( $recurrence_types as $key => $data ) : 
            $is_active = ( $recurrence === $key ) ? 'active' : '';
        ?>
            <div class="codo-recurrence-box <?php echo esc_attr( $is_active ); ?>" data-value="<?php echo esc_attr( $key ); ?>">
                <strong><?php echo esc_html( $data['label'] ); ?></strong>
                <p><?php echo esc_html( $data['description'] ); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="codo_recurrence" id="codo_recurrence" value="<?php echo esc_attr( $recurrence ); ?>" />

    <?php do_action( 'codobookings_calendar_settings_after', $post ); ?>
    <?php
}

/**
 * Enqueue Admin Scripts & Styles for Calendar Meta
 */
add_action( 'admin_enqueue_scripts', 'codobookings_enqueue_admin_scripts' );
function codobookings_enqueue_admin_scripts( $hook ) {
    global $post;

    if ( empty($post) || $post->post_type !== 'codo_calendar' ) return;

    wp_enqueue_style( 'codobookings-admin-css', CODOBOOKINGS_PLUGIN_URL . 'assets/css/admin-calendar.css', array(), CODOBOOKINGS_VERSION );
    wp_enqueue_script( 'codobookings-admin-js', CODOBOOKINGS_PLUGIN_URL . 'assets/js/admin-calendar.js', array('jquery'), CODOBOOKINGS_VERSION, true );

    // Pass PHP variables to JS
    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
    wp_localize_script( 'codobookings-admin-js', 'CodoBookingsData', array(
        'days' => $days,
        'i18n' => array(
            'noMondaySlots' => __( 'No slots to copy from Monday.', 'codobookings' ),
            'standardSlotsAdded' => __( 'Standard 9–5 slots added for Monday to Friday.', 'codobookings' ),
            'invalidJson' => __( 'Invalid JSON', 'codobookings' ),
        ),
    ));
}

/**
 * Save Calendar Meta
 */
add_action( 'save_post', 'codobookings_save_calendar_meta', 10, 2 );
function codobookings_save_calendar_meta( $post_id, $post ) {
    if ( $post->post_type !== 'codo_calendar' ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['codobookings_calendar_nonce'] ) ) return;
    $nonce = sanitize_text_field( wp_unslash( $_POST['codobookings_calendar_nonce'] ) );
    if ( ! wp_verify_nonce( $nonce, 'codobookings_save_calendar' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    //$slots = isset( $_POST['codo_weekly_slots'] ) ? wp_unslash( $_POST['codo_weekly_slots'] ) : array();
    // Get raw input safely
    $slots_raw = isset( $_POST['codo_weekly_slots'] ) ? wp_unslash( $_POST['codo_weekly_slots'] ) : array();
    $slots_sanitized = array();

    // Loop through each day
    foreach ( (array) $slots_raw as $day => $day_slots ) {
        $day = sanitize_key( $day ); // sanitize day key
        if ( ! is_array( $day_slots ) ) continue;

        $slots_sanitized[ $day ] = array();

        foreach ( $day_slots as $slot ) {
            $start = isset( $slot['start'] ) ? sanitize_text_field( $slot['start'] ) : '';
            $end   = isset( $slot['end'] ) ? sanitize_text_field( $slot['end'] ) : '';

            // Optional: validate time format HH:MM
            if ( preg_match( '/^\d{2}:\d{2}$/', $start ) && preg_match( '/^\d{2}:\d{2}$/', $end ) ) {
                $slots_sanitized[ $day ][] = array(
                    'start' => $start,
                    'end'   => $end,
                );
            }
        }
    }
    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');

    foreach($days as $day){
        if(isset($slots[$day])){
            foreach($slots[$day] as &$slot){
                $slot['start'] = sanitize_text_field($slot['start'] ?? '');
                $slot['end'] = sanitize_text_field($slot['end'] ?? '');
            }
        } else {
            $slots[$day] = array();
        }
    }

    update_post_meta( $post_id, '_codo_weekly_slots', $slots );
    update_post_meta( $post_id, '_codo_recurrence', sanitize_text_field( $_POST['codo_recurrence'] ?? 'none' ) );

    if ( isset( $_POST['codo_confirmation_message'] ) ) {
        update_post_meta( $post_id, '_codo_confirmation_message', sanitize_textarea_field( $_POST['codo_confirmation_message'] ) );
    }

    do_action( 'codobookings_calendar_saved', $post_id );
}

/**
 * Confirmation Message Field
 */
add_action( 'codobookings_calendar_settings_after', 'codobookings_add_confirmation_message_field' );
function codobookings_add_confirmation_message_field( $post ) {
    $message = get_post_meta( $post->ID, '_codo_confirmation_message', true );
    if ( empty( $message ) ) {
        $message = __( 'Your booking has been confirmed successfully! Our team will soon contact you with further details. Thank you for choosing us.', 'codobookings' );
    }
    ?>
    <hr>
    <h4><?php esc_html_e( 'Confirmation Message', 'codobookings' ); ?></h4>
    <p>
        <textarea name="codo_confirmation_message" rows="3" style="width:100%;"><?php echo esc_textarea( $message ); ?></textarea>
        <small><?php esc_html_e( 'This message will be shown to the user after they confirm their booking.', 'codobookings' ); ?></small>
    </p>
    <?php
}

/**
 * Sidebar Settings Meta Box
 */
add_action( 'add_meta_boxes', 'codobookings_add_sidebar_settings_meta' );
function codobookings_add_sidebar_settings_meta() {
    add_meta_box(
        'codo_calendar_sidebar_settings',
        __( 'Calendar Settings', 'codobookings' ),
        'codobookings_sidebar_settings_cb',
        'codo_calendar',
        'side',
        'default'
    );
}

function codobookings_sidebar_settings_cb( $post ) {
    wp_nonce_field( 'codobookings_save_sidebar_settings', 'codobookings_sidebar_nonce' );

    $settings = wp_parse_args(
        get_post_meta( $post->ID, '_codo_sidebar_settings', true ),
        array(
            'show_title'  => 'yes',
            'allow_guest' => 'no',
        )
    );

    $extra_fields = apply_filters( 'codobookings_sidebar_settings_fields', array() );
    ?>
    <p>
        <label>
            <input type="checkbox" name="codo_sidebar_settings[show_title]" value="yes" <?php checked( $settings['show_title'], 'yes' ); ?> />
            <?php esc_html_e( 'Show Title', 'codobookings' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Toggle to display the calendar title on the frontend.', 'codobookings' ); ?></p>
    </p>

    <p>
        <label>
            <input type="checkbox" name="codo_sidebar_settings[allow_guest]" value="yes" <?php checked( $settings['allow_guest'], 'yes' ); ?> />
            <?php esc_html_e( 'Allow Guest Bookings', 'codobookings' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Allow users who are not logged in to make bookings with only providing their email address.', 'codobookings' ); ?></p>
    </p>

    <?php
    if( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
        foreach( $extra_fields as $field ) {
            echo '<p>' . esc_html( $field ) . '</p>';
        }
    }
}

/**
 * Save Sidebar Settings
 */
add_action( 'save_post', 'codobookings_save_sidebar_settings', 20, 2 );
function codobookings_save_sidebar_settings( $post_id, $post ) {
    // Only for codo_calendar post type
    if ( $post->post_type !== 'codo_calendar' ) return;
    // Prevent auto-saves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    $nonce = $_POST['codobookings_sidebar_nonce'] ?? '';
    $nonce = sanitize_text_field( wp_unslash( $nonce ) ); // unslash then sanitize
    if ( ! wp_verify_nonce( $nonce, 'codobookings_save_sidebar_settings' ) ) {
        return;
    }
    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    // Sanitize and validate incoming settings
    $raw_settings = $_POST['codo_sidebar_settings'] ?? array();
    $sanitized = array(
        'show_title'  => ( isset( $raw_settings['show_title'] ) && $raw_settings['show_title'] === 'yes' ) ? 'yes' : 'no',
        'allow_guest' => ( isset( $raw_settings['allow_guest'] ) && $raw_settings['allow_guest'] === 'yes' ) ? 'yes' : 'no',
    );
    // Apply optional filter before saving
    $sanitized = apply_filters( 'codobookings_sidebar_settings_sanitize', $sanitized, $post_id );
    // Save sanitized settings
    update_post_meta( $post_id, '_codo_sidebar_settings', $sanitized );
}