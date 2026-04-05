@extends('layouts.app')

@section('title', 'Forschungs-Handel — Nouron')

@section('content')
<div id="trade">

@include('trade.partials.tabs')

{{-- Filter form --}}
<div class="row mb-3">
    <div class="col-12">
        <form method="GET" action="{{ route('trade.researches') }}" class="row g-2 align-items-end">
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
                <a href="{{ route('trade.researches') }}" class="btn btn-sm btn-outline-secondary">
                    Zurücksetzen
                </a>
            </div>
            @if($myColonies->isNotEmpty())
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-sm btn-success"
                        data-bs-toggle="modal" data-bs-target="#modal-create-research-offer">
                    <i class="bi bi-plus-circle"></i> Angebot erstellen
                </button>
            </div>
            @endif
        </form>
    </div>
</div>

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
@if($errors->has('trade'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ $errors->first('trade') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Offers table --}}
<div class="row">
    <div class="col-12">
        @if($offers->isEmpty())
            <p class="text-muted fst-italic">Keine Forschungs-Angebote gefunden.</p>
        @else
        <table class="table table-striped table-hover table-sm">
            <thead class="table-dark">
                <tr>
                    <th>Kolonie</th>
                    <th>Spieler</th>
                    <th>Richtung</th>
                    <th>Forschung</th>
                    <th>Menge</th>
                    <th>Preis/Einheit</th>
                    <th>Restriktion</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($offers as $offer)
                @php
                    $res = ($researches ?? [])[$offer->research_id] ?? null;
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
                            {{ __('techtree.' . $res->name) }}
                        @else
                            Forschung#{{ $offer->research_id }}
                        @endif
                    </td>
                    <td>{{ $offer->amount }}</td>
                    <td>{{ $offer->price }}</td>
                    <td>{{ $offer->restriction }}</td>
                    <td>
                        @if(isset($user_id) && (int) $offer->user_id === $user_id)
                        <form method="POST" action="{{ route('trade.offer.remove') }}" class="d-inline"
                              onsubmit="return confirm('Angebot wirklich löschen?')">
                            @csrf
                            <input type="hidden" name="colony_id"   value="{{ $offer->colony_id }}">
                            <input type="hidden" name="direction"   value="{{ $offer->direction }}">
                            <input type="hidden" name="research_id" value="{{ $offer->research_id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Angebot löschen">
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
@if($myColonies->isNotEmpty())
<div class="modal fade" id="modal-create-research-offer" tabindex="-1" role="dialog"
     aria-labelledby="modal-create-research-offer-title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('trade.offer.research') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-create-research-offer-title">
                        Forschungs-Angebot erstellen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i>
                        Die genaue Forschungshandel-Mechanik (Level-Transfer, Wissenstransfer oder Lizenz-Modell)
                        wird in Phase 3 definiert. Angebote können bereits jetzt eingestellt werden.
                    </div>
                    <input type="hidden" name="colony_id" value="{{ $myColonies->first()->id }}">
                    <div class="mb-3">
                        <label for="offer-research-id" class="form-label">Forschung</label>
                        <select id="offer-research-id" name="research_id" class="form-select" required>
                            <option value="">— bitte wählen —</option>
                            @foreach($researches ?? [] as $research)
                            <option value="{{ $research->id }}">{{ __('techtree.' . $research->name) }}</option>
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
@endif
@endsection
