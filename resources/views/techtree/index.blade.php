@extends('layouts.app')
@section('title', 'Techtree — Nouron')

@section('content')
<div class="row mb-2">
    <div class="col-md-6 offset-md-3 d-flex gap-2 flex-wrap">
        <a href="#" id="toggleBuildings"  class="btn btn-secondary btn-sm">Gebäude an/aus</a>
        <a href="#" id="toggleResearches" class="btn btn-secondary btn-sm">Forschungen an/aus</a>
        <a href="#" id="toggleShips"      class="btn btn-secondary btn-sm">Schiffe an/aus</a>
        <a href="#" id="toggleAdvisors"   class="btn btn-secondary btn-sm">Berater an/aus</a>
    </div>
</div>
<div id="colony">
    <div id="visualTechtree">
        {{-- Hidden storage used by techtree.js --}}
        <div id="storage-tech-id" class="d-none"></div>
        <div id="storage" class="d-none"></div>

{{-- 16 rows × 6 cols grid; IDs match tech row/column from DB (0-based) --}}
        <div class="grid">
            @for($row = 0; $row <= 15; $row++)
            <div class="row">
                @for($col = 0; $col <= 5; $col++)
                <span class="col-2 grid-cell" id="grid-{{ $row }}-{{ $col }}"></span>
                @endfor
            </div>
            @endfor
        </div>

        {{-- Hidden techdata source elements; JS reads these and copies them into the grid --}}
        <div class="d-none">

            @foreach(['building', 'research', 'ship', 'personell'] as $type)
            @foreach($techtree[$type] as $id => $tech)
            <span class="techdata" id="techsource-{{ $tech['row'] }}-{{ $tech['column'] }}">
                <a id="{{ $type }}-{{ $id }}"
                   class="btn btn-block technology {{ $type }}{{ ($tech['level'] ?? 0) == 0 ? ' notexists' : '' }}"
                   href="{{ route('techtree.technology', [$type, $id]) }}"
                   data-bs-toggle="modal"
                   data-bs-target="#{{ $type }}Modal-{{ $id }}">
                    {{ __('techtree.' . $tech['name']) }}{{ ($tech['level'] ?? 0) > 0 ? ' ' . $tech['level'] : '' }}
                </a>
                <span class="d-none data">{{ json_encode($tech) }}</span>
            </span>
            @endforeach
            @endforeach

            {{-- Requirements data for SVG dependency lines --}}
            {{-- Buildings requiring other buildings --}}
            @foreach($techtree['building'] as $id => $tech)
            @if(!empty($tech['required_building_id']))
            <div class="requirementsdata building">{{ $id }}-{{ $tech['required_building_id'] }}-{{ $tech['required_building_level'] ?? 1 }}-{{ $techtree['building'][$tech['required_building_id']]['level'] ?? 0 }}</div>
            @endif
            @endforeach

            {{-- Researches requiring buildings --}}
            @foreach($techtree['research'] as $id => $tech)
            @if(!empty($tech['required_building_id']))
            <div class="requirementsdata research">{{ $id }}-{{ $tech['required_building_id'] }}-{{ $tech['required_building_level'] ?? 1 }}-{{ $techtree['building'][$tech['required_building_id']]['level'] ?? 0 }}</div>
            @endif
            @endforeach

            {{-- Ships requiring researches --}}
            @foreach($techtree['ship'] as $id => $tech)
            @if(!empty($tech['required_research_id']))
            <div class="requirementsdata ship">{{ $id }}-{{ $tech['required_research_id'] }}-{{ $tech['required_research_level'] ?? 1 }}-{{ $techtree['research'][$tech['required_research_id']]['level'] ?? 0 }}</div>
            @endif
            @endforeach

            {{-- Personell requiring buildings --}}
            @foreach($techtree['personell'] as $id => $tech)
            @if(!empty($tech['required_building_id']))
            <div class="requirementsdata personell">{{ $id }}-{{ $tech['required_building_id'] }}-{{ $tech['required_building_level'] ?? 1 }}-{{ $techtree['building'][$tech['required_building_id']]['level'] ?? 0 }}</div>
            @endif
            @endforeach

        </div>

        {{-- One modal shell per tech; content loaded via AJAX on open --}}
        @foreach(['building', 'research', 'ship', 'personell'] as $type)
        @foreach($techtree[$type] as $id => $tech)
        <div id="{{ $type }}Modal-{{ $id }}" class="techModal modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content"></div>
            </div>
        </div>
        @endforeach
        @endforeach

    </div>{{-- #visualTechtree --}}
</div>{{-- #colony --}}
@endsection

@push('scripts')
<script>$(document).ready(function(){ if ($('#colony').length > 0) { techtree.init(); } });</script>
@endpush
