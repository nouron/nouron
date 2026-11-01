<script lang="ts">
	// Port of partials/feedback.blade.php (R15, closed beta): fixed button → beveled dialog → POST /feedback.
	import '../../../../public/css/dialogs.css';
	import '../../../../public/css/feedback.css';
	import { page } from '$app/state';
	import { t } from '#lib/i18n.ts';
	import { api, ApiError } from '#lib/stores/auth.ts';
	import Button from './Button.svelte';

	let dialog = $state<HTMLDialogElement>();
	let category = $state('bug');
	let message = $state('');
	let sending = $state(false);
	let sent = $state(false);
	let error = $state('');

	function reset() {
		if (sent) {
			message = '';
			category = 'bug';
		}
		sent = false;
		sending = false;
		error = '';
	}

	async function submit() {
		if (sending || message.trim() === '') return;
		sending = true;
		error = '';
		try {
			await api('/feedback', { method: 'POST', body: { category, message, page: page.url.pathname } });
			sent = true;
		} catch (e) {
			error = e instanceof ApiError && e.status === 429 ? t('feedback.rate_limited') : t('feedback.error');
		} finally {
			sending = false;
		}
	}
</script>

<button type="button" class="feedback-trigger" title={t('feedback.button_title')} onclick={() => dialog?.showModal()}>
	<i class="bi bi-chat-left-text" aria-hidden="true"></i>
	<span>{t('feedback.button')}</span>
</button>

<dialog bind:this={dialog} class="sol-modal feedback-dialog" onclose={reset}>
	<article>
		<header>
			<h3>{t('feedback.title')}</h3>
			<button type="button" class="sol-modal-close" onclick={() => dialog?.close()} aria-label={t('feedback.cancel')}
				>&#x2715;</button
			>
		</header>

		{#if !sent}
			<section>
				<p class="feedback-dialog__intro">{t('feedback.intro')}</p>
				<label
					>{t('feedback.category')}
					<select bind:value={category}>
						<option value="bug">{t('feedback.category_bug')}</option>
						<option value="balance">{t('feedback.category_balance')}</option>
						<option value="idea">{t('feedback.category_idea')}</option>
						<option value="other">{t('feedback.category_other')}</option>
					</select>
				</label>
				<label
					>{t('feedback.message')}
					<textarea bind:value={message} rows="5" maxlength="2000" placeholder={t('feedback.message_placeholder')}
					></textarea>
				</label>
				{#if error}<p class="feedback-dialog__error" role="alert">{error}</p>{/if}
			</section>
		{:else}
			<section><p role="status">{t('feedback.thanks')}</p></section>
		{/if}

		<footer>
			<Button variant="ghost" type="button" onclick={() => dialog?.close()}
				>{sent ? t('feedback.close') : t('feedback.cancel')}</Button
			>
			{#if !sent}
				<Button type="button" onclick={submit} disabled={sending || message.trim() === ''}
					>{sending ? t('feedback.sending') : t('feedback.send')}</Button
				>
			{/if}
		</footer>
	</article>
</dialog>

<style>
	footer {
		display: flex;
		justify-content: flex-end;
		gap: var(--space-sm);
	}
	section {
		padding-top: var(--space-md);
	}
</style>
