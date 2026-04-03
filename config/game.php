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
        'rate'           => 1,    // fallback rate (buildings, until per-type rates are migrated)
        'combat_factor'  => 2,    // ship decay multiplier in a combat tick
        'overcap_factor' => 2.0,  // decay multiplier when colony is over supply cap
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

    // Trade marketplace — AP costs for Händler (economy AP).
    // Creating an offer costs max(1, floor(amount × price / threshold)) AP.
    // Accepting an offer costs 1 AP (paid by the acceptor/buyer).
    // Removing an offer costs 0 AP.
    'trade' => [
        'ap_cost_threshold' => 1000,  // divisor: amount × price / threshold = AP cost
    ],

    // Moral system — all values used by MoralService::calculate() (see GDD §13).
    // Formula: clamp(Σbuildings + Σresearches + clamp(Σships, -30, +30) + tax + events, -100, +100)
    'moral' => [
        // Buildings: building_id => moral_per_level (only buildings with status_points > 0 count)
        'buildings' => [
            32 =>  2,   // temple
            45 =>  2,   // parc
            46 =>  3,   // hospital
            48 =>  1,   // public_security
            50 =>  2,   // denkmal
            51 =>  2,   // university
            53 =>  3,   // stadium
            56 =>  2,   // museum
            65 =>  1,   // recyclingStation
            52 => -1,   // bar
            54 => -2,   // casino
            55 => -3,   // prison
            64 => -1,   // wastedisposal
            66 => -2,   // secretOps
            68 => -1,   // militarySpaceyard
        ],
        // Researches: research_id => moral_per_level
        'researches' => [
            33 =>  1,   // biology
            72 =>  2,   // medicalScience
            79 =>  1,   // diplomacy
            80 =>  1,   // politicalScience
            81 => -2,   // military  (raised from -1: see GDD §13 rationale)
            34 =>  1,   // languages
        ],
        // Ships: ship_id => moral_per_ship (applied to colony_ships.amount)
        // Military ships cause unrest; economy/transport ships signal prosperity.
        // The total ship contribution is capped at ±30 before entering the sum.
        'ships' => [
            37 => -1,   // fighter1        — military
            29 => -2,   // frigate1        — military
            49 => -4,   // battlecruiser1  — military
            47 =>  1,   // smallTransporter  — economy
            83 =>  1,   // mediumTransporter — economy
            84 =>  2,   // largeTransporter  — economy
        ],
        // Hard cap for total ship moral contribution (before global clamp).
        'ships_cap' => 30,
        // Production multipliers by moral band (see GDD §13 "Effekte der Moral").
        'production_multiplier' => [
            ['min' =>  61, 'max' => 100, 'factor' => 1.20],
            ['min' =>  21, 'max' =>  60, 'factor' => 1.10],
            ['min' => -20, 'max' =>  20, 'factor' => 1.00],
            ['min' => -60, 'max' => -21, 'factor' => 0.85],
            ['min' => -100,'max' => -61, 'factor' => 0.70],
        ],
        // AP multipliers by moral band.
        'ap_multiplier' => [
            ['min' =>  61, 'max' => 100, 'factor' => 1.10],
            ['min' =>  21, 'max' =>  60, 'factor' => 1.05],
            ['min' => -20, 'max' =>  20, 'factor' => 1.00],
            ['min' => -60, 'max' => -21, 'factor' => 0.90],
            ['min' => -100,'max' => -61, 'factor' => 0.80],
        ],
        // Event moral effects (one-shot, active for exactly 1 tick).
        // Multiple events of the same key in one tick do NOT stack — strongest wins.
        'events' => [
            'building_level_up'     =>  1,
            'building_level_down'   => -3,
            'research_level_up'     =>  2,
            'trade_success'         =>  2,
            'trade_blocked'         => -3,
            'combat_won'            =>  2,
            'combat_lost'           => -5,
            'colony_attacked'       => -4,
            'war_declared'          => -8,
            'treaty_signed'         =>  3,
        ],
    ],
];
