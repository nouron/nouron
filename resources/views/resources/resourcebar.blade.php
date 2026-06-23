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
        $nexusPct = $hasNexus && ($nexusDebtMax ?? 12000) > 0 ? ($nexusDebt / ($nexusDebtMax ?? 12000)) * 100 : 0;
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
                )
            : null;

        // AP chip popup extras: base + advisor + trust-multiplier composition —
        // see PersonellService::getApBreakdown().
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
                number_format($nexusDebtMax ?? 12000, 0, ",", ".") .
                " Cr</span>" .
                "</div>"
            : null;
    @endphp
    <div class="res-bar-wrap resource-bar">

        {{-- Sol chip: no border, no max --}}
        @if ($solDisplay !== null)
            <span class="res-chip res-chip--sol" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false"
                @click.stop="open=!open" @click.outside="open=false" style="position:relative;cursor:default">
                <span class="res-abbr">Sol</span>
                <span class="res-amount">{{ $solDisplay }}</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_sol_title"),
                    "popup_desc" => __("resources.popup_sol_desc"),
                ])
            </span>
            <span class="res-divider" aria-hidden="true"></span>
        @endif

        {{-- Credits chip — NX shown in popup --}}
        @if ($crResource !== null)
            <span class="res-chip res-Cr {{ $nexusChipMod }}" x-data="{ open: false }" @mouseenter="open=true"
                @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                style="position:relative;cursor:default">
                <span class="res-abbr">CR</span>
                <span class="res-amount">{{ number_format($crResource["amount"] ?? 0, 0, ",", ".") }}</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_cr_title"),
                    "popup_desc" => __("resources.popup_cr_desc"),
                    "popup_extra" => $crPopupExtra,
                ])
            </span>
        @endif

        {{-- Supply chip — shows free/cap (not just cap) so the player sees how much
         headroom is left before new buildings/advisors/research are blocked. --}}
        @if (isset($primary[2]))
            <span class="res-chip res-Sup" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false"
                @click.stop="open=!open" @click.outside="open=false" style="position:relative;cursor:default">
                <span class="res-abbr">SUP</span>
                <span class="res-amount">
                    {{ number_format($supplyBreakdown["free"] ?? ($primary[2]["amount"] ?? 0), 0, ",", ".") }}
                    @if (isset($supplyBreakdown))
                        / {{ number_format($supplyBreakdown["cap"], 0, ",", ".") }}
                    @endif
                </span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_sup_title"),
                    "popup_desc" => __("resources.popup_sup_desc"),
                    "popup_extra" => $supplyPopupExtra,
                ])
            </span>
        @endif

        {{-- Trust — thematically next to Supply, shared globally (see AppServiceProvider). --}}
        @if (isset($trust))
            <span id="resbar-ap-trust"
                class="ap-chip {{ $trust >= 20 ? "ap-chip--trust-pos" : ($trust < 0 ? "ap-chip--trust-neg" : "ap-chip--trust-neu") }}"
                x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false" @click.stop="open=!open"
                @click.outside="open=false" style="position:relative;cursor:default">
                <span>{{ __("resources.res_trust") }} <span class="res-amount">{{ (int) $trust }}</span></span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_trust_title"),
                    "popup_desc" => __("resources.popup_trust_desc"),
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
                    $langKey = "popup_" . strtolower($abbr);
                @endphp
                <span class="res-chip res-{{ $abbr }}" x-data="{ open: false }" @mouseenter="open=true"
                    @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                    style="position:relative;cursor:default">
                    <span class="res-abbr">{{ $abbr }}</span>
                    <span class="res-amount">{{ number_format($resource["amount"] ?? 0, 0, ",", ".") }}</span>
                    @include("partials.res-popup", [
                        "popup_title" => __("resources." . $langKey . "_title"),
                        "popup_desc" => __("resources." . $langKey . "_desc"),
                    ])
                </span>
            @endforeach
        @endif

        {{-- AP + trust chips — shared globally (see AppServiceProvider). On colony.view
         these IDs are also used by colony-hexgrid.js to sync values + flash after AJAX
         actions; on other screens they just reflect the server-rendered values. --}}
        @if (isset($navAp, $constructionAp, $trust))
            <span class="res-divider" aria-hidden="true"></span>
            <span id="resbar-ap-nav" class="ap-chip ap-chip--nav" x-data="{ open: false }" @mouseenter="open=true"
                @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                style="position:relative;cursor:default">
                <span>Nav <span class="res-amount">{{ (int) $navAp }}</span> AP</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_nav_ap_title"),
                    "popup_desc" => __("resources.popup_nav_ap_desc"),
                    "popup_extra" => $apPopupExtra($apBreakdown["navigation"] ?? null),
                ])
            </span>
            <span id="resbar-ap-build" class="ap-chip ap-chip--build" x-data="{ open: false }" @mouseenter="open=true"
                @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                style="position:relative;cursor:default">
                <span>Con <span class="res-amount">{{ (int) $constructionAp }}</span> AP</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_bau_ap_title"),
                    "popup_desc" => __("resources.popup_bau_ap_desc"),
                    "popup_extra" => $apPopupExtra($apBreakdown["construction"] ?? null),
                ])
            </span>
            <span id="resbar-ap-research" class="ap-chip ap-chip--research" x-data="{ open: false }" @mouseenter="open=true"
                @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                style="position:relative;cursor:default">
                <span>Res <span class="res-amount">{{ (int) ($researchAp ?? 0) }}</span> AP</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_research_ap_title"),
                    "popup_desc" => __("resources.popup_research_ap_desc"),
                    "popup_extra" => $apPopupExtra($apBreakdown["research"] ?? null),
                ])
            </span>
            <span id="resbar-ap-economy" class="ap-chip ap-chip--economy" x-data="{ open: false }" @mouseenter="open=true"
                @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                style="position:relative;cursor:default">
                <span>Eco <span class="res-amount">{{ (int) ($economyAp ?? 0) }}</span> AP</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_economy_ap_title"),
                    "popup_desc" => __("resources.popup_economy_ap_desc"),
                    "popup_extra" => $apPopupExtra($apBreakdown["economy"] ?? null),
                ])
            </span>
            <span id="resbar-ap-strategy" class="ap-chip ap-chip--strategy" x-data="{ open: false }" @mouseenter="open=true"
                @mouseleave="open=false" @click.stop="open=!open" @click.outside="open=false"
                style="position:relative;cursor:default">
                <span>Str <span class="res-amount">{{ (int) ($strategyAp ?? 0) }}</span> AP</span>
                @include("partials.res-popup", [
                    "popup_title" => __("resources.popup_strategy_ap_title"),
                    "popup_desc" => __("resources.popup_strategy_ap_desc"),
                    "popup_extra" => $apPopupExtra($apBreakdown["strategy"] ?? null),
                ])
            </span>
        @endif

    </div>
@endauth
