@extends('layouts.app')
@section('title', 'Berater — Nouron')

@section('content')
<div id="advisors">

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Compact status line --}}
<div class="d-flex flex-wrap gap-3 align-items-center text-muted small mb-4">
    <span><i class="bi bi-hammer"></i> <strong>{{ $apInfo['construction'] }}</strong> Bau-AP</span>
    <span class="text-muted opacity-50">·</span>
    <span><i class="bi bi-microscope"></i> <strong>{{ $apInfo['research'] }}</strong> Forschungs-AP</span>
    <span class="text-muted opacity-50">·</span>
    <span><i class="bi bi-cart3"></i> <strong>{{ $apInfo['economy'] }}</strong> Wirtschafts-AP</span>
    <span class="text-muted opacity-50">·</span>
    <span><i class="bi bi-rocket"></i> <strong>{{ $apInfo['navigation'] }}</strong> Navigations-AP</span>
    <span class="ms-auto">
        <i class="bi bi-boxes"></i> Freie Supply:
        @include('partials.res_chip', ['abbreviation' => 'Sup', 'amount' => $freeSupply])
    </span>
</div>

{{-- Group definitions --}}
@php
    $rankNames  = [1 => 'Junior', 2 => 'Senior', 3 => 'Experte'];
    $rankThresh = config('game.advisor.rank_thresholds', [1 => 10, 2 => 20]);

    $colonySections = [
        [
            'personell_id' => 35,
            'label'        => 'Ingenieure',
            'sublabel'     => 'Konstruktions-AP',
            'icon'         => 'bi-hammer',
            'border'       => 'border-primary',
            'bg'           => 'bg-primary bg-opacity-10',
            'ap_type'      => 'construction',
        ],
        [
            'personell_id' => 36,
            'label'        => 'Wissenschaftler',
            'sublabel'     => 'Forschungs-AP',
            'icon'         => 'bi-microscope',
            'border'       => 'border-success',
            'bg'           => 'bg-success bg-opacity-10',
            'ap_type'      => 'research',
        ],
        [
            'personell_id' => 92,
            'label'        => 'Händler',
            'sublabel'     => 'Wirtschafts-AP',
            'icon'         => 'bi-cart3',
            'border'       => 'border-warning',
            'bg'           => 'bg-warning bg-opacity-10',
            'ap_type'      => 'economy',
        ],
    ];

    $grouped = $advisors->groupBy('personell_id');
@endphp

{{-- Section header with hire button --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Aktive Berater</h4>
    @if($freeSupply >= $costPerAdvisor)
    <button type="button" class="btn btn-sm btn-success"
            data-bs-toggle="modal" data-bs-target="#modal-hire-advisor">
        <i class="bi bi-person-plus"></i> Berater einstellen
    </button>
    @else
    <span class="text-muted small">
        <i class="bi bi-exclamation-triangle"></i>
        Nicht genug Supply — @include('partials.res_chip', ['abbreviation' => 'Sup', 'amount' => $costPerAdvisor]) benötigt
    </span>
    @endif
</div>

{{-- Colony advisor cards (2-per-row on desktop) --}}
<div class="row row-cols-1 row-cols-md-2 g-4 mb-4">

@foreach($colonySections as $section)
@php
    $sectionAdvisors = $grouped->get($section['personell_id'], collect());
    $totalAp         = $sectionAdvisors->sum(fn($a) => $a->getApPerTick());
    $supplyCost      = $sectionAdvisors->count() * $costPerAdvisor;
@endphp
<div class="col">
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">
                <i class="bi {{ $section['icon'] }}"></i> {{ $section['label'] }}
            </span>
            <span class="d-flex align-items-center gap-2">
                <small class="text-muted">{{ $section['sublabel'] }}:</small>
                <span class="fw-bold">{{ $totalAp }} AP/Tick</span>
            </span>
        </div>
        <div class="card-body p-0">
            @if($sectionAdvisors->isEmpty())
            <p class="text-muted small p-3 mb-0">
                Keine {{ $section['label'] }} aktiv.
            </p>
            @else
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rang</th>
                        <th class="text-center">AP/Tick</th>
                        <th class="text-center">Ticks</th>
                        <th class="text-center">Aufstieg</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($sectionAdvisors as $advisor)
                @php
                    $rankName      = $rankNames[$advisor->rank] ?? '?';
                    $nextRankTicks = $rankThresh[$advisor->rank] ?? null;
                    $progress      = $nextRankTicks
                        ? min(100, (int) round($advisor->active_ticks / $nextRankTicks * 100))
                        : 100;
                    $rankBadge = match($advisor->rank) {
                        1       => ['Junior',  'bg-secondary'],
                        2       => ['Senior',  'bg-info text-dark'],
                        3       => ['Experte', 'bg-warning text-dark'],
                        default => [$rankName, 'bg-secondary'],
                    };
                @endphp
                <tr>
                    <td><span class="badge {{ $rankBadge[1] }}">{{ $rankBadge[0] }}</span></td>
                    <td class="text-center fw-semibold">{{ $advisor->getApPerTick() }}</td>
                    <td class="text-center text-muted small">{{ $advisor->active_ticks }}</td>
                    <td class="text-center" style="min-width:100px">
                        @if($advisor->rank < 3 && $nextRankTicks)
                        <div class="progress" style="height:6px"
                             title="{{ $advisor->active_ticks }}/{{ $nextRankTicks }} Ticks">
                            <div class="progress-bar" style="width:{{ $progress }}%"></div>
                        </div>
                        <span class="text-muted" style="font-size:0.7rem">
                            {{ $advisor->active_ticks }}/{{ $nextRankTicks }}
                        </span>
                        @else
                        <span class="badge bg-warning text-dark" style="font-size:0.65rem">Max</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($advisor->unavailable_until_tick)
                            <span class="badge bg-warning text-dark" style="font-size:0.65rem">
                                Inaktiv bis T{{ $advisor->unavailable_until_tick }}
                            </span>
                        @else
                            <span class="badge bg-success" style="font-size:0.65rem">Aktiv</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('advisors.fire', $advisor->id) }}"
                              onsubmit="return confirm('Berater wirklich entlassen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1"
                                    title="Entlassen">
                                <i class="bi bi-person-dash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
        @if($sectionAdvisors->isNotEmpty())
        <div class="card-footer py-1 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Kosten: @include('partials.res_chip', ['abbreviation' => 'Sup', 'amount' => $supplyCost])
            </small>
            <small class="text-muted">
                {{ $sectionAdvisors->count() }} Berater · <strong>{{ $totalAp }} AP/Tick</strong>
            </small>
        </div>
        @endif
    </div>
