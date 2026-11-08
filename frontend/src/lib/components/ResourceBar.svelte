<script lang="ts">
	// Resource chips (vertical list inside ResourceRail) + breakdown popups that open to the right of the rail.
	import { t } from '#lib/i18n.ts';
	import type { Shell } from '#lib/stores/shell.ts';

	let { s, open = $bindable(null) }: { s: Shell; open?: string | null } = $props();

	type Row = { label: string; value: string | number; strong?: boolean; hidden?: boolean };
	type Chip = {
		key: string;
		cls: string;
		abbr?: string;
		label?: string;
		amount: string | number;
		title: string;
		desc: string;
		extra?: Row[][];
		dividerBefore?: boolean;
		ap?: boolean;
	};

	// Hover opens popups only for real pointers; on touch a tap toggles (a click with a mouse keeps the hover popup open; the rail also handles Esc/outside clicks).
	const canHover = () => typeof matchMedia === 'function' && matchMedia('(hover: hover)').matches;

	const fmt = (n: number) => Math.round(n).toLocaleString('de-DE');
	const signed = (n: number) => (n > 0 ? '+' : n < 0 ? '−' : '') + Math.abs(n);
	const rows = (pairs: [string, number | string][], keep = (v: number | string) => !!v): Row[] =>
		pairs.filter(([, v]) => keep(v)).map(([label, value]) => ({ label, value }));

	// Per-Sol balance: non-zero sources, then the net (ResourceFlowService::perSol()).
	const flow = (pairs: [string, number][], net: number, netLabel = t('resources.popup_flow_net')): Row[][] => {
		const lines = rows(pairs.map(([l, v]) => [l, v]), (v) => v !== 0).map((r) => ({
			...r,
			value: signed(r.value as number)
		}));
		const total = [{ label: netLabel, value: signed(net), strong: true }];
		return lines.length ? [lines, total] : [total];
	};

	const chips = $derived.by((): Chip[] => {
		const out: Chip[] = [];
		const p = s.resource_bar_possessions ?? {};
		const f = s.resource_flow;

		if (s.current_sol !== null) {
			out.push({
				key: 'sol',
				cls: 'res-chip res-chip--sol',
				abbr: 'Sol',
				amount: s.current_sol,
				title: t('resources.popup_sol_title'),
				desc: t('resources.popup_sol_desc')
			});
		}

		const nexusPct = s.nexus_debt !== null && s.nexus_debt_max > 0 ? (s.nexus_debt / s.nexus_debt_max) * 100 : 0;
		const nexusMod = nexusPct >= 95 ? 'res-chip--danger' : nexusPct >= 80 ? 'res-chip--warning' : '';
		if (p[1]) {
			const extra: Row[][] = f
				? flow(
						[
							[t('resources.popup_flow_subsidy'), f.credits.sources.subsidy],
							[t('resources.popup_flow_relay'), f.credits.sources.relay],
							[t('resources.popup_flow_upkeep'), -(f.credits.upkeep ?? 0)]
						],
						f.credits.net
					)
				: [];
			if (s.nexus_debt !== null)
				extra.push([
					{ label: t('resources.popup_nx_title'), value: `${fmt(s.nexus_debt)} / ${fmt(s.nexus_debt_max)} Cr` }
				]);
			out.push({
				key: 'cr',
				cls: `res-chip res-Cr ${nexusMod}`,
				abbr: 'CR',
				amount: fmt(p[1].amount ?? 0),
				title: t('resources.popup_cr_title'),
				desc: t('resources.popup_cr_desc'),
				extra,
				dividerBefore: s.current_sol !== null
			});
		}

		if (p[2]) {
			const c = s.colonists_overview;
			const b = s.supply_breakdown;
			const unfilledNone = (c?.unfilled ?? 0) <= 0;
			const extra: Row[][] = b
				? [
						rows([
							[t('resources.popup_sup_source_cc'), b.sources.cc],
							[t('resources.popup_sup_source_housing'), b.sources.housing]
						]),
						rows([
							[t('resources.popup_sup_used_buildings'), b.used.buildings],
							[t('resources.popup_sup_used_researches'), b.used.researches],
							[t('resources.popup_sup_used_advisors'), b.used.advisors]
						]),
						c
							? [
									{ label: t('resources.popup_sup_colonists'), value: c.colonists },
									{ label: t('resources.popup_sup_employed'), value: c.employed },
									{ label: t('resources.popup_sup_free'), value: c.free },
									{ label: t('resources.popup_sup_unfilled'), value: c.unfilled, hidden: unfilledNone },
									{
										label: t('resources.popup_sup_staffing'),
										value: t('resources.popup_sup_staffing_value', {
											pct: Math.round((s.staffing_share ?? 1) * 100)
										}),
										hidden: unfilledNone
									}
								]
							: []
					]
				: [];
			out.push({
				key: 'sup',
				cls: `res-chip res-Sup${unfilledNone ? '' : ' res-chip--warning'}`,
				abbr: 'KOL',
				amount: c && b ? `${fmt(c.employed)}/${fmt(c.colonists)}` : fmt(b ? (c?.colonists ?? b.cap) : (p[2].amount ?? 0)),
				title: t('resources.popup_sup_title'),
				desc: t('resources.popup_sup_desc'),
				extra
			});
		}

		if (s.trust !== undefined) {
			const tr = s.trust;
			const mod =
				tr >= 20
					? 'ap-chip--trust-pos'
					: tr < s.trust_chip_red
						? 'ap-chip--trust-neg'
						: tr < 0
							? 'ap-chip--trust-warn'
							: 'ap-chip--trust-neu';
			out.push({
				key: 'trust',
				cls: `ap-chip ${mod}`,
				label: t('resources.res_trust'),
				amount: tr,
				title: t('resources.popup_trust_title'),
				desc: t('resources.popup_trust_desc'),
				extra: s.trust_breakdown
					? flow(
							[
								[t('resources.popup_trust_buildings'), s.trust_breakdown.buildings],
								[t('resources.popup_trust_research'), s.trust_breakdown.research],
								[t('resources.popup_trust_ships'), s.trust_breakdown.ships],
								[t('resources.popup_trust_events'), s.trust_breakdown.events],
								[t('resources.popup_trust_hunger'), s.trust_breakdown.hunger]
							],
							s.trust_breakdown.total,
							t('resources.popup_trust_total')
						)
					: [],
				ap: true
			});
		}

		// Colony materials in display order Regolith, Organika, Werkstoffe — always shown, even at 0.
		[3, 5, 4]
			.filter((id) => p[id])
			.forEach((id, i) => {
				const abbr = p[id].abbreviation ?? 'x';
				const extra: Row[][] = !f
					? []
					: id === 3
						? flow(
								[
									[t('resources.popup_flow_harvester'), f.regolith.sources.harvester],
									[t('resources.popup_flow_geology'), f.regolith.sources.geology]
								],
								f.regolith.net
							)
						: id === 5
							? flow(
									[
										[t('resources.popup_flow_agrardom'), f.organika.sources.agrardom],
										[t('resources.popup_flow_agronomy'), f.organika.sources.agronomy],
										[t('resources.popup_flow_colonists'), -(f.organika.consumption ?? 0)]
									],
									f.organika.net
								)
							: [[{ label: t('resources.popup_flow_none'), value: '' }]];
				out.push({
					key: `res_${abbr.toLowerCase()}`,
					cls: `res-chip res-${abbr}`,
					abbr,
					amount: fmt(p[id].amount ?? 0),
					title: t(`resources.popup_${abbr.toLowerCase()}_title`),
					desc: t(`resources.popup_${abbr.toLowerCase()}_desc`),
					extra,
					dividerBefore: i === 0
				});
			});

		if (s.colony_ap !== undefined && s.trust !== undefined) {
			const ab = s.ap_breakdown;
			const extra: Row[][] = ab
				? [
						rows(
							[
								[t('resources.popup_ap_base'), ab.base],
								[t('resources.popup_ap_advisor'), ab.advisor]
							],
							(v) => v !== 0
						),
						...(ab.multiplier !== 1
							? [[{ label: t('resources.popup_ap_trust_multiplier'), value: '× ' + ab.multiplier.toFixed(2) }]]
							: []),
						...((ab.plague_multiplier ?? 1) < 1
							? [
									[
										{
											label: t('resources.popup_ap_plague_multiplier'),
											value: '× ' + (ab.plague_multiplier ?? 1).toFixed(2)
										}
									]
								]
							: [])
					]
				: [];
			out.push({
				key: 'ap',
				cls: 'ap-chip ap-chip--neutral',
				label: 'AP',
				amount: s.colony_ap,
				title: t('resources.popup_ap_title'),
				desc: t('resources.popup_ap_desc'),
				extra,
				dividerBefore: true,
				ap: true
			});
		}
		return out;
	});

	// Place a popup right of the rail panel (position: fixed escapes the panel's scroll clipping).
	// Vertically clamped between the header and the bottom chrome; falls back to covering the panel when too narrow.
	function place(el: HTMLElement) {
		const host = el.parentElement!.getBoundingClientRect();
		const panel = el.closest('.rail-panel')!.getBoundingClientRect();
		const root = getComputedStyle(document.documentElement);
		const top = parseFloat(root.getPropertyValue('--app-header-h')) || 0;
		const bottom = parseFloat(root.getPropertyValue('--app-bottom-h')) || 0;
		let left = panel.right + 8;
		let width = Math.min(280, window.innerWidth - left - 8);
		if (width < 200) {
			left = 8;
			width = Math.min(280, window.innerWidth - 16);
		}
		el.style.left = `${left}px`;
		el.style.width = `${width}px`;
		const h = el.offsetHeight;
		const max = window.innerHeight - bottom - 8 - h;
		el.style.top = `${Math.max(top + 8, Math.min(host.top, max))}px`;
	}
