# Entwurf: Kenntnisse je Gebäude und Siegbedingung (2026-11-26)

**Status:** Konzeptentwurf zur Owner-Entscheidung. Nichts davon ist umgesetzt; GDD, Config und Code bleiben unverändert.
**Auftrag:** Owner-Idee vom 2026-11-26: „Jede Kenntnis braucht genau ein Gebäude als Voraussetzung, jede ein anderes; Labor, Hangar und Cantina tragen die Grundkenntnisse, die übrigen Gebäude die fortgeschrittenen." Owner-Korrekturen vom selben Tag: **Die sieben Kenntnisse bleiben, wie sie sind** (keine neuen, keine umgebauten); wo ein passendes Gebäude fehlt, darf ein Gebäude skizziert werden. Nachtrag: „Damit haben wir für die Kenntnisse gleich die passenden Boni für die jeweiligen Gebäude."
**Bezug:** GDD §4b, §10, §11, §13, §15, §18; Specs `2026-11-11-tomas-rat-rueckmeldung.md` (stille Forschung), `2026-11-06-labor-vorschau.md`, `2026-11-24-ziele-und-spaetspiel-bewertung.md`, `2026-11-25-run-ziele-umbau.md`.

Kennzeichnung:
- **gemessen**: Bot-Messung M4 (`storage/logs/playtest/20261124_m4/`), Quelle genannt
- **gerechnet**: aus Config-Werten, nicht gemessen
- **Annahme**: eigene Deutung oder Schätzung

---

## Teil A — Kenntnisse je Gebäude

### A.1 Zielbild

