# Spec: Eröffnungsabhängige Phase-2-Ziele, 2026-11-27

**Status:** Zuschnitt vom Owner bestätigt (2026-11-27). Kein Code, keine Config geändert. Alle neuen Zielwerte sind **Annahmen**.
**Ersetzt:** Zielwert je Eröffnung (`opening_target`, Spec 2026-11-25 §3.1/§6 Option P5-A, Kriterium 10a) und Option A der Nacheichung 2026-11-26 in der bisherigen Form.
**Kennzeichnung:** gemessen (Quelle genannt), gerechnet (aus Config), Annahme.

## 0. Owner-Entscheidungen 2026-11-27 (fest)

1. Eröffnungsziele kommen nur in den Pool, wenn der Spieler diese Eröffnung gewählt hat (zuerst gebautes Pfadgebäude). Keine je Eröffnung verschiedenen Zielzahlen.
2. Jede Ziehung: genau ein Eröffnungsziel und zwei allgemeine.
3. Je Eröffnung zum Start ein mittelfristiges und ein langfristiges eigenes Ziel.
4. Exklusiv werden `task_expedition_coverage` (Hangar), `task_trade_volume` (Cantina) und `task_research_lead` (Labor). Letzteres ersetzt die frühere Entscheidung „bleibt allgemein“ und das vorgeschlagene neue Ziel „Fachgebiete“.
5. Die Ziehungsregel bleibt einfach: keine Ausschlussgruppen. Überschneiden sich Ziele, wird der Zuschnitt geändert.
6. Wartungsstand kommt als allgemeines mittelfristiges Ziel dazu, zuerst nur mitgemessen.
7. Als Eröffnung zählt das früheste platzierte Pfadgebäude mit mindestens Stufe 1 beim Phasenwechsel.
8. Eingespielte Crews und Stammrunde bleiben, mit Abbruchregel (§6). Expeditionen 24 und Händlerkäufe 18 sind vorläufige Annahmen bis zur Messung.

9. (nach Messung M7) Zuerst rüstet der Bot nach, danach werden Zielwerte festgelegt. Expeditionen zählt künftig nur Schwierigkeit „schwer“. Langfristiges Hangar-Ziel ist **Einsatzbreite** (verschiedene Missionsarten mindestens einmal erfolgreich; Startwert 9 von 12, **Annahme**); es ersetzt Eingespielte Crews und die Alternative Schwere Einsätze.

**Reihenfolge der Umsetzung:** (1) Ziehungsregel mit den drei bestehenden Fokus-Aufgaben; (2) neue Ziele und Wartungsstand mit vorläufigen Werten; (3) Bot verfolgt jedes Ziel gezielt, Expeditionen auf „schwer“; `aimless` bleibt unverändert; (4) Messung und Zielwerte.

**Stand:** (1) bis (3) umgesetzt; offen ist (4), die Messung. Bekannte Grenze: Der Bot erforscht nicht gezielt die Kenntnisse, die schwere Missionen oder weitere Missionsarten freischalten. Schlüssel: `task_research_breadth`, `task_mission_variety`, `task_cantina_circle`. Wartungsstand ist nicht im Pool (M7: Serie im Median 3 Sole).

Weiter gültig: mindestens ein mittel- und ein langfristiges Ziel, höchstens ein `economy`-Ziel, Sieg bei 2 von 3, keine Zielwahl durch den Spieler, erstes gespeichertes Ziel ab Phase-2-Start sichtbar. Kalibrierung: ohne Zielspiel frühestens ~Sol 90, gezielt Sol 70–85; `aimless` ~50 % Siege, `default` bis ~70 %.

Spielbegriff im UI: **Fokus-Aufgabe** (Glossar, Bedeutung wird angepasst); die Aufgabenkarte zeigt dazu das Fokus-Gebäude. Texte nennen weder „Pfad“ noch „Schwerpunkt“.

## 1. Ausgangslage

**Gemessen (M6, `storage/logs/playtest/20261126_m6`, 72 Runs):** Siege aimless 18/24 (Median Sol 75), default 20/24 (73), focus 22/24 (74,5). Bei Phase-2-Start stand in allen Runs genau ein Pfadgebäude; alle drei im Median erst ab Sol 47. Expeditionen und Handelspartner fallen ohne Zielspiel weit vor Sol 90; `focus` ist dort nicht schneller.

**Gemessen, Zusatz aus denselben Reports:** Kommandozentrale Stufe 5 erreichen 70 von 72 Runs, überwiegend zwischen Sol ~35 und ~65 (abgezählt: Run-Länge minus Sol-Einträge mit `cc_level` 5, auf wenige Sole genau). Das Stufen-Gate ist damit kein spätes Tor mehr. Kenntnisstufen am letzten Sol in vier Labor-Runs (Stichprobe):

