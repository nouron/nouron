{{--
    Wiederverwendbarer Ressourcen-Chip.

    Parameter:
      $abbreviation  — Ressourcen-Kürzel, z.B. 'Cr', 'Sup', 'W'  (required)
      $amount        — Anzahl (optional; wird weggelassen wenn null)
      $title         — Tooltip-Text (optional)
--}}
@php
    $chipTitle = $title ?? '';
    $hasAmount = !is_null($amount ?? null);
@endphp
<span class="res-chip res-{{ $abbreviation }}"
    @if($chipTitle) data-bs-toggle="tooltip" title="{{ $chipTitle }}" @endif
><span class="res-abbr">{{ $abbreviation }}</span>@if($hasAmount)<span class="res-amount">{{ $amount }}</span>@endif</span>
