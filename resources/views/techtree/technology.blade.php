<div class="p-3">
    <h5>{{ $tech['name'] ?? 'Unknown' }}</h5>
    <p>Level: {{ $tech['level'] ?? 0 }} | Status: {{ $tech['status_points'] ?? 0 }} | AP spent: {{ $tech['ap_spend'] ?? 0 }}</p>
    <p>AP available: {{ $apAvailable }}</p>
    <p>Requirements: {{ $requiredBuildingsCheck ? '✓' : '✗' }} Buildings | {{ $requiredResourcesCheck ? '✓' : '✗' }} Resources</p>
    <div class="mt-2">
        <form method="POST" action="{{ route('techtree.order', [$type, $techId]) }}">
            @csrf
            <input type="hidden" name="order" value="add">
            <input type="number" name="ap" value="1" min="1" class="form-control form-control-sm d-inline w-auto">
            <button class="btn btn-sm btn-primary">Invest AP</button>
        </form>
        <form method="POST" action="{{ route('techtree.order', [$type, $techId]) }}" class="mt-1">
            @csrf
            <input type="hidden" name="order" value="levelup">
            <button class="btn btn-sm btn-success">Level Up</button>
        </form>
    </div>
</div>
