@extends('layouts.app')

@section('title', 'Galaxis — Nouron')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('css/galaxy.css') }}">
@endpush

@section('content')
<div id="galaxy-overview"
     data-systems='@json($systems->values())'
     data-config='@json($config)'
     style="width:100%; height:calc(100vh - 130px);"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/galaxy.js') }}"></script>
@endpush
