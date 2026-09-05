{{--
    Condition (status points) bar, embedded as a segmented footer strip inside
    the Reparieren button. One segment per status point; gaps reveal the button
    background as notches. On desktop hover ($hoverExpr true) the next segment
    lights up as a ghost to preview the +1 a repair adds. Filled-segment color
    tracks conditionTone() (neutral/warning/danger — Owner-Fund 2026-09-05):
    healthy stays white, damaged turns amber, critical uses a dark fill with a
    light outline since red segments would be invisible on the red button.

    Params:
      hoverExpr — Alpine expression truthy while the button is hovered.
--}}
<span class="btn-segbar btn-segbar--status"
    :class="{
        'btn-segbar--status--warning': conditionTone(selectedBuilding) === 'warning',
        'btn-segbar--status--danger': conditionTone(selectedBuilding) === 'danger',
    }"
    aria-hidden="true">
    <template x-for="i in (selectedBuilding.max_status_points ?? 20)" :key="i">
        <span class="btn-seg"
            :class="{
                'btn-seg--filled': i <= selectedBuilding.status_points,
                'btn-seg--ghost': ({{ $hoverExpr }}) && i === selectedBuilding.status_points + 1,
            }"></span>
    </template>
</span>
