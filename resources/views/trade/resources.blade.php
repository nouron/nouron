@extends('layouts.app')

@section('title', 'Rohstoff-Handel — Nouron')

@section('content')
<div id="trade">

{{-- Tab navigation --}}
<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('trade.resources') }}">
                    <i class="bi bi-box-seam"></i> Rohstoffe
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('trade.researches') }}">
                    <i class="bi bi-flask"></i> Forschungen
                </a>
            </li>
        </ul>
    </div>
</div>

{{-- Filter form --}}
<div class="row mb-3">
    <div class="col-12">
        <form method="GET" action="{{ route('trade.resources') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="colony_id" class="form-label">Kolonie-ID</label>
                <input type="number" id="colony_id" name="colony_id" class="form-control form-control-sm"
                       value="{{ request('colony_id') }}" min="1" placeholder="alle">
            </div>
            <div class="col-auto">
                <label for="direction" class="form-label">Richtung</label>
                <select id="direction" name="direction" class="form-select form-select-sm">
                    <option value="">alle</option>
                    <option value="0" @selected(request('direction') === '0')>Kaufangebot</option>
                    <option value="1" @selected(request('direction') === '1')>Verkaufsangebot</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Filtern
                </button>
            </div>
            <div class="col-auto">
                <a href="{{ route('trade.resources') }}" class="btn btn-sm btn-outline-secondary">
                    Zurücksetzen
                </a>
            </div>
            <div class="col-auto ms-auto">
                {{-- "Angebot erstellen" triggers the modal below --}}
                <button type="button" class="btn btn-sm btn-success"
                        data-bs-toggle="modal" data-bs-target="#modal-create-resource-offer">
                    <i class="bi bi-plus-circle"></i> Angebot erstellen
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Result feedback --}}
@if(isset($result) && $result !== null)
<div class="alert alert-{{ $result ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
    {{ $result ? 'Angebot gespeichert.' : 'Angebot konnte nicht gespeichert werden.' }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Offers table --}}
<div class="row">
    <div class="col-12">
        @if($offers->isEmpty())
            <p class="text-muted fst-italic">Keine Rohstoff-Angebote gefunden.</p>
        @else
        <table class="table table-striped table-hover table-sm">
            <thead class="table-dark">
                <tr>
                    <th>Kolonie</th>
                    <th>Spieler</th>
                    <th>Richtung</th>
                    <th>Rohstoff</th>
                    <th>Menge</th>
                    <th>Preis/Einheit</th>
                    <th>Restriktion</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($offers as $offer)
                @php
                    $res = ($resources ?? [])[$offer->resource_id] ?? null;
                @endphp
                <tr>
                    <td>{{ $offer->colony }}</td>
                    <td>{{ $offer->username }}</td>
                    <td>
                        @if($offer->direction == 0)
                            <span class="badge bg-success">Kauf</span>
                        @else
                            <span class="badge bg-primary">Verkauf</span>
                        @endif
                    </td>
                    <td>
                        @if($res)
                            <span class="resicon-{{ $res->abbreviation }}"
                                  data-bs-toggle="tooltip"
                                  title="{{ __('resources.' . $res->name) }}">{{ $res->abbreviation }}</span>
                            {{ __('resources.' . $res->name) }}
                        @else
                            Res#{{ $offer->resource_id }}
                        @endif
                    </td>
                    <td>{{ $offer->amount }}</td>
                    <td>{{ $offer->price }}</td>
                    <td>{{ $offer->restriction }}</td>
                    <td>
                        @if(isset($user_id) && $offer->user_id == $user_id)
                        <form method="POST" action="{{ route('trade.offer.remove') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="offer_id" value="{{ $offer->id }}">
                            <input type="hidden" name="offer_type" value="resource">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    title="Angebot löschen"
                                    onclick="return confirm('Angebot wirklich löschen?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

</div>{{-- #trade --}}

{{-- Modal: Angebot erstellen --}}
<div class="modal fade" id="modal-create-resource-offer" tabindex="-1" role="dialog"
     aria-labelledby="modal-create-resource-offer-title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('trade.offer.resource') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-create-resource-offer-title">
                        Rohstoff-Angebot erstellen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="offer-resource-id" class="form-label">Rohstoff</label>
                        <select id="offer-resource-id" name="resource_id" class="form-select" required>
                            <option value="">— bitte wählen —</option>
                            @foreach($resources ?? [] as $res)
                            @if(!in_array($res->id, [1, 2, 12]))
                            <option value="{{ $res->id }}">{{ __('resources.' . $res->name) }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="offer-direction" class="form-label">Richtung</label>
                        <select id="offer-direction" name="direction" class="form-select" required>
                            <option value="0">Kaufangebot</option>
                            <option value="1">Verkaufsangebot</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="offer-amount" class="form-label">Menge</label>
                        <input type="number" id="offer-amount" name="amount"
                               class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="offer-price" class="form-label">Preis pro Einheit (Credits)</label>
                        <input type="number" id="offer-price" name="price"
                               class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="offer-restriction" class="form-label">Restriktion</label>
                        <input type="text" id="offer-restriction" name="restriction"
                               class="form-control" placeholder="optional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
