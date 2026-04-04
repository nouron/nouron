<?php

/**
 * Research definitions — canonical source of truth for all per-research mechanics.
 *
 * Fields:
 *   id               — DB primary key in `researches` table
 *   supply_cost      — supply consumed while research level > 0
 *   moral_per_lv     — moral change per research level (used by MoralService)
 *   decay_rate       — status_points lost per tick (also stored in DB, used by GameTick decay)
 *   max_status_points — status_points reset value after level-down
 *   credits          — base research cost in credits
 *
 * Decay reference: 21 d → 0.95 | 14 d → 1.43
 *
 * Note: decay_rate, max_status_points and supply_cost are also stored in the `researches` DB table.
 * After changing values here run: php artisan game:sync-techs (to be implemented)
 *
 * Localization: lang/de/techs.php
 */
return [

    // ── Civil researches ─────────────────────────────────────────────────────

    'biology' => [
        'id'                => 33,
        'supply_cost'       => 5,
        'moral_per_lv'      => 1,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'credits'           => 1_000,
    ],

    'languages' => [
        'id'                => 34,
        'supply_cost'       => 5,
        'moral_per_lv'      => 1,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_000,
    ],

    'mathematics' => [
        'id'                => 39,
        'supply_cost'       => 5,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_000,
    ],

    'medicalScience' => [
        'id'                => 72,
        'supply_cost'       => 5,
        'moral_per_lv'      => 2,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_100,
    ],

    'physics' => [
        'id'                => 73,
        'supply_cost'       => 5,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_000,
    ],

    'chemistry' => [
        'id'                => 74,
        'supply_cost'       => 5,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_000,
    ],

    // ── Economy / Diplomacy researches ────────────────────────────────────────

    'economicScience' => [
        'id'                => 76,
        'supply_cost'       => 5,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_200,
    ],

    'diplomacy' => [
        'id'                => 79,
        'supply_cost'       => 5,
        'moral_per_lv'      => 1,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_200,
    ],

    'politicalScience' => [
        'id'                => 80,
        'supply_cost'       => 5,
        'moral_per_lv'      => 1,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'credits'           => 1_200,
    ],

    // ── Military research ─────────────────────────────────────────────────────

    'military' => [
        'id'                => 81,
        'supply_cost'       => 8,
        'moral_per_lv'      => -2,      // see GDD §13
        'decay_rate'        => 1.43,    // 14 days — doctrine degrades faster without reinforcement
        'max_status_points' => 20,
        'credits'           => 2_000,
    ],

];
