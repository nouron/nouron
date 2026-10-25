# T43: Entscheidungsvorlage zu den Designfragen aus dem Playtest vom 2026-10-24

Stand 2026-10-25 · Autor: game-designer · Status: entschieden (Owner 2026-10-25), A3-Zahlenabgleich offen
Bezug: ROADMAP T43 (Blöcke A und B), `docs/superpowers/specs/2026-10-23-pfad-vertiefung.md`, GDD §4b „Vertiefung“

## Entschieden (Owner 2026-10-25)

- **B1 bis B5:** Ja, wie empfohlen. Familien werden nach Hangar-Stufe (Leitschiff) freigeschaltet; Training bekommt ein Sol-Limit je Familie bzw. Figur nach dem Muster des Gebäude-Deckels (Höhe nur nach Messung); Training bleibt abstrakt; ein Cantina-Gespräch hat je Figur 2 bis 3 Optionen mit unterschiedlich hoher Bemühung (AP-Kosten), der Spieler wählt, wie viel er hineinsteckt (Variante C bleibt, Fortschritt proportional zu den eingesetzten AP, Sol-Limit als AP-Deckel je Figur, siehe B4); am Phasenübergang kommt ein Erfolgsbildschirm, das erste gezogene Ziel ist von Anfang an offen, die anderen bleiben verdeckt, bis ihr Fortschritt über null liegt.
- **A2 weicht von meiner Empfehlung ab:** Wohnraum und Kolonisten werden **sofort** bei Abschluss eines Wohnhabitats neu berechnet (Ausbau und Neubau), nicht erst im Sol-Wechsel. Grund (Owner): leichter verständlich. Die Vorschau „ab nächstem Sol“ entfällt damit. Folge für den Code (Annahme): Die Neuberechnung der Wohnraum-Grenze muss beim Fertigstellen laufen, nicht nur im Tick; Überkapazität und Abwanderung bleiben Sache des Sol-Wechsels.
- **A3 weicht von meiner Empfehlung ab:** Grundregel „1 Organika versorgt 1 Kolonisten“ (Bedarf = Zahl der Kolonisten, bei 4 Kolonisten also 4 Organika) statt 1 je 4. Die Regel ist entschieden, der **Zahlenabgleich ist offen**: Der Verbrauch steigt auf das Vierfache, die Organika-Produktion muss ggf. gedrosselt bzw. neu abgestimmt werden. Das ist ein eigener Messschritt nach Skill `nouron-balance-pass` (ein Hebel, Bot-Batch nur nach Rückfrage und Dauerschätzung). **Welche Zahl zählt (Annahme, der Owner nannte nur sein Beispiel mit 4 Beschäftigten):** Es gibt im Spiel keine Kolonisten ohne Arbeitsplatz; die anwesenden Kolonisten sind genau die besetzten Arbeitsplätze (einschließlich der Reserve neu platzierter Gebäude, abzüglich Abgewanderter). „Alle vorhandenen“ und „beschäftigte“ sind also dieselbe Zahl. Am verständlichsten: Bedarf = diese Zahl, in der Anzeige „N Kolonisten“ getrennt von „Wohnraum für M“; das Wort „beschäftigt“ im Sol-Bericht kann entfallen. Die Abrundung (kleine Kolonie isst gratis) entfällt bei 1:1 von selbst.
- **Zusatz Labor:** siehe Absatz am Dateiende (Widerspruch zu B2).
- **Annahmen bestätigt (Owner 2026-10-25):** Zuordnung der Familien in B1 (Erkundung und Bergung ab Hangar Stufe 1, Versorgung sowie Handel und Kurier ab Stufe 2, Schutz ab Stufe 3); zweckgebundene Bonus-AP sind vom Sol-Limit ausgenommen; ein Vertrauens-Ereignis bei Wohnraumverlust gibt es in Version 1 nicht, ist später vermutlich sinnvoll („später prüfen“).

Alles, was hier als **Fakt** steht, habe ich im Code oder in der Config nachgelesen. Alles, was als **Annahme** oder **Empfehlung** steht, ist meine Deutung und noch keine Entscheidung. Konkrete Zahlen entscheidet diese Vorlage nicht; die Config-Werte der Pfad-Vertiefung sind weiterhin Platzhalter und werden erst nach einer Messung festgelegt.

## Überblick: was gilt heute (Fakten, die mehrere Fragen berühren)

- Aufstieg im Hangar (Training je Missionsfamilie) und in der Cantina (Vertrautheit je Figur) läuft über eine einzige Stelle im Code, den Pfad-Vertiefungs-Dienst. Er prüft vier Dinge: Figur ist schon getroffen (nur Cantina), Höchststufe nicht erreicht, Gebäudestufe erlaubt die Stufe, genug AP im gemeinsamen Pool. Eine Grenze pro Sol gibt es dort nicht.
- Der AP-Deckel aus T35 (Obergrenze pro Gebäude und Sol) gilt nur für den Gebäude-Ausbau durch den Baumeister. Er gilt weder für Kenntnisse noch für das Training.
- Alle fünf Missionsfamilien sind heute ab Hangar Stufe 1 trainierbar. Die Hangar-Stufe bestimmt aber schon die Schiffsklasse: Stufe 1 Drohne, Stufe 2 Frachter, Stufe 3 Korvette (Config des Hangars).
- Die Gebäudestufe deckelt die Trainingsstufe (Entscheidung F5): Stufe 1 und 2 ab Gebäudestufe 1, Stufe 3 und 4 ab Gebäudestufe 2, Stufe 5 ab Gebäudestufe 3.

