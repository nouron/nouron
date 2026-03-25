{{-- Resource bar partial (no layout) — replaces resources/json/reloadresourcebar.phtml --}}
@auth
<div class="d-flex flex-wrap gap-2 justify-content-center align-items-center resource-bar">
    @foreach($possessions as $resId => $resource)
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
