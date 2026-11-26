# Bewertung: Run-Ziele und Spätspiel nach M3/M4 (2026-11-24)

**Status:** Bewertung und Vorschläge, keine Entscheidung. GDD, Config und Code sind unverändert. Der Owner entscheidet.
**Bezug:** ROADMAP A45 (Sieg-Korridor), T35, T61 (AP-Ereignisse), R2 (Abnahme-Batch); GDD §9, §13, §14, §15, §18.4.
**Datenbasis:** Bot-Batches `storage/logs/playtest/20261124_m3/` und `.../20261124_m4/` (utility-Bot, Seeds 1–8 × Labor/Hangar/Cantina, volle Läufe bis Sol 100).

Kennzeichnung im ganzen Dokument:
- **gemessen**: aus den Batch-Logs oder dem Auftrag (Quelle genannt)
- **gerechnet**: eigene Rechnung aus Config-Werten, nicht gemessen
- **Annahme**: Deutung oder Vermutung, nicht belegt

---

## 0. Kurzfassung

1. **Ob ein Lauf gewonnen wird, entscheidet die Ziehung der Ziele, kaum das Spiel.** Vier Ziele sind in der jetzigen Regelwelt praktisch unerreichbar (Expertenstab, Forschungsvorsprung, Ingenieursleistung, Selbstversorgung). Zieht ein Lauf zwei davon, ist er ab Phase-2-Beginn verloren: 1 Sieg in 17 Läufen. Zieht er höchstens eines, gewinnen die Bots in 21 von 29 Läufen, also 72 % (gemessen, M4).
2. **Die frühen Siege kommen von drei Zielen, die der gewählte Pfad nebenbei erfüllt** (Handelspartner mit Cantina, Expeditionen mit Hangar, Kreditreserve). Der Bot, der ausdrücklich nicht auf Ziele spielt, erfüllt sie teils um Sol 27–39 (gemessen). Das verletzt die eigene Kalibrierregel (§15: ohne gezieltes Spiel frühestens um Sol ~90).
3. **Das Spätspiel hat zu wenige Entscheidungen**, weil alle AP-Ziele gedeckelt oder abgeschlossen sind und keine Gefahr mit der Laufzeit wächst. Überschüssige Ressourcen lassen sich nicht in Fortschritt bei den schweren Zielen umsetzen.
4. **Reihenfolge-Empfehlung:** erst die Ziel-Ausreißer und die Ziehung reparieren, dann T61. T61 vor der Ziel-Reparatur würde die ohnehin verlorenen Ziehungen nicht ändern und die gewinnbaren zusätzlich erschweren.

---

## 1. Fakten aus M4, neu ausgewertet

### 1.1 Die Ziehung hängt nur am Seed

Die Ziele werden aus `runs.rng_seed` gezogen (`RunProgressService::drawObjectives()`). Gleicher Seed heißt gleiche Ziele in allen drei Eröffnungen. Die 23 Läufe je Profil enthalten deshalb nur **8 verschiedene Ziehungen**, nicht 23. Die Erfüllungsquoten im Auftrag sind also stark geklumpt: Expertenstab steckt in 6 von 8 Ziehungen (17 bzw. 18 Läufe). Ob das ein Zufall der Seeds 1–8 ist oder eine Schieflage der Hash-Sortierung, lässt sich mit 8 Ziehungen nicht sagen (Annahme: Zufall; prüfbar ohne Bot, indem man die Ziehung für ein paar hundert Seeds auszählt).

### 1.2 Sieg je Ziehung (gemessen, M4, Sieg-Sol = Sol der zweiten Erfüllung)