</script>

<div class="chips resource-bar">
	{#each chips as chip (chip.key)}
		{#if chip.dividerBefore}<span class="res-divider" aria-hidden="true"></span>{/if}
		<!-- svelte-ignore a11y_no_static_element_interactions -->
		<div
			class="rc"
			onmouseenter={() => canHover() && (open = chip.key)}
			onmouseleave={() => canHover() && open === chip.key && (open = null)}
		>
			<button
				type="button"
				class="{chip.cls} chip-btn"
				aria-expanded={open === chip.key}
				aria-controls="rail-popup-{chip.key}"
				onclick={() => (open = open === chip.key && !canHover() ? null : chip.key)}
			>
				<span class={chip.ap ? 'ap-label' : 'res-abbr'}>{chip.abbr ?? chip.label}</span>
				<span class="res-amount">{chip.amount}</span>
			</button>
			{#if open === chip.key}
				<div class="rail-popup" id="rail-popup-{chip.key}" role="tooltip" {@attach place}>
					<div class="res-popup-header">{chip.title}</div>
					<div class="res-popup-body">{chip.desc}</div>
					{#if chip.extra?.some((g) => g.length)}
						<div class="res-popup-extra">
							{#each chip.extra.filter((g) => g.length) as group, gi (gi)}
								{#if gi > 0}<div class="res-popup-extra"></div>{/if}
								{#each group as row, ri (ri)}
									<div class="res-popup-row" style:display={row.hidden ? 'none' : undefined}>
										<span class="res-popup-label">{row.label}</span>
										{#if row.strong}<span><strong>{row.value}</strong></span>{:else}<span>{row.value}</span>{/if}
									</div>
								{/each}
							{/each}
						</div>
					{/if}
				</div>
			{/if}
		</div>
	{/each}
</div>

<style>
	.chips {
		display: flex;
		flex-direction: column;
		gap: 4px;
		text-align: left;
	}
	.rc {
		position: relative;
	}
	/* The chip itself is the button: reset button chrome, keep the chip visuals from resources.css. */
	.chip-btn {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		width: 100%;
		min-height: 36px;
		margin: 0;
		font: inherit;
		font-size: 0.78rem;
		font-weight: 700;
		cursor: pointer;
		/* Chips are rounded pills; a full-width pill reads fine at rail width. */
		border-radius: var(--radius-pill);
	}
	.chip-btn:focus-visible {
		outline: 2px solid var(--color-accent);
		outline-offset: 2px;
	}
	/* AP chips (trust, AP) have no border in resources.css; keep the same box as the resource chips. */
	.chip-btn:global(.ap-chip) {
		border: 1px solid rgba(0, 0, 0, 0.1);
	}
	.chip-btn:global(.res-chip--sol) {
		border-color: transparent;
	}
	.ap-label {
		font-size: 0.72rem;
	}
	.res-divider {
		width: auto;
		height: 1px;
		margin: 2px 6px;
		align-self: stretch;
	}
	@media (pointer: coarse) {
		.chip-btn {
			min-height: 44px;
		}
	}

	/* Popup: opaque (it holds table rows), fixed so the scrolling panel cannot clip it; coordinates set by place(). */
	.rail-popup {
		position: fixed;
		z-index: 1;
		background: var(--color-bg);
		border: 1px solid var(--color-border);
		border-radius: 6px;
		box-shadow: var(--shadow-flyout);
		padding: 0.5rem 0.75rem;
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
		pointer-events: none;
		animation: ds-slide-fade-in var(--duration-fast) ease-out;
	}
	@media (prefers-reduced-motion: reduce) {
		.rail-popup {
			animation: none;
		}
	}
</style>
