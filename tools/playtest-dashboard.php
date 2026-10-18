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
            'opening' => $data['opening'] ?? 'auto', // absent in reports from before T30
            'engine' => $data['engine'] ?? 'legacy', // absent in reports from before the utility bot
            'decisions' => $data['decisions'] ?? [],
            'utility_summary' => $data['utility_summary'] ?? null,
            'seed' => $data['seed'],
            'outcome' => $data['outcome'] ?? ['status' => 'unknown'],
            'phase2_start_sol' => $data['phase2_start_sol'] ?? null,
            'objectives' => $data['objectives'] ?? [],
            'actions' => $data['actions'] ?? [],
            'rejections' => $data['rejections'] ?? [],
            'sols' => $data['sols'] ?? [],
            'log' => $data['log'] ?? [],
            'project_metrics' => $data['project_metrics'] ?? ['project_durations' => [], 'last_completion_sol' => null],
            'regolith_ledger' => $data['regolith_ledger'] ?? null, // absent in reports from before the Regolith ledger
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
        <label class="pd-engine-filter">Engine:
            <select id="pd-engine-filter"><option value="">alle</option></select>
        </label>
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
            <label><input type="checkbox" id="pd-event-path" checked> Pfadgebäude Stufe 2/3</label>
        </div>

        <div class="pd-chart-stack">
            <div class="pd-chart-card"><h3>Regolith</h3><canvas id="chart-regolith"></canvas></div>
            <div class="pd-chart-card"><h3>Organika</h3><canvas id="chart-organics"></canvas></div>
            <div class="pd-chart-card"><h3>Credits</h3><canvas id="chart-credits"></canvas></div>
            <div class="pd-chart-card"><h3>AP (verfügbar)</h3><canvas id="chart-ap"></canvas></div>
            <div class="pd-chart-card"><h3>Vertrauen</h3><canvas id="chart-trust"></canvas></div>
            <div class="pd-chart-card"><h3>CC-Level</h3><canvas id="chart-cc"></canvas></div>
        </div>

        <!-- A32 (Playtest-Instrumentierung Phase C, MVP-Pflichtcharts laut
             docs/playtest-instrumentation-plan.md — die übrigen 4 Charts
             bleiben "iterativ später"). Zeigen den zuletzt einzeln ausgewählten
             Lauf, nicht alle aktiven Läufe überlagert — Stacked Areas und die
             Regolith-Quellen-Aufschlüsselung sind pro Lauf schon dicht genug. -->
        <div class="pd-chart-stack pd-chart-stack--single-run">
            <h2 class="pd-section-title">Detail-Charts (A30/A31-Metriken) — <span id="pd-detail-run-label">–</span></h2>
            <div class="pd-chart-card"><h3>AP-Bilanz (Repair / Projekt / Handlung / Rest)</h3><canvas id="chart-ap-breakdown"></canvas></div>
            <div class="pd-chart-card"><h3>Regolith-Quellen je Sol (Ledger-Töpfe)</h3><canvas id="chart-regolith-sources"></canvas></div>
            <div class="pd-chart-card"><h3>Regolith-Senken je Sol (Ledger) <span id="pd-rg-sinks-note"></span></h3><canvas id="chart-regolith-sinks"></canvas></div>
            <div class="pd-chart-card"><h3>Supply-Auslastung</h3><canvas id="chart-supply-utilization"></canvas></div>
            <div class="pd-chart-card"><h3>Pfadgebäude-Stufen (Labor / Hangar / Cantina) + CC</h3><canvas id="chart-path-levels"></canvas></div>
        </div>

        <table class="pd-summary">
            <thead>
                <tr>
                    <th>Profil</th><th>Engine</th><th>Seed</th><th>Outcome</th><th>Phase 2 ab Sol</th><th>Objectives</th><th>Score</th><th>Aktionen</th>
                    <th>AP Ungenutzt %</th><th>Rego Top-Quelle</th><th>Supply Max %</th><th>Projekte</th><th>Harvester-Umzüge</th><th>Letztes Projekt (Sol)</th>
                    <th>Pfad Lv2 / Lv3 (Sol)</th><th>CC Lv4 (Sol)</th><th>Reparatur-AP</th><th>Legacy-Anteil (K18)</th><th>Entsch./Sol (K20)</th>
                    <th>Top-Rejections</th>
                </tr>
            </thead>
            <tbody id="pd-summary-body"></tbody>
        </table>
    </div>

    <div class="pd-log-panel">
        <div class="pd-log-header">
            <h3>Aktions-Log</h3>
            <select id="pd-log-run-select"></select>
            <label><input type="checkbox" id="pd-log-decisions"> Entscheidungen (Utility)</label>
        </div>
        <div class="pd-log-scroll">
            <table class="pd-log-table">
                <thead id="pd-log-head"><tr><th>Sol</th><th>Aktion</th><th>Status</th></tr></thead>
                <tbody id="pd-log-body"></tbody>
            </table>
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

