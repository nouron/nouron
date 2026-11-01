<script lang="ts">
	// Port of resources/views/colony/partials/tile-terrain.blade.php: zone status, terrain,
	// status chips, find card, resource bar. `built` drops the parts redundant on a built tile.
	import { t } from '#lib/i18n.ts';
	import type { Tile } from './types.ts';

	let {
		tile,
		built = false,
		tileTypeName,
		eventTypeName,
		flightShortfall,
		onsalvage,
		onflight
	}: {
		tile: Tile;
		built?: boolean;
		tileTypeName: (type: string) => string;
		eventTypeName: (type: string) => string;
		flightShortfall: (tile: Tile) => string | null;
		onsalvage: (tile: Tile) => void;
		onflight: (tile: Tile) => void;
	} = $props();

	const resourcePct = $derived(tile.resource_max ? Math.round((tile.resource_amount / tile.resource_max) * 100) : 0);
	const shortfall = $derived(tile.find?.action?.action === 'flight' ? flightShortfall(tile) : null);
</script>

<div class="tile-terrain">
	<div class="tile-status-chips">
		{#if !built}
			{#if !tile.is_explored && !tile.is_colony_zone && !tile.next_zone}
				<span class="chip chip--locked">{t('colony.chip_locked')}</span>
			{/if}
			{#if !tile.is_explored && tile.is_colony_zone}
				<span class="chip chip--fog">{t('colony.chip_unexplored')}</span>
			{/if}
			{#if tile.is_explored && !tile.is_deep_scanned && !tile.has_signal}
				<span class="chip chip--explored">{t('colony.chip_explored')}</span>
			{/if}
		{/if}
		{#if tile.is_explored && tile.has_signal}<span class="chip chip--signal">{t('colony.chip_signal')}</span>{/if}
		{#if tile.is_deep_scanned}<span class="chip chip--scanned">{t('colony.chip_scanned')}</span>{/if}
	</div>

	{#if !built}
		<div class="tile-zone-status">
			{#if tile.is_colony_zone}
				<p class="tile-zone-line tile-zone-line--buildable">{t('colony.zone_buildable')}</p>
			{:else if tile.next_zone}
				<p class="tile-zone-line tile-zone-line--soon">{t('colony.zone_soon')}</p>
			{:else if tile.is_explored}
				<p class="tile-zone-line tile-zone-line--outside">{t('colony.zone_outside')}</p>
			{:else}
				<p class="tile-zone-line tile-zone-line--fog">{t('colony.zone_unexplored')}</p>
			{/if}
		</div>
	{/if}

	{#if tile.is_explored}
		{#if tile.tile_type !== 'terrain_empty'}
			<dl class="tile-dl">
				<div>
					<dt>{t('colony.terrain_label')}</dt>
					<dd>{tileTypeName(tile.tile_type)}</dd>
				</div>
			</dl>
		{/if}
		{#if tile.tile_type.startsWith('regolith_')}
			<p class="tile-terrain-hint tile-terrain-hint--regolith">{t('colony.hint_regolith_target')}</p>
		{:else if tile.tile_type === 'terrain_hazard'}
			<p class="tile-terrain-hint tile-terrain-hint--hazard">{t('colony.hint_hazard')}</p>
		{:else if tile.tile_type === 'terrain_impassable'}
			<p class="tile-terrain-hint tile-terrain-hint--impassable">{t('colony.hint_impassable')}</p>
		{/if}
	{/if}

	<dl class="tile-dl">
		{#if tile.is_deep_scanned && tile.event_type}
			<div>
				<dt>{t('colony.event_label')}</dt>
				<dd>{eventTypeName(tile.event_type)}</dd>
			</div>
		{/if}
		<div class="tile-dl-coords">
			<dt>{t('colony.coords_label')}</dt>
			<dd>q={tile.q}, r={tile.r} (Ring {tile.ring})</dd>
		</div>
	</dl>

	{#if tile.find}
		<div class="find-card">
			{#if tile.find.false}
				<p class="find-card__false">{t('colony.find_false_notice')}</p>
			{:else}
				<dl class="tile-dl find-card__dl">
					<div>
						<dt>{t('colony.find_label')}</dt>
						<dd>{t('colony.find_value', { rg: tile.find.rg })}</dd>
					</div>
				</dl>
				<p class="find-card__reason">{tile.find.action?.text ?? ''}</p>
				{#if tile.find.action?.action === 'self_salvage'}
					<div class="find-card__action">
						<button type="button" class="tile-action-btn" onclick={() => onsalvage(tile)}>
							<span class="tile-action-btn__body">{t('colony.find_salvage')}</span>
							<span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true">{tile.find.ap_total} AP</span>
						</button>
					</div>
				{:else if tile.find.action?.action === 'flight'}
					<div class="find-card__action">
						<button type="button" class="tile-action-btn" onclick={() => onflight(tile)} disabled={shortfall !== null}>
							<span class="tile-action-btn__body">{t('colony.find_flight')}</span>
							<span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true"
								>{tile.find.action.costs?.ap_cost} AP</span
							>
						</button>
						{#if shortfall}<p class="find-card__reason">{shortfall}</p>{/if}
					</div>
				{/if}
			{/if}
		</div>
	{/if}

	{#if tile.resource_max > 0 && tile.is_explored}
		<div class="tile-resource">
			<div class="tile-bar-group">
				<div class="tile-bar-label">
					<span>{t('colony.resource_regolith')}</span>
					<span>{tile.resource_amount} / {tile.resource_max}</span>
				</div>
				<div class="tile-bar-wrap">
					<div class="tile-bar tile-bar--resource" style="width:{resourcePct}%"></div>
				</div>
			</div>
		</div>
	{/if}
</div>
