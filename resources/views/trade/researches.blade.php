@extends('layouts.app')

@section('title', 'Forschungs-Handel — Nouron')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('trade.resources') }}">
                    <i class="bi bi-box-seam"></i> Rohstoffe
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('trade.researches') }}">
                    <i class="bi bi-flask"></i> Forschungen
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <form method="POST" action="{{ route('trade.researches') }}" class="row g-2 align-items-end">
            @csrf
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
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @if(isset($result) && $result !== null)
            <div class="alert alert-{{ $result ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                {{ $result ? 'Angebot gespeichert.' : 'Angebot konnte nicht gespeichert werden.' }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($offers->isEmpty())
            <p class="text-muted fst-italic">Keine Forschungs-Angebote gefunden.</p>
        @else
        <table class="table table-striped table-hover table-sm">
            <thead class="table-dark">
                <tr>
                    <th>Kolonie</th>
                    <th>Spieler</th>
                    <th>Richtung</th>
                    <th>Forschungs-ID</th>
                    <th>Menge</th>
                    <th>Preis</th>
                    <th>Restriktion</th>
                </tr>
            </thead>
            <tbody>
                @foreach($offers as $offer)
                <tr>
                    <td>{{ $offer->colony }}</td>
                    <td>{{ $offer->username }}</td>
                    <td>
                        @if($offer->direction === 0)
                            <span class="badge bg-success">Kauf</span>
                        @else
                            <span class="badge bg-primary">Verkauf</span>
                        @endif
                    </td>
                    <td>{{ $offer->research_id }}</td>
                    <td>{{ $offer->amount }}</td>
                    <td>{{ $offer->price }}</td>
                    <td>{{ $offer->restriction }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
