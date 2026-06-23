@extends("layouts.colony")
@section("title", "Techtree — Nouron")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/techtree-view.css") }}">
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
                            <span class="tech-status-chip" :class="'chip-' + selectedTech.status"
                                x-text="statusLabel(selectedTech)"></span>
                        </div>
                        <button class="detail-x" @click="closeDetail()"
                            aria-label="{{ __("techtree.detail_close") }}">&#215;</button>
                    </div>

                    {{-- Title --}}
                    <h3 class="detail-title" x-text="selectedTech.name"></h3>

                    {{-- Building image (only for building-type items with a resolved image_slug).
                     show_header:false because the <h3 detail-title> above already renders the name. --}}
                    @include("partials.building-detail", [
                        "expr" => "selectedTech",
                        "name_field" => "name",
                        "show_header" => false,
                    ])

                    {{-- Meta rows --}}
                    <div class="detail-body">

                        {{-- ADVISOR: hired status, AP type, hire cost, link to Berater screen --}}
                        <template x-if="selectedTech.type === 'personell'">
                            <div>
                                <div class="detail-row">
                                    <span class="detail-row-label">{{ __("techtree.detail_advisor_status") }}</span>
                                    <span :class="selectedTech.status === 'built' ? 'detail-advisor-hired' : ''"
                                        x-text="selectedTech.status === 'built' ? '{{ __("techtree.advisor_hired") }}' : (selectedTech.status === 'available' ? '{{ __("techtree.advisor_available") }}' : '{{ __("techtree.advisor_locked") }}')">
                                    </span>
                                </div>
                                <template x-if="selectedTech.ap_type">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_advisor_ap") }}</span>
                                        <span x-text="selectedTech.ap_type"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.hire_cost">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_advisor_cost") }}</span>
                                        <span x-text="selectedTech.hire_cost + ' Cr'"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.required_desc">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <span x-text="selectedTech.required_desc"></span>
                                    </div>
                                </template>
                                <a href="{{ route("advisors.index") }}" class="detail-advisor-link">
                                    {{ __("techtree.detail_advisor_link") }} &rarr;
                                </a>
                            </div>
                        </template>

                        {{-- BUILDING --}}
                        <template x-if="selectedTech.type === 'building'">
                            <div>
                                <template x-if="!selectedTech.is_instanced && selectedTech.level > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_level") }}</span>
                                        <span
                                            x-text="selectedTech.level + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.is_instanced && selectedTech.instance_count > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_instances") }}</span>
                                        <span
                                            x-text="selectedTech.instance_count + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.required_desc">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <span x-text="selectedTech.required_desc"></span>
                                    </div>
                                </template>
                                {{-- Colony link: opens build mode with this building pre-selected --}}
                                <a :href="'/colony/view?build=' + selectedTech.id" class="detail-colony-link">
                                    {{ __("techtree.detail_colony_link") }} &rarr;
                                </a>
                            </div>
                        </template>

                        {{-- RESEARCH / KNOWLEDGE --}}
                        <template x-if="selectedTech.type === 'research'">
                            <div>
                                <template x-if="selectedTech.level > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_level") }}</span>
                                        <span
                                            x-text="selectedTech.level + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')"></span>
                                    </div>
                                </template>
                                <template x-if="selectedTech.required_desc">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <span x-text="selectedTech.required_desc"></span>
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
                                <template x-if="selectedTech.required_desc">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_required") }}</span>
                                        <span x-text="selectedTech.required_desc"></span>
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
