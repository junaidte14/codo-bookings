<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create the main Calendar page (runs only once).
 */
function codobookings_create_calendar_page() {
    $page_title = 'Calendar';
    $page_slug  = 'calendar';
    $shortcode  = '[codo_calendar id="calendarId"]';

    // Prevent duplicates by slug
    $existing_page = get_page_by_path( $page_slug, OBJECT, 'page' );

    if ( ! $existing_page ) {
        $page_id = wp_insert_post( array(
            'post_title'     => $page_title,
            'post_name'      => $page_slug,
            'post_content'   => $shortcode,
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
            'post_author'    => get_current_user_id(),
        ) );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( 'codobookings_calendar_page_id', $page_id );
        }
    } else {
        // Update content only if missing
        if ( empty( trim( $existing_page->post_content ) ) ) {
            wp_update_post( array(
                'ID'           => $existing_page->ID,
                'post_content' => $shortcode,
            ) );
        }

        update_option( 'codobookings_calendar_page_id', $existing_page->ID );
    }
}