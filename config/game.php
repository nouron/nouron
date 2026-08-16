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
        'ap_checks' => (bool) env('GAME_BYPASS_AP', false),
        'resource_costs' => (bool) env('GAME_BYPASS_RESOURCES', false),
        'supply_checks' => (bool) env('GAME_BYPASS_SUPPLY', false),
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

    // Navigation-AP cost to explore a fogged tile, keyed by ring distance from the
    // CC (ring 0). Staggers the fog-of-war reveal pace: ring 2 costs more than ring 1,
    // ring 3 more than ring 2 — keeps the full map from being uncovered in a handful
    // of Sols. Ring 1 stays cheap (already auto-explored at seed time anyway).
    'colony' => [
        'explore_cost_per_ring' => [1 => 1, 2 => 2, 3 => 3],
        'explore_cost_default' => 1,
    ],

    // IMPORTANT: The tick system assumes the server (and PHP runtime) runs in UTC.
    // AppServiceProvider::boot() enforces date_default_timezone_set('UTC') at startup.
    // Never deploy Nouron with a non-UTC system timezone — tick boundaries will drift.
    'tick' => [
        // How many hours is one tick (currently 1 tick = 1 day)
        'length' => 24,
        // The daily calculation window (server time, hour of day, UTC)
        'calculation' => [
            'start' => 3,
            'end' => 4,
        ],
        // Fixed tick number used in test cases
        'testcase' => 14479,
    ],

    // Resource production per tick: building_id => [resource_id => [level => yield_at_that_level]]
    // Yield per level is NOT cumulative-per-level-multiplied — it's the marginal amount
    // added AT that level. Total yield = sum of curve[1..currentLevel]. Bell-shaped
    // (rises, peaks, falls) but never reaches 0 — every level is worth taking, none is
    // a pure grind. Capped at building max_level (config/buildings.php) — growth beyond
    // the cap comes only from Kenntnisse/Missionen/Handel (GDD §18 balance ticket,
    // 2026-07-20). Harvester peaks broad/mid-run (Regolith needed in bursts throughout —
    // CC upgrades, path buildings, repairs); bioFacility peaks early (Organika/food
    // security must stand up fast, before the hunger→trust spiral bites).
    'production_curve' => [
        27 => [3 => [1 => 8, 2 => 10, 3 => 12, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4]],   // harvester → Regolith (inert since §4c depletion wiring — see game.harvester below)
        41 => [5 => [1 => 8, 2 => 12, 3 => 12, 4 => 9, 5 => 7, 6 => 5, 7 => 3, 8 => 2]],     // bioFacility → Organika
    ],

    // Lower bound for additive project-AP discounts (§13.3) — prevents bonuses from
    // pushing ap_for_levelup to 0. Not binding at the current max discount (45%,
    // construction+cartography+trade fully invested); a guard rail for future bonus
    // sources (advisor rank, colony maturity) that are not yet implemented.
    'project_min_cost_factor' => 0.5,

    // Harvester depletion mechanic (GDD §4c "Erschöpfungskurve und Umzugstakt",
    // freigegeben 2026-08-03). Replaces production_curve[27] as the actual Regolith
    // source — the curve above stays as inert historical data (GDD §13.7).
    //
    //   Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max)
    //
    // Never drops below half of fresh_yield; at resource_amount <= 0, yield is 0
    // (relocation is player-triggered, not automatic). ColonyTileService reads the
    // same resource_max map so tile seeding and production can't drift apart.
    'harvester' => [
        'fresh_yield' => [
            'regolith_rich' => 24,
            'regolith_normal' => 18,
            'regolith_poor' => 12,
        ],
        'resource_max' => [
            'regolith_rich' => 500,
            'regolith_normal' => 300,
            'regolith_poor' => 160,
        ],
        // Verlegekosten 1 → 2 AP je Hex (GDD §4c, 2026-08-03) — the relocation-frequency
        // lever, not the depletion curve itself (see GDD §4c "Der eigentliche Regler...").
        'relocate_ap_per_hex' => 2,
        // Second harvester instance gate (GDD §4c "Harvester-Zweitinstanz: Bezugsquelle",
        // freigegeben 2026-08-05): instance 1 keeps the Regolith-free bootstrap exemption.
        // Instance 2 keeps the CommandCenter-level gate, but the old flat Regolith cost is
        // gone — the second instance is no longer a deterministic buy. It requires an
        // entitlement earned through one of two opportunistic, not-guaranteed paths:
        // Orin (`corporate_contact`, 400-800 Cr, see below) or the `mission_harvester_salvage`
        // reward. See ColonyController::placeBuilding — 'harvester_second_instance_unlocked_*'
        // onboarding-trigger keys carry the entitlement (no dedicated table needed).
        'second_instance_cc_level' => 3,

        // Bergungsmission (Weg B): the salvaged instance arrives damaged, not fully
        // productive. Deliberately mirrors missions.dispatch_min_sp_pct (0.25) — same
        // "barely operational" threshold, not a coincidence, not re-derived. Applied
        // against buildings.max_status_points at placement time. Full-health placement
        // (Weg A / Orin) does not use this value.
        'salvage_arrival_sp_pct' => 0.25,
    ],

    // Orin (`corporate_rep`, config('characters').corporate_contact) — Weg A for the
    // Harvester second instance (GDD §4c, freigegeben 2026-08-05). A dedicated, small
    // spawn-check modelled after MerchantService's shouldSpawn() pattern, but with its
    // own config namespace and its own service (CorporateContactService) — NOT part of
    // `game.merchant` / MerchantService, and NOT part of the generic BarService guest
    // rotation. Stateless by design: no visits table, the offer is a pure function of
    // (colony, tick) re-derived on every read and on purchase, so the display path and
    // the buy path can't drift apart from each other.
    //
    // Two-level roll: level 1 = does Orin appear at all this Sol (rare — GDD character
    // sheet calls his Cantina frequency "rare"); level 2 = does he bring the harvester
    // deal, conditional on level 1. Both intervals/chances are explicit playtest
    // candidates per GDD, not tuned further here.
    'corporate_contact' => [
        'appearance_interval_min' => 15,   // Sole between level-1 appearances (playtest candidate)
        'appearance_interval_max' => 25,
        'offer_chance' => 0.30,            // level-2: chance the appearance carries the harvester deal (GDD range 25-35%)
        'price_min' => 400,                // Cr (GDD-confirmed range, 2026-08-05)
        'price_max' => 800,
        // GDD §4c mentions a "2-Sol-Fenster" the player must have credits ready in.
        // This implementation rolls per-tick (duration_ticks=1, not 2) — the stateless
        // getActiveOffer(colonyId, tick) design (no visits table) makes a wider window
        // possible via intdiv($tick, duration_ticks), but that wasn't built here to keep
        // the roll formula simple and directly testable. Knowingly narrower than the
        // GDD's hit-rate math assumes — flag before relying on the ~40-60% figure.
        'duration_ticks' => 1,
    ],

    // Geology (config/knowledge.php id 92) production bonus — originally the first of
    // at most two hardcoded Kenntnis-Effekte before a generic effect framework became
    // mandatory; superseded by the deliberate decision (2026-08-15, design/knowledge-
    // effects-and-encounters spec) to add four more ad-hoc per-effect config keys
    // instead. Additive Regolith bonus per Harvester-Sol, cumulative across levels,
    // capped at level 5 (+3+3+2+2+2 = 12 max).
    'geology_harvester_bonus_per_level' => [1 => 3, 2 => 3, 3 => 2, 4 => 2, 5 => 2],

    // defense Kenntnis-Bonus: reduces Sturm trigger chance (GDD §9, docs/superpowers/
    // specs/2026-08-15-knowledge-effects-and-encounters-design.md §5). Bell-shaped,
    // Σ20% at Lv5, ~17% at Lv4 (spec's "~15-20% bei Lv4" target).
    'defense_storm_risk_reduction_per_lv' => [1 => 3, 2 => 5, 3 => 5, 4 => 4, 5 => 3],

    // geology Kenntnis: SEPARATE from geology_harvester_bonus_per_level (Regolith
    // production) — this curve reduces Geologische-Instabilität trigger chance (GDD
    // §9), geology's second, independent effect. Same bell shape as defense's curve.
    'geology_instability_risk_reduction_per_lv' => [1 => 3, 2 => 5, 3 => 5, 4 => 4, 5 => 3],

    // agronomy Kenntnis bonus on bioFacility Organika output — parity with geology's
    // Harvester bonus (GDD §13.5 parity requirement). Bell-shaped, NOT front-loaded
    // like geology: this effect is new, with no existing calibration history.
    'agronomy_agrardom_bonus_per_level' => [1 => 1, 2 => 2, 3 => 2, 4 => 1, 5 => 1],   // Σ7 Or/Sol

    // Economy — resource pricing for player-facing buy/sell mechanics.
    'economy' => [
        // Werkstoffe (compounds, resource 4) are not locally producible (GDD §3).
        // The Nexus direct import (gated behind Uplink-Station Lv1) is the guaranteed
        // safety-net source: a fixed Credits price per unit, always available. Set
        // deliberately above the Cantina spot price so the Cantina/merchant stay
        // the cheaper, opportunistic source and the Nexus stays the expensive fallback.
        // 90 → 165 (GDD §13.7, 2026-08-03): holds the ~1.5:1 ratio to the new Werkstoffe
        // spot price (110) required by the Knappheitsordnung (§3) after the bar.base_prices
        // correction below — Werkstoffe stay the scarcest good, Regolith < Organika < Werkstoffe.
        'compound_import_price' => 165,
    ],

    // Kolonisten-Zulage (GDD §14) — player-triggered Credits→Trust action.
    // Trust deltas live in trust.events.stipend_* below (single source of truth);
    // this block only maps the UI-facing tier key to its Credits cost and event_key.
    'stipend' => [
        'tiers' => [
            'small' => ['cost' => 100, 'event_key' => 'stipend_small'],
            'medium' => ['cost' => 300, 'event_key' => 'stipend_medium'],
            'large' => ['cost' => 600, 'event_key' => 'stipend_large'],
        ],
    ],

    // Manual building repair — Regolith cost per click (1 SP), on top of 1 Construction-AP.
    // CommandCenter + Harvester are exempt (AP-only, bootstrap anchor against decay spiral).
    'repair' => [
        // 2 → 1 (GDD §13.7, 2026-08-03): with repair also costing 1 Construction-AP,
        // 1 Rg/click makes Instandhaltung [Rg/Sol] = Instandhaltung [AP/Sol] = Σ decay_rate —
        // one number drives both currencies, no separate "feels expensive" multiplier needed.
        'regolith_per_click' => 1,

        // Damage display threshold (fraction of max_status_points): tiles show the
        // damage badge/status tint only below this. The 16/20 (80%) starting damage
        // is deliberately invisible — it acts as a staggered pacing timer (decay
        // pushes Harvester below 70% ~Sol 4, Housing ~Sol 6, CC ~Sol 8), so repair
        // is taught when it first matters, not on Sol 1 (playtest review 2026-07-14).
        // Exact SP stay visible in the tile sidebar — nothing is hidden, just not
        // pushed. The teaching repair hint uses the same threshold.
        'display_threshold' => 0.70,
    ],

    // Action Points — base value for the single shared colony pool, regardless
    // of advisors. Advisors add their per-rank contribution on top (see
    // 'advisor.ap_per_rank' below). One pool for the whole colony — GDD §13.1
    // ("Ein gemeinsamer AP-Pool", Entscheidung 2026-08-02).
    'ap' => [
        'base' => 12,
    ],

    // Supply cap model — supply is not generated per tick, it is a capacity ceiling.
    // Formula: CC-Level × cap_commandcenter + housing_units × cap_housingcomplex + Σ(knowledge_cap_per_level)
    // Per-entity supply_cost values live in config/buildings.php and config/ships.php.
    // Advisors do NOT consume supply — their cost runs through Credits (see GDD §12).
    'supply' => [
        'cap_max' => 200,   // absolute hard cap across the whole colony
        'cap_commandcenter' => 10,    // supply cap per CC level (max Lv5 → 50)
        'cap_housingcomplex' => 8,     // supply cap per housing unit (max 6 units → 48)
        'knowledge_cap_per_level' => [  // non-linear cap bonus per knowledge level (bell curve)
            1 => 3,
            2 => 5,
            3 => 5,
            4 => 4,
            5 => 3,
        ],
    ],

    // Hangar — Nexus ship ordering and pending-ship lifecycle.
    'hangar' => [
        // Min CC level required to use Nexus-Kredit (take ship on debt)
        'nexus_credit_min_cc_level' => 2,

        // Trust penalty when using Nexus-Kredit (one-shot event)
        'nexus_credit_trust_penalty' => -5,

        // Ticks before an unassigned (pending) ship decays and is removed
        'pending_decay_ticks' => 5,
    ],

    // Building/ship/research decay: global multipliers applied on top of per-entity decay_rate.
    // Per-entity decay_rate values live in config/buildings.php, config/ships.php, config/techs.php.
    'decay' => [
        'overcap_factor' => 2.0,  // decay multiplier when colony is over supply cap
    ],

    // GDD §9 "Begegnungen & Gefahren" — first-pass calibration figures (Richtwerte),
    // to be tuned after PlaytestBot runs, same convention as other "erste Fassung"
    // numbers in this file.
    'encounter' => [
        // Cooldown: no new danger WARNING for a colony within N Sols of its last
        // RESOLVED encounter (any type) — GDD §9's own flagged spiral-risk guard.
        'cooldown_sols' => 3,

        // Phase 1 ramp (2026-08-16, Owner-Entscheidung): a freshly-landed colony
        // has no mitigation infrastructure (securityHub/geology/defense all hang
        // off the Analytik-Labor, a Phase-2 building) and only ~5-10 Sol slack
        // against the Sol-30 deadline. Trigger chance ramps 0 -> full strength
        // linearly over the first N Sols of Phase 1 instead of applying full
        // strength from Sol 1 — "early is weaker", not "early is absent".
        'phase1_ramp_sols' => 15,

        'storm' => [
            'base_chance' => 0.02,
            'chance_per_building' => 0.01,   // additive per colony_zone building (excl. Harvester)
            'chance_cap' => 0.10,
        ],
        'instability' => [
            'chance_per_sol_since_relocation' => 0.0015,
            'chance_cap' => 0.05,
            'outage_sols' => 3,               // Harvester produces nothing for N Sols on trigger
        ],
        'plague' => [
            'chance_per_sol_when_emergent' => 0.05,   // only rolled when hunger_streak≥3 or trust<-20
            'debuff_sols' => 5,
            'ap_reduction_pct' => 0.20,        // total AP reduced by this fraction while active
        ],

        // Outcome tiers (GDD §9 table) — SP% thresholds and consequences.
        'damaged_threshold_pct' => 0.66,   // ≥66% SP → Abgewehrt; below → Beschädigt tier starts
        'critical_threshold_pct' => 0.33,  // <33% SP → Kritisch
        'damaged_sp_loss_pct' => 0.20,     // fraction of max_status_points lost on Beschädigt
    ],

    // Advisor rank-up: cumulative active_ticks required per rank (rank => ticks).
    // Configurable so balancing can be adjusted after first playtest (see GDD §8).
    'advisor' => [
        // Stretched 2026-07-19 (was [1=>10, 2=>20]) — gives the colony time to build
        // income infrastructure (Uplink Station, Konsul-Handelsvertrag) before upkeep
        // escalates. See GDD §18 task_credit_reserve.
        'rank_thresholds' => [1 => 15, 2 => 45],
        'ap_per_rank' => [1 => 2, 2 => 3, 3 => 4],
        // One-time Credits cost when advisor is promoted to this rank (keyed by target rank).
        // If user cannot afford it the promotion is deferred until next tick (ROADMAP Phase 3a).
        // Rank-3 cost lowered 400 → 250 (GDD §18.4 Nachtrag 2026-08-14) — up to
        // 3 advisors promoting near-simultaneously around Phase-2-Sol 45-60 could
        // stack to 1200 Cr in one-time cost right when the ongoing upkeep jump
        // (see 'upkeep' below) also hits, compounding the Post-Phase-1 collapse.
        'promotion_costs' => [2 => 150, 3 => 250],
        // Slot system: CC level = number of advisor slots (max 4).
        // Formula: min(cc_level, max_slots)
        'max_slots' => 4,
        // Credits deducted from the owning user each tick per active advisor (GDD §12).
        // Processed in GameTick after passive Credits income to prevent false-negative
        // deficits when income and upkeep fire in the same tick.
        // Flattened 2026-07-19 (was [1=>10, 2=>50, 3=>160]) — the old Rang-2 cliff made
        // 3 advisors at rank 2 cost 150 Cr/Tick against ~30-70 Cr/Tick income, a
        // structural collapse no player action could prevent (GDD §18 task_credit_reserve).
        // Flattened again 2026-08-14 (was [1=>10, 2=>30, 3=>80]) — the 07-19 fix only
        // checked the Rang-2 case; the Rang-2→3 jump stayed at 2.67× and was the actual,
        // *permanent* Post-Phase-1 collapse trigger (advisors never demote). See GDD §18.4
        // Nachtrag 2026-08-14 for the full break-even derivation.
        'upkeep' => [1 => 10, 2 => 25, 3 => 50],
    ],

    // Passive Credits income per tick (GDD §3).
    // Applied in GameTick step 8b (generatePassiveCredits), after resource generation.
    'credits' => [
        // Flat Cr/Tick subsidy from the Nexus for every colony that has CC > 0.
        // 30 → 50 (GDD §18.4 Nachtrag 2026-08-14) — this is the only income source
        // with no building/advisor prerequisite at all, so it has to carry the
        // absolute floor regardless of path choice (Sciencelab/Hangar/Cantina).
        'nexus_subsidy' => 50,
        // "Relaisvergütung" — Cr/Tick per Uplink Station level, paid by the Nexus for
        // the relay/sensor infrastructure the station hosts on its network (GDD §3).
        // Not housing-based: colonists' living quarters have no thematic connection
        // to Nexus relay capacity, and Uplink Station (CC Lv2+, single instance) is
        // the building that already gates every other Nexus-facing mechanic
        // (deep-scan cost, direct import, merchant frequency).
        // 20 → 35 (GDD §18.4 Nachtrag 2026-08-14) — Uplink Station doesn't conflict
        // with the Sciencelab/Hangar/Cantina path choice (separate CC-Lv2 gate), so
        // it's the deliberate active-effort lever for players who skip the Cantina.
        'relay_bonus_per_uplink_level' => 35,
        // "Handelsvertrag" — Cr/Tick flat income while a Konsul (trader advisor,
        // personell_id 92) is assigned to the colony AND the Cantina (Bar, building_id
        // 52) is built (level >= 1). Keyed by the Konsul's current rank. Represents the
        // Konsul actively brokering trade deals through the Cantina (GDD §12 Kanal 1).
        // 0 with no Konsul assigned — an intended cost of skipping that advisor type,
        // not a bug. Added 2026-07-19 as part of the Phase-1 credit-collapse fix.
        'consul_contract_income_per_rank' => [1 => 10, 2 => 25, 3 => 45],
    ],

    // Bar/Cantina NPC offer generation (GDD §12 Kanal 1).
    // base_prices: Cr per 1 unit of tradeable resource (before variance/discount).
    //   Also read by MerchantService for Corvan's buy offers (game.merchant.commodity).
    // price_variance: ±fraction applied to base price (pseudo-random per offer).
    // trader_discount: Rank 0 = no trader. Rank 1 gives 10% — Junior must have visible value.
    //   Also read by MerchantService for Corvan's prices (Konsul pflegt die Kontakte).
    // guest_count: [min, max] NPC guests per tick keyed by trader rank. Guests only
    //   ever barter (resource↔resource) — Credits-Handel moved entirely to Corvan
    //   (GDD §12 Kanal 1 "Corvan wird die zentrale Handelsfigur der Cantina",
    //   Freigegeben 2026-08-05).
    // ap_cost_accept: Economy-AP consumed when the player accepts any bar offer (shown as chip on button).
    // level_offer_duration: how many ticks an offer stays valid, keyed by bar building level.
    // level_max_concurrent: max simultaneous active *guest* offers per colony, keyed by
    //   bar level — Corvan's commodity offers (bar_offers.visit_id set) have their own
    //   budget (game.merchant.commodity) and are excluded from this cap.
    'bar' => [
        // Rg 30→25 / Or 50 (unverändert) / Wk 60→110 (GDD §13.7 "Korrektur durch die
        // Knappheitsordnung", 2026-08-03): respektiert §3 Regolith < Organika < Werkstoffe —
        // der Abstand Organika↔Werkstoffe war zu klein, um "deutlich knapper" zu zeigen.
        'base_prices' => [3 => 25, 4 => 110, 5 => 50], // regolith, compounds, organics
        'price_variance' => 0.20,
        'trader_discount' => [0 => 0.00, 1 => 0.10, 2 => 0.20, 3 => 0.30],
        'guest_count' => [0 => [0, 1], 1 => [0, 1], 2 => [0, 2], 3 => [1, 2]],
        'offer_duration' => 2,  // fallback when bar level unknown
        'ap_cost_accept' => 1,
        'level_offer_duration' => [1 => 2, 2 => 3, 3 => 3, 4 => 3, 5 => 4],
        'level_max_concurrent' => [1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6],

        // Cantina-Verhandlung (Risiko-Handel, GDD §12 Kanal 1) — Konsul (advisor_trader)
        // muss zugewiesen und verfügbar sein (kein Rang-Minimum über Rang 1 hinaus).
        // AP ist bewusst NICHT der eigentliche Deckel (siehe GDD): der Preis ist der
        // komplette Verlust des Angebots bei einem fehlgeschlagenen Wurf.
        'ap_cost_negotiate' => 3,
        'negotiate_success_chance' => [0 => 0.0, 1 => 0.55, 2 => 0.70, 3 => 0.85],
        'negotiate_bonus' => [0 => 0.0, 1 => 0.10, 2 => 0.15, 3 => 0.20],
    ],

    // Trust system — formula and multiplier bands (see GDD §13).
    // Formula: clamp(Σbuildings + Σresearches + clamp(Σships, -30, +30) + events, -100, +100)
    // Per-entity trust_per_lv / trust_per_unit values live in config/buildings.php,
    // config/techs.php and config/ships.php — TrustService reads from those files.
    'trust' => [
        // resource_id in colony_resources where the trust value is stored (res_trust).
        'resource_id' => 12,
        // Hard cap for total ship trust contribution (before global clamp).
        'ships_cap' => 30,
        // Production multipliers by trust band (see GDD §14 "Effekte des Vertrauens").
        'production_multiplier' => [
            ['min' => 61, 'max' => 100, 'factor' => 1.20],
            ['min' => 21, 'max' => 60, 'factor' => 1.10],
            ['min' => -20, 'max' => 20, 'factor' => 1.00],
            ['min' => -60, 'max' => -21, 'factor' => 0.85],
            ['min' => -100, 'max' => -61, 'factor' => 0.70],
        ],
        // AP multipliers by trust band.
        'ap_multiplier' => [
            ['min' => 61, 'max' => 100, 'factor' => 1.10],
            ['min' => 21, 'max' => 60, 'factor' => 1.05],
            ['min' => -20, 'max' => 20, 'factor' => 1.00],
            ['min' => -60, 'max' => -21, 'factor' => 0.90],
            ['min' => -100, 'max' => -61, 'factor' => 0.80],
        ],
        // Event trust effects (one-shot, active for exactly 1 tick).
        // Multiple events of the same key in one tick do NOT stack — strongest wins.
        'events' => [
            'building_level_up' => 1,
            'building_level_down' => -3,
            'research_level_up' => 2,
            'trade_success' => 2,
            'trade_blocked' => -3,
            'treaty_signed' => 3,
            'nexus_credit' => -5,  // trust penalty when ship is acquired on Nexus-Kredit
            'well_fed' => 1,       // colony's Organika stock covered the food need this Sol
            'encounter_won' => 2,  // successful protective/aid action (e.g. mission_aid_transport)
            'encounter_lost' => -4,        // damaged outcome (GDD §9, 33-65% SP)
            'colony_threatened' => -5,     // critical outcome (GDD §9, <33% SP)
            'stipend_small' => 2,  // Kolonisten-Zulage (GDD §14) — see stipend.tiers above
            'stipend_medium' => 3,
            'stipend_large' => 4,
        ],
    ],

    // Organika provisioning — the colony eats Organika (resource 5) each Sol.
    // Consumption = floor(used_supply / supply_per_eater). Stock covers it → well_fed
    // (+trust); stock short → hunger_streak grows and an escalating trust penalty bites
    // (see TrustService::hungerPenalty), making bioFacility a must-have. Missions also
    // burn Organika as crew provisions at dispatch.
    'food' => [
        'supply_per_eater' => 4,     // 1 "eater" per 4 used supply → food_need = floor(used/4)
        'well_fed_trust' => 1,       // (documented; actual bonus via trust.events.well_fed)
        'hunger_base_malus' => 2,    // trust penalty on the first hungry Sol
        'hunger_step' => 1,          // +1 penalty per consecutive hungry Sol
        'hunger_cap' => 8,           // max penalty
        // mission dispatch costs moved to config/missions.php (nav_ap_per_sol, organika_per_sol)
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
        'allow_multiple' => (bool) env('GAME_ALLOW_MULTIPLE_RUNS', false),
        'tick_limit' => 100,    // total ticks per run (60–100, default 100)
        'trust_fail_threshold' => -20,    // instant fail when trust drops below this value
        'nexus_debt_fail_threshold' => 12000,  // instant fail when nexus_debt exceeds this value
        'phase1_deadline_sol' => 30,    // hard fail if Phase 1 isn't complete by this Sol (checkFailStates)
        'phase1_warning_sol' => 22,     // escalating Nexus warning if Phase 1 still incomplete by this Sol
        // Invariant: phase1_warning_sol must stay below phase1_deadline_sol, or the warning
        // and the hard fail would land on the same tick.
        // task_credit_reserve: Credits threshold a colony must hold for the
        // objective's streak (RunProgressService::TASK_TARGETS, 10 consecutive
        // sols, unchanged). 5000 → 3000 (GDD §18.4 Nachtrag 2026-08-14) — 5000 was
        // effectively unreachable under the pre-fix Post-Phase-1 Credit-Ökonomie
        // collapse; kept as a real, non-trivial savings goal (not just a side
        // effect of baseline income) after the fix.
        'task_credit_reserve_threshold' => 3000,
        'task_pool' => [       // all available Phase-2 task keys
            'task_senior_advisors',
            'task_credit_reserve',
            'task_colony_prosperity',
            'task_research_lead',
            'task_self_sufficiency',
            'task_expedition_coverage',
            'task_engineering_output',
            'task_trade_volume',
        ],
        'tick_duration_hours' => 24,     // max real time per tick in hours (solo: irrelevant; multiplayer: timeout)
        'max_players' => 1,      // 1 = singleplayer; 2–4 = multiplayer
        'playbymailmode' => false,  // true: tick fires when all players confirm, at most after tick_duration_hours

        // Nexus intervention milestones (tick numbers, GDD §15 "Nexus-Eingriffe").
        'nexus_milestones' => [
            30 => 'warn_progress',   // at tick 30: at least 1 task must be >50% done, else INNN warning
            50 => 'warn_none_done',  // at tick 50: if 0 tasks fully done, second INNN warning
            85 => 'sanction',        // at tick 85: if 0 tasks done → advisor penalty + deadline shortened to 95
            90 => 'final_warning',   // at tick 90: last warning if still 0 tasks done
        ],

        // Score formula weights (GDD §15 "Highscore").
        // score = (tasks_done × w_task) + (tick_limit - done_at_tick) × w_tick + (credits_remaining / w_credits) + (trust_at_end × w_trust)
        'score_weights' => [
            'task_completed' => 1000,  // per completed objective
            'ticks_saved' => 10,  // per tick below tick_limit when last objective was met
            'credits_divisor' => 10,  // remaining credits divided by this value
            'trust_multiplier' => 5,  // trust value at run end × this value
        ],
    ],

    // Traveling Merchant (Corvan Ashe) — GDD §12 Kanal 1 "Corvan wird die zentrale
    // Handelsfigur der Cantina" (Freigegeben 2026-08-05, Direction 1). Corvan is now
    // the Cantina's ONLY Credits-Handel identity — the anonymous guest rotation
    // (game.bar) lost its credits offer type entirely and barters only.
    //
    // Each Corvan visit carries two independent offer layers:
    //   1. commodity (below) — Alltagsgeschäft: buy (Credits→Regolith/Compounds/
    //      Organics, reusing game.bar.base_prices/trader_discount) + sell
    //      (Organics→Credits, the §4b Pfad-C-Hebel). Persisted as bar_offers rows
    //      with visit_id set — same accept/negotiate/AP pipeline as guest offers.
    //   2. items (below) — the curated special inventory (AP packages, ships,
    //      information, one-off items, exotics), unchanged in content and rarity.
    //
    // The merchant appears once from Sol first_appearance_min–max, then every
    // interval_min–max Sols (~5–8, raised from 10–15 — Direction 1's "häufigeres
    // Erscheinen" is what makes the commodity layer's sizing work, see §4b/§12).
    // Each visit lasts duration_ticks Sols and offers items_count special items.
    'merchant' => [
        'first_appearance_min' => 15,   // earliest Sol the merchant can first appear
        'first_appearance_max' => 20,   // latest Sol for the first appearance
        'interval_min' => 5,   // minimum Sols between visits (Direction 1: was 10)
        'interval_max' => 8,   // maximum Sols between visits (Direction 1: was 15)
        'duration_ticks' => 2,    // how many Sols the merchant stays (inclusive)
        'items_count' => 3,    // items offered per visit (3 default, up to 4)
        'items' => [
            'ap_flex' => ['label' => 'AP-Paket (flexibel)',       'cost' => 800,  'ap_amount' => 20],
            'ap_targeted' => ['label' => 'AP-Paket (Kenntnis)',       'cost' => 500,  'ap_amount' => 15],
            'information' => ['label' => 'Systemkarte vollständig',   'cost' => 1200],
            'repair_kit' => ['label' => 'Reparatur-Kit (+30 SP)',    'cost' => 400,  'sp_amount' => 30],
            'trust_boost' => ['label' => 'Vertrauensschub (+15)',     'cost' => 600,  'trust_amount' => 15],
        ],

        // Corvan's Alltagsgeschäft (GDD §4b "Pfad-C-Hebel: von Regolith zu Credits",
        // §12 Kanal 1). Generated once per visit, alongside the special items above.
        'commodity' => [
            // Sell side: only Organika (resource_id=5) — the deliberately narrow
            // scope from §4b, not a generic sell-everything channel.
            'sell_resource_id' => 5,
            // ≈35 Cr/unit vs. the 50 Cr/unit buy base price (game.bar.base_prices[5])
            // — a 30% spread that makes buy-then-sell arbitrage unattractive.
            'sell_price_per_unit' => 35,
            // 2–3 lots per visit, ~15–25 units each (§12 "Sizing gegen §4b
            // nachgerechnet"): a single lot at this interval falls well short of the
            // ~247 Cr/Sol target; several lots per stop is the chosen lever, not a
            // shorter interval or one oversized lot (that would blow the reserve
            // floor below).
            'sell_lot_count_min' => 2,
            'sell_lot_count_max' => 3,
            'sell_lot_size_min' => 15,
            'sell_lot_size_max' => 25,
            // Reserve floor (§4b): a sell lot may not be generated, or accepted,
            // if it would leave the colony's Organika stock below this multiple of
            // ResourcesService::foodNeed() — protects the hunger→trust buffer.
            'sell_reserve_multiplier' => 2,
            // At Konsul rank 3, Corvan's buy offers bias towards compounds (moved
            // from the old game.bar.compounds_bias_at_rank3 — the effect travels
            // with the mechanic, see GDD §12 Kanal 1 "Folgefrage 1").
            'compounds_bias_at_rank3' => 0.50,
        ],
    ],

    'onboarding' => [
        // Status-points threshold (absolute, max is 20) at or below which the urgent
        // repair hint fires — warns of imminent level-down. Self-clears once every
        // building is back above it; never written to dismissed_hints.
        'hint_repair_urgent_sp' => 3,

        // Supply threshold below which Rank-1 hint fires (no housing built yet)
        'hint_supply_cap_threshold' => 10,

        // Ticks elapsed without any knowledge researched before Rank-4 hint fires
        'hint_no_knowledge_after_tick' => 8,

        // Trust value below which Rank-5 hint fires
        'hint_trust_threshold' => -20,

        // Minimum ticks elapsed before trust hint can fire (avoids day-1 trigger)
        'hint_trust_min_ticks' => 5,

        // Minimum ticks elapsed before Cantina hint fires (Agrardom placed + Housing>=1 + Bar missing).
        // 1 (Sol 2+) — deliberately equal to hint_no_analytik/hangar_after_tick: from
        // Sol 2 the player faces a genuine, equally-weighted path choice (Cantina /
        // Analytik-Labor / Hangar) — see GDD §16.2/§16.5 "Sol-2-Pfadwahl". The new
        // Sol-1-4 ramp (playtest review 2026-07-14) builds the path building BEFORE
        // CC Lv2, so advisor slot 2 can be filled the moment CC Lv2 completes.
        'hint_no_cantina_after_tick' => 1,

        // Minimum ticks elapsed before Agrardom hint fires (Harvester>=1 + bioFacility missing).
        // 0 (Sol 1) — the Agrardom is the colony's first build project (playtest review
        // 2026-07-14): placed and part-invested on Sol 1, finished Sol 2. It is the hard
        // prerequisite for CC Lv2, so it must come before everything else on the Bau-AP track.
        'hint_no_agrardome_after_tick' => 0,

        // Minimum ticks elapsed before Analytik-Labor hint fires (Agrardom placed + sciencelab missing).
        // 1 (Sol 2+) — equal to hint_no_cantina_after_tick (see comment there).
        'hint_no_analytik_after_tick' => 1,

        // Minimum ticks elapsed before Hangar hint fires (Agrardom placed + hangar missing).
        // 1 (Sol 2+) — equal to the other two path hints: all three path buildings
        // (Sciencelab, Hangar, Cantina) are equally-weighted choices from Sol 2 onward.
        'hint_no_hangar_after_tick' => 1,

        // Minimum current_tick floor for the CC-upgrade hint (hint_3). The primary
        // gate is state-based now (Agrardom >= Lv1 AND one path building >= Lv1 —
        // only then does CC Lv2 immediately pay off with a hireable advisor slot);
        // this tick value is just a floor so the hint can't fire before Sol 3.
        'hint_cc_upgrade_after_tick' => 2,

        // Latest current_tick at which the explore hint (hint_explore) still fires.
        // 0 = Sol 1 only — a single nudge into exploring the surroundings (find
        // regolith for the Harvester, scout hazards). Kept low on purpose: with
        // ring-staggered explore costs (game.colony.explore_cost_per_ring) repeated
        // nudging would push the player to dump all Nav-AP into fog-clearing every
        // Sol, defeating the slower reveal pace. See also the explore-tile-count
        // throttle in OnboardingHintService::checkHintExplore().
        'hint_explore_until_tick' => 0,

        // Once the player has explored at least this many tiles in the run, the
        // explore hint stops firing regardless of current_tick — they have clearly
        // engaged with the mechanic already and do not need further nudging.
        'hint_explore_max_explored_tiles' => 6,
    ],
];
