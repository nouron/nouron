@extends('layouts.app')
@section('title', 'Flotten-Konfiguration — Nouron')

@push('styles')
<style>
#fc-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(5,15,30,0.95);
    border: 1px solid rgba(80,130,220,0.25);
    border-radius: 4px;
    margin-bottom: 10px;
    position: sticky;
    top: 96px;
    z-index: 50;
    flex-wrap: wrap;
}
#fc-qty { display: flex; gap: 4px; }
.fc-qty-btn {
    border: 1px solid rgba(80,130,220,0.4);
    background: transparent;
    color: #8aabcc;
    padding: 3px 10px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.85em;
}
.fc-qty-btn.active {
    background: rgba(80,130,220,0.3);
    color: #cce0ff;
    border-color: rgba(80,130,220,0.7);
}
.fc-transfer-btn {
    border: 1px solid rgba(80,180,100,0.4);
    background: transparent;
    color: #88cc88;
    padding: 3px 12px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.85em;
    white-space: nowrap;
}
.fc-transfer-btn:disabled { opacity: 0.35; cursor: default; }
.fc-transfer-btn:not(:disabled):hover { background: rgba(80,180,100,0.2); }
#fc-label {
    flex: 1;
    text-align: center;
    color: #aabbcc;
    font-size: 0.85em;
    font-style: italic;
}