---

## B1: Welche Missionsfamilie wird wann trainierbar?

**Kurzfassung:** Ich empfehle, die Familien nach der Schiffsklasse freizuschalten, die ihre Missionen typischerweise fliegt: Erkundung und Bergung ab Hangar Stufe 1, Versorgung sowie Handel und Kurier ab Stufe 2, Schutz ab Stufe 3.

**Problem aus Spielersicht.** Wer den Hangar baut, sieht sofort fünf Trainingsbalken, obwohl er nur eine Drohne hat. Die Hälfte davon betrifft Schiffe, die es noch gar nicht gibt. Das wirkt überladen und nimmt dem Hangar-Ausbau den Reiz, denn die nächste Hangar-Stufe bringt dann „nur“ ein Schiff, aber nichts Neues zum Üben. Im Labor dagegen ist nicht alles gleich offen.

**Fakt: welche Schiffe fliegen was** (Missionskatalog):
- Erkundung: nur Drohne.
- Bergung: Bergungsflug mit Drohne oder Frachter; Trümmerbergung, Ruinen-Expedition und Harvester-Bergung mit Frachter oder Korvette.
- Versorgung: nur Frachter.
- Handel und Kurier: Botenflug mit Drohne, Handelsfahrt mit Frachter.
- Schutz: nur Korvette.

**Optionen.**
- **A. Alles offen lassen (heute).** Einfach, aber überladen, und die Hangar-Stufen verlieren ihren Sprung.
- **B. Freischaltung nach Leitschiff (Empfehlung).** Erkundung und Bergung ab Stufe 1 (Drohne findet und erkundet), Versorgung sowie Handel und Kurier ab Stufe 2 (Frachter), Schutz ab Stufe 3 (Korvette). Vorteil: jede Hangar-Stufe bringt etwas Neues zum Üben, passend zur bestehenden Idee „eine Stufe, eine Schiffsklasse“. Wer Hangar-First spielt, hat in Phase 1 trotzdem zwei Familien zum Üben, also eine Senke für Leerlauf-AP. Nachteil: Bergung und Handel und Kurier mischen Schiffe; die Zuordnung ist eine Vereinfachung (Annahme).
- **C. Freischaltung nur nach Besitz eines Schiffs des Typs.** Wirkt „ehrlich“, kollidiert aber mit B3 (Training an Schiff binden) und macht Training an Lieferzeiten und Kaufentscheidungen abhängig. Nicht empfohlen für Version 1.

**Wenn Ja (B):** Eine Zuordnung Familie → Mindest-Hangar-Stufe in `config/path_depth.php`, Balken gesperrter Familien entweder ausgeblendet oder mit Hinweis „ab Hangar Stufe 2“. Der Gebäudestufen-Deckel (F5) bleibt unverändert und gilt zusätzlich.
**Wenn Nein:** Alles bleibt offen; die UI-Aufgabe T43-C1 (Hangar-Screen aufräumen) muss dann fünf Balken unterbringen.

**Aufwand:** Klein (Config, eine Prüfung im Dienst, Hinweistext). **Auswirkung:** Auf T41 gering (Datenmodell unverändert). Auf den AP-Deckel keine, aber die Senke für Leerlauf-AP in Phase 1 schrumpft auf zwei Familien; ob das reicht, zeigt der Hangar-First-Handtest (offene Variable).

---

## B2: Soll Training ein Sol-Limit haben?

**Kurzfassung:** Ja. Ich empfehle eine Obergrenze pro Familie bzw. Figur und Sol nach demselben Schema wie der Gebäude-Deckel (Grundwert plus Bonus durch den Rang des zuständigen Beraters).

**Problem aus Spielersicht.** Heute kann man alle AP eines Sols in eine einzige Familie stecken und sie in einem Sol auf die nächste Stufe heben. Das beseitigt die Entscheidung „Breite gegen Tiefe“, weil Tiefe nur eine Frage des Durchhaltens an einem einzigen Tag ist, und es zieht AP weg von allem anderen (Bauen, Missionen).

