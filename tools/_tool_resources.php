<?php
// Resources tab — POST handler, DB queries, and HTML content.
// Requires: $db, $editableColonyResources, $message (set before include on GET)

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

// ── DB queries ────────────────────────────────────────────────────────────────
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

$buildingLevels = [];
foreach ($db->query("SELECT colony_id, building_id, level FROM colony_buildings WHERE building_id IN (25, 28)")
             ->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $buildingLevels[$r['colony_id']][$r['building_id']] = (int) $r['level'];
}

function supplyCapFormula(int $ccLevel, int $housingLevel): int
{
    return $ccLevel > 0 ? min(10 + ($housingLevel * 8), 200) : 0;
}
?>
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
    $res       = $colonyResMap[$col['colony_id']] ?? [];
    $bldLevels = $buildingLevels[$col['colony_id']] ?? [];
    $ccLevel   = $bldLevels[25] ?? 0;
    $hsLevel   = $bldLevels[28] ?? 0;
    $supplyCap = supplyCapFormula($ccLevel, $hsLevel);
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
