<script lang="ts">
	// Resource chips (vertical list inside ResourceRail) + breakdown popups that open to the right of the rail.
	import { t } from '#lib/i18n.ts';
	import type { Shell } from '#lib/stores/shell.ts';

	let { s, open = $bindable(null) }: { s: Shell; open?: string | null } = $props();

	type Row = { label: string; value: string | number; strong?: boolean; hidden?: boolean; note?: boolean; plain?: boolean };
	type Rung = { key: string; label: string; ap: string; tone: string; current: boolean };
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
		/** Trust: band word + effect line above the band ladder. */
		lead?: string;
		ladder?: Rung[];
		/** Two-line chip (label above the value) for values longer than a number. */
		stack?: boolean;
		/** Value drawn as two parts (word, effect) that may wrap at rail width; `amount` stays the accessible text. */
		split?: [string, string];
	};

	// Trust band -> chip tone (T59 §4): riot negative, restless warning, stable neutral, content/euphoric positive.
	const TRUST_TONE: Record<string, string> = { riot: 'neg', restless: 'warn', stable: 'neu', content: 'pos', euphoric: 'pos' };

	// Hover opens popups only for real pointers; on touch a tap toggles (a click with a mouse keeps the hover popup open; the rail also handles Esc/outside clicks).
	const canHover = () => typeof matchMedia === 'function' && matchMedia('(hover: hover)').matches;

	const fmt = (n: number) => Math.round(n).toLocaleString('de-DE');
	const signed = (n: number) => (n > 0 ? '+' : n < 0 ? '−' : '') + Math.abs(n);
	// AP effects read "±0" at zero (spec table), so the stable band still states its (non-)effect.
	const signedAp = (n: number) => (n === 0 ? '±0' : signed(n));
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
			const bandWord = (key: string) => t(`trust.band_${key}`);
			const bandEffect = t('resources.popup_trust_band_effect', { band: t(`trust.band_${tr.band}`), ap: signedAp(tr.ap) });
			// Sources: names only without a science lab (amounts would reveal the number, T59 F3).
			// With a lab, silent sources (0) are left out like in the other balances; the base always stays.
			const sources: Row[] = tr.sources.filter((src) => !tr.lab || src.key === 'base' || src.value !== 0).map((src) => ({
				label: t(`resources.popup_trust_${src.key}`),
				value: tr.lab && src.value !== undefined ? signed(src.value) : ''
			}));
			const bandMin = (key: string) => tr.bands.find((b) => b.key === key)?.min ?? 0;
			const totalLine = (total: number, band: string) =>
				t('resources.popup_trust_total', { total, band: bandWord(band), min: bandMin(band) });
			const footer: Row[] = [];
			if (tr.lab && tr.total !== undefined) footer.push({ label: totalLine(tr.total, tr.band), value: '', plain: true });
			// Preview of the next Sol (TrustService::view): with a lab the new total, without one only a band change.
			if (tr.next_band !== undefined) {
				const value = tr.lab && tr.next_total !== undefined ? totalLine(tr.next_total, tr.next_band) : bandWord(tr.next_band);
				footer.push({ label: t('resources.popup_trust_next', { value }), value: '', plain: true, note: true });
			}
			// The locked hint only explains amounts that are missing; no sources (no stored breakdown yet) -> no hint.
			if (!tr.lab && sources.length) footer.push({ label: t('resources.popup_trust_numbers_locked'), value: '', plain: true, note: true });
			out.push({
				key: 'trust',
				cls: `ap-chip ap-chip--trust-${TRUST_TONE[tr.band] ?? 'neu'}`,
				label: t('resources.res_trust'),
				amount: bandEffect,
				title: t('resources.popup_trust_title'),
				desc: t('resources.popup_trust_desc'),
				lead: bandEffect,
				ladder: tr.bands.map((b) => ({
					key: b.key,
					label: bandWord(b.key),
					ap: signedAp(b.ap),
					tone: TRUST_TONE[b.key] ?? 'neu',
					current: b.key === tr.band
				})),
				extra: [sources, footer],
				stack: true,
				split: [t(`trust.band_${tr.band}`), `${signedAp(tr.ap)} AP`],
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
			// All parts are whole AP summands (AP scale 2026-11-13 §4): base, then signed advisor/trust/plague parts.
			const extra: Row[][] = ab
				? [
						[
							{ label: t('resources.popup_ap_base'), value: ab.base },
							...rows(
								[
									[t('resources.popup_ap_advisor'), ab.advisor],
									[`${t('resources.popup_ap_trust')} (${t(`trust.band_${s.trust.band}`)})`, ab.trust_ap],
									[t('resources.popup_ap_plague'), ab.plague_ap]
								],
								(v) => v !== 0
							).map((r) => ({ ...r, value: signed(r.value as number) }))
						]
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
				class:chip-btn--stack={chip.stack}
				aria-expanded={open === chip.key}
				aria-controls="rail-popup-{chip.key}"
				onclick={() => (open = open === chip.key && !canHover() ? null : chip.key)}
			>
				<span class={chip.ap ? 'ap-label' : 'res-abbr'}>{chip.abbr ?? chip.label}</span>
				{#if chip.split}
					<span class="res-amount res-amount--split" aria-label={String(chip.amount)}
						><span>{chip.split[0]}</span><span>{chip.split[1]}</span></span
					>
				{:else}
					<span class="res-amount">{chip.amount}</span>
				{/if}
			</button>
			{#if open === chip.key}
				<div class="rail-popup" id="rail-popup-{chip.key}" role="tooltip" {@attach place}>
					<div class="res-popup-header">{chip.title}</div>
					<div class="res-popup-body">{chip.desc}</div>
					{#if chip.lead}<div class="trust-lead">{chip.lead}</div>{/if}
					{#if chip.ladder}
						<ol class="trust-ladder" aria-label={chip.title}>
							{#each chip.ladder as rung (rung.key)}
								<li
									class="trust-rung trust-rung--{rung.tone}"
									class:trust-rung--current={rung.current}
									aria-current={rung.current ? 'true' : undefined}
									title={rung.label}
								>
									<span class="trust-rung__bar"></span>
									<span class="trust-rung__ap">{rung.ap}</span>
								</li>
							{/each}
						</ol>
					{/if}
					{#if chip.extra?.some((g) => g.length)}
						<div class="res-popup-extra">
							{#each chip.extra.filter((g) => g.length) as group, gi (gi)}
								{#if gi > 0}<div class="res-popup-extra"></div>{/if}
								{#each group as row, ri (ri)}
									<div
										class="res-popup-row"
										class:res-popup-row--plain={row.plain}
										class:res-popup-row--note={row.note}
										style:display={row.hidden ? 'none' : undefined}
									>
										<span class="res-popup-label">{row.label}</span>
										{#if row.value !== ''}
											{#if row.strong}<span><strong>{row.value}</strong></span>{:else}<span>{row.value}</span>{/if}
										{/if}
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
	.chip-btn:global(.res-chip--sol) {
		border-color: transparent;
	}
	/* Rail look: chips are subtle glass tags (barely any border, light translucent fill); colour stays only as a hairline
	   tint and on warning/danger. The rail sets --rail-chip-bg (opaque fallback for reduced transparency). */
	.chip-btn:global(.res-chip),
	.chip-btn:global(.ap-chip) {
		background: var(--rail-chip-bg, rgba(255, 255, 255, 0.5)) !important;
		border: 1px solid rgba(0, 0, 0, 0.05);
		box-shadow: none;
		color: var(--color-text-primary);
		text-shadow: 0 0 3px rgba(255, 255, 255, 0.9);
	}
	.chip-btn:global(.res-chip:hover) {
		box-shadow: none;
		background: rgba(255, 255, 255, 0.7) !important;
	}
	.chip-btn:global(.res-chip--warning) {
		border-color: var(--color-warning) !important;
	}
	.chip-btn:global(.res-chip--danger) {
		border-color: var(--color-danger) !important;
	}
	.chip-btn:global(.res-chip--sol) {
		border-color: transparent;
	}
	.ap-label {
		font-size: 0.72rem;
	}
	/* Trust chip: "Zufrieden · +1 AP" does not fit beside the label at rail width — label on top, value below. */
	.chip-btn--stack {
		flex-direction: column;
		align-items: flex-start;
		justify-content: center;
		gap: 0;
		padding-top: 0.25rem;
		padding-bottom: 0.25rem;
		line-height: 1.25;
	}
	.chip-btn--stack .ap-label {
		font-size: 0.66rem;
		opacity: 0.7;
	}
	/* Band word left, AP effect right; at the narrow (112 px) rail the effect drops to its own line instead of clipping. */
	.res-amount--split {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		column-gap: 6px;
		width: 100%;
		white-space: nowrap;
	}
	/* Band tone survives the glass look as text colour of the value and a hairline (not colour alone: the word says it). */
	.chip-btn:global(.ap-chip--trust-pos) .res-amount {
		color: var(--color-success-fg);
	}
	.chip-btn:global(.ap-chip--trust-warn) {
		border-color: var(--color-warning) !important;
	}
	.chip-btn:global(.ap-chip--trust-warn) .res-amount {
		color: var(--color-warning-fg);
	}
	.chip-btn:global(.ap-chip--trust-neg) {
		border-color: var(--color-danger) !important;
	}
	.chip-btn:global(.ap-chip--trust-neg) .res-amount {
		color: var(--color-danger-fg);
	}

	/* Trust popup: band effect, then the five-band ladder with the current band marked. */
	.trust-lead {
		font-weight: 700;
		font-size: 0.85rem;
	}
	.trust-ladder {
		display: grid;
		grid-template-columns: repeat(5, 1fr);
		gap: 3px;
		margin: 0.15rem 0 0.25rem;
		padding: 0;
		list-style: none;
	}
	.trust-rung {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 2px;
		font-size: 0.68rem;
		color: var(--color-text-secondary);
		font-variant-numeric: tabular-nums;
	}
	.trust-rung__bar {
		display: block;
		width: 100%;
		height: 8px;
		border-radius: var(--radius-pill);
		background: var(--color-locked);
	}
	.trust-rung--neg .trust-rung__bar {
		background: var(--color-danger-bg);
		box-shadow: inset 0 0 0 1px var(--color-danger);
	}
	.trust-rung--warn .trust-rung__bar {
		background: var(--color-warning-bg);
		box-shadow: inset 0 0 0 1px var(--color-warning);
	}
	.trust-rung--neu .trust-rung__bar {
		background: var(--ap-neutral-bg);
		box-shadow: inset 0 0 0 1px var(--color-border-strong);
	}
	.trust-rung--pos .trust-rung__bar {
		background: var(--color-success-bg);
		box-shadow: inset 0 0 0 1px var(--color-success);
	}
	.trust-rung--current {
		color: var(--color-text-primary);
		font-weight: 700;
	}
	.trust-rung--current .trust-rung__bar {
		height: 12px;
		margin-top: -2px;
		box-shadow: inset 0 0 0 2px var(--color-anthracite);
	}
	.trust-rung--current.trust-rung--neg .trust-rung__bar {
		background: var(--color-danger);
	}
	.trust-rung--current.trust-rung--warn .trust-rung__bar {
		background: var(--color-warning);
	}
	.trust-rung--current.trust-rung--neu .trust-rung__bar {
		background: var(--color-border-strong);
	}
	.trust-rung--current.trust-rung--pos .trust-rung__bar {
		background: var(--color-success);
	}
	.trust-rung--current .trust-rung__ap::before {
		content: '▲ ';
		font-size: 0.55rem;
	}
	/* Sentence rows (trust total, locked hint): normal case, full strength, not a table label. */
	.res-popup-row--plain :global(.res-popup-label) {
		text-transform: none;
		letter-spacing: 0;
		opacity: 1;
		font-size: 0.78rem;
		font-weight: 600;
	}
	.res-popup-row--note :global(.res-popup-label) {
		color: var(--color-text-secondary);
		font-style: italic;
		font-weight: 400;
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