| Run | Geologie / Agronomie / weitere | 4 höchste, je höchstens 4 | ohne Geologie und Agronomie |
|---|---|---|---|
| aimless Seed 1 (Sol 89) | 5 / 5 / 0 | 8 | 0 |
| aimless Seed 5 (Sol 88) | 5 / 4 / Konstruktion 1 | 9 | 1 |
| focus Seed 1 (Sol 75) | 5 / 4 / 3, 2, 2 | 13 | 7 |
| focus Seed 2 (Sol 95) | 5 / 5 / 1, 1, 1 | 10 | 3 |

Folge: Tiefe in den zwei Sockel-Kenntnissen entsteht nebenbei. Labor-Ziele müssen Breite über sie hinaus verlangen. Vergleichsanker: Forschungsvorsprung 16 braucht grob 60 AP über diesem Sockel (Annahme, gerechnet) und fällt gemessen (M6, `focus` Labor) um Sol 62–68.

## 2. Neue Ziehungsregel

### 2.1 Config je Ziel

- `opening`: `sciencelab` | `hangar` | `bar`, fehlt bei allgemeinen Zielen.
- `horizon`, `category`, `type`, `target` wie bisher.

### 2.2 Eröffnung bestimmen

Unter den Pfadgebäuden (31, 44, 52) der Kolonie mit `level >= 1` zählt das kleinste `MIN(placed_at_tick)`, bei Gleichstand die kleinere `building_id`; `NULL` gilt als zuletzt (Annahme). Dieselbe Abfrage steht in `AdvisorController::buildSlots`: nach `PathBuildingGateService` ziehen und gemeinsam nutzen. Bestimmt wird einmal beim Phasenwechsel; die gespeicherten Ziele ändern sich danach nie.

### 2.3 Ablauf

1. Pool nach `crc32(seed|task_key)` ordnen (einzige Zufallsquelle).
2. **Eröffnungsziel:** erstes Ziel der Rangfolge mit passendem `opening`. Der Seed entscheidet so zwischen mittel und lang.
3. **Zweites Ziel:** erstes allgemeines Ziel der jeweils anderen Zeithorizont-Klasse, das die `economy`-Regel einhält.
4. **Drittes Ziel:** erstes noch freie allgemeine Ziel beliebiger Klasse, das die `economy`-Regel einhält.
5. Speicherreihenfolge = Rangfolge (das erste ist sichtbar, nicht zwingend die Fokus-Aufgabe).
6. Fremde Eröffnungsziele werden nie gezogen. Eine Ziehung ohne Eröffnung gibt es nicht (Phase 1 verlangt ein Pfadgebäude).

Die Ziehung hängt jetzt an **Seed und Eröffnung** (und Config). Akzeptanzkriterium 2 der Spec 2026-11-25 und der Satz in GDD §15 „hängt nicht an der Eröffnung“ entfallen. Seed-Vergleiche in Messungen gelten nur noch innerhalb einer Eröffnung.

## 3. Die sechs Eröffnungsziele

| Eröffnung | Platz | Empfehlung | Schlüssel | Kategorie | Startwert |
|---|---|---|---|---|---|
| Labor | mittel | Forschungsvorsprung (bestehend) | `task_research_lead` | research | 16 |
| Labor | lang | Fächervielfalt | `task_research_breadth` | research | 11 |
| Hangar | mittel | Expeditionen (bestehend) | `task_expedition_coverage` | exploration | 24 |
| Hangar | lang | Eingespielte Crews | `task_hangar_experience` | exploration | 20 |
| Cantina | mittel | Handelspartner (bestehend) | `task_trade_volume` | economy | 18 |
| Cantina | lang | Stammrunde | `task_cantina_circle` | social (nicht `economy`) | 20 |

Alle vom Typ `counter`; Stufen sinken im Run nicht, also kein Rückschritt. Cantina-lang darf nicht `economy` sein, sonst wäre die Kreditreserve in jeder Cantina-Ziehung gesperrt. Muster: Kenntnisse, Missionen, Beziehungen (die Marke je Fokus-Gebäude).

### 3.1 Labor mittel: Forschungsvorsprung

- **Messgröße und Zielwert:** unverändert (Stufensumme aller Kenntnisse, 16). Neu ist nur, dass das Ziel ausschließlich nach einer Labor-Eröffnung gezogen wird.
- **Gemessen (M6, alle Eröffnungen):** `aimless` 1/6 erfüllt, `focus` 6/6 (Median Sol 63,5). Für Labor allein neu auswerten.

