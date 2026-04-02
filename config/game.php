<?php

/**
 * Nouron game-specific configuration.
 */

return [
    // Set to false in production to enforce all game rules strictly.
    // When true, resource checks are bypassed so techs can be tested freely.
    'dev_mode' => (bool) env('GAME_DEV_MODE', true),

    'tick' => [
        // How many hours is one tick (currently 1 tick = 1 day)
        'length' => 24,
        // The daily calculation window (server time, hour of day)
        'calculation' => [
            'start' => 3,
            'end'   => 4,
        ],
        // Fixed tick number used in test cases
        'testcase' => 14479,
    ],

    // Resource production per tick: building_id => [resource_id => amount_per_level]
    // Each colony building produces (level × rate) units of the given resource per tick.
    'production' => [
        27 => [4 => 10],   // oremine        → ferum      × 10/level
        41 => [5 => 10],   // silicatemine   → silicates  × 10/level
        42 => [3 => 10],   // waterextractor → water      × 10/level
        // techs_powerstation not yet in DB — add entry here once available
    ],

    // Supply cap model — supply is not generated per tick, it is a capacity ceiling.
    // supply_cap = cap_commandcenter (flat) + count(housingComplex) × cap_housingcomplex
    // Military ships cost significantly more than transporters (see GDD §1.1 and §6).
    'supply' => [
        'cap_commandcenter'  => 15,   // building_id 25 — flat bonus, not per level
        'cap_housingcomplex' => 8,    // building_id 28 — per unit (level irrelevant)
        'cap_max'            => 200,  // absolute hard cap
        'cost_advisor'       => 2,    // supply per active advisor
        'ship_cost' => [
            37 => 8,   // fighter1       — military
            29 => 14,  // frigate1       — military
            49 => 25,  // battlecruiser1 — military (Phase 3: TBD if buildable)
            47 => 2,   // smallTransporter
            83 => 4,   // mediumTransporter
            84 => 7,   // largeTransporter
        ],
    ],

    // Building decay: status_points decremented per tick per colony building.
    // When status_points hits 0 the building loses one level and status_points resets.
    'decay' => [
        'rate'          => 1,    // fallback rate (buildings, until per-type rates are migrated)
        'combat_factor' => 2,    // ship decay multiplier in a combat tick
    ],

    // Navigation-AP cost per fleet order type.
    // Advisor rank-up: cumulative active_ticks required per rank (rank => ticks).
    // Configurable so balancing can be adjusted after first playtest (see GDD §8).
    'advisor' => [
        'rank_thresholds' => [1 => 10, 2 => 20],
        'ap_per_rank'     => [1 => 4, 2 => 7, 3 => 12],
    ],

    // Military orders are deliberately more expensive than civilian ones (see GDD §1.1).
    // Rule: military AP cost >= civilian AP cost — never violate this ratio.
    'fleet' => [
        'order_costs' => [
            'move'   => 1,  // civilian — move fleet to coordinates
            'trade'  => 1,  // civilian — execute trade at colony
            'attack' => 3,  // military — attack enemy fleet/colony
        ],
    ],

    // Combat power per ship type (ship_id => power value).
    // Transports have 0 combat power (non-combat).
    'combat' => [
        'ship_power' => [
            37 => 1,   // fighter1
            29 => 3,   // frigate1
            49 => 10,  // battlecruiser1
            47 => 0,   // smallTransporter
            83 => 0,   // mediumTransporter
            84 => 0,   // largeTransporter
        ],
    ],

    // Galaxy overview map
    'galaxy_view' => [
        'range'      => 10000,
        'offset'     => 0,
        'scale'      => 0.05,
        'systemSize' => 3,
    ],

    // System detail map
    'system_view' => [
        'range'      => 100,
        'offset'     => 100,
        'scale'      => 10,
        'planetSize' => 10,
        'slotSize'   => 10,
    ],
];
