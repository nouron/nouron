# Handoff: Handtest 2 (Hangar-First, Master nach #388) — 2026-10-18

Quelle: Owner-Handtest auf master `d7506293`, Eröffnung **Hangar-First**, Phase 1 abgeschlossen in Sol 12 (Wartezeit auf Rg für die Uplink-Station). Dieses Dokument ist die Arbeitsliste für einen neuen Agenten.

## Vorab lesen

- `CLAUDE.md`, `GLOSSAR.md` (Spielbegriffe), `.claude/agents/README.md` (Briefing-Vorlage)
- Skill `nouron-balance-pass` + `docs/superpowers/specs/2026-10-16-phase1-pacing-entscheidungen.md` (alles mit Balance/Rg/AP)
- Regeln: TDD (Test zuerst, rot), nie auf master committen (Branch `feat/...` + PR), Pre-Merge-Checkliste (CHANGELOG, PR-Text, `docs/game-reference.md` bei Config-Änderung), Browser-Checks nur mit `tools/uicheck/uicheck` (Wegwerf-DB), PHPUnit nur `TEST_TOKEN=9 LARAVEL_PARALLEL_TESTING=1 php bin/phpunit ...`, **nie** gegen Dev-DB `nouron` (Port 8000 gehört dem Owner). Blade-Dateien 2× mit Prettier formatieren. Pint = `bin/pint`.
- Spielertransparenz hat hohe Priorität (Memory `feedback_player_transparency_priority`): angezeigte Zahl = wirkende Zahl.
- Bei Designfragen: erst `game-designer` (Annahmen als Annahme kennzeichnen, nicht als Owner-Entscheidung).

## Paket A — Bugs / Transparenz (klar umrissen, direkt umsetzbar)

| # | Befund | Fundstellen / Hinweise |
|---|---|---|
| A1 | **Rg-Kosten nicht im Button** bei Hangar-Stufenausbau: Rg (25) wurde abgezogen, Button zeigte es nicht. Beim Start von Stufe 3 war es korrekt sichtbar. Vermutung Owner: Stufe-2-Ausbau war schon gestartet, Rg wird beim Start des Ausbaus fällig, nicht bei AP-Investition. Reproduzieren: wann genau wird Rg abgebucht, wann zeigt der Chip es? | `app/Services/BuildingCostService.php` (`levelupRegolith`), `resources/views/partials/rg-cost-chip.blade.php`, `ap-upgrade-button.blade.php`, `ColonyController::repairRegolithCost()` und Row-Daten. Kosten müssen **vor** dem ersten Klick sichtbar sein und im Zustand "Ausbau läuft" klar sagen, ob Rg schon bezahlt ist. |
| A2 | **Fracht-Kauf im Hangar**: Seite aktualisiert sich nicht, kein Feedback, erst nach manuellem Reload sichtbar. | `resources/views/colony/hangar.blade.php`, zugehöriges JS/Controller. Regel Resourcebar-Live-Sync (Memory `feedback_resourcebar_live_sync`). Feedback + Liste + Credits aktualisieren. |
| A3 | **Netto-Sol-Bilanz stimmt nicht mit Popup überein** (Sol 5): CC zeigt Rg +34, Or +7, Cr +40; Resourcebar-Popup zeigt bei Credits nichts, bei Organika "Agrardome +8, Kolonisten −1" (Owner erwartet −4 bei 4 Kolonisten). | `app/Services/ResourceFlowService.php` (`perSol()`), `GameTick`, `command_center.blade.php`, Popups in `resourcebar.blade.php`. Klären: Wie viel Organika isst ein Kolonist wirklich (`supply_per_eater`)? Popup muss Credits-Quellen/-Senken zeigen (Paritätstest `ResourceFlowServiceTest` erweitern). Ein Wert, eine Rechnung. |
| A4 | **Sol-Bericht**: "Credits +40", "Nexus-Subvention & Relaisvergütung +50 Cr" unverständlich. | `lang/de/colony.php:642` (`sol_report_passive_credits`), `app/Services/SolReportService.php:536`, `GameTick.php:~1795` (Event `colony.passive_credits`). Herkunft der +40 aufschlüsseln (Einkommen vs. Unterhalt), Label verständlich (content-writer). |
| A5 | **Kolonisten-Anzeige verwirrt**: Nach Bau des Hangars springt "Kolonisten" von 4/26 auf 10/26. Belegung durch Gebäude (Supply) ist korrekt, aber die Anzeige erklärt es nicht. | `SolReportService` ("X von Max belegt"), Resourcebar. Entscheiden: Belegung getrennt von Bevölkerung zeigen. Verbindet sich mit A6. |
| A6 | **Auf Gebäude-Tiles fehlt die KOL-Belegung** des Gebäudes. | `public/js/colony-hexgrid.js`, Tile-Panel `hexview.blade.php`. Kleines Badge am Tile + Wert im Panel. |
| A7 | **Baumenü-Hinweis falsch**: `colony.build_mode_hint` "Gebäude wählen, dann Terrain-Tile anklicken" – man muss zuerst das Tile wählen. | `lang/de/colony.php:86`. Wird mit B2 (Tile-Klick öffnet Baumenü) ohnehin umgebaut, Text danach prüfen. |
| A8 | **Uplink-Station im Bau**: Label auf dem Tile nur "?". | `public/js/colony-hexgrid.js` (Label-/Namenslogik für Baustellen), Baustelle sollte Kurzname/Icon des Gebäudes zeigen. |
| A9 | **Kleine/mittlere/große Funde sehen auf der Karte identisch aus.** | `colony-hexgrid.js`, Tile-Typen `regolith_y{yield}_d{deposit}` (`config/game.php` ~Z.128–155). Visuell nach Größe unterscheiden (z.B. Marker-Größe/Punkte). |
| A10 | **Phasenziele**: Harvester, Wohnhabitat, Agrardom müssen nicht einzeln aufgezählt werden. | `RunProgressService::phase1Criteria()` (Single Source), `lang/de|en/colony.php`. Zeilen zusammenfassen bzw. weglassen; Spec-Doc/GDD prüfen, ob Kriterien selbst bleiben (nur Anzeige?) — Annahme kennzeichnen. |

