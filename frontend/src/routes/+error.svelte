<script lang="ts">
	// Port of resources/views/errors/404 + 500.blade.php: unknown route or failed page load.
	import { page } from '$app/state';
	import { t } from '#lib/i18n.ts';

	const notFound = $derived(page.status === 404);
</script>

<svelte:head><title>{page.status} – Nouron</title></svelte:head>

<main>
	<p class="code">{page.status}</p>
	<h1>{t(notFound ? 'errors.not_found_title' : 'errors.error_title')}</h1>
	<p class="text">{t(notFound ? 'errors.not_found_text' : 'errors.error_text')}</p>
	<a href="/"><i class="bi bi-house"></i> {t('errors.back_home')}</a>
</main>

<style>
	main {
		text-align: center;
		padding: 18vh var(--space-md) var(--space-lg);
	}
	.code {
		font: 700 5rem var(--font-display, inherit);
		color: var(--color-text-secondary);
		margin: 0 0 var(--space-sm);
	}
	h1 {
		font-size: var(--text-h3-size, 1.25rem);
		margin: 0 0 var(--space-sm);
	}
	.text {
		color: var(--color-text-secondary);
		margin: 0 0 var(--space-lg);
	}
</style>
