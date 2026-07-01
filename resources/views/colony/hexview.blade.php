@extends("layouts.colony")
@section("title", $colony->name . " — Kolonie")
@section("page-nav-title", "Kolonie")

@section("content")
    <script>
        window.__colonyViewData = {
            tiles: @json($tiles),
            colony: @json(["id" => $colony->id, "name" => $colony->name]),
            ccLevel: {{ (int) $ccLevel }},
            ccBuildingId: {{ \App\Enums\BuildingId::CommandCenter->value }},
            buildings: @json($buildings),
            apNav: {{ (int) $navAp }},
            apConstruction: {{ (int) $constructionAp }},
            regolith: {{ (int) $regolith }},
            werkstoffe: {{ (int) $werkstoffe }},
            freeSupply: {{ (int) $freeSupply }},
            trust: {{ $trust }},
            currentSol: {{ $currentSol }},
            solLimit: {{ $solLimit }},
            activeHint: @json($activeHint),
            merchantVisit: @json($merchantVisit ?? null),
            merchantItems: @json($merchantItems ?? []),
            uplinkBuildingId: {{ (int) config("buildings.uplinkStation.id", 54) }},
            compoundImportPrice: {{ (int) config("game.economy.compound_import_price", 90) }},
            exploreCostPerRing: @json(config("game.colony.explore_cost_per_ring")),
            exploreCostDefault: {{ (int) config("game.colony.explore_cost_default", 1) }},
            phaseProgress: @json($phaseProgress),
            routes: {
                explore: '{{ route("colony.tile.explore") }}',
                deepScan: '{{ route("colony.tile.deep-scan") }}',
                buildingsAvailable: '{{ route("colony.buildings.available") }}',
                placeBuilding: '{{ route("colony.building.place") }}',
                investBuilding: '{{ route("colony.building.invest") }}',
                repairBuilding: '{{ route("colony.building.repair") }}',
                nexusImport: '{{ route("colony.nexus.import") }}',
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
                harvesterMoveInvalidTarget: @json(__("colony.harvester_move_invalid_target")),
                networkError: @json(__("colony.network_error")),
                nexusImportSuccess: @json(__("colony.nexus_import_success")),
                nexusImportError: @json(__("colony.nexus_import_error")),
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

                {{-- Onboarding hint bar now lives in layouts/colony.blade.php (shown on every
                 screen) — colonyHexView still tracks activeHint internally for hex-grid
                 highlighting and broadcasts changes via the `hint:sync` window event;
                 see setActiveHint() in colony-hexgrid.js and partials/hint-bar.blade.php. --}}

                {{-- Supply-cap warning banner (Trigger 2) — server-side flag set by game tick --}}
                @if ($supplyCapFull)
                    <div class="supply-banner" role="alert">
                        {{ __("colony.onboarding_trigger_supply_full") }}
                    </div>
                @endif

                <div x-ref="hexgrid" class="hex-canvas"></div>

                {{-- Info bar: phase progress + legend — always visible below canvas --}}
                <div class="canvas-info-bar">
                    <template x-if="phaseProgress">
                        <button class="info-bar-btn" @click="$refs.phaseDialog.showModal()"
                            :title="phaseProgress.phase === 1 ? '{{ __("colony.phase1_progress_title") }}' :
                                '{{ __("colony.phase2_progress_title") }}'">
                            <template x-if="phaseProgress.phase === 1">
                                <span x-text="`P1 — ${phaseProgress.criteria.filter(c => c.done).length}/3`"></span>
                            </template>
                            <template x-if="phaseProgress.phase === 2">
                                <span
                                    x-text="`P2 — ${phaseProgress.objectives.filter(o => o.done).length}/${phaseProgress.objectives.length}`"></span>
                            </template>
                        </button>
                    </template>

                    <details class="hex-legend">
                        <summary class="info-bar-btn">{{ __("colony.legend_title") }}</summary>
                        <ul class="hex-legend__list">
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--buildable"></span>
                                <span>{{ __("colony.legend_buildable") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--soon"></span>
                                <span>{{ __("colony.legend_soon_buildable") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--zonefog">+</span>
                                <span>{{ __("colony.legend_zone_fog") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--explorefog">?</span>
                                <span>{{ __("colony.legend_explore_fog") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--regolith"></span>
                                <span>{{ __("colony.legend_regolith") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--cc"></span>
                                <span>{{ __("colony.legend_cc") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--hazard"></span>
                                <span>{{ __("colony.legend_hazard") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--impassable"></span>
                                <span>{{ __("colony.legend_impassable") }}</span>
                            </li>
                            <li class="hex-legend__item">
                                <span class="hex-legend__swatch hex-legend__swatch--event"></span>
                                <span>{{ __("colony.legend_event") }}</span>
                            </li>
                        </ul>
                    </details>
                </div>
            </div>

            {{-- Tile info / build mode sidebar --}}
            <aside class="tile-panel">

                {{-- Context title: building name + level (or terrain name) at the very
                 top, above the action strip — identity before action, and the one
                 line that stays visible on mobile. Max level is shown inline on the
                 badge only when the building is actually capped. --}}
                <div class="tile-panel-title" x-show="!harvesterMoveMode && !buildMode && selectedTile" x-cloak>
                    <template x-if="selectedBuilding">
                        <div class="tile-panel-title__row">
                            <span class="tile-panel-title__name" x-text="selectedBuilding.label"></span>
                            <span class="sidebar-level-badge" x-show="selectedBuilding.level > 0"
                                x-text="selectedBuilding.max_level
                                    ? `Lv. ${selectedBuilding.level} / ${selectedBuilding.max_level}`
                                    : `Lv. ${selectedBuilding.level}`"></span>
                        </div>
                    </template>
                    <template x-if="!selectedBuilding">
                        <span class="tile-panel-title__name" x-text="tileHeading(selectedTile)"></span>
                    </template>
                </div>

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
                                    <span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true"
                                        x-text="`${exploreCostFor(selectedTile)} AP`"></span>
                                </button>
                            </template>
                            <template x-if="selectedTile.has_signal && !selectedTile.is_deep_scanned">
                                <button class="tile-action-btn" @click="doDeepScan(selectedTile)">
                                    <span class="tile-action-btn__body">{{ __("colony.deep_scan") }}</span>
                                    @include("partials.ap-cost-chip", ["amount" => 2, "type" => "nav"])
                                </button>
                            </template>
                            {{-- Repair: the condition bar is embedded as a segmented
                             footer strip inside the button. Local hover flag drives the
                             desktop-only +1 SP ghost segment; CSS gates the ghost to
                             (hover: hover) so it never sticks on touch. A fully repaired
                             building has no Reparieren button and shows no bar (intended). --}}
                            <template x-if="canRepair(selectedBuilding)">
                                <button class="tile-action-btn tile-action-btn--barred" x-data="{ hover: false }"
                                    @mouseenter="hover = true" @mouseleave="hover = false"
                                    @click="doRepair(selectedBuilding)">
                                    <span class="tile-action-btn__body">
                                        <span class="tile-action-btn__main"
                                            x-text="`{{ __("colony.repair") }} +${repairStepPct(selectedBuilding)} %`"></span>
                                    </span>
                                    @include("partials.ap-cost-chip", [
                                        "amount" => 1,
                                        "type" => "build",
                                    ])
                                    @include("colony.partials.condition-bar", ["hoverExpr" => "hover"])
                                </button>
                            </template>

                            {{-- Ausbauen: the AP-invested bar is embedded as a segmented
                             footer strip inside the button, shown whenever a level-up is
                             possible. --}}
                            <template x-if="buildingCanLevelUp(selectedBuilding)">
                                <button class="tile-action-btn tile-action-btn--barred"
                                    :class="canRepair(selectedBuilding) ? 'tile-action-btn--secondary' : ''"
                                    x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                                    @click="doInvestAp(selectedBuilding)">
                                    <span class="tile-action-btn__body">
                                        <span class="tile-action-btn__main"
                                            x-text="selectedBuilding.ap_spend === 0
                                                ? `Stufe ${selectedBuilding.level + 1} starten (0/${selectedBuilding.ap_for_levelup})`
                                                : `{{ __("colony.invest_ap") }} ${selectedBuilding.ap_spend}/${selectedBuilding.ap_for_levelup}`"></span>
                                    </span>
                                    @include("partials.ap-cost-chip", [
                                        "amount" => 1,
                                        "type" => "build",
                                    ])
                                    @include("colony.partials.ap-bar", ["hoverExpr" => "hover"])
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
                <div class="tile-panel-header tile-panel-header--hideable" x-show="harvesterMoveMode || buildMode"
                    x-cloak>
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
                                                <span class="building-list-name"
                                                    x-text="b.label ?? b.building_key"></span>
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
                                                .building_id,
                                            'building-list-item--disabled': !canAffordBuilding(b)
                                        }"
                                        :aria-disabled="!canAffordBuilding(b)"
                                        @click="canAffordBuilding(b) && selectPendingBuilding(b)">
                                        <div class="building-list-row">
                                            <span class="building-list-name" x-text="b.label"></span>
                                            <span class="building-list-row-right">
                                                <span class="building-list-info" x-data="{ open: false }"
                                                    @mouseenter="open=true" @mouseleave="open=false"
                                                    @click.stop="open=!open" @click.outside="open=false"
                                                    aria-label="{{ __("colony.building_info_label") }}">
                                                    i
                                                    <div class="res-popup res-popup--wide" x-show="open" x-cloak>
                                                        <div class="res-popup-header" x-text="b.label"></div>
                                                        <div class="res-popup-body" x-text="b.description"></div>
                                                    </div>
                                                </span>
                                                <span class="building-list-ap" x-text="`${b.ap_for_levelup} AP`"></span>
                                            </span>
                                        </div>
                                        <div class="building-list-row building-list-row--costs">
                                            <span class="building-list-supply" x-show="b.supply_cost > 0"
                                                x-text="`${b.supply_cost} SUP`"></span>
                                            <span class="building-list-cost" x-show="b.build_cost && b.build_cost[3]"
                                                x-text="`${b.build_cost?.[3]} Rg`"></span>
                                            <span class="building-list-cost building-list-cost--compounds"
                                                x-show="b.build_cost && b.build_cost[4]"
                                                x-text="`${b.build_cost?.[4]} Wk`"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>

                            {{-- Nexus-Import: Werkstoffe gegen Credits, ab Uplink-Station Lv1.
                             Garantierte Werkstoff-Quelle (GDD §3) — verhindert Bau-Deadlock. --}}
                            <div class="nexus-import" x-show="uplinkLevel() >= 1" x-cloak>
                                <h4 class="nexus-import-title">{{ __("colony.nexus_import_title") }}</h4>
                                <p class="nexus-import-hint">{{ __("colony.nexus_import_hint") }}</p>
                                <div class="nexus-import-controls">
                                    <input type="number" min="1" max="9999"
                                        x-model.number="nexusImportAmount" class="nexus-import-amount"
                                        aria-label="{{ __("colony.nexus_import_amount") }}">
                                    <span class="nexus-import-total"
                                        x-text="`${(nexusImportAmount || 0) * compoundImportPrice} Cr`"></span>
                                    <button class="nexus-import-btn"
                                        :disabled="!nexusImportAmount || nexusImportAmount < 1"
                                        @click="doNexusImport()">{{ __("colony.nexus_import_confirm") }}</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Normal mode: selected tile info. No tabs — the layout is
                     context-driven: a built tile is building-centric (terrain demoted
                     to a closed disclosure), an empty tile is terrain-centric (flat,
                     since terrain is then the decision basis for building). --}}
                    <template x-if="!harvesterMoveMode && !buildMode && selectedTile">
                        <div>
                            {{-- ── Built tile: building-centric ──────────────────── --}}
                            <template x-if="selectedBuilding">
                                <div class="tile-building">
                                    {{-- Name + level live in the .tile-panel-title header
                                     above; the partial renders the image only. --}}
                                    @include("partials.building-detail", [
                                        "expr" => "selectedBuilding",
                                        "name_field" => "label",
                                        "show_header" => false,
                                    ])

                                    <template
                                        x-if="buildingCanLevelUp(selectedBuilding) && (selectedBuilding.levelup_cost ?? 0) > 0">
                                        <p class="tile-building-levelup-cost"
                                            x-text="`{{ __("colony.levelup_cost_label") }} ${selectedBuilding.levelup_cost} RG {{ __("colony.levelup_cost_suffix") }}`">
                                        </p>
                                    </template>

                                    <template x-if="selectedBuilding.level === 0">
                                        <div class="tile-under-construction">
                                            {{ __("colony.under_construction") }}
                                        </div>
                                    </template>

                                    {{-- Terrain is secondary on a built tile → closed
                                     disclosure (PicoCSS styles <details> natively). --}}
                                    <details class="tile-terrain-disclosure">
                                        <summary>{{ __("colony.terrain_details") }}</summary>
                                        @include("colony.partials.tile-terrain", ["built" => true])
                                    </details>
                                </div>
                            </template>

                            {{-- ── Empty tile: terrain-centric, flat ─────────────── --}}
                            <template x-if="!selectedBuilding">
                                @include("colony.partials.tile-terrain", ["built" => false])
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

        {{-- Phase progress dialog -----------------------------------------------
         Opened by the .phase-btn floating button on the canvas.
    --}}
        <dialog x-ref="phaseDialog" class="sol-modal" @click.self="$refs.phaseDialog.close()">
            <article>
                <header>
                    <button aria-label="{{ __("colony.cancel") }}" rel="prev"
                        @click="$refs.phaseDialog.close()"></button>
                    <template x-if="phaseProgress && phaseProgress.phase === 1">
                        <h3>{{ __("colony.phase1_progress_title") }}</h3>
                    </template>
                    <template x-if="phaseProgress && phaseProgress.phase === 2">
                        <h3>{{ __("colony.phase2_progress_title") }}</h3>
                    </template>
                </header>
                <template x-if="phaseProgress && phaseProgress.phase === 1">
                    <ul class="phase-dialog-criteria">
                        <template x-for="c in phaseProgress.criteria" :key="c.key">
                            <li class="phase-criteria__item" :class="{ 'phase-criteria__item--done': c.done }">
                                <span class="phase-criteria__check" x-text="c.done ? '✓' : '○'"></span>
                                <span class="phase-criteria__label" x-text="c.label"></span>
                                <span class="phase-criteria__count" x-show="!c.done"
                                    x-text="`${c.current}/${c.target}`"></span>
                            </li>
                        </template>
                    </ul>
                </template>
                <template x-if="phaseProgress && phaseProgress.phase === 2">
                    <ul class="phase-dialog-criteria">
                        <template x-for="(obj, idx) in phaseProgress.objectives" :key="idx">
                            <li class="phase-criteria__item" :class="{ 'phase-criteria__item--done': obj.done }">
                                <span class="phase-criteria__check" x-text="obj.done ? '✓' : '○'"></span>
                                <span class="phase-criteria__label"
                                    x-text="obj.revealed ? obj.label : '{{ __("colony.sol_report_phase2_objective_hidden") }}'"></span>
                                <span class="phase-criteria__count" x-show="!obj.done && obj.revealed"
                                    x-text="`${obj.current}/${obj.target}`"></span>
                            </li>
                        </template>
                    </ul>
                </template>
            </article>
        </dialog>

        {{-- Event discovery popup ------------------------------------------------
         Uses the native <dialog> element (PicoCSS styles it out of the box).
         x-effect watches eventDiscovery and calls the DOM API to open/close,
         keeping Alpine state as the single source of truth.
    --}}
        <dialog x-ref="discoveryDialog" class="sol-modal"
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
