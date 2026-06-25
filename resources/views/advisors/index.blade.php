@extends("layouts.colony")
@section("title", "Berater — Nouron")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/advisors.css") }}?v={{ filemtime(public_path("css/advisors.css")) }}">
@endpush

@push("scripts")
    <script src="{{ asset("js/advisors.js") }}?v={{ filemtime(public_path("js/advisors.js")) }}"></script>
@endpush

@section("content")
    <script>
        window.__advisorData = @json($pageData)
    </script>

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

            <button class="carousel-arrow" @click="prev()" :disabled="activeIndex === 0"
                aria-label="Vorheriger Berater">&#8249;</button>

            <div class="carousel-viewport" @touchstart.passive="onTouchStart($event)"
                @touchmove.passive="onTouchMove($event)" @touchend.passive="onTouchEnd($event)">

                <div class="carousel-track" :style="trackStyle()">

                    <template x-for="(slot, i) in slots" :key="slot.key">
                        <div class="advisor-card"
                            :class="{
                                'advisor-card--locked': slot.state === 'locked',
                                'advisor-card--current': i === activeIndex
                            }">

                            {{-- Portrait --}}
                            <div class="advisor-portrait"
                                :style="portraitImageUrl(slot.key) ?
                                    'background-image: url(' + portraitImageUrl(slot.key) + ')' :
                                    ''">
                                {{-- Placeholder SVG shown only when no portrait image is available --}}
                                <template x-if="!portraitImageUrl(slot.key)">
                                    <svg class="advisor-portrait-placeholder" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                                    </svg>
                                </template>
                            </div>

                            {{-- Info panel: name + stats in bottom section --}}
                            <div class="advisor-info">

                                <div class="advisor-name-row">
                                    <span class="advisor-name" x-text="slot.name"></span>
                                    <template x-if="slot.advisor !== null">
                                        <span class="rank-badge" :class="'rank-badge--' + slot.advisor.rank"
                                            x-text="slot.advisor.rank_name"></span>
                                    </template>
                                </div>

                                <template x-if="slot.advisor !== null">
                                    <span class="advisor-subtitle"
                                        x-text="apTypeLabel(slot.ap_type) + ' · ' + slot.advisor.ap_per_tick + ' AP/Tick'">
                                    </span>
                                </template>
                                <template x-if="slot.advisor === null && slot.state === 'empty'">
                                    <span class="advisor-subtitle" x-text="apTypeLabel(slot.ap_type) + ' · Vakant'"></span>
                                </template>
                                <template x-if="slot.state === 'locked'">
                                    <span class="advisor-subtitle">Gesperrt</span>
                                </template>

                                <div class="advisor-stats">

                                    {{-- ACTIVE or UNAVAILABLE --}}
                                    <template x-if="slot.advisor !== null">
                                        <div style="display:contents">

                                            <div class="stat-row">
                                                <span class="stat-label">Ticks</span>
                                                <span class="stat-value" x-text="slot.advisor.active_ticks"></span>
                                            </div>

                                            <div class="stat-row">
                                                <span class="stat-label">Unterhalt</span>
                                                <span class="stat-value" x-text="slot.advisor.upkeep + ' Cr/Tick'"></span>
                                            </div>

                                            <div class="advisor-progress-wrap">
                                                <div class="stat-row">
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

                                            <div class="advisor-card-footer">
                                                <span class="advisor-status"
                                                    :class="slot.advisor.is_unavailable ?
                                                        'advisor-status--unavailable' :
                                                        'advisor-status--active'"
                                                    x-text="slot.advisor.is_unavailable
                                                      ? ('Inaktiv bis T' + slot.advisor.unavailable_until_tick)
                                                      : 'Aktiv'">
                                                </span>
                                                <button class="btn-fire" @click="openFireDialog(slot)">Entlassen</button>
                                            </div>

                                        </div>
                                    </template>

                                    {{-- EMPTY --}}
                                    <template x-if="slot.advisor === null && slot.state === 'empty'">
                                        <div style="display:contents">

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

                                            <div class="advisor-card-footer">
                                                <span class="advisor-status advisor-status--empty">Vakant</span>
                                                <button class="btn-hire" @click="openHireDialog(slot)">Einstellen</button>
                                            </div>

                                        </div>
                                    </template>

                                    {{-- LOCKED --}}
                                    <template x-if="slot.state === 'locked'">
                                        <div
                                            style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:0.5rem;color:#bbb;text-align:center;padding:0.5rem 0">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <rect x="3" y="11" width="18" height="11" rx="2"
                                                    ry="2" />
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                            </svg>
                                            <span class="advisor-status advisor-status--locked">Gesperrt</span>
                                            <span style="font-size:0.7rem;color:#bbb">
                                                CC Level <strong x-text="slot.cc_required"></strong> erforderlich
                                            </span>
                                        </div>
                                    </template>

                                </div>{{-- /.advisor-stats --}}

                            </div>{{-- /.advisor-info --}}
                        </div>{{-- /.advisor-card --}}
                    </template>

                </div>{{-- /.carousel-track --}}
            </div>{{-- /.carousel-viewport --}}

            <button class="carousel-arrow" @click="next()" :disabled="activeIndex >= slots.length - 1"
                aria-label="Nächster Berater">&#8250;</button>

        </div>{{-- /.carousel-wrapper --}}

        {{-- Pagination dots (mobile only, hidden on desktop via CSS) --}}
        <div class="carousel-dots">
            <template x-for="(slot, i) in slots" :key="'dot-' + i">
                <button class="carousel-dot" :class="{ 'carousel-dot--active': i === activeIndex }" @click="goTo(i)"
                    :aria-label="slot.name"></button>
            </template>
        </div>

        {{-- ── Hire confirmation dialog ─────────────────────────────────────────── --}}
        <dialog class="advisor-dialog" x-ref="hireDialog" @close="closeDialogs()">
            <h3>{{ __("advisors.dialog_hire_title") }}</h3>
            <template x-if="dialogSlot">
                <div>
                    <div class="dialog-advisor">
                        <div class="dialog-portrait"
                            :style="portraitImageUrl(dialogSlot.key) ?
                                'background-image: url(' + portraitImageUrl(dialogSlot.key) + ')' :
                                ''">
                        </div>
                        <div class="dialog-advisor-info">
                            <div class="dialog-advisor-name">
                                <strong x-text="dialogSlot.name"></strong>
                                <span class="dialog-rank-badge">{{ __("advisors.dialog_rank_junior") }}</span>
                            </div>
                            <p class="dialog-advisor-desc" x-text="dialogSlot.desc"></p>
                        </div>
                    </div>

                    <dl class="dialog-stats">
                        <div class="dialog-stat">
                            <dt x-text="apTypeLabel(dialogSlot.ap_type)"></dt>
                            <dd class="dialog-stat-positive"
                                x-text="'+' + dialogSlot.junior_ap + ' {{ __("advisors.dialog_ap_label") }}'"></dd>
                        </div>
                        <div class="dialog-stat">
                            <dt>{{ __("advisors.dialog_cost_once") }}</dt>
                            <dd x-text="dialogSlot.hire_cost + ' Cr'"></dd>
                        </div>
                        <div class="dialog-stat">
                            <dt>{{ __("advisors.dialog_upkeep") }}</dt>
                            <dd x-text="dialogSlot.junior_upkeep + ' {{ __("advisors.dialog_per_sol") }}'"></dd>
                        </div>
                    </dl>

                    <template x-if="dialogSlot.building_warning">
                        <div class="dialog-warning" x-text="dialogSlot.building_warning"></div>
                    </template>

                    <div class="dialog-error" x-text="errorMsg"></div>
                    <div class="dialog-actions">
                        <button class="btn-cancel" @click="closeDialogs()">{{ __("advisors.dialog_cancel") }}</button>
                        <button class="btn-confirm-hire" @click="doHire()">{{ __("advisors.dialog_hire") }}</button>
                    </div>
                </div>
            </template>
        </dialog>

        {{-- ── Fire confirmation dialog ─────────────────────────────────────────── --}}
        <dialog class="advisor-dialog" x-ref="fireDialog" @close="closeDialogs()">
            <h3>{{ __("advisors.dialog_fire_title") }}</h3>
            <template x-if="dialogSlot">
                <div>
                    <p class="dialog-cost">
                        <strong x-text="dialogSlot.name"></strong> {{ __("advisors.dialog_fire_confirm") }}
                    </p>
                    <div class="dialog-error" x-text="errorMsg"></div>
                    <div class="dialog-actions">
                        <button class="btn-cancel" @click="closeDialogs()">{{ __("advisors.dialog_cancel") }}</button>
                        <button class="btn-confirm-fire" @click="doFire()">{{ __("advisors.dialog_fire") }}</button>
                    </div>
                </div>
            </template>
        </dialog>

    </div>{{-- /.advisors-page --}}
@endsection
