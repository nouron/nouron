<script lang="ts">
	// Port of the header in resources/views/layouts/colony.blade.php (nav, user menu, resource bar).
	import { page } from '$app/state';
	import { t } from '#lib/i18n.ts';
	import { BACKEND_URL, logout } from '#lib/stores/auth.ts';
	import type { Shell } from '#lib/stores/shell.ts';
	import HintBar from './HintBar.svelte';
	import ResourceBar from './ResourceBar.svelte';
	import SolButton from './SolButton.svelte';

	let { s }: { s: Shell } = $props();

	let menuOpen = $state(false);
	let userOpen = $state(false);

	// `href` starting with "/" = migrated Svelte screen; anything else still lives in Blade (ADR 0006 coexistence).
	type NavItem = { key: string; icon: string; label: string; href: string; locked?: string; badge?: number };
	const nav = $derived<NavItem[]>([
		{ key: 'colony', icon: 'bi-hexagon', label: t('colony.nav_colony'), href: '/colony' },
		{
			key: 'cc',
			icon: 'bi-diagram-2',
			label: t('command_center.nav_label'),
			href: `${BACKEND_URL}/colony/command-center`
		},
		{ key: 'advisors', icon: 'bi-people', label: t('colony.nav_advisors'), href: `${BACKEND_URL}/advisors` },
		{
			key: 'techtree',
			icon: 'bi-diagram-3',
			label: t('colony.nav_techtree'),
			href: '/techtree',
			locked: s.sciencelab_built ? undefined : t('colony.nav_techtree_locked')
		},
		{
			key: 'cantina',
			icon: 'bi-cup-hot',
			label: t('colony.nav_cantina'),
			href: `${BACKEND_URL}/colony/bar`,
			locked: s.bar_built ? undefined : t('colony.nav_cantina_locked')
		},
		{
			key: 'hangar',
			icon: 'bi-rocket',
			label: t('colony.nav_hangar'),
			href: `${BACKEND_URL}/colony/hangar`,
			locked: s.hangar_built ? undefined : t('colony.nav_hangar_locked')
		},
		{
			key: 'log',
			icon: 'bi-journal-text',
			label: t('colony.nav_log'),
			href: `${BACKEND_URL}/comm-log`,
			badge: s.unread_nexus_count
		},
		{ key: 'nexusdb', icon: 'bi-database', label: t('colony.nav_nexusdb'), href: `${BACKEND_URL}/nexus-db` }
	]);

	const userNav = $derived([
		{ icon: 'bi-list-check', label: t('lobby.nav_runs'), href: `${BACKEND_URL}/lobby` },
		{ icon: 'bi-person', label: t('colony.user_nav_profile'), href: `${BACKEND_URL}/user` },
		{ icon: 'bi-gear', label: t('colony.user_nav_settings'), href: `${BACKEND_URL}/user/settings` },
		{ icon: 'bi-journal-bookmark', label: t('colony.user_nav_codex'), href: `${BACKEND_URL}/user/codex` }
	]);

	const isActive = (item: NavItem) => item.href.startsWith('/') && page.url.pathname.startsWith(item.href);
</script>

<svelte:window
	onclick={() => {
		menuOpen = false;
		userOpen = false;
	}}
/>

