<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook !== 'toplevel_page_codobookings_dashboard' ) return;
    wp_enqueue_style( 'codobookings-admin-dashboard', CODOBOOKINGS_PLUGIN_URL . 'assets/css/dashboard.css', [], CODOBOOKINGS_VERSION );
} );

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
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=codobookings_settings' ) ); ?>" class="button-primary" rel="noopener noreferrer">
                            <?php esc_html_e( 'Go to Settings', 'codobookings' ); ?>
                        </a>
                        <a href="https://codoplex.com/contact" target="_blank" class="button-secondary" rel="noopener noreferrer">
                            <?php esc_html_e( 'View Documentation', 'codobookings' ); ?>
                        </a>
                    </p>
                </div>

                <!-- System Overview -->
                <div class="card codobookings-card">
                    <h2><?php esc_html_e( 'System Overview', 'codobookings' ); ?></h2>
                    <table class="widefat striped">
                        <tbody>
                        <?php
                        // Default system overview rows
                        $overview_rows = [
                            'active_calendars' => [
                                'label' => __( 'Active Calendars', 'codobookings' ),
                                'value' => intval( codobookings_count_items( 'calendars' ) ),
                            ],
                            'total_bookings' => [
                                'label' => __( 'Total Bookings', 'codobookings' ),
                                'value' => intval( codobookings_count_items( 'bookings' ) ),
                            ]
                            
                        ];

                        /**
                         * Allow other extensions to add or modify system overview rows.
                         * Each item must return ['label' => '...', 'value' => '...'].
                         */
                        $overview_rows = apply_filters( 'codobookings_system_overview_rows', $overview_rows );

                        foreach ( $overview_rows as $key => $row ) :
                            ?>
                            <tr>
                                <th><?php echo esc_html( $row['label'] ); ?></th>
                                <td><?php echo esc_html( $row['value'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
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
                        <li><a href="https://care.codoplex.com/" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Get Support', 'codobookings' ); ?></a></li>
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
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                        <li>
                            <strong>CodoBookings for WooCommerce</strong>
                            <p><?php esc_html_e( 'Sell bookings as WooCommerce products with advanced checkout.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                        <li>
                            <strong>Google Meet Sync</strong>
                            <p><?php esc_html_e( 'Automatically create and manage Google Meet links for booked slots.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                        <li>
                            <strong>Zoom Integration</strong>
                            <p><?php esc_html_e( 'Seamlessly connect Zoom meetings to bookings.', 'codobookings' ); ?></p>
                            <a href="https://codoplex.com/contact" class="button button-small" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Coming Soon', 'codobookings' ); ?></a>
                        </li>
                    </ul>
                </div>

                <div class="card codobookings-card codobookings-support">
                    <h2><?php esc_html_e( 'Need Help?', 'codobookings' ); ?></h2>
                    <p><?php esc_html_e( 'Explore our documentation or reach out to our support team for assistance.', 'codobookings' ); ?></p>
                    <p>
                        <a href="https://codoplex.com/contact" class="button button-secondary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Docs', 'codobookings' ); ?></a>
                        <a href="https://codoplex.com/contact" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Support', 'codobookings' ); ?></a>
                    </p>
                </div>

            </div>
        </div>
    </div>
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