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

    {{-- Category toggle toolbar --}}
    <div class="techtree-toolbar">
        <button :class="{ active: visible.building }"  @click="toggle('building')">{{ __('techtree.types_buildings') }}</button>
        <button :class="{ active: visible.research }"  @click="toggle('research')">{{ __('techtree.types_researchs') }}</button>
        <button :class="{ active: visible.ship }"      @click="toggle('ship')">{{ __('techtree.types_ships') }}</button>
        <button :class="{ active: visible.personell }" @click="toggle('personell')">{{ __('techtree.types_personells') }}</button>
    </div>

    <div class="techtree-sections" x-ref="sectionsWrapper">
        <svg class="techtree-global-svg" x-ref="globalSvg" aria-hidden="true"></svg>

        {{-- Unified grid — all categories share one coordinate space --}}
        <div class="tech-grid">

            @foreach($pageData['categories']['building'] as $tech)
            <div class="tech-card tech-building status-{{ $tech['status'] }}"
                 id="tech-building-{{ $tech['id'] }}"
                 style="grid-column:{{ $tech['col'] + 1 }};grid-row:{{ $tech['row'] + 1 }}"
                 x-show="visible.building"
                 @click="openDetail({{ json_encode($tech) }})">
                <span class="tech-name">{{ $tech['name'] }}</span>
                <span class="tech-status-chip chip-{{ $tech['status'] }}">
                    @if($tech['status'] === 'built')Lv {{ $tech['level'] }}{{ $tech['max_level'] ? '/' . $tech['max_level'] : '' }}
                    @elseif($tech['status'] === 'available'){{ __('techtree.status_available') }}
                    @else{{ __('techtree.status_locked') }}
                    @endif
                </span>
                @if($tech['status'] !== 'built' && $tech['required_desc'])
                <span class="tech-sub">@if($tech['status'] === 'locked')&#128274; @endif{{ $tech['required_desc'] }}</span>
                @endif
            </div>
            @endforeach

            @foreach($pageData['categories']['research'] as $tech)
            <div class="tech-card tech-research status-{{ $tech['status'] }}"
                 id="tech-research-{{ $tech['id'] }}"
                 style="grid-column:{{ $tech['col'] + 1 }};grid-row:{{ $tech['row'] + 1 }}"
                 x-show="visible.research"
                 @click="openDetail({{ json_encode($tech) }})">
                <span class="tech-name">{{ $tech['name'] }}</span>
                <span class="tech-status-chip chip-{{ $tech['status'] }}">
                    @if($tech['status'] === 'built')Lv {{ $tech['level'] }}{{ $tech['max_level'] ? '/' . $tech['max_level'] : '' }}
                    @elseif($tech['status'] === 'available'){{ __('techtree.status_available') }}
                    @else{{ __('techtree.status_locked') }}
                    @endif
                </span>
                @if($tech['status'] !== 'built' && $tech['required_desc'])
                <span class="tech-sub">@if($tech['status'] === 'locked')&#128274; @endif{{ $tech['required_desc'] }}</span>
                @endif
            </div>
            @endforeach

            @foreach($pageData['categories']['ship'] as $tech)
            <div class="tech-card tech-ship status-{{ $tech['status'] }}"
                 id="tech-ship-{{ $tech['id'] }}"
                 style="grid-column:{{ $tech['col'] + 1 }};grid-row:{{ $tech['row'] + 1 }}"
                 x-show="visible.ship"
                 @click="openDetail({{ json_encode($tech) }})">
                <span class="tech-name">{{ $tech['name'] }}</span>
                <span class="tech-status-chip chip-{{ $tech['status'] }}">
                    @if($tech['status'] === 'built')Lv {{ $tech['level'] }}{{ $tech['max_level'] ? '/' . $tech['max_level'] : '' }}
                    @elseif($tech['status'] === 'available'){{ __('techtree.status_available') }}
                    @else{{ __('techtree.status_locked') }}
                    @endif
                </span>
                @if($tech['status'] !== 'built' && $tech['required_desc'])
                <span class="tech-sub">@if($tech['status'] === 'locked')&#128274; @endif{{ $tech['required_desc'] }}</span>
                @endif
            </div>
            @endforeach

            @foreach($pageData['categories']['personell'] as $tech)
            <div class="tech-card tech-personell status-{{ $tech['status'] }}"
                 id="tech-personell-{{ $tech['id'] }}"
                 style="grid-column:{{ $tech['col'] + 1 }};grid-row:{{ $tech['row'] + 1 }}"
                 x-show="visible.personell"
                 @click="openDetail({{ json_encode($tech) }})">
                <span class="tech-name">{{ $tech['name'] }}</span>
                <span class="tech-status-chip chip-{{ $tech['status'] }}">
                    @if($tech['status'] === 'built')Lv {{ $tech['level'] }}{{ $tech['max_level'] ? '/' . $tech['max_level'] : '' }}
                    @elseif($tech['status'] === 'available'){{ __('techtree.status_available') }}
                    @else{{ __('techtree.status_locked') }}
                    @endif
                </span>
                @if($tech['status'] !== 'built' && $tech['required_desc'])
                <span class="tech-sub">@if($tech['status'] === 'locked')&#128274; @endif{{ $tech['required_desc'] }}</span>
                @endif
            </div>
            @endforeach

        </div>{{-- .tech-grid --}}

    </div>{{-- .techtree-sections --}}

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

</div>{{-- .techtree-page --}}
@endsection
