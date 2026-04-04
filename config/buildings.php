<?php

/**
 * Building definitions — canonical source of truth for all per-building mechanics.
 *
 * Fields:
 *   id               — DB primary key in `buildings` table
 *   supply_cap       — flat supply cap granted (commandCenter) or per-unit cap (housingComplex)
 *   supply_cost      — supply consumed while the building exists at level > 0
 *   moral_per_lv     — moral change per building level (used by MoralService)
 *   decay_rate       — status_points lost per tick (also stored in DB, used by GameTick decay)
 *   max_status_points — status_points reset value after level-down (also stored in DB)
 *   max_level        — hard level cap (null = uncapped, practically limited by supply)
 *   credits          — base build cost in credits
 *
 * Decay reference: decay_rate = max_status_points / target_days
 *   7 d → 2.86 | 10 d → 2.0 | 14 d → 1.43 | 21 d → 0.95
 *   30 d → 0.67 | 45 d → 0.44 | 60 d → 0.33
 *
 * Note: decay_rate, max_status_points and supply_cost are also stored in the `buildings` DB table.
 * After changing values here run: php artisan game:sync-techs (to be implemented)
 *
 * Localization: lang/de/buildings.php
 */
return [

    // ── Supply-Cap providers ──────────────────────────────────────────────────

    'commandCenter' => [
        'id'                => 25,
        'supply_cap'        => 15,      // flat cap bonus (not per level) — requires level > 0
        'supply_cost'       => 0,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.33,    // 60 days
        'max_status_points' => 20,
        'max_level'         => 10,
        'credits'           => 100_000,
    ],

    'housingComplex' => [
        'id'                => 28,
        'supply_cap'        => 8,       // per unit (level), hard cap = 200
        'supply_cost'       => 0,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.44,    // 45 days
        'max_status_points' => 20,
        'max_level'         => 200,
        'credits'           => 5_000,
    ],

    // ── Industry ──────────────────────────────────────────────────────────────

    'oremine' => [
        'id'                => 27,
        'supply_cost'       => 2,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 150,
    ],

    'silicatemine' => [
        'id'                => 41,
        'supply_cost'       => 2,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 300,
    ],

    'waterextractor' => [
        'id'                => 42,
        'supply_cost'       => 2,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 200,
    ],

    'depot' => [
        'id'                => 30,
        'supply_cost'       => 3,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 20_000,
    ],

    // ── Science / Education ───────────────────────────────────────────────────

    'sciencelab' => [
        'id'                => 31,
        'supply_cost'       => 8,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 8_000,
    ],

    'university' => [
        'id'                => 51,
        'supply_cost'       => 8,
        'moral_per_lv'      => 2,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 20_000,
    ],

    // ── Civil welfare ─────────────────────────────────────────────────────────

    'temple' => [
        'id'                => 32,
        'supply_cost'       => 5,
        'moral_per_lv'      => 2,
        'decay_rate'        => 2.0,     // 10 days — needs regular upkeep
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 5_000,
    ],

    'parc' => [
        'id'                => 45,
        'supply_cost'       => 4,
        'moral_per_lv'      => 2,
        'decay_rate'        => 2.0,     // 10 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 5_000,
    ],

    'hospital' => [
        'id'                => 46,
        'supply_cost'       => 10,
        'moral_per_lv'      => 3,
        'decay_rate'        => 2.0,     // 10 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 12_000,
    ],

    'public_security' => [
        'id'                => 48,
        'supply_cost'       => 8,
        'moral_per_lv'      => 1,
        'decay_rate'        => 2.0,     // 10 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 2_000,
    ],

    'denkmal' => [
        'id'                => 50,
        'supply_cost'       => 2,
        'moral_per_lv'      => 2,
        'decay_rate'        => 0.33,    // 60 days — monuments are built to last
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 1_500,
    ],

    'museum' => [
        'id'                => 56,
        'supply_cost'       => 5,
        'moral_per_lv'      => 2,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 1_200,
    ],

    'recyclingStation' => [
        'id'                => 65,
        'supply_cost'       => 6,
        'moral_per_lv'      => 1,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 3_500,
    ],

    'wastedisposal' => [
        'id'                => 64,
        'supply_cost'       => 6,
        'moral_per_lv'      => -1,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 2_800,
    ],

    // ── Entertainment ─────────────────────────────────────────────────────────

    'stadium' => [
        'id'                => 53,
        'supply_cost'       => 14,
        'moral_per_lv'      => 3,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 5_000,
    ],

    'bar' => [
        'id'                => 52,
        'supply_cost'       => 4,
        'moral_per_lv'      => -1,
        'decay_rate'        => 2.86,    // 7 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 500,
    ],

    'casino' => [
        'id'                => 54,
        'supply_cost'       => 9,
        'moral_per_lv'      => -2,
        'decay_rate'        => 2.86,    // 7 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 2_500,
    ],

    // ── Economy ───────────────────────────────────────────────────────────────

    'tradecenter' => [
        'id'                => 43,
        'supply_cost'       => 7,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 20_000,
    ],

    'bank' => [
        'id'                => 70,
        'supply_cost'       => 14,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 18_000,
    ],

    // ── Military / Security ───────────────────────────────────────────────────

    'civilianSpaceyard' => [
        'id'                => 44,
        'supply_cost'       => 20,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 50_000,
    ],

    'militarySpaceyard' => [
        'id'                => 68,
        'supply_cost'       => 30,
        'moral_per_lv'      => -1,
        'decay_rate'        => 0.44,    // 45 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 60_000,
    ],

    'prison' => [
        'id'                => 55,
        'supply_cost'       => 15,
        'moral_per_lv'      => -3,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 5_000,
    ],

    'secretOps' => [
        'id'                => 66,
        'supply_cost'       => 26,
        'moral_per_lv'      => -2,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level'         => null,
        'credits'           => 2_000,
    ],

];
