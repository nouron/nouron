{{--
    Upgrade button with segmented AP bar — ONE component for the colony "Ausbauen"
    button and the techtree Kenntnis level-up button. Companion files:
    public/js/ap-bar.js (window.NouronApBar, spread into the Alpine component) and
    public/css/ap-upgrade-button.css.

    The button shows the label, the AP-cost chip and a segmented footer bar (one
    segment per AP needed). On desktop hover the next segment lights up as a +1 ghost.

    Row contract (the Alpine object named by $row):
      ap_spend, ap_for_levelup            — required, drive the bar
      ap_cap: null | { cap, spent, remaining }
        null/absent -> plain bar (filled / empty), no marker, no tooltip.
        object      -> four bar states (filled earlier, filled today = --today,
                       free today = --free, locked today = --locked), the thin
                       "day limit" marker at ap_spend + remaining, and a hover/focus
                       tooltip (colony.ap_cap_tooltip). remaining <= 0 disables the
                       button (aria-disabled, tooltip stays reachable).
      Knowledge rows carry no ap_cap yet; the states appear automatically once they do.

    Params:
      row          — Alpine expression of the row, e.g. "selectedBuilding".
      click        — Alpine statement run on click (guarded while blocked).
      btnLabel     — Alpine expression yielding the label string (not `label`: the
                     included ap-cost-chip partial reads $label and would show it).
      chip         — ap-cost-chip type ('build' | 'research'), default 'build'.
      classExpr    — optional extra entries for the :class object, e.g.
                     "'tile-action-btn--secondary': canRepair(selectedBuilding)".
      blockedExpr  — optional extra Alpine condition that disables the button
                     (e.g. no AP left), besides the per-Sol cap.
      tipId        — unique id of the tooltip element (aria-describedby).
--}}
@php
    $blocked = "apCapReached({$row})" . (!empty($blockedExpr) ? " || ({$blockedExpr})" : "");
    $tipId = $tipId ?? "ap-cap-tip";
@endphp
<button class="tile-action-btn tile-action-btn--barred ap-upgrade-btn"
    :class="{
        {!! $classExpr ?? "" !!}
            'tile-action-btn--hascap': !!{{ $row }}.ap_cap,
            'tile-action-btn--capped': {{ $blocked }},
    }"
    x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
    :aria-disabled="({{ $blocked }}) ? 'true' : null"
    :aria-describedby="{{ $row }}.ap_cap ? '{{ $tipId }}' : null"
    @click="if (!({{ $blocked }})) { {{ $click }} }">
    <span class="tile-action-btn__body">
        <span class="tile-action-btn__main" x-text="{{ $btnLabel }}"></span>
    </span>
    @include("partials.ap-cost-chip", ["amount" => 1, "type" => $chip ?? "build"])
    <span class="btn-segbar btn-segbar--ap" aria-hidden="true">
        <template x-for="i in {{ $row }}.ap_for_levelup" :key="i">
            <span class="btn-seg"
                :class="{
                    ...apSegClasses({{ $row }}, i),
                    'btn-seg--ghost': hover && i === {{ $row }}.ap_spend + 1 && !({{ $blocked }}),
                }"></span>
        </template>
        <span class="btn-seg-limit" x-show="apCapLimitPos({{ $row }}) !== null" x-cloak
            :style="{ left: apCapLimitPos({{ $row }}) + '%' }"></span>
    </span>
    <span id="{{ $tipId }}" class="tile-action-btn__tip"
        role="tooltip">{{ __("colony.ap_cap_tooltip") }}</span>
</button>