## Paket B — kleine Design-/UX-Entscheidungen (mit Owner-Rückmeldung, dann umsetzen)

| # | Befund | Hinweis |
|---|---|---|
| B1 | **Rg-Kosten einheitlich**: Owner sagt, CC- und Wohnhabitat-Ausbau kosten "derzeit kein Rg". Im Code zahlt CC `Ziel-Lv × 30`, alle anderen (außer Harvester) flat 25 (`BuildingCostService::levelupRegolith`). **Erst verifizieren, was der Spieler tatsächlich sieht/zahlt** (hängt vermutlich mit A1 zusammen: Anzeige statt Rechnung). Owner-Ziel: einheitliche Regel; Start-Rg (`start_regolith` 300) muss für Phase 1 reichen, wer Rg verschwendet, scheitert zu Recht. Messung mit Bot nötig (Skill `nouron-balance-pass`), eine Änderung pro Messung. |
| B2 | **Tile-Klick öffnet direkt das Baumenü** bei freiem, erkundetem Tile in der Koloniezone (spart einen Klick). Tile-Infos müssen weiter sichtbar bleiben (Sidebar oder auf der Karte). | ui-specialist. `hexview.blade.php`, `colony-hexgrid.js`. |
| B3 | **Baumenü zeigt alle Gebäude**, nicht verfügbare ausgegraut (mit Grund: Stufen-Gate/Voraussetzung). Aktuell nur freigeschaltete. | ui-specialist; Gate-Gründe aus bestehendem Stufen-Gate-Code, Text per content-writer. |
| B4 | **Pop-up beim Fertigstellen wichtiger Gebäude** (Hangar, Labor, Cantina, Uplink-Station) mit Tutorial-Text, was jetzt möglich ist; in Einstellungen abschaltbar. | Neu: Einstellung speichern (Per-Spieler/Run), Texte content-writer, ui-specialist. Prüfen, ob Settings-Infrastruktur existiert. |
| B5 | **Hint bei Hangar-First** (Sol 9): "Noch keine Kenntnis auf Level 1 – im Techtree AP zuweisen", obwohl Techtree ohne Analytik-Labor gesperrt. Owner-Idee: rudimentären Techtree-Teil + erste Kenntnis vorab freischalten (Pfad-Parität), volle Features mit Analytik-Labor. | Designfrage → `game-designer`. Sofort-Fix unabhängig: Hint darf nicht erscheinen, solange Techtree gesperrt (`OnboardingHintService`, `lang/de/colony.php:112`). |
| B6 | **Phase-1-Ende leer**: Letzte Sole vor Abschluss "wusste nicht wohin mit AP", Erkundung war aufgebraucht (Sol 12 nichts mehr zu erkunden). Phase 1 endete nur wegen Rg-Wartezeit spät (Uplink). | Hängt mit B1 (Rg-Knappheit, Fundmenge) zusammen. Messen: Idle-AP je Eröffnung (`tools/ap-idle.php`). Nicht isoliert "fixen". |

## Paket C — Design-Ideen (nur Konzept, nichts implementieren; an `game-designer`)

1. **Vertrauen-Mechanik**: Startbonus + automatisches Absinken über mehrere Sols, wenn keine guten Ereignisse/Fortschritte (Details offen). Aktuell: `well_fed` +2/Sol, Rest-Events (`building_level_up`, `research_level_up`) in `config/game.php` `trust` nie gefeuert (offene Owner-Frage: verdrahten oder löschen).
2. **Vertrauen → AP**: ab Vertrauensstufe +1 bis +2 verfügbare AP; soll Credits-Ausgaben (Kolonisten-Zulage im CC) attraktiv machen. Start-Credits wirken zu üppig, Vertrauen hat kaum Relevanz.
3. **Gefahrenzone-Tiles** haben noch keinerlei Wirkung (Design offen: Effekt definieren oder Tile-Typ entfernen).
4. Bestehende Folgeplanung aus Vortag: feste Kenntnis-Werte statt Prozent (Baukunde −1 AP/Stufe, Agronomie +1 Organika/Stufe), Organika T36 (1 Einheit = 1 Kolonist), Kenntnis-Deckel für Analytiker, `lang/comm_log.php` "Stabilisierungsphase" (content-writer).

