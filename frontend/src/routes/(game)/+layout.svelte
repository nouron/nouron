<script lang="ts">
	// Frame of every in-run screen: header + resource bar, page transition.
	import '../../../../public/css/resources.css';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { fade } from 'svelte/transition';
	import AppHeader from '#lib/components/AppHeader.svelte';
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
</script>

{#if $shell}<AppHeader s={$shell} />{/if}

{#key page.url.pathname + ':' + $solEpoch}
	<main in:fade={{ duration: 150 }}>{@render children()}</main>
{/key}

{#if $shell}<FeedbackButton />{/if}
