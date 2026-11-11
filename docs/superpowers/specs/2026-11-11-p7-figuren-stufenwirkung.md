# T41 P7: Stufenwirkung je Figur, im gleichen Muster wie Kenntnisse und Missionsfamilien

Stand 2026-11-11 · Autor: game-designer · Status: Entwurf, Owner-Fragen offen (Abschnitt 8)
Bezug: ROADMAP T41 P7 (Cantina-Stufenwirkung je Figur), T43-B4/G4 (Gesprächsoptionen), Spec `2026-10-23-pfad-vertiefung.md` (Frage O4), Datenarchiv-Entwurf `2026-11-08-nexusdb-codex-zusammenfuehren-entwurf.md` (Korrektur 2026-11-11).
Nur Dokument. Kein Code, keine Messung, GDD unverändert. Konkrete Zahlen stehen ausschließlich in Tabellen und sind **Platzhalter** (ADR 0004), sofern nicht als gemessen gekennzeichnet. Es liegen für diese Spec **keine Messdaten** vor; alle Rechnungen sind **Annahme (gerechnet)**.

## 0. Owner-Vorgaben (2026-11-11, verbindlich)

1. Figuren (Gesprächslevel), Kenntnisse (Stand der Forschung) und Missionsfamilien (Missionserfahrung im Hangar) bekommen ein **ähnliches Stufensystem**. Jede Stufe gibt einen kleinen Bonus, damit das Hochstufen ein Anreiz ist. Ohne Stufen ist das Spiel nicht zu gewinnen.
2. **Effekt-Prinzip:** Alle Effekte sind **absolute, sehr kleine Zahlen** und wirken **additiv, nie multiplikativ**. Beispiel: Figur +1 Regolith je Sol und Geologie +1 Regolith je Sol und Stufe ergeben zusammen +2.
3. Aufstieg bleibt AP-Investition (nicht Nutzung). Beziehungs-Sinken ist eine spätere Idee und nicht Teil dieser Spec.
4. Transparenz: angezeigte Zahl gleich wirkende Zahl, Quellen einzeln und additiv.
5. Zielbild: Menschen schaffen 70 bis 80 Prozent Siege, der Bot etwa 50 Prozent (gewollt). Die Effekte dürfen das Spiel nicht trivial machen.

## 1. Ist-Aufnahme

Quellen: `config/knowledge.php`, `config/game.php`, `config/path_depth.php`, `config/characters.php`, `config/missions.php`, `app/Console/Commands/GameTick.php`, `app/Services/PathDepthService.php`.

**Befund zur Geologie:** Die Annahme „Geologie hat keinen Effekt“ stimmt **nicht**. In `config/knowledge.php` steht kein Effektschlüssel, die Effekte liegen aber in `config/game.php` (Regolith je Sol für den Harvester und eine Instabilitäts-Risikosenkung). Die Effekte der Kenntnisse sind über zwei Dateien verstreut; für die Transparenz-Regel wäre eine zentrale Effekttabelle hilfreich (Vorschlag in Abschnitt 2).

**Befund zur Doku:** `docs/game-reference.md` Abschnitt 6 nennt unter der Kenntnis-Tabelle noch einen „Supply-Cap Bonus“ der Kenntnisse. Das widerspricht T43-H7 (Kenntnisse sind keine Wohnraumquelle mehr). Doku-Drift, gehört in T43-H11.

### 1.1 Kenntnisse (Labor)

Kosten je Kenntnis überall gleich: Stufe 1 bis 5 kosten 20/28/36/44/52 AP (180 AP gesamt). Spalte „Art“: **abs** = absolute Menge, additiv. **rel** = Prozent auf einen Basiswert oder Faktor (widerspricht dem Prinzip). **pp** = Prozentpunkte auf eine Wahrscheinlichkeit, additiv.

| Kenntnis | Wirkung | Stufenwerte Lv1 bis Lv5 | Art | Prinzip-Konflikt |
|---|---|---|---|---|
| Baukunde (`construction`) | Bau-AP-Kosten sinken | 2/4/4/3/2 % | rel | ja, relativ zu den Baukosten, auf ganze AP gerundet |
| Kartographie (`cartography`) | Navigations-AP (Feld-Erkundung, Missions-Anflug) sinken | 4/8/8/6/4 % | rel | ja, bei winzigen Basiskosten (1 bis 3 AP) schon einmal ein No-Op durch Rundung |
| Geologie (`geology`) | Harvester liefert Regolith je Sol | 3/3/2/2/2 | abs | nein (bereits konform) |
| Geologie | Instabilitäts-Risiko sinkt (Faktor `chance × (1 − r)`) | 3/5/5/4/3 % | rel | ja |
| Agrarwissen (`agronomy`) | Agrardom liefert Organika je Sol | 1/2/2/1/1 | abs | nein |
| Agrarwissen | Vertrauen je Stufe (Zusatzterm der Vertrauensformel) | +2 je Stufe | abs | nein |
| Gesundheit (`health`) | Seuchen-Risiko sinkt (Faktor, gemeinsam mit Krankenstation, Deckel 50 %) | 3/5/5/4/3 % | rel | ja |
| Gesundheit | Vertrauen je Stufe | +3 je Stufe | abs | nein |
| Handel (`trade`) | Cantina-Angebotsplätze | 0/1/1/0/0 | abs | nein |
| Handel | Handelsvorteil-Preisbonus auf alle drei Kanäle | 2/3/3/2/2 % | rel | ja (Preisprozente, additiv in einer Summe mit Konsul-Rang und Handelsposten, aber relativ zum Preis) |
| Handel | Verhandlungs-Erfolgschance | 1/2/2/2/1 pp | pp | nein |
| Verteidigung (`defense`) | Sturm-Risiko sinkt (Faktor) | 3/5/5/4/3 % | rel | ja |
| Verteidigung | Vertrauen je Stufe | +2 je Stufe | abs | nein |
| alle Kenntnisse | Missions-Erfolgschance je Stufe über dem Missions-Tor | +3 pp je Stufe | pp | nein |
| alle Kenntnisse | Vertrauens-Ereignis je Stufe | +2 einmalig | abs | nein |

