# Design-Spec: Nachwirkung von Vertrauens-Ereignissen

Stand: 2026-10-23 · Autor: game-designer · Status: **Entwurf, wartet auf Owner-Entscheidung**
Bezug: GDD §14 "Einflussfaktoren: Ereignisse", `config/game.php → trust`, `app/Services/TrustService.php`, ADR 0004 (Zahlen-Scope).

> **Kennzeichnung.** Alle Zahlen in diesem Dokument sind **Beispiele** zur Veranschaulichung, keine Vorschläge für Config-Werte. Konkrete Werte kommen erst nach Messung (Skill `nouron-balance-pass`). Eigene Rechnungen sind als **Annahme (gerechnet)** markiert. Gemessene Werte liegen für dieses Thema **nicht** vor.

## 1. Ausgangslage und Owner-Wunsch

Owner (2026-10-23): Vertrauensänderungen sollen nicht nur einen Sol gelten, sondern **träge über mehrere Sole wirken und langsam auslaufen**.

Ist-Stand (aus dem Code, `TrustService`):

- Ein Ereignis ist eine Zeile in `trust_events` (Kolonie, Sol, Typ). `eventContribution()` liest nur Zeilen mit `tick == aktueller Sol`.
- Mehrere Ereignisse **desselben Typs im selben Sol** stapeln nicht, das stärkste (nach Betrag) gewinnt. Verschiedene Typen addieren sich.
- Vertrauen = Gebäude + Kenntnis + Schiffe (gedeckelt) + Ereignisse + Hungerabzug + Überkapazitätsabzug, geklemmt auf -100..+100, **jeden Sol neu berechnet**, nicht akkumuliert.
- Hunger- und Überkapazitätsabzug sind bereits "träge": sie eskalieren über Streaks und enden, wenn die Ursache endet. Sie sind **keine** Ereignisse.
- Das Vertrauen steuert Produktions- und AP-Multiplikator (fünf Bänder) und löst bei Unterschreiten der Fail-Schwelle (`run.trust_fail_threshold`) **sofort** das Run-Ende aus. Wichtig: die Fail-Schwelle liegt nur knapp unter dem Band "Unruhig" (beginnt eine Stufe darunter). Wenig Spielraum nach unten.
- Das Vertrauens-Popup im Kommandozentrum zeigt die letzten 5 Ereignisse mit ihrem **Config-Delta** (`CommandCenterController`, Zeile ~113).

## 2. Design-Absicht der Nachwirkung

1. **Gefühl:** Ein Erfolg (Handel, Gebäudestufe) soll nachklingen, ein Rückschlag (Begegnung verloren) soll eine Weile nachhängen. Stimmung einer Kolonie springt nicht, sie schwingt aus.
2. **Wirkung bleibt lesbar:** Das Modell darf die Regel "neu berechnen statt akkumulieren" (GDD §14) nicht aufgeben. Der Spieler soll jederzeit sagen können, *welches* Ereignis *wie viel* gerade beiträgt und *wann es endet*.
3. **Kein stiller Balance-Umbau:** Nachwirkung ist ein Verstärker. Ohne Gegenmaßnahme erhöht sie die Gesamtwirkung von Ereignissen (siehe 4.3). Das muss ein bewusster, gemessener Hebel sein, kein Nebeneffekt.

## 3. Modell-Varianten

Gemeinsame Begriffe: **Auslösesol** e = Sol, in dem die Zeile zu wirken beginnt (heutiges `trust_events.tick`). **Alter** a = t - e für den berechneten Sol t. Ein **Profil** ist eine Liste von Prozentanteilen je Alter (a = 0, 1, 2 ...).

### Variante A: Stufenprofil über N Sole (zeilenbasiert)

Der Beitrag einer Zeile im Sol t ist `delta × Profil[a]`, pro Zeile ganzzahlig gerundet. Beispiel-Profil "Nachklang": `[100 %, 50 %, 25 %]`. Nach dem letzten Eintrag endet die Wirkung.

- **Vorteile:** Deterministisch aus den vorhandenen Zeilen rekonstruierbar, kein Zustand. Jede Zeile einzeln anzeigbar. Verlauf **im Voraus berechenbar** (Vorschau "+2, dann +1, dann +1, dann weg"). Das Verhalten "heute" ist der Sonderfall Profil `[100 %]`. Profile pro Ereignis-Typ wählbar. Kurven (linear, schnell abfallend, langer Schwanz) sind durch die Listenwerte frei formbar.
- **Nachteile:** Rundung bei kleinen Deltas (Werte sind meist 1 bis 5): ein Beispiel-Wert "+1 × 25 %" rundet auf 0 oder 1. Rundungsregel muss festgelegt werden (siehe 7). Endliche, harte Länge statt weichem Auslaufen (bei ganzzahligem Vertrauen ohnehin nicht sichtbar).