**Fakt: Vergleich.**
- Gebäude-Ausbau: pro Gebäude und Sol begrenzt (T35); Grundwert plus Baumeister-Bonus nach Rang. Der Owner hat am 2026-10-16 festgelegt, dass dieses Schema für alle Berater-Typen gelten soll (Pilot und Händler mit je eigenen Zielen). Das Training ist genau so ein Ziel, wurde aber noch nicht angeschlossen.
- Kenntnisse im Labor: Ich habe im Code der Kenntnis-Investition keine Obergrenze pro Sol gefunden; die Spec (Abschnitt 4a) bestätigt, dass der Deckel für Kenntnisse nicht gilt. Wenn das Labor sich im Spiel trotzdem begrenzt anfühlt, liegt es vermutlich an Stufenschwellen und den Kommandozentralen-Stufen für Kenntnis 4 und 5 sowie am AP-Pool selbst (Annahme, bitte im Handtest gegenprüfen). Die Aussage „Labor nimmt nur begrenzt AP auf“ trifft laut Code also nicht per Sol-Grenze zu.

**Optionen.**
- **A. Kein Limit (heute).** Einfach, aber Ein-Sol-Durchsprints und verzerrte Breite/Tiefe-Wahl.
- **B. Limit pro Familie bzw. Figur und Sol (Empfehlung).** Wie T35, mit Bonus durch Raumfahrer-Rang (Hangar) und Konsul-Rang (Cantina). Der Spieler muss Training über mehrere Sole verteilen; Breite gewinnt Tempo, Tiefe braucht Zeit. Passt zum bereits entschiedenen Schema.
- **C. Ein gemeinsames Limit pro Gebäude und Sol.** Einfacher zu erklären (eine Zahl), nimmt aber die Wahl zwischen den Familien nicht weg, sondern nur die Gesamtmenge.

**Wenn Ja (B):** Neue Config-Werte im Stil von `ap_cap_per_sol`, ein Zähler pro Familie/Figur und Sol (gleiche Bauart wie beim Gebäude), Chip „heute x/y“ im Trainingsbalken (Kostenkonvention). Das Labor bleibt vorerst unverändert; ein Deckel für Kenntnisse wäre ein eigener, späterer Schritt (steht schon als offen im Pacing-Entscheidungsdokument).
**Wenn Nein:** Die Spec-Annahme O3 („kein Gegenstück je Ding, es sei denn die Messung zeigt Ein-Sol-Durchsprints“) gilt weiter; der Playtest hat den Durchsprint nun aber gezeigt.

**Aufwand:** Klein bis Mittel (Zähler, Prüfung, Anzeige, Tests). **Auswirkung:** Direkt auf die Senke für Leerlauf-AP: Je niedriger das Limit, desto weniger Leerlauf-AP lassen sich im Hangar-First-Start versenken. Die Höhe darf deshalb nicht ohne Messung festgelegt werden (Hangar-First-Handtest oder Bot-Regel „trainieren“ nötig; Bot-Batch nur nach Rückfrage). Mit T35 selbst entsteht kein Konflikt, weil beide Deckel getrennte Zähler haben.

---

## B3: Soll laufendes Training an ein Schiff gebunden sein?

**Kurzfassung:** Nein, Training bleibt in Version 1 abstrakt (Erfahrung gehört der Kolonie). Eine Bindung an Schiffe prüfen wir erst zusammen mit der offenen Erweiterung „Missionen geben auch Fortschritt“.

**Problem aus Spielersicht.** „Ich trainiere Frachter-Flüge, aber mein Frachter fliegt gleichzeitig eine Mission“ wirkt unlogisch. Gleichzeitig ist Training heute eine sofortige AP-Investition ohne Dauer; es gibt also nichts, was „laufen“ könnte.

**Fakt:** Es gibt kein Training mit Laufzeit. Die Spec hält fest: „Die Erfahrung gehört der Kolonie, nicht dem Schiff“ (Annahme zu F6), und Version 1 ist reine AP-Investition (Variante C, Owner 2026-10-24).

**Optionen.**
- **A. Abstrakt lassen (Empfehlung).** Kein Schiff nötig, keine Dauer. Vorteil: Hangar-First kann in Phase 1 üben, ohne an Lieferzeiten oder Missionen zu hängen; das ist der Grund für Variante C.
- **B. Schiff des Typs muss im Hangar stehen (nicht auf Mission).** Keine Dauer nötig, aber Training wird blockiert, solange das einzige Schiff unterwegs ist. Das schwächt die Senke für Leerlauf-AP genau dann, wenn Hangar-First viele freie AP hat, und führt zu Frust („ich kann nicht üben, weil es fliegt“).
- **C. Training belegt das Schiff für mehrere Sole.** Echte neue Mechanik (Dauer, Belegung, Abbruch, Verschleiß?). Passt thematisch am besten, ist aber ein eigener Entwurf, kein Quickwin, und widerspricht Variante C.

**Wenn Ja (A):** Nichts zu bauen. Die UI-Aufgabe T43-C1 darf Training am Schiff *anzeigen* (Balken neben dem Schiff), ohne dass es eine Bindung gibt; die Erklärung im Spiel sagt, dass die Kolonie übt, nicht ein Schiff.
**Wenn B oder C:** Eigene Spec mit Zeitverlauf, Wechselwirkung mit Missionsstart und B1.

**Aufwand:** A: keiner. B: Klein. C: Groß. **Auswirkung:** Auf T41 keine bei A. Der AP-Deckel ist unberührt.

---

