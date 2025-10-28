// Entry point: wire everything together
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.codo-calendar-wrapper').forEach(root => {
        const calId = root.dataset.calendarId;
        root.innerHTML = `<div class="codo-calendar-loading">${window.CODOBookingsData && CODOBookingsData.i18n && CODOBookingsData.i18n.loading || 'Loading...'}</div>`;

        window.CodoBookings.api.fetchCalendar(calId)
            .then(data => {
                if (data.recurrence === 'weekly') window.CodoBookings.renderWeeklyCalendar(root, data);
                else window.CodoBookings.renderOneTimeCalendar(root, data);
            })
            .catch(err => { console.error(err); root.innerHTML = `<div class="codo-calendar-error">${window.CODOBookingsData && CODOBookingsData.i18n && CODOBookingsData.i18n.failed || 'Failed to load calendar'}</div>`; });
    });
});