### 1.2 Figuren (Cantina)

| Figur / Mechanik | Heutige Wirkung | Art | Hängt am Gesprächslevel? |
|---|---|---|---|
| Tomas, Barkeeper (`bartender`) | Beim Reden Bonus-AP in eine Kenntnis, Staffel nach Zahl der Gespräche (0/5/15/30 Gespräche ergibt 0/1/2/3 AP, `game.bartender.ap_bonus_tiers`), je Sol einmal, kostet keine AP | abs | **nein**, eigener Zähler (`interaction_count`) |
| Alle 15 Figuren | Gesprächslevel 0 bis 5, AP-Investition 4/6/8/10/12 (40 AP), Deckel aus Cantina-Stufe | – | ja, **aber ohne jede Wirkung** (O4 offen) |
| Anliegen-Slot (9 Figuren) | Einmalige Belohnungen: Regolith, Credits, Werkstoffe, 5 Bonus-AP, Schiff, Wette; Gutscheine 25 % (Vesper) und 15 % (Aldra) auf den nächsten Bau | abs, Gutscheine rel | nein |
| Information (Deva, Lenn) | Deva: nächster Zwischenfall halbiert (50 %), 15 Bonus-AP auf Geologie oder Gesundheit; Lenn: eine Erkundung gratis, 15 Bonus-AP auf Kartographie | abs, 50 % rel | nein |
| Zara, Voss, Orin | Zara: Wette; Voss: Auktion; Orin: keine Mechanik (`mechanic: null`, T43-H12) | – | nein |
| Kodex | Lore je Figur, 5 Einträge, spielerweit | keine Spielwirkung | nein |

### 1.3 Missionsfamilien (Hangar)

| Wirkung | Heute | Art | Konflikt |
|---|---|---|---|
| Ertrag der Familie (`yield_per_level` = 5 % je Stufe, Faktor `1 + 0,05 × Stufe`) auf alle Mengen-Belohnungen (Credits, Regolith, Werkstoffe, Organika, Forschungs-AP; nicht aufgedeckte Felder) | gleicher Faktor für alle fünf Familien, Rundung je Belohnungsteil | rel | ja |

Rundungsproblem (gerechnet, `round()` in `MissionReward::amount`): Auf eine Belohnung von 8 Forschungs-AP ergibt Stufe 1 (+5 %) 8,4 und damit **0** Zusatz; auf 10 Organika ergibt Stufe 1 den Wert 11 und Stufe 2 ebenfalls 11. Der Effekt kommt bei kleinen Mengen ungleichmäßig an, also gegen die Transparenz-Regel. Ein absoluter Zuschlag löst das von selbst.

### 1.4 Weitere multiplikative Stellen (nicht Kenntnis/Figur, aber betroffen)

- **Vertrauensbänder** (`game.trust.production_multiplier` und `ap_multiplier`): Produktion mal 0,70 bis 1,20 und AP mal 0,80 bis 1,10. Größter multiplikativer Hebel im Spiel; er multipliziert auch Geologie- und Agrarwissen-Boni.
- **Bau-Gutscheine** (Vesper, Aldra): Prozentrabatt auf den nächsten Bau.
- **Krankenstation**: Seuchen-Risiko um einen Prozentsatz je Ausbaustufe, **Labor**: Kenntnis-AP um einen Prozentsatz je Ausbaustufe, **Handelsposten**: Kanalrabatt in Prozent.
- **Schwierigkeitsfaktor der Missionen** (0,7/1,0/1,4 auf Belohnungen): ein Szenario-Faktor, kein Bonus; Vorschlag Ausnahme (Abschnitt 2).

## 2. Auswirkung des Additiv-Prinzips

### 2.1 Was dem Prinzip widerspricht und was die Umstellung bedeutet

Der Aufwand ist grob in Klein/Mittel/Groß geschätzt (**Annahme**, ohne Codeprüfung im Einzelnen). „Wertneutral“ heißt: Die Zahlen werden so gewählt, dass der Erwartungswert nahe dem heutigen liegt, damit eine Messung nicht zwei Dinge zugleich verändert.

| Nr. | Stelle | Umstellung auf absolut | Aufwand | Anmerkung |
|---|---|---|---|---|
| U1 | Hangar-Ertrag je Familienstufe | fester Zuschlag je Belohnungsart und Stufe, nach dem Schwierigkeitsfaktor addiert (Abschnitt 4) | Klein bis Mittel | `PathDepthService::yieldFactor`, `MissionReward`, Hangar-Anzeige und Texte, Tests; Muster für alles weitere |
| U2 | Baukunde: Bau-AP-Rabatt | „minus n AP je Gebäude-Stufe“ mit Untergrenze | Mittel | `ProjectBonusService`, Vorschau-Pfade (Falle: Lese-/Vorschaupfad vergessen), Labor-Ausbaustufen-Rabatt gleich mitziehen |
| U3 | Kartographie: Navigations-AP | „minus 1 AP je Stufe“ auf Erkundung und Anflug, Untergrenze 0 oder 1 | Klein bis Mittel | beseitigt das Rundungs-No-Op, passt zu den winzigen Basiskosten |
| U4 | Risiko-Senkungen (Verteidigung, Geologie, Gesundheit, Krankenstation) | **nicht sinnvoll als Prozentpunkte**: die Basis-Chancen sind winzig (Sturm 1,2 bis 2 % je Sol, Instabilität 0,15 % je Sol und Sol seit Umzug, Seuche 5 % nur bei Hunger oder Vertrauen unter −20) | Mittel | siehe 2.2: Ausnahme vorschlagen |
| U5 | Handel: Preis-Prozente (Kenntnis, Handelsposten, Konsul-Rabatt, Verhandlungsaufschlag) | „n Credits je Einheit“ statt Prozent | Groß | berührt Cantina-Preise, Nexus-Import, Händler, Sellkanal; viele Tests |
| U6 | Bau-Gutscheine (Vesper, Aldra) | „minus n AP auf den nächsten Bau“ | Klein | nur Konfiguration und Anzeige |
| U7 | Vertrauensbänder (Produktion und AP mal Faktor) | je Band feste Zu- oder Abschläge (Regolith, Organika, AP je Sol) | Groß | wirkt auf das ganze Spiel, Kopplung zu T40 (Nachwirkung von Vertrauens-Ereignissen); höchstes Balance-Risiko |

