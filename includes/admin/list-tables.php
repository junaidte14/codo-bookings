<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// --- Calendar Columns ---
add_filter( 'manage_codo_calendar_posts_columns', 'codobookings_calendar_columns' );
function codobookings_calendar_columns( $cols ) {
    $cols = array(
        'cb'        => $cols['cb'],
        'title'     => __( 'Calendar', 'codobookings' ),
        'shortcode' => __( 'Shortcode', 'codobookings' ),
        'recurrence'     => __( 'Type', 'codobookings' ),
        'category'  => __( 'Category', 'codobookings' ),
        'date'      => $cols['date'],
    );
    return $cols;
}

add_action( 'manage_codo_calendar_posts_custom_column', 'codobookings_calendar_columns_data', 10, 2 );
function codobookings_calendar_columns_data( $column, $post_id ) {
    if ( $column === 'shortcode' ) {
        echo '<code>[codo_calendar id="' . esc_attr( $post_id ) . '"]</code>';
    }
    if ( $column === 'recurrence' ) {
        $recurrence = get_post_meta( $post_id, '_codo_recurrence', true );
        if ( $recurrence === 'none' ) {
            echo esc_html__( 'one-time', 'codobookings' );
        } else {
            echo esc_html( $recurrence );
        }
    }

    if ( $column === 'category' ) {
        $terms = get_the_terms( $post_id, 'calendar_category' );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $links = array();

            foreach ( $terms as $term ) {
                $url = esc_url( add_query_arg(
                    array(
                        'post_type'          => 'codo_calendar',
                        'calendar_category'  => $term->slug,
                    ),
                    'edit.php'
                ) );

                $links[] = '<a href="' . $url . '">' . esc_html( $term->name ) . '</a>';
            }

            // Safe HTML output (links only)
            echo wp_kses_post( implode( ', ', $links ) );

        } else {
            echo '<span style="color:#aaa;">' . esc_html__( '—', 'codobookings' ) . '</span>';
        }
    }
}

// --- Add "View" link in Calendar admin list ---
add_filter( 'post_row_actions', 'codobookings_add_view_calendar_link', 10, 2 );
function codobookings_add_view_calendar_link( $actions, $post ) {
    if ( $post->post_type === 'codo_calendar' ) {
        $calendar_page = get_permalink( get_page_by_path( 'calendar' ) );
        if ( $calendar_page ) {
            $view_url = add_query_arg( 'calendar_id', $post->ID, $calendar_page );
            $actions['view_calendar'] = '<a href="' . esc_url( $view_url ) . '" target="_blank">' . __( 'View', 'codobookings' ) . '</a>';
        }
    }
    return $actions;
}

// --- Show "View Calendar" link after saving/updating ---
add_action( 'post_updated_messages', 'codobookings_calendar_updated_messages' );
function codobookings_calendar_updated_messages( $messages ) {
    global $post;
    
    if ( isset( $post->post_type ) && $post->post_type === 'codo_calendar' ) {
        $calendar_page = get_permalink( get_page_by_path( 'calendar' ) );

        if ( $calendar_page ) {
            $view_url = add_query_arg( 'calendar_id', $post->ID, $calendar_page );
            $view_link = ' <a href="' . esc_url( $view_url ) . '" target="_blank">' . __( 'View Calendar', 'codobookings' ) . '</a>';

            $messages['codo_calendar'][1] = __( 'Calendar updated.', 'codobookings' ) . $view_link;
            $messages['codo_calendar'][6] = __( 'Calendar published.', 'codobookings' ) . $view_link;
            $messages['codo_calendar'][7] = __( 'Calendar saved.', 'codobookings' ) . $view_link;
            $messages['codo_calendar'][10] = __( 'Calendar draft updated.', 'codobookings' ) . $view_link;
        }
    }

    return $messages;
}

// --- Add dropdown filter above Calendar list ---
add_action( 'restrict_manage_posts', 'codobookings_calendar_filter_dropdown' );
function codobookings_calendar_filter_dropdown() {
    global $typenow;
    if ( $typenow !== 'codo_calendar' ) {
        return;
    }

    $selected = isset( $_GET['recurrence_filter'] ) ? sanitize_text_field( wp_unslash($_GET['recurrence_filter']) ) : '';
    ?>
    <select name="recurrence_filter">
        <option value=""><?php esc_html_e( 'All Types', 'codobookings' ); ?></option>
        <option value="none" <?php selected( $selected, 'none' ); ?>><?php esc_html_e( 'One-time', 'codobookings' ); ?></option>
        <option value="weekly" <?php selected( $selected, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'codobookings' ); ?></option>
    </select>
    <?php
}

