<?php

/**
 * Knowledge (Kenntnisse) definitions — canonical source of truth for all per-knowledge mechanics.
 *
 * 7 practical fields of colonial expertise. Not academic science — hands-on colony knowledge.
 *
 * Fields:
 *   id               — DB primary key in `researches` table
 *   trust_per_lv     — trust change per knowledge level (used by TrustService)
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
    // Cumulative: Lv0→1 = 12 AP, Lv0→5 = 152 AP total (raised from 101, playtest
    // review 2026-07-14 — Lv1 at 5 AP completed inside a single Sol, which read
    // as "too cheap"/instant; the whole point of Kenntnisse is a multi-Sol
    // investment even at Lv1). Richtwert: Junior-Analytiker (10 AP/Sol, base 6 +
    // Rang-1-Bonus 4) braucht ~2 Sole für Lv1, ~11-13 Sole für Lv5 inklusive der
    // eigenen Rangaufstiege (Rang 2 bei 10 aktiven Ticks, Rang 3 bei 20).
    // ⚠️ Diese Kurve ist an game.ap.base / advisor.ap_per_rank gekoppelt — bei
    // Änderung dort gegen die AP/Sol-Rate neu prüfen, nicht isoliert betrachten.

    'construction' => [
        'id' => 90,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

    'cartography' => [
        'id' => 91,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

    'geology' => [
        'id' => 92,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

    'agronomy' => [
        'id' => 93,
        'trust_per_lv' => 1,       // see GDD §13
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

    'health' => [
        'id' => 94,
        'trust_per_lv' => 2,       // see GDD §13
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

    'trade' => [
        'id' => 95,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

    'defense' => [
        'id' => 96,
        'trust_per_lv' => -1,      // see GDD §13 — vigilance dampens trust slightly
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 100,
        'levelup_costs' => [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50],
    ],

];
