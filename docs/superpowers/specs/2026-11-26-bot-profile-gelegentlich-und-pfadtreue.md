# Bot-Profile: „gelegentlich“ und Regler „Pfad-Treue“ (2026-11-26)

Owner-Freigabe 2026-11-26. Grundlage: Messung M4 (AP je Run ab Sol 15), GDD §4b, §15, GLOSSAR. Testhilfe, keine Spielwerte: alles lebt in `tests/Feature/Playtest/` (`BotProfile.php`, `BotStrategy.php`, `Utility/weights.php`, `Utility/Valuers.php`).

## Befund aus dem Code (gelesen, nicht gemessen)
- In der Standard-Engine `legacy` ist der Ziel-Fokus **ein Schalter**: `objectiveFocus > 0` fügt die Fokus-Regeln ein, die Höhe spielt keine Rolle. 0,3 würde dort wie 1,0 spielen. Nur die Engine `utility` skaliert stufenlos (`w_objective`).
- Missions-Training (`train_mission_family`) steht in der Regelliste **vor** den Gesprächen (`converse_character`). Beide erhalten nur übrige AP. Das Training schluckt die Reste, deshalb hat der Cantina-Bot kaum AP für Gespräche. Das deckt sich mit M4.
- Die Forschung (`research_knowledge`) steht früh in der Liste und stößt an die AP-Deckel. Labor-Bots können deshalb nicht mehr forschen als andere, nur weil man ihre Forschung vorzieht. Sie forschen erst mehr, wenn das Labor höher ausgebaut ist.

## 1. Profil „gelegentlich“
- Werte: Ziel-Fokus **0,3**, Sparen **0,0**, Pfad-Treue **0,7** (siehe 2).
- Begründung: Ein echter Spieler schaut ab und zu auf seine Ziele. Er ignoriert sie nicht, jagt ihnen aber auch nicht nach. Ohne Sparbremse bleibt das Profil der „lockere“ Gegenpol zu `focus`. Nach GDD §15 soll es um Sol 90 oder später siegen und dabei etwa 50 % der Läufe gewinnen.
- **Abgestufter Fokus in `legacy` (neu):** Die Fokus-Regeln gelten nur an einem Teil der Sols, und zwar gleichmäßig verteilt und ohne Zufall. Ein Sol ist aktiv, wenn `floor(sol·f) > floor((sol−1)·f)` gilt. Bei f = 0,3 sind das 3 von 10 Sols, bei f = 1 jeder Sol. Das Verhalten von `focus` und `eager` ändert sich dadurch nicht. Die Hilfsfunktion `dialActiveOnSol(f, sol)` wird auch von der Pfad-Treue genutzt.
- **Name:** `default` bleibt der Code-Name und bekommt die neue Bedeutung. Für die Umbenennung spricht wenig: 114 Verweise in 41 Testdateien, die CLI-Vorgabe `--profile=default` und die Vergleichswerkzeuge würden sich ändern. Gegen das Behalten spricht, dass Batches vor M5 mit `default` nicht mehr vergleichbar sind. Gegenmittel: Den Bedeutungswechsel im Batch-Bericht und im CHANGELOG mit Datum vermerken.
- **Neues Profil `aimless`** (0/0/0) mit dem alten Verhalten: Mechanik- und Golden-Tests, die „keine Fokus-Regeln“ voraussetzen (u. a. `AdapterGoldenTest`, `ResearchSourceTest`, `BotProfileTest`), wechseln gezielt auf `aimless`. Es wird nur umbenannt, was rot wird.

## 2. Regler „Pfad-Treue“ (`pathLoyalty`, 0–1)
Wirkung: Der Bot vertieft das Gebäude seiner Eröffnung über den **ganzen Run** bevorzugt, nicht nur bis Sol 15. Fremde Pfade bekommen weiter AP, aber erst danach.

| Eröffnung | eigene Vertiefung (bevorzugt) | gedrosselt (nachrangig) |
|---|---|---|
| Labor | Ausbaustufen des Labors (höhere Forschungsdeckel), danach Forschung | Training, Gespräche |
| Hangar | Missions-Training im Simulator | Gespräche |
| Cantina | Gespräche (Beziehungen), Cantina-Ausbaustufen | Training |

