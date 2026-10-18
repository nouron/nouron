@extends("layouts.colony")
@section("title", "Techtree — Nouron")

@push("styles")
    <link rel="stylesheet"
        href="{{ asset("css/techtree-view.css") }}?v={{ filemtime(public_path("css/techtree-view.css")) }}">
@endpush

@push("scripts")
    <script src="{{ asset("js/techtree-canvas.js") }}?v={{ filemtime(public_path("js/techtree-canvas.js")) }}"></script>
    <script src="{{ asset("js/techtree-view.js") }}?v={{ filemtime(public_path("js/techtree-view.js")) }}"></script>
@endpush

@section("content")
    <script>
        window.__techtreeData = @json($pageData);
        window.__techtreeData.colonyViewUrl = @json(route("colony.view"));
        window.__techtreeData.hintRank = @json((int) $activeHintRank);
        window.__techtreeData.i18n = {
            instanceLevel: @json(__("techtree.detail_instance_level")),
            instanceLevelUncapped: @json(__("techtree.detail_instance_level_uncapped")),
            instanceConstruction: @json(__("techtree.detail_instance_construction")),
            instanceCondition: @json(__("techtree.detail_instance_condition")),
            statusAvailable: @json(__("techtree.status_available")),
            statusLocked: @json(__("techtree.status_locked")),
            statusBuilt: @json(__("techtree.status_built")),
            advisorHired: @json(__("techtree.advisor_hired")),
            advisorAvailable: @json(__("techtree.advisor_available")),
            advisorLocked: @json(__("techtree.advisor_locked")),
            advisorPlaced: @json(__("techtree.advisor_placed")),
            advisorNotPlaced: @json(__("techtree.advisor_not_placed")),
            ccTag: @json(__("techtree.cc_tag")),
            bandStart: @json(__("techtree.band_start")),
            bandCc: @json(__("techtree.band_cc")),
            columnN: @json(__("techtree.column_n")),
        };
    </script>

    <div class="techtree-page" x-ref="page" x-data="techtreeView(window.__techtreeData)" x-cloak :class="{ 'techtree-page--panel': selectedTech }"
        data-hint-rank="{{ $activeHintRank }}">

        <div class="techtree-toast" :class="`techtree-toast--${toastType}`" x-show="toastVisible" x-transition
            x-text="toastMessage" aria-live="polite" role="status"></div>

        {{-- Legend: colours = kind, stripe style = first / second prerequisite --}}
        <div class="techtree-legend" role="list" aria-label="{{ __("techtree.legend_label") }}">
            <span class="tt-leg" role="listitem"><span
                    class="tt-sw tt-sw--b"></span>{{ __("techtree.legend_building") }}</span>
            <span class="tt-leg" role="listitem"><span
                    class="tt-sw tt-sw--k"></span>{{ __("techtree.legend_research") }}</span>
            <span class="tt-leg" role="listitem"><span
                    class="tt-sw tt-sw--s"></span>{{ __("techtree.legend_ship") }}</span>
            <span class="tt-leg" role="listitem"><span
                    class="tt-sw tt-sw--a"></span>{{ __("techtree.legend_advisor") }}</span>
            <span class="tt-leg" role="listitem"><span class="tt-st"></span>{{ __("techtree.legend_required") }}</span>
            <span class="tt-leg" role="listitem"><span
                    class="tt-st tt-st--dash"></span>{{ __("techtree.legend_second") }}</span>
        </div>

        <div class="techtree-stage" x-ref="stage">
            <canvas class="techtree-canvas" x-ref="canvas" tabindex="0" role="img"
                aria-label="{{ __("techtree.canvas_label") }}"></canvas>
            <div class="techtree-controls">
                <button class="tt-btn tt-btn--sq" type="button" @click="zoomIn()"
                    aria-label="{{ __("techtree.zoom_in") }}">+</button>
                <button class="tt-btn tt-btn--sq" type="button" @click="zoomOut()"
                    aria-label="{{ __("techtree.zoom_out") }}">&minus;</button>
                <button class="tt-btn" type="button" @click="viewStart()">{{ __("techtree.view_start") }}</button>
                <button class="tt-btn" type="button" @click="viewOverview()">{{ __("techtree.view_overview") }}</button>
            </div>
            <div class="techtree-dots" x-ref="dots" hidden role="group"
                aria-label="{{ __("techtree.columns_label") }}">
            </div>
        </div>

        {{-- Text alternative to the canvas: also the keyboard / screen-reader way to open a node --}}
        <ul class="tt-sr">
            <template x-for="tech in flatItems()" :key="keyOf(tech)">
                <li>
                    <button type="button" @click="openDetail(tech)"
                        x-text="tech.name + ' (' + typeLabel(tech.type) + ', ' + statusLabel(tech) + ')' + (tech.required_desc ? ' — ' + tech.required_desc : '')"></button>
                </li>
            </template>
        </ul>

        {{-- Tech detail panel (sidebar on desktop, bottom sheet on mobile) --}}
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
                                @include("partials.required-list-chips", ["expr" => "selectedTech"])
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
                                <template x-if="selectedTech.is_instanced && selectedTech.instances.length > 0">
                                    <div class="detail-row">
                                        <span class="detail-row-label">{{ __("techtree.detail_instances") }}</span>
                                        <span class="detail-instance-count"
                                            x-text="instanceCountLabel(selectedTech)"></span>
                                    </div>
                                </template>
                                {{-- Read-only instance list (GDD techtree §11.4): every action
                                 lives in the colony view's tile panel, reached via "Zum Tile". --}}
                                <template x-if="selectedTech.instances.length > 0">
                                    <div class="detail-row detail-row--instances">
                                        <span class="detail-row-label">{{ __("techtree.detail_instance_list") }}</span>
                                        <ul class="detail-instance-list">
                                            <template x-for="inst in selectedTech.instances" :key="inst.instance_id">
                                                <li class="detail-instance">
                                                    <span class="detail-instance-level"
                                                        x-text="instanceLevelLabel(inst)"></span>
                                                    <span class="detail-instance-condition" x-show="inst.level > 0"
                                                        x-text="instanceConditionLabel(inst)"></span>
                                                    <a class="detail-instance-link" :href="tileLink(selectedTech, inst)">
                                                        {{ __("techtree.detail_instance_tile_link") }} &rarr;
                                                    </a>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                                @include("partials.required-list-chips", ["expr" => "selectedTech"])
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
                                {{-- Colony link: opens build mode with this building pre-selected,
                                 only while another instance is possible (GDD techtree §11.4). --}}
                                <template x-if="canBuildMore(selectedTech)">
                                    <a :href="buildLink(selectedTech)" class="detail-cta-link">
                                        {{ __("techtree.detail_colony_link") }} &rarr;
                                    </a>
                                </template>
                                {{-- Buildings the build menu never offers (Harvester, GDD §4c):
                                 how the next instance is obtained instead of a dead link. --}}
                                <template x-if="acquireHint(selectedTech)">
                                    <p class="detail-ap-hint" x-text="acquireHint(selectedTech)"></p>
                                </template>
                            </div>
                        </template>

                        {{-- RESEARCH / KNOWLEDGE --}}
                        <template x-if="selectedTech.type === 'research'">
                            <div>
                                {{-- Level moved to .detail-subhead right under the title,
                                 see above. --}}
                                @include("partials.required-list-chips", ["expr" => "selectedTech"])
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
                                        @include("partials.ap-upgrade-button", [
                                            "row" => "selectedTech",
                                            "click" => "investAp(selectedTech, 'research', 1)",
                                            "btnLabel" =>
                                                \Illuminate\Support\Js::from(__("techtree.detail_ap_invest")) .
                                                " + ' ' + selectedTech.ap_spend + '/' + selectedTech.ap_for_levelup",
                                            "chip" => "research",
                                            "blockedExpr" =>
                                                "selectedTech.ap_available <= 0 || selectedTech.ap_spend >= selectedTech.ap_for_levelup",
                                            "tipId" => "ap-cap-tip-tech",
                                        ])
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
                                @include("partials.required-list-chips", ["expr" => "selectedTech"])
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
