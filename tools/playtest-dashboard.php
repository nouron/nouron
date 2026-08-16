<?php
// Nouron Playtest Dashboard — visual comparison of PlaytestBot runs.
// Usage: php -S localhost:8082 tools/playtest-dashboard.php
// Then open: http://localhost:8082
//
// Reads the JSON reports game:playtest / PlaytestBotTest already write to
// storage/logs/playtest/ — no new data pipeline, this is a viewer only.

if (php_sapi_name() === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = dirname(__DIR__).$requestPath;
    if (is_file($file)) {
        return false;
    }
}

$reportDir = __DIR__.'/../storage/logs/playtest';
$runs = [];

if (is_dir($reportDir)) {
    $files = glob($reportDir.'/*.json');
    // Newest first — matches the sidebar's intended reading order.
    usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

    foreach ($files as $path) {
        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || ! isset($data['seed'], $data['sols'])) {
            continue; // skip malformed/foreign files rather than fail the whole page
        }

        $runs[] = [
            'file' => basename($path),
            'mtime' => filemtime($path),
            'profile' => $data['profile'] ?? 'default',
            'seed' => $data['seed'],
            'outcome' => $data['outcome'] ?? ['status' => 'unknown'],
            'phase2_start_sol' => $data['phase2_start_sol'] ?? null,
            'objectives' => $data['objectives'] ?? [],
            'actions' => $data['actions'] ?? [],
            'rejections' => $data['rejections'] ?? [],
            'sols' => $data['sols'] ?? [],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Nouron Playtest Dashboard</title>
<link rel="stylesheet" href="/tools/assets/dev-panel.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body>

<div class="panel-header">
    <h1>Nouron Playtest Dashboard</h1>
    <span class="hint">php -S localhost:8082 tools/playtest-dashboard.php</span>
</div>

<div class="pd-layout">
    <div class="pd-sidebar">
        <h2>Läufe (<?= count($runs) ?>)</h2>
        <?php if (empty($runs)) { ?>
            <p class="pd-empty">Keine Reports in <code>storage/logs/playtest/</code>. Erst <code>php artisan game:playtest</code> laufen lassen.</p>
        <?php } else { ?>
            <div id="pd-run-list"></div>
        <?php } ?>
    </div>

    <div class="pd-main">
        <div class="pd-chart-grid">
            <div class="pd-chart-card"><h3>Regolith</h3><canvas id="chart-regolith"></canvas></div>
            <div class="pd-chart-card"><h3>Credits</h3><canvas id="chart-credits"></canvas></div>
            <div class="pd-chart-card"><h3>AP (verfügbar)</h3><canvas id="chart-ap"></canvas></div>
            <div class="pd-chart-card"><h3>Vertrauen</h3><canvas id="chart-trust"></canvas></div>
            <div class="pd-chart-card"><h3>CC-Level</h3><canvas id="chart-cc"></canvas></div>
        </div>

        <table class="pd-summary">
            <thead>
                <tr><th>Profil</th><th>Seed</th><th>Outcome</th><th>Phase 2 ab Sol</th><th>Objectives</th><th>Score</th><th>Aktionen</th><th>Top-Rejections</th></tr>
            </thead>
            <tbody id="pd-summary-body"></tbody>
        </table>
    </div>
</div>

<script id="playtest-data" type="application/json"><?= json_encode($runs, JSON_UNESCAPED_SLASHES) ?></script>
<script>
const RUNS = JSON.parse(document.getElementById('playtest-data').textContent);

// Stable color per profile name — cycles through a fixed palette so the same
// profile always reads as the same color across page reloads.
const PALETTE = ['#7fbbff', '#ff9f7f', '#7fff9f', '#e0a0ff', '#ffe07f', '#7fe0e0', '#ff7fa0'];
const profileColors = {};
function colorFor(profile) {
    if (!(profile in profileColors)) {
        profileColors[profile] = PALETTE[Object.keys(profileColors).length % PALETTE.length];
    }
    return profileColors[profile];
}

function runLabel(run) {
    return `${run.profile} · seed ${run.seed}`;
}

// ── Sidebar ──────────────────────────────────────────────────────────────
const listEl = document.getElementById('pd-run-list');
const selected = new Set();

if (listEl) {
    RUNS.forEach((run, idx) => {
        const item = document.createElement('label');
        item.className = 'pd-run-item';
        const swatch = document.createElement('span');
        swatch.className = 'pd-run-swatch';
        swatch.style.background = colorFor(run.profile);
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.dataset.idx = idx;
        checkbox.addEventListener('change', () => {
            checkbox.checked ? selected.add(idx) : selected.delete(idx);
            render();
        });
        const text = document.createElement('span');
        text.className = 'pd-run-label';
        const outcomeClass = run.outcome.status === 'completed' ? 'pd-run-outcome-completed' : 'pd-run-outcome-failed';
        text.innerHTML = `<span class="pd-run-profile">${run.profile}</span> #${run.seed} <span class="${outcomeClass}">${run.outcome.status}</span>`;
        item.append(checkbox, swatch, text);
        listEl.appendChild(item);

        // Auto-select the 3 most recent runs on first load — sidebar is
        // newest-first, so this is just "check the first 3 rows".
        if (idx < 3) {
            checkbox.checked = true;
            selected.add(idx);
        }
    });
}

// ── Charts ───────────────────────────────────────────────────────────────
const chartDefs = [
    { id: 'chart-regolith', field: 'regolith' },
    { id: 'chart-credits', field: 'credits' },
    { id: 'chart-ap', field: 'ap_unspent' },
    { id: 'chart-trust', field: 'trust' },
    { id: 'chart-cc', field: 'cc_level' },
];
const charts = {};
chartDefs.forEach(def => {
    const canvas = document.getElementById(def.id);
    if (!canvas) return;
    charts[def.field] = new Chart(canvas, {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            animation: false,
            interaction: { mode: 'nearest', intersect: false },
            scales: {
                x: { type: 'linear', title: { display: true, text: 'Sol', color: '#888' }, ticks: { color: '#888' }, grid: { color: '#1e1e30' } },
                y: { ticks: { color: '#888' }, grid: { color: '#1e1e30' } },
            },
            plugins: { legend: { labels: { color: '#ccc', boxWidth: 12, font: { size: 10 } } } },
        },
    });
});

function render() {
    const activeRuns = [...selected].map(idx => RUNS[idx]).filter(Boolean);

    chartDefs.forEach(def => {
        const chart = charts[def.field];
        if (!chart) return;
        chart.data.datasets = activeRuns.map(run => ({
            label: runLabel(run),
            data: run.sols.map(s => ({ x: s.sol, y: s[def.field] })),
            borderColor: colorFor(run.profile),
            backgroundColor: colorFor(run.profile),
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.15,
        }));
        chart.update();
    });

    const body = document.getElementById('pd-summary-body');
    if (!body) return;
    body.innerHTML = activeRuns.map(run => {
        const objectivesDone = run.objectives.filter(o => o.completed_at).length;
        const rejections = Object.entries(run.rejections || {})
            .sort((a, b) => b[1] - a[1]).slice(0, 3)
            .map(([k, v]) => `${k}:${v}`).join(', ');
        const outcomeClass = run.outcome.status === 'completed' ? 'pd-outcome-completed' : 'pd-outcome-failed';
        const outcomeText = run.outcome.status === 'completed' ? 'completed' : `failed (${run.outcome.fail_reason || '?'})`;
        return `<tr>
            <td>${run.profile}</td>
            <td>${run.seed}</td>
            <td class="${outcomeClass}">${outcomeText}</td>
            <td>${run.phase2_start_sol ?? '—'}</td>
            <td>${objectivesDone}/${run.objectives.length}</td>
            <td>${run.outcome.score ?? 0}</td>
            <td>${run.actions.ok ?? '—'}/${run.actions.attempted ?? '—'}</td>
            <td class="pd-rejections">${rejections || '—'}</td>
        </tr>`;
    }).join('');
}

render();
</script>

</body>
</html>
