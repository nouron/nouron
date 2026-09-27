<?php

/**
 * Tile type definitions — canonical source of truth for all per-tile mechanics.
 *
 * Each key is the internal tile_type (or event_type) identifier stored in
 * the colony_tiles table. The value array describes what the tile produces
 * and whether random events can be triggered on it.
 *
 * Fields:
 *   resource_id     — resource produced by this tile (null = no yield)
 *   base_yield      — units produced per harvesting tick at level 1
 *   yields          — shorthand null for non-resource tiles (overrides resource_id / base_yield)
 *   event_eligible  — whether a random event can occur on this tile
 *
 * Resource reference (resources table):
 *   ID 3 = res_regolith (Rg) — primary surface mining resource
 *
 * Terrain tiles are passable but yield nothing.
 * Event tiles are revealed only by deep scan (is_deep_scanned = true).
 *
 * Design context: DS-4 (Tile Catalogue), GDD §5 (Colony Surface).
 */
return [

    // ── Terrain ──────────────────────────────────────────────────────────────

    'terrain_empty' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'terrain_hazard' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'terrain_impassable' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],

    // ── Regolith resource nodes ───────────────────────────────────────────────
    // resource_id 3 = res_regolith
    // A44/H1: yield tier (y1-y3) decoupled from deposit tier (d1-d3), see
    // config/game.php -> harvester.fresh_yield (single source of truth for
    // the actual per-Sol value; base_yield here mirrors it for the hexview
    // move-preview badge).

    'regolith_y1_d1' => ['resource_id' => 3, 'base_yield' => 16, 'event_eligible' => true],
    'regolith_y1_d2' => ['resource_id' => 3, 'base_yield' => 16, 'event_eligible' => true],
    'regolith_y1_d3' => ['resource_id' => 3, 'base_yield' => 16, 'event_eligible' => true],
    'regolith_y2_d1' => ['resource_id' => 3, 'base_yield' => 23, 'event_eligible' => true],
    'regolith_y2_d2' => ['resource_id' => 3, 'base_yield' => 23, 'event_eligible' => true],
    'regolith_y2_d3' => ['resource_id' => 3, 'base_yield' => 23, 'event_eligible' => true],
    'regolith_y3_d1' => ['resource_id' => 3, 'base_yield' => 30, 'event_eligible' => true],
    'regolith_y3_d2' => ['resource_id' => 3, 'base_yield' => 30, 'event_eligible' => true],

    // ── Event overlays (revealed via deep scan only) ──────────────────────────
    // event_eligible = false: events cannot randomly re-occur on these tiles

    'event_wreck' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_ruin' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_bunker' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_probe' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_crystal' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_vent' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_cave' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_cache' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_signal' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],
    'event_anomaly' => ['resource_id' => null, 'base_yield' => null, 'event_eligible' => false],

];
