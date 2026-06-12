@extends('layouts.colony')
@section('title', $colony->name . ' — Kolonie')
@section('page-nav-title', 'Kolonie')

@section('content')
<script>
window.__colonyViewData = {
    tiles:     @json($tiles),
    colony:    @json(['id' => $colony->id, 'name' => $colony->name]),
    ccLevel:   {{ (int)$ccLevel }},
    buildings: @json($buildings),
    apNav:          {{ (int)$navAp }},
    apConstruction: {{ (int)$constructionAp }},
    trust:          {{ $trust }},
    currentSol:     {{ $currentSol }},
    solLimit:       {{ $solLimit }},
    activeHint: @json($activeHint),
    merchantVisit:  @json($merchantVisit ?? null),
    merchantItems:  @json($merchantItems ?? []),
    routes: {
        explore:            '{{ route('colony.tile.explore') }}',
        deepScan:           '{{ route('colony.tile.deep-scan') }}',
        buildingsAvailable: '{{ route('colony.buildings.available') }}',
        placeBuilding:      '{{ route('colony.building.place') }}',
        investBuilding:     '{{ route('colony.building.invest') }}',
        dismissHint:        '{{ route('colony.hint.dismiss') }}',
    },
    i18n: {
        explore:            '{{ __('colony.explore') }}',
        deepScan:           '{{ __('colony.deep_scan') }}',
        investAp:           '{{ __('colony.invest_ap') }}',
        build:              '{{ __('colony.build') }}',
        cancel:             '{{ __('colony.cancel') }}',
        selectTileHint:     '{{ __('colony.select_tile_hint') }}',
        buildModeTitle:     '{{ __('colony.build_mode_title') }}',
        buildModeHint:      '{{ __('colony.build_mode_hint') }}',
        noBuildings:        '{{ __('colony.no_buildings') }}',
        constructionSite:   '{{ __('colony.construction_site') }}',
        discoveryTitle:     '{{ __('colony.discovery_title') }}',
        discoveryDismiss:   '{{ __('colony.discovery_dismiss') }}',
        harvesterMoveTip:      @json(__('colony.onboarding_trigger_harvester_move')),
        harvesterMove:         '{{ __('colony.harvester_move') }}',
        harvesterMoveModeHint: @json(__('colony.harvester_move_mode_hint')),
        harvesterMoveNoTargets: @json(__('colony.harvester_move_no_targets')),
    },
};
</script>

