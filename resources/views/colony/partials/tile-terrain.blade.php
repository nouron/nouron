{{--
    Terrain detail for the selected tile: zone status, terrain type, status
    chips, resource bar, hints. Used in two contexts:
      - empty/unbuilt tile  → primary content, flat (built = false)
      - built tile          → secondary content inside a <details> disclosure
                              (built = true): the exploration/zone chips and the
                              zone-status line are dropped as redundant — a
                              building only stands on explored, in-zone terrain.

    Params:
      built — bool; true suppresses the exploration-state chips + zone line.
--}}
@php $built = $built ?? false; @endphp
<div class="tile-terrain">
    <div class="tile-status-chips">
        @unless ($built)
            <span x-show="!selectedTile.is_explored && !selectedTile.is_colony_zone && !selectedTile.next_zone"
                class="chip chip--locked">{{ __("colony.chip_locked") }}</span>
            <span x-show="!selectedTile.is_explored && selectedTile.is_colony_zone"
                class="chip chip--fog">{{ __("colony.chip_unexplored") }}</span>
            <span x-show="selectedTile.is_explored && !selectedTile.is_deep_scanned && !selectedTile.has_signal"
                class="chip chip--explored">{{ __("colony.chip_explored") }}</span>
        @endunless
        <span x-show="selectedTile.is_explored && selectedTile.has_signal"
            class="chip chip--signal">{{ __("colony.chip_signal") }}</span>
        <span x-show="selectedTile.is_deep_scanned" class="chip chip--scanned">{{ __("colony.chip_scanned") }}</span>
    </div>

    {{-- Zone status: one clear sentence telling the player what they can do with
         this tile. Suppressed on built tiles (always in-zone + explored). --}}
    @unless ($built)
        <div class="tile-zone-status">
            <template x-if="selectedTile.is_colony_zone">
                <p class="tile-zone-line tile-zone-line--buildable">{{ __("colony.zone_buildable") }}</p>
            </template>
            <template x-if="!selectedTile.is_colony_zone && selectedTile.next_zone">
                <p class="tile-zone-line tile-zone-line--soon">{{ __("colony.zone_soon") }}</p>
            </template>
            <template x-if="!selectedTile.is_colony_zone && !selectedTile.next_zone && selectedTile.is_explored">
                <p class="tile-zone-line tile-zone-line--outside">{{ __("colony.zone_outside") }}</p>
            </template>
            <template x-if="!selectedTile.is_colony_zone && !selectedTile.next_zone && !selectedTile.is_explored">
                <p class="tile-zone-line tile-zone-line--fog">{{ __("colony.zone_unexplored") }}</p>
            </template>
        </div>
    @endunless

    {{-- Terrain type + per-type hint. Only meaningful once explored; under fog
         the concrete terrain is unknown. --}}
    <template x-if="selectedTile.is_explored && selectedTile.tile_type !== 'terrain_empty'">
        <dl class="tile-dl">
            <div>
                <dt>{{ __("colony.terrain_label") }}</dt>
                <dd x-text="tileTypeName(selectedTile.tile_type)"></dd>
            </div>
        </dl>
    </template>

    {{-- Regolith: relocation target for the Harvester. --}}
    <template x-if="selectedTile.is_explored && selectedTile.tile_type.startsWith('regolith_')">
        <p class="tile-terrain-hint tile-terrain-hint--regolith">{{ __("colony.hint_regolith_target") }}</p>
    </template>

    {{-- Hazard zone: building here is riskier (faster decay). --}}
    <template x-if="selectedTile.is_explored && selectedTile.tile_type === 'terrain_hazard'">
        <p class="tile-terrain-hint tile-terrain-hint--hazard">{{ __("colony.hint_hazard") }}</p>
    </template>

    {{-- Impassable: nothing can be built. --}}
    <template x-if="selectedTile.is_explored && selectedTile.tile_type === 'terrain_impassable'">
        <p class="tile-terrain-hint tile-terrain-hint--impassable">{{ __("colony.hint_impassable") }}</p>
    </template>

    <dl class="tile-dl">
        <template x-if="selectedTile.is_deep_scanned && selectedTile.event_type">
            <div>
                <dt>{{ __("colony.event_label") }}</dt>
                <dd x-text="eventTypeName(selectedTile.event_type)"></dd>
            </div>
        </template>

        <div class="tile-dl-coords">
            <dt>{{ __("colony.coords_label") }}</dt>
            <dd x-text="`q=${selectedTile.q}, r=${selectedTile.r} (Ring ${selectedTile.ring})`"></dd>
        </div>
    </dl>

    <template x-if="selectedTile.resource_max > 0 && selectedTile.is_explored">
        <div class="tile-resource">
            <div class="tile-bar-group">
                <div class="tile-bar-label">
                    <span>{{ __("colony.resource_regolith") }}</span>
                    <span x-text="`${selectedTile.resource_amount} / ${selectedTile.resource_max}`"></span>
                </div>
                <div class="tile-bar-wrap">
                    <div class="tile-bar tile-bar--resource" :style="`width:${resourcePct(selectedTile)}%`"></div>
                </div>
            </div>
        </div>
    </template>
</div>