1. **Designprinzip: Das Gebäude schaltet seine Kenntnis frei, die Kenntnis verstärkt ihr Gebäude.** Jede der sieben Kenntnisse hängt an genau einem Gebäude, und ihr Effekt wirkt auf oder über genau dieses Gebäude. Der Spieler lernt damit eine einzige Regel: „Was ich baue, kann ich auch besser machen."
2. **Das Labor ist nicht mehr das Tor zu den Kenntnissen, sondern ihre Werkstatt für Tempo.** Ohne Labor kann jede Kolonie die Kenntnisse ihrer Gebäude erforschen, aber nur langsam: Der AP-Deckel je Kenntnis liegt dann beim Grundwert. Das Labor bringt den Analytiker und damit dessen Fach-Deckel für **alle** Kenntnisse, dazu seine Stufen-Rabatte auf Kenntnis-Aufstiege und die genaue Sicht (Vertrauenszahl, Prognosen). Das Labor verstärkt also die Forschung selbst: Es ist das eine Gebäude, dessen „Kenntnis" die Forschung ist.
3. **Geforscht wird weiter an einem Ort, im Techtree** (§11 „einziger Ort für Forschung"). Eigene AP gehen in die Kenntnis, nicht ins Gebäude. Das Gebäude-Panel zeigt nur „Hier wird X erforscht" mit Sprung in den Techtree. *Annahme:* Ein zweiter Forschungsort an jedem Gebäude würde die Bedienung zersplittern.
4. **Die stille Forschung bleibt, verliert aber ihre Sonderregel.** Die zweckgebundenen Bonus-AP aus der Cantina (Barkeeper, Sarka, Deva, Lenn) kommen weiter nicht aus dem Pool und fallen nicht unter den Deckel. Das Aussetzen der Labor-Voraussetzung (`withSilentResearch`, `waive: Sciencelab`) entfällt, weil es diese Voraussetzung nicht mehr gibt. Die Gebäude-Voraussetzung gilt auch für Bonus-AP.
5. **Die Labor-Vorschau verliert ihre Grundlage.** Wer selbst AP einzahlt, muss Kosten und Fortschritt genau sehen (angezeigte Zahl = wirkende Zahl). Die Darstellung „grob in Worten" ohne Labor fällt deshalb weg. Das Labor behält die Zahlen, die es heute schon exklusiv zeigt (Vertrauen).
6. **Die Stufen 4 und 5 bleiben an die Kommandozentrale gebunden**, wie heute. Ein zusätzliches Labor-Tor für die Tiefe wäre möglich (Owner-Frage 3), widerspricht aber dem Anlass der stillen Forschung.
7. **Die Kenntnis bleibt, auch wenn ihr Gebäude verfällt.** Die Voraussetzung gilt nur für den Aufstieg, nicht für die Wirkung (Wissen verfällt nicht, §10). Fehlt das Gebäude, ruht die Forschung, der Effekt bleibt.

### A.2 Zuordnung Gebäude → Kenntnis

Der Abgleich mit den heutigen Effekten (`config/knowledge.php`) ergibt eine fast vollständige Eins-zu-eins-Zuordnung, ohne neues Gebäude.

| Gebäude | Kenntnis | Klasse | Heutiger Effekt (zahlenfrei) | Wirkt der Effekt auf/über dieses Gebäude? | Heute nötig neben dem Labor |
|---|---|---|---|---|---|
| Hangar | **Kartografie** | Grundkenntnis (Fokus Hangar) | Lange Missionen kosten einen AP weniger; ab höherer Stufe ist die erste Erkundung je Sol frei | **Ja** bei den Missionen (Hangar). **Teilweise** bei der Erkundung: Das ist eine Kartenaktion, kein Hangar-Vorgang. Passt thematisch (Raumfahrer, Drohne), keine Verschiebung nötig | Hangar |
| Cantina | **Handel** | Grundkenntnis (Fokus Cantina) | Mehr gleichzeitige Cantina-Angebote, bessere Verhandlungschance, Preisbonus auf allen Handelskanälen | **Ja** bei Angeboten und Verhandlung. **Teilweise** beim Preisbonus: Er wirkt auch bei Corvan und den Nexus-Kanälen (Uplink, Handelsposten). Vorschlag: so lassen, die Cantina ist der Ursprung des Handels | Cantina |
| Kommandozentrale | **Konstruktion** | Grundkenntnis (für alle) | Fester AP-Abzug auf großen Ausbaustufen, in der Praxis nur die Kommandozentrale | **Ja, genau.** Der Effekt trifft heute schon praktisch nur die Kommandozentrale | — |
| Harvester | **Geologie** | Sockel (für alle, ab Start) | Mehr Harvester-Ertrag, weniger Geologische Instabilität | **Ja, genau** | Harvester, Labor Stufe 2 |
| Agrardom | **Agronomie** | Sockel (für alle, ab Phase 1) | Mehr Agrardom-Produktion, etwas Vertrauen | **Ja, genau** (Vertrauen ist Nebeneffekt) | Agrardom |
| Krankenstation | **Gesundheit** | fortgeschritten | Senkt das Risiko eines Krankheitsausbruchs zusätzlich zur Krankenstation, gibt Vertrauen | **Ja**, die Kenntnis verstärkt den Schutz, den die Krankenstation liefert | Krankenstation |
| Leitstelle | **Verteidigung** | fortgeschritten | Senkt das Sturm-Risiko, gibt Vertrauen | **Nur thematisch.** Leitstelle und Verteidigung schützen beide die ganze Kolonie, aber der Effekt läuft nicht über die Leitstelle (sie dämpft Vertrauenseinbrüche, mindert Begegnungsschäden, recycelt). → Owner-Frage 4 | Hangar Stufe 2, Labor Stufe 3 |

**Ohne eigene Kenntnis (begründet):**
- **Analytik-Labor:** Keine der sieben Kenntnisse verstärkt das Labor. Seine Rolle ist das Forschungstempo für alle (A.1, Punkt 2). Wer dem Labor trotzdem eine eigene Grundkenntnis geben will, kann nur Konstruktion (als „Materialanalyse") oder Geologie (als „Probenanalyse", heute schon an Labor Stufe 2 gebunden) dorthin legen. Beides bricht das Designprinzip, weil der Effekt auf der Kommandozentrale bzw. dem Harvester bliebe → Owner-Frage 1.
- **Wohnhabitat:** Instanzgebäude mit bis zu sechs Kopien; ein Wohnraum-Bonus aus Kenntnissen ist ausgeschlossen (§6).
- **Uplink-Station:** reine Infrastruktur (Nexus-Import, Tiefenscan); der Handels-Preisbonus wirkt dort schon mit.
- **Handelsposten:** Handel liegt an der Cantina; der Handelsposten ist die späte Infrastruktur-Verstärkung desselben Felds.
- **Religiöse Stätte, Kolonialdenkmal:** ein Bekenntnis, kein Ausbauprojekt (eine Stufe, spät); eine Kenntnis dort käme zu spät, um noch zu wirken.

**Fehlende Gebäude:** keine. Jede Kenntnis findet ein Gebäude, dessen Funktion ihr Effekt schon heute verstärkt. Die einzige Lücke ist eine Zuordnungsfrage (Labor ohne eigene Kenntnis), kein fehlendes Gebäude. Ein neues Gebäude würde sie nicht schließen, weil keine Kenntnis das Labor verstärkt.

**Zur Klassen-Einteilung (Annahme):** Die Owner-Idee kennt zwei Klassen (Grund- und fortgeschrittene Kenntnisse). In der Zuordnung entstehen faktisch drei:
- **Sockel-Kenntnisse** (Geologie, Agronomie): Ihre Gebäude hat jede Kolonie spätestens in Phase 1. Sie sind fair, aber nicht „fortgeschritten".
- **Grundkenntnisse** (Kartografie, Handel, Konstruktion): an den Fokus-Gebäuden bzw. der Kommandozentrale.
- **Fortgeschrittene Kenntnisse** (Gesundheit, Verteidigung): an späten Gebäuden, die Werkstoffe kosten.

Wer Geologie und Agronomie trotzdem „fortgeschritten" haben will, kann ihre Voraussetzung an eine Gebäudestufe binden (Agrardom Stufe 2). Beim Harvester geht das nicht, er hat nur eine Stufe → Owner-Frage 2.

### A.3 Fairness je Eröffnung

Sol-Werte für Gebäude: *gemessen* M4, je ein Lauf (Spec 2026-11-25 §3.1), Sol = erster Sol mit platziertem Gebäude. Die Reihenfolge des zweiten und dritten Fokus-Gebäudes ist **Bot-Verhalten**, keine Regel (offene Variable).

| Eröffnung | ab Phase 1 offen | später | Forschungstempo bis zum Labor |
|---|---|---|---|
| Labor zuerst | Konstruktion, Geologie, Agronomie (3) | Kartografie (Hangar ~Sol 18), Handel (Cantina ~Sol 33) | Deckel mit Analytiker ab Sol ~6 |
| Hangar zuerst | Konstruktion, Geologie, Agronomie, **Kartografie** (4) | Handel (Cantina ~Sol 70) | Grunddeckel bis zum Labor (~Sol 46) |
| Cantina zuerst | Konstruktion, Geologie, Agronomie, **Handel** (4) | Kartografie (Hangar-Zeitpunkt nicht ausgelesen) | Grunddeckel bis ~Sol 46, dazu Bonus-AP der stillen Forschung |
| alle | — | Gesundheit, Verteidigung erst mit Krankenstation bzw. Leitstelle (Werkstoffe; *gemessen:* in M4 baut kein Bot eine Krankenstation) | — |

Zum Vergleich heute (*gemessen*): Hangar- und Cantina-First haben bis Sol ~46 **keine** offene Kenntnis, Labor-First hat ab Sol ~6 Kenntnisse.

**Bewertung:**
- **Zugang:** gleichwertig bis auf eine Kenntnis. Hangar und Cantina haben ihre Fokus-Kenntnis sofort, das Labor nicht. Dafür forscht das Labor schneller.
- **Tempo** (*gerechnet* aus `knowledge_ap_cap_per_sol.base` = 1 und `advisor.cap_bonus_per_rank`): Ohne Analytiker nimmt eine Kenntnis 1 AP je Sol auf. Stufe 1 braucht damit mindestens 8 Sole, Stufe 2 weitere 11. Mit Analytiker liegt der Deckel je Kenntnis ein Mehrfaches höher (*Annahme* zur Stapelung der Rang-Boni, im Code zu prüfen). Begrenzt ist dann eher der Pool als der Deckel.
- **Gleichwertig im Sinne des Owner-Ziels?** *Annahme:* ja, wenn das Labor-Tempo die fehlende Fokus-Kenntnis aufwiegt. Labor-First hat eine Kenntnis weniger, aber jede davon steigt schneller. Hangar und Cantina haben eine mehr, aber langsam. Das ist eine echte Abwägung „Breite gegen Tempo", kein Optimalpfad. Ob es aufgeht, zeigt nur die Messung (A.4).
- **Nebengewinn für die Paritätsregel (§4b):** Geologie ist ohne Labor für alle offen. Damit hat auch Pfad C einen planbaren Regolith-Hebel. Dafür verliert Pfad A sein Alleinstellungsmerkmal „Geologie" in der Tabelle der Grundbedürfnisse. Sein Merkmal ist dann das Tempo über alle Kenntnisse (§4b-Tabelle anpassen).

### A.4 Folgen und Risiken

**Balance**
- Der Effekt jeder Kenntnis bleibt unverändert (Owner). Die Wirkung kommt aber früher und für mehr Kolonien. *Annahme:* Geologie für alle ab Sol 1 hebt das Regolith-Einkommen von Hangar- und Cantina-Läufen spürbar. Das betrifft die Phase-1-Pacing-Messung (Sol 12–13), die ohne Geologie kalibriert wurde, denn heute braucht Geologie Labor Stufe 2.
- Gilt der AP-Deckel je Kenntnis ohne Labor nur mit dem Grundwert, entsteht eine Senke für Spätspiel-AP: jede offene Kenntnis 1 AP je Sol, ohne Labor-Bau. *gemessen M3/M4:* ungenutzte AP im Spätspiel ~20 %. Das wirkt in die gewünschte Richtung, beschleunigt aber Forschungsvorsprung.
- Die Tore der Kommandozentrale bleiben (Stufe 4/5 der Kenntnisse; Krankenstation ab Stufe 3; Leitstelle ab Stufe 2). Die fortgeschrittenen Kenntnisse hängen faktisch an **Werkstoffen**. Das ist der eigentliche Engpass, nicht die Kommandozentrale.
- Die Labor-Rabatte auf Kenntnis-Aufstiege (Stufe 3 und 5) gelten jetzt für mehr Kenntnisse je Lauf. Wirkung klein, nicht nachrechnen, mitmessen.
- **Risiko Labor-First zu schwach:** Ein Fokus-Gebäude ohne eigene Kenntnis könnte sich leer anfühlen. Gegenmittel, falls die Messung das zeigt: Tiefen-Tor (Stufe 4/5 brauchen das Labor, Owner-Frage 3), nicht neue Effekte.

**Techtree-Darstellung**
- Das Labor ist nicht mehr die Wurzel aller Kenntnisse. Jede Kenntnis hängt im Gitter unter ihrem Gebäude, ihre Spalte ist die Spalte des Gebäudes. Die heutigen phasenlokalen Koordinaten (`row`/`column` in `researches.php`) und die Phasen-Zuordnung (Konstruktion, Kartografie, Agronomie, Handel heute Phase 2; Geologie, Gesundheit Phase 3; Verteidigung Phase 4) sind neu zu setzen.
- Die Labor-Vorschau (Techtree ohne Labor, gesperrt, ohne Zahlen) entfällt. Der Techtree ist für alle von Anfang an der Forschungsort.

**Bot**
- Die Forschungsbewertung (`Utility/Valuers.php`, `ResearchSourceTest`) setzt heute ein Labor voraus. Sie muss Forschung ohne Labor mit Grunddeckel kennen.
- Kenntnisse ohne ihr Gebäude darf der Bot nicht anbieten (Lehre aus M4: Gesundheit ohne Krankenstation, 75–83 gescheiterte Aufstiege je Lauf).
- Werkstoff-Gebäude: Der Bot baut sie heute nicht. Ohne Bot-Regel sind Gesundheit und Verteidigung in jeder Messung tot. Das ist eine offene Variable für jede Ziel-Kalibrierung, die Kenntnisse zählt.

**Daten und Code (nur benennen)**
- `database/seeders/data/researches.php`: `required_building_id` je Kenntnis neu, `required_building2_*` entfällt, `row`/`column`/`phase` neu.
- `ResearchService`: Labor-Ausnahme der stillen Forschung entfällt; der Labor-Deckel wird zum Kenntnis-Deckel mit Analytiker-Zuschlag.
- Szenarien in `ResetPlayer.php` (`objectives-done`, `phase2`): Kenntnis-Stände und Voraussetzungen anpassen.
- Laufende Spielstände: laut Owner kein Thema.

---

## Teil B — Siegbedingung: Alternativen

Rahmen (verbindlich, GDD §15/§18): Nexus erteilt der Kolonie einen Auftrag, der Lauf endet mit „Auftrag erfüllt" oder „gescheitert", Frist Sol 100, Sieg-Korridor gezielt Sol 70–85, realistisch Sol 85–95. Owner-Ziel Siegquote: Bot ~50 %, Menschen 70–80 %.

### B.1 Die Alternativen

**(a) Ziehung von drei Aufgaben, zwei erfüllen — repariert (Spec 2026-11-25)**
Drei Aufgaben beim Phasenwechsel aus dem Seed, mindestens eine mittelfristig und eine langfristig, höchstens eine Wirtschafts-Aufgabe, Zielwert je Eröffnung für die Fokus-Aufgaben (P5-A).
- *Spielgefühl:* „Welche zwei verfolge ich?" Eine klare Entscheidung zu Beginn, danach vor allem Abarbeiten. Die Wahl fällt meist auf die zwei, die zur Eröffnung passen.
- *Fairness:* über die Ziehungsregel und P5-A herstellbar; die Ziehung bleibt Glückssache (*gemessen M4:* Siegquote hing stärker an der Ziehung als am Spiel, vor der Reparatur).
- *Spannungsbogen:* flach. Ab Phase-2-Beginn ein einziges Ziel, die Nexus-Leiter erzeugt Zwischendruck.
- *Lore:* „Nexus erteilt drei Auflagen, zwei sind Bedingung für die Verlängerung der Konzession." Funktioniert.
- *Aufwand:* gering, ist spezifiziert.
- *Bot-Testbarkeit:* gut, deterministisch je Seed, Messplan M5 steht.

**(b) Siegpunkte (Catan-artig)**
Viele Handlungen und Zustände bringen Punkte (Kenntnisstufen, Ausbaustufen, Missionen, Beziehungen, Vertrauensserien, Selbstversorgung …). Sieg, sobald eine Schwelle erreicht ist, spätestens bis Sol 100.
- *Spielgefühl:* Jede Handlung zählt, auch im Spätspiel. Das trifft den Leerlauf (*gemessen:* ~20 % ungenutzte AP Sol 71–100). Gefahr: „irgendwas machen" statt Entscheidung. Es gibt einen rechnerischen Optimalpfad (Punkte je AP), sobald die Punktetabelle bekannt ist. Das widerspricht der Catan-Linie „kein Optimalpfad", die bei Catan aus der Konkurrenz mit anderen Spielern kommt, die hier fehlt (*Annahme*).
- *Fairness:* gut steuerbar. Jede Eröffnung hat eigene Punktquellen, die Waage lässt sich je Quelle eichen.
- *Spannungsbogen:* gleichmäßige Rampe ohne Höhepunkte, außer man ergänzt große Einmalpunkte (Meilensteine).
- *Lore-Übersetzung (Vorschlag):* **Konzessionsbewertung.** Die Nexus-Prüfstelle führt eine Bewertungsakte über die Kolonie. Jede nachgewiesene Leistung bringt einen **Leistungsnachweis** (= Punkt). Die Summe ergibt die **Bewertungsstufe** der Konzession: *Probezeit → Bewährt → Bestätigt → Verlängerungsreif*. Erreicht die Kolonie „Verlängerungsreif", verlängert Nexus die Konzession vorzeitig („Auftrag erfüllt"). Bleibt sie bis Sol 100 darunter, greift der bestehende Fail State 3 „Die Konzession wurde nicht verlängert". Der Prüfbericht im Nexus-Funk nennt je Sol neue Nachweise. Das passt zum Konzessionär-Rahmen (§15), zur bürokratischen Nexus-Stimme (`lore_foundation.md`) und erklärt, warum Nexus zählt, statt zu befehlen.
- *Aufwand:* hoch. Punktetabelle über alle Systeme, UI für Akte und Prüfbericht, Score-Neubau.
- *Bot-Testbarkeit:* gut messbar (eine Zahl), aber der Bot optimiert dann Punkte je AP. Das misst die Tabelle, nicht das Spiel.

