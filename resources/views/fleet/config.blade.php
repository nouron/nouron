@extends('layouts.app')

@section('title', 'Flotten-Konfiguration — Nouron')

@push('styles')
<style>
    #fleetconfig .fc-item { cursor: pointer; }
    #fleetconfig .fc-item:hover { background-color: #f8f9fa; }
    #fleetconfig .fc-item.selected { background-color: #d1ecf1; }
    #fleetconfig .countOnColony,
    #fleetconfig .countInFleet,
    #fleetconfig .countInFleetCargo { text-align: center; }
    #fleetconfig .tech.building,
    #fleetconfig .tech.ship,
    #fleetconfig .tech.research,
    #fleetconfig .tech.personell { background: transparent; background-image: none; }
</style>
@endpush

@section('content')
<div id="fleetconfig">

    @if(!$fleet)
        <div class="alert alert-warning">Flotte nicht gefunden.</div>
    @else

    {{-- Hidden metadata read by fleets.js --}}
    <span id="fleet_id" class="d-none">{{ $fleet->id }}</span>
    <span id="colony_id" class="d-none">{{ $colony ? $colony->id : '' }}</span>

    <h2>
        <i class="bi bi-send"></i> {{ $fleet->fleet }}
        <small class="text-muted fs-5">
            Position: {{ $fleet->x }},{{ $fleet->y }},{{ $fleet->spot }}
        </small>
    </h2>

    @if($fleetIsInColonyOrbit && $colony)
    <div class="alert alert-info py-2">
        Flotte befindet sich in der Umlaufbahn von Kolonie
        <strong>{{ $colony->name }}</strong> (ID {{ $colony->id }}).
    </div>
    @endif

    <div class="row mt-3">

        {{-- ── Left panel: Colony inventory ─────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-building"></i>
                    {{ $colony ? $colony->name : 'Keine Kolonie' }}
                    — Bestand
                </div>
                <div class="card-body p-0">

                    {{-- Ships --}}
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th colspan="3">Schiffe</th>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <th class="countOnColony">Kolonie</th>
                                <th class="countInFleet">Flotte</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ships ?? [] as $id => $ship)
                            <tr class="fc-item tech ship"
                                data-type="ship" data-id="{{ $id }}" data-cargo="0">
                                <td class="fc-mid">{{ $ship['name'] }}</td>
                                <td class="countOnColony">
                                    <span class="shipOnColony-{{ $id }}">…</span>
                                </td>
                                <td class="countInFleet">
                                    <span class="shipInFleet-{{ $id }}">…</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-muted small fst-italic">
                                    Keine Schiffsdaten geladen.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <hr class="my-0">

                    {{-- Personell --}}
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th colspan="3">Personal</th>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <th class="countOnColony">Kolonie</th>
                                <th class="countInFleet">Flotte</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($personells ?? [] as $id => $personell)
                            <tr class="fc-item tech personell"
                                data-type="personell" data-id="{{ $id }}" data-cargo="0">
                                <td class="fc-mid">{{ $personell['name'] }}</td>
                                <td class="countOnColony">
                                    <span class="personellOnColony-{{ $id }}">…</span>
                                </td>
                                <td class="countInFleet">
                                    <span class="personellInFleet-{{ $id }}">…</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-muted small fst-italic">
                                    Keine Personaldaten geladen.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <hr class="my-0">

                    {{-- Researches (cargo) --}}
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th colspan="3">Forschungen (Cargo)</th>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <th class="countOnColony">Kolonie</th>
                                <th class="countInFleetCargo">Fracht</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($researches ?? [] as $id => $research)
                            <tr class="fc-item tech research"
                                data-type="research" data-id="{{ $id }}" data-cargo="1">
                                <td class="fc-mid">{{ __('techtree.' . $research['name']) }}</td>
                                <td class="countOnColony">
                                    <span class="researchOnColony-{{ $id }}">…</span>
                                </td>
                                <td class="countInFleetCargo">
                                    <span class="researchInFleetCargo-{{ $id }}">…</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-muted small fst-italic">
                                    Keine Forschungsdaten geladen.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <hr class="my-0">

                    {{-- Resources --}}
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th colspan="3">Ressourcen</th>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <th class="countOnColony">Kolonie</th>
                                <th class="countInFleetCargo">Fracht</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resources as $res)
                            {{-- Only tradeable resources make sense as fleet cargo --}}
                            @if(!in_array($res->id, [1, 2, 12]))
                            <tr class="fc-item"
                                data-type="resource" data-id="{{ $res->id }}" data-cargo="1">
                                <td class="fc-mid">
                                    <span class="resicon-{{ $res->abbreviation }}"
                                          title="{{ __('resources.' . $res->name) }}">{{ $res->abbreviation }}</span>
                                    {{ __('resources.' . $res->name) }}
                                </td>
                                <td class="countOnColony">
                                    <span class="resourceOnColony-{{ $res->id }}">…</span>
                                </td>
                                <td class="countInFleetCargo">
                                    <span class="resourceInFleetCargo-{{ $res->id }}">…</span>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>

                </div>{{-- .card-body --}}
            </div>
        </div>{{-- col-md-6 --}}

        {{-- ── Right panel: Transfer controls ──────────────────────────── --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-arrow-left-right"></i> Übertragen
                </div>
                <div class="card-body">

                    <p class="text-muted small mb-2">
                        Zeile auswählen, Menge einstellen, dann Pfeil klicken.
                    </p>

                    {{-- Selected item label --}}
                    <div class="mb-3">
                        <span class="badge bg-secondary" id="fc-label">Kein Item gewählt</span>
                    </div>

                    {{-- Quantity buttons --}}
                    <div class="btn-group mb-3" role="group" aria-label="Menge">
                        @foreach([1, 5, 10, 25, 50, 100] as $qty)
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary fc-qty-btn{{ $qty === 1 ? ' active' : '' }}"
                                data-qty="{{ $qty }}">
                            {{ $qty }}
                        </button>
                        @endforeach
                    </div>

                    {{-- Transfer direction buttons --}}
                    <div class="d-flex gap-2">
                        <button id="fc-to-fleet" class="btn btn-primary" disabled>
                            <i class="bi bi-arrow-right-circle"></i>
                            zur Flotte
                        </button>
                        <button id="fc-to-colony" class="btn btn-secondary" disabled>
                            <i class="bi bi-arrow-left-circle"></i>
                            zur Kolonie
                        </button>
                    </div>

                </div>
            </div>
        </div>{{-- col-md-6 --}}

    </div>{{-- .row --}}

    @endif{{-- $fleet --}}

</div>{{-- #fleetconfig --}}
@endsection
