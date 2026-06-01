<?php

/**
 * Nouron game-specific configuration.
 */

return [
    // ── Bypass flags ──────────────────────────────────────────────────────────
    // Granular overrides for testing individual game systems in isolation.
    // Each flag disables exactly one category of check — all default to false.
    // NEVER set any of these to true in production (AppServiceProvider enforces this).
    //
    // .env presets for common test scenarios:
    //   Test AP behaviour:      GAME_BYPASS_AP=false  (all flags false, real checks run)
    //   Test Supply behaviour:  GAME_BYPASS_RESOURCES=true, rest false
    //   Free-click everything:  all three true  (equivalent to old dev_mode=true)
    'bypass' => [
        'ap_checks'      => (bool) env('GAME_BYPASS_AP',        false),
        'resource_costs' => (bool) env('GAME_BYPASS_RESOURCES', false),
        'supply_checks'  => (bool) env('GAME_BYPASS_SUPPLY',    false),
    ],

    // @deprecated — use individual game.bypass.* flags instead.
    // Legacy shortcut: when true, sets all bypass flags to true at boot (see AppServiceProvider).
    // Will be removed in a future release.
    'dev_mode' => (bool) env('GAME_DEV_MODE', false),

    // Tiles unlocked by CC expansion per level (index 0 = CC Lv1, ..., index 4 = CC Lv5).
    // Walk order: ring 1 → ring 2 → ring 3; skip regolith_* and terrain_impassable.
    // Ring 1 (6 tiles) fully unlocked at Lv1 = your immediate base area.
    // Ring 2 expands step by step at Lv2–Lv5. Max = 15 terrain tiles + CC = 16 total.
    'colony_zone_expansion' => [6, 3, 3, 2, 1],

    // IMPORTANT: The tick system assumes the server (and PHP runtime) runs in UTC.
    // AppServiceProvider::boot() enforces date_default_timezone_set('UTC') at startup.
    // Never deploy Nouron with a non-UTC system timezone — tick boundaries will drift.
    'tick' => [
        // How many hours is one tick (currently 1 tick = 1 day)
        'length' => 24,
        // The daily calculation window (server time, hour of day, UTC)
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
        27 => [3 => 10],   // harvester      → Regolith                × 10/level
        41 => [5 => 10],   // bioFacility    → Organika   (Organics)   × 10/level
    ],

    // Supply cap model — supply is not generated per tick, it is a capacity ceiling.
    // Formula: CC-Level × cap_commandcenter + housing_units × cap_housingcomplex + Σ(knowledge_cap_per_level)
    // Per-entity supply_cost values live in config/buildings.php and config/ships.php.
    // Advisors do NOT consume supply — their cost runs through Credits (see GDD §12).
    'supply' => [
        'cap_max'             => 200,   // absolute hard cap across the whole colony
        'cap_commandcenter'   => 10,    // supply cap per CC level (max Lv5 → 50)
        'cap_housingcomplex'  => 8,     // supply cap per housing unit (max 6 units → 48)
        'knowledge_cap_per_level' => [  // non-linear cap bonus per knowledge level (bell curve)
            1 => 3,
            2 => 5,
            3 => 5,
            4 => 4,
            5 => 3,
        ],
    ],

    // Building/ship/research decay: global multipliers applied on top of per-entity decay_rate.
    // Per-entity decay_rate values live in config/buildings.php, config/ships.php, config/techs.php.
    'decay' => [
        'combat_factor'  => 2,    // hangar decay multiplier when fleet was in combat (ships don't decay)
        'overcap_factor' => 2.0,  // decay multiplier when colony is over supply cap
    ],

    // Navigation-AP cost per fleet order type.
    // Advisor rank-up: cumulative active_ticks required per rank (rank => ticks).
    // Configurable so balancing can be adjusted after first playtest (see GDD §8).
    'advisor' => [
        'rank_thresholds'  => [1 => 10, 2 => 20],
        'ap_per_rank'      => [1 => 4, 2 => 7, 3 => 12],
        // One-time Credits cost when advisor is promoted to this rank (keyed by target rank).
        // If user cannot afford it the promotion is deferred until next tick (ROADMAP Phase 3a).
        'promotion_costs'  => [2 => 150, 3 => 400],
        // Slot system: CC level = number of advisor slots (max 5).
        // Formula: min(cc_level, max_slots)
        'max_slots'        => 5,
        // Credits deducted from the owning user each tick per active advisor (GDD §12).
        // Processed in GameTick after passive Credits income to prevent false-negative
        // deficits when income and upkeep fire in the same tick.
        'upkeep'           => [1 => 10, 2 => 50, 3 => 160],
    ],

    // Passive Credits income per tick (GDD §3).
    // Applied in GameTick step 8b (generatePassiveCredits), after resource generation.
    'credits' => [
        // Flat Cr/Tick subsidy from the Nexus for every colony that has CC > 0.
        'nexus_subsidy'   => 30,
        // Cr/Tick per housing level (sum of all housingComplex instances in the colony).
        'tax_per_housing' => 20,
    ],

    // Bar/Cantina NPC offer generation (GDD §14 Kanal 1).
    // base_prices: Cr per 1 unit of tradeable resource (before variance/discount).
    // price_variance: ±fraction applied to base price (pseudo-random per offer).
    // trader_discount: fraction by which prices drop for the player (keyed by trader rank, 0 = no trader).
    // guest_count: [min, max] guests per tick keyed by trader rank.
    // offer_duration: ticks an offer stays valid (expires_tick = current_tick + offer_duration).
    'bar' => [
        'base_prices'     => [3 => 30, 4 => 60, 5 => 50], // regolith, compounds, organics
        'price_variance'  => 0.20,
        'trader_discount' => [0 => 0.00, 1 => 0.00, 2 => 0.10, 3 => 0.25],
        'guest_count'     => [0 => [0, 1], 1 => [0, 1], 2 => [0, 2], 3 => [1, 2]],
        'offer_duration'  => 2,
    ],

    // Military orders are deliberately more expensive than civilian ones (see GDD §1.1).
    // Rule: military AP cost >= civilian AP cost — never violate this ratio.
    'fleet' => [
        'order_costs' => [
            'move'    => 1,  // civilian — move fleet to coordinates
            'hold'    => 1,  // civilian — hold position for one tick
            'trade'   => 1,  // civilian — execute trade at colony
            'join'    => 1,  // civilian — merge with target fleet
            'convoy'  => 1,  // civilian — escort target fleet to its destination
            'defend'  => 2,  // semi-military — move to target fleet's position to defend
            'attack'  => 3,  // military — attack enemy fleet
        ],
    ],

    // Combat power per ship type (ship_id => power value).
    // Transports have 0 combat power (non-combat).
    'combat' => [
        'ship_power' => [
            85 => 0,   // probe     — unmanned, no weapons
            37 => 3,   // corvette  — combat ship
            47 => 0,   // freighter — transport, no weapons
        ],
    ],

    // Galaxy overview map
    'galaxy_view' => [
        'range'      => 10000,
        'offset'     => 0,
        'scale'      => 0.05,
        'systemSize' => 3,
    ],

    // System detail map
    'system_view' => [
        'range'      => 100,
        'offset'     => 100,
        'scale'      => 10,
        'planetSize' => 10,
        'slotSize'   => 10,
    ],

    // Trade marketplace — AP costs for Händler (economy AP).
    // Creating an offer costs max(1, floor(amount × price / threshold)) AP.
    // Accepting an offer costs 1 AP (paid by the acceptor/buyer).
    // Removing an offer costs 0 AP.
    'trade' => [
        'ap_cost_threshold' => 1000,  // divisor: amount × price / threshold = AP cost
    ],

    // Moral system — formula and multiplier bands (see GDD §13).
    // Formula: clamp(Σbuildings + Σresearches + clamp(Σships, -30, +30) + events, -100, +100)
    // Per-entity moral_per_lv / moral_per_unit values live in config/buildings.php,
    // config/techs.php and config/ships.php — MoralService reads from those files.
    'moral' => [
        // resource_id in colony_resources where the moral value is stored (res_moral).
        'resource_id' => 12,
        // Hard cap for total ship moral contribution (before global clamp).
        'ships_cap' => 30,
        // Production multipliers by moral band (see GDD §13 "Effekte der Moral").
        'production_multiplier' => [
            ['min' =>  61, 'max' => 100, 'factor' => 1.20],
            ['min' =>  21, 'max' =>  60, 'factor' => 1.10],
            ['min' => -20, 'max' =>  20, 'factor' => 1.00],
            ['min' => -60, 'max' => -21, 'factor' => 0.85],
            ['min' => -100,'max' => -61, 'factor' => 0.70],
        ],
        // AP multipliers by moral band.
        'ap_multiplier' => [
            ['min' =>  61, 'max' => 100, 'factor' => 1.10],
            ['min' =>  21, 'max' =>  60, 'factor' => 1.05],
            ['min' => -20, 'max' =>  20, 'factor' => 1.00],
            ['min' => -60, 'max' => -21, 'factor' => 0.90],
            ['min' => -100,'max' => -61, 'factor' => 0.80],
        ],
        // Event moral effects (one-shot, active for exactly 1 tick).
        // Multiple events of the same key in one tick do NOT stack — strongest wins.
        'events' => [
            'building_level_up'     =>  1,
            'building_level_down'   => -3,
            'research_level_up'     =>  2,
            'trade_success'         =>  2,
            'trade_blocked'         => -3,
            'encounter_won'         =>  2,
            'encounter_lost'        => -5,
            'colony_threatened'     => -4,
            'treaty_signed'         =>  3,
        ],
    ],

    // CC-Level gate for knowledge research levels 4 and 5.
    // A colony must have CommandCenter (ID 25) at this level before a Kenntnis
    // can be levelled to the corresponding level.
    // Enforcement logic (invest/levelup guard) is not yet implemented — this
    // entry documents the design rule and will be read by the service in a
    // future sprint.
    //
    // Format: knowledge_level => required_cc_level
    'knowledge_cc_level_cap' => [
        4 => 4,  // CC Lv4 required to reach knowledge Lv4
        5 => 5,  // CC Lv5 required to reach knowledge Lv5
    ],

    // Run structure — one run = one expedition with a defined start, goal and end (GDD §15).
    'run' => [
        'allow_multiple'       => (bool) env('GAME_ALLOW_MULTIPLE_RUNS', false),
        'tick_limit'           => 100,    // total ticks per run (60–100, default 100)
        'trust_fail_threshold' => -20,    // instant fail when trust drops below this value
        'task_pool'            => [       // all available Phase-2 task keys
            'task_senior_advisors',
            'task_credit_reserve',
            'task_colony_prosperity',
            'task_research_lead',
            'task_self_sufficiency',
            'task_expedition_coverage',
            'task_engineering_output',
            'task_trade_volume',
            'task_combat_record',
        ],
        'tick_duration_hours'  => 24,     // max real time per tick in hours (solo: irrelevant; multiplayer: timeout)
        'max_players'          => 1,      // 1 = singleplayer; 2–4 = multiplayer
        'playbymailmode'       => false,  // true: tick fires when all players confirm, at most after tick_duration_hours

        // Nexus intervention milestones (tick numbers, GDD §15 "Nexus-Eingriffe").
        'nexus_milestones' => [
            30  => 'warn_progress',   // at tick 30: at least 1 task must be >50% done, else INNN warning
            50  => 'warn_none_done',  // at tick 50: if 0 tasks fully done, second INNN warning
            85  => 'sanction',        // at tick 85: if 0 tasks done → advisor penalty + deadline shortened to 95
            90  => 'final_warning',   // at tick 90: last warning if still 0 tasks done
        ],

        // Score formula weights (GDD §15 "Highscore").
        // score = (tasks_done × w_task) + (tick_limit - done_at_tick) × w_tick + (credits_remaining / w_credits) + (trust_at_end × w_trust)
        'score_weights' => [
            'task_completed'    => 1000,  // per completed objective
            'ticks_saved'       =>   10,  // per tick below tick_limit when last objective was met
            'credits_divisor'   =>   10,  // remaining credits divided by this value
            'trust_multiplier'  =>    5,  // trust value at run end × this value
        ],
    ],

    // Traveling Merchant (Reisender Händler) — random system event, separate from Bar/Cantina.
    // The merchant appears once from Sol first_appearance_min–max, then every interval_min–max Sols.
    // Each visit lasts duration_ticks Sols and offers items_count items for Credits.
    'merchant' => [
        'first_appearance_min' => 15,   // earliest Sol the merchant can first appear
        'first_appearance_max' => 20,   // latest Sol for the first appearance
        'interval_min'         => 10,   // minimum Sols between visits
        'interval_max'         => 15,   // maximum Sols between visits
        'duration_ticks'       => 2,    // how many Sols the merchant stays (inclusive)
        'items_count'          => 3,    // items offered per visit (3 default, up to 4)
        'items' => [
            'ap_flex'     => ['label' => 'AP-Paket (flexibel)',       'cost' => 800,  'ap_amount' => 20],
            'ap_targeted' => ['label' => 'AP-Paket (Kenntnis)',       'cost' => 500,  'ap_amount' => 15],
            'information' => ['label' => 'Systemkarte vollständig',   'cost' => 1200],
            'repair_kit'  => ['label' => 'Reparatur-Kit (+30 SP)',    'cost' => 400,  'sp_amount' => 30],
            'trust_boost' => ['label' => 'Vertrauensschub (+15)',     'cost' => 600,  'trust_amount' => 15],
        ],
    ],

    'onboarding' => [
        // Supply threshold below which Rank-1 hint fires (no housing built yet)
        'hint_supply_cap_threshold' => 10,

        // Ticks elapsed without any engineer assigned before Rank-2 hint fires
        'hint_no_engineer_ticks' => 3,

        // Ticks elapsed without any knowledge researched before Rank-4 hint fires
        'hint_no_knowledge_after_tick' => 10,

        // Trust value below which Rank-5 hint fires
        'hint_trust_threshold' => -20,

        // Minimum ticks elapsed before trust hint can fire (avoids day-1 trigger)
        'hint_trust_min_ticks' => 5,

        // Minimum ticks elapsed before Cantina hint fires (CC>=2 + Housing>=1 + Bar missing)
        'hint_no_cantina_after_tick' => 5,
    ],
];
