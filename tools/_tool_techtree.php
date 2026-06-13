<?php
// Techtree tab — POST handler (JSON), DB queries, and HTML content.
// Requires: $db, $techtreeAllowed, $tab, $dbPath

// ── POST handler (JSON swap) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if (! isset($techtreeAllowed[$input['type_a'] ?? ''])) {
            echo json_encode(['ok' => false, 'error' => 'invalid type_a']);
            exit;
        }
        if (! empty($input['id_b']) && ! isset($techtreeAllowed[$input['type_b'] ?? ''])) {
            echo json_encode(['ok' => false, 'error' => 'invalid type_b']);
            exit;
        }

        try {
            $db->beginTransaction();

            $tableA = $techtreeAllowed[$input['type_a']];
            $db->prepare("UPDATE {$tableA} SET phase=?, row=?, column=? WHERE id=?")
                ->execute([$input['new_phase'], $input['new_row'], $input['new_col'], $input['id_a']]);

            if (! empty($input['id_b'])) {
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
}

// ── DB queries ────────────────────────────────────────────────────────────────
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

$prereqs = [];
$allBuildings = $db->query('SELECT id, name FROM buildings')->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($db->query('SELECT id, required_building_id, required_building_level, required_building2_id, required_building2_level FROM researches WHERE is_active=1 AND phase>0') as $r) {
    $tips = [];
    if ($r['required_building_id']) {
        $tips[] = ($allBuildings[$r['required_building_id']] ?? '?').' Lv'.$r['required_building_level'];
    }
    if ($r['required_building2_id']) {
        $tips[] = ($allBuildings[$r['required_building2_id']] ?? '?').' Lv'.$r['required_building2_level'];
    }
    if ($tips) {
        $prereqs['research_'.$r['id']] = implode(' + ', $tips);
    }
}
foreach ($db->query('SELECT id, required_building_id, required_building_level FROM buildings WHERE is_active=1 AND phase>0') as $r) {
    if ($r['required_building_id']) {
        $prereqs['building_'.$r['id']] = ($allBuildings[$r['required_building_id']] ?? '?').' Lv'.$r['required_building_level'];
    }
}
foreach ($db->query('SELECT id, required_building_id, required_building_level FROM ships WHERE is_active=1 AND phase>0') as $r) {
    if ($r['required_building_id']) {
        $prereqs['ship_'.$r['id']] = ($allBuildings[$r['required_building_id']] ?? '?').' Lv'.$r['required_building_level'];
    }
}
foreach ($db->query('SELECT id, required_building_id, required_building_level FROM personell WHERE is_active=1 AND phase>0') as $r) {
    if ($r['required_building_id']) {
        $prereqs['personell_'.$r['id']] = ($allBuildings[$r['required_building_id']] ?? '?').' Lv'.$r['required_building_level'];
    }
}

$ROWS = 8;
$COLS = 3;
?>
<div class="tab-content <?= $tab === 'techtree' ? 'active' : '' ?>">
<div class="techtree-wrap">

<div class="phase-tabs" id="phase-tabs">
    <?php for ($p = 1; $p <= 5; $p++) { ?>
    <button class="phase-tab <?= $p === 1 ? 'tt-active' : '' ?>" data-phase="<?= $p ?>">Phase <?= $p ?> (CC<?= $p ?>)</button>
    <?php } ?>
</div>

<?php for ($p = 1; $p <= 5; $p++) { ?>
<div class="phase-panel <?= $p === 1 ? 'tt-active' : '' ?>" id="tt-phase-<?= $p ?>">
    <div class="grid" id="grid-<?= $p ?>" style="--tt-cols:<?= $COLS ?>;--tt-rows:<?= $ROWS ?>">
        <div class="col-header"></div>
        <?php for ($c = 1; $c <= $COLS; $c++) { ?>
        <div class="col-header">Spalte <?= $c ?></div>
        <?php } ?>
        <?php for ($r = 1; $r <= $ROWS; $r++) { ?>
        <div class="row-header"><?= $r ?></div>
        <?php for ($c = 1; $c <= $COLS; $c++) { ?>
        <div class="cell" data-phase="<?= $p ?>" data-row="<?= $r ?>" data-col="<?= $c ?>"></div>
        <?php } ?>
        <?php } ?>
    </div>
</div>
<?php } ?>

<div class="tt-legend">
    <span style="font-weight:600;margin-right:.25rem">Legende:</span>
    <span class="legend-item"><span class="legend-swatch swatch-building"></span> Gebäude</span>
    <span class="legend-item"><span class="legend-swatch swatch-research"></span> Research</span>
    <span class="legend-item"><span class="legend-swatch swatch-ship"></span> Schiff</span>
    <span class="legend-item"><span class="legend-swatch swatch-personell"></span> Personal</span>
</div>

</div><!-- .techtree-wrap -->
</div>

<script>const entities = <?= json_encode($techtreeEntities) ?>; const prereqs = <?= json_encode($prereqs) ?>;</script>
<script src="/tools/assets/techtree.js"></script>
