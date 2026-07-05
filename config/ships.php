<?php

/**
 * Ship definitions — canonical source of truth for all per-ship mechanics.
 *
 * Fields:
 *   id                    — DB primary key in `ships` table
 *   moving_speed          — tiles per tick (fleet moves at slowest ship's speed)
 *   supply_cost           — always 0; ships do not consume supply (GDD §6, 2026-06-08)
 *   trust_per_unit        — trust change per ship in colony fleet (used by TrustService)
 *   nexus_cost            — Credits to request this ship from Nexus (standard purchase)
 *   nexus_delivery_ticks  — Sols until ship arrives after Nexus request
 *   wear_per_sol          — status_points lost per tick while dispatched on a mission (GDD §7)
 *
 * Ships do NOT decay over time. Wear happens only through active use (missions);
 * a docked ship never loses status_points.
 *
 * Localization: lang/de/ships.php, lang/en/ships.php
 */
return [

    // ── Unmanned ──────────────────────────────────────────────────────────────

    'drone' => [
        'id' => 85,
        'moving_speed' => 5,          // fastest unit in the game
        'supply_cost' => 0,          // unmanned — no crew, no supply upkeep
        'trust_per_unit' => 0,
        'nexus_cost' => 300,        // cheapest — unmanned, no crew
        'nexus_delivery_ticks' => 2,          // fast delivery
        'wear_per_sol' => 1.5,
    ],

    // ── Military ──────────────────────────────────────────────────────────────

    'corvette' => [
        'id' => 37,         // ex fighter1
        'moving_speed' => 4,
        'supply_cost' => 0,
        'trust_per_unit' => 0,          // neutral — colonists welcome protection, not a military threat
        'nexus_cost' => 800,        // expensive — military hardware
        'nexus_delivery_ticks' => 5,
        'wear_per_sol' => 0.75,
    ],

    // ── Transport ─────────────────────────────────────────────────────────────

    'freighter' => [
        'id' => 47,         // ex smallTransporter
        'moving_speed' => 3,
        'supply_cost' => 0,
        'trust_per_unit' => 1,
        'nexus_cost' => 500,
        'nexus_delivery_ticks' => 3,
        'wear_per_sol' => 1.0,
    ],

];