### 3.2 Labor lang: Fächervielfalt

- **Messgröße:** Stufensumme aller Kenntnisse **ohne die Sockel-Kenntnisse** Geologie und Agronomie (gleiche Quelle; Ausschlussliste in der Config).
- **Labor-Bezug:** wie oben, hier stärker: mehrere neue Kenntnisse bis in hohe Stufen brauchen gehobenen Deckel und Rabatt.
- **Nicht nebenbei:** Mit Labor allein ist nur Konstruktion offen (höchstens 5), also braucht es weitere Gebäude. 11 heißt z. B. 4 + 4 + 3: gerechnet 135 AP von null. Sockel ohne Zielspiel 0–1, bei Forschung auf ein anderes Ziel 3–7 (Stichprobe §1). Erwartet gezielt um Sol 78–90 (Annahme).
- **Anzeige:** „Fächervielfalt: Kenntnisse außer Geologie und Agronomie zusammen mindestens Stufe 11“.
- **Bot:** Konstruktion, Kartografie, Handel hochziehen; zweites und drittes Pfadgebäude, Analytiker-Rang.
- **Alternative:** vier höchste Kenntnisse zusammen 18 (Höchstwert 20; Sockel 10, es fehlen etwa 100 AP). Verworfen: drei höchste zusammen 14 (Höchstwert 15, Sockel 10, nur etwa 50 AP über dem Sockel, also zu früh).

> ⚠️ BALANCE CONCERN: Beide Labor-Ziele messen Forschung. Sie kommen nie in derselben Ziehung vor (ein Eröffnungsziel je Ziehung). Der Sockel stammt aus nur vier Runs.

### 3.3 Hangar mittel: Expeditionen

- **Messgröße:** unverändert, erfolgreiche Missionen ab Schwierigkeit „normal“ (`colony_hangar_missions`).
- **Startwert 24 (Annahme).** Gemessen (M6): 10 fällt mit Hangar bei `aimless` Sol 28–33, bei `focus` Sol 31–48. Linear fortgeschrieben läge die bisher angenommene 18 um Sol 50 (Annahme, rechtszensiert: Runs enden bei 2 von 3, Tempo nach Hangar-Ausbau unbekannt). 24 fällt fortgeschrieben um Sol 60–70, **auch bei `aimless`**; das verfehlt die Kalibrierregel bewusst vorerst (erst ~33 läge für `aimless` um Sol 90, dann läge auch `focus` jenseits von 85).
- **Anzeige:** wie heute mit Zielwert. **Bot:** muss Einsätze ab „normal“ und freie Buchten vorziehen können (heute ungeklärt).

### 3.4 Hangar lang: Eingespielte Crews

- **Messgröße:** Summe der Stufen aller Missionsfamilien (`colony_path_levels`, `kind` = Missionsfamilie, Spalte `level`).
- **Hangar-Bezug:** Training im Hangar. Bei Hangar-Stufe 2 sind höchstens 16 erreichbar (gerechnet aus Familien-Freischaltung und Stufen-Gate); 20 verlangt Hangar-Stufe 3.
- **Aufwand:** gerechnet 110 AP (fünf Familien auf 4) bis 128 AP (vier auf 5), begrenzt durch den Training-Deckel je Familie und Sol (Raumfahrer hebt ihn).
- **Anzeige:** „Eingespielte Crews: Missionserfahrung zusammen mindestens 20“. **Bot:** Training erhält Zielwert statt Restposten; Hangar 3, Raumfahrer.
- **Alternative:** Schwere Einsätze, 10 erfolgreiche Missionen der Schwierigkeit „hard“ (vorhandene Spalten `difficulty`, `succeeded`).

> ⚠️ BALANCE CONCERN: Der Bot trainiert mit übrigen AP (T58a), das Spätspiel hat viele ungenutzte AP. `aimless` könnte das Ziel nebenbei erfüllen. Die Reports erfassen keine Missionserfahrung: erst messen (Schritt 0).

### 3.5 Cantina mittel: Handelspartner

