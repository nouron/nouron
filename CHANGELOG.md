# Changelog

## 2026-06-29

- **Sol-Report V2: 3-Screen-Flow.** Sol-Report erweitert auf drei aufeinanderfolgende Screens. Screen 1 (Gruppen-Report) unverändert; „Weiter"-Button führt jetzt zu Screen 2. Screen 2 zeigt Phase-Fortschritt: in Phase 1 die drei Abschluss-Kriterien (CC Lv3, 2 Gebäude Lv2, 3 Berater) mit aktuellem Stand; in Phase 2 die drei Nexus-Direktiven mit Revelations-Mechanik (Direktive erst sichtbar wenn Fortschritt > 0). Screen 3 zeigt „SOL N / startet" als elegante Fade-in-Animation auf dunklem Hintergrund, danach „Mission fortsetzen"-Button. `SolReportService::buildReport()` gibt jetzt `phase_progress`-Block aus (`SolReportService::phaseProgress()`). Alpine.js: `currentScreen` (1/2/3) + `screen3Phase` (0–3) State, `goScreen2()` / `goScreen3()` Methoden. CSS: `.sol-phase__*` (Screen 2) + `.sol-launch` / `.sol-launch__*` (Screen 3). Reduced-motion: Screen 3 überspringt Animation.

## 2026-06-28

- **Voraussetzungsketten finalisiert** (Owner-Design). Analytiklabor + Hangar jetzt ab CC Lv1 baubar (Migration: `required_building_level` 2→1). Path-Gate (CC-Level−1-Formel) entfernt — natürliche Ressourcenknappheit steuert Spielerwahl statt künstlichem Gate. Techtree-Nav + Route gesperrt bis Analytiklabor gebaut (`sciencelabBuilt`-Flag in AppServiceProvider, Redirect in TechtreeController).
- **SecurityHub-Gate** (game-designer-Entscheidung). Hub von CC Lv2 auf Lv3 angehoben (Migration). Stratege-Slot 5 jetzt erst mit SecurityHub Lv1 entsperrbar (AdvisorController + PersonellService). `TrustService`: negative Trust-Events um 25% gedämpft wenn Hub aktiv (`event_mitigation_pct = 0.25` aus `config/buildings.php`). Hub-Effekte: `trust_per_lv=1`, Event-Dämpfung, `recycle_pct=0.10`. GDD §4/§11/§13 vollständig aktualisiert.
- **Flash-Animation für alle Ressourcen-Chips.** Credits, Regolith, Werkstoffe flashen bei Abnahme (`.res-chip--flash`-Klasse). `colony-hexgrid.js` flasht Rg/Co; `advisors.js` flasht Credits nach Berater-Hire.
- **Cantina-Mechanik: Economy-AP-Sink + Bar-Level-Progression + Konsul-Rang-Effekte.** Bar-Angebote annehmen kostet jetzt 1 Wirtschafts-AP (sichtbar als ÖAP-Chip im Angebots-Modal). Bar-Level 1–5 steuert `level_max_concurrent` (2–6 gleichzeitige Angebote) und `level_offer_duration` (2–4 Sole Laufzeit). Konsul-Rang 1–3 beeinflusst Rabatt (10–30%), Gastanzahl und Werkstoffe-Bias bei Rang 3 (50% Chance auf Werkstoffe statt zufälliger Ressource). Dead code `trade.ap_cost_threshold` (Player-Marketplace-Relikt) entfernt. `BarService` verwendet `PersonellService` für AP-Check + AP-Lock in Transaction. GDD §12 Kanal 1 aktualisiert.
- **Playtest-Fixes 2026-06-28** (vier Bugfixes + Balancing aus Playtest Sol 4/5). (1) Harvester-Transit-Bug behoben: `pending_until_tick` auf `getTick()` statt `+1` gesetzt — Harvester kam bisher erst in Sol 3 an statt Sol 2. (2) Agrardom-Gate für Pfad-Gebäude: Analytiklabor/Hangar/Cantina können nicht gebaut werden bevor Agrardom platziert ist (`placeBuilding()` + `availableBuildings()` + Fehlermeldung `error_agrardom_required`). (3) Hint `cc_invest` Sol-1 unterdrückt wenn Agrardom fehlt — Spieler wurde fälschlicherweise zu CC-Invest statt Agrardom-Bau geleitet. (4) Pfadgebäude-Balancing (game-designer): Hangar 6→4 Supply, 80+25Wk→90Rg; Cantina 4→6 Supply, 50→70Rg; Analytiklabor unverändert (8 Supply, 80Rg). Werkstoffe-Anforderung für alle CC-Lv2-Pfadgebäude entfernt. GDD §4/§6/§13 + config/buildings.php aktualisiert. (5) Harvester Multi-Sol-Transit: game-designer entschied 1-Sol-flat bleibt (AP/Hex skaliert bereits mit Distanz; Multi-Sol wäre Doppelmalus).
- **Onboarding-Hint-Texte überarbeitet** — alle 25 Hint- und First-Visit-Texte mit mehr Stimme und Lore-Bezug versehen.

## 2026-06-27

