window.CodoBookings = window.CodoBookings || {};

(function(ns){
    const { formatTimeToLocal } = ns.utils;
    const renderSidebar = ns.sidebar && ns.sidebar.renderSidebar;

    function renderWeeklyCalendar(root, data){
        const days = ns.utils.daysOfWeek();
        const table = document.createElement('table'); table.className = 'codo-weekly-calendar';
        const thead = document.createElement('thead'); const headerRow = document.createElement('tr');
        days.forEach(d => { const th = document.createElement('th'); th.textContent = d; headerRow.appendChild(th); });
        thead.appendChild(headerRow); table.appendChild(thead);
        const tbody = document.createElement('tbody'); const row = document.createElement('tr');

        days.forEach(day => {
            const td = document.createElement('td'); td.className = 'codo-weekly-cell';
            const daySlots = (data.slots || []).filter(s => s.day.toLowerCase() === day.toLowerCase());
            if (daySlots.length) {
                daySlots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.className = 'codo-slot';
                    btn.textContent = `${slot.start}-${slot.end} UTC`;
                    const tooltip = document.createElement('div');
                    tooltip.className = 'codo-slot-tooltip';
                    tooltip.innerHTML = `Every ${day} ${slot.start}-${slot.end} (UTC)<br>${formatTimeToLocal(slot.start)}-${formatTimeToLocal(slot.end)} (Local)`;
                    btn.appendChild(tooltip);
                    btn.addEventListener('click', () => renderSidebar(slot, day, 'weekly', root));
                    td.appendChild(btn);
                });
            } else td.innerHTML = '<span class="codo-no-slot">–</span>';
            row.appendChild(td);
        });

        tbody.appendChild(row); table.appendChild(tbody); root.innerHTML = ''; root.appendChild(table);
    }

    ns.renderWeeklyCalendar = renderWeeklyCalendar;
})(window.CodoBookings);
