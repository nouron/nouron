<?php
// Nouron Dev Panel — combined dev tool (Resource Editor + Techtree Editor).
// Usage: php -S localhost:8081 tools/dev-panel.php
// Then open: http://localhost:8081

$dbPath = __DIR__ . '/../data/db/nouron.db';

$editableColonyResources = [
    3  => 'Regolith',
    4  => 'Werkstoffe',
    5  => 'Organika',
    12 => 'Vertrauen (Moral)',
];

$techtreeAllowed = [
    'building'  => 'buildings',
    'research'  => 'researches',
    'ship'      => 'ships',
    'personell' => 'personell',
];

// ── POST: route by Content-Type ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // ── Techtree: JSON drag-drop swap ────────────────────────────────────────
    if (str_contains($contentType, 'application/json')) {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($techtreeAllowed[$input['type_a'] ?? ''])) {
            echo json_encode(['ok' => false, 'error' => 'invalid type_a']);
            exit;
        }
        if (!empty($input['id_b']) && !isset($techtreeAllowed[$input['type_b'] ?? ''])) {
            echo json_encode(['ok' => false, 'error' => 'invalid type_b']);
            exit;
        }

        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->beginTransaction();

            $tableA = $techtreeAllowed[$input['type_a']];
            $db->prepare("UPDATE {$tableA} SET phase=?, row=?, column=? WHERE id=?")
               ->execute([$input['new_phase'], $input['new_row'], $input['new_col'], $input['id_a']]);

            if (!empty($input['id_b'])) {
                $tableB = $techtreeAllowed[$input['type_b']];
                $db->prepare("UPDATE {$tableB} SET phase=?, row=?, column=? WHERE id=?")
                   ->execute([$input['old_phase'], $input['old_row'], $input['old_col'], $input['id_b']]);
            }

            $db->commit();
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ── Resources: form POST ─────────────────────────────────────────────────
    $message = '';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $type    = $_POST['type'] ?? '';
    $userId  = (int) ($_POST['user_id'] ?? 0);
    $colonId = (int) ($_POST['colony_id'] ?? 0);
    $field   = $_POST['field'] ?? '';
    $value   = (int) ($_POST['value'] ?? 0);

    try {
        if ($type === 'user' && $field === 'credits') {
            $db->prepare("UPDATE user_resources SET credits = ? WHERE user_id = ?")
               ->execute([$value, $userId]);
            $message = "Updated user #{$userId} credits → {$value}";
        } elseif ($type === 'colony' && isset($editableColonyResources[(int) $field])) {
            $resourceId = (int) $field;
            $db->prepare(
                "INSERT INTO colony_resources (colony_id, resource_id, amount)
                 VALUES (?, ?, ?)
                 ON CONFLICT(colony_id, resource_id) DO UPDATE SET amount = excluded.amount"
            )->execute([$colonId, $resourceId, $value]);
            $message = "Updated colony #{$colonId} {$editableColonyResources[$resourceId]} → {$value}";
        } elseif ($type === 'building_level' && in_array((int) $field, [25, 28], true)) {
            $buildingId = (int) $field;
            $maxLevel   = $buildingId === 25 ? 5 : 6;
            $value      = max(0, min($value, $maxLevel));
            $db->prepare(
                "UPDATE colony_buildings SET level = ? WHERE colony_id = ? AND building_id = ?"
            )->execute([$value, $colonId, $buildingId]);
            $label   = $buildingId === 25 ? 'CC Level' : 'Housing Level';
            $message = "Updated colony #{$colonId} {$label} → {$value}";
        } else {
            $message = 'Invalid update request.';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// ── Tab state ─────────────────────────────────────────────────────────────────
$tab     = $_GET['tab'] ?? 'resources';
$message = $message ?? '';

// ── DB connection (GET) ───────────────────────────────────────────────────────
if (!isset($db)) {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// ── Load resources data ───────────────────────────────────────────────────────
$users = $db->query(
    "SELECT u.user_id, u.username, ur.credits, ur.supply
     FROM user u
     LEFT JOIN user_resources ur ON ur.user_id = u.user_id
     WHERE ur.user_id IS NOT NULL
     ORDER BY u.username"
)->fetchAll(PDO::FETCH_ASSOC);

$colonies = $db->query(
    "SELECT c.id AS colony_id, c.name AS colony_name, c.user_id, u.username
     FROM glx_colonies c
     LEFT JOIN user u ON u.user_id = c.user_id
     ORDER BY c.name"
)->fetchAll(PDO::FETCH_ASSOC);

$colonyResRows = $db->query(
    "SELECT colony_id, resource_id, amount FROM colony_resources WHERE resource_id IN (3,4,5,12)"
)->fetchAll(PDO::FETCH_ASSOC);

$colonyResMap = [];
foreach ($colonyResRows as $r) {
    $colonyResMap[$r['colony_id']][$r['resource_id']] = $r['amount'];
}

// CC (25) + Housing (28) levels per colony — used to calculate supply cap
$buildingLevels = [];
foreach ($db->query("SELECT colony_id, building_id, level FROM colony_buildings WHERE building_id IN (25, 28)")
             ->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $buildingLevels[$r['colony_id']][$r['building_id']] = (int) $r['level'];
}

function supplyCapFormula(int $ccLevel, int $housingLevel): int {
    return $ccLevel > 0 ? min(10 + ($housingLevel * 8), 200) : 0;
}

// ── Load techtree data ────────────────────────────────────────────────────────
$techtreeEntities = [];
foreach ($techtreeAllowed as $typeKey => $tableName) {
    $rows = $db->query(
        "SELECT id, name, phase, row, column FROM {$tableName}
         WHERE is_active=1 AND phase>0 ORDER BY phase,row,column"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $r['entity_type'] = $typeKey;
        $techtreeEntities[] = $r;
    }
}

$prereqs     = [];
$allBuildings = $db->query("SELECT id, name FROM buildings")->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($db->query("SELECT id, required_building_id, required_building_level, required_building2_id, required_building2_level FROM researches WHERE is_active=1 AND phase>0") as $r) {
    $tips = [];
    if ($r['required_building_id'])  $tips[] = ($allBuildings[$r['required_building_id']]  ?? '?') . ' Lv' . $r['required_building_level'];
    if ($r['required_building2_id']) $tips[] = ($allBuildings[$r['required_building2_id']] ?? '?') . ' Lv' . $r['required_building2_level'];
    if ($tips) $prereqs['research_'  . $r['id']] = implode(' + ', $tips);
}
foreach ($db->query("SELECT id, required_building_id, required_building_level FROM buildings WHERE is_active=1 AND phase>0") as $r) {
    if ($r['required_building_id']) $prereqs['building_'  . $r['id']] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
}
foreach ($db->query("SELECT id, required_building_id, required_building_level FROM ships WHERE is_active=1 AND phase>0") as $r) {
    if ($r['required_building_id']) $prereqs['ship_'      . $r['id']] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
}
foreach ($db->query("SELECT id, required_building_id, required_building_level FROM personell WHERE is_active=1 AND phase>0") as $r) {
    if ($r['required_building_id']) $prereqs['personell_' . $r['id']] = ($allBuildings[$r['required_building_id']] ?? '?') . ' Lv' . $r['required_building_level'];
}

$entitiesJson = json_encode($techtreeEntities);
$prereqsJson  = json_encode($prereqs);

$ROWS = 8;
$COLS = 3;

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Nouron Dev Panel</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, monospace, sans-serif; font-size: 13px; background: #111827; color: #e0e0e0; min-height: 100vh; }

/* ── Header ───────────────────────────────────────────────────────────────── */
.panel-header {
    background: #0d1117; border-bottom: 1px solid #2a2a3e;
    padding: .6rem 1.2rem; display: flex; align-items: center; gap: 1.5rem;
}
.panel-header h1 { font-size: 1rem; font-weight: 700; color: #7fbbff; letter-spacing: .02em; }
.panel-header .hint { font-size: .78rem; color: #555; }

/* ── Tool tabs ────────────────────────────────────────────────────────────── */
.tool-tabs { display: flex; gap: 2px; padding: .5rem 1.2rem; background: #0d1117; border-bottom: 1px solid #1e2030; }
.tool-tab {
    padding: .35rem 1.1rem; border-radius: 4px 4px 0 0; cursor: pointer;
    border: 1px solid transparent; font-size: .82rem; font-weight: 500;
    color: #888; background: transparent; text-decoration: none; display: inline-block;
    transition: color .15s, background .15s;
}
.tool-tab:hover { color: #bbb; background: #1a1a2e; }
.tool-tab.active { background: #1a1a2e; color: #7fbbff; border-color: #2a2a4e; border-bottom-color: #1a1a2e; }

/* ── Content wrapper ──────────────────────────────────────────────────────── */
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ═══════════════════════════════════════════════════════════════════════════
   RESOURCES TAB
   ═══════════════════════════════════════════════════════════════════════════ */
.res-section { padding: 1.2rem; }
.res-section h2 { color: #aaa; font-size: .9rem; margin: 1.2rem 0 .6rem; border-bottom: 1px solid #252535; padding-bottom: .3rem; }
.res-section h2:first-child { margin-top: 0; }

.msg { background: #1e4a1e; border: 1px solid #2e6b2e; color: #7fff7f; padding: 8px 12px; border-radius: 4px; margin-bottom: 12px; }

table.res-table { border-collapse: collapse; width: 100%; margin-bottom: 1rem; }
table.res-table th { background: #1a1a2e; color: #7fbbff; text-align: left; padding: 5px 10px; font-size: .78rem; }
table.res-table td { padding: 5px 10px; border-top: 1px solid #1e1e30; vertical-align: middle; }
table.res-table tr:hover td { background: #161625; }

.user-label { font-weight: 600; color: #e0e0e0; }
.id-label    { font-size: .72rem; color: #555; }

input[type=number] {
    background: #0d0d1a; color: #fff; border: 1px solid #333; padding: 3px 6px;
    width: 100px; border-radius: 3px; font-family: monospace; font-size: .82rem;
}
input[type=number]:focus { border-color: #7fbbff; outline: none; }
button.set-btn {
    background: #1e3a6e; color: #aac8ff; border: 1px solid #2a4a8e;
    padding: 3px 10px; border-radius: 3px; cursor: pointer; font-size: .78rem;
    transition: background .1s;
}
button.set-btn:hover { background: #2a4a9e; }
.field-row { display: flex; gap: 6px; align-items: center; }

/* ═══════════════════════════════════════════════════════════════════════════
   TECHTREE TAB
   ═══════════════════════════════════════════════════════════════════════════ */
.techtree-wrap { background: #f0f2f5; color: #1a1a2e; padding-bottom: 2.5rem; }

.phase-tabs { display: flex; gap: .25rem; padding: .5rem 1rem; background: #fff; border-bottom: 1px solid #dde; }
.phase-tab {
    padding: .3rem .9rem; border-radius: 4px; cursor: pointer;
    border: 1px solid #ccd; background: #f5f5f8; font-size: .8rem; font-weight: 500; color: #1a1a2e;
}
.phase-tab.tt-active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }

.phase-panel { display: none; padding: 1rem; }
.phase-panel.tt-active { display: block; }

.grid {
    display: grid;
    grid-template-columns: 2.5rem repeat(<?= $COLS ?>, 1fr);
    grid-template-rows: 1.8rem repeat(<?= $ROWS ?>, 80px);
    gap: 4px;
    max-width: 900px;
}

.col-header { display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: .05em; }
.row-header { display: flex; align-items: center; justify-content: center; font-size: .7rem; color: #999; }

.cell { border: 2px dashed #cdd; border-radius: 6px; background: #f9fafb; min-height: 80px; position: relative; transition: background .15s, border-color .15s; }
.cell.drag-over { background: #e8f0ff; border-color: #4a90d9; }

.entity-card {
    position: absolute; inset: 3px; border-radius: 4px; padding: 5px 7px;
    cursor: grab; user-select: none;
    display: flex; flex-direction: column; gap: 2px;
    border: 1px solid rgba(0,0,0,.1);
    font-size: .75rem; line-height: 1.3;
}
.entity-card:active { cursor: grabbing; }
.entity-card.type-building  { background: #dbeafe; border-color: #93c5fd; }
.entity-card.type-research  { background: #dcfce7; border-color: #86efac; }
.entity-card.type-ship      { background: #fef3c7; border-color: #fcd34d; }
.entity-card.type-personell { background: #f3e8ff; border-color: #d8b4fe; }

.entity-card .type-badge   { font-size: .6rem; font-weight: 700; text-transform: uppercase; opacity: .6; letter-spacing: .05em; }
.entity-card .entity-name  { font-weight: 600; color: #111; }
.entity-card .entity-prereq { font-size: .65rem; color: #555; margin-top: auto; }

.tt-legend {
    display: flex; gap: 1rem; padding: .5rem 1rem;
    background: #fff; border-top: 1px solid #dde; font-size: .75rem; color: #1a1a2e;
}
.legend-item { display: flex; align-items: center; gap: .3rem; }
.legend-swatch { width: 10px; height: 10px; border-radius: 2px; }
.swatch-building  { background: #dbeafe; border: 1px solid #93c5fd; }
.swatch-research  { background: #dcfce7; border: 1px solid #86efac; }
.swatch-ship      { background: #fef3c7; border: 1px solid #fcd34d; }
.swatch-personell { background: #f3e8ff; border: 1px solid #d8b4fe; }

#status-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #1a1a2e; color: #aad; padding: .35rem 1rem;
    font-size: .75rem; font-family: monospace; z-index: 100;
}
</style>
</head>
<body>

<div class="panel-header">
    <h1>Nouron Dev Panel</h1>
    <span class="hint">php -S localhost:8081 tools/dev-panel.php</span>
</div>

<div class="tool-tabs">
    <a href="?tab=resources" class="tool-tab <?= $tab === 'resources' ? 'active' : '' ?>">Resources</a>
    <a href="?tab=techtree"  class="tool-tab <?= $tab === 'techtree'  ? 'active' : '' ?>">Techtree</a>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     RESOURCES TAB
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="tab-content <?= $tab === 'resources' ? 'active' : '' ?>">
<div class="res-section">

<?php if ($message): ?>
<div class="msg"><?= h($message) ?></div>
<?php endif; ?>

<h2>User Resources</h2>
<table class="res-table">
<tr><th>User</th><th>Credits</th></tr>
<?php foreach ($users as $u): ?>
<tr>
  <td><span class="user-label"><?= h($u['username']) ?></span><br><span class="id-label">user_id <?= $u['user_id'] ?></span></td>
  <td>
    <form method="POST" action="?tab=resources">
      <input type="hidden" name="type" value="user">
      <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
      <input type="hidden" name="field" value="credits">
      <div class="field-row">
        <input type="number" name="value" value="<?= (int) $u['credits'] ?>" min="0">
        <button type="submit" class="set-btn">Set</button>
      </div>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>

<h2>Colony Resources</h2>
<table class="res-table">
<tr>
  <th>Colony</th>
  <th>CC Level <span style="font-weight:400;color:#555">(max 5)</span></th>
  <th>Housing Level <span style="font-weight:400;color:#555">(max 6)</span></th>
  <th>Supply Cap <span style="font-weight:400;color:#555">(berechnet)</span></th>
  <?php foreach ($editableColonyResources as $name): ?><th><?= h($name) ?></th><?php endforeach; ?>
</tr>
<?php foreach ($colonies as $col): ?>
<?php
    $res        = $colonyResMap[$col['colony_id']] ?? [];
    $bldLevels  = $buildingLevels[$col['colony_id']] ?? [];
    $ccLevel    = $bldLevels[25] ?? 0;
    $hsLevel    = $bldLevels[28] ?? 0;
    $supplyCap  = supplyCapFormula($ccLevel, $hsLevel);
?>
<tr>
  <td>
    <span class="user-label"><?= h($col['colony_name']) ?></span><br>
    <span class="id-label">colony_id <?= $col['colony_id'] ?> · <?= h($col['username'] ?? 'NPC') ?></span>
  </td>
  <td>
    <form method="POST" action="?tab=resources">
      <input type="hidden" name="type" value="building_level">
      <input type="hidden" name="colony_id" value="<?= $col['colony_id'] ?>">
      <input type="hidden" name="field" value="25">
      <div class="field-row">
        <input type="number" name="value" value="<?= $ccLevel ?>" min="0" max="5">
        <button type="submit" class="set-btn">Set</button>
      </div>
    </form>
  </td>
  <td>
    <form method="POST" action="?tab=resources">
      <input type="hidden" name="type" value="building_level">
      <input type="hidden" name="colony_id" value="<?= $col['colony_id'] ?>">
      <input type="hidden" name="field" value="28">
      <div class="field-row">
        <input type="number" name="value" value="<?= $hsLevel ?>" min="0" max="6">
        <button type="submit" class="set-btn">Set</button>
      </div>
    </form>
  </td>
  <td>
    <span style="font-family:monospace;color:#7fbbff"><?= $supplyCap ?></span>
    <br><span class="id-label">10 + <?= $hsLevel ?>×8<?= $ccLevel === 0 ? ' (kein CC)' : '' ?></span>
  </td>
  <?php foreach ($editableColonyResources as $rid => $rname): ?>
  <td>
    <form method="POST" action="?tab=resources">
      <input type="hidden" name="type" value="colony">
      <input type="hidden" name="colony_id" value="<?= $col['colony_id'] ?>">
      <input type="hidden" name="field" value="<?= $rid ?>">
      <div class="field-row">
        <input type="number" name="value" value="<?= (int) ($res[$rid] ?? 0) ?>" min="-999999">
        <button type="submit" class="set-btn">Set</button>
      </div>
    </form>
  </td>
  <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

</div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     TECHTREE TAB
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="tab-content <?= $tab === 'techtree' ? 'active' : '' ?>">
<div class="techtree-wrap">

<div class="phase-tabs" id="phase-tabs">
    <?php for ($p = 1; $p <= 5; $p++): ?>
    <button class="phase-tab <?= $p === 1 ? 'tt-active' : '' ?>" data-phase="<?= $p ?>">Phase <?= $p ?> (CC<?= $p ?>)</button>
    <?php endfor; ?>
</div>

<?php for ($p = 1; $p <= 5; $p++): ?>
<div class="phase-panel <?= $p === 1 ? 'tt-active' : '' ?>" id="tt-phase-<?= $p ?>">
    <div class="grid" id="grid-<?= $p ?>">
        <div class="col-header"></div>
        <?php for ($c = 1; $c <= $COLS; $c++): ?>
        <div class="col-header">Spalte <?= $c ?></div>
        <?php endfor; ?>
        <?php for ($r = 1; $r <= $ROWS; $r++): ?>
        <div class="row-header"><?= $r ?></div>
        <?php for ($c = 1; $c <= $COLS; $c++): ?>
        <div class="cell" data-phase="<?= $p ?>" data-row="<?= $r ?>" data-col="<?= $c ?>"></div>
        <?php endfor; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endfor; ?>

<div class="tt-legend">
    <span style="font-weight:600;margin-right:.25rem">Legende:</span>
    <span class="legend-item"><span class="legend-swatch swatch-building"></span> Gebäude</span>
    <span class="legend-item"><span class="legend-swatch swatch-research"></span> Research</span>
    <span class="legend-item"><span class="legend-swatch swatch-ship"></span> Schiff</span>
    <span class="legend-item"><span class="legend-swatch swatch-personell"></span> Personal</span>
</div>

</div><!-- .techtree-wrap -->
</div>

<div id="status-bar">Bereit.</div>

<script>
// ── Techtree drag & drop ─────────────────────────────────────────────────────
const entities   = <?= $entitiesJson ?>;
const prereqs    = <?= $prereqsJson ?>;
const typeLabels = { building:'B', research:'R', ship:'S', personell:'P' };

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

// ── Techtree phase tabs ───────────────────────────────────────────────────────
document.getElementById('phase-tabs')?.addEventListener('click', e => {
    const tab = e.target.closest('.phase-tab');
    if (!tab) return;
    const phase = tab.dataset.phase;
    document.querySelectorAll('.phase-tab').forEach(t  => t.classList.toggle('tt-active', t === tab));
    document.querySelectorAll('.phase-panel').forEach(p => p.classList.toggle('tt-active', p.id === `tt-phase-${phase}`));
    setStatus(`Phase ${phase} aktiv.`);
});

renderEntities();
</script>
</body>
</html>
