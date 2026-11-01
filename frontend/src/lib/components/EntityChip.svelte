<script lang="ts">
	// Port of resources/views/components/entity-chip.blade.php: inline pill with hover/tap tooltip.
	import '../../../../public/css/entity-chips.css';
	import { t } from '#lib/i18n.ts';

	type Tooltip = { level?: number | null; description?: string; link?: string; link_label?: string; meta?: string };
	let {
		type,
		entityKey,
		label,
		tooltip = {}
	}: { type: string; entityKey: string; label: string; tooltip?: Tooltip } = $props();

	const ICONS: Record<string, string> = {
		building: 'bi-hexagon',
		knowledge: 'bi-book',
		resource: 'bi-layers',
		ship: 'bi-rocket-takeoff',
		advisor: 'bi-person-badge',
		research: 'bi-diagram-3'
	};
	const hasLevel = $derived(tooltip.level !== undefined && tooltip.level !== null);
	const hasTooltip = $derived(hasLevel || !!tooltip.description || !!tooltip.meta || !!tooltip.link);
	let open = $state(false);

	// Clamp horizontally to the viewport; flip below the chip when a <dialog> clips the top.
	function place(el: HTMLElement) {
		el.style.marginLeft = '';
		const r = el.getBoundingClientRect();
		if (r.left < 8) el.style.marginLeft = `${8 - r.left}px`;
		else if (r.right > window.innerWidth - 8) el.style.marginLeft = `${window.innerWidth - 8 - r.right}px`;
		const clip = el.closest('dialog');
		if (r.top < (clip ? clip.getBoundingClientRect().top : 0) + 8) {
			el.style.bottom = 'auto';
			el.style.top = 'calc(100% + 0.4rem)';
		}
	}
</script>

<svelte:window onclick={() => (open = false)} />

<span
	class="entity-chip entity-chip--{type}"
	data-chip-type={type}
	data-chip-key={entityKey}
	role="button"
	tabindex="0"
	aria-label={label}
	onmouseenter={() => (open = true)}
	onmouseleave={() => (open = false)}
	onclick={(e) => {
		e.stopPropagation();
		open = !open;
	}}
	onkeydown={(e) => {
		if (e.key === 'Escape') open = false;
		if (e.key === 'Enter') {
			e.preventDefault();
			open = !open;
		}
	}}
	><i class="bi {ICONS[type] ?? 'bi-circle'}" aria-hidden="true"></i>{label}{#if hasTooltip && open}<span
			class="entity-chip-tooltip"
			{@attach place}
			><span class="entity-chip-tooltip-name">{label}</span>{#if hasLevel}<span class="entity-chip-tooltip-row"
					><span class="entity-chip-tooltip-label">{t('entity_chip.label_level')}</span><span>{tooltip.level}</span></span
				>{/if}{#if tooltip.description}<span class="entity-chip-tooltip-desc">{tooltip.description}</span
				>{/if}{#if tooltip.meta}<span class="entity-chip-tooltip-row entity-chip-tooltip-meta">{tooltip.meta}</span
				>{/if}{#if tooltip.link}<span class="entity-chip-tooltip-link"
					><i class="bi bi-arrow-right" aria-hidden="true"></i><a href={tooltip.link}
						>{tooltip.link_label ?? t('entity_chip.label_open_link')}</a
					></span
				>{/if}</span
		>{/if}</span
>
