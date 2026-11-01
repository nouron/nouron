<script lang="ts">
	// Port of resources/views/colony/hexview.blade.php (ADR 0006 stage 3). The SVG renderer
	// is shared with Blade (public/js/colony-hexgrid.js); the Alpine part is ported here.
	import '#lib/styles/colony-shared.css';
	import '#lib/styles/colony-view.css';
	import '../../../../../public/css/resources.css';
	import '../../../../../public/js/colony-hexgrid.js';

	import { onMount, tick } from 'svelte';
	import { fade, fly } from 'svelte/transition';
	import { page } from '$app/state';
	import { api, ApiError, BACKEND_URL } from '#lib/stores/auth.ts';
	import { refreshShell, setAp, setHint, shell, type Shell } from '#lib/stores/shell.ts';
	import { appLink } from '#lib/links.ts';
	import { t } from '#lib/i18n.ts';
	import ApUpgradeButton from '#lib/components/ApUpgradeButton.svelte';
	import TileTerrain from './TileTerrain.svelte';
	import LeveldownDialog, { type LeveldownResult } from './LeveldownDialog.svelte';
	import type { ActionResult, Building, ColonyData, Tile } from './types.ts';

	// Legacy global from public/js/colony-hexgrid.js (framework-free, set on window).
	const hex = (
		window as unknown as {
			NouronHexGrid: {
				initHexGrid(el: HTMLElement, tiles: Tile[], opts: object): void;
				isBuildableTile(tile: Tile): boolean;
				TILE_LABELS: Record<string, string>;
				EVENT_TYPE_NAMES: Record<string, string>;
			};
		}
	).NouronHexGrid;

	let data = $state<ColonyData | null>(null);
	let loadError = $state('');
	let selectedTile = $state<Tile | null>(null);
	let gridEl = $state<HTMLElement>();
	let toast = $state<{ msg: string; type: 'error' | 'info' } | null>(null);
	let levelupNotice = $state<string | null>(null);
	let eventDiscovery = $state<Tile | null>(null);
	let leveldownOf = $state<Building | null>(null);
	let busy = $state(false);
	let repairHover = $state(false);
	let titleHover = $state(false);

	// Plain (non-reactive) renderer bookkeeping, same as the Alpine component's _-fields.
	const polygons = new Map<string, SVGPolygonElement & { _defaultStroke?: string; _defaultStrokeW?: string }>();
	const positions = new Map<string, { cx: number; cy: number }>();
	const panState = { x: 0, y: 0 };

	const key = (tile: { q: number; r: number }) => `${tile.q},${tile.r}`;
	const ROMAN = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
	const roman = (n: number) => ROMAN[n - 1] ?? String(n);
	const PLACEHOLDER =
		"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44'%3E%3Crect width='44' height='44' rx='6' fill='%23e0e0e8'/%3E%3C/svg%3E";

	const apAvailable = $derived($shell?.colony_ap ?? data?.apAvailable ?? 0);

	function buildingForTile(tile: Tile | null): Building | null {
		if (!tile || !data) return null;
		const d = data;
		if (tile.q === 0 && tile.r === 0) return d.buildings.find((b) => b.building_id === d.ccBuildingId) ?? null;
		return d.buildings.find((b) => b.tile_x === tile.q && b.tile_y === tile.r) ?? null;
	}
	const selectedBuilding = $derived(buildingForTile(selectedTile));

	const buildingLabel = (k: string) => data?.buildingCatalog?.[k]?.label ?? k;
	const tileTypeName = (type: string) => hex.TILE_LABELS[type] ?? type;
	function tileHeading(tile: Tile) {
		if (tile.q === 0 && tile.r === 0) return t('techtree.building_commandCenter');
		if (!tile.is_explored) return t('colony.tile_unknown');
		return tileTypeName(tile.tile_type);
	}
	function eventTypeName(type: string) {
		if (['find_small', 'find_medium', 'find_large', 'find_false'].includes(type)) return t(`colony.${type}`);
		return hex.EVENT_TYPE_NAMES[type] ?? t('colony.event_unknown');
	}
	const eventDescription = (type: string) =>
		['find_small', 'find_medium', 'find_large'].includes(type) ? t(`colony.${type}_desc`) : '';

	// Ring-staggered explore cost (raw config, see the ponytail note in colony-hexgrid.js).
	const exploreCostFor = (tile: Tile) => data?.exploreCostPerRing[tile.ring] ?? data?.exploreCostDefault ?? 1;

	// Same tier thresholds as EncounterService::resolveOutcome() (game.encounter.*).
	function conditionTone(b: Building | null) {
		if (!b || b.level < 1 || !data) return 'neutral';
		const pct = b.status_points / (b.max_status_points ?? 20);
		if (pct < data.criticalThresholdPct) return 'danger';
		if (pct < data.damagedThresholdPct) return 'warning';
		return 'neutral';
	}
	const canRepair = (b: Building) => b.level >= 1 && b.status_points < (b.max_status_points ?? 20);
	const repairStepPct = (b: Building) => Math.round(100 / (b.max_status_points ?? 20));
	const canLevelUp = (b: Building | null) => !!b && (b.max_level === null || b.level < b.max_level);
	const canLeveldown = (b: Building) =>
		b.building_id === data?.ccBuildingId ? b.level > 1 : b.tile_x !== null && b.tile_x !== undefined;

	// CC level gate (mirror of BuildingService::ccLevelTooLow; the server stays the source of truth).
	function ccGateReason(b: Building): string | null {
		if (!data || b.building_id === data.ccBuildingId) return null;
		const d = data;
		const cc = d.buildings.find((x) => x.building_id === d.ccBuildingId);
		const required = b.level + 1 - d.buildingCcLevelOffset;
		if ((cc ? cc.level : d.ccLevel) >= required) return null;
		return d.ccGateTemplate.replace(':level', String(required));
	}

	// Find-card flight button: the flight's own costs must be payable (else the server refuses).
	function flightShortfall(tile: Tile): string | null {
		const costs = tile.find?.action?.costs;
		if (!costs || !data) return null;
		if (costs.ap_cost > apAvailable)
			return t('missions.gate_nav_ap_hint', { available: apAvailable, required: costs.ap_cost });
		if (costs.organika > data.organika)
			return t('missions.gate_organika_hint', { available: data.organika, required: costs.organika });
		return null;
	}

	// ── Grid ────────────────────────────────────────────────────────────────

	function redrawGrid() {
		if (!data || !gridEl) return;
		positions.clear();
		hex.initHexGrid(gridEl, data.tiles, {
			onSelect: (tile: Tile) => selectTile(tile),
			polygonMap: polygons,
			positionMap: positions,
			buildings: data.buildings,
			buildMode: false,
			pendingBuilding: null,
			activeHint: $shell?.active_hint ?? null,
			harvesterMoveMode: false,
			harvesterBuilding: null,
			tileYields: data.tileYields,
			repairDisplayThreshold: data.repairDisplayThreshold,
			damagedThresholdPct: data.damagedThresholdPct,
			criticalThresholdPct: data.criticalThresholdPct,
			conditionTone,
			relocateApPerHex: data.relocateApPerHex,
			siteMarkerTooltip: t('colony.site_marker_tooltip'),
			panState,
			regolithFallbackTiles: data.regolithFallbackTiles,
			regolithFallbackTileHint: t('colony.regolith_fallback_tile_hint')
		});
		if (selectedTile) highlight(selectedTile);
	}

	function highlight(tile: Tile) {
		const cur = polygons.get(key(tile));
		cur?.setAttribute('stroke', '#c0392b');
		cur?.setAttribute('stroke-width', '3');
	}

	function selectTile(tile: Tile) {
		if (selectedTile) {
			const prev = polygons.get(key(selectedTile));
			prev?.setAttribute('stroke', prev._defaultStroke ?? '#555');
			prev?.setAttribute('stroke-width', prev._defaultStrokeW ?? '1.5');
		}
		selectedTile = tile;
		highlight(tile);
	}

	// Deep link ?building=ID&instance=N (techtree detail panel, building chips): select that
	// instance's tile and pan to it when it lies outside the visible viewBox.
	function selectBuildingInstance(buildingId: number, instanceId: number | null) {
		if (!data) return;
		const d = data;
		const isCc = (b: Building) => b.building_id === d.ccBuildingId;
		const placed = (b: Building) => isCc(b) || (b.tile_x !== null && b.tile_y !== null);
		const b =
			instanceId === null
				? d.buildings
						.filter((x) => x.building_id === buildingId && placed(x))
						.sort((x, y) => x.instance_id - y.instance_id)[0]
				: d.buildings.find((x) => x.building_id === buildingId && x.instance_id === instanceId);
		if (!b || !placed(b)) return;
		const tile = d.tiles.find((x) => (isCc(b) ? x.q === 0 && x.r === 0 : x.q === b.tile_x && x.r === b.tile_y));
		if (!tile) return;
		const svg = gridEl?.querySelector('svg');
		const pos = positions.get(key(tile));
		if (svg && pos) {
			const vb = svg.viewBox.baseVal;
			const m = 40;
			const inside =
				pos.cx >= vb.x + m && pos.cx <= vb.x + vb.width - m && pos.cy >= vb.y + m && pos.cy <= vb.y + vb.height - m;
			if (!inside) {
				panState.x = pos.cx - vb.width / 2;
				panState.y = pos.cy - vb.height / 2;
				redrawGrid();
			}
		}
		selectTile(tile);
	}

	// ── Actions ─────────────────────────────────────────────────────────────

	let toastTimer: ReturnType<typeof setTimeout>;
	function showToast(msg: string, type: 'error' | 'info' = 'error') {
		clearTimeout(toastTimer);
		toast = { msg, type };
		toastTimer = setTimeout(() => (toast = null), 3500);
	}
	let lvlTimer: ReturnType<typeof setTimeout>;
	function showLevelup(label: string) {
		clearTimeout(lvlTimer);
		levelupNotice = label;
		lvlTimer = setTimeout(() => (levelupNotice = null), 2200);
	}

	/** POST an action; the server answers rule violations with 422 + {ok:false,error,message}. */
	async function act(path: string, body: object): Promise<ActionResult> {
		busy = true;
		try {
			return await api<ActionResult>(path, { method: 'POST', body, allowError: true });
		} catch {
			return { ok: false, message: t('colony.network_error') };
		} finally {
			busy = false;
		}
	}

	/** Merge an action response into local state, then resync the header (AP, resources, hint). */
	async function apply(res: ActionResult) {
		if (!data) return;
		const d = data;
		if (res.tiles) d.tiles = res.tiles;
		if (res.tile) {
			const i = d.tiles.findIndex((x) => x.q === res.tile!.q && x.r === res.tile!.r);
			if (i !== -1) d.tiles[i] = res.tile;
		}
		if (res.building) {
			const b = res.building;
			const i = d.buildings.findIndex((x) => x.building_id === b.building_id && x.instance_id === b.instance_id);
			if (i !== -1) d.buildings[i] = b;
			else d.buildings.push(b);
		}
		for (const k of ['regolith', 'werkstoffe', 'organika', 'freeSupply'] as const) {
			if (res[k] !== undefined) d[k] = res[k];
		}
		if (res.apAvailable !== undefined) setAp(res.apAvailable);
		if ('activeHint' in res) setHint(res.activeHint as Shell['active_hint']);
		if (selectedTile) selectedTile = d.tiles.find((x) => x.q === selectedTile!.q && x.r === selectedTile!.r) ?? null;
		await tick();
		redrawGrid();
		refreshShell();
	}

	const fail = (res: ActionResult) => showToast(res.message ?? res.error ?? '', 'error');

	async function explore(tile: Tile) {
		const res = await act('/colony/tile/explore', { q: tile.q, r: tile.r });
		if (res.ok) await apply(res);
		else fail(res);
	}

	async function deepScan(tile: Tile) {
		const res = await act('/colony/tile/deep-scan', { q: tile.q, r: tile.r });
		if (!res.ok) return fail(res);
		await apply(res);
		if (res.tile?.event_type) eventDiscovery = res.tile;
	}

	async function salvage(tile: Tile) {
		const rg = tile.find?.rg ?? 0;
		const res = await act('/colony/tile/salvage', { q: tile.q, r: tile.r });
		if (!res.ok) return fail(res);
		await apply(res);
		if (res.completed) showToast(t('colony.find_salvaged', { rg }), 'info');
	}

	// T30 §13.4: the find card's flight button dispatches the ship findAction() chose.
	async function findFlight(tile: Tile) {
		const action = tile.find?.action;
		if (!action || action.action !== 'flight') return;
		const res = await act(`/colony/hangar/${action.bay}/dispatch`, {
			mission_key: 'mission_find_salvage',
			difficulty: 'normal',
			target: { q: tile.q, r: tile.r }
		});
		if (res.ok) await apply(res);
		else fail(res);
	}

	async function repair(b: Building) {
		const res = await act('/colony/building/repair', { building_id: b.building_id, instance_id: b.instance_id ?? 1 });
		if (res.ok) await apply(res);
		else fail(res);
	}

	async function investAp(b: Building) {
		const res = await act('/colony/building/invest', { building_id: b.building_id, instance_id: b.instance_id ?? 1 });
		if (!res.ok) {
			if (res.error === 'building_ap_cap' && b.ap_cap) {
				// Server says today's allowance is gone: lock the bar without a reload.
				const cap = res.cap ?? b.ap_cap.cap;
				await apply({ ok: true, building: { ...b, ap_cap: { cap, spent: cap, remaining: 0 } } });
			}
			let msg = res.message ?? res.error ?? '';
			if (res.error === 'resource_limit' && res.cost?.[3])
				msg += ` (${t('colony.regolith_needed', { needed: res.cost[3], have: data?.regolith ?? 0 })})`;
			return showToast(msg, 'error');
		}
		if (res.ap_cap !== undefined && res.building) res.building.ap_cap = res.ap_cap;
		await apply(res);
		if (res.leveled_up && res.building) showLevelup(res.building.label);
		if (res.showHarvesterMoveTip) showToast(t('colony.onboarding_trigger_harvester_move'), 'info');
	}

	async function leveldownDone(res: LeveldownResult) {
		leveldownOf = null;
		if (!res.ok) return fail(res as ActionResult);
		await apply(res as ActionResult);
		if (res.tile_released) document.querySelector('.tile-panel')?.scrollTo({ top: 0 });
		showToast(res.construction_cancelled ? t('colony.leveldown_cancelled_done') : t('colony.leveldown_done'), 'info');
	}

	// ── Map header ──────────────────────────────────────────────────────────

	const findCounters = $derived.by(() => {
		const tiles = data?.tiles ?? [];
		return t('colony.find_header', {
			signals: tiles.filter((x) => x.has_signal || x.find).length,
			scanned: tiles.filter((x) => x.find).length,
			open: tiles.reduce((sum, x) => sum + (x.find && !x.find.false ? x.find.rg : 0), 0)
		});
	});

	const legend: [string, string][] = [
		['buildable', 'legend_buildable'],
		['soon', 'legend_soon_buildable'],
		['zonefog', 'legend_zone_fog'],
		['explorefog', 'legend_explore_fog'],
		['regolith', 'legend_regolith'],
		['cc', 'legend_cc'],
		['hazard', 'legend_hazard'],
		['impassable', 'legend_impassable'],
		['event', 'legend_event']
	];
	const swatchText: Record<string, string> = { zonefog: '+', explorefog: '?' };

	// The hint bar lives in the shell; redraw when it changes so the grid's hint highlight follows.
	let lastHint: unknown = undefined;
	$effect(() => {
		const h = $shell?.active_hint ?? null;
		if (lastHint !== undefined && JSON.stringify(h) !== JSON.stringify(lastHint)) redrawGrid();
		lastHint = h;
	});

	// Redraw when the canvas width crosses the renderer's mobile breakpoint (600px).
	let wasMobile: boolean | null = null;
	function onResize() {
		const mobile = (gridEl?.clientWidth ?? 0) < 600;
		if (wasMobile !== null && mobile !== wasMobile) redrawGrid();
		wasMobile = mobile;
	}

	onMount(() => {
		api<ColonyData>('/colony')
			.then(async (d) => {
				data = d;
				await tick();
				redrawGrid();
				onResize();
				const linked = parseInt(page.url.searchParams.get('building') ?? '', 10);
				if (linked) selectBuildingInstance(linked, parseInt(page.url.searchParams.get('instance') ?? '', 10) || null);
			})
			.catch((e) => {
				if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});
		window.addEventListener('resize', onResize);
		return () => window.removeEventListener('resize', onResize);
	});
