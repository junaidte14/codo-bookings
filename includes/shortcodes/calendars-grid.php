<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcode: [codo_calendars_grid columns="3" category="workshops"]
 * Displays all booking calendars in a grid, or a single calendar view with a back button.
 *
 * @param array $atts
 * @return string
*/
function codobookings_calendars_grid_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'columns'      => 3,
        'post_type'    => 'codo_calendar',
        'details_page' => 'current', // 'current' or a specific page URL
        'category'     => '',
    ), $atts, 'codo_calendars_grid' );

    $columns = max( 1, intval( $atts['columns'] ) );

    // If a specific calendar is being viewed
    if ( isset( $_GET['calendar_id'] ) && is_numeric( $_GET['calendar_id'] ) ) {
        $calendar_id = intval( $_GET['calendar_id'] );
        $calendar    = get_post( $calendar_id );

        if ( ! $calendar || $calendar->post_type !== $atts['post_type'] ) {
            return '<p>' . __( 'Invalid calendar selected.', 'codobookings' ) . '</p>';
        }

        // Determine Back URL
        if ( $atts['details_page'] === 'current' ) {
            $back_url = remove_query_arg( 'calendar_id' );
        } else {
            $back_url = esc_url( $atts['details_page'] );
        }

        ob_start(); ?>
        <div class="codo-calendar-details">
            <a href="<?php echo esc_url( $back_url ); ?>" class="codo-back-btn">← <?php _e( 'Back to All Calendars', 'codobookings' ); ?></a>
            <div class="codo-single-calendar">
                <?php if ( has_post_thumbnail( $calendar_id ) ) : ?>
                    <div class="codo-calendar-featured">
                        <?php echo get_the_post_thumbnail( $calendar_id, 'large', array( 'class' => 'codo-calendar-img' ) ); ?>
                    </div>
                <?php endif; ?>
                <?php echo do_shortcode( '[codo_calendar id="' . esc_attr( $calendar_id ) . '"]' ); ?>
            </div>
        </div>

        <style>
        .codo-back-btn {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #0073aa;
            font-weight: 500;
            background: #f3f6f9;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.25s ease;
        }
        .codo-back-btn:hover {
            background: #0073aa;
            color: #fff;
        }
        .codo-single-calendar {
            border: 1px solid #e3e3e3;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 25px;
            background: #fff;
        }
        .codo-calendar-featured {
            text-align: center;
            margin: -25px -25px 0 -25px; /* cancel out card padding */
            overflow: hidden;
        }
        .codo-calendar-featured img {
            border-radius: 12px;
            max-width: 100%;
            height: auto;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    // Otherwise: show grid view
    // Build query args for grid view
    $query_args = array(
        'post_type'      => $atts['post_type'],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );

    // ✅ Filter by category if provided
    if ( ! empty( $atts['category'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'calendar_category',  // custom taxonomy
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ),
        );
    }

    $calendars = get_posts( $query_args );

    if ( empty( $calendars ) ) {
        return '<p>' . __( 'No calendars available at the moment.', 'codobookings' ) . '</p>';
    }

    ob_start(); ?>
    <div class="codo-calendars-grid" style="--codo-grid-columns: <?php echo esc_attr( $columns ); ?>;">
        <?php foreach ( $calendars as $calendar ) :
            $title = esc_html( get_the_title( $calendar ) );
            $desc  = esc_html( wp_trim_words( $calendar->post_content, 25 ) );
            $img   = has_post_thumbnail( $calendar->ID ) ? get_the_post_thumbnail( $calendar->ID, 'medium', array( 'class' => 'codo-calendar-thumb' ) ) : '';

            // Determine details URL
            if ( $atts['details_page'] === 'current' ) {
                $details_url = add_query_arg( 'calendar_id', $calendar->ID, get_permalink() );
            } else {
                $details_url = add_query_arg( 'calendar_id', $calendar->ID, esc_url( $atts['details_page'] ) );
            }
            ?>
            <div class="codo-calendar-item">
                <?php if ( $img ) : ?>
                    <div class="codo-calendar-thumb-wrap">
                        <a href="<?php echo esc_url( $details_url ); ?>">
                            <?php echo $img; ?>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="codo-calendar-content">
                    <h3 class="codo-calendar-title"><?php echo $title; ?></h3>
                    <p class="codo-calendar-desc"><?php echo $desc; ?></p>
                    <a href="<?php echo esc_url( $details_url ); ?>" class="codo-book-btn">
                        <?php _e( 'Book Now', 'codobookings' ); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <style>
    .codo-calendars-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
        justify-content: center;
        align-items: stretch;
        align-items: flex-start;
        margin: 40px 0;
    }
    .codo-calendar-item {
        flex: 1 1 calc(100% / var(--codo-grid-columns) - 25px);
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e3e3e3;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 25px;
        text-align: center;
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        justify-content: flex-start; /* ✅ allow content to stack naturally */
        height: auto; /* ✅ ensures card fits content */
    }
    .codo-calendar-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .codo-calendar-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .codo-calendar-thumb-wrap {
        margin: -25px -25px 0 -25px; /* cancel out card padding */
        overflow: hidden;
    }
    .codo-calendar-thumb {
        width: 100%;
        height: auto;
        border-radius: 10px;
        object-fit: cover;
    }
    .codo-calendar-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #222;
        margin-bottom: 10px;
    }
    .codo-calendar-desc {
        font-size: 0.95rem;
        color: #555;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    .codo-book-btn {
        display: inline-block;
        background: #0073aa;
        color: #fff;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        transition: background 0.25s ease;
    }
    .codo-book-btn:hover {
        background: #005f8d;
    }
    @media (max-width: 1024px) {
        .codo-calendar-item { flex: 1 1 calc(50% - 25px); }
    }
    @media (max-width: 640px) {
        .codo-calendar-item { flex: 1 1 100%; }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode( 'codo_calendars_grid', 'codobookings_calendars_grid_shortcode' );
