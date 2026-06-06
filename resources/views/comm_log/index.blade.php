@extends('layouts.colony')
@section('title', __('comm_log.page_title') . ' — Nouron')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/comm_log.css') }}">
@endpush

@section('content')
<div class="comm-log-page">

    {{-- ── Tab navigation ──────────────────────────────────────────────────── --}}
    <nav class="comm-tabs" aria-label="{{ __('comm_log.page_title') }}">
        <a href="{{ route('comm.log') }}"
           class="comm-tab @if($tab === 'log') comm-tab-active @endif">
            <i class="bi bi-journal-text"></i>
            {{ __('comm_log.tab_log') }}
        </a>
        <a href="{{ route('comm.nexus') }}"
           class="comm-tab @if($tab === 'nexus') comm-tab-active @endif">
            <i class="bi bi-broadcast-pin"></i>
            {{ __('comm_log.tab_nexus') }}
            @if($unreadCount > 0)
                <span class="comm-unread-badge">{{ $unreadCount }}</span>
            @endif
        </a>
    </nav>

    {{-- ── Entry list ───────────────────────────────────────────────────────── --}}
    @if($entries->isEmpty())

        <p class="comm-empty">
            @if($tab === 'nexus')
                {{ __('comm_log.empty_nexus') }}
            @else
                {{ __('comm_log.empty_log') }}
            @endif
        </p>

    @else

        @php $currentSol = null; @endphp

        @foreach($entries as $entry)

            {{-- Sol group divider: emit when sol changes --}}
            @if($entry['sol'] !== $currentSol)
                @php $currentSol = $entry['sol']; @endphp
                <div class="comm-sol-divider" aria-hidden="true">
                    {{ __('comm_log.sol_label', ['sol' => $currentSol]) }}
                </div>
            @endif

            @if($tab === 'nexus')
                {{-- ── Nexus-Funk card ──────────────────────────────────────── --}}
                @php
                    $nexusKey    = 'comm_log.nexus_events.' . $entry['event'];
                    $titleKey    = $nexusKey . '.title';
                    $bodyKey     = $nexusKey . '.body';
                    $badgeKey    = $nexusKey . '.badge';
                    $hasTitle    = Lang::has($titleKey);
                    $hasBody     = Lang::has($bodyKey);
                    $hasBadge    = Lang::has($badgeKey);
                    $badgeText   = $hasBadge  ? __($badgeKey)  : null;
                    $badgeClass  = $badgeText  ? 'comm-nexus-badge comm-nexus-badge--' . Str::slug($badgeText) : 'comm-nexus-badge comm-nexus-badge--neutral';
                @endphp
                <article class="comm-nexus-card">
                    <div class="comm-nexus-card-header">
                        <span class="comm-nexus-card-title">
                            <i class="bi bi-broadcast-pin"></i>
                            @if($hasTitle)
                                {{ __($titleKey) }}
                            @else
                                {{ $entry['event'] }}
                            @endif
                        </span>
                        @if($badgeText)
                            <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                        @endif
                    </div>
                    @if($hasBody)
                        <p class="comm-nexus-card-body">{{ __($bodyKey) }}</p>
                    @endif
                    <div class="comm-nexus-card-footer">
                        <span class="comm-entry-sol">
                            {{ __('comm_log.sol_label', ['sol' => $entry['sol']]) }}
                        </span>
                    </div>
                </article>

            @else
                {{-- ── Protokoll entry row ──────────────────────────────────── --}}
                @php
                    $eventKey   = 'comm_log.events.' . $entry['event'];
                    $hasEvent   = Lang::has($eventKey);
                    $areaIcon   = __('comm_log.area_icons.' . $entry['area']);
                    // Fallback to default icon if the area key is unknown
                    $iconClass  = (Str::startsWith($areaIcon, 'bi-')) ? $areaIcon : 'bi-journal-text';
                @endphp
                <div class="comm-entry">
                    <span class="comm-entry-icon" aria-hidden="true">
                        <i class="bi {{ $iconClass }}"></i>
                    </span>
                    <span class="comm-entry-label">
                        @if($hasEvent)
                            {{ __($eventKey) }}
                        @else
                            {{ $entry['event'] }}
                        @endif
                    </span>
                    <span class="comm-entry-sol">
                        {{ __('comm_log.sol_label', ['sol' => $entry['sol']]) }}
                    </span>
                </div>

            @endif

        @endforeach

    @endif

</div>
@endsection
