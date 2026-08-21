# GDD Balance & TODO Index

Sammelübersicht aller offenen Balance- und Designfragen im GDD, damit nach einem Playtest an einer Stelle steht, was zu prüfen ist. Angelegt 2026-08-02. Die maßgebliche Formulierung steht jeweils am genannten Ort im GDD — dies ist ein Verzeichnis, keine zweite Quelle.

## A.1 Blockierend — vor der Implementierung des Ratenmodells zu klären

| Thema | Stand | Ort |
|---|---|---|
| **`max_level` in `max_instances` + `max_level` aufteilen** — für den Harvester kollidieren „kein Level-Up" und „Deckel 2 Instanzen" in einem Feld; der Hangar braucht beide Achsen | **entschieden, Umsetzung offen — blockiert §13.7 und §4c** | §4c |
| **Instanz-Decay-Verdacht verifizieren** — `processBuildingDecay()` schreibt ohne Instanz-Unterscheidung. Bestraft sonst jede Umstellung auf Instanzen sofort | **offen, blockierend, vor der Umstellung** | §4c |
| **Regolith-Zahlensatz** (Sockel, Reparatur, `decay_rate`, Bau- und Level-Up-Kosten) | ✅ vollständig freigegeben — Bau-/Level-Up-Kosten + Erschöpfungskurve (08-03), Sockel-Bilanz + G6/G2-Metrik-Umstellung (2026-08-06) | §13.7 |
| **Harvester-Erschöpfungsrate** | ✅ freigegeben 2026-08-03 (Kurve, `resource_max` 500/300/160, 2 AP/Hex) — **Vertrauensgrad niedrig-mittel, als Erstes messen.** Instanz-2-Bezugsweg „CC Lv3 + 100 Rg" ist überholt durch Owner-Entscheidung 2026-08-05 (zweite Runde): 2. Instanz ist jetzt Bonus statt Sockel-Baseline, Bezugsweg = Cantina-Händler-Item (Weg A, AP+Cr) oder Ruinen-Bergung (Weg B, nur AP, 0 Rg — korrigiert 2026-08-06), beide nicht garantiert — CC-Lv3-Gate bleibt als fiktionale Untergrenze. **Sockelrechnung (§13.7) auf 1-Instanz-Baseline neu hergeleitet und freigegeben (2026-08-06):** Sockel-Anteil ~57,5 %, G6-Formulierung + `decay_rate`-G2-Konflikt entschieden | §4c |
| Regolith-Parität der drei Pfade | **entschärft** — löst sich weitgehend auf, wenn Wachstum über Harvester-Instanzen läuft | §4b, §4c |
| Harvester ohne Level-Up | entschieden; Umsetzung nur gemeinsam mit dem Zahlensatz | §13.5, §4c |
| Tatsächliche `ap_for_levelup`-Werte in der laufenden DB | ✅ verifiziert 2026-08-02: überall 10, nur Monument 20 | gdd-config-audit.md |
| AP-Grundwert, Projektkosten, Bonus-Kurve | ✅ freigegeben 2026-08-03 mit `ap.base = 12` statt 10 | §13.6 |
| Erstes Gebäudelevel günstiger (Early-Game-Tempo) | vorläufig: `f(1) = 0.5` | §13.6 |
| Bodengarantie je Domäne | vorläufig: keine | §13.1 |
| Braucht Versorgung noch eine eigene Rolle? | vorläufig: ja, bleibt unverändert | §6 |
| Lage des Verfall-Gleichgewichts | geklärt — existiert bei den aktuellen Werten nicht, §13.5 umgeschrieben | §13.5 |
| `decay.overcap_factor` 2.0 → 1.5 + sichtbarer Zustand | Vorschlag liegt vor | §13.1 |

## A.2 Folgearbeiten aus der AP-Zusammenlegung

Stellen, die noch von getrennten AP-Pools ausgehen und nachzuziehen sind.

