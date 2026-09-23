{{-- Resource bar partial --}}
{{-- $possessions: keyed by resource_id — amount, abbreviation, name --}}
{{-- Optional: $currentSol, $solLimit, $nexusDebt, $nexusDebtMax --}}
@auth
    @php
        // Trust (12) removed — shown in colony header as "Vertrauen", not duplicated here
        $primaryIds = [1, 2]; // Credits (Cr), Supply (Sup)
        $activeResIds = [1, 2, 3, 4, 5]; // whitelist — Trust (12) shown separately; ENrg/LNrg/ANrg removed
        $primary = [];
        $secondary = [];

        foreach ($possessions as $resId => $resource) {
            $rid = (int) $resId;
            if (!in_array($rid, $activeResIds)) {
                continue;
            }
            if (in_array($rid, $primaryIds)) {
                $primary[$rid] = $resource;
            } else {
                $secondary[$rid] = $resource;
            }
        }

        ksort($primary);
        ksort($secondary);

        $solDisplay = $currentSol ?? null;
        $crResource = $primary[1] ?? null;
        $hasNexus = isset($nexusDebt) && $nexusDebt !== null;
        $nexusMax = (int) ($nexusDebtMax ?? config("game.run.nexus_debt_fail_threshold", 12000));
        $nexusPct = $hasNexus && $nexusMax > 0 ? ($nexusDebt / $nexusMax) * 100 : 0;
        $nexusChipMod = match (true) {
            $nexusPct >= 95 => "res-chip--danger",
            $nexusPct >= 80 => "res-chip--warning",
            default => "",
        };

        // Builds a stack of ".res-popup-row" lines from [label => value] pairs —
        // reused for the Supply and AP chip breakdown popups below.
        $breakdownRows = function (array $rows) {
            $html = "";
            foreach ($rows as $label => $value) {
                $html .=
                    '<div class="res-popup-row"><span class="res-popup-label">' .
                    $label .
                    "</span><span>" .
                    $value .
                    "</span></div>";
            }

            return $html;
        };

        // Supply chip popup extra: cap composition (CC/Housing/Knowledge) + usage
        // breakdown (Buildings/Researches/Advisors) — see ResourcesService::getSupplyBreakdown().
        $supplyPopupExtra = isset($supplyBreakdown)
            ? $breakdownRows(
                    array_filter([
                        __("resources.popup_sup_source_cc") => $supplyBreakdown["sources"]["cc"],
                        __("resources.popup_sup_source_housing") => $supplyBreakdown["sources"]["housing"],
                        __("resources.popup_sup_source_knowledge") => $supplyBreakdown["sources"]["knowledge"],
                    ]),
                ) .
                '<div class="res-popup-extra"></div>' .
                $breakdownRows(
                    array_filter([
                        __("resources.popup_sup_used_buildings") => -$supplyBreakdown["used"]["buildings"],
                        __("resources.popup_sup_used_researches") => -$supplyBreakdown["used"]["researches"],
                        __("resources.popup_sup_used_advisors") => -$supplyBreakdown["used"]["advisors"],
                    ]),
                ) .
                '<div class="res-popup-extra"></div>' .
                $breakdownRows([__("resources.popup_sup_free") => $supplyBreakdown["free"]])
            : null;

        // Over-capacity rows (GDD §6 "Überkapazität", A14): colonist deficit plus either
        // the remaining grace period or the current trust penalty — see OvercapService::status().
        if ($supplyPopupExtra !== null && ($overcapStatus["over"] ?? false)) {
            $supplyPopupExtra .=
                '<div class="res-popup-extra"></div>' .
                $breakdownRows([__("resources.popup_sup_overcap_deficit") => $overcapStatus["deficit"]]) .
                ($overcapStatus["trust_penalty"] < 0
                    ? $breakdownRows([__("resources.popup_sup_overcap_trust") => $overcapStatus["trust_penalty"]])
                    : $breakdownRows([
                        __("resources.popup_sup_overcap_grace") => __("resources.popup_sup_overcap_grace_value", [
                            "sols" => $overcapStatus["grace_sols_left"],
                        ]),
                    ]));
        }

        // Trust chip popup extra: streak-based penalties as own rows (hunger, over-capacity).
        $trustPopupExtra = isset($overcapStatus)
            ? $breakdownRows(
                array_filter(
                    [
                        __("resources.popup_trust_hunger") => $overcapStatus["hunger_penalty"],
                        __("resources.popup_trust_overcap") => $overcapStatus["trust_penalty"],
                    ],
                    fn($v) => $v !== 0,
                ),
            )
            : null;

        // AP chip popup extras: base + advisor + trust-multiplier composition —
        // see AdvisorService::getApBreakdown().
        $apPopupExtra = function (?array $breakdown) use ($breakdownRows) {
            if (!$breakdown) {
                return null;
            }

            return $breakdownRows(
                array_filter(
                    [
                        __("resources.popup_ap_base") => $breakdown["base"],
                        __("resources.popup_ap_advisor") => $breakdown["advisor"],
                    ],
                    fn($v) => $v !== 0,
                ),
            ) .
                ($breakdown["multiplier"] != 1.0
                    ? $breakdownRows([
                        __("resources.popup_ap_trust_multiplier") => "× " . number_format($breakdown["multiplier"], 2),
                    ])
                    : "") .
                (($breakdown["plague_multiplier"] ?? 1.0) < 1.0
                    ? $breakdownRows([
                        __("resources.popup_ap_plague_multiplier") =>
                            "× " . number_format($breakdown["plague_multiplier"], 2),
                    ])
                    : "");
        };

        // Popup extra for CR chip (NX info row)
        $crPopupExtra = $hasNexus
            ? '<div class="res-popup-nx-row ' .
                $nexusChipMod .
                '">' .
                '<span class="res-popup-label">' .
                __("resources.popup_nx_title") .
                "</span>" .
                "<span>" .
                number_format($nexusDebt, 0, ",", ".") .
                " / " .
                number_format($nexusMax, 0, ",", ".") .
                " Cr</span>" .
                "</div>"
            : null;
    @endphp
    <div class="res-bar-wrap resource-bar" x-data="{ openChip: null }">

        {{-- Sol chip: no border, no max --}}
        @if ($solDisplay !== null)
            <span class="res-chip res-chip--sol" @mouseenter="openChip = 'sol'" @mouseleave="openChip = null"
                @click.stop="openChip = openChip === 'sol' ? null : 'sol'"
                @click.outside="openChip === 'sol' && (openChip = null)" style="position:relative;cursor:default">
                <span class="res-abbr">Sol</span>
                <span class="res-amount">{{ $solDisplay }}</span>
                @include("partials.res-popup", [
                    "popup_key" => "sol",
                    "popup_title" => __("resources.popup_sol_title"),
                    "popup_desc" => __("resources.popup_sol_desc"),
                ])
            </span>
            <span class="res-divider" aria-hidden="true"></span>
        @endif

        {{-- Credits chip — NX shown in popup --}}
        @if ($crResource !== null)
            <span class="res-chip res-Cr {{ $nexusChipMod }}" @mouseenter="openChip = 'cr'" @mouseleave="openChip = null"
                @click.stop="openChip = openChip === 'cr' ? null : 'cr'"
                @click.outside="openChip === 'cr' && (openChip = null)" style="position:relative;cursor:default">
                <span class="res-abbr">CR</span>
                <span class="res-amount">{{ number_format($crResource["amount"] ?? 0, 0, ",", ".") }}</span>
                @include("partials.res-popup", [
                    "popup_key" => "cr",
                    "popup_title" => __("resources.popup_cr_title"),
                    "popup_desc" => __("resources.popup_cr_desc"),
                    "popup_extra" => $crPopupExtra,
                ])
            </span>
        @endif

        {{-- Colonist chip (GDD §6 Kolonisten-Framing, A15) — "used / cap" colonists, e.g. "47 / 60".
         Flagged red when the colony is over its cap (free < 0). --}}
        @if (isset($primary[2]))
            @php
                $supplyOver = isset($supplyBreakdown) && $supplyBreakdown["free"] < 0;
            @endphp
            <span class="res-chip res-Sup{{ $supplyOver ? " res-chip--over" : "" }}" @mouseenter="openChip = 'sup'"
                @mouseleave="openChip = null" @click.stop="openChip = openChip === 'sup' ? null : 'sup'"
                @click.outside="openChip === 'sup' && (openChip = null)" style="position:relative;cursor:default">
                <span class="res-abbr">KOL</span>
                <span class="res-amount">
                    @if (isset($supplyBreakdown))
                        {{ number_format($supplyBreakdown["cap"] - $supplyBreakdown["free"], 0, ",", ".") }}
                        / {{ number_format($supplyBreakdown["cap"], 0, ",", ".") }}
                    @else
                        {{ number_format($primary[2]["amount"] ?? 0, 0, ",", ".") }}
                    @endif
                </span>
                @include("partials.res-popup", [
                    "popup_key" => "sup",
                    "popup_title" => __("resources.popup_sup_title"),
                    "popup_desc" => __("resources.popup_sup_desc"),
                    "popup_extra" => $supplyPopupExtra,
                ])
            </span>
        @endif

        {{-- Trust — thematically next to Supply, shared globally (see AppServiceProvider). --}}
        @if (isset($trust))
            <span id="resbar-ap-trust"
                class="ap-chip {{ $trust >= 20 ? "ap-chip--trust-pos" : ($trust < (int) config("game.run.trust_warning.chip_red", -10) ? "ap-chip--trust-neg" : ($trust < 0 ? "ap-chip--trust-warn" : "ap-chip--trust-neu")) }}"
                @mouseenter="openChip = 'trust'" @mouseleave="openChip = null"
                @click.stop="openChip = openChip === 'trust' ? null : 'trust'"
                @click.outside="openChip === 'trust' && (openChip = null)" style="position:relative;cursor:default">
                <span>{{ __("resources.res_trust") }} <span class="res-amount">{{ (int) $trust }}</span></span>
                @include("partials.res-popup", [
                    "popup_key" => "trust",
                    "popup_title" => __("resources.popup_trust_title"),
                    "popup_desc" => __("resources.popup_trust_desc"),
                    "popup_extra" => $trustPopupExtra,
                ])
            </span>
        @endif

        {{-- Secondary (colony) resources — always shown, even at 0, so the player sees
         the full economy (Regolith, Werkstoffe, Organika). --}}
        @if (count($secondary) > 0)
            <span class="res-divider" aria-hidden="true"></span>
            @foreach ($secondary as $resId => $resource)
                @php
                    $abbr = $resource["abbreviation"] ?? "x";
                    $chipKey = "res_" . strtolower($abbr);
                    $langKey = "popup_" . strtolower($abbr);
                @endphp
                <span class="res-chip res-{{ $abbr }}" @mouseenter="openChip = '{{ $chipKey }}'"
                    @mouseleave="openChip = null"
                    @click.stop="openChip = openChip === '{{ $chipKey }}' ? null : '{{ $chipKey }}'"
                    @click.outside="openChip === '{{ $chipKey }}' && (openChip = null)"
                    style="position:relative;cursor:default">
                    <span class="res-abbr">{{ $abbr }}</span>
                    <span class="res-amount">{{ number_format($resource["amount"] ?? 0, 0, ",", ".") }}</span>
                    @include("partials.res-popup", [
                        "popup_key" => $chipKey,
                        "popup_title" => __("resources." . $langKey . "_title"),
                        "popup_desc" => __("resources." . $langKey . "_desc"),
                    ])
                </span>
            @endforeach
        @endif

        {{-- AP chip — shared globally (see AppServiceProvider). On colony.view
         this ID is also used by colony-hexgrid.js to sync the value + flash
         after AJAX actions; on other screens it just reflects the server-
         rendered value. One shared colony pool (GDD §13.1) — no more
         per-domain split. --}}
        @if (isset($colonyAp, $trust))
            <span class="res-divider" aria-hidden="true"></span>
            <span id="resbar-ap" class="ap-chip ap-chip--neutral" @mouseenter="openChip = 'ap'"
                @mouseleave="openChip = null" @click.stop="openChip = openChip === 'ap' ? null : 'ap'"
                @click.outside="openChip === 'ap' && (openChip = null)" style="position:relative;cursor:default">
                <span>AP <span class="res-amount">{{ (int) $colonyAp }}</span></span>
                @include("partials.res-popup", [
                    "popup_key" => "ap",
                    "popup_title" => __("resources.popup_ap_title"),
                    "popup_desc" => __("resources.popup_ap_desc"),
                    "popup_extra" => $apPopupExtra($apBreakdown ?? null),
                ])
            </span>
        @endif

    </div>
@endauth