- **Messgröße:** unverändert, Käufe beim reisenden Händler (`merchant_items.sold`, braucht gebaute Cantina).
- **Startwert 18 (Annahme).** Gemessen (M6, Cantina, 24 Runs): 8 in 22/24 (Median Sol 48), 10 in 11/24, 14 in 9/24 (Sol 59), Run-Ende Median 69,5. Die Quoten sind durch das Run-Ende zensiert; die bisher angenommene 14 läge trotzdem klar vor Sol 70. 18 fällt im selben Tempo fortgeschrieben um Sol 65–70, ebenfalls **auch ohne Zielspiel** (Annahme; wie §3.3 bewusste vorläufige Abweichung). Vorher ohne Bot prüfen, wie viele Käufe die Händlerbesuche bis Sol 85 überhaupt zulassen.
- **Bot:** Credits für Händlerkäufe zurückhalten, Cantina und Uplink vorziehen (Wirkung offen).

### 3.6 Cantina lang: Stammrunde

- **Messgröße:** Summe der Beziehungsstufen der fünf engsten Figuren, je höchstens 4 („befreundet“) gezählt (`colony_path_levels`, `kind` = Figur). Config zusätzlich `count` 5, `level` 4.
- **Cantina-Bezug und Bremse:** ein Gespräch je Figur und Sol, nur anwesende Figuren (Tomas immer, sonst ein bis drei Gäste), langes Gespräch erst ab Cantina-Stufe 3. Gerechnet 50 AP und rund sieben Besuche je Figur mit langem Gespräch: Die **Anwesenheit** begrenzt, nicht die AP. Dauer unbekannt (Annahme: Spätspiel).
- **Anzeige:** „Stammrunde: fünf Figuren mindestens befreundet“, Fortschritt x/20.
- **Bot:** die fünf weitesten Figuren gezielt ansprechen statt „Tomas zuerst“; Cantina 3.
- **Alternative:** sieben Figuren mindestens „vertraut“ (21). Verworfen: erledigte Anliegen zählen (reiner Zufallsfluss; `bar_concerns` wird beim neuen Run nicht gelöscht).

> ⚠️ BALANCE CONCERN: Gleiches Nebenbei-Risiko wie §3.4 (Gespräche als Restposten); zu seltene Anwesenheit kann das Ziel auch unerreichbar machen. Erst messen.

## 4. Allgemeiner Pool und mögliche Ziehungen

Allgemein: mittelfristig Kreditreserve (`economy`), später Wartungsstand; langfristig Expertenstab, Ingenieursleistung, Selbstversorgung, Kolonieblüte.

Gerechnet: Anzahl möglicher Paare allgemeiner Ziele (nicht Häufigkeiten).

| Eröffnungsziel | ohne Wartungsstand | mit Wartungsstand |
|---|---|---|
| Labor mittel, Hangar mittel | 10 | 14 |
| Cantina mittel (`economy`) | 6 | 10 |
| jedes lange Eröffnungsziel | 4 (Kreditreserve immer) | 9 |
| Ziehungen je Eröffnung: Labor, Hangar, Cantina | 14, 14, 10 | 23, 23, 19 |

Bis Wartungsstand aufgenommen ist, steht neben jedem langen Eröffnungsziel die Kreditreserve.

**Wartungsstand** (bestätigt, zuerst Schattenwert) (`task_maintenance`, mittel, Kategorie `maintenance`, `streak`): Alle Gebäude halten mindestens 70 % ihrer Statuspunkte (`colony_buildings.status_points` gegen den Höchstwert), 20 Sole in Folge (beides Annahme). Zivil, für jede Eröffnung gleich, Entscheidung zwischen Reparieren und Ausbauen. Risiko: Der Bot repariert ohnehin, daher vor Aufnahme als Schattenwert messen. Anzeige: „Wartungsstand: alle Gebäude in gutem Zustand, 20 Sole in Folge“. Bot: Reparaturschwelle anheben, solange offen.

Die Kreditreserve bleibt allgemein; mit Cantina fällt sie früher (gemessen M4, `focus` Cantina Sol 39–40). Nach der Messung gesondert prüfen.

## 5. Auswirkungen

**Code:** `RunProgressService` (`selectObjectives` erhält die Eröffnung; drei neue Update-Methoden (Wartungsstand später)), `RunTaskCatalog` (`opening`, Beschriftung), `PathBuildingGateService`, `config/game.php` (`run.tasks`, `task_pool`, veraltete Kommentare), `ResetPlayer` (Szenario `objectives-done` prüfen). Keine Migration; neue Schlüssel passen in `task_key`.

**Tests:** `RunObjectivesTest`, `RunObjectivesRedesignTest`, `RunProgressServiceTest`, `PlaytestBotTest` und die Bot-Stellen, die die Ziehung nutzen (`BotStrategy`).

