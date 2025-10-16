<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [codo_my_bookings]
 * Shows a simple table of the current user's bookings.
 * You can place this in a PMPro account page or a dedicated page.
 */
function codo_my_bookings_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Please log in to view your bookings.', 'codo-bookings' ) . '</p>';
    }

    $user_id = get_current_user_id();

    $q = new WP_Query( array(
        'post_type'      => 'codo_booking',
        'post_status'    => array( 'publish', 'pending' ),
        'author'         => $user_id,
        'posts_per_page' => -1,
    ) );

    if ( ! $q->have_posts() ) {
        return '<p>' . esc_html__( 'You have no upcoming bookings.', 'codo-bookings' ) . '</p>';
    }

    ob_start();
    echo '<table class="codo-my-bookings"><thead><tr><th>' . esc_html__( 'Date', 'codo-bookings' ) . '</th><th>' . esc_html__( 'Time', 'codo-bookings' ) . '</th><th>' . esc_html__( 'Status', 'codo-bookings' ) . '</th></tr></thead><tbody>';
    while ( $q->have_posts() ) {
        $q->the_post();
        $id = get_the_ID();
        $date = get_post_meta( $id, '_codo_date', true );
        $time = get_post_meta( $id, '_codo_time', true );
        $status = get_post_meta( $id, '_codo_status', true );
        echo '<tr>';
        echo '<td>' . esc_html( $date ) . '</td>';
        echo '<td>' . esc_html( $time ) . '</td>';
        echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'codo_my_bookings', 'codo_my_bookings_shortcode' );

/**
 * Shortcode: [codo_booking_levels]
 * Display PMPro membership levels with bookings enabled in a 3-column layout.
 */
function codo_booking_levels_shortcode( $atts ) {

    if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
        return '<p>' . esc_html__( 'PMPro plugin is not active.', 'codo-bookings' ) . '</p>';
    }

    $levels = pmpro_getAllLevels( true ); // Only active levels
    if ( empty( $levels ) ) {
        return '<p>' . esc_html__( 'No membership levels found.', 'codo-bookings' ) . '</p>';
    }

    // Filter levels with booking enabled
    $enabled_levels = [];
    foreach ( $levels as $level ) {
        $opt1 = get_option( "pmpro_level_{$level->id}_enable_bookings", null );
        $opt2 = get_option( 'codo_pmpro_level_' . $level->id . '_allow_booking', null );
        if ( ! empty( $opt1 ) || ! empty( $opt2 ) ) {
            $enabled_levels[] = $level;
        }
    }

    if ( empty( $enabled_levels ) ) {
        return '<p>' . esc_html__( 'No levels with bookings enabled.', 'codo-bookings' ) . '</p>';
    }

    // Start output
    ob_start();
    ?>
    <div class="codo-booking-levels-container" style="display:flex; flex-wrap:wrap; gap:20px; justify-content:flex-start;">
        <?php foreach ( $enabled_levels as $level ): ?>
            <div class="codo-booking-level-card" style="flex:1 1 calc(33.333% - 20px); border:1px solid #ddd; border-radius:8px; padding:20px; box-sizing:border-box; text-align:center;">
                <h3 style="margin-top:0;"><?php echo esc_html( $level->name ); ?></h3>
                <p style="font-size:14px; color:#555;"><?php echo wp_kses_post( $level->description ); ?></p>
                <p style="font-weight:bold; font-size:16px;"><?php echo pmpro_formatPrice( $level->initial_payment ); ?></p>
                <a href="<?php echo esc_url( pmpro_url( 'checkout', '?level=' . $level->id ) ); ?>" class="codo-booking-btn" style="display:inline-block; margin-top:10px; padding:10px 20px; background:#0073aa; color:#fff; text-decoration:none; border-radius:5px;">
                    <?php esc_html_e( 'Book Now', 'codo-bookings' ); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <style>
        /* Responsive: 1 column on small screens */
        @media (max-width: 768px){
            .codo-booking-level-card { flex:1 1 100%; }
        }
        .codo-booking-btn:hover { background:#005177; }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode( 'codo_booking_levels', 'codo_booking_levels_shortcode' );
