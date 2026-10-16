{{--
    AP-invested bar, embedded as a segmented footer strip inside the Ausbauen
    button. One segment per AP needed for the level-up; gaps reveal the button
    background as notches. On desktop hover ($hoverExpr true) the next segment
    lights up as a ghost to preview the +1 AP an invest click adds. Decorative —
    the value is already in the button label.

    With the per-Sol AP cap active (building.ap_cap) four states show: filled
    earlier, filled today (--today), free today (default track) and locked for
    today (--locked, dimmed). Cap off: plain filled/empty, as before.

    Params:
      hoverExpr — Alpine expression truthy while the button is hovered.
--}}
<span class="btn-segbar btn-segbar--ap" aria-hidden="true">
    <template x-for="i in selectedBuilding.ap_for_levelup" :key="i">
        <span class="btn-seg"
            :class="{
                ...apSegClasses(selectedBuilding, i),
                'btn-seg--ghost': ({{ $hoverExpr }}) && i === selectedBuilding.ap_spend + 1 && !apCapReached(
                    selectedBuilding),
            }"></span>
    </template>
    <span class="btn-seg-limit" x-show="apCapLimitPos(selectedBuilding) !== null" x-cloak
        :style="{ left: apCapLimitPos(selectedBuilding) + '%' }"></span>
</span>
