@extends('layouts.app')

@section('title', 'Galaxis — Nouron')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .leaflet-container { background: transparent !important; }
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
    .galaxy-star { cursor: pointer; }
</style>
@endpush

@section('content')
<div id="galaxy-overview"
     data-systems="{{ json_encode($systems->values()) }}"
     data-config="{{ json_encode($config) }}"
     style="width:100%; height:calc(100vh - 130px);"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/galaxy.js') }}"></script>
@endpush
