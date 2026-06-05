@extends('layouts.colony')
@section('title', __('nexusdb.page_title') . ' — Nouron')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/nexusdb.css') }}">
@endpush

@section('content')
<div class="nexusdb-page">
    <p class="nexusdb-intro">{{ __('nexusdb.page_subtitle') }}</p>

    <div class="nexusdb-accordion">
        @foreach(['supply','trust','sol','ap','decay','repair','nexus','colonists'] as $concept)
        <details class="nexusdb-item">
            <summary class="nexusdb-item-title">{{ __('nexusdb.concept_' . $concept . '_title') }}</summary>
            <p class="nexusdb-item-body">{{ __('nexusdb.concept_' . $concept . '_body') }}</p>
        </details>
        @endforeach
    </div>
</div>
@endsection
