<?php

/**
 * Advisor (personell) definitions — canonical source of truth for all per-advisor mechanics.
 *
 * Fields:
 *   id           — DB primary key in `personell` table
 *   ap_type      — which action-point pool this advisor fills
 *                  'construction' | 'research' | 'navigation' | 'economy' | 'strategy'
 *   moral_per_unit — moral change per active advisor (minor effects)
 *   credits      — hire cost in credits
 *
 * Advisors do NOT consume Supply — their cost runs through Credits only (see GDD §12).
 * Advisors do not decay. Rank promotion is governed by config('game.advisor').
 *
 * Localization: lang/de/advisors.php
 */
return [

    // credits = one-time hire cost (Rang 1 = Junior).
    // All 5 at once costs 2150 Cr — forces real day-1 decisions with 3000 Cr start.
    // Rang 2 (Senior) and Rang 3 (Experte) hire costs: see GDD §12 (~150 / ~400 Cr).

    'engineer' => [
        'id'             => 35,
        'ap_type'        => 'construction',
        'moral_per_unit' => 0,
        'credits'        => 300,    // critical for early building — first hire
    ],

    'scientist' => [
        'id'             => 36,
        'ap_type'        => 'research',
        'moral_per_unit' => 0,
        'credits'        => 400,
    ],

    'pilot' => [
        'id'             => 89,
        'ap_type'        => 'navigation',
        'moral_per_unit' => 0,
        'credits'        => 500,    // fleet-focused, later priority
    ],

    'trader' => [
        'id'             => 92,
        'ap_type'        => 'economy',
        'moral_per_unit' => 0,
        'credits'        => 350,
    ],

    'stratege' => [
        'id'             => 93,
        'ap_type'        => 'strategy',
        'moral_per_unit' => 0,
        'credits'        => 600,    // military/strategy — typically late-game hire
    ],

];
