// Cantina Hotspot Editor
// Globals required: hotspots (object), characterSlugs (array)

(function () {
    const slotColors = { spot_0: '#1d6ef5', spot_1: '#c0392b', spot_2: '#27ae60', spot_3: '#e67e22', spot_4: '#8e44ad', spot_5: '#16a085' };
    const slotLabels = { spot_0: 'Spot 0', spot_1: 'Spot 1', spot_2: 'Spot 2', spot_3: 'Spot 3', spot_4: 'Spot 4', spot_5: 'Spot 5' };
    let activeDevice = 'desktop';
    let activeSlot   = 'spot_0';

    const canvas     = document.getElementById('hs-canvas');
    const img        = document.getElementById('hs-image');
    const saveBtn    = document.getElementById('hs-save-btn');
    const saveStatus = document.getElementById('hs-save-status');
    const cursorPos  = document.getElementById('hs-cursor-pos');

    if (!canvas) return;

    function renderDots() {
        canvas.querySelectorAll('.hs-dot').forEach(d => d.remove());
        for (const [slot, data] of Object.entries(hotspots)) {
            const pos = data[activeDevice];
            if (!pos) continue;
            const dot = document.createElement('div');
            dot.className = 'hs-dot' + (slot === activeSlot ? ' hs-dot--active' : '');
            dot.dataset.slot = slot;
            dot.style.left = pos.left + '%';
            dot.style.top  = pos.top  + '%';
            dot.textContent = slot.replace('spot_', '');
            const lbl = document.createElement('span');
            lbl.className = 'hs-dot-label';
            lbl.textContent = (slotLabels[slot] ?? slot) + ' ' + pos.left + '%, ' + pos.top + '%';
            dot.appendChild(lbl);
            canvas.appendChild(dot);
        }
    }

    canvas.addEventListener('click', e => {
        if (e.target === img || e.target === canvas) {
            const rect = img.getBoundingClientRect();
            const left = Math.round(((e.clientX - rect.left) / rect.width)  * 100 * 100) / 100;
            const top  = Math.round(((e.clientY - rect.top)  / rect.height) * 100 * 100) / 100;
            hotspots[activeSlot][activeDevice] = { left, top };
            renderDots();
        }
    });

    canvas.addEventListener('mousemove', e => {
        const rect = img.getBoundingClientRect();
        const left = Math.round(((e.clientX - rect.left) / rect.width)  * 100 * 100) / 100;
        const top  = Math.round(((e.clientY - rect.top)  / rect.height) * 100 * 100) / 100;
        if (cursorPos) cursorPos.textContent = `cursor: ${left}%, ${top}%`;
    });

    document.getElementById('hs-device-group')?.addEventListener('click', e => {
        const btn = e.target.closest('.hs-btn[data-device]');
        if (!btn) return;
        activeDevice = btn.dataset.device;
        document.querySelectorAll('#hs-device-group .hs-btn').forEach(b => b.classList.toggle('active', b === btn));
        renderDots();
    });

    document.getElementById('hs-slot-group')?.addEventListener('click', e => {
        const btn = e.target.closest('.hs-btn[data-slot]');
        if (!btn) return;
        activeSlot = btn.dataset.slot;
        document.querySelectorAll('#hs-slot-group .hs-btn').forEach(b => b.classList.toggle('slot-active', b === btn));
        renderDots();
        updateTableActiveColumn();
    });

    saveBtn?.addEventListener('click', async () => {
        saveStatus.textContent = 'Saving…';
        saveStatus.className = '';
        try {
            const res = await fetch('?tab=cantina', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(hotspots),
            });
            const data = await res.json();
            if (data.ok) {
                saveStatus.textContent = '✓ Saved';
                saveStatus.className = 'ok';
            } else {
                saveStatus.textContent = '✗ Error';
                saveStatus.className = 'err';
            }
        } catch {
            saveStatus.textContent = '✗ Network error';
            saveStatus.className = 'err';
        }
        setTimeout(() => { saveStatus.textContent = ''; }, 3000);
    });

    function renderCharAssignment() {
        const table = document.getElementById('hs-matrix');
        if (!table) return;
        const spots = Object.keys(hotspots);

        let html = '<thead><tr><th class="col-char">Character</th>';
        for (const spot of spots) {
            const c = slotColors[spot] ?? '#888';
            html += `<th data-spot="${spot}" style="border-top:3px solid ${c}"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${c};margin-right:4px;vertical-align:middle"></span>${spot.replace('spot_', '')}</th>`;
        }
        html += '</tr></thead><tbody>';

        for (const slug of characterSlugs) {
            const hasAny = spots.some(s => (hotspots[s].characters ?? []).includes(slug));
            html += `<tr class="${hasAny ? '' : 'row-unassigned'}" data-slug="${slug}">`;
            html += `<td class="col-char">${slug}</td>`;
            for (const spot of spots) {
                const checked = (hotspots[spot].characters ?? []).includes(slug);
                html += `<td data-spot="${spot}"><input type="checkbox" data-spot="${spot}" data-slug="${slug}"${checked ? ' checked' : ''}></td>`;
            }
            html += '</tr>';
        }
        html += '</tbody>';
        table.innerHTML = html;

        table.addEventListener('change', e => {
            const cb = e.target;
            if (cb.type !== 'checkbox') return;
            const { spot, slug } = cb.dataset;
            if (!hotspots[spot].characters) hotspots[spot].characters = [];
            if (cb.checked) {
                if (!hotspots[spot].characters.includes(slug)) hotspots[spot].characters.push(slug);
            } else {
                hotspots[spot].characters = hotspots[spot].characters.filter(s => s !== slug);
            }
            const row = cb.closest('tr');
            const hasAny = spots.some(s => (hotspots[s].characters ?? []).includes(row.dataset.slug));
            row.classList.toggle('row-unassigned', !hasAny);
        });
    }

    function updateTableActiveColumn() {
        const table = document.getElementById('hs-matrix');
        if (!table) return;
        table.querySelectorAll('th[data-spot], td[data-spot]').forEach(el => {
            el.classList.toggle('col-active', el.dataset.spot === activeSlot);
        });
    }

    // init slot button border colors
    document.querySelectorAll('#hs-slot-group .hs-btn[data-slot]').forEach(btn => {
        const c = slotColors[btn.dataset.slot];
        if (c) btn.style.borderLeft = `3px solid ${c}`;
    });

    img.addEventListener('load', renderDots);
    if (img.complete) renderDots();
    renderCharAssignment();
    updateTableActiveColumn();
})();
