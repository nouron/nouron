<script lang="ts">
	import { goto } from '$app/navigation';
	import Button from '#lib/components/Button.svelte';
	import { api, token } from '#lib/stores/auth.ts';

	let result = $state('…');

	$effect(() => {
		if (!$token) {
			goto('/login');
			return;
		}
		api<{ pong: boolean; user_id: number }>('/ping')
			.then((r) => (result = JSON.stringify(r)))
			.catch((e) => (result = e.message));
	});
</script>

<main>
	<h1>Nouron</h1>
	<p>Ping: <code>{result}</code></p>
	<p><a href="/techtree">Techtree</a></p>
	<Button variant="ghost" onclick={() => token.set(null)}>Abmelden</Button>
</main>

<style>
	main {
		max-width: 56rem;
		margin: 0 auto;
		padding: var(--space-lg) var(--space-md);
	}
</style>
