<?php

/**
 * Ship definitions — canonical source of truth for all per-ship mechanics.
 *
 * Fields:
 *   id               — DB primary key in `ships` table
 *   moving_speed     — tiles per tick (fleet moves at slowest ship's speed)
 *   supply_cost      — supply consumed per ship unit (0 for unmanned craft)
 *   moral_per_unit   — moral change per ship in colony fleet (used by MoralService)
 *   credits          — base build cost per unit in credits
 *
 * Ships do NOT decay. They are either intact or destroyed (combat, mission hazards).
 * Maintenance pressure comes from the Hangar building decaying, not from the ships themselves.
 *
 * Fleet speed = min(moving_speed) of all ships in fleet.
 *
 * Localization: lang/de/ships.php, lang/en/ships.php
 */
return [

    // ── Unmanned ──────────────────────────────────────────────────────────────

    'sonde' => [
        'id'             => 85,         // new — DB record to be created in migration
        'moving_speed'   => 5,          // fastest unit in the game
        'supply_cost'    => 0,          // unmanned — no crew, no supply upkeep
        'moral_per_unit' => 0,
        'credits'        => 5_000,
    ],

    // ── Military ──────────────────────────────────────────────────────────────

    'korvette' => [
        'id'             => 37,         // ex fighter1
        'moving_speed'   => 4,
        'supply_cost'    => 14,         // high — limits fleet size organically
        'moral_per_unit' => -1,
        'credits'        => 150_000,
    ],

    // ── Transport ─────────────────────────────────────────────────────────────

    'frachter' => [
        'id'             => 47,         // ex smallTransporter
        'moving_speed'   => 3,
        'supply_cost'    => 6,
        'moral_per_unit' => 1,
        'credits'        => 15_000,
    ],

];
