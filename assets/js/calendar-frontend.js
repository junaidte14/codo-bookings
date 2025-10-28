(function(){
    const daysOfWeek=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    function el(q,parent){return (parent||document).querySelector(q);}

    function fetchCalendar(id){
        const fd=new FormData();
        fd.append('action','codo_get_calendar');
        fd.append('calendar_id',id);
        fd.append('nonce',CODOBookingsData.nonce);
        return fetch(CODOBookingsData.ajaxUrl,{method:'POST',credentials:'same-origin',body:fd})
            .then(r=>r.json())
            .then(json=>json.success?json.data:Promise.reject(json.data?.message||'Failed to load calendar'));
    }

    function formatTimeToLocal(utcTime){
        if(!utcTime) return '';
        const [h,m]=utcTime.split(':');
        const d=new Date();
        d.setUTCHours(h,m);
        return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    }

    function renderSidebar(slots, label, type, root) {
        if (!Array.isArray(slots)) slots = [slots];

        // --- Make sidebar unique per calendar root ---
        let sidebar = root.querySelector('.codo-calendar-sidebar');
        if (!sidebar) {
            sidebar = document.createElement('div');
            sidebar.className = 'codo-calendar-sidebar';
            sidebar.dataset.calendarId = root.dataset.calendarId; // store calendar ID
            root.appendChild(sidebar);
            // Trigger animation
            requestAnimationFrame(() => {
                sidebar.classList.add('visible');
            });

            // --- Header ---
            const header = document.createElement('div');
            header.className = 'codo-sidebar-header';
            header.innerHTML = '<strong>Booking Details</strong><br><small>Select slots and click Confirm Booking</small>';
            header.style.marginBottom = '10px';
            sidebar.appendChild(header);

            // --- Container for slots ---
            const container = document.createElement('div');
            container.className = 'codo-sidebar-container';
            sidebar.appendChild(container);

            // --- Footer with confirm button ---
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

            // --- Confirm booking click ---
            confirmBtn.addEventListener('click', () => {
                confirmBtn.disabled = true;
                let email = CODOBookingsData.userEmail || '';
                if (!email) {
                    email = prompt('Enter your email to confirm booking:');
                    if (!email) return;
                }

                const slotsToBook = Array.from(container.querySelectorAll('.codo-sidebar-item.selected')).map(item => {
                    let recurrence_day = '';

                    if (type === 'weekly') {
                        recurrence_day = item.dataset.day; // already 'monday', 'tuesday', ...
                    } else {
                        // For one-time booking, convert the date to day of week
                        const dateParts = item.dataset.day.split('-'); // ['2025','10','29']
                        const dt = new Date(dateParts[0], dateParts[1]-1, dateParts[2]); // JS months 0-indexed
                        const daysOfWeek = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                        recurrence_day = daysOfWeek[dt.getDay()]; // get proper lowercase weekday
                    }

                    return {
                        start: item.dataset.start,
                        end: item.dataset.end,
                        day: recurrence_day, // send proper day for backend
                        calendar_id: item.dataset.calendarId
                    };
                });

                //console.log(slotsToBook); return;

                if (!slotsToBook.length) return;

                let successCount = 0;
                let failedCount = 0;

                const bookingPromises = slotsToBook.map(slotData => {
                    let day = '';
                    day = slotData.day;

                    const fd = new FormData();
                    fd.append('action', 'codobookings_create_booking');
                    fd.append('nonce', CODOBookingsData.nonce);
                    fd.append('calendar_id', slotData.calendar_id); // correct calendar
                    fd.append('start', slotData.start);
                    fd.append('end', slotData.end);
                    fd.append('email', email);
                    fd.append('day', day);

                    return fetch(CODOBookingsData.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) successCount++;
                            else failedCount++;
                        })
                        .catch(() => failedCount++);
                });

                Promise.all(bookingPromises).then(() => {
                    container.innerHTML = '';
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
                        container.innerHTML = '';
                        confirmBtn.disabled = true;
                    });

                    messageBox.appendChild(rebookBtn);
                    container.appendChild(messageBox);
                });
            });
        }

        // --- Render slots inside this calendar's sidebar ---
        const container = sidebar.querySelector('.codo-sidebar-container');
        const confirmBtn = sidebar._confirmBtn;

        slots.forEach(slot => {
            const slotKey = `${slot.day}-${slot.start}-${slot.end}`;
            if (container.querySelector(`[data-slot-key="${slotKey}"]`)) return;

            const localStart = formatTimeToLocal(slot.start);
            const localEnd = formatTimeToLocal(slot.end);

            const item = document.createElement('div');
            item.className = 'codo-sidebar-item';
            // Determine if this is weekly or one-time
            const isWeekly = type === 'weekly';
            // For weekly, store HH:MM; for one-time, store full datetime
            if (isWeekly) {
                item.dataset.start = slot.start; // HH:MM
                item.dataset.end   = slot.end;   // HH:MM
            } else {
                // full UTC datetime for one-time slots
                item.dataset.start = label + ' ' + slot.start + ':00'; // YYYY-MM-DD HH:MM:SS
                item.dataset.end   = label + ' ' + slot.end + ':00';
            }
            item.dataset.day = label;
            item.dataset.calendarId = root.dataset.calendarId; // correct calendar
            item.dataset.slotKey = `${slot.day}-${slot.start}-${slot.end}`;

            item.innerHTML = `
                <strong>${type === 'weekly' ? 'Every ' + label : label}</strong><br>
                ${slot.start}-${slot.end} UTC / ${localStart}-${localEnd} Local
                <button class="remove-slot" style="display:none;">Remove</button>
            `;

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

        function updateConfirmButtonState() {
            confirmBtn.disabled = container.querySelectorAll('.codo-sidebar-item.selected').length === 0;
        }
    }


    function renderWeeklyCalendar(root,data){
        const days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        const table=document.createElement('table'); table.className='codo-weekly-calendar';
        const thead=document.createElement('thead'); const headerRow=document.createElement('tr');
        days.forEach(d=>{ const th=document.createElement('th'); th.textContent=d; headerRow.appendChild(th); });
        thead.appendChild(headerRow); table.appendChild(thead);
        const tbody=document.createElement('tbody'); const row=document.createElement('tr');
        days.forEach(day=>{
            const td=document.createElement('td'); td.className='codo-weekly-cell';
            const daySlots=data.slots.filter(s=>s.day.toLowerCase()===day.toLowerCase());
            if(daySlots.length){
                daySlots.forEach(slot=>{
                    const btn=document.createElement('button');
                    btn.className='codo-slot';
                    btn.textContent=`${slot.start}-${slot.end} UTC`;
                    const tooltip=document.createElement('div');
                    tooltip.className='codo-slot-tooltip';
                    tooltip.innerHTML=`Every ${day} ${slot.start}-${slot.end} (UTC)<br>${formatTimeToLocal(slot.start)}-${formatTimeToLocal(slot.end)} (Local)`;
                    btn.appendChild(tooltip);
                    btn.addEventListener('click',()=>renderSidebar(slot,day,'weekly',root));
                    td.appendChild(btn);
                });
            }else td.innerHTML='<span class="codo-no-slot">–</span>';
            row.appendChild(td);
        });
        tbody.appendChild(row); table.appendChild(tbody); root.innerHTML=''; root.appendChild(table);
    }

    function getSlotsForDate(dateStr, data) {
        const dt = new Date(dateStr);
        const dow = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'][dt.getDay()];

        return (data.slots || []).filter(s => s.day.toLowerCase() === dow);
    }


    function renderOneTimeCalendar(root,data,monthOffset=0){
        //console.log(data);
        const now=new Date(); const today=new Date(); today.setHours(0,0,0,0);
        const current=new Date(now.getFullYear(),now.getMonth()+monthOffset,1);
        const year=current.getFullYear(); const month=current.getMonth();
        const header=document.createElement('div'); header.className='codo-calendar-header';
        const prevBtn=document.createElement('button'); prevBtn.textContent='« Prev';
        const nextBtn=document.createElement('button'); nextBtn.textContent='Next »';
        const title=document.createElement('span'); title.textContent=current.toLocaleString('default',{month:'long',year:'numeric'});
        header.append(prevBtn,title,nextBtn); root.innerHTML=''; root.appendChild(header);
        prevBtn.addEventListener('click',()=>renderOneTimeCalendar(root,data,monthOffset-1));
        nextBtn.addEventListener('click',()=>renderOneTimeCalendar(root,data,monthOffset+1));

        const table=document.createElement('table'); table.className='codo-onetime-calendar';
        const trHeader=document.createElement('tr'); ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d=>{ const th=document.createElement('th'); th.textContent=d; trHeader.appendChild(th); });
        table.appendChild(trHeader);
        const firstDay=new Date(year,month,1).getDay();
        const daysInMonth=new Date(year,month+1,0).getDate();
        let dayCount=1;

        for(let w=0; w<6; w++){
            const tr=document.createElement('tr');
            for(let dow=0; dow<7; dow++){
                const td=document.createElement('td'); td.style.position='relative';
                if((w===0 && dow<firstDay) || dayCount>daysInMonth){ td.textContent=''; }
                else{
                    const dateStr=`${year}-${('0'+(month+1)).slice(-2)}-${('0'+dayCount).slice(-2)}`;
                    const daySlots=getSlotsForDate(dateStr,data);
                    //console.log(daySlots);
                    td.innerHTML=`<div class="codo-calendar-date">${dayCount}</div>`;
                    const cellDate=new Date(year,month,dayCount); cellDate.setHours(0,0,0,0);
                    if(cellDate<today) td.classList.add('past');

                    if(daySlots.length && cellDate>=today){
                        td.classList.add('available');
                        const tooltip=document.createElement('div'); tooltip.className='codo-calendar-tooltip';
                        tooltip.innerHTML=daySlots.map(s=>`${s.start}-${s.end} UTC / ${formatTimeToLocal(s.start)}-${formatTimeToLocal(s.end)} Local`).join('<br>');
                        td.appendChild(tooltip);

                        td.addEventListener('click',()=>daySlots.forEach(s=>renderSidebar(s,dateStr,'none',root)));
                    }
                    dayCount++;
                }
                tr.appendChild(td);
            }
            table.appendChild(tr);
        }
        root.appendChild(table);
    }

    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.codo-calendar-wrapper').forEach(root=>{
            const calId=root.dataset.calendarId;
            root.innerHTML=`<div class="codo-calendar-loading">${CODOBookingsData.i18n.loading}</div>`;
            fetchCalendar(calId).then(data=>{
                console.log(data);
                if(data.recurrence==='weekly') renderWeeklyCalendar(root,data);
                else renderOneTimeCalendar(root,data);
            }).catch(err=>{ console.error(err); root.innerHTML=`<div class="codo-calendar-error">${CODOBookingsData.i18n.failed}</div>`; });
        });
    });
})();
