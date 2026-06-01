@extends('layouts.app')

@section('title', ($system->name ?? 'System') . ' — Nouron')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/galaxy.css') }}">
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
         data-objects='@json($objects->values())'
         data-colonies='@json($colonies->values())'
         data-config='@json($config)'
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
