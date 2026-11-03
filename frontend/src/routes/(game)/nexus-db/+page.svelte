<script lang="ts">
	// Port of resources/views/nexusdb/index.blade.php (ADR 0006 stage 4e): the core concepts in short.
	import '#lib/styles/nexusdb.css';
	import { onMount } from 'svelte';
	import { api } from '#lib/stores/auth.ts';
	import { t } from '#lib/i18n.ts';
	import FirstVisit from '#lib/components/FirstVisit.svelte';

	const CONCEPTS = ['supply', 'trust', 'sol', 'ap', 'decay', 'repair', 'nexus', 'colonists'];
	type BuildingEntry = {
		key: string;
		label: string;
		description: string;
		requires: { cc_level: number | null; building: { label: string; level: number } | null };
		max_level: number;
		supply_cost: number;
		placement_cost: Record<string, number>;
		levels: { level: number; ap: number; regolith: number }[];
		is_path: boolean;
	};
	let firstVisit = $state(false);
	let buildings = $state<BuildingEntry[]>([]);
	let tab = $state<'concepts' | 'buildings'>('concepts');
	onMount(() => {
		api<{ first_visit: boolean; buildings: BuildingEntry[] }>('/nexus-db')
			.then((d) => {
				firstVisit = d.first_visit;
				buildings = d.buildings ?? [];
			})
			.catch(() => {});
	});

	const requirement = (b: BuildingEntry) =>
		[
			b.requires.cc_level ? t('nexusdb.building_requires_cc', { level: b.requires.cc_level }) : '',
			b.requires.building
				? t('nexusdb.building_requires_building', { name: b.requires.building.label, level: b.requires.building.level })
				: ''
		]
			.filter(Boolean)
			.join(', ') || t('nexusdb.building_requires_none');

	// resource_id -> short label (3 Regolith, 4 Werkstoffe)
	const RES: Record<string, string> = { '3': 'Rg', '4': 'Wk' };
	const placing = (b: BuildingEntry) =>
		Object.entries(b.placement_cost)
			.map(([id, n]) => `${n} ${RES[id] ?? id}`)
			.join(' + ') || t('nexusdb.building_cost_free');
</script>

<div class="nexusdb-page">
	<p class="nexusdb-intro">{t('nexusdb.page_subtitle')}</p>
	<div class="nexusdb-tabs" role="tablist">
		<button role="tab" aria-selected={tab === 'concepts'} class:active={tab === 'concepts'} onclick={() => (tab = 'concepts')}
			>{t('nexusdb.tab_concepts')}</button
		>
		<button role="tab" aria-selected={tab === 'buildings'} class:active={tab === 'buildings'} onclick={() => (tab = 'buildings')}
			>{t('nexusdb.tab_buildings')}</button
		>
	</div>
	{#if tab === 'concepts'}
		<div class="nexusdb-accordion">
			{#each CONCEPTS as c (c)}
				<details class="nexusdb-item">
					<summary class="nexusdb-item-title">{t(`nexusdb.concept_${c}_title`)}</summary>
					<p class="nexusdb-item-body">{t(`nexusdb.concept_${c}_body`)}</p>
				</details>
			{/each}
		</div>
	{:else}
		<div class="nexusdb-accordion">
			{#each buildings as b (b.key)}
				<details class="nexusdb-item">
					<summary class="nexusdb-item-title">{b.label}{#if b.is_path}<small class="nexusdb-path"> · {t('nexusdb.building_path_note')}</small>{/if}</summary>
					<div class="nexusdb-item-body">
						<p>{b.description}</p>
						<dl class="nexusdb-facts">
							<dt>{t('nexusdb.building_requires')}</dt>
							<dd>{requirement(b)}</dd>
							<dt>{t('nexusdb.building_cost_place')}</dt>
							<dd>{placing(b)}</dd>
							<dt>{t('nexusdb.building_colonists')}</dt>
							<dd>{b.supply_cost}</dd>
							<dt>{t('nexusdb.building_max_level')}</dt>
							<dd>{b.max_level}</dd>
						</dl>
						<table class="nexusdb-levels">
							<caption>{t('nexusdb.building_levels')}</caption>
							<thead>
								<tr>
									<th>{t('nexusdb.building_col_level')}</th><th>{t('nexusdb.building_col_ap')}</th><th>{t('nexusdb.building_col_regolith')}</th>
								</tr>
							</thead>
							<tbody>
								{#each b.levels as l (l.level)}
									<tr><td>{l.level}</td><td>{l.ap}</td><td>{l.regolith}</td></tr>
								{/each}
							</tbody>
						</table>
					</div>
				</details>
			{/each}
		</div>
	{/if}
</div>

{#if firstVisit}<FirstVisit screen="nexusdb" />{/if}
