<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Optional: remove bookings (posts) created by plugin
$bookings = get_posts( array(
    'post_type' => 'codo_booking',
    'numberposts' => -1,
    'post_status' => 'any',
) );

if ( $bookings ) {
    foreach ( $bookings as $b ) {
        wp_delete_post( $b->ID, true );
    }
}

// Optionally remove plugin options here (none created in v1)
