<script lang="ts">
	// Port of partials/encounter-notice-bar + partials/hint-bar.
	import '#lib/styles/hints.css';
	import { t } from '#lib/i18n.ts';
	import { appLink } from '#lib/links.ts';
	import { dismissHint, type Shell } from '#lib/stores/shell.ts';

	let { s }: { s: Shell } = $props();

	type Hint = NonNullable<Shell['active_hint']>;
	let completed = $state<Hint | null>(null);
	let prev: Hint | null = null;
	let timer: ReturnType<typeof setTimeout>;

	// When the active hint changes, show the old one briefly as "done" (✓) while the new one slides in.
	$effect(() => {
		const hint = s.active_hint ?? null;
		if ((hint?.key ?? null) !== (prev?.key ?? null) && prev) {
			completed = prev;
			clearTimeout(timer);
			timer = setTimeout(() => (completed = null), 1000);
		}
		prev = hint;
	});
</script>

{#each s.active_encounter_notices ?? [] as notice, i (i)}
	<div class="encounter-notice-bar">
		<span class="encounter-notice-bar__icon" aria-hidden="true">⚠</span>
		<span class="encounter-notice-bar__text">{notice.text}</span>
	</div>
{/each}

<div class="hint-bar-stack">
	{#if completed}
		<div class="hint-bar hint-bar--done">
			<span class="hint-bar__icon hint-bar__icon--done" aria-hidden="true">✓</span>
			<span class="hint-bar__text">{completed.text}</span>
		</div>
	{/if}
	{#if s.active_hint}
		{#key s.active_hint.key}
			<div class="hint-bar hint-bar--enter">
				<span class="hint-bar__icon" aria-hidden="true" title={t('colony.hint_not_mandatory')}>!</span>
				<span class="hint-bar__text">{s.active_hint.text}</span>
				<span class="hint-bar__badge" title={t('colony.hint_not_mandatory')}>{t('colony.hint_suggestion_label')}</span>
				<a class="hint-bar__link" href={appLink(s.active_hint.target_url)}>→</a>
				<button
					class="hint-bar__dismiss"
					aria-label={t('techtree.detail_close')}
					onclick={() => s.active_hint && dismissHint(s.active_hint.key)}>×</button
				>
			</div>
		{/key}
	{/if}
</div>