| Seed | Gezogene Ziele | default L / H / C | focus L / H / C | Siege |
|---|---|---|---|---|
| 1 | Expertenstab, Forschungsvorsprung, Handelspartner | 87 / – / – | – / – / – | 1/6 |
| 2 | Selbstversorgung, Expeditionen, Expertenstab | – / (fehlt) / – | – / – / – | 0/5 |
| 3 | Handelspartner, Expertenstab, Kolonieblüte | 84 / 84 / – | 84 / 84 / 53 | 5/6 |
| 4 | Expeditionen, Expertenstab, Handelspartner | 62 / 75 / 66 | 62 / 89 / 64 | 6/6 |
| 5 | Ingenieursleistung, Forschungsvorsprung, Expertenstab | – / – / – | – / – / – | 0/6 |
| 6 | Selbstversorgung, Handelspartner, Expeditionen | 60 / – / 53 | – / (fehlt) / – | 2/5 |
| 7 | Kreditreserve, Kolonieblüte, Expertenstab | 96 / – / 69 | – / – / 58 | 3/6 |
| 8 | Kreditreserve, Expeditionen, Ingenieursleistung | 87 / – / 90 | 82 / 62 / 56 | 5/6 |

Gruppiert (gemessen): Ziehungen mit **zwei oder mehr** der vier schweren Ziele (Seeds 1, 2, 5): **1 Sieg in 17 Läufen**. Ziehungen mit **höchstens einem** (Seeds 3, 4, 6, 7, 8): **21 Siege in 29 Läufen (72 %)**.

Folgerung (Annahme): Die gemessene Siegquote von ~50 % ist kein Abbild „mittelmäßig gespielt", sondern ein Mittelwert aus „chancenlos" und „fast sicher". Sie trifft die Owner-Vorgabe nur zufällig.

### 1.3 Wann die leichten Ziele fallen (gemessen, M4)

| Ziel | Pfad, der es füttert | default (nicht gezielt) | focus |
|---|---|---|---|
| Handelspartner | Cantina | Sol 30, 30, 34, 39 | Sol 30, 30, 34, 39 |
| Expeditionen | Hangar | Sol 27, 27 (Seeds 4, 6) | Sol 48–75 |
| Kreditreserve | (alle, Cantina am schnellsten) | Sol 60–70, einmal 96/97 | Cantina 39–40, sonst 61–62 |

Handelspartner und Expeditionen fallen beim nicht gezielt spielenden Bot **vor dem ersten Nexus-Kontrollpunkt**. Sie zählen ab Run-Start, also auch Käufe und Missionen aus Phase 1, bevor der Spieler das Ziel überhaupt kennt (`updateTradeVolume`, `updateExpeditionCoverage`).

Auffällig (gemessen): Das Ziel-Profil ist bei einzelnen Zielen *langsamer* als das Standard-Profil (Expeditionen im Hangar 48–75 statt 27; Forschungsvorsprung 0/7 statt 2/7 Kenntnisse auf Stufe 5). Annahme: Das Ziel-Profil verteilt AP auf alle offenen Ziele gleichzeitig und bringt keines konzentriert durch. Das ist eine **offene Bot-Variable**: „gezielt gespielt" ist für diese Ziele derzeit nicht verlässlich gemessen.

---

## 2. Bewertung der Ziele

### 2.1 Gemeinsame Ursache der Ausreißer

Die drei Ziel-Werte, die heute unerreichbar sind, wurden **vor** den großen Regeländerungen kalibriert (Daten aus den Config-Kommentaren und der ROADMAP):

| Ziel | Kalibriert | Danach eingeführt |
|---|---|---|
| Expertenstab | 2026-09-26 (beide Profile Sol 73–79) | Stufen-Gate (10-15), AP-Deckel je Gebäude (10-16), AP-Skala (11-13) → Kommandozentrale Stufe 4 und damit Berater-Platz 4 kommt später |
| Ingenieursleistung | 2026-09-30 (gezielt 30 bei Sol 48) | Stufen-Gate (Gebäudestufe ≤ Stufe der Kommandozentrale), AP-Deckel je Gebäude |
| Forschungsvorsprung | 2026-10-01 (gezielt Sol ~68) | Labor-Deckel je Kenntnis und Sol (10-25), AP-Skala (11-13) |

Das ist eine Prozess-Ursache, keine reine Zahl: Die Ziele messen Endzustände von Systemen, die seitdem bewusst gebremst wurden. Die Bremsen wirken, die Ziele wurden nicht mitgezogen.

### 2.2 Ziel für Ziel

