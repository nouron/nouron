<script lang="ts">
	// Port of resources/views/run/result.blade.php (ADR 0006 stage 4f): outcome, score, objectives.
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';

	type Result = {
		status: 'completed' | 'failed';
		fail_reason: string | null;
		score: number;
		current_tick: number;
		tick_limit: number;
		objectives: { label: string; current: number; target: number; pct: number; done: boolean }[];
	};

	const FAIL_BODY: Record<string, string> = {
		trust_collapse: 'run.result_fail_trust',
		phase1_deadline: 'run.result_fail_phase1_deadline',
		nexus_debt: 'run.result_fail_nexus_debt',
		abandoned: 'run.result_fail_abandoned'
	};

	let r = $state<Result | null>(null);
	let error = $state('');
	const won = $derived(r?.status === 'completed');
	const body = $derived(
		won
			? t('run.run_completed')
			: t(FAIL_BODY[r?.fail_reason ?? ''] ?? 'run.result_fail_time')
	);

	onMount(() => {
		api<Result>(`/run/${page.params.id}/result`)
			.then((d) => (r = d))
			.catch((e) => {
				// Still running: the result does not exist yet (Blade redirected to the colony).
				if (e instanceof ApiError && e.status === 409) goto('/colony', { replaceState: true });
				else if (!(e instanceof ApiError && e.status === 401)) error = String(e.message ?? e);
			});
	});
</script>

{#if error}<p class="infra-alert infra-alert--error" role="alert">{error}</p>{/if}

{#if r}
	<div class="run-result">
		<div class="run-result__status {won ? 'run-result__status--win' : 'run-result__status--lose'}">
			<i class="bi run-result__status-icon {won ? 'bi-trophy-fill' : 'bi-x-octagon-fill'}"></i>
			<div>
				<h1 class="run-result__status-title">{won ? t('run.result_title_completed') : t('run.result_title_failed')}</h1>
				<p class="run-result__status-body">{body}</p>
			</div>
		</div>

		<div class="run-result__score-wrap">
			<p class="run-result__score-label">{t('run.result_score_label')}</p>
			<p class="run-result__score-value">{r.score.toLocaleString('en-US')}</p>
			{#if won}
				<p class="run-result__score-sub">{t('run.result_ticks_label', { current: r.current_tick, limit: r.tick_limit })}</p>
			{/if}
		</div>

		<div class="run-result__objectives">
			<p class="run-result__obj-title">{t('run.phase1_complete')}</p>
			<ul class="run-result__obj-list">
				{#each r.objectives as o, i (i)}
					<li class="run-result__obj-item">
						<span class="run-result__obj-label">{o.label}</span>
						<div class="run-result__obj-bar">
							<progress class="run-result__obj-progress" value={o.pct} max="100"></progress>
							<span class="run-result__obj-count">{o.current} / {o.target}</span>
						</div>
						<span class="run-result__obj-badge {o.done ? 'run-result__obj-badge--done' : 'run-result__obj-badge--open'}">
							{#if o.done}<i class="bi bi-check-lg"></i>{/if}
							{o.done ? t('run.result_objective_fulfilled') : t('run.result_objective_open')}
						</span>
					</li>
				{:else}
					<li class="none">—</li>
				{/each}
			</ul>
		</div>

		<div class="run-result__actions">
			<a class="primary" href="/lobby"><i class="bi bi-arrow-left"></i> {t('run.result_btn_new_run')}</a>
		</div>
	</div>
{/if}

<style>
	.none {
		text-align: center;
		padding: 1rem;
		color: var(--color-text-secondary);
	}
	/* PicoCSS drew these buttons in Blade; token-based equivalent. */
	.primary {
		display: inline-flex;
		align-items: center;
		gap: 0.4rem;
		padding: 0.6rem 1.1rem;
		font: inherit;
		font-weight: 600;
		border-radius: var(--radius-sm);
		border: 1px solid var(--color-accent);
		cursor: pointer;
		text-decoration: none;
	}
	.primary {
		background: var(--color-accent);
		color: #fff;
	}
</style>
