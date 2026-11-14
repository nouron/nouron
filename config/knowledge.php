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
 * Localization: lang/de/knowledge.php, lang/en/knowledge.php
 */
return [

    // levelup_costs: AP needed to reach that level (index = target level, 1–5).
    // AP scale 2026-11-13 §11.2 (L-4): 20/28/36/44/52 → 8/11/14/18/21. credits 0
    // (§4b Pfad-A-Credits-Lücke — Analytiker path has no other Credits sink).
    // ⚠️ Diese Kurve ist an game.ap.base / advisor.ap_per_rank gekoppelt — bei
    // Änderung dort gegen die AP/Sol-Rate neu prüfen, nicht isoliert betrachten.

    'construction' => [
        'id' => 90,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
        // Building levelup AP discount in absolute AP per knowledge level (AP scale
        // 2026-11-13 §4). Applies only to levelups of at least game.min_discountable_cost_ap
        // (in practice the Command Center), see ProjectBonusService.
        'ap_cost_reduction_per_lv' => [2 => 1, 4 => 1],   // Σ2 AP
    ],

    'cartography' => [
        'id' => 91,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
        // AP scale 2026-11-13 §4 (rule §5.1: 1-AP actions get frequency, not a discount):
        // from from_level, missions of at least min_distance Sols cost `ap` AP less (floor
        // game.action_min_cost_ap); from free_explore_from_level the first exploration of a
        // Sol costs no AP. See ProjectBonusService / ColonyTileService::exploreCost().
        'mission_ap_reduction' => ['from_level' => 2, 'min_distance' => 3, 'ap' => 1],
        'free_explore_from_level' => 4,
    ],

    'geology' => [
        'id' => 92,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
    ],

    'agronomy' => [
        'id' => 93,
        'trust_per_lv' => 1,       // see GDD §13; 2→1 (T59 0..100 scale, halved)
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
    ],

    'health' => [
        'id' => 94,
        'trust_per_lv' => 2,       // see GDD §13; 3→2 (T59 0..100 scale, halved)
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
    ],

    'trade' => [
        'id' => 95,
        'trust_per_lv' => 0,
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
        // Kein Bau-AP-Rabatt mehr: bis 2026-09-20 senkte trade die AP-Kosten von Gebäude-
        // Levelups (Σ15%, additiv mit construction). Owner-Entscheidung (A13): thematisch
        // falsch — Handel wirkt auf Preise, Angebote und Verhandlung, nicht auf Bauprojekte.
        // Cantina-Angebotsslot-Bonus (Task 4 dieses Plans) — zusätzliche gleichzeitige
        // Bar-Angebote bei höherem trade-Level.
        'bar_offer_boost_per_lv' => [1 => 0, 2 => 1, 3 => 1, 4 => 0, 5 => 0],   // Σ2 Slots
        // Additiver Preis-Bonus auf ALLE 3 Handelskanäle, zusätzlich zum bestehenden
        // TradingPostService-Kanalrabatt — additives Stacking mehrerer Quellen ist
        // etablierte Projekt-Konvention. Owner-Entscheidung 2026-08-27, Platzhalter-Größe (ADR 0004).
        'trade_price_bonus_per_lv' => [1 => 2, 2 => 3, 3 => 3, 4 => 2, 5 => 2],
        // Verhandlungsführung: Prozentpunkte auf die Erfolgschance der Cantina-Verhandlung
        // (game.bar.negotiate_success_chance), kumulativ über die Level (Σ8 bei Lv5). Wirkt nur,
        // wenn ein Konsul verhandelt. Owner-Entscheidung A13 2026-09-20, Kalibrierung offen.
        'negotiate_chance_bonus_per_lv' => [1 => 1, 2 => 2, 3 => 2, 4 => 2, 5 => 1],
    ],

    'defense' => [
        'id' => 96,
        // Vorzeichen umgekehrt (Owner-Entscheidung 2026-08-27, "Sicherheit schafft
        // Vertrauen" statt "Wachsamkeit dämpft Vertrauen") — dieselbe Magnitude
        // wie agronomy ursprünglich, nur positiv. 1→2 (2026-09-14, A37-Folge
        // task_colony_prosperity: realistisches Trust-Maximum lag bei ~50, weit
        // unter der 70er-Zielschwelle — Trust-Quellen verstärkt statt Ziel gesenkt).
        'trust_per_lv' => 1,       // 2→1 (T59 0..100 scale, halved)
        'decay_rate' => 0,
        'max_status_points' => 20,
        'credits' => 0,
        'levelup_costs' => [1 => 8, 2 => 11, 3 => 14, 4 => 18, 5 => 21],
    ],

];
