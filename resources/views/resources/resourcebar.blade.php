{{-- Resource bar partial --}}
{{-- $possessions: keyed by resource_id — amount, abbreviation, name --}}
{{-- Optional: $currentSol, $solLimit, $nexusDebt, $nexusDebtMax --}}
@auth
@php
    // Trust (12) removed — shown in colony header as "Vertrauen", not duplicated here
    $primaryIds   = [1, 2];           // Credits (Cr), Supply (Sup)
    $activeResIds = [1, 2, 3, 4, 5]; // whitelist — Trust (12) shown separately; ENrg/LNrg/ANrg removed
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

    $solDisplay   = $currentSol ?? null;
    $crResource   = $primary[1] ?? null;
    $hasNexus     = isset($nexusDebt) && $nexusDebt !== null;
    $nexusPct     = $hasNexus && ($nexusDebtMax ?? 12000) > 0
        ? ($nexusDebt / ($nexusDebtMax ?? 12000)) * 100 : 0;
    $nexusChipMod = match(true) {
        $nexusPct >= 95 => 'res-chip--danger',
        $nexusPct >= 80 => 'res-chip--warning',
        default         => '',
    };

    // Popup extra for CR chip (NX info row)
    $crPopupExtra = $hasNexus
        ? '<div class="res-popup-nx-row ' . $nexusChipMod . '">'
            . '<span class="res-popup-label">' . __('resources.popup_nx_title') . '</span>'
            . '<span>' . number_format($nexusDebt, 0, ',', '.') . ' / ' . number_format($nexusDebtMax ?? 12000, 0, ',', '.') . ' Cr</span>'
          . '</div>'
        : null;
@endphp
<div class="res-bar-wrap d-flex flex-wrap gap-2 justify-content-center align-items-center resource-bar">

    {{-- Sol chip: no border, no max --}}
    @if($solDisplay !== null)
        <span class="res-chip res-chip--sol" x-data="{ open: false }"
              @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open"
              style="position:relative;cursor:default">
            <span class="res-abbr">Sol</span>
            <span class="res-amount">{{ $solDisplay }}</span>
            @include('partials.res-popup', [
                'popup_title' => __('resources.popup_sol_title'),
                'popup_desc'  => __('resources.popup_sol_desc'),
            ])
        </span>
        <span class="res-divider" aria-hidden="true"></span>
    @endif

    {{-- Credits chip — NX shown in popup --}}
    @if($crResource !== null)
        <span class="res-chip res-Cr {{ $nexusChipMod }}" x-data="{ open: false }"
              @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open"
              style="position:relative;cursor:default">
            <span class="res-abbr">CR</span>
            <span class="res-amount">{{ number_format($crResource['amount'] ?? 0, 0, ',', '.') }}</span>
            @include('partials.res-popup', [
                'popup_title' => __('resources.popup_cr_title'),
                'popup_desc'  => __('resources.popup_cr_desc'),
                'popup_extra' => $crPopupExtra,
            ])
        </span>
    @endif

    {{-- Supply chip --}}
    @if(isset($primary[2]))
        <span class="res-chip res-Sup" x-data="{ open: false }"
              @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open"
              style="position:relative;cursor:default">
            <span class="res-abbr">SUP</span>
            <span class="res-amount">{{ number_format($primary[2]['amount'] ?? 0, 0, ',', '.') }}</span>
            @include('partials.res-popup', [
                'popup_title' => __('resources.popup_sup_title'),
                'popup_desc'  => __('resources.popup_sup_desc'),
            ])
        </span>
    @endif

    {{-- Secondary (tradeable) resources: only shown when amount > 0 --}}
    @if(count($secondary) > 0)
        @php
            $anySecondary = collect($secondary)->contains(fn($r) => ($r['amount'] ?? 0) > 0);
            $popupKeyMap  = ['W' => 'w', 'O' => 'o', 'Rg' => 'rg'];
        @endphp
        @if($anySecondary)
            <span class="res-divider" aria-hidden="true"></span>
            @foreach($secondary as $resId => $resource)
                @if(($resource['amount'] ?? 0) > 0)
                    @php
                        $abbr    = $resource['abbreviation'] ?? 'x';
                        $langKey = 'popup_' . strtolower($abbr);
                    @endphp
                    <span class="res-chip res-{{ $abbr }}" x-data="{ open: false }"
                          @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open"
                          style="position:relative;cursor:default">
                        <span class="res-abbr">{{ $abbr }}</span>
                        <span class="res-amount">{{ number_format($resource['amount'], 0, ',', '.') }}</span>
                        @include('partials.res-popup', [
                            'popup_title' => __('resources.' . $langKey . '_title'),
                            'popup_desc'  => __('resources.' . $langKey . '_desc'),
                        ])
                    </span>
                @endif
            @endforeach
        @endif
    @endif

</div>
@endauth
