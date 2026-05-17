@extends('layouts.colony')
@section('title', $colony->name . ' — Kolonie')

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
        merchantBuy:        '{{ route('colony.merchant.buy', ['itemId' => '__ID__']) }}',
        merchantOpen:       '{{ route('colony.merchant.open', ['visitId' => '__VISIT__']) }}',
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
        harvesterMoveTip:   @json(__('colony.onboarding_trigger_harvester_move')),
    },
};
</script>

<div class="hex-page" x-data="colonyHexView(window.__colonyViewData)">
    <div class="hex-layout">

        {{-- Hex grid canvas --}}
        <div class="hex-canvas-wrap">
            <div class="hex-canvas-header">
                <h2>{{ $colony->name }}</h2>
                <small x-text="statusLine()"></small>
                <div class="ap-chips">
                    <span class="ap-chip ap-chip--nav" x-text="`Nav ${apNav} AP`"></span>
                    <span class="ap-chip ap-chip--build" x-text="`Bau ${apConstruction} AP`"></span>
                    <span class="ap-chip"
                          :class="trust >= 20 ? 'ap-chip--trust-pos' : trust < 0 ? 'ap-chip--trust-neg' : 'ap-chip--trust-neu'"
                          x-text="`Vertrauen ${trust}`"></span>
                </div>
                {{-- Merchant button — only shown when merchant is in system --}}
                <button class="merchant-btn"
                        x-show="hasMerchant()"
                        @click="openMerchant()"
                        x-cloak>
                    🛸 {{ __('colony.merchant_in_system') }}
                </button>

                <button class="build-btn"
                        :class="{ 'build-btn--active': buildMode }"
                        @click="toggleBuildMode()">
                    <span x-text="buildMode ? '{{ __('colony.cancel') }}' : '{{ __('colony.build') }}'"></span>
                </button>
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
            <div class="tile-panel-header">
                <h3 x-text="buildMode ? '{{ __('colony.build_mode_title') }}' : '{{ __('colony.tile_info') }}'"></h3>
            </div>

            <div class="tile-panel-body">

                {{-- Build mode: building list --}}
                <template x-if="buildMode">
                    <div>
                        <p class="build-mode-hint">{{ __('colony.build_mode_hint') }}</p>

                        <template x-if="pendingBuilding">
                            <p class="build-mode-selected">
                                <strong x-text="pendingBuilding.label"></strong>
                                &nbsp;&mdash; {{ __('colony.select_tile_hint') }}
                            </p>
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
                                    <span class="building-list-ap" x-text="`${b.ap_for_levelup} AP`"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                {{-- Normal mode: selected tile info --}}
                <template x-if="!buildMode && selectedTile">
                    <div>
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
                            <dt>Koordinaten</dt>
                            <dd x-text="`q=${selectedTile.q}, r=${selectedTile.r} (Ring ${selectedTile.ring})`"></dd>

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
                            </div>
                        </template>

                        {{-- Context action buttons --}}
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
                        </div>
                    </div>
                </template>

                {{-- Normal mode: nothing selected --}}
                <template x-if="!buildMode && !selectedTile">
                    <div class="tile-panel-empty">
                        <p>{{ __('colony.click_tile_hint') }}</p>
                    </div>
                </template>

            </div>
        </aside>

    </div>

    {{-- Merchant modal -------------------------------------------------------
         Native <dialog> element; opened via openMerchant() which calls
         $refs.merchantDialog.showModal() — browser handles backdrop + focus-trap
         + Escape key. merchantOpen tracks state on Alpine side so x-for re-renders
         correctly when the dialog is reopened.
    --}}
    <dialog x-ref="merchantDialog" class="merchant-dialog" @close="merchantOpen = false">
        <article>
            <header>
                <button aria-label="{{ __('colony.close') }}" rel="prev" @click="closeMerchant()"></button>
                <h3>{{ __('colony.merchant_title') }}</h3>
                <small x-show="merchantVisit">
                    {{ __('colony.merchant_until_sol') }} <span x-text="merchantVisit?.tick_end"></span>
                </small>
            </header>

            <div class="merchant-items">
                <template x-for="item in merchantItems" :key="item.id">
                    <article class="merchant-item" :class="{ 'merchant-item--sold': item.sold }">
                        <div class="merchant-item__label" x-text="item.label"></div>
                        <div class="merchant-item__cost" x-text="`${item.cost_credits} Cr`"></div>
                        <button class="merchant-item__buy"
                                :disabled="item.sold"
                                @click="buyMerchantItem(item.id)">
                            <span x-show="!item.sold">{{ __('colony.merchant_buy') }}</span>
                            <span x-show="item.sold">{{ __('colony.merchant_sold') }}</span>
                        </button>
                    </article>
                </template>
            </div>

            <footer>
                <button @click="closeMerchant()">{{ __('colony.close') }}</button>
            </footer>
        </article>
    </dialog>

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

</div>
@endsection
