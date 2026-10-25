# Archiv: Historie der GDD-Unterdateien

> Geschichtliche Begründungen, Entscheidungsdaten und abgeschlossene Analysen, die aus den Dateien unter `docs/gdd/` entfernt wurden. Das GDD beschreibt nur den heutigen Sollzustand (ADR 0004). Hier steht, wie es dazu kam. Kein Teil des Sollzustands.

---

## onboarding.md

### Ausgliederung

Das Onboarding-Kapitel wurde am 2026-08-02 aus `docs/GDD.md` in eine eigene Datei ausgelagert.

### Hint-Rangfolge: Überarbeitung nach Playtest-Review (2026-07-14)

Owner-Befund: Die Hints führten in der falschen Reihenfolge (CC-Invest auf Sol 1, Pfadgebäude erst Sol 3, Beraterslot-Hint lief in die Sackgasse `path_building_missing`). Neue Ziellinie seitdem: Sol 1 Agrardom, Sol 2/3 Pfadgebäude, Sol 3/4 CC Lv2, sofort Berater 2.

Weitere Tabellenänderungen am 2026-07-14: `hint_invest_site` ersetzt das CC-fixierte `hint_cc_invest`; `hint_end_sol` feuerte zuvor trotz nutzbarer AP (Playtest-Fix); die Pfad-Hints schweigen, solange das erste Pfadgebäude gebaut wird oder der CC-Ausbau aussteht (Befund: "Kein Analytik-Labor"-Nag bei fertiger Cantina).

### Nachzieh-Vermerk nach der AP-Zusammenlegung (2026-08-02, Terminologie am 2026-09-06 vereinheitlicht)

Mehrere Hinweistexte und Bedingungen sprachen von domänenspezifischen AP-Pools ("AP bleibt beim Grundwert von 6", "AP nicht verfallen lassen", "Navigations-AP in Erkundung"). Mit dem gemeinsamen Pool (§13.1) gibt es diese Pools nicht mehr; AP verfallen nur noch als Ganzes. Betroffen waren `hint_1`, `hint_agrardome`, `hint_invest_site`, `hint_explore` und die Sol-1–4-Budget-Rechnung (rechnete mit getrennten Pools und alten Grundwerten; offener Task, ROADMAP Stufe 6). Spieltexte waren am 08-31/09-04 bereits angepasst.

### Designentscheidung Rang 5 (Reparieren, Lehr-Hint), überarbeitet 2026-07-14

Alle drei Startgebäude starten mit beschädigtem Zustand, der aber seit 2026-07-14 nicht mehr angezeigt wird: Schadens-Badge, Statusfärbung und Lehr-Hint greifen erst unter der Sichtbarkeits-Schwelle. Vorher lud der Hint nur zum Verbrennen knapper AP ein (Owner-Playtest-Befund). Ein mechanisches Repair-Gate am Baumeister-Hire wurde geprüft und verworfen: Es verletzt die Linie "Hints sind Hinweise, keine Gates" und würde im Urgent-Fall ohne Baumeister die einzige Rettungsaktion blockieren.

### Designentscheidung Rang 12 (CC-Lv2-Hint zustandsbasiert), 2026-07-14

`hint_3` feuerte vorher rein tick-basiert ab Sol 2. Owner-Playtest-Befund: "CC ist zuerst fertig, Beraterslot frei, aber Berater kann nicht eingestellt werden", weil Slot 2–4 ein gebautes Pfadgebäude verlangen. Seitdem feuert der Hint erst, wenn Agrardom und ein Pfadgebäude fertig sind.

### Verifikation der Schwellen (2026-06-21)

Prüfung gegen Config: Junior-Bonus +4 AP bestätigt; `ap_for_levelup` für CC kommt aus der DB-Migration `2026_04_17_000003_calibrate_building_ap_costs.php`, nicht aus `config/buildings.php`. Drift der alten Tabelle: Kenntnis-Hint Sol 9 statt Sol 8, Vertrauens-Hint Sol 6 statt Sol 5, die alte Cantina-Schwelle "Sol 8" existierte nicht mehr.

### "Baumeister zuerst" ist gewollt (Owner-Entscheidung 2026-06-21)

