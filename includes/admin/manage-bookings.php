<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add meta box for editing booking times
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'codo_booking_times',
        __( 'Booking Times', 'codobookings' ),
        'codobookings_booking_times_meta_box',
        'codo_booking',
        'normal',
        'high'
    );
});

/**
 * Render meta box
 */
function codobookings_booking_times_meta_box( $post ) {
    // Retrieve saved data
    $start_utc = get_post_meta( $post->ID, '_codo_start', true );
    $end_utc   = get_post_meta( $post->ID, '_codo_end', true );
    $email     = get_post_meta( $post->ID, '_codo_attendee_email', true );
    $status    = get_post_meta( $post->ID, '_codo_status', true );
    $recurrence = get_post_meta( $post->ID, '_codo_recurrence', true ); // 'none' or 'weekly'
    $rec_days   = get_post_meta( $post->ID, '_codo_day', true );
    //var_dump($rec_days);

    // Convert UTC → Admin timezone
    $tz = new DateTimeZone( get_option('timezone_string') ?: 'UTC' );
    $start_dt = $start_utc ? new DateTimeImmutable( $start_utc, new DateTimeZone('UTC') ) : null;
    $end_dt   = $end_utc   ? new DateTimeImmutable( $end_utc,   new DateTimeZone('UTC') ) : null;

    $start_local = $start_dt ? $start_dt->setTimezone($tz)->format('Y-m-d H:i') : '';
    $end_local   = $end_dt   ? $end_dt->setTimezone($tz)->format('Y-m-d H:i')   : '';

    ?>
    <p>
        <label for="codo_start"><?php _e('Start Time', 'codobookings'); ?></label><br>
        <input type="datetime-local" id="codo_start" name="codo_start" value="<?php echo esc_attr($start_local); ?>" style="width:100%;">
    </p>
    <p>
        <label for="codo_end"><?php _e('End Time', 'codobookings'); ?></label><br>
        <input type="datetime-local" id="codo_end" name="codo_end" value="<?php echo esc_attr($end_local); ?>" style="width:100%;">
    </p>
    <p>
        <label for="codo_email"><?php _e('Email', 'codobookings'); ?></label><br>
        <input type="email" id="codo_email" name="codo_email" value="<?php echo esc_attr($email); ?>" style="width:100%;">
    </p>
    <p>
        <label for="codo_status"><?php _e('Status', 'codobookings'); ?></label><br>
        <select id="codo_status" name="codo_status" style="width:100%;">
            <?php
            $statuses = ['pending','confirmed','cancelled','completed'];
            foreach($statuses as $s) {
                printf('<option value="%1$s"%2$s>%1$s</option>', esc_attr($s), selected($status,$s,false));
            }
            ?>
        </select>
    </p>
    <p>
        <em><?php _e('Times are shown in your WordPress admin timezone.', 'codobookings'); ?></em>
    </p>
    <?php
}

// Save the additional fields
add_action( 'save_post_codo_booking', function( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

    $tz = new DateTimeZone( get_option('timezone_string') ?: 'UTC' );

    // Start
    if ( isset($_POST['codo_start']) ) {
        $start_local = sanitize_text_field( $_POST['codo_start'] );
        try {
            $start_utc = ( new DateTimeImmutable( $start_local, $tz ) )->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            update_post_meta( $post_id, '_codo_start', $start_utc );
        } catch (Exception $e) {}
    }

    // End
    if ( isset($_POST['codo_end']) && $_POST['codo_end'] !== '' ) {
        $end_local = sanitize_text_field( $_POST['codo_end'] );
        try {
            $end_utc = ( new DateTimeImmutable( $end_local, $tz ) )->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            update_post_meta( $post_id, '_codo_end', $end_utc );
        } catch (Exception $e) {}
    }

    // Email
    if ( isset($_POST['codo_email']) ) {
        update_post_meta( $post_id, '_codo_attendee_email', sanitize_email($_POST['codo_email']) );
    }

    // Status
    if ( isset($_POST['codo_status']) ) {
        update_post_meta( $post_id, '_codo_status', sanitize_text_field($_POST['codo_status']) );
    }
});
