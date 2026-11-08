<script lang="ts">
	// Full-screen detail sheet for phones (< 600 px): replaces the side panel there. Plain role="dialog" (not a native
	// <dialog>) on purpose, so toasts (z-index 500) stay visible above it. Covers header, rail and bottom navigation
	// completely (z-index 300 > bottom chrome 200), so there is exactly one place to act and one way out (X / Esc).
	import { tick, type Snippet } from 'svelte';
	import { t } from '#lib/i18n.ts';

	let {
		open,
		title,
		onclose,
		returnFocus,
		bodyClass = '',
		children
	}: {
		open: boolean;
		title: string;
		onclose: () => void;
		/** Element to focus after closing when the opener is no longer focusable (e.g. an SVG tile). */
		returnFocus?: () => HTMLElement | null | undefined;
		bodyClass?: string;
		children: Snippet;
	} = $props();

	let sheet = $state<HTMLElement>();
	let closeBtn = $state<HTMLButtonElement>();
	let opener: HTMLElement | null = null;
	const titleId = `sheet-title-${Math.random().toString(36).slice(2, 8)}`;

	const FOCUSABLE =
		'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

	// Open/close side effects: remember opener, lock body scroll, move focus in; undo on close.
	$effect(() => {
		if (!open) return;
		opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
		const prevOverflow = document.body.style.overflow;
		document.body.style.overflow = 'hidden';
		tick().then(() => closeBtn?.focus());
		return () => {
			document.body.style.overflow = prevOverflow;
			const target = opener && opener !== document.body && opener.isConnected ? opener : returnFocus?.();
			target?.focus?.({ preventScroll: true });
		};
	});

	function onKeydown(e: KeyboardEvent) {
		if (!open || !sheet) return;
		if (e.key === 'Escape') {
			// A native modal on top (leveldown/confirm) handles its own Esc.
			if (document.querySelector('dialog[open]')) return;
			e.preventDefault();
			e.stopPropagation();
			onclose();
		} else if (e.key === 'Tab') {
			const items = [...sheet.querySelectorAll<HTMLElement>(FOCUSABLE)].filter((el) => el.offsetParent !== null);
			if (!items.length) return;
			const first = items[0];
			const last = items[items.length - 1];
			const active = document.activeElement;
			if (e.shiftKey && (active === first || !sheet.contains(active))) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && (active === last || !sheet.contains(active))) {
				e.preventDefault();
				first.focus();
			}
		}
	}
</script>

<svelte:window onkeydowncapture={onKeydown} />

{#if open}
	<div class="mobile-sheet" role="dialog" aria-modal="true" aria-labelledby={titleId} bind:this={sheet}>
		<header class="mobile-sheet__head">
			<h2 id={titleId} class="mobile-sheet__title">{title}</h2>
			<button type="button" class="mobile-sheet__close" bind:this={closeBtn} onclick={onclose} aria-label={t('colony.sheet_close')}>
				<span aria-hidden="true">&#x2715;</span>
			</button>
		</header>
		<div class="mobile-sheet__body {bodyClass}">
			{@render children()}
		</div>
	</div>
{/if}

<style>
	.mobile-sheet {
		position: fixed;
		inset: 0;
		z-index: 300;
		display: flex;
		flex-direction: column;
		background: #fff;
		padding-top: env(safe-area-inset-top);
		animation: sheet-in 160ms ease-out;
	}
	.mobile-sheet__head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: var(--space-sm, 0.5rem);
		flex-shrink: 0;
		padding: 0 0.5rem 0 1rem;
		min-height: 56px;
		border-bottom: 2px solid var(--color-accent);
	}
	.mobile-sheet__title {
		margin: 0;
		font-size: 0.95rem;
		font-weight: 600;
		letter-spacing: 0.03em;
		min-width: 0;
		overflow-wrap: break-word;
	}
	.mobile-sheet__close {
		flex-shrink: 0;
		width: 44px;
		height: 44px;
		margin: 0;
		padding: 0;
		display: grid;
		place-items: center;
		font-size: 1.2rem;
		line-height: 1;
		background: transparent;
		border: 1px solid var(--color-border-strong, #d0d0dc);
		border-radius: 4px;
		color: var(--color-anthracite, #333);
	}
	.mobile-sheet__body {
		flex: 1;
		min-height: 0;
		overflow-y: auto;
		overscroll-behavior: contain;
		padding-bottom: env(safe-area-inset-bottom);
	}
	@keyframes sheet-in {
		from {
			transform: translateY(24px);
			opacity: 0;
		}
	}
	@media (prefers-reduced-motion: reduce) {
		.mobile-sheet {
			animation: none;
		}
	}
</style>