- **Berater-Screen: Voraussetzungs-Bereich visuell vereinheitlicht** (Owner-Feedback Screenshot #7). Baumeister-Slot zeigte CC-Chip in abweichendem Layout — alle Slots nutzen jetzt dasselbe dual-Layout (CC-Chip + „+" + Gebäude-Bild). Fehlende Gebäude-Bilder (`command-center.webp`, `security-hub.webp`) zeigen graues SVG-Platzhalter-Bild statt kaputtem `<img>`-Tag. Portrait verschob sich bei variierender Voraussetzungs-Höhe — behoben durch feste Höhe (`flex: 0 0 96px`) am Prereq-Bereich.
- **Ressourcen-Popup: Breite begrenzt, Viewport-Überlauf behoben** (Owner-Feedback Screenshot #8). Popup war zu breit und konnte links aus dem Sichtbereich ragen. Jetzt `max-width: 240px`, `white-space: normal` und Alpine `x-effect` korrigiert die `margin-left` sobald der Popup sichtbar wird.
- **Protokoll-Log: Wiederholte Einträge zusammengefasst** (Owner-Feedback Screenshot #8). Mehrere AP-Klicks auf dasselbe Gebäude innerhalb eines Sols erzeugten identische Log-Zeilen. `CommLogController::collapseEntries()` fasst aufeinanderfolgende gleiche Ereignisse zusammen und zeigt `×N`-Badge.
- **Level-Up-Kosten sichtbar im Gebäude-Detailbereich** (Owner-Feedback Screenshot #10). Spieler sah „nicht genügend Ressourcen" ohne Kostenanzeige. Regolith-Kosten für den nächsten Stufenaufstieg werden jetzt unter dem Gebäudebild angezeigt (`Kosten: X RG bei Baubeginn`). Fehlermeldung beim AP-Invest nennt konkret wie viel RG benötigt vs. vorhanden ist. `ColonyController` liefert `levelup_cost` sowohl beim initialen Page-Load als auch in der Invest-AP-Response.

## 2026-06-25

- **Pfadwahl ab Sol 3: Sciencelab/Hangar/Cantina + generische Berater-Slots 2–4** (Owner-Design). Sol-1/2 bleiben strikt linear; ab CC Lv2 wählt der Spieler einen von drei Pfaden — Sciencelab+Analytiker (Forschungs-AP), Hangar+Pilot (Navigations-AP für Missionen) oder Cantina+Konsul (Wirtschafts-AP). Bau-Gate: maximal `CC-Level − 1` Pfadgebäude gleichzeitig platzierbar. Berater-Slots 2–4 generisch: Slot-Typ ergibt sich aus der Reihenfolge, in der Pfadgebäude gebaut werden (`colony_buildings.placed_at_tick`); `SLOT_ORDER`-Konstante entfernt. Hire-Gate: Analytiker/Pilot/Konsul können erst eingestellt werden, wenn das zugehörige Pfadgebäude platziert ist. Agrardom ist Pflichtvoraussetzung für CC-Ausbau auf Lv2 (`required_building_id=41`). Hangar-Gate von CC3 auf CC2 gesenkt; `supply_cost` auf 6 korrigiert (Config/DB-Drift behoben, `MasterDataSeeder` synchronisiert). `OnboardingHintService`: `allChoiceBuildingsPlaced()` auf neues Trio (31+44+52) umgestellt, neuer `pathGateFree()`-Helper in allen drei `*PrereqMet()`-Methoden, neuer Rang-15-Hint `hint_hangar_path`, `checkHint3()` leitet auf Agrardom um solange nicht gebaut. Neue Lang-Keys für Gate-Fehler, Pfad-Slot-Beschreibung und Hint-Texte.
- **Hire-Warnung für Analytiker/Pilot ohne Pfad-Gebäude** (Owner-Feedback). Bestätigt-Dialog im Berater-Screen zeigt Amber-Warning, wenn der AP-Pool des angeheuerten Beraters mangels Pfad-Gebäude sofort brachliegen würde (Analytiker ohne Sciencelab, Pilot ohne Hangar).

## 2026-06-23

- **Hint-System: `hint_end_sol`-Bug gefixt + First-Visit-Popups** (Owner-Playtest-Report ab Sol 5). `hint_end_sol` behauptete „alles Wichtige erledigt", obwohl noch Bau-/Forschungs-/Nav-/Wirtschafts-AP übrig war, sobald Cantina/Agrardom/Analytik bereits alle drei gebaut waren. Neuer Catch-Hint `hint_spend_remaining_ap` zeigt jetzt den AP-Pool mit dem größten Rest; `hint_end_sol` feuert nur noch, wenn wirklich kein Pool mehr etwas leisten kann. Zusätzlich First-Visit-Popups für Techtree, Nexus-DB, Cantina und Hangar — erklären die Screens beim ersten Besuch (Key-Präfix `visit_*`, gleicher Dismiss-Speicher/-Endpoint wie die Hint-Bar).
- **Header-Leisten (Nav/Ressourcen/Hint-Bar) auf allen Screens vereinheitlicht** (Owner-Report). Sol-Button, Vertrauen-Chip und AP-Chips waren auf den Koloniescreen beschränkt — fehlten komplett auf Berater/Techtree/Cantina/Hangar/Nexus-DB. Trust/AP-Pools/aktiver Hint werden jetzt global im `AppServiceProvider`-View-Composer berechnet statt nur in `ColonyController::hexview`. Die Hint-Bar zieht aus `hexview.blade.php` in `layouts/colony.blade.php` um und wird ein eigenständiges Alpine-Component (`partials/hint-bar.blade.php`), `colony-hexgrid.js` behält `activeHint` nur noch fürs Hexgrid-Highlighting (Sync per `hint:sync`/`hint:dismissed`-Window-Events). Dabei gefixt: `@json()` direkt in `x-data="..."` eingebettet brach das HTML-Attribut (JSON-Struktur-Quotes werden von `JSON_HEX_QUOT` nicht entfernt) — Hint-Bar zeigte deshalb nie einen Hint; Daten laufen jetzt wie an anderen Stellen über eine `window.__hintBarData`-Script-Variable.
- **Supply/AP-Chips zeigen Zusammensetzung im Popup, Supply-Chip zeigt frei/Cap** (Owner-Report). Supply-Chip zeigte nur die Kapazität, nie den freien Rest — obwohl Gebäude/Forschung/Berater Supply als Cap-Gate verbrauchen. Zeigt jetzt „frei / Cap"; Popups (Supply + alle 5 AP-Chips) zeigen die Zusammensetzung (Quellen/Verbrauch bzw. Basis-AP + Berater-Bonus + Vertrauen-Multiplikator) statt einer generischen Beschreibung. Geprüft, aber **nicht umgesetzt**: Sciencelab/Analytiker-Timing (Forschungs-AP verfällt mehrere Sole zwischen Analytiker-Hire und Labor-Fertigstellung) — game-designer riet von einer strukturellen Änderung ab, da sie die gerade erst festgeschriebene Sol-1/2-Linearität bzw. Sol-3-Wahlfreiheit verletzen würde; als bekannte Sequenzierungs-Eigenheit zurückgestellt.

## 2026-06-22

- **Depot-Gebäude ersatzlos entfernt** (Owner-Entscheidung nach Pro/Contra-Evaluation durch @game-designer). Depot hatte keine implementierte Spielwirkung (kein Resource-Cap-System existiert) und wurde als verwirrendes Pflichtgebäude ohne Effekt identifiziert. Statt das fehlende Cap-System nachzuziehen — was dem Roguelike-Designprinzip "aktive Produktion belohnen statt bestrafen" entgegengelaufen wäre und ein Nischenproblem (temporärer Regolith-Überschuss vor Sol 5) unverhältnismäßig adressiert hätte — wurde Depot komplett gestrichen: `config/buildings.php`, `lang/de+en/buildings.php`, `lang/de+en/techtree.php`, `MasterDataSeeder`, `ColonySeedDemo` bereinigt; neue Migration `2026_06_22_000001_remove_depot_building.php` löscht die DB-Zeilen (Tradecenter-Removal-Muster). `docs/GDD.md` aktualisiert (Gebäudeliste, Supply-/Decay-Tabellen, Techtree-Grid, §16 Befund 1 als erledigt markiert). Vier Testdateien, die Depot als generisches Stellvertreter-Gebäude nutzten, auf Krankenstation (`infirmary`, ID 46) umgestellt (`BuildingServiceTest`, `ColonyZoneDecoupleTest`, `BuildResourceSinkTest`, `RunProgressServiceTest`) — volle Suite grün (644 Tests, 1635 Assertions). Bei Bedarf kann Depot + Cap-System später erneut eingeführt werden.

## 2026-06-21

- **Build-Menü-Info-Popup, größere Desktop-Colony-View, vollständige Hex-Legende** (Owner-Feedback). Im Bau-Menü fehlten Infos zum Effekt eines Gebäudes — neues Info-Icon je Bau-Chip zeigt Hover-Popup (Desktop) bzw. Tap-Popup (Mobile), Text aus bestehenden `buildings.*_desc`-Lore-Strings. Colony-View nutzt ab 1400px Breite mehr Platz (SVG-Hex-Grid größer dargestellt). Hex-Legende um Kommandozentrale/Gefahrenzone/Unpassierbar/entdecktes Ereignis ergänzt und ab 900px fix unten links angepinnt (immer aufgeklappt statt einklappbar).
- **Roguelike: Ring-2/3-Tile-Anordnung wird bei Run-Start/-Reset randomisiert** (Owner-Wunsch). Bisher war die Kolonie-Umgebung (Terrain/Hazard/Regolith-Verteilung außerhalb des Kerns) bei jedem Run identisch — ein statisches Array in `OnboardingService::seedStartingTiles()`. Jetzt würfelt `ColonyTileService::randomizeOuterRingRows()` Ring 2 (12 Tiles) und Ring-3-Frontier (9 Tiles) pro Aufruf echt zufällig aus, garantiert dabei weiterhin genau ein vorerkundetes Regolith-Tile als Harvester-Umzugsziel. Ring 0+1 bleiben fix (Gebäude-Platzierung, kein Hazard im Kern). Betrifft `setupNewPlayer()`, `resetColonyToSol1()` (Lobby-Neustart) und `ResetPlayer` gleichermaßen, da alle drei dieselbe Seed-Routine durchlaufen.
- **Onboarding: Hint-Bar-Lücke nach CC-Lvl2-Ausbau geschlossen** (Owner-Playtest-Report). Nach dem CC-Ausbau auf Level 2 (typ. Sol 2) blieb die Hint-Bar mehrere Sols leer — die nachfolgenden Hints (Agrardom/Analytik/Cantina) waren erst ab Sol 6-9 gegated. Neuer Hint `hint_advisor_slot2` (Rang 6) feuert sofort, wenn CC2 einen freien Berater-Slot freischaltet. Tick-Gates für Cantina/Analytik auf 0 gesenkt (CC>=2-Vorbedingung verhindert Day-1-Spam bereits selbst). Agrardom-Gate bewusst bei 1 belassen (Sol 2+) — ohne CC-Gate hätte es sonst in Sol 1 gefeuert, sobald Bau-AP verbraucht ist, und den „Sol beenden"-Bridge-Hint verdrängt (per Regressionstest abgesichert).
- **Roguelike-Fix: Ring-3-Kartensilhouette war noch fix** (Owner-Folgereport). Welche 9 von 18 Ring-3-Koordinaten existieren, war trotz randomisiertem Tile-Inhalt bei jedem Run identisch. `ColonyTileService::randomizeOuterRingRows()` würfelt jetzt auch die Koordinaten-Auswahl pro Sol-1-Seed neu.
- **Hint-Dismiss-Bug, Berater-Credits-Sync, Build-Chip-Affordability, Harvester-Vorschaupfeil** (Owner-Playtest-Reports). `colony.hint.dismiss` gab unübersetzten `text_key` zurück — Hint-Box blieb nach Klick auf „X" sichtbar (leerer Text). Berater-Hire zog Credits korrekt ab, Resourcebar zeigte den neuen Stand aber erst nach Reload. Bau-Chips waren anklickbar, auch wenn AP/Regolith/Werkstoffe/Supply fehlten — greyen jetzt aus. Harvester-Verlege-Vorschau zeigte eine Luftlinie ohne AP-Kosten; folgt jetzt dem echten Hex-Pfad (Red-Blob-Games-Line-Draw) mit AP-Kosten-Badge, Geste auf Desktop/Mobile vereinheitlicht (Tap = Vorschau, gedrückt halten ~0.9s mit Lade-Ring = Verlegen bestätigen). Explore-Button zeigte fix „1 AP" statt der echten ring-abhängigen Kosten.
- **Build-Order-Fix: Analytik-Labor + genereller Affordability-Check für Bau-Hints** (Owner-Playtest-Report, mit @game-designer-Konsultation). CC-Lvl2 schaltete gleichzeitig Analytiker-Slot und Analytik-Labor-Hint frei, aber das Labor kostete Werkstoffe — nicht lokal produzierbar so früh im Run, Berater damit für mehrere Sols nutzlos. Analytik-Labor-Kosten auf reines Regolith reduziert (analog zur bewussten Werkstoff-Ausnahme bei Uplink-Station). Zusätzlich: Cantina/Agrardom/Analytik-Hints prüfen jetzt generisch, ob Bau-AP + Ressourcen für die Platzierung tatsächlich reichen, bevor sie feuern (`canAffordBuildingPlacement`) — verhindert, dass der Hint zu einer in diesem Sol unbezahlbaren Aktion auffordert. Neuer Hint `hint_build_priority` warnt, wenn 2+ der drei Gebäude gleichzeitig „bereit" wären, die Ressourcen aber nicht für alle reichen. `hint_end_sol` ist jetzt ein universeller Floor (kein Sol-1-Limit mehr) — Hint-Bar wird nie leer. Hint-Bar zeigt neu ein „Vorschlag"-Badge (kein Zwang, andere Baureihenfolgen möglich). Berater-Slot-Tausch (Analytiker↔Konsul) wurde evaluiert und verworfen — würde die dokumentierte GDD-§13-Priority-Kurve zerstören, ohne das eigentliche Problem besser zu lösen als der Affordability-Check.
- **GDD-Eval: Hint-Flow-Dokumentation (§16.2) war veraltet** (@game-designer-Konsultation). Die Hint-Rang-Tabelle dokumentierte nur 8 von inzwischen 15 implementierten Rängen (`hint_repair`, `hint_repair_urgent`, `hint_advisor_slot2`, `hint_cc_invest`, `hint_explore`, `hint_build_priority`, `hint_agrardome`, `hint_analytik`, `hint_end_sol` fehlten). Tabelle und Pulse-Indikator-Mapping auf den tatsächlichen Implementierungsstand korrigiert; Sol-Schwellen nachgerechnet (Tick→Sol-Off-by-one bei `hint_4`/`hint_5`/`hint_6` korrigiert). Neuer BALANCE CONCERN: `hint_4` (Kenntnis fehlt, Rang 9) feuert am selben Sol wie `hint_analytik` (Gebäude fehlt, Rang 14) und gewinnt die Priorität — Spieler landet auf `/techtree`, obwohl zuerst das Gebäude fehlt. Empfehlung: `hint_no_knowledge_after_tick` über `hint_no_analytik_after_tick` setzen. Pulse-Indikator-Mapping (§16.3) für die 9 neuen Hints noch offen — vor nächstem UI-Pass mit `ui-specialist` zu klären.
- **Onboarding-Hint-Flow: Designentscheidungen festgeschrieben + Cleanup** (Owner-Vorgaben, @game-designer-Konsultation). "Baumeister zuerst" (`hint_1`) als bewusste, dauerhafte Designentscheidung bestätigt (kein offener Punkt mehr). Sol 1–2 sind als reiner Bau-/Erkundungs-Fokus dokumentiert; ab Sol 3 stehen Cantina und Analytik-Labor jetzt auf identischer Tick-Schwelle (`hint_no_cantina_after_tick` 0→2) für eine echte gleichwertige Wahl Handel vs. Forschung, mit Konsul/Analytiker als Standard-Empfehlung statt Zwang. Code-Cleanup in `OnboardingHintService.php`: toter Config-Key `hint_no_engineer_ticks` entfernt, Fallback-Defaults von fünf Tick-Schwellen auf die tatsächlich aktiven Config-Werte synchronisiert (reine Drift-Korrektur, keine Verhaltensänderung — 53 Onboarding-Tests bleiben grün). Geprüfter, aber **nicht umgesetzter** Punkt: ein Depot-Hint (gegen Regolith-Leerlauf) ist blockiert, weil Depot aktuell keine Spielwirkung hat — das Resource-Cap-System fehlt noch im `ResourcesService` (bereits bestehendes TODO Balance); Hint folgt erst nach dessen Implementierung.

## 2026-06-20

- **Hex-Bau-UI: zwei Layout-Fixes** (Owner-Report, Playtest). Der reine „Bauen"-Öffnen-Button (öffnet nur die Gebäudeauswahl, löst selbst keine AP-Aktion aus) zeigte fälschlich einen AP-Kosten-Chip — entfernt. In der Bauliste standen Gebäudename, AP-Kosten und Ressourcenkosten alle in einer Zeile und brachen hässlich um (z. B. „Lagerhalle-10 AP"); jetzt zwei Zeilen pro Eintrag: Name + AP-Kosten oben, Ressourcen-Chips (Regolith/Werkstoffe/Supply) darunter mit Flex-Wrap (`building-list-row`/`building-list-row--costs` in `colony.css`).
- **Fix: Lobby-„Neuer Run" erzeugte einen abweichenden Sol-1-Zustand** (Owner-Playtest-Report). `LobbyController::newRun()` war eine eigenständige, aus dem Tritt geratene Re-Implementierung des Onboarding-Setups — es fehlte das HousingComplex-Gebäude, Gebäude-Status stimmte nicht (20/20 statt 16/20), Zonen-Zuweisung/Fog wurde nicht neu berechnet, `user_preferences` (Hints/Trigger) und `colony_log` (Nexus-Briefing) wurden nicht zurückgesetzt.
  - Neue kanonische Methode `OnboardingService::resetColonyToSol1()` bündelt den kompletten Reset (alle Colony-/User-Scoped-Tabellen leeren inkl. `user_preferences`, `locked_actionpoints`, `colony_log`, `merchant_visits`, `trust_events`, `colony_hangar_missions`, `colony_personell`, `trade_resources`) und seedet danach über dieselbe `seedSol1State()`-Routine wie `setupNewPlayer()` (Gebäude, Tiles + Zonen-Zuweisung, Nexus-Briefing, Run-Record). Berater werden dabei nur entkoppelt (`colony_id = null`), nicht gelöscht — der Spieler behält sie über Runs hinweg.
  - `LobbyController::newRun()` delegiert jetzt vollständig an `resetColonyToSol1()` statt eigene SQL-Statements zu pflegen. `ResetPlayer`-Command bleibt als Dev-Tool-Superset bestehen (löscht Berater komplett + alle Runs des Users), ruft aber weiterhin `setupNewPlayer()` für den finalen Seed auf.
  - Tests ergänzt (`LobbyNewRunTest`): Gebäude-Anzahl/Status, Zonen-Zuweisung + Ring-0/1-Exploration, Hint/Trigger-Reset, Nexus-Briefing-Erzeugung trotz Altlasten in `colony_log`, sowie ein direkter Paritäts-Test zwischen `newRun()` und `setupNewPlayer()`.
- **Galaxie/Systemkarte + Fleet-Layer entfernt** (Owner-Entscheidung: „bis auf weiteres gestrichen"). Die navigierbare Galaxie-/Systemkarte und Flottenbewegung/-kampf waren UI-seitig längst weg; jetzt ist auch das tote Backend raus.
  - Gelöscht: `FleetService`, `GalaxyService`, alle `Fleet*`/`GlxSystem*`-Models, GameTick-Schritte Fleet-Move/Trade/Combat + Fleet-Ship-Decay. Migration droppt `fleets`/`fleet_*` + `glx_systems`/`glx_system*`-Tabellen + Views.
  - **Kolonie entkoppelt:** `glx_colonies` ohne `system_object_id`/`spot` neu gebaut (Koordinaten + System-Objekt-FK entfallen); `v_glx_colonies` ist jetzt ein Passthrough. Kolonie = ein Heimat-Standort ohne Systemraum.
  - **Kampf komplett raus:** Objective „Bewährungsprobe" (`task_combat_record`) aus dem Pool, Trust-Events `encounter_won`/`encounter_lost`/`colony_threatened` entfernt, `game.fleet`/`game.combat`/`galaxy_view`/`system_view`/`decay.combat_factor` aus der Config.
  - **Advisors** ohne `fleet_id`/`is_commander` (Fleet-Commander-Reste) neu gebaut. Hangar/Schiffe/Dispatch-Missionen + Kolonie-Hex-Exploration (`task_expedition_coverage`) bleiben unberührt.
  - GDD §8/§8a als „gestrichen (Phase 4+)" markiert; §14/§15 + Tick-Phasen-Tabelle bereinigt.
- **Ressourcenleiste zeigt die volle Ökonomie.** Bisher fehlten Werkstoffe/Organika (wurden bei Bestand 0 ausgeblendet) und drei AP-Typen. Jetzt: alle drei Kolonieressourcen (Regolith, Werkstoffe, Organika) werden immer angezeigt — auch bei 0 — und alle fünf Aktionspunkt-Pools (Nav, Bau, Forschung, Wirtschaft, Strategie) als Chips mit Tooltip. `ColonyController::hexview` liefert die drei zusätzlichen AP-Werte; neue Lang-Keys (`popup_co`/`popup_or` korrigiert + AP-Popups) und Chip-Styles. Trust-Chip sitzt jetzt direkt neben Supply (thematisch).
- **Fog-of-War-Pacing-Fix: Ring-gestaffelte Erkundungskosten + gedrosselter Onboarding-Hint** (Owner-Entscheidung). Bei pauschal 1 Nav-AP/Tile war die komplette Karte bei 6 Nav-AP/Sol nach rund 5 Sols aufgedeckt — Fog of War verlor seinen Spannungswert.
  - Erkundungskosten pro Tile sind jetzt ring-abhängig (`config/game.php → colony.explore_cost_per_ring`): Ring 1 = 1 Nav-AP, Ring 2 = 2, Ring 3 = 3. `ColonyTileService::exploreTile()` schlägt die Kosten anhand des Tile-Rings nach (`explore_cost_default` als Fallback) und nutzt sie für AP-Check und AP-Abzug.
  - Onboarding-Hint `hint_explore` feuert nur noch in Sol 1 (`game.onboarding.hint_explore_until_tick` 2 → 0) und drosselt zusätzlich selbst, sobald der Spieler bereits ≥ 6 Tiles ab Ring 2 erkundet hat (`hint_explore_max_explored_tiles`) — verhindert, dass der Hint jeden Sol erneut zum Vollerkunden drängt.
  - GDD §Sichtbarkeit aktualisiert (Ring-Kosten dokumentiert); offener Designpunkt zur Erweiterung des Erkundungsradius über Ring 3 hinaus (Mobile-Navigierbarkeit, Tile-Zahl vs. AP-Sink-Zahl entkoppeln) als Notiz vermerkt, nicht umgesetzt.
- **Organika-Sinks: Verpflegung + Missions-Proviant** (PR 2, game-designer-Spec). Organika hatte bisher außer Handel keinen Verbraucher — tote Ressource.
  - **Verpflegung (laufend, eskalierend):** Jede Kolonie verbraucht pro Sol `floor(belegte_Supply / 4)` Organika (neuer GameTick-Schritt 3a, zwischen Produktion und Vertrauen). Vorrat reicht → `well_fed` (+1 Vertrauen); Vorrat leer → `glx_colonies.hunger_streak` wächst und ein **eskalierender** Vertrauens-Malus `−min(2+(streak−1), 8)` greift (`TrustService::hungerPenalty`). Sättigen setzt Streak + Malus sofort zurück. Macht den Agrardom zum Pflichtgebäude; Survival-Spirale statt weichem Einmal-Malus.
  - **Missions-Proviant:** Hangar-Dispatch kostet jetzt `sol_distance × 3` Organika (Crew-Verpflegung) **und** `sol_distance × 1` Navigations-AP; bei Mangel an beidem wird die Entsendung blockiert.
  - Sol-Report zeigt eine Verpflegungs-Zeile (versorgt / Vorräte erschöpft), damit der Hunger-Vertrauensverlust eine sichtbare Ursache hat. Neue Spalte `glx_colonies.hunger_streak` (Migration), Config-Block `game.food`, `game.trust.events.well_fed`. GDD §3/§14 + Tick-Phasen-Tabelle aktualisiert.
- **Ressourcen-Bau-Sink: Bauen, Ausbauen und Reparieren verbrauchen jetzt Ressourcen** (PR 1, game-designer-Spec). Der Hex-Bau-Flow war bisher gratis (nur AP) — die Kolonie-Ökonomie hatte keinen Sink, produzierte Ressourcen versickerten ungenutzt.
  - **Errichten:** Regolith für alle Gebäude außer Kommandozentrale + Harvester (Bootstrap-Ausnahme); späte/High-Tech-Gebäude zusätzlich ein kleiner Werkstoff-Akzent (10–25). Supply wirkt als **Gate** (Bau nur, wenn freie Cap ≥ `supply_cost`), kein Abzug — modelltreu zum Cap-System.
  - **Level-Up:** flacher Regolith-Anteil (25 % der Errichtungskosten, keine Eskalation), erst beim Level-Up-Abschluss abgezogen; ein Mangel verfällt keine AP. Kommandozentrale skaliert separat (`Ziel-Level × 30` Rg).
  - **Reparatur:** 2 Regolith pro Klick mit hartem Gate (kein Rg → gesperrt, Hinweis „Harvester reparieren"). Kommandozentrale + Harvester sind ausgenommen (nur AP) — die Regolith-Quelle bleibt immer reparierbar, die Decay-Spirale ist ein erholbarer Rückschlag statt eines Deadlocks.
  - **Werkstoffe (knapp, nicht produzierbar):** neuer **Nexus-Direktimport** gegen Credits, gegated über Uplink-Station Lv1, fester Preis (90 Cr/Einheit, teurer als die Cantina) — die garantierte Anti-Lock-Quelle aus GDD §3. Import-Panel in der Kolonie-Sidebar.
  - **Schiffe kosten nur noch Credits** (Legacy-Werkstoff-/Organika-Schiffskosten entfernt). Organika ist nie Baukosten.
  - Kosten sind canonical in `config/buildings.php` (`build_cost`) gepflegt und werden über `game:sync-config` in `building_costs` gesynct; neue Migration + testdata-Bereinigung. Bauliste zeigt Regolith-/Werkstoff-Kosten als Chips. GDD §3/§4/§6/§7 aktualisiert.
  - Organika-Sinks (eskalierende Verpflegung + Missions-Proviant) folgen als eigene PR.

## 2026-06-17

- **Onboarding Sol-1 AP-Pacing: zwei neue Hints gegen den frühen AP-Leerlauf** (game-designer-Spec). Bisher endete Sol 1 mit ungenutzten Bau-AP und brachliegenden Navigations-AP, während CC-Level-2 in Sol 2 nur „gerade so" fertig wurde.
  - **`hint_cc_invest`** (Rang 6, nur Sol 1): sobald Engineer angeheuert + Harvester verlegt + kein dringender Repair und die Kommandozentrale noch unter Level 2 ist, lenkt der Hint die *restlichen* Bau-AP in den CC-Ausbau — Vorinvestieren via `ap_spend`, damit Level 2 in Sol 2 sicher fertig wird statt zu rutschen. Gegated auf „noch verfügbare Bau-AP", self-clearing, nie dismissed.
  - **`hint_explore`** (Rang 7, Sol 1–3, `hint_explore_until_tick=2`): die brachliegenden Navigations-Basis-AP (6/Sol) werden jetzt geführt — solange Nav-AP da sind und unerkundete Tiles existieren, leitet der Hint zum Erkunden (Regolith fürs Harvester-Verlegen finden, Gefahren scouten). Nutzt die bestehende Erkunden-Mechanik, keine neue Logik.
  - Sequenzierung rein über Rang-Ordering: Bau-AP → CC (rank 6), dann Nav-AP → Erkunden (rank 7), dann „Sol beenden" (`hint_end_sol`, jetzt rank 11). `hint_3` (CC-Ausbau Sol 2+) unverändert; Reparatur behält Vorrang als Lehr-Hint. CC-Tile pulst auch bei `hint_cc_invest`.
  - Verworfen/zurückgestellt: „Tiles per Bau-AP freiräumen" (falscher AP-Typ, würde den Bau-Engpass verschärfen), Deep-Scan-Funde (neue Mechanik) und eine hangar-unabhängige Start-Sonde (entwertet den Hangar) — alle später separat.
- **Kolonie-Raum: Bebaubarkeit von Erkundung entkoppelt** (game-designer-Spec) — bisher steuerte der CC-Level beides gleichzeitig (Baufläche UND Auto-Erkundung), was „Erschließen" und „Erkunden" für den Spieler ununterscheidbar machte. Jetzt zwei klar getrennte Achsen: **Erschließen** (CC-Level macht Gelände *baubar*, deckt es aber nicht mehr auf) vs. **Erkunden** (Navigations-AP lüftet den Fog, findet Regolith/Signale). `assignColonyZone()` setzt kein `is_explored=1` mehr; Bauen auf einem noch verschleierten Zone-Tile deckt es auf („siedeln → sehen"); der Harvester braucht weiterhin ein erkundetes Regolith-Ziel. Damit bekommt die Navigations-Basis-AP ab Sol 1 echten Zweck und der `hint_explore` lenkt gezielt nach draußen (Text geschärft). GDD §4a „Sichtbarkeit" entsprechend präzisiert.
- **Fog visuell klar gemacht** (ui-specialist) — Fog-Tiles waren nahezu weiß/unsichtbar; jetzt gedämpftes Slate/Blaugrau mit Nebel-Schraffur, zwei unterscheidbare Arten: **Zonen-Fog** (baubar, noch unentdeckt — gestrichelter Rand + „+"-Glyph) und **Explorations-Fog** (Scout-Ziel — „?"-Glyph). Neue eingeklappte Hex-Grid-Legende erklärt die Zustände.
- **„Bald bebaubar"-Badge ehrlich gemacht** — der verwirrende „CC ↑"-Badge markierte JEDES erkundete Terrain-Tile außerhalb der Zone, auch solche die der CC nie erschließt (Zone ist auf 15 Tiles gedeckelt). Jetzt ein Schloss-Symbol, das ausschließlich auf den Tiles erscheint, die der **nächste** CC-Ausbau tatsächlich erschließt — serverseitig berechnet (`ColonyTileService::nextZoneTileKeys()`, Delta der deterministischen Zonen-Expansion), pro Tile als `next_zone`-Flag an die View. Leer bei max CC-Level.
- **Sidebar-Terrain-Info angereichert** — statt nur „Erkundet" + Koordinaten zeigt das Terrain-Panel jetzt den **Zonen-Status** (bebaubar / bald bebaubar / außerhalb der Koloniezone / unerforscht), den Terraintyp, einen Regolith-Hinweis („Ziel für Harvester-Verlegung" + Ergiebigkeit) sowie Warnhinweise für Gefahrenzone/unpassierbar.

## 2026-06-16

- **Sol-Report: animierter End-of-Sol-Übergangsscreen** — „Sol beenden" zeigte bisher nur 5 s einen inhaltslosen Spinner. Jetzt liefert `sol.next` die Tick-Ergebnisse als JSON, und ein neuer Übergangsscreen spielt sie Schritt für Schritt animiert ab (fade/roll-in pro Gruppe, Counter-Hochzählen für Zahlen, ~3–5 s). Dramaturgie nach game-designer-Spec: **Die Kolonie altert** (Verfall/Level-Downs) → **Ereignisse** (Händler, Begegnungen) → **Produktion & Vorräte** (Ertrag + Supply-Cap) → **Kolonie & Personal** (Vertrauen, Credits, Berater-Beförderungen) → **Der Run** (Sol-Zähler, Phase, Ziele). Bedrohung vor Belohnung; Level-Down = roter Shake-Beat, Beförderung/Phasenwechsel = goldener Beat. Bei Run-Ende mündet der Report in ein Vollbild-Finale (Sieg/Scheitern, würdevoll) mit Weiterleitung zum Run-Result-Screen.
- **Echte Zahlen statt Deko** — `SolReportService` snapshottet den Kolonie-Zustand vor dem Tick und difft danach gegen den Live-Zustand (Ressourcen, Credits, Supply, Vertrauen, Gebäude-Status/Level, Berater-Rang) + liest die `colony_log`-Events des verarbeiteten Ticks. Leere Vorkommnis-Gruppen (Verfall/Ereignisse) entfallen, Zustands-Gruppen (Produktion/Kolonie/Run) sind immer da. Gebäude-Snapshot per `building_id:instance_id` (Mehrfach-Instanzen kollidieren nicht).
- **Skip-Mechanik** — Klick/Tap überspringt zum Endzustand (alle Counter springen, Weiter-Button erscheint, schließt nicht). Neue Einstellung `sol_report_skip` (user_preferences) lässt den Report künftig automatisch durchlaufen — wird aber bei wichtigen Beats (Level-Down, Beförderung, Phasenwechsel, Run-Ende) per `force_show` erzwungen angezeigt. Toggle direkt im Report (`POST /sol/report-skip`). `prefers-reduced-motion` schaltet die Animationen ab.

## 2026-06-15

- **Sol-Uhr-Bugfix: `runs.current_tick` ist jetzt die einzige Spielzeit-Uhr** — „Sol beenden" erhöhte zwar `run.current_tick` und verarbeitete den Tick, aber die Web-Schicht (Sol-Anzeige, Harvester-`in_transit`, AP-Regeneration) rechnete weiter mit einem zeitbasierten Legacy-Tick (~20000, Tage seit Epoch). Folge: Sol-Zähler blieb auf 1 statt auf 2 zu springen, Harvester produzierte nach dem Verlegen nie wieder (pending_until_tick im falschen Maßstab), und Bau-AP regenerierten nicht pro Sol. Fix: `TickService` wird im HTTP-Request request-scoped auf `run.current_tick` des aktiven Runs gebunden (Console/`game:tick` unverändert, setzt den Tick eh explizit). Sol-Anzeige = `current_tick + 1` (Run-Start = Sol 1) über neue `BaseController::currentSol()`; `since_tick` ist fürs Display entkoppelt. Damit laufen Anzeige, Decay, Produktion, Merchant, Fleet, AP-Lock und Run-Ende auf derselben Uhr.
- **Onboarding: Brücken-Hint „Sol beenden" (Rang 9) gegen den Sol-1-Leerlauf** — nach erledigten Sol-1-Aktionen (Baumeister da, Harvester verlegt, kein dringender Repair) gab es bei `current_tick=0` keinen Hint mehr → neue Spieler wussten nicht, dass „Sol beenden" der nächste Schritt ist. Neuer `hint_end_sol` füllt diese Lücke, ist selbst-clearend (nur Sol 1, verschwindet nach dem ersten Sol-Wechsel), nie dismissbar, niedrigste Priorität (alle echten To-dos gehen vor). game-designer-Spec. Zusätzlich: Da die Tick-Gates durch den Uhr-Fix erstmals real wirksam sind, `hint_cc_upgrade_after_tick` von 2 auf 1 gesenkt (CC-Ausbau-Hint ab Sol 2 statt Sol 3), damit Sol 2 nicht in dieselbe Lücke fällt.

- **Reparieren/Ausbauen-Buttons mit eingebetteter Segment-Fortschrittsleiste** — die Colony-Aktionsbuttons tragen jetzt eine segmentierte Fortschrittsleiste in der Button-Unterkante: Reparieren zeigt den Gebäude-Zustand (1 Segment = 1 Statuspunkt, weiß auf rot), Ausbauen den Level-up-Fortschritt (1 Segment = 1 Bau-AP, grün). Lücken zwischen den Segmenten wirken als Notches. Auf Desktop leuchtet beim Hovern das nächste Segment als +1-Vorschau-Ghost (via `@media (hover: hover)` gegated, kann auf Touch nicht hängenbleiben). Voll instand → kein Reparieren-Button, keine Leiste.
- **Button-Labels entschlackt** — numerische Status-Texte raus, da aus den Leisten ablesbar (game-designer-Spec): `Reparieren (85 %)` + Sublabel `+5 % Zustand` → einzeilig `Reparieren +5 %` (Klick-*Wirkung* bleibt, plattformsicher auch auf Touch ohne Hover-Ghost); `Ausbauen (5/10 AP)` → `Ausbauen 5/10` (Fortschritt-bis-Level-up bleibt, da variable Segmentzahl schlecht abzählbar). AP-Kosten-Chip `1 AP` unverändert.
- **Colony-Sidebar umstrukturiert** (game-designer + ui-specialist) — Gebäudename + Level-Badge wandern als Kontext-Header (`.tile-panel-title`) ganz nach oben über die Buttons (Identität vor Aktion, auch auf Mobile sichtbar). Max-Stufe nur noch inline am Badge (`Lv. 1 / 5`) und nur bei tatsächlich begrenzten Gebäuden. Gebäude/Terrain-Tabs entfallen: bebautes Tile ist gebäude-zentriert (Terrain in zugeklapptem `<details>`-Disclosure „Terrain & Standort"), leeres Tile terrain-zentriert/flach. Redundanter „Erkundet"-Chip bei bebauten Tiles ausgeblendet, Koordinaten ins Disclosure verschoben. Swipe-Tab-Flip + `tileTab`/`onTilePanel`/`panelTouch*` aus `colony-hexgrid.js` entfernt; verwaiste Lang-Keys (`tab_building`/`tab_terrain`/`max_level`/`condition`/`ap_invested`) aufgeräumt.

## 2026-06-14

- **Sol-1-Reparatur-Onboarding-Hint** — neuer Hint `hint_repair` (Rang 3, nach dem Harvester-Verlegen) führt neue Spieler proaktiv zum „Reparieren"-Button. Die drei Startgebäude starten beschädigt (16/20), bekamen bisher aber keinen Hinweis zum Reparieren — das Decay-INNN-Event greift erst, wenn ein Gebäude *unter* 80% fällt. Bedingung: irgendein Gebäude unter Maximal-Statuspunkten; kein Tick-Gate (ab Sol 1), löst sich organisch auf sobald alles repariert ist. Bewusst HINTER dem Harvester-Hint (Rang 2): alle drei Gebäude zu reparieren kostet ~12 Bau-AP > 1 Sol (~10), sonst säße der Spieler auf einem in Sol 1 nicht abschließbaren Hinweis fest — das billige Harvester-Verlegen (~2 AP) geht voran. Bestehende Hints auf Rang 4–7 verschoben.
- **Repair-Hint = Lehr-Hint, verschwindet nach erstem Klick** — `hint_repair` wird beim ersten Reparieren-Klick dauerhaft dismissed (Controller). Der Spieler lernt DASS er reparieren kann, ohne dass der Hinweis nagt, solange Gebäude noch intakt sind. Rang 4 (hinter Harvester).
- **Neuer Leveldown-Dringlichkeits-Hint `hint_repair_urgent`** (Rang 2, game-designer-Spec) — feuert nur wenn ein gebautes Gebäude (Level ≥ 1) auf/unter `game.onboarding.hint_repair_urgent_sp` (Default 3/20) fällt, also kurz vor dem Stufenverlust steht. Im Gegensatz zum Lehr-Hint nicht dismissbar, selbst-clearend, kehrt bei erneutem Verfall zurück. Pulst das kritische Gebäude. Höchste Repair-Priorität (nur hinter Baumeister-Hint). Bestehende Hints auf Rang 3–8 verschoben; `hint_repair` ignoriert jetzt Level-0-Gebäude (im Bau, nicht reparierbar).
- **Harvester-Verlegen: Feedback statt stillem No-Op** — Klick auf ein ungültiges Feld im Verlege-Modus zeigte bisher nichts an; jetzt erscheint ein Hinweis-Toast („Kein gültiges Ziel — freies, erkundetes Regolith-Tile, hellblau markiert"). `doMoveHarvester`-POST in try/catch gekapselt (Netzwerkfehler-Toast statt verschluckter Promise-Rejection).
- **Onboarding-Pulse-Ring auf Hint-Key statt Rang-Nummer** — die Tile-Hervorhebung (Puls-Ring) war auf feste Rang-Nummern hartkodiert (`hintRank===2` → Harvester, `===3` → CC) und desynchronisierte beim Umsortieren der Hints. Jetzt über den Hint-`key` (`hint_2`/`hint_3`/`hint_repair`); `hint_repair` pulst die beschädigten Gebäude-Tiles. GDD-Hint-Tabelle + Tests (Service + E2E-Flow) aktualisiert.
- **Tech-Debt: CC-Building-ID aus Config statt Magic-Number** — `buildingForTile()` in `colony-hexgrid.js` nutzte hartkodiert `building_id === 25` für das CC-Tile. ID wird jetzt via `\App\Enums\BuildingId::CommandCenter` über `__colonyViewData.ccBuildingId` durchgereicht (Config = Source of Truth).
- **Cleanup** — verwaiste Lang-Strings `onboarding_trigger_decay_*` + `onboarding_trigger_trust_*` (0 Code-Referenzen, veraltete „investieren"-Framing) aus `lang/de/colony.php` entfernt; der gerenderte Event-Text liegt in `lang/de/events.php`. Stale-Kommentar in `OnboardingService` korrigiert (Repair-Mechanik ist implementiert, kein „future feature" mehr).

## 2026-06-13

- **Schiffsreparatur auf Fixkosten vereinheitlicht** — Hangar-Reparatur kostet jetzt fix 1 Bau-AP pro Klick (→ +2 Statuspunkte), statt einer spielergewählten AP-Menge (Range 1–10). Gleiche Interaktion wie Gebäude-Reparatur, damit sich „Reparieren" spielweit konsistent anfühlt (game-designer-Empfehlung: diskrete Wirkung → fix, kontinuierlicher Output → dosierbar). Repair-Button bekommt den AP-Kosten-Chip; AP-Eingabe-Modal entfernt. Controller zieht echtes 1 Bau-AP ab (vorher kein AP-Lock — GDD-Lücke geschlossen). GDD §8 + design-guide §5.5 aktualisiert. Tests angepasst (Service/Controller, obsolete AP-Mengen-Tests entfernt). Dispatch/Nexus-Anfrage bleiben dosierbar (kontinuierlicher Bonus).
- **In-Run-Screens gegen Pending-Runs abgesichert** — neue Middleware `run.started` (`EnsureRunStarted`) leitet auf die Lobby um, wenn kein aktiver Run mit `started_at` existiert (frisch erstellter Run ist `active` aber pending bis „Mission starten"). Behebt die Sackgasse, dass man die Kolonieansicht eines noch nicht gestarteten Runs öffnen konnte — ohne Sol-Button (Run-UI ist auf `started_at` gated). Gilt für colony/techtree/advisors/comm-log/nexus-db (Screens); `sol.next` behält seine eigene 404-Logik. AJAX-Aufrufe erhalten 409 + `redirect`. Info-Alert in der Lobby + `layouts.infra`.

- **AP-Kosten-Chips an Aktionsbuttons** — jeder AP-verbrauchende Button zeigt die Kosten vorab als Chip (optisch wie die AP-Chips der Resource Bar: Bau=grün, Nav=blau). Wiederverwendbares Partial `partials/ap-cost-chip.blade.php` (`amount`+`type` oder `label`). Colony-Buttons umgesetzt: Erkunden (1 Nav), Sondieren (2 Nav), Reparieren/Ausbauen/Bauen (1 Bau), Verlegen (1 AP/Feld, distanzabhängig). Button-Layout auf Flex-Row (Label links, Chip rechts) umgestellt; redundante „1 AP"-Sub-Labels entfernt. Konvention in `docs/design-guide.md` §5.5 verankert (gilt screen-übergreifend). Render-Test ergänzt.

- **Regressionstests Colony-Sidebar** — `BuildingInvestTest` (Levelup: AP-Fortschritt, Level-Schwelle setzt `ap_spend`→0 + Zustand→max, max_level-Block, Protokoll-Event) und `ColonyViewTest` (Render-Smoke: `colony.view` liefert 200 mit Tab-Markup + Repair/Invest-Wiring). Schließt die Coverage-Lücke für den Levelup-Endpoint; verifiziert die Logik hinter den manuellen Repair-/Levelup-Checks. 6 Tests, gesamt 647 grün.

- **Pre-commit Lint-Hooks** — `.githooks/pre-commit` lintet vor jedem Commit: PHP via Laravel Pint (Auto-Fix), JS/CSS via Prettier (Auto-Fix), Blade via Prettier `--check` (blockt, kein Auto-Write — Plugin zu aggressiv für Alpine-Templates). Aktivierung pro Clone: `npm install && git config core.hooksPath .githooks`. Einmalige Baselines: Prettier über `public/js`+`public/css`, Pint über `app/tests/config/lang/routes/tools` (`database/migrations` dauerhaft ausgeschlossen). Configs: `.prettierrc.json`, `.prettierignore`, `pint.json`. Lint-Konventionen in `docs/code-style.md` dokumentiert; alle Code-Subagenten (`backend-coder`, `game-developer`, `ui-specialist`, `db-migration-agent`, `content-writer`, `qa-tester`) um einen „Code-Style (Linter)"-Block ergänzt (Kernregel: nie vertikal ausrichten — Pint/Prettier kollabieren ausgerichtete `=>`/Keys).
- **Tile-Sidebar Refactoring (Clean Code)** — Markup/CSS-Gerüst der Sidebar bereinigt: `selectedBuilding`-Getter ersetzt ~20× wiederholtes `buildingForTile(selectedTile)`; Prozent-Logik in Helfer `conditionPct`/`apProgressPct`/`resourcePct` statt dupliziertem Inline-`Math.round`; `buildingCanLevelUp`-Helfer statt wiederholter Level-Bedingung; redundanter Wrapper `tile-info-container` aufgelöst (x-effect/Swipe direkt auf `tile-tab-body`); Inline-`style` entfernt; Klassennamen vereinheitlicht (`sidebar-* → tile-*`, nur sidebar-lokale Klassen; geteiltes `building-detail`/`sidebar-level-badge` unberührt). CSS: tote Regeln (`.sidebar-section-title`, `.sidebar-building-name`, `.build-btn*`) und überschattete Duplikate (`.tile-panel dl/dt/dd`, `.tile-panel-body h3`) entfernt, verwaiste schließende Klammer im sol-overlay-Block korrigiert.
- **Tile-Sidebar in Tabs aufgeteilt** — bei bebauten Tiles trennen jetzt zwei Tabs „Gebäude" (Default) und „Terrain" den Inhalt, statt alles untereinander zu stapeln (Gebäude-Info rutschte vorher unter die Terrain-Info, Scrollen nötig). Tabs nur sichtbar wenn ein Gebäude auf dem Tile steht; leeres Terrain zeigt seine Info direkt. Mobile: horizontales Swipen wechselt die Tabs. Doppelte `TYP`-Zeile entfernt (Überschrift deckt sie ab). Redundante „TILE-INFO"-Kopfzeile in der normalen Tile-Ansicht ausgeblendet (nur noch im Bau-/Harvester-Modus). Action-Buttons stapeln jetzt vertikal in voller Breite → Repair-Sub-Label („1 AP → +5 % Zustand") bricht nicht mehr um.
- **Repair-Mechanik implementiert** — neuer Endpoint `POST colony/building/repair`: 1 Bau-AP stellt 1 Status-Punkt wieder her (Gates: Gebäude existiert, nicht im Bau, nicht voll instand). Colony-View zeigt zwei getrennte Buttons im Action-Strip: „Reparieren · X %" (nur bei Beschädigung, primär) und „Ausbauen · X/Y AP" (vorher ein doppeldeutiger „AP investieren"-Button, der bei beschädigten Gebäuden fälschlich Levelup-AP buchte — Sol-1-Trap behoben). Fortschritt direkt im Button-Label sichtbar (kein Scrollen nötig). Protokoll-Event `colony.building_repaired` + Comm-Log-Beschreibung. Bau-/Nav-AP-Chips pulsieren beim Sinken (Flash-Animation). 9 Feature-Tests (`BuildingRepairTest`).
- **Colony-Header entschlackt** — Canvas-Header-Zeile („Kolonie"-Titel + Statuszeile) entfernt (redundant zum Nav-Titel); AP-/Vertrauen-Chips ans Ende der Ressourcenleiste verschoben (Sync + Flash via DOM-IDs aus colony-hexgrid.js, da außerhalb des Alpine-Scopes); Nav-Leiste teilt sich jetzt die Zeile mit dem Logo (`flex:1` statt Komplett-Umbruch); Merchant-Hinweis schwebt oben rechts über dem Grid; tote Bootstrap-Utility-Klassen aus resourcebar-Partial entfernt.
- **Nav-Fix (Desktop schmal)** — Nav-Items brechen nicht mehr innerhalb des Items um (`white-space: nowrap`); unter 1100px werden die Nav-Labels ausgeblendet (Icon-only), ab 1100px Icons + Labels — kein Umbruch, kein Scrollen, Header-Höhe stabil (Design-Guide-Ladder aktualisiert: Burger < 600, Icon-only 600–1099, Labels ≥ 1100). Nav-Gruppe zentriert zwischen Logo und Benutzername; Icon-only-Zellen mit einheitlichem Padding, Unread-Badge dockt oben rechts ans Icon statt das Item zu verbreitern. Schloss-Icons an gesperrten Nav-Items (Cantina/Hangar) entfernt — Ausgrauen + Flyout-Sperr-Grund reichen. Nebenfund behoben: `.nav-link-locked`-Basisstyle (Dimmen) lag in der gelöschten style.css und fehlte im colony-Layout — in colony.css wiederhergestellt.
- **Code-Leichen entsorgt** — `PersonellService::assignCommander/removeCommander` (FleetController-Abhängigkeit, seit PR #172 ohne Aufrufer) + `CommanderAssignmentServiceTest` gelöscht; `public/js/techtree.js`, `public/css/techtree.css` (nie in colony-Layout geladen), `public/js/nouron.js` (Bootstrap-Tooltip-Init), `public/js/innn.js` (Bootstrap-abhängig, nirgends mehr geladen) gelöscht; stale `techtree.js`-Kommentare in drei Techtree-Partials bereinigt.
- **Infra-Screens auf PicoCSS migriert** — Login, Register, Lobby, Run-Result, User-Profil/-Einstellungen, 404/500 verwenden jetzt `layouts.infra` (neues Layout: Pico, Alpine.js, Bootstrap-Icons, kein Bootstrap). Lobby-Inline-CSS (~280 Zeilen) nach `public/css/lobby.css` extrahiert. `layouts/app.blade.php` und `public/css/style.css` gelöscht — Bootstrap vollständig entfernt. Debug-Bar (Admin) in `layouts.colony` übernommen. `colony-hexgrid.js` nur noch auf `colony.view` geladen (war global). `a.technology.btn` + `resicon-*` Styles in `techtree-view.css` verschoben (waren in style.css, aber nie für colony-Layout geladen).

## 2026-06-12

- **Legacy-Spiel-Screens entfernt** — Flotte (Liste + Konfiguration), Rohstoff-Handel und Galaxis-/Systemkarte (Leaflet) komplett gelöscht: Views, Routes (`fleet.*`, `trade.*`, `galaxy.*`), Controller (Fleet/Trade/Galaxy), `TradeGateway`-Service samt Tests sowie Assets (galaxy.js/css, fleets.js/css, fleet-config.css, trade.js). `FleetService`/`GalaxyService` bleiben (Tick). Navbar-Links + fleets.js-Init aus dem App-Layout entfernt, Brand-Link zeigt auf die Lobby.
- **Kolonie-Umbenennen in Lobby verlagert** — alte `colony/index`-Seite gelöscht; Pending-Run-Karte in der Lobby enthält jetzt das Umbenennen-Formular (`colony.rename`-Redirect → Lobby).
- **Mobile-First-Audit** — komplettes Frontend auditiert; Findings als Task-Liste: 100dvh-Umstellung, Touch-Targets, Popup-Touch-Verhalten, Breakpoint-Konsolidierung, Infra-Screens-Migration auf PicoCSS (danach Bootstrap-Entfernung).
- **Mobile-First-Fixes (Audit-Tasks)** — `100vh` → `100dvh` mit Fallback (Hex-Layout, Cantina-Viewport, Hangar-/Berater-Karten, Bar-Backdrop via `inset:0`); Swipe-/Carousel-Dots auf 24px-Hitbox vergrößert (sichtbarer Punkt via `::before`); Chip-Popups schließen per `@click.outside` (7 Stellen); gesperrte Nav-Items (Cantina/Hangar) zeigen Schloss-Icon, im Mobile-Flyout zusätzlich den Sperr-Grund als Subzeile.
- **Breakpoints konsolidiert** — verbindliches Set 599/767/899 (max-width) bzw. 768/900 (min-width); Techtree 640/480 → 599 (CSS + `isMobile`-JS), `max-width:900` → 899 (Doppel-Match bei exakt 900px behoben); neue Sektion „Responsive Breakpoints" im Design Guide inkl. Mobile-First-Konvention.

- **Harvester-Transit (1-Sol Verzögerung)** — Verlegen setzt `pending_until_tick` (Migration); in-transit Harvester produziert nicht (`GameTick`), ist nicht erneut verlegbar; Transit-Badge "HV →" im Grid; Controller blockiert Doppelmove; 6 Feature-Tests (`HarvesterTransitTest`).
- **Baumeister-Dialog neu gestaltet** — Hire-Dialog zeigt Portrait, Name, JUNIOR-Badge, Beschreibung, AP-Typ, Einmalkosten, Unterhalt/Sol; PicoCSS-`<dialog>`-Override behebt Fullscreen-Bug; `AdvisorController::buildSlots()` liefert `desc`, `junior_ap`, `junior_upkeep`.
- **Mobile: Colony-Zone-Viewport & SVG-Pan** — Hex-Grid clippt ViewBox auf Colony-Zone + Ring-3-Randstreifen (größere, tappbare Tiles); Touch-Drag verschiebt ViewBox um Ring-3-Tiles (Regolith-Ziele) zu erreichen; Pan-State überlebt Redraws.
- **Mobile-First Layout** — Nav-Logo zeigt Seitennamen ("Kolonie") statt "NOURON"; Statuszeile, "TILE-INFO"-Header, Koordinaten-Zeile ausgeblendet; AP-Header-Row bricht auf Mobilgeräten korrekt um (kein horizontaler Overflow).
- **Tile-First Build-Flow** — "Bauen"-Button aus Header entfernt; Tile antippen → Action-Strip zeigt "Bauen" (wenn bebaubar + leer); Klick öffnet Gebäudeliste; Gebäude wählen platziert sofort auf vorgemerktem Tile. Flow gilt für alle Screens (Mobile-first-Prinzip).
- **Action-Strip über Tile-Info** — Kontext-Aktionen (Erkunden, Sondieren, AP investieren, Verlegen, Bauen) immer oben im Tile-Panel sichtbar, kein Scrollen nötig.

## 2026-06-11

- **Harvester-Verlegung im Frontend** — "Verlegen"-Button am Harvester-Tile startet Move-Mode: gültige Ziele (erkundete, freie Regolith-Tiles) blau markiert, Hover zeigt gestrichelte Pfeil-Vorschau vom Harvester zum Ziel, Klick verlegt mit Move-Animation (1 Bau-AP pro Hex-Distanz). Ohne verfügbares Ziel zeigt das Panel einen Hinweis ("erst neue Tiles erkunden"). Bugfix dabei: Harvester (`is_instanced=1`) wurde beim Verlegen als neue Instanz eingefügt statt verschoben — Controller behandelt Harvester jetzt explizit als Move (UPDATE).
- **Alpine-`$refs`-Bug im Hex-Grid behoben** — `redrawGrid` über Buttons in `x-if`-Templates schlug fehl, weil `$refs.hexgrid` nach DOM-Entfernung des Buttons nicht mehr auflösbar war → Grid wurde im Move-Mode nie neu gezeichnet (kein Highlight, kein Pfeil). Grid-Container wird jetzt in `init()` gecacht. Zudem Cache-Busting (`?v=filemtime`) für `colony-hexgrid.js`.
- **Koloniezone via CC-Level freigeschaltet** — Ring 2 ist ab Start erkundet, aber erst ab CC Level 2 bebaubar (`assignColonyZone` beim Setup verdrahtet). Regolith existiert nur außerhalb der Koloniezone; vorerkundetes Ring-3-Tile (3,0) als garantiertes erstes Harvester-Ziel. Tile (1,0) in der Koloniezone von Regolith auf Terrain korrigiert.
- **`game:reset-player` zuverlässig gemacht** — Auto-Seed wenn Dev-DB leer ist (kein "User not found: Bart" mehr nach `migrate:fresh`). Zwei stille Phantom-Deletes gefixt: `locked_actionpoints` (Spalte heißt `scope_id`, nicht `colony_id`) und `run_objectives` (keyed by `run_id`) — SQLite interpretiert unbekannte Spalten in WHERE still als String-Literal und löschte nichts; alte AP-Locks blieben dadurch nach Reset aktiv ("keine Bau-AP übrig").
- **UI-Detail**: AP-Investitionsbalken wird bei Gebäuden auf Max-Level ausgeblendet (Harvester Lv 1/1 zeigte verwirrend "AP investiert 0/10").

- **Sol-1 Startszenario: alle drei Gebäude Level 1 beschädigt** — CC, Harvester und Wohnhabitat starten auf Level 1 mit `status_points=16/20` (80% Zustand). Gebäude funktionieren voll, zeigen aber sichtbare Beschädigung; natürlicher Verfall macht Reparatur nach ~5–10 Sols nötig. Repair-Mechanik (AP → `status_points`) folgt in Phase 4. Hint 2 neu: "Kommandozentrale auf Level 2 ausbauen" (schaltet zweiten Berater-Slot frei) statt "Wohnhabitat fertigbauen". `game:reset-player`-Artisan-Command (Dev-Tool): setzt kompletten Spielstand zurück auf Sol 1 ohne erneute Registrierung. Weitere Sol-1-Fixes: Root-Redirect `/` → Lobby (war `/galaxy` → 404), Hint-1-Link → `/advisors` (war `/techtree/personell` → 404), Hint 4–6 nutzen Run-lokalen Sol-Zähler statt globalem Tick.
- **Kolonien ohne Planeten** (Migration): `glx_colonies.system_object_id` nullable — Koloniengründung benötigt keinen zugewiesenen Planeten mehr in `glx_system_objects`. Beide Views (`v_glx_colonies`, `v_trade_resources`) via SQLite-Recreate-Pattern migriert.

- **Playtest-Blocker Sol 1 behoben** (4 Fixes): Run startet jetzt bei `current_tick=0` statt globalem Tick; Nexus-Briefing zeigt Sol 0. `OnboardingService` seeded 19 Starttiles (Ring 0/1 colony_zone + Ring-2-Fog) inkl. Regolith-Tile für Harvester-Platzierung. Onboarding-Hint 1 = Baumeister einstellen (kein Tick-Threshold), Hint 2 = Wohnhabitat platzieren — Reihenfolge korrigiert. `lang/de/validation.php` ergänzt (war komplett fehlend → Validierungsfehler zeigten Rohkeys). Globaler CSRF-Fix in `tests/TestCase.php` behebt 138 pre-existing HTTP-Test-Failures.

- **AP-Grundwert implementiert** (GDD §13): `PersonellService::getTotalActionPoints()` addiert jetzt 6 Basis-AP für alle Bereiche (Bau/Forschung/Wirtschaft/Strategie), unabhängig von Beratern. Neuer Config-Key `game.ap.base = 6`. Verhindert Deadlocks zu Spielbeginn. Alle betroffenen Tests (`PersonellServiceTest`, `TradeApTest`) auf neue Baseline angepasst.
- **Onboarding-Startszenario überarbeitet**: Harvester (ID 27) und Wohndepot (ID 28) werden beim Setup nicht mehr auf Level 1 gesetzt, sondern als `level=0, ap_spend=7` (7/10 AP bereits investiert) und `tile_x=null` vorseeded. Spieler muss Sol 1 beide Gebäude fertigstellen und platzieren. Narrativ: Spieler übernimmt Kolonie im Aufbau. `OnboardingTest` + `OnboardingE2ETest` entsprechend aktualisiert.
- **Harvester versetzen: distanzbasierte AP-Kosten** — Kolonie-Controller berechnet Hex-Distanz (axiale Koordinaten) zwischen altem und neuem Tile; Kosten = 1 Bau-AP pro Tile-Distanz. Neuer Hilfs-Methode `hexDistance()`. I18n-String (`onboarding_trigger_harvester_move`) aktualisiert.

## 2026-06-08

- **Cantina: NPC-Portraits** — 15 Charakter-Portraits (`public/img/characters/`) eingebunden. Hotspot-Buttons zeigen Portrait-Karten (160×220px) statt Icon-Kreisen; Modal-Avatar zeigt Portrait statt Person-Icon. `colony.css`: `.has-portrait`-Modifier, `.hotspot-portrait`, `.guest-avatar__portrait`. Dev-Tool (Cantina-Tab): Charakter-Matrix zeigt Thumbnail-Portraits. `informationsagent.webp` → `information_broker.webp` (Slug-Konsistenz).

- **Supply-Redesign: Schiffe kosten kein Supply mehr** (GDD §6): `ResourcesService::getFreeSupply()` berücksichtigt Schiffs-Supply-Kosten nicht länger. `config/ships.php`: `supply_cost` aller Schifftypen auf 0 gesetzt + Docstring aktualisiert. DB via `game:sync-config` synchronisiert. Flottenausbau weiterhin begrenzt durch Hangars/Tiles, Credits, Lieferzeit und Navigator-AP. GDD §6 + §13 entsprechend aktualisiert.
- **Korvette Trust-Malus gestrichen**: `corvette.trust_per_unit` -1 → 0 — Kolonisten begrüßen Schutz, keine Strafwirkung. GDD §13 Trust-Klammer auf `[0, +30]` angepasst. `TrustServiceTest` entsprechend umgestellt.

## 2026-06-07

- **Kolonieprotokoll: reichere Log-Beschreibungen**: Entity-Namen in Log-Nachrichten werden korrekt aufgelöst — `techtree.level_down` speichert jetzt `entity_type`, `entity_name`, `new_level` direkt in den Params (kein nachträgliches DB-Raten). `resolveEntityName()` sucht fallback über Buildings/Ships/Researches-Tabellen. Gebäude-Verfall zeigt "Level für X mangels Wartung auf Y gesunken.", Schiffs-Verfall "Schiff X zerstört."
- **Cantina, Berater, Handelsroute, Tiefen-Scan**: Log-Beschreibungen zeigen jetzt Kontext — Bar-Tausch ("80 Regolith gegen 200 Credits getauscht."), Berater ohne "Berater"-Präfix mit Kosten ("Analytiker eingestellt. Kosten: 400 CR."), Handelsroute mit Erlös ("+75 CR"), Tiefen-Scan mit Koordinaten ("Sektor (2/0)"). `BarService::acceptOffer` gibt Offer-Details zurück; `AdvisorController` speichert `credits_cost` im Event.
- **i18n**: `techs_*` Forschungs-Keys (altes Konzept) aus `lang/de/techtree.php` entfernt. `knowledge_*` Kenntnisse bleiben.
- **techs_* DB-Cleanup**: 10 veraltete `techs_*`-Einträge aus `researches` + abhängige Zeilen aus `colony_researches`, `fleet_researches`, `research_costs` entfernt (Migration `2026_06_07_000001_remove_techs_researches`). `testdata.sqlite.sql` bereinigt. 6 Test-Dateien auf `knowledge_*`-IDs umgestellt. GDD §1/§7 Widersprüche bzgl. Kenntnis-Decay korrigiert: Kenntnisse verfallen nicht, Schiffe haben Decay.

- **Testdaten**: 17 neue `colony_log`-Einträge in `testdata.sqlite.sql` + Dev-DB, decken alle Event-Typen ab.
- **Phase 3k Entity-Chips** (PR #165): Neue wiederverwendbare Blade-Komponente `<x-entity-chip>` — Inline-Pills mit Hover/Tap-Tooltip (Alpine.js). `CommLogController::buildDescription()` gibt Segment-Array zurück (ADR 0002) statt String. Protokoll-Tab zeigt Gebäude, Kenntnis, Schiff, Ressource, Berater als farbige Chips mit Level + "Aufrufen"-Link → `/nexus-db`. CSS: 6 Typ-Varianten, Tooltip-Positionierung, Mobile-Responsive. Fix: verschachtelter `<a>`-Bug behoben (outer-Element immer `<span>`).

## 2026-06-06

- **Phase 3j: Kolonieprotokoll** (INNN-Redesign): INNN-Nachrichtensystem vollständig ersetzt. Neuer Screen `/comm-log` mit zwei Tabs — "Protokoll" (chronologisches Aktions- + Ereignis-Log) und "Nexus-Funk" (game-generierte Nexus-Nachrichten mit Ungelesen-Badge). Player-Messaging, Inbox/Outbox, Compose-Screen, Galaxy-News entfallen. DB: `innn_events` → `colony_log` (+`is_read`-Spalte); `innn_messages`, `innn_news`, `innn_message_types`, `v_innn_messages` View gedroppt. `EventService` setzt `is_read=false` automatisch für Nexus-Events. Colony-Nav: "Nachrichten" → "Protokoll" mit rotem Badge. 725 Tests grün.

## 2026-06-05

- **Moral → Trust/Vertrauen**: Vollständige Umbenennung — `MoralService` → `TrustService`, `game.moral.*` → `game.trust.*`, `moral_per_lv` → `trust_per_lv`, DB-Tabelle `moral_events` → `trust_events`, Resource-Slug `res_moral` → `res_trust`, `lang/de/moral.php` → `trust.php`. CLAUDE.md aktualisiert. 759 Tests grün.
- **GDD §2**: Tick-Phasen auf 5 Konzeptphasen komprimiert (Fleet / Decay / Supply & Ressourcen / Vertrauen / Beratung & Events). Detail-Reihenfolge in `GameTick.php`-Docblock als kanonische Quelle.
- **GDD §13**: Burnout-Implementierungshinweis korrigiert — probabilistische Prüfung Phase 4+; `unavailable_until_tick` existiert bereits in DB.
- **GDD §5**: Harvester-Produktionsrate — feste Rate `×10/level` dokumentiert (Phase 3); tile-abhängige Mechanik auf Phase 4+ verschoben.
- **GDD §4a**: `terrain_fog` / `terrain_locked` als UI-Render-States dokumentiert (kein `tile_type` in DB — abgeleitet aus `is_explored` + `is_colony_zone`).
- **Characters**: `informationsagent` → `information_broker` (Slug-Konsistenz; alle anderen Slugs englisch).
- **GDD §8b Hangar-Redesign**: Schiffsakquise vollständig überarbeitet — Nexus als Lieferant statt Selbstbau. 4 Akquise-Pfade: Standardkauf (Credits + Lieferzeit), Nexus-Kredit (ab CC Lv2, Trust-Penalty), Konsul-Verhandlung (AP-Rabatt 50 Cr/AP), Event/Händler. Kein Duplikat-Constraint mehr. Pending-State für Schiffe ohne Hangar-Zuweisung (Decay 5 Sole).
- **feat(hangar)**: `requestShip()` ersetzt `buildShip()`; `getPendingShips()`, `assignToHangar()` neu. `colony_ships` PK auto-increment. TickService `processHangarDeliveries()`. Config: `nexus_cost`/`nexus_delivery_ticks` in ships.php, `hangar`-Block in game.php. UI: "Nexus anfragen"-Dialog, Lieferung-State, "Nicht zugewiesen"-Sektion. 760 Tests grün.
- **feat(hangar) UI**: Nexus-Request-Dialog auf Sofort-Buttons umgestellt — je Schiffstyp (Drohne/Frachter/Korvette) ein großer Button, Klick führt Request direkt aus ohne Bestätigungsschritt. Optionale Controls (Nexus-Kredit, Konsul-AP) nur bei Verfügbarkeit sichtbar.
- **Run abbrechen**: Spieler kann aktiven Run in der Lobby freiwillig abbrechen (Run → Status `failed`). Bestätigungsdialog via `confirm()`. Route `POST /lobby/{run}/abandon`. "Run-Übersicht"-Link im Colony-Nav-Dropdown (Desktop + Mobile) ergänzt.
- **NexusDB Redesign**: Screen komplett überarbeitet — reines Spielbegriff-Glossar (Versorgung, Vertrauen, Sol, AP, Verfall, Reparatur, Nexus, Kolonisten) als zentriertes Accordion. Tabs/Gebäude/Schiffe/Kenntnisse-Inhalt entfernt. `layouts.colony` statt `layouts.app`. Sol-beenden-Button auf `colony.view` beschränkt (war auf allen Screens sichtbar).

## 2026-06-04

- **Docs-Review**: Vollständiger Audit aller `docs/`-Dateien. 21 Findings, 15 direkt behoben:
  GDD: DB-Cleanup-Status, Harvester Max-Level, bioFacility-Voraussetzung, securityHub/uplinkStation/tradingPost "geplant" entfernt (IDs 53–55), Supply-Kosten-Tabelle ergänzt, Sonde→Drohne, Korvetten-Stärkewert (1→3), Bar CC Lv1→Lv2, config/advisors.php-Referenz entfernt.
  Weitere Fixes: Veraltet-Header in Balancing-Dokument, Umlaute + "Industriemine"→"Harvester" in narrative/resources.md, 3 fehlende lang/de/buildings.php-Einträge (securityHub, uplinkStation, tradingPost), Tippfehler planet.md, design-guide fixed-top→CSS, game-reference §17-Referenz bereinigt.

**Offene TODOs (Docs-Review-Rest):**
- [ ] GDD §2 vs §6: Supply-Cap Tick-Schritt — §2 nennt Schritt 5, §6 nennt Schritt 7; `TickService` prüfen welcher Schritt korrekt ist
- [ ] GDD §5: Harvester-Produktionsrate — GDD-Text sagt "tile-abhängig", `config/game.php` hat feste Rate `× 10/level`; Design-Entscheidung klären
- [ ] GDD §13: Burnout-Config-Block — GDD referenziert `config/game.php → advisors.burnout`, der Block existiert nicht; entweder Config ergänzen oder GDD-Referenz entfernen
- [ ] lore/tiles.md: `terrain_fog` + `terrain_locked` fehlen im GDD §4a Tile-Typ-Katalog; klären ob DB-gespeicherte Typen oder nur UI-Render-States (→ dann in GDD als UI-only kennzeichnen)
- [ ] characters/informationsagent.md: Slug auf Deutsch (`informationsagent`) während alle anderen englisch sind — auf `information_broker` umbenennen (inkl. Dateiname)
- [ ] CLAUDE.md/GDD §14: Erklärung der Moral/Vertrauen-Zweigleisigkeit ergänzen (technisch `moral` in config/DB, UI-Label `Vertrauen` in lang/de)

- **Carousel-Refactor**: Gemeinsame Carousel-Primitives aus Advisors extrahiert in `carousel.css` + `carousel.js`; Berater- und Hangar-Screen nutzen dieselbe Basis
- **Hangar Mobile-Fix**: Viewport-Calc korrigiert (84px → 100px — nav 60px + resbar ~40px); Pagination-Dots auf Mobile ohne Scrollen sichtbar
- **GDD-Audit**: Flottenkommandanten-Pfad (Option A, verworfen) aus §13 entfernt; Raumfahrer als colony-scoped AP-Produzent dokumentiert; §8b Hangar-Screen neu; Design-Guide um Carousel-Screen-Typ + jQuery-Entfernung (Mai 2026) ergänzt; `trade_researches` als inaktiv markiert

## 2026-06-03

Hangar Screen (Carousel-basiert) + Cantina Character System + Dev-Panel Hotspot Tool.

- 15 NPC-Character-Sheets nach `docs/characters/` migriert (English, enriched: Background, Personality, Appearance, Dialogue Tone, Cantina Placement)
- Image-Gen-Prompts extrahiert nach `.prompts/images/characters/` (abgeleitet von Character Sheets); `_config.json` 2:3 Format
- Alte `.md`-Dateien aus `public/img/characters/` entfernt (nur noch `.webp`-Assets dort)
- **Hangar Screen**: Carousel-View (1 Karte pro Hangar-Instanz, Swipe-Navigation); Aktionen: Schiff bauen, Entsenden, Zurückrufen, Reparieren; Raumfahrer-Badge wenn Pilot-Berater aktiv
- `HangarService`: `getHangarSlots`, `buildShip`, `dispatchShip`, `recallShip`, `repairShip`; Missionslog via `colony_hangar_missions`-Tabelle
- DB: `colony_ships` um `hangar_instance_id` + `ship_state` erweitert; neue Tabelle `colony_hangar_missions`
- 53 neue Tests (HangarServiceTest + HangarControllerTest)
- `data/cantina_hotspots.json`: 6 generische Spots (spot_0–5), Koordinaten per Device (desktop/tablet/mobile), Character-Zuweisung pro Spot
- `bar.blade.php`: Hotspot-Positionen aus JSON statt hardcoded; CSS-Klassen `hs-slot-spot_*` via `@push('styles')` pro Breakpoint
- Dev-Panel: neuer Tab "Cantina Hotspots" — visueller Positionseditor (Klick auf Bild setzt %) + Character-Mapping-Matrix; Spot-Farben konsistent zwischen Bild-Dots und Tabelle
- Dev-Panel refactored: monolithische `dev-panel.php` aufgeteilt in `_tool_resources.php`, `_tool_techtree.php`, `_tool_cantina.php` + `tools/assets/` (CSS + JS)

## 2026-06-02

**Nexus-Datenbank** (Phase 3 — letzter offener Punkt): Statische Referenzseite für Gebäude, Schiffe und Kenntnisse.

- `NexusDbController` liest Daten aus `config/buildings`, `config/ships`, `config/game.knowledge_cc_level_cap`
- Blade-View mit Alpine.js Tab-Navigation (Gebäude / Schiffe / Kenntnisse), PicoCSS scoped via `.nexusdb-scope`
- Lang-Datei `lang/de/nexusdb.php` mit allen UI-Labels
- Nav-Link "Nexus-DB" in beiden Layouts (`app.blade.php` + `colony.blade.php`)

Cantina-Redesign (Bar-Screen): Hotspot-basiertes Viewport-Layout mit NPC-Interaktion.

- Cantina-Viewport mit Hintergrundbild (cantina-interior.webp) ersetzt altes Merchant-Section-Layout
- Hotspots (Merchant + Bar-Gäste) mit Pulse-Animation und Name-Label
- Modal/Drawer: Mobile slide-up, Desktop zentriertes Popup
- Mobile Fullscreen-View (edge-to-edge, `calc(100vh - 105px)`)
- Swipe-Panning: 300%-breiter Wrapper, 4 Positionen decken das gesamte Bild ab (Tresen links bis Tür rechts)
- `data-theme="light"` am HTML-Tag (PicoCSS Dark-Mode-Override verhindert)
- Page-Hintergrund colony-main explizit weiß
- Berater-Screen: Fullscreen-Karten auf Mobile (Pfeile versteckt, 100vw)
- Assets: `public/img/cantina/` + `public/img/characters/` (NPC-Konzepte)

## 2026-06-01

Technischer Audit durchgeführt (39 Findings). Alle kritischen und hohen Punkte behoben:

**Sicherheit:**
- serialize() → json_encode() an allen DB-Schreibstellen (RCE-Vektor geschlossen)
- user_id aus Fleet::$fillable entfernt (Mass Assignment)
- Colony-Ownership-Check in TradeController (addResourceOffer, removeOffer)
- XSS in building-detail.blade.php geschlossen ({!! →  {{ }})
- JSON in Galaxy-data-Attributen korrekt escaped (@json statt {{ json_encode }})

**Gameplay-Bugs:**
- Supply-Cap-Bug: Wohnhabitat-Instanzen werden jetzt summiert (value → sum)
- Knowledge-CC-Level-Gate implementiert (war dokumentiert aber nicht enforced)
- AP-Items vom Händler funktionieren jetzt (creditAp in PersonellService)

**Stabilität:**
- DB::transaction() in BarService::acceptOffer() (atomarer Ressourcentransfer)
- MerchantService::buyItem() atomar
- Advisor-Promotion: lockForUpdate() gegen Race Condition
- UTC in AppServiceProvider erzwungen (Tick-System-Stabilität)

**Frontend:**
- jQuery aus user.js und techtree.js entfernt (native fetch + DOM-API)
- Tailwind-Import aus app.css entfernt
- Inline-CSS in fleet-config.css und galaxy.css ausgelagert
- confirm()-Dialoge lokalisiert

**Architektur/Code-Qualität:**
- BuildingId-Enum für Magic Numbers eingeführt
- Run-Model-Scopes ergänzt
- Colony-Model: ColonyRecord für Writes, Colony für Reads
- DB-Index auf colony_resources.colony_id
- Testdata-Inkonsistenz behoben (Springfield since_tick)
- ROADMAP Phase-3-Items als [x] markiert

## 2026-05-30

- **Cantina Mobile-Swipe & Hotspots (Phase 3)**: Cantina-Screen komplett überarbeitet. Mobile Ansichten unterstützen jetzt horizontales Parallaxe-Panning des breiten retro DOS-Hintergrundbildes (`cantina-interior.webp`) via Touch-Swipes (`swipeCarousel`). Charaktere/Angebote und der Händler sind als absolute Hotspots direkt im Bild verankert und wandern mit. Klick auf einen Hotspot öffnet ein interaktives, am unteren Rand hochgleitendes Drawer-Overlay (Mobile) bzw. einen zentrierten modalen Dialog (Desktop) für die Handelsaktionen.
- **Colony-View Bugfixes (Playtest)**: Instanzierte Gebäude als Voraussetzung korrekt geprüft (`MAX(level)` statt erstem DB-Eintrag — verhinderte Cantina-Anzeige als gesperrt trotz gebautem Wohnhabitat). Levelup setzt `status_points` auf Maximum (war 0%). Build-Mode: Tile mit platziertem Gebäude anklicken verlässt Build-Mode und zeigt AP-Invest-Sidebar. Bauliste zeigt "Im Bau"-Sektion für platzierte Gebäude mit level=0. Levelup-Benachrichtigung: grüne "✓ Bau abgeschlossen: X"-Box slidet aus Bildschirmmitte hoch und fadet aus. Hint-Links gefixt (`/techtree/buildings` → 404; hint_5 → `/colony/view`; hint_6 → `/colony/view?build=52`).
- **GDD §13 — Berater als Informationsebene**: Neues Designkonzept dokumentiert ("Fog of Information"). Jeder Berater liefert QoL-Infos in seinem zugehörigen Screen (Baumeister → Decay-Prognosen, Analytiker → AP-Fluss, Konsul → Händler-Einschätzung, Raumfahrer → Reisezeitprognose, Stratege → Ziel-Erreichbarkeit). Implementierung Phase 4. ROADMAP entsprechend ergänzt.
- **Techtree Colony-Link mit Baumodus-Vorauswahl**: "Auf Kolonie bauen →" Link im Techtree übergibt `?build={building_id}`. Colony-View öffnet automatisch Baumodus mit vorausgewähltem Gebäude. AP-Invest-Leiste aus Techtree-Sidebar für Gebäude entfernt. Kenntnisse behalten AP-Leiste im Techtree.
- **Techtree Overhaul**: Berater zurück als Karten (kein Chip), korrekte Status-Anzeige (Eingestellt/Verfügbar/Gesperrt statt LV X). Instanzierte Gebäude zeigen Anzahl/Max. Schiffe zeigen count/Hangar-Kapazität. Sidebar: Berater mit AP-Typ + Kosten + Link; Gebäude mit Colony-Link; Kenntnisse mit AP-Invest-Leiste.
- **Cantina Playtest-Readiness**: Händler spawnt jetzt nur wenn Cantina gebaut (bug fix). Onboarding-Hint 6 "Cantina nicht gebaut" feuert ab CC lv2 + Housing lv1. INNN-Event bei jedem Händler-Besuch (`merchant.visit`). Cantina-Nav-Link grau + Tooltip wenn nicht gebaut (beide Layouts).
- **Ressourcen-DB-Cleanup**: ENrg (6), LNrg (8), ANrg (10) vollständig entfernt — Migration, `data.sqlite.sql`, `testdata.sqlite.sql`, `fleet_resources`, `trade_resources`, `research_costs` bereinigt.
- **Testdata-Stabilisierung**: Colony 1 auf realistischen Spielstand aktualisiert (CC lv3, Housing lv2, Depot lv3, ScienceLab lv1, Bar lv0). Werkstoffe/Organika-Startwerte von 0 auf 50 gesetzt. 12 Tests korrigiert.

## 2026-05-29 (Session 2)

- **Design Guide**: `docs/design-guide.md` erstellt — verbindliche Referenz für Farben (`#8c2030` Nouron-Rot), Typografie (Libre Baskerville für H1/H2/Logo, system-ui für alles andere), Spacing-System (8px-Basis), Komponenten (Navbar, Cards, Buttons, Chips), Screen-Typen (Lobby, In-Run, Cantina).

- **Navbar-Migration hell**: Bootstrap-Navbar von `navbar-dark bg-dark` auf helle Variante (`navbar-nouron`) umgestellt. Libre-Baskerville-Logo. Beide Layouts (`app.blade.php` + `colony.blade.php`) migriert. Colony-Layout: Techtree-Navlink ergänzt. Navbar kontextbewusst: Run-Navigation (Galaxis, Flotte etc.), Nexus-Kredit, Sol-Button nur sichtbar wenn aktiver Run + nicht auf Lobby-Route.

- **CSS-Refactor**: Alle Ressourcen-Chip-Styles in eigene `public/css/resources.css` ausgelagert (importiert von `style.css` + `colony.css`). Design-Tokens als CSS Custom Properties in `:root` (`--color-accent`, `--color-bg`, etc.). Sol/AP-Overlay-Styles ergänzt.

- **Ressourcenleiste vereinfacht**: Trust (resource_id=12) aus Bar entfernt (Duplikat mit Colony-Header). Nexus-Kredit aus Navbar in CR-Chip-Popup verschoben (hover/tap). Sol-Chip ohne Max-Wert und ohne Border. Alle Chips einheitliche Größe (`0.82rem`, kein `res-chip--primary` size-jump).

- **Chip-Popups (reusable)**: `resources/views/partials/res-popup.blade.php` — Alpine-Hover/Tap-Popup für alle Chips (SOL, CR, SUP, sekundäre, Nav-AP, Bau-AP, Vertrauen). Beschreibungstexte in `lang/de/resources.php`.

- **Sol-Button Flow**: AP-Check vor Sol-Ende via `GET /sol/remaining-ap`. Confirm-Dialog wenn ungenutzte AP vorhanden (zeigt AP-Aufschlüsselung). Ladescree (Blur-Overlay, Spinner, min. 5 Sekunden). `partials/sol-button.blade.php` als wiederverwendbarer Alpine-Komponent für beide Layouts.

- **Lobby-Fixes**: Dunkle PicoCSS-Karte → hell (`data-theme="light"`). Sol 1938/100 → gecappt auf tick_limit. Run-Nav + Ressourcenleiste auf Lobby-Route ausgeblendet.

- **Techtree-Verbesserungen**: Onboarding-Hint 4 Route `/techtree/research` → `/techtree` (404 gefixt). Hint-Text klarer formuliert. AP-Hinweis unter Progress-Bar wenn `apAvailable = 0` ("Analytiker einstellen" bzw. "Baumeister einstellen").

- **Supply-Kosten im Bau-Dialog**: `supply_cost` wird jetzt im Gebäude-Bau-Panel angezeigt (`X SUP` Badge, nur bei supply_cost > 0).

- **Testdata Springfield bereinigt**: Von korruptem Viel-Gebäude-Stand (Usage 70 >> Cap 26) auf validen Sol-5-Startstand zurückgesetzt — CC Lv1, Harvester Lv1, 1× WH Lv1, Baumeister-Berater, Credits 2700, Supply 18.

- **`game:validate-colony` Artisan Command**: Prüft aktiven Run, Supply-Cap vs. Usage, CC-Level, Trust-Ressource, Tick-Sanity. Exit-Code 1 bei Fehlern (CI-fähig). Aufruf: `php artisan game:validate-colony [colony_id]`.

- **image-gen crop-Feature**: `tools/image-gen/generate.py` — center-crop nach Resize über `crop`-Key in Kategorie-Config.

- **Messages-Screen auf neues Design migriert**: Von `layouts.app` (Bootstrap) auf `layouts.colony` (PicoCSS/Alpine). Bootstrap-Accordion durch Alpine `x-data`/`x-show` ersetzt. Tabs-Partial mit neuem `msg-tabs`-Stil. `messages.css` als eigene CSS-Datei extrahiert (geladen via `@push('styles')` im Tabs-Partial). `msg-*`-CSS aus `resources.css` entfernt (gehört dort nicht hin).

- **Navbar Icon-only auf Mobile**: Nav-Label-Text in `<span class="nav-label">` gewrappt — auf Mobilgeräten (< 768px) via `swipe.css` ausgeblendet, nur Icons bleiben sichtbar. Gilt für beide Layouts.

- **Swipe-Infrastruktur**: `public/js/swipe.js` — zwei Alpine-Komponenten: `swipeNav({prev, next})` (URL-Navigation via Swipe) + `swipeCarousel(count, initial)` (In-Page-Panels, für Berater/Cantina/Hangar geplant). `public/css/swipe.css` — Container, Track, Panel, Dot-Indikatoren. Geladen in `layouts.colony` + `layouts.app`.

- **Messages Swipe-Navigation**: Alle Messages-Views (`inbox`, `outbox`, `archive`, `events`, `news`) haben `swipeNav`-Wrapper — auf Mobile zwischen Tabs hin- und herwischen (Eingang → Ausgang → Archiv → Ereignisse → INNN).

- **Hamburger-Menü (Mobile)**: Unter 600px kollabiert die Colony-Navbar zu Logo + Hamburger-Icon. Flyout-Menü mit allen 5 Nav-Links + Profil/Einstellungen/Abmelden. Hamburger via Alpine `@click.outside` schließbar.

- **Sol-Button Mobile**: Auf Mobile nur auf der Kolonie-View sichtbar (`body.page-colony`). Auf Messages, Techtree, Berater, Cantina ausgeblendet — Sol beenden ist die zentrale Hub-Aktion der Kolonie-View.

- **Messages Mobile**: Tabs-Bar unter 600px ausgeblendet. Stattdessen: aktueller Tab-Name + 5 Dot-Indikatoren. Swipe-Logik via window-level vanilla JS (zuverlässiger als Alpine für touch-Events).

## 2026-05-29

- **PR #142 Review-Fixes**: ROADMAP Phase-4-TODO Objective Discovery: Sol +5 auf Sol +15 korrigiert (war nach §17.1-Timing-Korrektur nicht mitgezogen). Hook-Kommentar in `pre-merge-check.sh` präzisiert (kein PR-Description-Check vorhanden). `advisor_dialogs.status`-Semantik geschärft: `declined` = explizite Spieler-Ablehnung, `expired` = automatischer Verfall durch Postpone-Maximum oder Timeout — in GDD §17.2 und `game-reference.md` konsistent dokumentiert. CHANGELOG doppelte Leerzeile entfernt. CLAUDE.md abschließendes Newline ergänzt.

## 2026-05-28

- **GDD §17 — Progressive Discovery System**: Drei neue Designmechaniken als roter Faden durch das Spiel festgehalten. §17.1 Objective Discovery: Phase-2-Objectives nicht sofort sichtbar, gestaffelte Enthüllung über Sol +1/+4–5/+8–12, Fallback bei Sol +15 nach Phase-2-Start. §17.2 Advisor Dialogs: Berater als aktive Akteure mit Multi-Sol-Dialogen und AP-Kosten. §17.3 Almanach Unlock: Artikel per Run-Fortschritt freischalten, Lesen gibt einmaligen Wissensbonus (ap_bonus, resource_bonus, knowledge_hint, encounter_prep). §17.4 Implementierungshinweise: 3 neue Tabellen (advisor_dialogs, almanac_articles, run_almanac_unlocks), empfohlene Implementierungsreihenfolge. Phase-4-Eintrag in ROADMAP ergänzt. Nur Dokumentation, kein Code.

- **ROADMAP Phase 4 bereinigt**: Drei veraltete Einträge korrigiert: Rassen-System als abgekündigt markiert (GDD §3, DB-Cleanup-Eintrag ersetzt TODO); klassisches Diplomatie-System (Krieg/Allianz) auf NPC-Vereinbarungen reduziert (inkompatibel mit Singleplayer-Roguelike, GDD §1.1); Nexus-Außenposten-Slot aus Phase 4 entfernt (steht bereits als Phase-5-Hypothese).

- **Sprint B — Run-System Phase 2 (Playtest-Voraussetzung)**: 5 weitere Aufgaben-Typen: `task_self_sufficiency` (Streak: Regolith + Organika + Supply), `task_expedition_coverage` (Counter: erkannte Colony-Zone-Tiles), `task_engineering_output` (Counter: SP-Summe Gebäude), `task_trade_volume` (Counter: gekaufte Händler-Items im Run), `task_combat_record` (Counter: Kampfsiege im Run). Combo-Blacklist: max. 1 Economy-Aufgabe pro Ziehung. Nexus-Interventionen: Warnungen bei Sol-30/50, Berater-Sperre bei Sol-65 (bei 0 Aufgaben), Countdown bei Sol-80. Nexus-Schulden-Fail-State (> 12.000 Cr). UI: Highscore-Tabelle in Lobby (letzte 10 Runs), vollständiger `newRun()`-Flow (Kolonie-Reset inkl. `colony_researches`), Nexus-Kredit-Badge in Navbar (grau/gelb/rot). DB: `innn_events.created_at` ergänzt. QA-Review: Credits/Supply wurden aus falscher Tabelle gelesen (`colony_resources` statt `user_resources`) — gefixt; Score-Clamp auf 0. Task-Keys von Deutsch auf Englisch umbenannt (CLAUDE.md-Konvention). 57 neue Tests, 613 gesamt grün.

- **Sprint A — Run-Struktur (Playtest-Voraussetzung)**: Phase-1-Erkennung (CC Lv3 + 2 Produktionsgebäude Lv2+ + 3 Berater), Phase-2-Start mit Aufgaben-Zuweisung (4 trackbare Typen: Expertenstab, Kreditimperium, Kolonieblüte, Forschungsvorsprung), Aufgaben-Fortschrittstracking pro Sol im GameTick, Sieg-Bedingung (2/3 Aufgaben), Fail-States (Vertrauen < -20, Zeitablauf). Ergebnis-Screen (`/run/{id}/result`) mit Score, Fortschrittsbalken, Sieg/Niederlage-Feedback. DB: `run_objectives`-Tabelle, `phase`/`fail_reason` auf `runs`. 20 neue Tests, 576 gesamt grün.

- **Kommandanten-Zuweisung (Fleet)**: Raumfahrer-Berater kann einer Flotte als Kommandant zugewiesen und abberufen werden. DB-Migration re-added `fleet_id`/`is_commander` auf `advisors`-Tabelle. `PersonellService::assignCommander/removeCommander`, zwei neue Routes (`fleet.commander.assign/remove`). `fleet/config` zeigt Commander-Sektion (Zuweisung, Abberufung, Verfügbarkeitscheck). Berater-Screen zeigt "Auf Flotte"-Chip wenn Raumfahrer als Kommandant aktiv. GDD-TODO §14 erfüllt. 16 neue Tests.

- **CLAUDE.md bereinigt**: Veraltete Statusangaben korrigiert — Berater-Screen, jQuery-Migration und Onboarding-System als abgeschlossen markiert. Abgeschlossen-Liste um Run-System, Lobby, Debug-Bar, Fleet Command Overlay ergänzt. Ausstehend präzisiert: Onboarding-Wizard (kein dedizierter Flow), Kommandanten-Zuweisung UI, Ressourcen-DB-Cleanup (ENrg/LNrg/ANrg).

## 2026-05-23

- **Run-System + manueller Sol-Trigger**: `runs`-Tabelle eingeführt (`current_tick`, `status`, FK auf User + Colony). `Run`-Model mit `scopeActive()`. `TickService`-Singleton verwendet bei Web-Requests `runs.current_tick` statt timestamp-basierter Berechnung. `GameTick`-Command nimmt `--run=N` entgegen. `SolController` (`POST /sol/next`): inkrementiert `current_tick` atomar, feuert sofort `game:tick`. "Nächsten Sol starten"-Button in Bootstrap-Navbar. Fixer `dailyAt('03:00')`-Scheduler entfernt (als Kommentar erhalten als Multiplayer-Referenz).

## 2026-05-18

- **Reisender Händler in Cantina**: Händler-Dialog aus Hexview entfernt und vollständig in Cantina-Screen (`colony.bar`) integriert. `BarController` lädt `merchantVisit` + `merchantItems`, Alpine-Komponente mit Item-Loop, Kauf-Button, Toast-Feedback. Bug-Fix: `x-data`-Attribut verwendete doppelte Anführungszeichen — JSON-Strukturzeichen terminierten Attribut frühzeitig (SyntaxError). Fix: einfache Anführungszeichen für HTML-Attribut, `@json()`-Routen als Parameter übergeben.
- **Advisor-Exploit behoben**: Berater konnte im selben Sol eingestellt, gefeuert und sofort wieder eingestellt werden — Credits wurden mehrfach abgezogen. Fix: `PersonellService::fire()` setzt `unavailable_until_tick = currentTick`, `hire()` lehnt Wiedereinstellung im gleichen Tick ab (`dismissed_this_tick`). Fehlermeldung in `lang/de/advisors.php` ergänzt.
- **Vertrauens-Abkürzung korrigiert**: Ressource 12 (Vertrauen) hatte Abkürzung `M` (veraltet) → `Tr` (englisch, konsistent mit anderen Bezeichnern). CSS-Klasse `.res-M` → `.res-Tr` in `style.css` und `colony.css`. Testdaten aktualisiert.
- **Navigation vereinfacht**: Galaxis, Flotte und Techtree aus Colony-Nav entfernt. Neue schlanke Nav: Kolonie · Berater · Cantina · Nachrichten. Logo-Link → `colony.view`. Techtree später über Kolonie-Tiles erreichbar.
- **Händler-Benachrichtigung in Nav**: Hexview-Händler-Dialog entfernt, stattdessen `🛸 Händler im System`-Link in Nav-Leiste (Alpine `x-show="hasMerchant()"`, nur sichtbar bei aktivem Besuch).
- **Sol-Anzeige behoben**: `currentSol` zeigte globalen Tick (~20591) statt run-lokalen Sol. Fix: `min($solLimit, max(1, $globalTick - $sinceTick + 1))` in `AppServiceProvider`. Testdaten: `since_tick = 20585` → Sol zeigt korrekt ~6/100.
- **Tests**: `MerchantServiceTest` (22 Tests), `MerchantControllerTest` (8 Tests) neu. `BarControllerTest`, `AdvisorPromotionCostTest`, `BuildingServiceTest`, `TechtreeControllerTest` angepasst (self-contained setUp ohne Testdaten-Abhängigkeit für Berater).

## 2026-05-17

- **Vertrauensanzeige + Sol-Nummer**: Colony Hexview zeigt jetzt aktuelle Sol-Nummer in der Statuszeile (`Sol 42 · X/Y Tiles erkundet · CC Level N`) sowie Vertrauens-Chip mit Farbindikator (grün ≥ 20, grau 0–19, rot < 0). Bar-Screen: "Tick" → "Sol" korrigiert.
- **Reisender Händler** (`MerchantService`, `MerchantController`): Händler erscheint ab Sol 15–20, danach alle 10–15 Sole für je 2 Sole. Bietet 3 Items (Reparatur-Kit, Vertrauensschub, Systemkarte, AP-Paket). Alpine-gesteuerte `<dialog>`-Modal im Hexview mit Kauffunktion (`/colony/merchant/buy/{id}`). GameTick (Schritt 11) spawnt Besuche automatisch. DB: `merchant_visits` + `merchant_items` Tabellen.
- **Sol-Terminologie**: Player-facing "Tick" → "Sol" in `lang/de/` (buildings, colony, fleet — 5 Strings) und `docs/GDD.md`. Intern bleiben `TickService`, `game:tick`, DB-Spalten und Config-Keys unverändert. Spielzeit heißt jetzt offiziell **Sol** (angelehnt an NASA-Terminologie für Marssonnentag).
- **Dev Panel (`tools/dev-panel.php`)**: Kombiniertes Browser-Tool, löst `techtree-editor.php` und `resource-editor.php` ab. Tab-Navigation: **Resources** — Credits, Supply, Regolith, Werkstoffe, Organika, Vertrauen für beliebige User/Kolonie setzen ohne SQL. **Techtree** — Drag-and-Drop-Editor für Techtree-Positionen (phase/row/column). Ein Port statt zwei: `php -S localhost:8081 tools/dev-panel.php`.
- **Tick-Dry-Run (`game:tick-dry-run`)**: Artisan-Command simuliert einen Tick ohne DB-Schreibzugriff. Zeigt Credits-Delta (Nexus/Housing/Berater-Upkeep), Ressourcen-Produktion mit Moral-Multiplikator, Building-Decay-Status mit farbigen Warnungen (gelb < 40% SP, rot < 20% SP / Level-Down). `--colony=ID` filtert auf eine Kolonie.
- **CHANGELOG + ROADMAP aktualisiert**: Phase 3 als abgeschlossen markiert; Phase 3h (Techtree Phase-Layout) in ROADMAP ergänzt.
- **Globale Ressourcenleiste (PR #125)**: Sol-Chip + Credits + Supply + Trust auf allen Gameplay-Seiten (`layouts/app` + `layouts/colony`). Sol run-lokal berechnet (`since_tick`-Proxy, gecappt auf `solLimit`). Deprecated Ressourcen (ENrg/LNrg/ANrg) per Whitelist gefiltert. Per-Ressource Farbchips, Bootstrap Icons in Colony-Nav. `since_tick` in Testdaten auf 20585 gesetzt (Sol zeigt ~6/100).
- **Nav-Active-Bug behoben**: `colony.*` matchte `colony.bar*` → Cantina und Kolonie gleichzeitig aktiv. Fix: Kolonie-Link schließt `colony.bar*` + `colony.merchant*` explizit aus. Aktiver Tab jetzt Hintergrund-Highlight statt Unterstrich.

## 2026-05-15

- **Lore-Dokumente erstellt (PR #119)**: `docs/lore/planet.md` (5 Planetentypen mit Ressourcenprofil + Lore), `docs/lore/tiles.md` (vollständiger Tile-Katalog mit SVG-Piktogramm-Spezifikationen), `docs/lore/ships.md` (Visual-Direction für alle 3 Schiffstypen). ADR 0001 (`docs/adr/0001-graphics-asset-format.md`): verbindliches Grafik-Asset-Format (WebP 2×, em/rem CSS, SVG-clipPath für Hex-Tiles). `lang/de/buildings.php`: Beschreibungstexte für alle 11 Gebäude ergänzt. CLAUDE.md: Grafik-Asset-Abschnitt hinzugefügt, caveman-komprimiert. Agent-Definitionen (`.claude/agents/`) auf Deutsch übersetzt.
- **Gebäude-Bildintegration (PR #118)**: Shared Blade Partial `partials/building-detail.blade.php` zeigt Gebäudebilder in Colony-Sidebar und Techtree-Panel ohne Code-Duplikat. `ColonyController` + `TechtreeController` berechnen `image_slug` serverseitig (camelCase→kebab, `bar`→`cantina` Override, `building_`-Prefix wird gestrippt). Bild läuft randlos über volle Sidebar-Breite (full-bleed via negative Margins). 11 Gebäudebilder initial in `public/img/buildings/`.
- **Image-Gen-Tool (`tools/image-gen/generate.py`)**: Per-Kategorie `_config.json` steuert `api_size`, `quality` und `resize`. `_base.prompt.md` mit Inhalt `none` deaktiviert den globalen Style-Prompt für Kategorien wie Tile-Piktogramme. `--no-base-prompt` Flag für einmaligen Override. Pillow-Resize-Support für kleine Ausgabegrößen. `.gitignore`: `public/img/_*/` und `.prompts/` ausgeschlossen — Image-Gen-Staging bleibt lokal, manuell nach `public/img/<type>/` kuratiert.
- **Bild-Prompts überarbeitet**: Gebäude-Prompts korrigiert (keine Personen außen ohne Schutzanzug; einheitlich runde Bullaugen als Fenster). Kommandozentrale erhält runden Grundriss als zentrales Kolonie-Gebäude.
- **Claude Code Skills committet**: Caveman-Plugin-Skills + `skills-lock.json` ins Repo aufgenommen.

## 2026-05-14

- **Fix: Cantina-Link in Navigationen ergänzt**: Cantina-Link fehlte in beiden Navigationsleisten (`app.blade.php` + `colony.blade.php`).
- **jQuery-Migration Schritt 2 (feat/jquery-migration-step2)**: `fleets.js` und `trade.js` auf Vanilla JS migriert. `fleets.js`: alle `$.getJSON`/`$.post`/`$(...)` durch `fetch()`, `querySelectorAll`, `addEventListener` ersetzt; CSRF-Token via `<meta name="csrf-token">` in POST-Requests eingebunden (war vorher komplett fehlend → Transfer-Funktion war kaputt); URL-Bug behoben (`/resources/json/getColonyResources/` → `/resources/colony/`). `trade.js`: gesamte jQuery/Bootbox/Bootstrap-3-Logik entfernt (war teils broken mit BS5); Stub `{ init: function () {} }` behalten. `layouts/app.blade.php`: jQuery CDN, Bootbox, `jquery.bootstrap-growl.min.js` entfernt — jQuery vollständig aus dem Projekt entfernt.
- **jQuery-Migration Schritt 1 (feat/jquery-migration)**: `galaxy.js`, `nouron.js`, `innn.js` auf Vanilla JS / `DOMContentLoaded` migriert; `fetch()` statt `$.getJSON` in `innn.js`; CSS-Animation `inbox-pulse` ersetzt jQuery fade-cycle; `techtree.js` und `leader-line.min.js` aus `layouts/app.blade.php` entfernt (dead code seit neuem Techtree-Screen); Inline `$(document).ready` in `layouts.app` auf `DOMContentLoaded` umgestellt. jQuery bleibt bis `fleets.js` + `trade.js` migriert sind.
- **Phase 3a Reste abgeschlossen (PR #114)**: Bar-Event-System implementiert — `BarService` generiert pro Tick 0–2 NPC-Angebote für Kolonien mit Cantina, Konsul-Rang steuert Angebotsanzahl und Preisrabatt, Angebote laufen nach 2 Ticks ab. Zwei Angebotstypen: Ressource gegen Credits (60 %) und Tausch Ressource↔Ressource (40 %). `BarController` + Route `/colony/bar` + Blade-View (PicoCSS + Alpine.js). Berater-Beförderungskosten: Rang 1→2 kostet 150 Cr, 2→3 kostet 400 Cr; bei fehlenden Credits wird Beförderung auf nächsten Tick verschoben. 32 neue Tests (BarServiceTest, BarControllerTest, AdvisorPromotionCostTest).
- **Phase 3g: Neue Gebäude implementiert (PR #112)**: Sicherheits-Hub (ID 53), Uplink-Station (ID 54) und Handelsposten (ID 55) vollständig implementiert. DB-Migration mit Baukosten, Config-Einträge (recycle_pct, merchant_price_bonus), deutsche Sprachschlüssel. Service-Effekte: Sicherheits-Hub reduziert `defend`-Order von 2 auf 1 Strategie-AP und recycelt beim Decay-Level-Down 10 % der Baukosten in handelbare Ressourcen zurück. Uplink-Station auf Lv2+ halbiert die Nav-AP-Kosten beim Tiefenscan (2 → 1). Handelsposten-Händler-Bonus in Config hinterlegt (Händler-System wird separat implementiert). Alle 469 Tests weiterhin grün.
- **Phase 3e abgeschlossen (PRs #110 + #111)**: Advisor-Portraits-Screen mit vollständiger `.advisor-info`-Struktur, viewport-füllendem Portrait-Layout (`calc(100vh - 230px)`, `flex: 0 0 65%`). Techtree-Pulse für Onboarding-Hinweise Rang 2/4/5 via `data-hint-rank`-Attribut + CSS-Animation. E2E-Test für den kompletten Onboarding-Flow (4 Szenarien in `OnboardingE2ETest.php`).
- **Phase 3e Schritt 6: Onboarding-Trigger-System (PR #108)**: Fünf One-shot-Trigger implementiert, die dem Spieler beim ersten Auftreten bestimmter Spielereignisse kontextbezogene Hinweise geben. Trigger werden als JSON-Array in `user_preferences.fired_triggers` gespeichert und sind idempotent — jeder Key feuert maximal einmal pro User. Drei Trigger im GameTick: `onboarding_decay` (Gebäude fällt unter 80 % SP → INNN-Event), `supply_cap_full` (Supply erschöpft → UI-Banner), `onboarding_trust` (Trust erstmals negativ → INNN-Event). Zwei Trigger im Frontend: `ap_limit_shown` (AP leer → Toast) und `harvester_move_shown` (erster Harvester-Move → Info-Toast). Neuer `OnboardingTriggerService` mit Unit-Tests (9 Tests), 11 Feature-Integration-Tests gegen echten GameTick. `.claude/settings.json` mit Subagent-Permissions für autonomes Write/Edit.

## 2026-05-13

- **Neue Gebäude im Design (feat/new-buildings-design, PR #104)**: Drei neue Gebäude als Design-Entscheidungen ins GDD aufgenommen. Sicherheits-Hub (CC Lv2, 1 Instanz): `defend`-Order günstiger (1 statt 2 Nav-AP) + Level-Down-Recycling. Uplink-Station (CC Lv2–5, Lv1–3, ersetzt frühere Relais-Station- und Sendezentrum-Idee): einheitliches Kommunikationsgebäude, das aktive Nexus-Anfragen gateted (Lv1), Exploration-Bonus und Händler-Frequenz verbessert (Lv2) und eine Run-Abschluss-Aktion ermöglicht (Lv3). Handelsposten (CC Lv4, 1 Instanz): Konsul-AP-Effizienz + bessere Händler-Konditionen. Wartungs-Depot verworfen (globale Decay-Aura bricht Entropie als USP). Config-Stubs (provisional) in `config/buildings.php` ergänzt. Tier-Gate-Tabelle §11.2 aktualisiert.
- **Agent-Definitionen überarbeitet**: backend-coder (Migration-Scope an db-migration-agent abgegrenzt, Kommentar-Policy angepasst), content-writer (`colony.php` als neues Lang-File ergänzt), game-designer (ADR-Scope geklärt), game-developer (Ressourcen-Anzahl korrekt auf 6, DB-Transaction-Pattern ergänzt). Neuer Agent: git-expert.

## 2026-05-12

- **GDD-Review vollständig abgearbeitet (chore/gdd-update-2)**: Alle 7 kritischen und 8 mittleren Punkte aus dem GDD-Review-Paket behoben. Korrekturen: CC-Expansion-Tabelle (6/3/3/2/1), Berater-AP-Werte (+4/+7/+12), typ-spezifische Einstellungskosten (300–600 Cr), Bar- und Infirmary-Decay-Rates. Neue Designdokumentation: Schiffs-Verschleiß- und Berater-Burnout-System (§7), Lagerhalle-Mechanik (Ressourcen-Cap), NPC-Encounter-Typen (§9), Nexus-Handelsschiff als INNN-Mechanik (§12), erweiterter Aufgabenpool §15 (11 Typen), run-Block in config/game.php. Begriffliche Bereinigung: "Pilot/Kommandant" → "Raumfahrer", veraltete Phase-Referenzen entfernt, Tick-Schritte neu nummeriert. Infirmary-Decay-Rate von 2.0 auf 0.67 korrigiert (Designentscheidung: Basisinfrastruktur, nicht Luxus).

## 2026-05-10

- **Techtree Phase-Layout v2 (Game-Design-Review)**: Nach Game-Designer-Analyse wurden 5 Elemente zwischen Phasen verschoben. Cantina und Händler-Berater wandern von Phase 1 → Phase 2 (Cantina als Gate für den Händler ergibt nur in Phase 2 einen geschlossenen Feedback-Loop). Krankenstation und knowledge_health wandern von Phase 3 → Phase 2 (Wohlfahrt gehört zur Stabilisierungsphase, nicht zur Militärphase; GDD §4 sagt CC Lv2 als Gate). knowledge_geology wandert von Phase 2 → Phase 3 (tiefes Abbau-Wissen passt zur Exploration-Phase). Phase 1 bleibt damit schlanker und deterministischer: housingComplex, Harvester, Bio-Anlage, Baumeister. Phase 2 wird zur vollständigen Aufbau- und Wohlfahrtsphase. Hangar + Drohne bleiben in Phase 3 (CC Lv3 als Gate ist richtig — Exploration muss erarbeitet werden). Migration 000001 v2 mit korrekter Update-Reihenfolge zur Vermeidung von Unique-Constraint-Verletzungen. 3 neue Feature-Tests für infirmary/bar Phase 2 und geology Phase 3.

## 2026-05-09

- **Techtree Phase-basiertes Layout (Phase 3h)**: Techtree-Ansicht komplett überarbeitet. Statt einer einheitlichen 6-Spalten-Karte gibt es jetzt fünf Sektionen (Phase 1–5), eine pro Kommandozentrale-Level. Jede Sektion hat ein 3-Spalten-Grid (max. 3 Spalten, alle Kategorien gemischt). Desktop: Sektionen vertikal gestapelt. Mobile: horizontales Karussell mit Wisch-Geste und Dot-Navigation. Pfeile verbinden Abhängigkeiten ausschließlich innerhalb einer Phase (keine CC-Pfeile, da der Phasen-Header das CC-Requirement kommuniziert). Bei Forschungen mit sektorübergreifender Sekundärbedingung wird automatisch auf das Phasen-interne Primärgebäude (Analytik-Labor) zurückgegriffen. DB-Migration 000003 fügt `phase`-Spalte zu allen vier Master-Tabellen hinzu und ersetzt die alten `(row, column)` Unique-Indizes durch partielle `(phase, row, column)` Indizes. TestSeeder erweitert um UPDATE-Support.

## 2026-05-08

- **Techtree-Screen Migration (Phase 3g)**: Techtree komplett auf Alpine.js + PicoCSS migriert. Neue 16×6-CSS-Grid-Ansicht mit Karten je Tech, farblichen Kategorie-Akzenten (Gebäude/Forschung/Schiff/Personal) und Status-Chips (gebaut/verfügbar/gesperrt). SVG-Bézier-Linien mit Pfeilköpfen zeigen Abhängigkeiten — grün bei erfüllter, gestrichelt-grau bei unerfüllter Voraussetzung; Scroll-Offset-Kompensation damit Linien beim Scrollen korrekt bleiben. Kategorie-Toggles blenden Karten aus (visibility:hidden, kein Grid-Reflow). Klick öffnet nativen Detail-Dialog. Aktionsbuttons entfernt (bauen/reparieren jetzt in Colony-View). 3 neue Controller-Tests (index-Route, pageData-Struktur, lines-Felder).

## 2026-05-06

- **Berater-Screen Redesign (Phase 3f)**: Berater-Screen komplett auf Alpine.js + PicoCSS migriert (war Bootstrap/jQuery). Neue Karussell-Ansicht mit 5 Portrait-Karten: Mobile zeigt eine Karte mit Swipe-Navigation, Desktop zeigt alle fünf nebeneinander. Jede Karte zeigt Rang, AP/Tick, Ticks, Aufstieg-Fortschrittsbalken und Status-Chip. Leere Slots haben Hire-Knopf, gesperrte Slots (CC-Level zu niedrig) sind ausgegraut. Hire/Fire laufen jetzt per AJAX ohne Seitenreload, mit nativen `<dialog>`-Bestätigungen. `AdvisorController` um `buildSlots()` und JSON-Branching erweitert (22 neue Feature-Tests).

## 2026-05-05

- **Koloniekarte UX-Überarbeitung (Browser-Test-Fixes)**: Ring 1 generiert jetzt ausschließlich `terrain_empty`-Tiles (kein Regolith, keine Blocker), Ring 2 hat nur seltene Hazards/Blocker. Colony-Zone-Expansion auf `[6,3,3,2,1]` geändert — Ring 1 komplett ab CC Lv1 freigeschaltet, logische Progression ohne Teilringe. CC hard cap bei Lv5: `investBuilding()` prüft `max_level`, "AP investieren"-Button wird bei Max-Level ausgeblendet.
- **Sidebar-Verbesserungen**: Level-0-Gebäude zeigen "Im Bau"-Badge statt Zustandsbalken; fertige Gebäude zeigen Zustand als Prozent. Tile-Bezeichnung "Leeres Terrain" → "Freies Feld".
- **On-Tile-Info**: Gebäude-Badge zeigt jetzt Level (z.B. "WH 1"); roter Warn-Dot bei Zustand < 10%.
- **Visuelle Hierarchie**: Unerkundete Tiles hell/ausgewaschen (fast weiß), erkundete Tiles farbiger — klares "erkundet vs. unbekannt". Erkundete Tiles außerhalb Colony Zone: gestrichelter Outline + `CC ↑`-Badge.
- **Berater-Namen korrigiert**: Ingenieur → Baumeister, Wissenschaftler → Analytiker, Händler → Konsul (lang/de/advisors.php war veraltet). Onboarding-Hint 2 entsprechend aktualisiert.

## 2026-05-04

- **GDD § 15 Onboarding ausgearbeitet**: Fünf konkrete Maßnahmen definiert — Nexus-Briefing (INNN-Event beim Run-Start, Absender "Nexus Command"), Hint-System (zustandsbasierte Hinweis-Leiste, 5 Prioritätsregeln, deaktivierbar), Pulse-Indikator (CSS-Animation `ring-pulse`, bläulich-weiß, an Hint-System gekoppelt), Techtree-Kaltstart (Kacheln nach "verfügbar / gesperrt / vorhanden" gruppiert), Inline-Erklärungen (5 einmalige INNN-Trigger pro Run: Decay, Supply-Cap, Vertrauen, AP-Limit, Harvester-Verlagerung). Technische Anforderungen, Konfigurationsblock (`config/game.php → onboarding`) und offene Design-TODOs ebenfalls dokumentiert.
- **ROADMAP Phase 3e konkretisiert**: Platzhalter durch 7-Schritt-Task-Breakdown ersetzt (Schritt 1: Infrastruktur/Config → Schritt 7: Integration/Settings). Alle Aufgaben mit Agenten-Zuordnungen und Abhängigkeitsreihenfolge versehen.
- Kein Code implementiert — reine Design- und Planungsarbeit.

- **Hint-Leiste reaktiv gemacht**: Hint-Bar aus isoliertem `@if($activeHint)`/`x-data`-Block in den `colonyHexView`-Alpine-Scope integriert. Controller-Methoden `exploreTile`, `deepScanTile`, `placeBuilding`, `investBuilding` geben jetzt `activeHint` in jeder Erfolgs-Response zurück. Neuer `resolveHint()`-Helper im Controller übersetzt den Text serverseitig (`text`-Feld). JS: `updateHint(res)` + `dismissHint()`-Methode im Component. Blade: `x-show="activeHint"` + `x-cloak` statt bedingtem Rendering. `ui-specialist.md` mit Muster-Dokumentation ergänzt.

**Phase 3e Implementierung (Schritte 1–4):**

- **Schritt 1 — Infrastruktur**: Migration `user_preferences`-Tabelle mit `onboarding_hints BOOLEAN DEFAULT 1` und `dismissed_hints TEXT nullable`. `config/game.php → onboarding`-Block mit 5 Schwellwerten. `UserController::updateOnboardingHints()` + Route `PATCH /user/settings/onboarding` + Toggle in `settings.blade.php`.
- **Schritt 2 — Nexus-Briefing**: `EventService::createNexusBriefing()` erzeugt einmalig beim ersten Login ein INNN-Event (Absender "Nexus Command"); idempotent via Guard auf `event = 'onboarding.nexus_briefing'`; serialisiert `colony_id` in `parameters`. `OnboardingService::setupNewPlayer()` ruft Nexus-Briefing am Ende der Transaktion auf. Neues Icon `bi-broadcast-pin` für `area = 'nexus'` in `events.blade.php`. Lang-Keys `onboarding_nexus_briefing`, `onboarding_decay`, `onboarding_trust` in `lang/de/events.php`. 6 neue Tests in `NexusBriefingTest.php`, alle grün.
- **Schritt 3 — Hint-System**: `OnboardingHintService` mit 5 Prioritätsregeln (kein Wohnhabitat, kein Ingenieur, Harvester auf falschem Tile, kein Wissen, Vertrauen < −20); Dismiss-Logik via JSON in `dismissed_hints`; Schwellwerte aus `config/game.php → onboarding`. API-Endpunkt `POST /colony/hint/dismiss` in `ColonyController`. Hint-Leiste in `hexview.blade.php` — gedämpft-gelb, Alpine.js, AJAX-Dismiss, `x-transition`. Lang-Keys `onboarding_hint_1`–`_5` in `lang/de/colony.php`. 17 neue Tests in `OnboardingHintServiceTest.php`, alle grün.
- **Schritt 4 — Pulse-Indikator**: CSS-Animation `onboarding-ring-pulse` in `colony.css` — bläulich-weiß, 2 s, visuell abgegrenzt vom orangen Signal-Pulse. Pulse-Hexagon-Ring in `colony-hexgrid.js` für Rang 1 (freie Terrain-Tiles) und Rang 3 (Harvester-Tile). `activeHint` wird via `window.__colonyViewData` an das Frontend übergeben.
- **Bewusst zurückgestellt**: Schritt 5 (Techtree-Kaltstart) und Pulse für Rang 2/4/5 — erst nach Migration des Techtree-Screens auf Alpine.js sinnvoll.

## 2026-05-01

- **Phase 3d Browser-Test-Fixes**: Harvester (ID 27) als instanziertes Gebäude eingeführt (`is_instanced=1`, max. 1 Instanz, Relocation via Move-Action statt Demolish). Alle 11 Buildings-INSERTs in `testdata.sqlite.sql` um `is_instanced`-Spalte ergänzt — verhindert dauerhaft, dass der Seeder migrierte Flags überschreibt. Neue Migration `2026_05_01_000001_harvester_mark_as_instanced`.
- **Bypass-Flags griffen nicht**: `GAME_BYPASS_AP` wurde in `ColonyController` (`placeBuilding`, `investBuilding`) und `ColonyTileService` (`exploreTile`, `deepScanTile`) nicht ausgewertet — AP-Checks und AP-Locks jetzt korrekt hinter `config('game.bypass.ap_checks')` geschützt.
- **Hex-Grid Farbunterscheidung**: Erkundete Terrain-Tiles außerhalb der Colony Zone (Exploration Zone) werden nun in kühlerem Grau/Braun dargestellt (`#a8aeb8` statt `#c8cdd6`, `#c8956a` statt `#e8b87a`).
- **Event-Discovery-Popup**: Nach erfolgreichem Sondieren auf einem Tile mit `event_type` erscheint ein nativer `<dialog>` ("Signal entschlüsselt") mit dem Event-Namen. Alpine.js `x-effect` + PicoCSS-Styling. Platzhalter für das spätere Event-System.
- **Subagenten-Definitionen überarbeitet**: Sprachregeln (Code = Englisch, Docs = Deutsch), Rollenabgrenzungen (`DO NOT`-Regeln), veraltete Laminas-Referenzen entfernt, Alpine.js + PicoCSS als primärer Frontend-Stack dokumentiert, `qa-tester` und `content-writer` auf proaktiven Einsatz umgestellt. `CLAUDE.md` um Sprachregeln- und Agenten-Routing-Abschnitt ergänzt.

## 2026-04-30

- **Phase 3d — Colony Zone Expansion**: `is_ring_unlocked` → `is_colony_zone` umbenannt (DB-Migration, PRAGMA-Fix für stale `v_trade_researches`-View). Koloniezone schaltet nun individuelle Terrain-Tiles frei statt ganzer Ringe — CC Lv1–5 entspricht kumulativ 4/2/3/3/3 = max. 15 Tiles (config: `game.colony_zone_expansion`). `assignColonyZone()` in `ColonyTileService` berechnet die Zone deterministisch in Ringfolge, überspringt Regolith/impassable, setzt colony-zone-Tiles automatisch auf explored. Karte auf 3 Ringe (37 Tiles) als Default reduziert. Mehrfach-Instanzen für `is_instanced=true`-Gebäude (Wohnhabitat max 6, Hangar) in `availableBuildings()` und `placeBuilding()` implementiert. CC Level-Up gibt aktualisierte Tile-Liste zurück → Frontend (Alpine) aktualisiert Grid sofort. Demo-Seed auf CC Lv5 + 3 Ringe aktualisiert. 393 Tests grün.

## 2026-04-28

- **GDD §4 Bauregeln** (`docs/GDD.md`): Harvester/Regolith-Trennungsregel formal dokumentiert. Neue Tabelle und Bullet-Regeln: Harvester darf ausschließlich auf `regolith_*`-Tiles stehen, reguläre Gebäude nur auf Terrain-Tiles. Querverweis in §4a (Kolonieoberfläche) ergänzt.
- **Phase 3c — Kolonieaktionen** (PR #93): Drei Kernaktionen implementiert: (1) **Erkunden** — Tile-Typ aufdecken für 1 Nav-AP, kontextsensitiver Button in Sidebar; (2) **Sondieren (Deep Scan)** — Event auf Signal-Tiles aufdecken für 2 Nav-AP; nur ~15–20 % der Exploration-Zone-Tiles senden ein Signal (pulsierender SVG-Indikator, Chip `chip--signal`); (3) **Bauen** — globaler Button im Canvas-Header, Gebäude-Auswahlliste in Sidebar, Platzierung auf Terrain-Tile kostet 1 Construction-AP, danach AP investieren bis Level-Up. `has_signal`-Feld in Tile-Daten: `event_type` bleibt verborgen bis Sondieren. Lokalisierung: `lang/de/colony.php` + `lang/en/colony.php` für alle UI-Strings und Fehlermeldungen. 391 Tests grün.

## 2026-04-26 (Phase 3b: Buildings-Cleanup + Colony-Sidebar Redesign + Hex-Grid Visuals)

- **Buildings-Cleanup-Migration**: 13 veraltete Gebäude (IDs 42, 45, 48, 51, 53, 54, 55, 56, 64, 65, 66, 68, 70) aus der DB entfernt. Verbleibende 11 Gebäude von `techs_*`-Keys auf `building_*`-Keys umbenannt (GDD §4). CC max_level 10→5 korrigiert. FK-Referenzen in `researches`, `ships`, `personell` bereinigt.
- **Neue deutsche Namen** (`lang/de/techtree.php`): Alle `building_*`-Keys mit GDD-konformen Bezeichnungen: Kommandozentrale, Harvester, Wohnhabitat, Lagerhalle, Analytik-Labor, Religiöse Stätte, Agrardom, Hangar, Krankenstation, Kolonialdenkmal, Cantina.
- **Colony-Sidebar Redesign**: Tile-Detail-Modal (`<dialog>`) entfernt. Alle Tile- und Gebäude-Informationen werden direkt inline in der 320px-Sidebar angezeigt — kein extra Klick mehr nötig. Neue CSS-Klassen `sidebar-*` und `tile-dl` statt `modal-*`.
- **Gebäude-Badges auf Tiles**: SVG-Renderer zeigt 2-Buchstaben-Kürzel (CC, WH, LH, AL, HG …) als dunkles Badge auf belegten Tiles. Event-Tiles erhalten orangenen Dot (top-left, nur nach Tiefenscan). Impassable Tiles werden ohne Rand gerendert.
- **Demo-Command** `php artisan colony:seed-demo [colony_id]`: Generiert 61 Tiles (Ringe 0–4); Kolonie-Zone (Ringe 1–2) nur Terrain, Exploration-Zone (Ringe 3–4) mit Regolith + Events. Harvester steht auf Regolith-Tile in Ring 3. Design-Entscheidung (Game Designer): Regolith-Tiles sind nicht bebaubar — ausschließlich für den Harvester reserviert (GDD §4).

## 2026-04-25 (Phase 3b: Colony Tile Detail Modal)

- **Tile Detail Modal** (`<dialog>` + Alpine.js, kein Bootstrap): Klick auf "Details & Aktionen" in der Tile-Sidebar öffnet ein modales Overlay mit Tile-Typ, Status-Chips, Regolith-Leiste sowie — für Tiles mit Gebäude — Name, Level-Badge, Zustandsbalken (rot) und AP-Fortschrittsbalken (grün). Gebäudenamen werden serverseitig via `lang/de/techtree.php` übersetzt.
- **Controller erweitert**: `ColonyController::hexview()` lädt jetzt `colony_buildings` gejoint mit `buildings`-Stammdaten und übergibt `$buildings` an die View. Zuweisung Gebäude→Tile: CC (building_id=25) immer bei q=0/r=0; weitere Gebäude via `tile_x`/`tile_y` (Phase 3c).
- Aktionsbuttons (Ausbauen, Erkunden) als Platzhalter vorhanden, deaktiviert bis Phase 3c.

## 2026-04-25 (Phase 3b: Colony Hex-Grid View + UI-Polish)

- **Colony Hex-Grid View** (`GET /colony/view`): neuer Game-Screen mit interaktivem SVG-Hex-Grid (Axial-Koordinaten, Pointy-top), Alpine.js State-Management, Tile-Sidebar mit Koordinaten/Typ/Ressourcen-Info. Auto-Generierung von Demo-Tiles (Ring 0–3) beim ersten Aufruf. `ColonyTile`-Model + `ColonyTileService` eingeführt.
- **Neues Layout `layouts/colony.blade.php`**: Alpine.js 3 + PicoCSS 2 via CDN, kein Bootstrap/jQuery. Helles UI (Weiß/Anthrazit/Rot), kompakte Navbar (44px), zentrierte Ressourcenleiste. Veraltete Ressourcentypen (ENRG/LNRG/ANRG) aus der Anzeige gefiltert; alle 6 neuen Ressourcen immer sichtbar, ausgegraut wenn Menge = 0.
- **Navigation** (`app.blade.php`): "Kolonie"-Link zeigt jetzt auf `colony.view`, "Techtree" als eigenständiger Nav-Eintrag ergänzt.
- **Frontend-Stack final**: Alpine.js + PicoCSS + SVG. Kein Mix mit jQuery/Bootstrap in neuen Screens. Bekanntes Problem beim Einbetten von `@json()` in HTML-Attributen gelöst (via `<script>`-Tag).
- **Fix**: `remember_token`-Spalte zur `user`-Tabelle hinzugefügt (Laravel Auth-Anforderung, fehlte nach Schema-Import). Migration + schema.sqlite.sql + testdata.sqlite.sql angepasst.

## 2026-04-23 (Design-Sprint: DS-4 Pre-Phase-3b abgeschlossen)

- **Tech-Stack entschieden:** SVG + plain JS für Spielfelder (Hex-Grid, System-Grid), Alpine.js via CDN für UI-Shell, jQuery AJAX für Server-Calls, Blade+AJAX Hybrid Backend mit 8 neuen JSON-Endpunkten.
- **Hex-Grid:** Axial-Koordinaten (q,r) + Pointy-top. Tile-Typ-Katalog (15 Typen: terrain, regolith, 10 event_*). 5 Planetentypen (rocky, desert, ice, ocean, volcanic) mit unterschiedlichen Event-Pools — alle im ersten Release.
- **colony_tiles Schema:** Neue Tabelle mit q/r/ring, tile_type, event_type (nullable), is_explored, is_deep_scanned, resource_amount. Event-Overlay nur nach Tiefenscan sichtbar.
- **Buildings:** leveled vs. instanced formal entschieden — Instanced mit instance_id als PK-Teil, Level Lv1–3 je Instanz, Config-Flag is_instanced.
- **Händler AP-Paket:** Zwei Varianten — flexibel (Spieler wählt Ziel, teurer) + gezielt (Ziel fixiert, günstiger).
- **Systemansicht:** Einheitliches 12×12-Grid (grid_x/grid_y) für Objekte und Flotten. Stern bei (6,6). spot-Feld entfällt.
- **Nexus-Schulden:** Limit 12.000 Cr, keine Zinsen, manuelle Rückzahlung, INNN-Warn bei >95%.
- **Kenntnisse:** Level-Modell (Lv1–5) beibehalten, Decay abgeschafft — GDD-Widersprüche §6/§10/§13 damit aufgelöst. Supply-Cap 200 mit CC(50)+Wohnhabitat(48)+Kenntnisse(140) erreichbar.
- GDD §4a, §4, §6, §8a, §10, §14 entsprechend aktualisiert.

## 2026-04-21 (Design-Sprint: DS-1, DS-2, DS-3 abgeschlossen)

- **DS-1 Kolonieansicht:** Hex-Grid, zwei Zonen (Kolonie + Exploration), CC-Level schaltet Ringe frei (max Lv5, Klein/Mittel/Groß = 2/3/4 Ringe), Harvester als Sondergebäude mit Tile-Position, Organika aus Agrardom, Quellen versiegen graduell. Run-Ende: Vertrauen kritisch → abgesetzt, Nexus-Schulden zu hoch → zurückgerufen.
- **DS-2 Systemansicht:** 2D top-down Grid 12×12 (unsichtbar, erscheint nur im Flottenbefehlsmodus), Scan/Tiefenscan-Erkundung, fixe Objekte (Stern, Heimatplanet, Sprungtor, Nexus-Außenposten), prozedurale Objekte pro Run.
- **DS-3 Reisender Händler:** Erscheint ab Tick 15–20, dann alle 10–15 Ticks (~6–7 Besuche/Run), 3–4 Items/Besuch, Credits-Preise, höhere Preise auf schwierigeren Runs. Item-Kategorien: AP-Paket, Schiff (mit Eigenname), Information, Einmal-Item, Exotics (Phase 4+).
- GDD §4a, §8a und §13/§14 entsprechend aktualisiert (Vertrauen statt Moral, Direktor/Direktorin als Spieler-Titel, Nexus-Narrativ).

## 2026-04-18

- Forschungshandel (`trade_researches`) vollständig entfernt — im neuen Singleplayer-Roguelike-Design nicht mehr vorgesehen; Migration `2026_04_18_000001` droppt die Tabelle, zugehörige Models/Views/Routen/Tests bereinigt
- `config/researches.php` + `ResearchService::idFor()` eingeführt (analog zu `config/advisors.php` + `PersonellService::idFor()`) — config-driven IDs statt Magic Numbers
- Test-Suite-Infrastruktur repariert: `testdata.sqlite.sql` in korrekter FK-Dependency-Reihenfolge neu sortiert (Seeder lief bisher mit `PRAGMA foreign_keys = OFF` als Workaround); SQLite-Migrationsbug gefixt (`PRAGMA legacy_alter_table = ON` vor `personell` RENAME verhindert dangling FKs in `personell_costs`)
- 393 Tests grün (vorher: 403 Errors, 0 Assertions)

## 2026-04-17 (Balancing: Schiffssystem + Berater-Einstellungskosten)

- Migration 000005: Sonde (ID 85) in `ships` eingeführt; Korvette (37) + Frachter (47) umbenannt (`ship_*`-Präfix); Schiffskosten auf 3 Ressourcen umgestellt (Credits + Werkstoffe + Organika); alte Ship-Kosten für deprecated Schiffe entfernt
- Berater-Einstellungskosten: 50 Cr → differenziert (300/400/500/350/600 Cr je Typ), alle 5 gleichzeitig kostet 2.150 Cr — echter Day-1-Tradeoff
- `config/buildings.php` + `config/ships.php`: ungenutztes `credits`-Feld entfernt (Build-Kosten kommen ausschließlich aus `building_costs`/`ship_costs` DB-Tabellen)

## 2026-04-17 (Balancing: AP-Kosten, Regolith-Baukosten, passive Credits, Berater-Upkeep)

- Migration 000003: ap_for_levelup für alle Gebäude kalibriert (CC=10, Standard=20, High-Tech=30)
- Migration 000004: Regolith als Baukosten für alle Gebäude außer CC+Harvester (40–300 Rg je Gebäude)
- GameTick: passive Credits-Einnahmen (Nexus-Subvention 30 Cr/Tick + Kolonistensteuern 20 Cr/Tick pro Housing-Level)
- GameTick: Berater-Upkeep-Abzug pro Tick (10/50/160 Cr je Rang)
- config/game.php: `credits`-Block + `advisor.upkeep` ergänzt

## 2026-04-17 (Balancing: Ressourcen, Harvester, Startzustand)

- Ferum (ID 4) → Werkstoffe (Co), Silikate (ID 5) → Organika (Or): Migration + Lang + Testdata
- industrieMine → Harvester umbenannt (config, lang)
- Bar decay_rate: 2.86 → 1.0 (von 7 auf ~20 Ticks bis Verfall)
- Startzustand: Spieler beginnt mit CC Lv1 + Harvester Lv1 vorgebaut; Startressourcen: Credits + Regolith (Werkstoffe/Organika Startwert 0)

## 2026-04-17 (Implementierung: Kenntnisse-System + GDD §14 Nexus-Mechanik)

- **7 Kenntnisse in DB eingeführt** (IDs 90–96, GDD §10): construction, cartography, geology, agronomy, health, trade, defense. Migration `2026_04_17_000001` fügt die Rows in `researches` ein. Kein Decay (decay_rate=0) — Wissen ist dauerhaft.
- **Decay-Loop überspringt Kenntnisse aktiv** (`whereNotIn` auf `knowledge`-IDs) statt still durch Rate=0 zu laufen.
- **Steigende AP-Kosten**: `levelup_costs` in `config/knowledge.php` (5/10/18/28/40 pro Level). `ResearchService` überschreibt `resolveApForLevelup()` aus `AbstractTechnologyService`; `checkRequiredActionPoints` nutzt jetzt ebenfalls diesen Hook.
- **Supply-Cap-Formel erweitert** (GDD §6): `calculateSupply()` in `GameTick` berücksichtigt `knowledge_cap_per_level`-Bonus (+3/+5/+5/+4/+3 je Level). Formel: `CC_flat(10) + housing × 8 + Σ(knowledge_bonus)`.
- **GDD §14 erweitert**: Nexus als aktiver Hintergrund-Akteur (Boni/Sanktionen an Schwellwerten, Gnadenfrist-Mechanik Tick 85→95), Tick-Konfiguration (PbM-Modus, tick_duration_hours), Milestone-Warnungen Nexus-gebranded.
- **Tests**: 8 neue Tests in `KnowledgeServiceTest`. 401 Tests grün.

## 2026-04-17 (Refactoring: Fleet-Commander-Mechanik entfernt, Test-Suite grüngestellt)

- **Fleet-scoped Berater entfernt**: `assignToFleet`/`unassignFromFleet`/`getFleetCommander` aus `PersonellService` gelöscht. Alle Berater sind jetzt colony-scoped. Flottenerstellung benötigt keinen Kommandant mehr. Migration `2026_04_16_000002` entfernt `fleet_id`/`is_commander` aus `advisors` und `can_command_fleet` aus `personell`.
- **TestSeeder**: `INSERT OR REPLACE INTO`-Fix verhindert UNIQUE-Constraint-Fehler bei Migrations-Seedern.
- **Test-Suite**: 395 Tests grün. Alle Testfälle auf neue Konfiguration (Regolith, neue Knowledge-/Ship-IDs, CC-Supply-Cap=10, unique Berater-Constraint) aktualisiert.

## 2026-04-16 (Implementierung: Regolith eingeführt, Tradecenter entfernt)

- **Regolith (Rg)** als primäre lokale Bergbau-Ressource eingeführt: Ressource ID 3 von `res_water` auf `res_regolith` umbenannt, Startwert 200, handelbar. DB-Migration + OnboardingService angepasst.
- **Industriemine** produziert jetzt Regolith (resource_id 3) statt Ferum/Werkstoffe — Config, Seeder und Testdata angepasst.
- **Tradecenter (building ID 43) entfernt**: aus config/buildings.php, MasterDataSeeder, Migration, Lang-Dateien und testdata.sqlite.sql gelöscht. Trader-Berater und Wirtschafts-Forschung erfordern jetzt Bar (ID 52) als Voraussetzung.

## 2026-04-15 (Design: Flottenbewegung — interstellare Bewegung bewusst nicht implementiert)

- **Interstellare Bewegung gestrichen** (GDD §8, ROADMAP): Flotten operieren ausschließlich im eigenen System — bewusste Designentscheidung, kein vergessenes Feature.
- **Sprungtor als narratives Element** definiert: im System sichtbar, nicht nutzbar, bewachbar (`defend`-Order). Verbindung zur Nexus-Lore (warum siedelt Nexus hier?).
- **"Gäste von außerhalb"** kommen via Events und Bar — keine Bewegungsmechanik nötig.
- ROADMAP: Flottenbewegung als erledigt markiert, interstellare Bewegung in "Bewusste Designentscheidungen"-Tabelle aufgenommen.

## 2026-04-15 (Design: Handelsmechanik — Bar als einziger Handelsort, Nexus-Fallback, Lore)

- **Handelssystem komplett redesignt** (GDD §11): Bar/Cantina ist der einzige Handelsort — NPC-Gäste, Spieler-zu-Spieler, Kauf und Verkauf alles über dieselbe Mechanik.
- **Bar-Mechanik:** 0–2 Gäste pro Tick (RNG), Angebote 1–2 Ticks gültig, Credits-gegen-Ressource und Ressource-gegen-Ressource möglich. Spieler-Angebote erscheinen anonym als Gäste.
- **Nexus-Handelsschiffe** als garantierter Fallback (immer verfügbar, teuer, 3 Ticks Lieferzeit). Händler-Berater verbessert Preis und Lieferzeit auf beiden Kanälen.
- **Tradecenter gestrichen** (war CC Lv5, zu spät, ohne eigenständige Rolle).
- **Kenntnishandel entfällt** mit Freischalt-Modell; AP-Delegation als Phase-4-Idee dokumentiert.
- **Lore-Fundament** erstellt: Nouron = untergegangenes System, Nouronen = Hochkultur, Nexus = menschliche Expansionsinstanz. Narrativ-Referenz unter `docs/narrative/`.
- **Mission-Einleitungstext** (DE+EN) mit Nexus als Instanz ausgearbeitet.

## 2026-04-15 (Design: Ressourcen-Redesign — Regolith eingeführt, Werkstoffe nur Handel/Events)

- **Regolith (Rg)** als dritte handelbare Ressource eingeführt (GDD §3): lokal abbaubar durch Industriemine, primäres Baumaterial für Gebäude. Spieler startet mit 200 Rg (Frontier-Depot-Narrativ).
- **Werkstoffe (Co)** neu positioniert: nicht mehr lokal produzierbar, nur via KI-Händler, Spielerhandel und Events. Verwendungsdomäne: Schiffbau, High-Tech, Reparatur.
- **Industriemine** produziert jetzt Regolith statt Werkstoffe (GDD §5, config/game.php TODO).
- **Klare Ressourcen-Domänen** definiert: Regolith = Rohbau, Werkstoffe = High-Tech/Schiffe, Credits = Grundkosten überall.
- **Singleplayer-Sicherheitsnetz** dokumentiert: KI-Händler garantieren Werkstoffe-Verfügbarkeit; Events sind Bonus, kein Progression-Lock.

## 2026-04-14 (Design: Kenntnisse-Redesign — Freischalt-Techtree + Berater-Zuweisung)

- **Kenntnisse-System grundlegend neu designt** (GDD §10): Level+Decay-Modell wird durch Freischalt-Techtree ersetzt — Kenntnisse werden einmalig erarbeitet und bleiben permanent. Kein Decay auf Wissen.
- **Zwei Effekt-Ebenen** definiert: Primäreffekt (immer aktiv nach Freischaltung), Sekundäreffekt (aktiv wenn Kenntnis einem Berater zugewiesen).
- **Berater-Zuweisung** als neue Mechanik: jeder Berater ab Rang 2 hat 1 Kenntnis-Slot. Max. 5 aktive Sekundäreffekte gleichzeitig (einer pro Berater). Erzeugt echte Spezialisierungsentscheidungen.
- **Roguelike-Variabilität**: pro Run nur zufällige Teilmenge der Kenntnisse verfügbar (z.B. 5 von 7).
- **Roadmap Phase 3a** um drei Design-Punkte erweitert: Kenntnisse-Redesign, Handel-Redesign, Flottenbewegung-Redesign — je mit eigenem Branch.
- Vollständige Berater×Kenntnisse-Matrix (35 Kombinationen) und konkrete Sekundäreffekt-Werte sind TODOs nach erstem Playtest.

## 2026-04-12 (GDD-Review: Inkonsistenzen behoben, techs → knowledge umbenannt)

- **CC max_level 10 → 5** in GDD §4 korrigiert (war nur noch dort veraltet).
- **GDD §2 Tick-Schritt 7** vereinfacht: Formel durch Verweis auf §6 ersetzt (single source of truth).
- **Wohnhabitat max_level 200 → 6** (max 6 Instanzen); Voraussetzung CC Lv3 → CC Lv1 (Tutorial-Schritt).
- **Leveled vs. Instanced Buildings** als TODO in GDD §4 dokumentiert. Game-Designer-Evaluation: nur Wohnhabitat und Hangar sind Instanced, alle anderen Leveled.
- **§7 Decay** bereinigt: Einleitungstext "Schiffe verfallen" entfernt; Instanced-Building-Konsequenz (sofortige Zerstörung statt Level-Down) dokumentiert; Hangar combat_factor korrekt auf Hangar-Decay (nicht Schiffs-Decay) umgestellt; Notreparatur (Credits statt AP) für CC/Wohnhabitat definiert.
- **Fail State 1** neu definiert: "Kolonie unbewohnbar" (CC Lv0 oder alle Wohnhabitate zerstört) statt vagem "Supply = 0".
- **Supply-Startwert** in §3 auf 10 korrigiert (CC Lv1, 0 Wohnhabitate); §6 Startsituation angepasst.
- **`config/game.php`** bereinigt: `supply`-Block um `cap_commandcenter`, `cap_housingcomplex`, `knowledge_cap_per_level` ergänzt; `cost_advisor` entfernt; `combat.ship_power` auf 3 aktuelle Schiffstypen reduziert; `production`-Eintrag waterextractor entfernt; Kommentare aktualisiert.
- **`config/advisors.php`**: `supply_cost`-Key entfernt (Berater kosten kein Supply); `credits` auf 50 Cr kalibriert.
- **`config/buildings.php`**: Wohnhabitat `max_level` 200 → 6, Voraussetzung CC Lv1; Kommentare aktualisiert.
- **techs → knowledge umbenannt**: `config/techs.php` → `config/knowledge.php` mit 7 neuen Kenntnissen (construction, cartography, geology, agronomy, health, trade, defense, IDs 90–96); `lang/de/techs.php` und `lang/en/techs.php` umbenannt; `SyncTechs.php` → `SyncKnowledge.php`, Command `game:sync-techs` → `game:sync-knowledge`; `MoralService` aktualisiert.
- **GDD §11 Handel**: Restriktion vereinfacht — Handel ist immer öffentlich (nur Wert 0), Werte 1–3 abgekündigt.
- **GDD §13**: Moralreferenz "Schritt 8" → "Schritt 8b" korrigiert; Querverweis auf Kenntnisse-Moraleffekte in §10 ergänzt.
- **Sonderfall "Schiffe ohne Hangar"** als TODO in §6 notiert (Events/Handelsdeals als Roguelike-Element, Phase 4+).

## 2026-04-11 (AP-1: Balancing-Review — Supply-System, Kenntnisse, Credits)

- **CC max_level 10 → 5:** Kommandozentrale hat Hard-Cap Level 5 (max. 50 Supply-Cap). GDD und config/buildings.php korrigiert.
- **CC Supply-Formel: 10 pro Level** (statt pauschal 15): Startsituation CC Lv1 + 1 Wohnhabitat = 18 Supply-Cap (vorher 23). Engerer Einstieg, stärkerer Anreiz für CC-Ausbau.
- **Kenntnisse als Supply-Cap-Quelle:** Kenntnisse kosten kein Supply mehr — sie erhöhen den Cap. Nicht-lineare Progression pro Level: +3/+5/+5/+4/+3 (total 20 pro Kenntnis, 7 × 20 = 140 max). Konfiguriert in `config/game.php → supply.knowledge_cap_per_level`. Hard-Cap 200 bleibt erreichbar, erfordert aber signifikante Investition in Breite (alle Kenntnisse Lv3 > wenige Kenntnisse Lv5).
- **Credits-Einnahmen dokumentiert (GDD §3):** Vier Quellen: Kolonistensteuern, Galaktischer Rat (staatliche Subventionen, Name TBD), Handel, Events.
- **Hangar-Decay-Konsequenz definiert (GDD §7):** Verfallener Hangar macht zugewiesenes Schiff unbrauchbar (nicht zerstört). Reparatur des Hangars reaktiviert das Schiff. Schiff bleibt in der DB erhalten.

## 2026-04-10 (Berater-System: Slot-Implementierung, Stratege, Kommandanten-UI)

- **Berater-System: Slot-System implementiert.** GDD §12 und Implementierung auf einen Stand gebracht: max. 1 Berater pro Typ pro Kolonie (UNIQUE INDEX), CC-Level schaltet Slots frei (CC Lv1 = 1 Slot, max. 5). Berater kosten jetzt korrekt Credits statt Supply — Bug in `PersonellService::hire()` behoben.
- **Stratege als 5. Beratertyp eingeführt.** DB-Migration, Config-Eintrag (`strategy`-AP-Pool), `PersonellService::resolveType()` und View-Karte ergänzt.
- **Kommandanten-UI:** Piloten können nun über die Berater-Seite Flotten zugewiesen und abberufen werden (`assignCommander`/`unassignCommander`). Die Service-Methoden existierten bereits, Route und UI fehlten noch.
- **Testdaten bereinigt:** Von bis zu 19 Beratern pro Typ auf je 1 pro Typ reduziert (entspricht dem Slot-System). Stratege in beide Kolonien aufgenommen.
- **GDD §12 aktualisiert** (game-designer): Individuen-Prinzip explizit formuliert, Typenbezeichnungen vereinheitlicht, Rang-Tabelle (Junior/Senior/Experte), Credits-Kosten, TODO Kommandanten-UI dokumentiert.

## 2026-04-10 (Design-Sprint Phase 3: Gebäude, Schiffe, Kenntnisse, GDD-Review)

- **Gebäude 25 → 12:** Stark reduziert auf Mini-4X-Kernsortiment. Neue Namen (Cantina, Agrardom, Industriemine, Kolonialdenkmal etc.). Raumwerft + Kampfwerft → **Hangar** (1 Gebäude = 1 Schiffsslot, Supply-begrenzt). Wasser als Ressource gestrichen (durch Versorgung abstrahiert), Wasserextraktor entfernt. Englische Sprachdateien (`lang/en/`) neu erstellt.
- **Schiffe 6 → 3:** Sonde (unbemannt, kein Supply), Korvette (ex Fighter, 14 Supply), Frachter (ex Transporter, 6 Supply). Ship-Decay abgeschafft — Schiffe werden durch Kampf/Gefahren zerstört, nicht durch Verfall. Hangar-Decay ersetzt den Wartungsdruck.
- **Kenntnisse (ex Forschungen) 10 → 7:** Umbenennung von "Forschungen" zu "Kenntnisse" — praktisches Kolonialwissen statt akademische Wissenschaft. Doppelnamen: Bautechnik & Materialverarbeitung, Kartografie & Erkundung, Geologie & Rohstoffgewinnung, Agronomie & Kultivierung, Gesundheit & Wohlbefinden, Handel & Logistik, Verteidigung & Überlebenstaktik.
- **GDD §1 neu geschrieben:** Singleplayer-Koloniekommandant mit Roguelike-Elementen. Vorbilder um FTL und Catan erweitert. Diplomatie/Politik als USP gestrichen.
- **GDD-Review durch 3 Game-Designer-Agenten:** 15 Inkonsistenzen gefunden und behoben (§4–§14). Veraltete Schiffe, Gebäude, Ressourcen und Forschungs-Keys durchgehend ersetzt.
- **Berater-Cap durch CC-Level:** CC Lv1–5 erlaubt 1–5 Berater. Berater kosten Credits, kein Supply — Widerspruch zwischen §6 und §12 aufgelöst.
- **Phase-1-Bedingung "Supply > 0" entfernt** (trivial, da Supply nie spontan auf 0 fällt).

## 2026-04-09 (Design-Sprint Phase 3: Berater- und AP-System)

- **Berater-Redesign (GDD §12):** 4 Typen → 5 Typen. Neue Namen: Baumeister, Analytiker, Raumfahrer, Stratege (neu, MilitärAP), Konsul. Navigation und Militär wurden als separate AP-Typen aufgespalten; Handel und Diplomatie unter Konsul/Handel zusammengeführt.
- **AP-System:** 5 Typen mit Kurznamen (Konstruktion, Analyse, Navigation, Strategie, Handel). Grundwert 6 AP/Tick auch ohne Berater. Max. 5 Berater (einer pro Typ). Level 1–5 mit Sweet Spot bei Level 4.
- **Upkeep-Mechanik:** Berater kosten Credits/Tick statt Supply. Supply bleibt Kapazitätsdeckel für Gebäude und Schiffe — sauberere konzeptionelle Trennung.

## 2026-04-08 (Design-Sprint Phase 3: Spielkonzept und Ressourcen)

- **Spielkonzept neu ausgerichtet:** Nouron wird von einem 4X-Multiplayer-Online-Spiel zu einem Singleplayer-Roguelike-Mini-4X (FTL/Catan-Stil) umgebaut. Keine Rassen mehr — nur Fraktionen. Kein Battlecruiser/Kreuzer. Async Spielerinteraktion (Forge-of-Empires-Stil).
- **Run-Struktur definiert (GDD §14):** Jeder Run = Expeditionsmission mit 2 Phasen. Phase 1: Kolonie stabilisieren (CC Lv3, Supply stabil, 3 Berater). Phase 2: 2 von 3 zufälligen Aufgaben aus einem 10er-Pool erfüllen (Tick-Limit 100). Fail States: Versorgungskollaps oder Zeitablauf.
- **Ressourcenliste überarbeitet (GDD §3):** ENrg/LNrg/ANrg (rassenspezifisch) abgekündigt. Neue Liste: Credits (Cr), Versorgung (Sup), Wasser (W), Werkstoffe (Co), Organika (Or), Moral (M). Kürzel basieren auf englischen Namen für Sprachunabhängigkeit. Exotics als 4. handelbarer Rohstoff für Phase 4+ reserviert.

## 2026-04-07 (AP-0: DB-Reset und Test-Isolation)

- **`php artisan db:reset`:** Neuer Artisan-Command (AP-0a) — löscht alle Tabellen, führt Migrations aus, befüllt mit Simpsons-Testdaten (via `TestSeeder`). Bestätigungsprompt schützt vor versehentlichem Aufruf; `--force` überspringt ihn.
- **Test-Isolation fix (AP-0b):** `phpunit.xml` nutzte `DB_DATABASE=:memory:` ohne `force="true"` — `.env.testing` überschrieb den Wert mit `test.db` (Datei). Durch Ergänzung von `force="true"` laufen Tests jetzt korrekt gegen In-Memory-SQLite. `test.db` wird nach einem Testdurchlauf nicht mehr verändert.

## 2026-04-06 (QA-Tests: Ownership, Trade-Clamp, Colony-Rename, Auth-Throttle)

- **4 neue Feature-Testklassen** mit insgesamt 40 Tests für zuvor unabgedeckte Phase-2-QA-Befunde (CRIT-1, HIGH-2, HIGH-4, MED-3, LOW-1).
- **CRIT-1 / HIGH-4 (`FleetTransferOwnershipTest`):** `addToFleet`-Endpoint liefert 403 für fremde Flotten; `convoy`/`join`-Orders auf fremde Zielflotten werden mit Validierungsfehler abgelehnt. Happy-Path (eigene Flotte) jeweils abgedeckt.
- **HIGH-2 (`TradeOrderResourceClampTest`):** `game:tick` Trade-Orders clampen korrekt auf Quellbestand — Fleet gibt nicht mehr ab als vorhanden, Colony nicht mehr als sie hat. Dabei wurde ein Bug in `GameTick::transferResource` behoben: `FleetResource::increment()` schlug wegen fehlendem Einzel-PK (Composite-Key) lautlos fehl; ersetzt durch direktes `DB::table->update/insert`.
- **MED-3 + Flash-Messenger (`ColonyRenameTest`):** HTML-Injection (Script-Tags, Angle-Brackets, Curly Braces) wird per Regex-Validierung abgelehnt; Grenzwerte (min 2, max 50) und Success-Flash-Message getestet.
- **LOW-1 + Auth-Flow (`LoginFlowTest`):** Login-Throttle (`throttle:5,1`) blockiert ab dem 6. Versuch mit 429; korrektes Passwort nach Lockout ebenfalls geblockt. Erfolgreicher Login mit Username und Email je separat abgedeckt.

## 2026-04-06 (Flotten auf Galaxiekarte)

- **Flotten auf Galaxiekarte:** `GalaxyController::getMapData()` liefert jetzt Layer-3-Einträge für alle Flotten im System-Sichtbereich (Radius 50). Eigene Flotten werden grün dargestellt, fremde gelb — galaxy.js war bereits vorbereitet, Farb-Logik per `obj.attribs.class` ergänzt. `Fleet`-Model um `user()`-Relation erweitert.
- **Skalierungsprüfung:** System-Radius 50 Einheiten; Speed 4 ≈ 12 Ticks, Speed 1 ≈ 50 Ticks — kein Anpassungsbedarf.

## 2026-04-06 (Colony-UI)

- **Colony-UI:** Neue Route `/colony` mit `ColonyController` und Blade-View. Zeigt Kolonienname, Position und Gründungs-Tick. Umbenennung über PATCH `/colony/name` mit Validierung (min 2, max 50 Zeichen). Schreibt direkt in `glx_colonies` (Colony-Model liest aus View `v_glx_colonies`).

## 2026-04-06 (README überarbeitet)

- **README:** Testaccounts-Tabelle (Bart/Homer/Marge), DB-Dateien-Übersicht (nouron.db vs. test.db), Artisan-Commands (`game:sync-techs`, `game:tick`), Scheduler-Cron-Snippet, WSL2-Hinweis für Windows-Entwickler, korrekter Test-Aufruf (`--testsuite=Feature`). Twitter-Link auf X aktualisiert, Facebook-Link auf HTTPS.

## 2026-04-06 (Granulare Bypass-Flags)

- **`config/game.php`:** Neuer `bypass`-Block mit drei unabhängigen Flags: `ap_checks`, `resource_costs`, `supply_checks` (je per `.env` steuerbar). Ermöglicht gezieltes Testen einzelner Systeme — z.B. AP-Verhalten testen während Ressourcenkosten deaktiviert bleiben.
- **`dev_mode` deprecated:** Bleibt als Legacy-Shortcut erhalten, wirft aber `E_USER_DEPRECATED` + Laravel-Log-Warning und expandiert sich in alle drei Bypass-Flags. Wird in einer späteren Version entfernt.
- **`AppServiceProvider::bootBypassFlags()`:** Verarbeitet Legacy-Expansion und enthält Production-Guard — aktive Bypass-Flags in Produktion werfen eine `RuntimeException`.
- **Alle Verwendungsstellen** auf `game.bypass.*` umgestellt (`FleetService`, `AbstractTechnologyService`, `PersonellService`, `TradeGateway`).
- **`.env`** nutzt jetzt `GAME_BYPASS_AP/RESOURCES/SUPPLY=true` statt `GAME_DEV_MODE=true`. **`.env.example`** dokumentiert alle Flags mit Test-Szenarien.

## 2026-04-06 (QA-Fixes Phase 2)

- **CRIT-1** `addToFleet`: Ownership-Check ergänzt — fremde Fleet-IDs werden mit 403 abgewiesen.
- **CRIT-2** AP-Check + AP-Lock in `FleetService::addOrder` in eine DB-Transaktion zusammengefasst (TOCTOU-Schutz).
- **HIGH-1** Off-by-one in `GalaxyService::getPath()`: `$path[$tick++][2]` → `$path[$tick][2]` (Slot-Wert am Zielpunkt wurde den Tick-Zähler fälschlich weitersetzen).
- **HIGH-2** `GameTick::transferResource()`: Menge wird jetzt auf verfügbaren Bestand der Quelle geklemmt, bevor DB-Updates erfolgen — verhindert Ressourcenerzeugung aus Luft.
- **HIGH-3** `processShipDecay()`: `FleetShip::all()` durch `chunkById(200, …)` ersetzt.
- **HIGH-4** `convoy`/`join` Orders: Zielflotte muss dem eigenen User gehören; `defend` bleibt offen (Allianz-Unterstützung legitim).
- **MED-1** `addResearchOffer()`: AP-Check und AP-Lock analog zu `addResourceOffer()` ergänzt.
- **MED-2** `config/game.php`: `dev_mode` Default auf `false` gesetzt; `.env` erhält `GAME_DEV_MODE=true` für die Dev-Umgebung.
- **MED-3** Colony-Rename: Regex-Validierung blockiert HTML/Script-Zeichen (`<>{}[]`).
- **MED-4** Combat-Events: Moral-Events und INNN-Events werden jetzt für alle beteiligten Defender-User gefeuert, nicht nur den ersten.
- **LOW-1** Login: `throttle:5,1` Middleware auf POST `/login`.
- **LOW-2** `getColoniesByCoords()`: hardcoded Radius 50 durch `getSystemViewRange()` ersetzt.
- **LOW-3** Tippfehler `devide` → `divide` in `FleetService::$validOrders`.
- **LOW-4** `getOrders()`: `orderByRaw()` durch Whitelist-geprüftes `orderBy()` ersetzt.

## 2026-04-06 (Roadmap, GDD und config/game.php abgeglichen)

- **ROADMAP.md aktualisiert:** Phase 1b als abgeschlossen markiert (April 2026). Prio-1-Bug (PersonellService), alle Prio-2-Items (Advisor-UI, Forschungshandel, Einstellungen) und Prio-3-Items (game:sync-techs, Scheduler, Fleet-Orders) als erledigt markiert. Interstellare Flottenbewegung aus Phase 2 entfernt (nur in Phase 3a).
- **GDD §7 (Decay):** Abschnitt "Schema-Konsequenzen (noch nicht implementiert)" entfernt und durch korrekte Beschreibung des implementierten Schemas ersetzt (max_status_points, decay_rate in Stammdaten; status_points in colony_buildings, fleet_ships, colony_researches).
- **GDD §2 (Tick-Schritte):** Schritt 8b (Moral Calculation) in der Tick-Tabelle ergänzt — war im §13 bereits dokumentiert, fehlte aber in der zentralen Übersicht.
- **GDD §8 (Fleet Orders):** AP-Kostentabelle um hold/join/convoy (je 1 AP), defend (2 AP) erweitert. Beide AP-Tabellen (§1.1 und §8) sind jetzt konsistent.
- **GDD §12 (Supply-Kosten):** "noch zu definieren"-Platzhalter durch tatsächliche Werte aus `config/buildings.php`, `config/ships.php` und `config/techs.php` ersetzt.
- **`config/game.php`:** `fleet.order_costs` um hold, join, convoy (1 AP), defend (2 AP) ergänzt (gemäß Designprinzip "Militarismus ist teuer").

## 2026-04-06 (Scheduler, game:sync-techs, Fleet-Orders vervollständigt)

- **`game:sync-techs`:** Neuer Artisan-Command synchronisiert `config/ships.php` → `ships`-Tabelle (moving_speed, decay_rate, supply_cost, max_status_points) und `config/buildings.php` → `buildings`-Tabelle (decay_rate, supply_cost, max_status_points, max_level). Zeigt je geänderter Row "alt → neu". `--dry-run`-Option zum Vorschauen. Erster Lauf hat 6 Ships mit `moving_speed=500` (Altlast) auf korrekte Werte gesetzt.
- **Laravel Scheduler aktiviert:** `routes/console.php` — `game:tick` läuft jetzt täglich um 03:00 Uhr automatisch (cron muss einmalig auf dem Server eingerichtet werden: `* * * * * php artisan schedule:run`).
- **Fleet-Orders vervollständigt:** `storeOrder`-Validator akzeptiert jetzt `hold`, `convoy`, `defend`, `join` zusätzlich zu `move`, `trade`, `attack`. Jeder neue Order-Typ hat eigene Controller-Logik und UI-Felder im Flotten-Konfigurationsformular. Befehlstyp-Dropdown mit `<optgroup>`-Struktur. Order-Beschreibungszeile unter Formular (zeigt was der Befehl tut).
- **Lokalisierung `lang/de/fleet.php`:** Neue Sprachdatei für alle Flotten-UI-Texte (Order-Namen, Feldbezeichnungen, Beschreibungen).

## 2026-04-06 (UI-Polishing: Berater, Handel)

- **Berater-Seite umgebaut:** Berater werden jetzt in 4 separaten Cards gruppiert (Ingenieure, Wissenschaftler, Händler, Kommandanten). Jede Card zeigt die eigene AP-Summe und Supply-Kosten im Footer. Auf Desktop 2 Cards nebeneinander. Flottenkommandanten werden mit Flottenname angezeigt; `AdvisorController` lädt jetzt zusätzlich alle `is_commander`-Advisors des Users über alle Flotten. AP-Gesamtübersicht durch kompakte Statuszeile ersetzt. Dicke Farbrahmen entfernt, neutrale Cards.
- **Trade-Seiten:** Rohstoff-Spalte zeigt jetzt `res-chip` mit Kürzel + Tooltip statt plain Text. Preis-Spalte zeigt Credits-Chip. Restriktions-Spalte zeigt lesbare Badges (Alle/Gruppe/Fraktion/Rasse) statt roher Integers. Tabellenzeilen farblich nach Richtung (Kauf/Verkauf). `table-striped` entfernt.
- **Shared Partial:** `resources/views/partials/res_chip.blade.php` — wiederverwendbarer Ressourcen-Chip, der dieselbe `.res-chip`-Klasse wie die Ressourcenleiste nutzt.

## 2026-04-06 (Forschungshandel-View, User-Einstellungen, Roadmap Phase 3–5)

- **Forschungshandel-View:** Neue Route `/trade/researches`, Controller-Methoden `researches()` und `addResearchOffer()` in `TradeController` ergänzt. View analog zu Rohstoff-Handel (Tabelle, Filter, Modal zum Erstellen). `TradeGateway::addResearchOffer/removeResearchOffer` war bereits vorhanden. Hinweis-Banner im Formular: Acceptance-Flow folgt in Phase 3 nach Entscheidung über Mechanik (Level-Transfer vs. Lizenz). Beide Trade-Seiten haben jetzt einen Tab-Header (Rohstoffe / Forschungen).
- **User-Einstellungen:** `/user/settings` ist nun funktional — Anzeigename und Passwort können geändert werden. Zwei PATCH-Routen (`/user/settings/name`, `/user/settings/password`) mit Validierung und aktuellem-Passwort-Check.
- **Roadmap Phase 3–5:** Phase 3 in 3a (Balancing/Kernmechaniken), 3b (UI/Almanach) und 3c (Onboarding) unterteilt. Phase 4 ("Das Spiel vertiefen": Diplomatie, Rassen, Gilden, Steuern, Berater) und Phase 5 ("Das Spiel erweitern": Außenposten, neue Schiffstypen, galaktische Politik) neu eingeführt. Alle bisher im GDD als "Phase 3" vermerkten Themen in die neue Struktur überführt.

## 2026-04-05 (Spielsystemanalyse, Advisor-UI, Schiffsgeschwindigkeit)

- **Systemanalyse:** Vollständige Bestandsaufnahme aller Module und Spielmechaniken. Roadmap Phase 2 mit konkreten Befunden, Prioritäten und Design-Klarstellungen (1 Kolonie/Spieler, nur PvP-Schiffskämpfe, keine Kolonisierung) aktualisiert. Phase 3 mit vorgemerkten Themen (UI-Umbau, Almanach, Onboarding, Gebäude-Rework) strukturiert. Design-Entscheidung gegen modulare Schiffe dokumentiert.
- **Bugfix `PersonellService::hire`:** `ResourcesService` war nicht im Konstruktor deklariert — hätte bei `dev_mode=false` einen Fatal Error ausgelöst. Zudem Supply-Check jetzt in `DB::transaction()` eingebettet, um Race-Condition bei parallelen Requests zu verhindern.
- **Advisor-UI:** Neue Seite `/advisors` mit AP-Übersicht (Konstruktion/Forschung/Wirtschaft), Beratertabelle (Typ, Rang, AP/Tick, Rang-Fortschritt, Status) und Hire/Fire-Funktion. Ownership-Check beim Entlassen (verhindert, dass fremde Berater entlassen werden können). Nav-Eintrag "Berater" ergänzt.
- **Schiffsgeschwindigkeit:** `moving_speed` für alle 6 Schiffstypen in `config/ships.php` gesetzt (Fighter 4, Fregatte/Small Transporter 3, Battlecruiser/Medium Transporter 2, Large Transporter 1). `FleetService::calcFleetSpeed()` war bereits korrekt implementiert.

## 2026-04-04 (INNN Ereignisse — Polishing)

- **Platzhalter-Fix:** `tech_id` in Event-Parametern wird jetzt gegen Building → Research → Ship aufgelöst (vorher nur Building → "Tech #34" bei Forschungs-/Schiff-Events).
- **Fleet-Name:** Fleet-Events zeigen jetzt den Fleet-Namen statt "Flotte #X".
- **Null-Guards:** `colony_id=0`, `attacker_id=0`, `defender_id=0` zeigen "unbekannt" statt "#0". Alle Platzhalter haben Fallback-Defaults — `:placeholder` kann nie mehr roh erscheinen.
- **Texte:** `techtree_level_down` sagt "Struktur" statt "Gebäude" (gilt auch für Schiff- und Forschungs-Verfall). `galaxy_combat` leicht umformuliert.
- **Layout:** Desktop-Ansicht nutzt max. ~80% Seitenbreite (`col-md-10 col-xl-8`). Area-Badge durch kontextuelles Icon ersetzt.

## 2026-04-04 (Tech-Config-Refactoring)

- **Zentrale Config-Dateien:** `config/buildings.php`, `config/ships.php`, `config/techs.php`, `config/advisors.php` eingeführt als einzige Quelle für alle per-Entity-Mechanik-Werte (Supply-Cost, Moral, Decay-Rate, Credits). Ersetzt die zersplitterten `game.moral.*`- und `game.supply.*`-Sektionen.
- **Decay-Kalibrierung:** Alle Decay-Raten neu kalibiert für 1 Tick = 24 Stunden. Bisherige Werte (1-Tick/Stunde-Auslegung) ergaben 100–400 Tage Verfallszyklen; neue Werte: 7 Tage (Bar/Kasino) bis 60 Tage (Kommandozentrale/Denkmal). Migration `2026_04_04_000001_recalibrate_decay_rates` aktualisiert DB-Tabellen.
- **Lokalisierung:** `lang/de/buildings.php`, `ships.php`, `techs.php`, `advisors.php` mit deutschen Namen und Tooltip-Beschreibungen für alle Spielentitäten.
- **MoralService-Refactoring:** Liest `moral_per_lv`/`moral_per_unit` jetzt aus den neuen Config-Dateien statt aus `game.moral.*`. `GameTick` referenziert Supply-Cap-Werte über `config('buildings.*.supply_cap')`.

## 2026-04-04 (Moralsystem Phase 2)

- **MoralService:** Neuer Service berechnet Kolonial-Moral (-100..+100) aus statischen Faktoren (Gebäude, Forschungen, Schiffe) und One-Shot-Events (`moral_events`-Tabelle). Events desselben Typs im selben Tick stacken nicht — nur der stärkste Wert zählt.
- **Produktionsmultiplikator:** `GameTick::generateResources()` wendet den Moral-Multiplikator an (0.70× Aufruhr .. 1.20× Euphorisch). Schritt 8b berechnet und speichert Moral nach der Ressourcengenerierung.
- **AP-Multiplikator:** `PersonellService::getTotalActionPoints()` skaliert colony-scoped AP mit dem Moral-Multiplikator (0.80× .. 1.10×). Fleet-scoped Navigation-AP bleibt unverändert.
- **Trade-Events:** `TradeGateway::acceptResourceOffer()` feuert `trade_success`-Event für Käufer und Verkäufer. Combat-Events (`combat_won`, `combat_lost`, `colony_attacked`) werden in `GameTick::processCombatOrders()` gefeuert.
- **GDD §13** dokumentiert das vollständige Design inkl. Bänder, Einflussfaktoren, Balance-Entscheidungen (Schiffs-Cap ±30, Event-No-Stacking). `lang/de/moral.php` mit Band- und Event-Bezeichnungen. 53 neue Tests.

## 2026-04-03 (Flottenoperationen Phase 2)

- **Fleet CRUD:** Flotten können jetzt erstellt (POST `/fleet`) und gelöscht (DELETE `/fleet/{id}`) werden. Erstellung erfordert einen verfügbaren Pilot-Advisor (Kommandant) — dieser wird automatisch zugewiesen. Beim Löschen kehrt der Kommandant zur Kolonie zurück.
- **Order-Erstellung:** Spieler können Move-, Trade- und Attack-Orders über das Fleet-Config-UI erteilen (POST `/fleet/{id}/orders`). Move-Orders sind auf intra-System-Bewegung beschränkt (Phase-3-Vorbehalt für interstellare Reisen mit Wurmloch/Sternentor-Mechanik).
- **Multi-Tick-Bewegung:** Flotten bewegen sich tick-basiert mit der Geschwindigkeit des langsamsten Schiffs. `FleetService::addOrder()` legt für jeden Tick des Weges eine Order an.
- **Bug-Fixes:** serialize/json-Mismatch in `_storePathInDb()` behoben (Orders konnten nie verarbeitet werden); `startTick=0`-Fehler in `getPath()`-Aufruf korrigiert; Route-Inkonsistenz zwischen `fleets.js` und `web.php` behoben.
- **Pending Orders** werden in der Fleet-Übersicht angezeigt. GDD §8 und §12 um Bewegungs-Mechanik und Kommandant-Modell (Option A) ergänzt. 17 neue Tests.

## 2026-04-02 (Supply-Cap-Enforcement)

- **Over-Cap-Decay:** Wenn eine Kolonie ihren Supply-Cap überschreitet, verfallen Gebäude und Forschungen mit 2× Decay-Rate. Spieler werden so zum Abbau gedrängt ohne erzwungene Zerstörung.
- **Blockierung:** Neue Level-Ups und Hire-Aktionen bleiben geblockt solange die Kolonie Over-Cap ist (bestehende Logik via `checkRequiredSupplyByEntityId`).
- **`getFreeSupply()`** gibt jetzt negative Werte zurück bei Over-Cap; neue Hilfsmethode `getOverCapColonyIds()` in `ResourcesService`.
- `overcap_factor = 2.0` konfigurierbar in `config/game.php`. 7 neue Tests in `OverCapDecayTest`.

## 2026-04-02 (Economy-AP für Händler)

- **Händler-AP implementiert:** Trade-Aktionen verbrauchen jetzt Economy-AP. Angebot erstellen kostet `max(1, floor(amount × price / 1000))` AP — skaliert mit dem Handelswert. Angebot annehmen kostet 1 AP (Käufer). Angebot entfernen ist kostenlos.
- **Konfigurierbar:** AP-Schwellenwert in `config/game.php` unter `trade.ap_cost_threshold = 1000`.
- **DI:** `PersonellService` per Constructor-Injection in `TradeGateway` eingebunden, explizite Bindung in `AppServiceProvider`.
- 6 neue Tests in `TradeApTest` (Skalierung, Locks, Exceptions, dev_mode-Bypass).

## 2026-04-02 (Trade Acceptance-Flow)

- **Angebote annehmen:** Spieler können Ressourcen-Angebote anderer Spieler annehmen. Instant Transfer — Ressourcen und Credits wechseln sofort den Besitzer, kein physischer Transport. Komplettkauf only, Angebot wird nach Abschluss gelöscht.
- **Restriction durchgesetzt:** `restriction`-Feld wird server-seitig geprüft (0=alle, 2=gleiche Fraktion, 3=gleiche Rasse). Wert 1 (Gruppe) wird bis zur Implementierung des Gruppenmoduls wie 0 behandelt. Annehmen-Button im UI je nach Restriction ein-/ausgeblendet.
- **Sicherheit:** Selbstkauf blockiert, Buyer-IDs aus Session (kein POST-Injection), alle Transfers atomar in `DB::transaction()`. 28 neue Tests inkl. Edge Cases und Rollback-Konsistenz.
- **GDD §11** mit Acceptance-Flow und restriction-Semantik aktualisiert.

## 2026-04-02 (Laminas/Zend-Reste entfernt)

- **Komplettbereinigung:** `module/` (11 Module, ~200 PHP-Dateien), `config/autoload/` (LmcUser, ZfcRbac, global, local), `init_autoloader.php` und `test/Bootstrap.php` gelöscht — der gesamte alte Laminas-Modulbaum ist damit aus dem Repo entfernt.
- **Kommentarbereinigung:** Laminas-Migrations-Annotationen aus 19 aktiven Laravel-Dateien entfernt.
- **README aktualisiert:** Laminas → Laravel 12, PHP 8.0 → 8.2+, `phpunit` → `php artisan test`, Dev-Server auf `artisan serve`.

## 2026-04-02 (Onboarding-Risiken behoben)

- **Race Condition:** `OnboardingService::setupNewPlayer()` läuft jetzt in `DB::transaction()` — SQLite serialisiert Writes, sodass zwei simultane Registrierungen nicht denselben Planeten belegen können.
- **spot=1 hardcoded:** `ColonyService::createColony()` berechnet den Spot jetzt dynamisch (`MAX(spot) + 1`), sodass mehrere Kolonien auf demselben Planeten korrekt auf verschiedene Spots verteilt werden.
- **Kein freier Planet:** `RegisterController::register()` wickelt User-Erstellung und Onboarding atomar in einer Transaktion ab — bei vollem Universum wird kein verwaister Account angelegt, sondern eine Fehlermeldung im Registrierungsformular angezeigt.
- **UNIQUE-Constraint** auf `(system_object_id, spot)` in `glx_colonies` als Datenbank-Safety-Net hinzugefügt.
- **LoginTest:** `TestSeeder` in `setUp()` ergänzt. Neuer Test `test_registration_fails_when_no_free_planets`.

## 2026-04-01 (Onboarding nach Registrierung)

- **OnboardingService:** Neuer Service `setupNewPlayer()` — sucht freien Planeten, erstellt Kolonie, setzt Startressourcen (3000 Cr, 15 Supply, 500/500/500/100/100/100 Kolonieressourcen) und platziert CommandCenter auf Level 1.
- **RegisterController:** Ruft nach Login den Onboarding-Service auf und setzt `activeIds.colonyId` in der Session.
- **LoginController:** Triggert Onboarding beim Login wenn User noch keine Kolonie hat (Legacy-Accounts).
- **ColonyService:** Neue Methode `createColony()` für programmatische Kolonie-Erstellung.
- 3 neue Tests in `OnboardingTest`.

## 2026-04-01 (Tests E1/E2/I4)

- **Tests E1/E2 (AP-Delta-Locking):** `invest('add')` lockt korrekt die investierten AP (E1); AP-Locks sind tick-scoped und verfallen nach Tick-Advance (E2). In `PersonellServiceTest`.
- **Test I4 (Cross-Colony-Exploit):** Neuer `TechtreeControllerTest` — verifiziert dass Controller-Aktionen immer nur die eigene Kolonie (session-basiert) betreffen und keine `colony_id` per URL injiziert werden kann.

## 2026-03-31 (GDD Tick-Tabelle, Supply Enforcement, Rang-Schwellen in Config)

- **Rang-Schwellen in config ausgelagert:** `RANK_UP_THRESHOLDS` (PersonellService) und `AP_BY_RANK` (Advisor) durch `config('game.advisor.rank_thresholds')` und `config('game.advisor.ap_per_rank')` ersetzt. Widerspruch zwischen GDD (Rang 3 bei 20 Ticks) und Code (30 Ticks) aufgelöst — config jetzt auf 20. Test entsprechend angepasst.

## 2026-03-31 (GDD Tick-Tabelle, Supply Enforcement)

- **GDD §2 Tick-Tabelle:** Von 6 auf 9 Schritte aktualisiert — Ship Decay (5), Research Decay (6), Supply Cap (7, statt "Supply Generation"), Advisor Ticks (9) ergänzt. Beschreibungen präzisiert. Widersprüchlichen "Konsequenz für den Tick"-Abschnitt durch korrekten Text zum Cap-Modell ersetzt.

## 2026-03-31 (Supply Enforcement)

- **Supply Enforcement:** Beim Level-Up von Gebäuden, Schiffen und Forschungen wird jetzt geprüft, ob genügend freies Supply (Cap − aktuell genutztes Supply) vorhanden ist. Neue Methode `ResourcesService::getFreeSupply()` berechnet freies Supply aus Cap und Summe aller Entity-Supply-Kosten. `AbstractTechnologyService::checkRequiredSupplyByEntityId()` blockiert Level-Ups wenn Kapazität fehlt. `PersonellService::hire()` prüft supply_cost pro Berater (aus `config/game.php`). Alle Checks werden im dev_mode bypassed. 2 neue Tests in `BuildingServiceTest`.

## 2026-03-30 (Agenten aktualisiert, Ressourcenleiste)

- **Agenten-Updates:** `backend-coder` und `ui-specialist` auf Laravel/Blade aktualisiert; `project-manager` auf Phase 2/3-Stand gebracht; neuer `content-writer`-Agent für Lore, Beschreibungen und INNN-Texte; README.md aktualisiert.
- **Ressourcenleiste:** Credits (ID 1) und Supply (ID 2) immer an erster Stelle und visuell hervorgehoben (größer, dickerer Rand, Box-Shadow). Optischer Trenner zwischen primären und sekundären Ressourcen.

## 2026-03-30 (colonyShip entfernt)

- **colonyShip (id=88) vollständig entfernt:** Migration löscht ship aus DB; testdata.sqlite.sql bereinigt; MasterDataSeeder, GDD, CLAUDE.md, lang-Dateien aktualisiert. `colonize`-Order-Typ aus `config/game.php` entfernt.
- Testreferenz angepasst: FleetServiceTest erwartet jetzt 4 statt 5 Schiffe in Fleet 10.

## 2026-03-30 (Supply Cap und Decay im GameTick implementiert)

- **Supply: Cap-Modell implementiert** — `calculateSupply()` setzt `user_resources.supply` jetzt als Kapazitäts-Cap (SET statt INCREMENT): `cap = CC_flat (15) + housing_level × 8`, max 200. Ohne CommandCenter → Supply = 0.
- **Decay: per-Entity-Werte** — `processDecay()` aufgeteilt in `processBuildingDecay()`, `processShipDecay()` und `processResearchDecay()`. Alle drei nutzen die individuellen `decay_rate`-Werte aus den Stammdaten-Tabellen statt dem globalen Fallback-Wert. Decay ist fraktional (REAL).
- **Schiff-Decay** — Schiffe in Kampf-Ticks erhalten Faktor 2 (`combat_factor`). Bei SP ≤ 0 wird der `fleet_ships`-Eintrag gelöscht (kein Level-Down, Schiff vernichtet). Fix: `DB::table()` statt Eloquent-Update bei Composite-Key-Tabellen.
- **10 neue Tests** — Supply-Cap (CC-Pflicht, Housing-Skalierung, Max-Cap), Building-Decay (fraktional, Level-Down, Level-0-Skip), Ship-Decay (fraktional, Vernichtung), Research-Decay (fraktional, Level-Down).

## 2026-03-29 (Decay- und Supply-Migrationen)

- **Zwei neue Migrations:** `decay_rate REAL` und `supply_cost INTEGER` zu `buildings`, `ships`, `researches` hinzugefügt; `status_points REAL DEFAULT 20` zu `fleet_ships` (neu — Schiffe hatten bislang kein Status-Tracking).
- **Original-Migrationen angepasst:** `colony_buildings` und `colony_researches` verwenden jetzt `double` für `status_points` (Voraussetzung für fraktionale Decay-Werte).
- **MasterDataSeeder:** Befüllt alle neuen Felder mit den im GDD §6/§7 beschlossenen Werten (decay_rate 0.05–0.20, supply_cost 0–30). Wird automatisch vom TestSeeder aufgerufen.
- **testdata.sqlite.sql aktualisiert:** Positionale INSERT-Statements um neue Spalten ergänzt (NULL-Platzhalter für Stammdaten, 20.0 für fleet_ships).

## 2026-03-28 (Trade-Modul repariert)

- **Vier kritische Bugs behoben:** `withoutLayout()` existiert nicht in Laravel → durch Redirect+Flash ersetzt; Filter funktioniert jetzt per GET; Remove-Formular sendete falsche Felder (`offer_id`/`offer_type` statt Composite-Key); Create-Modal hatte kein `colony_id`-Feld.
- **Validierung verbessert:** `amount`/`price` auf `min:1` angehoben (konsistent mit UI), `removeOffer`-Endpunkt validiert jetzt `colony_id` und `direction`, strict equality beim `user_id`-Vergleich in den Views.
- **25 neue Tests:** HTTP-Controller-Tests für alle Trade-Endpunkte (GET-Filter, POST-Erstellen, POST-Löschen, Authentifizierung, Ownership-Checks, Upsert-Pfad).
- **Offene Design-Fragen identifiziert:** Forschungshandel-Semantik (sinkt Level beim Verkauf?) und `restriction`-Feld (Bedeutung ungeklärt) — werden in der nächsten Session geklärt bevor der Acceptance-Flow implementiert wird.

## 2026-03-28 (Berater-System: advisors-Tabelle, Rang-System, Kommandant)

- **Neue `advisors`-Tabelle:** Berater sind jetzt individuelle Einträge (id, user_id, rank, active_ticks) statt level-aggregierte Zeilen in colony_personell. Bestehende Daten aus colony_personell und fleet_personell wurden migriert.
- **Rang-System implementiert:** Junior(1)=4 AP, Senior(2)=7 AP, Experte(3)=12 AP/Tick. Automatischer Rang-Aufstieg nach 10 bzw. 30 aktiven Ticks via GameTick.
- **Kommandant fleet-assignable:** `assignToFleet()` / `unassignFromFleet()` — nur Kommandant-Typ erlaubt (personell.can_command_fleet=true). Prüfung auf DB-Ebene per Flag, Durchsetzung im Service.
- **Arbeitslos-Zustand:** `fire()` löscht keine Berater mehr, setzt nur colony_id/fleet_id auf NULL. Vorbereitung für Berater-Handel zwischen Spielern (Phase 3).
- **Passagier-Zustand:** fleet_id gesetzt + is_commander=false = Berater als Passagier auf Flotte (alle Typen erlaubt).
- **PersonellService** komplett neu geschrieben auf advisors-Tabelle. `hire()` gibt Advisor-Instanz zurück.
- **GDD Abschnitt 12** mit vollständigem Datenmodell und Zustandstabelle aktualisiert.

## 2026-03-27 (AP-System: Berater und Flottenkommandant)

- **AP-System vervollständigt:** Alle vier Berater-Typen (Ingenieur, Wissenschaftler, Pilot/Kommandant, Händler) vollständig implementiert. Navigation-AP sind jetzt fleet-scoped statt colony-scoped — der Kommandant fliegt mit der Flotte.
- **DB-Migration `locked_actionpoints`:** Schema von `(tick, colony_id, personell_id)` auf `(tick, scope_type, scope_id, personell_id)` umgestellt. `scope_type='colony'` für Bau/Forschung/Wirtschaft, `scope_type='fleet'` für Navigation.
- **FleetService:** AP-Kosten-Check bei `addOrder()` integriert. Konfigurierbar in `config/game.php → fleet.order_costs`. Im Dev-Mode übersprungen.
- **GDD Abschnitt 12:** Berater & Aktionspunkte dokumentiert (alle 4 Typen, Formel, Scope, Implementierung).
- **GDD Abschnitt 1.1:** Neues Kapitel "Designprinzipien" — militärische Aktionen kosten immer mehr AP als zivile (Kernprinzip für das gesamte Spiel inkl. Verträge, Diplomatie).
- **Offenes Designthema:** Das Berater-System (Berater als Gebäude mit Leveln) muss grundsätzlich überarbeitet werden — wird in einer eigenen Session angegangen.

## 2026-03-26 (GDD erstellt)

- **Game Design Document:** `docs/GDD.md` neu angelegt. Dokumentiert alle bisher implementierten Spielmechaniken: Tick-System (Zeitberechnung, Berechnungsfenster, Schrittreihenfolge), Ressourcenproduktion, Supply-Generierung, Gebäude-Verfall, Flottenorders (Move/Trade), Kampfsystem. Alle Balancewerte mit Verweis auf `config/game.php`.

## 2026-03-26 (Phase 2: Tick-System, Teil 2)

- **Gebäude-Verfall:** Jeder Tick dekrementiert `status_points` um 1 pro Kolonie-Gebäude. Erreicht `status_points` 0, verliert das Gebäude ein Level und `status_points` wird auf `max_status_points` zurückgesetzt. INNN-Event `techtree.level_down` wird erzeugt. Rate konfigurierbar in `config/game.php → decay.rate`.
- **Supply-Generierung:** Jeder Tick addiert Supply zu jedem User: `Σ(CommandCenter.Level × 5) + Σ(HousingComplex.Level × 10)` über alle Kolonien des Users. Rates konfigurierbar in `config/game.php → supply`.
- **Kampfsystem (einfach):** Attack-Orders werden verarbeitet: Angreifer bewegt sich zu den Zielkoordinaten, gegnerische Flotten werden gesucht. Kampfstärke = `Σ(Schiffanzahl × Kampfwert)`. Verluste werden proportional zur gegnerischen Stärke berechnet (nicht-Kampfschiffe bleiben verschont). INNN-Events für beide Seiten. Kampfwerte konfigurierbar in `config/game.php → combat.ship_power`.
- **lang/de/events.php:** Key `events.techtree_level_down` ergänzt für INNN-Anzeige.

## 2026-03-26 (Phase 2: Tick-System)

- **Tick-Processor:** `php artisan game:tick [--tick=N]` implementiert. Der Command verarbeitet für den angegebenen Tick: (1) Fleet-Move-Orders — Flotte wird auf die befohlenen Koordinaten gesetzt, `was_processed=1`; (2) Fleet-Trade-Orders — Ressourcentransfer zwischen Kolonie und Flotte (Kauf/Verkauf), `colony_id` als Schlüssel; (3) Ressourcengenerierung — alle Kolonien erhalten pro Industrie-Gebäude `level × rate` Ressourcen pro Tick (konfigurierbar in `config/game.php` unter `production`). Für jede verarbeitete Move- und Trade-Order wird ein INNN-Event erzeugt.
- **config/game.php:** Produktionsraten ergänzt (`oremine→ferum: 10/Level`, `silicatemine→silicates: 10/Level`, `waterextractor→water: 10/Level`). Scheduling-Stub für `dailyAt('03:00')` als Kommentar hinterlegt.
- **Diagnose:** Die in der DB vorhandene Trade-Order hatte einen JSON-Datenfehler (duplizierter `"colony"`-Key statt `"colony_id"`). Kein Designfehler — `trade.js` nutzt korrekt `colony_id`. Bestehende Test-Daten sind als `was_processed=1` markiert.

## 2026-03-24 (UI-Aufwertung & Bugfixes)

- **Techtree:** Grid-Dimensionen korrigiert (war fälschlicherweise 6×16 statt 16×6). Leader Line ersetzt das manuelle SVG-Drawing — Abhängigkeitspfeile werden jetzt sauber mittig auf den Buttons gesetzt. Toggle-Buttons (Gebäude/Forschungen/Schiffe/Berater) wieder eingebaut, Toggles steuern auch Leader-Line-Instanzen.
- **Techtree-Buttons:** Modernes Flat-Design mit farbigem linken Akzentrand (lila/grün/gelb/grau je Typ) statt alter Farbverläufe. `notexists`-Buttons in ausgewaschener Variante.
- **Galaxy:** `galaxy.js` komplett auf Leaflet umgestellt — liest Systemkoordinaten aus `data-x`/`data-y`-Attributen statt inline PHP. Neue Routen `/galaxy/{sid}` und `/galaxy/json/getmapdata/{x}/{y}` ergänzt. `TechtreeController` nutzt `resolveColonyId()` als Session-Fallback über `ColonyService`.
- **Resource Bar:** Heller Hintergrund (`#f8f9fa`), fixiert unterhalb der Navbar. Ressourcen als farbige Chips mit Akzentfarbe je Ressourcentyp.

## 2026-03-24 (Blade-Templates)

- **Layout:** `fixed-top` zur Navbar-Klasse ergänzt, damit der Content nicht hinter der Leiste verschwindet.
- **Techtree-Index:** Vollständig auf das originale Grid-Layout portiert — 16×6 Zellen-Raster, `.techdata`-Spans mit `id="techsource-{row}-{col}"`, die techtree.js per `init()` in die Grid-Zellen verschiebt. Requirement-Linien-Daten als `.requirementsdata`-Spans eingebettet. Pro Tech ein leeres `.techModal`-Shell, das per AJAX befüllt wird.
- **Techtree-Technology-Partial:** Neues AJAX-Partial (kein `@extends`) mit vollständiger Modal-Dialog-Struktur: Kosten/Voraussetzungen-Tabelle, `techstatus_bar` und `techlevelup_bar` Partials, Levelup/Leveldown-Buttons mit korrekten IDs im Format `{type}-{id}|{order}` für techtree.js. Sonderbehandlung für Personell (Anheuern/Feuern statt Ausbauen/Abbauen) und Ships (zusätzliche Forschungs-Voraussetzung).
- **Techtree-Partials:** `techstatus_bar.blade.php` und `techlevelup_bar.blade.php` in `resources/views/techtree/partials/` angelegt — segmentierte Bootstrap-Progress-Bars mit klickbaren `<a>`-Segmenten für AP-Investment und Reparatur.
- **TechtreeController:** Neues `action()`-GET-Endpoint (`/techtree/{type}/{id}/{order}[/{ap}]`) für das techtree.js-AJAX-Muster, das nach jedem Klick die gesamte Modal-Partial neu lädt. `$buildings` und `$researches` werden jetzt an `technology()` übergeben.
- **Fleet-Index:** Auf Laminas-Struktur portiert — eigene Flotten links, fremde Flotten rechts, Formular zum Anlegen neuer Flotten im `<tfoot>`, Lösch-Button mit Bestätigungs-Dialog.
- **Fleet-Config:** Vollständig auf die Laminas-Vorlage portiert — Kolonie-Inventar-Tabellen für Schiffe/Personal/Forschungen/Ressourcen mit AJAX-Placeholdern (`…`), Menge-Auswahl-Buttons, Transfer-Buttons. Alle CSS-Klassen für fleets.js kompatibel (`fc-item`, `fc-mid`, `data-type/id/cargo`, `#fleet_id`, `#colony_id`).
- **Trade-Resources/Researches:** Rohstoff-Anzeige mit Icon und Tooltip statt roher ID. "Angebot erstellen"-Button mit Modal-Formular. Eigene Angebote bekommen Lösch-Button. Filter-Formular auf GET umgestellt.
- **Messages-Outbox:** Deaktivierte Aktionsbuttons (Thumbs-Up/Down, Antworten) im Accordion-Body ergänzt.

## 2026-03-24 (Schritt 11+12)

- **Layout & Navigation (Schritt 11):** `resources/views/layouts/app.blade.php` vollständig überarbeitet — Bootstrap-Navbar mit allen migrierten Modulen (Galaxis, Flotte, Techtree, Handel, Nachrichten), Ressourcenleiste via View Composer (AppServiceProvider::boot registriert den Composer, injiziert `$resourceBarPossessions` aus `ResourcesService::getPossessionsByColonyId()` in den Layout-View), Spiel-JS-Dateien (nouron.js, techtree.js, fleets.js, trade.js, innn.js), Tooltip-Init, Sub-Nav-Slot via `@hasSection('subnav')`. Fehler-Seiten `errors/404.blade.php` und `errors/500.blade.php` erstellt.
- **Cleanup (Schritt 12):** Alle 24 Laminas-Pakete sowie `lm-commons/lmc-user` und `firephp/firephp-core` aus `composer.json` entfernt. Laminas-Module-Autoload-Einträge (`Application`, `Core`, `Colony`, `Fleet`, `Galaxy`, `INNN`, `Map`, `Resources`, `Techtree`, `Trade`, `User`) aus `autoload` und `autoload-dev` bereinigt. `laminas/laminas-test` aus `require-dev` entfernt. Laminas-Testsuiten aus `phpunit.xml` entfernt, Bootstrap auf `vendor/autoload.php` umgestellt. `composer update` ausgeführt — 81 Pakete (statt 118 vorher). 187/187 Laravel Feature-Tests weiterhin grün.



## 2026-03-24 (Techtree)

- **Techtree-Modul migriert (Schritt 10):** 10 Eloquent-Modelle erstellt (`Building`, `BuildingCost`, `ColonyBuilding`, `LockedActionpoint`, `Personell`, `PersonellCost`, `Research`, `ResearchCost`, `Ship`, `ShipCost`). `AbstractTechnologyService` als gemeinsame Basis für alle Techtree-Services implementiert (Prerequisite-Checks, AP-Investment, levelup/leveldown, Kostenzahlung). Konkrete Services `BuildingService`, `ResearchService`, `ShipService`, `PersonellService` (inkl. AP-Verwaltung, lockActionPoints, hire/fire) und `TechtreeColonyService` (Gesamtübersicht mit Merge aus Master- und Kolonie-Tabellen). `TechtreeController` mit 3 Routen unter `/techtree` (index, technology-Detail-Popup, order). Blade-Views `techtree/index.blade.php` und `techtree/technology.blade.php`. Services in AppServiceProvider registriert. 22 neue Feature-Tests, Gesamtstand: 185 Tests grün.

## 2026-03-23 (Fleet)

- **Fleet-Modul migriert (Schritt 9):** Eloquent-Modelle `Fleet`, `FleetShip`, `FleetResearch`, `FleetPersonell`, `FleetOrder`, `FleetResource` sowie `ColonyShip`, `ColonyResearch`, `ColonyPersonell` (für transferTechnology). `App\Services\FleetService` portiert alle Methoden: getFleet, saveFleet, saveFleetOrder, getFleetOrdersByFleetIds, transferShip/Research/Personell/Technology/Resource, getFleetShip/Research/Personell/Resource (Singular + plural), getOrders, getFleetsByUserId/EntityId/Coords, getFleetTechnologies. `App\Http\Controllers\Fleet\FleetController` mit 5 Routen unter `/fleet` (index, config, addtofleet, technologies, resources). Blade-Views `fleet/index.blade.php` und `fleet/config.blade.php`. `Colony::getCoords()` ergänzt (wurde für transferTechnology benötigt). 23 Feature-Tests grün (2 skipped: addOrder/transferResource wie im Original), Gesamtstand: 163 Tests grün.

## 2026-03-23 (Trade)

- **Trade-Modul migriert (Schritt 8):** Eloquent-Modelle `TradeResource`, `TradeResearch` (Basistabellen, composite PK, kein incrementing) sowie `TradeResourceView`, `TradeResearchView` (lesen aus `v_trade_resources` und `v_trade_researches`). `App\Services\TradeGateway` portiert alle Operationen: getResources, getResearches, addResourceOffer, addResearchOffer, removeResourceOffer, removeResearchOffer — mit Ownership-Check via ColonyService. `App\Http\Controllers\Trade\TradeController` mit 5 Routen unter `/trade`. Blade-Views unter `resources/views/trade/`. Service in AppServiceProvider registriert. 18 neue Feature-Tests, Gesamtstand: 140 Tests grün.

## 2026-03-23

- **INNN-Modul migriert (Schritt 7):** `InnnMessage`, `InnnMessageView`, `InnnEvent`, `InnnNews` (Eloquent). `MessageService` (getMessage, getInboxMessages, getOutboxMessages, getArchivedMessages, sendMessage, setMessageStatus) und `EventService` (getEvent, getEvents, createEvent). `MessageController` mit 8 Routen unter `/messages`. 4 Blade-Templates (inbox, outbox, archive, compose). 39 neue Feature-Tests, 122/122 grün.

## 2026-03-21

- **Code-Analyse:** Vollständige Analyse der Laminas-Codebasis durchgeführt (373 PHP-Dateien, 11 Module, 42 Controller, 35 Table-Klassen, 98 Tests). Migrationsoptionen Laravel, PHP-Microframework und Python/Flask gegenübergestellt — Laravel als empfohlener Migrationspfad identifiziert.
- **Branch-Management:** Branch `claude/analyze-test-coverage-3rtlh` in `laminas-migration` gemergt (Fast-Forward). Branch lokal und remote gelöscht.
- **Tagging:** Tag `laminas-migration-finished` auf den aktuellen Stand von `laminas-migration` gesetzt. Tag `legacy-zf2-final` auf den letzten ZF2-Commit (`b325183`) gesetzt, um das Ende der Legacy-Version zu kennzeichnen.
- **README aktualisiert:** Quickstart auf PHP 8 / `composer install` / `./vendor/bin/phpunit` aktualisiert, Zend Framework 2 durch Laminas ersetzt, Google+ entfernt, Copyright-Jahr auf 2026 aktualisiert.
- **Pull Request erstellt:** PR von `laminas-migration` → `master` eröffnet, der die vollständige ZF2→Laminas+Bootstrap5-Migration zusammenfasst.
- **INNN Bugfix:** Schema-Inkonsistenz behoben — `nouron.db` verwendete camelCase-Spalten (`isRead`, `isArchived`, `isDeleted`) statt snake_case wie in `schema.sqlite.sql` und `test.db`. Tabelle und View in `nouron.db` neu erstellt, `MessageService.php` bleibt bei snake_case. Alle 19 INNN-Tests grün.
- **Testdaten:** Reiche Testdaten in `nouron.db` eingefügt: 3 neue Kolonien (Homer, Marge, Bart 2nd), 3 neue Flotten, 19 neue Nachrichten, 14 Events, 4 News-Einträge, 15 Handelsrouten für Ressourcen und Forschungen.
- **PR gemergt:** `laminas-migration` → `master` gemergt (Merge-Commit `7f3cac3`). Tag `laminas-migration-finished` auf den Merge-Commit aktualisiert.

## 2026-03-23 (INNN)

- **INNN-Modul migriert (Schritt 7):** Eloquent-Modelle `InnnMessage`, `InnnMessageView` (liest aus `v_innn_messages`-View mit Sender/Empfanger-Namen), `InnnEvent`, `InnnNews`. `App\Services\MessageService` portiert alle Methoden: getMessage, getInboxMessages, getOutboxMessages, getArchivedMessages, sendMessage, setMessageStatus. `App\Services\EventService` mit getEvent, getEvents, createEvent. `App\Http\Controllers\INNN\MessageController` vereint Inbox, Outbox, Archiv, Compose, Send, React, Remove. Blade-Views unter `resources/views/messages/`. Routen unter `/messages` (auth-geschützt). Services in AppServiceProvider registriert. 39 neue Feature-Tests, Gesamtstand: 122 Tests grün.

## 2026-03-23 (Galaxy)

- **Galaxy-Modul migriert (Schritt 6):** `App\Models\GlxSystem` (liest aus `v_glx_systems`-View), `App\Models\GlxSystemObject` (liest aus `v_glx_system_objects`-View). `App\Services\GalaxyService` portiert alle Methoden aus `Galaxy\Service\Gateway`: getSystems, getSystem, getSystemObjects, getSystemObject, getSystemObjectByColonyId, getSystemObjectByCoords, getObjectsByCoords, getColoniesByCoords, getSystemBySystemObject, getSystemByObjectCoords, getDistance, getDistanceTicks, getPath (Bresenham mit Speed). `GalaxyController` vereint IndexController, SystemController und JsonController (index, showSystem, getMapData). Blade-Views `galaxy/index.blade.php` und `galaxy/system.blade.php`. Galaxy-Routen unter `/galaxy` (auth-geschützt). Config-Werte `galaxy_view` und `system_view` in `config/game.php`. 36 neue Feature-Tests grün, Gesamtstand: 83 Tests grün.

## 2026-03-23

- **Resources-Modul migriert (Schritt 5):** `App\Models\Resource`, `ColonyResource`, `UserResource` (Eloquent). `App\Services\ResourcesService` mit allen Methoden (getResources, getColonyResources, getUserResources, getPossessionsByColonyId, check, payCosts, increaseAmount, decreaseAmount). `JsonController` mit 3 Endpunkten (GET /resources, /resources/colony/{id}, /resources/resourcebar). Blade-Partial für Ressourceleiste. 15 Feature-Tests grün. Bugfix: Composite-PK-Problem bei ColonyResource-Updates gelöst via `DB::table('colony_resources')->updateOrInsert(...)` statt Eloquent-`save()`.
- **Colony-Modul migriert (Schritt 4):** `App\Models\Colony` (Eloquent, liest aus `v_glx_colonies`-View), `App\Services\ColonyService` (alle 8 Methoden aus Laminas-Port: getColonies, getColony, getColoniesByUserId, checkColonyOwner, getPrimeColony, setActiveColony, setSelectedColony, getColoniesByCoords, getColonyByCoords, getColoniesBySystemObjectId). 24 Feature-Tests grün.
- **Test-Infrastruktur verbessert:** `DB_FOREIGN_KEYS=false` in `.env.testing` — SQLite lässt `PRAGMA foreign_keys = OFF` innerhalb von Transaktionen nicht zu, daher wird FK-Enforcement global für Tests deaktiviert. Die Testdaten aus `testdata.sqlite.sql` sind bereits konsistent. Colony-Tests seeden via `$this->app->make(TestSeeder::class)->run()` in `setUp()` innerhalb der offenen Test-Transaktion.
- **User/Auth-Modul migriert (Schritt 3):** `App\Models\User` (Eloquent, `user_id` PK, bcrypt-kompatibel), `LoginController`/`RegisterController`/`UserController`, Blade-Views für Login/Register/User-Profil, `routes/web.php` mit Guest- und Auth-Routen, angepasste `UserFactory`.
- **Test-Infrastruktur komplett:** `TestSeeder` spielt die Simpsons-Testdaten aus `data/sql/testdata.sqlite.sql` in die `:memory:`-DB ein — Laravel Feature Tests nutzen dieselbe kanonische Testbasis wie die Laminas Unit Tests. `DatabaseSeeder` ruft `TestSeeder` auf. 8/8 Laravel Feature Tests grün.

## 2026-03-22

- **Agenten-Definitionen angereichert:** Alle 7 `.claude/agents/`-Definitionen mit projektspezifischem Wissen ergänzt (Testpfade, PHPUnit-Binary, Factory-Pattern, Base-Classes, SQLite-Limitierungen, JS-Module, Nouron-2026-Vision).
- **Phase 1b definiert:** Laminas → Laravel als neue Phase 1b in CLAUDE.md und ROADMAP.md aufgenommen. ROADMAP.md mit 12-stufigem Migrationsplan erstellt (Bestandsaufnahme: 373 PHP-Dateien, 94 Factories, 31 Tables, 108 Tests).
- **Laravel 12 aufgesetzt (Schritt 0):** `laravel/framework ^12.0` neben Laminas installiert, PHPUnit 9.5 → 11.5 angehoben, `laminas/laminas-log` wegen psr/log-Konflikt entfernt, `AbstractService` auf Noop-Logger umgestellt. Verzeichnisstruktur (app/, bootstrap/, routes/, database/, storage/) und Entry Point eingerichtet.
- **DB-Migrations erstellt (Schritt 1):** 35 Laravel-Migration-Dateien + 6 Views aus `schema.sqlite.sql` übersetzt, korrekte FK-Reihenfolge, `colony_buildings` FK-Fehler korrigiert, `MIGRATION_LOG.md` erstellt.
- **Core-Schicht implementiert (Schritt 2):** `TickService`, `ValidatesId`-Trait, `BaseController` als Laravel-Äquivalente für `Core\Service\Tick`, `AbstractService._validateId()` und `IngameController`. `config/game.php` für Spielkonfiguration angelegt.