### Variante B: Exponentieller Zerfall (Halbwertszeit pro Zeile)

Beitrag einer Zeile im Alter a ist `delta × 0.5^(a / Halbwertszeit)`, Wirkung endet, sobald der gerundete Wert 0 ist.

- **Vorteile:** Ein einziger Parameter, "natürlich" wirkend.
- **Nachteile:** Rundung wie A, nur versteckt: die Länge der Wirkung ergibt sich indirekt aus dem Delta (großes Delta wirkt länger als kleines), schwer zu erklären. Für den Spieler weniger greifbar ("Halbwertszeit" ist Fachsprache). Lässt sich vollständig durch ein Profil in A nachbilden, bringt also keine zusätzliche Gestaltungsfreiheit.

### Variante C: Gleitender Ereignis-Pegel (Zustandsspeicher)

Pro Kolonie ein gespeicherter Wert: `pegel = pegel × Abklingfaktor + neue Deltas`; der Pegel geht als Ereignis-Beitrag in die Summe ein.

- **Vorteile:** Sehr einfach in der Berechnung, natürliche Trägheit auch beim Anstieg.
- **Nachteile:** Braucht neuen Zustand (Spalte in `glx_colonies`), bricht die Regel "neu berechnet statt akkumuliert", ist nicht aus `trust_events` rekonstruierbar (Debugging, Snapshots, Fixtures, `game:reset-player`). Quellen sind nicht mehr einzeln zeigbar, "angezeigte Zahl = wirkende Zahl" ist nur als eine Summe erfüllbar. Verletzt damit das Transparenz-Prinzip des Owners.

### Empfehlung

**Variante A.** Sie erfüllt den Owner-Wunsch, braucht kein neues Schema, bleibt transparent und lässt sich sicher einführen (Standardprofil `[100 %]` = heutiges Verhalten). B und C werden nicht weiterverfolgt.

## 4. Regeln im Detail (Variante A)

### 4.1 Interaktion mit "stärkstes gewinnt, kein Stapeln"

Die bestehende Regel bleibt **unverändert und gilt weiter pro Typ und Auslösesol**: Mehrere Zeilen desselben Typs mit gleichem `tick` werden zu einer Zeile (die mit dem größten Betrag). Danach wird das Profil auf die verbleibende Zeile angewendet.

Neu: Ereignisse **gleichen Typs aus verschiedenen Solen** sind verschiedene Zeilen und **addieren sich**, solange ihre Nachwirkungen überlappen. Das ist beabsichtigt: zwei Gebäudestufen in zwei aufeinanderfolgenden Solen klingen beide nach. Die Regel "kein Stapeln" schützt weiter vor dem Fall "5 Dinge im selben Sol", nicht vor "etwas in jedem Sol".

Der **Leitstellen-Abschlag** (negative Ereignisse, `securityHub`) wird auf den Delta **vor** dem Profil angewendet. Er wirkt damit auf die ganze Nachwirkung, auch auf die Spitze.

**Deckel (Sicherheitsnetz).** Der Gesamt-Ereignisbeitrag (Summe aller Zeilen) wird auf `[-Ereignisdeckel, +Ereignisdeckel]` begrenzt (Beispiel: ±10). Zweck: die Stapelung über Sole kann den Ereignisanteil sonst über das hinaus schieben, was heute je möglich war. Ohne Deckel wären Rückschläge in Folge (Begegnung verloren, Nexus-Kredit, Kolonisten gegangen) in Kombination mit Hunger- und Überkapazitätsabzug eine Fail-Falle (siehe 6.1). Der Deckel gilt **nur** für den Ereignisanteil, nicht für Dauer- oder Streak-Beiträge.

### 4.2 Was bekommt eine Nachwirkung, was nicht

