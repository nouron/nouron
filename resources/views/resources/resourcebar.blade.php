{{-- Resource bar partial (no layout) — replaces resources/json/reloadresourcebar.phtml --}}
{{-- Server-side data dependency: $possessions — keyed by resource_id, each entry has: amount, abbreviation, name --}}
@auth
@php
    $primaryIds  = [1, 2];   // Credits (Cr) and Supply (Sup) — always shown, always first
    $primary     = [];
    $secondary   = [];

    foreach ($possessions as $resId => $resource) {
        if (in_array((int)$resId, $primaryIds)) {
            $primary[(int)$resId] = $resource;
        } else {
            $secondary[$resId] = $resource;
        }
    }

    // Guarantee order: Credits first, then Supply
    ksort($primary);
@endphp
<div class="d-flex flex-wrap gap-2 justify-content-center align-items-center resource-bar">

    {{-- Primary resources: always visible, regardless of amount --}}
    @foreach($primary as $resId => $resource)
        <span class="res-chip res-chip--primary res-{{ $resource['abbreviation'] ?? 'x' }}"
              data-bs-toggle="tooltip" data-bs-placement="bottom"
              title="{{ __('resources.' . ($resource['name'] ?? '')) }}">
            <span class="res-abbr">{{ $resource['abbreviation'] ?? '' }}</span>
            <span class="res-amount">{{ number_format($resource['amount'] ?? 0, 0, ',', '.') }}</span>
        </span>
    @endforeach

    {{-- Visual separator between primary and secondary resources --}}
    @if(count($secondary) > 0)
        <span class="res-divider" aria-hidden="true"></span>
    @endif

    {{-- Secondary (tradeable) resources: only shown when amount > 0 --}}
    @foreach($secondary as $resId => $resource)
        @if(($resource['amount'] ?? 0) > 0)
            <span class="res-chip res-{{ $resource['abbreviation'] ?? 'x' }}"
                  data-bs-toggle="tooltip" data-bs-placement="bottom"
                  title="{{ __('resources.' . ($resource['name'] ?? '')) }}">
                <span class="res-abbr">{{ $resource['abbreviation'] ?? '' }}</span>
                <span class="res-amount">{{ number_format($resource['amount'], 0, ',', '.') }}</span>
            </span>
        @endif
    @endforeach

</div>
@endauth
