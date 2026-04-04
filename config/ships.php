<?php

/**
 * Ship definitions — canonical source of truth for all per-ship mechanics.
 *
 * Fields:
 *   id               — DB primary key in `ships` table
 *   supply_cost      — supply consumed per ship unit in fleet
 *   moral_per_unit   — moral change per ship in colony fleet (used by MoralService)
 *                      Total ship contribution is capped at ±30 before global clamp (game.moral.ships_cap).
 *   decay_rate       — status_points lost per tick per ship (also stored in DB)
 *   max_status_points — status_points reset value
 *   credits          — base build cost per unit in credits
 *
 * Decay reference: 10 d → 2.0 | 14 d → 1.43 | 21 d → 0.95 | 30 d → 0.67
 *
 * Note: decay_rate, max_status_points and supply_cost are also stored in the `ships` DB table.
 * After changing values here run: php artisan game:sync-techs (to be implemented)
 *
 * Localization: lang/de/ships.php
 */
return [

    // ── Military ships ────────────────────────────────────────────────────────

    'fighter1' => [
        'id'                => 37,
        'supply_cost'       => 8,
        'moral_per_unit'    => -1,
        'decay_rate'        => 2.0,     // 10 days — light craft need frequent maintenance
        'max_status_points' => 20,
        'credits'           => 80_000,
    ],

    'frigate1' => [
        'id'                => 29,
        'supply_cost'       => 14,
        'moral_per_unit'    => -2,
        'decay_rate'        => 1.43,    // 14 days
        'max_status_points' => 20,
        'credits'           => 500_000,
    ],

    'battlecruiser1' => [
        'id'                => 49,
        'supply_cost'       => 25,
        'moral_per_unit'    => -4,
        'decay_rate'        => 1.43,    // 14 days
        'max_status_points' => 20,
        'credits'           => 2_000_000,
    ],

    // ── Transport / Economy ships ─────────────────────────────────────────────

    'smallTransporter' => [
        'id'                => 47,
        'supply_cost'       => 2,
        'moral_per_unit'    => 1,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'credits'           => 10_000,
    ],

    'mediumTransporter' => [
        'id'                => 83,
        'supply_cost'       => 4,
        'moral_per_unit'    => 1,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'credits'           => 20_000,
    ],

    'largeTransporter' => [
        'id'                => 84,
        'supply_cost'       => 7,
        'moral_per_unit'    => 2,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'credits'           => 40_000,
    ],

];