Als bereits konform gelten: Geologie-Regolith, Agrarwissen-Organika, Handel-Plätze, Verhandlungs-Prozentpunkte (die Erfolgschance liegt im mittleren Bereich), Missions-Erfolgschance (Berater-Rang und Kenntnisse), Vertrauen je Stufe, Berater-AP je Rang, Gebäude-AP-Deckel, Tomas-Bonus-AP, alle Anliegen-Belohnungen.

### 2.2 Wo das Prinzip an Grenzen stößt (Annahme, Owner-Entscheidung nötig)

- **Seltene Ereignisse.** Eine absolute Senkung um „1 Prozentpunkt“ wäre bei einer Sturm-Chance von etwa 1 bis 2 % ein Zehntel bis die Hälfte des Risikos, mit Ansatz „n Punkte“ also unbrauchbar. Außerdem ist ihre Wirkung heute ohnehin kaum sichtbar (20 % von 1,2 % sind 0,24 Prozentpunkte je Sol). **Empfehlung:** Risiko-Senkungen bleiben prozentual, werden aber als **eine additive Summe** geführt (so ist es heute schon bei Gesundheit plus Krankenstation) und nie mit einem zweiten Faktor multipliziert. Für P7 gilt: **Figuren bekommen keine Risiko-Effekte**, weil sie unsichtbar bleiben würden (Abschnitt 3).
- **Preise.** Preis-Prozente sind eine **benannte Summe** (Handelsvorteil), additiv aus mehreren Quellen. **Empfehlung:** als Ausnahme belassen, solange sie nur dort und nur additiv wirken.
- **Schwierigkeitsfaktor und Malus-Zustände** (Seuche halbiert AP, Drill-Puffer): Szenario-Faktoren, keine Boni. Ausnahme.

### 2.3 Reihenfolge-Empfehlung

**Eigener Schritt** (Nummer vergibt der project-manager), nicht Teil von P7. Begründung: P7 ist rein additiv aufbaubar und braucht die Umstellung nicht. Eine Umstellung bestehender Werte ist wertneutral zu kalibrieren und je Hebel einzeln zu messen.

1. P7 (Figuren) und U1 (Hangar-Ertrag) zusammen oder unmittelbar hintereinander: beide setzen das Muster „Quelle, Stufenwerte, Summe sichtbar“.
2. U3 und U6 (klein, rundungsanfällig beziehungsweise reine Konfiguration).
3. U2 (Baukunde), danach U5 nur falls der Owner die Preis-Ausnahme ablehnt.
4. U7 (Vertrauensbänder) zuletzt und erst nach der T40-Messung, weil sie jede Zahl im Spiel berührt.

**Zentrale Effekttabelle:** Empfehlung, die Effekte aller drei Pfade (Kenntnis, Figur, Familie) in einer Struktur zu führen (Quelle, Ziel-Größe, Stufenwerte, Art). Das beantwortet die Transparenz-Regel in Config und Anzeige gleichermaßen und ist Voraussetzung für die Summen-Aufschlüsselung (Abschnitt 6). Aufwand Mittel; als erste Teilaufgabe von P7 einplanen.

## 3. Effekt je Figur und Stufe

### 3.1 Leitlinien (Annahmen des Designers)

1. **Eine Figur, ein Effekt**, der zu Rolle und Beruf der Figur passt. Er wirkt dauerhaft, solange die Stufe im Lauf erreicht ist.
2. **Nur Größen, die der Spieler sieht und die sich absolut ausdrücken lassen:** Regolith je Sol, Organika je Sol, Credits je Sol, Bonus-AP, Missions-Erfolgschance, Vertrauen, Gebäude-Haltbarkeit. Keine seltenen Risiko-Effekte (siehe 2.2). Keine Werkstoffe (nicht lokal produzierbar, GDD §3). Kein allgemeines „plus AP je Sol“ (AP sind die Leitwährung; Tomas ist die eine, bewusst begrenzte Ausnahme).
3. **Absolute Gesamtwerte je Stufe**, additiv zu allen anderen Quellen. Die Tabellen nennen den **Gesamtwert auf Stufe n**, nicht den Zuwachs.
4. **Stufe 0 (nur getroffen) wirkt nicht.** Die Wirkung beginnt mit Stufe 1 (4 AP).
5. **Zugang ist Glück, Fortschritt nicht** (O1): Zufallsfiguren (alle außer Tomas) wirken nur, wenn sie im Lauf getroffen wurden. Das ist gewollt und im Dokument offen benannt (Frage 3).
6. **Jede Stufe soll etwas geben.** Bei grobkörnigen Größen (Regolith, Organika) steigt der Hauptwert nur an manchen Stufen. Damit auch die übrigen Stufen einen Bonus haben, wird ein **Stufenaufstiegs-Ereignis „Vertrauen +1“** je Gesprächslevel vorgeschlagen (analog zum Vertrauens-Ereignis der Kenntnisse). Annahme: das passt zur Regel „stärkstes Ereignis zählt“ der Vertrauens-Ereignisse; vor Übernahme im Code zu prüfen. Siehe Frage 4.

