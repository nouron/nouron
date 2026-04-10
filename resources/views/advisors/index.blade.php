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
    <span><i class="bi bi-diagram-3"></i> <strong>{{ $apInfo['strategy'] }}</strong> Strategie-AP</span>
    <span class="text-muted opacity-50">·</span>
    <span><i class="bi bi-rocket"></i> <strong>{{ $apInfo['navigation'] }}</strong> Navigations-AP</span>
    <span class="ms-auto">
        <i class="bi bi-person-badge"></i>
        Slots: <strong>{{ $slotInfo['used'] }}/{{ $slotInfo['max'] }}</strong>
        <span class="text-muted">(CC Lv{{ $slotInfo['cc_level'] }})</span>
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
            'sublabel'     => 'Bau-AP',
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
        [
            'personell_id' => 93,
            'label'        => 'Strategen',
            'sublabel'     => 'Strategie-AP',
            'icon'         => 'bi-diagram-3',
            'border'       => 'border-danger',
            'bg'           => 'bg-danger bg-opacity-10',
            'ap_type'      => 'strategy',
        ],
    ];

    $grouped = $advisors->groupBy('personell_id');
@endphp

{{-- Section header with hire button --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Aktive Berater</h4>
    @if($slotInfo['free'] > 0)
    <button type="button" class="btn btn-sm btn-success"
            data-bs-toggle="modal" data-bs-target="#modal-hire-advisor">
        <i class="bi bi-person-plus"></i> Berater einstellen
    </button>
    @else
    <span class="text-muted small">
        <i class="bi bi-exclamation-triangle"></i>
        Keine freien Slots — CC Level {{ $slotInfo['cc_level'] }} erlaubt max. {{ $slotInfo['max'] }} Berater
    </span>
    @endif
</div>

{{-- Colony advisor cards (2-per-row on desktop) --}}
<div class="row row-cols-1 row-cols-md-2 g-4 mb-4">

@foreach($colonySections as $section)
@php
    $sectionAdvisors = $grouped->get($section['personell_id'], collect());
    $totalAp         = $sectionAdvisors->sum(fn($a) => $a->getApPerTick());
    $creditsCost     = $creditsByPersonellId->get($section['personell_id'], 0);
    $hasAdvisor      = $sectionAdvisors->isNotEmpty();
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
            @if(!$hasAdvisor)
            <p class="text-muted small p-3 mb-0">
                Kein{{ $section['personell_id'] == 36 ? 'e' : '' }} {{ $section['label'] }} aktiv.
                @if($slotInfo['free'] > 0)
                <a href="#" data-bs-toggle="modal" data-bs-target="#modal-hire-advisor"
                   data-personell-id="{{ $section['personell_id'] }}">Jetzt einstellen</a>
                @endif
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
                    $nextRankTicks = $rankThresh[$advisor->rank] ?? null;
                    $progress      = $nextRankTicks
                        ? min(100, (int) round($advisor->active_ticks / $nextRankTicks * 100))
                        : 100;
                    $rankBadge = match($advisor->rank) {
                        1       => ['Junior',  'bg-secondary'],
                        2       => ['Senior',  'bg-info text-dark'],
                        3       => ['Experte', 'bg-warning text-dark'],
                        default => [$rankNames[$advisor->rank] ?? '?', 'bg-secondary'],
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
        @if($hasAdvisor)
        <div class="card-footer py-1 d-flex justify-content-end align-items-center">
            <small class="text-muted">
                <strong>{{ $totalAp }} AP/Tick</strong>
            </small>
        </div>
        @endif
    </div>
</div>
@endforeach

{{-- Pilots card (colony + fleet) --}}
@php
    $navTotalAp = $fleetCommanders->sum(fn($a) => $a->getApPerTick());
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

            {{-- Available pilots on colony --}}
            @if($availablePilots->isNotEmpty())
            <div class="p-2 border-bottom">
                <p class="text-muted small mb-2">
                    <i class="bi bi-person-check"></i> Auf Kolonie verfügbar:
                </p>
                @foreach($availablePilots as $pilot)
                @php
                    $rankBadge = match($pilot->rank) {
                        1 => ['Junior', 'bg-secondary'],
                        2 => ['Senior', 'bg-info text-dark'],
                        3 => ['Experte', 'bg-warning text-dark'],
                        default => ['?', 'bg-secondary'],
                    };
                @endphp
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge {{ $rankBadge[1] }} flex-shrink-0">{{ $rankBadge[0] }}</span>
                    <span class="text-muted small flex-shrink-0">{{ $pilot->getApPerTick() }} AP/Tick</span>
                    @if($userFleets->isNotEmpty())
                    <form method="POST" action="{{ route('advisors.assign-fleet', $pilot->id) }}"
                          class="d-flex align-items-center gap-1 ms-auto">
                        @csrf
                        <select name="fleet_id" class="form-select form-select-sm py-0" style="font-size:0.8rem;min-width:120px" required>
                            @foreach($userFleets as $fleet)
                            <option value="{{ $fleet->id }}">{{ $fleet->fleet ?? 'Flotte #' . $fleet->id }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1" title="Flotte zuweisen">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                    @else
                    <span class="text-muted small ms-auto">Keine Flotten vorhanden</span>
                    @endif
                    <form method="POST" action="{{ route('advisors.fire', $pilot->id) }}"
                          onsubmit="return confirm('Kommandant wirklich entlassen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Entlassen">
                            <i class="bi bi-person-dash"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Fleet commanders --}}
            @if($fleetCommanders->isEmpty() && $availablePilots->isEmpty())
            <p class="text-muted small p-3 mb-0">
                Kein Kommandant aktiv.
                @if($slotInfo['free'] > 0)
                Stelle einen Piloten über
                <a href="#" data-bs-toggle="modal" data-bs-target="#modal-hire-advisor"
                   data-personell-id="89">Berater einstellen</a> ein.
                @endif
            </p>
            @elseif($fleetCommanders->isEmpty())
            <p class="text-muted small p-3 mb-0">
                Kein Kommandant auf Flotte aktiv. Weise einen verfügbaren Piloten zu.
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($fleetCommanders as $advisor)
                @php
                    $nextRankTicks = $rankThresh[$advisor->rank] ?? null;
                    $progress      = $nextRankTicks
                        ? min(100, (int) round($advisor->active_ticks / $nextRankTicks * 100))
                        : 100;
                    $rankBadge = match($advisor->rank) {
                        1       => ['Junior',  'bg-secondary'],
                        2       => ['Senior',  'bg-info text-dark'],
                        3       => ['Experte', 'bg-warning text-dark'],
                        default => [$rankNames[$advisor->rank] ?? '?', 'bg-secondary'],
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
                    <td class="text-end">
                        <form method="POST" action="{{ route('advisors.unassign-fleet', $advisor->id) }}"
                              onsubmit="return confirm('Kommandant von der Flotte abberufen?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                    title="Von Flotte abberufen">
                                <i class="bi bi-arrow-return-left"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
        @if($fleetCommanders->isNotEmpty())
        <div class="card-footer py-1 d-flex justify-content-end align-items-center">
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
                        Neue Berater starten als Junior ({{ config('game.advisor.ap_per_rank.1', 4) }} AP/Tick).
                        Slots belegt: <strong>{{ $slotInfo['used'] }}/{{ $slotInfo['max'] }}</strong>
                        (CC Lv{{ $slotInfo['cc_level'] }}).
                    </p>
                    <div class="mb-3">
                        <label for="personell_id" class="form-label">Typ</label>
                        <select name="personell_id" id="personell_id" class="form-select" required>
                            @foreach($personellTypes as $pId => $pType)
                            @php
                                $label = match($pType->name) {
                                    'techs_engineer'  => 'Ingenieur (Bau-AP)',
                                    'techs_scientist' => 'Wissenschaftler (Forschungs-AP)',
                                    'techs_trader'    => 'Händler (Wirtschafts-AP)',
                                    'techs_stratege'  => 'Stratege (Strategie-AP)',
                                    'techs_pilot'     => 'Pilot / Kommandant (Navigations-AP)',
                                    default           => $pType->name,
                                };
                                $cost = $creditsByPersonellId->get($pId, 0);
                                $alreadyHired = $advisors->contains('personell_id', $pId);
                            @endphp
                            <option value="{{ $pId }}" @if($alreadyHired) disabled @endif>
                                {{ $label }}
                                — @include('partials.res_chip', ['abbreviation' => 'Cr', 'amount' => number_format($cost, 0, ',', '.')])
                                @if($alreadyHired) (bereits eingestellt) @endif
                            </option>
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

<script>
// Pre-select the type in the hire modal when clicking "Jetzt einstellen" links
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-personell-id]').forEach(function (el) {
        el.addEventListener('click', function () {
            var pId = this.getAttribute('data-personell-id');
            var sel = document.getElementById('personell_id');
            if (sel && pId) {
                sel.value = pId;
            }
        });
    });
});
</script>

@endsection
