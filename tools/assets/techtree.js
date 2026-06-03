// Techtree drag & drop editor
// Globals required: entities (array), prereqs (object)

const typeLabels = { building: 'B', research: 'R', ship: 'S', personell: 'P' };

function setStatus(msg) {
    document.getElementById('status-bar').textContent = msg;
}

function renderEntities() {
    document.querySelectorAll('.cell').forEach(cell => {
        cell.innerHTML = '';
        delete cell.dataset.entityId;
        delete cell.dataset.entityType;
    });
    entities.forEach(e => {
        const cell = document.querySelector(
            `#grid-${e.phase} .cell[data-phase="${e.phase}"][data-row="${e.row}"][data-col="${e.column}"]`
        );
        if (!cell) return;
        const prereqKey  = `${e.entity_type}_${e.id}`;
        const prereqText = prereqs[prereqKey] ?? '';
        const card = document.createElement('div');
        card.className = `entity-card type-${e.entity_type}`;
        card.draggable = true;
        card.dataset.id    = e.id;
        card.dataset.type  = e.entity_type;
        card.dataset.phase = e.phase;
        card.dataset.row   = e.row;
        card.dataset.col   = e.column;
        card.title = prereqText ? `Voraussetzung: ${prereqText}` : '';
        card.innerHTML = `
            <span class="type-badge">${typeLabels[e.entity_type]}</span>
            <span class="entity-name">${e.name}</span>
            ${prereqText ? `<span class="entity-prereq">⬆ ${prereqText}</span>` : ''}
        `;
        card.addEventListener('dragstart', onDragStart);
        cell.appendChild(card);
        cell.dataset.entityId   = e.id;
        cell.dataset.entityType = e.entity_type;
    });
}

let dragging = null;

function onDragStart(e) {
    const card = e.currentTarget;
    dragging = { id: card.dataset.id, type: card.dataset.type, phase: card.dataset.phase, row: card.dataset.row, col: card.dataset.col };
    e.dataTransfer.effectAllowed = 'move';
}

document.querySelectorAll('.cell').forEach(cell => {
    cell.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; cell.classList.add('drag-over'); });
    cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));
    cell.addEventListener('drop', async e => {
        e.preventDefault();
        cell.classList.remove('drag-over');
        if (!dragging) return;
        const newPhase = cell.dataset.phase, newRow = cell.dataset.row, newCol = cell.dataset.col;
        if (dragging.phase === newPhase && dragging.row === newRow && dragging.col === newCol) return;
        const occupied = cell.dataset.entityId ? { id: cell.dataset.entityId, type: cell.dataset.entityType } : null;
        setStatus(`Verschiebe ${dragging.type} #${dragging.id} → Phase ${newPhase} / Zeile ${newRow} / Spalte ${newCol} …`);
        try {
            const res = await fetch('?tab=techtree', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_a: dragging.id, type_a: dragging.type,
                    new_phase: newPhase, new_row: newRow, new_col: newCol,
                    old_phase: dragging.phase, old_row: dragging.row, old_col: dragging.col,
                    id_b: occupied?.id ?? null, type_b: occupied?.type ?? null,
                }),
            });
            const data = await res.json();
            if (data.ok) {
                const a = entities.find(x => String(x.id) === String(dragging.id) && x.entity_type === dragging.type);
                if (a) { a.phase = parseInt(newPhase); a.row = parseInt(newRow); a.column = parseInt(newCol); }
                if (occupied) {
                    const b = entities.find(x => String(x.id) === String(occupied.id) && x.entity_type === occupied.type);
                    if (b) { b.phase = parseInt(dragging.phase); b.row = parseInt(dragging.row); b.column = parseInt(dragging.col); }
                }
                renderEntities();
                setStatus(`✓ Gespeichert — Phase ${newPhase} / Zeile ${newRow} / Spalte ${newCol}`);
            } else {
                setStatus(`✗ Fehler: ${data.error}`);
            }
        } catch (err) {
            setStatus(`✗ Netzwerkfehler: ${err.message}`);
        }
        dragging = null;
    });
});

// Phase tab switching
document.getElementById('phase-tabs')?.addEventListener('click', e => {
    const tab = e.target.closest('.phase-tab');
    if (!tab) return;
    const phase = tab.dataset.phase;
    document.querySelectorAll('.phase-tab').forEach(t  => t.classList.toggle('tt-active', t === tab));
    document.querySelectorAll('.phase-panel').forEach(p => p.classList.toggle('tt-active', p.id === `tt-phase-${phase}`));
    setStatus(`Phase ${phase} aktiv.`);
});

renderEntities();
