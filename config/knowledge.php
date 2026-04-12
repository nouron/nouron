<?php

/**
 * Knowledge (Kenntnisse) definitions — canonical source of truth for all per-knowledge mechanics.
 *
 * 7 practical fields of colonial expertise. Not academic science — hands-on colony knowledge.
 *
 * Fields:
 *   id               — DB primary key in `researches` table
 *   moral_per_lv     — moral change per knowledge level (used by MoralService)
 *   decay_rate       — status_points lost per tick (stored in DB, used by GameTick decay)
 *   max_status_points — status_points reset value after level-down
 *   credits          — base cost in credits to invest one level
 *
 * Supply-Cap bonus per level is NOT per-entity — it is the same for all knowledge types.
 * See: config/game.php → supply.knowledge_cap_per_level (+3/+5/+5/+4/+3 = 20 max per knowledge)
 *
 * Note: decay_rate and max_status_points are also stored in the `researches` DB table.
 * After changing values here run: php artisan game:sync-knowledge
 *
 * Localization: lang/de/knowledge.php, lang/en/knowledge.php
 */
return [

    'construction' => [
        'id'                => 90,      // new — DB record to be created in migration
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.13,    // ~150 days — knowledge fades slowly
        'max_status_points' => 20,
        'credits'           => 100,
    ],

    'cartography' => [
        'id'                => 91,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.13,
        'max_status_points' => 20,
        'credits'           => 100,
    ],

    'geology' => [
        'id'                => 92,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.13,
        'max_status_points' => 20,
        'credits'           => 100,
    ],

    'agronomy' => [
        'id'                => 93,
        'moral_per_lv'      => 1,       // see GDD §13
        'decay_rate'        => 0.13,
        'max_status_points' => 20,
        'credits'           => 100,
    ],

    'health' => [
        'id'                => 94,
        'moral_per_lv'      => 2,       // see GDD §13
        'decay_rate'        => 0.13,
        'max_status_points' => 20,
        'credits'           => 100,
    ],

    'trade' => [
        'id'                => 95,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.13,
        'max_status_points' => 20,
        'credits'           => 100,
    ],

    'defense' => [
        'id'                => 96,
        'moral_per_lv'      => -1,      // see GDD §13 — vigilance dampens morale slightly
        'decay_rate'        => 0.13,
        'max_status_points' => 20,
        'credits'           => 100,
    ],

];
