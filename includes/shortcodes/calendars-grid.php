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

            // Determine details page ID (stored when plugin was activated)
            $calendar_page_id = get_option( 'codobookings_calendar_page_id' );

            // Validate that the page still exists
            if ( $calendar_page_id && get_post_status( $calendar_page_id ) === 'publish' ) {
                // Use the stored page permalink
                $calendar_page_url = get_permalink( $calendar_page_id );
            } else {
                // Fallback: if the page was deleted, create it again
                $calendar_page_url = codobookings_create_calendar_page();
            }

            // Capture current page URL to use as "back" link
            $current_page_url = get_permalink(); //
            $current_page_id = get_queried_object_id(); 
            // Build details URL with both calendar_id and back params
            $details_url = add_query_arg( array(
                'calendar_id' => $calendar->ID,
                'back'        => $current_page_id,
            ), esc_url( $calendar_page_url ) );
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
                    <?php if ( ! empty( trim( $desc ) ) ) : ?>
                        <p class="codo-calendar-desc"><?php echo $desc; ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $details_url ); ?>" class="button codo-book-btn">
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
        padding: 10px;
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
        margin: -10px -10px 0 -10px; /* cancel out card padding */
        overflow: hidden;
    }
    .codo-calendar-thumb {
        width: 100%;
        height: auto;
        border-radius: 10px 10px 0 0;
        object-fit: cover;
    }
    .codo-calendar-title {
        margin-bottom: 10px;
    }
    .codo-calendar-desc {
        margin-bottom: 20px;
    }
    .codo-book-btn {
        display: inline-block;
        font-weight: 500;
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
