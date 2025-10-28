// =========================
// File: js/utils.js
// =========================

window.CodoBookings = window.CodoBookings || {};

(function(ns){
    const utils = {
        el(q, parent){ return (parent || document).querySelector(q); },

        formatTimeToLocal(utcTime){
            if(!utcTime) return '';
            const [h, m] = utcTime.split(':');
            const d = new Date();
            // setUTCHours accepts numbers
            d.setUTCHours(parseInt(h,10), parseInt(m,10), 0, 0);
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        // Lowercase week names starting from sunday for getDay() compatibility
        weekDayNamesLower() {
            return ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        },

        // Human-readable days (Monday..Sunday)
        daysOfWeek() {
            return ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        }
    };

    ns.utils = utils;
})(window.CodoBookings);








// =========================
// File: js/main.js
// =========================



// =========================
// Optional: PHP enqueue snippet included below this JS bundle in the same file for convenience.
// Add this to your plugin/theme to enqueue the modular scripts in proper order.

/*
php enqueue example:

function codo_enqueue_scripts(){
    $base = plugin_dir_url(__FILE__) . 'js/';
    wp_enqueue_script('codo-utils', $base . 'utils.js', [], '1.0', true);
    wp_enqueue_script('codo-api', $base . 'api.js', ['codo-utils'], '1.0', true);
    wp_enqueue_script('codo-sidebar', $base . 'sidebar.js', ['codo-utils','codo-api'], '1.0', true);
    wp_enqueue_script('codo-weekly', $base . 'calendar-weekly.js', ['codo-sidebar'], '1.0', true);
    wp_enqueue_script('codo-onetime', $base . 'calendar-onetime.js', ['codo-sidebar'], '1.0', true);
    wp_enqueue_script('codo-main', $base . 'main.js', ['codo-weekly','codo-onetime'], '1.0', true);
}
add_action('wp_enqueue_scripts','codo_enqueue_scripts');
*/
