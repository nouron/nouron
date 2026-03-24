{{-- Resource bar partial (no layout) — replaces resources/json/reloadresourcebar.phtml --}}
@auth
<div class="d-flex flex-wrap gap-2 justify-content-center resource-bar">
    @foreach($possessions as $resId => $resource)
        @if(($resource['amount'] ?? 0) > 0)
            <a data-placement="bottom" rel="tooltip" href="#"
               data-bs-toggle="tooltip" data-bs-placement="bottom"
               title="{{ $resource['name'] ?? '' }}">
                <i class="{{ $resource['icon'] ?? '' }}">{{ $resource['abbreviation'] ?? '' }}</i>
                {{ $resource['amount'] }}
            </a>
        @endif
    @endforeach
</div>
@endauth
