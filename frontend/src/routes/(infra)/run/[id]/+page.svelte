<script lang="ts">
	// Port of resources/views/run/result.blade.php (ADR 0006 stage 4f): outcome, score, objectives.
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { refreshShell } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';

	type Result = {
		status: 'completed' | 'failed';
		fail_reason: string | null;
		score: number;
		current_tick: number;
		tick_limit: number;
		objectives: { label: string; current: number; target: number; pct: number; done: boolean }[];
	};

	let r = $state<Result | null>(null);
	let error = $state('');
	let busy = $state(false);
	const won = $derived(r?.status === 'completed');
	const body = $derived(
		won
			? t('run.run_completed')
			: r?.fail_reason === 'trust_collapse'
				? t('run.result_fail_trust')
				: r?.fail_reason === 'phase1_deadline'
					? t('run.result_fail_phase1_deadline')
					: t('run.result_fail_time')
	);

	async function newRun() {
		busy = true;
		const res = await api<{ ok: boolean; message?: string }>('/run/new', { method: 'POST', allowError: true }).catch(() => ({
			ok: false,
			message: t('colony.network_error')
		}));
		busy = false;
		if (!res.ok) return void (error = res.message ?? '');
		await refreshShell();
		goto('/lobby');
	}

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
			<button class="primary" disabled={busy} onclick={newRun}><i class="bi bi-arrow-repeat"></i> {t('run.result_btn_new_run')}</button>
			{#if won}
				<a href="/colony" class="outline"><i class="bi bi-hexagon"></i> {t('run.result_btn_colony')}</a>
			{:else}
				<button class="outline" disabled title={t('run.result_btn_colony_disabled')}><i class="bi bi-hexagon"></i> {t('run.result_btn_colony')}</button>
			{/if}
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
	.primary,
	.outline {
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
	.outline {
		background: transparent;
		color: var(--color-accent);
	}
	button:disabled {
		opacity: 0.55;
		cursor: not-allowed;
	}
</style>