### 3.2 Startumfang: 7 Figuren mit Effekt

Alle 15 Figuren gleichzeitig zu bedienen verdoppelt Konfiguration, Texte, Tests und Balance-Fläche. Vorschlag: **7 Figuren im ersten Schritt** (jede mit einer eigenen, gut sichtbaren Größe), 7 weitere als Idee-Liste (3.4), Orin bleibt ohne Mechanik (T43-H12).

| Figur (Schlüssel) | Rolle | Effekt | Lv1 | Lv2 | Lv3 | Lv4 | Lv5 | Zugang |
|---|---|---|---|---|---|---|---|---|
| Tomas (`bartender`) | Barkeeper | Bonus-AP beim Reden, in eine Kenntnis | 1 | 1 | 1 | 2 | 2 | immer da |
| Fen (`prospector`) | Prospektor | Regolith je Sol (Harvester läuft) | 1 | 1 | 2 | 2 | 3 | Zufallsgast |
| Maret (`doctor`) | Kolonieärztin | Organika je Sol (Agrardom läuft; „Ernährungsberatung“) | 1 | 1 | 1 | 2 | 2 | Zufallsgast |
| Dax (`smuggler`) | Schmuggler | Credits je Sol | 2 | 4 | 6 | 8 | 10 | Zufallsgast |
| Juno (`mercenary`) | Söldnerin a. D. | Missions-Erfolgschance, Prozentpunkte | 1 | 2 | 3 | 4 | 5 | Zufallsgast |
| Sarka (`mechanic`) | Mechanikerin | Gebäude-Verschleiß je Sol sinkt, in SP | 0,02 | 0,04 | 0,06 | 0,08 | 0,10 | Zufallsgast |
| Sorel (`preacher`) | Prediger | Vertrauen (Zusatzterm der Vertrauensformel) | 1 | 2 | 3 | 4 | 5 | Zufallsgast |

(Alle Werte Platzhalter. Tomas, Fen, Maret, Dax, Juno, Sarka, Sorel.)

Begründung je Figur:

- **Tomas.** Heute zählt er Gespräche (0, 5, 15, 30), ohne Bezug zum Gesprächslevel. Vorschlag: der Gesprächslevel **ersetzt** die Gesprächs-Staffel, die Mechanik „mit Tomas reden“ bleibt (kostenlos, einmal je Sol, Bonus-AP in eine gewählte Kenntnis). Der Maximalwert liegt bewusst **unter** dem heutigen (2 statt 3), weil er der einzige Effekt ist, der AP erzeugt, und mit 40 AP Investition sehr schnell netto positiv wird (Abschnitt 5). Die Bonus-AP bleiben zweckgebunden und vom Labor-Deckel ausgenommen (wie heute). **Offene Variable:** ohne Labor wirkt er nicht (Frage 5).
- **Fen.** Das ist das Beispiel des Owners: Regolith je Sol, additiv zu Geologie. Wirkt nur bei laufendem Harvester. Annahme (vom game-developer zu prüfen): derselbe Weg wie der Geologie-Zuschlag, also zählt der Zuschlag in die Regolith-Quellenliste und folgt denselben Regeln für die Erschöpfung des Vorkommens.
- **Maret.** Ärztin, aber ein Seuchen-Effekt wäre unsichtbar (Abschnitt 2.2); „Ernährungs- und Hygieneberatung“ bringt etwas Organika je Sol, additiv zu Agrarwissen. Etwas kleiner als Fen, weil Organika höher bepreist ist (Basispreis Organika 50 gegen Regolith 25).
- **Dax.** Schwarzmarkt, Credits je Sol. Geringer Wert je Stufe, weil die Credits-Bilanz als Engpass bekannt ist (R2 Credits-Inflation).
- **Juno.** Erfahrung im Einsatz, Erfolgschance der Hangar-Missionen. Prozentpunkte auf eine mittlere Wahrscheinlichkeit sind additiv zulässig und stapeln mit Berater-Rang und Kenntnissen (die Summe wird sichtbar).
- **Sarka.** Wer Dinge am Laufen hält, lässt sie langsamer verfallen. Die Wirkung ist klein und braucht eine Übersetzung für den Spieler („Gebäude halten etwa einen Sol länger“). **Schwächster Vorschlag** der Liste; Alternative in Frage 6.
- **Sorel.** Passt thematisch am besten (Gemeinschaft, Vertrauen). Fügt sich als weiterer Term in die Vertrauensformel ein (wie die Kenntnisse).

### 3.3 Überschneidung mit Kenntnissen und Empfehlung ergänzen/ersetzen

**Empfehlung: durchgängig ergänzen, nicht ersetzen.** Kenntnisse bleiben das verlässliche Grundgerüst (immer erreichbar, planbar), Figuren sind das glücksabhängige Zusatzgerüst (nur wenn getroffen). Überschneidende Größen werden **einzeln ausgewiesen und addiert**.

| Größe | Kenntnis (Quelle 1) | Figur (Quelle 2) | Verhältnis |
|---|---|---|---|
| Regolith je Sol | Geologie bis +12 | Fen bis +3 | ergänzen; die Figur ist rund ein Viertel der Kenntnis |
| Organika je Sol | Agrarwissen bis +7 | Maret bis +2 | ergänzen |
| Credits je Sol | (keine Kenntnis) | Dax bis +10 | neue Quelle; Pfad-Credits-Lücke (R2) beachten |
| Missions-Erfolgschance | Berater-Rang 5 pp je Rang, Kenntnisse 3 pp je Stufe über dem Tor | Juno bis +5 pp | ergänzen; Gesamtdeckel 95 % bleibt |
| Vertrauen | Agrarwissen +2, Gesundheit +3, Verteidigung +2 je Stufe | Sorel bis +5 | ergänzen; Summe wird in der Vertrauensformel gezeigt |

