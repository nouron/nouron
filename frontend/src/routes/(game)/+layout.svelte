<script lang="ts">
	// Frame of every in-run screen: header, resource rail (left overlay), page, bottom navigation (+ admin debug bar), page transition.
	import '#lib/styles/resources.css';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { fade } from 'svelte/transition';
	import BottomNav from '#lib/components/BottomNav.svelte';
	import AppHeader from '#lib/components/AppHeader.svelte';
	import DebugBar from '#lib/components/DebugBar.svelte';
	import ResourceRail from '#lib/components/ResourceRail.svelte';
	import FeedbackButton from '#lib/components/FeedbackButton.svelte';
	import { token } from '#lib/stores/auth.ts';
	import { refreshShell, shell, solEpoch } from '#lib/stores/shell.ts';

	let { children } = $props();
	let feedback = $state<FeedbackButton>();

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
			const d = document.querySelector<HTMLElement>('.bottom-chrome');
			root.style.setProperty('--app-header-h', `${h ? h.offsetHeight : 0}px`);
			root.style.setProperty('--app-bottom-h', `${d ? d.offsetHeight : 0}px`);
		};
		const ro = new ResizeObserver(apply);
		const sync = () => {
			ro.disconnect();
			document.querySelectorAll('.app-header, .bottom-chrome').forEach((el) => ro.observe(el));
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

{#if $shell}
	<AppHeader s={$shell} onfeedback={() => feedback?.open()} />
	<!-- Fixed left overlay; kept outside the {#key} so its state survives page changes. -->
	<ResourceRail s={$shell} />
{/if}

{#key page.url.pathname + page.url.search + ':' + $solEpoch}
	<main in:fade={{ duration: 150 }}>{@render children()}</main>
{/key}

{#if $shell}
	<FeedbackButton bind:this={feedback} floating={false} />
	<!-- Fixed bottom chrome: navigation, with the collapsible admin debug bar underneath. -->
	<div class="bottom-chrome">
		<BottomNav s={$shell} />
		{#if $shell.is_admin}<DebugBar docked />{/if}
	</div>
{/if}

<style>
	.bottom-chrome {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 200;
	}
	/* Reserve the measured height of the fixed bottom chrome so content never hides behind it. */
	main {
		padding-bottom: var(--app-bottom-h);
	}
</style>
