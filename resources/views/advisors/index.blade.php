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

{{-- AP Summary --}}
<div class="row mb-4">
    <div class="col-12">
        <h4>Aktionspunkte pro Tick</h4>
        <div class="d-flex gap-3 flex-wrap">
            <div class="card text-center px-4 py-2">
                <div class="fs-4 fw-bold">{{ $apInfo['construction'] }}</div>
                <div class="text-muted small"><i class="bi bi-hammer"></i> Konstruktion</div>
            </div>
            <div class="card text-center px-4 py-2">
                <div class="fs-4 fw-bold">{{ $apInfo['research'] }}</div>
                <div class="text-muted small"><i class="bi bi-flask"></i> Forschung</div>
            </div>
            <div class="card text-center px-4 py-2">
                <div class="fs-4 fw-bold">{{ $apInfo['economy'] }}</div>
                <div class="text-muted small"><i class="bi bi-cart3"></i> Wirtschaft</div>
            </div>
            <div class="card text-center px-4 py-2 ms-auto">
                <div class="fs-4 fw-bold {{ $freeSupply < $costPerAdvisor ? 'text-danger' : 'text-success' }}">
                    {{ $freeSupply }}
                </div>
                <div class="text-muted small"><i class="bi bi-boxes"></i> Freie Supply</div>
            </div>
        </div>
    </div>
</div>

{{-- Active advisors --}}
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">Aktive Berater</h4>
        @if($freeSupply >= $costPerAdvisor)
        <button type="button" class="btn btn-sm btn-success"
                data-bs-toggle="modal" data-bs-target="#modal-hire-advisor">
            <i class="bi bi-person-plus"></i> Berater einstellen
        </button>
        @else
        <span class="text-muted small">Nicht genug Supply für neuen Berater ({{ $costPerAdvisor }} benötigt)</span>
        @endif
    </div>

    @if($advisors->isEmpty())
    <div class="col-12">
        <p class="text-muted">Keine Berater aktiv. Stelle Berater ein, um Aktionspunkte zu generieren.</p>
    </div>
    @else
    <div class="col-12">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th>Typ</th>
                    <th>Rang</th>
                    <th class="text-center">AP/Tick</th>
                    <th class="text-center">Aktive Ticks</th>
                    <th class="text-center">Nächster Rang</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @php
                $rankNames    = [1 => 'Junior', 2 => 'Senior', 3 => 'Experte'];
                $rankThresh   = config('game.advisor.rank_thresholds', [1 => 10, 2 => 20]);
            @endphp
            @foreach($advisors as $advisor)
            @php
                $typeName = $advisor->personell->name ?? 'Unbekannt';
                $rankName = $rankNames[$advisor->rank] ?? 'Unbekannt';
                $nextRankTicks = $rankThresh[$advisor->rank] ?? null;
                $progress = $nextRankTicks
                    ? min(100, (int) round($advisor->active_ticks / $nextRankTicks * 100))
                    : 100;
            @endphp
            <tr>
                <td>{{ $typeName }}</td>
                <td>{{ $rankName }}</td>
                <td class="text-center">{{ $advisor->getApPerTick() }}</td>
                <td class="text-center">{{ $advisor->active_ticks }}</td>
                <td class="text-center" style="min-width:120px">
                    @if($advisor->rank < 3 && $nextRankTicks)
                    <div class="progress" style="height:8px" title="{{ $advisor->active_ticks }}/{{ $nextRankTicks }} Ticks">
                        <div class="progress-bar bg-info" style="width:{{ $progress }}%"></div>
                    </div>
                    <small class="text-muted">{{ $advisor->active_ticks }}/{{ $nextRankTicks }}</small>
                    @else
                    <span class="text-muted small">Max</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($advisor->unavailable_until_tick)
                        <span class="badge bg-warning text-dark">Inaktiv bis Tick {{ $advisor->unavailable_until_tick }}</span>
                    @else
                        <span class="badge bg-success">Aktiv</span>
                    @endif
                </td>
                <td class="text-end">
                    <form method="POST" action="{{ route('advisors.fire', $advisor->id) }}"
                          onsubmit="return confirm('Berater wirklich entlassen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-person-dash"></i> Entlassen
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

</div>

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
                        Jeder Berater kostet <strong>{{ $costPerAdvisor }} Supply</strong>.
                        Freie Supply: <strong>{{ $freeSupply }}</strong>.
                        Neue Berater starten als Junior ({{ config('game.advisor.ap_per_rank.1', 4) }} AP/Tick).
                    </p>
                    <div class="mb-3">
                        <label for="personell_id" class="form-label">Typ</label>
                        <select name="personell_id" id="personell_id" class="form-select" required>
                            @foreach($personellTypes as $pId => $pType)
                            <option value="{{ $pId }}">{{ $pType->name }}</option>
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
