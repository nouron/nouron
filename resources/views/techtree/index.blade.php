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

<div class="techtree-page"
     x-data="techtreeView(window.__techtreeData)"
     x-cloak
     @touchstart.passive="onTouchStart($event)"
     @touchend.passive="onTouchEnd($event)">

    <div class="techtree-sections" x-ref="sectionsWrapper">
        <svg class="techtree-global-svg" x-ref="globalSvg" aria-hidden="true"></svg>

        @foreach($pageData['phases'] as $phaseNum => $phase)
        <section class="techtree-phase"
                 id="phase-{{ $phaseNum }}"
                 x-show="!isMobile || activePhase === {{ $phaseNum }}">
            <h2 class="phase-header">
                <span class="phase-label">Phase {{ $phaseNum }}</span>
                <span class="phase-cc">Kommandozentrale Lv{{ $phase['cc_level'] }}</span>
            </h2>
            <div class="tech-grid">

                @foreach($phase['items'] as $tech)
                <div class="tech-card tech-{{ $tech['type'] }} status-{{ $tech['status'] }}"
                     id="tech-{{ $tech['type'] }}-{{ $tech['id'] }}"
                     style="grid-column:{{ $tech['col'] }};grid-row:{{ $tech['row'] }}"
                     @click="openDetail({{ json_encode($tech) }})">
                    <span class="tech-name">{{ $tech['name'] }}</span>
                    <span class="tech-status-chip chip-{{ $tech['status'] }}">
                        @if($tech['status'] === 'built')Lv {{ $tech['level'] }}{{ $tech['max_level'] ? '/' . $tech['max_level'] : '' }}
                        @elseif($tech['status'] === 'available'){{ __('techtree.status_available') }}
                        @else{{ __('techtree.status_locked') }}
                        @endif
                    </span>
                    @if($tech['status'] === 'locked' && $tech['required_desc'])
                    <span class="tech-sub">&#128274; {{ $tech['required_desc'] }}</span>
                    @endif
                </div>
                @endforeach

            </div>
        </section>
        @endforeach

    </div>{{-- .techtree-sections --}}

    {{-- Mobile phase navigation (sticky bottom bar) --}}
    <div class="phase-nav" x-show="isMobile">
        <button class="phase-nav-arrow" @click="prevPhase()" :disabled="activePhase <= 1">&#8249;</button>
        <div class="phase-dots">
            @foreach($pageData['phases'] as $n => $unused)
            <button class="phase-dot"
                    :class="{ active: activePhase === {{ $n }} }"
                    @click="goToPhase({{ $n }})"></button>
            @endforeach
        </div>
        <button class="phase-nav-arrow" @click="nextPhase()" :disabled="activePhase >= 5">&#8250;</button>
    </div>

    {{-- Tech detail dialog --}}
    <dialog class="tech-detail" x-ref="detailDialog" @close="closeDetail()" @click.self="closeDetail()">
        <template x-if="selectedTech">
            <div class="detail-inner" :class="'detail-cat-' + selectedTech.type">

                {{-- Header row: badges + × --}}
                <div class="detail-head">
                    <div class="detail-badges">
                        <span class="detail-type-badge" x-text="typeLabel(selectedTech.type)"></span>
                        <span class="tech-status-chip" :class="'chip-' + selectedTech.status" x-text="statusLabel(selectedTech)"></span>
                    </div>
                    <button class="detail-x" @click="closeDetail()" aria-label="{{ __('techtree.detail_close') }}">&#215;</button>
                </div>

                {{-- Title --}}
                <h3 class="detail-title" x-text="selectedTech.name"></h3>

                {{-- Meta rows --}}
                <div class="detail-body">
                    <template x-if="selectedTech.level > 0">
                        <div class="detail-row">
                            <span class="detail-row-label">{{ __('techtree.detail_level') }}</span>
                            <span x-text="selectedTech.level + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                        </div>
                    </template>
                    <template x-if="selectedTech.required_desc">
                        <div class="detail-row">
                            <span class="detail-row-label">{{ __('techtree.detail_required') }}</span>
                            <span x-text="selectedTech.required_desc"></span>
                        </div>
                    </template>
                </div>

                <button class="detail-close" @click="closeDetail()">{{ __('techtree.detail_close') }}</button>
            </div>
        </template>
    </dialog>

</div>{{-- .techtree-page --}}
@endsection
