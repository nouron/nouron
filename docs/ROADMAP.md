# Nouron — Roadmap

Stand: 2026-08-02

Singleplayer Roguelike Mini-4X (FTL/Catan-Stil). Keine Rassen, keine Kolonisierung, ein Run hat ein konkretes Ziel + klares Ende.

---

## Abgeschlossen

### Phase 1 — ZF2 → Laminas + Bootstrap 5 Migration
Abgeschlossen. Codebase vollständig auf Laminas migriert.

### Phase 1b — Laminas → Laravel 12 Migration
Abgeschlossen. Läuft auf Laravel 12 + SQLite.

### Phase 2 — Kern-Mechaniken stabilisiert
Abgeschlossen: Tick-System, Supply-Cap, Decay, Trade-Routen, Flottenoperationen.

### Phase 3a — Supply-Cap-Rework + Colony-Zone-Fundament
Abgeschlossen.

### Phase 3b — Colony-UI (Alpine.js Hex-Grid, Fog of War, Tile-System)
Abgeschlossen.

### Phase 3c — Colony-Aktionen (Erkunden, Sondieren, Bauen)
Abgeschlossen.

### Phase 3d — Colony-Zonen-Erweiterung (Tile-Count-Unlock, 3-Ring-Karte, Instanced Buildings)
Abgeschlossen.

### Phase 3e — Onboarding / Neue-Spieler-Erfahrung
Abgeschlossen: Triggers + Hints-Bar implementiert. Kein dedizierter New-Player-Wizard.

### Phase 3f — Berater-Screen (Alpine.js + PicoCSS)
Abgeschlossen.

### Phase 3g — Run-System + Lobby/Runs-Übersicht
Abgeschlossen.

### Phase 3h — Hangar-Screen (Carousel-View, Nexus-Schiffsanfragen, Hangar-Missionslog)
Abgeschlossen.

### Phase 3i — jQuery-Entfernung (vollständig), NexusDB-Redesign, Cantina-Charakter-System
Abgeschlossen (Mai/Juni 2026).

### Phase 3j — Kolonieprotokoll (INNN-Redesign)
Abgeschlossen: INNN-System vollständig ersetzt durch `/comm-log` (Protokoll-Tab + Nexus-Funk-Tab). Rich Log Descriptions + `building_invested`-Event. DB: `colony_log`. 725 Tests grün.

### Phase 3k — Entity-Chip-System (ADR 0002)
Abgeschlossen: `<x-entity-chip>`-Komponente, `CommLogController::buildDescription()` liefert Segment-Array (kein Legacy-String, kein Migrationsbedarf), Comm-Log auf Chip-Rendering umgestellt, Chip-Stile je Typ (`resource`/`building`/`knowledge`/`ship`/`advisor`), Tooltip-Daten inline. Ausstehend: Integration in weitere Views (Berater-Screen, Kolonie-Screen) — spätere Iteration, siehe unten.

### Phase 3l — Cantina-Redesign
Abgeschlossen: Bar-Hintergrund (`cantina-interior.webp`) + NPC-Charaktere via `config('characters')` + Hotspot-Portraits.

### Phase 3m — content-writer-Tonalität + lang/en-Sync
Abgeschlossen (2026-07-01): Drei-Stimmen-System (Kolonie/Nexus-Direktiven/NexusDB-Almanach), alle `lang/de/`-Beschreibungstexte neu geschrieben, `lang/en/` vollständig synchronisiert (12 neue Dateien). Globales Sci-Fi-Dialog-System (`dialogs.css`, `sol-modal`).

### ADR 0003 — Multiplayer-Turn-Resolution (Architektur)
Abgeschlossen (2026-07-01): Architekturentscheidungen dokumentiert (`docs/adr/0003-simultan-turn-resolution-multiplayer.md`). Zwei Sofort-Maßnahmen gemergt: `runs.rng_seed`-Vorbereitung + Domain-Events (`RunStarted`/`SolAdvanced`/`RunEnded`). Rest (Games/TurnOrders/Resolution-Engine/KI) zurückgestellt bis Multiplayer aktiv angegangen wird — siehe Phase 4.