| Thema | Ort |
|---|---|
| Außenmissions-AP-Staffel (2–10 AP) war gegen den Navigations-Grundpool kalibriert | §8b |
| Cantina: AP als Deckel für Vielhandel — wirkt jetzt erstmals wirklich | §12 |
| Onboarding-Hinweistexte sprechen von „Bau-AP verfällt" o. ä. | `gdd/onboarding.md` §16.2 |
| Sol-1–4-Budget-Rechnung rechnet mit getrennten Pools | `gdd/onboarding.md` §16.5 |
| `locked_actionpoints.personell_type` — Pool-Trennung oder nur Auswertungsmerkmal? | §13 „Implementierung" |
| AP-Malus bei Aufruhr (−20 %) trifft jetzt die gesamte Kolonie statt einer Domäne | §14 |

## A.3 Nach dem ersten Playtest zu kalibrieren

| Thema | Ort |
|---|---|
| Sicherheits-Hub: Vertrauens-Bonus, Event-Dämpfung, Recycling-Anteil | §4 |
| Uplink-Station: Tiefenscan-Basiskosten, Händler-Erscheinungsrate | §4 |
| Handelsposten: Baukosten, Decay, Supply, Handelswert-Bonus | §4 |
| Korvetten-Stacking ohne Supply-Limiter | §6 |
| Schiffs-Verschleiß `wear_per_sol` | §7 |
| Missionen: Werkstoff-Durchsatz, Credit-Missionen mit mehreren Drohnen, Erkundungsflug vs. Ring-Erkundung, Milderungs-Stacking | §8b |
| Kolonistengefahren: Sturm und Seuchenausbruch lösen beide `colony_threatened` aus | §9 |
| Cantina-Verhandlung: `negotiate_bonus`, `negotiate_success_chance` | §12 |
| Berater-Außenmissionen: Analytiker-Bonus (stärkster Effekt), Rang-1-Misserfolgsrate | §13 |
| Konsul: Händler-Einschätzung nicht binär, erster Cantina-Besuch ohne Konsul | §13 |
| Vertrauen: theoretisches Maximum bei Voll-Ausbau, Kolonisten-Zulage ohne Cooldown, Bauwesen-Events wiederholbar | §14 |
| Run-Aufgabenpool: Wirtschafts-Cluster (1, 7, 9), Kollision Aufgabe 11 mit 2/8 | §15 |
| Highscore-Formel: Gewichtung | §15 |
| Fail-State: −20-Trust-Schwelle, `nexus_debt`-Mechanik | §18 |
| ~~`task_expedition_coverage: 19` als schwierigster Task-Target-Wert~~ — ✅ behoben 2026-08-14, war unerreichbar, jetzt 16 (s. §18) | §18 |
| Run-Ende: „Kolonie ansehen" setzt voraus, dass Koloniedaten erhalten bleiben | §18 |

## A.4 Offene Designfragen (kein Playtest nötig, Entscheidung steht aus)

**Nächste zusammenhängende Design-Runde: die Supply-Achse.** Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war als nach §13.7. Wird Bauen leichter, wird Supply relativ zum bindenderen Limiter — was §6 entspricht, aber verlangt, die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen. Zu dieser Runde gehören die drei folgenden Deckel-Fragen: sie bestimmen gemeinsam, wie tief eine Kolonie überhaupt wachsen kann.

