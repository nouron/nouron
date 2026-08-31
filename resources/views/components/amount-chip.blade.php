{{--
    Static amount chip — reuses the resourcebar's exact pill markup/CSS
    (.res-chip/.res-abbr/.res-amount, .ap-chip) for inline amounts in
    historical text (Protokoll), without the resourcebar's Alpine tooltip
    machinery (nothing to sync live in a log entry). Owner-Playtest-Fund
    2026-08-31: AP/Credits amounts in the Protokoll were plain text, visually
    inconsistent with the header chips showing the same units.

    Props:
      $abbr    — string: chip abbreviation, e.g. 'AP', 'CR', 'RG', 'CO', 'OR'
      $value   — int|string: the amount to display
      $variant — string: 'ap'|'res' (default 'res') — picks the chip color family
--}}
@props(["abbr", "value", "variant" => "res"])

@php
    // .res-{X} color classes use mixed case (Cr/Rg/Co/Or/Sup) — decoupled from
    // the displayed $abbr, which res-abbr's CSS uppercases visually anyway.
    $colorClassMap = ["CR" => "Cr", "RG" => "Rg", "CO" => "Co", "OR" => "Or", "SUP" => "Sup"];
    $colorClass = $colorClassMap[strtoupper($abbr)] ?? $abbr;
@endphp

<span class="{{ $variant === "ap" ? "ap-chip ap-chip--neutral" : "res-chip res-" . $colorClass }}">
    <span class="res-abbr">{{ $abbr }}</span>
    <span class="res-amount">{{ $value }}</span>
</span>