Befund 2 kritisierte, dass `hint_1` ohne Tick-Schwelle und ohne Alternative nur auf den Baumeister verweist und damit faktisch die einzige vom Hint-System unterstützte Eröffnung erzwingt. Owner-Entscheidung: beabsichtigt. Baumeister/AP ist die einzige Ressource, die in Sol 1 alles andere freischaltet. `hint_1` ist dismissable und blockiert nichts. Verworfen wurden `hint_no_scientist`/`hint_no_trader` auf Rang 1 und ein dynamischer Rang-1-Text. Die ursprüngliche Analyse nannte als de facto nahegelegte Standardreihenfolge: Baumeister, Harvester verlegen, CC-Vorinvestition/Erkunden, CC Lv2, 2. Berater, Cantina/Agrardom/Analytik. Empfehlungen 1 und 4 (Rang 1 generalisieren, Ränge 1–8 zu Wahlgruppe umbauen) wurden verworfen.

### Sol-1-Fokus (2026-07-14, ersetzt Fassungen vom 2026-06-21/24)

Die frühere Verengung ("nur Bau + Erkundung, Pfadwahl ab Sol 3") führte im Playtest dazu, dass Sol 1 AP in den CC versenkte und der Beraterslot nach CC Lv2 leer blieb. Neue Linie: Agrardom als erstes Bauprojekt (Sol 1), Pfadwahl ab Sol 2, CC Lv2 nach dem ersten Pfadgebäude. Die Schwellen der Pfad-Hints standen vorher auf Sol 3 (`hint_no_*_after_tick = 2`).

### Gleichwertige Pfadwahl (2026-06-24, Schwelle am 2026-07-14 aktualisiert)

Alle drei Pfad-Hints stehen auf derselben Tick-Schwelle. Am 2026-06-21 war die Cantina-Schwelle von `0` auf `2` korrigiert worden, weil die Cantina einen unbeabsichtigten Sol-Vorsprung hatte (echte Balance-Lücke, kein reiner Doku-Drift).

### Agrardom wird Pflichtgebäude (2026-06-24)

Frühere Fassung (2026-06-21): Agrardom war von der Cantina-vs.-Analytik-Wahlgruppe entkoppelt, aber optional. Seit 2026-06-24 ist er CC2-Bau-Voraussetzung (§4); `hint_agrardome` wechselte von Empfehlung zu Pflicht-Warnung, und `hint_build_priority` zählt ihn nicht mehr in die Pfadwahl.

### Balance-Concern zu `hint_4` (Kenntnis-Hint)

Ursprünglicher Concern: Der Kenntnis-Hint feuert vor dem Gebäude-Hint. Hinfällig durch die frühe Pfadwahl; die späte Schwelle bleibt als Sicherheitswarnung bewusst so.

### Startzustand

Implementiert 2026-06-11; Sichtbarkeit am 2026-07-14 überarbeitet (Startbeschädigung ist unsichtbarer Pacing-Timer statt sichtbares Lehrmaterial). Seit A44/H2 zwei vorab erkundete Ring-3-Regolith-Tiles (Gegensatzpaar).

### Balance-Anpassung der Rampe (2026-07-14)

Vorher endete der Hangar-Pfad bei Sol 4 mit ca. 7 Rg Rest (null Puffer für Repairs oder Housing-Ausbau). Zwei Hebel: `cc_upgrade_regolith_per_level` von 30 auf 20 (CC Lv2 = 40 statt 60 Rg) und `hangar.build_cost` von 90 auf 80 (Pfad-Gleichwertigkeit mit Cantina 70 / Sciencelab 80). Ein höherer Harvester-Yield wurde verworfen (globaler Hebel, Trivialisierungsrisiko); der Nexus-Kredit hilft nicht (liefert Credits, Engpass ist Regolith). Offen nach Playtest: Nexus-Kredit als eigener Discovery-Moment, sobald die Cantina steht.

### Repair-Concern (2026-06-21 erledigt)

Der Concern "Repair-Mechanik fehlt noch" war mit `hint_repair`, `hint_repair_urgent` und dem Reparieren-Button erledigt. Offen blieb nur die Validierung der Schwelle `hint_repair_urgent_sp` nach Playtest.

### Befund 1 – Leerlauf in den frühen Sols (Analyse 2026-06-21)

Gelöst durch die AP-Zusammenlegung (2026-08-02): Es gibt keine separaten Economy-/Strategy-Pools mehr, die ungenutzt verfallen. Ursprüngliche Punkte: Economy-AP (Konsul) und Strategy-AP (Stratege) hatten keinen Hint und im Frühspiel keine Verwendung (Strategy-AP erst ab Hangar/Korvette). Regolith-Überschuss bei AP-limitierten Bauprojekten. Credits (~2.700 Cr nach dem Baumeister) bewusst als "freie" Ressource ohne Hint.