### Phase 3n — Playtest-Bot (automatisierte Balance-Messung)
Abgeschlossen (PR #217 Vorarbeiten + PR #218, 2026-07-17/18): PHPUnit-basierter Bot unter `tests/Feature/Playtest/` spielt komplette Runs ausschließlich über echte HTTP-Routen (`BotSession`, `BotStrategy`, `RunReport`-JSON-Artefakt unter `storage/logs/playtest/`). Eigene Suite `playtest` (aus `laravel-feature` ausgeschlossen). Deckte dabei mehrere echte Bugs auf (Session-Hard-Default Kolonie 1, ungeseedete Ziel-Ziehung, nicht persistierter Score, 200er statt 422 bei Colony-Fehlern u.a.) und legte den strukturellen Credits-Ökonomie-Kollaps nach Phase 1 offen — siehe folgende zwei Einträge.

### Credit-Ökonomie-Balance (2-Schritt-Ticket)
Abgeschlossen — Schritt 1, PR #219, gemergt (2026-07-19): Relaisvergütung Housing→Uplink-Station umgehängt, Advisor-Upkeep abgeflacht (`[10,50,160]`→`[10,30,80]` Cr/Tick), Rang-Schwellen gestreckt (`[10,20]`→`[15,45]` active_ticks), neue Handelsvertrag-Einkommensquelle (Konsul + Cantina). Playtest-Bot-Ergebnis: `phase2_start_sol` nie erreicht → Sol 49.

Abgeschlossen — Schritt 2, PR #220, gemergt (2026-07-20): Harvester/Agrardom-Grundproduktion von flacher Rate (`×10/Level`, unbegrenzte Level) auf Glockenkurve mit Deckel umgestellt (`config/game.php: production_curve`, `max_level=8` in `config/buildings.php`). Playtest-Bot-Ergebnis: `phase2_start_sol` 49 → 18. Ökonomie-Kollaps nach Phase 1 bleibt weiterhin offen — siehe „Laufend" unten.

---

## Laufend — Phase 3o: AP-Ratenmodell (Umsetzungsplan, 2026-08-02)

Design steht im GDD (§4b, §6, §13.1–13.6, Anhang A/B), gemergt mit PR #231. **Nichts davon ist implementiert.** Dieser Abschnitt ist der Umsetzungsplan.

TDD ist verbindlich (CLAUDE.md): für jede Stufe mit Verhalten zuerst ein fehlschlagender Test, der das gewünschte Verhalten beschreibt.

### Der kritische Pfad ist nicht der AP-Umbau

Die Pfad-Parität (§4b) verlangt, dass jeder der drei Pfade einen eigenen Regolith-Hebel mit vergleichbarem Ertrag hat. **Alle drei Hebel hängen an Punkten, die seit dem 2026-07-20 offen unter „Brainstorming: Kenntnisse/Hangar/Cantina-Pfad-Parität" stehen** — die neue Design-Runde hat sie nicht gelöst, sondern präzisiert und verschärft:

| Pfad | Hebel laut §4b | Bestehender offener Punkt |
|---|---|---|
| A — Analytik | `geology` → Regolith-Produktionsbonus | „Kenntnisse-Sekundäreffekt-Matrix ausfüllen — **keine Ressourcen-/AP-Boni**" |
| B — Hangar | Frachter auf `mission_supply_run` | „Hangar-Missionsnutzbarkeit — Bot/Spieler kommt praktisch nie zum Freighter-Kauf" |
| C — Cantina | Credits→Regolith-Ankauf | „Bar/Cantina ‚Not enough resources.' — kein nutzbarer reiner Credits-Einkommenstyp" |

Solange diese drei offen sind, existiert **kein** funktionierender Pfad-Hebel. Der Harvester-Umbau (`max_level = 1`, §13.5) darf deshalb nicht vorher kommen — er nimmt die einzige heute funktionierende Regolith-Skalierung weg.

Die Design-Entscheidung vom 2026-07-20 (Analytiker = passiver Multiplikator, Pilot = aktive Burst-Beschaffung, Konsul = aktive Konversion) bleibt gültig; §4b macht sie explizit und ergänzt die Zahlen-Zielgröße (~6 Rg/Sol je Hebel).

### Stufe 0 — Klären (Owner, keine Implementierung)

- [x] `ap_for_levelup` in der laufenden DB verifiziert (2026-08-02): **überall 10**, nur Monument 20. Die Migration `2026_04_17_000003` (10/20/30) ist nicht aktiv. Damit stimmt die Onboarding-Budgetrechnung — der Wert ist aber ein Default, kein Balancing, und bei der Herleitung frei wählbar.
- [ ] **`harvester.max_level` angleichen, bevor jemand `game:sync-config` ausführt.** DB und Testfixture haben 1, `config/buildings.php` hat 8; der Sync würde die Config in die DB schreiben und den Harvester still zurücksetzen. Nebenfolge: Die Glockenkurve aus PR #220 ist für den Harvester wirkungslos — bei `max_level = 1` greift nur `production_curve[27][3][1]`.
- [ ] Zahlenvorschlag §13.6 freigeben oder anpassen (Grundwert 10, Berater 2/3/4, `f(1)=0.5`, Boni max 42 %)
- [ ] Höhe des `geology`-Regolith-Bonus festlegen (Vorschlag +1,5/Level)
- [ ] Entscheiden, ob Pfad A eine Credits-Antwort braucht (§4b, zweite Paritätslücke)

### Stufe 1 — Pfad-Hebel funktionsfähig machen (Voraussetzung für alles Weitere)

Reihenfolge innerhalb der Stufe beliebig, aber alle drei vor Stufe 4.

- [ ] **Kenntnisse-Sekundäreffekte**: Effekt-Infrastruktur bauen (es gibt bisher keinen einzigen Ressourcen-/Produktionsbonus in `config/knowledge.php`), `geology` → Harvester-Ausbeute als erste Anwendung
- [ ] **Hangar-Erreichbarkeit**: Frachter muss im realen Spielverlauf erreichbar sein, sonst existiert Pfad B's Hebel nur auf dem Papier
- [ ] **Cantina-Ankaufskanal**: verlässlicher Credits→Regolith-Weg, nicht nur die Chance auf ein passendes Zufallsangebot
- [ ] Post-Phase-1-Ökonomie-Erholung (bestehender offener Punkt, beeinflusst alle drei)

### Stufe 2 — AP-Pool zusammenlegen (§13.1)

Kernumbau. `ap_spend` existiert bereits auf `colony_buildings`, `colony_research` und `colony_ships` — die **Projekt-Investition über mehrere Sole funktioniert also schon**, sie ist nur typgebunden.

- [ ] Tests zuerst: ein Pool, Berater aller Domänen zahlen ein, Locks verfallen zum Sol-Wechsel
- [ ] `PersonellService` entkoppeln — `getTotalActionPoints(type, …)`, `getAvailableActionPoints(type, …)`, `lockActionPoints(type, …)`, `getConstructionPoints`/`getResearchPoints`/`getEconomyPoints`/`getStrategyPoints`, `creditApToType`, `resolveType`
- [ ] Callsites: `AbstractTechnologyService`, `FleetService`, `BarService`, `HangarService`, `ColonyTileService`
- [ ] Migration: `advisors.personell_type`-Enum ohne `strategy`; Entscheidung zu `locked_actionpoints.personell_type` (Pool-Trennung entfällt — als Auswertungsmerkmal behalten oder streichen)
- [ ] `config/game.php`: `ap.base`, `advisor.ap_per_rank`; `config/advisors.php`: `strategist` entfernen
- [ ] UI: AP-Chips, Ressourcenleiste, Berater-Screen auf einen Pool

### Stufe 3 — Ratenmodell vervollständigen (§13.2–13.3, §13.6)

- [ ] `f(L)`-Kostenkurve statt flacher `ap_for_levelup` je Level; `f(1) = 0.5` fürs Errichten
- [ ] Bonus-System: additive Kostenreduktion aus Berater-Rang, Domänen-Kenntnis, Koloniereife; `project_min_cost_factor` als Leitplanke
- [ ] Restzeit-Berechnung je Baustelle („noch 3 Sole bei aktueller Rate")
- [ ] Handlungs-AP nachziehen: `bar.ap_cost_accept` 1→2, `ap_cost_negotiate` 3→4
- [ ] `decay.overcap_factor` 2.0 → 1.5

### Stufe 4 — Regolith-Ökonomie umstellen (§13.5)

**Nur gemeinsam ausliefern.** Einzeln bricht jeder Teil die Wirtschaft.

- [ ] `harvester.max_level` 8 → 1
- [ ] `geology`-Produktionsbonus aktiv (aus Stufe 1)
- [ ] Cantina-Ankaufskanal aktiv (aus Stufe 1)
- [ ] Frachter real erreichbar (aus Stufe 1)
- [ ] Regolith-Bilanz gegen die Reparaturkurve gegenprüfen: 8 Rg/Sol Sockel gegen 5,3 / 8,7 / 11,9 / 20,6 Rg/Sol bei 4 / 6 / 8 / 13 Gebäudetypen

### Stufe 5 — Kommandozentrale-Dashboard (§13.4)

Tragende Voraussetzung des Ratenmodells, kein Komfort — es ersetzt die bewusst weggelassene Bodengarantie.

- [ ] AP-Zufluss und Verwendung, Restzeit je Baustelle, Instandhaltungsanteil
- [ ] Restertrag bis Run-Ende je Projekt (trägt den Late-Game-Kipppunkt ohne Zahlenänderung)
- [ ] Regolith-Bilanz, Over-Cap-Warnung, Konzessions-Prognose, Run-Aufgaben-Fortschritt

### Stufe 6 — Instrumentierung, Playtest, Kalibrierung

Der Playtest-Bot (Phase 3n) ist die Messumgebung.

- [ ] Die neun Metriken aus GDD Anhang A.5 in `RunReport` aufnehmen
- [ ] Determinismus-Bug `ColonyTileService::randomizeOuterRingRows()` beheben — ohne reproduzierbare Läufe ist Kalibrierung wertlos
- [ ] Bot-Läufe, dann §13.6-Zahlen gegen die Zielkorridore nachziehen

### Stufe 7 — Nachzieharbeiten

Kein Blocker, aber Teil der Definition-of-Done.

- [ ] Onboarding-Hinweistexte und Sol-1–4-Budgetrechnung (`gdd/onboarding.md` §16.2/§16.5) auf einen Pool und die neuen Grundwerte
- [ ] Außenmissions-AP-Staffel (§8b) gegen den neuen Pool neu kalibrieren
- [ ] Drifts aus GDD Anhang B abarbeiten (CC-Upgrade-Regolith, Decay-Richtwerte, `supply.ship_cost`, Kommentare in `knowledge.php`/`advisors.php`, `testdata.sqlite.sql`)
- [ ] `ResetPlayer.php`: hartcodierte `supply`/`regolith`-Werte in allen fünf Szenarien nachziehen

---

## Laufend — Phase 3 (UI-Migration + Feature-Finish)

### Brainstorming: Kenntnisse/Hangar/Cantina-Pfad-Parität (mit Owner, 2026-07-20)

Design-Entscheidung getroffen, konkrete Zahlen noch offen: die drei Berater-Pfade behalten unterschiedliche, aber gleichwertige Wirkweisen statt eines neuen Subsystems — Analytiker/Kenntnisse = passiver Multiplikator, Pilot/Hangar = aktive Burst-Beschaffung (Missionen), Konsul/Cantina = aktive Konversion (Handel). Guard-Rail: Grundproduktion muss für sich allein "knapp, aber machbar" sein, bevor irgendein Pfad-Bonus draufkommt.

> **Fortgeführt 2026-08-02 (GDD §4b, PR #231).** Diese Entscheidung ist unverändert gültig und jetzt im GDD ausformuliert — inklusive Zielgröße (~6 Rg/Sol je Pfad-Hebel) und einer Prüfregel für künftige Mechaniken. Die vier Punkte unten sind damit **von „offen" zu „blockierend" geworden**: Der Harvester-Umbau (`max_level = 1`, GDD §13.5) nimmt die einzige heute funktionierende Regolith-Skalierung weg, und die drei Pfad-Hebel, die sie ersetzen sollen, hängen genau an diesen Punkten. Siehe Phase 3o, Stufe 1.

Nächste Schritte, alle noch offen — **jetzt blockierend für Phase 3o**:

- [ ] Bar/Cantina „Not enough resources." — **neu diagnostiziert 2026-08-02, die Ursache ist eine andere als vermutet.** Gegen `BarService::buildOffer()` geprüft: Die Credits→Ressource-**Kauf**richtung existiert und ist mit 60 % der Regelfall; die **Verkaufs**richtung existiert überhaupt nicht (der Code-Kommentar in Z. 305 sagt das Gegenteil des Codes darunter). Das Fehlschlagen kommt von den **Losgrößen**: `rand(1,5) × 10` Einheiten ergibt ~1.400 Cr Erwartungswert je Angebot gegen +5 Cr/Sol Netto-Einkommen. Keine kaputte Prüfung, eine Fehlkalibrierung um eine Größenordnung.
  - [ ] Losgröße an die Zahlungsfähigkeit binden (höchstens ~35 % des Bestands)
  - [ ] Tauschrichtung nach Bestand wählen statt würfeln (Give = größter Überschuss, Get = knappste Ressource) — damit wird der **Tausch** statt des Credits-Kaufs zum Pfad-C-Hebel und umgeht die kaputte Credits-Ökonomie
  - [ ] Verkaufsrichtung als dritter Angebotstyp — **eigene Owner-Entscheidung**, revidiert die Einführung des Handelsvertrags vom 2026-07-19 teilweise. Betrifft zugleich die Post-Phase-1-Ökonomie: Organika ist der einzige strukturelle Überschuss (~14/Sol bei der Zielkolonie) und aktuell nicht monetarisierbar.
- [ ] Post-Phase-1-Ökonomie-Erholung (Kollaps bei mehreren Rang-2/3-Beratern gleichzeitig)
- [ ] Kenntnisse-Sekundäreffekt-Matrix ausfüllen (GDD §10, aktuell nur 6 von 35 Kombinationen als Platzhalter, keine Ressourcen-/AP-Boni)
- [ ] Hangar-Missionsnutzbarkeit — **neu diagnostiziert 2026-08-02, die ursprüngliche Formulierung war ein Messartefakt.** `BotStrategy` kauft hartkodiert eine Drohne (`ship_id => 85`), deckelt auf genau ein Schiff (`! hasAnyShip`) und heuert den Raumfahrer nie an (`HIRE_ORDER = [35, 36, 92]`) — der Bot *kann* keinen Frachter kaufen. Die echten Ursachen sind andere:
  - [ ] **Bot-Fix zuerst** (sonst ist der Vorher-Zustand nicht messbar): Schiffstyp aus dem verfügbaren Missionskatalog ableiten statt hartkodieren, Schiffs-Deckel an freie Slots binden, `HIRE_ORDER` um den Raumfahrer ergänzen und pfadabhängig machen
  - [ ] **Zweite Hangar-Instanz kostet den vollen `build_cost` (80 Rg)** statt der 25 % Level-Up-Kosten (`ColonyController::placeBuilding`) — und wird mit Regolith bezahlt, genau der Ressource, die der Frachter beschaffen soll. Bootstrap-Zirkel, den `harvester.max_level = 1` verschärft. Betrifft ebenso Wohnhabitat-Instanzen.
  - [ ] Drohne hat 3 ungegatete Missionen, der Frachter genau 1 (`mission_supply_run`) — die anderen drei hängen an Kenntnissen, also am Analytik-Labor. Die Drohne zuerst zu kaufen ist heute korrektes Spiel, kein Fehler.
  - [ ] **Verdacht auf Instanz-Decay-Bug:** `GameTick::processBuildingDecay()` schreibt mit `['colony_id', 'building_id']` ohne Instanz-Unterscheidung. Bei mehreren Instanzen könnte Decay superlinear wirken. Vor jeder Pfad-B-Balance verifizieren (`qa-tester`, Regressionstest).

### Entity-Chip-Rollout — weitere Views

- [x] Integration in Berater-Screen, Kolonie-Screen (2026-07-21). Kommandozentrale-Dashboard (Wartungsstau, Berater-Kurzübersicht) ergänzt.

### Offene GDD / Design-TODOs

Abgeschlossen (2026-07-10, game-designer): alle 4 Punkte geprüft und im GDD bereinigt.

- [x] GDD §2 vs §6: Supply-Cap Tick-Schritt. §2s "Phase 3" war eine grobe Gruppierung, kein Widerspruch zu §6/Code (Schritt 7) — beide bereits konsistent. §2 um eine Phase-≠-Schritt-Klarstellung ergänzt.
- [x] GDD §13: Burnout-Config-Block. Kein echter fehlender Verweis — GDD markiert die Formel bereits explizit als "noch nicht implementiert (Phase 4+)" und referenziert keinen nicht-existenten Config-Pfad. Klarstellung ergänzt, keine Config geschrieben (Feature bewusst aufgeschoben).
- [x] GDD §14/Koloniebeiträge: "Steuern"-Konzept ist im GDD bereits als **verworfen** (nicht umbenannt) dokumentiert, ersetzt durch die undramatische Relaisvergütung. Abweichung von der ursprünglichen Design-Notiz ("Begriff klären, nicht verwerfen") — Owner-Rückfrage empfohlen, siehe Bericht.
- [x] GDD §9/Merchant Fleet-Referenzen: §9 selbst war bereits sauber. `encounter_prep` ist keine Merchant-Item-Kategorie (die sind `ap_flex/ap_targeted/information/repair_kit/trust_boost`) — es ist ein Almanach-Bonustyp (§17) und referenziert bereits das neue §9-Kolonistengefahr-System, keine Flotten-Mechanik. Die Prämisse dieses ROADMAP-Punkts war insofern doppelt ungenau. Tatsächlicher Fund: Reisender Händler (aktive Mechanik) stand fälschlich unter dem "GESTRICHEN"-Banner der alten Systemansicht (§8a) — nach §12 Handel (neuer "Kanal 3") verschoben, Wortlaut von "im System" auf Exploration Zone korrigiert. Zusätzlich zwei tote Platzhalter-Werte (Angriffs-AP, Bewegungsreichweite) in der Kenntnisse-Beispieltabelle durch zivile Äquivalente ersetzt.

---

## Geplant — Phase 4 (Post-Playtest)

- GDD-Cleanup: Balance-TODOs nach erstem Playtest einarbeiten
- Tile-abhängige Harvester-Produktionsrate obendrauf auf die bestehende Glockenkurve (PR #220) — zusätzlicher Modifier, ersetzt sie nicht
- Berater-Traits (Draft — siehe Memory)
- Berater Außendienst-Mechanik für weitere Typen (nach Playtest evaluieren)
- Begegnungen & Gefahren (GDD §9) — konkrete Events + Encounter-Screens
- Forschung / Techtree-Screen: Kenntnisse-Freischalt-Flow
- Play-by-Mail-Multiplayer (3–4 Spieler, variable Tick-Zeiten) — optionale spätere Iteration. Architektur in ADR 0003 festgelegt (siehe oben); Rest (Games/TurnOrders/Resolution-Engine/KI) zurückgestellt bis aktiv angegangen.
- Admin-Tool zur visuellen Auswertung/Vergleich von Playtest-Bot-Läufen (Owner-Wunsch 2026-07-20) — JSON-Reports aktuell nur manuell mit jq/python einsehbar

### Neu gefunden bei GDD-Aufräumpass (2026-07-10, technisch, nicht blockierend)

- `config/game.php → merchant.items.information.label` heißt noch "Systemkarte vollständig" (Restverweis auf die 2026-06-20 gestrichene Systemkarte) — rein kosmetisch, geprüft: `MerchantService` setzt bereits korrekt `colony_tiles.is_explored`, die Wirkung ist nicht kaputt. Label-Text anpassen. Siehe GDD §12 Kanal 3.
- Tick-Schritt-Nummerierung in `GameTick.php` (Docblock) ist selbst lückenhaft/inkonsistent (springt 0→4→6, Food-Consumption-Schritt fehlt ganz) und mehrere GDD-Stellen (§8b, §13, §14, §15) referenzieren daraus veraltete Schrittnummern (z.B. "Tick-Schritt 7" für Advisor Ticks, tatsächlich Schritt 9). Docblock zuerst durch game-developer korrigieren/vervollständigen, danach GDD-Referenzen in einem eigenen Pass nachziehen.

### Determinismus-Bug (gefunden 2026-07-20)

- `ColonyTileService::randomizeOuterRingRows()` nutzt PHP-Ambient-Zufall (`random_int`/`shuffle`/`array_rand`) statt `rng_seed` und läuft in `OnboardingService::resetColonyToSol1()` **vor** dessen explizitem Setzen — "gleicher Seed → gleicher Run" gilt aktuell **nicht** für Tile-Layouts. Gefunden via `PlaytestBotTest::test_same_seed_draws_identical_objectives`, Test läuft aktuell mit `markTestSkipped` statt rot.

---

## Verworfene Features

- Kolonisierung / Colony-Ships (kein zweites Colony-Objekt)
- Rassen (kein Rassen-System)
- Battlecruiser (Schiffstypen reduziert auf Drohne/Frachter/Korvette)
- Tradecenter + Forschungshandel (entfernt)
- Player-Messaging / Galaxy-News / Inbox-Outbox (mit Phase 3j entfernt)
- Fleet-Commander als separater Berater-Typ (entfernt)
