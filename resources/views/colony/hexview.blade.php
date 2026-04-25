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
                        <h3 x-text="tileHeading(selectedTile)"></h3>
                        <dl>
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

                            <template x-if="selectedTile.resource_max > 0 && selectedTile.is_explored">
                                <div>
                                    <dt>Regolith</dt>
                                    <dd x-text="`${selectedTile.resource_amount} / ${selectedTile.resource_max}`"></dd>
                                </div>
                            </template>
                        </dl>

                        <div class="tile-status-chips">
                            <span x-show="!selectedTile.is_ring_unlocked" class="chip chip--locked">Gesperrt</span>
                            <span x-show="selectedTile.is_ring_unlocked && !selectedTile.is_explored" class="chip chip--fog">Unerforscht</span>
                            <span x-show="selectedTile.is_explored && !selectedTile.is_deep_scanned" class="chip chip--explored">Erkundet</span>
                            <span x-show="selectedTile.is_deep_scanned" class="chip chip--scanned">Tiefgescannt</span>
                        </div>

                        <div class="tile-panel-actions">
                            <button class="tile-detail-btn" @click="$refs.tileModal.showModal()">
                                Details &amp; Aktionen
                            </button>
                        </div>
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

    {{-- Tile detail modal --}}
    <dialog x-ref="tileModal" class="tile-modal" @click.self="$refs.tileModal.close()">
        <article>
            <header class="tile-modal-header">
                <button class="tile-modal-close" @click="$refs.tileModal.close()" aria-label="Schließen">&#x2715;</button>
                <h3 x-text="selectedTile ? tileHeading(selectedTile) : ''"></h3>
                <small x-text="selectedTile ? `q=${selectedTile.q}, r=${selectedTile.r} · Ring ${selectedTile.ring}` : ''"></small>
            </header>

            <div class="tile-modal-body">

                {{-- Status chips --}}
                <div class="tile-status-chips" style="margin-bottom:1rem">
                    <template x-if="selectedTile">
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                            <span x-show="selectedTile && !selectedTile.is_ring_unlocked" class="chip chip--locked">Gesperrt</span>
                            <span x-show="selectedTile && selectedTile.is_ring_unlocked && !selectedTile.is_explored" class="chip chip--fog">Unerforscht</span>
                            <span x-show="selectedTile && selectedTile.is_explored && !selectedTile.is_deep_scanned" class="chip chip--explored">Erkundet</span>
                            <span x-show="selectedTile && selectedTile.is_deep_scanned" class="chip chip--scanned">Tiefgescannt</span>
                        </div>
                    </template>
                </div>

                {{-- Tile properties --}}
                <template x-if="selectedTile && selectedTile.is_explored">
                    <dl class="modal-dl">
                        <dt>Typ</dt>
                        <dd x-text="tileTypeName(selectedTile.tile_type)"></dd>

                        <template x-if="selectedTile.is_deep_scanned && selectedTile.event_type">
                            <div>
                                <dt>Event</dt>
                                <dd x-text="eventTypeName(selectedTile.event_type)"></dd>
                            </div>
                        </template>

                        <template x-if="selectedTile.resource_max > 0">
                            <div>
                                <dt>Regolith-Vorkommen</dt>
                                <dd>
                                    <span x-text="`${selectedTile.resource_amount} / ${selectedTile.resource_max}`"></span>
                                    <div class="modal-bar-wrap" style="margin-top:.3rem">
                                        <div class="modal-bar modal-bar--resource"
                                             :style="`width:${Math.round(selectedTile.resource_amount / selectedTile.resource_max * 100)}%`"></div>
                                    </div>
                                </dd>
                            </div>
                        </template>
                    </dl>
                </template>

                {{-- Building on tile --}}
                <template x-if="selectedTile && buildingForTile(selectedTile)">
                    <div class="modal-building-section">
                        <h4 class="modal-section-title">Gebäude</h4>
                        <div class="modal-building-header">
                            <strong x-text="buildingForTile(selectedTile).label"></strong>
                            <span class="modal-level-badge" x-text="`Lv. ${buildingForTile(selectedTile).level}`"></span>
                        </div>

                        <dl class="modal-dl">
                            <dt>Max. Stufe</dt>
                            <dd x-text="buildingForTile(selectedTile).max_level ?? '∞'"></dd>
                        </dl>

                        {{-- Status bar --}}
                        <div class="modal-bar-group">
                            <div class="modal-bar-label">
                                <span>Zustand</span>
                                <span x-text="`${buildingForTile(selectedTile).status_points} / ${buildingForTile(selectedTile).max_status_points ?? 20}`"></span>
                            </div>
                            <div class="modal-bar-wrap">
                                <div class="modal-bar modal-bar--status"
                                     :style="`width:${Math.round(buildingForTile(selectedTile).status_points / (buildingForTile(selectedTile).max_status_points ?? 20) * 100)}%`"></div>
                            </div>
                        </div>

                        {{-- AP progress bar --}}
                        <div class="modal-bar-group">
                            <div class="modal-bar-label">
                                <span>AP investiert</span>
                                <span x-text="`${buildingForTile(selectedTile).ap_spend} / ${buildingForTile(selectedTile).ap_for_levelup}`"></span>
                            </div>
                            <div class="modal-bar-wrap">
                                <div class="modal-bar modal-bar--ap"
                                     :style="`width:${Math.round(buildingForTile(selectedTile).ap_spend / buildingForTile(selectedTile).ap_for_levelup * 100)}%`"></div>
                            </div>
                        </div>
                    </div>
                </template>

            </div>

            <footer class="tile-modal-footer">
                <button class="tile-modal-action" disabled title="Noch nicht implementiert">Ausbauen</button>
                <button class="tile-modal-action" disabled title="Noch nicht implementiert">Erkunden</button>
                <button class="tile-modal-action tile-modal-action--secondary" @click="$refs.tileModal.close()">Schließen</button>
            </footer>
        </article>
    </dialog>

</div>
@endsection
