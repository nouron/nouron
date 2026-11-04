<script lang="ts">
	// Port of resources/views/lobby/index.blade.php (ADR 0006 stage 4f).
	import '#lib/styles/lobby.css';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { refreshShell } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';
	import ConfirmDialog from '#lib/components/ConfirmDialog.svelte';

	type RunRow = {
		id: number;
		status: string;
		colony_name: string;
		current_tick: number;
		tick_limit: number;
		supply_cap: number | null;
		bypass: Record<string, boolean>;
		started_at: string | null;
		ended_at: string | null;
	};
	type Score = {
		id: number;
		status: string;
		current_tick: number;
		tick_limit: number;
		ended_at: string | null;
		completed_objectives: number;
		total_objectives: number;
		score: number;
	};
	type Data = {
		active: RunRow[];
		pending: RunRow[];
		finished: RunRow[];
		finishedRuns: Score[];
		allowMultiple: boolean;
		resultRunId: number | null;
	};
	type Result = { ok: boolean; message?: string; errors?: Record<string, string[]> };

	let d = $state<Data | null>(null);
	let alert = $state<{ type: 'success' | 'error'; text: string } | null>(null);
	let renameError = $state<string | null>(null);
	let names = $state<Record<number, string>>({});
	let busy = $state(false);

	// jump: only right after abandoning a run — then its result is shown. A plain visit (or the result
	// page's "back to Mission Control") must stay on the lobby, otherwise it can never be reached.
	async function load(jump = false) {
		const r = await api<Data>('/lobby');
		if (jump && r.resultRunId) return goto(`/run/${r.resultRunId}`, { replaceState: true });
		d = r;
		names = Object.fromEntries(r.pending.map((p) => [p.id, p.colony_name]));
	}

	async function send(path: string, method = 'POST', body: object = {}): Promise<Result> {
		busy = true;
		try {
			return await api<Result>(path, { method, body, allowError: true });
		} catch {
			return { ok: false, message: t('colony.network_error') };
		} finally {
			busy = false;
		}
	}

	async function start() {
		const res = await send('/lobby/start');
		if (!res.ok) return void (alert = { type: 'error', text: res.message ?? '' });
		await refreshShell();
		goto('/colony');
	}

	// Themed confirm dialog state (replaces window.confirm).
	let pending = $state<{ title: string; message: string; label: string; run: () => Promise<void> } | null>(null);

	function abandon(id: number) {
		pending = {
			title: t('lobby.abandon_button'),
			message: t('lobby.abandon_confirm'),
			label: t('lobby.abandon_button'),
			run: () => doAbandon(id)
		};
	}

	async function doAbandon(id: number) {
		const res = await send(`/lobby/${id}/abandon`);
		alert = { type: res.ok ? 'success' : 'error', text: res.message ?? '' };
		await refreshShell();
		await load(true);
	}

	// No confirmation: the button only exists while no run is active or pending, so nothing is lost.
	async function newRun() {
		const res = await send('/run/new');
		alert = { type: res.ok ? 'success' : 'error', text: res.message ?? '' };
		await load();
	}

	async function rename(id: number, e: SubmitEvent) {
		e.preventDefault();
		renameError = null;
		const res = await send('/colony/name', 'PATCH', { name: names[id] });
		if (!res.ok) return void (renameError = res.errors?.name?.[0] ?? res.message ?? '');
		alert = { type: 'success', text: t('lobby.rename_success') };
	}

	const activeBypass = (r: RunRow) => Object.entries(r.bypass).filter(([, on]) => on);
	const pill = (status: string) => (status === 'completed' ? 'completed' : 'failed');
	const statusLabel = (status: string) => t(status === 'completed' ? 'lobby.status_completed' : 'lobby.status_failed');

	onMount(() => {
		load().catch((e) => {
			if (!(e instanceof ApiError && e.status === 401)) alert = { type: 'error', text: String(e.message ?? e) };
		});
	});
</script>

