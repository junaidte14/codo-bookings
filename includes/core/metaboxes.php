<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'codobookings_add_calendar_meta' );
function codobookings_add_calendar_meta() {
    add_meta_box(
        'codo_calendar_settings',
        __( 'Calendar Settings', 'codobookings' ),
        'codobookings_calendar_settings_cb',
        'codo_calendar',
        'normal',
        'high'
    );
}

function codobookings_calendar_settings_cb( $post ) {
    wp_nonce_field( 'codobookings_save_calendar', 'codobookings_calendar_nonce' );
    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
    $slots = get_post_meta( $post->ID, '_codo_weekly_slots', true ) ?: array_fill_keys($days, array());
    //var_dump($slots);
    $recurrence = get_post_meta( $post->ID, '_codo_recurrence', true ) ?: 'none';
    ?>

    <h4><?php _e( 'Weekly Availability', 'codobookings' ); ?></h4>
    <p>
        <button type="button" class="button" id="copy_monday"><?php _e( 'Copy Monday → All Days', 'codobookings' ); ?></button>
        <button type="button" id="export-json" class="button"><?php _e( 'Export JSON', 'codobookings' ); ?></button>
        <button type="button" id="import-json" class="button"><?php _e( 'Import JSON', 'codobookings' ); ?></button>
        <input type="file" id="import-file" accept="application/json" style="display:none;">
    </p>
    
    <div id="weekly-slots">
        <?php foreach ( $days as $day ) : ?>
            <div class="codo-day-section" data-day="<?php echo esc_attr( $day ); ?>">
                <h4 style="margin-bottom:5px;"><?php echo ucfirst( $day ); ?></h4>
                <div class="codo-slots-wrap">
                    <?php if ( ! empty( $slots[$day] ) ) :
                        foreach ( $slots[$day] as $i => $slot ) : ?>
                            <div class="codo-slot">
                                <label><?php _e( 'Start', 'codobookings' ); ?></label>
                                <input type="time" name="codo_weekly_slots[<?php echo esc_attr( $day ); ?>][<?php echo $i; ?>][start]" value="<?php echo esc_attr( $slot['start'] ?? '' ); ?>" />
                                <label><?php _e( 'End', 'codobookings' ); ?></label>
                                <input type="time" name="codo_weekly_slots[<?php echo esc_attr( $day ); ?>][<?php echo $i; ?>][end]" value="<?php echo esc_attr( $slot['end'] ?? '' ); ?>" />
                                <button type="button" class="button remove-slot">×</button>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
                <button type="button" class="button add-slot"><?php _e( 'Add Slot', 'codobookings' ); ?></button>
            </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <h4><?php _e( 'Calendar Type', 'codobookings' ); ?></h4>
    <p>
        <?php
        // Default recurrence types
        $recurrence_types = array(
            'none'   => __( 'One-time Booking', 'codobookings' ),
            'weekly' => __( 'Weekly (Booking Repeats Every Week)', 'codobookings' ),
        );

        /**
         * Filter: codobookings_recurrence_types
         * 
         * Allows extensions to register new calendar recurrence types.
         * 
         * Example:
         * add_filter('codobookings_recurrence_types', function($types){
         *     $types['monthly'] = __('Monthly', 'myaddon');
         *     return $types;
         * });
         */
        $recurrence_types = apply_filters( 'codobookings_recurrence_types', $recurrence_types );
        ?>
        <select name="codo_recurrence" id="codo_recurrence">
            <?php foreach ( $recurrence_types as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $recurrence, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <?php 
    // Allow extensions to add more fields
    do_action( 'codobookings_calendar_settings_after', $post );
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        // Add Slot button
        document.querySelectorAll('.add-slot').forEach(btn => {
            btn.addEventListener('click', e => {
                const daySection = btn.closest('.codo-day-section');
                const day = daySection.dataset.day;
                const wrap = daySection.querySelector('.codo-slots-wrap');
                const index = wrap.children.length;

                const slotDiv = document.createElement('div');
                slotDiv.classList.add('codo-slot');
                slotDiv.innerHTML = `
                    <label>Start</label>
                    <input type="time" name="codo_weekly_slots[${day}][${index}][start]" value="" />
                    <label>End</label>
                    <input type="time" name="codo_weekly_slots[${day}][${index}][end]" value="" />
                    <button type="button" class="button remove-slot">×</button>
                `;
                wrap.appendChild(slotDiv);
            });
        });

        // Remove Slot
        document.addEventListener('click', function(e){
            if(e.target.classList.contains('remove-slot')){
                const slotDiv = e.target.closest('.codo-slot');
                slotDiv.remove();
                // Re-index names
                const daySection = e.target.closest('.codo-day-section');
                const day = daySection.dataset.day;
                daySection.querySelectorAll('.codo-slot').forEach((slot, i) => {
                    slot.querySelectorAll('input').forEach(input => {
                        const type = input.name.match(/\[(start|end)\]/)[1];
                        input.name = `codo_weekly_slots[${day}][${i}][${type}]`;
                    });
                });
            }
        });

        // Copy Monday → All Days
        document.getElementById('copy_monday').addEventListener('click', function(){
            const mondaySlots = document.querySelectorAll('[data-day="monday"] .codo-slot');
            if(!mondaySlots.length) return alert('No slots to copy from Monday.');

            days.forEach(day => {
                if(day==='monday') return;
                const wrap = document.querySelector(`[data-day="${day}"] .codo-slots-wrap`);
                wrap.innerHTML = '';
                mondaySlots.forEach((slotDiv, i) => {
                    const start = slotDiv.querySelector('input[name*="[start]"]').value;
                    const end = slotDiv.querySelector('input[name*="[end]"]').value;
                   
                    const newSlot = document.createElement('div');
                    newSlot.classList.add('codo-slot');
                    newSlot.innerHTML = `
                        <label>Start</label>
                        <input type="time" name="codo_weekly_slots[${day}][${i}][start]" value="${start}" />
                        <label>End</label>
                        <input type="time" name="codo_weekly_slots[${day}][${i}][end]" value="${end}" />
                        <button type="button" class="button remove-slot">×</button>
                    `;
                    wrap.appendChild(newSlot);
                });
            });
        });

        // Export JSON
        document.getElementById('export-json').addEventListener('click', () => {
            const data = {};
            days.forEach(day => {
                const slots = [];
                document.querySelectorAll(`[data-day="${day}"] .codo-slot`).forEach(slotDiv => {
                    slots.push({
                        start: slotDiv.querySelector('input[name*="[start]"]').value,
                        end: slotDiv.querySelector('input[name*="[end]"]').value,
                    });
                });
                data[day] = slots;
            });
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "codobookings_slots.json";
            a.click();
            URL.revokeObjectURL(url);
        });

        // Import JSON
        document.getElementById('import-json').addEventListener('click', () => {
            document.getElementById('import-file').click();
        });

        document.getElementById('import-file').addEventListener('change', e => {
            const file = e.target.files[0];
            if(!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const data = JSON.parse(reader.result);
                    days.forEach(day => {
                        const wrap = document.querySelector(`[data-day="${day}"] .codo-slots-wrap`);
                        wrap.innerHTML = '';
                        if(data[day]){
                            data[day].forEach((slot, i)=>{
                                const slotDiv = document.createElement('div');
                                slotDiv.classList.add('codo-slot');
                                slotDiv.innerHTML = `
                                    <label>Start</label>
                                    <input type="time" name="codo_weekly_slots[${day}][${i}][start]" value="${slot.start}" />
                                    <label>End</label>
                                    <input type="time" name="codo_weekly_slots[${day}][${i}][end]" value="${slot.end}" />
                                    <button type="button" class="button remove-slot">×</button>
                                `;
                                wrap.appendChild(slotDiv);
                            });
                        }
                    });
                } catch(err){
                    alert('Invalid JSON');
                }
            };
            reader.readAsText(file);
        });

    });
    </script>
    <?php
}

