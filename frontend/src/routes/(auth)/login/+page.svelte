<script lang="ts">
	import { goto } from '$app/navigation';
	import Button from '#lib/components/Button.svelte';
	import { login } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';

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

<svelte:head><title>{t('auth.login_title')} – Nouron</title></svelte:head>

<h1>{t('auth.login_title')}</h1>
<form onsubmit={submit}>
	<label
		>{t('auth.login_name')}
		<!-- svelte-ignore a11y_autofocus -->
		<input bind:value={name} required autofocus autocomplete="username" aria-invalid={!!error} />
	</label>
	<label
		>{t('auth.password')}
		<input type="password" bind:value={password} required autocomplete="current-password" aria-invalid={!!error} />
	</label>
	{#if error}<p class="error" role="alert">{error}</p>{/if}
	<Button type="submit" disabled={busy}>{t('auth.login_button')}</Button>
</form>
<p class="footer">{t('auth.no_account')} <a href="/register">{t('auth.register_title')}</a></p>
