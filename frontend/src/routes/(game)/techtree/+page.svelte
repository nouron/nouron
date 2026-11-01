<script lang="ts">
	// Shared with the Blade techtree while both coexist (ADR 0006): one renderer, one stylesheet.
	import '../../../../../public/css/techtree-view.css';
	import '#lib/styles/colony-shared.css';
	import '../../../../../public/js/techtree-canvas.js';

	import { onMount, tick } from 'svelte';
	import { fly, fade } from 'svelte/transition';
	import { api, ApiError, BACKEND_URL } from '#lib/stores/auth.ts';
	import { setAp, shell } from '#lib/stores/shell.ts';
	import FirstVisit from '#lib/components/FirstVisit.svelte';
	import ApUpgradeButton from '#lib/components/ApUpgradeButton.svelte';
	import { t } from '#lib/i18n.ts';

	type TechType = 'building' | 'research' | 'ship' | 'personell';
	type EffectLine = { text?: string; chip?: { cls: string; abbr: string; value: string | number } };
	type ApCap = { cap: number; spent: number; remaining: number } | null;
	type Instance = {
		instance_id: number;
		level: number;
		max_level: number | null;
		status_points: number;
		max_status_points: number;
	};
	type Tech = {
		id: number;
		type: TechType;
		name: string;
		level: number;
		status: 'built' | 'available' | 'locked';
		grid_row: number;
		grid_col: number;
		req_cc: number;
		band: number;
		required_desc: string | null;
		required_list: string[];
		description: string | null;
		unlocks_next_level: EffectLine[];
		effects_current_level: EffectLine[];
		max_level: number | null;
		image_slug: string | null;
		is_instanced: boolean;
		max_instances: number | null;
		instances: Instance[];
		menu_buildable: boolean;
		acquire_hint: string | null;
		hangar_cap: number | null;
		ap_spend: number;
		ap_for_levelup: number;
		ap_available: number;
		ap_cap: ApCap;
	};
	type Phases = Record<string, { cc_level: number; items: Tech[] }>;
	type Edge = Record<string, unknown>;
	type OrderResponse = {
		success: boolean;
		message?: string;
		leveled_up?: boolean;
		levelup_blocked_message?: string | null;
		tech?: Partial<Tech>;
		ap_available?: number;
		phases_update?: { phases: Record<string, { items: Partial<Tech>[] }>; edges: Edge[] };
	};
	type Canvas = {
		zoom(f: number): void;
		start(): void;
		overview(): void;
		select(key: string): void;
		clearSelection(): void;
		resize(): void;
		refresh(a?: null, edges?: Edge[]): void;
		focusNode(key: string, o: { bottom: number }): void;
	};

	// Legacy globals from public/js (framework-free, set on window).
	const w = window as unknown as {
		TechtreeCanvas: { create(opts: object): Canvas };
	};
	const CAT: Record<TechType, string> = { building: 'b', research: 'k', ship: 's', personell: 'a' };

	let page = $state<HTMLDivElement>();
	let stage = $state<HTMLDivElement>();
	let canvas = $state<HTMLCanvasElement>();
	let dots = $state<HTMLDivElement>();
	let panel = $state<HTMLElement>();

	let phases = $state<Phases>({});
	let edges: Edge[] = [];
	let selected = $state<Tech | null>(null);
	const apAvailable = $derived($shell?.colony_ap ?? 0);
	let sheetMode = $state(false);
	let loadError = $state('');
	let firstVisit = $state(false);
	let toast = $state<{ message: string; type: 'error' | 'info' } | null>(null);
	let tree: Canvas | null = null;
	let toastTimer: ReturnType<typeof setTimeout>;

	const keyOf = (tech: Tech) => `${tech.type}-${tech.id}`;

	function flatItems(): Tech[] {
		return Object.keys(phases)
			.map(Number)
			.sort((a, b) => a - b)
			.flatMap((n) => phases[n].items);
	}

	function bandLabels() {
		return Object.fromEntries(
			Object.keys(phases).map((n) => [
				n,
				Number(n) === 0 ? t('techtree.band_start') : t('techtree.band_cc', { level: n })
			])
		);
	}

	function statusLabel(tech: Tech): string {
		const gate: Record<string, string> = {
			available: t('techtree.status_available'),
			locked: t('techtree.status_locked')
		};
		if (tech.type === 'personell') {
			return (
				{
					built: t('techtree.advisor_hired'),
					available: t('techtree.advisor_available'),
					locked: t('techtree.advisor_locked')
				}[tech.status] ?? tech.status
			);
		}
		if (tech.type === 'ship') {
			if (tech.status === 'built') return tech.level + (tech.hangar_cap ? ' / ' + tech.hangar_cap : '');
			return gate[tech.status] ?? tech.status;
		}
		if (tech.is_instanced) {
			if (tech.status !== 'built') return gate[tech.status] ?? tech.status;
			if (tech.max_level === 1)
				return tech.instances.length > 0 ? t('techtree.advisor_placed') : t('techtree.advisor_not_placed');
			return instanceCountLabel(tech);
		}
		if (tech.status === 'built')
			return tech.level > 0
				? `Lv ${tech.level}${tech.max_level ? '/' + tech.max_level : ''}`
				: t('techtree.status_built');
		return gate[tech.status] ?? tech.status;
	}

	function instanceCountLabel(tech: Tech) {
		const placed = tech.instances?.length ?? 0;
		return tech.max_instances ? `${placed} / ${tech.max_instances}` : String(placed);
	}

	function hasInstanceRoom(tech: Tech) {
		const placed = tech.instances?.length ?? 0;
		if (!tech.is_instanced) return placed === 0;
		return tech.max_instances == null || placed < tech.max_instances;
	}

	const canBuildMore = (tech: Tech) => tech.menu_buildable !== false && hasInstanceRoom(tech);
	const acquireHint = (tech: Tech) =>
		tech.menu_buildable !== false || !tech.acquire_hint ? '' : hasInstanceRoom(tech) ? tech.acquire_hint : '';

	function instanceLevelLabel(inst: Instance) {
		if (inst.level === 0) return t('techtree.detail_instance_construction');
		if (inst.max_level === null) return t('techtree.detail_instance_level_uncapped', { level: inst.level });
		return t('techtree.detail_instance_level', { level: inst.level, max: inst.max_level });
	}

	const colonyLink = (params: Record<string, number>) =>
		`/colony?${new URLSearchParams(Object.entries(params).map(([k, v]) => [k, String(v)]))}`;

	const showSubhead = (tech: Tech) =>
		(tech.type === 'building' && !tech.is_instanced && tech.level > 0) || (tech.type === 'research' && tech.level > 0);

	// Size the stage to the viewport below whatever sits above it; the side panel starts at the same line.
	function fit() {
		if (!page || !stage) return;
		const top = Math.max(0, stage.getBoundingClientRect().top);
		page.style.setProperty('--tt-top', `${top}px`);
		stage.style.height = `${Math.max(320, Math.round(window.innerHeight - top))}px`;
	}

	function onResize() {
		sheetMode = window.innerWidth < 600;
		fit();
	}

	async function openDetail(tech: Tech, syncCanvas = true) {
		selected = tech;
		const key = keyOf(tech);
		if (syncCanvas) tree?.select(key);
		await tick();
		tree?.resize();
		tree?.focusNode(key, { bottom: sheetMode && panel ? panel.offsetHeight : 0 });
	}

	async function closeDetail(syncCanvas = true) {
		selected = null;
		if (syncCanvas) tree?.clearSelection();
		await tick();
		tree?.resize();
	}

	function selectByKey(key: string | null) {
		if (!key) return closeDetail(false);
		const tech = flatItems().find((it) => keyOf(it) === key);
		if (tech) openDetail(tech, false);
	}

	function showToast(message: string, type: 'error' | 'info' = 'error') {
		clearTimeout(toastTimer);
		toast = { message, type };
		toastTimer = setTimeout(() => (toast = null), 3500);
	}

	// Invest 1 AP; the server levels up by itself once the threshold is reached and
	// re-derives every node's gate status (phases_update), so the whole graph stays current.
	async function investAp(tech: Tech) {
		const json = await api<OrderResponse>(`/techtree/research/${tech.id}/order`, {
			method: 'POST',
			body: { order: 'add', ap: 1 },
			allowError: true
		});
		if (!json.success) return showToast(json.message ?? t('techtree.toast_invest_failed'));

		if (json.tech) Object.assign(tech, json.tech);
		if (json.ap_available !== undefined) setAp(json.ap_available);
		if (json.phases_update) {
			for (const [n, update] of Object.entries(json.phases_update.phases)) {
				for (const u of update.items) {
					const item = phases[n]?.items.find((i) => i.id === u.id && i.type === u.type);
					if (item) Object.assign(item, u);
				}
			}
			edges = json.phases_update.edges;
		}
		tree?.refresh(null, json.phases_update?.edges);

		if (json.leveled_up) showToast(t('techtree.toast_level_reached', { name: tech.name, level: tech.level }), 'info');
		else if (json.levelup_blocked_message) showToast(json.levelup_blocked_message);
	}

	onMount(() => {
		onResize();
		window.addEventListener('resize', onResize);
		// The header grows and shrinks (resource bar wraps): keep the stage flush below it.
		const header = document.querySelector('.app-header');
		const ro = new ResizeObserver(() => fit());
		if (header) ro.observe(header);

		api<{ phases: Phases; edges: Edge[]; first_visit: boolean }>('/techtree')
			.then(async (data) => {
				// Band = phase number; set once here (writing state while rendering is not allowed).
				for (const [n, phase] of Object.entries(data.phases)) for (const it of phase.items) it.band = Number(n);
				phases = data.phases;
				edges = data.edges ?? [];
				firstVisit = data.first_visit;
				await tick();
				fit();
				tree = w.TechtreeCanvas.create({
					root: page,
					stage,
					canvas,
					dots,
					items: flatItems().map((it) => ({
						key: keyOf(it),
						cat: CAT[it.type],
						band: it.band,
						col: it.grid_col,
						row: it.grid_row,
						ref: it
					})),
					edges,
					bandLabels: bandLabels(),
					labels: { columnN: t('techtree.column_n') },
					describe: (item: { ref: Tech & { pulse?: unknown } }) => ({
						label: item.ref.name,
						sub: statusLabel(item.ref),
						tag: item.ref.req_cc ? t('techtree.cc_tag', { level: item.ref.req_cc }) : '',
						status: item.ref.status,
						pulse: item.ref.pulse
					}),
					onSelect: selectByKey
				});
			})
			.catch((e) => {
				if (e instanceof ApiError && e.status === 409) loadError = e.message;
				else if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});

		return () => {
			window.removeEventListener('resize', onResize);
			ro.disconnect();
		};
	});
