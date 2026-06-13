{{--
    AP cost chip — shows upfront how many action points an action button consumes.
    Styled like the AP chips in the resource bar (.ap-chip + colour variant).

    Game-wide convention: every action button that spends AP renders this chip.

    Params:
      type   — 'build' (Bau-AP, green) | 'nav' (Nav-AP, blue). Default 'build'.
      amount — int AP cost (used when no $label given).
      label  — optional free text (e.g. "1 AP/Feld" for distance-scaled costs);
               overrides $amount.
--}}
@php
    $apType = ($type ?? "build") === "nav" ? "ap-chip--nav" : "ap-chip--build";
    $apLabel = $label ?? ($amount ?? 1) . " AP";
@endphp
<span class="ap-chip ap-cost-chip {{ $apType }}" aria-hidden="true">{{ $apLabel }}</span>
