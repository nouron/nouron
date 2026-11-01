<script lang="ts">
	// Port of resources/views/comm_log/index.blade.php (ADR 0006 stage 4e): Protokoll and Nexus-Funk tabs.
	import '../../../../../public/css/comm_log.css';
	import { page } from '$app/state';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import { refreshShell } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';
	import EntityChip from '#lib/components/EntityChip.svelte';

	type Segment =
		| { type: 'text'; value: string }
		| { type: 'amount'; abbr: string; value: string | number; variant: 'ap' | 'res' }
		| { type: string; key: string; label: string; tooltip?: Record<string, string> };
	type Entry = {
		id: number;
		sol: number;
		event: string;
		segments?: Segment[];
		_collapsed?: number;
		icon?: string;
		text?: string;
		title?: string;
		body?: string | null;
		badge?: string | null;
		badge_class?: string;
	};

	const tab = $derived(page.url.searchParams.get('tab') === 'nexus' ? 'nexus' : 'log');
	let entries = $state<Entry[]>([]);
	let unread = $state(0);
	let loaded = $state(false);
	let loadError = $state('');

	// The layout remounts this page on a query change, so a plain load per mount is enough.
	$effect(() => {
		api<{ entries: Entry[]; unreadCount: number }>(`/comm-log?tab=${tab}`)
			.then((d) => {
				entries = d.entries;
				unread = d.unreadCount;
				loaded = true;
				// Opening Nexus-Funk marks it read: the nav badge follows.
				if (tab === 'nexus') refreshShell();
			})
			.catch((e) => {
				if (!(e instanceof ApiError && e.status === 401)) loadError = String(e.message ?? e);
			});
	});

	// .res-{X} colour classes use mixed case (Cr/Rg/Co/Or/Sup), decoupled from the shown abbreviation.
	const COLOR: Record<string, string> = { CR: 'Cr', RG: 'Rg', CO: 'Co', OR: 'Or', KOL: 'Sup' };
	const showSol = (i: number) => i === 0 || entries[i - 1].sol !== entries[i].sol;
</script>

{#if loadError}<p class="error load-error" role="alert">{loadError}</p>{/if}

<div class="comm-log-page">
	<nav class="comm-tabs" aria-label={t('comm_log.page_title')}>
		<a href="/comm-log" class="comm-tab" class:comm-tab-active={tab === 'log'}><i class="bi bi-journal-text"></i> {t('comm_log.tab_log')}</a>
		<a href="/comm-log?tab=nexus" class="comm-tab" class:comm-tab-active={tab === 'nexus'}>
			<i class="bi bi-broadcast-pin"></i>
			{t('comm_log.tab_nexus')}
			{#if unread > 0}<span class="comm-unread-badge">{unread}</span>{/if}
		</a>
	</nav>

	{#if loaded && entries.length === 0}
		<p class="comm-empty">{tab === 'nexus' ? t('comm_log.empty_nexus') : t('comm_log.empty_log')}</p>
	{/if}

	{#each entries as e, i (e.id)}
		{#if showSol(i)}
			<div class="comm-sol-divider" aria-hidden="true">{t('comm_log.sol_label', { sol: e.sol })}</div>
		{/if}
		{#if tab === 'nexus'}
			<article class="comm-nexus-card">
				<div class="comm-nexus-card-header">
					<span class="comm-nexus-card-title"><i class="bi bi-broadcast-pin"></i> {e.title}</span>
					{#if e.badge}<span class="comm-nexus-badge {e.badge_class}">{e.badge}</span>{/if}
				</div>
				{#if e.body}<p class="comm-nexus-card-body">{e.body}</p>{/if}
				<div class="comm-nexus-card-footer">
					<span class="comm-entry-sol">{t('comm_log.sol_label', { sol: e.sol })}</span>
				</div>
			</article>
		{:else}
			{@const segs = e.segments ?? []}
			<div class="comm-entry">
				<span class="comm-entry-icon" aria-hidden="true"><i class="bi {e.icon}"></i></span>
				<span class="comm-entry-label" class:comm-entry-label--has-chips={segs.some((s) => s.type !== 'text')}>
					{#if segs.length}
						{#each segs as s, si (si)}
							{#if s.type === 'text' && 'value' in s}
								{s.value}
							{:else if s.type === 'amount' && 'abbr' in s}
								<span class={s.variant === 'ap' ? 'ap-chip ap-chip--neutral' : `res-chip res-${COLOR[s.abbr.toUpperCase()] ?? s.abbr}`}>
									<span class="res-abbr">{s.abbr}</span>
									<span class="res-amount">{s.value}</span>
								</span>
							{:else if 'key' in s}
								<EntityChip type={s.type} entityKey={s.key} label={s.label} tooltip={s.tooltip ?? {}} />
							{/if}
						{/each}
					{:else}
						{e.text}
					{/if}
				</span>
				{#if (e._collapsed ?? 0) > 1}<span class="comm-entry-collapsed">×{e._collapsed}</span>{/if}
			</div>
		{/if}
	{/each}
</div>

<style>
	.load-error {
		padding: var(--space-md);
	}
</style>