## B4: Cantina mit Gesprächsoptionen statt „Investieren“

**Kurzfassung:** Machbar und mit der Entscheidung Variante C vereinbar, solange das Gespräch nichts anderes bewirkt als die AP-Investition. **Entschieden (Owner 2026-10-25, geändert):** Je Figur gibt es 2 bis 3 Gesprächsoptionen mit unterschiedlich hohen AP-Kosten; der Spieler justiert damit, wie viel Bemühung er in das Gespräch steckt. Ein einzelnes festes Paket ist damit überholt (meine ursprüngliche Empfehlung).

**Problem aus Spielersicht.** Ein Knopf „Investieren“ an einer Figur wirkt wie Buchhaltung, nicht wie Zeit mit einem Menschen. Die Schritte sind klein (heute 1 AP je Klick), das fühlt sich zäh an.

**Fakt:**
- Vertrautheit wächst heute ausschließlich durch AP-Investition; die Figur muss im Lauf einmal getroffen worden sein (O1, entschieden). Der Barkeeper gilt von Anfang an als getroffen.
- Der Dienst begrenzt eine Einzahlung automatisch auf den Rest bis zur nächsten Schwelle; mehrere AP am Stück sind technisch schon möglich (Parameter für die Punktzahl).
- Die Spec trennt in der Tabelle 4a1 „Ausbau“ (nur AP) und „Ausführung“ (das Gespräch selbst kostet AP). Gespräche, die zugleich Ausbau sind, vermischen beides in der Cantina. Das ist mit Variante C vereinbar, solange das Gespräch **keinen zusätzlichen Ertrag** liefert, der nur durch Nutzung entsteht. Sobald ein Gespräch Informationen, Credits oder Zufallsausgang bringt, wäre es Variante A (Nutzung zählt) und müsste getrennt entschieden werden.

**Optionen.**
- **A. „Investieren“ belassen.** Keine Arbeit, aber der Playtest-Eindruck bleibt.
- **B. Ein festes AP-Paket je Figur (ursprüngliche Empfehlung, überholt).** Ein Knopf, mehrere AP am Stück; weniger Klicks, aber keine Wahl der Bemühung.
- **C. 2 bis 3 Gesprächsoptionen je Figur mit unterschiedlich hohen AP-Kosten (Owner-Entscheidung).** Die Frage an den Spieler lautet: „Wie viel Bemühung stecke ich in dieses Gespräch?“ Die Optionen unterscheiden sich in den AP-Kosten und im Ton der Antwort, nicht in einem Zusatzertrag.

**Wie viel Fortschritt bringt mehr Bemühung? (Annahme, mit Variante C vereinbar).** Der Fortschritt ist **proportional zu den eingesetzten AP** (jeder AP bringt gleich viel Fortschritt am Vertrautheitsbalken, wie heute bei der Investition). Es gibt keine Mengenrabatte und keine Gegenwert-Stufen, weil eine größere Option sonst immer die bessere wäre (oder die kleine nutzlos) und der Spieler eine versteckte Rechnung bräuchte; die angezeigte Zahl ist die wirkende Zahl. Der Sinn mehrerer Optionen liegt in der **Feinsteuerung**: kleine Option kurz vor einer Schwelle oder bei knappen AP, große, wenn viele AP frei sind. Nichts erzeugt Ausführung oder Zusatzertrag (keine Information, kein Credits-Gewinn, kein Zufall); sobald es das täte, wäre es Variante A. Kurz vor einer Schwelle zahlt eine Option nur den Rest bis zur Schwelle; die Anzeige nennt die echten Kosten.

**Sol-Limit auch für Gespräche (Owner-Ergänzung 2026-10-25).** Das Prinzip „AP lassen sich nicht an einem Sol in eine einzige Sache kippen“ (Bauen, Labor je Kenntnis, Training je Familie) gilt in ähnlicher Form für die Cantina. Neu gedacht, nachdem es mehrere Optionen mit variablen Kosten gibt. **Empfehlung (Annahme): ein AP-Deckel je Figur und Sol**, gleiche Logik wie bei Bauen, Labor und Training (derselbe Mechanismus: AP je Schlüssel gegen einen Deckel zählen, Chip „heute x/y“). Begründung: Bei „ein Gespräch je Figur und Sol“ müsste der Spieler zwischen den Optionen wählen, und es würde fast immer die größte passen, solange AP frei sind; die kleinen Optionen wären sinnlos, und die Feinsteuerung nahe der Schwelle ginge verloren. Beim Deckel kann er stattdessen zwei kleine Gespräche führen oder ein großes, bis der Deckel erreicht ist; die Regel ist dieselbe wie in den anderen drei Pfaden („pro Sol nimmt jede Sache nur begrenzt AP an“), also einmal zu lernen. Eine Option, die den Rest des Deckels übersteigt, wird ausgegraut oder nennt ihre gekürzten Kosten. Höhe des Deckels und Rang-Bonus des Konsuls (analog zum Bonus beim Bauen und Training) nach Messung, Zahlenabgleich offen. Die Alternative „ein Gespräch je Figur und Sol“ wäre nur dann besser, wenn der Owner bewusst eine Tagesszene je Figur will (eine Entscheidung, wie viel Bemühung), nicht eine Menge AP. Offen für die Umsetzung: Ob eine Figur, die am Sol nicht anwesend ist, trotzdem ansprechbar bleibt (Annahme: ja, Vertrautheit hängt nicht an Anwesenheit; nur das erste Treffen setzt Auftritt voraus, O1).