.fc-header {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    padding: 4px 10px;
    font-size: 0.75em;
    color: rgba(80,130,220,0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid rgba(80,130,220,0.15);
}
.fc-header .fc-colony { text-align: right; }
.fc-header .fc-mid    { text-align: center; }
.fc-header .fc-fleet  { text-align: left; padding-left: 8px; }

.fc-section {
    padding: 8px 10px 3px;
    font-size: 0.72em;
    color: rgba(80,130,220,0.7);
    text-transform: uppercase;
    letter-spacing: 2px;
    border-top: 1px solid rgba(80,130,220,0.12);
    margin-top: 4px;
}
.fc-section:first-of-type { border-top: none; margin-top: 0; }

.fc-item {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    align-items: center;
    padding: 7px 10px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.1s;
    user-select: none;
}
.fc-item:hover    { background: rgba(80,130,220,0.08); }
.fc-item.selected { background: rgba(80,130,220,0.2); border-color: rgba(80,130,220,0.3); }

.fc-colony { text-align: right; font-size: 0.9em; color: #9ab; padding-right: 8px; min-width: 40px; }
.fc-mid    { text-align: center; font-size: 0.85em; color: #ccc; padding: 0 4px; }
.fc-fleet  { text-align: left; font-size: 0.9em; color: #9ab; padding-left: 8px; min-width: 40px; }

.fc-item.selected .fc-colony,
.fc-item.selected .fc-fleet { color: #cce; }
.fc-item.selected .fc-mid   { color: #fff; }
</style>
@endpush

@section('content')
<div id="fleetconfig">

    <span class="d-none" id="fleet_id">{{ $fleet->id }}</span>
    <span class="d-none" id="colony_id">{{ $colony ? $colony->id : '' }}</span>

    <h2>
        <i class="bi bi-send"></i> {{ $fleet->fleet }}
        <small class="text-muted fs-5">
            Position: {{ $fleet->x }},{{ $fleet->y }},{{ $fleet->spot }}
        </small>
    </h2>

    @if($fleetIsInColonyOrbit && $colony)

    <div class="alert alert-info py-2 mb-3">
        Flotte befindet sich in der Umlaufbahn von Kolonie
        <strong>{{ $colony->name }}</strong> (ID {{ $colony->id }}).
    </div>

    <div id="fc-bar">
        <div id="fc-qty">
            <button class="fc-qty-btn active" data-qty="1">1</button>
            <button class="fc-qty-btn" data-qty="5">5</button>
            <button class="fc-qty-btn" data-qty="10">10</button>
        </div>
        <button id="fc-to-colony" class="fc-transfer-btn" disabled>&#8592; Kolonie</button>
        <span id="fc-label">— Element auswählen —</span>
        <button id="fc-to-fleet" class="fc-transfer-btn" disabled>Flotte &#8594;</button>
    </div>

    <div class="fc-header">
        <div class="fc-colony">Kolonie</div>
        <div class="fc-mid"></div>
        <div class="fc-fleet">Flotte</div>
    </div>

    {{-- Schiffe --}}
    <div class="fc-section">{{ __('techtree.types_ships') }}</div>
    @foreach($ships as $id => $ship)
    <div class="fc-item" data-type="ship" data-id="{{ $id }}" data-cargo="0">
        <div class="fc-colony"><span class="shipOnColony-{{ $id }}">…</span></div>
        <div class="fc-mid">{{ __('techtree.' . $ship->name) }}</div>
        <div class="fc-fleet"><span class="shipInFleet-{{ $id }}">…</span></div>
    </div>
    @endforeach

    {{-- Crew --}}
    <div class="fc-section">Crew</div>
    @foreach($personells as $id => $p)
    <div class="fc-item" data-type="personell" data-id="{{ $id }}" data-cargo="0">
        <div class="fc-colony"><span class="personellOnColony-{{ $id }}">…</span></div>
        <div class="fc-mid">{{ __('techtree.' . $p->name) }}</div>
        <div class="fc-fleet"><span class="personellInFleet-{{ $id }}">…</span></div>
    </div>
    @endforeach

    {{-- Passagiere --}}
    <div class="fc-section">Passagiere</div>
    @foreach($personells as $id => $p)
    <div class="fc-item" data-type="personell" data-id="{{ $id }}" data-cargo="1">
        <div class="fc-colony"><span class="personellOnColony-{{ $id }}">…</span></div>
        <div class="fc-mid">
            {{ __('techtree.' . $p->name) }}
            <small style="opacity:.6">(Passagier)</small>
        </div>
        <div class="fc-fleet"><span class="personellInFleetCargo-{{ $id }}">…</span></div>
    </div>
    @endforeach

    {{-- Forschungen --}}
    <div class="fc-section">{{ __('techtree.types_researchs') }}</div>
    @foreach($researches as $id => $r)
    <div class="fc-item" data-type="research" data-id="{{ $id }}" data-cargo="1">
        <div class="fc-colony"><span class="researchOnColony-{{ $id }}">…</span></div>
        <div class="fc-mid">{{ __('techtree.' . $r->name) }}</div>
        <div class="fc-fleet"><span class="researchInFleetCargo-{{ $id }}">…</span></div>
    </div>
    @endforeach

    {{-- Ressourcen --}}
    <div class="fc-section">Ressourcen</div>
    @foreach($resources as $res)
    @if($res->is_tradeable)
    <div class="fc-item" data-type="resource" data-id="{{ $res->id }}" data-cargo="1">
        <div class="fc-colony"><span class="resourceOnColony-{{ $res->id }}">…</span></div>
        <div class="fc-mid">{{ __('resources.' . $res->name) }}</div>
        <div class="fc-fleet"><span class="resourceInFleetCargo-{{ $res->id }}">…</span></div>
    </div>
    @endif
    @endforeach

    @else

    <div class="alert alert-info mt-3">
        Die Flotte befindet sich nicht im Orbit einer Kolonie. Transfer nicht möglich.
    </div>

    @endif

    {{-- Order form --}}
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-terminal"></i> Befehl erteilen</h5>

            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if($errors->has('order'))
                <div class="alert alert-danger py-2">{{ $errors->first('order') }}</div>
            @endif

            <form method="POST" action="{{ route('fleet.orders.store', $fleet->id) }}" id="order-form">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small mb-1">Befehlstyp</label>
                        <select name="order" id="order-type" class="form-select form-select-sm" onchange="updateOrderForm()">
                            <option value="move">Bewegen</option>
                            <option value="trade">Handeln</option>
                            <option value="attack">Angreifen</option>
                        </select>
                    </div>

                    {{-- Move fields --}}
                    <div id="fields-move" class="col-auto">
                        <label class="form-label small mb-1">Ziel X</label>
                        <input type="number" name="destination_x" class="form-control form-control-sm" style="width:90px;"
                               value="{{ old('destination_x') }}">
                    </div>
                    <div id="fields-move-y" class="col-auto">
                        <label class="form-label small mb-1">Ziel Y</label>
                        <input type="number" name="destination_y" class="form-control form-control-sm" style="width:90px;"
                               value="{{ old('destination_y') }}">
                    </div>

                    {{-- Trade fields --}}
                    <div id="fields-trade" class="col-auto d-none">
                        <label class="form-label small mb-1">Kolonie-ID</label>
                        <input type="number" name="colony_id" class="form-control form-control-sm" style="width:90px;" value="{{ old('colony_id') }}">
                    </div>
                    <div id="fields-trade-res" class="col-auto d-none">
                        <label class="form-label small mb-1">Ressource-ID</label>
                        <input type="number" name="resource_id" class="form-control form-control-sm" style="width:90px;" value="{{ old('resource_id') }}">
                    </div>
                    <div id="fields-trade-amt" class="col-auto d-none">
                        <label class="form-label small mb-1">Menge</label>
                        <input type="number" name="amount" class="form-control form-control-sm" style="width:90px;" min="1" value="{{ old('amount', 1) }}">
                    </div>
                    <div id="fields-trade-dir" class="col-auto d-none">
                        <label class="form-label small mb-1">Richtung</label>
                        <select name="direction" class="form-select form-select-sm">
                            <option value="0">Kaufen (Kolonie → Flotte)</option>
                            <option value="1">Verkaufen (Flotte → Kolonie)</option>
                        </select>
                    </div>

                    {{-- Attack fields --}}
                    <div id="fields-attack" class="col-auto d-none">
                        <label class="form-label small mb-1">Ziel-Flotten-ID</label>
                        <input type="number" name="target_fleet_id" class="form-control form-control-sm" style="width:90px;" value="{{ old('target_fleet_id') }}">
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-warning mt-3">
                            <i class="bi bi-play-fill"></i> Befehl erteilen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>{{-- #fleetconfig --}}

@push('scripts')
<script>
function updateOrderForm() {
    const t = document.getElementById('order-type').value;
    document.getElementById('fields-move').classList.toggle('d-none', t !== 'move');
    document.getElementById('fields-move-y').classList.toggle('d-none', t !== 'move');
    document.getElementById('fields-trade').classList.toggle('d-none', t !== 'trade');
    document.getElementById('fields-trade-res').classList.toggle('d-none', t !== 'trade');
    document.getElementById('fields-trade-amt').classList.toggle('d-none', t !== 'trade');
    document.getElementById('fields-trade-dir').classList.toggle('d-none', t !== 'trade');
    document.getElementById('fields-attack').classList.toggle('d-none', t !== 'attack');
}
</script>
@endpush
@endsection
