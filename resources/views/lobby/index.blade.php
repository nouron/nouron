@extends('layouts.app')
@section('title', __('lobby.page_title') . ' — Nouron')

@push('styles')
{{--
    PicoCSS is loaded AFTER Bootstrap in <head> (via @stack('styles')).
    Risk: PicoCSS element selectors reset body, a, button globally.
    Mitigation: Bootstrap's class-based selectors (.btn, .navbar, .nav-link …)
    have higher specificity than PicoCSS bare element rules, so the Bootstrap
    navbar survives. All lobby-specific PicoCSS components live inside
    .lobby-scope to keep any remaining conflicts contained.
--}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
<style>
    /* ── Scope: contain PicoCSS custom-property cascade to .lobby-scope ──────
       PicoCSS and Bootstrap share element selectors (body, a, button, input).
       We cannot fully isolate CDN-loaded CSS, but class-scoped overrides and
       Bootstrap's higher-specificity class selectors keep the nav unharmed.  */
    .lobby-scope {
        --pico-font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        --pico-font-size: 1rem;
        --pico-line-height: 1.5;
        font-family: var(--pico-font-family);
    }

    /* Card grid — responsive columns */
    .lobby-run-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
        gap: 1rem;
        margin-top: 0.75rem;
    }

    /* PicoCSS <article> cards */
    .lobby-run-card {
        margin: 0;
        padding: 1.25rem;
    }

    .lobby-run-card header {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0;
        margin-bottom: 0.5rem;
        background: none;
        border: none;
    }

    .lobby-run-card header h3 {
        margin: 0;
        font-size: 1.1rem;
    }

    .lobby-run-card footer {
        padding: 0;
        margin-top: 1rem;
        background: none;
        border: none;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Sol progress label */
    .lobby-sol-progress {
        font-size: 0.85rem;
        color: var(--pico-muted-color, #6c757d);
        margin-bottom: 0.25rem;
    }

    /* PicoCSS <progress> */
    .lobby-run-card progress {
        margin-bottom: 0.5rem;
        height: 0.5rem;
    }

    /* Bypass warning badge */
    .lobby-bypass-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15em 0.55em;
        border-radius: 0.3em;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--pico-del-color, #c0392b);
        color: #fff;
        white-space: nowrap;
    }

    /* Settings meta list */
    .lobby-meta {
        font-size: 0.85rem;
        color: var(--pico-muted-color, #6c757d);
        margin: 0.5rem 0 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
    }

    .lobby-meta li::before {
        content: none;
    }

    /* Status pills for finished runs */
    .lobby-status-pill {
        display: inline-block;
        padding: 0.15em 0.6em;
        border-radius: 0.3em;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    .lobby-status-pill--completed {
        background: var(--pico-ins-color, #27ae60);
        color: #fff;
    }

    .lobby-status-pill--failed {
        background: var(--pico-del-color, #c0392b);
        color: #fff;
    }

    /* PicoCSS native <details>/<summary> for expandable settings */
    .lobby-run-card details {
        margin-top: 0.75rem;
        font-size: 0.85rem;
    }

    .lobby-run-card details summary {
        cursor: pointer;
        color: var(--pico-primary, #1095c1);
        font-size: 0.85rem;
        padding: 0.25rem 0;
    }

    /* Details meta list: block layout (not flex) to stack items vertically */
    .lobby-details-meta {
        list-style: none;
        padding: 0.5rem 0 0;
        margin: 0;
    }

    .lobby-details-meta li {
        padding: 0.15rem 0;
    }

    .lobby-details-meta li::before {
        content: none;
    }

    /* Empty state */
    .lobby-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--pico-muted-color, #6c757d);
    }

    /* Section headings */
    .lobby-section-heading {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 1.75rem 0 0.25rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid var(--pico-muted-border-color, #ddd);
    }

    .lobby-section-heading:first-child {
        margin-top: 0;
    }

    /* Disabled "coming soon" button */
    button[disabled].lobby-btn-disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .lobby-coming-soon-note {
        font-size: 0.8rem;
        color: var(--pico-muted-color, #6c757d);
        margin-top: 0.35rem;
    }

    /* Alpine x-cloak support */
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
{{--
    .lobby-scope wrapper:
    - Contains PicoCSS custom-property influence.
    - No Bootstrap grid classes used inside — PicoCSS article/grid only.
    - Alpine.js is already loaded globally by layouts/app.blade.php.
--}}
<div class="lobby-scope" style="max-width: 56rem; margin: 0 auto; padding: 1rem 0 3rem;">

    {{-- ── Page header ──────────────────────────────────────────────────────── --}}
    <hgroup style="margin-bottom: 1.75rem;">
        <h1 style="margin-bottom: 0.25rem;">{{ __('lobby.page_title') }}</h1>
        <p style="color: var(--pico-muted-color, #6c757d); margin: 0;">
            {{ __('lobby.page_subtitle') }}
        </p>
    </hgroup>

    {{-- ── Active Runs ──────────────────────────────────────────────────────── --}}
    @if($active->isNotEmpty())
        <h2 class="lobby-section-heading">{{ __('lobby.active_runs') }}</h2>
        <div class="lobby-run-grid">
            @foreach($active as $run)
                @php
                    $tickLimit  = $run->settings['tick_limit'] ?? 100;
                    $solPct     = $tickLimit > 0
                        ? min(100, round(($run->current_tick / $tickLimit) * 100))
                        : 0;
                    $colonyName = $run->colony->name ?? __('lobby.colony_unnamed');
                @endphp
                <article class="lobby-run-card">
                    <header>
                        <h3>{{ $colonyName }}</h3>
                    </header>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.sol_progress', [
                            'current' => $run->current_tick,
                            'limit'   => $tickLimit,
                        ]) }}
                    </p>
                    <progress
                        value="{{ $run->current_tick }}"
                        max="{{ $tickLimit }}"
                        title="{{ $solPct }}%"
                    ></progress>

                    @if($run->started_at)
                        <p class="lobby-sol-progress">
                            {{ __('lobby.started_at') }}: {{ $run->started_at->format('d.m.Y') }}
                        </p>
                    @endif

                    <footer>
                        <a href="{{ route('colony.view') }}" role="button">
                            {{ __('lobby.continue_button') }}
                        </a>
                    </footer>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ── Pending Runs ─────────────────────────────────────────────────────── --}}
    @if($pending->isNotEmpty())
        <h2 class="lobby-section-heading">{{ __('lobby.pending_runs') }}</h2>
        <div class="lobby-run-grid">
            @foreach($pending as $run)
                @php
                    $tickLimit  = $run->settings['tick_limit'] ?? 100;
                    $supplyCap  = $run->settings['supply_cap_max'] ?? null;
                    $maxPlayers = $run->settings['max_players'] ?? null;
                    $bypass     = is_array($run->settings['bypass'] ?? null)
                        ? $run->settings['bypass']
                        : [];
                    $anyBypass  = collect($bypass)->contains(true);
                    $colonyName = $run->colony->name ?? __('lobby.colony_unnamed');
                @endphp
                <article class="lobby-run-card">
                    <header>
                        <h3>{{ $colonyName }}</h3>
                        @if($anyBypass)
                            <span class="lobby-bypass-badge" title="{{ __('lobby.bypass_warning') }}">
                                &#9888; {{ __('lobby.bypass_warning') }}
                            </span>
                        @endif
                    </header>

                    <p class="lobby-sol-progress">{{ __('lobby.colony_ready') }}</p>

                    <ul class="lobby-meta">
                        <li>{{ __('lobby.tick_limit') }}: <strong>{{ $tickLimit }}</strong></li>
                        @if($supplyCap !== null)
                            <li>{{ __('lobby.supply_cap') }}: <strong>{{ $supplyCap }}</strong></li>
                        @endif
                        @if($maxPlayers !== null)
                            <li>{{ __('lobby.max_players') }}: <strong>{{ $maxPlayers }}</strong></li>
                        @endif
                        @foreach($bypass as $checkKey => $isActive)
                            @if($isActive)
                                <li>
                                    <span class="lobby-bypass-badge">
                                        {{ __('lobby.bypass_active') }}: {{ $checkKey }}
                                    </span>
                                </li>
                            @endif
                        @endforeach
                    </ul>

                    <footer>
                        {{-- POST to lobby.start — controller finds the pending run by user_id,
                             run_id is passed as a hint but the controller currently ignores it. --}}
                        <form method="POST" action="{{ route('lobby.start') }}" style="margin: 0;">
                            @csrf
                            <input type="hidden" name="run_id" value="{{ $run->id }}">
                            <button type="submit">{{ __('lobby.start_button') }}</button>
                        </form>
                    </footer>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ── New Run button ───────────────────────────────────────────────────── --}}
    {{-- Shown when the game allows multiple runs AND no pending run exists yet. --}}
    {{-- Disabled until the run-creation flow is implemented.                   --}}
    @if($allowMultiple && $pending->isEmpty())
        <div style="margin-top: 1.5rem;">
            <button
                class="lobby-btn-disabled"
                disabled
                aria-disabled="true"
                title="{{ __('lobby.coming_soon') }}"
            >
                {{ __('lobby.new_run_button') }}
            </button>
            <p class="lobby-coming-soon-note">{{ __('lobby.coming_soon') }}</p>
        </div>
    @endif

    {{-- ── Finished Runs ────────────────────────────────────────────────────── --}}
    @if($finished->isNotEmpty())
        <h2 class="lobby-section-heading">{{ __('lobby.finished_runs') }}</h2>
        <div class="lobby-run-grid">
            @foreach($finished as $run)
                @php
                    $tickLimit  = $run->settings['tick_limit'] ?? 100;
                    $supplyCap  = $run->settings['supply_cap_max'] ?? null;
                    $maxPlayers = $run->settings['max_players'] ?? null;
                    $bypass     = is_array($run->settings['bypass'] ?? null)
                        ? $run->settings['bypass']
                        : [];
                    $anyBypass  = collect($bypass)->contains(true);
                    $colonyName = $run->colony->name ?? __('lobby.colony_unnamed');
                    $statusKey  = $run->status === 'completed' ? 'status_completed' : 'status_failed';
                    $statusCls  = $run->status === 'completed' ? 'completed' : 'failed';
                    $fromDate   = $run->started_at ? $run->started_at->format('d.m.Y') : '—';
                    $toDate     = $run->ended_at   ? $run->ended_at->format('d.m.Y')   : '—';
                @endphp

                {{-- PicoCSS native <details> handles expand/collapse without Alpine.
                     Alpine x-data is not needed here — <details>/<summary> provide
                     built-in toggle, focus management, and keyboard (Enter/Space).  --}}
                <article class="lobby-run-card">
                    <header>
                        <h3>
                            {{ __('lobby.run_number', ['id' => $run->id]) }}
                            @if($colonyName)
                                &mdash; {{ $colonyName }}
                            @endif
                        </h3>
                        <span class="lobby-status-pill lobby-status-pill--{{ $statusCls }}">
                            {{ __('lobby.' . $statusKey) }}
                        </span>
                    </header>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.sol_progress', [
                            'current' => $run->current_tick,
                            'limit'   => $tickLimit,
                        ]) }}
                    </p>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.played_from_to', ['from' => $fromDate, 'to' => $toDate]) }}
                    </p>

                    {{-- Expandable settings snapshot — PicoCSS <details>/<summary> --}}
                    <details>
                        <summary>{{ __('lobby.settings_detail') }}</summary>
                        <ul class="lobby-details-meta">
                            <li>{{ __('lobby.tick_limit') }}: <strong>{{ $tickLimit }}</strong></li>
                            @if($supplyCap !== null)
                                <li>{{ __('lobby.supply_cap') }}: <strong>{{ $supplyCap }}</strong></li>
                            @endif
                            @if($maxPlayers !== null)
                                <li>{{ __('lobby.max_players') }}: <strong>{{ $maxPlayers }}</strong></li>
                            @endif
                            @if($anyBypass)
                                @foreach($bypass as $checkKey => $isActive)
                                    @if($isActive)
                                        <li>
                                            <span class="lobby-bypass-badge">
                                                {{ __('lobby.bypass_active') }}: {{ $checkKey }}
                                            </span>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </details>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ── Empty state ──────────────────────────────────────────────────────── --}}
    @if($active->isEmpty() && $pending->isEmpty() && $finished->isEmpty())
        <div class="lobby-empty">
            <p>{{ __('lobby.no_runs') }}</p>
        </div>
    @endif

</div>
@endsection
