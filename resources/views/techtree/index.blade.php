@extends("layouts.colony")
@section("title", "Techtree — Nouron")

@push("styles")
    <link rel="stylesheet"
        href="{{ asset("css/techtree-view.css") }}?v={{ filemtime(public_path("css/techtree-view.css")) }}">
@endpush

@push("scripts")
    <script src="{{ asset("js/techtree-view.js") }}"></script>
@endpush

@section("content")
    <script>
        window.__techtreeData = @json($pageData)
    </script>

    <div class="techtree-page" x-data="techtreeView(window.__techtreeData)" x-cloak data-hint-rank="{{ $activeHintRank }}"
        @touchstart.passive="onTouchStart($event)" @touchend.passive="onTouchEnd($event)">

        <div class="techtree-toast" :class="`techtree-toast--${toastType}`" x-show="toastVisible" x-transition
            x-text="toastMessage" aria-live="polite" role="status"></div>

        <div class="techtree-sections" x-ref="sectionsWrapper">
            <svg class="techtree-global-svg" x-ref="globalSvg" aria-hidden="true"></svg>

            @foreach ($pageData["phases"] as $phaseNum => $phase)
                <section class="techtree-phase" id="phase-{{ $phaseNum }}"
                    x-show="!isMobile || activePhase === {{ $phaseNum }}">
                    <h2 class="phase-header">
                        <span class="phase-label">Phase {{ $phaseNum }}</span>
                        <span class="phase-cc">Kommandozentrale Lv{{ $phase["cc_level"] }}</span>
                    </h2>
                    <div class="tech-grid">

                        @foreach ($phase["items"] as $tech)
                            <div class="tech-card tech-{{ $tech["type"] }} status-{{ $tech["status"] }}"
                                id="tech-{{ $tech["type"] }}-{{ $tech["id"] }}"
                                style="grid-column:{{ $tech["col"] }};grid-row:{{ $tech["row"] }}"
                                @click="openDetail({{ json_encode($tech) }})"
                                @mouseenter="onCardEnter({{ json_encode($tech) }})" @mouseleave="onCardLeave()">
                                <span class="tech-name">{{ $tech["name"] }}</span>
                                <span class="tech-status-chip chip-{{ $tech["status"] }}">
                                    @if ($tech["type"] === "personell")
                                        @if ($tech["status"] === "built")
                                            {{ __("techtree.advisor_hired") }}
                                        @elseif($tech["status"] === "available")
                                            {{ __("techtree.advisor_available") }}
                                            @else{{ __("techtree.advisor_locked") }}
                                        @endif
                                    @elseif($tech["type"] === "ship")
                                        @if ($tech["status"] === "built")
                                            {{ $tech["level"] }}{{ $tech["hangar_cap"] ? " / " . $tech["hangar_cap"] : "" }}
                                        @elseif($tech["status"] === "available")
                                            {{ __("techtree.status_available") }}
                                            @else{{ __("techtree.status_locked") }}
                                        @endif
                                    @elseif($tech["is_instanced"] ?? false)
                                        @if ($tech["max_level"] === 1)
                                            @if ($tech["instance_count"] > 0)
                                                {{ __("techtree.advisor_placed") }}
                                                @else{{ __("techtree.advisor_not_placed") }}
                                            @endif
                                        @elseif($tech["status"] === "built")
                                            {{ $tech["instance_count"] }}@if ($tech["max_level"])
                                                / {{ $tech["max_level"] }}
                                            @endif
                                        @elseif($tech["status"] === "available")
                                            {{ __("techtree.status_available") }}
                                            @else{{ __("techtree.status_locked") }}
                                        @endif
                                    @else
                                        @if ($tech["status"] === "built")
                                            Lv
                                            {{ $tech["level"] }}{{ $tech["max_level"] ? "/" . $tech["max_level"] : "" }}
                                        @elseif($tech["status"] === "available")
                                            {{ __("techtree.status_available") }}
                                            @else{{ __("techtree.status_locked") }}
                                        @endif
                                    @endif
                                </span>
                                @if ($tech["status"] === "locked" && $tech["required_desc"])
                                    <span class="tech-sub">&#128274; {{ $tech["required_desc"] }}</span>
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
                @foreach ($pageData["phases"] as $n => $unused)
                    <button class="phase-dot" :class="{ active: activePhase === {{ $n }} }"
                        @click="goToPhase({{ $n }})"></button>
                @endforeach
            </div>
            <button class="phase-nav-arrow" @click="nextPhase()" :disabled="activePhase >= 5">&#8250;</button>
        </div>

        {{-- Tech detail panel (sidebar on desktop, bottom sheet on mobile) --}}
        <div class="tech-panel-backdrop" x-show="selectedTech" @click="closeDetail()" x-cloak></div>
        <aside class="tech-panel" x-show="selectedTech" x-cloak x-transition:enter-start="tech-panel-hidden"
            x-transition:enter-end="tech-panel-visible" x-transition:leave-start="tech-panel-visible"
            x-transition:leave-end="tech-panel-hidden">
            <template x-if="selectedTech">
                <div class="detail-inner" :class="'detail-cat-' + selectedTech.type">

                    {{-- Header row: badges + × --}}
                    <div class="detail-head">
                        <div class="detail-badges">
                            <span class="detail-type-badge" x-text="typeLabel(selectedTech.type)"></span>
                            {{-- Hidden when the compact .detail-subhead level indicator below
                             the title already shows the same value (building/research at
                             level > 0) — avoids showing "Lv 1" twice. --}}
                            <span class="tech-status-chip" :class="'chip-' + selectedTech.status"
                                x-show="!((selectedTech.type === 'building' && !selectedTech.is_instanced && selectedTech.level > 0) ||
                                    (selectedTech.type === 'research' && selectedTech.level > 0))"
                                x-text="statusLabel(selectedTech)"></span>
                        </div>
                        <button class="detail-x" @click="closeDetail()"
                            aria-label="{{ __("techtree.detail_close") }}">&#215;</button>
                    </div>

                    {{-- Title --}}
                    <h3 class="detail-title" x-text="selectedTech.name"></h3>

                    {{-- Compact level indicator directly under the title (Owner-Fund
                     2026-09-02: LEVEL sat buried further down in the body as an
                     oversized label+value row — moved up here and shrunk to a
                     subheader-sized detail, right where it belongs next to the name). --}}
                    <template
                        x-if="(selectedTech.type === 'building' && !selectedTech.is_instanced && selectedTech.level > 0) ||
                            (selectedTech.type === 'research' && selectedTech.level > 0)">
                        <div class="detail-subhead">
                            <span class="detail-subhead-label">{{ __("techtree.detail_level") }}</span>
                            <span class="detail-subhead-value"
                                x-text="selectedTech.level + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                        </div>
                    </template>

                    {{-- Building image (only for building-type items with a resolved image_slug).
                     show_header:false because the <h3 detail-title> above already renders the name. --}}
                    @include("partials.building-detail", [
                        "expr" => "selectedTech",
                        "name_field" => "name",
                        "show_header" => false,
                    ])

                    {{-- Meta rows --}}
                    <div class="detail-body">

                        {{-- ADVISOR: description (from partials.building-detail above), required list, link to Berater screen.
                         Status/AP-type/hire-cost dropped here (Owner-Playtest-Fund 2026-09-04): status is already
                         shown by the header chip, and AP-type/hire-cost belong on the Berater screen itself, not
                         this popup — single-pool consolidation made "AP-type" meaningless anyway (see Fix 1). --}}
                        <template x-if="selectedTech.type === 'personell'">
                            <div>
                                <template x-if="selectedTech.required_list && selectedTech.required_list.length > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(part, idx) in selectedTech.required_list"
                                                :key="idx">
                                                <li>
                                                    <span class="res-chip res-chip--neutral" x-text="part"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                <a href="{{ route("advisors.index") }}" class="detail-cta-link">
                                    {{ __("techtree.detail_advisor_link") }} &rarr;
                                </a>
                            </div>
                        </template>

                        {{-- BUILDING --}}
                        <template x-if="selectedTech.type === 'building'">
                            <div>
                                {{-- Level moved to .detail-subhead right under the title,
                                 see above — kept only the instanced-count row here since
                                 that's a different metric (built count vs. level). --}}
                                <template x-if="selectedTech.is_instanced && selectedTech.instance_count > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_instances") }}</span>
                                        <span
                                            x-text="selectedTech.instance_count + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.required_list && selectedTech.required_list.length > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(part, idx) in selectedTech.required_list"
                                                :key="idx">
                                                <li>
                                                    <span class="res-chip res-chip--neutral" x-text="part"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                {{-- What the CURRENT level already delivers (Owner-Fund
                                 2026-09-02: sidebar only ever showed the next level's
                                 effect, never the active one) — see
                                 BuildingUnlockService::unlocksAtLevel() called at the
                                 current level instead of level+1. --}}
                                <template
                                    x-if="selectedTech.effects_current_level && selectedTech.effects_current_level.length > 0">
                                    <div class="detail-row">
                                        <span
                                            class="detail-row-label">{{ __("techtree.detail_effects_current_level") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(line, idx) in selectedTech.effects_current_level"
                                                :key="idx">
                                                <li>
                                                    <template x-if="line.chip">
                                                        <span :class="'res-chip res-' + line.chip.cls">
                                                            <span class="res-abbr" x-text="line.chip.abbr"></span>
                                                            <span class="res-amount" x-text="line.chip.value"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="!line.chip">
                                                        <span class="res-chip res-chip--neutral"
                                                            x-text="line.text"></span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                {{-- Reverse of Voraussetzung: what the NEXT level unlocks
                                 (Owner-Playtest-Fund 2026-08-31, e.g. "Hangar Lv2 →
                                 Frachter") — derived from existing gate data, see
                                 BuildingUnlockService. --}}
                                <template
                                    x-if="selectedTech.unlocks_next_level && selectedTech.unlocks_next_level.length > 0">
                                    <div class="detail-row">
                                        <span
                                            class="detail-row-label">{{ __("techtree.detail_effects_next_level") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(line, idx) in selectedTech.unlocks_next_level"
                                                :key="idx">
                                                <li>
                                                    <template x-if="line.chip">
                                                        <span :class="'res-chip res-' + line.chip.cls">
                                                            <span class="res-abbr" x-text="line.chip.abbr"></span>
                                                            <span class="res-amount" x-text="line.chip.value"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="!line.chip">
                                                        <span class="res-chip res-chip--neutral"
                                                            x-text="line.text"></span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                {{-- Colony link: opens build mode with this building pre-selected --}}
                                <a :href="'/colony/view?build=' + selectedTech.id" class="detail-cta-link">
                                    {{ __("techtree.detail_colony_link") }} &rarr;
                                </a>
                            </div>
                        </template>

                        {{-- RESEARCH / KNOWLEDGE --}}
                        <template x-if="selectedTech.type === 'research'">
                            <div>
                                {{-- Level moved to .detail-subhead right under the title,
                                 see above. --}}
                                <template x-if="selectedTech.required_list && selectedTech.required_list.length > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(part, idx) in selectedTech.required_list"
                                                :key="idx">
                                                <li>
                                                    <span class="res-chip res-chip--neutral" x-text="part"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                {{-- What the CURRENT level's effect curve already delivers
                                 (Owner-Fund 2026-09-02: sidebar only ever showed the next
                                 level's effect, never the active one) — see
                                 KnowledgeEffectDescriptionService, called at the current
                                 level instead of level+1. --}}
                                <template
                                    x-if="selectedTech.effects_current_level && selectedTech.effects_current_level.length > 0">
                                    <div class="detail-row">
                                        <span
                                            class="detail-row-label">{{ __("techtree.detail_effects_current_level") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(line, idx) in selectedTech.effects_current_level"
                                                :key="idx">
                                                <li>
                                                    <template x-if="line.chip">
                                                        <span :class="'res-chip res-' + line.chip.cls">
                                                            <span class="res-abbr" x-text="line.chip.abbr"></span>
                                                            <span class="res-amount" x-text="line.chip.value"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="!line.chip">
                                                        <span class="res-chip res-chip--neutral"
                                                            x-text="line.text"></span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                {{-- What the NEXT level's effect curve delivers (Owner-Playtest-
                                 Fund 2026-08-31, follow-up — e.g. "-4% Bau-AP-Kosten") — see
                                 KnowledgeEffectDescriptionService. --}}
                                <template
                                    x-if="selectedTech.unlocks_next_level && selectedTech.unlocks_next_level.length > 0">
                                    <div class="detail-row">
                                        <span
                                            class="detail-row-label">{{ __("techtree.detail_effects_next_level") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(line, idx) in selectedTech.unlocks_next_level"
                                                :key="idx">
                                                <li>
                                                    <template x-if="line.chip">
                                                        <span :class="'res-chip res-' + line.chip.cls">
                                                            <span class="res-abbr" x-text="line.chip.abbr"></span>
                                                            <span class="res-amount" x-text="line.chip.value"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="!line.chip">
                                                        <span class="res-chip res-chip--neutral"
                                                            x-text="line.text"></span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                {{-- AP invest bar for knowledge --}}
                                <template x-if="selectedTech.ap_for_levelup > 0 && selectedTech.status !== 'locked'">
                                    <div class="detail-ap-section">
                                        <div class="detail-row detail-row--ap">
                                            <span class="detail-row-label">{{ __("techtree.detail_ap_invest") }}</span>
                                            <span
                                                x-text="selectedTech.ap_spend + ' / ' + selectedTech.ap_for_levelup + ' AP'"></span>
                                        </div>
                                        <div class="detail-ap-bar">
                                            <template x-for="n in selectedTech.ap_for_levelup" :key="n">
                                                <span
                                                    :class="n <= selectedTech.ap_spend ? 'ap-seg ap-seg--done' : (selectedTech
                                                        .ap_available > 0 ? 'ap-seg ap-seg--todo' :
                                                        'ap-seg ap-seg--locked')"
                                                    @click="n > selectedTech.ap_spend && selectedTech.ap_available > 0 && investAp(selectedTech, 'research', n - selectedTech.ap_spend)">
                                                </span>
                                            </template>
                                        </div>
                                        <template
                                            x-if="selectedTech.ap_available <= 0 && selectedTech.ap_spend < selectedTech.ap_for_levelup">
                                            <p class="detail-ap-hint">{{ __("techtree.hint_no_research_ap") }}</p>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- SHIP --}}
                        <template x-if="selectedTech.type === 'ship'">
                            <div>
                                <template x-if="selectedTech.level > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_count") }}</span>
                                        <span
                                            x-text="selectedTech.level + (selectedTech.hangar_cap ? ' / ' + selectedTech.hangar_cap : '')"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.required_list && selectedTech.required_list.length > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <ul class="detail-list detail-list--chips">
                                            <template x-for="(part, idx) in selectedTech.required_list"
                                                :key="idx">
                                                <li>
                                                    <span class="res-chip res-chip--neutral" x-text="part"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>

                    <button class="detail-close" @click="closeDetail()">{{ __("techtree.detail_close") }}</button>
                </div>
            </template>
        </aside>

    </div>{{-- .techtree-page --}}

    @include("partials.first-visit-popup", [
        "firstVisitKey" => "techtree",
        "firstVisitTitle" => "colony.first_visit_techtree_title",
        "firstVisitText" => "colony.first_visit_techtree_text",
    ])
@endsection
