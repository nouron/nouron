<script lang="ts" module>
	export type ApRow = {
		ap_spend: number;
		ap_for_levelup: number;
		ap_cap?: { cap: number; spent: number; remaining: number } | null;
	};
</script>

<script lang="ts">
	// Port of resources/views/partials/ap-upgrade-button.blade.php — ONE upgrade button with a
	// segmented AP bar for the colony "Ausbauen" and the techtree knowledge level-up.
	// Bar logic: public/js/ap-bar.js (window.NouronApBar), styles: public/css/ap-upgrade-button.css.
	import '../../../../public/css/ap-upgrade-button.css';
	import '../../../../public/js/ap-bar.js';
	import { t } from '#lib/i18n.ts';

	let {
		row,
		label,
		onclick,
		chip = 'build',
		blocked: extraBlocked = false,
		gateReason = null,
		rg = 0,
		regolith = 0,
		capTip = t('colony.ap_cap_tooltip'),
		tipId = 'ap-cap-tip',
		secondary = false
	}: {
		row: ApRow;
		label: string;
		onclick: () => void;
		chip?: 'build' | 'research';
		blocked?: boolean;
		gateReason?: string | null;
		rg?: number;
		regolith?: number;
		capTip?: string;
		tipId?: string;
		secondary?: boolean;
	} = $props();

	const bar = (
		window as unknown as {
			NouronApBar: {
				apCapReached(r: ApRow): boolean;
				apCapTipApplies(r: ApRow): boolean;
				apBarLayout(r: ApRow): { rows: number; cols: number };
				apCapLimitPlace(r: ApRow): { left: number; row: number } | null;
				apSegClasses(r: ApRow, i: number): Record<string, boolean>;
			};
		}
	).NouronApBar;

	let hover = $state(false);
	const blocked = $derived(!!gateReason || bar.apCapReached(row) || extraBlocked);
	const tipOn = $derived(!!gateReason || bar.apCapTipApplies(row));
	const layout = $derived(bar.apBarLayout(row));
	const limit = $derived(bar.apCapLimitPlace(row));
</script>

<button
	class="tile-action-btn tile-action-btn--barred ap-upgrade-btn"
	class:tile-action-btn--secondary={secondary}
	class:tile-action-btn--hascap={tipOn}
	class:tile-action-btn--capped={blocked}
	style="--seg-rows: {layout.rows}"
	onmouseenter={() => (hover = true)}
	onmouseleave={() => (hover = false)}
	aria-disabled={blocked ? 'true' : undefined}
	aria-describedby={tipOn ? tipId : undefined}
	onclick={() => !blocked && onclick()}
>
	<span class="tile-action-btn__body">
		<span class="tile-action-btn__main">{label}</span>
	</span>
	<span class="tile-action-btn__costs">
		{#if rg > 0}
			<span class="ap-chip ap-cost-chip rg-cost-chip" class:rg-cost-chip--short={rg > regolith} aria-hidden="true"
				>{rg} Rg</span
			>
		{/if}
		<span class="ap-chip ap-cost-chip ap-chip--{chip}" aria-hidden="true">1 AP</span>
	</span>
	<span class="btn-segbar btn-segbar--ap" aria-hidden="true" style="--seg-cols: {layout.cols}">
		{#each Array.from({ length: row.ap_for_levelup }, (_, k) => k + 1) as i (i)}
			<span
				class={['btn-seg', bar.apSegClasses(row, i), { 'btn-seg--ghost': hover && i === row.ap_spend + 1 && !blocked }]}
			></span>
		{/each}
		{#if limit}
			<span class="btn-seg-limit" style="left: {limit.left}%; --limit-row: {limit.row}"></span>
		{/if}
	</span>
	<span id={tipId} class="tile-action-btn__tip" role="tooltip">{gateReason || capTip}</span>
</button>
