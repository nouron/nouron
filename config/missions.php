<?php

/**
 * Mission catalog — canonical source of truth for hangar dispatch missions (GDD §8b).
 *
 * sol_distance is one-way; total duration = 2 × sol_distance ticks
 * (return_tick = dispatch_tick + 2 × sol_distance, computed — not stored).
 *
 * Dispatch costs (both gate the start):
 *   nav AP   = sol_distance × nav_ap_per_sol
 *   organika = sol_distance × max(organika_floor_per_sol,
 *              organika_per_sol − organika_scaling_per_level × levels_above_gate)
 *              + extra_cost (knowledge scaling only applies to knowledge-gated missions)
 *
 * requires:
 *   knowledge — ['config_key' => min_level] (config/knowledge.php keys)
 *   target    — 'signal_tile' | 'ruin_tile' (player picks a concrete tile at dispatch)
 * target_type — what the player must select in the dispatch dialog
 *   (deep_survey: the signal tile to scan; data_sweep: the knowledge to invest in)
 *
 * reward value forms:
 *   int              — fixed amount
 *   [min, max]       — deterministic roll from run rng_seed + mission id
 *   loot_table       — seeded pick of one entry, then roll
 *
 * Localization: lang/de/missions.php, lang/en/missions.php
 */
return [

    'nav_ap_per_sol' => 2,    // dispatch Nav-AP = sol_distance × this
    'organika_per_sol' => 3,  // dispatch provisions = sol_distance × this (before knowledge scaling)
    'organika_scaling_per_level' => 1, // −1 organika per sol per knowledge level above the gate
    'organika_floor_per_sol' => 1,    // scaling never drops below this
    'dispatch_min_sp_pct' => 0.25,    // ships below 25% of max SP cannot be dispatched (GDD §7)

    'catalog' => [

        // ── Drone — information ──────────────────────────────────────────────

        'mission_courier_run' => [
            'ships' => ['drone'],
            'sol_distance' => 1,
            'requires' => [],
            'reward' => ['credits' => 60],
            'repeatable' => true,
        ],
        'mission_recon_flight' => [
            'ships' => ['drone'],
            'sol_distance' => 1,
            'requires' => [],
            'reward' => ['reveal_tiles' => 2],
            'repeatable' => true,
        ],
        'mission_deep_survey' => [
            'ships' => ['drone'],
            'sol_distance' => 2,
            'requires' => ['target' => 'signal_tile'],
            'target_type' => 'signal_tile',
            'reward' => ['deep_scan' => 1],
            'repeatable' => true, // consumes one signal tile per run
        ],
        'mission_prospecting_flight' => [
            'ships' => ['drone'],
            'sol_distance' => 2,
            'requires' => ['knowledge' => ['geology' => 1]],
            'reward' => ['regolith' => [20, 30]],
            'repeatable' => true,
        ],
        'mission_data_sweep' => [
            'ships' => ['drone'],
            'sol_distance' => 3,
            'requires' => ['knowledge' => ['cartography' => 1]],
            'target_type' => 'knowledge',
            'reward' => ['research_ap' => 8], // invested into player-chosen knowledge, capped at levelup threshold
            'repeatable' => true,
        ],
        'mission_long_range_expedition' => [
            'ships' => ['drone'],
            'sol_distance' => 5,
            'requires' => ['knowledge' => ['cartography' => 3]],
            'reward' => ['loot_table' => [
                ['credits' => [250, 400]],
                ['compounds' => [8, 12]],
                ['regolith' => [30, 45]],
            ]],
            'repeatable' => true,
        ],

        // ── Freighter — goods ────────────────────────────────────────────────

        'mission_supply_run' => [
            'ships' => ['freighter'],
            'sol_distance' => 1,
            'requires' => [],
            'reward' => ['regolith' => 25, 'organics' => 10],
            'repeatable' => true,
        ],
        'mission_trade_convoy' => [
            'ships' => ['freighter'],
            'sol_distance' => 3,
            'requires' => ['knowledge' => ['trade' => 1]],
            'reward' => ['credits' => 180, 'trust_event' => 'trade_success'],
            'repeatable' => true,
        ],
        'mission_aid_transport' => [
            'ships' => ['freighter'],
            'sol_distance' => 2,
            'requires' => [], // ungegatet (Stufe 1b) — schließt Pfad-B-Vertrauenslücke, war zuvor an knowledge.health Lv1 gegatet
            'extra_cost' => ['organics' => 10], // aid cargo, on top of provisions
            'reward' => ['credits' => 60, 'trust_event' => 'encounter_won'],
            'repeatable' => true,
        ],

        // ── Freighter or corvette — salvage ──────────────────────────────────

        'mission_salvage_sweep' => [
            'ships' => ['freighter', 'corvette'],
            'sol_distance' => 4,
            'requires' => ['knowledge' => ['construction' => 1]],
            'reward' => ['compounds' => [6, 10]],
            'repeatable' => true,
        ],
        'mission_ruin_expedition' => [
            'ships' => ['freighter', 'corvette'],
            'sol_distance' => 4,
            'requires' => ['target' => 'ruin_tile'],
            'target_type' => 'ruin_tile',
            // almanac_unlock reward follows once §17 (Almanach) is implemented
            'reward' => ['credits' => 150],
            'repeatable' => false, // once per revealed ruin tile
        ],

        // ── Corvette — protection ────────────────────────────────────────────

        'mission_escort_convoy' => [
            'ships' => ['corvette'],
            'sol_distance' => 3,
            'requires' => [],
            'reward' => ['credits' => 200],
            'repeatable' => true,
        ],

        // 'mission_perimeter_patrol' (corvette, defense Lv1, encounter_prep reward)
        // is deferred until the §9 colonist-hazard system exists — see GDD §8b.

    ],
];