**Verhältnis zu T41 P7 (Stufenwirkung je Figur):** P7 legt fest, *was* eine Vertrautheitsstufe bringt. Die Gesprächsoptionen können später Träger dieses Ergebnisses sein (zum Beispiel öffnet Stufe 2 ein weiteres Thema); in Version 1 unterscheiden sie sich nur in Bemühung und Ton. Die 15 Figuren brauchen Gesprächstexte; das ist ein Auftrag für den Content-Writer und größer als der Code.

**Text-Hinweise für die Oberfläche (Vorschlag für den content-writer, keine fertigen Texte):**
- Optionsnamen nach Bemühung: zum Beispiel „kurz“, „normal“, „ausführlich“ (oder „Hallo sagen“, „zuhören“, „den Abend verbringen“); bei zwei Optionen genügen kurz und ausführlich.
- Ton statt Zahl: Jede Option zeigt Text und den AP-Chip; der Fortschritt in AP steht im Balken („+n zur nächsten Stufe“).
- Gekürzte Kosten nahe der Schwelle oder am Deckel klar nennen („nur noch n AP bis zur nächsten Stufe“, „heute x/y“); nie „Baucrew“ oder Bauvokabular.
- Antwort der Figur wechselt mit der Vertrautheitsstufe, nicht mit der Option (keine Zusatzbelohnung im Text).
- Gesperrt-Grund sagen: „noch nicht getroffen“, „Tageslimit erreicht“, „nicht genug AP“.

**Wenn Ja (C):** Gesprächsoptionen als Config-Liste je Figur (Kosten Platzhalter), Fortschritt gleich eingesetzte AP, Sol-Deckel je Figur (derselbe Mechanismus wie bei Bauen, Labor, Training), Texte `pathdepth.ui_*` (T43-E2) mitziehen, Kodex-Auslöser unverändert (F3).
**Wenn Nein:** Bleibt „Investieren“; T43-C4 (Tomas hinter die Bar) ist davon unabhängig.

**Aufwand:** Mittel (UI mit Optionen, Deckel-Zähler, Texte für 15 Figuren). **Auswirkung:** Auf das AP-System keine neue Quelle; der Cantina-Aufstieg wird schneller, weil weniger Klicks nötig sind, nicht weil die Kosten sinken. Ein Sol-Limit (B2) wirkt hier gleichermaßen.

---

## B5: Übergang von Phase 1 zu Phase 2

**Kurzfassung:** Ein Teil ist da, der wichtigste fehlt. Ich empfehle, beim Übergang ein klares Erfolgs-Feedback zu zeigen und mindestens eine der drei Phase-2-Direktiven sofort offenzulegen; die anderen bleiben, wie bisher, bis zum ersten Fortschritt verdeckt.

**Problem aus Spielersicht.** Phase 1 endet, ohne dass es sich wie ein Erfolg anfühlt, und in Phase 2 stehen drei Fragezeichen („Direktive noch unbekannt“). Der Spieler weiß nicht, worauf er hinarbeitet. Verschärfend: Die Nexus-Kontrollpunkte in Phase 2 verlangen Fortschritt bei den Zielen. Verdeckte Ziele kann niemand gezielt verfolgen, aber sie werden trotzdem geprüft.

**Fakt: was schon da ist.**
- Phase 1 hat im Sol-Bericht eine Checkliste mit Haken (Kommandozentrale, Pfadgebäude, Berater; Harvester, Wohnhabitat und Agrardom werden bewusst nicht aufgelistet).
- Beim Übergang wird eine ungelesene Nexus-Nachricht erzeugt („Phase 1 abgeschlossen … Weitere Anforderungen wurden übermittelt“) plus ein Text in der Laufübersicht.
- Phase 2 zieht beim Start sofort drei Ziele (reproduzierbar je Lauf-Seed, höchstens ein Wirtschaftsziel).
- Der Sol-Bericht zeigt in Phase 2 die Ziele; ein Ziel gilt als aufgedeckt, sobald sein Fortschritt über null liegt oder es erfüllt ist. Bis dahin steht dort der Text „Direktive noch unbekannt — im Spielverlauf erspielt“.

**Fakt: was fehlt.**
- Kein eigener Erfolgsmoment am Übergang. Ich habe keinen eigenen Übergangsbildschirm gefunden; das Feedback besteht aus der Nexus-Nachricht (Annahme: der Spieler übersieht sie leicht).
- Kein Ziel ist zu Beginn sichtbar. Welches Ziel zufällig früh aufgedeckt wird, hängt vom Los ab: „Gesamtsumme der Ausbaustufen“ ist zum Beispiel schon am ersten Sol über null, ein Ziel mit Berater-Rängen erst viel später.

