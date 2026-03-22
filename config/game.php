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
];
