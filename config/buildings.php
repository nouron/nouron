<?php

/**
 * Building definitions — canonical source of truth for all per-building mechanics.
 *
 * Fields:
 *   id               — DB primary key in `buildings` table
 *   supply_cap       — flat supply cap granted (commandCenter) or per-unit cap (housingComplex)
 *   supply_cost      — supply consumed while the building exists at level > 0
 *   build_cost       — one-time resource cost to erect (level 0→1), as [resource_id => amount]
 *                      (3 = Regolith, 4 = Werkstoffe/compounds). Absent = no resource cost
 *                      (CommandCenter + Harvester only — bootstrap exemption). Werkstoffe
 *                      appear only on late/high-tech buildings (accent, 10–25). Level-up
 *                      Regolith is derived as 25 % of build_cost[3] (min 10); CC scales
 *                      separately via cc_upgrade_regolith_per_level. Reparatur: 2 Rg/click.
 *                      Canonical source — synced into building_costs by game:sync-config.
 *   trust_per_lv     — trust change per building level (used by TrustService)
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
        'id' => 25,
        // No build_cost: CC exists from the start (bootstrap). Upgrades cost Regolith,
        // scaling with the target level (target_level × cc_upgrade_regolith_per_level).
        'cc_upgrade_regolith_per_level' => 30,
        'supply_cap' => 10,      // cap per level (CC Lv1 = 10, Lv5 = 50 — hard cap Lv5)
        'supply_cost' => 0,
        'trust_per_lv' => 0,
        'decay_rate' => 0.33,    // 60 days
        'max_status_points' => 20,
        'max_level' => 5,
    ],

    'housingComplex' => [
        'id' => 28,
        'build_cost' => [3 => 40],   // Regolith only (early)
        'supply_cap' => 8,       // per unit (instance), max 6 units → +48 cap
        'supply_cost' => 0,
        'trust_per_lv' => 0,
        'decay_rate' => 0.44,    // 45 days
        'max_status_points' => 20,
        'max_level' => 6,       // max 6 instances (instanced building)
    ],

    // ── Industry ──────────────────────────────────────────────────────────────

    'harvester' => [                    // ex industrieMine/oremine (ID 27) — produces Regolith (resource 3)
        'id' => 27,
        // No build_cost: Harvester is the only Regolith source (bootstrap exemption).
        'supply_cost' => 2,
        'trust_per_lv' => 0,
        'decay_rate' => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level' => null,
    ],

    // bioFacility (Agrardom) — mandatory prerequisite for the CC Lv1→Lv2
    // upgrade (2026-06-24, GDD §4 "Agrardom wird Pflichtgebäude vor CC Lv2").
    // No longer part of the Sol-3 path-choice group (sciencelab/hangar/bar) —
    // it has no CC-level gate of its own (only Harvester ≥ Lv1), so it stays
    // reachable from Sol 1 and must be built before CC2 to guarantee Organika
    // flow through the strictly linear Sol-1/2 ramp. Enforced in the CC
    // levelup endpoint (ColonyService — NOT in this config), not here.
    'bioFacility' => [                  // ex silicatemine (ID 41) — now produces Organika
        'id' => 41,
        'build_cost' => [3 => 40],   // Regolith only (early)
        'supply_cost' => 2,
        'trust_per_lv' => 0,
        'decay_rate' => 0.95,
        'max_status_points' => 20,
        'max_level' => null,
    ],

    // ── Science ───────────────────────────────────────────────────────────────

    'sciencelab' => [
        'id' => 31,
        // Regolith only — no Compounds (circular dep risk, same reasoning as
        // uplinkStation below): CC Lv2 unlocks this building (one of three
        // parallel "path" buildings — sciencelab/hangar/bar — see GDD §13
        // "Pfadwahl ab Sol 3"). Building it is what grants the matching
        // generic advisor slot (Analytiker) — slot binding is no longer a
        // fixed CC-level→type mapping, see AdvisorController::PATH_BUILDINGS.
        // Werkstoffe aren't reachable this early (Uplink-Station Lv1 +
        // Cantina/merchant, both later). The previous [Rg+Wk] cost made the
        // Analytiker structurally useless for several Sols right after hiring.
        'build_cost' => [3 => 80],
        'supply_cost' => 8,
        'trust_per_lv' => 0,
        'decay_rate' => 0.95,    // 21 days
        'max_status_points' => 20,
        'max_level' => null,
    ],

    // ── Fleet ─────────────────────────────────────────────────────────────────

    // Hangar — CC gate lowered from Lv3 to Lv2 (2026-06-24): one of three
    // parallel "path" buildings (sciencelab/hangar/bar), see GDD §13
    // "Pfadwahl ab Sol 3". Only 1 of the 3 path buildings can be placed at
    // CC Lv2 — the build-gate (CC-level − 1 ≥ count of path buildings already
    // placed) is enforced in ColonyService::placeBuilding, NOT in this config.
    // supply_cost (6) is intentionally low: the hangar itself is cheap to run
    // because each ship commissioned carries its own supply cost — fleet size
    // is capped by ships, not by the building. Building it grants the matching
    // generic advisor slot (Raumfahrer) — see AdvisorController::PATH_BUILDINGS.
    'hangar' => [                       // replaces civilianSpaceyard + militarySpaceyard
        'id' => 44,      // ex civilianSpaceyard — 1 hangar = 1 ship slot
        'build_cost' => [3 => 80, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 6,       // low: ships carry supply cost; hangar is just a slot
        'trust_per_lv' => 0,
        'decay_rate' => 0.67,    // 30 days
        'max_status_points' => 20,
        'max_level' => null,    // repeatable, supply-limited
    ],

    // ── Civil welfare ─────────────────────────────────────────────────────────

    'infirmary' => [
        'id' => 46,
        'build_cost' => [3 => 60, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 10,
        'trust_per_lv' => 3,
        'decay_rate' => 0.67,    // 30 days — core infrastructure, same tier as hangar
        'max_status_points' => 20,
        'max_level' => null,
    ],

    // Cantina (bar) — CC Lv2, one of three parallel "path" buildings
    // (sciencelab/hangar/bar), see GDD §13 "Pfadwahl ab Sol 3". Building it
    // grants the matching generic advisor slot (Konsul) — see
    // AdvisorController::PATH_BUILDINGS.
    'bar' => [
        'id' => 52,
        'build_cost' => [3 => 50],   // Regolith only (early)
        'supply_cost' => 4,
        'trust_per_lv' => 2,       // social hub — leisure in an otherwise bleak colony life
        'decay_rate' => 1.0,     // 20 days — sturdy enough, but needs occasional maintenance
        'max_status_points' => 20,
        'max_level' => null,
    ],

    'monument' => [
        'id' => 50,
        'build_cost' => [3 => 60, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 2,
        'trust_per_lv' => 2,
        'decay_rate' => 0.33,    // 60 days — monuments are built to last
        'max_status_points' => 20,
        'max_level' => null,
    ],

    'temple' => [
        'id' => 32,
        'build_cost' => [3 => 50, 4 => 15],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 4,
        'trust_per_lv' => 2,
        'decay_rate' => 2.0,     // 10 days — needs regular upkeep
        'max_status_points' => 20,
        'max_level' => null,
    ],

    // ── Phase 3g — implementiert (Mai 2026) ──────────────────────────────────

    // Security Hub — CC Lv2, max 1 instance (is_instanced=0).
    // (Former defend-order discount removed with the fleet/galaxy layer 2026-06.)
    // Effect: on building level-down by decay, return 10% of step costs in
    //         tradeable resources (GameTick, config key: securityHub_recycle_pct).
    // TODO Balance: baukosten/supply_cost/decay kalibrieren nach erstem Playtest.
    'securityHub' => [
        'id' => 53,
        'build_cost' => [3 => 80, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 8,
        'trust_per_lv' => 0,
        'decay_rate' => 0.67,    // 30 days — provisional
        'max_status_points' => 20,
        'max_level' => 3,
        'recycle_pct' => 0.10,    // fraction of build cost returned on level-down
    ],

    // Uplink Station — CC Lv2 (Lv1), CC Lv3 (Lv2), CC Lv5 (Lv3). 1 instance (is_instanced=0).
    // Effect Lv2+: deep-scan costs 1 Nav-AP instead of 2 (ColonyTileService).
    // Effect Lv2+: merchant appears more frequently (TODO: implement with merchant system).
    // Effect Lv3: run-completion action — TODO: implement when run-end mechanic is built.
    // Lv1 build cost: Regolith + Credits only — no Compounds (circular dep risk).
    // TODO Balance: per-level CC gates (Lv2→CC3, Lv3→CC5) not yet enforced — post-playtest.
    'uplinkStation' => [
        'id' => 54,
        // Late building, but NO Werkstoffe: the Uplink is the Werkstoff-import gate
        // (Nexus direct import) — requiring Werkstoffe to build it would be circular.
        'build_cost' => [3 => 80],
        'supply_cost' => 6,
        'trust_per_lv' => 0,
        'decay_rate' => 0.67,    // 30 days — provisional
        'max_status_points' => 20,
        'max_level' => 3,
    ],

    // Trading Post — CC Lv4, max 1 instance (is_instanced=0).
    // Effect: Merchant (+Reisender Händler) gives +12% better trade value when present.
    //         Konsul Economy-AP reduction: TODO implement when merchant system is built.
    // TODO Balance: merchant_price_bonus vs. Konsul-Rang-System (kein Stack-Effekt) — post-playtest.
    'tradingPost' => [
        'id' => 55,
        'build_cost' => [3 => 100, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 6,
        'trust_per_lv' => 0,
        'decay_rate' => 0.67,    // 30 days — provisional
        'max_status_points' => 20,
        'max_level' => 3,
        'merchant_price_bonus' => 0.12,    // +12% trade value when Reisender Händler present
    ],

];