| Quelle | Nachwirkung? | Begründung |
|---|---|---|
| Einmalige Ereignisse (building_level_up, research_level_up, trade_success, encounter_won/lost, colony_threatened, nexus_credit, story_concern_*, colonists_left/dismissed) | **Ja** | Genau der Fall, den der Owner meint. |
| well_fed | **Nein (Profil `[100 %]`)** | Siehe 4.3. |
| Stipend (small/medium/large) | Ja, mit Sonderregel | Siehe 4.4. |
| Gebäude-, Kenntnis-, Schiffsbeitrag | Nein | Sind bereits Dauerzustände. |
| Hunger-/Überkapazitätsabzug | Nein | Eskalieren bereits über Streaks, würden sich doppeln. |
| trade_blocked, treaty_signed | Egal (derzeit nie ausgelöst) | Erhalten das Standardprofil mit, falls später aktiv. |

### 4.3 Wiederholbare Ereignisse: well_fed

**Annahme (gerechnet):** `well_fed` wird in einer gut versorgten Kolonie **jeden Sol** ausgelöst. Es ist damit faktisch ein Dauerbonus in Höhe seines Deltas. Mit einem Nachwirkungsprofil mit Gewichtssumme S wirkt im eingeschwungenen Zustand `delta × S`. Beispiel: Delta 2, Profil `[100, 50, 25]` (S = 1.75) ergibt ca. +3.5 statt +2 dauerhaft, also einen versteckten Dauerbonus von rund +1.5 bei jeder gut versorgten Kolonie. Das verschiebt Bandgrenzen und Produktions-/AP-Multiplikator für **alle** Läufe, ohne dass der Spieler etwas anders macht.

**Empfehlung:** well_fed bleibt beim Sofort-Profil. Begründung neben der Zahlenfolge: das Ereignis ist im Spiel bereits als "Zustand" gerahmt (Versorgung gesichert), sein Gegenstück Hunger ist ebenfalls zustandsbasiert. Eine Nachwirkung würde den Übergang "Hunger beginnt" verschleiern (die Kolonie fühlt sich noch drei Sole satt). Wer das Gegenteil will, muss Delta oder Profil so anpassen, dass `delta × S` dem heutigen Dauerniveau entspricht.

Allgemeine Regel für jedes häufig wiederkehrende Ereignis: das **eingeschwungene Niveau** `Auslösungen pro Sol × delta × S` ist die relevante Größe, nicht das Einzel-Delta.

### 4.4 Stipend (Kolonisten-Zulage)

Die Zulage ist der **Rettungsanker** (GDD §14, §18.2): sie muss im ersten Sol nach dem Kauf **voll** wirken (Profil beginnt mit 100 %), sonst wird sie als Krisenhebel wertlos. Die Nachwirkung ist dann ein Bonus obendrauf.

Sonderfälle:

- **Preis-Leistung:** Die Stufen sind bewusst degressiv bepreist (Credits pro Vertrauenspunkt steigen mit der Stufe). Mit Nachwirkung ändert sich das *Integral* (Gesamtwirkung über alle Sole) und damit die Preislogik. **Annahme (gerechnet):** Bei Profil mit S = 1.75 wird jede Stufe um den Faktor 1.75 "billiger" pro Gesamtpunkt, die Degression bleibt erhalten. Ob die Zulage dadurch zum Standardmittel wird (Balance-Concern in GDD §14), ist **offene Variable** und gehört in die Messung. Alternativ bekommt das Stipend ein eigenes, kürzeres Profil oder S = 1 (Integral gleich, Spitze niedriger).
- **Täglicher Kauf:** Die "eine Stufe pro Sol"-Sperre bleibt (sie prüft pro Auslösesol, `hasEventThisTick`). Ohne Mehr-Sol-Cooldown **stapeln die Nachwirkungen** bei täglichem Kauf (im eingeschwungenen Zustand `delta × S`). Das verschärft den bereits dokumentierten Balance-Concern "Vertrauen auf Knopfdruck". Der Ereignisdeckel (4.1) begrenzt das Problem. Ein zusätzlicher Cooldown ist eine Option, wird hier aber nicht vorgeschlagen (ein Hebel pro Variante).
- **Anzeige am Kaufknopf:** Das heutige "+X Vertrauen" muss die tatsächliche Wirkung zeigen (Verlauf, siehe 5), nicht nur das Delta.

## 5. Spieler-Transparenz (Vertrauens-Popup)

Prinzip des Owners: angezeigte Zahl = wirkende Zahl, Quellen einzeln und additiv. Das heutige Popup zeigt das Config-Delta der letzten 5 Ereignisse. Mit Nachwirkung wäre das **falsch** (es würde +2 zeigen, während heute +1 wirkt). Vorschlag:

