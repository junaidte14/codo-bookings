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
    var_dump($slots);
    $recurrence = get_post_meta( $post->ID, '_codo_recurrence', true ) ?: 'none';
    ?>

    <h4><?php _e( 'Weekly Availability', 'codobookings' ); ?></h4>
    <p><button type="button" class="button" id="copy_monday"><?php _e( 'Copy Monday → All Days', 'codobookings' ); ?></button></p>

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

    <h4><?php _e( 'Recurrence Rule', 'codobookings' ); ?></h4>
    <p>
        <select name="codo_recurrence" id="codo_recurrence">
            <option value="none" <?php selected( $recurrence, 'none' ); ?>><?php _e( 'None (One-time booking)', 'codobookings' ); ?></option>
            <option value="weekly" <?php selected( $recurrence, 'weekly' ); ?>><?php _e( 'Weekly', 'codobookings' ); ?></option>
        </select>
    </p>

    <hr>
    <p>
        <button type="button" id="export-json" class="button"><?php _e( 'Export JSON', 'codobookings' ); ?></button>
        <button type="button" id="import-json" class="button"><?php _e( 'Import JSON', 'codobookings' ); ?></button>
        <input type="file" id="import-file" accept="application/json" style="display:none;">
    </p>

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
                    <label>Capacity</label>
                    <input type="number" name="codo_weekly_slots[${day}][${index}][capacity]" value="1" min="1" />
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
                        const type = input.name.match(/\[(start|end|capacity)\]/)[1];
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
    
    do_action( 'codobookings_calendar_saved', $post_id );
}
