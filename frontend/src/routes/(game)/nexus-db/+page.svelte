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
	type Req = { label: string; level: number };
	type AssetEntry = {
		key: string;
		label: string;
		description: string;
		requires: Req[];
		max_level?: number;
		levels?: { level: number; ap: number }[];
		nexus_cost?: number;
		delivery_sols?: number;
		hire_cost?: number;
	};
	type Tab = 'concepts' | 'buildings' | 'knowledge' | 'ships' | 'advisors';
	const TABS: Tab[] = ['concepts', 'buildings', 'knowledge', 'ships', 'advisors'];
	let firstVisit = $state(false);
	let buildings = $state<BuildingEntry[]>([]);
	let assets = $state<Record<'knowledge' | 'ships' | 'advisors', AssetEntry[]>>({ knowledge: [], ships: [], advisors: [] });
	let tab = $state<Tab>('concepts');
	onMount(() => {
		api<{ first_visit: boolean; buildings: BuildingEntry[]; knowledge: AssetEntry[]; ships: AssetEntry[]; advisors: AssetEntry[] }>('/nexus-db')
			.then((d) => {
				firstVisit = d.first_visit;
				buildings = d.buildings ?? [];
				assets = { knowledge: d.knowledge ?? [], ships: d.ships ?? [], advisors: d.advisors ?? [] };
			})
			.catch(() => {});
	});

	const reqText = (rs: Req[]) =>
		rs.map((r) => t('nexusdb.building_requires_building', { name: r.label, level: r.level })).join(', ') || t('nexusdb.building_requires_none');

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
		{#each TABS as k (k)}
			<button role="tab" aria-selected={tab === k} class:active={tab === k} onclick={() => (tab = k)}>{t(`nexusdb.tab_${k}`)}</button>
		{/each}
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
	{:else if tab === 'buildings'}
		<div class="nexusdb-accordion">
			{#each buildings as b (b.key)}
				<details class="nexusdb-item">
					<summary class="nexusdb-item-title"><span>{b.label}{#if b.is_path}<small class="nexusdb-path"> · {t('colony.focus_' + b.key)}</small>{/if}</span></summary>
					<div class="nexusdb-item-body">
						<p>{b.description}</p>
						{#if b.is_path}<p class="nexusdb-focus-note">{t('nexusdb.building_focus_note')}</p>{/if}
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
	{:else}
		<div class="nexusdb-accordion">
			{#each assets[tab] as a (a.key)}
				<details class="nexusdb-item">
					<summary class="nexusdb-item-title"><span>{a.label}</span></summary>
					<div class="nexusdb-item-body">
						<p>{a.description}</p>
						<dl class="nexusdb-facts">
							<dt>{t('nexusdb.building_requires')}</dt>
							<dd>{reqText(a.requires)}</dd>
							{#if a.hire_cost !== undefined}
								<dt>{t('nexusdb.asset_hire_cost')}</dt>
								<dd>{a.hire_cost} Cr</dd>
							{/if}
							{#if a.nexus_cost !== undefined}
								<dt>{t('nexusdb.asset_nexus_cost')}</dt>
								<dd>{a.nexus_cost} Cr</dd>
								<dt>{t('nexusdb.asset_delivery')}</dt>
								<dd>{t('nexusdb.asset_delivery_value', { count: a.delivery_sols ?? 0 })}</dd>
							{/if}
							{#if a.max_level}
								<dt>{t('nexusdb.building_max_level')}</dt>
								<dd>{a.max_level}</dd>
							{/if}
						</dl>
						{#if a.levels?.length}
							<table class="nexusdb-levels">
								<caption>{t('nexusdb.building_levels')}</caption>
								<thead>
									<tr><th>{t('nexusdb.building_col_level')}</th><th>{t('nexusdb.building_col_ap')}</th></tr>
								</thead>
								<tbody>
									{#each a.levels as l (l.level)}
										<tr><td>{l.level}</td><td>{l.ap}</td></tr>
									{/each}
								</tbody>
							</table>
						{/if}
					</div>
				</details>
			{/each}
		</div>
	{/if}
</div>

{#if firstVisit}<FirstVisit screen="nexusdb" />{/if}
