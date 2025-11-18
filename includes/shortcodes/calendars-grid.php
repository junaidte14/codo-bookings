<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register and enqueue CSS for calendars grid
 */
function codobookings_enqueue_calendars_grid_assets() {
    wp_register_style(
        'codobookings-calendars-grid',
        CODOBOOKINGS_PLUGIN_URL . 'assets/css/calendars-grid.css',
        array(),
        CODOBOOKINGS_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'codobookings_enqueue_calendars_grid_assets' );
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

    // Filter by category if provided
    if ( ! empty( $atts['category'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'codo_calendar_category',  // custom taxonomy
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ),
        );
    }

    $calendars = get_posts( $query_args );

    if ( empty( $calendars ) ) {
        return '<p>' . __( 'No calendars available at the moment.', 'codobookings' ) . '</p>';
    }
    // ✅ Enqueue CSS before returning
    wp_enqueue_style( 'codobookings-calendars-grid' );
    ob_start(); ?>
    <div class="codo-calendars-grid" style="--codo-grid-columns: <?php echo esc_attr( $columns ); ?>;">
        <?php foreach ( $calendars as $calendar ) :
            // Validate post type and status for extra safety
            if ( $calendar->post_type !== 'codo_calendar' || $calendar->post_status !== 'publish' ) {
                continue;
            }

            $title = esc_html( get_the_title( $calendar ) );
            $desc  = esc_html( wp_trim_words( $calendar->post_content, 25 ) );
            $img   = has_post_thumbnail( $calendar->ID ) ? get_the_post_thumbnail( $calendar->ID, 'medium', array( 'class' => 'codo-calendar-thumb' ) ) : '';

            // Determine details page ID
            $calendar_page_id = get_option( 'codobookings_calendar_page_id' );
            if ( $calendar_page_id && get_post_status( $calendar_page_id ) === 'publish' ) {
                $calendar_page_url = get_permalink( $calendar_page_id );
            } else {
                $calendar_page_url = codobookings_create_calendar_page();
            }

            $current_page_id = get_queried_object_id(); 
            $details_url = add_query_arg( array(
                'calendar_id' => $calendar->ID,
                'back'        => $current_page_id,
            ), esc_url( $calendar_page_url ) );
            ?>
            <div class="codo-calendar-item">
                <?php if ( $img ) : ?>
                    <div class="codo-calendar-thumb-wrap">
                        <a href="<?php echo esc_url( $details_url ); ?>" class="codo-calendar-link">
                            <?php echo wp_kses_post( $img ); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="codo-calendar-content">
                    <h3 class="codo-calendar-title"><?php echo esc_html( $title ); ?></h3>
                    <?php if ( ! empty( trim( $desc ) ) ) : ?>
                        <p class="codo-calendar-desc"><?php echo esc_html( $desc ); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $details_url ); ?>" class="button codo-book-btn">
                        <?php esc_html_e( 'Book Now', 'codobookings' ); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'codo_calendars_grid', 'codobookings_calendars_grid_shortcode' );