add_action( 'save_post', 'codobookings_save_calendar_meta', 10, 2 );
function codobookings_save_calendar_meta( $post_id, $post ) {
    if ( $post->post_type !== 'codo_calendar' ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['codobookings_calendar_nonce'] ) || ! wp_verify_nonce( $_POST['codobookings_calendar_nonce'], 'codobookings_save_calendar' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $slots = $_POST['codo_weekly_slots'] ?? array();
    // sanitize slots
    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
    foreach($days as $day){
        if(isset($slots[$day])){
            foreach($slots[$day] as &$slot){
                $slot['start'] = sanitize_text_field($slot['start'] ?? '');
                $slot['end'] = sanitize_text_field($slot['end'] ?? '');
            }
        } else {
            $slots[$day] = array();
        }
    }

    update_post_meta( $post_id, '_codo_weekly_slots', $slots );
    update_post_meta( $post_id, '_codo_recurrence', sanitize_text_field( $_POST['codo_recurrence'] ?? 'none' ) );

    if ( isset( $_POST['codo_confirmation_message'] ) ) {
        update_post_meta(
            $post_id,
            '_codo_confirmation_message',
            sanitize_textarea_field( $_POST['codo_confirmation_message'] )
        );
    }
    
    do_action( 'codobookings_calendar_saved', $post_id );
}

add_action( 'codobookings_calendar_settings_after', 'codobookings_add_confirmation_message_field' );
function codobookings_add_confirmation_message_field( $post ) {
    $message = get_post_meta( $post->ID, '_codo_confirmation_message', true );
    if ( empty( $message ) ) {
        $message = __( 'Your booking has been confirmed successfully! Our team will soon contact you with further details. Thank you for choosing us.', 'codobookings' );
    }
    ?>
    <hr>
    <h4><?php _e( 'Confirmation Message', 'codobookings' ); ?></h4>
    <p>
        <textarea name="codo_confirmation_message" rows="3" style="width:100%;"><?php echo esc_textarea( $message ); ?></textarea>
        <small><?php _e( 'This message will be shown to the user after they confirm their booking.', 'codobookings' ); ?></small>
    </p>
    <?php
}


/**
 * Add Sidebar Meta Box for Calendar Settings
 */
add_action( 'add_meta_boxes', 'codobookings_add_sidebar_settings_meta' );
function codobookings_add_sidebar_settings_meta() {
    add_meta_box(
        'codo_calendar_sidebar_settings',
        __( 'Calendar Settings', 'codobookings' ),
        'codobookings_sidebar_settings_cb',
        'codo_calendar',
        'side',
        'default'
    );
}

/**
 * Render Sidebar Settings Meta Box
 */
function codobookings_sidebar_settings_cb( $post ) {
    wp_nonce_field( 'codobookings_save_sidebar_settings', 'codobookings_sidebar_nonce' );

    // Default settings
    $settings = wp_parse_args(
        get_post_meta( $post->ID, '_codo_sidebar_settings', true ),
        array(
            'show_title'        => 'yes',
            'allow_guest'       => 'no',
        )
    );

    // Allow other developers to add extra settings fields
    $extra_fields = apply_filters( 'codobookings_sidebar_settings_fields', array() );
    ?>

    <p>
        <label>
            <input type="checkbox" name="codo_sidebar_settings[show_title]" value="yes" <?php checked( $settings['show_title'], 'yes' ); ?> />
            <?php _e( 'Show Title', 'codobookings' ); ?>
        </label>
        <p class="description"><?php _e( 'Toggle to display the calendar title on the frontend.', 'codobookings' ); ?></p>
    </p>

    <p>
        <label>
            <input type="checkbox" name="codo_sidebar_settings[allow_guest]" value="yes" <?php checked( $settings['allow_guest'], 'yes' ); ?> />
            <?php _e( 'Allow Guest Bookings', 'codobookings' ); ?>
        </label>
        <p class="description"><?php _e( 'Allow users who are not logged in to make bookings with only providing their email address.', 'codobookings' ); ?></p>
    </p>

    <?php
    // Output extra fields added via hooks
    if( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {
        foreach( $extra_fields as $field ) {
            echo '<p>' . $field . '</p>';
        }
    }
}

/**
 * Save Sidebar Settings
 */
add_action( 'save_post', 'codobookings_save_sidebar_settings', 20, 2 );
function codobookings_save_sidebar_settings( $post_id, $post ) {
    if ( $post->post_type !== 'codo_calendar' ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['codobookings_sidebar_nonce'] ) || ! wp_verify_nonce( $_POST['codobookings_sidebar_nonce'], 'codobookings_save_sidebar_settings' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $settings = $_POST['codo_sidebar_settings'] ?? array();
    
    // Sanitize each known setting
    $sanitized = array(
        'show_title'       => isset( $settings['show_title'] ) && $settings['show_title'] === 'yes' ? 'yes' : 'no',
        'allow_guest'      => isset( $settings['allow_guest'] ) && $settings['allow_guest'] === 'yes' ? 'yes' : 'no',
    );

    /**
     * Allow developers to sanitize/modify settings before saving
     */
    $sanitized = apply_filters( 'codobookings_sidebar_settings_sanitize', $sanitized, $post_id );

    update_post_meta( $post_id, '_codo_sidebar_settings', $sanitized );
}