</script>

<svelte:head><title>Techtree – Nouron</title></svelte:head>

{#snippet chipList(lines: EffectLine[])}
	<ul class="detail-list detail-list--chips">
		{#each lines as line, idx (idx)}
			<li>
				{#if line.chip}
					<span class="res-chip res-{line.chip.cls}">
						<span class="res-abbr">{line.chip.abbr}</span>
						<span class="res-amount">{line.chip.value}</span>
					</span>
				{:else}
					<span class="res-chip res-chip--neutral">{line.text}</span>
				{/if}
			</li>
		{/each}
	</ul>
{/snippet}

{#snippet effects(tech: Tech)}
	{#if tech.required_list?.length}
		<div class="detail-row">
			<span class="detail-row-label">{t('techtree.detail_required')}</span>
			{@render chipList(tech.required_list.map((text) => ({ text })))}
		</div>
	{/if}
	{#if tech.effects_current_level?.length}
		<div class="detail-row">
			<span class="detail-row-label">{t('techtree.detail_effects_current_level')}</span>
			{@render chipList(tech.effects_current_level)}
		</div>
	{/if}
	{#if tech.unlocks_next_level?.length}
		<div class="detail-row">
			<span class="detail-row-label">{t('techtree.detail_effects_next_level')}</span>
			{@render chipList(tech.unlocks_next_level)}
		</div>
	{/if}
{/snippet}

{#if firstVisit}<FirstVisit screen="techtree" />{/if}

{#if loadError}
	<p class="error load-error" role="alert">{loadError}</p>
{/if}

<div class="techtree-page" class:techtree-page--panel={selected} bind:this={page}>
	{#if toast}
		<div class="techtree-toast techtree-toast--{toast.type}" role="status" aria-live="polite" transition:fade>
			{toast.message}
		</div>
	{/if}

	<div class="techtree-legend" role="list" aria-label={t('techtree.legend_label')}>
		<span class="tt-leg" role="listitem"><span class="tt-sw tt-sw--b"></span>{t('techtree.legend_building')}</span>
		<span class="tt-leg" role="listitem"><span class="tt-sw tt-sw--k"></span>{t('techtree.legend_research')}</span>
		<span class="tt-leg" role="listitem"><span class="tt-sw tt-sw--s"></span>{t('techtree.legend_ship')}</span>
		<span class="tt-leg" role="listitem"><span class="tt-sw tt-sw--a"></span>{t('techtree.legend_advisor')}</span>
		<span class="tt-leg" role="listitem"><span class="tt-st"></span>{t('techtree.legend_required')}</span>
		<span class="tt-leg" role="listitem"><span class="tt-st tt-st--dash"></span>{t('techtree.legend_second')}</span>
	</div>

	<div class="techtree-stage" bind:this={stage}>
		<!-- svelte-ignore a11y_no_interactive_element_to_noninteractive_role (same as Blade: focusable canvas, list below is the accessible path) -->
		<canvas class="techtree-canvas" bind:this={canvas} tabindex="0" role="img" aria-label={t('techtree.canvas_label')}
		></canvas>
		<div class="techtree-controls">
			<button class="tt-btn tt-btn--sq" type="button" onclick={() => tree?.zoom(1.3)} aria-label={t('techtree.zoom_in')}
				>+</button
			>
			<button
				class="tt-btn tt-btn--sq"
				type="button"
				onclick={() => tree?.zoom(1 / 1.3)}
				aria-label={t('techtree.zoom_out')}>&minus;</button
			>
			<button class="tt-btn" type="button" onclick={() => tree?.start()}>{t('techtree.view_start')}</button>
			<button class="tt-btn" type="button" onclick={() => tree?.overview()}>{t('techtree.view_overview')}</button>
		</div>
		<div class="techtree-dots" bind:this={dots} hidden role="group" aria-label={t('techtree.columns_label')}></div>
	</div>

	<!-- Text alternative to the canvas: keyboard / screen-reader way to open a node -->
	<ul class="tt-sr">
		{#each flatItems() as tech (keyOf(tech))}
			<li>
				<button type="button" onclick={() => openDetail(tech)}>
					{tech.name} ({t('techtree.type_' + tech.type)}, {statusLabel(tech)}){tech.required_desc
						? ' — ' + tech.required_desc
						: ''}
				</button>
			</li>
		{/each}
	</ul>

	{#if selected}
		{@const tech = selected}
		<aside
			class="tech-panel"
			bind:this={panel}
			transition:fly={sheetMode ? { y: 400, duration: 200 } : { x: 300, duration: 200 }}
		>
			<div class="detail-inner detail-cat-{tech.type}">
				<div class="detail-head">
					<div class="detail-badges">
						<span class="detail-type-badge">{t('techtree.type_' + tech.type)}</span>
						{#if !showSubhead(tech)}
							<span class="tech-status-chip chip-{tech.status}">{statusLabel(tech)}</span>
						{/if}
					</div>
					<button class="detail-x" onclick={() => closeDetail()} aria-label={t('techtree.detail_close')}
						>&#215;</button
					>
				</div>

				<h3 class="detail-title">{tech.name}</h3>

				{#if showSubhead(tech)}
					<div class="detail-subhead">
						<span class="detail-subhead-label">{t('techtree.detail_level')}</span>
						<span class="detail-subhead-value">{tech.level}{tech.max_level ? ' / ' + tech.max_level : ''}</span>
					</div>
				{/if}

				{#if tech.image_slug}
					<div class="building-detail-wrap">
						<div class="building-detail-img-wrap">
							<img
								class="building-detail-img"
								src="{BACKEND_URL}/img/buildings/{tech.image_slug}.webp"
								alt={tech.name}
								onerror={(e) => ((e.currentTarget as HTMLImageElement).style.visibility = 'hidden')}
							/>
						</div>
					</div>
				{/if}
				{#if tech.description}
					<p class="building-detail-description">{tech.description}</p>
				{/if}

				<div class="detail-body">
					{#if tech.type === 'personell'}
						{@render effects(tech)}
						<a href="/advisors" class="detail-cta-link">{t('techtree.detail_advisor_link')} &rarr;</a>
					{:else if tech.type === 'building'}
						{#if tech.is_instanced && tech.instances.length > 0}
							<div class="detail-row">
								<span class="detail-row-label">{t('techtree.detail_instances')}</span>
								<span class="detail-instance-count">{instanceCountLabel(tech)}</span>
							</div>
						{/if}
						{#if tech.instances.length > 0}
							<div class="detail-row detail-row--instances">
								<span class="detail-row-label">{t('techtree.detail_instance_list')}</span>
								<ul class="detail-instance-list">
									{#each tech.instances as inst (inst.instance_id)}
										<li class="detail-instance">
											<span class="detail-instance-level">{instanceLevelLabel(inst)}</span>
											{#if inst.level > 0}
												<span class="detail-instance-condition"
													>{t('techtree.detail_instance_condition', {
														sp: inst.status_points,
														max: inst.max_status_points
													})}</span
												>
											{/if}
											<a class="detail-instance-link" href={colonyLink({ building: tech.id, instance: inst.instance_id })}
												>{t('techtree.detail_instance_tile_link')} &rarr;</a
											>
										</li>
									{/each}
								</ul>
							</div>
						{/if}
						{@render effects(tech)}
						{#if canBuildMore(tech)}
							<a href={colonyLink({ build: tech.id })} class="detail-cta-link">{t('techtree.detail_colony_link')} &rarr;</a>
						{/if}
						{#if acquireHint(tech)}
							<p class="detail-ap-hint">{acquireHint(tech)}</p>
						{/if}
					{:else if tech.type === 'research'}
						{@render effects(tech)}
						{#if tech.ap_for_levelup > 0 && tech.status !== 'locked'}
							<div class="detail-ap-section">
								<ApUpgradeButton
									row={tech}
									label="{t('techtree.detail_ap_invest')} {tech.ap_spend}/{tech.ap_for_levelup}"
									chip="research"
									blocked={apAvailable <= 0 || tech.ap_spend >= tech.ap_for_levelup}
									capTip={t('techtree.knowledge_ap_cap_tooltip')}
									tipId="ap-cap-tip-tech"
									onclick={() => investAp(tech)}
								/>
								{#if tech.ap_cap && tech.ap_spend < tech.ap_for_levelup}
									<p class="detail-ap-hint">
										{t('techtree.knowledge_ap_cap_today', { spent: tech.ap_cap.spent, cap: tech.ap_cap.cap })}
									</p>
								{/if}
								{#if apAvailable <= 0 && tech.ap_spend < tech.ap_for_levelup}
									<p class="detail-ap-hint">{t('techtree.hint_no_research_ap')}</p>
								{/if}
							</div>
						{/if}
					{:else if tech.type === 'ship'}
						{#if tech.level > 0}
							<div class="detail-row">
								<span class="detail-row-label">{t('techtree.detail_count')}</span>
								<span>{tech.level}{tech.hangar_cap ? ' / ' + tech.hangar_cap : ''}</span>
							</div>
						{/if}
						{@render effects(tech)}
					{/if}
				</div>

				<button class="detail-close" onclick={() => closeDetail()}>{t('techtree.detail_close')}</button>
			</div>
		</aside>
	{/if}
</div>

<style>
	.load-error {
		margin: var(--space-md);
	}
</style>
