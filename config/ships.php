<?php

/**
 * Ship definitions — canonical source of truth for all per-ship mechanics.
 *
 * Fields:
 *   id                    — DB primary key in `ships` table
 *   moving_speed          — tiles per tick (fleet moves at slowest ship's speed)
 *   supply_cost           — supply consumed per ship unit (0 for unmanned craft)
 *   trust_per_unit        — trust change per ship in colony fleet (used by TrustService)
 *   nexus_cost            — Credits to request this ship from Nexus (standard purchase)
 *   nexus_delivery_ticks  — Sols until ship arrives after Nexus request
 *
 * Ships do NOT decay. They are either intact or destroyed (combat, mission hazards).
 * Maintenance pressure comes from the Hangar building decaying, not from the ships themselves.
 *
 * Fleet speed = min(moving_speed) of all ships in fleet.
 *
 * Localization: lang/de/ships.php, lang/en/ships.php
 */
return [

    // ── Unmanned ──────────────────────────────────────────────────────────────

    'drone' => [
        'id'                   => 85,
        'moving_speed'         => 5,          // fastest unit in the game
        'supply_cost'          => 0,          // unmanned — no crew, no supply upkeep
        'trust_per_unit'       => 0,
        'nexus_cost'           => 300,        // cheapest — unmanned, no crew
        'nexus_delivery_ticks' => 2,          // fast delivery
    ],

    // ── Military ──────────────────────────────────────────────────────────────

    'corvette' => [
        'id'                   => 37,         // ex fighter1
        'moving_speed'         => 4,
        'supply_cost'          => 14,         // still in DB; supply cost for ships being removed (Phase 3)
        'trust_per_unit'       => 0,          // neutral — colonists welcome protection, not a military threat
        'nexus_cost'           => 800,        // expensive — military hardware
        'nexus_delivery_ticks' => 5,
    ],

    // ── Transport ─────────────────────────────────────────────────────────────

    'freighter' => [
        'id'                   => 47,         // ex smallTransporter
        'moving_speed'         => 3,
        'supply_cost'          => 6,
        'trust_per_unit'       => 1,
        'nexus_cost'           => 500,
        'nexus_delivery_ticks' => 3,
    ],

];