**Optionen.**
- **A. Heute lassen.** Kein Aufwand, aber Rätselgefühl und unfaire Kontrollpunkte.
- **B. Erstes gezogenes Ziel sofort aufdecken, Rest per Fortschritt (Empfehlung, Owner-Wunsch).** Dazu ein Erfolgsmoment: ein zusätzlicher Bildschirm im Sol-Bericht beim Übergang („Grundaufbau steht“, kurz zusammengefasst, was erreicht wurde) mit dem aufgedeckten Ziel. Welches Ziel zuerst aufgedeckt wird, ist eine Annahme: das erste in der gezogenen Reihenfolge, damit es ohne Zusatzlogik reproduzierbar bleibt.
- **C. Alle drei sofort aufdecken.** Maximal transparent und fair für die Kontrollpunkte, nimmt Phase 2 aber den Entdeckungs-Reiz. Als Rückfallposition denkbar, falls das Verstecken in Tests nur frustriert.
- **D. Alle verdeckt lassen, aber Kategorie zeigen** (zum Beispiel „Wirtschaft“, „Ausbau“). Eine Zwischenlösung; zusätzlicher Textpflegeaufwand, wenig Gewinn.

**Wenn Ja (B):** Aufdeck-Regel erweitern (ein Ziel gilt von Anfang an als aufgedeckt), neuer Übergangstext, Test. **Wenn Nein:** Siehe A; die Kontrollpunkte sollten dann zumindest nur aufgedeckte Ziele zählen (Prüfpunkt, nicht entschieden).

**Aufwand:** Klein bis Mittel. **Auswirkung:** Keine auf T41 oder den AP-Deckel.

---

## A2: Wohnhabitat-Ausbau gibt Kolonisten erst nach Sol-Ende; weniger für einen Ausbau?

**Kurzfassung:** Heute ist der Zeitpunkt Regel, kein Fehler, und ein Ausbau gibt genauso viel Wohnraum wie ein Neubau. Ich empfehle, die Regel zu lassen und die Wirkung vor dem Sol-Ende anzuzeigen; „Ausbau gibt weniger“ ist erst nach Messung sinnvoll.

**Fakt (Regel heute).**
- Das Wohnhabitat liefert **Wohnraum**, keine Kolonisten. Der Wohnraum summiert sich aus einem flachen Anteil der Kommandozentrale plus (Summe der Stufen aller Wohnhabitate) mal einem Wert je Stufe (`game-reference.md` §10).
- Ein Wohnhabitat der Stufe 2 bringt also exakt das Doppelte einer Stufe 1; Neubau und Ausbau sind pro Stufe gleichwertig. Es gibt bis zu sechs Wohnhabitate und je höchstens Stufe 3.
- Die Wohnraum-Grenze wird im Sol-Wechsel neu berechnet. Deshalb zeigt die Anzeige erst nach Sol-Ende die neue Zahl. Das gleiche Prinzip gilt beim Rückbau: Die Oberfläche zeigt dort schon eine Vorschau („Wohnraum ab dem nächsten Sol: n Plätze weniger“). Für den Ausbau ist eine solche Vorschau nicht gefunden (Annahme: fehlt).
- Kolonisten ergeben sich aus den **Arbeitsplätzen** (siehe A3), nicht aus dem Wohnraum. Zusätzlicher Wohnraum erlaubt neue Gebäude, schafft aber von sich aus keine zusätzlichen Kolonisten.

**Was ich daraus ableite (Annahme):** Der Eindruck „der Ausbau liefert nichts“ entsteht, weil die Zahl bis zum Sol-Ende alt bleibt und weil im Sol-Bericht „Kolonisten“ auf den Wohnraum zeigt (siehe A3). Der Spieler baut aus und sieht sofort keine Veränderung.

**Optionen.**
- **A. Regel lassen, Vorschau zeigen (Empfehlung).** Beim Ausbau und im Gebäudefenster steht „ab dem nächsten Sol: +n Wohnraum“ (die Vorschau-Berechnung gibt es im Code bereits für den Rückbau). Kleiner Eingriff, kein Balancing-Risiko.
- **B. Ausbau gibt weniger als Neubau** (Owner-Gedanke). Argument dafür: Die Bau-Entscheidung „ein weiteres Feld belegen oder ein vorhandenes ausbauen“ wird qualitativ unterschiedlich. Das Review vom 2026-08-25 kritisierte genau, dass beide Wege denselben Wohnraum-Topf gleich füllen. Argument dagegen: Es ist eine zusätzliche Regel, die der Spieler erklärt bekommen muss („Stufe 2 zählt nur halb“); das widerspricht dem Transparenz-Prinzip. Eleganter wäre der Unterschied über die **Kosten** (Ausbau billiger oder teurer als Neubau), nicht über die Wirkung.
- **C. Sofortwirkung statt Sol-Ende.** Widerspricht dem Tick-Prinzip (alles Wirken im Sol-Wechsel) und riskiert Reihenfolge-Fehler im Tick. Nicht empfohlen.

