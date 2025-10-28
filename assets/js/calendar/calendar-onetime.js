window.CodoBookings = window.CodoBookings || {};

(function(ns){
    const { formatTimeToLocal } = ns.utils;
    const renderSidebar = ns.sidebar && ns.sidebar.renderSidebar;

    function getSlotsForDate(dateStr, data){
        const dt = new Date(dateStr);
        const dow = ns.utils.weekDayNamesLower()[dt.getDay()];
        return (data.slots || []).filter(s => s.day.toLowerCase() === dow);
    }

    function renderOneTimeCalendar(root, data, monthOffset = 0){
        const now = new Date();
        const today = new Date();
        today.setHours(0,0,0,0);

        const current = new Date(now.getFullYear(), now.getMonth() + monthOffset, 1);
        const year = current.getFullYear();
        const month = current.getMonth();
        // firstDay adjusted: Monday = 0
        const firstDay = (new Date(year, month, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const header = document.createElement('div');
        header.className = 'codo-calendar-header';
        const prevBtn = document.createElement('button'); prevBtn.textContent = '« Prev';
        const nextBtn = document.createElement('button'); nextBtn.textContent = 'Next »';
        const title = document.createElement('span');
        title.textContent = current.toLocaleString('default', { month: 'long', year: 'numeric' });
        header.append(prevBtn, title, nextBtn);
        root.innerHTML = ''; root.appendChild(header);

        prevBtn.addEventListener('click', () => renderOneTimeCalendar(root, data, monthOffset - 1));
        nextBtn.addEventListener('click', () => renderOneTimeCalendar(root, data, monthOffset + 1));

        const table = document.createElement('table'); table.className = 'codo-onetime-calendar';
        const trHeader = document.createElement('tr');
        ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(d => { const th = document.createElement('th'); th.textContent = d; trHeader.appendChild(th); });
        table.appendChild(trHeader);

        for (let w = 0; w < 6; w++){
            const tr = document.createElement('tr');
            for (let dow = 0; dow < 7; dow++){
                const td = document.createElement('td'); td.style.position = 'relative';
                const cellIndex = w * 7 + dow;
                const dayNumber = cellIndex - firstDay + 1;

                if (dayNumber < 1 || dayNumber > daysInMonth){ td.textContent = ''; }
                else {
                    const dateStr = `${year}-${('0'+(month+1)).slice(-2)}-${('0'+dayNumber).slice(-2)}`;
                    const daySlots = getSlotsForDate(dateStr, data);
                    td.innerHTML = `<div class="codo-calendar-date">${dayNumber}</div>`;

                    const cellDate = new Date(year, month, dayNumber);
                    cellDate.setHours(0,0,0,0);

                    //if (cellDate < today) td.classList.add('past');
                    // Mark past days (before today) or days with no slots as 'past'
                    if (cellDate < today || daySlots.length === 0) td.classList.add('past');

                    if (daySlots.length && cellDate >= today){
                        td.classList.add('available');
                        const tooltip = document.createElement('div');
                        tooltip.className = 'codo-calendar-tooltip';
                        tooltip.innerHTML = daySlots.map(s => `${s.start}-${s.end} UTC / ${formatTimeToLocal(s.start)}-${formatTimeToLocal(s.end)} Local`).join('<br>');
                        td.appendChild(tooltip);

                        td.addEventListener('click', () => daySlots.forEach(s => renderSidebar(s, dateStr, 'none', root)));
                    }
                }
                tr.appendChild(td);
            }
            table.appendChild(tr);
        }

        root.appendChild(table);
    }

    ns.renderOneTimeCalendar = renderOneTimeCalendar;
})(window.CodoBookings);
