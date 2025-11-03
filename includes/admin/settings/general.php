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

    /**
     * 🔧 Allow other modules to register their own settings.
     * Example usage:
     * add_action( 'codobookings_register_settings', function() {
     *     register_setting( 'codobookings_options', 'my_custom_setting', [
     *         'type' => 'string',
     *         'sanitize_callback' => 'sanitize_text_field',
     *         'default' => '',
     *     ]);
     * });
     */
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            if (form) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tab';
                input.value = '<?php echo esc_js( $active_tab ); ?>';
                form.appendChild(input);
            }
        });
    </script>
    <?php
}

/**
 * Render General Settings Tab
 */
function codobookings_render_general_settings() {
    ?>
    <table class="form-table">
        <?php
        /**
         * Default "General" settings fields
         */
        $fields = [
            'codobookings_default_meeting_app' => function() {
                $meeting_apps = apply_filters( 'codobookings_meeting_apps', [
                    'none' => __( 'None', 'codobookings' ),
                ]);
                $current = get_option( 'codobookings_default_meeting_app', 'none' );
                ?>
                <tr>
                    <th><?php _e( 'Default meeting app', 'codobookings' ); ?></th>
                    <td>
                        <select name="codobookings_default_meeting_app">
                            <?php foreach ( $meeting_apps as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <br><small><?php _e( 'Meeting app options can be added through officially supported extensions.', 'codobookings' ); ?></small>
                    </td>
                </tr>
                <?php
            },

        ];

        /**
         * Allow extensions to register new fields.
         * Example:
         * add_filter( 'codobookings_general_settings_fields', function( $fields ) {
         *     $fields['my_custom_option'] = function() {
         *         ?>
         *         <tr>
         *             <th><?php _e( 'My Custom Option', 'myplugin' ); ?></th>
         *             <td><input type="text" name="my_custom_option" value="<?php echo esc_attr( get_option('my_custom_option') ); ?>"></td>
         *         </tr>
         *         <?php
         *     };
         *     return $fields;
         * });
         */
        $fields = apply_filters( 'codobookings_general_settings_fields', $fields );

        // Render each registered field
        foreach ( $fields as $callback ) {
            if ( is_callable( $callback ) ) call_user_func( $callback );
        }
        ?>
    </table>
    <?php
}
