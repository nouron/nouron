@extends('layouts.app')
@section('title', __('nexusdb.page_title') . ' — Nouron')

@push('styles')
{{--
    PicoCSS is loaded AFTER Bootstrap in <head> (via @stack('styles')).
    Risk: PicoCSS element selectors reset body, a, button globally.
    Mitigation: Bootstrap's class-based selectors (.btn, .navbar, .nav-link …)
    have higher specificity than PicoCSS bare element rules, so the Bootstrap
    navbar survives. All nexusdb-specific PicoCSS components live inside
    .nexusdb-scope to keep any remaining conflicts contained.
--}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400&display=swap">
<style>
    /* ── Scope PicoCSS cascade to .nexusdb-scope ──────────────────────────── */
    .nexusdb-scope {
        --pico-font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        --pico-font-size: 1rem;
        --pico-line-height: 1.5;
        font-family: var(--pico-font-family);
        color: #1a1a1e;
    }

    /* ── Page header ─────────────────────────────────────────────────────── */
    .nexusdb-page-header {
        margin-bottom: 2rem;
    }

    .nexusdb-page-header h1 {
        font-family: 'Libre Baskerville', serif;
        font-weight: 400;
        font-size: 2rem;
        text-transform: uppercase;
        letter-spacing: 0.45em;
        color: #1a1a1e;
        margin: 0 0 0.4rem;
    }

    .nexusdb-page-header p {
        color: #6b6b7a;
        font-size: 0.95rem;
        margin: 0;
    }

    /* ── Tab navigation ──────────────────────────────────────────────────── */
    .nexusdb-tabs {
        display: flex;
        gap: 0;
        border-bottom: 1px solid #e8e8ec;
        margin-bottom: 2rem;
    }

    .nexusdb-tab-btn {
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.6rem 1.25rem;
        font-family: system-ui, sans-serif;
        font-size: 0.875rem;
        font-weight: 400;
        color: #6b6b7a;
        cursor: pointer;
        margin-bottom: -1px;  /* overlap the border-bottom of the container */
        border-radius: 0;
        transition: color 0.15s, border-color 0.15s;
    }

    .nexusdb-tab-btn:hover {
        color: #1a1a1e;
        background: none;
    }

    .nexusdb-tab-btn[aria-selected="true"] {
        color: #8c2030;
        border-bottom-color: #8c2030;
        font-weight: 600;
    }

    /* ── Card grid ───────────────────────────────────────────────────────── */
    .nexusdb-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
        gap: 1rem;
    }

    /* ── Individual cards ────────────────────────────────────────────────── */
    .nexusdb-card {
        background: #ffffff;
        border: 1px solid #e8e8ec;
        border-radius: 4px;
        padding: 1.5rem;
        margin: 0;
    }

    .nexusdb-card h3 {
        font-family: system-ui, sans-serif;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1a1a1e;
        margin: 0 0 0.4rem;
    }

    .nexusdb-card-desc {
        font-size: 0.9rem;
        color: #6b6b7a;
        margin: 0 0 1.25rem;
        line-height: 1.5;
    }

    /* ── Stats table inside cards ────────────────────────────────────────── */
    .nexusdb-stats {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .nexusdb-stats tr {
        border-bottom: 1px solid #e8e8ec;
    }

    .nexusdb-stats tr:last-child {
        border-bottom: none;
    }

    .nexusdb-stats td {
        padding: 0.45rem 0;
        vertical-align: middle;
    }

    .nexusdb-stats td:first-child {
        color: #6b6b7a;
        padding-right: 1rem;
        white-space: nowrap;
    }

    .nexusdb-stats td:last-child {
        color: #1a1a1e;
        font-weight: 500;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    /* ── Trust effect badges ─────────────────────────────────────────────── */
    .nexusdb-trust-pos {
        color: var(--pico-ins-color, #2d8a4e);
    }

    .nexusdb-trust-neg {
        color: var(--pico-del-color, #c0392b);
    }

    /* ── CC-gate hint ────────────────────────────────────────────────────── */
    .nexusdb-cc-hint {
        display: inline-block;
        font-size: 0.78rem;
        background: #f7f7f5;
        border: 1px solid #e8e8ec;
        border-radius: 0.3em;
        padding: 0.15em 0.55em;
        color: #6b6b7a;
        margin-top: 0.75rem;
    }

    /* ── x-cloak: hide Alpine-controlled elements before Alpine init ─────── */
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="nexusdb-scope" x-data="{ tab: 'buildings' }" x-cloak>

    {{-- ── Page header ──────────────────────────────────────────────────────── --}}
    <div class="nexusdb-page-header">
        <h1>{{ __('nexusdb.page_title') }}</h1>
        <p>{{ __('nexusdb.page_subtitle') }}</p>
    </div>

    {{-- ── Tab navigation ───────────────────────────────────────────────────── --}}
    <div class="nexusdb-tabs" role="tablist">
        <button class="nexusdb-tab-btn"
                role="tab"
                :aria-selected="(tab === 'buildings').toString()"
                @click="tab = 'buildings'">
            {{ __('nexusdb.tab_buildings') }}
        </button>
        <button class="nexusdb-tab-btn"
                role="tab"
                :aria-selected="(tab === 'ships').toString()"
                @click="tab = 'ships'">
            {{ __('nexusdb.tab_ships') }}
        </button>
        <button class="nexusdb-tab-btn"
                role="tab"
                :aria-selected="(tab === 'knowledge').toString()"
                @click="tab = 'knowledge'">
            {{ __('nexusdb.tab_knowledge') }}
        </button>
    </div>

    {{-- ── Tab 1: Buildings ─────────────────────────────────────────────────── --}}
    <div x-show="tab === 'buildings'" role="tabpanel">
        <div class="nexusdb-card-grid">
            @foreach ($buildings as $key => $data)
            <article class="nexusdb-card">
                <h3>{{ __('buildings.' . $key) }}</h3>
                <p class="nexusdb-card-desc">{{ __('buildings.' . $key . '_desc') }}</p>
                <table class="nexusdb-stats">
                    <tbody>
                        <tr>
                            <td>{{ __('nexusdb.supply_cost') }}</td>
                            <td>{{ $data['supply_cost'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('nexusdb.max_level') }}</td>
                            <td>
                                @if(isset($data['max_level']) && $data['max_level'] !== null)
                                    {{ $data['max_level'] }}
                                @else
                                    {{ __('nexusdb.max_level_none') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('nexusdb.durability') }}</td>
                            <td>
                                @php
                                    $days = $data['decay_rate'] > 0
                                        ? round(20 / $data['decay_rate'])
                                        : '∞';
                                @endphp
                                {{ sprintf(__('nexusdb.durability_days'), $days) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </article>
            @endforeach
        </div>
    </div>

    {{-- ── Tab 2: Ships ─────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'ships'" role="tabpanel">
        <div class="nexusdb-card-grid">
            @foreach ($ships as $key => $data)
            <article class="nexusdb-card">
                <h3>{{ __('ships.' . $key) }}</h3>
                <p class="nexusdb-card-desc">{{ __('ships.' . $key . '_desc') }}</p>
                <table class="nexusdb-stats">
                    <tbody>
                        <tr>
                            <td>{{ __('nexusdb.speed') }}</td>
                            <td>{{ $data['moving_speed'] }} {{ __('nexusdb.speed_unit') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('nexusdb.supply_cost_ship') }}</td>
                            <td>
                                @if($data['supply_cost'] === 0)
                                    {{ __('nexusdb.supply_unmanned') }}
                                @else
                                    {{ $data['supply_cost'] }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('nexusdb.trust_effect') }}</td>
                            <td>
                                @if($data['trust_per_unit'] === 0)
                                    <span>{{ __('nexusdb.trust_neutral') }}</span>
                                @elseif($data['trust_per_unit'] > 0)
                                    <span class="nexusdb-trust-pos">+{{ $data['trust_per_unit'] }}</span>
                                @else
                                    <span class="nexusdb-trust-neg">{{ $data['trust_per_unit'] }}</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </article>
            @endforeach
        </div>
    </div>

    {{-- ── Tab 3: Knowledge ─────────────────────────────────────────────────── --}}
    <div x-show="tab === 'knowledge'" role="tabpanel">
        @php
            $knowledgeKeys = ['construction', 'cartography', 'geology', 'agronomy', 'health', 'trade', 'defense'];
        @endphp
        <div class="nexusdb-card-grid">
            @foreach ($knowledgeKeys as $key)
            <article class="nexusdb-card">
                <h3>{{ __('knowledge.' . $key) }}</h3>
                <p class="nexusdb-card-desc">{{ __('knowledge.' . $key . '_desc') }}</p>
                <table class="nexusdb-stats">
                    <tbody>
                        <tr>
                            <td>{{ __('nexusdb.knowledge_max_level') }}</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td>{{ __('nexusdb.knowledge_ap_cost') }}</td>
                            <td>{{ __('nexusdb.knowledge_ap_values') }}</td>
                        </tr>
                    </tbody>
                </table>
                {{-- CC-gate hints for levels 4 and 5 --}}
                @foreach ($knowledge as $knowledgeLevel => $ccRequired)
                    <span class="nexusdb-cc-hint">
                        {{ sprintf(__('nexusdb.knowledge_cc_gate_hint'), $knowledgeLevel, $ccRequired) }}
                    </span>
                @endforeach
            </article>
            @endforeach
        </div>
    </div>

</div>{{-- /.nexusdb-scope --}}
@endsection
