{{--
    Terrain detail for the selected tile: status chips, coordinates, event,
    resource bar. Used in two contexts:
      - empty/unbuilt tile  → primary content, flat (built = false)
      - built tile          → secondary content inside a <details> disclosure
                              (built = true): the exploration chips
                              (locked/fog/explored) are dropped as redundant —
                              a building only stands on explored terrain.

    Params:
      built — bool; true suppresses the exploration-state chips.
--}}
@php $built = $built ?? false; @endphp
<div class="tile-terrain">
    <div class="tile-status-chips">
        @unless ($built)
            <span x-show="!selectedTile.is_explored && !selectedTile.is_colony_zone"
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

    <dl class="tile-dl">
        <div class="tile-dl-coords">
            <dt>Koordinaten</dt>
            <dd x-text="`q=${selectedTile.q}, r=${selectedTile.r} (Ring ${selectedTile.ring})`"></dd>
        </div>

        <template x-if="selectedTile.is_deep_scanned && selectedTile.event_type">
            <div>
                <dt>Event</dt>
                <dd x-text="eventTypeName(selectedTile.event_type)"></dd>
            </div>
        </template>
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
