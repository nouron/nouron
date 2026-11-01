<script lang="ts">
	// Port of resources/views/colony/hexview.blade.php (ADR 0006 stage 3). The SVG renderer
	// is shared with Blade (public/js/colony-hexgrid.js); the Alpine part is ported here.
	import '#lib/styles/colony-shared.css';
	import '#lib/styles/colony-view.css';
	import '../../../../../public/js/colony-hexgrid.js';

	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { shell } from '#lib/stores/shell.ts';
	import { appLink } from '#lib/links.ts';
	import { t } from '#lib/i18n.ts';

	type Tile = {
		q: number;
		r: number;
		tile_type: string;
		has_signal?: boolean;
		find?: { rg: number; false?: boolean } | null;
		[k: string]: unknown;
	};
	type Building = {
		building_id: number;
		instance_id: number;
		level: number;
		status_points: number;
		max_status_points: number | null;
		tile_x: number | null;
		tile_y: number | null;
		[k: string]: unknown;
	};
	type ColonyData = {
		tiles: Tile[];
		buildings: Building[];
		ccBuildingId: number;
		merchantVisit: unknown;
		supplyCapFull: boolean;
		findTotal: number;
		tileYields: Record<string, number>;
		repairDisplayThreshold: number;
		damagedThresholdPct: number;
		criticalThresholdPct: number;
		relocateApPerHex: number;
		regolithFallbackTiles: { q: number; r: number }[];
		[k: string]: unknown;
	};

	// Legacy global from public/js/colony-hexgrid.js (framework-free, set on window).
	const hex = (window as unknown as {
		NouronHexGrid: {
			initHexGrid(el: HTMLElement, tiles: Tile[], opts: object): void;
			isBuildableTile(tile: Tile): boolean;
			TILE_LABELS: Record<string, string>;
		};
	}).NouronHexGrid;

	let data = $state<ColonyData | null>(null);
	let loadError = $state('');
	let selectedTile = $state<Tile | null>(null);
	let gridEl = $state<HTMLElement>();

	// Plain (non-reactive) renderer bookkeeping, same as the Alpine component's _-fields.
	const polygons = new Map<string, SVGPolygonElement & { _defaultStroke?: string; _defaultStrokeW?: string }>();
	const positions = new Map<string, { cx: number; cy: number }>();
	const panState = { x: 0, y: 0 };

	const key = (tile: { q: number; r: number }) => `${tile.q},${tile.r}`;

	// Same tier thresholds as EncounterService::resolveOutcome() (game.encounter.*).
	function conditionTone(b: Building | null) {
		if (!b || b.level < 1 || !data) return 'neutral';
		const pct = b.status_points / (b.max_status_points ?? 20);
		if (pct < data.criticalThresholdPct) return 'danger';
		if (pct < data.damagedThresholdPct) return 'warning';
		return 'neutral';
	}

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
				? d.buildings.filter((x) => x.building_id === buildingId && placed(x)).sort((x, y) => x.instance_id - y.instance_id)[0]
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
				await Promise.resolve();
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

<div class="hex-page">
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
			<div class="tile-panel-body">
				{#if selectedTile}
					<!-- ponytail: placeholder until the tile panel lands (stage 3c). -->
					<div class="tile-panel-title">
						<div class="tile-panel-title__row">
							<span class="tile-panel-title__name">{hex.TILE_LABELS[selectedTile.tile_type] ?? selectedTile.tile_type}</span>
						</div>
					</div>
				{:else}
					<div class="tile-panel-empty"><p>{t('colony.click_tile_hint')}</p></div>
				{/if}
			</div>
		</aside>
	</div>
</div>

<style>
	.load-error {
		padding: var(--space-md);
	}
</style>
