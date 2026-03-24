@extends('layouts.app')
@section('title', 'Techtree — Nouron')

@section('content')
<div id="colony">
    <div id="visualTechtree">
        {{-- Hidden storage used by techtree.js to read the active tech ID --}}
        <div id="storage-tech-id" class="d-none"></div>
        <div id="storage" class="d-none"></div>

        {{-- SVG overlay for requirement lines; pointer-events:none keeps clicks on buttons --}}
        <svg class="grid-svg" id="grid-svg" xmlns="http://www.w3.org/2000/svg" fill="none">
            <rect x="0" y="0" height="10" width="10" stroke="black" />
            <rect x="100%" y="100%" height="10" width="10" stroke="black" />
        </svg>

        {{-- 16 columns × 6 rows grid; techtree.js moves .techdata spans into matching cells --}}
        <div class="grid">
            @for($row = 1; $row <= 6; $row++)
            <div class="row">
                @for($col = 1; $col <= 16; $col++)
                <div class="col-md-2">
                    <span id="grid-{{ $row }}-{{ $col }}" class="grid-cell"></span>
                </div>
                @endfor
            </div>
            @endfor
        </div>

        {{-- Hidden techdata source elements; JS reads these and copies them into the grid --}}
        <div class="d-none">

            @foreach(['building', 'research', 'ship', 'personell'] as $type)
            @foreach($techtree[$type] as $id => $tech)
            <span class="techdata" id="techsource-{{ $tech['row'] ?? 1 }}-{{ $tech['column'] ?? 1 }}">
                <a id="{{ $type }}-{{ $id }}"
                   class="btn btn-block technology {{ $type }}{{ ($tech['level'] ?? 0) == 0 ? ' notexists' : '' }}"
                   href="{{ route('techtree.technology', [$type, $id]) }}"
                   data-bs-toggle="modal"
                   data-bs-target="#{{ $type }}Modal-{{ $id }}">
                    {{ $tech['name'] }} {{ ($tech['level'] ?? 0) > 0 ? $tech['level'] : '' }}
                </a>
                {{-- JSON payload used by draw_requirements() to draw SVG dependency lines --}}
                <span class="d-none data">{{ json_encode($tech) }}</span>
            </span>
            @endforeach
            @endforeach

            {{-- Requirement data spans: techId-requiredTechId-requiredLevel-currentLevel --}}
            @foreach(['building', 'research', 'ship', 'personell'] as $type)
            @foreach($techtree[$type] as $id => $tech)
            @if(!empty($tech['required_building_id']))
            <span class="requirementsdata {{ $type }}">{{ $id }}-{{ $tech['required_building_id'] }}-{{ $tech['required_building_level'] ?? 1 }}-{{ $techtree['building'][$tech['required_building_id']]['level'] ?? 0 }}</span>
            @endif
            @endforeach
            @endforeach

        </div>

        {{-- One modal shell per tech; content is loaded via AJAX on open --}}
        @foreach(['building', 'research', 'ship', 'personell'] as $type)
        @foreach($techtree[$type] as $id => $tech)
        <div id="{{ $type }}Modal-{{ $id }}" class="techModal modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content"></div>
            </div>
        </div>
        @endforeach
        @endforeach

    </div>{{-- #visualTechtree --}}
</div>{{-- #colony --}}
@endsection
