<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register core settings
 */
add_action( 'admin_init', 'codobookings_register_general_settings' );
function codobookings_register_general_settings() {
    register_setting( 'codobookings_options', 'codobookings_default_meeting_app', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'none',
    ]);

    do_action( 'codobookings_register_settings' );
}

/**
 * Settings tabs system
 */
function codobookings_get_settings_tabs() {
    $tabs = [
        'general' => [
            'label'    => __( 'General', 'codobookings' ),
            'callback' => 'codobookings_render_general_settings',
        ],
    ];

    return apply_filters( 'codobookings_settings_tabs', $tabs );
}

/**
 * Settings page renderer
 */
function codobookings_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $tabs       = codobookings_get_settings_tabs();
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
    if ( ! isset( $tabs[ $active_tab ] ) ) $active_tab = 'general';

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'codobookings_messages', 'codobookings_message',
            __( 'Settings saved successfully.', 'codobookings' ), 'updated' );
    }

    settings_errors( 'codobookings_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CodoBookings Settings', 'codobookings' ); ?></h1>

        <h2 class="nav-tab-wrapper">
            <?php foreach ( $tabs as $slug => $tab ) :
                $class = ( $slug === $active_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
                $url   = add_query_arg( [ 'page' => 'codobookings_settings', 'tab' => $slug ], admin_url( 'admin.php' ) );
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
                    <?php echo esc_html( $tab['label'] ); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'codobookings_options' );

                if ( isset( $tabs[ $active_tab ]['callback'] ) && is_callable( $tabs[ $active_tab ]['callback'] ) ) {
                    call_user_func( $tabs[ $active_tab ]['callback'], $active_tab );
                }
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Enqueue admin inline JS for hidden tab input
 */
function codobookings_admin_inline_js( $hook ) {
    if ( $hook !== 'settings_page_codobookings_settings' ) return;

    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

    wp_add_inline_script( 'jquery-core', "
        jQuery(document).ready(function($){
            var form = $('form');
            if(form.length){
                $('<input>').attr({
                    type: 'hidden',
                    name: 'tab',
                    value: '" . esc_js( $active_tab ) . "'
                }).appendTo(form);
            }
        });
    ");
}
add_action( 'admin_enqueue_scripts', 'codobookings_admin_inline_js' );

/**
 * Render General Settings Tab
 */
function codobookings_render_general_settings() {
    ?>
    <table class="form-table">
        <?php
        $fields = [
            'codobookings_default_meeting_app' => function() {
                $meeting_apps = apply_filters( 'codobookings_meeting_apps', [
                    'none' => __( 'None', 'codobookings' ),
                ]);
                $current = get_option( 'codobookings_default_meeting_app', 'none' );
                ?>
                <tr>
                    <th><?php esc_html_e( 'Default meeting app', 'codobookings' ); ?></th>
                    <td>
                        <select name="codobookings_default_meeting_app">
                            <?php foreach ( $meeting_apps as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <br><small><?php esc_html_e( 'Meeting app options can be added through officially supported extensions.', 'codobookings' ); ?></small>
                    </td>
                </tr>
                <?php
            },
        ];

        $fields = apply_filters( 'codobookings_general_settings_fields', $fields );

        foreach ( $fields as $callback ) {
            if ( is_callable( $callback ) ) call_user_func( $callback );
        }
        ?>
    </table>
    <?php
}