**Hinweis zum Beispiel des Owners (Geologie +1 je Stufe):** Geologie liefert heute 3/3/2/2/2 (Summe 12) und ist damit **schon absolut und additiv**. „+1 je Stufe“ wäre ein deutlicher Abbau (Summe 5). Das ist keine Konsequenz des Prinzips, sondern eine eigene Balance-Entscheidung (Frage 2).

### 3.4 Die übrigen Figuren (Idee-Liste, kein Startumfang)

| Figur | Mögliche Wirkung | Anmerkung |
|---|---|---|
| Deva (`veteran`) | Sturm-Schaden: weniger SP-Verlust je Treffer | Sturm selten, Wirkung wenig sichtbar; erst nach Messung der Sturmhäufigkeit |
| Aldra (`founder`) | Wohnraum, +1 Kolonist ab Lv3 und ab Lv5 | stark (Wohnraum steuert Bau-Tor und Organika-Verbrauch), nur mit Balance-Messung |
| Lenn (`ai_researcher`) | Feld-Erkundung kostet 1 AP weniger ab Lv3 und Lv5 | überschneidet Kartographie; erst nach U3 |
| Vesper (`information_broker`) | Cantina-Angebote bleiben einen Sol länger ab Lv3 | überschneidet Handel (Angebots-Plätze) |
| Zara (`gambler`) | Wette: Auszahlung +Credits je Stufe | wirkt nur auf ihr eigenes Ereignis |
| Voss (`scrap_dealer`) | Auktion: Mindestgebot oder Menge +1 je Stufe | wirkt nur auf sein eigenes Ereignis |
| Die unbekannte Gestalt (`stranger`) | bewusst ohne Zahl; Wirkung bleibt Geheimnis | Gegenstück zu „alle Figuren geben einen Bonus“ (Frage 6) |
| Orin (`corporate_rep`) | keine (`mechanic: null`, T43-H12) | Entscheidung nach Beta-Feedback |

## 4. Hangar-Missionsfamilien im selben Muster

**Konsistenz:** Familien haben bereits Stufen, Kosten (6/9/12/16/20), Deckel (Hangar-Stufe), Sol-Limit und Freischaltung nach Hangar-Stufe; es fehlt nur die Umstellung der Wirkung auf absolut (U1).

**Bewertung `yield_per_level`:** relativ (5 % je Stufe), für alle Familien gleich, mit den oben beschriebenen Rundungslücken. Die Wirkung ist als Prozentsatz nicht an der Mission ablesbar. Er widerspricht Prinzip und Transparenz.

**Vorschlag (wertneutral zu 5 %, Platzhalter):** Jede Familienstufe schaltet einen **festen Zuschlag je Belohnungsart** frei; die Missionskarte zeigt die echten Zahlen („Credits 180 + 20“). Reihenfolge der Rechnung: erst Schwierigkeitsfaktor auf die Basisbelohnung, dann der Stufen-Zuschlag unverändert addiert (Annahme: sonst würde der Zuschlag vom Schwierigkeitsfaktor multipliziert; das wäre ein verstecktes Produkt).

| Belohnungsart | Zuschlag je Stufe, Gesamtwert auf Lv1/2/3/4/5 | Vergleich heute (5 %) |
|---|---|---|
| Credits | +10/+20/+30/+40/+50 | bei Basis 180 etwa +9 je Stufe |
| Organika | 0/+1/+1/+2/+2 | bei Basis 10 etwa +0,5 je Stufe, gerundet unstetig |
| Werkstoffe | 0/0/+1/+1/+2 | bei Basis 6 bis 10 etwa +0,3 bis +0,5 |
| Regolith (Beute-Lose) | +2/+4/+6/+8/+10 | bei Basis 30 bis 45 etwa +1,5 bis 2,3 je Stufe |
| Forschungs-AP | 0/+1/+1/+2/+2 | bei Basis 8 etwa +0,4, Stufe 1 ohne Wirkung |
| Aufgedeckte Felder | unverändert | war schon nicht skaliert |

Anmerkung: Die Familie „Erkundung“ erzeugt Forschungs-AP und Felder, ihr Zuschlag ist daher klein; ihren Nutzen sieht man eher über die Freischaltung (Fernexpedition). Das ist ein bekanntes Ungleichgewicht der heutigen Konfiguration, kein Ergebnis dieser Spec.

## 5. Balance-Überlegung (Annahmen, nicht gemessen)

### 5.1 Budgets (gerechnet)

- AP-Kosten: eine Figur 40 AP gesamt (4/6/8/10/12), 15 Figuren 600 AP. Eine Familie 63 AP, fünf Familien 315 AP. Sieben Kenntnisse 1260 AP. **Voller Ausbau aller drei Pfade: 2175 AP.**
- AP-Einnahme: Grundwert 12 AP je Sol (`game.ap.base`) plus Berater-Rang (2/3/4 AP je Berater). Mit drei Beratern auf Rang 2 etwa 21 AP je Sol. Lauf bis etwa Sol 90: **grob 1500 bis 2000 AP insgesamt** (Annahme, nicht gemessen; gemessen ist nur die Phase 1). Davon fließt der größere Teil in Bauen, Missionen, Handel; **für Tiefe bleiben grob 600 bis 800 AP** (Annahme, bis 40 Prozent).
- Folge: Alles auszubauen ist unmöglich. Es bleibt eine echte Wahl (Breite gegen Tiefe, Pfad gegen Pfad). Das ist gewollt.

### 5.2 Lohnt sich das Hochstufen? (Annahme, gerechnet)

Annahme für die Rechnung: Stufe n wird im Mittel um Sol 40 erreicht, danach wirken die Effekte noch etwa 50 Sol.

