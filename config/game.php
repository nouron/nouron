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
    // Per-entity supply_cap and supply_cost values live in config/buildings.php,
    // config/ships.php, config/techs.php and config/advisors.php.
    'supply' => [
        'cap_max'      => 200,  // absolute hard cap across the whole colony
        'cost_advisor' => 2,    // supply per active advisor (same for all types)
    ],

    // Building/ship/research decay: global multipliers applied on top of per-entity decay_rate.
    // Per-entity decay_rate values live in config/buildings.php, config/ships.php, config/techs.php.
    'decay' => [
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
            'move'    => 1,  // civilian — move fleet to coordinates
            'hold'    => 1,  // civilian — hold position for one tick
            'trade'   => 1,  // civilian — execute trade at colony
            'join'    => 1,  // civilian — merge with target fleet
            'convoy'  => 1,  // civilian — escort target fleet to its destination
            'defend'  => 2,  // semi-military — move to target fleet's position to defend
            'attack'  => 3,  // military — attack enemy fleet
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

    // Moral system — formula and multiplier bands (see GDD §13).
    // Formula: clamp(Σbuildings + Σresearches + clamp(Σships, -30, +30) + events, -100, +100)
    // Per-entity moral_per_lv / moral_per_unit values live in config/buildings.php,
    // config/techs.php and config/ships.php — MoralService reads from those files.
    'moral' => [
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
