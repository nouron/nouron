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
 *                      Regolith is a flat 25 for all non-CC/non-Harvester buildings,
 *                      independent of build_cost (GDD §13.7, 2026-08-03); CC scales
 *                      separately via cc_upgrade_regolith_per_level. Reparatur: 1 Rg/click.
 *                      Canonical source — synced into building_costs by game:sync-config.
 *   trust_per_lv     — trust change per building level (used by TrustService)
 *   decay_rate       — status_points lost per tick (also stored in DB, used by GameTick decay)
 *   max_status_points — status_points reset value after level-down (also stored in DB)
 *   max_level        — hard level cap (null = uncapped, practically limited by supply)
 *   max_instances    — hard instance cap for instanced buildings (null = uncapped,
 *                      practically limited by supply). Separate axis from max_level
 *                      (Owner-Entscheidung 2026-08-03, GDD §4c) — needed because a
 *                      building can be capped on level, instance count, or both
 *                      (hangar: instances = ship slots, level = ship class).
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
        // 30 → 20 (playtest review 2026-07-14): with the new Sol-1-4 onboarding ramp
        // (Agrardom → path building → CC Lv2) the CC-Lv2 levelup was the single
        // biggest regolith drain, leaving ~7 Rg buffer on the hangar path by Sol 4.
        // 20 → 30 (GDD §13.7, 2026-08-03): "zentraler Progressionshebel" — der Zahlensatz
        // trägt CC-Ausbau jetzt als teuersten Einzelposten des Runs (Bilanz: 75% Sockel-
        // Deckung der Zielkolonie), s. Handoff docs/handoff-ap-ratenmodell.md §7.
        'cc_upgrade_regolith_per_level' => 30,
        'supply_cap' => 10,      // cap per level (CC Lv1 = 10, Lv5 = 50 — hard cap Lv5)
        'supply_cost' => 0,
        'trust_per_lv' => 0,
        // Klasse "Robust" (GDD §13.7 decay_rate-Klassentabelle, 2026-08-03): 50 Sole bis Level-Down.
        'decay_rate' => 0.40,
        'max_status_points' => 20,
        'max_level' => 5,
    ],

    'housingComplex' => [
        'id' => 28,
        'build_cost' => [3 => 40],   // Regolith only (early)
        'supply_cap' => 8,       // per unit (instance), max 6 units → +48 cap
        'supply_cost' => 0,
        'trust_per_lv' => 0,
        // Klasse "Robust" (GDD §13.7, 2026-08-03): 50 Sole bis Level-Down.
        'decay_rate' => 0.40,
        'max_status_points' => 20,
        // max_level is a real per-instance level cap (not vestigial): each
        // housingComplex instance levels independently and ResourcesService::
        // getSupplyBreakdown() sums per-instance `level` for the supply
        // contribution, so max_level genuinely caps how much supply a single
        // instance can contribute. max_instances=6 is the separate instance-count
        // cap that ColonyController::buildableBuildings() reads directly (not via
        // max_level). Gesenkt von 6 auf 3 (2026-08-25, game-designer-Review):
        // max_level und max_instances waren bis dahin identisch (6) aus
        // historischem Zufall, keine Design-Absicht — Doppelachse machte die
        // Bauentscheidung uninteressant (beide Wege füttern denselben Supply-Pool
        // ohne qualitativen Unterschied). Die beiden Felder sind jetzt bewusst
        // unterschiedlich: max_level=3 ist der Pro-Instanz-Levelcap, max_instances=6
        // bleibt die separate, tile-limitierte Instanzanzahl-Achse; Level ist
        // sekundäre Feinabstimmung. supply_cap-Neukalibrierung ist ein separater
        // Balance-Task nach Playtest
        // (docs/superpowers/specs/2026-08-23-building-tier-system-design.md, Punkt 9).
        'max_level' => 3,
        'max_instances' => 6,
    ],

    // ── Industry ──────────────────────────────────────────────────────────────

    'harvester' => [                    // ex industrieMine/oremine (ID 27) — produces Regolith (resource 3)
        'id' => 27,
        // No build_cost: Harvester is the only Regolith source (bootstrap exemption).
        'supply_cost' => 2,
        'trust_per_lv' => 0,
        // Klasse "Beansprucht" (GDD §13.7, 2026-08-03): 25 Sole bis Level-Down.
        'decay_rate' => 0.80,
        'max_status_points' => 20,
        // Harvester ohne Level-Up (Owner-Entscheidung, GDD §13.5/§4c, 2026-08-03):
        // max_level bleibt 1, Wachstum läuft über max_instances (Deckel 2), nicht
        // mehr über production_curve[27]-Level 2-8 — die Kurve bleibt als Daten
        // stehen (inert), siehe Handoff docs/handoff-ap-ratenmodell.md §2/§4.1.
        'max_level' => 1,
        'max_instances' => 2,
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
        // 40 → 70 (GDD §13.7 Sol-1-4-Rampe, 2026-08-03): Agrardom ist der erste Kauf des
        // Runs, die Rampenprobe rechnet ihn explizit mit 70 gegen Startbestand 200.
        'build_cost' => [3 => 70],
        'supply_cost' => 2,
        'trust_per_lv' => 0,
        // Klasse "Standard" (GDD §13.7, 2026-08-03): 33 Sole bis Level-Down.
        'decay_rate' => 0.60,
        'max_status_points' => 20,
        // Gedeckelt auf 3 (2026-08-25, Ausbaustufen-Umstellung) — production_curve[41]
        // (config/game.php) hat weiterhin Einträge für Lv4-8; die werden ab jetzt nie
        // erreicht und bleiben als inerte historische Daten stehen (gleiches Muster
        // wie production_curve[27] beim Harvester, siehe dortiger Kommentar).
        'max_level' => 3,
        'tiers' => [3],
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
        // 80 → 95 (GDD §13.7, 2026-08-03): Pfad-Parität mit Cantina. Seit GDD-Audit
        // G4 (2026-08-11) kostet auch der Hangar-Pfad 95 — alle drei Pfadgebäude sind
        // jetzt preisgleich.
        'build_cost' => [3 => 95],
        // 8 → 6 (Owner, 2026-08-11, provisorisch): gleicher supply_cost wie Hangar/Bar,
        // s. Kommentar bei Hangar. Testkonvenienz, keine vollständige Neuherleitung.
        'supply_cost' => 6,
        'trust_per_lv' => 0,
        // Klasse "Beansprucht" (GDD §13.7, 2026-08-03): 25 Sole bis Level-Down.
        'decay_rate' => 0.80,
        'max_status_points' => 20,
        // Gedeckelt auf 5 (2026-08-25) — Ausnahme von der max.-3-Regel: Lv1-3
        // bleiben Kenntnis-Freischalt-Gates (unverändert, siehe researches-
        // Migrationen), Lv4/5 bekommen einen Domänen-Effizienzbonus "Wissen"
        // (separater Folge-Plan, noch nicht implementiert — Level-Deckel wird
        // hier vorab gesetzt, damit er nicht vergessen wird).
        'max_level' => 5,
    ],

    // ── Fleet ─────────────────────────────────────────────────────────────────

    // Hangar — CC gate lowered from Lv3 to Lv2 (2026-06-24): one of three
    // parallel "path" buildings (sciencelab/hangar/bar), see GDD §13
    // "Pfadwahl ab Sol 3". Only 1 of the 3 path buildings can be placed at
    // CC Lv2 — the build-gate (CC-level − 1 ≥ count of path buildings already
    // placed) is enforced in ColonyService::placeBuilding, NOT in this config.
    //
    // Rebalanced 2026-06-28 (GDD §4 Pfadwahl-Kostenbalancing):
    //   - Werkstoffe requirement REMOVED: same circular-dep argument as sciencelab —
    //     Uplink-Station (Wk import gate) is also CC Lv2; requiring Wk here forced
    //     an extra 80-Rg + 6-supply detour before the Hangar path was accessible,
    //     making Pfad B structurally harder than Pfad A/C without any counterbalance.
    //   - Regolith raised from 80 → 90: Hangar is a massive physical structure;
    //     higher Rg build cost offsets the lower supply cost.
    //   - supply_cost lowered from 6 → 4: ships carry no supply cost (2026-06-08
    //     design decision); the hangar shell itself is the cheapest-to-run path
    //     building, making it the "supply-friendly, Rg-heavy" option.
    //   Trade-off character: supply-light long-term, expensive to build (Rg 90).
    //   Level-up Rg cost is the flat rate (25), same as every other non-CC building.
    // Building it grants the matching generic advisor slot (Raumfahrer) — see
    // AdvisorController::PATH_BUILDINGS.
    'hangar' => [                       // replaces civilianSpaceyard + militarySpaceyard
        'id' => 44,      // ex civilianSpaceyard — 1 hangar = 1 ship slot
        // 120 → 95 (GDD Balance-Audit G4, 2026-08-11): all three path buildings now cost
        // the same 95 Rg — at 120 this was the only one to miss the 5-8-Sole G4 corridor
        // at the cycle-mean 12.9 Rg/Sol income (9.3 Sole). Path parity via equal cost now
        // (GDD §4b Paritäts-Anforderung), superseding the 2026-08-03 trade-off rationale.
        'build_cost' => [3 => 95],
        // 4 → 6 (Owner, 2026-08-11, provisorisch): gleicher supply_cost wie Sciencelab/Bar,
        // damit alle drei Pfadgebäude auch hier gleichauf sind für weitere Playtests —
        // löst den G4-Fix-Kollateraleffekt (Hangar war sonst auf beiden Achsen günstigster
        // Pfad). Keine vollständige Neuherleitung, nur Testkonvenienz.
        'supply_cost' => 6,
        'trust_per_lv' => 0,
        // Klasse "Standard" (GDD §13.7, 2026-08-03): 33 Sole bis Level-Down.
        'decay_rate' => 0.60,
        'max_status_points' => 20,
        // max_level = 3 (GDD §4c "Hangar: der einzige Fall mit beiden Achsen", 2026-08-03):
        // Level ist die Schiffsklasse (Lv1 Drohne, Lv2 Frachter, Lv3 Korvette) — der Techtree
        // gatet Schiffe bereits so, das Feld holt nur nach. Instanzen bleiben die primäre
        // Wachstumsachse (Schiffsplätze), unverändert unbegrenzt/supply-limitiert.
        'max_level' => 3,
        // Ausbaustufen-Beinamen (2026-08-25) — jede Stufe schaltet eine neue
        // Schiffsklasse frei (echter Fähigkeits-Sprung), siehe lang/de/techtree.php
        // tier_hangar_1/2/3.
        'tiers' => [1, 2, 3],
        'max_instances' => null,
    ],

    // ── Civil welfare ─────────────────────────────────────────────────────────

    'infirmary' => [
        'id' => 46,
        'build_cost' => [3 => 60, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 10,
        'trust_per_lv' => 3,
        // Klasse "Beansprucht" (GDD §13.7, 2026-08-03): 25 Sole bis Level-Down.
        'decay_rate' => 0.80,
        'max_status_points' => 20,
        // Gedeckelt auf 3 (2026-08-25) — Absicht ist, dass Stufe 3 den
        // plague_risk_reduction_cap trifft, damit keine Überinvestition über den
        // Wirkungsdeckel hinaus möglich ist. Aktuell (3 × 0.08 = 0.24) erreicht das
        // den Cap (0.50) noch NICHT exakt — Zahlen-Kalibrierung ist ein separater
        // Balance-Task nach Playtest (ADR 0004, Spec Folge-Task 3), nicht Teil
        // dieses Struktur-Plans.
        'max_level' => 3,
        'tiers' => [3],
        // Reduces Seuchenausbruch trigger chance (GDD §9) — flat per-level, capped.
        // Mirrors decay_rate's own flat-per-level-times-multiplier style rather than
        // a production_curve-style per-level table.
        'plague_risk_reduction_pct_per_level' => 0.08,
        'plague_risk_reduction_cap' => 0.50,
    ],

    // Cantina (bar) — CC Lv2, one of three parallel "path" buildings
    // (sciencelab/hangar/bar), see GDD §13 "Pfadwahl ab Sol 3". Building it
    // grants the matching generic advisor slot (Konsul) — see
    // AdvisorController::PATH_BUILDINGS.
    //
    // Rebalanced 2026-06-28 (GDD §4 Pfadwahl-Kostenbalancing):
    //   - Regolith raised from 50 → 70: was far cheaper than the other two paths,
    //     making Cantina the dominant early choice without any real trade-off.
    //     The trust_per_lv bonus (+2/level, unique among path buildings) justifies
    //     a mid-tier build cost.
    //   - supply_cost raised from 4 → 6: balances the trust advantage; Cantina is
    //     now the "balanced" path (mid Rg, mid supply, Trust bonus) rather than
    //     the unambiguous cheapest option.
    //   Trade-off character: balanced cost with Trust bonus — the "always-valid"
    //   option for players who need Trust stability.
    //   Level-up Rg cost is the flat rate (25), same as every other non-CC building.
    'bar' => [
        'id' => 52,
        // 70 → 95 (GDD §13.7 Sol-1-4-Rampe, 2026-08-03): Pfad-Parität mit Sciencelab —
        // s. Kommentar dort.
        'build_cost' => [3 => 95],
        // 6 (unverändert seit 2026-06-28) — jetzt zugleich der gemeinsame supply_cost
        // aller drei Pfadgebäude (Owner, 2026-08-11, provisorisch, s. Hangar/Sciencelab).
        'supply_cost' => 6,
        'trust_per_lv' => 2,       // social hub — leisure in an otherwise bleak colony life
        // Klasse "Beansprucht" (GDD §13.7, 2026-08-03): 25 Sole bis Level-Down.
        'decay_rate' => 0.80,
        'max_status_points' => 20,
        // Gedeckelt auf 3 (2026-08-25, Ausbaustufen-Umstellung) — reine
        // Mengensteigerung (Angebotszahl/-dauer), kein Fähigkeits-Sprung, daher
        // keine Beinamen.
        'max_level' => 3,
    ],

    'monument' => [
        'id' => 50,
        'build_cost' => [3 => 60, 4 => 25],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 2,
        'trust_per_lv' => 2,
        // Klasse "Robust" (GDD §13.7, 2026-08-03): 50 Sole bis Level-Down.
        'decay_rate' => 0.40,
        'max_status_points' => 20,
        // 1 Instanz, Lv1 (GDD §4c Zuordnungstabelle, 2026-08-03): "Ein Denkmal, fertig
        // oder nicht" — weder Instanz- noch Level-Wachstum. is_instanced bleibt false
        // (eine Kopie), max_level=1 kappt das bisher unbegrenzte Hochleveln.
        'max_level' => 1,
    ],

    'temple' => [
        'id' => 32,
        'build_cost' => [3 => 50, 4 => 15],   // late: Regolith + Werkstoffe (accent)
        'supply_cost' => 4,
        'trust_per_lv' => 2,
        // Klasse "Fragil" (GDD §13.7, 2026-08-03): 17 Sole bis Level-Down — bewusst der
        // teuerste Unterhalt im Spiel, sie zahlt in Vertrauen statt Funktion.
        'decay_rate' => 1.20,
        'max_status_points' => 20,
        // 1 Instanz, Lv1 (GDD §4c, 2026-08-03) — "ein Bekenntnis, kein Ausbauprojekt".
        'max_level' => 1,
    ],

    // ── Phase 3g — implementiert (Mai 2026) ──────────────────────────────────

    // Security Hub — CC Lv3, max 1 instance (is_instanced=0).
    // Gate raised from CC Lv2 → Lv3 (2026-06-28): Hub is the prerequisite for the
    // Stratege advisor slot (Slot 5), analogous to the three path buildings (Pfad
    // A/B/C) that open Slots 2–4. Not part of the Pfadwahl build-gate group.
    //
    // Effects:
    //   1. trust_per_lv = 1: passive trust bonus per level (see GDD §4).
    //   2. Event mitigation: negative trust events (building_level_down,
    //      encounter_lost, colony_threatened) reduced by 25% when Hub active
    //      (TrustService::eventContribution()).
    //   3. recycle_pct: on building level-down by decay, return 10% of build
    //      cost in tradeable resources (GameTick — partially implemented).
    //
    // Former defend-order discount removed with the fleet/galaxy layer 2026-06.
    // TODO Balance: trust_per_lv, event_mitigation_pct, recycle_pct, supply_cost
    //               all to be calibrated after first playtest.
    'securityHub' => [
        'id' => 53,
        'build_cost' => [3 => 80, 4 => 25],   // Regolith + Werkstoffe (Compounds gate accepted — see GDD §4)
        'supply_cost' => 8,
        'trust_per_lv' => 1,                   // +1 trust per level (Lv3 max = +3)
        // Klasse "Standard" (GDD §13.7, 2026-08-03): 33 Sole bis Level-Down.
        'decay_rate' => 0.60,
        'max_status_points' => 20,
        'max_level' => 3,
        // Ausbaustufen-Beiname nur bei Stufe 3 (Recycling-Effekt, aktuell nur
        // konfiguriert — siehe securityHub Folge-Plan zum Verdrahten von
        // recycle_pct) — Stufen 1/2 sind reine Trust-Bonus-Mengensteigerung.
        'tiers' => [3],
        'recycle_pct' => 0.10,                 // fraction of build cost returned on level-down
        'event_mitigation_pct' => 0.25,        // 25% reduction on encounter/decay trust penalties (TrustService::eventContribution())
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
        // Klasse "Standard" (GDD §13.7, 2026-08-03): 33 Sole bis Level-Down.
        'decay_rate' => 0.60,
        'max_status_points' => 20,
        'max_level' => 3,
        // Nur Stufe 1 hat einen Beiname (schaltet Nexus-Bestellungen überhaupt
        // erst frei — echter Fähigkeits-Sprung). Stufe 2 ist Mengensteigerung
        // (Scankosten -1 AP). Stufe 3 ist zurückgestellt (Design-Spec Punkt "Neue
        // Mechaniken" — braucht einen eigenen Meta-Progressions-Design-Sprint).
        'tiers' => [1],
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
        // Klasse "Standard" (GDD §13.7, 2026-08-03): 33 Sole bis Level-Down.
        'decay_rate' => 0.60,
        'max_status_points' => 20,
        'max_level' => 3,
        // Alle 3 Stufen benannt — jede schaltet einen neuen Rabatt-Kanal frei
        // (Cantina → Reisender Händler → Nexus/Corporate Contact), echter
        // Fähigkeits-Sprung pro Stufe (Design-Spec, Abschnitt "Handelsposten").
        'tiers' => [1, 2, 3],
        'merchant_price_bonus' => 0.12,    // +12% trade value when Reisender Händler present
    ],

];