**Expertenstab (4 Berater mit Rang 3) — Ausreißer, Design-Ursache: reiner Zeitmesser.**
Rang 3 kommt nach einer festen Zahl Dienstsole (Config `advisor.rank_thresholds`), der vierte Platz erst mit Kommandozentrale Stufe 4. Gerechnet: frühester Abschluss = Einstellung des vierten Beraters + 45 Dienstsole. Bei Stufe 4 um Sol 40 (Auftrag, gerechnet) liegt das bei Sol ~85, ohne jeden Puffer; jeder Austausch, jede verspätete Einstellung und möglicherweise die Nexus-Sanktion (Berater kurz abgezogen; Annahme, dass dabei keine Dienstsole zählen) schiebt es hinter Sol 100. Der Spieler hat dabei fast nichts zu entscheiden: früh einstellen, nicht austauschen, Beförderung bezahlen, warten. Gemessen 2 Erfüllungen in 35 Ziehungen, beide in Labor-Läufen bei Sol 87/93. Dazu ist es das am häufigsten gezogene Ziel dieser Stichprobe.

**Forschungsvorsprung (7 Kenntnisse auf Stufe 5) — Ausreißer, Design-Ursache: verlangt den kompletten Baum.**
Es gibt genau 7 Kenntnisse, das Ziel verlangt also *alle* auf Höchststufe. Die GDD-Prosa sagt „einige Kenntnisse". Gerechnet: 72 AP je Kenntnis bis Stufe 5, ~500 AP für alle; Stufe 5 erst ab Kommandozentrale Stufe 5 (Bots: Sol 41–60, Auftrag). Gerechnet bei 8–13 AP je Sol im Spätspiel: Das Ziel bindet über die ganze Phase 2 den größten Teil des Pools, während derselbe Pool die Kommandozentrale auf Stufe 5 bringen muss, von der das Ziel abhängt. Damit ist die im GDD gewollte Entscheidung „Breite gegen Tiefe" (§10) ausgehebelt: Das Ziel verlangt beides vollständig.

**Ingenieursleistung (Summe aller Ausbaustufen) — gutes Design, falsche Höhe.**
Die Messart ist gut (Breite und Tiefe zählen gleich, stabil, verständlich). Gemessen 24–33 von 35 am Ende, also nah dran. Ursache (Annahme): Das Stufen-Gate macht die Summe von der Kommandozentrale abhängig, der AP-Deckel je Gebäude streckt jede Stufe. Hier genügt Neukalibrieren; kein Umbau nötig.

