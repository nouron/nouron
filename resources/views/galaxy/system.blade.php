@extends('layouts.app')

@section('title', ($system->name ?? 'System') . ' — Nouron')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .leaflet-container { background-color: transparent !important; }
    .leaflet-control-attribution { display: none; }
    .leaflet-control-zoom {
        border: 1px solid rgba(80,130,220,0.35) !important;
        background: rgba(2,8,20,0.92) !important;
    }
    .leaflet-control-zoom a {
        color: #5577aa !important;
        background: transparent !important;
        border-bottom-color: rgba(80,130,220,0.2) !important;
    }
    .leaflet-control-zoom a:hover {
        background: rgba(60,100,200,0.15) !important;
        color: #99bbdd !important;
    }
    .system-star-outer {
        filter: drop-shadow(0 0 10px rgba(255,230,100,1))
                drop-shadow(0 0 25px rgba(255,190,40,0.7))
                drop-shadow(0 0 55px rgba(255,140,20,0.35));
    }
    .fleet-marker { filter: drop-shadow(0 0 5px rgba(255,180,50,0.9)); }
    .leaflet-tooltip {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: #8aa8c8 !important;
        font-family: "Courier New", monospace !important;
        font-size: 9px !important;
        letter-spacing: 2px !important;
        text-transform: uppercase;
        text-shadow: 0 0 10px rgba(80,140,220,0.9), 0 0 3px rgba(0,0,0,1) !important;
        padding: 1px 0 !important;
        white-space: nowrap;
    }
    .leaflet-tooltip::before { display: none !important; }
    .star-label {
        color: #ffe888 !important;
        font-size: 10px !important;
        letter-spacing: 2px !important;
        text-shadow: 0 0 14px rgba(255,220,80,1), 0 0 3px rgba(0,0,0,1) !important;
    }
    /* Move-mode grid cell hover — applied via JS setStyle, kept here as reference */
    .grid-cell-hover { fill: rgba(80,160,220,0.20) !important; }
</style>
@endpush

@section('content')
<div class="row mb-2">
    <div class="col-md-6 offset-md-3 d-flex gap-2 flex-wrap">
        <a href="{{ route('galaxy.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Galaxis
        </a>
        <a href="#" id="toggleGridLayer" class="btn btn-secondary btn-sm">Raster an/aus</a>
        <a href="#" id="toggleSystemLayer" class="btn btn-secondary btn-sm">Planeten an/aus</a>
        <a href="#" id="toggleFleetsLayer" class="btn btn-secondary btn-sm">Flotten an/aus</a>
    </div>
</div>

{{-- Relative wrapper so the absolute-positioned command panel sits over the map --}}
<div style="position:relative;">

    <div id="galaxy-map"
         data-system-id="{{ $system->id }}"
         data-system-name="{{ $system->name ?? '' }}"
         data-x="{{ (int)($system->x ?? 0) }}"
         data-y="{{ (int)($system->y ?? 0) }}"
         data-bg="{{ $system->background_image_url ? '/img/' . $system->background_image_url : '' }}"
         data-objects="{{ json_encode($objects->values()) }}"
         data-colonies="{{ json_encode($colonies->values()) }}"
         data-config="{{ json_encode($config) }}"
         style="width:100%; height:calc(100vh - 130px);"></div>

    {{-- Fleet command panel — shown via JS when an own fleet marker is clicked --}}
    <div id="fleet-command-panel"
         style="display:none; position:absolute; top:80px; right:16px; z-index:1000; width:220px;">
        <div class="card bg-dark border-secondary">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="text-light small fw-bold" id="cmd-fleet-name"></span>
                <button type="button"
                        class="btn-close btn-close-white btn-sm"
                        onclick="deactivateFleet()"
                        aria-label="{{ __('galaxy.close_panel') }}"></button>
            </div>
            <div class="card-body p-2 d-flex flex-column gap-2">
                <button class="btn btn-sm btn-outline-info w-100" onclick="activateMoveMode()">
                    <i class="bi bi-cursor-fill"></i> {{ __('fleet.order_move') }}
                </button>
                <button class="btn btn-sm btn-outline-secondary w-100" onclick="submitHold()">
                    <i class="bi bi-pause-circle"></i> {{ __('fleet.order_hold') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Hidden form submitted by JS to POST fleet orders --}}
    <form id="fleet-order-form" method="POST" action="" style="display:none;">
        @csrf
        <input type="hidden" id="order-type"   name="order">
        <input type="hidden" id="order-dest-x" name="destination_x">
        <input type="hidden" id="order-dest-y" name="destination_y">
    </form>

</div>{{-- end relative wrapper --}}
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/galaxy.js') }}"></script>
@endpush
