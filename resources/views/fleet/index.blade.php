@extends('layouts.app')

@section('title', 'Flotten — Nouron')

@section('content')
<div id="fleetlist">
    <p class="text-center">Eigene Flotten und die Flotten in der näheren Umgebung.</p>

    <div class="row">

        {{-- Own fleets --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title">eigene Flotten</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Flotte</th>
                                <th>Position</th>
                                <th>Aktion</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ownFleets as $fleet)
                            @if($fleet->user_id == $userId)
                            <tr>
                                <td>
                                    <a href="{{ route('fleet.config', $fleet->id) }}">
                                        <i class="bi bi-search"></i> {{ $fleet->fleet }}
                                    </a>
                                </td>
                                <td>
                                    @if($fleet->spot)
                                        {{-- docked at colony --}}
                                        <i class="bi bi-building" title="angedockt"></i> {{ $fleet->x }},{{ $fleet->y }},{{ $fleet->spot }}
                                    @else
                                        {{ $fleet->x }},{{ $fleet->y }}
                                    @endif
                                </td>
                                <td>
                                    @php $nextOrder = $pendingOrders->get($fleet->id)?->first() @endphp
                                    @if($nextOrder)
                                        <span class="text-muted small">
                                            @if($nextOrder->order === 'move')
                                                <i class="bi bi-arrow-right-circle"></i>
                                                @php $c = json_decode($nextOrder->coordinates, true) @endphp
                                                Bewegt sich → ({{ $c[0] ?? '?' }},{{ $c[1] ?? '?' }}) [Tick {{ $nextOrder->tick }}]
                                            @elseif($nextOrder->order === 'trade')
                                                <i class="bi bi-bag"></i> Handelt [Tick {{ $nextOrder->tick }}]
                                            @elseif($nextOrder->order === 'attack')
                                                <i class="bi bi-lightning"></i> Greift an [Tick {{ $nextOrder->tick }}]
                                            @else
                                                {{ ucfirst($nextOrder->order) }} [Tick {{ $nextOrder->tick }}]
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('fleet.destroy', $fleet->id) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Flotte löschen"
                                                onclick="return confirm('Flotte \'{{ addslashes($fleet->fleet) }}\' wirklich löschen?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    @if($errors->has('fleet'))
                                        <div class="alert alert-danger py-1 mb-2 small">{{ $errors->first('fleet') }}</div>
                                    @endif
                                    <form method="POST" action="{{ route('fleet.store') }}" class="d-flex gap-2 align-items-center flex-wrap">
                                        @csrf
                                        <input type="text" name="fleet" class="form-control form-control-sm"
                                               placeholder="Name der neuen Flotte" style="max-width:200px;" required
                                               value="{{ old('fleet') }}">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle"></i> Neue Flotte
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Foreign fleets in range --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title">fremde Flotten</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Flotte</th>
                                <th>Position</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ownFleets->filter(fn($f) => $f->user_id != $userId) as $fleet)
                            <tr>
                                <td>{{ $fleet->fleet }}</td>
                                <td>{{ $fleet->x }},{{ $fleet->y }},{{ $fleet->spot }}</td>
                                <td></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-muted fst-italic">Keine fremden Flotten in der Nähe.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- .row --}}
</div>{{-- #fleetlist --}}
@endsection