| Thema | Ort |
|---|---|
| **Supply-Achse unconstrained neu herleiten** — `supply_cost` je Gebäude, Cap-Quellen, Zielkolonie gegen erreichbaren Cap | §6, §13.7 |
| **Level-Deckel für Cantina und Krankenstation** — beide heute `NULL` (unbegrenzt), was dem „kleine Kolonie"-Prinzip widerspricht | §4c, §1 |
| **Instanz-Deckel für den Agrardom** — mit der Umstellung auf Instanzen offen; hängt am Organika-Rennen und am Tile-Budget | §4c, §3 |
| **`max_level = NULL` bei sieben Gebäuden** (Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina) — die `f(L)`-Kostenkurve läuft dort ohne natürlichen Endpunkt weiter | §4c, §13.6 |
| Stratege — neu bewerten und designen (eigener Pfad oder Modifikator?) | §13 |
| Cantina: verlässlicher Credits→Regolith-Kanal (heute nur Verkaufsrichtung garantiert) | §13.5, §12 |
| Pfad-C-Regolith-Hebel neu denken | **✅ beantwortet** (§4b, freigegeben 2026-08-05; bestätigt durch die §13.7-Neuherleitung 2026-08-06: Pfad A + B schließen die Regolith-Lücke gemeinsam, 18,25 ≥ 14,1 Rg/Sol reif, ohne Pfad-C-Beitrag) | §13.7, §4b |
| `geology` als Träger des Regolith-Produktionsbonus — Höhe und Balance gegen den Analytik-Pfad insgesamt; bleibt nötig (trägt 12 der benötigten 14,1 Rg/Sol reif allein, §13.7 Punkt 5) — **nicht überflüssig** | §13.5, §4c |
| Wird Pfad B (Hangar) durch den Regolith-Bedarf faktisch zur Pflicht? | §4b, §13.5 |
| Pfad A (Analytik) hat keine eigene Credits-Quelle — Kostensenkung statt Einnahme? | §4b |
| **Credits-Bilanz über den Run** (analog zur Regolith-Bilanz in §13.7) — fehlt komplett; nötig, um die Organika-Verkauf-Zielgröße (Pfad C) neu und korrekt herzuleiten, nachdem die 247-Cr/Sol-Zahl am 2026-08-06 als falsch hergeleitet (aus dem Regolith-Gap statt aus dem Credits-Bedarf) zurückgezogen wurde | §13.7, §4b, §12 |
| `agronomy`-Kenntnis: hat sie einen Organika-Effekt oder nur den Supply-Cap-Bonus? | **✅ beantwortet** (2026-08-15, PR #253) — `agronomy` erhält einen Organika-Produktionsbonus auf den Agrardom, Parität zu `geology`s Harvester-Bonus | §4b, §10, §13.5 |
| **Kenntnisse-Boni komplett ausarbeiten** (Owner, 2026-08-12) — `config/knowledge.php` definiert bisher nur einheitliche `levelup_costs`/`credits` (0/20-28-36-44-52 für alle 7 Kenntnisse identisch), aber keine differenzierten Spieleffekte pro Kenntnis/Level. Kosten sind ebenfalls noch nicht gegeneinander austariert (aktuell für alle Kenntnisse gleich). Hängt mit dem `agronomy`-Punkt direkt darüber zusammen (ein Symptom desselben Problems), betrifft aber alle 7 Kenntnisse, nicht nur `agronomy` | §10, `config/knowledge.php` |
| **Phase-1-Pacing auf Sol-15-20-Ziel neu hergeleitet** (Owner, 2026-08-12, verschärft + umgesetzt 2026-08-13) — ✅ **umgesetzt + empirisch bestätigt** (§13.7 „Nachtrag 2026-08-12" + „Nachtrag 2026-08-13"). Befund: weder Harvester-Ertrag noch die Mengengrenze eines Tiles (`resource_max`) binden im Zielfenster, noch Berater-Hire-Credits — der alleinige Engpass ist der zu knappe Startbestand gegen die Ratengrenze des Harvesters. Der 08-12-Zielwert (300) beruhte auf einer um 35 Rg unterzählten Bedarfskette (Errichtung setzt Level 0, nicht Level 1 — der 0→1-Sprung ist ein separater Level-Up-Schritt, für beide Pfadgebäude komplett übersehen); korrigiert auf **200→340**. Zwei zugehörige PlaytestBot-Bugs (Rg-Puffer ignorierte platzierte-aber-ungelevelte Pfadgebäude; `productionInvestCandidate()` bevorzugte sie nicht) ebenfalls gefixt. `ResetPlayer.php` brauchte keine Anpassung (teilt `seedResources()` über `resetColonyToSol1()`), `testdata.sqlite.sql`s `start_amount`-Spalte ist tote Legacy-Spalte (ungenutzt) — beide geprüft, keine Änderung nötig. Ergebnis über 3 Testseeds: `phase2_start_sol` = 20–22 durchgehend. **Weiterhin offen:** §15-Bedingung-2-Wortlaut („2 Produktionsgebäude" vs. Code „2 Nicht-CC-Gebäude") klären — eigene, unabhängige Owner-Entscheidung | §13.7, §15, `app/Services/OnboardingService.php`, `tests/Feature/Playtest/BotStrategy.php` |
| **Events + Missionen im Detail ausarbeiten** (Owner, 2026-08-12) — Deep-Scan/`event_ruin`/Bergungsmission-Pfad wurde im PlaytestBot verdrahtet (PR #244, 2026-08-12) und dabei sichtbar, dass der Bot vorher nie deep-gescannt hat, weil Event-Content strukturell kaum genutzt wurde; wie viel davon am fehlenden Bot-Verhalten lag vs. am noch dünn ausgearbeiteten Event-/Missionskatalog selbst ist offen. Betrifft `config/missions.php` (bisher nur `mission_recon_flight`, `mission_ruin_expedition`, `mission_harvester_salvage`, `mission_supply_run` echt ausgearbeitet) und die Event-Typen hinter `event_type`/`is_deep_scanned` allgemein | §8b, §9, `config/missions.php` |
| Optionale dritte Bedingung für Run-Phase 1 (Roguelike-Variabilität) | §15 |
| Nexus-Boni in Phase 1 oder erst ab Phase 2? | §15 |
| Schiffe ohne Hangar (Events, Handelsdeals) — Phase 4+ | §6 |
| Kolonisten-Ausbildung — Design-Konzept, Phase 4+ | §10 |
| Exotics als vierter handelbarer Rohstoff — Phase 4+ | §3 |
| AP-Delegation zwischen Kolonien — Phase 4+ | §12 |

## A.5 Playtest-Instrumentierung

Ohne diese Messwerte ist keine der Zahlen aus §13.6 nach dem ersten Lauf begründet korrigierbar — man hätte nur Bauchgefühl.

| # | Metrik | Zielkorridor / Auslöser |
|---|---|---|
| 1 | **AP-Bilanz je Sol**, fünf Zahlen: Zufluss / Reparatur / Projekte / Handlungen / **ungenutzt** | ungenutzt > 15 % über mehrere Sole = Grundwert zu hoch |
| 2 | **Sole bis Fertigstellung** je Projekt, mit Start-Sol | erstes Drittel 4–6 Sole, letztes Drittel 1–2 |
| 3 | **Median gleichzeitiger Baustellen** | > 4 = Spieler streut, weil Einzelprojekte zu lang wirken |
| 4 | **Sol der letzten Projekt-Fertigstellung** (Kipppunkt-Test) | Ziel 85–92; über 95 = Kipppunkt fehlt |
| 5 | **Instandhaltungsanteil am Pool** je Sol | Ziel 20 % → 33 %; bleibt er unter 25 %, levelskalierte Reparatur nachrüsten (§13.5) |
| 6 | **Regolith-Bilanz**: Produktion / Reparatur / Level-Ups je Sol, aufgeschlüsselt nach Quelle (Harvester / Missionen / Handel / Events) | die eigentliche Wachstumsgrenze; besonders Sole 8–20 beobachten |
| 7 | **Supply-Auslastung** (`used/cap`) je Sol + Anzahl Sole über Cap | bleibt die Auslastung dauerhaft unter 70 %, ist Supply doch nicht bindend — dann wäre die Streichungsfrage aus §6 neu zu stellen |
| 8 | **Sole mit 0 AP je Domäne** | > 60 % in einer Domäne → prüfen, ob ein Hint nötig ist (keine Bodengarantie, §13.1) |
| 9 | **Regolith-Durchsatz je Pfad** (Frachter / `geology` / Cantina) | die drei Kanäle sollen im Playtest tatsächlich vergleichbar liefern (§13.5) |
| 10 | **Harvester-Umzüge pro Run** und Sole ohne Produktion durch Transit | Zielbild §4c: mehrere Umzüge, aber Umziehen darf keine Daueraufgabe werden |
| 11 | **Organika-Bilanz je Sol** (Produktion / Verpflegung / Missionsproviant / Events) und Anzahl Sole im Mangel | prüft, ob das Agrardom-Rennen aus §3 tatsächlich kippen kann |

**Metrik 7** ist der explizite Falsifikationstest für die Entscheidung, Supply zu behalten. **Metrik 9** für die Pfad-Parität.