- **legacy:** An aktiven Sols (`dialActiveOnSol(p, sol)`) rückt die eigene Vertiefungsregel in der Resteliste vor die fremden Vertiefungsregeln und vor Tiefenscan und Fundbergung. Beim Labor zieht die Ausbau-Auswahl (`invest_production`) das Labor vor die anderen Pfadgebäude. Die Reihenfolge allein sorgt dafür, dass nichts liegen bleibt: Hat die eigene Vertiefung keinen Kandidaten mehr (Deckel erreicht, keine Figur anwesend), fließen die übrigen AP wie bisher in fremde Pfade.
- **utility:** Aktionen der eigenen Vertiefung erhalten einen Aufschlag auf den Wert, `Wert·(1 + p·loyalty_boost)`, mit **Annahme** `loyalty_boost = 1,0`. Bei p = 1 zählt die eigene Vertiefung also doppelt. `w_tier` für das eigene Pfadgebäude steigt ebenso um `(1 + p)`.
- **Unberührt:** Instandhaltung und Reparatur, Kommandozentrale, Ernte und Versorgung, Phase-1-Aufbau, Anstellen von Beratern, Fokus-Regeln, Missionen aussenden, Cantina-Begegnungen und Handel. Ein stures Spiel auf nur einem Pfad gibt es nicht.
- Bei `opening = auto` hat der Regler keine Wirkung (kein Eröffnungsgebäude, Bestandsverhalten).
- Profilwerte (**Annahme**): `default` 0,7 · `thrifty` 0,7 · `lean-explore` 0,7 · `focus` 0,5 · `eager` 0,5 · `aimless` 0. Die Ziel-Profile erhalten weniger, weil ihre Fokus-Regeln ohnehin vorgehen.

## 3. Erwartung für Messung M5 (alles **Annahme (gerechnet)**)
Messung in zwei Schritten, ein Hebel je Schritt: **M5a** nur „gelegentlich“ (Pfad-Treue überall 0), **M5b** zusätzlich Pfad-Treue. Jeweils 24 Läufe je Profil, alle drei Eröffnungen.
- M5a: `default` siegt in 35–55 % der Läufe, Median Sol 85–95. `focus` bleibt innerhalb von ±5 Prozentpunkten bei seinem bisherigen Wert. Die AP-Verteilung ändert sich kaum (±10 %).
- M5b (`default`, AP ab Sol 15): Cantina-Gespräche steigen von 24 auf ≥ 60 und das Training fällt von 80 auf ≤ 50. Hangar bleibt beim Training ≥ 140. Beim Labor steigt die Forschung um ≥ 15 % (von 119 auf ≥ 137), Labor-Stufe 3 kommt ≥ 3 Sol früher, das Training fällt um ≥ 20 %. Die Siegquote je Eröffnung liegt höchstens 15 Prozentpunkte auseinander.
- Offene Variablen, die vom Verhalten des Bots abhängen: wie oft Figuren in der Cantina anwesend sind (das begrenzt die Gespräche) und die Deckel der Forschung und des Trainings. Bleibt Labor in M5b unter +15 %, liegt der Engpass bei den Deckeln und nicht bei der Priorität.

## 4. Akzeptanzkriterien (TDD)
1. `BotProfile::named('default')` liefert Ziel-Fokus 0,3, Sparen 0,0 und Pfad-Treue 0,7.
2. `BotProfile::named('aimless')` liefert 0/0/0 und dieselbe Regelliste wie das alte `default`.
3. `dialActiveOnSol(0.3, sol)` ist für Sol 1–10 an genau 3 Sols wahr, `dialActiveOnSol(1.0, sol)` an jedem, `dialActiveOnSol(0.0, sol)` an keinem.
4. In `legacy` greifen die Fokus-Regeln bei `objectiveFocus` 0,3 nur an aktiven Sols, bei 1,0 an jedem Sol.
5. Ein Cantina-Bot mit Pfad-Treue > 0 wählt an einem aktiven Sol ein Gespräch vor dem Training, wenn beide Kandidaten offen sind.
6. Hat der Cantina-Bot an diesem Sol keinen Gesprächskandidaten, geht der AP-Rest weiterhin ins Training (keine ungenutzten AP durch die Treue).
7. Ein Labor-Bot mit Pfad-Treue > 0 baut das Labor vor anderen Pfadgebäuden aus, wenn beide Stufen bezahlbar sind.
8. Pfad-Treue ändert weder die Reihenfolge noch die Auswahl von Reparatur, Kommandozentrale, Versorgung und Fokus-Regeln.
9. Bei `opening = auto` ist die Regelliste mit und ohne Pfad-Treue gleich.
10. In `utility` liegt der Wert einer eigenen Vertiefungsaktion bei p = 1 genau um den Faktor `1 + loyalty_boost` über dem bei p = 0. Fremde Aktionen bleiben unverändert.
11. `withOpening()` und `withEngine()` übernehmen `pathLoyalty`.

## 5. Owner-Fragen
1. Soll `default` als Name bleiben, mit neuer Bedeutung und `aimless` für das alte Verhalten? **Empfehlung: ja.**
2. Soll Labor-Treue über den Ausbau des Labors wirken und nicht über Forschung jenseits der Deckel? **Empfehlung: ja.** Die Deckel sind Spielregel und bleiben unangetastet.
3. Soll M5 in zwei Schritten laufen (M5a, dann M5b, je 24 Läufe pro Profil, Dauer vorher schätzen)? **Empfehlung: ja**, ein Hebel pro Messung.
