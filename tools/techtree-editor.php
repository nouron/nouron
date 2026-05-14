<?php
// Techtree Editor — local dev tool for repositioning techtree entities via drag & drop.
// Usage: php -S localhost:8081 tools/techtree-editor.php
// Then open: http://localhost:8081

$dbPath = __DIR__ . '/../data/db/nouron.db';

$allowed = [
    'building'  => 'buildings',
    'research'  => 'researches',
    'ship'      => 'ships',
    'personell' => 'personell',
];

// ── POST: update position (swap) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($allowed[$input['type_a'] ?? ''])) {
        echo json_encode(['ok' => false, 'error' => 'invalid type_a']);
        exit;
    }
    if (!empty($input['id_b']) && !isset($allowed[$input['type_b'] ?? ''])) {
        echo json_encode(['ok' => false, 'error' => 'invalid type_b']);
        exit;
    }

    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        $db->prepare("UPDATE {$allowed[$input['type_a']]} SET phase=?, row=?, column=? WHERE id=?")
           ->execute([$input['new_phase'], $input['new_row'], $input['new_col'], $input['id_a']]);

        if (!empty($input['id_b'])) {
            $db->prepare("UPDATE {$allowed[$input['type_b']]} SET phase=?, row=?, column=? WHERE id=?")
               ->execute([$input['old_phase'], $input['old_row'], $input['old_col'], $input['id_b']]);
        }

        $db->commit();
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── GET: load entities ───────────────────────────────────────────────────────
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$entities = [];
foreach ($allowed as $table => $tableName) {
    $rows = $db->query("SELECT id, name, phase, row, column FROM $tableName WHERE is_active=1 AND phase>0 ORDER BY phase,row,column")
               ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $r['entity_type'] = $table;
        $entities[] = $r;
    }
}

// Build lookup for prerequisites (for tooltips)
$prereqs = [];
$allBuildings = $db->query("SELECT id, name FROM buildings")->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($db->query("SELECT id, required_building_id, required_building_level, required_building2_id, required_building2_level FROM researches WHERE is_active=1 AND phase>0") as $r) {
    $tips = [];
    if ($r['required_building_id']) {
        $tips[] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
    }
    if ($r['required_building2_id']) {
        $tips[] = ($allBuildings[$r['required_building2_id']] ?? '?') . ' Lv' . $r['required_building2_level'];
    }
    if ($tips) $prereqs['research_' . $r['id']] = implode(' + ', $tips);
}
foreach ($db->query("SELECT id, required_building_id, required_building_level FROM buildings WHERE is_active=1 AND phase>0") as $r) {
    if ($r['required_building_id']) {
        $prereqs['building_' . $r['id']] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
    }
}
foreach ($db->query("SELECT id, required_building_id, required_building_level FROM ships WHERE is_active=1 AND phase>0") as $r) {
    if ($r['required_building_id']) {
        $prereqs['ship_' . $r['id']] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
    }
}
foreach ($db->query("SELECT id, required_building_id, required_building_level FROM personell WHERE is_active=1 AND phase>0") as $r) {
    if ($r['required_building_id']) {
        $prereqs['personell_' . $r['id']] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
    }
}

$entitiesJson = json_encode($entities);
$prereqsJson  = json_encode($prereqs);

$ROWS = 8;
$COLS = 3;
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Techtree Editor — Nouron</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; font-size: 13px; background: #f0f2f5; color: #1a1a2e; }

header {
    background: #1a1a2e; color: #fff; padding: .6rem 1rem;
    display: flex; align-items: center; gap: 1rem;
}
header h1 { font-size: 1rem; font-weight: 600; }

.phase-tabs { display: flex; gap: .25rem; padding: .5rem 1rem; background: #fff; border-bottom: 1px solid #dde; }
.phase-tab {
    padding: .3rem .9rem; border-radius: 4px; cursor: pointer;
    border: 1px solid #ccd; background: #f5f5f8; font-size: .8rem; font-weight: 500;
}
.phase-tab.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }

