@extends("layouts.colony")
@section("title", __("command_center.title"))

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/command_center.css") }}">
    <link rel="stylesheet" href="{{ asset("css/entity-chips.css") }}">
@endpush

@push("scripts")
    <script src="{{ asset("js/command_center.js") }}"></script>
@endpush

@section("content")
    <script>
        window.__commandCenterData = {
            routes: {
                stipend: "{{ route("colony.stipend") }}",
            },
            i18n: {
                stipendSuccess: @json(__("colony.stipend_success")),
                stipendError: @json(__("colony.stipend_error")),
            },
        };
    </script>

    <div class="cc-dashboard" x-data="commandCenter(window.__commandCenterData)" x-cloak>

        {{-- Widget 1: Phasenziele --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("command_center.widget_phase_title") }}</h3>
            @if ($phaseProgress["phase"] === 1)
                <ul class="phase-dialog-criteria">
                    @foreach ($phaseProgress["criteria"] as $c)
                        <li class="phase-criteria__item @if ($c["done"]) phase-criteria__item--done @endif">
                            <span class="phase-criteria__check">{{ $c["done"] ? "✓" : "○" }}</span>
                            <span class="phase-criteria__label">{{ $c["label"] }}</span>
                            @unless ($c["done"])
                                <span class="phase-criteria__count">{{ $c["current"] }}/{{ $c["target"] }}</span>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="phase-dialog-criteria">
                    @foreach ($phaseProgress["objectives"] as $obj)
                        <li
                            class="phase-criteria__item @if ($obj["done"]) phase-criteria__item--done @endif">
                            <span class="phase-criteria__check">{{ $obj["done"] ? "✓" : "○" }}</span>
                            <span class="phase-criteria__label">
                                {{ $obj["revealed"] ? $obj["label"] : __("colony.sol_report_phase2_objective_hidden") }}
                            </span>
                            @if (!$obj["done"] && $obj["revealed"])
                                <span class="phase-criteria__count">{{ $obj["current"] }}/{{ $obj["target"] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        {{-- Widget 2: Kolonisten-Zulage --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("colony.stipend_dialog_title") }}</h3>
            <p class="cc-card-hint">{{ __("colony.stipend_dialog_hint") }}</p>
            <div class="stipend-tiers">
                @foreach ($stipendTiers as $tierKey => $tierCfg)
                    <button class="stipend-tier-btn" @click="doPurchaseStipend('{{ $tierKey }}')">
                        <strong>{{ __("colony.stipend_tier_{$tierKey}") }}</strong>
                        <span class="cc-chip-row">
                            <span class="res-chip res-Cr">
                                <span class="res-abbr">CR</span>
                                <span class="res-amount">{{ $tierCfg["cost"] }}</span>
                            </span>
                            <span
                                class="ap-chip ap-chip--trust-pos">+{{ config("game.trust.events.{$tierCfg["event_key"]}") }}
                                {{ __("resources.res_trust") }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </article>

        {{-- Widget 3: Run-Fortschritt / Sol-Countdown --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("command_center.widget_run_title") }}</h3>
            <p class="cc-stat">{{ __("command_center.widget_run_sol", ["current" => $currentSol, "limit" => $solLimit]) }}
            </p>
            <p class="cc-card-hint">
                {{ __("command_center.widget_run_remaining", ["count" => max(0, $solLimit - $currentSol)]) }}</p>
            <div class="cc-nexus-debt">
                <div class="cc-nexus-debt-label">
                    <span>{{ __("command_center.widget_run_nexus_debt") }}</span>
                    <span class="res-chip res-Cr {{ $nexusDebtTone !== "neutral" ? "res-chip--{$nexusDebtTone}" : "" }}">
                        <span class="res-abbr">CR</span>
                        <span class="res-amount">{{ number_format($nexusDebt, 0, ",", ".") }}
                            / {{ number_format($nexusDebtMax, 0, ",", ".") }}</span>
                    </span>
                </div>
                <div class="cc-nexus-debt-bar">
                    <div class="cc-nexus-debt-bar-fill cc-nexus-debt-bar-fill--{{ $nexusDebtTone }}"
                        style="width: {{ min(100, $nexusDebtMax > 0 ? ($nexusDebt / $nexusDebtMax) * 100 : 0) }}%"></div>
                </div>
            </div>
        </article>

        {{-- Widget 4: Wartungsstau --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("command_center.widget_maintenance_title") }}</h3>
            @if ($damagedBuildings->isEmpty())
                <p class="cc-card-hint">{{ __("command_center.widget_maintenance_none") }}</p>
            @else
                <p class="cc-stat">
                    {{ __("command_center.widget_maintenance_count", ["count" => $damagedBuildings->count(), "total" => $totalBuildings]) }}
                </p>
                <ul class="cc-list">
                    @foreach ($damagedBuildings as $b)
                        @php
                            $buildingKey = $b["building_key"];
                            $buildingLabel = $b["label"];
                            $buildingTooltip = [
                                "description" => __(
                                    "buildings." . str_replace("building_", "", $buildingKey) . "_desc",
                                ),
                            ];
                        @endphp
                        <li class="cc-list-item">
                            <span>
                                <x-entity-chip type="building" :entity-key="$buildingKey" :label="$buildingLabel" :tooltip="$buildingTooltip" />
                                ({{ $b["tile_x"] }}, {{ $b["tile_y"] }})
                            </span>
                            <span class="cc-list-item-danger">
                                {{ __("command_center.widget_maintenance_status", ["sp" => $b["status_points"], "max" => $b["max_status_points"]]) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        {{-- Widget 5: Netto-Sol-Bilanz --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("command_center.widget_balance_title") }}</h3>
            @if ($lastSolDeltas === null)
                <p class="cc-card-hint">{{ __("command_center.widget_balance_none") }}</p>
            @else
                <p class="cc-card-hint">{{ __("command_center.widget_balance_intro") }}</p>
                <ul class="cc-list">
                    @foreach (["regolith" => "res_regolith", "werkstoffe" => "res_werkstoffe", "organika" => "res_organika", "credits" => "res_credits", "trust" => "res_trust"] as $key => $langKey)
                        @php $delta = $lastSolDeltas[$key] ?? 0; @endphp
                        <li class="cc-list-item">
                            <span>{{ __("resources.{$langKey}") }}</span>
                            <span
                                class="{{ $delta > 0 ? "cc-list-item-good" : ($delta < 0 ? "cc-list-item-danger" : "") }}">
                                {{ $delta > 0 ? "+" : "" }}{{ $delta }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        {{-- Widget 6: Berater-Kurzübersicht --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("command_center.widget_advisors_title") }}</h3>
            @if ($advisors->isEmpty())
                <p class="cc-card-hint">{{ __("command_center.widget_advisors_none") }}</p>
            @else
                <p class="cc-stat">{{ __("command_center.widget_advisors_count", ["count" => $advisors->count()]) }}</p>
                <ul class="cc-list">
                    @php
                        $apChipClass = [
                            "construction" => "ap-chip--build",
                            "research" => "ap-chip--research",
                            "navigation" => "ap-chip--nav",
                            "economy" => "ap-chip--economy",
                            "strategy" => "ap-chip--strategy",
                        ];
                    @endphp
                    @foreach ($advisors as $a)
                        @php
                            $advisorEntityKey = "advisor_" . $a["type_key"];
                            $advisorLabel = $a["name"];
                            $advisorTooltip = [
                                "description" => $a["type_key"] ? __("advisors." . $a["type_key"] . "_desc") : null,
                            ];
                        @endphp
                        <li class="cc-list-item">
                            <span>
                                <x-entity-chip type="advisor" :entity-key="$advisorEntityKey" :label="$advisorLabel" :tooltip="$advisorTooltip" />
                                ({{ $a["rank_name"] }})
                            </span>
                            <span class="ap-chip {{ $apChipClass[$a["ap_type"]] ?? "" }}">+{{ $a["ap_per_tick"] }}
                                AP</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <a href="{{ route("advisors.index") }}"
                class="cc-card-link">{{ __("command_center.widget_advisors_link") }}</a>
        </article>

        {{-- Widget 7: Vertrauens-Ereignisse --}}
        <article class="cc-card">
            <h3 class="cc-card-title">{{ __("command_center.widget_trust_events_title") }}</h3>
            @if ($trustEvents->isEmpty())
                <p class="cc-card-hint">{{ __("command_center.widget_trust_events_none") }}</p>
            @else
                <ul class="cc-list">
                    @foreach ($trustEvents as $e)
                        <li class="cc-list-item">
                            <span>{{ __("command_center.widget_trust_events_sol", ["sol" => $e["tick"] + 1]) }}
                                — {{ $e["description"] }}</span>
                            <span
                                class="ap-chip {{ $e["delta"] > 0 ? "ap-chip--trust-pos" : ($e["delta"] < 0 ? "ap-chip--trust-neg" : "ap-chip--trust-neu") }}">
                                {{ $e["delta"] > 0 ? "+" : "" }}{{ $e["delta"] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        {{-- Action / feedback toast (mirrors colony-hexgrid.js's .colony-toast) --}}
        <div class="colony-toast" :class="`colony-toast--${toastType}`" x-show="toastVisible" x-transition
            x-text="toastMessage" aria-live="polite" role="status"></div>
    </div>
@endsection