- Depot-Hint (2026-06-21) geprüft und nicht umsetzbar: Es gab kein Resource-Storage-Cap-System, `cap` im Code meint nur den Supply-Cap, das Depot hatte keine Spielwirkung.
- Depot ersatzlos entfernt (2026-06-22): Das Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit hätte aktive Produktion bestraft und stand quer zum Roguelike-Prinzip. Betroffen waren Config, Lang-Dateien, Seeder, Demo-Seed und eine Migration. Bei Bedarf kann Depot samt Cap-System später neu eingeführt werden.
- Toter Config-Key `hint_no_engineer_ticks` entfernt (2026-06-21); Code-Fallback-Defaults auf Config-Werte synchronisiert, Test-Suite blieb grün.

### Befund 2 – Erzwungene Berater-Reihenfolge

Siehe "Baumeister zuerst ist gewollt" oben; Analyse und Empfehlungen sind abgeschlossen.

### Pfadwahl-Überarbeitung (2026-06-24): Implementierungs-Checkliste

Umgesetzt wurden: Hangar-Gate auf CC Lv2 gesenkt; Agrardom als Voraussetzung für CC Lv1→Lv2; generische Beraterslots 2–4 nach Bau-Reihenfolge der Pfadgebäude (neue Spalte `placed_at_tick` in `colony_buildings`, Tie-Break nach `building_id`); Bau-Gate für Pfadgebäude (`Anzahl platzierter Pfadgebäude < CC-Level − 1`, Fehler `path_gate_locked`); `OnboardingHintService`: `allChoiceBuildingsPlaced()`/`checkHintBuildPriority()` ohne Agrardom, neuer `checkHintHangarPath()`, Prereq-Methoden prüfen zusätzlich das Bau-Gate, `checkHint3()` prüft den Agrardom; neuer Config-Key `hint_no_hangar_after_tick`; neuer Lang-Key für `hint_hangar_path`. Tests: Bau-Gate, Agrardom-Voraussetzung, Slot-Zuordnung, Tie-Break (Bestand: 53 Tests im `OnboardingHintServiceTest`, Stand 2026-06-21).

### Config-Block (Stand 2026-06-21)

Der frühere Codeblock in der GDD-Datei listete `config/game.php → onboarding` mit Zahlen und Kommentaren. Er war teils veraltet (z. B. nannte er `hint_no_engineer_ticks`) und dupliziert die Config; die Config ist die Quelle der Wahrheit. Zusätzlich dokumentierte Drift: Code-Defaults in `OnboardingHintService.php` weichen von den Config-Werten ab (Config gewinnt immer).

### Formulierungen

- "Nexus-Briefing-Text ist bisher nur als Entwurf definiert" (offener Design-TODO, Abstimmung mit content-writer).

---

## techtree.md

- Ausgelagert aus `docs/GDD.md` am 2026-08-02.
- Owner-Entscheidung A43 (2026-09-24): Der Techtree hat nur zwei Aufgaben (Übersicht und Forschung); Gebäude-Aktionen liegen im Tile-Panel. Davor waren Gebäude an zwei Orten ausbaubar, und zwei Codepfade rechneten Supply-Gate, Rabatte und Rundung getrennt.
- Rückbau: Owner-Entscheidung A14/A43 (2026-09-24). Kein Vertrauens-Ereignis bei Rückbau und Stufenverlust durch Verfall: Owner-Entscheidung T13 (2026-09-25).
- Grid-Koordinaten Phase 2/3: Der Hangar wurde am 2026-06-28 von Phase 3 auf Phase 2 gezogen (Phase 2 dann 11 statt 9 Einträge); securityHub/strategist neu, alte Positionen von hangar/pilot frei.
- Blade/JS: Die vier getrennten `<section>`-Blöcke mit je eigenem `tech-grid` wurden zu einem gemeinsamen Grid zusammengeführt.
- AP-Typ-Spalte: seit der AP-Zusammenlegung Domäne statt eigener Pool.

---

## progressive-discovery.md

- Ausgelagert aus `docs/GDD.md` am 2026-08-02.
- Der Dialog `strategist_threat_assessment` (Stratege, Bedrohungseinschätzung) entfiel am 2026-08-02, weil der Stratege zurückgestellt wurde (§13); der prognostizierte Ausgang wird stattdessen dauerhaft im Dashboard gezeigt (§13.4).
- Der Almanach hieß zuvor "Ingame-Nachschlagewerk" in der ROADMAP.
