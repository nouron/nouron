<?php

/**
 * Knowledge (Kenntnisse) definitions — canonical source of truth for all per-knowledge mechanics.
 *
 * 7 practical fields of colonial expertise. Not academic science — hands-on colony knowledge.
 *
 * Fields:
 *   id               — DB primary key in `researches` table
 *   moral_per_lv     — moral change per knowledge level (used by MoralService)
 *   decay_rate       — always 0; knowledge never decays (GDD §10). GameTick skips Kenntnisse in decay loop.
 *   max_status_points — status_points reset value (kept for compatibility with colony_researches schema)
 *   credits          — base cost in credits to invest one level
 *   levelup_costs    — AP required per level-up step (index = target level, 1-based).
 *                      ResearchService reads this instead of the DB ap_for_levelup field.
 *                      Costs rise non-linearly to create meaningful mid-/late-run tradeoffs.
 *
 * Supply-Cap bonus per level is NOT per-entity — it is the same for all knowledge types.
 * See: config/game.php → supply.knowledge_cap_per_level (+3/+5/+5/+4/+3 = 20 max per knowledge)
 *
 * Localization: lang/de/knowledge.php, lang/en/knowledge.php
 */
return [

    // levelup_costs: AP needed to reach that level (index = target level, 1–5).
    // Cumulative: Lv0→1 = 5 AP, Lv0→5 = 101 AP total.
    // Richtwert: Rang-1-Wissenschaftler (4 AP/Tick) braucht ~2 Ticks für Lv1, ~25 Ticks für Lv5.

    'construction' => [
        'id'                => 90,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

    'cartography' => [
        'id'                => 91,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

    'geology' => [
        'id'                => 92,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

    'agronomy' => [
        'id'                => 93,
        'moral_per_lv'      => 1,       // see GDD §13
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

    'health' => [
        'id'                => 94,
        'moral_per_lv'      => 2,       // see GDD §13
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

    'trade' => [
        'id'                => 95,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

    'defense' => [
        'id'                => 96,
        'moral_per_lv'      => -1,      // see GDD §13 — vigilance dampens morale slightly
        'decay_rate'        => 0,
        'max_status_points' => 20,
        'credits'           => 100,
        'levelup_costs'     => [1 => 5, 2 => 10, 3 => 18, 4 => 28, 5 => 40],
    ],

];