</script>

{#if loadError}
	<p class="error load-error" role="alert">{loadError}</p>
{/if}

<div class="hex-page" aria-busy={busy}>
	<div class="hex-layout">
		<div class="hex-canvas-wrap">
			{#if data?.merchantVisit}
				<a href={appLink('/colony/bar')} class="merchant-notify merchant-notify--floating">
					🛸 {t('colony.merchant_in_system')}
				</a>
			{/if}

			{#if data?.supplyCapFull}
				<div class="supply-banner" role="alert">{t('colony.onboarding_trigger_supply_full')}</div>
			{/if}

			<div class="hex-canvas" bind:this={gridEl}></div>

			{#if data}
				<div class="canvas-info-bar">
					<span class="find-counters">{findCounters}</span>
					<details class="hex-legend">
						<summary class="info-bar-btn">{t('colony.legend_title')}</summary>
						<ul class="hex-legend__list">
							{#each legend as [mod, label] (mod)}
								<li class="hex-legend__item">
									<span class="hex-legend__swatch hex-legend__swatch--{mod}">{swatchText[mod] ?? ''}</span>
									<span>{t(`colony.${label}`)}</span>
								</li>
							{/each}
						</ul>
						<p class="hex-legend__help">{t('colony.find_help', { total: data.findTotal })}</p>
					</details>
				</div>
			{/if}
		</div>

		<aside class="tile-panel">
			{#if selectedTile && data}
				{@const tile = selectedTile}
				{@const b = selectedBuilding}

				<!-- Context title: name + level; the full "Level x / max" sits in a hover popup. -->
				<div class="tile-panel-title">
					{#if b}
						<div class="tile-panel-title__row">
							<!-- svelte-ignore a11y_no_static_element_interactions -->
							<span
								class="tile-panel-title__name tile-panel-title__name--hoverable"
								onmouseenter={() => (titleHover = true)}
								onmouseleave={() => (titleHover = false)}
							>
								<span>{buildingLabel(b.building_key)}</span>
								{#if b.level > 0}
									<span>{b.tier_label ? ` ${roman(b.level)} – ${b.tier_label}` : ` | ${b.level}`}</span>
								{/if}
								{#if titleHover && b.level > 0}
									<div class="res-popup">
										{#if b.tier_label}
											{b.tier_label} — {roman(b.level)}{b.max_level ? ` / ${roman(b.max_level)}` : ''}
										{:else if b.max_level}
											{t('colony.level_popup', { level: b.level, max: b.max_level })}
										{:else}
											{t('colony.level_popup_uncapped', { level: b.level })}
										{/if}
									</div>
								{/if}
							</span>
						</div>
					{:else}
						<span class="tile-panel-title__name">{tileHeading(tile)}</span>
					{/if}
				</div>

				<!-- Action strip: quick actions on desktop, the primary action surface on mobile. -->
				<div class="tile-panel-action-strip">
					<div class="tile-actions">
						{#if !tile.is_explored}
							<button class="tile-action-btn" onclick={() => explore(tile)}>
								<span class="tile-action-btn__body">{t('colony.explore')}</span>
								<span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true">{exploreCostFor(tile)} AP</span>
							</button>
						{/if}
						{#if tile.has_signal && !tile.is_deep_scanned}
							<button class="tile-action-btn" onclick={() => deepScan(tile)}>
								<span class="tile-action-btn__body">{t('colony.deep_scan')}</span>
								<span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true">{data.deepScanAp} AP</span>
							</button>
						{/if}
						{#if b && b.level >= 1}
							{@const ok = canRepair(b)}
							{@const tone = conditionTone(b)}
							<button
								class="tile-action-btn tile-action-btn--barred"
								class:tile-action-btn--secondary={!ok}
								class:tile-action-btn--hascap={!ok}
								class:tile-action-btn--capped={!ok}
								aria-disabled={ok ? undefined : 'true'}
								aria-describedby={ok ? undefined : 'repair-full-tip'}
								onmouseenter={() => (repairHover = true)}
								onmouseleave={() => (repairHover = false)}
								onclick={() => ok && repair(b)}
							>
								<span class="tile-action-btn__body">
									<span class="tile-action-btn__main">{t('colony.repair')} +{repairStepPct(b)} %</span>
								</span>
								<span class="tile-action-btn__costs">
									{#if ok && b.repair_cost > 0}
										<span
											class="ap-chip ap-cost-chip rg-cost-chip"
											class:rg-cost-chip--short={b.repair_cost > data.regolith}
											aria-hidden="true">{b.repair_cost} Rg</span
										>
									{/if}
									<span class="ap-chip ap-cost-chip ap-chip--build" aria-hidden="true">1 AP</span>
								</span>
								{#if ok}
									<span
										class="btn-segbar btn-segbar--status"
										class:btn-segbar--status--warning={tone === 'warning'}
										class:btn-segbar--status--danger={tone === 'danger'}
										aria-hidden="true"
									>
										{#each Array.from({ length: b.max_status_points ?? 20 }, (_, k) => k + 1) as i (i)}
											<span
												class="btn-seg"
												class:btn-seg--filled={i <= b.status_points}
												class:btn-seg--ghost={repairHover && i === Math.floor(b.status_points) + 1}
											></span>
										{/each}
									</span>
								{/if}
								<span id="repair-full-tip" class="tile-action-btn__tip" role="tooltip"
									>{t('colony.repair_full_tooltip')}</span
								>
							</button>
						{/if}
						{#if b && canLevelUp(b)}
							<ApUpgradeButton
								row={b}
								label={b.ap_spend === 0
									? t('colony.levelup_start', { level: b.level + 1, ap: b.ap_for_levelup })
									: `${t('colony.invest_ap')} ${b.ap_spend}/${b.ap_for_levelup}`}
								secondary={canRepair(b)}
								gateReason={ccGateReason(b)}
								rg={b.ap_spend === 0 ? (b.levelup_cost ?? 0) : 0}
								regolith={data.regolith}
								onclick={() => investAp(b)}
							/>
						{/if}
						{#if b?.building_key === 'building_harvester' && b.in_transit}
							<p class="tile-action-note">{t('colony.harvester_in_transit')}</p>
						{/if}
						<!-- ponytail: build mode + harvester relocation buttons land in stage 3d. -->
					</div>
				</div>

				<div class="tile-panel-body">
					{#if b}
						<div class="tile-building">
							{#if b.image_slug}
								<div class="building-detail-wrap">
									<div class="building-detail-img-wrap">
										<img
											class="building-detail-img"
											src="{BACKEND_URL}/img/buildings/{b.image_slug}.webp"
											alt={b.label}
											onerror={(e) => {
												const img = e.currentTarget as HTMLImageElement;
												img.onerror = null;
												img.src = PLACEHOLDER;
											}}
										/>
									</div>
								</div>
							{/if}
							{#if b.description}<p class="building-detail-description">{b.description}</p>{/if}

							{#if b.required_list?.length}
								<div class="detail-row">
									<span class="detail-row-label">{t('techtree.detail_required')}</span>
									<ul class="detail-list detail-list--chips">
										{#each b.required_list as part, idx (idx)}
											<li><span class="res-chip res-chip--neutral">{part}</span></li>
										{/each}
									</ul>
								</div>
							{/if}

							{#if (b.workplaces ?? 0) > 0}
								<p class="tile-building-colonists">{t('colony.tile_colonists')}: {b.workplaces}</p>
							{/if}

							{#if canLevelUp(b) && (b.levelup_cost ?? 0) > 0}
								<p class="tile-building-levelup-cost">
									<span>{t('colony.levelup_cost_label')}</span>
									<span class="res-chip res-RG"
										><span class="res-abbr">RG</span><span class="res-amount">{b.levelup_cost}</span></span
									>
									<span
										>{b.ap_spend > 0 ? t('colony.levelup_cost_already_paid') : t('colony.levelup_cost_suffix')}</span
									>
								</p>
							{/if}
							{#if canLevelUp(b) && b.unlocks_next_level?.length}
								<p class="tile-building-unlocks">
									{t('techtree.detail_unlocks_next_level')}: {b.unlocks_next_level.map((l) => l.text).join(', ')}
								</p>
							{/if}
							{#if canLevelUp(b) && b.first_level_prepaid}
								<p class="tile-building-levelup-cost">{t('colony.first_level_prepaid')}</p>
							{/if}
							{#if b.level === 0}
								<div class="tile-under-construction">{t('colony.under_construction')}</div>
							{/if}

							{#if tile.regolith_remaining !== undefined}
								<p class="tile-building-regolith-remaining">
									<span>{t('colony.harvester_regolith_remaining_label')}</span>
									<span class="res-chip res-RG"
										><span class="res-abbr">RG</span><span class="res-amount"
											>{tile.regolith_remaining} / {tile.regolith_max}</span
										></span
									>
								</p>
							{/if}
							{#if tile.sols_remaining !== undefined && tile.sols_remaining !== null}
								<p
									class="tile-building-sols-remaining"
									class:tile-building-sols-remaining--warn={tile.sols_remaining <= 3}
								>
									<span>{t('colony.harvester_sols_remaining_label')}</span>
									<span>{t('colony.sols_remaining', { n: tile.sols_remaining })}</span>
								</p>
							{/if}

							<details class="tile-terrain-disclosure">
								<summary>{t('colony.terrain_details')}</summary>
								<TileTerrain
									{tile}
									built
									{tileTypeName}
									{eventTypeName}
									{flightShortfall}
									onsalvage={salvage}
									onflight={findFlight}
								/>
							</details>

							<!-- Rückbau: free of charge (no AP chip), always confirmed in the dialog. -->
							{#if canLeveldown(b)}
								<div class="tile-leveldown">
									<button type="button" class="tile-action-btn tile-leveldown__btn" onclick={() => (leveldownOf = b)}>
										<span class="tile-action-btn__body"
											>{b.level === 0 ? t('colony.leveldown_cancel_construction') : t('colony.leveldown')}</span
										>
									</button>
								</div>
							{/if}
						</div>
					{:else}
						<TileTerrain {tile} {tileTypeName} {eventTypeName} {flightShortfall} onsalvage={salvage} onflight={findFlight} />
					{/if}
				</div>
			{:else}
				<div class="tile-panel-body">
					<div class="tile-panel-empty"><p>{t('colony.click_tile_hint')}</p></div>
				</div>
			{/if}
		</aside>
	</div>
</div>

{#if leveldownOf}
	<LeveldownDialog
		building={leveldownOf}
		label={buildingLabel(leveldownOf.building_key)}
		onclose={() => (leveldownOf = null)}
		ondone={leveldownDone}
	/>
{/if}

{#if eventDiscovery}
	{@const ev = eventDiscovery}
	<dialog
		class="sol-modal discovery-dialog"
		{@attach (el: HTMLDialogElement) => el.showModal()}
		onclose={() => (eventDiscovery = null)}
	>
		<article>
			<header><h3>{t('colony.discovery_title')}</h3></header>
			<p class="discovery-event-name">{eventTypeName(ev.event_type ?? '')}</p>
			{#if eventDescription(ev.event_type ?? '')}
				<p class="discovery-event-desc">{eventDescription(ev.event_type ?? '')}</p>
			{/if}
			<footer><button onclick={() => (eventDiscovery = null)}>{t('colony.discovery_dismiss')}</button></footer>
		</article>
	</dialog>
{/if}

{#if toast}
	<div class="colony-toast colony-toast--{toast.type}" aria-live="polite" role="status" transition:fade>
		{toast.msg}
	</div>
{/if}

{#if levelupNotice}
	<div class="colony-levelup-notice" aria-live="polite" in:fly={{ y: 30, duration: 350 }} out:fade>
		<span class="levelup-icon">✓</span>
		<span>{t('colony.levelup_built')}</span>
		<strong>{levelupNotice}</strong>
	</div>
{/if}

<style>
	.load-error {
		padding: var(--space-md);
	}
</style>