</div>
@endforeach

{{-- Fleet commanders card --}}
@php
    $navTotalAp      = $fleetCommanders->sum(fn($a) => $a->getApPerTick());
    $navSupplyCost   = $fleetCommanders->count() * $costPerAdvisor;
@endphp
<div class="col">
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">
                <i class="bi bi-rocket"></i> Kommandanten
            </span>
            <span class="d-flex align-items-center gap-2">
                <small class="text-muted">Navigations-AP:</small>
                <span class="fw-bold">{{ $navTotalAp }} AP/Tick</span>
            </span>
        </div>
        <div class="card-body p-0">
            @if($fleetCommanders->isEmpty())
            <p class="text-muted small p-3 mb-0">
                Keine Kommandanten aktiv. Weise Piloten in der
                <a href="{{ route('fleet.index') }}">Flottenkonfiguration</a> zu.
            </p>
            @else
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Flotte</th>
                        <th>Rang</th>
                        <th class="text-center">AP/Tick</th>
                        <th class="text-center">Ticks</th>
                        <th class="text-center">Aufstieg</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($fleetCommanders as $advisor)
                @php
                    $rankName      = $rankNames[$advisor->rank] ?? '?';
                    $nextRankTicks = $rankThresh[$advisor->rank] ?? null;
                    $progress      = $nextRankTicks
                        ? min(100, (int) round($advisor->active_ticks / $nextRankTicks * 100))
                        : 100;
                    $rankBadge = match($advisor->rank) {
                        1       => ['Junior',  'bg-secondary'],
                        2       => ['Senior',  'bg-info text-dark'],
                        3       => ['Experte', 'bg-warning text-dark'],
                        default => [$rankName, 'bg-secondary'],
                    };
                @endphp
                <tr>
                    <td>
                        <i class="bi bi-send text-muted small"></i>
                        {{ $advisor->fleet->fleet ?? 'Flotte #' . $advisor->fleet_id }}
                    </td>
                    <td><span class="badge {{ $rankBadge[1] }}">{{ $rankBadge[0] }}</span></td>
                    <td class="text-center fw-semibold">{{ $advisor->getApPerTick() }}</td>
                    <td class="text-center text-muted small">{{ $advisor->active_ticks }}</td>
                    <td class="text-center" style="min-width:100px">
                        @if($advisor->rank < 3 && $nextRankTicks)
                        <div class="progress" style="height:6px"
                             title="{{ $advisor->active_ticks }}/{{ $nextRankTicks }} Ticks">
                            <div class="progress-bar bg-dark" style="width:{{ $progress }}%"></div>
                        </div>
                        <span class="text-muted" style="font-size:0.7rem">
                            {{ $advisor->active_ticks }}/{{ $nextRankTicks }}
                        </span>
                        @else
                        <span class="badge bg-warning text-dark" style="font-size:0.65rem">Max</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($advisor->unavailable_until_tick)
                            <span class="badge bg-warning text-dark" style="font-size:0.65rem">
                                Inaktiv bis T{{ $advisor->unavailable_until_tick }}
                            </span>
                        @else
                            <span class="badge bg-success" style="font-size:0.65rem">Aktiv</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
        @if($fleetCommanders->isNotEmpty())
        <div class="card-footer py-1 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Kosten: @include('partials.res_chip', ['abbreviation' => 'Sup', 'amount' => $navSupplyCost])
            </small>
            <small class="text-muted">
                {{ $fleetCommanders->count() }} Kommandanten · <strong>{{ $navTotalAp }} AP/Tick</strong>
            </small>
        </div>
        @endif
    </div>
</div>

</div>{{-- /.row --}}

</div>{{-- #advisors --}}

{{-- Hire modal --}}
<div class="modal fade" id="modal-hire-advisor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Berater einstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('advisors.hire') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        Jeder Berater kostet
                        @include('partials.res_chip', ['abbreviation' => 'Sup', 'amount' => $costPerAdvisor]).
                        Freie Supply:
                        @include('partials.res_chip', ['abbreviation' => 'Sup', 'amount' => $freeSupply]).
                        Neue Berater starten als Junior ({{ config('game.advisor.ap_per_rank.1', 4) }} AP/Tick).
                    </p>
                    <div class="mb-3">
                        <label for="personell_id" class="form-label">Typ</label>
                        <select name="personell_id" id="personell_id" class="form-select" required>
                            @foreach($personellTypes as $pId => $pType)
                            @php
                                $label = match($pType->name) {
                                    'techs_engineer'  => 'Ingenieur (Konstruktion)',
                                    'techs_scientist' => 'Wissenschaftler (Forschung)',
                                    'techs_trader'    => 'Händler (Wirtschaft)',
                                    default           => $pType->name,
                                };
                            @endphp
                            <option value="{{ $pId }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> Einstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
