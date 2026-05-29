{{--
    techlevelup_bar partial
    Variables: $tech (array), $type (string), $apAvailable (int)

    Renders a segmented progress bar showing AP investment progress towards the
    next level:
      - Segments up to ap_spend        → bg-success (already invested, not clickable)
      - Remaining segments             → bg-info    (clickable if AP available)

    Button IDs follow "{type}-{id}|add-{n}" where n = AP count to invest.
    techtree.js parses type, id, order="add", ap=n from the id attribute.
--}}
@php
    $apTotal  = (int)($tech['ap_for_levelup'] ?? 1);
    $apSpend  = (int)($tech['ap_spend'] ?? 0);
    $techId   = (int)($tech['id'] ?? 0);
    $widthPct = $apTotal > 0 ? round(100 / $apTotal) : 0;
@endphp
<div class="progress ap_spend" style="height:20px;">
    @for($n = 1; $n <= $apTotal; $n++)
    @if($n <= $apSpend)
        <span class="progress-bar bg-success"
              style="width:{{ $widthPct }}%;"
              title="{{ $n }} / {{ $apTotal }} AP investiert">&nbsp;</span>
    @else
        @php $investAp = $n - $apSpend; @endphp
        <a id="{{ $type }}-{{ $techId }}|add-{{ $investAp }}"
           class="progress-bar bg-info{{ $apAvailable <= 0 ? ' disabled' : '' }}"
           style="width:{{ $widthPct }}%;"
           href="#"
           title="{{ $n }} / {{ $apTotal }} AP — {{ $investAp }} AP investieren"
           role="button">&nbsp;</a>
    @endif
    @endfor
</div>
@if($apAvailable <= 0 && $apSpend < $apTotal)
    @php
        $apHintKey = match($type) {
            'research' => 'techtree.hint_no_research_ap',
            default    => 'techtree.hint_no_construction_ap',
        };
    @endphp
    <p class="text-muted small mt-1 mb-0">
        <i class="bi bi-info-circle"></i> {{ __($apHintKey) }}
    </p>
@endif
