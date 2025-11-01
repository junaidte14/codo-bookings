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

    // Get sidebar settings
    $settings = get_post_meta( $calendar_id, '_codo_sidebar_settings', true );
    $settings = wp_parse_args( $settings, array(
        'show_title'       => 'yes',
        'allow_guest'      => 'no',
    ));

    //var_dump($settings);

    $current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $login_url   = wp_login_url( $current_url );

    $confirmation_message = get_post_meta( $calendar_id, '_codo_confirmation_message', true ) ?: __( 'Your booking has been confirmed successfully! Our team will soon contact you with further details. Thank you for choosing us.', 'codobookings' );

    wp_localize_script( 'codobookings-main', 'CODOBookingsData', array(
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'codobookings_nonce' ),
        'calendarId' => $calendar_id,
        'i18n'       => array(
            'loading' => __( 'Loading booking calendar...', 'codobookings' ),
            'failed'  => __( 'Failed to load calendar. Please refresh the page.', 'codobookings' ),
            'book'    => __( 'Book this slot', 'codobookings' ),
            'noSlots' => __( 'No slots available for selected period.', 'codobookings' ),
        )
    ));

    wp_add_inline_script( 'codobookings-main', sprintf(
        'window.codobookings_settings_%d = %s;',
        $calendar_id,
        wp_json_encode([
            'settings'  => $settings,
            'confirmation_message' => $confirmation_message,
            'userEmail' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
            'loginUrl'  => $login_url,
        ])
    ) );

    ob_start();
    $unique_id = 'codo-calendar-' . $calendar_id . '-' . uniqid();
    ?>
    <div class="codo-calendar-container">
        <?php if ( $settings['show_title'] === 'yes' ) : ?>
            <h2 class="codo-calendar-title">
                <?php echo esc_html( get_the_title( $calendar_id ) ); ?>
            </h2>
        <?php endif; ?>

        <?php 
        $desc = trim( get_post_field( 'post_content', $calendar_id ) );
        if ( ! empty( $desc ) ) : ?>
            <p class="codo-calendar-description">
                <?php echo wp_kses_post( $desc ); ?>
            </p>
        <?php endif; ?>

        <div id="<?php echo esc_attr($unique_id); ?>" 
             class="codo-calendar-wrapper" 
             data-calendar-id="<?php echo esc_attr($calendar_id); ?>">
            <div class="codo-calendar-loading"><?php echo esc_html__('Loading booking calendar...', 'codobookings'); ?></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
