<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add booking fields to checkout ONLY if the level has "Enable Bookings" checked.
 */
function codo_pmpro_checkout_fields() {
    // Get current level in checkout
    $level_id = 0;

    // PMPro stores selected level in $_REQUEST['level'] during checkout
    if ( isset( $_REQUEST['pmpro_level'] ) ) {
        $level_id = intval( $_REQUEST['pmpro_level'] );
    }
    
    // Check if bookings are enabled for this level
    if ( ! $level_id || ! codo_pmpro_level_allows_booking( $level_id ) ) {
        return; // Booking not enabled for this level
    }

    ?>
    <fieldset id="pmpro_booking_fields" class="pmpro_form_fieldset">
        <div class="pmpro_card">
            <div class="pmpro_card_content">
                <legend class="pmpro_form_legend">
                    <h2 class="pmpro_form_heading pmpro_font-large">
                        <?php esc_html_e( 'Booking Preference', 'codo-bookings' ); ?>
                    </h2>
                </legend>

                <?php
                /**
                 * Allow plugins or extensions to modify booking date/time fields.
                 * Developers can replace this HTML with a calendar or other UI.
                 */
                $booking_datetime_fields = '
                    <div class="pmpro_checkout-field pmpro_checkout-field-booking_date">
                        <label for="booking_date">' . esc_html__( 'Preferred Date', 'codo-bookings' ) . '</label>
                        <input type="date" id="booking_date" name="booking_date" />
                    </div>

                    <div class="pmpro_checkout-field pmpro_checkout-field-booking_time">
                        <label for="booking_time">' . esc_html__( 'Preferred Time', 'codo-bookings' ) . '</label>
                        <input type="time" id="booking_time" name="booking_time" />
                    </div>
                ';

                // Apply filter for replacement (calendar view, etc.)
                echo apply_filters( 'codo_booking_datetime_fields_html', $booking_datetime_fields );
                ?>

            </div><!-- .pmpro_card_content -->
        </div><!-- .pmpro_card -->
    </fieldset>
    <?php
}
add_action( 'pmpro_checkout_after_user_fields', 'codo_pmpro_checkout_fields' );


/**
 * Validate booking fields before PMPro checkout submit
 */
function codo_pmpro_validate_checkout_fields() {
    if ( empty( $_REQUEST['booking_date'] ) || empty( $_REQUEST['booking_time'] ) ) {
        return;
    }

    $date     = sanitize_text_field( $_REQUEST['booking_date'] );
    $time     = sanitize_text_field( $_REQUEST['booking_time'] );

    // Robust conflict check
    if ( function_exists( 'codo_is_time_slot_conflict' ) && codo_is_time_slot_conflict( $date, $time ) ) {
        pmpro_setMessage(
            esc_html__( 'The selected booking time overlaps with an existing booking. Please choose a different slot.', 'codo-bookings' ),
            'pmpro_error'
        );
        global $pmpro_error_fields;
        $pmpro_error_fields[] = 'booking_time';
        return;
    }

    // Enforce max bookings per level (if enabled)
    if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && is_user_logged_in() ) {
        $user_id  = get_current_user_id();
        $level    = pmpro_getMembershipLevelForUser( $user_id );

        if ( ! empty( $level ) && function_exists( 'codo_pmpro_level_max_bookings' ) ) {
            $max = codo_pmpro_level_max_bookings( $level->id );
            if ( $max > 0 && function_exists( 'codo_user_bookings_count_for_level' ) ) {
                $count = codo_user_bookings_count_for_level( $user_id, $level->id );
                if ( $count >= $max ) {
                    pmpro_setMessage(
                        esc_html__( 'You have reached the maximum number of bookings allowed for your membership level.', 'codo-bookings' ),
                        'pmpro_error'
                    );
                    global $pmpro_error_fields;
                    $pmpro_error_fields[] = 'booking_time';
                    return;
                }
            }
        }
    }
}
add_action( 'pmpro_checkout_before_submit', 'codo_pmpro_validate_checkout_fields' );


/**
 * Automatically create a booking after successful checkout
 *
 * @param int $user_id The ID of the user who checked out
 * @param object $order The PMPro order object
 */
