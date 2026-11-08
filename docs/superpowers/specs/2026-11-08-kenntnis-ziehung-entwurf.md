# Entwurf: Kenntnis-Ziehung pro Run

> **Status: ENTWURF, kein Beschluss.** Alles hier ist Annahme oder Vorschlag des Game-Designers, nichts ist Owner-Entscheidung. Nichts davon gehört ins GDD, solange der Owner nicht entschieden hat. Zahlenfrei nach ADR 0004; konkrete Werte würden erst nach einer Messung festgelegt.

## 0. Wichtiger Vorbefund: die Idee wurde schon einmal verworfen

- Das GDD hält fest (Abschnitt Kenntnisse, „Alle 7 Kenntnisse sind in jedem Run verfügbar"): Eine Teilmengen-Auswahl pro Run wurde geprüft und **verworfen**, weil bei nur sieben Kenntnissen das Risiko zu hoch sei, dass ein Run ohne eine essentielle Kenntnis auskommen muss. Die Roguelike-Varianz solle aus Startbedingungen, Begegnungswürfen, Cantina-Angeboten und Berater-Erfolgschancen kommen.
- `docs/roadmap-archiv.md` führt das als Entscheidung F6 / Audit-Punkt A10 (2026-09-08): „komplett gestrichen, auch nicht abgeschwächt".
- **Reste der alten Idee** stehen noch an drei Stellen und widersprechen dem GDD: der Hinweiskasten „Roguelike-Variabilität" in `docs/gdd/techtree.md` (Abschnitt 11.1, unter der Kenntnis-Tabelle), das `TODO (Design)` am Ende von `docs/gdd/onboarding.md` (Kenntnis-Hint prüft Labor-Teilnahme) und ein Satz in `docs/superpowers/specs/2026-08-23-building-tier-system-design.md`. Unabhängig vom Ausgang dieses Entwurfs sollte der Hinweiskasten im Techtree-Dokument bereinigt werden (Annahme: Doku-Aufräumen, nicht Teil dieses Entwurfs).
- Dieser Entwurf behandelt die Ziehung also als **bewusste Wiedervorlage**. Der Owner muss zuerst entscheiden, ob F6 aufgehoben werden soll (Frage 1 unten).

## 1. Ausgangslage (aus der Config gelesen)

Was die sieben Kenntnisse heute tatsächlich tun (`config/knowledge.php`):

| Kenntnis | Wirkung heute | Gehört thematisch zu |
|---|---|---|
| Bautechnik | senkt Bau-AP aller Gebäudestufen | alle Eröffnungen (Querschnitt) |
| Kartografie | senkt Navigations-AP (Erkundung, Missionsreisen) | Hangar |
| Handel & Logistik | zusätzliche Cantina-Angebote, Preisbonus, Verhandlungschance | Cantina |
| Agronomie | Vertrauensbeitrag je Stufe | Vertrauen / Agrardom |
| Gesundheit | Vertrauensbeitrag je Stufe | Vertrauen / Krankenstation |
| Verteidigung | Vertrauensbeitrag je Stufe | Vertrauen / Schutz |
| Geologie | **keine eigene Wirkung in der Config** (nur Stufenkosten) | Harvester |

Annahme (gerechnet, nicht gemessen): Zwei Kenntnisse (Bautechnik, Kartografie) und Handel sind die „Wirtschafts-Kenntnisse"; drei sind im Kern Vertrauensquellen, die Vertrauens-Siegziele (Vertrauen als Zielwert) mittragen. Ob eine Kenntnis „essentiell" ist, hängt also vom Siegziel ab, nicht nur von der Kenntnis. Das ist der eigentliche Grund für das alte Risiko-Argument.

Weitere Randbedingungen:

- Alle Kenntnisse setzen das Labor voraus. Wer die Eröffnung „Hangar zuerst" oder „Cantina zuerst" spielt, erreicht Kenntnisse erst nach dem Labor-Bau. Die Eröffnungen sind laut Owner gleichwertig gedacht (T30); das Labor ist dabei der Weg, der Kenntnisse am frühesten öffnet.
- Zwei Kenntnisse hängen funktional an einem Fokus-Gebäude (Kartografie an Hangar, Handel an Cantina). Eine Ziehung berührt damit direkt den Wert der jeweiligen Eröffnung.
- Die Labor-Vorschau (PR #456, Techtree ohne Labor) zeigt heute alle sieben Kenntnisse. Eine Ziehung verändert deren Aussage.

## 2. Gemeinsame Annahmen für alle Varianten

Vorschlag, nicht entschieden:

- **Ziehung einmal pro Run, zum Run-Start,** deterministisch aus dem Run-Seed (`rng_seed` existiert bereits). Dadurch bleibt ein Run reproduzierbar, und Bot-Messungen mit festem Seed bleiben vergleichbar.
- **Ungezogene Kenntnisse sind in diesem Run nicht erforschbar,** haben keine Wirkung und zählen für keine Aufgabe oder kein Siegziel. Siegziele und Aufgaben, die eine ungezogene Kenntnis voraussetzen, dürfen in diesem Run nicht gezogen werden (Annahme: sonst unlösbar; Aufwand siehe Abschnitt 7).
- Die Zahl „fünf von sieben" ist eine Platzhalter-Größe aus dem alten GDD. Eine Variante mit „sechs von sieben" ist ebenfalls denkbar und deutlich harmloser (Frage 2).

## 3. Variante A: reine Zufallsziehung zu Run-Start

**Beschreibung.** Zu Run-Start werden aus den sieben Kenntnissen zufällig die gezogenen ausgewählt, ohne Garantien.

**Spielerfahrung.**
- Maximale Überraschung, stärkster Wiederspielreiz (Catan-Brettvariation). Der Spieler plant nach dem, was er hat.
- Härtester Fehlschlag-Modus: Ein Run ohne die Kenntnis, die das Siegziel trägt, fühlt sich unfair an („Ziehungs-Pech", kein eigenes Versagen). Das passt schlecht zu „Entscheidungen ohne Optimalpfad", weil die Entscheidung dem Spieler abgenommen wird.
- Ein Run ohne Handel macht Cantina-Angebote ärmer, ohne kartografische Rabatte wird Erkundung teurer. Beides ist verkraftbar, solange es nicht gleichzeitig mit Siegziel-Druck zusammenfällt.

**Auswirkung auf die gleichwertigen Eröffnungen.** Negativ. Wird Kartografie nicht gezogen, verliert die Hangar-Eröffnung einen Teil ihres Mehrwerts; fehlt Handel, trifft es die Cantina. Die Gleichwertigkeit wird damit je Seed verschieden gebrochen; über viele Seeds mittelt es sich, im einzelnen Run nicht.

**Auswirkung auf Balance und Bot-Messung.** Erheblich. Die Streuung der Ergebnisse steigt, ein Batch muss pro Ziehungskombination ausgewertet werden (Anzahl der Kombinationen bei fünf von sieben: einundzwanzig). Mit den üblichen 6–10 Seeds sind die Zellen leer. Siegquote pro Eröffnung wird nur lesbar, wenn die Ziehung im Bot-Aufruf fest vorgebbar ist (Annahme, siehe Abschnitt 7). Hinweis aus dem Gedächtnis des Projekts: Die Bot-Quote ist kein Balance-Ziel (Owner), aber die Streuung zwischen Ziehungen ist es sehr wohl.

**Auswirkung auf Techtree-Anzeige / Spoiler-Frage.** Zwei Optionen:
- (A1) Vorschau und Techtree zeigen **nur gezogene** Kenntnisse. Einfach und ehrlich („angezeigte Zahl = wirkende Zahl"), aber der Spieler lernt nie, was er verpasst; Wiederspiel-Neugier fehlt.
- (A2) Zeigt **alle,** ungezogene deutlich als „diesen Run nicht verfügbar" markiert. Bessere Lesbarkeit, nimmt aber der Labor-Vorschau etwas von ihrem Zweck (sie soll ja zeigen, was das Labor bringt).
- Empfehlung innerhalb der Variante (Annahme): A2.

**Umsetzungsaufwand: Mittel.** Ziehung + Persistenz am Run, Sperre in der Forschungsaktion, Techtree-Markierung, Aufgaben-/Siegziel-Filter, Bot-Anpassung.

## 4. Variante B: Ziehung mit garantierten Kernkenntnissen

**Beschreibung.** Eine kleine feste Menge ist in jedem Run dabei (Vorschlag: Bautechnik, weil sie als Querschnitts-Rabatt in jeder Eröffnung gilt, und eine Vertrauensquelle, damit Vertrauens-Siegziele immer lösbar bleiben, z. B. Gesundheit). Die übrigen Plätze werden aus dem Rest gezogen. Bei „fünf von sieben" und zwei Garantien sind es drei Plätze aus fünf Kandidaten.

**Spielerfahrung.**
- Behält Abwechslung, entschärft aber das alte Argument „essentielle Lücke" direkt: Das, was ein Run unbedingt braucht, ist garantiert.
- Weniger dramatisch als A; der Spieler sieht schnell, dass nur zwei bis drei Kenntnisse variieren. Gefahr: Die Variation wirkt klein und kosmetisch, wenn die wirksamsten Kenntnisse garantiert sind.
- Die Kern-Auswahl ist eine Designaussage („das braucht jede Kolonie") und muss begründbar sein.

**Auswirkung auf die gleichwertigen Eröffnungen.** Deutlich besser als A, sofern die Garantien nicht eine Eröffnung bevorzugen. Bautechnik ist eröffnungsneutral. Kartografie (Hangar) und Handel (Cantina) bleiben variabel; das lässt sich als bewusste Würze lesen („dieser Run lohnt sich eher für die Cantina"), oder als Bruch der Gleichwertigkeit. Annahme: Der Bruch ist akzeptabel, wenn er sichtbar ist (siehe Spoiler-Frage), denn dann wird er zur Entscheidungsinformation und nicht zum verdeckten Pech.

**Auswirkung auf Balance und Bot-Messung.** Moderat. Weniger Kombinationen (bei zwei Garantien: zehn). Siegziele müssen nur gegen die variablen Plätze abgesichert werden. Messbar mit größerem, aber vertretbarem Batch; Messen pro Eröffnung bleibt aussagekräftiger als bei A. Offene Variable: Das Bot-Verhalten (welche Kenntnisse er bevorzugt erforscht) verfälscht die Messung, wenn der Bot eine nicht gezogene Kenntnis „bevorzugt".

**Auswirkung auf Techtree-Anzeige / Spoiler-Frage.** Garantierte Kenntnisse normal, variable Kenntnisse mit Zustand „gezogen" oder „diesen Run nicht verfügbar". Zeigen aller sieben (mit Markierung) ist hier unproblematisch, weil die Menge ohnehin klein ist. Die Labor-Vorschau (ohne Labor) könnte die Ziehung schon zeigen: Der Spieler weiß vor der Eröffnungswahl, was das Labor in diesem Run bringt. Das ist eine echte Entscheidungsinformation und stärkt die Eröffnungswahl.

**Umsetzungsaufwand: Mittel.** Wie A, plus die Garantie-Liste (Config), ohne zusätzliche Mechanik.

## 5. Variante C: Ziehung nach Eröffnungswahl (Fokus-Gebäude)

**Beschreibung.** Die Ziehung geschieht nicht zu Run-Start, sondern wenn der Spieler sein erstes Fokus-Gebäude (Labor, Hangar oder Cantina) baut. Die Wahl beeinflusst die Ziehung: Zum Beispiel erhält das gewählte Fokus-Gebäude seine passende Kenntnis garantiert (Hangar: Kartografie, Cantina: Handel, Labor: Bautechnik), der Rest wird gezogen. Alternative Lesart: Das Fokus-Gebäude schaltet zwei Kenntnisse fest frei, die übrigen werden gezogen.

**Spielerfahrung.**
- Die Eröffnungswahl bekommt ein zusätzliches Gewicht und Gesicht: Die Entscheidung formt den Run. Das passt stark zur Kern-Fantasie „Entscheidungen ohne Optimalpfad".
- Die Ziehung ist zugleich Belohnung und Überraschung. Risiko: Die Wahl kann sich wie eine verdeckte Wette anfühlen, wenn der Spieler vor dem Bau nicht weiß, was gezogen wird.
- Komplexer zu erklären (Onboarding: eine Regel mehr, wo die Hint-Kette heute auf Fokussteuerung statt Information setzt).

**Auswirkung auf die gleichwertigen Eröffnungen.** Potenziell positiv, aber riskant: Die Garantie je Fokus-Gebäude kann die Eröffnungen aneinander angleichen (jede bringt „ihre" Kenntnis) oder verschieben, falls eine der drei Kenntnisse stärker wirkt. Heutige Datenlage: Das Labor ist ohnehin der Weg, der Kenntnisse am frühesten öffnet; ein Hangar- oder Cantina-Start bekäme die Kenntnis zwar „geschenkt", kann sie aber erst nach dem Labor-Bau nutzen. Damit würde C die Schwäche der Nicht-Labor-Eröffnungen eher verstärken als beheben, solange Kenntnisse am Labor hängen. Das ist die größte Gefahr dieser Variante (Annahme, gerechnet, nicht gemessen).

**Auswirkung auf Balance und Bot-Messung.** Am schwierigsten. Ziehung hängt vom Bot-Verhalten ab (welche Eröffnung der Bot wählt), die Ziehung ist damit nicht mehr nur seed-, sondern pfadabhängig; Seeds sind für gleiche Eröffnung vergleichbar, über Eröffnungen hinweg nicht. Messung braucht pro Eröffnung getrennte Batches, und der Einfluss „Eröffnung" und „Ziehung" lässt sich nicht trennen (zwei Hebel gleichzeitig, widerspricht „eine Änderung pro Messung").

**Auswirkung auf Techtree-Anzeige / Spoiler-Frage.** Die Labor-Vorschau kann die Ziehung erst nach der Wahl zeigen, also nicht in ihrer wichtigsten Rolle (Planung vor dem Labor-Bau). Vor der Ziehung zeigt der Techtree alle sieben als „möglich", danach die gezogenen. Zwei Zustände, mehr Anzeige-Logik.

**Umsetzungsaufwand: Groß.** Ziehung zur Laufzeit statt zum Start, Abhängigkeit von Bau-Ereignissen, Regeln für Sonderfälle (Fokus-Gebäude zurückgebaut, zweites Fokus-Gebäude), Onboarding-Anpassung, getrennte Mess-Infrastruktur.

## 6. Vergleich

| Kriterium | A: reine Zufallsziehung | B: Kern garantiert | C: nach Eröffnungswahl |
|---|---|---|---|
| Wiederspielreiz | hoch | mittel | hoch |
| Gefühl von Ziehungs-Pech | hoch | niedrig | mittel (Wette) |
| Gleichwertige Eröffnungen | gebrochen je Seed | meist erhalten, sichtbar variabel | unklar, verstärkt Labor-Vorsprung |
| Messbarkeit mit Seeds | schlecht | mittel | schlecht (zwei Hebel) |
| Vorschau zeigt Ziehung vor der Wahl | ja (Start) | ja (Start) | nein |
| Erklärungsaufwand (Onboarding) | niedrig | niedrig | hoch |
| Aufwand | Mittel | Mittel | Groß |

## 7. Aufwand und technische Annahmen (grob, ungeprüft)

Annahmen, nicht im Code verifiziert:

- Run-Start hat bereits einen Seed und eine Aufgaben-/Siegziel-Ziehung (Aufgaben werden gezogen, siehe CHANGELOG zu T43). Die Kenntnis-Ziehung könnte dort andocken.
- Es braucht eine Persistenz „gezogene Kenntnisse" am Run, eine Sperre in der Forschungsaktion, einen Filter für Aufgaben und Siegziele, die Kenntnisse voraussetzen, und einen Zustand im Techtree-Antwortobjekt.
- Der Bot braucht eine Regel für „nicht erforschbar" (Familie der „fehlende Regel"-Bugs, siehe Projekt-Erfahrung) sowie einen Aufrufparameter, um die Ziehung für Messungen festzulegen. Ohne diesen Parameter ist keine der Varianten sauber messbar.
- Zu klären: Geologie hat heute keine eigene Wirkung. Eine Ziehung macht dieses Loch sichtbar (eine gezogene Geologie wäre ein „toter Platz"). Das ist ein eigener Befund, kein Argument für oder gegen die Ziehung, sollte aber vor einer Umsetzung geschlossen sein.

## 8. Empfehlung (Vorschlag des Game-Designers)

1. **Kurzfristig nichts umsetzen.** Der Spielstand hat noch offene Balance-Baustellen (gleichwertige Eröffnungen, Credits-Pfade). Eine Ziehung fügt eine weitere Varianzquelle über genau die Größe, die gerade kalibriert wird, und erschwert jede Messung.
2. **Falls der Owner F6 aufheben will: Variante B**, mit **sechs von sieben** als vorsichtigem Einstieg (eine Kenntnis fehlt pro Run) und mindestens einer garantierten Vertrauensquelle. Begründung: Sie beantwortet das dokumentierte Ablehnungsargument („essentielle Lücke") direkt, bleibt messbar, und die Ziehung kann schon in der Labor-Vorschau erscheinen, wodurch die Eröffnungswahl informierter statt zufälliger wird.
3. **Anzeige (Annahme):** Alle sieben zeigen, ungezogene klar als „in diesem Run nicht verfügbar" markiert. Begründung: passt zur Owner-Priorität, dass die angezeigte Zahl die wirkende Zahl ist, und die Vorschau verliert ihre Planungsfunktion nicht.
4. **Variante C nicht empfohlen,** solange Kenntnisse hinter dem Labor hängen, weil sie den Labor-Vorsprung eher vergrößert, und weil sie zwei Messhebel verschmilzt.
5. **Variante A nicht empfohlen** als Erstversion (Ziehungs-Pech, Messbarkeit).
6. Alternative ohne Ziehung (Annahme, zu prüfen): Run-Varianz über die Verfügbarkeit der **Erkenntnisse-Wirkung** statt der Kenntnis selbst (z. B. pro Run schwankt, welche Kenntnis in der Cantina häufiger als Anliegen auftaucht). Das ist hier nicht ausgearbeitet.

## 9. Offene Owner-Fragen

1. Soll die Entscheidung F6 (Teilmenge verworfen, 2026-09-08) aufgehoben werden? Wenn ja: Was hat sich seit damals geändert, das das Risiko-Argument entkräftet? (Meine Empfehlung: nur aufheben, wenn Garantien nach Variante B akzeptabel sind.)
2. Wie viele Kenntnisse pro Run: fünf von sieben (stark), sechs von sieben (mild)? Empfehlung: sechs für den Anfang.
3. Wenn Kern garantiert wird: Welche Kenntnisse gelten als Kern, und ist „Vertrauensquelle immer dabei" der richtige Maßstab für „essentiell"?
4. Zeigt die Labor-Vorschau die Ziehung schon vor dem Labor-Bau (Empfehlung ja) oder bleibt sie bewusst verdeckt (Entdeckungs-Reiz, dann aber Ziehungs-Pech ohne Vorwarnung)?
5. Sollen Aufgaben und Siegziele, die eine ungezogene Kenntnis voraussetzen, aus der Ziehung ausgeschlossen werden (Empfehlung) oder soll die Kenntnis-Ziehung die Aufgaben-Ziehung bestimmen (z. B. eine gezogene Kenntnis erzeugt eine passende Aufgabe)?
6. Soll der Spieler Einfluss auf die Ziehung haben (Neu-Ziehung einer Kenntnis gegen Kosten, Tausch gegen Vertrauen)? Das wäre eine Variante D, hier nicht ausgearbeitet.
7. Reihenfolge: Soll die Ziehung warten, bis die gleichwertigen Eröffnungen und Credits-Pfade gemessen stabil sind (Empfehlung ja)?
8. Geologie hat heute keine eigene Wirkung. Soll diese Lücke vor einer Ziehung geschlossen werden?
