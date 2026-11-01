<script lang="ts">
	// Port of resources/views/nexusdb/index.blade.php (ADR 0006 stage 4e): the core concepts in short.
	import '../../../../../public/css/nexusdb.css';
	import { onMount } from 'svelte';
	import { api } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';
	import FirstVisit from '#lib/components/FirstVisit.svelte';

	const CONCEPTS = ['supply', 'trust', 'sol', 'ap', 'decay', 'repair', 'nexus', 'colonists'];
	let firstVisit = $state(false);
	onMount(() => {
		api<{ first_visit: boolean }>('/nexus-db')
			.then((d) => (firstVisit = d.first_visit))
			.catch(() => {});
	});
</script>

<div class="nexusdb-page">
	<p class="nexusdb-intro">{t('nexusdb.page_subtitle')}</p>
	<div class="nexusdb-accordion">
		{#each CONCEPTS as c (c)}
			<details class="nexusdb-item">
				<summary class="nexusdb-item-title">{t(`nexusdb.concept_${c}_title`)}</summary>
				<p class="nexusdb-item-body">{t(`nexusdb.concept_${c}_body`)}</p>
			</details>
		{/each}
	</div>
</div>

{#if firstVisit}<FirstVisit screen="nexusdb" />{/if}
