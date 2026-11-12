<script lang="ts" module>
	export type EffectLine = { text?: string; chip?: { cls: string; abbr: string; value: string | number } | null };
	/** knowledge_result payload of a cantina bonus (silent research, spec 2026-11-11-tomas-rat-rueckmeldung). */
	export type KnowledgeResult = {
		accepted?: boolean;
		knowledge_id: number;
		knowledge_name: string | null;
		ap_invested: number;
		level: number;
		leveled_up: boolean;
		effects: EffectLine[];
		ap_forfeited: number;
		block_reason: string | null;
		block_label: string | null;
		has_lab: boolean;
		ap_spend: number | null;
		ap_for_levelup: number | null;
		rough_progress: number | null;
		rough_progress_label: string | null;
	};
</script>

<script lang="ts">
	// Result line after an earmarked knowledge bonus — shared by Tomas, Sarka, Deva and Lenn.
	// With a lab: exact AP and bar; without: a rough word. A level-up and forfeited AP are always explicit.
	import { t } from '#lib/i18n.ts';

	let { result, source }: { result: KnowledgeResult; source: string } = $props();

	const name = $derived(result.knowledge_name ?? '?');
	const exact = $derived(result.has_lab && result.ap_for_levelup !== null && result.ap_for_levelup > 0);
	// After a breakthrough, progress is only worth a line if AP carried into the next level.
	const showProgress = $derived(
		!result.leveled_up || (exact ? (result.ap_spend ?? 0) > 0 : result.rough_progress !== null)
	);
</script>

<div class="knowledge-result" data-knowledge-result>
	{#if result.leveled_up}
		<p class="knowledge-result__breakthrough">
			<i class="bi bi-stars" aria-hidden="true"></i>
			<strong>{t('colony.knowledge_result_breakthrough', { knowledge: name, level: result.level })}</strong>
		</p>
		{#if result.effects?.length}
			<p class="knowledge-result__effects">
				{#each result.effects as line, i (i)}
					<span class="knowledge-result__effect">
						{line.text}
						{#if line.chip}
							<span class="res-chip res-{line.chip.cls}"
								><span class="res-abbr">{line.chip.abbr}</span><span class="res-amount">{line.chip.value}</span></span
							>
						{/if}
					</span>
				{/each}
			</p>
		{/if}
	{/if}

	{#if showProgress}
		{#if exact}
			<div class="knowledge-result__progress">
				<span>{t('colony.knowledge_result_lab', { ap: result.ap_invested, knowledge: name, name: source })}</span>
				<span class="knowledge-result__numbers"
					>{t('colony.knowledge_result_lab_progress', { spent: result.ap_spend ?? 0, needed: result.ap_for_levelup ?? 0 })}</span
				>
				<span class="knowledge-result__bar" aria-hidden="true"
					><span style="width: {Math.min(100, ((result.ap_spend ?? 0) / (result.ap_for_levelup || 1)) * 100)}%"></span></span
				>
			</div>
		{:else if result.rough_progress_label}
			<p>{t('colony.knowledge_result_rough', { knowledge: name, progress: result.rough_progress_label, name: source })}</p>
		{:else}
			<p>{t('colony.knowledge_result_plain', { knowledge: name, name: source })}</p>
		{/if}
	{/if}

	{#if result.ap_forfeited > 0}
		<p class="knowledge-result__forfeited">
			{t('colony.knowledge_result_forfeited', { ap: result.ap_forfeited, reason: result.block_label ?? '?' })}
		</p>
	{/if}
</div>

<style>
	.knowledge-result {
		display: flex;
		flex-direction: column;
		gap: var(--space-xs);
		padding: var(--space-sm) var(--space-md);
		border-left: 3px solid var(--color-accent);
		background: var(--color-accent-wash);
		font-size: 0.9rem;
		margin: 0.5rem 0;
	}
	.knowledge-result p {
		margin: 0;
	}
	.knowledge-result__breakthrough {
		color: var(--color-accent);
		font-size: 1rem;
	}
	.knowledge-result__effects {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		color: var(--color-text-secondary);
	}
	.knowledge-result__effect {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
	}
	.knowledge-result__progress {
		display: grid;
		grid-template-columns: 1fr auto;
		gap: 0.25rem 0.75rem;
		align-items: center;
	}
	.knowledge-result__numbers {
		font-variant-numeric: tabular-nums;
		color: var(--color-text-secondary);
	}
	.knowledge-result__bar {
		grid-column: 1 / -1;
		display: block;
		height: 6px;
		border-radius: 999px;
		background: var(--color-border);
		overflow: hidden;
	}
	.knowledge-result__bar > span {
		display: block;
		height: 100%;
		background: var(--color-accent);
	}
	.knowledge-result__forfeited {
		color: var(--color-text-secondary);
		font-size: 0.85rem;
	}
</style>