## Bestätigt gut (nicht anfassen)

- Erkundungsflüge funktionieren.
- Drang zur Erkundung wegen knappem Rg funktioniert auch in diesem Run.

## Empfohlene Reihenfolge

1. Branch `feat/handtest2-fixes` von master.
2. Paket A (A1–A3 zuerst, sie berühren dieselben Kosten-/Flow-Pfade; dann A4–A10), `backend-coder`/`game-developer` + `ui-specialist`, TDD.
3. B2/B3 + Hint-Sofortfix aus B5 als UI-Paket.
4. B1 + B6 als Balance-Messung (Bot-Batch, Dauer vorher mit Owner abstimmen: 8/16/24 Läufe, `--concurrency=24`).
5. B4/B5 und Paket C erst nach Owner-Entscheidung über `game-designer`.
6. Vor Merge: CHANGELOG-Tagesblock, PR-Text, `docs/game-reference.md` bei Config-Änderung. Merge nur auf explizite Owner-Anweisung.

## Fortschritt (Stand Session 1, Branch `feat/handtest2-fixes`, nichts committet)

- **A2 erledigt**: `HangarController::requestShip` liefert `credits` + `apAvailable`; `hangar.js` synct Resourcebar, klappt "Nicht zugewiesen" auf, zeigt Meldung (`colony.hangar_request_ordered`). Test `test_request_ship_response_carries_remaining_credits_for_resourcebar_sync`. Offen: Browser-Check (uicheck), ggf. CSS für `.hangar-notice`.
- **A3 teilweise**: `ResourceFlowService::perSol()` hat jetzt `credits` (Subvention, Relais, Berater-Unterhalt, Netto), Popup zeigt es; Paritätstest grün. **Organika −1 bei 4 Kolonisten ist kein Bug** (`game.food.supply_per_eater` = 4) — hängt an T36 (1 Einheit = 1 Kolonist), Owner-Entscheidung.
- **A4 Erkenntnis**: CC "Cr +40" = 50 Subvention − 10 Unterhalt (1 Berater Rang 1). Fehlt noch: Sol-Bericht-Label/Aufschlüsselung (`SolReportService.php:536`, `lang/de/colony.php:642`).
- **A1 nicht reproduziert**: Rg wird beim ersten AP-Klick eines Zyklus abgebucht (`ColonyController` ~Z.807–837), Chip blendet bei `ap_spend>0` bewusst aus (dann schon bezahlt) — Label sagt das nicht. Server/Client-Pfad im Tile-Panel konsistent; Verdacht: Zustand "Ausbau läuft" ohne Hinweis "Rg bezahlt". Browser-Repro mit `tools/uicheck/uicheck` nötig.
- Fehlt noch: A4–A10, Paket B/C.

### Session 2 (nach Antigravity-Stand)
- Antigravity hatte A2-CSS, `expires_at_tick` und A1-Label ("bereits bezahlt", `levelup_cost_already_paid`) ergänzt.
- **Erledigt:** A4 (Sol-Bericht-Zeilen `sol_report_subsidy/relay/upkeep`), A6 (`workplaces` in Building-Rows, Tile-Badge + Panel), A8 (Kürzel UP/HP), A9 (Fund-Punktgröße nach `event_type`). Browser-Check dieser JS-Änderungen steht aus.
- **Erledigt (Teil 2):** A5 (nur Texte: Popup/Sol-Bericht erklären Belegung statt Bevölkerung; Annahme: kein neues Bevölkerungs-Feld), A10 (Annahme: nur Anzeige, Abschlusskriterien unverändert).
- **Erledigt (Teil 3):** B5-Sofortfix (`checkHint4` braucht Analytik-Labor Lv1; Designfrage rudimentärer Techtree bleibt offen).
- **Erledigt (Teil 4):** B2 + B3 + A7 (Browser-Check mit uicheck: Menü öffnet per Tile-Klick, gesperrte Einträge mit Grund, A6-Badge/A8-Kürzel sichtbar, keine JS-Fehler; Colony-Tests 364 grün). Kleinigkeit: Kolonisten-Punkt überlappt leicht die Badge-Ecke.
- **Erledigt (Teil 5):** B4 (Popup bei Fertigstellung, Schalter respektiert; Texte `colony.built_popup_*` sind Entwurf, content-writer). PR #389 offen.
- **Offen:** A1-Browser-Repro, B1+B6 (Bot-Messung, Dauer mit Owner klären), B5-Design, Paket C. Browser-Check (uicheck) für alle JS/Blade-Änderungen dieser Branch steht aus.