function codo_pmpro_after_checkout_capture( $user_id, $order ) {
    if ( empty( $user_id ) || empty( $order ) || ! is_object( $order ) ) {
        return;
    }

    $order_id = $order->id;

    $date = isset( $_REQUEST['booking_date'] ) ? sanitize_text_field( $_REQUEST['booking_date'] ) : '';
    $time = isset( $_REQUEST['booking_time'] ) ? sanitize_text_field( $_REQUEST['booking_time'] ) : '';

    if ( empty( $date ) || empty( $time ) ) {
        return; // Booking not filled
    }

    // Double-check no overlap
    if ( function_exists( 'codo_is_time_slot_conflict' ) && codo_is_time_slot_conflict( $date, $time ) ) {
        return;
    }

    // Verify membership level allows bookings
    if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
        return;
    }
    $level = pmpro_getMembershipLevelForUser( $user_id );
    if ( empty( $level ) || ! function_exists( 'codo_pmpro_level_allows_booking' ) || ! codo_pmpro_level_allows_booking( $level->id ) ) {
        return;
    }

    $level_title = $level->id > 0 && function_exists('pmpro_getLevel') ? pmpro_getLevel( $level->id )->name : esc_html__( 'General Bookings', 'codo-bookings' );

    // Create booking post
    $post_id = wp_insert_post( array(
        'post_type'   => 'codo_booking',
        'post_title'  => $level_title,
        'post_status' => 'publish',
        'post_author' => $user_id,
    ) );

    if ( ! is_wp_error( $post_id ) && $post_id ) {
        update_post_meta( $post_id, '_codo_date', $date );
        update_post_meta( $post_id, '_codo_time', $time );
        update_post_meta( $post_id, '_codo_status', 'confirmed' );
        update_post_meta( $post_id, '_codo_pmpro_level_id', intval( $level->id ) );
        update_post_meta( $post_id, '_codo_order_id', $order_id ); // link booking to PMPro order

        // Optional email notifications
        if ( function_exists( 'codo_send_booking_notifications' ) ) {
            codo_send_booking_notifications( $post_id );
        }
    }
}
add_action( 'pmpro_after_checkout', 'codo_pmpro_after_checkout_capture', 10, 2 );

/**
 * Append bookings to PMPro account page content with PMPro styling.
 */
