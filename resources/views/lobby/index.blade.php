@extends('layouts.app')
@section('title', 'Mission Control — Nouron')

@push('styles')
{{-- PicoCSS scoped to .lobby-scope — prevents bleed into Bootstrap navbar/layout --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
<style>
    /* ── Scope PicoCSS resets away from Bootstrap nav/container ──────────────
       PicoCSS and Bootstrap conflict on box-model, form resets, button styles.
       We contain PicoCSS influence to .lobby-scope only.                     */
    .lobby-scope {
        /* Re-apply PicoCSS custom properties locally */
        --pico-font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        --pico-font-size: 1rem;
        --pico-line-height: 1.5;
        font-family: var(--pico-font-family);
    }

    /* PicoCSS sets margin/padding on <main> — we're not using <main> here
       so no conflict, but ensure our wrapper behaves cleanly */
    .lobby-scope article {
        margin-bottom: 1rem;
    }

    /* Card grid for runs */
    .lobby-run-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
        gap: 1rem;
        margin-top: 0.75rem;
    }

    /* Run card — PicoCSS <article> base with additional tweaks */
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

    /* Sol progress text */
    .lobby-sol-progress {
        font-size: 0.85rem;
        color: var(--pico-muted-color, #6c757d);
        margin-bottom: 0.25rem;
    }

    /* Progress bar wrapper — PicoCSS <progress> is block-level */
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

    /* Settings meta list inside cards */
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

    /* Status pill for finished runs */
    .lobby-status-pill {
        display: inline-block;
        padding: 0.15em 0.6em;
        border-radius: 0.3em;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .lobby-status-pill--completed {
        background: var(--pico-ins-color, #27ae60);
        color: #fff;
    }

    .lobby-status-pill--failed {
        background: var(--pico-del-color, #c0392b);
        color: #fff;
    }

    /* Expandable details toggle — uses PicoCSS <details>/<summary> */
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
        margin: 1.5rem 0 0.25rem;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid var(--pico-muted-border-color, #ddd);
    }

    .lobby-section-heading:first-child {
        margin-top: 0;
    }

    /* Disabled "coming soon" button */
    .lobby-btn-disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    /* [x-cloak] support */
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
{{-- Scoping wrapper: PicoCSS influence is contained inside .lobby-scope.
     Bootstrap navbar/layout outside this div is unaffected.               --}}
<div class="lobby-scope" style="max-width: 56rem; margin: 0 auto; padding: 1rem 0 3rem;">

    {{-- ── Page header ──────────────────────────────────────────────────────── --}}
    <hgroup style="margin-bottom: 1.5rem;">
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
                    $tickLimit = $run->settings['tick_limit'] ?? 100;
                    $solPct    = $tickLimit > 0 ? min(100, round(($run->current_tick / $tickLimit) * 100)) : 0;
                    $colonyName = $run->colony->name ?? __('lobby.colony_unnamed');
                @endphp
                <article class="lobby-run-card">
                    <header>
                        <h3>{{ $colonyName }}</h3>
                    </header>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.sol_progress', ['current' => $run->current_tick, 'limit' => $tickLimit]) }}
                    </p>
                    <progress value="{{ $run->current_tick }}" max="{{ $tickLimit }}" title="{{ $solPct }}%"></progress>

                    @if($run->started_at)
                        <p class="lobby-sol-progress" style="margin-top: 0.25rem;">
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
                    $bypass     = $run->settings['bypass'] ?? [];
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
                        @foreach($bypass as $checkKey => $isActive)
                            @if($isActive)
                                <li>
                                    <span class="lobby-bypass-badge">{{ __('lobby.bypass_active') }}: {{ $checkKey }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>

                    <footer>
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

    {{-- ── New Run button (when $allowMultiple and no pending run) ─────────── --}}
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
            <p style="font-size: 0.8rem; color: var(--pico-muted-color, #6c757d); margin-top: 0.35rem;">
                {{ __('lobby.coming_soon') }}
            </p>
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
                    $bypass     = $run->settings['bypass'] ?? [];
                    $anyBypass  = collect($bypass)->contains(true);
                    $colonyName = $run->colony->name ?? __('lobby.colony_unnamed');
                    $statusKey  = $run->status === 'completed' ? 'status_completed' : 'status_failed';
                    $statusClass= $run->status === 'completed' ? 'completed' : 'failed';
                    $fromDate   = $run->started_at ? $run->started_at->format('d.m.Y') : '—';
                    $toDate     = $run->ended_at   ? $run->ended_at->format('d.m.Y')   : '—';
                @endphp

                {{-- Alpine x-data for expandable details --}}
                <article class="lobby-run-card" x-data="{ open: false }">
                    <header>
                        <h3>
                            {{ __('lobby.run_number', ['id' => $run->id]) }}
                            @if($colonyName)
                                &mdash; {{ $colonyName }}
                            @endif
                        </h3>
                        <span class="lobby-status-pill lobby-status-pill--{{ $statusClass }}">
                            {{ __('lobby.' . $statusKey) }}
                        </span>
                    </header>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.sol_progress', ['current' => $run->current_tick, 'limit' => $tickLimit]) }}
                    </p>

                    <p class="lobby-sol-progress">
                        {{ __('lobby.played_from_to', ['from' => $fromDate, 'to' => $toDate]) }}
                    </p>

                    {{-- Expandable details via Alpine + PicoCSS <details> fallback --}}
                    <details>
                        <summary>{{ __('lobby.settings_detail') }}</summary>
                        <ul class="lobby-meta" style="margin-top: 0.5rem; display: block; padding-left: 0;">
                            <li>{{ __('lobby.tick_limit') }}: <strong>{{ $tickLimit }}</strong></li>
                            @if($supplyCap !== null)
                                <li>{{ __('lobby.supply_cap') }}: <strong>{{ $supplyCap }}</strong></li>
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
