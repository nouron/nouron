@extends('layouts.app')
@section('title', 'Techtree')
@section('content')
<div class="container-fluid">
    <h2>Techtree</h2>
    @foreach(['building','research','ship','personell'] as $type)
    <h4>{{ ucfirst($type) }}</h4>
    <div class="row mb-3">
        @foreach($techtree[$type] as $id => $tech)
        <div class="col-2 mb-1">
            <button class="btn btn-sm {{ ($tech['level'] ?? 0) > 0 ? 'btn-success' : 'btn-outline-secondary' }} w-100 tech-btn"
                data-type="{{ $type }}" data-id="{{ $id }}"
                title="{{ $tech['name'] }}">
                {{ $tech['name'] }}<br><small>Lv {{ $tech['level'] ?? 0 }}</small>
            </button>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endsection
