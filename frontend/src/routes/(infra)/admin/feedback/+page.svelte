<script lang="ts">
	// Port of resources/views/admin/feedback.blade.php (ADR 0006 stage 5c): tester feedback, newest first.
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';

	type Entry = { id: number; created_at: string; username: string; category: string; run_id: number | null; sol: number | null; page: string | null; message: string };
	type Page = { data: Entry[]; current_page: number; last_page: number };

	let p = $state<Page | null>(null);
	let error = $state('');
	let current = $state(1);

	$effect(() => {
		api<Page>(`/admin/feedback?page=${current}`).then(
			(r) => (p = r),
			(e) => (error = e instanceof ApiError ? e.message : String(e))
		);
	});
</script>

<svelte:head><title>{t('feedback.admin_title')} – Nouron</title></svelte:head>

<h1>{t('feedback.admin_title')}</h1>
{#if error}<p class="infra-alert infra-alert--error" role="alert">{error}</p>{/if}

{#if p}
	{#if !p.data.length}
		<p>{t('feedback.admin_empty')}</p>
	{:else}
		<div class="scroll">
			<table>
				<thead>
					<tr>
						<th>{t('feedback.admin_col_date')}</th>
						<th>{t('feedback.admin_col_user')}</th>
						<th>{t('feedback.admin_col_category')}</th>
						<th>{t('feedback.admin_col_context')}</th>
						<th>{t('feedback.admin_col_message')}</th>
					</tr>
				</thead>
				<tbody>
					{#each p.data as e (e.id)}
						<tr>
							<td>{e.created_at}</td>
							<td>{e.username}</td>
							<td>{e.category}</td>
							<td>{e.run_id ? `#${e.run_id}` : '–'} / {e.sol ?? '–'} / <code>{e.page ?? '–'}</code></td>
							<td class="msg">{e.message}</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
		{#if p.last_page > 1}
			<nav class="pager">
				<button disabled={current <= 1} onclick={() => current--}>‹</button>
				<span>{p.current_page} / {p.last_page}</span>
				<button disabled={current >= p.last_page} onclick={() => current++}>›</button>
			</nav>
		{/if}
	{/if}
{/if}

<style>
	.scroll {
		overflow-x: auto;
	}
	table {
		width: 100%;
		border-collapse: collapse;
		font-size: 0.9rem;
	}
	th,
	td {
		text-align: left;
		vertical-align: top;
		padding: var(--space-xs) var(--space-sm);
		border-bottom: 1px solid var(--color-border);
	}
	tbody tr:nth-child(odd) {
		background: var(--color-surface);
	}
	td:first-child {
		white-space: nowrap;
	}
	.msg {
		white-space: pre-wrap;
	}
	.pager {
		display: flex;
		gap: var(--space-sm);
		align-items: center;
		justify-content: center;
		margin-top: var(--space-md);
	}
	.pager button {
		font: inherit;
		padding: 0.2rem 0.7rem;
		border: 1px solid var(--color-border-strong);
		background: var(--color-bg);
		cursor: pointer;
	}
	.pager button:disabled {
		opacity: 0.4;
		cursor: not-allowed;
	}
</style>
