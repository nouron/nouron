<?php
// Resource Editor — local dev tool for setting colony/user resource values.
// Usage: php -S localhost:8082 tools/resource-editor.php
// Then open: http://localhost:8082

$dbPath = __DIR__ . '/../data/db/nouron.db';

$editableColonyResources = [
    3  => 'Regolith',
    4  => 'Werkstoffe',
    5  => 'Organika',
    12 => 'Vertrauen (Moral)',
];

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';

// ── POST: update values ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type    = $_POST['type'] ?? '';
    $userId  = (int) ($_POST['user_id'] ?? 0);
    $colonId = (int) ($_POST['colony_id'] ?? 0);
    $field   = $_POST['field'] ?? '';
    $value   = (int) ($_POST['value'] ?? 0);

    try {
        if ($type === 'user' && in_array($field, ['credits', 'supply'], true)) {
            $stmt = $db->prepare("UPDATE user_resources SET {$field} = ? WHERE user_id = ?");
            $stmt->execute([$value, $userId]);
            $message = "Updated user #{$userId} {$field} → {$value}";
        } elseif ($type === 'colony' && isset($editableColonyResources[(int)$field])) {
            $resourceId = (int) $field;
            $stmt = $db->prepare(
                "INSERT INTO colony_resources (colony_id, resource_id, amount)
                 VALUES (?, ?, ?)
                 ON CONFLICT(colony_id, resource_id) DO UPDATE SET amount = excluded.amount"
            );
            $stmt->execute([$colonId, $resourceId, $value]);
            $message = "Updated colony #{$colonId} {$editableColonyResources[$resourceId]} → {$value}";
        } else {
            $message = 'Invalid update request.';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// ── Load data ────────────────────────────────────────────────────────────────
$users = $db->query(
    "SELECT u.user_id, u.username, ur.credits, ur.supply
     FROM user u
     LEFT JOIN user_resources ur ON ur.user_id = u.user_id
     WHERE ur.user_id IS NOT NULL
     ORDER BY u.username"
)->fetchAll(PDO::FETCH_ASSOC);

$colonies = $db->query(
    "SELECT c.id AS colony_id, c.name AS colony_name, c.user_id,
            u.username
     FROM glx_colonies c
     LEFT JOIN user u ON u.user_id = c.user_id
     ORDER BY c.name"
)->fetchAll(PDO::FETCH_ASSOC);

// Build colony resources map: colony_id => [resource_id => amount]
$colonyResRows = $db->query(
    "SELECT colony_id, resource_id, amount FROM colony_resources
     WHERE resource_id IN (3,4,5,12)"
)->fetchAll(PDO::FETCH_ASSOC);

$colonyResMap = [];
foreach ($colonyResRows as $r) {
    $colonyResMap[$r['colony_id']][$r['resource_id']] = $r['amount'];
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Resource Editor — Nouron Dev Tool</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; margin: 0; padding: 20px; }
  h1 { color: #7fbbff; margin-bottom: 4px; font-size: 1.2rem; }
  .subtitle { color: #666; margin-bottom: 20px; font-size: 0.85rem; }
  .msg { background: #1e4a1e; border: 1px solid #2e6b2e; color: #7fff7f; padding: 8px 12px; border-radius: 4px; margin-bottom: 16px; }
  h2 { color: #aaa; font-size: 1rem; margin: 20px 0 8px; border-bottom: 1px solid #333; padding-bottom: 4px; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 24px; }
  th { background: #252540; color: #7fbbff; text-align: left; padding: 6px 10px; font-size: 0.8rem; }
  td { padding: 6px 10px; border-top: 1px solid #2a2a40; font-size: 0.85rem; vertical-align: middle; }
  tr:hover td { background: #1e1e35; }
  .label { color: #888; font-size: 0.75rem; display: block; }
  input[type=number] {
    background: #0d0d1a; color: #fff; border: 1px solid #444; padding: 3px 6px;
    width: 100px; border-radius: 3px; font-family: monospace;
  }
  input[type=number]:focus { border-color: #7fbbff; outline: none; }
  button {
    background: #2a4a7f; color: #fff; border: none; padding: 4px 10px;
    border-radius: 3px; cursor: pointer; font-family: monospace; font-size: 0.8rem;
  }
  button:hover { background: #3a5a9f; }
  .val { color: #fff; font-weight: bold; }
  .zero { color: #555; }
  .warn { color: #ffaa44; }
  .crit { color: #ff6644; }
</style>
</head>
<body>
<h1>Resource Editor</h1>
<p class="subtitle">php -S localhost:8082 tools/resource-editor.php &nbsp;|&nbsp; Änderungen sind sofort in der Dev-DB wirksam</p>

<?php if ($message): ?>
<div class="msg"><?= h($message) ?></div>
<?php endif; ?>

<h2>User Resources (Credits / Supply)</h2>
<table>
<tr><th>User</th><th>Credits</th><th>Supply</th></tr>
<?php foreach ($users as $u): ?>
<tr>
  <td><span class="val"><?= h($u['username']) ?></span><br><span class="label">user_id <?= $u['user_id'] ?></span></td>
  <td>
    <form method="POST" style="display:flex;gap:6px;align-items:center">
      <input type="hidden" name="type" value="user">
      <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
      <input type="hidden" name="field" value="credits">
      <input type="number" name="value" value="<?= (int)$u['credits'] ?>" min="0">
      <button type="submit">Set</button>
    </form>
  </td>
  <td>
    <form method="POST" style="display:flex;gap:6px;align-items:center">
      <input type="hidden" name="type" value="user">
      <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
      <input type="hidden" name="field" value="supply">
      <input type="number" name="value" value="<?= (int)$u['supply'] ?>" min="0">
      <button type="submit">Set</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>

<h2>Colony Resources</h2>
<table>
<tr>
  <th>Colony</th>
  <?php foreach ($editableColonyResources as $name): ?><th><?= h($name) ?></th><?php endforeach; ?>
</tr>
<?php foreach ($colonies as $col): ?>
<?php $res = $colonyResMap[$col['colony_id']] ?? []; ?>
<tr>
  <td>
    <span class="val"><?= h($col['colony_name']) ?></span><br>
    <span class="label">colony_id <?= $col['colony_id'] ?> &nbsp;·&nbsp; <?= h($col['username'] ?? 'NPC') ?></span>
  </td>
  <?php foreach ($editableColonyResources as $rid => $name): ?>
  <td>
    <form method="POST" style="display:flex;gap:6px;align-items:center">
      <input type="hidden" name="type" value="colony">
      <input type="hidden" name="colony_id" value="<?= $col['colony_id'] ?>">
      <input type="hidden" name="field" value="<?= $rid ?>">
      <?php $cur = (int)($res[$rid] ?? 0); ?>
      <input type="number" name="value" value="<?= $cur ?>" min="-999999">
      <button type="submit">Set</button>
    </form>
  </td>
  <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