**(c) Aufträge wählen statt ziehen**
Beim Phasenwechsel legt Nexus einen Katalog vor (z. B. fünf Aufträge, aus dem Seed), der Direktor zeichnet zwei davon gegen. Beide müssen erfüllt werden.
- *Spielgefühl:* stärkerer Besitz („mein Plan"), eine echte Entscheidung mit Informationen über die eigene Kolonie. Gefahr: Man wählt immer die zwei, die zur Eröffnung passen. Dann verschwindet der Run-Unterschied.
- *Fairness:* hoch, aber nur mit Zielwert je Eröffnung (P5-A), sonst sind die eigenen Aufträge Selbstläufer.
- *Spannungsbogen:* wie (a), flach nach der Wahl.
- *Lore:* „Nexus legt den Auftragskatalog vor; der Direktor zeichnet gegen." Sehr gut, der Konzessionär verhandelt seine Auflagen.
- *Aufwand:* mittel (Auswahldialog, Speicherung, Bot-Wahlregel).
- *Bot-Testbarkeit:* braucht eine Wahlregel im Bot; dann je Seed deterministisch. Die Messung hängt an der Wahlregel (offene Variable).

**(d) Aufträge in Etappen (Nexus-Auflagen über den Lauf)**
Phase 2 besteht aus drei **Auflagen** nacheinander. Die erste und zweite kommen aus den mittelfristigen Aufgaben, die dritte aus den langfristigen. Jede Auflage hat einen Prüftermin (Phase-2-Sol). Nach Erfüllung kommt sofort die nächste. Sieg, wenn die dritte Auflage erfüllt ist. Ein verpasster Prüftermin bringt eine Verwarnung bzw. Sanktion, die Auflage bleibt offen.
- *Spielgefühl:* klare Kapitel, wechselnde Schwerpunkte über den Lauf. Gefahr: kein „2 von 3", eine harte Auflage blockiert alles. Gegenmittel: **Auflage tauschen** gegen Kosten (Nexus-Schuld oder Vertrauen), eine eigene Entscheidung.
- *Fairness:* gut; je Etappe ist die Klasse fest, P5-A gilt weiter.
- *Spannungsbogen:* der beste der Alternativen. Drei Höhepunkte, die dritte Auflage trägt das Spätspiel bis Sol 85–95. Die Prüftermine ersetzen die heutige Kontrollpunkt-Leiter; aus abstrakten Prozenten werden konkrete Aufträge.
- *Lore:* „Nexus erteilt die Konzession in Auflagen. Jede erfüllte Auflage gibt die nächste Tranche frei." Sehr gut, passt zum Vorschuss-Modell (Startkapital als Tranche, §15).
- *Aufwand:* mittel bis hoch (Etappen-Zustand, Prüftermine statt Leiter, UI, Sol-Bericht).
- *Bot-Testbarkeit:* gut; Sieg-Sol = Summe dreier Etappen, jede einzeln messbar.

**(e) Etappen mit Wahl (Mischung aus c und d), eigener Vorschlag**
Wie (d), aber zu Beginn jeder Etappe legt Nexus **drei Auflagen der passenden Klasse** vor (aus dem Seed), der Direktor zeichnet eine.
- *Spielgefühl:* drei echte Entscheidungen statt einer, jeweils mit dem Wissen über den aktuellen Koloniezustand (FTL-Muster: nächster Sektor wählen). Die Wahl der dritten Auflage um Sol 50–60 ist die stärkste Spätspiel-Entscheidung im Spiel.
- *Fairness:* gut mit P5-A. Zusätzlich lässt sich eine „fremde" Auflage im Score belohnen (Bewertungsaufschlag), damit die Wahl nicht trivial ist.
- *Spannungsbogen:* wie (d).
- *Lore:* „Nexus legt zur nächsten Tranche drei Auflagen vor; der Direktor zeichnet eine." Verbindet Konzessionär (verhandelt) und Nexus (gibt vor).
- *Aufwand:* hoch (Summe aus c und d).
- *Bot-Testbarkeit:* wie (c), braucht eine Wahlregel. Je Etappe einzeln messbar.

**(f) Leitauftrag plus Nebenauftrag, eigener Vorschlag**
Die Eröffnung bestimmt einen **Leitauftrag** (Labor → Forschungsvorsprung, Hangar → Expeditionen, Cantina → Handelspartner, mit Zielwert je Eröffnung). Dazu werden zwei langfristige Nebenaufträge gezogen. Sieg = Leitauftrag + einer der beiden.
- *Spielgefühl:* klare Identität je Eröffnung, eine Wahl bleibt. Die Eröffnung wird wichtiger, die Run-Varianz kleiner.
- *Fairness:* direkt eichbar, weil jede Eröffnung genau ihren eigenen Leitauftrag hat.
- *Spannungsbogen:* mittel.
- *Lore:* „Nexus hat Ihre Konzession für den gewählten Schwerpunkt ausgestellt." Gut.
- *Aufwand:* gering (Ziehungsregel ändern).
- *Bot-Testbarkeit:* sehr gut.

### B.2 Bewertungsmatrix

Noten 1 (schwach) bis 5 (stark), *Annahme* aus der Beschreibung oben, nicht gemessen.

| Kriterium | (a) Ziehung repariert | (b) Siegpunkte | (c) Wählen | (d) Etappen | (e) Etappen mit Wahl | (f) Leit + Neben |
|---|---|---|---|---|---|---|
| Echte Entscheidungen | 3 | 2 | 4 | 3 | **5** | 3 |
| Fairness je Eröffnung | 3 | 4 | 4 | 4 | 4 | **5** |
| Spannungsbogen bis Sol 100 | 2 | 3 | 2 | **5** | **5** | 3 |
| Lore-Erzählung | 4 | 3 | 4 | **5** | **5** | 4 |
| Run-Varianz | 4 | 2 | 3 | 4 | **5** | 2 |
| Aufwand (5 = wenig) | **5** | 1 | 3 | 2 | 2 | 4 |
| Bot-Testbarkeit | **5** | 3 | 3 | 4 | 3 | **5** |
| **Summe** | 26 | 18 | 23 | 27 | **29** | 26 |

### B.3 Empfehlung

1. **Jetzt: (a) repariert, wie spezifiziert.** M5 ist geplant und misst die Ziel-Definitionen. Die gemessenen Erfüllungs-Sole je Aufgabe braucht jede andere Alternative ohnehin als Eichung. Kein Umbau vor M5.
2. **Zielbild: (e) Etappen mit Wahl.** Begründung: Sie trifft die beiden gemessenen Schwächen zugleich. Erstens das flache Spätspiel: Die dritte Auflage gibt der Zeit nach Sol 60 eine neue Entscheidung. Zweitens das Ziehungsglück: Der Spieler wählt, statt das Los zu tragen. Sie ersetzt die abstrakte Kontrollpunkt-Leiter durch konkrete Nexus-Auflagen, was die Lore stärker erzählt als jede andere Variante. Die Aufgaben-Definitionen aus der Spec vom 2026-11-25 bleiben als Inhalt der Auflagen erhalten, nichts ist verloren. Umsetzung in zwei Stufen: zuerst (d) ohne Wahl (testbar wie a), dann die Wahl.
3. **Siegpunkte nicht als Siegbedingung, aber als Bewertung.** Die Lore-Übersetzung „Konzessionsbewertung" eignet sich sehr gut für den **Highscore** (T57: Score auch für nicht abgeschlossene Läufe). Die Bewertungsakte zählt Leistungsnachweise über den ganzen Lauf, auch wenn er scheitert. Damit bekommt das Spätspiel einen Sinn über den Sieg hinaus, ohne dass Punkte über Sieg und Niederlage entscheiden.
4. **(f) als Rückfall,** falls (e) zu aufwendig ist: kleinster Eingriff mit bester Fairness, aber weniger Varianz.

---

## Teil C — Zusammenspiel: Ziele aus der Kenntnis-Zuordnung

### C.1 Was sich für Forschungsvorsprung ändert

Mit der Zuordnung aus Teil A ist beim Phasenwechsel bekannt, welche Kenntnisse jede Eröffnung offen hat: 3 (Labor) bzw. 4 (Hangar, Cantina), siehe A.3. Heute sind es 6 gegenüber 0. Daraus folgt eine saubere Trennung in die zwei Zeithorizonte:

| Ziel | Zeithorizont | Was zählt | Warum diese Klasse |
|---|---|---|---|
| **Forschungsvorsprung** | mittelfristig (Fokus Labor) | Summe der Stufen aller Kenntnisse (wie Spec 2026-11-25) | Die Sockel- und Grundkenntnisse sind ab Phase-2-Beginn ohne spätes Tor offen: eine Tätigkeit, kein Reifegrad |
| **Wissensbreite** (Vorschlag, neues Ziel, keine neue Kenntnis) | langfristig (neutral) | Alle sieben Kenntnisse mindestens auf einer niedrigen Stufe | Braucht alle sieben Voraussetzungs-Gebäude, also auch Krankenstation und Leitstelle (Werkstoffe, Kommandozentrale Stufe 3): misst den Ausbaugrad der ganzen Kolonie |

**Zielwerte je Eröffnung (nur Vorschlag, zu messen):**
- Forschungsvorsprung: Mit Teil A haben alle Eröffnungen von Anfang an offene Kenntnisse. Der Unterschied liegt im Tempo (Analytiker), nicht im Zugang. *Annahme:* Der Abstand der Zielwerte (heute vorgeschlagen 18 Labor / 14 sonst) wird kleiner. Vorschlag: P5-A beibehalten, aber den Labor-Zuschlag nach M5 neu setzen. Hangar und Cantina beginnen dann nicht erst um Sol ~46 zu forschen.
- *gerechnet* (`knowledge.levelup_costs`): Die vier Kenntnisse einer Hangar- oder Cantina-Eröffnung auf Stufe 2 kosten 4 × 19 = 76 AP. Mit Grunddeckel 1 AP je Kenntnis und Sol dauert das mindestens 19 Sole parallel. Die Summe 8 ist für Hangar und Cantina also um Sol ~35 erreichbar, wenn sie gezielt forschen. Für Labor-First mit 3 Kenntnissen und höherem Deckel liegt die Summe 8 entsprechend früher. Die Zielwerte müssen deutlich darüber liegen. Das ist eine reine Mindestrechnung ohne Pool-Konkurrenz und ersetzt keine Messung.
- Wissensbreite: ein Wert für alle Eröffnungen, weil alle alle sieben Gebäude bauen müssen. *Offene Variable:* Der Bot baut heute keine Werkstoff-Gebäude. Ohne Bot-Regel ist das Ziel in jeder Messung unerreichbar.

### C.2 Je Eröffnung im Zielbild (e) Etappen mit Wahl

| Etappe | Labor zuerst | Hangar zuerst | Cantina zuerst |
|---|---|---|---|
| 1 (mittelfristig) | Forschungsvorsprung als „eigene" Auflage im Angebot | Expeditionen als eigene Auflage | Handelspartner als eigene Auflage |
| 2 (mittelfristig) | Eine fremde Fokus-Auflage oder Kreditreserve; das zweite Fokus-Gebäude steht dann (*gemessen:* Hangar ~Sol 18) | wie links; das Labor kommt bei diesem Bot erst ~Sol 46 | wie links |
| 3 (langfristig) | Wissensbreite, Ingenieursleistung, Expertenstab, Selbstversorgung, Kolonieblüte | gleich | gleich |

Regel für das Angebot (Vorschlag): In Etappe 1 enthält jedes Angebot die eigene Fokus-Auflage (Zielwert je Eröffnung) und zwei andere mittelfristige. So hat jede Eröffnung eine „naheliegende" und zwei fremde Optionen. Ein Bewertungsaufschlag für fremde Auflagen hält die Wahl offen.

Ohne (e), also in (a): Forschungsvorsprung bleibt mittelfristig, Wissensbreite kommt als neuntes Ziel in den langfristigen Pool. Damit hat auch das Labor ein langfristiges Ziel mit Kenntnis-Bezug.

---

## Teil D — Owner-Fragen

1. **Labor ohne eigene Kenntnis?** Keine der sieben Kenntnisse verstärkt das Labor. Soll es eine bekommen (Konstruktion oder Geologie dorthin, Effekt bleibt auf Kommandozentrale bzw. Harvester), oder ist seine Rolle das Forschungstempo für alle? *Empfehlung:* Tempo für alle, Konstruktion an die Kommandozentrale. Nur so gilt „Kenntnis verstärkt ihr Gebäude" ausnahmslos, und das Labor bekommt keinen Zugangsvorteil.
2. **Geologie und Agronomie als Sockel-Kenntnisse?** Ihre Gebäude hat jede Kolonie in Phase 1, sie sind also für alle früh offen statt „fortgeschritten". *Empfehlung:* ja, als dritte Klasse „Sockel" akzeptieren. Sie stärkt die Paritätsregel (Regolith-Hebel für alle). Kein Stufen-Tor beim Agrardom.
3. **Bleiben die Stufen 4/5 nur an die Kommandozentrale gebunden, oder brauchen sie zusätzlich das Labor?** *Empfehlung:* nur Kommandozentrale wie heute. Das Labor-Tor für die Tiefe erst einführen, wenn eine Messung Labor-First als schwächste Eröffnung zeigt.
4. **Verteidigung an die Leitstelle,** obwohl ihr Effekt (Sturm-Risiko) nicht über die Leitstelle läuft? Abweichung vom Designprinzip. *Empfehlung:* ja, so zuordnen, den Effekt jetzt nicht ändern. Bei T61 (Sturmfront mit „Sichern") prüfen, ob die Leitstelle der Ort des Sicherns wird. Dann passt es von selbst.
5. **Labor-Vorschau aufgeben?** Wer ohne Labor selbst forscht, muss Zahlen sehen. *Empfehlung:* ja. Das Labor behält die Vertrauenszahl und die Prognosen als exklusive Sicht.
6. **Siegbedingung:** (a) jetzt messen und (e) Etappen mit Wahl als Zielbild, in zwei Stufen (erst Etappen, dann Wahl)? *Empfehlung:* ja. M5 nicht verschieben, der Umbau beginnt erst danach.
7. **Siegpunkte als Highscore „Konzessionsbewertung"** (Leistungsnachweise, Bewertungsstufen, auch für gescheiterte Läufe, T57) statt als Siegbedingung? *Empfehlung:* ja. Das löst T57 und gibt dem Spätspiel Wert, ohne „Optimalpfad nach Punkten".
8. **Neues langfristiges Ziel „Wissensbreite"** (alle sieben Kenntnisse mindestens auf einer niedrigen Stufe)? *Empfehlung:* ja, aber erst, wenn der Bot Werkstoff-Gebäude baut. Sonst ist es in jeder Messung unerreichbar.
