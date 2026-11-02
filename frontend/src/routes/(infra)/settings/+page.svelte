<script lang="ts">
	// Port of resources/views/user/show.blade.php + settings.blade.php (ADR 0006 stage 5b): one page.
	import { onMount } from 'svelte';
	import Button from '#lib/components/Button.svelte';
	import { api } from '#lib/stores/auth.ts';
	import { locale as currentLocale, t, useLocale } from '#lib/i18n.ts';

	type Data = {
		username: string;
		display_name: string;
		email: string;
		role: string;
		registration: string | null;
		onboarding_hints: boolean;
		locale: string;
		supported_locales: string[];
	};
	type Result = { ok?: boolean; message?: string; errors?: Record<string, string[]> };

	let d = $state<Data | null>(null);
	let alert = $state<{ type: 'success' | 'error'; text: string } | null>(null);
	let errors = $state<Record<string, string[]>>({});
	let busy = $state(false);
	const pw = $state({ current_password: '', password: '', password_confirmation: '' });

	onMount(async () => {
		d = await api<Data>('/settings');
	});

	async function save(path: string, body: object, e?: Event): Promise<boolean> {
		e?.preventDefault();
		busy = true;
		errors = {};
		try {
			const res = await api<Result>(`/settings/${path}`, { method: 'PATCH', body, allowError: true });
			if (res.ok) alert = { type: 'success', text: res.message ?? '' };
			else errors = res.errors ?? {};
			if (!res.ok && !res.errors) alert = { type: 'error', text: res.message ?? '' };
			return !!res.ok;
		} catch {
			alert = { type: 'error', text: t('colony.network_error') };
			return false;
		} finally {
			busy = false;
		}
	}
</script>

<svelte:head><title>{t('settings.title')} – Nouron</title></svelte:head>

{#if alert}<p class="infra-alert infra-alert--{alert.type}" role="alert">{alert.text}</p>{/if}

{#snippet error(name: string)}
	{#if errors[name]}<small class="field-error">{errors[name][0]}</small>{/if}
{/snippet}

{#if d}
	<div class="settings">
		<h1><i class="bi bi-person-circle"></i> {d.username}</h1>
		<dl>
			<dt>{t('settings.profile_email')}</dt>
			<dd>{d.email}</dd>
			<dt>{t('settings.profile_role')}</dt>
			<dd>{d.role}</dd>
			{#if d.registration}<dt>{t('settings.profile_registered')}</dt>
				<dd>{new Date(d.registration).toLocaleDateString(currentLocale)}</dd>{/if}
		</dl>

		<section>
			<h2><i class="bi bi-person"></i> {t('settings.display_name_title')}</h2>
			<form onsubmit={(e) => save('name', { display_name: d!.display_name }, e)}>
				<label
					>{t('settings.profile_display_name')}
					<input bind:value={d.display_name} maxlength="50" required aria-invalid={!!errors.display_name} />
					{@render error('display_name')}
				</label>
				<Button type="submit" disabled={busy}>{t('settings.save')}</Button>
			</form>
		</section>

		<section>
			<h2><i class="bi bi-lock"></i> {t('settings.password_title')}</h2>
			<form
				onsubmit={(e) =>
					save('password', pw, e).then((ok) => ok && Object.assign(pw, { current_password: '', password: '', password_confirmation: '' }))}
			>
				<label
					>{t('settings.password_current')}
					<input type="password" bind:value={pw.current_password} required autocomplete="current-password" aria-invalid={!!errors.current_password} />
					{@render error('current_password')}
				</label>
				<label
					>{t('settings.password_new')}
					<input type="password" bind:value={pw.password} required autocomplete="new-password" aria-invalid={!!errors.password} />
					{@render error('password')}
				</label>
				<label
					>{t('settings.password_confirmation')}
					<input type="password" bind:value={pw.password_confirmation} required autocomplete="new-password" />
				</label>
				<Button type="submit" disabled={busy}>{t('settings.password_title')}</Button>
			</form>
		</section>

		<section>
			<h2><i class="bi bi-info-circle"></i> {t('settings.onboarding_title')}</h2>
			<!-- Saves on toggle: one switch needs no extra button. -->
			<label class="switch">
				<input
					type="checkbox"
					role="switch"
					bind:checked={d.onboarding_hints}
					disabled={busy}
					onchange={() => save('onboarding', { onboarding_hints: d!.onboarding_hints })}
				/>
				{t('settings.onboarding_label')}
			</label>
			<p class="help">{t('settings.onboarding_help')}</p>
		</section>

		<section>
			<h2><i class="bi bi-translate"></i> {t('settings.language')}</h2>
			<form onsubmit={(e) => save('locale', { locale: d!.locale }, e).then((ok) => ok && useLocale(d!.locale))}>
				<label
					>{t('settings.language')}
					<select bind:value={d.locale} aria-invalid={!!errors.locale}>
						{#each d.supported_locales as code (code)}<option value={code}>{t(`settings.locale_${code}`)}</option>{/each}
					</select>
					{@render error('locale')}
				</label>
				<Button type="submit" disabled={busy || d.locale === currentLocale}>{t('settings.save')}</Button>
			</form>
		</section>
	</div>
{/if}

<style>
	.settings {
		max-width: 36rem;
	}
	h1 {
		display: flex;
		align-items: center;
		gap: var(--space-sm);
	}
	dl {
		display: grid;
		grid-template-columns: auto 1fr;
		gap: var(--space-xs) var(--space-md);
		margin: 0 0 var(--space-lg);
	}
	dt {
		color: var(--color-text-secondary);
	}
	dd {
		margin: 0;
	}
	section {
		border: 1px solid var(--color-border);
		border-radius: var(--radius-sm);
		padding: var(--space-md);
		margin-bottom: var(--space-md);
		background: var(--color-surface);
	}
	h2 {
		font-size: var(--text-h4-size, 1rem);
		margin: 0 0 var(--space-sm);
	}
	select {
		display: block;
		width: 100%;
		margin-top: var(--space-xs);
		padding: 0.5rem;
		font: inherit;
	}
	.switch {
		display: flex;
		align-items: center;
		gap: var(--space-sm);
	}
	.help {
		font-size: 0.85rem;
		color: var(--color-text-secondary);
		margin: var(--space-sm) 0 0;
	}
	.field-error {
		color: var(--color-danger, #c0392b);
		font-size: 0.85rem;
	}
</style>
