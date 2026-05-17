{{-- Resource bar partial (no layout) — replaces resources/json/reloadresourcebar.phtml --}}
{{-- Server-side data dependency: $possessions — keyed by resource_id, each entry has: amount, abbreviation, name --}}
{{-- Optional: $currentSol (int), $solLimit (int) — inject Sol chip before primary resources --}}
@auth
@php
    $primaryIds   = [1, 2, 12]; // Credits (Cr), Supply (Sup), Trust (M) — always shown
    $activeResIds = [1, 2, 3, 4, 5, 12]; // whitelist — ENrg/LNrg/ANrg (6/8/10) excluded
    $primary      = [];
    $secondary    = [];

    foreach ($possessions as $resId => $resource) {
        $rid = (int) $resId;
        if (!in_array($rid, $activeResIds)) continue;
        if (in_array($rid, $primaryIds)) {
            $primary[$rid] = $resource;
        } else {
            $secondary[$rid] = $resource;
        }
    }

    ksort($primary);

    // Cap Sol display: if runs don't exist yet, since_tick may be very old
    $solDisplay = (isset($currentSol) && $currentSol !== null && $currentSol <= ($solLimit ?? 100))
        ? $currentSol
        : null;
@endphp
<div class="res-bar-wrap d-flex flex-wrap gap-2 justify-content-center align-items-center resource-bar">

    {{-- Sol chip: only shown when run-local Sol is meaningful (≤ solLimit) --}}
    @if($solDisplay !== null)
        <span class="res-chip res-chip--primary res-chip--sol res-Sol">
            <span class="res-abbr">Sol</span>
            <span class="res-amount">{{ $solDisplay }} / {{ $solLimit ?? 100 }}</span>
        </span>
        <span class="res-divider" aria-hidden="true"></span>
    @endif

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
