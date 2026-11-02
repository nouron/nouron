<script lang="ts">
	// Port of partials/first-visit-popup: one-time explanation per screen, confirmed via hint key visit_<screen>.
	import '#lib/styles/hints.css';
	import { t } from '#lib/i18n.ts';
	import { dismissHint } from '#lib/stores/shell.ts';

	let { screen }: { screen: string } = $props();
	let open = $state(true);
</script>

{#if open}
	<div class="first-visit-popup">
		<div class="first-visit-popup__card">
			<div class="first-visit-popup__icon" aria-hidden="true">ℹ</div>
			<div class="first-visit-popup__body">
				<p class="first-visit-popup__title">{t(`colony.first_visit_${screen}_title`)}</p>
				<p class="first-visit-popup__text">{t(`colony.first_visit_${screen}_text`)}</p>
			</div>
			<button
				class="first-visit-popup__dismiss"
				type="button"
				onclick={() => {
					open = false;
					dismissHint(`visit_${screen}`);
				}}>{t('colony.first_visit_dismiss')}</button
			>
		</div>
	</div>
{/if}