**Akzeptanzkriterien (TDD):**
1. Seeds 1–1000 je bestimmter Eröffnung (ausgelieferte Config): drei verschiedene Ziele, genau eines mit passendem `opening`, nie ein fremdes, mindestens je eines mittel und lang, höchstens ein `economy`.
2. Gleicher Seed und gleiche Eröffnung: gleiche Ziele, gleiche Reihenfolge (Rangfolge).
3. Zwei stehende Pfadgebäude: das früher platzierte zählt; Gleichstand: kleinere ID; Stufe 0 zählt nicht.
4. Je neuem Ziel: Zählung inklusive Deckelung (Fächervielfalt, Stammrunde), Erfüllungs-Sol, Beschriftung zeigt Config-Werte, Nexus-Leiter rechnet mit `current/target`.

**Doku:** GDD §15 (Ziehung, Tabelle, Fokus-Aufgaben, Frage „frühe Fokus-Aufgaben“ erledigt) und §18.1; `docs/game-reference.md` §18; `lang/de|en/run.php` (content-writer); Glossar (Fokus-Aufgabe: nur noch im eigenen Run, Forschungsvorsprung als Labor-Aufgabe; drei neue Aufgaben, später Wartungsstand); ROADMAP.

**Bot:** `BotContext` kennt die Eröffnung nur über die gezogenen Ziele; Zielbewertung je neuem Ziel (§3). `aimless` bewertet keines davon.

## 6. Umsetzung und Messung

Jeder Batch: Owner-Freigabe und Dauerschätzung vorher.

| Schritt | Änderung | Prüfung |
|---|---|---|
| 0 | Nur Messung: Reports erfassen je Sol die Messgrößen der sechs Eröffnungsziele und Wartungsstand; Messmodus ohne Run-Ende durch Sieg, bis Sol 100. Ohne Bot: Ziehungen Seeds 1–1000 auszählen, Händler-Obergrenze klären | Zielwert je Ziel so wählen, dass `aimless` ihn im Median nicht vor Sol 90 erreicht; erst dann Config |
| 1 | Ziehungsregel + drei neue Ziele + zwei neue Zielwerte + Bot-Bewertung | Batch wie M6 (3 Profile × 3 Eröffnungen × Seeds 1–8) |
| 2+ | je eine Änderung: ein Zielwert, oder Wartungsstand | gleicher Batch |

Schritt 1 ist begründet gebündelt: Jede Eröffnung braucht beide Ziele, ein halber Pool misst nichts; jedes Ziel hat einen eigenen Erfüllungs-Sol und ist einzeln widerlegbar.

**Hypothesen Schritt 1 (Annahme):**
- Siege `aimless` 10–14/24, `default` 14–18/24, `focus` mindestens 18/24 mit Median Sol 70–85.
- Eröffnungen (alle Profile) höchstens 15 Prozentpunkte auseinander.
- Forschungsvorsprung, Fächervielfalt, Crews, Stammrunde, wo gezogen: `aimless` erfüllt vor Sol 90 höchstens in 1 von 4 Runs; `focus` bis Sol 85 in mindestens der Hälfte. Widerlegt eines davon: nur diesen Zielwert ändern; bei Crews und Stammrunde ohne Abstand zwischen den Profilen auf die Alternative wechseln.
- Expeditionen (24) und Handelspartner (18): Erfüllungs-Median beider Profile Sol 60–75, `focus` nicht mehr als 5 Sole früher. Widerlegt, wenn der Median außerhalb liegt. Ob die Abweichung von „ohne Ziel ab Sol 90“ tragbar ist, entscheidet die Siegquote.

Offene Bot-Variablen: Training und Gespräche als Restposten, Missionswahl, Händlerkäufe (`focus` bisher nicht schneller), Bau weiterer Gebäude, Kommandozentrale 5.

> ⚠️ BALANCE CONCERN: Wo gezieltes Spiel das Tempo nicht erhöhen kann (Anwesenheit, Händlerbesuche), trennt kein Zielwert „ohne Ziel ab Sol 90“ von „gezielt Sol 70–85“. Dann ist die Mechanik zu prüfen, nicht die Zahl.

## 7. Weitere Ideen (später)

- Labor: Datenarchiv (gesamte investierte Forschungs-AP; braucht neue Erfassung).
- Hangar: Bergungsmeister (erfolgreiche Bergungsflüge; Funde sind begrenzt).
- Cantina: Vermittler (erfolgreich gelöste Anliegen; Run-Filter nötig).

## 8. Messung vor Schritt 0: neuer Vergleichswert