**Wenn Ja (A):** Vorschau-Zeile, Text, Test. **Wenn B:** Eigene Messung (ein Hebel), Szenarien in `game:reset-player` und `docs/game-reference.md` anpassen (Wohnraum-Formel). **Wenn Nein:** Verhalten bleibt, Verwirrung bleibt.

**Aufwand:** A: Klein. B: Mittel (plus Messung). **Auswirkung:** Keine auf T41 und den AP-Deckel.

---

## A3: Organika, 1 je 4 Kolonisten gegen 1 je Kolonist, und die Anzeige „4 von 18 beschäftigt“

**Kurzfassung:** Die Rechnung im Spiel ist in sich stimmig, aber die Beschriftung im Sol-Bericht ist irreführend. Ich empfehle, zuerst die Anzeige zu klären und die Verbrauchsregel (1 je 4) zu lassen; die einfachere Regel „1 je Kolonist“ wäre ein eigener Balance-Schritt mit Messung.

**Fakt: welche Zahl zählt.**
- Verbrauch pro Sol = Anzahl der **anwesenden Kolonisten** geteilt durch 4, abgerundet (`game.food.supply_per_eater`).
- „Anwesend“ heißt: besetzte Arbeitsplätze (Summe aus Stufe mal Platzbedarf der Gebäude, einschließlich der Reserve neu platzierter Gebäude und der Platzbedarfe von Kenntnissen) abzüglich der abgewanderten Kolonisten. Der **Wohnraum** (im Beispiel 18) zählt dafür nicht.
- Im Sol-Bericht steht seit dem Quickwin „:cap Kolonisten, davon :used beschäftigt“. Dort ist `cap` der **Wohnraum** (im Beispiel 18) und `used` die belegten Plätze (im Beispiel 4). „18 Kolonisten“ ist also falsch gelabelt: Es gibt vier Kolonisten und Wohnraum für 18. Der Verbrauch von einer Organika (4 geteilt durch 4) ist damit korrekt.
- Folgen der Abrundung: Bei weniger als vier Kolonisten ist der Verbrauch null. Eine kleine Anfangskolonie isst gratis. Das widerspricht der Owner-Erwartung „jeder Kolonist braucht etwas“ und dem Memory-Zielbild „1 Einheit = 1 Kolonist pro Sol“.
- Die Produktionskurve des Agrardoms und die Hunger-Stufen sind auf die heutige Regel kalibriert; Missionen verbrauchen zusätzlich Organika als Proviant.

**Antwort auf die Frage:** Ja, die Anzeige führt in die Irre. Maßgeblich ist die kleinere Zahl (hier 4). Dass der Owner 18 Esser vermutete und 1 Organika zu wenig fand, ist die logische Folge der Beschriftung.

**Optionen.**
- **A. Regel lassen, Anzeige klarer (Empfehlung).** Beschriftung etwa „4 Kolonisten, Wohnraum für 18“ und in der Organika-Zeile „Verbrauch 1 (4 Kolonisten, ein Esser je vier)“. Kein Balancing-Risiko, sofort umsetzbar.
- **B. Regel auf „1 Organika je Kolonist“ (Owner-Idee).** Einfach zu erklären und ohne Abrundungsloch. Aber: Der Verbrauch würde sich vervierfachen, Agrardom-Produktion, Hunger-Ablauf und Proviant müssten neu kalibriert werden, vor allem Phase 1, in der der Agrardom Pflichtgebäude ist. Gleichzeitig läuft die Pacing-Frage (B6); zwei Hebel in einer Messung vermischen die Ergebnisse. Als Annahme: Der Aufwand liegt in der Messung, nicht im Code (ein Config-Wert).
- **C. Zwischenschritt (zum Beispiel ein Esser je zwei Kolonisten).** Löst weder das Verständnisproblem noch das Abrundungsloch ganz; ich sehe keinen Vorteil gegenüber A oder B.

**Wenn Ja (A):** Beschriftung ändern (Sprachdatei, eine Zeile Logik), Wohnraum und Kolonisten in der Ressourcenleiste ebenso trennen (Prüfpunkt, nicht untersucht). **Wenn B später:** eigener Auftrag nach dem Pacing-Schritt, mit Messung gemäß Skill `nouron-balance-pass` (Grundaufbau-Haushalt, Hunger-Test), Szenarien und `docs/game-reference.md` nachziehen.

**Aufwand:** A: Klein. B: Mittel bis Groß (Messung). **Auswirkung:** Keine auf T41 oder den AP-Deckel; B hängt über Organika-Bedarf indirekt an den Hangar-Missionen (Proviant).

---

## B6 (nicht Teil der Vorlage): Phase 2 schon in Sol 9

