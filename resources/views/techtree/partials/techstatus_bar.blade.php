{{--
    techstatus_bar partial
    Variables: $tech (array), $type (string), $apAvailable (int)

    Renders a segmented Bootstrap progress bar representing the building's
    status (health) points. Each segment maps to one status point:
      - Segments up to status_points   → bg-warning (healthy, click = remove/demolish)
      - Segments above status_points   → bg-danger  (damaged, click = repair)

    Button IDs follow the pattern "{type}-{id}|remove-{n}" / "{type}-{id}|repair-{n}"
    so that techtree.js can parse type, id, order and AP count from the id attribute.
--}}
@if(($tech['level'] ?? 0) > 0)
@php
    $maxSP    = (int)($tech['max_status_points'] ?? 0);
    $currentSP= (int)($tech['status_points'] ?? 0);
    $techId   = (int)($tech['id'] ?? 0);
    $widthPct = $maxSP > 0 ? round(100 / $maxSP) : 0;
@endphp
<div class="progress status_points" style="height:20px;">
    @for($n = 1; $n <= $maxSP; $n++)
    @if($n <= $currentSP)
        {{-- Healthy segment — clicking removes one status point (AP cost = n) --}}
        <a id="{{ $type }}-{{ $techId }}|remove-{{ $n }}"
           class="progress-bar bg-warning{{ $apAvailable <= 0 ? ' disabled' : '' }}"
           style="width:{{ $widthPct }}%;"
           href="#"
           title="{{ $n }} SP — abreißen"
           role="button">&nbsp;</a>
    @else
        {{-- Damaged segment — clicking repairs up to this point (AP cost = n - status_points) --}}
        @php $repairAp = $n - $currentSP; @endphp
        <a id="{{ $type }}-{{ $techId }}|repair-{{ $repairAp }}"
           class="progress-bar bg-danger{{ $apAvailable <= 0 ? ' disabled' : '' }}"
           style="width:{{ $widthPct }}%;"
           href="#"
           title="{{ $n }} SP — reparieren ({{ $repairAp }} AP)"
           role="button">&nbsp;</a>
    @endif
    @endfor
</div>
@endif
