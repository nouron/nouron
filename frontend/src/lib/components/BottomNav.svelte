<script lang="ts">
	// Bottom navigation (UI rework 1B): five tiles, desktop and mobile alike.
	// Groups with a sub-navigation (Colony, Data archive) open a tile row directly above the bar.
	import { tick } from 'svelte';
	import { page } from '$app/state';
	import { t } from '#lib/i18n.ts';
	import type { Shell } from '#lib/stores/shell.ts';

	let { s }: { s: Shell } = $props();

	type Item = { key: string; icon: string; label: string; href: string; locked?: string; badge?: number };
	type Group = Item & { children?: Item[] };

	const groups = $derived<Group[]>([
		{
			key: 'colony',
			icon: 'bi-hexagon',
			label: t('colony.nav_group_colony'),
			href: '',
			children: [
				{ key: 'cc', icon: 'bi-diagram-2', label: t('command_center.nav_label'), href: '/command-center' },
				{ key: 'map', icon: 'bi-map', label: t('colony.nav_map'), href: '/colony' },
				{ key: 'advisors', icon: 'bi-people', label: t('colony.nav_advisors'), href: '/advisors' }
			]
		},
		{ key: 'techtree', icon: 'bi-diagram-3', label: t('colony.nav_techtree'), href: '/techtree' },
		{
			key: 'cantina',
			icon: 'bi-cup-hot',
			label: t('colony.nav_cantina'),
			href: '/cantina',
			locked: s.bar_built ? undefined : t('colony.nav_cantina_locked')
		},
		{
			key: 'hangar',
			icon: 'bi-rocket',
			label: t('colony.nav_hangar'),
			href: '/hangar',
			locked: s.hangar_built ? undefined : t('colony.nav_hangar_locked')
		},
		{
			key: 'archive',
			icon: 'bi-archive',
			label: t('colony.nav_group_archive'),
			href: '',
			badge: s.unread_nexus_count,
			children: [
				{
					key: 'log',
					icon: 'bi-journal-text',
					label: t('colony.nav_log'),
					href: '/comm-log',
					badge: s.unread_nexus_count
				},
				{ key: 'nexusdb', icon: 'bi-database', label: t('colony.nav_nexusdb'), href: '/nexus-db' },
				{ key: 'codex', icon: 'bi-journal-bookmark', label: t('colony.nav_codex'), href: '/codex' }
			]
		}
	]);

	let openKey = $state<string | null>(null);
	let root = $state<HTMLElement>();

	const here = (href: string) => href !== '' && page.url.pathname.startsWith(href);
	const groupActive = (g: Group) => here(g.href) || !!g.children?.some((c) => here(c.href));

	async function toggle(g: Group) {
		if (openKey === g.key) {
			openKey = null;
			return;
		}
		openKey = g.key;
		await tick();
		root?.querySelector<HTMLElement>(`#subnav-${g.key} a, #subnav-${g.key} button`)?.focus();
	}

	function close(returnFocus: boolean) {
		const key = openKey;
		openKey = null;
		if (returnFocus && key) root?.querySelector<HTMLElement>(`[data-trigger="${key}"]`)?.focus();
	}

	// Close the sub-navigation after every route change.
	$effect(() => {
		void page.url.pathname;
		openKey = null;
	});
</script>

<svelte:window
	onclick={(e) => {
		if (openKey && root && !root.contains(e.target as Node)) close(false);
	}}
	onkeydown={(e) => {
		if (e.key === 'Escape' && openKey) {
			e.preventDefault();
			close(true);
		}
	}}
/>