Nur, was für eine Entscheidung nötig wäre; Zahlen erfinde ich nicht. Vor jedem Vorschlag wären nach dem Skill `nouron-balance-pass` die Messwerte aus `php tools/phase1-budget.php --since=...` für die aktuelle Fassung zu holen (Hypothese vorab: Welches Sol soll Phase 1 enden, und ist der Playtest-Spieler schneller als der Bot?). Das Entscheidungsdokument vom 2026-10-16 nennt als Hebel die Grund-AP, die Phase-1-Kriterien und den Gebäude-Deckel; mit Deckel liegt der gemessene Phase-2-Start bei Sol 11 bis 13, der Owner hat dagegen Sol 9 gespielt. Offene Variablen: Bot-Verhalten gegen menschliches Spiel (Erkundung, Reihenfolge, Extras), die in diesem Playtest neu hinzugekommenen Einflüsse (Training) und die Frage, ob Sol 9 an einer menschlichen Eröffnung liegt, die der Bot nicht spielt. Ein Bot-Batch erst nach Rückfrage und Dauerschätzung; eine Änderung pro Messung.

---

## Entscheidungsübersicht

| Frage | Empfehlung | Owner-Entscheidung |
|---|---|---|
| B1 Freischaltung der Missionsfamilien | Nach Leitschiff: Erkundung und Bergung ab Hangar Stufe 1, Versorgung sowie Handel und Kurier ab Stufe 2, Schutz ab Stufe 3 (Zuordnung Annahme) | Ja, nach Hangar-Stufe/Leitschiff |
| B2 Sol-Limit für Training | Ja: Limit je Familie bzw. Figur und Sol nach T35-Schema (Grundwert plus Rang-Bonus), Höhe nach Messung | Ja (Höhe nur nach Messung); Widerspruch zur Labor-Aussage siehe unten |
| B3 Bindung an ein Schiff | Nein, abstrakt lassen (Erfahrung gehört der Kolonie); erneut prüfen, wenn Missionen auch Fortschritt geben (Variante A) | Ja: Training bleibt abstrakt |
| B4 Cantina-Gespräche | Ein Gespräch je Figur als festes AP-Paket, ohne Zusatzertrag (vereinbar mit Variante C); zweite Option erst mit P7 | Ja, geändert: je Figur 2 bis 3 Gesprächsoptionen mit unterschiedlich hohen AP-Kosten, Variante C bleibt; Fortschritt proportional zu den AP (Annahme); Sol-Limit als AP-Deckel je Figur (Annahme) |
| B5 Übergang Phase 1 zu 2 | Erfolgsmoment am Übergang plus erstes gezogenes Ziel sofort aufdecken, Rest per Fortschritt | Ja: Erfolgsbildschirm, erstes Ziel offen, übrige verdeckt bis Fortschritt über null |
| A2 Wohnhabitat-Ausbau | Regel lassen (Ausbau und Neubau gleichwertig je Stufe), Vorschau „ab nächstem Sol“ ergänzen; „weniger für Ausbau“ nur nach Messung, eher über Kosten | **Abweichend:** Neuberechnung sofort bei Abschluss (Ausbau und Neubau); keine Vorschau nötig |
| A3 Organika und Kolonisten-Anzeige | Verbrauchsregel (1 je 4) lassen, Beschriftung klären (Kolonisten gegen Wohnraum); „1 je Kolonist“ als eigener Messschritt | **Abweichend:** 1 Organika je Kolonist; Regel entschieden, Zahlenabgleich (Produktion) offen |
| B6 Pacing Phase 2 in Sol 9 | Erst Messung mit Skill `nouron-balance-pass` (siehe Absatz oben), keine Zahlen in dieser Vorlage | Nicht entschieden (Messschritt) |

## Widerspruch: „Das Labor nimmt kein Limit an, analog zum Bauen“

## Entschieden (Owner 2026-10-25, Klärung): Auch das Labor hat ein Sol-Limit

Die frühere Aussage „Labor ohne Limit, analog zum Bauen“ beruhte auf einem Missverständnis: Der AP-Deckel aus T35 begrenzt die Investition in eine Baustelle pro Gebäude und Sol tatsächlich; im Code hat die Kenntnis-Investition bisher kein solches Limit. Der Owner entscheidet: Das Labor nimmt **nicht** unbegrenzt AP an. Nach dem Prinzip des Bauens gilt ein Deckel **je Kenntnis und Sol**; die Formulierung im Spiel ist frei, nur „Baucrew“ passt nicht. Damit gilt dasselbe Prinzip in allen drei Pfaden (Bauen, Labor, Training), der Widerspruch zu B2 ist aufgelöst, B2 bleibt unverändert. Die Höhe des Deckels wird nach Messung festgelegt, der Zahlenabgleich ist offen. Folge (Annahme): Die Spec-Annahme O3 und der Hinweis „Deckel für Kenntnisse“ im Pacing-Entscheidungsdokument sind damit entschieden; eine Umsetzung braucht einen Zähler je Kenntnis und Sol, eine Prüfung in der Kenntnis-Investition (die zweckgebundenen Bonus-AP, z. B. vom Barkeeper, sind vorab zu klären: Annahme, sie bleiben ausgenommen, da sie nicht aus dem Pool kommen) und eine Anzeige im Techtree-Balken.
