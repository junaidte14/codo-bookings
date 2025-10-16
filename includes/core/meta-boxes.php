<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add Meta Boxes for codo_booking
 */
function codo_add_booking_meta_boxes() {
    add_meta_box(
        'codo_booking_details',
        __( 'Booking Details', 'codo-bookings' ),
        'codo_booking_details_callback',
        'codo_booking',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'codo_add_booking_meta_boxes' );

/**
 * Meta Box callback
 */
function codo_booking_details_callback( $post ) {
    wp_nonce_field( 'codo_save_booking_meta', 'codo_booking_meta_nonce' );

    $date     = get_post_meta( $post->ID, '_codo_date', true );
    $time     = get_post_meta( $post->ID, '_codo_time', true );
    $status   = get_post_meta( $post->ID, '_codo_status', true );
    $notes    = get_post_meta( $post->ID, '_codo_notes', true );
    $order_id    = get_post_meta( $post->ID, '_codo_order_id', true );
    $user_id  = $post->post_author;

    ?>
    <table class="form-table">
        <tr>
            <th><?php _e( 'Order ID', 'codo-bookings' ); ?></th>
            <td>
                <?php
                if ( $order_id ) {
                    $order_url = admin_url( 'admin.php?page=pmpro-orders&order=' . intval( $order_id ) );
                    echo '<a href="' . esc_url( $order_url ) . '" target="_blank">#' . intval( $order_id ) . '</a>';
                } else {
                    echo esc_html__( 'Not set', 'codo-bookings' );
                }
                ?>
            </td>
        </tr>
        <tr>
            <th><label for="codo_date"><?php _e( 'Date', 'codo-bookings' ); ?></label></th>
            <td><input type="date" id="codo_date" name="codo_date" value="<?php echo esc_attr( $date ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="codo_time"><?php _e( 'Time Slot', 'codo-bookings' ); ?></label></th>
            <td><input type="time" id="codo_time" name="codo_time" value="<?php echo esc_attr( $time ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="codo_status"><?php _e( 'Status', 'codo-bookings' ); ?></label></th>
            <td>
                <select id="codo_status" name="codo_status">
                    <?php
                    $statuses = array( 'pending', 'confirmed', 'completed', 'cancelled' );
                    foreach ( $statuses as $st ) {
                        printf( '<option value="%s" %s>%s</option>', esc_attr( $st ), selected( $status, $st, false ), ucfirst( $st ) );
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="codo_notes"><?php _e( 'Notes', 'codo-bookings' ); ?></label></th>
            <td><textarea id="codo_notes" name="codo_notes" rows="4" cols="50"><?php echo esc_textarea( $notes ); ?></textarea></td>
        </tr>
        <tr>
            <th><?php _e( 'Booked By', 'codo-bookings' ); ?></th>
            <td>
                <?php
                if ( $user_id ) {
                    $user = get_userdata( $user_id );
                    echo $user ? esc_html( $user->display_name . ' (ID: ' . $user_id . ')' ) : esc_html( 'User ID: ' . $user_id );
                } else {
                    echo esc_html__( 'Guest / Not set', 'codo-bookings' );
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save meta box data
 */
function codo_save_booking_meta( $post_id ) {
    if ( ! isset( $_POST['codo_booking_meta_nonce'] ) || ! wp_verify_nonce( $_POST['codo_booking_meta_nonce'], 'codo_save_booking_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = array( 'date', 'time', 'status', 'notes' );
    foreach ( $fields as $field ) {
        $key = '_codo_' . $field;
        $val = isset( $_POST['codo_' . $field] ) ? sanitize_text_field( $_POST['codo_' . $field] ) : '';
        update_post_meta( $post_id, $key, $val );
    }
}
add_action( 'save_post_codo_booking', 'codo_save_booking_meta' );