1. **Block "Laufende Nachwirkungen"** im Vertrauens-Popup, eine Zeile pro wirkende Ereignis-Zeile:
   - Name des Ereignisses (bestehende `trust.event_*`-Texte) und Auslösesol,
   - **wirkt jetzt:** gerundeter Beitrag im aktuellen Sol (z. B. "+1"),
   - **Verlauf:** die noch folgenden Werte (z. B. "dann +1, dann endet in 2 Solen"). Die Vorschau ist exakt, weil sie aus Zeile und Profil deterministisch folgt.
2. **Summenzeile** "Ereignisse gesamt: +X". Falls der Deckel greift, eine Zeile "begrenzt auf ±Deckel" damit die Differenz erklärt ist.
3. **Getrennt davon** die Dauerbeiträge (Gebäude, Kenntnisse, Schiffe) und Abzüge (Hunger, Überkapazität) wie bisher, jeweils mit eigenem Wert. Gesamtsumme = Summe der gezeigten Zeilen (vor dem Klemmen auf -100..+100).
4. **Rundung:** Jede Zeile wird **einzeln** ganzzahlig gerundet; die Summe ist die Summe der gezeigten Ganzzahlen. So stimmen Zeilen und Summe immer überein. Zeilen mit gerundetem Wert 0 werden ausgeblendet.
5. **Sol-Bericht:** Der Vertrauens-Sprung eines Sols sollte als "Ursache: neu +A, Nachwirkung +B" lesbar sein (Anpassung `SolReportService`, falls dort Vertrauensdeltas aufgeschlüsselt werden; im Detail zu prüfen).

Die Anzeige braucht dafür **dieselbe Berechnungsfunktion** wie `calculateTrust` (eine Quelle der Wahrheit, z. B. eine öffentliche Methode, die Zeilen mit Wirkung zurückgibt, und `eventContribution` summiert diese). Nie zwei parallele Berechnungen.

## 6. Risiken

### 6.1 Fail-State-Spirale

Der Instant-Fail knapp unter dem Band "Unruhig" macht negative Nachwirkung gefährlich: derselbe Rückschlag wirkt länger, und der Spieler hat weniger Sole, ihn auszugleichen.

**Annahme (gerechnet):** Beispiel-Profil `[100, 50, 25]`, ein `nexus_credit` (Delta -5) zieht im Verlauf -5, -3 (2.5 aufgerundet), -1. Folgt im nächsten Sol ein `encounter_lost` (-4), liegt der Ereignisanteil bei -7 im Folgesol statt -4. Treffen Hunger (bis -8) und Überkapazität (bis -4) hinzu, ist der Weg zur Fail-Schwelle kürzer als heute. Das ist **kein** Fehler des Modells, sondern ein Balance-Hebel, aber er ist asymmetrisch gefährlich, weil der Fail endgültig ist.

Gegenmaßnahmen (alle optional, aufsteigend nach Eingriffstiefe):

1. Ereignisdeckel (4.1), schützt gegen Aufschichtung.
2. Negative Ereignisse bekommen ein **kürzeres** Profil als positive (z. B. 2 statt 3 Sole). Leicht zu begründen ("Schlechtes spricht sich schnell herum, Besserung braucht Zeit" lässt sich auch umgekehrt erzählen, das ist eine Tonfrage für den Owner).
3. Die Fail-Prüfung nutzt nur die **Dauerkomponente** plus Spitze. Nicht empfohlen: würde "angezeigte Zahl = wirkende Zahl" brechen.
4. Der Nexus-Warnhinweis (`trust_warning`) läuft weiter auf dem Gesamtwert, damit der Spieler vor dem Fail reagieren kann (Stipend hilft sofort, siehe 4.4).

### 6.2 Pacing und Bot-Messung

Vertrauen steuert Produktion und AP, also **Phase-1-Pacing**. Nachwirkung darf das Pacing nicht verschieben ohne Messung.

