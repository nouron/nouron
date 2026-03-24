@extends('layouts.app')

@section('title', 'Fleet Configuration')

@section('content')
<div class="container-fluid">
    @if($fleet)
    <h2>{{ $fleet->fleet }}</h2>
    <p class="text-muted">Position: ({{ $fleet->x }}, {{ $fleet->y }}, {{ $fleet->spot }})</p>

    @if($fleetIsInColonyOrbit && $colony)
        <div class="alert alert-info">
            Fleet is in orbit of colony <strong>{{ $colony->name }}</strong>.
        </div>
    @endif

    <div class="row mt-3">
        <div class="col-md-6">
            <h4>Ships</h4>
            <div id="fleet-ships">
                <p class="text-muted small">Loading…</p>
            </div>
        </div>
        <div class="col-md-6">
            <h4>Resources</h4>
            <div id="fleet-resources">
                <p class="text-muted small">Loading…</p>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-warning">Fleet not found.</div>
    @endif
</div>

<script>
const fleetId = {{ $fleet?->id ?? 'null' }};
if (fleetId) {
    fetch(`/fleet/${fleetId}/technologies`)
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('fleet-ships');
            el.innerHTML = Object.values(data.ship || {})
                .filter(s => (s.count || 0) > 0)
                .map(s => `<div>${s.name}: ${s.count}</div>`)
                .join('') || '<em>none</em>';
        });

    fetch(`/fleet/${fleetId}/resources`)
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('fleet-resources');
            el.innerHTML = Object.values(data)
                .map(r => `<div>${r.name}: ${r.amount}</div>`)
                .join('') || '<em>none</em>';
        });
}
</script>
@endsection