function codo_pmpro_append_bookings_to_account_content( $content ) {
    if ( is_admin() || ! is_user_logged_in() || ! is_singular() ) {
        return $content;
    }

    global $post;
    if ( empty( $post ) || empty( $post->post_content ) ) return $content;

    if ( false === stripos( $post->post_content, '[pmpro_account' ) && false === stripos( $post->post_content, '[pmpro_account]' ) ) {
        return $content;
    }

    $user_id = get_current_user_id();
    $bookings = get_posts([
        'post_type'      => 'codo_booking',
        'author'         => $user_id,
        'post_status'    => ['publish', 'pending'],
        'posts_per_page' => -1,
        'orderby'        => 'meta_value',
        'meta_key'       => '_codo_date',
        'order'          => 'DESC',
    ]);

    if ( empty( $bookings ) ) {
        $html = '<section class="pmpro_section"><h2 class="pmpro_section_title pmpro_font-x-large">' . esc_html__( 'My Bookings', 'codo-bookings' ) . '</h2>';
        $html .= '<div class="pmpro_card"><div class="pmpro_card_content">';
        $html .= '<p>' . esc_html__( 'You have no bookings yet.', 'codo-bookings' ) . '</p>';
        $html .= '</div></div></section>';
        return $content . $html;
    }

    // Group bookings by level
    $grouped = [];
    foreach ( $bookings as $b ) {
        $level_id = get_post_meta( $b->ID, '_codo_pmpro_level_id', true );
        $level_id = $level_id ? intval( $level_id ) : 0;
        $grouped[ $level_id ][] = $b;
    }

    $html = '<div class="pmpro"><section class="pmpro_section"><h2 class="pmpro_section_title pmpro_font-x-large">' . esc_html__( 'My Bookings', 'codo-bookings' ) . '</h2>';
    $html .= '<div class="pmpro_card"><div class="pmpro_card_content">';

    foreach ( $grouped as $level_id => $items ) {
        $level_title = $level_id > 0 && function_exists('pmpro_getLevel') ? pmpro_getLevel( $level_id )->name : esc_html__( 'General Bookings', 'codo-bookings' );

        $html .= '<table class="pmpro_table pmpro_table_orders" width="100%"><thead><tr>';
        $html .= '<th class="pmpro_table_order-level">' . esc_html__( 'Level', 'codo-bookings' ) . '</th>';
        $html .= '<th class="pmpro_table_order-date">' . esc_html__( 'Date', 'codo-bookings' ) . '</th>';
        $html .= '<th class="pmpro_table_order-total">' . esc_html__( 'Time', 'codo-bookings' ) . '</th>';
        $html .= '<th class="pmpro_table_order-status">' . esc_html__( 'Status', 'codo-bookings' ) . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ( $items as $b ) {
            $date   = get_post_meta( $b->ID, '_codo_date', true );
            $time   = get_post_meta( $b->ID, '_codo_time', true );
            $status = get_post_meta( $b->ID, '_codo_status', true );

            $html .= '<tr id="pmpro_table_booking-' . esc_attr( $b->ID ) . '">';
            $html .= '<td class="pmpro_table_order-level" data-title="Level">' . esc_html( $level_title ) . '</td>';
            $html .= '<th class="pmpro_table_order-date" data-title="Date">' . esc_html( $date ) . '</th>';
            $html .= '<td class="pmpro_table_order-amount" data-title="Time">' . esc_html( $time ) . '</td>';
            $html .= '<td class="pmpro_table_order-status" data-title="Status">';
            
            // Status tag
            $status_class = $status === 'pending' ? 'pmpro_tag-warning' : 'pmpro_tag-success';
            $html .= '<span class="pmpro_tag ' . esc_attr( $status_class ) . '">' . esc_html( ucfirst( $status ) ) . '</span>';
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table>';
    }

    $html .= '</div></div></section></div>';

    return $content . $html;
}
add_filter( 'the_content', 'codo_pmpro_append_bookings_to_account_content', 20 );

/**
 * Show booking details on PMPro invoice/order page for the specific order.
 */
function codo_pmpro_order_specific_booking_details( $order ) {
    if ( ! $order || empty( $order->id ) ) {
        return;
    }

    $order_id = $order->id;
    $user_id  = $order->user_id;

    // Fetch bookings linked to this specific order (we assume booking meta _codo_order_id stores order ID)
    $args = array(
        'post_type'      => 'codo_booking',
        'author'         => $user_id,
        'post_status'    => array( 'publish', 'pending' ),
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => '_codo_order_id',
                'value' => $order_id,
                'compare' => '=',
            ),
        ),
        'orderby'        => 'meta_value',
        'meta_key'       => '_codo_date',
        'order'          => 'ASC',
    );
    $bookings = get_posts( $args );

    if ( empty( $bookings ) ) {
        return; // No booking linked to this order, do nothing
    }

    // Output booking table
    echo '<h3 style="margin-top:20px;">' . esc_html__( 'Your Booking Details', 'codo-bookings' ) . '</h3>';
    echo '<table class="pmpro_table pmpro_table_orders" style="width:100%;">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Date', 'codo-bookings' ) . '</th>';
    echo '<th>' . esc_html__( 'Time', 'codo-bookings' ) . '</th>';
    echo '<th>' . esc_html__( 'Status', 'codo-bookings' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $bookings as $b ) {
        $date   = get_post_meta( $b->ID, '_codo_date', true );
        $time   = get_post_meta( $b->ID, '_codo_time', true );
        $status = get_post_meta( $b->ID, '_codo_status', true );

        echo '<tr>';
        echo '<td>' . esc_html( $date ) . '</td>';
        echo '<td>' . esc_html( $time ) . '</td>';
        echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}
add_action( 'pmpro_invoice_bullets_bottom', 'codo_pmpro_order_specific_booking_details', 10, 1 );
