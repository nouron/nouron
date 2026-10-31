<script lang="ts">
	import { goto } from '$app/navigation';
	import { login } from '#lib/stores/auth.ts';

	let email = $state('');
	let password = $state('');
	let error = $state('');

	async function submit(e: SubmitEvent) {
		e.preventDefault();
		error = '';
		try {
			await login(email, password);
			await goto('/');
		} catch (err) {
			error = (err as Error).message;
		}
	}
</script>

<!-- ponytail: hardcoded spike texts; i18n via GET /api/locale/{lang} comes with stage 1 (ADR 0006). -->
<main>
	<h1>Nouron</h1>
	<form onsubmit={submit}>
		<label>E-Mail <input type="email" bind:value={email} required autocomplete="email" /></label>
		<label
			>Passwort <input
				type="password"
				bind:value={password}
				required
				autocomplete="current-password"
			/></label
		>
		<button>Anmelden</button>
		{#if error}<p role="alert">{error}</p>{/if}
	</form>
</main>
