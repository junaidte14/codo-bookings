<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Replace date/time inputs with PHP-generated calendar view via filter.
 */
function codo_bookings_calendar_filter( $html ) {
    if ( ! get_option( 'codo_bookings_enable_calendar', 0 ) ) {
        return $html;
    }

    // enqueue assets
    add_action( 'wp_enqueue_scripts', 'codo_bookings_enqueue_calendar_assets' );

    ob_start();
    ?>
    <div class="pmpro_checkout-field pmpro_checkout-field-booking_calendar">
        <p><?php esc_html_e( 'Select Your Booking Slot:', 'codo-bookings' ); ?></p>

        <div id="codo-booking-calendar">
            <?php 
                // Generate calendar data
                codo_bookings_generate_calendar();
            ?>
        </div>
        <div id="codo-booking-time-panel"></div>
        <!-- Appointment result -->
        <div id="codo-booking-selected"></div>
        <input type="hidden" name="booking_date" id="booking_date" value="" />
        <input type="hidden" name="booking_time" id="booking_time" value="" />
        <p class="description" style="font-size: 13px;"><?php esc_html_e( 'Click a date to see available time slots. Booked slots are disabled.', 'codo-bookings' ); ?></p>
    </div>

    <?php
    return ob_get_clean();
}
add_filter( 'codo_booking_datetime_fields_html', 'codo_bookings_calendar_filter' );

function codo_bookings_generate_calendar( $month = null, $year = null ) {
    $today = date('Y-m-d');
    $month = $month ?: date('m');
    $year  = $year ?: date('Y');

    // Weekday slot patterns saved as "Mon" => "09:00,10:00,11:00"
    $weekday_slots = get_option('codo_bookings_weekday_slots', []);

    // --- Define start and end of month ---
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date   = date('Y-m-t', strtotime($start_date));

    // --- Fetch all bookings ---
    $all_bookings = get_posts([
        'post_type'   => 'codo_booking',
        'numberposts' => -1,
        'post_status' => 'publish', // ensure we only get confirmed ones
        'meta_query'  => [
            'relation' => 'OR',
            [
                'key'     => '_codo_status',
                'compare' => 'NOT EXISTS', // if status not yet set
            ],
            [
                'key'     => '_codo_status',
                'value'   => 'cancelled',
                'compare' => '!=',
            ],
        ],
    ]);

    $bookings = [];
    foreach ($all_bookings as $b) {
        $booking_date = get_post_meta($b->ID, '_codo_date', true);

        // Only include if booking_date is within this month
        if ($booking_date >= $start_date && $booking_date <= $end_date) {
            $bookings[] = $b;
        }
    }

    // --- Build booked slot map ---
    $booked_slots = [];
    foreach ($bookings as $b) {
        $date = get_post_meta($b->ID, '_codo_date', true);
        $time = get_post_meta($b->ID, '_codo_time', true);

        if (!isset($booked_slots[$date])) {
            $booked_slots[$date] = [];
        }

        $booked_slots[$date][] = trim($time);
    }

    // --- Prepare month navigation ---
    $days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
    $prev_month = date('Y-m', strtotime("$year-$month-01 -1 month"));
    $next_month = date('Y-m', strtotime("$year-$month-01 +1 month"));

    // --- Header ---
    echo '<div class="codo-calendar-header">';
    echo '<button type="button" class="codo-nav-month" data-month="'.date('m', strtotime($prev_month)).'" data-year="'.date('Y', strtotime($prev_month)).'">&lt; '.__('Prev', 'codo-bookings').'</button>';
    echo '<span class="codo-month-label">'.esc_html( date_i18n('F Y', strtotime("$year-$month-01")) ).'</span>';
    echo '<button type="button" class="codo-nav-month" data-month="'.date('m', strtotime($next_month)).'" data-year="'.date('Y', strtotime($next_month)).'">'.__('Next', 'codo-bookings').' &gt;</button>';
    echo '</div>';

    // --- Calendar grid ---
    echo '<div class="codo-calendar-grid">';
    $start_weekday = date('N', strtotime("$year-$month-01"));
    for ( $i = 1; $i < $start_weekday; $i++ ) {
        echo '<div class="codo-empty"></div>';
    }

    $slots_data = [];

    // --- Loop through all days ---
    for ( $day = 1; $day <= $days_in_month; $day++ ) {
        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $weekday  = date('D', strtotime($date_str));

        // Slots available for that weekday (like Mon, Tue, etc.)
        $available = array_filter(array_map('trim', explode(',', $weekday_slots[$weekday] ?? '')));
        $booked    = $booked_slots[$date_str] ?? [];

        // Filter out booked slots
        $free_slots = array_values( array_diff( $available, $booked ) );

        // Count remaining available slots
        $remaining = count( $free_slots );
        $is_past   = ( $date_str < $today );

        // Disable if past or fully booked
        $disabled = ( $is_past || $remaining === 0 );

        // Label and tooltip
        $label = $remaining > 0 ? $day . ' [' . $remaining . ']' : $day;
        $title = $remaining > 0
            ? sprintf(
                _n( '%d slot available', '%d slots available', $remaining, 'codo-bookings' ),
                $remaining
            )
            : __( 'Fully booked', 'codo-bookings' );

        // Day button
        echo '<button type="button" class="codo-day'.( $disabled ? ' disabled' : '' ).'" '.
             ( $disabled ? 'disabled="disabled"' : '' ).' '.
             'data-date="'.esc_attr($date_str).'" title="'.esc_attr($title).'">'.
             esc_html($label).
             '</button>';

        $slots_data[ $date_str ] = $free_slots;
    }

    echo '</div>'; // .codo-calendar-grid

    // JSON data for JS
    echo '<script>var codoBookingSlots = '.wp_json_encode($slots_data).';</script>';
}


/**
 * AJAX month render
 */
add_action('wp_ajax_nopriv_render_month', 'codo_bookings_render_month');
add_action('wp_ajax_render_month', 'codo_bookings_render_month');

function codo_bookings_render_month() {
    if ( empty($_GET['month']) || empty($_GET['year']) ) wp_send_json_error();

    ob_start();
    codo_bookings_generate_calendar(intval($_GET['month']), intval($_GET['year']));
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

function codo_bookings_enqueue_calendar_assets() {
    wp_enqueue_style(
        'codo-bookings-calendar-style',
        CODO_BOOKINGS_URL . 'assets/css/calendar-view.css',
        [],
        CODO_BOOKINGS_VERSION
    );

    wp_enqueue_script(
        'codo-bookings-calendar-script',
        CODO_BOOKINGS_URL . 'assets/js/calendar-view.js',
        ['jquery'],
        CODO_BOOKINGS_VERSION,
        true
    );

    wp_localize_script('codo-bookings-calendar-script', 'codoBookingsData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);
}
add_action('wp_enqueue_scripts', 'codo_bookings_enqueue_calendar_assets');