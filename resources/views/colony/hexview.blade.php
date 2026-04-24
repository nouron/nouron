@extends('layouts.colony')
@section('title', $colony->name . ' — Kolonie')

@section('content')
<div
    class="hex-page"
    x-data="colonyHexView({
        tiles: @json($tiles),
        colony: @json(['id' => $colony->id, 'name' => $colony->name]),
        ccLevel: {{ (int)$ccLevel }}
    })"
    x-init="init()"
>
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
                </div>
            </template>

            <template x-if="!selectedTile">
                <div class="tile-panel-empty">
                    <p>Tile auswählen für Details.</p>
                </div>
            </template>
        </aside>

    </div>
</div>
@endsection
