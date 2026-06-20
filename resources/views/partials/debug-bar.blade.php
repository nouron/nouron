@php
    use App\Models\Run;
    $dbgRun = auth()->check()
        ? Run::where("user_id", auth()->id())
            ->where("status", "active")
            ->first()
        : null;
    $bypass = config("game.bypass");
    $cfgRun = config("game.run");
    $cfgSupply = config("game.supply");
    $cfgCredit = config("game.credits");
    $cfgTrust = config("game.trust.events");
    $cfgTick = config("game.tick");
@endphp

<div x-data="{ open: false }"
    style="position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#0d0d0d;color:#ccc;font-family:monospace;font-size:11px;border-top:1px solid #2a2a2a;">

    {{-- Compact status row --}}
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:3px 10px;">

        <span style="color:#ff0;font-weight:bold;">⚙ DEBUG</span>

        <span style="color:#444;">|</span>

        @if ($dbgRun)
            <span>Run <strong style="color:#fff;">#{{ $dbgRun->id }}</strong></span>
            <span>Sol <strong style="color:#fff;">{{ $dbgRun->current_tick }}</strong><span
                    style="color:#555;">/{{ $cfgRun["tick_limit"] }}</span></span>
            <span style="color:#555;">{{ $dbgRun->status }}</span>
        @else
            <span style="color:#f55;">kein aktiver Run</span>
        @endif

        <span style="color:#444;">|</span>

        <span style="color:#888;">bypass:</span>
        <span
            style="color:{{ $bypass["ap_checks"] ? "#f90" : "#4c4" }};">AP:{{ $bypass["ap_checks"] ? "OFF" : "on" }}</span>
        <span
            style="color:{{ $bypass["resource_costs"] ? "#f90" : "#4c4" }};">Res:{{ $bypass["resource_costs"] ? "OFF" : "on" }}</span>
        <span
            style="color:{{ $bypass["supply_checks"] ? "#f90" : "#4c4" }};">Sup:{{ $bypass["supply_checks"] ? "OFF" : "on" }}</span>

        <span style="color:#444;">|</span>

        <span style="color:#888;">env:</span>
        <span
            style="color:{{ app()->environment("production") ? "#f55" : "#4c4" }};">{{ app()->environment() }}</span>

        <button @click="open = !open"
            style="margin-left:auto;background:none;border:1px solid #333;color:#888;cursor:pointer;font-size:11px;padding:0 7px;line-height:16px;"
            x-text="open ? 'Config ▴' : 'Config ▾'">Config ▾</button>
        <button @click="$el.closest('[x-data]').remove()"
            style="background:none;border:1px solid #333;color:#555;cursor:pointer;font-size:11px;padding:0 5px;line-height:16px;">×</button>
    </div>

    {{-- Expandable config panel --}}
    <div x-show="open" x-transition
        style="background:#0a0a0a;border-top:1px solid #1e1e1e;padding:8px 12px;display:flex;flex-wrap:wrap;gap:24px;">

        {{-- Run --}}
        <div>
            <div style="color:#ff0;margin-bottom:4px;">Run</div>
            @foreach (["tick_limit", "tick_duration_hours", "max_players", "playbymailmode"] as $k)
                <div><span style="color:#666;">{{ $k }}:</span>
                    <span
                        style="color:#ddd;">{{ is_bool($cfgRun[$k]) ? ($cfgRun[$k] ? "true" : "false") : $cfgRun[$k] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Tick --}}
        <div>
            <div style="color:#ff0;margin-bottom:4px;">Tick</div>
            <div><span style="color:#666;">length:</span> <span style="color:#ddd;">{{ $cfgTick["length"] }}h</span>
            </div>
            <div><span style="color:#666;">window:</span> <span
                    style="color:#ddd;">{{ $cfgTick["calculation"]["start"] }}–{{ $cfgTick["calculation"]["end"] }}
                    Uhr</span></div>
        </div>

        {{-- Supply --}}
        <div>
            <div style="color:#ff0;margin-bottom:4px;">Supply</div>
            <div><span style="color:#666;">cap_max:</span> <span style="color:#ddd;">{{ $cfgSupply["cap_max"] }}</span>
            </div>
            <div><span style="color:#666;">per_cc_lv:</span> <span
                    style="color:#ddd;">{{ $cfgSupply["cap_commandcenter"] }}</span></div>
            <div><span style="color:#666;">per_housing:</span> <span
                    style="color:#ddd;">{{ $cfgSupply["cap_housingcomplex"] }}</span></div>
        </div>

        {{-- Credits --}}
        <div>
            <div style="color:#ff0;margin-bottom:4px;">Credits</div>
            <div><span style="color:#666;">nexus_subsidy:</span> <span
                    style="color:#ddd;">{{ $cfgCredit["nexus_subsidy"] }} Cr</span></div>
            <div><span style="color:#666;">tax_per_housing:</span> <span
                    style="color:#ddd;">{{ $cfgCredit["tax_per_housing"] }} Cr</span></div>
        </div>

        {{-- Trust Events --}}
        <div>
            <div style="color:#ff0;margin-bottom:4px;">Trust Events</div>
            @foreach ($cfgTrust as $event => $val)
                <div>
                    <span style="color:#666;">{{ $event }}:</span>
                    <span
                        style="color:{{ $val >= 0 ? "#4c4" : "#f55" }};">{{ $val >= 0 ? "+" : "" }}{{ $val }}</span>
                </div>
            @endforeach
        </div>

    </div>

</div>
