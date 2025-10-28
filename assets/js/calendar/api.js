window.CodoBookings = window.CodoBookings || {};

(function(ns){
    const api = {
        fetchCalendar(id){
            const fd = new FormData();
            fd.append('action','codo_get_calendar');
            fd.append('calendar_id', id);
            fd.append('nonce', window.CODOBookingsData && CODOBookingsData.nonce);

            return fetch(window.CODOBookingsData.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(r => r.json())
                .then(json => json.success ? json.data : Promise.reject(json.data && json.data.message || 'Failed to load calendar'));
        },

        createBooking(payload){
            const fd = new FormData();
            fd.append('action', 'codobookings_create_booking');
            fd.append('nonce', window.CODOBookingsData && CODOBookingsData.nonce);
            fd.append('calendar_id', payload.calendar_id);
            fd.append('start', payload.start);
            fd.append('end', payload.end);
            fd.append('email', payload.email);
            fd.append('day', payload.day);

            return fetch(window.CODOBookingsData.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(r => r.json());
        }
    };

    ns.api = api;
})(window.CodoBookings);