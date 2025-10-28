<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ==========================================================
 *  CodoBookings Admin Dashboard
 * ==========================================================
 */

function codobookings_dashboard() {
    ?>
    <div class="wrap codobookings-dashboard">

        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e( 'CodoBookings Dashboard', 'codobookings' ); ?>
        </h1>
        <hr class="wp-header-end">

        <div class="codobookings-dashboard-grid">
            <!-- Left: Overview -->
            <div class="codobookings-dashboard-main">

                <!-- Welcome Card -->
                <div class="card codobookings-card">
                    <h2><?php esc_html_e( 'Welcome to CodoBookings', 'codobookings' ); ?></h2>
                    <p>
                        <?php esc_html_e( 'Manage your bookings, calendars, and availability directly from your WordPress dashboard. Easily integrate with Google Calendar and automate appointment scheduling.', 'codobookings' ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=codobookings_settings' ) ); ?>" class="button-primary">
                            <?php esc_html_e( 'Go to Settings', 'codobookings' ); ?>
                        </a>
                        <a href="https://codoplex.com/contact" target="_blank" class="button-secondary">
                            <?php esc_html_e( 'View Documentation', 'codobookings' ); ?>
                        </a>
                    </p>
                </div>

                <!-- System Overview -->
                <div class="card codobookings-card">
                    <h2><?php esc_html_e( 'System Overview', 'codobookings' ); ?></h2>
                    <table class="widefat striped">
                        <tbody>
                            <tr>
                                <th><?php esc_html_e( 'Active Calendars', 'codobookings' ); ?></th>
                                <td><?php echo intval( codobookings_count_items( 'calendars' ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Total Bookings', 'codobookings' ); ?></th>
                                <td><?php echo intval( codobookings_count_items( 'bookings' ) ); ?></td>
                            </tr>
                            <!-- <tr>
                                <th><?php esc_html_e( 'Upcoming Bookings', 'codobookings' ); ?></th>
                                <td><?php echo intval( codobookings_count_upcoming_bookings() ); ?></td>
                            </tr> -->
                            <tr>
                                <th><?php esc_html_e( 'Google Calendar Linked', 'codobookings' ); ?></th>
                                <td>
                                    <?php if ( get_option( 'codobookings_google_refresh_token' ) ) : ?>
                                        <span style="color:green;"><?php esc_html_e( 'Connected', 'codobookings' ); ?></span>
                                    <?php else : ?>
                                        <span style="color:#a00;"><?php esc_html_e( 'Not Connected', 'codobookings' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Quick Actions -->
                <div class="card codobookings-card">
                    <h2><?php esc_html_e( 'Quick Actions', 'codobookings' ); ?></h2>
                    <ul class="codobookings-quick-links">
                        <li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=codo_calendar' ) ); ?>"><span class="dashicons dashicons-calendar"></span> <?php esc_html_e( 'Manage Calendars', 'codobookings' ); ?></a></li>
                        <li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=codo_booking' ) ); ?>"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Manage Bookings', 'codobookings' ); ?></a></li>
                        <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=codobookings_settings' ) ); ?>"><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Settings', 'codobookings' ); ?></a></li>
                        <li><a href="https://care.codoplex.com/" target="_blank"><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Get Support', 'codobookings' ); ?></a></li>
                    </ul>
                </div>
            </div>

            <!-- Right: Add-ons and Updates -->
            <div class="codobookings-dashboard-sidebar">

                <div class="card codobookings-card codobookings-addons">
                    <h2><?php esc_html_e( 'CodoBookings Extensions', 'codobookings' ); ?></h2>
                    <p><?php esc_html_e( 'Enhance your booking experience with our official add-ons and integrations.', 'codobookings' ); ?></p>

                    <ul class="codobookings-addons-list">
                        <li>
                            <strong>CodoBookings for PMPro</strong>
                            <p><?php esc_html_e( 'Sell bookings as PMPro membership levels with advanced checkout.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                        <li>
                            <strong>CodoBookings for WooCommerce</strong>
                            <p><?php esc_html_e( 'Sell bookings as WooCommerce products with advanced checkout.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                        <li>
                            <strong>Google Meet Sync</strong>
                            <p><?php esc_html_e( 'Automatically create and manage Google Meet links for booked slots.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                        <li>
                            <strong>Zoom Integration</strong>
                            <p><?php esc_html_e( 'Seamlessly connect Zoom meetings to bookings.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                    </ul>
                </div>

                <div class="card codobookings-card codobookings-support">
                    <h2><?php esc_html_e( 'Need Help?', 'codobookings' ); ?></h2>
                    <p><?php esc_html_e( 'Explore our documentation or reach out to our support team for assistance.', 'codobookings' ); ?></p>
                    <p>
                        <a href="https://codoplex.com/contact" class="button button-secondary" target="_blank"><?php esc_html_e( 'View Docs', 'codobookings' ); ?></a>
                        <a href="https://codoplex.com/contact" class="button button-primary" target="_blank"><?php esc_html_e( 'Get Support', 'codobookings' ); ?></a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <style>
        .codobookings-dashboard-grid {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .codobookings-dashboard-main { flex: 3; display: flex; flex-direction: column; gap: 20px; }
        .codobookings-dashboard-sidebar { flex: 1; display: flex; flex-direction: column; gap: 20px; }
        .codobookings-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            max-width: 100%;
        }
        .codobookings-quick-links li {
            margin: 6px 0;
        }
        .codobookings-quick-links a {
            text-decoration: none;
            color: #0073aa;
            display: inline-flex;
            align-items: center;
        }
        .codobookings-quick-links a:hover {
            color: #2271b1;
        }
        .codobookings-quick-links .dashicons {
            margin-right: 8px;
            color: #555;
        }
        .codobookings-addons-list li {
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        .codobookings-addons-list li:last-child {
            border-bottom: none;
        }
        @media (max-width: 960px) {
            .codobookings-dashboard-grid { flex-direction: column; }
        }
    </style>
    <?php
}

/**
 * Utility: Count items
 */

/**
 * Count items by type using registered Custom Post Types.
 *
 * @param string $type The type of item ('calendars' or 'bookings').
 * @return int The total number of published posts.
 */
function codobookings_count_items( $type ) {
    switch ( $type ) {
        case 'calendars':
            $post_type = 'codo_calendar';
            break;
        case 'bookings':
            $post_type = 'codo_booking';
            break;
        default:
            return 0;
    }

    $counts = wp_count_posts( $post_type );
    return isset( $counts->publish ) ? (int) $counts->publish : 0;
}

/**
 * Count upcoming bookings based on booking date stored in post meta.
 * Assumes `_codo_booking_start` meta key stores datetime in 'Y-m-d H:i:s' (UTC or site time).
 *
 * @return int Number of upcoming bookings.
 */
function codobookings_count_upcoming_bookings() {
    $today = current_time( 'mysql' );

    $query = new WP_Query( array(
        'post_type'      => 'codo_booking',
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_codo_start',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATETIME',
            ),
        ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    return (int) $query->found_posts;
}