// --- Filter Calendar list query by recurrence type ---
add_action( 'pre_get_posts', 'codobookings_filter_calendars_by_type' );
function codobookings_filter_calendars_by_type( $query ) {
    global $pagenow, $typenow;

    if ( $pagenow !== 'edit.php' || $typenow !== 'codo_calendar' || ! $query->is_main_query() ) {
        return;
    }

    if ( isset( $_GET['recurrence_filter'] ) && $_GET['recurrence_filter'] !== '' ) {
        $query->set( 'meta_query', array(
            array(
                'key'     => '_codo_recurrence',
                'value'   => sanitize_text_field( $_GET['recurrence_filter'] ),
                'compare' => '=',
            ),
        ) );
    }
}

// Add "Filter by Category" dropdown in the admin list view for Calendars
add_action( 'restrict_manage_posts', function( $post_type ) {
    if ( $post_type !== 'codo_calendar' ) {
        return;
    }

    $taxonomy = 'calendar_category';
    $selected = isset( $_GET[$taxonomy] ) ? absint($_GET[$taxonomy]) : 0;
    $info_taxonomy = get_taxonomy( $taxonomy );

    wp_dropdown_categories( array(
        'show_option_all' => __( 'All Categories', 'codobookings' ),
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $selected,
        'hierarchical'    => true,
        'depth'           => 0,
        'show_count'      => true,
        'hide_empty'      => false,
    ) );
});

add_filter( 'parse_query', function( $query ) {
    global $pagenow;

    $taxonomy = 'calendar_category';
    $q_vars   = &$query->query_vars;

    if ( $pagenow === 'edit.php'
        && isset( $q_vars['post_type'] )
        && $q_vars['post_type'] === 'codo_calendar'
        && isset( $q_vars[$taxonomy] )
        && is_numeric( $q_vars[$taxonomy] )
        && $q_vars[$taxonomy] != 0
    ) {
        $term = get_term_by( 'id', $q_vars[$taxonomy], $taxonomy );
        $q_vars[$taxonomy] = $term->slug;
    }
});

// --- Bookings Columns ---
add_filter( 'manage_codo_booking_posts_columns', 'codobookings_booking_columns' );
function codobookings_booking_columns( $cols ) {
    $cols = array(
        'cb'        => $cols['cb'],
        'title'     => __( 'Booking', 'codobookings' ),
        'calendar'  => __( 'Calendar', 'codobookings' ),
        'datetime'     => __( 'Date/Time (UTC / Local)', 'codobookings' ),
        'status'    => __( 'Status', 'codobookings' ),
    );
    return $cols;
}