| Quelle | AP-Einsatz | Ertrag | Kosten je Einheit |
|---|---|---|---|
| Geologie | 180 AP | +12 Regolith je Sol (Summe) | 15 AP je Einheit; Stufe 1 etwa 7, Stufe 5 etwa 26 |
| Fen | 40 AP | +3 Regolith je Sol | 13 AP je Einheit; Stufe 1 = 4, Stufe 5 = 12 je Zuwachsschritt |
| Dax | 40 AP | +10 Credits je Sol | 4 AP je Credit/Sol; entspricht rund 500 Credits über 50 Sol, das ist etwa eine Missionsbelohnung |
| Tomas | 40 AP | bis +2 AP je Sol | Rückfluss nach etwa 8 bis 15 Sol; **netto positiv, rund +60 AP** |

Aussagen:

1. Figuren sind im Gesamtwert ungefähr so „teuer“ wie die Geologie (13 gegen 15 AP je Einheit), in den ersten Stufen aber billiger. Das ist ein Anreiz zum Einstieg und gewollt. Es gibt aber kein „Muss“.
2. **Tomas ist der einzige AP-erzeugende Effekt** und wirkt als Hebel auf die Kenntnisse. Er ist selbst mit reduziertem Maximalwert der kosteneffizienteste Effekt der ganzen Liste. Risiko: Alle optimierenden Spieler öffnen Cantina und Labor zuerst. Dem steht Owner-Ziel „drei gleichwertige Eröffnungen“ entgegen (Frage 5).
3. Der gesamte Stapel der sieben Figuren auf Stufe 5 bringt grob +3 Regolith (zur Orientierung: Harvester mit Geologie etwa 20 bis 25 je Sol, also etwa 12 bis 15 Prozent), +2 Organika (etwa 3 Prozent), +10 Credits (Einnahme etwa 100 bis 150 je Sol, also 7 bis 10 Prozent), +5 pp Missions-Erfolg, +5 Vertrauen. Dafür sind 280 AP nötig. **Spürbar, aber nicht spielentscheidend.** Das trifft die Vorgabe „kleine Boni“; zugleich sind Organika und Verschleiß einzeln nur schwach wahrnehmbar.
4. Gesamtstapel in Bezug auf die Siegquote: Die Aussage des Owners „ohne Stufen nicht zu gewinnen“ ist durch die Figuren **nicht** erfüllt; sie ergibt sich (wenn überhaupt) aus Kenntnissen und Aufgabenzielen. Wenn sie gelten soll, muss die Basisschwierigkeit mit allen Boni kalibriert werden, nicht umgekehrt (Messansatz unten).

### 5.3 Risiken

- **Zu stark:** Tomas (AP), Dax (Credits-Inflation R2), Stapel mit Berater-Rang und Vertrauensband-Faktor (der Faktor multipliziert die Zuschläge; solange U7 offen ist, ist Fens +1 mehr als +1). Gegenmaßnahme: kleine Werte, Sol-Deckel der Gespräche (existiert), Messung vor Freigabe.
- **Zu schwach:** Organika +1 bis +2 und Verschleiß 0,02 sind in einer Summe von 20 bis 70 kaum zu erkennen; dann entsteht kein Anreiz. Gegenmaßnahme: Anzeige der Summe, Aufstiegs-Meldung, Stufenaufstiegs-Ereignis (Vertrauen +1).
- **Ungleiche Verteilung durch Zugangsglück:** Wer Fen nicht trifft, hat kein Regolith-Plus. Wirkt wie Startkarten-Variation (vom Owner gewünscht), ist aber messbar zu prüfen.
- **Doppelter Nutzen Gebäude plus Figur:** Cantina-Stufe deckelt Figuren (Stufe 3 braucht Cantina 2, Stufe 5 braucht Cantina 3); das bindet Regolith und AP in das Gebäude. Eingerechnet sind die Gebäudekosten oben nicht.

### 5.4 Messansatz (nur beschrieben, kein Batch)

Vor jeder Messung Rückfrage beim Owner mit Dauerschätzung (Pflicht). Einen Hebel je Variante:

1. **Zugang:** Wie oft wird welche Figur im Lauf getroffen (Anteil der Läufe mit Fen, Maret, …)? Vorbedingung, damit die Erwartung der Boni überhaupt stimmt.
2. **Bot-Verhalten als offene Variable:** Der Bot kennt die Investition in Gesprächslevel und Training (laut Pfad-Spec) nicht. Ohne Regel „Figur kennenlernen“ misst ein Batch keinen Effekt. Entweder Bot-Regel (z. B. Profil mit festem Anteil der freien AP in Gespräche) oder Handtest.
3. **A/B je Effekt:** gleiche Seeds (mindestens 6 bis 10), Variante „Effekte aus“ gegen „Effekte an“; Zielgrößen: Phase-1-Ende (unverändert erwartet), Regolith-Kurve, Credits-Bilanz, Siegquote, Anteil investierter AP.
4. **Messdaten anfordern:** AP-Gesamtbudget je Lauf und AP-Verbrauch nach Verwendung (nur Phase 1 liegt aus `tools/phase1-budget.php` vor). Ohne diese Zahl bleiben alle Rechnungen in 5.1 und 5.2 Annahmen.

## 6. Anzeige und Transparenz

**Grundsatz:** Jede Zahl, die wirkt, steht irgendwo genau so; jede Summe zeigt ihre Quellen einzeln.

