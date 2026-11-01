<script lang="ts">
	// Frame of the screens outside a running run (lobby, result, codex) — port of layouts/infra.blade.php.
	import '../../../../public/css/resources.css';
	import '#lib/styles/infra.css';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { fade } from 'svelte/transition';
	import FeedbackButton from '#lib/components/FeedbackButton.svelte';
	import { BACKEND_URL, logout, token } from '#lib/stores/auth.ts';
	import { refreshShell, shell } from '#lib/stores/shell.ts';
	import { t } from '#lib/i18n.ts';

	let { children } = $props();
	let open = $state(false);

	$effect(() => {
		if (!$token) goto('/login');
	});
	$effect(() => {
		if ($token) refreshShell().catch(() => {});
	});
</script>

<svelte:window onclick={() => (open = false)} />

{#if $shell}
	<header class="infra-header">
		<nav>
			<a href="/lobby" class="infra-brand">Nouron</a>
			<div class="right">
				{#if $shell.in_active_run}
					<a href="/colony" class="plain"><i class="bi bi-hexagon"></i><span class="infra-nav-label"> {t('colony.nav_colony')}</span></a>
				{/if}
				<div class="user">
					<button
						class="plain"
						aria-expanded={open}
						onclick={(e) => {
							e.stopPropagation();
							open = !open;
						}}>{$shell.username}</button
					>
					{#if open}
						<div class="menu">
							<a class="menu-item" href="/lobby">{t('lobby.nav_runs')}</a>
							<a class="menu-item" href="{BACKEND_URL}/user">{t('colony.user_nav_profile')}</a>
							<a class="menu-item" href="{BACKEND_URL}/user/settings">{t('colony.user_nav_settings')}</a>
							<a class="menu-item" href="/codex">{t('colony.user_nav_codex')}</a>
							<button class="menu-item" onclick={logout}>{t('colony.user_nav_logout')}</button>
						</div>
					{/if}
				</div>
			</div>
		</nav>
	</header>
{/if}

{#key page.url.pathname}
	<main class="infra-main" in:fade={{ duration: 150 }}>{@render children()}</main>
{/key}

{#if $shell}<FeedbackButton />{/if}

<style>
	nav {
		display: flex;
		align-items: center;
		justify-content: space-between;
		height: 56px;
	}
	.right {
		display: flex;
		align-items: center;
		gap: var(--space-md);
	}
	.plain {
		background: none;
		border: none;
		font: inherit;
		font-size: var(--text-nav-size, 0.9rem);
		color: var(--color-text-secondary);
		text-decoration: none;
		cursor: pointer;
	}
	.plain i {
		margin-right: 0.3rem;
	}
	.plain:hover {
		color: var(--color-text-primary);
	}
	.user {
		position: relative;
	}
	.menu {
		position: absolute;
		right: 0;
		top: 2rem;
		min-width: 200px;
		background: var(--color-bg);
		border: 1px solid var(--color-border);
		border-radius: 6px;
		box-shadow: var(--shadow-dropdown, 0 4px 12px rgba(0, 0, 0, 0.1));
		padding: var(--space-xs) 0;
		display: flex;
		flex-direction: column;
		z-index: 500;
	}
	.menu-item {
		padding: 0.6rem 1rem;
		font: inherit;
		font-size: 0.875rem;
		color: var(--color-text-primary);
		text-decoration: none;
		background: none;
		border: none;
		text-align: left;
		cursor: pointer;
	}
	.menu-item:hover {
		background: var(--color-surface);
	}
	@media (max-width: 599px) {
		.infra-nav-label {
			display: none;
		}
	}
</style>
