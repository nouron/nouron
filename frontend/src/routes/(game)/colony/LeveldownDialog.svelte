<script lang="ts">
	// Rückbau confirmation (GDD techtree §11.5): loads the real consequences from the preview
	// endpoint, writes only on confirm. Port of the leveldown <dialog> in hexview.blade.php.
	import '../../../../../public/css/dialogs.css';
	import { onMount } from 'svelte';
	import { api } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';

	type Preview = {
		ok: boolean;
		current_level: number;
		new_level: number;
		construction_cancelled: boolean;
		tile_released: boolean;
		ap_forfeited: number;
		freed_workplaces: number;
		colonists_lost: number;
		unfilled_now: number;
		unfilled_after: number;
	};
	export type LeveldownResult = { ok: boolean; error?: string; message?: string; [k: string]: unknown };

	let {
		building,
		label,
		onclose,
		ondone
	}: {
		building: { building_id: number; instance_id?: number; level: number };
		label: string;
		onclose: () => void;
		ondone: (res: LeveldownResult) => void;
	} = $props();

	let dialog = $state<HTMLDialogElement>();
	let preview = $state<Preview | null>(null);
	let submitting = $state(false);
	const ids = $derived({ building_id: building.building_id, instance_id: building.instance_id ?? 1 });
	const site = $derived(building.level === 0);

	onMount(() => {
		dialog?.showModal();
		api<Preview & LeveldownResult>(`/colony/building/leveldown-preview?${new URLSearchParams(ids as never)}`, {
			allowError: true
		})
			.then((res) => (res.ok ? (preview = res) : ondone(res)))
			.catch(() => ondone({ ok: false, message: t('colony.network_error') }));
	});

	async function confirm() {
		if (submitting) return;
		submitting = true;
		const res = await api<LeveldownResult>('/colony/building/leveldown', {
			method: 'POST',
			body: ids,
			allowError: true
		}).catch(() => ({ ok: false, message: t('colony.network_error') }));
		ondone(res);
	}
</script>

<dialog bind:this={dialog} class="sol-modal leveldown-dialog" onclose={onclose}>
	<article>
		<header>
			<h3>{site ? t('colony.leveldown_cancel_construction') : t('colony.leveldown_title')}</h3>
			<button type="button" class="sol-modal-close" onclick={onclose} aria-label={t('colony.cancel')}>&#x2715;</button>
		</header>
		<div class="leveldown-dialog__body">
			<p class="leveldown-dialog__name">{label}</p>
			{#if !preview}
				<p class="leveldown-dialog__loading">{t('colony.leveldown_loading')}</p>
			{:else}
				{@const p = preview}
				<ul class="leveldown-dialog__list">
					{#if p.construction_cancelled}
						<li class="leveldown-dialog__warn">{t('colony.leveldown_construction_cancelled')}</li>
					{:else}
						<li>{t('colony.leveldown_new_level', { from: p.current_level, to: p.new_level })}</li>
						{#if p.tile_released}<li class="leveldown-dialog__warn">{t('colony.leveldown_tile_released')}</li>{/if}
					{/if}
					{#if p.ap_forfeited > 0}
						<li class="leveldown-dialog__warn">{t('colony.leveldown_ap_forfeited', { ap: p.ap_forfeited })}</li>
					{/if}
					{#if p.freed_workplaces > 0}
						<li>{t('colony.leveldown_freed_workplaces', { count: p.freed_workplaces })}</li>
					{/if}
					{#if p.colonists_lost > 0}
						<li class="leveldown-dialog__warn">{t('colony.leveldown_colonists_lost', { count: p.colonists_lost })}</li>
					{/if}
					{#if p.unfilled_after > p.unfilled_now}
						<li class="leveldown-dialog__warn">{t('colony.leveldown_unfilled', { count: p.unfilled_after })}</li>
					{/if}
				</ul>
			{/if}
			<p class="leveldown-dialog__note">{t('colony.leveldown_no_refund')}</p>
		</div>
		<footer class="leveldown-dialog__actions">
			<button type="button" class="leveldown-dialog__cancel" onclick={onclose}>{t('colony.cancel')}</button>
			<button
				type="button"
				class="leveldown-dialog__confirm"
				onclick={confirm}
				disabled={!preview || submitting}
				aria-busy={submitting ? 'true' : 'false'}
			>
				{site ? t('colony.leveldown_confirm_cancel_construction') : t('colony.leveldown_confirm')}
			</button>
		</footer>
	</article>
</dialog>
