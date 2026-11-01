<script lang="ts">
	// Frame of every in-run screen: header + resource bar, page transition.
	import '../../../../public/css/resources.css';
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
</script>

{#if $shell}<AppHeader s={$shell} />{/if}

{#key page.url.pathname + page.url.search + ':' + $solEpoch}
	<main in:fade={{ duration: 150 }}>{@render children()}</main>
{/key}

{#if $shell}<FeedbackButton />{/if}
{#if $shell?.is_admin}<DebugBar />{/if}
