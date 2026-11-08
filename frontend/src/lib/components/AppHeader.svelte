<script lang="ts">
	// Slim header: logo, Sol button, user menu; below it resource bar and hint bar. Navigation lives in BottomNav.
	import { t } from '#lib/i18n.ts';
	import { logout } from '#lib/stores/auth.ts';
	import type { Shell } from '#lib/stores/shell.ts';
	import HintBar from './HintBar.svelte';
	import ResourceBar from './ResourceBar.svelte';
	import SolButton from './SolButton.svelte';

	let { s, onfeedback }: { s: Shell; onfeedback: () => void } = $props();

	let userOpen = $state(false);
	let userBtn = $state<HTMLButtonElement>();

	const userNav = $derived([
		{ icon: 'bi-list-check', label: t('lobby.nav_runs'), href: '/lobby' },
		{ icon: 'bi-gear', label: t('colony.user_nav_settings'), href: '/settings' },
		...(s.is_admin ? [{ icon: 'bi-chat-left-text', label: t('feedback.admin_title'), href: '/admin/feedback' }] : [])
	]);
</script>

<svelte:window
	onclick={() => (userOpen = false)}
	onkeydown={(e) => {
		if (e.key === 'Escape' && userOpen) {
			userOpen = false;
			userBtn?.focus();
		}
	}}
/>

<header class="app-header">
	<div class="topbar">
		<a href="/" class="logo">Nouron</a>

		<div class="right">
			{#if s.in_active_run}<SolButton />{/if}
			<div class="user">
				<button
					class="plain"
					bind:this={userBtn}
					onclick={(e) => {
						e.stopPropagation();
						userOpen = !userOpen;
					}}
					aria-expanded={userOpen}
					aria-haspopup="true"
					aria-label="{t('colony.nav_user_menu')}: {s.username}"
					><i class="bi bi-person-circle" aria-hidden="true"></i><span class="uname">{s.username}</span></button
				>
				{#if userOpen}
					<div class="menu">
						{#each userNav as u (u.href)}<a href={u.href} class="menu-item"><i class="bi {u.icon}"></i> {u.label}</a
							>{/each}
						<button class="menu-item" onclick={onfeedback}
							><i class="bi bi-chat-left-text"></i> {t('feedback.button')}</button
						>
						<div class="divider"></div>
						<button class="menu-item" onclick={logout}><i class="bi bi-power"></i> {t('colony.user_nav_logout')}</button>
					</div>
				{/if}
			</div>
		</div>
	</div>

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
	.topbar {
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
	.right {
		display: flex;
		align-items: center;
		gap: var(--space-sm);
		position: relative;
	}
	.plain {
		display: inline-flex;
		align-items: center;
		gap: 0.4rem;
		min-height: 44px;
		background: none;
		border: none;
		font: inherit;
		font-size: var(--text-nav-size);
		color: #4a4a58;
		cursor: pointer;
		padding: 0 var(--space-sm);
	}
	.plain:hover {
		color: var(--color-text-primary);
	}
	.plain .bi {
		font-size: 1.2rem;
	}
	.uname {
		max-width: 10rem;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
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

	/* Phones: the user menu shows the icon only. */
	@media (max-width: 599px) {
		.uname {
			display: none;
		}
	}
</style>