- **Annahme (nicht gemessen):** In Phase 1 liegt das Vertrauen überwiegend im Band "Stabil" (Multiplikator 1.00). Dann bewegt Nachwirkung Produktion und AP nur, wenn sie das Vertrauen über die obere Bandgrenze "Zufrieden" hebt. Die Häufigkeit dieses Übertritts ist unbekannt.
- **Bot-Abhängigkeit (offene Variable):** Ereignishäufigkeit hängt vom Bot-Verhalten ab: Anzahl Ausbauschritte (building_level_up), Forschung, Begegnungen, ob und wann der Bot ein Stipend kauft oder Schiffe auf Nexus-Kredit nimmt. Ein Bot, der selten Stipend kauft, unterschätzt dessen Nachwirkungsfolgen.
- **Messplan (wenn der Owner zustimmt):** Eine Änderung pro Messung. Hebel = "Profil für alle Ereignisse außer well_fed von `[100]` auf Beispiel `[100, 50, 25]`". Vergleich vor/nach mit identischen Seeds (Seed-Determinismus seit PR #333) und ausreichender Stichprobe (mindestens 6-10 Seeds, besser mehr). Messgrößen: Anteil der Sole je Vertrauensband, minimales Vertrauen pro Lauf, Rate `trust_collapse`, Phase-1-Ende in Sol (via `php tools/phase1-budget.php --since=...`), Credits-Verlauf wegen Stipend. Hypothese zur Widerlegung: "Phase-1-Ende verschiebt sich um höchstens 1 Sol und `trust_collapse` steigt nicht." Die Siegquote des Bots ist kein Optimierungsziel (Owner-Entscheidung 2026-10-02).
- **Vor jedem Batch** Dauer schätzen und beim Owner nachfragen (nicht selbst starten).

### 6.3 Aufwand und Schema

- **Schema:** `trust_events` reicht (Kolonie, `tick` = Auslösesol, `event_type`). Das Alter ergibt sich aus `aktueller Sol - tick`. **Keine Migration**, keine neue Spalte. Index `(colony_id, tick)` deckt die Bereichsabfrage `tick BETWEEN t-(N-1) AND t` ab.
- **Config:** neue Struktur `trust.event_profiles` (benannte Profile als Prozentlisten) und `trust.event_profile` (Zuordnung Ereignis-Typ zu Profil, Standard = Sofort `[100]`), plus `trust.event_cap`. `trust.events` (Deltas) bleibt unverändert. Config-Kommentar "active for exactly 1 tick" ist anzupassen.
- **Code (später, nicht Teil dieser Spec):** `TrustService::eventContribution` (Bereichsabfrage, Profil, Rundung, Deckel), Popup-Daten in `CommandCenterController`, ggf. `SolReportService`. TDD-Pflicht: Tests laufen über `calculateTrust`, da `eventContribution` privat ist.
- **Szenarien / Fixtures:** `game:reset-player` löscht `trust_events` bereits; Szenario `near-fail-trust` (Vertrauen -15) prüfen, ob ältere Ereigniszeilen jetzt hineinragen. `docs/game-reference.md` §14 und GDD §14 ("genau 1 Sol", Implementierungsnotiz "2 Sole") nach Umsetzung nachziehen.
- **Laufende Läufe:** Alte Zeilen werden rückwirkend mitbewertet, wenn die Funktion eingeschaltet wird. Unkritisch, solange der Standard `[100]` ist.
- **Retention:** Zeilen werden nicht gelöscht; die Abfrage bleibt durch den Index günstig.

## 7. Offene technische Detailfrage: Rundung

Beitrag je Zeile = `round(delta × Prozent / 100)`, gerundet **von der Null weg bei .5**. Konsequenz: Kleine Deltas (Betrag 1) behalten bei Anteilen ab 50 % ihren Wert 1, darunter endet die Wirkung dort. Beispiel (Annahme, gerechnet), Profil `[100, 50, 25]`:

| Delta | Verlauf (gerundet) | Summe über alle Sole | Faktor ggü. heute |
|---|---|---|---|
| +1 | +1, +1, 0 | 2 | 2.0 |
| +2 | +2, +1, +1 | 4 | 2.0 |
| +3 | +3, +2, +1 | 6 | 2.0 |
| -5 | -5, -3, -1 | -9 | 1.8 |

Die Rundung verstärkt kleine Deltas also eher. Wenn der Owner die Gesamtwirkung gleich halten will ("Summe bleibt, nur verteilt"), muss die Verteilung statt Prozentgewichten eine **Aufteilung der Summe** sein (z. B. +2 als +1, +1, 0; +5 als -2, -2, -1), die Spitze sinkt dann deutlich. Das ist die Entscheidung in Frage 1 unten.

## 8. Empfohlenes Vorgehen

1. Owner entscheidet die Grundsatzfragen unten.
2. Umsetzung hinter Profil `[100]` als Standard (verhaltensgleich), Anzeige im Popup zuerst, damit Spieler und Entwickler dieselbe Zahl sehen.
3. Einschalten des Profils für alle Ereignisse außer well_fed als **ein** Messschritt (Abschnitt 6.2).
4. Danach getrennt (je ein Hebel): Stipend-Preise/Profil, Profil-Länge negativ vs. positiv, Deckelhöhe.
5. GDD §14 und `docs/game-reference.md` §14 nach der Messung zahlenfrei bzw. mit Werten nachziehen.

## Rückfragen an den Owner

1. **Gesamtwirkung oder Spitze erhalten?** Soll ein Ereignis nach Einführung (a) dieselbe *Gesamtsumme* haben, nur verteilt (Spitze sinkt, sicherer fürs Fail-Risiko) oder (b) seine *volle Spitze* behalten und zusätzlich nachklingen (Gesamtwirkung steigt, etwa Faktor 2 bei Kleinwerten)? **Empfehlung: (b)**, weil ein Rückschlag sofort spürbar bleiben soll und die Mehrwirkung messbar ist; Begleitmaßnahme Ereignisdeckel.
2. **Länge der Nachwirkung:** Beispiel 3 Sole (volle Wirkung, dann abfallend). Oder kürzer/länger? **Empfehlung:** 3 Sole als Messstart, endgültige Länge nach Messung.
3. **Gleiche Länge für Gutes und Schlechtes?** **Empfehlung:** zunächst gleich, weil ein Hebel pro Messung; negativ kürzen erst, wenn die Messung `trust_collapse` ansteigen zeigt.
4. **well_fed ausnehmen** (bleibt 1 Sol, weil täglich wiederholt und sonst ein versteckter Dauerbonus)? **Empfehlung: ja.**
5. **Ereignisdeckel** für den Gesamt-Ereignisbeitrag (Beispiel ±10) als Fail-Sicherheitsnetz? **Empfehlung: ja**, Höhe nach Messung.
6. **Stipend:** soll es dieselbe Nachwirkung bekommen (Spitze im ersten Sol bleibt voll), und soll die Preis-Leistung danach neu geprüft werden? **Empfehlung: ja**, mit Prüfung in der Messung; der Mehr-Sol-Cooldown bleibt vorerst draußen.
7. **Dauereffekte glätten?** Soll auch der *Wegfall* eines Vertrauensgebäudes (z. B. durch Verfall) träge auslaufen statt sofort? **Empfehlung: nein**, eigene Spec falls gewünscht (anderer Mechanismus, kein Ereignis).
8. **Anzeige:** Block "Laufende Nachwirkungen" mit Verlauf-Vorschau im Vertrauens-Popup wie in Abschnitt 5, jede Zeile einzeln gerundet? **Empfehlung: ja.**
9. **Messfreigabe:** Darf ich nach Umsetzung (hinter Standardprofil) einen Bot-Batch mit Dauerabschätzung vorschlagen, der Pacing, Bandverteilung und `trust_collapse` vergleicht? **Empfehlung: ja**, 24 Läufe mit `--concurrency=24` (geschätzt wenige Minuten, Bestätigung vor dem Start).

## Owner-Entscheidungen (2026-10-23)

Alle Empfehlungen der Rückfragen 1–9 übernommen, mit einer Änderung:

- **Länge der Nachwirkung (Frage 2): 5 Sole als Messstart** statt 3. (Die Rechenbeispiele im Text mit 3 Solen sind entsprechend nur Beispiele; das 5-Sol-Profil und seine Rundung sind vor der Umsetzung neu zu rechnen, insbesondere Summe der Gewichte und der Deckel.)
- Spitze bleibt erhalten, die Mehrwirkung wird gemessen (Frage 1).
- Gutes und Schlechtes gleich lang; negativ kürzen erst, wenn `trust_collapse` steigt (Frage 3).
- `well_fed` bleibt beim 1-Sol-Profil (Frage 4).
- Deckel für den Ereignisbeitrag: ja, Höhe nach Messung (Frage 5).
- Stipend bekommt dieselbe Nachwirkung, volle Wirkung im ersten Sol, Preis-Leistung später prüfen (Frage 6).
- Wegfall von Vertrauensgebäuden läuft nicht träge aus (Frage 7).
- Popup mit Block „Laufende Nachwirkungen“ samt Verlauf-Vorschau, jede Zeile einzeln gerundet (Frage 8).
- Bot-Batch nach der Umsetzung nur nach Rückfrage mit Dauerabschätzung (Frage 9, Memory-Regel).