**Selbstversorgung (Regolith- und Organika-Vorrat über Schwelle, Serie) — Ausreißer, Design-Ursache: belohnt Nichtstun.**
Das Ziel verlangt Horten, also das Gegenteil jeder anderen Tätigkeit. Die Messung zeigt das direkt: Seit der Bot Regolith für zusätzliche Wohnhabitate ausgibt (#497), fällt das Ziel von 3/6 (M3) auf 0/5 (M4). Ein Ziel, das durch sinnvolles Bauen schlechter wird, erzeugt keine Entscheidung, sondern eine Pause. Es kollidiert außerdem mit Ingenieursleistung in derselben Ziehung.

**Handelspartner, Expeditionen — gut als Spielhandlung, aber pfadgekoppelt zu stark.**
Beide sind aktive Ziele mit echten Handlungen. Das Problem ist die Kopplung: Mit dem passenden ersten Pfad sind sie ein Nebenprodukt der Eröffnung und fallen um Sol 30. Ohne den Pfad (Handelspartner ohne Cantina: M3 0/8, Auftrag) sind sie schwer. Die GDD will Varianz über „passt die Aufgabe zum Pfad" (§15), aber die Spanne „Sol 30 oder nie" ist zu groß.

**Kreditreserve — im Korridor bis auf die Cantina.**
Sol 60–70 ohne gezieltes Spiel ist etwas zu früh, mit Cantina gezielt Sol 39–40 deutlich zu früh. Ursache (Annahme): verdoppelte Credit-Belohnungen (2026-09-30) plus ungenutzte Credits im Spätspiel (M3: 1.000–6.000 am Ende, Auftrag).

**Kolonieblüte — im Korridor.** Sol 53–84, verständlich, mit Vertrauen gekoppelt. Kein Handlungsbedarf, aber T61 wird es beeinflussen (siehe §3).

### 2.3 Optionen für die Ziele

Sortiert nach Wirkung je Aufwand.

**Z-A. Ziehungs-Leitplanke: höchstens ein „Langläufer" je Ziehung.** Neue Kategorie-Eigenschaft (z. B. `pace: 'long'`) für Ziele, die nur über lange Zeit oder Horten fallen; die vorhandene Kombo-Regel (höchstens ein Wirtschaftsziel) bekommt eine zweite Regel.
- Vorteil: kleinster Eingriff, nutzt bestehende Mechanik; beseitigt die chancenlosen Ziehungen sofort (gemessen: genau diese Gruppe gewinnt 1/17).
- Nachteil: heilt die Ausreißer nicht, versteckt sie nur; mit einem Langläufer je Ziehung wird faktisch fast immer „die zwei anderen" gespielt, das „2 von 3"-Moment verengt sich.
- Erwartete Wirkung (Annahme, aus §1.2 hochgerechnet): Siegquote beider Profile von ~50 % auf ~70 %. **Braucht neue Messung.**

**Z-B. Die drei strukturellen Ausreißer umbauen (je ein Hebel, einzeln messbar).**
- Forschungsvorsprung → **Summe aller Kenntnisstufen** über einer Schwelle (analog Ingenieursleistung). Breite und Tiefe zählen gleich, die Entscheidung aus §10 lebt wieder, die Abhängigkeit von Stufe 5 (und damit von Kommandozentrale Stufe 5) fällt als harte Bedingung weg.
- Expertenstab → **Summe der Berater-Ränge** über einer Schwelle (oder „3 Berater mit Rang 3"). Entkoppelt das Ziel vom vierten Platz und erlaubt eine Entscheidung (welche Berater halte ich lange, wen befördere ich zuerst).
- Selbstversorgung → **Überschuss statt Vorrat**: eine Serie von Solen, in denen die Kolonie mehr Regolith und Organika erzeugt als verbraucht. Das belohnt Produktionsausbau statt Horten und verträgt sich mit Bauen.
- Vorteil: heilt die Ursachen, jedes Ziel wird wieder eine Spielweise statt eine Wartezeit; GDD-Prosa bleibt zahlenfrei.
- Nachteil: drei Code-Änderungen in `RunProgressService` plus Texte; drei getrennte Messungen, wenn man sauber je Hebel misst.
- Erwartete Wirkung: je Ziel gezielt Erfüllung im Korridor Sol 70–85. **Braucht Messung je Ziel** (und vorher eine Bot-Bewertung, die das neue Maß kennt).

**Z-C. Zähler-Ziele zählen erst ab der Ziehung (Phase-2-Beginn).** Handelspartner, Expeditionen (und gleichartig neu gebaute Summen-Ziele) zählen nur Fortschritt nach dem Phasenwechsel.
- Vorteil: nimmt Phase-1-Vorlauf heraus, ein Ziel wird erst dann erarbeitet, wenn der Spieler es kennt; macht den Fortschrittsbalken ehrlich.
- Nachteil: wirkt bei Cantina-Handel nur um die wenigen Phase-1-Käufe; reicht allein vermutlich nicht für Sol 30 → 70 (Annahme). Bei Summen-Zielen (Ingenieursleistung) wäre „ab Ziehung" verwirrend; dort nicht anwenden.
- **Braucht Messung.**

**Z-D. Pfad-bewusste Ziehung** (Ziehung kennt beim Phasenwechsel den ersten Pfad): z. B. „ein Ziel, das der Pfad füttert, eines neutral, eines fremd", oder das pfadgefütterte Ziel bekommt einen höheren Zielwert (Beschriftung zeigt die echte Zahl, Transparenz bleibt).
- Vorteil: steuert die Varianz bewusst, „2 von 3" wird wieder eine echte Abwägung.
- Nachteil: mehr Regelwerk, schwerer zu erklären; Gefahr, dass Läufe sich gleich anfühlen.
- **Braucht Messung.**

**Empfehlung:** Z-A sofort (klein, beseitigt die Ungerechtigkeit), danach Z-B in der Reihenfolge Forschungsvorsprung → Selbstversorgung → Expertenstab, Ingenieursleistung nur neu kalibrieren. Z-C als kleiner Begleiter zu Handelspartner und Expeditionen. Z-D erst, wenn Z-A bis Z-C gemessen sind. Wichtig: Z-A und Z-B heben die Bot-Siegquote voraussichtlich deutlich über 50 % (Annahme, §1.2: 72 % in den fairen Ziehungen). Das ist richtig so; die Quote wird danach über T61 wieder gesenkt, nicht über unfaire Ziehungen.

---

## 3. Spätspiel ab ~Sol 60

### 3.1 Was dem Spieler fehlt

Gemessen (M3, Auftrag): ungenutzte AP Sol 71–100 im Median 54 % (vor #497) bzw. ~20 % (M4), im Mittelspiel Sol 46–70 dagegen ~0–1,5 %. Ressourcen liegen am Ende ungenutzt (M3: 600–2.400 Regolith, oft 1.000–6.000 Credits; M4: Regolith-Median ~420). Der Bot repariert und verlegt den Harvester, wenn er nichts findet.

Design-Ursachen (Annahme, aus Config und Messung abgeleitet):
1. **Sättigung statt Knappheit.** Fast jedes AP-Ziel hat einen Deckel je Sol (Gebäude, Kenntnis, Training, Figur) und eine Höchststufe. Um Sol 60 sind Kommandozentrale Stufe 5, alle Berater-Plätze und die meisten Gebäude erreicht. Die Deckel sollten im Frühspiel bremsen; im Spätspiel bremsen sie nicht mehr, sie lassen den Pool leer laufen.
2. **Keine Umwandlung von Überschuss in Fortschritt.** Regolith und Credits stauen sich, aber die schweren Ziele lassen sich damit nicht voranbringen (Expertenstab ist reine Zeit, Forschungsvorsprung reine AP).
3. **Keine Gefahr, die mit dem Lauf wächst.** Gerechnet aus Config: Sturm-Chance je Sol liegt so niedrig, dass in Phase 2 im Schnitt nur ein bis zwei Stürme pro Lauf fallen; Instabilität trifft nur den Harvester (und die Verlegung setzt sie zurück, was der Bot im Spätspiel ohnehin tut); Krankheit tritt nur bei Vernachlässigung auf. Eine gut geführte Kolonie erlebt im Spätspiel praktisch keine Gefahr. Das widerspricht dem Kern „kleine Kolonie am Leben halten".
4. **Kein Abschlussbogen.** Nach Sol 80 gibt es Countdown-Meldungen, aber keine neue Art Entscheidung. Highscore belohnt Tempo, nicht „gut zu Ende geführt".

Bot-Abhängigkeit (offene Variable): Ein Teil der ungenutzten AP ist Bot-Verhalten (M3 → M4 halbiert sie durch einen reinen Bot-Fix). Ob Menschen im Spätspiel ebenso Leerlauf haben, ist nicht gemessen. Das „Harvester wiederholt verlegen" spricht aber dafür, dass es schlicht keine sinnvolle Handlung gibt, auch für Menschen.

### 3.2 T61 als Design-Skizze (zahlenfrei; Zahlen nur als Config-Vorschlag)

Leitbild: **Vorzeichen → Handlungsfenster → Ausgang.** Jede neue Gefahr kündigt sich an, gibt dem Spieler ein kurzes Fenster für eine AP-Maßnahme und rechnet dann den Ausgang nach dem Zustand am Ende des Fensters (bestehendes Muster aus §9 „Vorwarnung & Protokollierung"). Das setzt das Designprinzip „Vorsorge statt Verbot" (§1.1) fort: Vorsorge ist billiger als Schadensbehebung, wer nichts tut, überlebt, zahlt aber mehr.

**Auslöser und Steigerung**
- Gefahren steigen mit der **Zeit in Phase 2** („Saison"), nicht mit der Gebäudezahl (die Schadenssumme skaliert ja schon mit der Kolonie, §9). Sie setzen im Mittelspiel ein, damit sie auch die heute zu frühen Siege um Sol 50–65 erreichen, und erreichen im Spätspiel ihr Maximum.
- Ein Deckel für gleichzeitige Fenster (höchstens eines offen) und der bestehende Cooldown verhindern Spiralen.
- Keine neue Niederlage: Die Gefahren kosten AP, Regolith, Vertrauen und Zeit, aber führen nicht direkt zum Fail State.

**Drei Gefahren, drei Verben**

| Gefahr | Vorzeichen | AP-Maßnahme im Fenster | Ausgang ohne Maßnahme |
|---|---|---|---|
| **Sturmfront** (Ausbau des bestehenden Sturms) | Vorhersage mehrere Sole vorher, erst grob („Front im Anmarsch"), dann genau | **Sichern**: AP je Gebäude, das gesicherte Gebäude übersteht den Sturm eine Ausgangsstufe besser. Der Spieler wählt, *welche* Gebäude er sichert | wie heute: Schaden nach Zustand, Reparatur danach teurer als Sichern vorher |
| **Verletzte** (Folge von kritischen Sturmtreffern und Instabilität) | Meldung „Verletzte nach dem Sturm" | **Behandeln** in der Krankenstation: AP je Verletztem | Verletzte fehlen als Arbeitskraft (bestehende Unterbesetzung, A14), heilen langsam von selbst, Vertrauen sinkt leicht je Sol ohne Behandlung |
| **Krankheitswelle** (Umbau des heutigen Seuchenausbruchs, neuer Name) | „Erste Fälle" — auch in gesunden, dicht besiedelten Kolonien möglich, nicht nur bei Vernachlässigung | **Eindämmen**: kleines AP-Projekt mit Schwelle (wie eine Kenntnis-Stufe), innerhalb des Fensters | Ausbruch wie heute: AP-Zufluss für einige Sole gesenkt, Vertrauensmalus |

**Telegrafierung und Rollen**
- Die Vorhersage liegt im Dashboard der Kommandozentrale (§13.4) und im Sol-Bericht, mit klarer Restzeit.
- Bestehende Stellschrauben bekommen Spätspiel-Sinn: Kenntnis Verteidigung und die Leitstelle mildern Stürme, Gesundheit und Krankenstation Krankheiten, Kartografie bzw. der Raumfahrer verlängern die Vorwarnung. So hat auch eine im Frühspiel „fertige" Kenntnis später noch Wert, und Ressourcenüberschuss kann in Vorsorge fließen (z. B. Sichern mit Regolith statt AP als Alternative, Annahme, offen).

**Wechselwirkung mit Vertrauen und Reparatur**
- Gut bewältigt (gesichert, behandelt, eingedämmt) löst den bestehenden positiven Ausgang aus; ignoriert den negativen. Damit wird Kolonieblüte im Spätspiel zu einem aktiven Ziel statt einem Selbstläufer. Achtung: Das verschiebt die heute stimmige Kalibrierung von Kolonieblüte, nach T61 neu messen.
- Reparatur bleibt das Grundverb; Sichern ist die Vorsorge-Variante. Reparatur und Sichern sollten nicht unter denselben AP-Deckel fallen wie Bauprojekte (Reparatur ist heute ausgenommen), sonst entsteht wieder Sättigung (Annahme).

**Namensfrage:** Der Owner mag „Seuche" nicht. Vorschlag „Krankheitswelle" für das Ereignis, „Erkrankung" im Fließtext; Umbenennung des bestehenden Seuchenausbruchs in GDD, Lang-Dateien und Glossar gleich mit T61.

**Config-Vorschlag (ungemessen, nur Startwerte für die erste Messung)**
- `encounter.season.start_phase2_sol` ~ 20, `encounter.season.full_phase2_sol` ~ 60
- `encounter.storm.warning_sols` 2–3 (heute 1)
- `encounter.secure.ap_per_building` 1, `encounter.injury.treat_ap` 1, `encounter.illness.containment_ap` 3–4, `encounter.illness.window_sols` 2
- Zielgröße der ersten Messung (gerechnet, eine Hypothese): Die Gefahren sollen im Spätspiel im Mittel etwa den heute ungenutzten Anteil binden, also rund 20 % des Zuflusses (bei 8–13 AP je Sol etwa 2–3 AP je Sol). Messbar mit der vorhandenen Leerlauf-Auswertung Sol 71–100.

### 3.3 Optionen für das Spätspiel

**S-A. T61 wie oben (Gefahren mit Vorzeichen und AP-Maßnahme).**
- Vorteil: trifft den Owner-Wunsch, stärkt den Kern (Kolonie am Leben halten), gibt spät fertigen Kenntnissen und Gebäuden Sinn, wirkt auf Mittel- und Spätspiel.
- Nachteil: groß (neue Aktionen, UI, Sol-Bericht, Bot-Regeln); macht die schweren Ziele noch schwerer, solange sie nicht repariert sind.
- **Braucht Messung bis Sol 100** und neue Bot-Regeln (Sichern/Behandeln/Eindämmen), sonst misst man einen Bot, der die Maßnahmen nicht kennt.

**S-B. Überschuss umwandelbar machen.** Zwei Kandidaten: (1) „Nexus-Vorschuss tilgen" (ROADMAP R21) als Credit-Senke mit Spielwert (Sicherheit gegen die Schuldengrenze, Highscore); (2) wenige Spätprojekte ab Kommandozentrale Stufe 5, die Regolith/Credits in Fortschritt eines Ziels verwandeln (z. B. Fortbildung für Berater, Forschungskooperation mit zweckgebundenen Kenntnis-AP wie bei der stillen Forschung).
- Vorteil: nutzt die gemessen ungenutzten Ressourcen, erzeugt echte Abwägung (Rücklage, Schulden, Tempo).
- Nachteil: (2) beschleunigt Ziele, das widerspricht der Owner-Entscheidung 2026-09-27 „keine Beschleunigungsmechanik für Expertenstab" — neu zu fragen; Gefahr neuer Selbstläufer.
- **Braucht Messung.**

**S-C. Deckel im Spätspiel lockern** (z. B. höhere Fach-Deckel ab Kommandozentrale Stufe 5).
- Vorteil: klein, reine Config.
- Nachteil: reines Zahlen-Tuning, beschleunigt die Siege, statt Entscheidungen zu schaffen. **Nicht empfohlen.**

**Empfehlung:** S-A als Hauptweg, aber nach Z-A/Z-B. S-B(1) „Tilgen" als kleiner Begleiter, weil es die Credits sinnvoll bindet und einen offenen GDD-Punkt schließt. S-C nicht.

---

## 4. Frühe Siege des Ziel-Profils (focus: 6 von 10 Siegen vor Sol 70)

Gemessen (M4): Die sechs frühen focus-Siege (Sol 53, 56, 58, 62, 62, 64) entfallen auf Cantina (4), Hangar (1) und Labor (1). Jeder davon enthält mindestens eines der Ziele Handelspartner, Expeditionen oder Kreditreserve, die der Pfad füttert; vier davon ein Paar aus zwei solchen Zielen.

**Bewertung: Problem, aber nicht von gutem Spiel, sondern von Ziel-Pfad-Kopplung.**
- Der Korridor sagt: Sol 70–85 nur für einen sehr guten Run *mit Glück*. Sol 53–64 liegt darunter, und das bei einem Bot, nicht bei einem geübten Menschen. Annahme: Menschen, die die Kopplung erkennen, landen noch früher.
- Dieselben Ziele erfüllt auch das Standard-Profil, das ausdrücklich nicht auf Ziele spielt, um Sol 27–39 (§1.3). Das ist kein „sehr gut gespielt", sondern ein Selbstläufer.
- Die Nexus-Leiter greift bei frühen Siegen nicht (sie bestraft nur Rückstand). Zudem liegt Phase-2-Beginn gemessen bei Sol 10–12, nicht bei Sol 15 wie in der §18.4-Tabelle angenommen; die Kontrollpunkte fallen also etwas früher als dort beschrieben.

**Optionen**
- **F-A. Akzeptieren** als „Glück der Ziehung". Vorteil: kein Aufwand. Nachteil: Korridor gilt für einen großen Teil der Läufe nicht; der Highscore belohnt dann vor allem eine glückliche Ziehung.
- **F-B. Z-C + Z-D** (Zählung ab Ziehung, pfad-bewusste Ziehung oder höherer Zielwert für das pfadgefütterte Ziel). Vorteil: trifft die Ursache. Nachteil: mehr Regelwerk. **Braucht Messung.**
- **F-C. T61 ab Mitte Phase 2** zieht AP aus genau dem Fenster Sol 45–65. Vorteil: schiebt frühe Siege ohne Ziel-Eingriff. Nachteil: allein reicht es nicht, ein Handelspartner-Ziel bei Sol 30 bleibt bei Sol 30 (Annahme). **Braucht Messung.**

**Empfehlung:** F-B zusammen mit F-C. Nicht über allgemeine Kostensteigerungen lösen.

---

## 5. Messplan (je Schritt eine Änderung)

| Schritt | Änderung | Hypothese (eine Zahl) | Messung |
|---|---|---|---|
| 0 | keine (Werkzeug) | Ziehungsverteilung über viele Seeds ist gleichmäßig | Ziehung ohne Bot auszählen, kein Batch nötig |
| 1 | Z-A Leitplanke | Siegquote default steigt auf ~70 % (Annahme aus §1.2) | Batch wie M4, Owner-Freigabe |
| 2 | Z-B Forschungsvorsprung als Summe | focus erfüllt es gezielt im Korridor Sol 70–85 | Batch, vorher Bot-Bewertung anpassen |
| 3 | Z-B Selbstversorgung als Überschuss | erfüllt gezielt Sol 70–85, nicht gezielt ≥ Sol 90 | Batch |
| 4 | Z-B Expertenstab als Rangsumme | erfüllt gezielt Sol 70–85 | Batch |
| 5 | Z-C Zählung ab Ziehung | Handelspartner (Cantina) und Expeditionen (Hangar) nicht gezielt nicht vor Sol ~60 | Batch |
| 6 | T61 Stufe 1 (nur Sturmfront + Sichern) | ungenutzte AP Sol 71–100 sinkt von ~20 % auf unter 5 %, Sieg-Median rückt in Sol 85–95 | Batch bis Sol 100 |

Offene Bot-Variablen für alle Schritte: Konzentration des Ziel-Profils auf wenige Ziele (heute verteilt es), Bewertung neuer Maßnahmen (Sichern, Behandeln, Eindämmen), Erkundungsanteil.

---

## 6. Owner-Fragen (mit Empfehlung)

1. **Soll die Ziehung höchstens ein Langläufer-Ziel enthalten?** Empfehlung: Ja, sofort, als kleiner Schritt vor allem anderen.
2. **Forschungsvorsprung als Summe der Kenntnisstufen statt „alle 7 auf Stufe 5"?** Empfehlung: Ja; die Breite-gegen-Tiefe-Entscheidung wird damit wieder Teil des Ziels.
3. **Expertenstab vom vierten Berater-Platz lösen (Summe der Ränge oder 3 Berater auf Rang 3)?** Ihre Entscheidung vom 2026-09-27 (keine Beschleunigung) beruhte auf Messungen vor Stufen-Gate und AP-Skala. Empfehlung: Rangsumme, ohne Beschleunigungsmechanik.
4. **Selbstversorgung von „Vorrat halten" auf „mehr erzeugen als verbrauchen" umbauen?** Empfehlung: Ja; heute wird das Ziel durch sinnvolles Bauen schlechter.
5. **Sollen Zähler-Ziele (Handelspartner, Expeditionen) erst ab der Ziehung zählen?** Empfehlung: Ja, plus Messung; falls das nicht reicht, höherer Zielwert für das Ziel, das der erste Pfad füttert.
6. **T61 nach der Ziel-Reparatur, in der ersten Stufe nur Sturmfront mit „Sichern", und „Krankheitswelle" statt Seuchenausbruch?** Empfehlung: Ja zu allen drei; Verletzte und Krankheitswelle als zweite Stufe nach der ersten Messung.
