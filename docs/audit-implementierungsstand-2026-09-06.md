# Audit: Implementierungsstand vs. GDD / ROADMAP / CHANGELOG

**Stand:** 2026-09-06 · **Basis:** `master` @ a125c06 (PR #311) · **Zweck:** Abweichungen sammeln als Diskussionsgrundlage für Tasks. Keine Entscheidungen, keine Code-Änderungen.

**Quellen:** `docs/GDD.md` (3640 Zeilen) inkl. `docs/gdd/*.md`, `ROADMAP.md` (Stand 2026-08-16), `CHANGELOG.md` (bis 2026-09-06), `docs/gdd-config-audit.md` (Stand 08-21), `docs/gdd-balance-checklist.md`, `docs/game-reference.md` (Stand 08-21), `docs/handoff-ap-ratenmodell.md`, `docs/superpowers/specs/*`, `CLAUDE.md`, `config/*.php`, `app/**`, `routes/web.php`, `database/migrations`. Eine externe Memory-Datei existiert in dieser Umgebung nicht — als „Memory" wurde ausschließlich `CLAUDE.md` herangezogen.

**Methode:** Für jedes Kapitel des GDD wurde geprüft, ob die beschriebene Mechanik in Config/Code existiert, und umgekehrt, ob als „offen" markierte Punkte in ROADMAP/Anhängen inzwischen umgesetzt sind. Tests wurden nicht ausgeführt — `composer install` scheiterte im Audit-Container an der GitHub-Authentifizierung (kein `vendor/`); letzter dokumentierter Stand laut CHANGELOG: Suite grün (~1147 Testmethoden). Vor Task-Start lokal `bin/phpunit` laufen lassen.

Gliederung: **A** = Design vorhanden, Code fehlt · **B** = Code vorhanden, Doku sagt „offen" · **C** = Doku widerspricht Code inhaltlich · **D** = Doku-Hygiene (veraltete Status-/Referenzangaben) · **E** = Diskussionsfragen / Task-Vorschlag.

---

## A. Design beschrieben, Implementierung fehlt

| # | Thema | GDD/ROADMAP | Befund Code | Gewicht |
|---|---|---|---|---|
| A1 | **Kommandozentrale-Dashboard** (§13.4, ROADMAP 3o Stufe 4) | „tragende Voraussetzung des Ratenmodells": AP-Zufluss/-Verwendung, Restzeit je Baustelle, Instandhaltungsanteil, Restertrag bis Run-Ende, Regolith-Bilanz, Over-Cap-Warnung, Konzessions-Prognose | `CommandCenterController` liefert Run-Fortschritt, Wartungsstau (SP-Liste), Berater, Trust-Events, Nexus-Kredit-Balken, Uplink/Import-Widget. **Fehlt:** Restzeit, AP-Bilanz, Regolith-Bilanz, Restertrag, Prognosen | Hoch |
| A2 | **Bonusquellen Berater-Rang + Koloniereife** (§13.3, Stufe 3) | additive Kostenreduktion aus Rang, Kenntnis, CC-Level; Verweis auf `config('game.project_cost_bonus')` | Nur Kenntnis-Rabatt (`ProjectBonusService`). `project_cost_bonus` existiert nicht in `config/game.php`; `project_min_cost_factor` ungenutzt-wirksam (max. 30 % Rabatt) | Mittel |
| A3 | **f(L)-Kostenkurve** statt flacher `ap_for_levelup`, `f(1)=0.5` (§13.6, Stufe 3) | freigegeben 08-03 | `ap_for_levelup` weiterhin flach aus DB; Errichtung kostet nicht die halben Kosten | Mittel |
| A4 | `decay.overcap_factor` 2.0 → 1.5 (§13.1 „Zu ändern", Stufe 3) | Owner-Vorgabe | Config steht auf **2.0** | Klein |
| A5 | Handlungs-AP nachziehen: `bar.ap_cost_accept` 1→2, `ap_cost_negotiate` 3→4 (Stufe 3) | | Config **1 / 3** | Klein |
| A6 | **Trust-Warnstufen** (§18.2, §18.6) | < 0 Kolonist-Event, < −10 roter Chip, < −18 Nexus-Warnung | Nur `onboarding_trust` (< 0, einmalig). −10/−18 nicht gefunden | Mittel |
| A7 | **Nexus-Trigger-Tabelle / Milestones** (§15, §18.2) | Sol 30/50/85/90; Sol-85-Sanktion **verkürzt Frist auf 95**; Sol-90-Letzte-Warnung; `config → run.nexus_triggers` | Code hartkodiert 30/50/65/80 (Phase-2-Sol). `config('game.run.nexus_milestones')` (30/50/85/90) wird **nirgends gelesen** — toter Block mit falschen Werten. Fristverkürzung + Sol-90-Warnung nicht implementiert | Mittel |
| A8 | **Nexus-Schulden: Rückzahlung + 95 %-Warnung** (§15, §18.2, §18.6) | manuelle Rückzahlung über Nexus-UI; INNN-Meldung bei > 95 % | Akkumulation ✓ (Start 3000, Nexus-Kredit-Schiffskauf). Rückzahlung ✗, 95 %-Meldung ✗ (nur Balkenfarbe) | Mittel |
| A9 | **Nexus-Boni** ahead-of-curve (§15) | Credits-Zulage, AP-Boost 3 Sole, Zielwert-Abschlag | Nicht implementiert; nur Warnungen/Sanktion | Niedrig (Design-Frage offen, A.4) |
| A10 | **Roguelike-Kenntnis-Teilmenge** pro Run (§10 „5 von 7") | TODO Implementierung; §8b begründet „Roguelike-Varianz gratis" damit | Nicht implementiert — alle 7 Kenntnisse jeden Run | Mittel (Design hängt daran) |
| A11 | **Uplink-Station Lv2/Lv3-Effekte** (§4) | Lv2: Tiefenscan „1 Sol weniger" + Händler häufiger; Lv3: Kolonialbericht → Meta-Bonus; Lv1: „Handelsschiff anfordern, Verwaltungsanfragen" | Lv2: Scan-**AP** 2→1 (nicht Dauer), `MerchantService` kennt Uplink nicht. Lv3: nichts. Lv1: nur Werkstoff-Import | Mittel |
| A12 | **Kanal 2 Nexus-Handelsschiffe** (§12) | INNN-Anfrage, Credits einfrieren, Lieferung nach 1–3 Solen, max. 1 offen | Nicht implementiert; beschreibt zudem das entfernte INNN-System. Werkstoff-Direktimport deckt Teilfunktion ab | Entscheidung: streichen oder als Direktimport umdefinieren |
| A13 | **Handelsposten „Konsul-Effizienz"** (§4, AP-Rabatt auf Trade-Orders) | GDD hat selbst TODO-Kommentar | `TradingPostService` nur Kanal-Rabatt, kein AP-Effekt | Klein (Text streichen?) |
| A14 | **Notreparatur** CC/Wohnhabitat (§7, Credits automatisch) | als Regel formuliert, ohne Status | Kein Code | Klein |
| A15 | **Kolonisten-Framing** für Supply (§6, „vorgezogen 2026-08-02") | „47 Kolonisten im Einsatz / 60 verfügbar" | Keine Lang-Keys, UI zeigt „Supply" | Klein |
| A16 | **Harvester Konstant-Yield-Spec** (`specs/2026-08-10-harvester-constant-yield-design.md`, Owner-approved) | konstante Rate bis Tile leer | Code + GDD §4c: Erschöpfungs**kurve** (0,5-Boden) — Spec nie umgesetzt, GDD §13.7 nennt sie „nicht implementiert" | Entscheidung: Spec zurückziehen oder nachholen |
| A17 | **Playtest-Instrumentierung** A.5 (11 Metriken) | `docs/playtest-instrumentation-plan.md` wartet auf Owner-Entscheidung | 6/11 in `RunReport` | Mittel (blockiert Kalibrierung) |
| A18 | `mission_perimeter_patrol` (§8b-Katalog) | „zurückgestellt bis §9 implementiert" — §9 ist seit 08-16 implementiert | Fehlt in `config/missions.php` (13 Missionen, `harvester_salvage` drin, Patrol nicht) | Klein |
| A19 | **Berater-Burnout-Formel**, **Berater-Außenmissionen**, **Berater als Informationsebene**, **Progressive Discovery** | GDD markiert alle als Phase 4+ | Nicht implementiert — konsistent dokumentiert, kein Drift | — |

---

## B. Implementiert, aber Doku führt es als „offen"

| # | Dokument | Stale-Aussage | Tatsächlich |
|---|---|---|---|
| B1 | GDD §18.6 „Offene Implementierungsaufgaben" | `checkWinCondition()` offen; `nexus_debt`-Migration offen; Run-Ende-Screen offen; Config-Key `nexus_debt_limit` offen | Sieg-Check in `GameTick` ✓, Spalte ✓, `RunResultController` ✓, `run.nexus_debt_fail_threshold` ✓. Offen bleiben nur A6/A8 |
| B2 | `docs/gdd-config-audit.md` (08-21) | 6 Prio-Punkte + „Bereits bekannt" (testdata-Drift, `max_level=NULL` bei 7 Gebäuden) | Laut CHANGELOG 08-21/08-22/08-26 **alles behoben**. Datei ist komplett veraltet |
| B3 | `docs/gdd-balance-checklist.md` A.1 | „`max_level` aufteilen — Umsetzung offen — blockiert"; „Instanz-Decay-Verdacht — offen, blockierend" | Beides erledigt (PR #234, Stufe 1c) |
| B4 | `gdd-balance-checklist.md` A.4 | Level-Deckel Cantina/Krankenstation NULL; `max_level=NULL` bei 7 Gebäuden | Seit 08-26: alle 13 Gebäude haben `max_level` (3/3/5/3/3/1/1/3/3/3) |
| B5 | ROADMAP Stufe 1b | „[ ] Harvester-Erschöpfung" | `GameTick` Depletion-Mechanik ✓ (Kurve, `resource_amount`, Onboarding-Hint 09-06) |
| B6 | ROADMAP Stufe 1d / Stufe 1 | „[ ] Level-Deckel Cantina/Krankenstation"; „Religiöse Stätte/Kolonialdenkmal je 1 Instanz/Lv1 offen" | Erledigt 08-26 (Tier-System) |
| B7 | ROADMAP Balance-Checkliste | „`hint_2` → genereller Erschöpfungs-Alert noch nicht umgesetzt" | 09-06: `hint_harvester_low_regolith` — prüfen, ob `hint_2` damit obsolet |
| B8 | ROADMAP Phase 3b | „[ ] Ingame-Almanach" | `NexusDbController` / `/nexus-db` existiert (Almanach-Stimme). Abhaken oder Restumfang definieren |
| B9 | ROADMAP Phase 3i | „9 Objective-Typen inkl. `task_combat_record`" | Pool hat 8, Combat entfernt |
| B10 | `docs/handoff-ap-ratenmodell.md` | „Status: nichts ist implementiert" (08-03) | Stufen 0/1/1c/2 abgeschlossen — Dokument irreführend, archivieren |
| B11 | GDD §15 Aufgabenpool | „Kombo-Blacklist noch nicht implementiert" | `RunProgressService::TASK_CATEGORIES` + Blacklist (max. 1 Economy) ✓ |
| B12 | GDD §9 „Umsetzungslücke Sol-Report" (09-03) | Encounter-Ausgänge nicht im Sol-Report | `SolReportService` kennt `encounter.storm_resolved` (09-03). **Instabilität/Seuche** noch nicht → Lücke nur teilweise geschlossen |
| B13 | `docs/gdd/entity-chips.md` | „Status: Design-Entwurf (2026-06-07)" | Implementiert (Phase 3j/3k, ADR 0002) |
| B14 | `config/game.php:498` Kommentar zu `knowledge_cc_level_cap` | „Enforcement … not yet implemented" | `ResearchService::levelupBlocker()` erzwingt es. GDD §4c-Nachtrag 09-05 zitiert den veralteten Kommentar und ist dadurch **falsch** |

---

## C. Doku widerspricht Code inhaltlich

| # | Stelle | GDD sagt | Code macht | Klärung |
|---|---|---|---|---|
| C1 | §13.1 Deadlock-Tabelle | „Upkeep auf ≥ 0 geklemmt, der Verlust läuft über `nexus_debt`" | `GameTick::deductAdvisorUpkeep()` klemmt nur; **keine Schuldenbuchung** | Feature oder Textkorrektur? Beeinflusst Fail-State-2-Balance |
| C2 | §7 Konsequenzen-Tabelle | Wohnhabitat/Hangar bei SP ≤ 0: **Instanz zerstört**, Hangar-Schiff „unbrauchbar" | `processBuildingDecay()` setzt Level auf 0 (Ruine bleibt, Tile belegt), kein Schiffs-Handling | Welches Verhalten ist gewollt? |
| C3 | §4 Uplink Lv2 | „Tiefenscan dauert 1 Sol weniger" | Scan-**AP-Kosten** 2 → 1 (`ColonyTileService:80`) | Text an Code angleichen |
| C4 | §15 Phase-1-Bedingung / §18.4 | „mehrere Gebäude … + CC aufgestuft" bzw. „2 Produktionsgebäude Lv2" | CC ≥ 3, ≥ 2 **Nicht-CC**-Gebäude ≥ Lv2, 3 Berater | Owner-Entscheidung laut A.4 weiterhin offen |
| C5 | §15 Fail States / Gnadenfrist | Trust-Streak < 10; Sol 85 Sanktion + Frist auf 95; Sol 90 Warnung | Instant < −20; Phase-2-Sol 65 Sanktion, 80 Countdown; keine Fristverkürzung | §18.5-TODO seit 06-28 unerledigt (siehe A7) |
| C6 | §6 Supply-Cap-Quellen | Wohnhabitat „pro Einheit"; Config-Kommentar „max 6 units → 48" | Cap = Σ **Level** × 8 über alle Instanzen (`GameTick:781`); mit `max_level=3` × 6 Instanzen theoretisch 144 | Formel und Ziel-Cap nach Tier-System neu prüfen (Stufe 1d) |
| C7 | §8b Katalog-Tabelle | `mission_aid_transport` Gate „Gesundheit Lv1"; Lieferzeiten Drohne 1–2 / Frachter 3 / Korvette 5; `harvester_salvage` fehlt | ungegatet (seit 08-04); Lieferzeiten 1/2/3 (09-04); 13 Missionen | Tabelle nachziehen |
| C8 | §4 Gebäudetabelle | Max-Level „—" bei 9 Gebäuden; Wohnhabitat „6"; „11 aktive + 3 im Design" | alle 13 gedeckelt (s. B4); Wohnhabitat Level 3 / 6 Instanzen; alle 13 implementiert | Tabelle nachziehen |
| C9 | §4c Zuordnungstabelle | Agrardom „**Instanz**, Deckel offen"; Cantina/Krankenstation „offen"; Wohnhabitat „Instanz 6" | Agrardom: Level (max 3), **nicht instanziert**; Cantina/Krankenstation Lv3; Wohnhabitat Level 3 + 6 Instanzen | Owner-Entscheidung „Agrardom Level statt Instanz" ist faktisch gefallen (Tier-Spec 08-23) — GDD nachziehen oder Umstellung planen |
| C10 | §10 „bereits implementierte Effekte" | nennt construction/trade/cartography/agronomy-Trust | fehlt: `geology` (Harvester-Bonus + Instabilität), `agronomy` Organika, `health` Seuche, `defense` Sturm, `trade` Preisbonus, Analytik-Lv4/5-Rabatt | Liste vervollständigen (game-reference hat sie) |
| C11 | §13 „Implementierung" | `PersonellService.php`, `FleetService.php` | heißt `AdvisorService`; `FleetService` existiert nicht | Trivial |
| C12 | §14 Sol-Integration, §13, §8b | Tick-Schritt „6b", „7 Advisor Ticks" | real Schritt 9 / 12 (Docblock 08-18 neu nummeriert) | ROADMAP Stufe 6 kennt das, offen |
| C13 | §12/§14/§15/§18 durchgängig | „INNN-Feed", „INNN-Ereignis" (29 Nennungen) | INNN ersetzt durch Kolonieprotokoll + Nexus-Funk (`/comm-log`, Phase 3j) | Terminologie-Pass |
| C14 | §14 / §18.2 / §6 | konkrete Zahlen (+2/+3 Gebäude, Stipend 100/300/600, 12.000/9.600/11.400, −20/−18/−10, CC×10 + Wohn×8) | ADR-0004-Verstöße (Prosa ohne Zahlen) | Niedrig, im selben Pass |
| C15 | `docs/gdd/techtree.md` 11.1/11.3 | Max-Level „supply-limitiert" bei 8 Gebäuden; Zeile 166 `strategist` im Grid; Bio-Anlage-Gate nur „Harvester Lv1" | Deckel existieren; Stratege gestrichen; GDD §4 sagt CC Lv1 + Harvester | Nachziehen |

---

## D. Doku-Hygiene (Status-/Referenzangaben)

| # | Dokument | Befund |
|---|---|---|
| D1 | `CLAUDE.md` „Abgeschlossen" | listet **Flottenoperationen, INNN-Nachrichten, Systemkarte, Fleet Command Overlay, Kommandanten-Zuweisung (PR #139)** — alle 2026-06-20 bzw. mit Phase 3j entfernt. Kein Code mehr vorhanden (Routen/Services fehlen). „Stand 2026-08-22" |
| D2 | `ROADMAP.md` | Header „Stand 2026-08-16"; Phase 2 Prio 4 offene Checkboxen zu entfernten Features (Handelsrouten, PvP-Schiffskampf, Flash-Messenger); Phase 4 „Berater als Informationsebene" nennt Strategen; Phase 5 „Scout/Sonde" (Drohne = Sonde existiert). Siehe auch B5–B9 |
| D3 | `docs/game-reference.md` (08-21) | Gebäudetabelle: Wohnhabitat Max Level 6 (real 3), Agrardom 8 (real 3), Analytik ∞ (5), Krankenstation/Cantina ∞ (3), Zeile **„Lagerhalle"** (Depot seit 06-22 entfernt). Refresh nach Tier-System 08-26 fehlt — Pflicht-Checkliste in CLAUDE.md wurde hier nicht eingehalten |
| D4 | `docs/gdd/onboarding.md` | 21 Treffer auf „Bau-AP/Forschungs-AP/Navigations-AP/Wirtschafts-AP" — Spieltexte wurden 08-31/09-04 bereinigt, Doku nicht (Stufe 6) |
| D5 | `config/game.php` | `run.nexus_milestones` toter Block (A7); Kommentar `knowledge_cc_level_cap` falsch (B14); Kommentar `supply.cap_housingcomplex` „max 6 units → 48" passt nicht zur Level-Summe (C6) |
| D6 | `docs/gdd-config-audit.md` | siehe B2 — sollte durch dieses Audit ersetzt oder neu geschrieben werden |
| D7 | DB-Schema | `locked_actionpoints.personell_type` (A.2 offen), `user_preferences.sol_report_skip` (ungenutzt seit 09-05), `race_id` (ROADMAP Phase 4) — bekannte Reste, kein Drift, nur Sammelliste |

---

## E. Diskussionsfragen (Owner) und Task-Vorschlag

**Entscheidungen, die Tasks blockieren:**

1. **Agrardom: Level oder Instanz?** (C9) — Config ist Level/3, GDD §4c sagt Instanz. Bleibt Level → GDD-Korrektur; sonst Umstellung inkl. Instanz-Deckel (Stufe 1d).
2. **Instanz-Zerstörung bei Decay** (C2) — Ruine auf Level 0 (heute) oder Löschung + Schiff deaktivieren (GDD §7)?
3. **Upkeep-Defizit → Nexus-Schulden?** (C1) — Feature oder Text streichen. Hängt mit A8 (Rückzahlung) zusammen.
4. **Konstant-Yield-Spec** (A16) — zurückziehen oder umsetzen? Betrifft §13.7-Sockelrechnung.
5. **Kanal 2 Nexus-Handelsschiffe** (A12) — streichen zugunsten Werkstoff-Direktimport?
6. **Roguelike-Kenntnis-Teilmenge** (A10) — noch gewollt? §8b-Argumentation hängt daran.
7. **Phase-1-Bedingung** „Produktionsgebäude" vs. „Nicht-CC-Gebäude" (C4) — seit 08-13 offen.
8. **Playtest-Instrumentierung** (A17) — Plan freigeben?

**Task-Cluster (Vorschlag, nach Aufwand):**

- **T1 Doku-Konsolidierung (klein, sofort):** B1–B14, C3, C7, C8, C10–C15, D1–D6 in einem Pass; `gdd-config-audit.md` durch diese Datei ersetzen; ROADMAP-Header und CLAUDE.md-Stand aktualisieren; `game-reference.md` refresh.
- **T2 Config-Nachzügler (klein):** A4, A5, toten `nexus_milestones`-Block entfernen oder in Code verdrahten (A7), Kommentare D5.
- **T3 Nexus/Fail-State-Vervollständigung (mittel, TDD):** A6, A7 (Sol-90, Fristverkürzung), A8 (95 %-Warnung, Rückzahlung), abhängig von Frage 3.
- **T4 Ratenmodell Stufe 3 (mittel, TDD):** A2, A3 — f(L)-Kurve, Rang-/CC-Bonus, `project_cost_bonus`-Config.
- **T5 Kommandozentrale-Dashboard Stufe 4 (groß, UI + Service):** A1.
- **T6 Uplink-Effekte (mittel):** A11 — Lv2 Händler-Frequenz, Lv3 definieren oder streichen.
- **T7 Encounter-Reste (klein):** B12 (Instabilität/Seuche in Sol-Report), A18 (Patrol-Mission).
- **T8 Supply-Achse Stufe 1d (Design-Runde):** C6 + Frage 1, danach `game-reference` erneut.