1. **Figuren-Panel (Cantina).** Je Figur: Balken und Stufe (bereits da), darunter eine Zeile „Jetzt: +2 Regolith je Sol · nächste Stufe: +3 (benötigt n AP)“ nach dem Muster der Hangar-Trainingsanzeige (`ui_training_effect_now/next`). Figuren ohne Treffer zeigen „noch nicht getroffen“ ohne Zahl. Kurzbeschreibung der Wirkung steht schon in Stufe 0 sichtbar, damit der Spieler **vor** dem Investieren weiß, was ihn erwartet.
2. **Ressourcenleiste und Sol-Bericht.** Die Aufschlüsselung je Sol existiert bereits für Regolith (Harvester, Geologie) und Organika (Agrardom, Agrarwissen) und speist Ressourcenleiste und Sol-Bericht (`ResourceFlowService`). Neue Quellen erscheinen dort als eigene Zeilen: „Regolith je Sol: Harvester 10 · Geologie +6 · Fen +2“. Für Credits und Vertrauen braucht es dieselbe Aufschlüsselung (heute unvollständig; Aufwand Klein bis Mittel).
3. **Missions- und Hangar-Anzeige.** Erfolgschance wird als Summe mit allen Quellen gezeigt („Basis · Berater-Rang · Kenntnis · Juno“). Missionskarten zeigen den echten Ertrag mit Stufe (U1).
4. **Aufstiegs-Meldung** beim Gesprächslevel: Name, neuer Wert der Wirkung, gleiche Form wie bei Kenntnissen.
5. **Datenarchiv-Querverweis.** Das Figuren-Panel zeigt neben der Wirkung den Archiv-Stand („Archiv: 3 von 5 Einträgen“). Der Archiv-Eintrag selbst enthält **keine Zahlen zur Wirkung** (reine Lore), damit Spielwerte nur an einer Stelle gepflegt werden.
6. **Texte**: neue Schlüssel in `lang/*` (content-writer); Glossar-Eintrag „Figurenwirkung“ (ein Satz, keine Zahlen).

## 7. Zusammenspiel mit dem Datenarchiv

Regel laut Entwurf: Archiv-Eintrag n öffnet bei **Gesprächslevel n (im Lauf erreicht) und zuvor beendeten Läufen über der Schwelle**. Die Wirkung gilt **je Lauf**, der Eintrag **spielerweit**.

**Kein struktureller Konflikt**, wenn man beides als zwei getrennte Dinge erklärt: Der Gesprächslevel ist Lauf-Stand mit Spielwirkung; der Eintrag ist Dauer-Stand ohne Spielwirkung. Einzelne Reibungen, alle mit Lösung:

1. **Stufe erreicht, Eintrag noch zu.** Im ersten Lauf erreicht der Spieler vielleicht Stufe 5, der Eintrag 4 und 5 bleibt wegen der Lauf-Schwelle verschlossen. Lösung: Panel zeigt „Eintrag öffnet nach weiteren beendeten Läufen“ (ohne Zahl, wie im Entwurf), die Wirkung ist davon unabhängig.
2. **Figur nicht getroffen.** Ohne Treffer gibt es im Lauf weder Wirkung noch Eintrag. Das ist hinzunehmen, die Lauf-Schwelle liefert über mehrere Läufe weitere Chancen.
3. **Spoiler.** Der Eintrag soll die Wirkungszahl nicht verraten; die Zahl steht im Panel (6.1).
4. **Tomas hat zwei Zähler.** Der Gesprächszähler (`interaction_count`, laufgebunden) und das Gesprächslevel. Vorschlag: der Zähler entfällt als Wirkungsquelle, bleibt bestenfalls als Statistik. Siehe Frage 5.
5. **Zahl der Stufen.** Fünf Gesprächslevel entsprechen den fünf Kodex-Einträgen je Figur (`character_codex.entries_per_character`); das passt ohne Anpassung.

## 8. Offene Owner-Fragen

**Frage 1: Gilt das Additiv-Prinzip auch für kleine Wahrscheinlichkeiten und für Preise?**
Sturm, Instabilität und Seuche haben winzige Grundchancen (grob 0,15 bis 5 Prozent je Sol). Eine feste Senkung um Prozentpunkte würde sie auf null drücken oder unsichtbar bleiben; die heutigen Kurven sind relative Senkungen, die als eine Summe addiert werden. Preise werden ebenso als Prozent-Summe aus Kenntnis, Handelsposten und Konsul addiert. Eine Umstellung auf feste Zahlen wäre bei Preisen aufwendig (Groß) und bei Risiken kaum sinnvoll. **Empfehlung:** Prinzip auslegen als „keine Faktoren auf Faktoren; mehrere Quellen derselben Größe werden zu einer sichtbaren Summe addiert“; Risiken und Preise bleiben als Prozent-Summen erlaubt. Figuren bekommen keine Risiko-Effekte.

**Frage 2: Soll die Geologie auf „+1 je Stufe“ gekürzt werden?**
Geologie liefert heute 3/3/2/2/2 Regolith je Sol (Summe 12) und ist damit bereits absolut und additiv. Dein Beispiel „+1 je Stufe“ wäre ein Abbau auf Summe 5 und damit eine Balance-Entscheidung, die den Regolith-Verlauf und das Phase-1-Tempo verändert. Für die Figuren-Zahlen ist die Antwort wichtig, weil Fen als „ein Viertel der Geologie“ gedacht ist. **Empfehlung:** Geologie unverändert lassen. Soll sie doch sinken, den Hebel als eigenen Schritt mit Messung durchführen und Fens Werte danach neu festlegen.

**Frage 3: Dürfen Figuren-Boni vom Zufall des Auftauchens abhängen?**
Alle Figuren außer Tomas erscheinen zufällig; ohne Treffer gibt es keinen Bonus (Entscheidung O1: Zugang ist Glück, Fortschritt nicht). Das passt zur gewünschten Startkarten-Variation, bedeutet aber, dass ein Lauf ohne Fen kein Regolith-Plus hat und Läufe unterschiedlich leicht werden. Wir haben nicht gemessen, wie oft jede Figur im Lauf auftaucht. **Empfehlung:** Zugang als Glück beibehalten, die Auftrittshäufigkeit messen (ohne Bot-Batch erst per Zählung im Handtest), und erst danach über Stammgäste oder eine Cantina-Stufe als Auftritts-Hilfe entscheiden.

