<?php

/**
 * Advisor (personell) definitions — canonical source of truth for all per-advisor mechanics.
 *
 * Fields:
 *   id           — DB primary key in `personell` table
 *   supply_cost  — supply consumed per active advisor (same for all types currently)
 *   ap_type      — which action-point pool this advisor fills
 *                  'construction' | 'research' | 'navigation' | 'economy' | 'strategy'
 *   moral_per_unit — moral change per active advisor (minor effects)
 *   credits      — hire cost in credits
 *
 * Note: Advisors do not decay. Rank promotion is governed by config('game.advisor').
 *
 * Localization: lang/de/advisors.php
 */
return [

    'engineer' => [
        'id'           => 35,
        'supply_cost'  => 2,
        'ap_type'      => 'construction',
        'moral_per_unit' => 0,
        'credits'      => 500_000,
    ],

    'scientist' => [
        'id'           => 36,
        'supply_cost'  => 2,
        'ap_type'      => 'research',
        'moral_per_unit' => 0,
        'credits'      => 1_000_000,
    ],

    'pilot' => [
        'id'           => 89,
        'supply_cost'  => 2,
        'ap_type'      => 'navigation',
        'moral_per_unit' => 0,
        'credits'      => 500_000,
    ],

    'trader' => [
        'id'           => 92,
        'supply_cost'  => 2,
        'ap_type'      => 'economy',
        'moral_per_unit' => 0,
        'credits'      => 750_000,
    ],

    'stratege' => [
        'id'             => 93,
        'supply_cost'    => 2,
        'ap_type'        => 'strategy',
        'moral_per_unit' => 0,
        'credits'        => 1_500_000,
    ],

];
