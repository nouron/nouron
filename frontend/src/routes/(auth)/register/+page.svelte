<script lang="ts">
	// Port of resources/views/auth/register.blade.php (ADR 0006 stage 5a).
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import Button from '#lib/components/Button.svelte';
	import { API_URL, FormError, register } from '#lib/stores/auth.ts';
	import type { FullAutoFill } from 'svelte/elements';
	import { t } from '#lib/i18n.ts';

	const f = $state({ username: '', email: '', password: '', password_confirmation: '', invite_code: '' });
	let errors = $state<Record<string, string[]>>({});
	let error = $state('');
	let inviteRequired = $state(false);
	let busy = $state(false);

	onMount(async () => {
		const res = await fetch(`${API_URL}/auth/register`, { headers: { Accept: 'application/json' } });
		if (res.ok) inviteRequired = (await res.json()).invite_required;
	});

	async function submit(e: SubmitEvent) {
		e.preventDefault();
		errors = {};
		error = '';
		busy = true;
		try {
			await register(f);
			await goto('/lobby');
		} catch (err) {
			if (err instanceof FormError) errors = err.errors;
			else error = (err as Error).message;
		} finally {
			busy = false;
		}
	}
</script>

<svelte:head><title>{t('auth.register_title')} – Nouron</title></svelte:head>

{#snippet field(name: keyof typeof f, type: string, label: string, autocomplete: FullAutoFill)}
	<label
		>{label}
		<input {type} bind:value={f[name]} required {autocomplete} aria-invalid={!!errors[name]} />
		{#if errors[name]}<small class="field-error">{errors[name][0]}</small>{/if}
	</label>
{/snippet}

<h1>{t('auth.register_title')}</h1>
<form onsubmit={submit}>
	{@render field('username', 'text', t('auth.username'), 'username')}
	{@render field('email', 'email', t('auth.email'), 'email')}
	{@render field('password', 'password', t('auth.password'), 'new-password')}
	{@render field('password_confirmation', 'password', t('auth.password_confirmation'), 'new-password')}
	{#if inviteRequired}{@render field('invite_code', 'text', t('auth.invite_code'), 'off')}{/if}
	{#if error}<p class="error" role="alert">{error}</p>{/if}
	<Button type="submit" disabled={busy}>{t('auth.register_button')}</Button>
</form>
<p class="footer">{t('auth.have_account')} <a href="/login">{t('auth.login_title')}</a></p>
