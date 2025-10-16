<?php
/**
 * Plugin Name:       CodoBookings – PMPro Extension (Bookings)
 * Plugin URI:        https://www.codoplex.com/codo-bookings
 * Description:       Booking & scheduling extension that integrates with Paid Memberships Pro (PMPro). Handles time slots, bookings, admin UI and notifications. PMPro handles memberships/payments.
 * Version:           1.0.0
 * Author:            Codoplex
 * Author URI:        https://www.codoplex.com
 * Text Domain:       codo-bookings
 * Domain Path:       /languages
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CODO_BOOKINGS_VERSION', '1.0.0' );
define( 'CODO_BOOKINGS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CODO_BOOKINGS_URL', plugin_dir_url( __FILE__ ) );

// -----------------------------------------------------------------------------
// Basic dependency check: Paid Memberships Pro
// -----------------------------------------------------------------------------
add_action( 'plugins_loaded', 'codo_bookings_plugins_loaded' );
function codo_bookings_plugins_loaded() {
    // If PMPro is not active, show admin notice and still load core minimal functionality.
    if ( ! class_exists( 'PMPro' ) && ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
        add_action( 'admin_notices', 'codo_bookings_admin_notice_pmpro_missing' );
    }
}

function codo_bookings_admin_notice_pmpro_missing() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <?php esc_html_e( 'CodoBookings is active but requires Paid Memberships Pro (PMPro) to operate as an extension. Please install & activate PMPro.', 'codo-bookings' ); ?>
        </p>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// Include core files (CPT, admin, frontend, integration)
// -----------------------------------------------------------------------------
require_once CODO_BOOKINGS_DIR . 'includes/core/cpt-register.php';
require_once CODO_BOOKINGS_DIR . 'includes/core/meta-boxes.php';
require_once CODO_BOOKINGS_DIR . 'includes/core/helpers.php';
require_once CODO_BOOKINGS_DIR . 'includes/core/emails.php';

require_once CODO_BOOKINGS_DIR . 'includes/frontend/shortcodes.php';

require_once CODO_BOOKINGS_DIR . 'includes/admin/admin-menu.php';
require_once CODO_BOOKINGS_DIR . 'includes/admin/booking-list-columns.php';
require_once CODO_BOOKINGS_DIR . 'includes/admin/settings-bookings.php';

/**
 * Load PMPro integration after all plugins are initialized
 */
function codo_bookings_load_pmpro_integration() {
    if ( function_exists( 'pmpro_hasMembershipLevel' ) || class_exists( 'PMPro' ) ) {
        require_once CODO_BOOKINGS_DIR . 'includes/integration/pmpro-hooks.php';
        require_once CODO_BOOKINGS_DIR . 'includes/integration/pmpro-level-settings.php';
        require_once CODO_BOOKINGS_DIR . 'includes/integration/calendar-view.php';
    } else {
        // Optional: Log or note that PMPro not active
        error_log( 'CodoBookings: PMPro not detected at load time.' );
    }
}
add_action( 'plugins_loaded', 'codo_bookings_load_pmpro_integration', 20 );

// Activation / Deactivation hooks
register_activation_hook( __FILE__, 'codo_bookings_activate' );
function codo_bookings_activate() {
    // Ensure CPTs are registered on activation
    codo_register_booking_cpt();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'codo_bookings_deactivate' );
function codo_bookings_deactivate() {
    flush_rewrite_rules();
}
