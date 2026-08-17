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
            'log' => $data['log'] ?? [],
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
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3"></script>
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
        <div class="pd-event-toggles">
            <span class="pd-event-toggles-label">Ereignis-Marker:</span>
            <label><input type="checkbox" id="pd-event-cc" checked> CC-Level-Aufstieg</label>
            <label><input type="checkbox" id="pd-event-advisor" checked> Berater angestellt</label>
        </div>

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

        <div class="pd-log-card">
            <div class="pd-log-header">
                <h3>Aktions-Log</h3>
                <select id="pd-log-run-select"></select>
            </div>
            <div class="pd-log-scroll">
                <table class="pd-log-table">
                    <thead><tr><th>Sol</th><th>Aktion</th><th>Status</th></tr></thead>
                    <tbody id="pd-log-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script id="playtest-data" type="application/json"><?= json_encode($runs, JSON_UNESCAPED_SLASHES) ?></script>
<script>
const RUNS = JSON.parse(document.getElementById('playtest-data').textContent);

// Stable color per individual run (profile+seed) — cycles through a fixed
// palette so every run line is distinguishable, even when several runs
// share the same profile name.
const PALETTE = ['#7fbbff', '#ff9f7f', '#7fff9f', '#e0a0ff', '#ffe07f', '#7fe0e0', '#ff7fa0', '#b0ff7f', '#ff7fd0', '#7fa0ff', '#ffd07f', '#a0ff7f'];
const runColors = {};
function colorFor(run) {
    const key = `${run.profile}-${run.seed}-${run.file}`;
    if (!(key in runColors)) {
        runColors[key] = PALETTE[Object.keys(runColors).length % PALETTE.length];
    }
    return runColors[key];
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
        swatch.style.background = colorFor(run);
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

// ── Event markers ────────────────────────────────────────────────────────
// Derived from sols[] deltas — no extra data needed beyond what's already
// snapshotted per Sol. Cached per run since RUNS is immutable after load.
const eventsCache = new Map();
function eventsFor(run) {
    if (eventsCache.has(run)) return eventsCache.get(run);
    const events = [];
    let prevCc = null;
    let prevAdvisors = null;
    run.sols.forEach(s => {
        if (prevCc !== null && s.cc_level > prevCc) {
            events.push({ sol: s.sol, type: 'cc', label: `CC → Lv${s.cc_level}` });
        }
        if (prevAdvisors !== null && s.advisors > prevAdvisors) {
            events.push({ sol: s.sol, type: 'advisor', label: `Berater #${s.advisors}` });
        }
        prevCc = s.cc_level;
        prevAdvisors = s.advisors;
    });
    eventsCache.set(run, events);
    return events;
}

if (window['chartjs-plugin-annotation']) {
    Chart.register(window['chartjs-plugin-annotation']);
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
            plugins: {
                legend: { labels: { color: '#ccc', boxWidth: 12, font: { size: 10 } } },
                annotation: { annotations: {} },
            },
        },
    });
});

function activeEventTypes() {
    const types = [];
    if (document.getElementById('pd-event-cc')?.checked) types.push('cc');
    if (document.getElementById('pd-event-advisor')?.checked) types.push('advisor');
    return types;
}

function annotationsFor(activeRuns) {
    const types = activeEventTypes();
    if (types.length === 0) return {};
    const annotations = {};
    let n = 0;
    activeRuns.forEach(run => {
        eventsFor(run).filter(e => types.includes(e.type)).forEach(e => {
            annotations[`ev${n++}`] = {
                type: 'line',
                xMin: e.sol,
                xMax: e.sol,
                borderColor: colorFor(run),
                borderWidth: 1,
                borderDash: [4, 3],
                label: {
                    display: true,
                    content: e.label,
                    position: 'start',
                    font: { size: 9 },
                    color: colorFor(run),
                    backgroundColor: 'rgba(0,0,0,0.6)',
                },
            };
        });
    });
    return annotations;
}

function render() {
    const activeRuns = [...selected].map(idx => RUNS[idx]).filter(Boolean);
    const annotations = annotationsFor(activeRuns);

    chartDefs.forEach(def => {
        const chart = charts[def.field];
        if (!chart) return;
        chart.data.datasets = activeRuns.map(run => ({
            label: runLabel(run),
            data: run.sols.map(s => ({ x: s.sol, y: s[def.field] })),
            borderColor: colorFor(run),
            backgroundColor: colorFor(run),
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.15,
        }));
        if (chart.options.plugins.annotation) {
            chart.options.plugins.annotation.annotations = annotations;
        }
        chart.update();
    });

    renderLogSelect(activeRuns);

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

// ── Log panel ────────────────────────────────────────────────────────────
const logSelect = document.getElementById('pd-log-run-select');
const logBody = document.getElementById('pd-log-body');

function renderLogSelect(activeRuns) {
    if (!logSelect) return;
    const prevValue = logSelect.value;
    logSelect.innerHTML = activeRuns.map((run, i) => `<option value="${i}">${runLabel(run)}</option>`).join('');
    const restoreIdx = [...logSelect.options].findIndex(o => o.value === prevValue);
    logSelect.selectedIndex = restoreIdx >= 0 ? restoreIdx : 0;
    renderLogTable(activeRuns[logSelect.selectedIndex] ?? null);
}

function renderLogTable(run) {
    if (!logBody) return;
    if (!run || !run.log || run.log.length === 0) {
        logBody.innerHTML = '<tr><td colspan="3" class="pd-empty">Kein Log in diesem Report (ältere Reports vor 2026-08-17 haben keins).</td></tr>';
        return;
    }
    logBody.innerHTML = run.log.map(entry => `<tr class="${entry.ok ? 'pd-log-ok' : 'pd-log-error'}">
        <td>${entry.sol}</td>
        <td>${entry.rule}</td>
        <td>${entry.ok ? 'OK' : (entry.error || 'error')}</td>
    </tr>`).join('');
}

logSelect?.addEventListener('change', () => {
    const activeRuns = [...selected].map(idx => RUNS[idx]).filter(Boolean);
    renderLogTable(activeRuns[logSelect.selectedIndex] ?? null);
});

document.getElementById('pd-event-cc')?.addEventListener('change', render);
document.getElementById('pd-event-advisor')?.addEventListener('change', render);

render();
</script>

</body>
</html>
