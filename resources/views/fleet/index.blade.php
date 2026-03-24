@extends('layouts.app')

@section('title', 'Fleets')

@section('content')
<div class="container-fluid">
    <h2>My Fleets</h2>

    @if($ownFleets->isEmpty())
        <p class="text-muted">No fleets found.</p>
    @else
        <div class="row">
            @foreach($ownFleets as $fleet)
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $fleet->fleet }}</h5>
                        <p class="card-text text-muted small">
                            Position: ({{ $fleet->x }}, {{ $fleet->y }}, {{ $fleet->spot }})
                        </p>
                        <a href="{{ route('fleet.config', $fleet->id) }}" class="btn btn-sm btn-primary">
                            Configure
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
