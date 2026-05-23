@php
    use App\Models\Run;
    $dbgRun    = auth()->check()
        ? Run::where('user_id', auth()->id())->where('status', 'active')->first()
        : null;
    $bypass    = config('game.bypass');
    $tickLimit = config('game.run.tick_limit');
@endphp
<div id="debug-bar"
     style="position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#0d0d0d;color:#ccc;font-family:monospace;font-size:11px;border-top:1px solid #2a2a2a;">
    <div class="d-flex align-items-center flex-wrap gap-3 px-2 py-1">

        <span style="color:#ff0;font-weight:bold;">⚙ DEBUG</span>

        <span style="color:#555;">|</span>

        {{-- Run / Sol --}}
        @if($dbgRun)
            <span>Run <span style="color:#fff;">#{{ $dbgRun->id }}</span></span>
            <span>Sol <span style="color:#fff;font-weight:bold;">{{ $dbgRun->current_tick }}</span><span style="color:#555;">/{{ $tickLimit }}</span></span>
            <span style="color:#555;">{{ $dbgRun->status }}</span>
        @else
            <span style="color:#f55;">kein aktiver Run</span>
        @endif

        <span style="color:#555;">|</span>

        {{-- Bypass flags --}}
        <span style="color:#888;">bypass:</span>
        <span style="color:{{ $bypass['ap_checks']      ? '#f90' : '#4c4' }};">
            AP:{{ $bypass['ap_checks']      ? 'OFF' : 'on' }}
        </span>
        <span style="color:{{ $bypass['resource_costs'] ? '#f90' : '#4c4' }};">
            Res:{{ $bypass['resource_costs'] ? 'OFF' : 'on' }}
        </span>
        <span style="color:{{ $bypass['supply_checks']  ? '#f90' : '#4c4' }};">
            Sup:{{ $bypass['supply_checks']  ? 'OFF' : 'on' }}
        </span>

        <span style="color:#555;">|</span>

        {{-- Environment --}}
        <span style="color:#888;">env:</span>
        <span style="color:{{ app()->environment('production') ? '#f55' : '#4c4' }};">{{ app()->environment() }}</span>

        {{-- Dismiss --}}
        <button onclick="document.getElementById('debug-bar').remove();"
                style="margin-left:auto;background:none;border:1px solid #333;color:#666;cursor:pointer;font-size:11px;padding:0 5px;line-height:16px;">×</button>
    </div>
</div>
