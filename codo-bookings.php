<?php
/**
* Plugin Name: CodoBookings - Booking Management System for WordPress
* Plugin URI: https://codoplex.com/
* Description: Extensible booking system with multiple calendars, timezone-aware slots, Google Meet integration (via Calendar), payments hooks (PMPro/WooCommerce friendly), capacities, buffers, recurring availability, exceptions, CSV export and reports. Procedural, modular, with many actions/filters for extensibility.
* Version: 1.1.0
* Author: CODOPLEX
* License: GPLv2+
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'CODOBOOKINGS_VERSION', '1.1.0' );
define( 'CODOBOOKINGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CODOBOOKINGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/menu.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/post-types.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/metaboxes.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/list-tables.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/admin/settings.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/bookings-post-type.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/core/ajax-handlers.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/frontend/shortcodes.php';
require_once CODOBOOKINGS_PLUGIN_DIR . 'includes/integrations/google-calendar.php';


register_activation_hook( __FILE__, 'codobookings_activate' );
function codobookings_activate() {
    codobookings_register_post_types();
    // create DB table for occurrences or reports if desired via action
    do_action( 'codobookings_activate' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'codobookings_deactivate' );
function codobookings_deactivate() {
    flush_rewrite_rules();
}

// Load textdomain
add_action( 'init', function(){ load_plugin_textdomain( 'codobookings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); } );

// Provide default capability mapping filter
add_filter( 'codobookings_capability', function( $cap ){ return $cap ?: 'manage_options'; } );