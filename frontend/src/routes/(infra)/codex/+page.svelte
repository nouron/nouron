<script lang="ts">
	// Port of resources/views/user/codex.blade.php (ADR 0006 stage 4e): what the player learned about each figure.
	import { onMount } from 'svelte';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';

	type Character = { slug: string; name: string | null; role: string | null; unlocked: number[]; max_entries: number };
	let characters = $state<Character[]>([]);
	let loadError = $state('');

	onMount(() => {
		api<{ characters: Character[] }>('/codex')
			.then((d) => (characters = d.characters))
			.catch((e) => {
				if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});
	});
</script>

{#if loadError}<p class="error" role="alert">{loadError}</p>{/if}

<div class="codex">
	<h2><i class="bi bi-journal-bookmark"></i> {t('colony.codex_heading')}</h2>
	<p class="muted">{t('colony.codex_page_intro')}</p>

	{#each characters as c (c.slug)}
		<details>
			<summary>
				<strong>{c.name ?? t('colony.codex_locked_entry')}</strong>
				{#if c.role}<small class="muted"> — {c.role}</small>{/if}
				<span class="count">{c.unlocked.length} / {c.max_entries}</span>
			</summary>
			{#if c.unlocked.length === 0}
				<p class="muted">{t('colony.codex_no_entries_yet')}</p>
			{:else}
				<ul>
					{#each Array.from({ length: c.max_entries }, (_, i) => i + 1) as n (n)}
						{@const open = c.unlocked.includes(n)}
						<li class:muted={!open}>{open ? t(`colony.codex_entry_${c.slug}_${n}`) : t('colony.codex_locked_entry')}</li>
					{/each}
				</ul>
			{/if}
		</details>
	{/each}
</div>

<style>
	/* Was inline style + PicoCSS <details> in Blade. */
	.codex {
		max-width: 48rem;
	}
	.muted {
		color: var(--color-text-secondary);
	}
	details {
		border-bottom: 1px solid var(--color-border);
		padding: 0.75rem 0;
	}
	summary {
		cursor: pointer;
	}
	.count {
		float: inline-end;
		font-size: 0.85rem;
		color: var(--color-text-secondary);
	}
	ul {
		margin: 0.5rem 0 0;
	}
</style>
