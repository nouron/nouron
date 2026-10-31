<script lang="ts">
	import { goto } from '$app/navigation';
	import Button from '#lib/components/Button.svelte';
	import { login } from '#lib/stores/auth.ts';

	let name = $state('');
	let password = $state('');
	let error = $state('');
	let busy = $state(false);

	async function submit(e: SubmitEvent) {
		e.preventDefault();
		error = '';
		busy = true;
		try {
			await login(name, password);
			await goto('/');
		} catch (err) {
			error = (err as Error).message;
		} finally {
			busy = false;
		}
	}
</script>

<svelte:head><title>Anmelden – Nouron</title></svelte:head>

<!-- ponytail: hardcoded texts until i18n endpoint (GET /api/locale/{lang}, ADR 0006) exists. -->
<main>
	<h1>Nouron</h1>
	<form onsubmit={submit}>
		<label
			>Benutzername oder E-Mail
			<input bind:value={name} required autocomplete="username" aria-invalid={!!error} />
		</label>
		<label
			>Passwort
			<input
				type="password"
				bind:value={password}
				required
				autocomplete="current-password"
				aria-invalid={!!error}
			/>
		</label>
		{#if error}<p class="error" role="alert">{error}</p>{/if}
		<Button type="submit" disabled={busy}>Anmelden</Button>
	</form>
</main>

<style>
	main {
		max-width: 26rem;
		margin: 0 auto;
		padding: 18vh var(--space-md) var(--space-lg);
	}
	h1 {
		text-align: center;
		padding-left: var(--text-h1-tracking); /* balance trailing letter-spacing */
		margin-bottom: var(--space-lg);
	}
	form :global(.btn) {
		width: 100%;
	}
</style>
