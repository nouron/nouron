<script lang="ts">
	// Port of resources/views/colony/command_center.blade.php + public/js/command_center.js (ADR 0006 stage 4b).
	import '../../../../../public/css/command_center.css';
	import '#lib/styles/colony-view.css';
	import { onMount } from 'svelte';
	import { fade } from 'svelte/transition';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { refreshShell } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';
	import EntityChip from '#lib/components/EntityChip.svelte';
	import Button from '#lib/components/Button.svelte';

	type Criterion = { label: string; done: boolean; current: number; target: number; revealed?: boolean };
	type PriceRow = { price: number; base: number };
	type Data = {
		colonists: { colonists: number; employed: number; free: number; unfilled: number };
		phaseProgress: { phase: number; criteria?: Criterion[]; objectives?: Criterion[] };
		stipendTiers: { key: string; cost: number; trust: number }[];
		solLimit: number;
		currentSol: number;
		nexusDebt: number;
		nexusDebtMax: number;
		nexusDebtTone: 'neutral' | 'warning' | 'danger';
		totalBuildings: number;
		damagedBuildings: {
			building_id: number;
			instance_id: number;
			building_key: string;
			label: string;
			status_points: number;
			max_status_points: number;
			tile_x: number | null;
			tile_y: number | null;
		}[];
		lastSolDeltas: Record<string, number> | null;
		advisors: { type_key: string | null; name: string; rank_name: string; ap_per_tick: number }[];
		trustEvents: { tick: number; description: string; delta: number; following: number[] }[];
		trustEventsSum: number;
		trustEventCap: number | null;
		uplinkLevel: number;
		nexusImport: {
			resources: Record<string, PriceRow>;
			advantage_view: { lines: { label: string; percent_text: string }[]; hints: string[] };
		};
	};
	type Result = { ok: boolean; message?: string; error?: string; [k: string]: unknown };

	let d = $state<Data | null>(null);
	let loadError = $state('');
	let toast = $state<{ msg: string; type: 'error' | 'info' } | null>(null);
	let importAmount = $state(10);
	let delayedResource = $state(3);
	let delayedAmount = $state(10);

	const RES_LABELS: Record<number, string> = {
		3: 'resources.res_regolith',
		4: 'resources.res_werkstoffe',
		5: 'resources.res_organika'
	};
	const BALANCE: [string, string][] = [
		['regolith', 'res_regolith'],
		['werkstoffe', 'res_werkstoffe'],
		['organika', 'res_organika'],
		['credits', 'res_credits'],
		['trust', 'res_trust']
	];
	const fmt = (n: number) => n.toLocaleString('de-DE');
	const signed = (n: number) => (n > 0 ? '+' : '') + n;
	const price = (id: number) => d?.nexusImport.resources[id]?.price ?? 0;
	const locked = $derived((d?.uplinkLevel ?? 0) < 1);
	const sources = $derived(
		d?.nexusImport.advantage_view.lines.map((l) => `${l.label} ${l.percent_text}`).join(', ') ?? ''
	);

	let toastTimer: ReturnType<typeof setTimeout>;
	function showToast(msg: string, type: 'error' | 'info') {
		clearTimeout(toastTimer);
		toast = { msg, type };
		toastTimer = setTimeout(() => (toast = null), 3500);
	}

	async function post(path: string, body: object, errorKey: string): Promise<Result | null> {
		const res: Result = await api<Result>(path, { method: 'POST', body, allowError: true }).catch(() => ({
			ok: false,
			message: t('colony.network_error')
		}));
		if (res.ok) {
			refreshShell();
			return res;
		}
		showToast(res.message ?? res.error ?? t(errorKey), 'error');
		return null;
	}

	// Kolonisten-Zulage: the trust effect lands with the next Sol; credits change now.
	async function stipend(tier: string) {
		const res = await post('/colony/stipend', { tier }, 'colony.stipend_error');
		if (res) showToast(t('colony.stipend_success', { cost: res.cost as number }), 'info');
	}

	async function importCompounds() {
		if (!importAmount || importAmount < 1) return;
		const res = await post('/colony/nexus/import-compounds', { amount: importAmount }, 'colony.nexus_import_error');
		if (res) showToast(t('colony.nexus_import_success', { amount: res.amount as number, cost: res.cost as number }), 'info');
	}

	// Delayed import: paid now, delivered after N Sols by the game tick.
	async function importDelayed() {
		if (!delayedAmount || delayedAmount < 1) return;
		const res = await post(
			'/colony/nexus/import-delayed',
			{ resource_id: delayedResource, amount: delayedAmount },
			'colony.nexus_import_delayed_error'
		);
		if (res)
			showToast(
				t('colony.nexus_import_delayed_success', {
					amount: res.amount as number,
					ticks: res.delivery_ticks as number,
					cost: res.cost as number
				}),
				'info'
			);
	}

	onMount(() => {
		api<Data>('/command-center')
			.then((r) => (d = r))
			.catch((e) => {
				if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});
	});
