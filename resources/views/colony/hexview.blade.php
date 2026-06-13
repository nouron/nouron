@extends("layouts.colony")
@section("title", $colony->name . " — Kolonie")
@section("page-nav-title", "Kolonie")

@section("content")
    <script>
        window.__colonyViewData = {
            tiles: @json($tiles),
            colony: @json(["id" => $colony->id, "name" => $colony->name]),
            ccLevel: {{ (int) $ccLevel }},
            buildings: @json($buildings),
            apNav: {{ (int) $navAp }},
            apConstruction: {{ (int) $constructionAp }},
            trust: {{ $trust }},
            currentSol: {{ $currentSol }},
            solLimit: {{ $solLimit }},
            activeHint: @json($activeHint),
            merchantVisit: @json($merchantVisit ?? null),
            merchantItems: @json($merchantItems ?? []),
            routes: {
                explore: '{{ route("colony.tile.explore") }}',
                deepScan: '{{ route("colony.tile.deep-scan") }}',
                buildingsAvailable: '{{ route("colony.buildings.available") }}',
                placeBuilding: '{{ route("colony.building.place") }}',
                investBuilding: '{{ route("colony.building.invest") }}',
                repairBuilding: '{{ route("colony.building.repair") }}',
                dismissHint: '{{ route("colony.hint.dismiss") }}',
            },
            i18n: {
                explore: '{{ __("colony.explore") }}',
                deepScan: '{{ __("colony.deep_scan") }}',
                investAp: '{{ __("colony.invest_ap") }}',
                repair: '{{ __("colony.repair") }}',
                build: '{{ __("colony.build") }}',
                cancel: '{{ __("colony.cancel") }}',
                selectTileHint: '{{ __("colony.select_tile_hint") }}',
                buildModeTitle: '{{ __("colony.build_mode_title") }}',
                buildModeHint: '{{ __("colony.build_mode_hint") }}',
                noBuildings: '{{ __("colony.no_buildings") }}',
                constructionSite: '{{ __("colony.construction_site") }}',
                discoveryTitle: '{{ __("colony.discovery_title") }}',
                discoveryDismiss: '{{ __("colony.discovery_dismiss") }}',
                harvesterMoveTip: @json(__("colony.onboarding_trigger_harvester_move")),
                harvesterMove: '{{ __("colony.harvester_move") }}',
                harvesterMoveModeHint: @json(__("colony.harvester_move_mode_hint")),
                harvesterMoveNoTargets: @json(__("colony.harvester_move_no_targets")),
            },
        };
    </script>

    <div class="hex-page" x-data="colonyHexView(window.__colonyViewData)">
        <div class="hex-layout">

            {{-- Hex grid canvas --}}
            <div class="hex-canvas-wrap">
                {{-- Merchant notification — links to Bar when merchant is present --}}
                <a href="{{ route("colony.bar") }}" class="merchant-notify merchant-notify--floating" x-show="hasMerchant()"
                    x-cloak>
                    🛸 {{ __("colony.merchant_in_system") }}
                </a>

                {{-- Onboarding hint bar — reactive, driven by colonyHexView.activeHint.
                 Updates automatically after any AJAX action that changes hint state. --}}
                <div class="hint-bar" x-show="activeHint" x-cloak>
                    <span class="hint-bar__icon" aria-hidden="true">!</span>
                    <span class="hint-bar__text" x-text="activeHint?.text"></span>
                    <a class="hint-bar__link" :href="activeHint?.target_url">→</a>
                    <button class="hint-bar__dismiss" aria-label="Dismiss hint" @click="dismissHint()">×</button>
                </div>

                {{-- Supply-cap warning banner (Trigger 2) — server-side flag set by game tick --}}
                @if ($supplyCapFull)
                    <div class="supply-banner" role="alert">
                        {{ __("colony.onboarding_trigger_supply_full") }}
                    </div>
                @endif

                <div x-ref="hexgrid" class="hex-canvas"></div>
            </div>

            {{-- Tile info / build mode sidebar --}}
            <aside class="tile-panel">

                {{-- Action strip: always visible above the section header.
                 On desktop this serves as a top-of-panel quick-action bar.
                 On mobile this is the primary action surface (no scrolling needed). --}}
                <div class="tile-panel-action-strip" x-show="harvesterMoveMode || buildMode || selectedTile" x-cloak>
                    {{-- Harvester move mode: cancel --}}
                    <template x-if="harvesterMoveMode">
                        <div class="tile-actions">
                            <button class="tile-action-btn" @click="cancelHarvesterMove()">
                                {{ __("colony.cancel") }}
                            </button>
                        </div>
                    </template>
                    {{-- Normal mode tile actions --}}
                    <template x-if="!harvesterMoveMode && !buildMode && selectedTile">
                        <div class="tile-actions">
                            <template x-if="!selectedTile.is_explored">
                                <button class="tile-action-btn" @click="doExploreTile(selectedTile)">
                                    <span class="tile-action-btn__body">{{ __("colony.explore") }}</span>
                                    @include("partials.ap-cost-chip", ["amount" => 1, "type" => "nav"])
                                </button>
                            </template>
                            <template x-if="selectedTile.has_signal && !selectedTile.is_deep_scanned">
                                <button class="tile-action-btn" @click="doDeepScan(selectedTile)">
                                    <span class="tile-action-btn__body">{{ __("colony.deep_scan") }}</span>
                                    @include("partials.ap-cost-chip", ["amount" => 2, "type" => "nav"])
                                </button>
                            </template>
                            <template x-if="canRepair(selectedBuilding)">
                                <button class="tile-action-btn" @click="doRepair(selectedBuilding)">
                                    <span class="tile-action-btn__body">
                                        <span class="tile-action-btn__main"
                                            x-text="`{{ __("colony.repair") }} (${conditionPct(selectedBuilding)} %)`"></span>
                                        <span class="tile-action-btn__sub"
                                            x-text="`+${repairStepPct(selectedBuilding)} % {{ __("colony.condition") }}`"></span>
                                    </span>
                                    @include("partials.ap-cost-chip", ["amount" => 1, "type" => "build"])
                                </button>
                            </template>
                            <template x-if="buildingCanLevelUp(selectedBuilding)">
                                <button class="tile-action-btn"
                                    :class="canRepair(selectedBuilding) ? 'tile-action-btn--secondary' : ''"
                                    @click="doInvestAp(selectedBuilding)">
                                    <span class="tile-action-btn__body">
                                        <span class="tile-action-btn__main"
                                            x-text="`{{ __("colony.invest_ap") }} (${selectedBuilding.ap_spend}/${selectedBuilding.ap_for_levelup} AP)`"></span>
                                    </span>
                                    @include("partials.ap-cost-chip", ["amount" => 1, "type" => "build"])
                                </button>
                            </template>
                            <template
                                x-if="selectedBuilding?.building_key === 'building_harvester' && selectedBuilding?.level > 0 && !selectedBuilding?.in_transit">
                                <button class="tile-action-btn tile-action-btn--secondary" @click="startHarvesterMove()">
                                    <span class="tile-action-btn__body">{{ __("colony.harvester_move") }}</span>
                                    @include("partials.ap-cost-chip", [
                                        "type" => "build",
                                        "label" => __("colony.ap_per_tile"),
                                    ])
                                </button>
                            </template>
                            <template
                                x-if="selectedBuilding?.building_key === 'building_harvester' && selectedBuilding?.in_transit">
                                <p class="tile-action-note">{{ __("colony.harvester_in_transit") }}</p>
                            </template>
                            <template x-if="isBuildableTile(selectedTile) && !selectedBuilding">
                                <button class="tile-action-btn tile-action-btn--secondary"
                                    @click="startBuildForTile(selectedTile)">
                                    <span class="tile-action-btn__body">{{ __("colony.build") }}</span>
                                    @include("partials.ap-cost-chip", ["amount" => 1, "type" => "build"])
                                </button>
                            </template>
                        </div>
                    </template>
                    {{-- Build mode: cancel button in strip --}}
                    <template x-if="buildMode">
                        <div class="tile-actions">
                            <button class="tile-action-btn tile-action-btn--secondary" @click="cancelBuildMode()">
                                {{ __("colony.cancel") }}
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Only labels build/harvester modes. In normal tile mode the tabs
                 (building) or the terrain <h3> name the content themselves. --}}
                <div class="tile-panel-header tile-panel-header--hideable" x-show="harvesterMoveMode || buildMode" x-cloak>
                    <h3
                        x-text="harvesterMoveMode ? '{{ __("colony.harvester_move") }}' : '{{ __("colony.build_mode_title") }}'">
                    </h3>
                </div>

                <div class="tile-panel-body">

                    {{-- Harvester move mode panel --}}
                    <template x-if="harvesterMoveMode">
                        <div>
                            <p class="build-mode-hint"
                                x-text="hasHarvesterTargets() ? i18n.harvesterMoveModeHint : i18n.harvesterMoveNoTargets">
                            </p>
                        </div>
                    </template>

                    {{-- Build mode: building list --}}
                    <template x-if="!harvesterMoveMode && buildMode">
                        <div>
                            <p class="build-mode-hint">{{ __("colony.build_mode_hint") }}</p>

                            <template x-if="pendingBuilding">
                                <p class="build-mode-selected">
                                    <strong x-text="pendingBuilding.label"></strong>
                                    &nbsp;&mdash; {{ __("colony.select_tile_hint") }}
                                </p>
                            </template>

                            {{-- In-progress buildings (placed but level=0): guide player to click the tile --}}
                            <template x-if="buildings.filter(b => b.level === 0 && b.tile_x !== null).length > 0">
                                <div class="build-mode-inprogress">
                                    <p class="build-mode-inprogress-label">{{ __("colony.inprogress_label") }}</p>
                                    <ul class="building-list">
                                        <template x-for="b in buildings.filter(b => b.level === 0 && b.tile_x !== null)"
                                            :key="b.building_id + '-' + b.instance_id">
                                            <li class="building-list-item building-list-item--inprogress">
                                                <span class="building-list-name" x-text="b.label ?? b.building_key"></span>
                                                <span class="building-list-ap"
                                                    x-text="`${b.ap_spend} / ${b.ap_for_levelup} AP`"></span>
                                            </li>
                                        </template>
                                    </ul>
                                    <p class="build-mode-inprogress-hint">{{ __("colony.inprogress_hint") }}</p>
                                </div>
                            </template>

                            <template x-if="availableBuildings.length === 0">
                                <p class="build-mode-empty">{{ __("colony.no_buildings") }}</p>
                            </template>

                            <ul class="building-list">
                                <template x-for="b in availableBuildings" :key="b.building_id">
                                    <li class="building-list-item"
                                        :class="{
                                            'building-list-item--selected': pendingBuilding?.building_id === b
                                                .building_id
                                        }"
                                        @click="selectPendingBuilding(b)">
                                        <span class="building-list-name" x-text="b.label"></span>
                                        <span class="building-list-meta">
                                            <span class="building-list-ap" x-text="`${b.ap_for_levelup} AP`"></span>
                                            <span class="building-list-supply" x-show="b.supply_cost > 0"
                                                x-text="`${b.supply_cost} SUP`"></span>
                                        </span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    {{-- Normal mode: selected tile info.
                     x-effect resets the active tab to "Gebäude" whenever a
                     different tile is selected (compares coords, so refreshes
                     of the same tile keep the player's chosen tab). --}}
                    {{-- x-effect resets the active tab to "Gebäude" on tile change
                     (compares coords, so same-tile refreshes keep the chosen
                     tab). Horizontal swipe flips tabs on mobile. --}}
                    <template x-if="!harvesterMoveMode && !buildMode && selectedTile">
                        <div class="tile-tab-body" x-effect="onTilePanel()" @touchstart.passive="panelTouchStart($event)"
                            @touchend.passive="panelTouchEnd($event)">

                            {{-- Tabs — only when a building occupies the tile.
                             Empty terrain has no building info, so no tab chrome. --}}
                            <template x-if="selectedBuilding">
                                <div class="tile-tabs" role="tablist">
                                    <button type="button" class="tile-tab" role="tab"
                                        :class="{ 'tile-tab--active': tileTab === 'building' }"
                                        :aria-selected="tileTab === 'building'" @click="tileTab = 'building'">
                                        {{ __("colony.tab_building") }}
                                    </button>
                                    <button type="button" class="tile-tab" role="tab"
                                        :class="{ 'tile-tab--active': tileTab === 'terrain' }"
                                        :aria-selected="tileTab === 'terrain'" @click="tileTab = 'terrain'">
                                        {{ __("colony.tab_terrain") }}
                                    </button>
                                </div>
                            </template>

                            {{-- ── Building tab ──────────────────────────────────── --}}
                            <template x-if="selectedBuilding && tileTab === 'building'">
                                <div class="tile-building">
                                    @include("partials.building-detail", [
                                        "expr" => "selectedBuilding",
                                        "name_field" => "label",
                                    ])

                                    <dl class="tile-dl">
                                        <dt>{{ __("colony.max_level") }}</dt>
                                        <dd x-text="selectedBuilding.max_level ?? '∞'"></dd>
                                    </dl>

                                    <template x-if="selectedBuilding.level === 0">
                                        <div class="tile-under-construction">
                                            {{ __("colony.under_construction") }}
                                        </div>
                                    </template>

                                    <template x-if="selectedBuilding.level > 0">
                                        <div class="tile-bar-group">
                                            <div class="tile-bar-label">
                                                <span>{{ __("colony.condition") }}</span>
                                                <span x-text="`${conditionPct(selectedBuilding)} %`"></span>
                                            </div>
                                            <div class="tile-bar-wrap">
                                                <div class="tile-bar tile-bar--status"
                                                    :style="`width:${conditionPct(selectedBuilding)}%`"></div>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="buildingCanLevelUp(selectedBuilding)">
                                        <div class="tile-bar-group">
                                            <div class="tile-bar-label">
                                                <span>{{ __("colony.ap_invested") }}</span>
                                                <span
                                                    x-text="`${selectedBuilding.ap_spend} / ${selectedBuilding.ap_for_levelup}`"></span>
                                            </div>
                                            <div class="tile-bar-wrap">
                                                <div class="tile-bar tile-bar--ap"
                                                    :style="`width:${apProgressPct(selectedBuilding)}%`"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- ── Terrain tab (or sole content when no building) ── --}}
                            <template x-if="!selectedBuilding || tileTab === 'terrain'">
                                <div class="tile-terrain">
                                    <h3 class="tile-heading" x-text="tileHeading(selectedTile)"></h3>

                                    <div class="tile-status-chips">
                                        <span x-show="!selectedTile.is_explored && !selectedTile.is_colony_zone"
                                            class="chip chip--locked">{{ __("colony.chip_locked") }}</span>
                                        <span x-show="!selectedTile.is_explored && selectedTile.is_colony_zone"
                                            class="chip chip--fog">{{ __("colony.chip_unexplored") }}</span>
                                        <span
                                            x-show="selectedTile.is_explored && !selectedTile.is_deep_scanned && !selectedTile.has_signal"
                                            class="chip chip--explored">{{ __("colony.chip_explored") }}</span>
                                        <span x-show="selectedTile.is_explored && selectedTile.has_signal"
                                            class="chip chip--signal">{{ __("colony.chip_signal") }}</span>
                                        <span x-show="selectedTile.is_deep_scanned"
                                            class="chip chip--scanned">{{ __("colony.chip_scanned") }}</span>
                                    </div>

                                    <dl class="tile-dl">
                                        <div class="tile-dl-coords">
                                            <dt>Koordinaten</dt>
                                            <dd
                                                x-text="`q=${selectedTile.q}, r=${selectedTile.r} (Ring ${selectedTile.ring})`">
                                            </dd>
                                        </div>

                                        <template x-if="selectedTile.is_deep_scanned && selectedTile.event_type">
                                            <div>
                                                <dt>Event</dt>
                                                <dd x-text="eventTypeName(selectedTile.event_type)"></dd>
                                            </div>
                                        </template>
                                    </dl>

                                    <template x-if="selectedTile.resource_max > 0 && selectedTile.is_explored">
                                        <div class="tile-resource">
                                            <div class="tile-bar-group">
                                                <div class="tile-bar-label">
                                                    <span>{{ __("colony.resource_regolith") }}</span>
                                                    <span
                                                        x-text="`${selectedTile.resource_amount} / ${selectedTile.resource_max}`"></span>
                                                </div>
                                                <div class="tile-bar-wrap">
                                                    <div class="tile-bar tile-bar--resource"
                                                        :style="`width:${resourcePct(selectedTile)}%`"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                        </div>
                    </template>

                    {{-- Normal mode: nothing selected --}}
                    <template x-if="!harvesterMoveMode && !buildMode && !selectedTile">
                        <div class="tile-panel-empty">
                            <p>{{ __("colony.click_tile_hint") }}</p>
                        </div>
                    </template>

                </div>
            </aside>

        </div>

        {{-- Event discovery popup ------------------------------------------------
         Uses the native <dialog> element (PicoCSS styles it out of the box).
         x-effect watches eventDiscovery and calls the DOM API to open/close,
         keeping Alpine state as the single source of truth.
    --}}
        <dialog x-ref="discoveryDialog"
            x-effect="eventDiscovery ? $refs.discoveryDialog.showModal() : $refs.discoveryDialog.close()"
            @close="eventDiscovery = null">
            <article>
                <header>
                    <h3 x-text="i18n.discoveryTitle"></h3>
                </header>
                <p class="discovery-event-name" x-text="eventDiscovery ? eventTypeName(eventDiscovery.event_type) : ''">
                </p>
                <footer>
                    <button @click="dismissEventDiscovery()" x-text="i18n.discoveryDismiss"></button>
                </footer>
            </article>
        </dialog>

        {{-- Action / AP-limit toast (Triggers 4 + 5) --}}
        <div class="colony-toast" :class="`colony-toast--${toastType}`" x-show="toastVisible" x-transition
            x-text="toastMessage" aria-live="polite" role="status"></div>

        {{-- Levelup notice: centered slide-up + fade --}}
        <div class="colony-levelup-notice" x-show="levelupNotice" x-transition:enter="levelup-enter"
            x-transition:enter-start="levelup-enter-start" x-transition:enter-end="levelup-enter-end"
            x-transition:leave="levelup-leave" x-transition:leave-start="levelup-leave-start"
            x-transition:leave-end="levelup-leave-end" aria-live="polite">
            <span class="levelup-icon">✓</span>
            <span>{{ __("colony.levelup_built") }}</span>
            <strong x-text="levelupNotice"></strong>
        </div>

    </div>
@endsection
