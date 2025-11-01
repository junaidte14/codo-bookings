<?php
/**
* Plugin Name: CodoBookings
* Plugin URI: https://codoplex.com/
* Description: A Booking Management System for WordPress. Extensible booking system with multiple calendars, availability slots, and recurring bookings. Modular and develper-friendly, with many actions/filters for extensibility.
* Version: 1.1.0
* Author: CODOPLEX
* License: GPLv2+
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'CODOBOOKINGS_VERSION', '1.1.0' );
define( 'CODOBOOKINGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CODOBOOKINGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Admin Menu
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/menu.php';

// Calendar and Bookings
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/post-types.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/metaboxes.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/list-tables.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/manage-bookings.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/bookings-post-type.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/ajax-handlers.php';

// Settings
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/settings/general.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/settings/emails.php';

// Dashboard
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/dashboard.php';

// Shortcodes
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/shortcodes/single-calendar.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/shortcodes/calendars-grid.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/page-with-shortcode.php';

// Emails
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/emails/basic-emails.php';

register_activation_hook( __FILE__, 'codobookings_activate' );
function codobookings_activate() {
    // Register post types before creating pages
    codobookings_register_post_types();
    // Create or ensure Calendar page exists
    codobookings_create_calendar_page();
    do_action( 'codobookings_activate' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'codobookings_deactivate' );
function codobookings_deactivate() {
    flush_rewrite_rules();
}

// Load textdomain
add_action( 'init', function(){ load_plugin_textdomain( 'codobookings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); } );