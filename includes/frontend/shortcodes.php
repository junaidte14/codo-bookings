<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', function() {
    add_shortcode('codo_calendar', 'codobookings_calendar_shortcode');
});

function codobookings_calendar_shortcode( $atts ) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'codo_calendar');

    $calendar_id = intval($atts['id']);
    if (!$calendar_id) {
        return '<div class="codo-calendar-error">Invalid calendar ID.</div>';
    }

    // Enqueue frontend assets
    wp_enqueue_style( 'codobookings-frontend', CODOBOOKINGS_PLUGIN_URL . 'assets/css/calendar-frontend.css', array(), CODOBOOKINGS_VERSION );
    // JS dependency chain
    wp_enqueue_script( 'codobookings-utils', CODOBOOKINGS_PLUGIN_URL . 'assets/js/calendar/utils.js', array(), CODOBOOKINGS_VERSION, true );
    wp_enqueue_script( 'codobookings-api', CODOBOOKINGS_PLUGIN_URL . 'assets/js/calendar/api.js', array('codobookings-utils'), CODOBOOKINGS_VERSION, true );
    wp_enqueue_script( 'codobookings-sidebar', CODOBOOKINGS_PLUGIN_URL . 'assets/js/calendar/sidebar.js', array('codobookings-utils'), CODOBOOKINGS_VERSION, true );
    wp_enqueue_script( 'codobookings-calendar-weekly', CODOBOOKINGS_PLUGIN_URL . 'assets/js/calendar/calendar-weekly.js', array('codobookings-api','codobookings-sidebar'), CODOBOOKINGS_VERSION, true );
    wp_enqueue_script( 'codobookings-calendar-onetime', CODOBOOKINGS_PLUGIN_URL . 'assets/js/calendar/calendar-onetime.js', array('codobookings-api','codobookings-sidebar'), CODOBOOKINGS_VERSION, true );
    wp_enqueue_script( 'codobookings-main', CODOBOOKINGS_PLUGIN_URL . 'assets/js/calendar/main.js', array(
        'codobookings-utils',
        'codobookings-api',
        'codobookings-sidebar',
        'codobookings-calendar-weekly',
        'codobookings-calendar-onetime'
    ), CODOBOOKINGS_VERSION, true );

    wp_localize_script( 'codobookings-main', 'CODOBookingsData', array(
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'codobookings_nonce' ),
        'calendarId' => $calendar_id,
        'userEmail'  => is_user_logged_in() ? wp_get_current_user()->user_email : '',
        'i18n'       => array(
            'loading' => __( 'Loading booking calendar...', 'codobookings' ),
            'failed'  => __( 'Failed to load calendar. Please refresh the page.', 'codobookings' ),
            'book'    => __( 'Book this slot', 'codobookings' ),
            'noSlots' => __( 'No slots available for selected period.', 'codobookings' ),
        )
    ));

    ob_start();
    $unique_id = 'codo-calendar-' . $calendar_id . '-' . uniqid();
    ?>
    <div class="codo-calendar-container">
        <div id="<?php echo esc_attr($unique_id); ?>" 
             class="codo-calendar-wrapper" 
             data-calendar-id="<?php echo esc_attr($calendar_id); ?>">
            <div class="codo-calendar-loading"><?php echo esc_html__('Loading booking calendar...', 'codobookings'); ?></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
