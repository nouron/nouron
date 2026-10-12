<?php

/**
 * Ship definitions — canonical source of truth for all per-ship mechanics.
 *
 * Fields:
 *   id                    — DB primary key in `ships` table
 *   trust_per_unit        — trust change per ship in colony fleet (used by TrustService)
 *   nexus_cost            — Credits to request this ship from Nexus (standard purchase)
 *   nexus_delivery_ticks  — Sols until ship arrives after Nexus request
 *   wear_per_sol          — status_points lost per tick while dispatched on a mission (GDD §7)
 *   find_carry            — find types this ship can bring home on mission_find_salvage (T30 §13.3, E5)
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
        'trust_per_unit' => 0,
        'nexus_cost' => 300,        // cheapest — unmanned, no crew
        'nexus_delivery_ticks' => 1,          // fast delivery
        'wear_per_sol' => 1.5,
        'find_carry' => ['find_small', 'find_medium'],
    ],

    // ── Military ──────────────────────────────────────────────────────────────

    'corvette' => [
        'id' => 37,         // ex fighter1
        'trust_per_unit' => 0,          // neutral — colonists welcome protection, not a military threat
        'nexus_cost' => 800,        // expensive — military hardware
        'nexus_delivery_ticks' => 3,
        'wear_per_sol' => 0.75,
        'find_carry' => [],
    ],

    // ── Transport ─────────────────────────────────────────────────────────────

    'freighter' => [
        'id' => 47,         // ex smallTransporter
        'trust_per_unit' => 2,       // 1→2 (2026-09-14, A37-Folge task_colony_prosperity)
        'nexus_cost' => 500,
        'nexus_delivery_ticks' => 2,
        'wear_per_sol' => 1.0,
        'find_carry' => ['find_small', 'find_medium', 'find_large'],
    ],

];
