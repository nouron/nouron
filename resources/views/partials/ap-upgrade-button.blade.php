{{--
    Upgrade button with segmented AP bar — ONE component for the colony "Ausbauen"
    button and the techtree Kenntnis level-up button. Companion files:
    public/js/ap-bar.js (window.NouronApBar, spread into the Alpine component) and
    public/css/ap-upgrade-button.css.

    The button shows the label, the AP-cost chip and a segmented footer bar (one
    segment per AP needed, wrapped into rows of at most 10). On desktop hover the next segment lights up as a +1 ghost.

    Row contract (the Alpine object named by $row):
      ap_spend, ap_for_levelup            — required, drive the bar
      ap_cap: null | { cap, spent, remaining }
        null/absent -> plain bar (filled / empty), no marker, no tooltip.
        object      -> four bar states (filled earlier, filled today = --today,
                       free today = --free, locked today = --locked), the thin
                       "day limit" marker at ap_spend + remaining, and a hover/focus
                       tooltip (colony.ap_cap_tooltip). remaining <= 0 disables the
                       button (aria-disabled, tooltip stays reachable).
      Cap decoration (states, marker) only shows once ap_spend > 0; at a level
                       start the bar is plain. Reached-state/tooltip are independent of that.
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
      gateReasonExpr — optional Alpine expression yielding a prerequisite-unmet reason
                     text (string) or null. A reason blocks the button (same disabled
                     look/aria-disabled) and its text is the tooltip; it takes precedence
                     over the day-cap reason. The cap tooltip shows only when a cap applies
                     (capped, or the day-limit marker is visible).
      rgExpr       — optional Alpine expression yielding the Regolith this click charges
                     (rg-cost-chip next to the AP chip; hidden at 0, red when unaffordable).
      tipId        — unique id of the tooltip element (aria-describedby).
--}}
@php
    $gate = !empty($gateReasonExpr) ? $gateReasonExpr : "null";
    $blocked = "!!({$gate}) || apCapReached({$row})" . (!empty($blockedExpr) ? " || ({$blockedExpr})" : "");
    $tipOn = "!!({$gate}) || apCapTipApplies({$row})";
    $tipId = $tipId ?? "ap-cap-tip";
@endphp
<button class="tile-action-btn tile-action-btn--barred ap-upgrade-btn"
    :class="{
        {!! $classExpr ?? "" !!}
            'tile-action-btn--hascap': {{ $tipOn }},
            'tile-action-btn--capped': {{ $blocked }},
    }"
    x-data="{ hover: false }" :style="{ '--seg-rows': apBarLayout({{ $row }}).rows }" @mouseenter="hover = true"
    @mouseleave="hover = false" :aria-disabled="({{ $blocked }}) ? 'true' : null"
    :aria-describedby="({{ $tipOn }}) ? '{{ $tipId }}' : null"
    @click="if (!({{ $blocked }})) { {{ $click }} }">
    <span class="tile-action-btn__body">
        <span class="tile-action-btn__main" x-text="{{ $btnLabel }}"></span>
    </span>
    <span class="tile-action-btn__costs">
        @if (!empty($rgExpr))
            @include("partials.rg-cost-chip", ["amountExpr" => $rgExpr])
        @endif
        @include("partials.ap-cost-chip", ["amount" => 1, "type" => $chip ?? "build"])
    </span>
    <span class="btn-segbar btn-segbar--ap" aria-hidden="true"
        :style="{ '--seg-cols': apBarLayout({{ $row }}).cols }">
        <template x-for="i in {{ $row }}.ap_for_levelup" :key="i">
            <span class="btn-seg"
                :class="{
                    ...apSegClasses({{ $row }}, i),
                    'btn-seg--ghost': hover && i === {{ $row }}.ap_spend + 1 && !({{ $blocked }}),
                }"></span>
        </template>
        <span class="btn-seg-limit" x-show="apCapLimitPlace({{ $row }}) !== null" x-cloak
            :style="{
                left: apCapLimitPos({{ $row }}) + '%',
                '--limit-row': apCapLimitPlace({{ $row }})
                    ?.row ?? 0
            }"></span>
    </span>
    <span id="{{ $tipId }}" class="tile-action-btn__tip" role="tooltip"
        x-text="{{ $gate }} || {{ \Illuminate\Support\Js::from(__("colony.ap_cap_tooltip")) }}"></span>
</button>
