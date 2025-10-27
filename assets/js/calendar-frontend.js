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

    function renderSidebar(slot,label,type){
        let sidebar=el('.codo-calendar-sidebar');
        if(!sidebar){
            sidebar=document.createElement('div');
            sidebar.className='codo-calendar-sidebar';
            document.body.appendChild(sidebar);
        }
        const localStart=formatTimeToLocal(slot.start);
        const localEnd=formatTimeToLocal(slot.end);
        const item=document.createElement('div');
        item.className='codo-sidebar-item';
        item.innerHTML=`<strong>${type==='weekly'?'Every '+label:label}</strong><br>${slot.start}-${slot.end} UTC / ${localStart}-${localEnd} Local<button class="remove-slot">Remove</button>`;
        item.querySelector('.remove-slot').addEventListener('click',()=>item.remove());
        sidebar.appendChild(item);
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
                    btn.addEventListener('click',()=>renderSidebar(slot,day,'weekly'));
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

        return (data.slots || []).filter(s => {
            if (s.recurrence === 'weekly') {
                return s.day.toLowerCase() === dow;
            }
            if (s.recurrence === 'none') {
                // For one-time bookings, match day of week instead of exact date
                return s.day.toLowerCase() === dow;
            }
            return false;
        });
    }


    function renderOneTimeCalendar(root,data,monthOffset=0){
        console.log(data);
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
                    console.log(daySlots);
                    td.innerHTML=`<div class="codo-calendar-date">${dayCount}</div>`;
                    const cellDate=new Date(year,month,dayCount); cellDate.setHours(0,0,0,0);
                    if(cellDate<today) td.classList.add('past');

                    if(daySlots.length && cellDate>=today){
                        td.classList.add('available');
                        const tooltip=document.createElement('div'); tooltip.className='codo-calendar-tooltip';
                        tooltip.innerHTML=daySlots.map(s=>`${s.start}-${s.end} UTC / ${formatTimeToLocal(s.start)}-${formatTimeToLocal(s.end)} Local`).join('<br>');
                        td.appendChild(tooltip);

                        td.addEventListener('click',()=>daySlots.forEach(s=>renderSidebar(s,dateStr,'none')));
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
                if(data.recurrence==='weekly') renderWeeklyCalendar(root,data);
                else renderOneTimeCalendar(root,data);
            }).catch(err=>{ console.error(err); root.innerHTML=`<div class="codo-calendar-error">${CODOBookingsData.i18n.failed}</div>`; });
        });
    });
})();