Schritt 0 ist zugleich die neue Vergleichsmessung. Sie enthält **zwei** Änderungen gegenüber M6: Der Bot repariert Schiffe (#504) und Bergungsflüge verschleißen (#505). Beide Wirkungen lassen sich nicht getrennt ablesen (Owner hat die gemeinsame Messung bestätigt). Die Hypothese wird vor der Freigabe des Laufs hier eingetragen. Nach dieser Messung dürfen die Rohberichte früherer Batches gelöscht werden.

### 8.1 Hypothese der Vergleichsmessung (vor dem Lauf notiert, 2026-11-27)

**Lauf:** 72 Runs wie M6 (`aimless`, `default`, `focus` × Labor, Hangar, Cantina × Seeds 1–8), Messmodus `GAME_BYPASS_VICTORY=true`, also jeder Run bis Sol 100 oder bis zu einer Niederlage. „Sieg“ heißt in der Auswertung: erster Sol mit zwei erfüllten gezogenen Aufgaben (alte Ziehung, alte Zielwerte). Alle Erwartungen sind **Annahmen**.

| Größe | Erwartung | Widerlegt, wenn |
|---|---|---|
| Siege je Profil | höchstens 3 von 24 neben M6 (18 / 20 / 22) | Abweichung größer |
| Median Sieg-Sol | Sol 70–80 je Profil | außerhalb |
| Schiffsreparatur | `ship_repair_spent` > 0 in Runs mit Schiff; `focus_expedition_mission` wird ausgeführt | nie ausgeführt |
| Expeditionen 24, Hangar-Runs | Median Sol 55–75 (`aimless`) | außerhalb |
| Händlerkäufe 18, Cantina-Runs | in mindestens der Hälfte erreicht, Median Sol 65–80 | seltener oder außerhalb |
| Fächervielfalt 11, Crews 20, Stammrunde 20, jeweils eigene Eröffnung | `aimless` erreicht sie vor Sol 90 höchstens in 2 von 8 Runs | öfter |
| Wartungsstand (20 Sole in Folge) | offen, reine Erhebung | — |

## 9. Messhistorie

### 9.1 Vergleichsmessung `20261127_m7` (2026-11-27)

72 Runs wie M6, Messmodus ohne Sieg-Ende, alle bis Sol 100. Enthält #504 (Bot repariert Schiffe) und #505 (Bergungsflüge verschleißen) gemeinsam. 9 Runs brachen zuerst mit Datenbank-Deadlocks ab und wurden mit demselben Batch-Namen nachgeholt. Alle Zahlen **gemessen**.

**Siege** (zwei gezogene Aufgaben erfüllt, alte Ziehung und Zielwerte):

| Profil | M6 | M7 | Median Sieg-Sol M7 |
|---|---|---|---|
| `aimless` | 18/24 | 23/24 | 74 |
| `default` | 20/24 | 22/24 | 75 |
| `focus` | 22/24 | 23/24 | 78 |

**Neue Zielwerte, jeweils nur Runs der eigenen Eröffnung** (erreicht von 8, in Klammern Median Sol):

| Messgröße (Wert) | Eröffnung | `aimless` | `default` | `focus` |
|---|---|---|---|---|
| Forschungsvorsprung (16) | Labor | 0 | 2 (70) | 2 (72,5) |
| Fächervielfalt (11) | Labor | 0 | 0 | 0 |
| Expeditionen (24) | Hangar | 8 (49,5) | 8 (50) | 8 (51,5) |
| Eingespielte Crews (20) | Hangar | 8 (82) | 8 (83) | 7 (83) |
| Händlerkäufe (18) | Cantina | 7 (80) | 7 (85) | 6 (83) |
| Stammrunde (20) | Cantina | 0 | 0 | 0 |

Wartungsstand (20 Sole in Folge): in keinem der 72 Runs erreicht, längste Serie im Median 3 Sole. Welches Gebäude die Serie bricht, ist nicht geprüft.

**Eröffnungen, `aimless`, Median an Sol 90:**

| Messgröße | Labor | Hangar | Cantina |
|---|---|---|---|
| Expeditionen | 51 | 45 | 30,5 |
| Händlerkäufe | 16 | 9,5 | 21 |
| Forschungsvorsprung | 10 | 10 | 17 |

Alle drei Pfadgebäude stehen am Ende in jedem Run. Expeditionen liegen nach Labor höher als nach Hangar.

**Abgleich mit der Hypothese (§8.1):**

| Erwartung | Ergebnis |
|---|---|
| Siege höchstens 3 neben M6 | **widerlegt** (`aimless` +5) |
| Median Sieg-Sol 70–80 | erfüllt |
| Schiffsreparatur findet statt | erfüllt (alle Runs) |
| `focus_expedition_mission` wird ausgeführt | **widerlegt** (0 Runs) |
| Expeditionen 24, Hangar, Median 55–75 | **widerlegt** (49,5; 36 fällt um Sol 61) |
| Händlerkäufe 18, Cantina | erfüllt, am oberen Rand (7/8, Median 80) |
| Crews 20 selten vor Sol 90 | **widerlegt** (`aimless` 8/8) |
| Fächervielfalt, Stammrunde selten vor Sol 90 | erfüllt (nie), Erreichbarkeit offen |

**Befunde zum Bot** (Einschränkung aller Werte):

- `focus_expedition_mission` ist nicht kaputt, sondern überflüssig: Die Regel `dispatch_credit_mission` steht davor und fliegt ausschließlich Missionen der Schwierigkeit „normal“, die bereits zählen. Die frühere Annahme, die fehlende Reparatur sei der Grund, war falsch oder unvollständig. Damit zählt auch jede Kurierfahrt für Expeditionen.
- Direkte Forschung geht bei `aimless` fast nur in Geologie und Agronomie (Labor und Hangar: ausschließlich). Die höheren Kenntnisstufen nach Cantina-Eröffnung kommen offenbar über die Cantina (Annahme; Handel steigt ohne direkten Forschungsauftrag). Forschungsvorsprung und Fächervielfalt messen deshalb vor allem die Vorlieben des Bots.
- Für die neuen Ziele gibt es noch keine Bot-Regeln; die `focus`-Werte dazu sind ungezielt.
- Wo gezieltes Spiel nicht schneller ist (Expeditionen, Händlerkäufe), trennt kein Zielwert „ohne Ziel ab Sol 90“ von „gezielt Sol 70–85“.

**Anwendung der Abbruchregel (§6):** Eingespielte Crews fällt nebenbei und ohne Abstand zwischen den Profilen. Es gilt die Alternative **Schwere Einsätze** (10 erfolgreiche Missionen der Schwierigkeit „schwer“, **Annahme**, ungemessen: Der Bot fliegt keine schweren Missionen).


### 9.2 Messung `20261127_m8` — Hypothese (vor dem Lauf notiert)

**Lauf:** wie M7 (72 Runs, Messmodus bis Sol 100). Änderungen gegenüber M7: neue Ziehung (ein Eröffnungsziel), drei neue Langzeitziele, Expeditionen nur „schwer“, neue Fokus-Regeln, `READ COMMITTED`. Zielwerte vorläufig. Gezieltes Spiel ist nur in Runs bewertbar, in denen das Ziel gezogen wurde (je Eröffnung etwa die Hälfte, also kleine Fallzahlen). Alle Erwartungen sind **Annahmen**.

| Größe | Erwartung | Widerlegt, wenn |
|---|---|---|
| Abbrüche durch Deadlocks | keine | mindestens ein Abbruch durch Deadlock |
| Siege `aimless` | 12–18 von 24 | außerhalb |
| Siege `focus` | nicht weniger als `aimless` | weniger |
| Expeditionen 10 „schwer“, Hangar | `aimless` vor Sol 90 höchstens 2 von 8; `focus` (gezogen) mindestens die Hälfte bis Sol 85 | eines davon verfehlt |
| Forschungsvorsprung 16, Labor | `aimless` höchstens 1 von 8; `focus` (gezogen) mindestens die Hälfte | eines davon verfehlt |
| Händlerkäufe 8, Cantina | beide Profile im Median bis Sol 65 (zu leicht) | Median später |
| Fächervielfalt 11, Einsatzbreite 9, Stammrunde 20, jeweils eigene Eröffnung | `aimless` vor Sol 90 höchstens 1 von 8; `focus` (gezogen) mindestens einmal erreicht | `aimless` öfter, oder `focus` nie (dann vermutlich unerreichbar) |

### 9.3 Messung `20261127_m8` — Ergebnis (2026-11-27)

72 von 72 Runs im ersten Durchgang, **keine Abbrüche** (ein Lauf mit 24 parallelen Bots; erster Beleg für `READ COMMITTED`). Alle Zahlen **gemessen**.

**Siege** (zwei gezogene Aufgaben bis Sol 100): `aimless` 14/24 (Median Sieg-Sol 83,5), `default` 20/24 (87,5), `focus` 18/24 (82). Je Eröffnung `aimless`: Labor 4/8, Hangar 3/8, Cantina 7/8.

**Eröffnungsziele** (Runs der eigenen Eröffnung; „gezielt“ = `focus`, Ziel gezogen, kleine Fallzahlen):

| Ziel (Wert) | ohne Zielspiel | gezielt | Urteil |
|---|---|---|---|
| Forschungsvorsprung (16) | 0/8, an Sol 90 Stand 10 | 2/2, Sol 69 | passt |
| Fächervielfalt (11) | 0/8, an Sol 90 Stand 0 | 6/6, Sol 84 | passt, eher spät |
| Händlerkäufe (8) | 4/4 (gezogen), Sol 36,5 | 4/4, Sol 42 | viel zu leicht |
| Stammrunde (20) | 0/8, an Sol 90 Stand 9 | 2/4, Sol 72,5 | erreichbar, eher zu hoch |
| Expeditionen (10, „schwer“) | 0 schwere Erfolge in allen Hangar-Runs | 0/3 | Bot-Lücke |
| Einsatzbreite (9) | höchstens 4 | höchstens 5 | Bot-Lücke |

Nachgerechnet aus denselben Reports (Schwelle: erster Sol, an dem der Wert erreicht war; gezogene Runs, 4 je Profil): Händlerkäufe 14 `aimless` Sol 73 / `focus` Sol 59; 16: 80 (3/4) / 66. Stammrunde 16: `aimless` 0/4 / `focus` Sol 67; `default` 89 (3/4).

**Abgleich mit der Hypothese (§9.2):** erfüllt: keine Abbrüche, Siege `aimless` 12–18, Forschungsvorsprung, Händlerkäufe zu leicht, Fächervielfalt, Stammrunde. Siege `focus` liegen wie erwartet nicht unter `aimless`, aber unter `default` (nicht vorhergesagt). **Widerlegt:** Expeditionen und Einsatzbreite (`focus` nie).

**Ursache der Hangar-Lücke (belegt):** Der Bot kauft nie eine Korvette (`shipToRequest` kennt nur Drohne und Frachter; alle 8 `aimless`- und `focus`-Hangar-Runs besitzen nur diese beiden). Schwere Missionen von Drohne und Frachter verlangen Kenntnisse, die der Bot nach Hangar-Eröffnung meist nicht hat (Konstruktion in 3 bzw. 2 von 8 Runs, Handel in keinem).

### 9.4 Entscheidungen nach M8 (Owner 2026-11-27)

- **Händlerkäufe 8 → 16** und **Stammrunde 20 → 16**. Forschungsvorsprung 16 und Fächervielfalt 11 bleiben. Händlerkäufe 16 fällt nach der Nachrechnung ohne Zielspiel weiterhin vor Sol 90 (um Sol 80).
- **Bot-Nachrüstung Hangar** (#515). Befund aus den M8-Reports: Der Frachterkauf belegte alle drei Hallenplätze, direkte Forschung ging in Hangar-Runs ausschließlich in Geologie und Agronomie. Bei offenem Hangar-Ziel kauft der gezielte Bot jetzt die Korvette und bewertet die Kenntnisstufen, die Missionen freischalten. `aimless` unverändert.
- **Beobachtet, nicht geändert:** Bei vollen Hallenplätzen kauft der Bot wiederholt eine Drohne, die ohne Platz verfällt (`aimless`/`focus`, Hangar: nach dem dritten Frachter etwa alle 6 Sole). Das kostet Credits in allen Profilen; Behebung verschiebt den Vergleichswert und braucht eine eigene Entscheidung.

### 9.5 Nächste Messung — Hypothese (vor dem Lauf notiert)

Wie M8 (72 Runs, Messmodus bis Sol 100). Alle Erwartungen sind **Annahmen**; gezieltes Spiel nur in gezogenen Runs (kleine Fallzahlen).

| Größe | Erwartung | Widerlegt, wenn |
|---|---|---|
| Händlerkäufe 16, Cantina | gezielt Median Sol 60–75; `aimless` Median Sol 70–90 | außerhalb |
| Stammrunde 16, Cantina | gezielt mindestens die Hälfte bis Sol 80; `aimless` vor Sol 90 höchstens 2 von 8 | eines davon verfehlt |
| Siege `aimless`, Cantina | weniger als 7 von 8 | 7 oder mehr |
| Hangar `focus` (gezogen) | Korvette in mindestens der Hälfte der Runs; schwere Erfolge > 0; Einsatzbreite mindestens 7 | eines davon verfehlt |
| Expeditionen 10, Hangar | `focus` (gezogen) höchstens die Hälfte erreicht: eine Korvette fliegt etwa alle 6 Sole, Erfolgschance „schwer“ 60 % plus Boni | mehr als die Hälfte (dann zu optimistisch gerechnet) |
| Siege `aimless`, Labor und Hangar | wie M8 (Abweichung höchstens 1 je Eröffnung, Bot unverändert) | größere Abweichung |

