@extends('layouts.colony')
@section('title', 'Techtree — Nouron')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/techtree-view.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/techtree-view.js') }}"></script>
@endpush

@section('content')
<script>window.__techtreeData = @json($pageData)</script>

<div class="techtree-page" x-data="techtreeView(window.__techtreeData)" x-cloak>

    {{-- Category toggles --}}
    <div class="techtree-toolbar">
        <button :class="{ active: visible.building }"  @click="toggle('building')">{{ __('techtree.types_buildings') }}</button>
        <button :class="{ active: visible.research }"  @click="toggle('research')">{{ __('techtree.types_researchs') }}</button>
        <button :class="{ active: visible.ship }"      @click="toggle('ship')">{{ __('techtree.types_ships') }}</button>
        <button :class="{ active: visible.personell }" @click="toggle('personell')">{{ __('techtree.types_personells') }}</button>
    </div>

    {{-- Scrollable grid + SVG overlay --}}
    <div class="techtree-grid-wrapper" x-ref="wrapper">
        <svg class="techtree-svg" x-ref="svg" aria-hidden="true"></svg>

        <div class="techtree-grid">
            @foreach(['building', 'research', 'ship', 'personell'] as $type)
                @foreach($pageData['categories'][$type] as $tech)
                <div class="tech-card tech-{{ $type }} status-{{ $tech['status'] }}"
                     id="tech-{{ $type }}-{{ $tech['id'] }}"
                     style="grid-row: {{ $tech['row'] + 1 }}; grid-column: {{ $tech['col'] + 1 }}"
                     @click="openDetail({{ json_encode($tech) }})">

                    <div class="tech-name">{{ $tech['name'] }}</div>

                    @if($tech['level'] > 0)
                    <div class="tech-level-badge">Lv {{ $tech['level'] }}{{ $tech['max_level'] ? ' / ' . $tech['max_level'] : '' }}</div>
                    @endif

                    @if($tech['required_desc'] && $tech['status'] === 'locked')
                    <div class="tech-required">{{ $tech['required_desc'] }}</div>
                    @endif

                    <div class="tech-status-chip chip-{{ $tech['status'] }}">
                        @if($tech['status'] === 'built') Lv {{ $tech['level'] }}
                        @elseif($tech['status'] === 'available') {{ __('techtree.status_available') }}
                        @else {{ __('techtree.status_locked') }}
                        @endif
                    </div>
                </div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Tech detail dialog --}}
    <dialog class="tech-detail" x-ref="detailDialog" @close="closeDetail()">
        <template x-if="selectedTech">
            <div>
                <h3 x-text="selectedTech.name"></h3>
                <div class="detail-meta">
                    <span><strong>{{ __('techtree.detail_type') }}:</strong> <span x-text="typeLabel(selectedTech.type)"></span></span>
                    <span><strong>{{ __('techtree.detail_status') }}:</strong> <span x-text="statusLabel(selectedTech)"></span></span>
                    <template x-if="selectedTech.level > 0">
                        <span><strong>{{ __('techtree.detail_level') }}:</strong>
                            <span x-text="selectedTech.level + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                        </span>
                    </template>
                    <template x-if="selectedTech.required_desc">
                        <span><strong>{{ __('techtree.detail_required') }}:</strong> <span x-text="selectedTech.required_desc"></span></span>
                    </template>
                </div>
                <button class="detail-close" @click="closeDetail()">{{ __('techtree.detail_close') }}</button>
            </div>
        </template>
    </dialog>

</div>
@endsection
