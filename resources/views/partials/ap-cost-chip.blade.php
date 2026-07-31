{{--
    AP cost chip — shows upfront how many action points an action button consumes.
    Styled like the AP chips in the resource bar (.ap-chip + colour variant).

    Game-wide convention: every action button that spends AP renders this chip.

    Params:
      type   — 'build' (Bau-AP, green, default) | 'nav' (Nav-AP, blue) |
               'research' (Forschungs-AP, purple) | 'economy' (Wirtschafts-AP,
               yellow) | 'strategy' (Strategie-AP, red). Unknown/omitted type
               that isn't one of these falls back to neutral grey.
      amount — int AP cost (used when no $label given).
      label  — optional free text (e.g. "1 AP/Feld" for distance-scaled costs,
               or "Eco 1 AP" to match the resource-bar "Code N AP" pattern);
               overrides $amount.
--}}
@php
    $apTypeClasses = [
        "build" => "ap-chip--build",
        "nav" => "ap-chip--nav",
        "research" => "ap-chip--research",
        "economy" => "ap-chip--economy",
        "strategy" => "ap-chip--strategy",
    ];
    $apType = $apTypeClasses[$type ?? "build"] ?? "ap-chip--neutral";
    $apLabel = $label ?? ($amount ?? 1) . " AP";
@endphp
<span class="ap-chip ap-cost-chip {{ $apType }}" aria-hidden="true">{{ $apLabel }}</span>