</script>

{#snippet prices(ids: number[])}
	{#if d}
		<ul class="cc-nexus-prices">
			{#each ids as id (id)}
				{@const row = d.nexusImport.resources[id]}
				<li class="cc-nexus-price">
					<span class="cc-nexus-price-name">{t(RES_LABELS[id])}</span>
					{#if row.price < row.base}<s class="cc-nexus-price-base">{row.base}</s>{/if}
					<strong class="cc-nexus-price-final">{t('colony.nexus_import_price_each', { price: row.price })}</strong>
				</li>
			{/each}
		</ul>
		{#if sources}
			<p class="cc-card-hint cc-nexus-price-sources">{t('colony.nexus_import_price_sources', { sources })}</p>
		{/if}
		{#each d.nexusImport.advantage_view.hints as hint, i (i)}
			<p class="cc-card-hint cc-nexus-price-hint">{hint}</p>
		{/each}
	{/if}
{/snippet}

{#if loadError}<p class="error load-error" role="alert">{loadError}</p>{/if}

{#if d}
	<div class="cc-dashboard">
		<!-- Widget 1: phase goals -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_phase_title')}</h3>
			<ul class="phase-dialog-criteria">
				{#each (d.phaseProgress.phase === 1 ? d.phaseProgress.criteria : d.phaseProgress.objectives) ?? [] as c, i (i)}
					{@const hidden = d.phaseProgress.phase !== 1 && !c.revealed}
					<li class="phase-criteria__item" class:phase-criteria__item--done={c.done}>
						<span class="phase-criteria__check">{c.done ? '✓' : '○'}</span>
						<span class="phase-criteria__label">{hidden ? t('colony.sol_report_phase2_objective_hidden') : c.label}</span>
						{#if !c.done && !hidden}<span class="phase-criteria__count">{c.current}/{c.target}</span>{/if}
					</li>
				{/each}
			</ul>
		</article>

		<!-- Widget 2: Kolonisten-Zulage -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('colony.stipend_dialog_title')}</h3>
			<p class="cc-card-hint">{t('colony.stipend_dialog_hint')}</p>
			<div class="stipend-tiers">
				{#each d.stipendTiers as tier (tier.key)}
					<button class="stipend-tier-btn" onclick={() => stipend(tier.key)}>
						<strong>{t(`colony.stipend_tier_${tier.key}`)}</strong>
						<span class="cc-chip-row">
							<span class="res-chip res-Cr"><span class="res-abbr">CR</span><span class="res-amount">{tier.cost}</span></span>
							<span class="ap-chip ap-chip--trust-pos">+{tier.trust} {t('resources.res_trust')}</span>
						</span>
					</button>
				{/each}
			</div>
		</article>

		<!-- Widget 2b: colonists -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_colonists_title')}</h3>
			<p class="cc-stat">
				{t('command_center.widget_colonists_line', { colonists: d.colonists.colonists, employed: d.colonists.employed })}
			</p>
			<p class="cc-card-hint">
				{t('command_center.widget_colonists_free', { free: d.colonists.free })}
				{#if d.colonists.unfilled > 0}· {t('command_center.widget_colonists_unfilled', { count: d.colonists.unfilled })}{/if}
			</p>
		</article>

		<!-- Widget 3: run progress -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_run_title')}</h3>
			<p class="cc-stat">{t('command_center.widget_run_sol', { current: d.currentSol, limit: d.solLimit })}</p>
			<p class="cc-card-hint">{t('command_center.widget_run_remaining', { count: Math.max(0, d.solLimit - d.currentSol) })}</p>
			<div class="cc-nexus-debt">
				<div class="cc-nexus-debt-label">
					<span>{t('command_center.widget_run_nexus_debt')}</span>
					<span class="res-chip res-Cr {d.nexusDebtTone !== 'neutral' ? `res-chip--${d.nexusDebtTone}` : ''}">
						<span class="res-abbr">CR</span>
						<span class="res-amount">{fmt(d.nexusDebt)} / {fmt(d.nexusDebtMax)}</span>
					</span>
				</div>
				<div class="cc-nexus-debt-bar">
					<div
						class="cc-nexus-debt-bar-fill cc-nexus-debt-bar-fill--{d.nexusDebtTone}"
						style="width: {Math.min(100, d.nexusDebtMax > 0 ? (d.nexusDebt / d.nexusDebtMax) * 100 : 0)}%"
					></div>
				</div>
			</div>
		</article>

		<!-- Widget 4: maintenance backlog -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_maintenance_title')}</h3>
			{#if d.damagedBuildings.length === 0}
				<p class="cc-card-hint">{t('command_center.widget_maintenance_none')}</p>
			{:else}
				<p class="cc-stat">
					{t('command_center.widget_maintenance_count', { count: d.damagedBuildings.length, total: d.totalBuildings })}
				</p>
				<ul class="cc-list">
					{#each d.damagedBuildings as b (b.building_id + '-' + b.instance_id)}
						<li class="cc-list-item">
							<span>
								<EntityChip
									type="building"
									entityKey={b.building_key}
									label={b.label}
									tooltip={{
										description: t(`buildings.${b.building_key.replace('building_', '')}_desc`),
										link: `/colony?building=${b.building_id}&instance=${b.instance_id}`,
										link_label: t('entity_chip.label_tile_link')
									}}
								/>
								({b.tile_x}, {b.tile_y})
							</span>
							<span class="cc-list-item-danger"
								>{t('command_center.widget_maintenance_status', { sp: b.status_points, max: b.max_status_points })}</span
							>
						</li>
					{/each}
				</ul>
			{/if}
		</article>

		<!-- Widget 5: net balance of the last Sol -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_balance_title')}</h3>
			{#if d.lastSolDeltas === null}
				<p class="cc-card-hint">{t('command_center.widget_balance_none')}</p>
			{:else}
				{@const deltas = d.lastSolDeltas}
				<p class="cc-card-hint">{t('command_center.widget_balance_intro')}</p>
				<ul class="cc-list">
					{#each BALANCE as [key, lang] (key)}
						{@const delta = deltas[key] ?? 0}
						<li class="cc-list-item">
							<span>{t(`resources.${lang}`)}</span>
							<span class:cc-list-item-good={delta > 0} class:cc-list-item-danger={delta < 0}>{signed(delta)}</span>
						</li>
					{/each}
				</ul>
			{/if}
		</article>

		<!-- Widget 6: advisors overview -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_advisors_title')}</h3>
			{#if d.advisors.length === 0}
				<p class="cc-card-hint">{t('command_center.widget_advisors_none')}</p>
			{:else}
				<p class="cc-stat">{t('command_center.widget_advisors_count', { count: d.advisors.length })}</p>
				<ul class="cc-list">
					{#each d.advisors as a, i (i)}
						<li class="cc-list-item">
							<span>
								<EntityChip
									type="advisor"
									entityKey="advisor_{a.type_key}"
									label={a.name}
									tooltip={{ description: a.type_key ? t(`advisors.${a.type_key}_desc`) : undefined }}
								/>
								({a.rank_name})
							</span>
							<span class="ap-chip">+{a.ap_per_tick} AP</span>
						</li>
					{/each}
				</ul>
			{/if}
			<a href="/advisors" class="cc-card-link">{t('command_center.widget_advisors_link')}</a>
		</article>

		<!-- Widget 7: trust events acting on the next Sol -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('command_center.widget_trust_events_title')}</h3>
			{#if d.trustEvents.length === 0}
				<p class="cc-card-hint">{t('command_center.widget_trust_events_none')}</p>
			{:else}
				<ul class="cc-list">
					{#each d.trustEvents as e, i (i)}
						<li class="cc-list-item">
							<span>
								{t('command_center.widget_trust_events_sol', { sol: e.tick + 1 })} — {e.description}
								{#if e.following.length}
									<small class="cc-card-hint"
										>{t('command_center.widget_trust_events_following', { values: e.following.map(signed).join(', ') })}</small
									>
								{/if}
							</span>
							<span
								class="ap-chip {e.delta > 0 ? 'ap-chip--trust-pos' : e.delta < 0 ? 'ap-chip--trust-neg' : 'ap-chip--trust-neu'}"
								>{signed(e.delta)}</span
							>
						</li>
					{/each}
					<li class="cc-list-item">
						<strong>{t('command_center.widget_trust_events_sum')}</strong>
						<span>
							{signed(d.trustEventsSum)}
							{#if d.trustEventCap !== null && Math.abs(d.trustEventsSum) >= d.trustEventCap}
								<small class="cc-card-hint">{t('command_center.widget_trust_events_capped', { cap: d.trustEventCap })}</small>
							{/if}
						</span>
					</li>
				</ul>
			{/if}
		</article>

		<!-- Widget 8: Nexus import (Werkstoffe), from Uplink-Station Lv1; always shown so the option is known. -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('colony.nexus_import_title')}</h3>
			<p class="cc-card-hint">{t('colony.nexus_import_hint')}</p>
			{#if locked}<p class="cc-card-hint cc-card-hint--warning">{t('colony.nexus_import_uplink_required')}</p>{/if}
			{@render prices([4])}
			<div class="nexus-import-controls">
				<input
					type="number"
					min="1"
					max="9999"
					bind:value={importAmount}
					class="nexus-import-amount"
					disabled={locked}
					aria-label={t('colony.nexus_import_amount')}
				/>
				<span class="nexus-import-total">{(importAmount || 0) * price(4)} Cr</span>
				<Button disabled={locked || !importAmount || importAmount < 1} onclick={importCompounds}
					>{t('colony.nexus_import_confirm')}</Button
				>
			</div>
		</article>

		<!-- Widget 9: delayed Nexus import (Regolith/Organika): paid now, delivered after some Sols. -->
		<article class="cc-card">
			<h3 class="cc-card-title">{t('colony.nexus_import_delayed_title')}</h3>
			<p class="cc-card-hint">{t('colony.nexus_import_delayed_hint')}</p>
			{#if locked}<p class="cc-card-hint cc-card-hint--warning">{t('colony.nexus_import_uplink_required')}</p>{/if}
			{@render prices([3, 5])}
			<div class="nexus-import-controls">
				<select
					bind:value={delayedResource}
					class="nexus-import-resource"
					disabled={locked}
					aria-label={t('colony.nexus_import_delayed_resource')}
				>
					<option value={3}>{t('colony.resource_regolith')}</option>
					<option value={5}>{t('resources.res_organika')}</option>
				</select>
				<input
					type="number"
					min="1"
					max="9999"
					bind:value={delayedAmount}
					class="nexus-import-amount"
					disabled={locked}
					aria-label={t('colony.nexus_import_delayed_amount')}
				/>
				<span class="nexus-import-total">{(delayedAmount || 0) * price(delayedResource)} Cr</span>
				<Button disabled={locked || !delayedAmount || delayedAmount < 1} onclick={importDelayed}
					>{t('colony.nexus_import_delayed_confirm')}</Button
				>
			</div>
		</article>
	</div>
{/if}

{#if toast}
	<div class="colony-toast colony-toast--{toast.type}" aria-live="polite" role="status" transition:fade>{toast.msg}</div>
{/if}

<style>
	.load-error {
		padding: var(--space-md);
	}
	/* Blade (PicoCSS) put the import button at the right end of the controls row. */
	.nexus-import-controls :global(.btn) {
		margin-left: auto;
	}
</style>
