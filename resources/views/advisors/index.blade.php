@extends('layouts.colony')
@section('title', 'Berater — Nouron')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/advisors.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/advisors.js') }}"></script>
@endpush

@section('content')
<script>window.__advisorData = @json($pageData)</script>

<div class="advisors-page" x-data="advisorCarousel(window.__advisorData)" x-cloak>

    {{-- ── Header bar ──────────────────────────────────────────────────────── --}}
    <div class="advisors-header">
        <h2>Berater</h2>

        {{-- AP type chips — one per slot showing ap_type label --}}
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
            <template x-for="slot in slots" :key="'chip-' + slot.key">
                <span class="ap-chip" x-show="slot.state === 'active'">
                    <strong x-text="apTypeLabel(slot.ap_type)"></strong>
                    <span x-text="slot.advisor ? slot.advisor.ap_per_tick + ' AP' : ''"></span>
                </span>
            </template>
        </div>

        <span class="slot-badge">
            Slots: <strong x-text="slotInfo.used + '/' + slotInfo.max"></strong>
            <span x-text="'(CC Lv' + slotInfo.cc_level + ')'"></span>
        </span>
    </div>

    {{-- ── Carousel ─────────────────────────────────────────────────────────── --}}
    <div class="carousel-wrapper">

        <button class="carousel-arrow"
                @click="prev()"
                :disabled="activeIndex === 0"
                aria-label="Vorheriger Berater">&#8249;</button>

        <div class="carousel-viewport"
             @touchstart.passive="onTouchStart($event)"
             @touchend.passive="onTouchEnd($event)">

            <div class="carousel-track" :style="trackStyle()">

                <template x-for="(slot, i) in slots" :key="slot.key">
                    <div class="advisor-card"
                         :class="{
                             'advisor-card--locked':  slot.state === 'locked',
                             'advisor-card--current': i === activeIndex
                         }">

                        {{-- Portrait area --}}
                        <div class="advisor-portrait"
                             :style="portraitImageUrl(slot.key) ? 'background-image: url(' + portraitImageUrl(slot.key) + ')' : ''"
                             :data-initials="portraitInitials(slot.key)">
                            <div class="advisor-type-label" x-text="slot.name"></div>
                            <div class="advisor-ap-chip">
                                <strong x-text="apTypeLabel(slot.ap_type)"></strong>
                            </div>
                        </div>

                        {{-- Stats / state section --}}
                        <div class="advisor-stats">

                            {{-- ACTIVE or UNAVAILABLE: advisor object is present --}}
                            <template x-if="slot.advisor !== null">
                                <div style="display:flex;flex-direction:column;gap:0.4rem;height:100%">

                                    <div class="stat-row">
                                        <span class="stat-label">Rang</span>
                                        <span class="rank-badge"
                                              :class="'rank-badge--' + slot.advisor.rank"
                                              x-text="slot.advisor.rank_name"></span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">AP/Tick</span>
                                        <span class="stat-value" x-text="slot.advisor.ap_per_tick"></span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Ticks</span>
                                        <span class="stat-value" x-text="slot.advisor.active_ticks"></span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Unterhalt</span>
                                        <span class="stat-value" x-text="slot.advisor.upkeep + ' Cr/Tick'"></span>
                                    </div>

                                    {{-- Rank advancement progress --}}
                                    <div>
                                        <div class="stat-row" style="margin-bottom:2px">
                                            <span class="stat-label">Aufstieg</span>
                                            <span class="stat-label"
                                                  x-text="slot.advisor.is_max_rank
                                                      ? 'Max'
                                                      : (slot.advisor.active_ticks + '/' + slot.advisor.next_rank_ticks)">
                                            </span>
                                        </div>
                                        <div class="advisor-progress">
                                            <div class="advisor-progress-fill"
                                                 :style="{ width: slot.advisor.progress_pct + '%' }"></div>
                                        </div>
                                    </div>

                                    {{-- Status chip + fire button --}}
                                    <div class="stat-row" style="margin-top:auto">
                                        <span class="advisor-status"
                                              :class="slot.advisor.is_unavailable
                                                  ? 'advisor-status--unavailable'
                                                  : 'advisor-status--active'"
                                              x-text="slot.advisor.is_unavailable
                                                  ? ('Inaktiv bis T' + slot.advisor.unavailable_until_tick)
                                                  : 'Aktiv'">
                                        </span>
                                        <button class="btn-fire" @click="openFireDialog(slot)">Entlassen</button>
                                    </div>

                                </div>
                            </template>

                            {{-- EMPTY: slot is available, no advisor hired yet --}}
                            <template x-if="slot.advisor === null && slot.state === 'empty'">
                                <div style="display:flex;flex-direction:column;gap:0.5rem;height:100%">

                                    <span class="advisor-status advisor-status--empty">Vakant</span>

                                    <div class="stat-row">
                                        <span class="stat-label">Einstellungskosten</span>
                                        <span class="stat-value" x-text="slot.hire_cost + ' Cr'"></span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">AP/Tick (Junior)</span>
                                        <span class="stat-value">4</span>
                                    </div>

                                    <div class="stat-row">
                                        <span class="stat-label">Unterhalt</span>
                                        <span class="stat-value" x-text="juniorUpkeep + ' Cr/Tick'"></span>
                                    </div>

                                    <button class="btn-hire"
                                            style="margin-top:auto"
                                            @click="openHireDialog(slot)">Einstellen</button>

                                </div>
                            </template>

                            {{-- LOCKED: CC level too low to unlock this slot --}}
                            <template x-if="slot.state === 'locked'">
                                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:0.4rem;color:#aaa;text-align:center">
                                    <span style="font-size:1.5rem">&#128274;</span>
                                    <span class="advisor-status advisor-status--locked">Gesperrt</span>
                                    <span style="font-size:0.72rem">
                                        CC Level <strong x-text="slot.cc_required"></strong> erforderlich
                                    </span>
                                </div>
                            </template>

                        </div>{{-- /.advisor-stats --}}
                    </div>{{-- /.advisor-card --}}
                </template>

            </div>{{-- /.carousel-track --}}
        </div>{{-- /.carousel-viewport --}}

        <button class="carousel-arrow"
                @click="next()"
                :disabled="activeIndex >= slots.length - 1"
                aria-label="Nächster Berater">&#8250;</button>

    </div>{{-- /.carousel-wrapper --}}

    {{-- Pagination dots (mobile only, hidden on desktop via CSS) --}}
    <div class="carousel-dots">
        <template x-for="(slot, i) in slots" :key="'dot-' + i">
            <button class="carousel-dot"
                    :class="{ 'carousel-dot--active': i === activeIndex }"
                    @click="goTo(i)"
                    :aria-label="slot.name"></button>
        </template>
    </div>

    {{-- ── Hire confirmation dialog ─────────────────────────────────────────── --}}
    <dialog class="advisor-dialog" x-ref="hireDialog" @close="closeDialogs()">
        <h3>Berater einstellen</h3>
        <template x-if="dialogSlot">
            <div>
                <p class="dialog-cost">
                    <strong x-text="dialogSlot.name"></strong> &middot; Junior &middot; 4 AP/Tick<br>
                    Kosten: <strong x-text="dialogSlot.hire_cost + ' Cr'"></strong>
                </p>
                <div class="dialog-error" x-text="errorMsg"></div>
                <div class="dialog-actions">
                    <button class="btn-cancel" @click="closeDialogs()">Abbrechen</button>
                    <button class="btn-confirm-hire" @click="doHire()">Einstellen</button>
                </div>
            </div>
        </template>
    </dialog>

    {{-- ── Fire confirmation dialog ─────────────────────────────────────────── --}}
    <dialog class="advisor-dialog" x-ref="fireDialog" @close="closeDialogs()">
        <h3>Berater entlassen</h3>
        <template x-if="dialogSlot">
            <div>
                <p class="dialog-cost">
                    <strong x-text="dialogSlot.name"></strong> wirklich entlassen?
                </p>
                <div class="dialog-error" x-text="errorMsg"></div>
                <div class="dialog-actions">
                    <button class="btn-cancel" @click="closeDialogs()">Abbrechen</button>
                    <button class="btn-confirm-fire" @click="doFire()">Entlassen</button>
                </div>
            </div>
        </template>
    </dialog>

</div>{{-- /.advisors-page --}}
@endsection
