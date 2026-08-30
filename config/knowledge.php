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
    // Raised 12/20/30/40/50 → 20/28/36/44/52 (GDD §13.7, 2026-08-03, Owner-Entscheidung):
    // amortization ~7 Sole against the new AP-Ratenmodell sockel (ap.base=12). credits
    // dropped 100 → 0 in the same change (§4b Pfad-A-Credits-Lücke — Analytiker path
    // has no other Credits sink to justify a per-level fee).
    // ⚠️ Diese Kurve ist an game.ap.base / advisor.ap_per_rank gekoppelt — bei
    // Änderung dort gegen die AP/Sol-Rate neu prüfen, nicht isoliert betrachten.

    'construction' => [
        'id' => 90,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
        // Bau-AP-Rabatt (GDD §13.3, glockenförmig statt linear — game-designer review
        // 2026-08-15, docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md).
        // Wirkt additiv mit trade auf ALLE Gebäude-Levelups (Owner-Entscheidung:
        // keine Domänentrennung nach Projekttyp, da nur Bau-Projekte existieren).
        'ap_cost_reduction_per_lv' => [1 => 2, 2 => 4, 3 => 4, 4 => 3, 5 => 2],   // Σ15%
    ],

    'cartography' => [
        'id' => 91,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
        // Navigation-AP-Rabatt (seit 2026-08-27 kein Mitglied des Bau-Rabatt-Pools mehr).
        // Rabatt auf Explorationsaktionen, Kurve wird Task 2 finalisieren.
        'nav_ap_reduction_per_lv' => [1 => 2, 2 => 4, 3 => 4, 4 => 3, 5 => 2],   // Σ15%
    ],

    'geology' => [
        'id' => 92,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
    ],

    'agronomy' => [
        'id' => 93,
        'trust_per_lv' => 1,       // see GDD §13
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
    ],

    'health' => [
        'id' => 94,
        'trust_per_lv' => 2,       // see GDD §13
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
    ],

    'trade' => [
        'id' => 95,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
        // Bau-AP-Rabatt (GDD §13.3, glockenförmig statt linear — game-designer review
        // 2026-08-15, docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md).
        // Wirkt additiv mit construction auf ALLE Gebäude-Levelups (Owner-Entscheidung:
        // keine Domänentrennung nach Projekttyp, da nur Bau-Projekte existieren).
        'ap_cost_reduction_per_lv' => [1 => 2, 2 => 4, 3 => 4, 4 => 3, 5 => 2],   // Σ15%
        // Cantina-Angebotsslot-Bonus (Task 4 dieses Plans) — zusätzliche gleichzeitige
        // Bar-Angebote bei höherem trade-Level.
        'bar_offer_boost_per_lv' => [1 => 0, 2 => 1, 3 => 1, 4 => 0, 5 => 0],   // Σ2 Slots
    ],

    'defense' => [
        'id' => 96,
        'trust_per_lv' => -1,      // see GDD §13 — vigilance dampens trust slightly
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52],
    ],

];