.phase-panel { display: none; padding: 1rem; }
.phase-panel.active { display: block; }

.grid {
    display: grid;
    grid-template-columns: 2.5rem repeat(<?= $COLS ?>, 1fr);
    grid-template-rows: 1.8rem repeat(<?= $ROWS ?>, 80px);
    gap: 4px;
    max-width: 900px;
}

.col-header {
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: .05em;
}
.row-header {
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem; color: #999;
}

.cell {
    border: 2px dashed #cdd; border-radius: 6px; background: #f9fafb;
    min-height: 80px; position: relative; transition: background .15s, border-color .15s;
}
.cell.drag-over { background: #e8f0ff; border-color: #4a90d9; }

.entity-card {
    position: absolute; inset: 3px;
    border-radius: 4px; padding: 5px 7px;
    cursor: grab; user-select: none;
    display: flex; flex-direction: column; gap: 2px;
    border: 1px solid rgba(0,0,0,.1);
    font-size: .75rem; line-height: 1.3;
}
.entity-card:active { cursor: grabbing; }
.entity-card.type-building  { background: #dbeafe; border-color: #93c5fd; }
.entity-card.type-research   { background: #dcfce7; border-color: #86efac; }
.entity-card.type-ship       { background: #fef3c7; border-color: #fcd34d; }
.entity-card.type-personell  { background: #f3e8ff; border-color: #d8b4fe; }

.entity-card .type-badge {
    font-size: .6rem; font-weight: 700; text-transform: uppercase;
    opacity: .6; letter-spacing: .05em;
}
.entity-card .entity-name { font-weight: 600; color: #111; }
.entity-card .entity-prereq { font-size: .65rem; color: #555; margin-top: auto; }

.legend {
    display: flex; gap: 1rem; padding: .5rem 1rem;
    background: #fff; border-top: 1px solid #dde; font-size: .75rem;
}
.legend-item { display: flex; align-items: center; gap: .3rem; }
.legend-swatch { width: 10px; height: 10px; border-radius: 2px; }
.swatch-building  { background: #dbeafe; border: 1px solid #93c5fd; }
.swatch-research   { background: #dcfce7; border: 1px solid #86efac; }
.swatch-ship       { background: #fef3c7; border: 1px solid #fcd34d; }
.swatch-personell  { background: #f3e8ff; border: 1px solid #d8b4fe; }

#status-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #1a1a2e; color: #aad; padding: .35rem 1rem;
    font-size: .75rem; font-family: monospace;
}
</style>
</head>
<body>

<header>
    <h1>Techtree Editor</h1>
    <span style="opacity:.5;font-size:.8rem">Drag &amp; Drop → DB-Update direkt in nouron.db</span>
</header>

<div class="phase-tabs" id="phase-tabs">
    <?php for ($p = 1; $p <= 5; $p++): ?>
    <button class="phase-tab <?= $p === 1 ? 'active' : '' ?>" data-phase="<?= $p ?>">Phase <?= $p ?> (CC<?= $p ?>)</button>
    <?php endfor; ?>
</div>

<?php for ($p = 1; $p <= 5; $p++): ?>
<div class="phase-panel <?= $p === 1 ? 'active' : '' ?>" id="phase-<?= $p ?>">
    <div class="grid" id="grid-<?= $p ?>">
        <!-- Column headers -->
        <div class="col-header"></div>
        <?php for ($c = 1; $c <= $COLS; $c++): ?>
        <div class="col-header">Spalte <?= $c ?></div>
        <?php endfor; ?>
        <!-- Rows -->
        <?php for ($r = 1; $r <= $ROWS; $r++): ?>
        <div class="row-header"><?= $r ?></div>
        <?php for ($c = 1; $c <= $COLS; $c++): ?>
        <div class="cell" data-phase="<?= $p ?>" data-row="<?= $r ?>" data-col="<?= $c ?>"></div>
        <?php endfor; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endfor; ?>

<div class="legend">
    <span style="font-weight:600;margin-right:.25rem">Legende:</span>
    <span class="legend-item"><span class="legend-swatch swatch-building"></span> Gebäude</span>
    <span class="legend-item"><span class="legend-swatch swatch-research"></span> Research</span>
    <span class="legend-item"><span class="legend-swatch swatch-ship"></span> Schiff</span>
    <span class="legend-item"><span class="legend-swatch swatch-personell"></span> Personal</span>
</div>

<div id="status-bar">Bereit — ziehe eine Karte auf eine andere Zelle um die Position zu ändern.</div>

<script>
const entities = <?= $entitiesJson ?>;
const prereqs  = <?= $prereqsJson ?>;
const ROWS = <?= $ROWS ?>;
const COLS = <?= $COLS ?>;

const typeLabels = { building:'B', research:'R', ship:'S', personell:'P' };

function setStatus(msg) {
    document.getElementById('status-bar').textContent = msg;
}

// ── Place entity cards into grid cells ──────────────────────────────────────
function renderEntities() {
    // Clear all cells
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

        const prereqKey = `${e.entity_type}_${e.id}`;
        const prereqText = prereqs[prereqKey] ?? '';

        const card = document.createElement('div');
        card.className = `entity-card type-${e.entity_type}`;
        card.draggable = true;
        card.dataset.id   = e.id;
        card.dataset.type = e.entity_type;
        card.dataset.phase = e.phase;
        card.dataset.row  = e.row;
        card.dataset.col  = e.column;
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

// ── Drag & Drop ──────────────────────────────────────────────────────────────
let dragging = null;

function onDragStart(e) {
    const card = e.currentTarget;
    dragging = {
        id:    card.dataset.id,
        type:  card.dataset.type,
        phase: card.dataset.phase,
        row:   card.dataset.row,
        col:   card.dataset.col,
    };
    e.dataTransfer.effectAllowed = 'move';
}

document.querySelectorAll('.cell').forEach(cell => {
    cell.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        cell.classList.add('drag-over');
    });
    cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));
    cell.addEventListener('drop', async e => {
        e.preventDefault();
        cell.classList.remove('drag-over');
        if (!dragging) return;

        const newPhase = cell.dataset.phase;
        const newRow   = cell.dataset.row;
        const newCol   = cell.dataset.col;

        // No-op if dropped on same cell
        if (dragging.phase === newPhase && dragging.row === newRow && dragging.col === newCol) return;

        const occupied = cell.dataset.entityId ? {
            id:   cell.dataset.entityId,
            type: cell.dataset.entityType,
        } : null;

        setStatus(`Verschiebe ${dragging.type} #${dragging.id} → Phase ${newPhase} / Zeile ${newRow} / Spalte ${newCol} …`);

        try {
            const res = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_a:      dragging.id,
                    type_a:    dragging.type,
                    new_phase: newPhase,
                    new_row:   newRow,
                    new_col:   newCol,
                    old_phase: dragging.phase,
                    old_row:   dragging.row,
                    old_col:   dragging.col,
                    id_b:      occupied?.id   ?? null,
                    type_b:    occupied?.type ?? null,
                }),
            });
            const data = await res.json();
            if (data.ok) {
                // Update local entity data and re-render (no full page reload)
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

// ── Phase tabs ───────────────────────────────────────────────────────────────
document.getElementById('phase-tabs').addEventListener('click', e => {
    const tab = e.target.closest('.phase-tab');
    if (!tab) return;
    const phase = tab.dataset.phase;
    document.querySelectorAll('.phase-tab').forEach(t => t.classList.toggle('active', t === tab));
    document.querySelectorAll('.phase-panel').forEach(p => p.classList.toggle('active', p.id === `phase-${phase}`));
});

// ── Init ─────────────────────────────────────────────────────────────────────
renderEntities();
</script>
</body>
</html>
