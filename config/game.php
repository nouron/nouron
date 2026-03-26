<?php

/**
 * Nouron game-specific configuration.
 * Migrated from config/autoload/global.php (Laminas).
 */

return [
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

    // Supply production per tick per building level (user-level resource)
    // supply += Σ(commandCenter.level × cc_rate) + Σ(housingComplex.level × housing_rate)
    'supply' => [
        'commandcenter_rate'  => 5,   // building_id 25
        'housingcomplex_rate' => 10,  // building_id 28
    ],

    // Building decay: status_points decremented per tick per colony building.
    // When status_points hits 0 the building loses one level and status_points resets.
    'decay' => [
        'rate' => 1,
    ],

    // Combat power per ship type (ship_id => power value).
    // Transports and colony ships have 0 combat power (non-combat).
    'combat' => [
        'ship_power' => [
            37 => 1,   // fighter1
            29 => 3,   // frigate1
            49 => 10,  // battlecruiser1
            47 => 0,   // smallTransporter
            83 => 0,   // mediumTransporter
            84 => 0,   // largeTransporter
            88 => 0,   // colonyShip
        ],
    ],

    // Galaxy overview map (Laminas: galaxy_view_config)
    'galaxy_view' => [
        'range'      => 10000,
        'offset'     => 0,
        'scale'      => 0.05,
        'systemSize' => 3,
    ],

    // System detail map (Laminas: system_view_config)
    'system_view' => [
        'range'      => 100,
        'offset'     => 100,
        'scale'      => 10,
        'planetSize' => 10,
        'slotSize'   => 10,
    ],
];
