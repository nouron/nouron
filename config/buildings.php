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
 *
 * Decay reference: decay_rate = max_status_points / target_days
 *   7 d → 2.86 | 10 d → 2.0 | 14 d → 1.43 | 21 d → 0.95
 *   30 d → 0.67 | 45 d → 0.44 | 60 d → 0.33
 *
 * Note: decay_rate, max_status_points and supply_cost are also stored in the `buildings` DB table.
 * After changing values here run: php artisan game:sync-techs (to be implemented)
 *
 * Localization: lang/de/buildings.php, lang/en/buildings.php
 */
return [

    // ── Supply-Cap providers ──────────────────────────────────────────────────

    'commandCenter' => [
        'id'                => 25,
        'supply_cap'        => 10,      // cap per level (CC Lv1 = 10, Lv5 = 50 — hard cap Lv5)
        'supply_cost'       => 0,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.33,    // 60 days
        'max_status_points' => 20,
        'max_level'         => 5,
    ],

    'housingComplex' => [
        'id'                => 28,
        'supply_cap'        => 8,       // per unit (instance), max 6 units → +48 cap
        'supply_cost'       => 0,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.44,    // 45 days
        'max_status_points' => 20,
        'max_level'         => 6,       // max 6 instances (instanced building)
    ],

    // ── Industry ──────────────────────────────────────────────────────────────

    'harvester' => [                    // ex industrieMine/oremine (ID 27) — produces Regolith (resource 3)
        'id'                => 27,
        'supply_cost'       => 2,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    'bioFacility' => [                  // ex silicatemine (ID 41) — now produces Organika
        'id'                => 41,
        'supply_cost'       => 2,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    'depot' => [
        'id'                => 30,
        'supply_cost'       => 3,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    // ── Science ───────────────────────────────────────────────────────────────

    'sciencelab' => [
        'id'                => 31,
        'supply_cost'       => 8,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    // ── Fleet ─────────────────────────────────────────────────────────────────

    'hangar' => [                       // replaces civilianSpaceyard + militarySpaceyard
        'id'                => 44,      // ex civilianSpaceyard — 1 hangar = 1 ship slot
        'supply_cost'       => 12,      // limits fleet naturally: 3 hangars = 36 supply
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level'         => null,    // repeatable, supply-limited
    ],

    // ── Civil welfare ─────────────────────────────────────────────────────────

    'infirmary' => [
        'id'                => 46,
        'supply_cost'       => 10,
        'moral_per_lv'      => 3,
        'decay_rate'        => 0.67,    // 30 days — core infrastructure, same tier as depot/hangar
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    'bar' => [
        'id'                => 52,
        'supply_cost'       => 4,
        'moral_per_lv'      => 2,       // social hub — leisure in an otherwise bleak colony life
        'decay_rate'        => 1.0,     // 20 days — sturdy enough, but needs occasional maintenance
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    'monument' => [
        'id'                => 50,
        'supply_cost'       => 2,
        'moral_per_lv'      => 2,
        'decay_rate'        => 0.33,    // 60 days — monuments are built to last
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    'temple' => [
        'id'                => 32,
        'supply_cost'       => 4,
        'moral_per_lv'      => 2,
        'decay_rate'        => 2.0,     // 10 days — needs regular upkeep
        'max_status_points' => 20,
        'max_level'         => null,
    ],

    // ── Phase 3g — implementiert (Mai 2026) ──────────────────────────────────

    // Security Hub — CC Lv2, max 1 instance (is_instanced=0).
    // Effect 1: defend-order costs 1 Nav-AP instead of 2 (FleetService).
    // Effect 2: on building level-down by decay, return 10% of step costs in
    //           tradeable resources (GameTick, config key: securityHub_recycle_pct).
    // TODO Balance: baukosten/supply_cost/decay kalibrieren nach erstem Playtest.
    'securityHub' => [
        'id'                  => 53,
        'supply_cost'         => 8,
        'moral_per_lv'        => 0,
        'decay_rate'          => 0.67,    // 30 days — provisional
        'max_status_points'   => 20,
        'max_level'           => 3,
        'recycle_pct'         => 0.10,    // fraction of build cost returned on level-down
    ],

    // Uplink Station — CC Lv2 (Lv1), CC Lv3 (Lv2), CC Lv5 (Lv3). 1 instance (is_instanced=0).
    // Effect Lv2+: deep-scan costs 1 Nav-AP instead of 2 (ColonyTileService).
    // Effect Lv2+: merchant appears more frequently (TODO: implement with merchant system).
    // Effect Lv3: run-completion action — TODO: implement when run-end mechanic is built.
    // Lv1 build cost: Regolith + Credits only — no Compounds (circular dep risk).
    // TODO Balance: per-level CC gates (Lv2→CC3, Lv3→CC5) not yet enforced — post-playtest.
    'uplinkStation' => [
        'id'                => 54,
        'supply_cost'       => 6,
        'moral_per_lv'      => 0,
        'decay_rate'        => 0.67,    // 30 days — provisional
        'max_status_points' => 20,
        'max_level'         => 3,
    ],

    // Trading Post — CC Lv4, max 1 instance (is_instanced=0).
    // Effect: Merchant (+Reisender Händler) gives +12% better trade value when present.
    //         Konsul Economy-AP reduction: TODO implement when merchant system is built.
    // TODO Balance: merchant_price_bonus vs. Konsul-Rang-System (kein Stack-Effekt) — post-playtest.
    'tradingPost' => [
        'id'                   => 55,
        'supply_cost'          => 6,
        'moral_per_lv'         => 0,
        'decay_rate'           => 0.67,    // 30 days — provisional
        'max_status_points'    => 20,
        'max_level'            => 3,
        'merchant_price_bonus' => 0.12,    // +12% trade value when Reisender Händler present
    ],

];
