<?php
// Nouron Dev Panel — entry point.
// Usage: php -S localhost:8081 tools/dev-panel.php
// Then open: http://localhost:8081

if (php_sapi_name() === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = dirname(__DIR__).$requestPath;
    if (is_file($file)) {
        return false;
    }
}

$dbPath = __DIR__.'/../data/db/nouron.db';

$hotspotsPath = __DIR__.'/../data/cantina_hotspots.json';
$defaultHotspots = [
    'spot_0' => ['desktop' => ['left' => 17, 'top' => 58], 'tablet' => ['left' => 17, 'top' => 58], 'mobile' => ['left' => 17, 'top' => 58], 'characters' => ['bartender']],
    'spot_1' => ['desktop' => ['left' => 27, 'top' => 45], 'tablet' => ['left' => 27, 'top' => 45], 'mobile' => ['left' => 27, 'top' => 45], 'characters' => []],
    'spot_2' => ['desktop' => ['left' => 39, 'top' => 40], 'tablet' => ['left' => 39, 'top' => 40], 'mobile' => ['left' => 39, 'top' => 40], 'characters' => []],
    'spot_3' => ['desktop' => ['left' => 55, 'top' => 50], 'tablet' => ['left' => 55, 'top' => 50], 'mobile' => ['left' => 55, 'top' => 50], 'characters' => []],
    'spot_4' => ['desktop' => ['left' => 67, 'top' => 44], 'tablet' => ['left' => 67, 'top' => 44], 'mobile' => ['left' => 67, 'top' => 44], 'characters' => []],
    'spot_5' => ['desktop' => ['left' => 83, 'top' => 52], 'tablet' => ['left' => 83, 'top' => 52], 'mobile' => ['left' => 83, 'top' => 52], 'characters' => []],
];
$hotspots = file_exists($hotspotsPath)
    ? (json_decode(file_get_contents($hotspotsPath), true) ?: $defaultHotspots)
    : $defaultHotspots;

$characterSlugs = [];
$charDir = __DIR__.'/../docs/characters';
if (is_dir($charDir)) {
    foreach (glob($charDir.'/*.md') as $f) {
        $slug = basename($f, '.md');
        if ($slug === '_template') {
            continue;
        }
        $characterSlugs[] = $slug;
    }
    sort($characterSlugs);
}

$editableColonyResources = [
    3 => 'Regolith',
    4 => 'Werkstoffe',
    5 => 'Organika',
    12 => 'Vertrauen (Moral)',
];

$techtreeAllowed = [
    'building' => 'buildings',
    'research' => 'researches',
    'ship' => 'ships',
    'personell' => 'personell',
];

// ── Tab state ─────────────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'resources';
$message = '';

// ── DB connection ─────────────────────────────────────────────────────────────
$db = new PDO('sqlite:'.$dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── POST routing ──────────────────────────────────────────────────────────────
// Each tool file handles its own POST at the top (exits for JSON, sets $message for form).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        // JSON POST — route to the correct tool file; it will exit
        if ($tab === 'cantina') {
            require __DIR__.'/_tool_cantina.php';
        } else {
            require __DIR__.'/_tool_techtree.php';
        }
        exit; // unreachable, but defensive
    }
    // Form POST (resources) — handled inside _tool_resources.php
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Nouron Dev Panel</title>
<link rel="stylesheet" href="/tools/assets/dev-panel.css">
</head>
<body>

<div class="panel-header">
    <h1>Nouron Dev Panel</h1>
    <span class="hint">php -S localhost:8081 tools/dev-panel.php</span>
</div>

<div class="tool-tabs">
    <a href="?tab=resources" class="tool-tab <?= $tab === 'resources' ? 'active' : '' ?>">Resources</a>
    <a href="?tab=techtree"  class="tool-tab <?= $tab === 'techtree' ? 'active' : '' ?>">Techtree</a>
    <a href="?tab=cantina"   class="tool-tab <?= $tab === 'cantina' ? 'active' : '' ?>">Cantina Hotspots</a>
</div>

<?php require __DIR__.'/_tool_resources.php'; ?>
<?php require __DIR__.'/_tool_techtree.php'; ?>
<?php require __DIR__.'/_tool_cantina.php'; ?>

<div id="status-bar">Bereit.</div>

</body>
</html>
