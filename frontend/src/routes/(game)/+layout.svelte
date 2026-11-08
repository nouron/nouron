<script lang="ts">
	// Frame of every in-run screen: header + resource bar, page transition.
	import '#lib/styles/resources.css';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { fade } from 'svelte/transition';
	import AppHeader from '#lib/components/AppHeader.svelte';
	import DebugBar from '#lib/components/DebugBar.svelte';
	import FeedbackButton from '#lib/components/FeedbackButton.svelte';
	import { token } from '#lib/stores/auth.ts';
	import { refreshShell, shell, solEpoch } from '#lib/stores/shell.ts';

	let { children } = $props();

	$effect(() => {
		if (!$token) goto('/login');
	});

	$effect(() => {
		if ($token) refreshShell().catch(() => {});
	});

	// Like the run.started middleware: without a started run the in-run screens send you to the lobby.
	$effect(() => {
		if ($shell && !$shell.in_active_run) goto('/lobby', { replaceState: true });
	});

	// Publish the measured chrome heights as --app-header-h / --app-bottom-h on :root (the one place for layout heights).
	// The debug bar can appear/close at any time, so a MutationObserver re-syncs the observed elements.
	$effect(() => {
		if (!$token) return;
		const root = document.documentElement;
		const apply = () => {
			const h = document.querySelector<HTMLElement>('.app-header');
			const d = document.querySelector<HTMLElement>('.debug');
			root.style.setProperty('--app-header-h', `${h ? h.offsetHeight : 0}px`);
			root.style.setProperty('--app-bottom-h', `${d ? d.offsetHeight : 0}px`);
		};
		const ro = new ResizeObserver(apply);
		const sync = () => {
			ro.disconnect();
			document.querySelectorAll('.app-header, .debug').forEach((el) => ro.observe(el));
			apply();
		};
		const mo = new MutationObserver(sync);
		mo.observe(document.body, { childList: true, subtree: true });
		sync();
		return () => {
			mo.disconnect();
			ro.disconnect();
			root.style.removeProperty('--app-header-h');
			root.style.removeProperty('--app-bottom-h');
		};
	});
</script>

{#if $shell}<AppHeader s={$shell} />{/if}

{#key page.url.pathname + page.url.search + ':' + $solEpoch}
	<main in:fade={{ duration: 150 }}>{@render children()}</main>
{/key}

{#if $shell}<FeedbackButton />{/if}
{#if $shell?.is_admin}<DebugBar />{/if}