**Frage 4: Soll jede Stufe einen Bonus geben, auch bei grobkörnigen Größen?**
Bei Regolith und Organika lassen sich nur ganze Zahlen vergeben, deshalb steigen Fen und Maret nicht an jeder Stufe. Du möchtest, dass das Hochstufen immer ein Anreiz ist. **Empfehlung:** ein kleines einheitliches Stufenaufstiegs-Ereignis „Vertrauen +1“ je Gesprächslevel (ähnlich dem Vertrauens-Ereignis +2 bei Kenntnissen). Das gibt jeder Stufe etwas, kostet keine Sonderlogik je Figur, und das Vertrauen ist ohnehin eine knappe Zielgröße. Alternative: feinere Werte (Credits-Beigabe), mehr Konfiguration.

**Frage 5: Soll Tomas’ Gesprächs-Staffel durch das Gesprächslevel ersetzt werden, und wie stark darf er sein?**
Heute zählt Tomas Gespräche (0, 5, 15, 30) und gibt bis 3 Bonus-AP in eine Kenntnis, unabhängig vom Gesprächslevel. Er ist der einzige Effekt, der AP erzeugt, und er braucht das Labor; mit Gesprächslevel-Investition (40 AP) wäre er in wenigen Sol netto positiv. Das widerspricht deinem Ziel „drei gleichwertige Eröffnungen“ (Cantina-First ohne Labor hätte von ihm nichts). **Empfehlung:** Gesprächslevel ersetzt den Zähler, Maximum 2 statt 3, zweckgebunden und Labor-Deckel-frei wie heute; ohne Labor sammeln die Bonus-AP nicht, deshalb Hinweistext im Panel. Die Alternative „Tomas gibt Credits statt AP“ wäre ungefährlicher, aber weniger spürbar.

**Frage 6: Startumfang 7 oder 15 Figuren, und wie behandeln wir Orin und die unbekannte Gestalt?**
Sieben Figuren mit Wirkung sind gut testbar; die übrigen sieben haben entweder schwache Größen (Deva, Sturm ist selten), starke (Aldra, Wohnraum), überschneidende (Lenn, Vesper) oder nur eigene Ereignisse (Zara, Voss). Orin hat bis zum Beta-Feedback keine Mechanik, die Gestalt soll ihr Geheimnis behalten. Sarkas Wirkung (Verschleiß 0,02 je Stufe) ist die schwächste der sieben und wäre notfalls zu ersetzen, z. B. durch eine Reparatur-Wirkung. **Empfehlung:** erst die sieben bauen, messen, dann Aldra, Lenn, Vesper nachziehen; Orin und die Gestalt ohne Zahl lassen und im Panel offen so benennen („Diese Figur gibt nichts außer Geschichten“), damit „alles gibt Boni“ nicht heimlich verletzt wirkt.

**Frage 7: Ist die Umstellung auf additiv ein eigener Schritt, und wann kommt Vertrauen (U7)?**
Sieben Stellen (U1 bis U7) widersprechen dem Prinzip (Abschnitt 2); die Hangar-Umstellung (U1) und die kleinen (U3, U6) sind schnell erledigt, Baukunde (U2) ist mittel, Preise (U5) und Vertrauensbänder (U7) groß. Die Vertrauensbänder multiplizieren jede Quelle und beeinflussen damit auch die Wirkung der neuen Figuren-Boni; sie hängen außerdem an der offenen T40-Messung. **Empfehlung:** eigener Schritt; P7 und U1 zuerst, U7 zuletzt und nur nach der T40-Messung. Die Frage der Reihenfolge entscheidet der Owner, die Nummernvergabe der project-manager.

**Frage 8: Dürfen die Credits-Wirkung von Dax (und die Credits aus Missionen) so wachsen?**
Das Credits-Gleichgewicht ist ein bekannter Engpass (R2 Credits-Inflation, Pfad-Credits-Lücke). Dax bringt bis +10 Credits je Sol bei 40 AP, das sind etwa 7 bis 10 Prozent der Einnahmen, die gesamte Hangar-Umstellung (U1) bringt nochmal bis +50 Credits je Mission. Beides zusammen kann das Credits-Gleichgewicht kippen. **Empfehlung:** Dax im ersten Schritt mit der Hälfte der Werte freigeben, Messung der Credits-Bilanz vor dem Vollwert; keine Stufen-Boni auf Credits in zwei Pfaden zugleich ohne Messung.

**Frage 9: Soll der Bot die Gespräche kennen, bevor wir messen?**
Ohne Bot-Regel misst ein Batch nichts (Abschnitt 5.4); mit einer Regel hängt das Ergebnis stark an ihrer Wahl (wie viele AP in welche Figur, wann). Du hast festgelegt, dass die Bot-Siegquote von etwa 50 Prozent kein Balance-Problem ist; für die Messung der Wirkungen braucht es trotzdem eine plausible Regel. **Empfehlung:** erst Handtest mit Cantina-First, danach eine einfache Bot-Regel („wenn Figur getroffen und AP übrig, kurz sprechen“) als eigenes Profil, und eine Messung mit Dauerschätzung nur nach deiner Freigabe.

## 9. Nicht Teil dieser Spec

- Konkrete Endwerte (nach Messung, nur Config).
- Beziehungs-Sinken, Konkurrenz zwischen Figuren (spätere Idee).
- Umsetzung der Umstellung U1 bis U7 (eigener Schritt, Abschnitt 2.3).
- Datenarchiv-Texte und Anlass-Liste (eigener Arbeitsstrang, Abschnitt 7 nur Schnittstelle).
- ROADMAP-Pflege (project-manager), GDD-Übernahme (nach Owner-Entscheidungen, zahlenfrei).
