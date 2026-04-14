<?php

/**
 * Advisor (personell) definitions — canonical source of truth for all per-advisor mechanics.
 *
 * Fields:
 *   id           — DB primary key in `personell` table
 *   ap_type      — which action-point pool this advisor fills
 *                  'construction' | 'knowledge' | 'navigation' | 'economy' | 'strategy'
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
    // Rang 2 (Senior) and Rang 3 (Experte) hire costs: see GDD §12 (~150 / ~400 Cr).
    // Exact values to be calibrated after first playtest.

    'advisor_engineer' => [
        'id'             => 35,
        'ap_type'        => 'construction',
        'moral_per_unit' => 0,
        'credits'        => 50,
    ],

    'advisor_scientist' => [
        'id'             => 36,
        'ap_type'        => 'knowledge',
        'moral_per_unit' => 0,
        'credits'        => 50,
    ],

    'advisor_pilot' => [
        'id'             => 89,
        'ap_type'        => 'navigation',
        'moral_per_unit' => 0,
        'credits'        => 50,
    ],

    'advisor_consul' => [
        'id'             => 92,
        'ap_type'        => 'economy',
        'moral_per_unit' => 0,
        'credits'        => 50,
    ],

    'advisor_strategist' => [
        'id'             => 93,
        'ap_type'        => 'strategy',
        'moral_per_unit' => 0,
        'credits'        => 50,
    ],

];
