<script lang="ts">
	// Resource rail: fixed overlay on the left edge (docs/superpowers/specs/2026-11-08-ressourcenleiste-links-design.md).
	// Expanded = panel with all chips; collapsed = only a handle (chevron + AP); peek = collapsed but briefly open
	// on mouse hover / keyboard focus. Never part of the layout flow, so toggling never resizes the page.
	import { page } from '$app/state';
	import { t } from '#lib/i18n.ts';
	import { tileTaps } from '#lib/stores/rail.ts';
	import type { Shell } from '#lib/stores/shell.ts';
	import ResourceBar from './ResourceBar.svelte';

	let { s }: { s: Shell } = $props();

	type Group = 'colony' | 'other';
	const group = $derived<Group>(page.url.pathname === '/colony' ? 'colony' : 'other');
	const KEY = (g: Group) => `nouron.rail.${g}`;

	// User's choice per screen group; null = default (colony expanded, everything else collapsed). Comfort only.
	let choice = $state<Record<Group, boolean | null>>({ colony: null, other: null });
	try {
		for (const g of ['colony', 'other'] as Group[]) {
			const v = localStorage.getItem(KEY(g));
			if (v === '1' || v === '0') choice[g] = v === '1';
		}
	} catch {
		/* private mode / blocked storage: defaults apply */
	}
	// Temporary fold-in (phone tile tap, outside tap); never stored, cleared when the screen group changes.
	let folded = $state(false);
	let peek = $state(false);
	let open = $state<string | null>(null);
	let panelEl = $state<HTMLElement>();
	let handleEl = $state<HTMLButtonElement>();

	const pinned = $derived(folded ? false : (choice[group] ?? group === 'colony'));
	const shown = $derived(pinned || peek);

	function setPinned(v: boolean) {
		folded = false;
		peek = false;
		choice[group] = v;
		if (!v) open = null;
		try {
			localStorage.setItem(KEY(group), v ? '1' : '0');
		} catch {
			/* ignore */
		}
	}

	// Group change: forget temporary state, close the popup (also on every route change).
	let lastGroup: Group | null = null;
	$effect(() => {
		void page.url.pathname;
		open = null;
		if (lastGroup !== null && lastGroup !== group) {
			folded = false;
			autoDone = false;
		}
		lastGroup = group;
	});

	// Phones: the first tile tap on the colony folds the rail in so the way back to the map is not covered.
	let autoDone = false;
	let lastTaps = 0;
	$effect(() => {
		const n = $tileTaps;
		if (n === lastTaps) return;
		lastTaps = n;
		if (group === 'colony' && window.innerWidth < 600 && !autoDone) {
			autoDone = true;
			if (pinned) folded = true;
			open = null;
		}
	});

	// Peek: hover (mouse only, 200 ms in / 300 ms out) or keyboard focus on the handle.
	let peekTimer: ReturnType<typeof setTimeout>;
	function peekIn(e: PointerEvent) {
		if (e.pointerType !== 'mouse' || pinned) return;
		clearTimeout(peekTimer);
		peekTimer = setTimeout(() => (peek = true), 200);
	}
	function peekOut(e: PointerEvent) {
		if (e.pointerType !== 'mouse') return;
		clearTimeout(peekTimer);
		if (peek && !pinned) peekTimer = setTimeout(() => ((peek = false), (open = null)), 300);
	}
	function onFocusIn(e: FocusEvent) {
		if (!pinned && (e.target as HTMLElement).matches(':focus-visible')) {
			clearTimeout(peekTimer);
			peek = true;
		}
	}
	function onFocusOut(e: FocusEvent) {
		const next = e.relatedTarget as Node | null;
		if (peek && !pinned && !(next && (panelEl?.contains(next) || handleEl?.contains(next)))) {
			peek = false;
			open = null;
		}
	}

	function isTyping(el: EventTarget | null) {
		const n = el as HTMLElement | null;
		return !!n && (/^(INPUT|TEXTAREA|SELECT)$/.test(n.tagName) || n.isContentEditable);
	}

	function onKey(e: KeyboardEvent) {
		if (e.key === 'Escape') {
			// First the popup, then the panel (only when focus is inside the rail; focus returns to the handle).
			if (open !== null) {
				open = null;
			} else if (shown && (panelEl?.contains(document.activeElement) || handleEl === document.activeElement)) {
				if (pinned) setPinned(false);
				peek = false;
				handleEl?.focus();
			}
		} else if (
			(e.key === 'r' || e.key === 'R') &&
			!e.ctrlKey && !e.metaKey && !e.altKey &&
			!isTyping(e.target) &&
			!document.querySelector('dialog[open]')
		) {
			setPinned(!pinned);
		}
	}

	// Outside click: closes the popup; on touch devices a pinned panel outside the colony folds in too (look-up only there).
	function onWindowClick(e: MouseEvent) {
		const el = e.target as Element;
		if (el.closest('.rc') || el.closest('.rail-handle')) return;
		if (open !== null) {
			open = null;
		} else if (pinned && group === 'other' && !el.closest('.rail-panel') && matchMedia('(hover: none)').matches) {
			setPinned(false);
		}
	}

	// Warning mark on the handle (not colour alone): nexus debt >= 80 %, trust below the red threshold, unfilled workplaces.
	const warn = $derived.by(() => {
		const nx = s.nexus_debt !== null && s.nexus_debt_max > 0 ? (s.nexus_debt / s.nexus_debt_max) * 100 : 0;
		const danger = nx >= 95 || (s.trust !== undefined && s.trust < s.trust_chip_red);
		const warning = nx >= 80 || (s.colonists_overview?.unfilled ?? 0) > 0;
		return danger ? 'danger' : warning ? 'warning' : null;
	});
