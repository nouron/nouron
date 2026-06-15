{{--
    Condition (status points) bar, embedded as a segmented footer strip inside
    the Reparieren button. One segment per status point; gaps reveal the button
    background as notches. On desktop hover ($hoverExpr true) the next segment
    lights up as a ghost to preview the +1 a repair adds. Decorative — the value
    is already in the button label.

    Params:
      hoverExpr — Alpine expression truthy while the button is hovered.
--}}
<span class="btn-segbar btn-segbar--status" aria-hidden="true">
    <template x-for="i in (selectedBuilding.max_status_points ?? 20)" :key="i">
        <span class="btn-seg"
            :class="{
                'btn-seg--filled': i <= selectedBuilding.status_points,
                'btn-seg--ghost': ({{ $hoverExpr }}) && i === selectedBuilding.status_points + 1,
            }"></span>
    </template>
</span>