<header class="app-header">
	<nav>
		<a href="/" class="logo">Nouron</a>

		<ul class="nav-desktop">
			{#each nav as item (item.key)}
				<li>
					{#if item.locked}
						<span class="nav-link locked" title={item.locked}
							><i class="bi {item.icon}"></i><span class="label">{item.label}</span></span
						>
					{:else}
						<a href={item.href} class="nav-link" class:active={isActive(item)}>
							<i class="bi {item.icon}"></i><span class="label">{item.label}</span>
							{#if item.badge}<span class="badge">{item.badge}</span>{/if}
						</a>
					{/if}
				</li>
			{/each}
		</ul>

		<div class="right">
			<!-- On phones only on the colony view, like Blade (body.page-colony). -->
			{#if s.in_active_run}<div class="sol-wrap" class:on-colony={page.url.pathname === '/colony'}>
					<SolButton />
				</div>{/if}
			<div class="user nav-desktop-only">
				<button
					class="plain"
					onclick={(e) => {
						e.stopPropagation();
						userOpen = !userOpen;
					}}
					aria-expanded={userOpen}>{s.username}</button
				>
				{#if userOpen}
					<div class="menu">
						{#each userNav as u (u.href)}<a href={u.href} class="menu-item">{u.label}</a>{/each}
						<button class="menu-item" onclick={logout}>{t('colony.user_nav_logout')}</button>
					</div>
				{/if}
			</div>

			<div class="nav-mobile">
				<button
					class="plain burger"
					onclick={(e) => {
						e.stopPropagation();
						menuOpen = !menuOpen;
					}}
					aria-expanded={menuOpen}
					aria-label={t('colony.nav_menu')}><i class="bi bi-list"></i></button
				>
				{#if menuOpen}
					<div class="menu">
						{#each nav as item (item.key)}
							{#if item.locked}
								<span class="menu-item locked"
									><i class="bi {item.icon}"></i><span
										>{item.label}<small class="locked-hint">{item.locked}</small></span
									></span
								>
							{:else}
								<a href={item.href} class="menu-item" class:active={isActive(item)}
									><i class="bi {item.icon}"></i>
									{item.label}
									{#if item.badge}<span class="badge">{item.badge}</span>{/if}</a
								>
							{/if}
						{/each}
						<div class="divider"></div>
						{#each userNav as u (u.href)}<a href={u.href} class="menu-item"><i class="bi {u.icon}"></i> {u.label}</a
							>{/each}
						<button class="menu-item" onclick={logout}><i class="bi bi-power"></i> {t('colony.user_nav_logout')}</button>
					</div>
				{/if}
			</div>
		</div>
	</nav>

	<div class="resbar"><ResourceBar {s} /></div>
	<HintBar {s} />
</header>

<style>
	.app-header {
		position: sticky;
		top: 0;
		z-index: 100;
		background: var(--color-navbar-bg);
	}
	nav {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: var(--space-md);
		height: 60px;
		padding: 0 var(--space-md);
	}
	.logo {
		font-family: var(--font-display);
		text-transform: uppercase;
		letter-spacing: var(--text-h1-tracking);
		font-size: 0.9rem;
		color: var(--color-text-primary);
		text-decoration: none;
		flex-shrink: 0;
	}
	.logo:hover {
		color: var(--color-accent);
	}
	ul {
		display: flex;
		flex: 1;
		justify-content: center;
		min-width: 0;
		gap: 0.25rem;
		margin: 0;
		padding: 0;
		list-style: none;
	}
	.nav-link {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0 10px;
		line-height: 40px;
		font-size: var(--text-nav-size);
		color: #4a4a58;
		text-decoration: none;
		white-space: nowrap;
		border-radius: 6px;
		position: relative;
	}
	a.nav-link:hover {
		color: var(--color-text-primary);
		background: rgba(0, 0, 0, 0.04);
	}
	.nav-link.active,
	.menu-item.active {
		color: var(--color-accent);
		font-weight: 600;
		background: var(--color-accent-tint);
	}
	.locked {
		opacity: 0.45;
		cursor: default;
	}
	.badge {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 1.1em;
		height: 1.1em;
		padding: 0 0.25em;
		border-radius: var(--radius-round);
		background: var(--color-danger);
		color: #fff;
		font-size: 0.65em;
		font-weight: 700;
		line-height: 1;
	}
	.right {
		display: flex;
		align-items: center;
		gap: var(--space-sm);
		position: relative;
	}
	.plain {
		background: none;
		border: none;
		font: inherit;
		font-size: var(--text-nav-size);
		color: #4a4a58;
		cursor: pointer;
		padding: 0 var(--space-sm);
		line-height: 40px;
	}
	.plain:hover {
		color: var(--color-text-primary);
	}
	.burger {
		font-size: 1.3rem;
	}
	.user {
		position: relative;
	}
	.menu {
		position: absolute;
		right: 0;
		top: 44px;
		min-width: 200px;
		max-width: min(320px, calc(100vw - 1rem));
		background: var(--color-bg);
		border: 1px solid var(--color-border);
		border-radius: 6px;
		box-shadow: var(--shadow-flyout);
		padding: var(--space-xs) 0;
		display: flex;
		flex-direction: column;
		z-index: 500;
		animation: ds-slide-fade-in var(--duration-fast) ease-out;
	}
	.menu-item {
		display: flex;
		align-items: center;
		gap: 0.6rem;
		padding: 0.6rem 1rem;
		font: inherit;
		font-size: 0.875rem;
		color: var(--color-text-primary);
		text-decoration: none;
		background: none;
		border: none;
		text-align: left;
		cursor: pointer;
		white-space: nowrap;
	}
	.menu-item:hover {
		background: var(--color-surface);
	}
	.menu-item.locked {
		align-items: flex-start;
		white-space: normal;
		opacity: 0.55;
	}
	.locked-hint {
		display: block;
		font-size: 0.75rem;
		color: var(--color-text-secondary);
	}
	.divider {
		height: 1px;
		background: var(--color-border);
		margin: var(--space-xs) 0;
	}
	.resbar {
		border-bottom: 1px solid var(--color-border);
		padding: 5px 12px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
	}

	/* Breakpoints: docs/design-system (mobile ≤599, icon-only nav <1100). */
	.nav-mobile {
		display: none;
	}
	@media (max-width: 1099px) {
		.label {
			display: none;
		}
		.nav-link {
			width: 2.5rem;
			justify-content: center;
			padding: 0;
		}
		.nav-link .badge {
			position: absolute;
			top: 4px;
			right: -2px;
		}
	}
	@media (max-width: 599px) {
		.nav-desktop,
		.nav-desktop-only,
		.sol-wrap {
			display: none;
		}
		.nav-mobile {
			display: block;
		}
		.sol-wrap.on-colony {
			display: block;
		}
	}
</style>
