<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register core settings (General tab)
 */
add_action( 'admin_init', 'codobookings_register_general_settings' );
function codobookings_register_general_settings() {
    register_setting( 'codobookings_options', 'codobookings_default_meeting_app', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'google_calendar'
    ]);

    register_setting( 'codobookings_options', 'codobookings_notify_before_minutes', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 30
    ]);
}

/**
 * Core tab registration — can be extended by plugins
 */
function codobookings_get_settings_tabs() {
    $tabs = [
        'general' => [
            'label' => __( 'General', 'codobookings' ),
            'callback' => 'codobookings_render_general_settings',
        ],
    ];

    /**
     * Allow other modules to register their own tabs.
     * Each tab should be added as:
     * $tabs['tab_id'] = ['label' => 'Tab Label', 'callback' => 'function_name'];
     */
    return apply_filters( 'codobookings_settings_tabs', $tabs );
}

/**
 * Settings Page Output (Tabbed)
 */
function codobookings_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $tabs = codobookings_get_settings_tabs();
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    if ( ! isset( $tabs[ $active_tab ] ) ) $active_tab = 'general';

    // Handle settings updated message
    if ( isset($_GET['settings-updated']) ) {
        add_settings_error(
            'codobookings_messages',
            'codobookings_message',
            __( 'Settings saved successfully.', 'codobookings' ),
            'updated'
        );
    }

    settings_errors( 'codobookings_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CodoBookings Settings', 'codobookings' ); ?></h1>

        <h2 class="nav-tab-wrapper">
            <?php foreach ( $tabs as $slug => $tab ) : ?>
                <?php
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

                // Callback for the current tab
                if ( isset( $tabs[ $active_tab ]['callback'] ) && is_callable( $tabs[ $active_tab ]['callback'] ) ) {
                    call_user_func( $tabs[ $active_tab ]['callback'], $active_tab );
                }
            ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
        // Maintain active tab on form submission
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            if (form) {
                const activeTab = '<?php echo esc_js($active_tab); ?>';
                if (activeTab) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tab';
                    input.value = activeTab;
                    form.appendChild(input);
                }
            }
        });
    </script>
    <?php
}

/**
 * Render General Tab
 */
function codobookings_render_general_settings() {
    ?>
    <table class="form-table">
        <tr>
            <th><?php _e( 'Default meeting app', 'codobookings' ); ?></th>
            <td>
                <?php
                // Default apps
                $meeting_apps = array(
                    'none' => __( 'None', 'codobookings' ),
                );

                /**
                 * Filter: codobookings_meeting_apps
                 *
                 * Allow other extensions to register meeting app integrations.
                 *
                 * Example:
                 * add_filter( 'codobookings_meeting_apps', function( $apps ) {
                 *     $apps['google_calendar'] = __( 'Google Meet (via Calendar)', 'codobookings' );
                 *     $apps['zoom'] = __( 'Zoom Meetings', 'codobookings' );
                 *     return $apps;
                 * });
                 */
                $meeting_apps = apply_filters( 'codobookings_meeting_apps', $meeting_apps );

                $current_app = get_option( 'codobookings_default_meeting_app', 'none' );
                ?>
                <select name="codobookings_default_meeting_app" id="codobookings_default_meeting_app">
                    <?php foreach ( $meeting_apps as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_app, $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><?php _e( 'Notify before (minutes)', 'codobookings' ); ?></th>
            <td>
                <input type="number" name="codobookings_notify_before_minutes"
                    value="<?php echo esc_attr( get_option( 'codobookings_notify_before_minutes', 30 ) ); ?>">
            </td>
        </tr>
    </table>
    <?php
}