<div class="hex-page" x-data="colonyHexView(window.__colonyViewData)">
    <div class="hex-layout">

        {{-- Hex grid canvas --}}
        <div class="hex-canvas-wrap">
            <div class="hex-canvas-header">
                <h2>{{ $colony->name }}</h2>
                <small class="status-line" x-text="statusLine()"></small>
                <div class="ap-chips">
                    <span class="ap-chip ap-chip--nav" x-data="{ open: false }"
                          @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                          style="position:relative;cursor:default">
                        <span x-text="`Nav ${apNav} AP`"></span>
                        @include('partials.res-popup', [
                            'popup_title' => __('resources.popup_nav_ap_title'),
                            'popup_desc'  => __('resources.popup_nav_ap_desc'),
                        ])
                    </span>
                    <span class="ap-chip ap-chip--build" x-data="{ open: false }"
                          @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                          style="position:relative;cursor:default">
                        <span x-text="`Bau ${apConstruction} AP`"></span>
                        @include('partials.res-popup', [
                            'popup_title' => __('resources.popup_bau_ap_title'),
                            'popup_desc'  => __('resources.popup_bau_ap_desc'),
                        ])
                    </span>
                    <span class="ap-chip" x-data="{ open: false }"
                          :class="trust >= 20 ? 'ap-chip--trust-pos' : trust < 0 ? 'ap-chip--trust-neg' : 'ap-chip--trust-neu'"
                          @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                          style="position:relative;cursor:default">
                        <span x-text="`Vertrauen ${trust}`"></span>
                        @include('partials.res-popup', [
                            'popup_title' => __('resources.popup_trust_title'),
                            'popup_desc'  => __('resources.popup_trust_desc'),
                        ])
                    </span>
                </div>
                {{-- Merchant notification — links to Bar when merchant is present --}}
                <a href="{{ route('colony.bar') }}"
                   class="merchant-notify"
                   x-show="hasMerchant()"
                   x-cloak>
                    🛸 {{ __('colony.merchant_in_system') }}
                </a>

            </div>

            {{-- Onboarding hint bar — reactive, driven by colonyHexView.activeHint.
                 Updates automatically after any AJAX action that changes hint state. --}}
            <div class="hint-bar"
                 x-show="activeHint"
                 x-cloak>
                <span class="hint-bar__icon" aria-hidden="true">!</span>
                <span class="hint-bar__text" x-text="activeHint?.text"></span>
                <a class="hint-bar__link" :href="activeHint?.target_url">→</a>
                <button class="hint-bar__dismiss"
                        aria-label="Dismiss hint"
                        @click="dismissHint()">×</button>
            </div>

            {{-- Supply-cap warning banner (Trigger 2) — server-side flag set by game tick --}}
            @if($supplyCapFull)
            <div class="supply-banner" role="alert">
                {{ __('colony.onboarding_trigger_supply_full') }}
            </div>
            @endif

            <div x-ref="hexgrid" class="hex-canvas"></div>
        </div>

        {{-- Tile info / build mode sidebar --}}
        <aside class="tile-panel">

            {{-- Action strip: always visible above the section header.
                 On desktop this serves as a top-of-panel quick-action bar.
                 On mobile this is the primary action surface (no scrolling needed). --}}
            <div class="tile-panel-action-strip"
                 x-show="harvesterMoveMode || buildMode || selectedTile"
                 x-cloak>
                {{-- Harvester move mode: cancel --}}
                <template x-if="harvesterMoveMode">
                    <div class="sidebar-actions">
                        <button class="sidebar-action-btn" @click="cancelHarvesterMove()">
                            {{ __('colony.cancel') }}
                        </button>
                    </div>
                </template>
                {{-- Normal mode tile actions --}}
                <template x-if="!harvesterMoveMode && !buildMode && selectedTile">
                    <div class="sidebar-actions">
                        <template x-if="!selectedTile.is_explored">
                            <button class="sidebar-action-btn" @click="doExploreTile(selectedTile)">
                                {{ __('colony.explore') }}
                            </button>
                        </template>
                        <template x-if="selectedTile.has_signal && !selectedTile.is_deep_scanned">
                            <button class="sidebar-action-btn" @click="doDeepScan(selectedTile)">
                                {{ __('colony.deep_scan') }}
                            </button>
                        </template>
                        <template x-if="buildingForTile(selectedTile) && (buildingForTile(selectedTile).max_level === null || buildingForTile(selectedTile).level < buildingForTile(selectedTile).max_level)">
                            <button class="sidebar-action-btn" @click="doInvestAp(buildingForTile(selectedTile))">
                                {{ __('colony.invest_ap') }}
                            </button>
                        </template>
                        <template x-if="buildingForTile(selectedTile)?.building_key === 'building_harvester' && buildingForTile(selectedTile)?.level > 0 && !buildingForTile(selectedTile)?.in_transit">
                            <button class="sidebar-action-btn sidebar-action-btn--secondary" @click="startHarvesterMove()">
                                {{ __('colony.harvester_move') }}
                            </button>
                        </template>
                        <template x-if="buildingForTile(selectedTile)?.building_key === 'building_harvester' && buildingForTile(selectedTile)?.in_transit">
                            <p class="build-mode-hint" style="margin:0">{{ __('colony.harvester_in_transit') }}</p>
                        </template>
                        <template x-if="isBuildableTile(selectedTile) && !buildingForTile(selectedTile)">
                            <button class="sidebar-action-btn sidebar-action-btn--secondary" @click="startBuildForTile(selectedTile)">
                                {{ __('colony.build') }}
                            </button>
                        </template>
                    </div>
                </template>
                {{-- Build mode: cancel button in strip --}}
                <template x-if="buildMode">
                    <div class="sidebar-actions">
                        <button class="sidebar-action-btn sidebar-action-btn--secondary" @click="cancelBuildMode()">
                            {{ __('colony.cancel') }}
                        </button>
                    </div>
                </template>
            </div>

            <div class="tile-panel-header tile-panel-header--hideable">
                <h3 x-text="harvesterMoveMode ? '{{ __('colony.harvester_move') }}' : (buildMode ? '{{ __('colony.build_mode_title') }}' : '{{ __('colony.tile_info') }}')"></h3>
            </div>

            <div class="tile-panel-body">

                {{-- Harvester move mode panel --}}
                <template x-if="harvesterMoveMode">
                    <div>
                        <p class="build-mode-hint"
                           x-text="hasHarvesterTargets() ? i18n.harvesterMoveModeHint : i18n.harvesterMoveNoTargets"></p>
                    </div>
                </template>

                {{-- Build mode: building list --}}
                <template x-if="!harvesterMoveMode && buildMode">
                    <div>
                        <p class="build-mode-hint">{{ __('colony.build_mode_hint') }}</p>

                        <template x-if="pendingBuilding">
                            <p class="build-mode-selected">
                                <strong x-text="pendingBuilding.label"></strong>
                                &nbsp;&mdash; {{ __('colony.select_tile_hint') }}
                            </p>
                        </template>

                        {{-- In-progress buildings (placed but level=0): guide player to click the tile --}}
                        <template x-if="buildings.filter(b => b.level === 0 && b.tile_x !== null).length > 0">
                            <div class="build-mode-inprogress">
                                <p class="build-mode-inprogress-label">{{ __('colony.inprogress_label') }}</p>
                                <ul class="building-list">
                                    <template x-for="b in buildings.filter(b => b.level === 0 && b.tile_x !== null)" :key="b.building_id + '-' + b.instance_id">
                                        <li class="building-list-item building-list-item--inprogress">
                                            <span class="building-list-name" x-text="b.label ?? b.building_key"></span>
                                            <span class="building-list-ap" x-text="`${b.ap_spend} / ${b.ap_for_levelup} AP`"></span>
                                        </li>
                                    </template>
                                </ul>
                                <p class="build-mode-inprogress-hint">{{ __('colony.inprogress_hint') }}</p>
                            </div>
                        </template>

                        <template x-if="availableBuildings.length === 0">
                            <p class="build-mode-empty">{{ __('colony.no_buildings') }}</p>
                        </template>

                        <ul class="building-list">
                            <template x-for="b in availableBuildings" :key="b.building_id">
                                <li class="building-list-item"
                                    :class="{ 'building-list-item--selected': pendingBuilding?.building_id === b.building_id }"
                                    @click="selectPendingBuilding(b)">
                                    <span class="building-list-name" x-text="b.label"></span>
                                    <span class="building-list-meta">
                                        <span class="building-list-ap" x-text="`${b.ap_for_levelup} AP`"></span>
                                        <span class="building-list-supply" x-show="b.supply_cost > 0" x-text="`${b.supply_cost} SUP`"></span>
                                    </span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                {{-- Normal mode: selected tile info --}}
                <template x-if="!harvesterMoveMode && !buildMode && selectedTile">
                    <div class="tile-info-container">
                        <h3 class="tile-heading" x-text="tileHeading(selectedTile)"></h3>

                        <div class="tile-status-chips">
                            <span x-show="!selectedTile.is_explored && !selectedTile.is_colony_zone"
                                  class="chip chip--locked">{{ __('colony.chip_locked') }}</span>
                            <span x-show="!selectedTile.is_explored && selectedTile.is_colony_zone"
                                  class="chip chip--fog">{{ __('colony.chip_unexplored') }}</span>
                            <span x-show="selectedTile.is_explored && !selectedTile.is_deep_scanned && !selectedTile.has_signal"
                                  class="chip chip--explored">{{ __('colony.chip_explored') }}</span>
                            <span x-show="selectedTile.is_explored && selectedTile.has_signal"
                                  class="chip chip--signal">{{ __('colony.chip_signal') }}</span>
                            <span x-show="selectedTile.is_deep_scanned"
                                  class="chip chip--scanned">{{ __('colony.chip_scanned') }}</span>
                        </div>

                        <dl class="tile-dl">
                            <div class="tile-dl-coords">
                                <dt>Koordinaten</dt>
                                <dd x-text="`q=${selectedTile.q}, r=${selectedTile.r} (Ring ${selectedTile.ring})`"></dd>
                            </div>

                            <template x-if="selectedTile.is_explored">
                                <div>
                                    <dt>Typ</dt>
                                    <dd x-text="tileTypeName(selectedTile.tile_type)"></dd>
                                </div>
                            </template>

                            <template x-if="selectedTile.is_deep_scanned && selectedTile.event_type">
                                <div>
                                    <dt>Event</dt>
                                    <dd x-text="eventTypeName(selectedTile.event_type)"></dd>
                                </div>
                            </template>
                        </dl>

                        <template x-if="selectedTile.resource_max > 0 && selectedTile.is_explored">
                            <div class="sidebar-resource">
                                <div class="sidebar-bar-group">
                                    <div class="sidebar-bar-label">
                                        <span>{{ __('colony.resource_regolith') }}</span>
                                        <span x-text="`${selectedTile.resource_amount} / ${selectedTile.resource_max}`"></span>
                                    </div>
                                    <div class="sidebar-bar-wrap">
                                        <div class="sidebar-bar sidebar-bar--resource"
                                             :style="`width:${Math.round(selectedTile.resource_amount / selectedTile.resource_max * 100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="buildingForTile(selectedTile)">
                            <div class="sidebar-building">
                                <div class="sidebar-section-title">{{ __('colony.building_section') }}</div>

                                @include('partials.building-detail', [
                                    'expr'       => 'buildingForTile(selectedTile)',
                                    'name_field' => 'label',
                                ])

                                <dl class="tile-dl" style="margin-top:.5rem">
                                    <dt>{{ __('colony.max_level') }}</dt>
                                    <dd x-text="buildingForTile(selectedTile).max_level ?? '∞'"></dd>
                                </dl>

                                <template x-if="buildingForTile(selectedTile).level === 0">
                                    <div class="sidebar-under-construction">
                                        {{ __('colony.under_construction') }}
                                    </div>
                                </template>

                                <template x-if="buildingForTile(selectedTile).level > 0">
                                    <div class="sidebar-bar-group">
                                        <div class="sidebar-bar-label">
                                            <span>{{ __('colony.condition') }}</span>
                                            <span x-text="`${Math.round(buildingForTile(selectedTile).status_points / (buildingForTile(selectedTile).max_status_points ?? 20) * 100)} %`"></span>
                                        </div>
                                        <div class="sidebar-bar-wrap">
                                            <div class="sidebar-bar sidebar-bar--status"
                                                 :style="`width:${Math.round(buildingForTile(selectedTile).status_points / (buildingForTile(selectedTile).max_status_points ?? 20) * 100)}%`"></div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="buildingForTile(selectedTile).max_level === null || buildingForTile(selectedTile).level < buildingForTile(selectedTile).max_level">
                                    <div class="sidebar-bar-group">
                                        <div class="sidebar-bar-label">
                                            <span>{{ __('colony.ap_invested') }}</span>
                                            <span x-text="`${buildingForTile(selectedTile).ap_spend} / ${buildingForTile(selectedTile).ap_for_levelup}`"></span>
                                        </div>
                                        <div class="sidebar-bar-wrap">
                                            <div class="sidebar-bar sidebar-bar--ap"
                                                 :style="`width:${Math.round(buildingForTile(selectedTile).ap_spend / buildingForTile(selectedTile).ap_for_levelup * 100)}%`"></div>
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
                        <p>{{ __('colony.click_tile_hint') }}</p>
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
            <p class="discovery-event-name"
               x-text="eventDiscovery ? eventTypeName(eventDiscovery.event_type) : ''"></p>
            <footer>
                <button @click="dismissEventDiscovery()"
                        x-text="i18n.discoveryDismiss"></button>
            </footer>
        </article>
    </dialog>

    {{-- Action / AP-limit toast (Triggers 4 + 5) --}}
    <div class="colony-toast"
         :class="`colony-toast--${toastType}`"
         x-show="toastVisible"
         x-transition
         x-text="toastMessage"
         aria-live="polite"
         role="status"></div>

    {{-- Levelup notice: centered slide-up + fade --}}
    <div class="colony-levelup-notice"
         x-show="levelupNotice"
         x-transition:enter="levelup-enter"
         x-transition:enter-start="levelup-enter-start"
         x-transition:enter-end="levelup-enter-end"
         x-transition:leave="levelup-leave"
         x-transition:leave-start="levelup-leave-start"
         x-transition:leave-end="levelup-leave-end"
         aria-live="polite">
        <span class="levelup-icon">✓</span>
        <span>{{ __('colony.levelup_built') }}</span>
        <strong x-text="levelupNotice"></strong>
    </div>

</div>
@endsection