add_action( 'manage_codo_booking_posts_custom_column', 'codobookings_booking_columns_data', 10, 2 );
function codobookings_booking_columns_data( $column, $post_id ) {
    // Calendar column
    if ( $column === 'calendar' ) {
        $calendar_id = get_post_meta( $post_id, '_codo_calendar_id', true );
        if ( $calendar_id ) {
            $calendar_title = get_the_title( $calendar_id );
            $edit_link = get_edit_post_link( $calendar_id );
            echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $calendar_title ) . '</a>';
        } else {
            echo '-';
        }
        return;
    }

    // Status column
    if ( $column === 'status' ) {
        echo esc_html( get_post_meta( $post_id, '_codo_status', true ) );
        return;
    }

    // Combine Start & End into a single column called 'time' (adjust your columns filter accordingly)
    if ( $column === 'datetime' ) {
        $calendar_id = get_post_meta( $post_id, '_codo_calendar_id', true );
        if ( ! $calendar_id ) {
            echo '-';
            return;
        }

        $recurrence = get_post_meta( $post_id, '_codo_recurrence', true ); // 'none' or 'weekly'
        $rec_day    = get_post_meta( $post_id, '_codo_day', true );

        $start_utc = get_post_meta( $post_id, '_codo_start', true );
        $end_utc   = get_post_meta( $post_id, '_codo_end', true );

        $wp_tz = new DateTimeZone( wp_timezone_string() );

        // ---- One-time booking ----
        if ( $recurrence === 'none' ) {
            if ( !$start_utc ) {
                echo '-';
                return;
            }

            $start_dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start_utc, new DateTimeZone('UTC'));
            $end_dt   = $end_utc ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end_utc, new DateTimeZone('UTC')) : null;

            $start_local = $start_dt->setTimezone( $wp_tz );
            $end_local   = $end_dt ? $end_dt->setTimezone( $wp_tz ) : null;

            echo '<div>';
            // UTC
            $utc_date = $start_dt->format('Y-m-d');
            $utc_start = $start_dt->format('H:i');
            $utc_end   = $end_dt ? $end_dt->format('H:i') : '';
            echo '<strong>UTC:</strong> ' . esc_html("{$utc_date} {$utc_start}");
            if ( $utc_end ) echo ' - ' . esc_html($utc_end);
            echo '<br>';
            // Local
            $local_date = $start_local->format('Y-m-d');
            $local_start = $start_local->format('H:i');
            $local_end   = $end_local ? $end_local->format('H:i') : '';
            echo '<strong>Local:</strong> ' . esc_html("{$local_date} {$local_start}");
            if ( $local_end ) echo ' - ' . esc_html($local_end);
            echo '</div>';
        }

        // ---- Weekly booking ----
        else if ( $recurrence === 'weekly' ) {
            if ( !$start_utc ) {
                echo esc_html("Every {$rec_day}");
                return;
            }

            $start_dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start_utc, new DateTimeZone('UTC'));
            $end_dt   = $end_utc ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end_utc, new DateTimeZone('UTC')) : null;

            $start_local = $start_dt->setTimezone( $wp_tz );
            $end_local   = $end_dt ? $end_dt->setTimezone( $wp_tz ) : null;

            echo '<div>';
            echo '<strong>UTC:</strong> Every ' . esc_html( ucfirst($rec_day) ) . ' ' . esc_html($start_dt->format('H:i'));
            if ( $end_dt ) echo ' - ' . esc_html($end_dt->format('H:i'));
            echo '<br>';
            echo '<strong>Local:</strong> Every ' . esc_html( ucfirst($rec_day) ) . ' ' . esc_html($start_local->format('H:i'));
            if ( $end_local ) echo ' - ' . esc_html($end_local->format('H:i'));
            echo '</div>';
        }
    }
}

// --- Bookings Filters ---
add_action( 'restrict_manage_posts', 'codobookings_add_booking_filters' );
function codobookings_add_booking_filters() {
    global $typenow;
    if ( $typenow !== 'codo_booking' ) return;

    // Get unique calendar IDs from existing bookings
    $calendar_ids = get_posts( array(
        'post_type'      => 'codo_booking',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    $used_calendar_ids = array_unique( array_filter( array_map( function( $id ) {
        return get_post_meta( $id, '_codo_calendar_id', true );
    }, $calendar_ids ) ) );

    // Calendar Filter
    echo '<select name="codo_calendar_filter">';
    echo '<option value="">' . esc_html__( 'All Calendars', 'codobookings' ) . '</option>';
    foreach ( $used_calendar_ids as $calendar_id ) {
        $selected = ( isset( $_GET['codo_calendar_filter'] ) && $_GET['codo_calendar_filter'] == $calendar_id ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $calendar_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( get_the_title( $calendar_id ) ) . '</option>';
    }
    echo '</select>';

    // Status Filter
    $status_selected = isset( $_GET['codo_status_filter'] ) ? sanitize_text_field( wp_unslash($_GET['codo_status_filter']) ) : '';
    $statuses = array( 'pending', 'confirmed', 'cancelled', 'completed' );
    echo '<select name="codo_status_filter">';
    echo '<option value="">' . esc_html__( 'All Statuses', 'codobookings' ) . '</option>';
    foreach ( $statuses as $status ) {
        echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status_selected, $status, false ) . '>' . esc_html( ucfirst( $status ) ) . '</option>';
    }
    echo '</select>';
}

// --- Filter query ---
add_action( 'pre_get_posts', 'codobookings_filter_bookings_query' );
function codobookings_filter_bookings_query( $query ) {
    global $pagenow;
    if ( ! is_admin() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'codo_booking' ) return;

    // Filter by Calendar
    if ( ! empty( $_GET['codo_calendar_filter'] ) ) {
        $query->set( 'meta_query', array(
            array(
                'key'   => '_codo_calendar_id',
                'value' => sanitize_text_field( $_GET['codo_calendar_filter'] ),
            )
        ) );
    }

    // Filter by Status
    if ( ! empty( $_GET['codo_status_filter'] ) ) {
        $meta_query = (array) $query->get( 'meta_query' );
        $meta_query[] = array(
            'key'   => '_codo_status',
            'value' => sanitize_text_field( $_GET['codo_status_filter'] ),
        );
        $query->set( 'meta_query', $meta_query );
    }
}
