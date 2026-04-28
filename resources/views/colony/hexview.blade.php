@extends('layouts.colony')
@section('title', $colony->name . ' — Kolonie')

@section('content')
<script>
window.__colonyViewData = {
    tiles:     @json($tiles),
    colony:    @json(['id' => $colony->id, 'name' => $colony->name]),
    ccLevel:   {{ (int)$ccLevel }},
    buildings: @json($buildings),
};
</script>
<div class="hex-page" x-data="colonyHexView(window.__colonyViewData)">
    <div class="hex-layout">

        {{-- Hex grid canvas --}}
        <div class="hex-canvas-wrap">
            <div class="hex-canvas-header">
                <h2>{{ $colony->name }}</h2>
                <small x-text="statusLine()"></small>
            </div>
            <div x-ref="hexgrid" class="hex-canvas"></div>
        </div>

        {{-- Tile info sidebar --}}
        <aside class="tile-panel">
            <div class="tile-panel-header">
                <h3>Tile-Info</h3>
            </div>

            <div class="tile-panel-body">
                <template x-if="selectedTile">
                    <div>
                        <h3 class="tile-heading" x-text="tileHeading(selectedTile)"></h3>

                        <div class="tile-status-chips">
                            <span x-show="!selectedTile.is_ring_unlocked" class="chip chip--locked">Gesperrt</span>
                            <span x-show="selectedTile.is_ring_unlocked && !selectedTile.is_explored" class="chip chip--fog">Unerforscht</span>
                            <span x-show="selectedTile.is_explored && !selectedTile.is_deep_scanned" class="chip chip--explored">Erkundet</span>
                            <span x-show="selectedTile.is_deep_scanned" class="chip chip--scanned">Tiefgescannt</span>
                        </div>

                        <dl class="tile-dl">
                            <dt>Koordinaten</dt>
                            <dd x-text="`q=${selectedTile.q}, r=${selectedTile.r} (Ring ${selectedTile.ring})`"></dd>

                            <template x-if="selectedTile.is_explored">
                                <div>
                                    <dt>Typ</dt>
                                    <dd x-text="tileTypeName(selectedTile.tile_type)"></dd>
                                </div>
                            </template>

                            <template x-if="selectedTile.is_deep_scanned && selectedTile.event_type">
                                <div>
                                    <dt>Event</dt>
                                    <dd x-text="eventTypeName(selectedTile.event_type)"></dd>
                                </div>
                            </template>
                        </dl>

                        <template x-if="selectedTile.resource_max > 0 && selectedTile.is_explored">
                            <div class="sidebar-resource">
                                <div class="sidebar-bar-group">
                                    <div class="sidebar-bar-label">
                                        <span>Regolith</span>
                                        <span x-text="`${selectedTile.resource_amount} / ${selectedTile.resource_max}`"></span>
                                    </div>
                                    <div class="sidebar-bar-wrap">
                                        <div class="sidebar-bar sidebar-bar--resource"
                                             :style="`width:${Math.round(selectedTile.resource_amount / selectedTile.resource_max * 100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="buildingForTile(selectedTile)">
                            <div class="sidebar-building">
                                <div class="sidebar-section-title">Gebäude</div>
                                <div class="sidebar-building-name">
                                    <strong x-text="buildingForTile(selectedTile).label"></strong>
                                    <span class="sidebar-level-badge" x-text="`Lv. ${buildingForTile(selectedTile).level}`"></span>
                                </div>

                                <dl class="tile-dl" style="margin-top:.5rem">
                                    <dt>Max. Stufe</dt>
                                    <dd x-text="buildingForTile(selectedTile).max_level ?? '∞'"></dd>
                                </dl>

                                <div class="sidebar-bar-group">
                                    <div class="sidebar-bar-label">
                                        <span>Zustand</span>
                                        <span x-text="`${buildingForTile(selectedTile).status_points} / ${buildingForTile(selectedTile).max_status_points ?? 20}`"></span>
                                    </div>
                                    <div class="sidebar-bar-wrap">
                                        <div class="sidebar-bar sidebar-bar--status"
                                             :style="`width:${Math.round(buildingForTile(selectedTile).status_points / (buildingForTile(selectedTile).max_status_points ?? 20) * 100)}%`"></div>
                                    </div>
                                </div>

                                <div class="sidebar-bar-group">
                                    <div class="sidebar-bar-label">
                                        <span>AP investiert</span>
                                        <span x-text="`${buildingForTile(selectedTile).ap_spend} / ${buildingForTile(selectedTile).ap_for_levelup}`"></span>
                                    </div>
                                    <div class="sidebar-bar-wrap">
                                        <div class="sidebar-bar sidebar-bar--ap"
                                             :style="`width:${Math.round(buildingForTile(selectedTile).ap_spend / buildingForTile(selectedTile).ap_for_levelup * 100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedTile.is_ring_unlocked">
                            <div class="sidebar-actions">
                                <button class="sidebar-action-btn" disabled>Ausbauen</button>
                                <button class="sidebar-action-btn" disabled>Erkunden</button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!selectedTile">
                    <div class="tile-panel-empty">
                        <p>Hex-Tile anklicken<br>um Details anzuzeigen.</p>
                    </div>
                </template>
            </div>
        </aside>

    </div>
</div>
@endsection
