document.addEventListener('DOMContentLoaded', function() {
    const days = CodoBookingsData?.days || ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

    // ---------- Add Slot ----------
    document.querySelectorAll('.add-slot').forEach(btn => {
        btn.addEventListener('click', () => {
            const daySection = btn.closest('.codo-day-section');
            if (!daySection) return;

            const day = daySection.dataset.day;
            const wrap = daySection.querySelector('.codo-slots-wrap');
            if (!wrap) return;

            const index = wrap.children.length;
            const slotDiv = document.createElement('div');
            slotDiv.classList.add('codo-slot');
            slotDiv.innerHTML = `
                <label>Start</label>
                <input type="time" name="codo_weekly_slots[${day}][${index}][start]" value="" />
                <label>End</label>
                <input type="time" name="codo_weekly_slots[${day}][${index}][end]" value="" />
                <button type="button" class="button remove-slot" aria-label="Remove Slot">×</button>
            `;
            wrap.appendChild(slotDiv);
        });
    });

    // ---------- Remove Slot ----------
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('remove-slot')) return;

        const slotDiv = e.target.closest('.codo-slot');
        if (!slotDiv) return;

        const daySection = e.target.closest('.codo-day-section');
        if (!daySection) return;

        const day = daySection.dataset.day;
        slotDiv.remove();

        // Re-index names
        const slots = daySection.querySelectorAll('.codo-slot');
        slots.forEach((slot, i) => {
            const startInput = slot.querySelector('input[name*="[start]"]');
            const endInput = slot.querySelector('input[name*="[end]"]');
            if (startInput) startInput.name = `codo_weekly_slots[${day}][${i}][start]`;
            if (endInput) endInput.name = `codo_weekly_slots[${day}][${i}][end]`;
        });
    });

    // ---------- Recurrence Boxes ----------
    const boxes = document.querySelectorAll('.codo-recurrence-box');
    const hiddenInput = document.getElementById('codo_recurrence');
    if (boxes.length && hiddenInput) {
        boxes.forEach(box => {
            box.addEventListener('click', () => {
                boxes.forEach(b => b.classList.remove('active'));
                box.classList.add('active');
                hiddenInput.value = box.dataset.value;
            });
        });
    }

    // ---------- Fill Standard 9–5 ----------
    const fillBtn = document.getElementById('fill_standard_hours');
    if (fillBtn) {
        fillBtn.addEventListener('click', () => {
            const standardSlots = [
                { start: '09:00', end: '10:00' },
                { start: '10:00', end: '11:00' },
                { start: '11:00', end: '12:00' },
                { start: '13:00', end: '14:00' },
                { start: '14:00', end: '15:00' },
                { start: '15:00', end: '16:00' },
                { start: '16:00', end: '17:00' },
            ];
            const weekdays = ['monday','tuesday','wednesday','thursday','friday'];
            weekdays.forEach(day => {
                const wrap = document.querySelector(`[data-day="${day}"] .codo-slots-wrap`);
                if (!wrap) return;
                wrap.innerHTML = '';
                standardSlots.forEach((slot, i) => {
                    const newSlot = document.createElement('div');
                    newSlot.classList.add('codo-slot');
                    newSlot.innerHTML = `
                        <label>Start</label>
                        <input type="time" name="codo_weekly_slots[${day}][${i}][start]" value="${slot.start}" />
                        <label>End</label>
                        <input type="time" name="codo_weekly_slots[${day}][${i}][end]" value="${slot.end}" />
                        <button type="button" class="button remove-slot" aria-label="Remove Slot">×</button>
                    `;
                    wrap.appendChild(newSlot);
                });
            });
            alert('Standard 9–5 slots added for Monday to Friday.');
        });
    }

    // ---------- Copy Monday → All Days ----------
    const copyBtn = document.getElementById('copy_monday');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const mondaySlots = document.querySelectorAll('[data-day="monday"] .codo-slot');
            if (!mondaySlots.length) return alert('No slots to copy from Monday.');

            days.forEach(day => {
                if (day === 'monday') return;
                const wrap = document.querySelector(`[data-day="${day}"] .codo-slots-wrap`);
                if (!wrap) return;
                wrap.innerHTML = '';

                mondaySlots.forEach((slotDiv, i) => {
                    const start = slotDiv.querySelector('input[name*="[start]"]')?.value || '';
                    const end = slotDiv.querySelector('input[name*="[end]"]')?.value || '';
                    const newSlot = document.createElement('div');
                    newSlot.classList.add('codo-slot');
                    newSlot.innerHTML = `
                        <label>Start</label>
                        <input type="time" name="codo_weekly_slots[${day}][${i}][start]" value="${start}" />
                        <label>End</label>
                        <input type="time" name="codo_weekly_slots[${day}][${i}][end]" value="${end}" />
                        <button type="button" class="button remove-slot" aria-label="Remove Slot">×</button>
                    `;
                    wrap.appendChild(newSlot);
                });
            });
        });
    }

    // ---------- Export JSON ----------
    const exportBtn = document.getElementById('export-json');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const data = {};
            days.forEach(day => {
                const wrap = document.querySelector(`[data-day="${day}"] .codo-slots-wrap`);
                if (!wrap) return;
                const slots = [];
                wrap.querySelectorAll('.codo-slot').forEach(slotDiv => {
                    const start = slotDiv.querySelector('input[name*="[start]"]')?.value || '';
                    const end = slotDiv.querySelector('input[name*="[end]"]')?.value || '';
                    slots.push({ start, end });
                });
                data[day] = slots;
            });
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'codobookings_slots.json';
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    // ---------- Import JSON ----------
    const importBtn = document.getElementById('import-json');
    const importFile = document.getElementById('import-file');
    if (importBtn && importFile) {
        importBtn.addEventListener('click', () => importFile.click());

        importFile.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const data = JSON.parse(reader.result);
                    days.forEach(day => {
                        const wrap = document.querySelector(`[data-day="${day}"] .codo-slots-wrap`);
                        if (!wrap) return;
                        wrap.innerHTML = '';
                        (data[day] || []).forEach((slot, i) => {
                            const slotDiv = document.createElement('div');
                            slotDiv.classList.add('codo-slot');
                            slotDiv.innerHTML = `
                                <label>Start</label>
                                <input type="time" name="codo_weekly_slots[${day}][${i}][start]" value="${slot.start || ''}" />
                                <label>End</label>
                                <input type="time" name="codo_weekly_slots[${day}][${i}][end]" value="${slot.end || ''}" />
                                <button type="button" class="button remove-slot" aria-label="Remove Slot">×</button>
                            `;
                            wrap.appendChild(slotDiv);
                        });
                    });
                } catch(err) {
                    alert('Invalid JSON file.');
                }
            };
            reader.readAsText(file);
        });
    }
});
