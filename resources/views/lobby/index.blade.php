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

    /* PicoCSS <article> cards — force light background regardless of system color scheme */
    .lobby-run-card {
        margin: 0;
        padding: 1.25rem;
        background: #ffffff;
        color: #333;
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
        color: inherit;
    }

    .lobby-run-card header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #1a1a2e;
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

    /* Colony rename form (pending run card) */
    .lobby-rename-form {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin: 0.5rem 0 0;
    }
    .lobby-rename-form input {
        flex: 1;
        min-width: 0;
        margin: 0;
        font-size: 1rem; /* ≥16px — prevents iOS focus zoom */
    }
    .lobby-rename-form button {
        margin: 0;
        white-space: nowrap;
        width: auto;
    }
    .lobby-rename-error {
        color: var(--pico-del-color, #c0392b);
        font-size: 0.8rem;
        margin: 0.25rem 0 0;
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

    /* ── Highscore table ───────────────────────────────────────────────────── */
    .lobby-highscore-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .lobby-highscore-table th,
    .lobby-highscore-table td {
        padding: 0.55rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--pico-muted-border-color, #ddd);
        vertical-align: middle;
    }

    .lobby-highscore-table thead th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--pico-muted-color, #6c757d);
        border-bottom: 2px solid var(--pico-muted-border-color, #ddd);
    }

    .lobby-highscore-table tbody tr:last-child td {
        border-bottom: none;
    }

    .lobby-highscore-table tbody tr:hover td {
        background: var(--pico-card-background-color, #f9f9f9);
    }

    /* Score column — right-align and slightly bolder */
    .lobby-highscore-score {
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }

    /* Ended date sub-line */
    .lobby-hs-meta {
        font-size: 0.78rem;
        color: var(--pico-muted-color, #6c757d);
    }

    /* Empty state for highscore section */
    .lobby-hs-empty {
        font-size: 0.9rem;
        color: var(--pico-muted-color, #6c757d);
        margin-top: 0.75rem;
    }
</style>
@endpush

@section('content')
{{--
    .lobby-scope wrapper:
    - Contains PicoCSS custom-property influence.
    - No Bootstrap grid classes used inside — PicoCSS article/grid only.
    - Alpine.js is already loaded globally by layouts/app.blade.php.
--}}
<div class="lobby-scope" data-theme="light" style="max-width: 56rem; margin: 0 auto; padding: 2rem 0 3rem;">

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
                    $tickLimit   = $run->settings['tick_limit'] ?? 100;
                    $displayTick = min($run->current_tick, $tickLimit);
                    $solPct      = $tickLimit > 0
                        ? min(100, round(($run->current_tick / $tickLimit) * 100))
                        : 0;
                    $colonyName  = $run->colony->name ?? __('lobby.colony_unnamed');
                @endphp
                <article class="lobby-run-card">
                    <header>
                        <h3>{{ $colonyName }}</h3>
                    </header>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.sol_progress', [
                            'current' => $displayTick,
                            'limit'   => $tickLimit,
                        ]) }}
                    </p>
                    <progress
                        value="{{ $displayTick }}"
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
                        <form method="POST"
                              action="{{ route('lobby.abandon', $run->id) }}"
                              style="margin: 0;"
                              onsubmit="return confirm(@json(__('lobby.abandon_confirm')))">
                            @csrf
                            <button type="submit" class="secondary outline" style="color:#c0392b; border-color:#c0392b;">
                                {{ __('lobby.abandon_button') }}
                            </button>
                        </form>
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

                    {{-- Colony rename — moved here from the removed colony/index screen --}}
                    <form method="POST" action="{{ route('colony.rename') }}" class="lobby-rename-form">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="name"
                               value="{{ old('name', $colonyName) }}"
                               minlength="2" maxlength="50" required
                               aria-label="{{ __('lobby.rename_label') }}">
                        <button type="submit" class="secondary outline">{{ __('lobby.rename_button') }}</button>
                    </form>
                    @error('name')
                        <p class="lobby-rename-error">{{ $message }}</p>
                    @enderror

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
    @if($allowMultiple && $pending->isEmpty() && $active->isEmpty())
        <div style="margin-top: 1.5rem;">
            {{-- POST to run.new — controller resets the colony and creates a fresh Run. --}}
            <form
                method="POST"
                action="{{ route('run.new') }}"
                style="display: inline;"
                onsubmit="return confirm(@json(__('lobby.new_run_confirm')))"
            >
                @csrf
                <button type="submit">
                    {{ __('lobby.new_run_button') }}
                </button>
            </form>
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

    {{-- ── Highscore table ─────────────────────────────────────────────────── --}}
    {{-- Feature 1: Shows the last 10 finished runs with pre-calculated scores. --}}
    <h2 class="lobby-section-heading" style="margin-top: 2.5rem;">
        {{ __('lobby.highscore_title') }}
    </h2>

    @if($finishedRuns->isNotEmpty())
        <div style="overflow-x: auto; margin-top: 0.75rem;">
            <table class="lobby-highscore-table">
                <thead>
                    <tr>
                        <th>{{ __('lobby.highscore_col_mission') }}</th>
                        <th>{{ __('lobby.highscore_col_status') }}</th>
                        <th>{{ __('lobby.highscore_col_sol') }}</th>
                        <th>{{ __('lobby.highscore_col_tasks') }}</th>
                        <th class="lobby-highscore-score">{{ __('lobby.highscore_col_score') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($finishedRuns as $entry)
                        @php
                            $statusCls = $entry['status'] === 'completed' ? 'completed' : 'failed';
                            $statusKey = $entry['status'] === 'completed' ? 'status_completed' : 'status_failed';
                            $endedDate = $entry['ended_at'] ? $entry['ended_at']->format('d.m.Y') : '—';
                        @endphp
                        <tr>
                            <td>
                                {{ __('lobby.run_number', ['id' => $entry['id']]) }}
                                @if($entry['ended_at'])
                                    <br>
                                    <small class="lobby-hs-meta">{{ __('lobby.highscore_ended', ['date' => $endedDate]) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="lobby-status-pill lobby-status-pill--{{ $statusCls }}">
                                    {{ __('lobby.' . $statusKey) }}
                                </span>
                            </td>
                            <td>{{ $entry['current_tick'] }} / {{ $entry['tick_limit'] }}</td>
                            <td>
                                {{ $entry['completed_objectives'] }}
                                @if($entry['total_objectives'] > 0)
                                    / {{ $entry['total_objectives'] }}
                                @endif
                            </td>
                            <td class="lobby-highscore-score">
                                <strong>{{ number_format($entry['score']) }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="lobby-hs-empty">{{ __('lobby.highscore_no_runs') }}</p>
    @endif

    {{-- ── Empty state ──────────────────────────────────────────────────────── --}}
    @if($active->isEmpty() && $pending->isEmpty() && $finished->isEmpty())
        <div class="lobby-empty">
            <p>{{ __('lobby.no_runs') }}</p>
        </div>
    @endif

</div>
@endsection
