
window.CodoBookings = window.CodoBookings || {};

(function(ns){
    const { formatTimeToLocal } = ns.utils;
    const { api } = ns;

    function renderSidebar(slots, label, type, root){
        if (!Array.isArray(slots)) slots = [slots];

        // --- Make sidebar unique per calendar root ---
        let sidebar = root.querySelector('.codo-calendar-sidebar');
        if (!sidebar) {
            sidebar = document.createElement('div');
            sidebar.className = 'codo-calendar-sidebar';
            sidebar.dataset.calendarId = root.dataset.calendarId;
            root.appendChild(sidebar);
            requestAnimationFrame(() => sidebar.classList.add('visible'));

            const header = document.createElement('div');
            header.className = 'codo-sidebar-header';
            header.innerHTML = '<strong>Booking Details</strong><br><small>Select slots and click Confirm Booking</small>';
            header.style.marginBottom = '10px';
            sidebar.appendChild(header);

            const container = document.createElement('div');
            container.className = 'codo-sidebar-container';
            sidebar.appendChild(container);

            const footer = document.createElement('div');
            footer.className = 'codo-sidebar-footer';
            footer.style.marginTop = '10px';
            const confirmBtn = document.createElement('button');
            confirmBtn.textContent = 'Confirm Booking';
            confirmBtn.style.width = '100%';
            confirmBtn.style.padding = '8px';
            confirmBtn.style.background = '#0073aa';
            confirmBtn.style.color = '#fff';
            confirmBtn.style.border = 'none';
            confirmBtn.style.borderRadius = '4px';
            confirmBtn.style.cursor = 'pointer';
            confirmBtn.disabled = true;
            footer.appendChild(confirmBtn);
            sidebar.appendChild(footer);

            sidebar._confirmBtn = confirmBtn;

            confirmBtn.addEventListener('click', () => {
                confirmBtn.disabled = true;
                let email = window.CODOBookingsData && CODOBookingsData.userEmail || '';
                if (!email){
                    email = prompt('Enter your email to confirm booking:');
                    if (!email) return;
                }

                const containerEl = sidebar.querySelector('.codo-sidebar-container');
                const selectedItems = Array.from(containerEl.querySelectorAll('.codo-sidebar-item.selected'));

                const slotsToBook = selectedItems.map(item => {
                    let recurrence_day = '';
                    if (type === 'weekly') recurrence_day = item.dataset.day; // 'monday', ...
                    else {
                        const dateParts = item.dataset.day.split('-');
                        const dt = new Date(dateParts[0], dateParts[1]-1, dateParts[2]);
                        const dow = ns.utils.weekDayNamesLower()[dt.getDay()];
                        recurrence_day = dow;
                    }

                    return {
                        start: item.dataset.start,
                        end: item.dataset.end,
                        day: recurrence_day,
                        calendar_id: item.dataset.calendarId
                    };
                });

                if (!slotsToBook.length) return;

                let successCount = 0; let failedCount = 0;

                const promises = slotsToBook.map(slotData => {
                    return api.createBooking({
                        calendar_id: slotData.calendar_id,
                        start: slotData.start,
                        end: slotData.end,
                        email: email,
                        day: slotData.day
                    })
                    .then(resp => { if (resp && resp.success) successCount++; else failedCount++; })
                    .catch(() => { failedCount++; });
                });

                Promise.all(promises).then(() => {
                    containerEl.innerHTML = '';
                    confirmBtn.disabled = true;

                    const messageBox = document.createElement('div');
                    messageBox.className = 'codo-booking-message';
                    messageBox.style.padding = '15px';
                    messageBox.style.textAlign = 'center';
                    messageBox.style.background = '#e6f4ff';
                    messageBox.style.border = '1px solid #0073aa';
                    messageBox.style.borderRadius = '6px';

                    const msg = [];
                    if (successCount) msg.push(`Booking confirmed for ${successCount} slot(s)!`);
                    if (failedCount) msg.push(`${failedCount} slot(s) could not be booked.`);

                    messageBox.innerHTML = `<p>${msg.join('<br>')}</p>`;

                    const rebookBtn = document.createElement('button');
                    rebookBtn.textContent = 'Book Again';
                    rebookBtn.style.marginTop = '10px';
                    rebookBtn.style.padding = '8px 12px';
                    rebookBtn.style.background = '#0073aa';
                    rebookBtn.style.color = '#fff';
                    rebookBtn.style.border = 'none';
                    rebookBtn.style.borderRadius = '4px';
                    rebookBtn.style.cursor = 'pointer';

                    rebookBtn.addEventListener('click', () => {
                        containerEl.innerHTML = '';
                        confirmBtn.disabled = true;
                    });

                    messageBox.appendChild(rebookBtn);
                    containerEl.appendChild(messageBox);
                });
            });
        }

        const container = sidebar.querySelector('.codo-sidebar-container');
        const confirmBtn = sidebar._confirmBtn;

        slots.forEach(slot => {
            const slotKey = `${slot.day}-${slot.start}-${slot.end}`;
            if (container.querySelector(`[data-slot-key="${slotKey}"]`)) return;

            const localStart = formatTimeToLocal(slot.start);
            const localEnd = formatTimeToLocal(slot.end);

            const item = document.createElement('div');
            item.className = 'codo-sidebar-item';
            const isWeekly = type === 'weekly';

            if (isWeekly){
                item.dataset.start = slot.start;
                item.dataset.end = slot.end;
            } else {
                item.dataset.start = label + ' ' + slot.start + ':00';
                item.dataset.end = label + ' ' + slot.end + ':00';
            }

            item.dataset.day = label;
            item.dataset.calendarId = root.dataset.calendarId;
            item.dataset.slotKey = slotKey;

            item.innerHTML = `\n                <strong>${type === 'weekly' ? 'Every ' + label : label}</strong><br>\n                ${slot.start}-${slot.end} UTC / ${localStart}-${localEnd} Local\n                <button class="remove-slot" style="display:none;">Remove</button>\n            `;

            const removeBtn = item.querySelector('.remove-slot');

            item.addEventListener('click', () => {
                const selected = item.classList.toggle('selected');
                removeBtn.style.display = selected ? 'inline-block' : 'none';
                updateConfirmButtonState();
            });

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                item.classList.remove('selected');
                removeBtn.style.display = 'none';
                updateConfirmButtonState();
            });

            container.appendChild(item);
        });

        function updateConfirmButtonState(){
            confirmBtn.disabled = container.querySelectorAll('.codo-sidebar-item.selected').length === 0;
        }
    }

    ns.sidebar = ns.sidebar || {};
    ns.sidebar.renderSidebar = renderSidebar;
})(window.CodoBookings);
