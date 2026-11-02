<script lang="ts">
	// Themed replacement for window.confirm(); reuses the shared .sol-modal shell.
	import '#lib/styles/dialogs.css';
	import { onMount } from 'svelte';
	import { t } from '#lib/i18n.ts';

	let {
		title,
		message,
		confirmLabel,
		onconfirm,
		oncancel
	}: { title: string; message: string; confirmLabel: string; onconfirm: () => void; oncancel: () => void } = $props();

	let dialog = $state<HTMLDialogElement>();
	onMount(() => dialog?.showModal());
</script>

<dialog bind:this={dialog} class="sol-modal confirm-dialog" onclose={oncancel}>
	<article>
		<header>
			<h3>{title}</h3>
			<button type="button" class="sol-modal-close" onclick={oncancel} aria-label={t('colony.cancel')}>&#x2715;</button>
		</header>
		<p class="confirm-dialog__body">{message}</p>
		<footer class="confirm-dialog__actions">
			<button type="button" class="confirm-dialog__cancel" onclick={oncancel}>{t('colony.cancel')}</button>
			<button type="button" class="confirm-dialog__confirm" onclick={onconfirm}>{confirmLabel}</button>
		</footer>
	</article>
</dialog>

<style>
	.confirm-dialog__body {
		margin: 0;
		padding: 1rem 1.25rem;
		line-height: 1.5;
	}
	.confirm-dialog__actions {
		display: flex;
		justify-content: flex-end;
		gap: 0.5rem;
		padding: 0 1.25rem 1rem;
	}
	.confirm-dialog__actions button {
		width: auto;
		margin: 0;
		padding: 0.4rem 0.9rem;
		font-size: 0.85rem;
		border-radius: 4px;
	}
	.confirm-dialog__cancel {
		background: transparent;
		border: 1px solid var(--color-border-strong, #d0d0dc);
		color: var(--color-anthracite, #333);
	}
	.confirm-dialog__confirm {
		background: var(--color-accent, #8c2030);
		border: 1px solid var(--color-accent, #8c2030);
		color: #fff;
	}
</style>