{#if alert}<p class="infra-alert infra-alert--{alert.type}" role="alert">{alert.text}</p>{/if}

{#if d}
	<div class="lobby">
		<hgroup class="lobby-page-header">
			<h1 class="lobby-page-title">{t('lobby.page_title')}</h1>
			<p class="lobby-page-subtitle">{t('lobby.page_subtitle')}</p>
		</hgroup>

		{#if d.active.length}
			<h2 class="lobby-section-heading">{t('lobby.active_runs')}</h2>
			<div class="lobby-run-grid">
				{#each d.active as run (run.id)}
					{@const shown = Math.min(run.current_tick, run.tick_limit)}
					<article class="lobby-run-card">
						<header><h3>{run.colony_name}</h3></header>
						<p class="lobby-sol-progress">{t('lobby.sol_progress', { current: shown, limit: run.tick_limit })}</p>
						<progress value={shown} max={run.tick_limit}></progress>
						{#if run.started_at}<p class="lobby-sol-progress">{t('lobby.started_at')}: {run.started_at}</p>{/if}
						<footer>
							<a href="/colony" class="btn-link">{t('lobby.continue_button')}</a>
							<button class="lobby-btn-danger-outline outline" disabled={busy} onclick={() => abandon(run.id)}>
								{t('lobby.abandon_button')}
							</button>
						</footer>
					</article>
				{/each}
			</div>
		{/if}

		{#if d.pending.length}
			<h2 class="lobby-section-heading">{t('lobby.pending_runs')}</h2>
			<div class="lobby-run-grid">
				{#each d.pending as run (run.id)}
					{@const bypass = activeBypass(run)}
					<article class="lobby-run-card">
						<header>
							<h3>{run.colony_name}</h3>
							{#if bypass.length}
								<span class="lobby-bypass-badge" title={t('lobby.bypass_warning')}>&#9888; {t('lobby.bypass_warning')}</span>
							{/if}
						</header>
						<p class="lobby-sol-progress">{t('lobby.colony_ready')}</p>
						<form class="lobby-rename-form" onsubmit={(e) => rename(run.id, e)}>
							<input type="text" bind:value={names[run.id]} minlength="2" maxlength="50" required aria-label={t('lobby.rename_label')} />
							<button type="submit" class="outline" disabled={busy}>{t('lobby.rename_button')}</button>
						</form>
						{#if renameError}<p class="lobby-rename-error">{renameError}</p>{/if}
						<ul class="lobby-meta">
							<li>{t('lobby.tick_limit')}: <strong>{run.tick_limit}</strong></li>
							{#if run.supply_cap !== null}<li>{t('lobby.supply_cap')}: <strong>{run.supply_cap}</strong></li>{/if}
							{#each bypass as [key] (key)}
								<li><span class="lobby-bypass-badge">{t('lobby.bypass_active')}: {key}</span></li>
							{/each}
						</ul>
						<footer>
							<button class="primary" disabled={busy} onclick={start}>{t('lobby.start_button')}</button>
						</footer>
					</article>
				{/each}
			</div>
		{/if}

		<!-- Whenever no run is pending or active: the way to start the next mission (the result page links here). -->
		{#if !d.pending.length && !d.active.length}
			<div class="lobby-new-run-row">
				<button class="primary" disabled={busy} onclick={newRun}>{t('lobby.new_run_button')}</button>
			</div>
		{/if}

		<h2 class="lobby-section-heading lobby-highscore-heading">{t('lobby.highscore_title')}</h2>
		{#if d.finishedRuns.length}
			<div class="lobby-highscore-scroll">
				<table class="lobby-highscore-table">
					<thead>
						<tr>
							<th>{t('lobby.highscore_col_mission')}</th>
							<th>{t('lobby.highscore_col_status')}</th>
							<th>{t('lobby.highscore_col_sol')}</th>
							<th>{t('lobby.highscore_col_tasks')}</th>
							<th class="lobby-highscore-score">{t('lobby.highscore_col_score')}</th>
						</tr>
					</thead>
					<tbody>
						{#each d.finishedRuns as e (e.id)}
							<tr>
								<td>
									{t('lobby.run_number', { id: e.id })}
									{#if e.ended_at}<br /><small class="lobby-hs-meta">{t('lobby.highscore_ended', { date: e.ended_at })}</small>{/if}
								</td>
								<td><span class="lobby-status-pill lobby-status-pill--{pill(e.status)}">{statusLabel(e.status)}</span></td>
								<td>{e.current_tick} / {e.tick_limit}</td>
								<td>{e.completed_objectives}{e.total_objectives > 0 ? ` / ${e.total_objectives}` : ''}</td>
								<td class="lobby-highscore-score"><strong>{e.score.toLocaleString('en-US')}</strong></td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		{:else}
			<p class="lobby-hs-empty">{t('lobby.highscore_no_runs')}</p>
		{/if}

		{#if !d.active.length && !d.pending.length && !d.finished.length}
			<div class="lobby-empty"><p>{t('lobby.no_runs')}</p></div>
		{/if}
	</div>
{/if}

{#if pending}
	{@const p = pending}
	<ConfirmDialog
		title={p.title}
		message={p.message}
		confirmLabel={p.label}
		oncancel={() => (pending = null)}
		onconfirm={() => {
			const run = p.run;
			pending = null;
			run();
		}}
	/>
{/if}

<style>
	/* PicoCSS drew <button>/<a role=button> in Blade; here a small token-based equivalent. */
	button,
	.btn-link {
		display: inline-block;
		padding: 0.55rem 1rem;
		font: inherit;
		font-weight: 600;
		font-size: var(--text-button-size, 0.9rem);
		border-radius: var(--radius-sm);
		cursor: pointer;
		text-decoration: none;
		border: 1px solid var(--color-accent);
	}
	.primary,
	.btn-link {
		background: var(--color-accent);
		color: #fff;
	}
	.primary:hover:not(:disabled),
	.btn-link:hover {
		background: var(--color-accent-hover);
	}
	.outline {
		background: transparent;
		color: var(--color-accent);
	}
	button:disabled {
		opacity: 0.55;
		cursor: not-allowed;
	}
	.lobby-rename-form input {
		margin-top: 0;
	}
	.lobby-run-card footer {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: var(--space-sm);
	}
</style>
