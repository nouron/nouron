<script lang="ts">
	import { goto } from '$app/navigation';
	import { api, token } from '#lib/stores/auth.ts';

	let result = $state('…');

	$effect(() => {
		if (!$token) {
			goto('/login');
			return;
		}
		api<{ pong: boolean; user_id: number }>('/ping', $token)
			.then((r) => (result = JSON.stringify(r)))
			.catch((e) => (result = e.message));
	});
</script>

<main>
	<h1>Nouron</h1>
	<p>Ping: <code>{result}</code></p>
	<button onclick={() => token.set(null)}>Abmelden</button>
</main>
