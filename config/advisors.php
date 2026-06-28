<?php

/**
 * Advisor (personell) definitions — canonical source of truth for all per-advisor mechanics.
 *
 * Fields:
 *   id           — DB primary key in `personell` table
 *   ap_type      — which action-point pool this advisor fills
 *                  'construction' | 'research' | 'navigation' | 'economy' | 'strategy'
 *   trust_per_unit — trust change per active advisor (minor effects)
 *   credits      — hire cost in credits
 *
 * Advisors do NOT consume Supply — their cost runs through Credits only (see GDD §12).
 * Advisors do not decay. Rank promotion is governed by config('game.advisor').
 *
 * Slot binding (2026-06-28, GDD §13 "Slot-System"):
 *   Slot 1 (fix): engineer — gate: CC Lv1.
 *   Slots 2–4 (generic): scientist/pilot/trader — gate: build order of the
 *     matching path building (sciencelab→scientist, hangar→pilot, bar→trader).
 *     See AdvisorController::PATH_BUILDINGS.
 *   Slot 5 (fix): strategist — gate: CC Lv3 + SecurityHub Lv1 (Pfad D).
 *     CC Lv3 check is already in AdvisorController ($ccGate=3 for 'strategist').
 *     SecurityHub check is a TODO (see AdvisorController comment "gate wired up later").
 *
 * Localization: lang/de/advisors.php
 */
return [

    // credits = one-time hire cost (Rang 1 = Junior). Type-specific — see GDD §13.

    'engineer' => [
        'id' => 35,
        'ap_type' => 'construction',
        'trust_per_unit' => 0,
        'credits' => 300,    // critical for early building — first hire
    ],

    'scientist' => [
        'id' => 36,
        'ap_type' => 'research',
        'trust_per_unit' => 0,
        'credits' => 400,
    ],

    'pilot' => [
        'id' => 89,
        'ap_type' => 'navigation',
        'trust_per_unit' => 0,
        'credits' => 500,    // fleet-focused, later priority
    ],

    'trader' => [
        'id' => 92,
        'ap_type' => 'economy',
        'trust_per_unit' => 0,
        'credits' => 350,
    ],

    'strategist' => [
        'id' => 93,
        'ap_type' => 'strategy',
        'trust_per_unit' => 0,
        'credits' => 600,    // military/strategy — typically late-game hire
    ],

];