function engineTag(run) {
    return run.engine && run.engine !== 'legacy' ? `[${run.engine}]` : '';
}

function runLabel(run) {
    return `${run.profile}/${run.opening}${engineTag(run)} · seed ${run.seed}`;
}

// ── Sidebar ──────────────────────────────────────────────────────────────
const listEl = document.getElementById('pd-run-list');
const selected = new Set();

if (listEl) {
    RUNS.forEach((run, idx) => {
        const item = document.createElement('label');
        item.className = 'pd-run-item';
        item.dataset.engine = run.engine || 'legacy';
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
        text.innerHTML = `<span class="pd-run-profile">${run.profile}/${run.opening}${engineTag(run)}</span> #${run.seed} <span class="${outcomeClass}">${run.outcome.status}</span>`;
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

// Engine filter: hides sidebar rows of other engines and unticks them.
const engineFilter = document.getElementById('pd-engine-filter');
if (engineFilter) {
    [...new Set(RUNS.map(r => r.engine || 'legacy'))].sort().forEach(e => {
        engineFilter.insertAdjacentHTML('beforeend', `<option value="${e}">${e}</option>`);
    });
    engineFilter.addEventListener('change', () => {
        listEl?.querySelectorAll('.pd-run-item').forEach(item => {
            const hide = engineFilter.value !== '' && item.dataset.engine !== engineFilter.value;
            item.style.display = hide ? 'none' : '';
            const cb = item.querySelector('input');
            if (hide && cb.checked) {
                cb.checked = false;
                selected.delete(Number(cb.dataset.idx));
            }
        });
        render();
    });
}

// ── Event markers ────────────────────────────────────────────────────────
// Derived from sols[] deltas — no extra data needed beyond what's already
// snapshotted per Sol. Cached per run since RUNS is immutable after load.
const PATH_BUILDINGS = [
    { id: 31, name: 'Labor', color: '#7fbbff' },
    { id: 44, name: 'Hangar', color: '#ffe07f' },
    { id: 52, name: 'Cantina', color: '#ff7fa0' },
];

// Highest level of any instance of a building per Sol snapshot.
function buildingLevel(snapshot, buildingId) {
    let lv = 0;
    Object.entries(snapshot.buildings ?? {}).forEach(([key, info]) => {
        if (Number(key.split(':')[0]) === buildingId) lv = Math.max(lv, info.level ?? 0);
    });
    return lv;
}

// {level: first Sol reaching it} for a path building.
function pathLevelSols(run, buildingId) {
    const out = {};
    (run.sols ?? []).forEach(s => {
        const lv = buildingLevel(s, buildingId);
        for (let l = 1; l <= lv; l++) if (out[l] === undefined) out[l] = s.sol;
    });
    return out;
}

function firstCcSol(run, level) {
    return (run.sols ?? []).find(s => s.cc_level >= level)?.sol;
}

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
    PATH_BUILDINGS.forEach(pb => {
        [2, 3].forEach(lv => {
            const first = pathLevelSols(run, pb.id)[lv];
            if (first !== undefined) events.push({ sol: first, type: 'path', label: `${pb.name} Lv${lv}` });
        });
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
    { id: 'chart-organics', field: 'organics' },
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
    if (document.getElementById('pd-event-path')?.checked) types.push('path');
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
            <td>${run.profile}/${run.opening}</td>
            <td>${run.engine || 'legacy'}</td>
            <td>${run.seed}</td>
            <td class="${outcomeClass}">${outcomeText}</td>
            <td>${run.phase2_start_sol ?? '—'}</td>
            <td>${objectivesDone}/${run.objectives.length}</td>
            <td>${run.outcome.score ?? 0}</td>
            <td>${run.actions.ok ?? '—'}/${run.actions.attempted ?? '—'}</td>
            <td>${apUnspentPct(run)}</td>
            <td>${regolithTopSource(run)}</td>
            <td>${supplyMaxPct(run)}</td>
            <td>${run.project_metrics?.project_durations?.length ?? 0}</td>
            <td>${harvesterMoveCount(run)}</td>
            <td>${run.project_metrics?.last_completion_sol ?? '—'}</td>
            <td>${pathTierCell(run)}</td>
            <td>${firstCcSol(run, 4) ?? 'nicht erreicht'}</td>
            <td>${repairAp(run)}</td>
            <td>${run.utility_summary ? run.utility_summary.legacy_share + '%' : '—'}</td>
            <td>${run.utility_summary?.median_decisions_per_sol ?? '—'}</td>
            <td class="pd-rejections">${rejections || '—'}</td>
        </tr>`;
    }).join('');
}

// ── A33 summary-table helpers (Phase D) ─────────────────────────────────
// All derived purely from data already in the JSON report (A30/A31 Sol
// snapshots + project_metrics) — no new fields needed beyond project_metrics,
// already added to the PHP pass-through above.

function apUnspentPct(run) {
    const sols = run.sols ?? [];
    if (sols.length === 0) return '—';
    const totalUnspent = sols.reduce((sum, s) => sum + (s.ap?.unspent ?? 0), 0);
    const totalInflow = sols.reduce((sum, s) => sum + (s.ap?.inflow ?? 0), 0);
    if (totalInflow <= 0) return '—';
    return `${Math.round((totalUnspent / totalInflow) * 100)}%`;
}

// regolith_sources buckets (RunReport::regolithSources()). Reports before the
// Regolith ledger only carry the first six; the rest then stays 0 and
// 'harvester' is the old remainder bucket (incl. import/recycling/geology).
const RG_SOURCE_KEYS = ['harvester', 'geology', 'mission', 'find', 'find_flight', 'trade', 'concern', 'import', 'recycling', 'event', 'other'];
const RG_SOURCE_LABELS = {
    harvester: 'Harvester', geology: 'Geologie', mission: 'Mission', find: 'Fund (selbst)', find_flight: 'Bergungsflug',
    trade: 'Handel', concern: 'Anliegen', import: 'Import', recycling: 'Recycling', event: 'Ereignis', other: 'Sonstige',
};
const RG_SOURCE_COLORS = ['#b0ff7f', '#6fcf4f', '#7fbbff', '#c99bff', '#7fffe0', '#ffe07f', '#ffb07f', '#ff7fa0', '#bbbbbb', '#ff5050', '#666'];
// Ledger sink tags (regolith_ledger.per_sol[].out); unlisted tags are summed as 'other'.
const RG_SINK_KEYS = ['build_place', 'build_levelup', 'cc_upgrade', 'repair', 'research_levelup', 'cantina_sell', 'merchant_sell', 'wager', 'other'];
const RG_SINK_LABELS = ['Platzieren', 'Stufe', 'CC-Ausbau', 'Reparatur', 'Forschung', 'Cantina-Verkauf', 'Händler-Verkauf', 'Wette', 'Sonstige'];
const RG_SINK_COLORS = ['#ff7f7f', '#ffb07f', '#ffffff', '#ffe07f', '#7fbbff', '#c99bff', '#7fffe0', '#ff7fa0', '#666'];

function regolithSinkValue(row, key) {
    const out = row.out ?? {};
    if (key !== 'other') return out[key] ?? 0;
    return Object.entries(out).filter(([tag]) => !RG_SINK_KEYS.includes(tag)).reduce((sum, [, v]) => sum + v, 0);
}

function regolithTopSource(run) {
    const totals = Object.fromEntries(RG_SOURCE_KEYS.map(k => [k, 0]));
    (run.sols ?? []).forEach(s => {
        Object.keys(totals).forEach(k => { totals[k] += s.regolith_sources?.[k] ?? 0; });
    });
    const labels = RG_SOURCE_LABELS;
    const [topKey, topValue] = Object.entries(totals).sort((a, b) => b[1] - a[1])[0];
    return topValue > 0 ? `${labels[topKey]} (${topValue})` : '—';
}

function supplyMaxPct(run) {
    const sols = run.sols ?? [];
    if (sols.length === 0) return '—';
    const max = Math.max(...sols.map(s => s.supply?.utilization ?? 0));
    return `${Math.round(max * 100)}%`;
}

// Opening's path building = the one with the highest level at the end.
function pathTierCell(run) {
    const last = run.sols?.[run.sols.length - 1];
    if (!last) return '—';
    return PATH_BUILDINGS.map(pb => {
        const sols = pathLevelSols(run, pb.id);
        return `${pb.name}: ${sols[2] ?? '–'} / ${sols[3] ?? '–'}`;
    }).join('<br>');
}

function repairAp(run) {
    return (run.sols ?? []).reduce((sum, s) => sum + (s.ap?.repair_spent ?? 0), 0);
}

function harvesterMoveCount(run) {
    return (run.log ?? []).filter(e => e.rule === 'relocate_harvester' && e.ok).length;
}

// ── A32 detail charts (single selected run — stacked breakdowns don't
// overlay meaningfully across runs, so these follow the same "detail run"
// the log panel already tracks via logSelect). ──────────────────────────
const detailCharts = {};
function makeStackedAreaChart(canvasId, seriesKeys, seriesLabels, seriesColors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    return new Chart(canvas, {
        type: 'line',
        data: { datasets: seriesKeys.map((key, i) => ({
            label: seriesLabels[i],
            data: [],
            borderColor: seriesColors[i],
            backgroundColor: seriesColors[i] + '55',
            borderWidth: 1,
            pointRadius: 0,
            fill: true,
            stack: 'stack0',
            tension: 0.1,
        })) },
        options: {
            responsive: true,
            animation: false,
            interaction: { mode: 'nearest', intersect: false },
            scales: {
                x: { type: 'linear', title: { display: true, text: 'Sol', color: '#888' }, ticks: { color: '#888' }, grid: { color: '#1e1e30' } },
                y: { stacked: true, ticks: { color: '#888' }, grid: { color: '#1e1e30' } },
            },
            plugins: { legend: { labels: { color: '#ccc', boxWidth: 12, font: { size: 10 } } } },
        },
    });
}

detailCharts.apBreakdown = makeStackedAreaChart(
    'chart-ap-breakdown',
    ['repair_spent', 'project_spent', 'action_spent', 'unspent'],
    ['Reparatur', 'Projekt', 'Handlung', 'Ungenutzt'],
    ['#ff7f7f', '#7fbbff', '#ffe07f', '#555']
);
detailCharts.regolithSources = makeStackedAreaChart(
    'chart-regolith-sources',
    RG_SOURCE_KEYS,
    RG_SOURCE_KEYS.map(k => RG_SOURCE_LABELS[k]),
    RG_SOURCE_COLORS
);
detailCharts.regolithSinks = makeStackedAreaChart('chart-regolith-sinks', RG_SINK_KEYS, RG_SINK_LABELS, RG_SINK_COLORS);

const supplyCanvas = document.getElementById('chart-supply-utilization');
if (supplyCanvas) {
    detailCharts.supplyUtilization = new Chart(supplyCanvas, {
        type: 'line',
        data: { datasets: [{
            label: 'Auslastung',
            data: [],
            borderColor: '#7fbbff',
            backgroundColor: '#7fbbff33',
            borderWidth: 2,
            pointRadius: 0,
            fill: true,
            tension: 0.1,
        }] },
        options: {
            responsive: true,
            animation: false,
            interaction: { mode: 'nearest', intersect: false },
            scales: {
                x: { type: 'linear', title: { display: true, text: 'Sol', color: '#888' }, ticks: { color: '#888' }, grid: { color: '#1e1e30' } },
                // utilization is a 0..1 ratio (RunReport clamps it) — a fixed
                // [0,1] axis makes the 70%-Zielmarke line visually meaningful
                // instead of rescaling per run.
                y: { min: 0, max: 1, ticks: { color: '#888', callback: v => `${Math.round(v * 100)}%` }, grid: { color: '#1e1e30' } },
            },
            plugins: {
                legend: { display: false },
                annotation: {
                    annotations: {
                        target: {
                            type: 'line', yMin: 0.7, yMax: 0.7,
                            borderColor: '#ffe07f', borderWidth: 1, borderDash: [4, 3],
                            label: { display: true, content: '70%-Zielmarke', position: 'end', font: { size: 9 }, color: '#ffe07f', backgroundColor: 'rgba(0,0,0,0.6)' },
                        },
                    },
                },
            },
        },
    });
}

detailCharts.pathLevels = (() => {
    const canvas = document.getElementById('chart-path-levels');
    if (!canvas) return null;
    const series = [...PATH_BUILDINGS, { name: 'CC', color: '#ffffff' }];
    return new Chart(canvas, {
        type: 'line',
        data: { datasets: series.map(p => ({
            label: p.name, data: [], borderColor: p.color, backgroundColor: p.color,
            borderWidth: 2, pointRadius: 0, stepped: true, borderDash: p.name === 'CC' ? [4, 3] : [],
        })) },
        options: {
            responsive: true,
            animation: false,
            scales: {
                x: { type: 'linear', title: { display: true, text: 'Sol', color: '#888' }, ticks: { color: '#888' }, grid: { color: '#1e1e30' } },
                y: { min: 0, ticks: { color: '#888', stepSize: 1 }, grid: { color: '#1e1e30' } },
            },
            plugins: { legend: { labels: { color: '#ccc', boxWidth: 12, font: { size: 10 } } } },
        },
    });
})();

function renderDetailCharts(run) {
    const label = document.getElementById('pd-detail-run-label');
    if (label) label.textContent = run ? runLabel(run) : '–';

    const sols = run?.sols ?? [];

    if (detailCharts.apBreakdown) {
        detailCharts.apBreakdown.data.datasets.forEach(ds => {
            const key = ['repair_spent', 'project_spent', 'action_spent', 'unspent'][detailCharts.apBreakdown.data.datasets.indexOf(ds)];
            ds.data = sols.map(s => ({ x: s.sol, y: s.ap?.[key] ?? 0 }));
        });
        detailCharts.apBreakdown.update();
    }
    if (detailCharts.regolithSources) {
        detailCharts.regolithSources.data.datasets.forEach(ds => {
            const key = RG_SOURCE_KEYS[detailCharts.regolithSources.data.datasets.indexOf(ds)];
            ds.data = sols.map(s => ({ x: s.sol, y: s.regolith_sources?.[key] ?? 0 }));
        });
        detailCharts.regolithSources.update();
    }
    if (detailCharts.regolithSinks) {
        const rows = run?.regolith_ledger?.per_sol ?? [];
        const note = document.getElementById('pd-rg-sinks-note');
        if (note) note.textContent = run && rows.length === 0 ? '— Lauf ohne Ledger (alter Report)' : '';
        detailCharts.regolithSinks.data.datasets.forEach((ds, i) => {
            ds.data = rows.map(r => ({ x: r.sol, y: regolithSinkValue(r, RG_SINK_KEYS[i]) }));
        });
        detailCharts.regolithSinks.update();
    }
    if (detailCharts.pathLevels) {
        const ds = detailCharts.pathLevels.data.datasets;
        PATH_BUILDINGS.forEach((pb, i) => { ds[i].data = sols.map(s => ({ x: s.sol, y: buildingLevel(s, pb.id) })); });
        ds[PATH_BUILDINGS.length].data = sols.map(s => ({ x: s.sol, y: s.cc_level }));
        detailCharts.pathLevels.update();
    }
    if (detailCharts.supplyUtilization) {
        detailCharts.supplyUtilization.data.datasets[0].data = sols.map(s => ({ x: s.sol, y: s.supply?.utilization ?? 0 }));
        detailCharts.supplyUtilization.update();
    }
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
    renderDetailCharts(activeRuns[logSelect.selectedIndex] ?? null);
}

const logHead = document.getElementById('pd-log-head');
const decisionsToggle = document.getElementById('pd-log-decisions');

function renderDecisionTable(run) {
    logHead.innerHTML = '<tr><th>Sol</th><th>Quelle</th><th>Aktion</th><th>Score</th><th>Netto</th><th>Alternativen</th><th>OK</th></tr>';
    if (!run || !run.decisions || run.decisions.length === 0) {
        logBody.innerHTML = '<tr><td colspan="7" class="pd-empty">Keine Entscheidungen (Legacy-Engine oder älterer Report).</td></tr>';
        return;
    }
    const alt = d => (d.alternatives ?? []).map(a => typeof a === 'string' ? a : (a.name ?? JSON.stringify(a))).join(', ');
    logBody.innerHTML = run.decisions.map(d => `<tr class="${d.ok ? 'pd-log-ok' : 'pd-log-error'}" title="${JSON.stringify(d.terms ?? {}).replace(/"/g, '&quot;')}">
        <td>${d.sol}</td><td>${d.source}</td><td>${d.name}</td><td>${d.score ?? '—'}</td><td>${d.net ?? '—'}</td>
        <td>${alt(d)}</td><td>${d.ok ? 'OK' : 'x'}</td>
    </tr>`).join('');
}

function renderLogTable(run) {
    if (!logBody) return;
    if (decisionsToggle?.checked) return renderDecisionTable(run);
    if (logHead) logHead.innerHTML = '<tr><th>Sol</th><th>Aktion</th><th>Status</th></tr>';
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
    renderDetailCharts(activeRuns[logSelect.selectedIndex] ?? null);
});

decisionsToggle?.addEventListener('change', () => {
    const activeRuns = [...selected].map(idx => RUNS[idx]).filter(Boolean);
    renderLogTable(activeRuns[logSelect.selectedIndex] ?? null);
});
document.getElementById('pd-event-path')?.addEventListener('change', render);
document.getElementById('pd-event-cc')?.addEventListener('change', render);
document.getElementById('pd-event-advisor')?.addEventListener('change', render);

render();
</script>

</body>
</html>