</script>

<svelte:window onkeydown={onKey} onclick={onWindowClick} />

<div class="rail" class:rail--shown={shown} onfocusin={onFocusIn} onfocusout={onFocusOut}>
	<button
		type="button"
		class="rail-handle"
		class:rail-handle--warning={warn === 'warning'}
		class:rail-handle--danger={warn === 'danger'}
		bind:this={handleEl}
		aria-expanded={shown}
		aria-controls="rail-panel"
		aria-label={t('resources.rail_toggle_ap', { ap: s.colony_ap ?? 0 })}
		title={t('resources.rail_toggle')}
		onclick={() => setPinned(!pinned)}
		onpointerenter={peekIn}
		onpointerleave={peekOut}
	>
		<span class="tab">
			<i class="bi {shown ? 'bi-chevron-left' : 'bi-chevron-right'}" aria-hidden="true"></i>
			{#if !shown}<span class="tab-ap">{s.colony_ap ?? '–'}</span>{/if}
			{#if warn}<span class="tab-warn" aria-label={t('resources.rail_warning')}>!</span>{/if}
		</span>
	</button>

	<aside
		id="rail-panel"
		class="rail-panel"
		bind:this={panelEl}
		aria-label={t('resources.rail_label')}
		inert={!shown}
		onpointerenter={peekIn}
		onpointerleave={peekOut}
	>
		<div class="rail-scroll" onscroll={() => (open = null)}>
			<ResourceBar {s} bind:open />
		</div>
	</aside>
</div>

<style>
	.rail {
		--rail-w: 112px;
		--rail-top: calc(var(--app-header-h) + 8px);
		--rail-bg: rgba(255, 255, 255, 0.72);
		--rail-blur: 8px;
		position: fixed;
		inset: 0;
		z-index: 80;
		/* Only the panel and the handle catch pointer events; the map and page stay clickable. */
		pointer-events: none;
	}
	@media (min-width: 900px) {
		.rail {
			--rail-w: 132px;
		}
	}

	.rail-panel {
		position: fixed;
		top: var(--rail-top);
		left: calc(-1 * var(--rail-w) - 24px);
		width: var(--rail-w);
		box-sizing: border-box;
		border-radius: 0 var(--radius-pill) var(--radius-pill) 0;
		box-shadow: var(--shadow-flyout);
		pointer-events: auto;
		visibility: hidden;
		transition:
			left var(--duration-fast) ease-out,
			visibility 0s linear var(--duration-fast);
	}
	/* Blur lives on a background layer: backdrop-filter on the panel itself would make it the containing block of the
	   fixed popups, which would then be clipped by the scroll area. */
	.rail-panel::before {
		content: '';
		position: absolute;
		inset: 0;
		border: 1px solid var(--color-border);
		border-left: 0;
		border-radius: inherit;
		background: var(--rail-bg);
		-webkit-backdrop-filter: blur(var(--rail-blur));
		backdrop-filter: blur(var(--rail-blur));
		pointer-events: none;
	}
	.rail-scroll {
		position: relative;
		max-height: calc(100vh - var(--rail-top) - var(--app-bottom-h) - 8px);
		max-height: calc(100dvh - var(--rail-top) - var(--app-bottom-h) - 8px);
		overflow-y: auto;
		padding: 8px 4px 8px 8px;
		border-radius: inherit;
	}
	.rail--shown .rail-panel {
		left: 0;
		visibility: visible;
		transition: left var(--duration-fast) ease-out;
	}

	/* Handle: 44x64 touch area, 28 px visible tab; sits on the panel's right edge when open. */
	.rail-handle {
		position: fixed;
		top: var(--rail-top);
		left: 0;
		width: 44px;
		height: 64px;
		padding: 0;
		margin: 0;
		border: 0;
		background: none;
		cursor: pointer;
		pointer-events: auto;
		z-index: 1;
		transition: left var(--duration-fast) ease-out;
		-webkit-tap-highlight-color: transparent;
	}
	.rail--shown .rail-handle {
		left: calc(var(--rail-w) - 1px);
	}
	.tab {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 2px;
		width: 28px;
		height: 100%;
		box-sizing: border-box;
		background: var(--rail-bg);
		-webkit-backdrop-filter: blur(var(--rail-blur));
		backdrop-filter: blur(var(--rail-blur));
		border: 1px solid var(--color-border);
		border-left: 0;
		border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
		box-shadow: var(--shadow-flyout);
		color: var(--color-text-primary);
		font-size: 0.72rem;
		font-weight: 700;
		font-variant-numeric: tabular-nums;
	}
	.rail--shown .tab {
		height: 44px;
		margin-top: 0;
	}
	.rail-handle:hover .tab {
		background: var(--color-surface);
	}
	.rail-handle:focus-visible {
		outline: none;
	}
	.rail-handle:focus-visible .tab {
		outline: 2px solid var(--color-accent);
		outline-offset: 1px;
	}
	.rail-handle--warning .tab {
		border-color: var(--color-warning);
	}
	.rail-handle--danger .tab {
		border-color: var(--color-danger);
	}
	.tab-warn {
		position: absolute;
		top: 2px;
		right: 3px;
		font-size: 0.65rem;
		line-height: 1;
		color: var(--color-warning-fg);
	}
	.rail-handle--danger .tab-warn {
		color: var(--color-danger-fg);
	}

	/* Fallbacks: no transparency/blur when the user or the browser asks for none. */
	@media (prefers-reduced-transparency: reduce), (prefers-contrast: more) {
		.rail {
			--rail-bg: var(--color-bg);
			--rail-blur: 0px;
		}
	}
	@supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
		.rail {
			--rail-bg: var(--color-bg);
		}
	}
	@media (prefers-reduced-motion: reduce) {
		.rail-panel,
		.rail-handle {
			transition: none;
		}
	}
</style>