{#snippet tile(item: Item, active: boolean)}
	{#if item.locked}
		<button
			type="button"
			class="tile locked"
			aria-disabled="true"
			aria-describedby="why-{item.key}"
			title={item.locked}
		>
			<i class="bi {item.icon}" aria-hidden="true"></i><span class="label">{item.label}</span>
		</button>
		<span id="why-{item.key}" class="sr-only">{item.locked}</span>
	{:else}
		<a href={item.href} class="tile" class:active aria-current={active ? 'page' : undefined}>
			<i class="bi {item.icon}" aria-hidden="true"></i><span class="label">{item.label}</span>
			{#if item.badge}<span class="badge">{item.badge}</span>{/if}
		</a>
	{/if}
{/snippet}

<nav class="bottom-nav" aria-label={t('colony.nav_main_aria')} bind:this={root}>
	{#each groups as g (g.key)}
		{@const active = groupActive(g)}
		<div class="slot">
			{#if g.children}
				<button
					type="button"
					class="tile"
					class:active
					data-trigger={g.key}
					aria-current={active ? 'true' : undefined}
					aria-expanded={openKey === g.key}
					aria-controls="subnav-{g.key}"
					onclick={() => toggle(g)}
				>
					<i class="bi {g.icon}" aria-hidden="true"></i><span class="label"
						>{g.label}<i class="bi bi-caret-up-fill caret" class:flipped={openKey === g.key} aria-hidden="true"></i></span
					>
					{#if g.badge}<span class="badge">{g.badge}</span>{/if}
				</button>
				{#if openKey === g.key}
					<ul class="subnav" id="subnav-{g.key}" aria-label={g.label}>
						{#each g.children as c (c.key)}
							<li>{@render tile(c, here(c.href))}</li>
						{/each}
					</ul>
				{/if}
			{:else}
				{@render tile(g, here(g.href))}
			{/if}
		</div>
	{/each}
</nav>

<style>
	.bottom-nav {
		position: relative;
		display: flex;
		justify-content: center;
		gap: var(--space-xs);
		min-height: 64px;
		padding: 4px var(--space-sm) calc(4px + env(safe-area-inset-bottom));
		background: var(--color-navbar-bg);
		border-top: 1px solid var(--color-navbar-border);
		box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.04);
	}
	.slot {
		flex: 1 1 0;
		max-width: 9rem;
		min-width: 0;
		display: flex;
	}
	.tile {
		position: relative;
		flex: 1;
		min-width: 44px;
		min-height: 56px;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 2px;
		padding: 2px 4px;
		font: inherit;
		font-size: 0.72rem;
		line-height: 1.2;
		color: #4a4a58;
		white-space: nowrap;
		text-decoration: none;
		background: none;
		border: none;
		border-radius: 6px;
		cursor: pointer;
	}
	.tile .bi:first-child {
		font-size: 1.25rem;
	}
	.tile:hover {
		color: var(--color-text-primary);
		background: rgba(0, 0, 0, 0.04);
	}
	.tile.active {
		color: var(--color-accent);
		font-weight: 600;
		background: var(--color-accent-tint);
	}
	.tile.locked {
		opacity: 0.45;
		cursor: not-allowed;
	}
	.tile.locked:hover {
		background: none;
		color: #4a4a58;
	}
	.label {
		display: inline-flex;
		align-items: center;
		height: 1.2em;
	}
	.caret {
		line-height: 1;
		margin-left: 0.25em;
		font-size: 0.45em;
		opacity: 0.4;
		transition: transform var(--duration-fast);
	}
	.caret.flipped {
		transform: rotate(180deg);
	}
	.badge {
		position: absolute;
		top: 2px;
		right: calc(50% - 1.6em);
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 1.1rem;
		height: 1.1rem;
		padding: 0 0.25em;
		border-radius: var(--radius-round);
		background: var(--color-danger);
		color: #fff;
		font-size: 0.65rem;
		font-weight: 700;
		line-height: 1;
	}
	.subnav {
		position: absolute;
		bottom: 100%;
		left: 0;
		right: 0;
		display: flex;
		justify-content: center;
		gap: var(--space-xs);
		margin: 0;
		padding: 4px var(--space-sm);
		list-style: none;
		background: var(--color-navbar-bg);
		border-top: 1px solid var(--color-navbar-border);
		box-shadow: var(--shadow-flyout);
		animation: ds-slide-fade-in var(--duration-fast) ease-out;
	}
	.subnav li {
		flex: 1 1 0;
		max-width: 10rem;
		min-width: 0;
		display: flex;
	}
	.sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		overflow: hidden;
		clip-path: inset(50%);
		white-space: nowrap;
	}
</style>
