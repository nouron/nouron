@extends('layouts.infra')
@section('title', __('lobby.page_title') . ' — Nouron')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/lobby.css') }}">
@endpush

@section('content')
<div>

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
