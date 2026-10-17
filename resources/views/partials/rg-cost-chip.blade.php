{{--
    Regolith cost chip — sits next to the AP cost chip on action buttons that also
    pay Regolith (Ausbauen at the start of a cycle, Reparieren). Shows exactly the
    amount the server charges for that click; hidden at 0. Turns red ("short") when
    the colony cannot pay it. The abbreviation "Rg" is fixed (never translated).

    Params (Alpine expressions, evaluated in the surrounding x-data scope):
      amountExpr — Regolith charged by this click, e.g. "selectedBuilding.repair_cost".
      haveExpr   — current Regolith stock, default "regolith".
--}}
@php
    $rgHave = $haveExpr ?? "regolith";
@endphp
<span class="ap-chip ap-cost-chip rg-cost-chip" x-show="({{ $amountExpr }}) > 0" x-cloak
    :class="{ 'rg-cost-chip--short': ({{ $amountExpr }}) > ({{ $rgHave }} ?? 0) }" aria-hidden="true"
    x-text="({{ $amountExpr }}) + ' Rg'"></span>
