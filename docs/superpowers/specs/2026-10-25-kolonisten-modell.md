# Kolonisten-Modell: Wohnraum, lebende Kolonisten, Beschäftigte

Stand 2026-10-25 · Autor: game-designer · Status: Vorlage, wartet auf Owner-Entscheidung
Bezug: Owner-Wunsch vom 2026-10-25; berührt `docs/superpowers/specs/2026-10-25-t43-designfragen.md` (A2, A3), GDD §4a „Organika“, §6 „Supply“ und „Überkapazität“, `docs/game-reference.md` §10 und §10a.

Im Text steht **Fakt** für Nachgelesenes (Code, Config), **Annahme** und **Empfehlung** für meine Deutung. Beispielzahlen sind reine Veranschaulichung, keine Balancewerte (ADR 0004).

## Kurzfassung

Der Owner will drei Zahlen getrennt sehen: **Wohnraum** (wie viele Kolonisten haben Platz), **Bewohner** (wie viele Kolonisten leben wirklich in der Kolonie) und **Beschäftigte** (wie viele davon arbeiten an einem Gebäude). Heute gibt es nur zwei echte Größen, Wohnraum und Arbeitsplätze; die „lebenden Kolonisten“ sind keine eigene Größe, sondern werden aus den Arbeitsplätzen abgeleitet. Meine frühere Annahme „anwesend gleich beschäftigt“ stimmt für den Code, trifft aber nicht das, was der Owner sich vorstellt. Ich empfehle, die Bewohner zu einer **eigenen, langsam wachsenden Größe** zu machen (Zuzug bis zum Wohnraum, Beschäftigte sind der kleinere Wert aus Bewohnern und Arbeitsplätzen, alle Bewohner werden verpflegt). Das ist ein mittlerer bis großer Umbau mit Messschritt; die Kurzversion „nur Beschriftung“ löst den Wunsch nicht.

## (a) Fakt: was die Zahlen heute bedeuten

- **Wohnraum** (im Code „Kapazität“ oder Supply-Cap): fester Anteil der Kommandozentrale plus (Summe der Stufen aller Wohnhabitate) mal Wert je Stufe. Er wird im Sol-Wechsel berechnet und gespeichert; ein Deckel liegt über allem. Kenntnisse sind seit 2026-10-15 keine Quelle mehr (GDD §6).
- **Arbeitsplätze:** Summe aus Stufe mal Platzbedarf je Gebäude, dazu die Reserve für platzierte, noch nicht fertige Gebäude (Stufe 0). Der Platzbedarf heißt im Code `supply_cost`, im Spiel steht „KOL“ am Gebäude. Prüfpunkt: Der Code-Kommentar von „freies Supply“ nennt noch einen Kenntnis-Anteil, das GDD sagt seit 2026-10-15, Kenntnisse kosten keinen Platz. Ich habe nicht nachgeprüft, ob der Anteil real null ist.
- **„Anwesende Kolonisten“** (im Code `present`) ist **keine eigene Größe**: anwesend gleich Arbeitsplätze minus Abgewanderte. Gespeichert ist nur, wie viele abgewandert sind (und die Zahl der Sole mit Obdachlosen). Es gibt **kein Wachstum, keinen Zuzug und keinen Schwund** außer dieser Abwanderung. Wer ein Gebäude baut, „erzeugt“ damit im selben Moment die Kolonisten, die es besetzen.
- **Obdachlose** entstehen, wenn die Arbeitsplätze den Wohnraum übersteigen (anwesend größer als Wohnraum). Dann läuft eine Frist mit Vertrauens-Malus, danach wandern die Obdachlosen ab; ihre Arbeitsplätze bleiben leer (Produktion sinkt anteilig), und sie kommen zurück, sobald wieder Wohnraum frei ist (Überkapazität, A14). Es gibt keine Arbeitslosen: Ein Kolonist ohne Arbeitsplatz kommt im Modell nicht vor.
- **Bau-Gate:** Ein Gebäude kann nur gebaut oder ausgebaut werden, wenn freier Wohnraum (Wohnraum minus Arbeitsplätze) für seinen Platzbedarf reicht. Obdachlosigkeit entsteht deshalb nur über Ausnahmen (Kommandozentrale, Wohnhabitat, erster Harvester ungeprüft) oder wenn Wohnraum durch Verfall wegfällt.
- **Kopfleiste „KOL n / cap“:** n sind die anwesenden Kolonisten (also die besetzten Arbeitsplätze), die zweite Zahl ist der Wohnraum. Rot bei Obdachlosen, Warnfarbe bei leeren Arbeitsplätzen nach Abwanderung.
- **Sol-Bericht „:cap Kolonisten, davon :used beschäftigt“:** Hier steht der **Wohnraum** an der Stelle „Kolonisten“ und die belegten Arbeitsplätze bei „beschäftigt“. Die Beschriftung nennt den Wohnraum also fälschlich „Kolonisten“. Die beiden Anzeigen widersprechen sich: Die Kopfleiste nennt die Arbeitsplätze „Kolonisten“, der Bericht nennt den Wohnraum so.
- **Organika:** Der Bedarf richtet sich nach den anwesenden Kolonisten. Mit dem Beschluss vom 2026-10-25 (T43 A3) ist die Grundregel 1 Organika je Kolonist; welche Zahl damit gemeint ist, hängt am Modell unten.

## (b) Modelloptionen

Gemeinsame Begriffe in allen Optionen: **Wohnraum** (W), **Arbeitsplätze** (A), **Bewohner** (B, lebende Kolonisten), **Beschäftigte** (E gleich der kleinere Wert aus B und A). Es entstehen drei neue Zustände, die es heute nicht gibt: **ohne Arbeit** (B größer A) und **unbesetzte Arbeitsplätze** (A größer B). Wohnraum-Bau und Gebäude-Gate bleiben in allen Optionen unverändert (ein Hebel pro Schritt).

### Option 1: Nur ehrlich benennen (kein neues Modell)

Die Anzeige zeigt zwei Zahlen: „N Kolonisten (alle in Arbeit)“ und „Wohnraum für M“. „Beschäftigt“ entfällt, weil es nichts anderes gibt.
- **Vorteil:** Sofort, kein Balancing-Risiko, beseitigt den Widerspruch zwischen Kopfleiste und Bericht.
- **Nachteil:** Löst den Owner-Wunsch **nicht**: Es bleiben zwei Größen, die dritte (lebende, aber nicht beschäftigte Kolonisten) gibt es nicht.
- **Aufwand:** Klein. **Messschritt:** Nein. **Anzeige:** Kopfleiste „KOL n / m“ mit klarem Popup; Bericht „n Kolonisten, Wohnraum für m“.

### Option 2: Bewohner füllen den Wohnraum sofort

Bewohner gleich Wohnraum: Jeder Platz ist belegt (Annahme: Zuzug ohne Zeitverlauf). Beschäftigte sind der kleinere Wert aus Bewohnern und Arbeitsplätzen. Wer mehr Wohnraum baut, hat sofort mehr Kolonisten.
- **Vorteil:** Kein neuer Zustand im Tick, einfach zu erklären („Platz ist voll“), die drei Zahlen sind sofort getrennt. Passt zur Sofortwirkung beim Wohnhabitat (A2).
- **Nachteil:** Der Verbrauch hängt direkt am Wohnraum, nicht am Aufbau: Schon der Start mit viel freiem Wohnraum würde bei 1 Organika je Kolonist ein Vielfaches der Arbeitsplätze verpflegen. Das verschiebt die Phase-1-Balance stark (Agrardom wäre sofort Engpass). Obdachlose und Abwanderung (A14) entfielen, weil die Bewohner nie über dem Wohnraum liegen; die Vertrauens-Spirale müsste neu angebunden werden.
- **Aufwand:** Mittel (A14 umbauen, Anzeige, Tests). **Messschritt:** Ja, groß (Organika-Last, Phase-1-Tempo, A14-Ersatz).
- **Anzeige:** Kopfleiste „KOL Bewohner / Wohnraum“ (immer gleich, wenig Aussage).

### Option 3: Bewohner als eigene, langsam wachsende Größe (Empfehlung)

Die Bewohner sind ein gespeicherter Wert. Jeden Sol ziehen neue Kolonisten zu, solange Wohnraum frei ist und die Kolonie nicht hungert (Zuzug-Regel: fester kleiner Betrag je Sol, Höhe nach Messung, hier **keine Zahl**). Beschäftigte sind der kleinere Wert aus Bewohnern und Arbeitsplätzen. Verpflegt werden **alle Bewohner**.
- **Vertrag bei zu wenig Wohnraum:** Fällt Wohnraum weg (Verfall, Rückbau) und die Bewohner liegen darüber, bleibt die A14-Regel erhalten: Obdachlose, Frist, Vertrauens-Malus, Abwanderung. Es wird nur der Maßstab „anwesend“ durch die echten Bewohner ersetzt.
- **Bei zu wenig Arbeitsplätzen:** Überzählige Bewohner sind „ohne Arbeit“. Version 1 gibt dafür **keine Strafe** (sie essen, aber arbeiten nicht); eine Wirkung auf Vertrauen wäre ein späterer, eigener Schritt.
- **Bei zu wenig Bewohnern:** Unbesetzte Arbeitsplätze senken die Produktion anteilig, derselbe Mechanismus wie heute bei Abwanderung (Besetzungsanteil gleich Beschäftigte geteilt durch Arbeitsplätze).
- **Hunger:** Ohne Organika stoppt der Zuzug (Annahme: kein Schwund durch Hunger in Version 1; der Hungerverlauf aus dem Vertrauen bleibt unverändert).
- **Vorteil:** Zeigt dem Spieler genau die drei Dinge, die der Owner will; „Wohnraum bauen“ wird eine Entscheidung mit Preis (die Bewohner brauchen Organika), der Aufbau fühlt sich organisch an (Kolonie wächst, statt aus dem Nichts zu erscheinen), passend zu „kleine Kolonie am Leben erhalten“. Zuzug glättet den Organika-Sprung.
- **Nachteil:** Neuer Zustand im Tick, die Frühphase hängt an der Zuzug-Rate (Phase 1 zu langsam oder zu schnell). Unbesetzte Arbeitsplätze in den ersten Sols könnten die Produktion bremsen, bevor das Spiel das erklärt.
- **Aufwand:** Mittel bis Groß (neue Spalte, Zuzugsschritt im Tick, `colonistStatus` umbauen, Startwert der Bewohner, Szenarien in `game:reset-player`, `docs/game-reference.md` §10/§10a, Tests). **Messschritt:** Ja (Zuzug-Rate, Phase-1-Tempo, Organika-Bilanz); Bot-Batch nur nach Rückfrage und Dauerschätzung.

### Welche Zahl wird verpflegt? (Zusammenspiel mit T43 A3)

**Annahme:** Verpflegt werden **alle Bewohner**, nicht nur die Beschäftigten. Begründung: Essen hängt am Leben, nicht an der Arbeit; sonst könnte der Spieler Hunger „wegverwalten“, indem er Arbeit nur wegnimmt. Der Owner nannte bei A3 das Beispiel „4 Beschäftigte, 4 Organika“; das bleibt richtig, solange Bewohner und Beschäftigte gleich sind, also in Option 1 immer und in Option 3 früh im Lauf. Weicht der Owner hier ab, ist die Alternative „nur Beschäftigte essen“ einfacher zu erklären („wer arbeitet, isst“), macht aber Arbeitslose zu einem versteckten Gratis-Posten.
**Reihenfolge:** Den Zahlenabgleich der Organika-Produktion (A3) erst **nach** der Modellentscheidung messen, weil sich die verpflegte Zahl je Option unterscheidet (Option 1: Arbeitsplätze, Option 2: Wohnraum, Option 3: Bewohner). Sonst misst man zweimal.

### Was der Spieler sieht (Option 3)

Beispielzahlen nur zur Veranschaulichung: 12 Bewohner, 8 beschäftigt, 6 Arbeitsplätze unbesetzt hinzu wäre unstimmig; deshalb Beispiel: Wohnraum 18, Bewohner 10, Arbeitsplätze 12, Beschäftigte 10.
- **Kopfleiste:** Chip „KOL Bewohner / Wohnraum“ (hier 10 / 18). Das Popup nennt in drei Zeilen: Bewohner, davon beschäftigt (10 von 12 Arbeitsplätzen, 2 unbesetzt), freier Wohnraum, und darunter „Verpflegung: 10 Organika je Sol“. Rot bei Obdachlosen, Warnfarbe bei unbesetzten Arbeitsplätzen.
- **Sol-Bericht:** Eine Zeile „Kolonisten: 10 (+1 Zuzug), beschäftigt 10 von 12 Arbeitsplätzen; Wohnraum 18“ und die Organika-Zeile „Bedarf 10“. Das Wort „beschäftigt“ gilt nur für Arbeit, nie für Wohnraum.
- **Kommandozentrale:** Ein kleiner Block mit drei Balken (Wohnraum, Bewohner, Beschäftigte gegen Arbeitsplätze) und den Hinweis, was als Nächstes limitiert (Platz, Arbeit oder Zuzug).
- **Gebäude-Seitenleiste:** Pro Gebäude „Arbeitsplätze: n“ und der gemeinsame Besetzungsanteil der Kolonie (nicht je Gebäude, weil die Besetzung gleichmäßig wirkt); das Wohnhabitat nennt „+n Wohnraum, sofort“ (A2).

### Verhältnis zu Vertrauen, Hunger und A14

- **Hunger:** Verlauf und Malus unverändert; der Bedarf rechnet mit den Bewohnern.
- **A14:** Obdachlose, Frist und Abwanderung bleiben, nur der Vergleichswert wechselt von „Arbeitsplätzen“ zu „Bewohnern“. Weil Zuzug nur bis zum Wohnraum läuft, entstehen Obdachlose nur noch bei weggefallenem Wohnraum, nicht mehr durch Bauen über den Wohnraum hinaus.
- **Offen (nicht für Version 1):** Arbeitslose als Vertrauens-Faktor, Schwund durch Hunger, Familiennachzug.

## (c) Empfehlung

Option 3 mit der kleinsten Regelmenge: Bewohner als gespeicherte Größe, Zuzug mit fester Rate bis zum Wohnraum, Stopp bei Hunger, Beschäftigte gleich Minimum aus Bewohnern und Arbeitsplätzen, alle Bewohner essen, A14 mit Bewohnern statt Arbeitsplätzen, keine Strafe für Arbeitslose, Bau-Gate unverändert. Wenn der Umbau jetzt zu groß ist, ist Option 1 die ehrliche Übergangslösung (zwei klar benannte Zahlen), sie ersetzt die Entscheidung aber nicht. Option 2 empfehle ich nicht, weil sie die Phase-1-Balance am stärksten erschüttert.

**Wenn Ja (Option 3):** Eigener Auftrag für den game-developer (Test zuerst), dann Messschritt Zuzug-Rate, dann Zahlenabgleich der Organika-Produktion (A3). GDD §4a und §6 erst nach der Entscheidung nachziehen.
**Wenn Nein (Option 1):** Beschriftung Kopfleiste und Sol-Bericht vereinheitlichen; A3-Abgleich auf Arbeitsplätze.

## Entscheidungsübersicht

| Frage | Empfehlung | Owner-Entscheidung |
|---|---|---|
| Wie werden Wohnraum, Bewohner und Beschäftigte verankert? | Option 3: Bewohner als eigene, langsam wachsende Größe (Zuzug bis Wohnraum), Beschäftigte gleich Minimum aus Bewohnern und Arbeitsplätzen | |
| Welche Zahl wird verpflegt? | Alle Bewohner (Annahme), nicht nur Beschäftigte | |
| Strafe für Arbeitslose in Version 1? | Nein, später als eigener Schritt | |
| Reihenfolge der Messung | Modellentscheidung zuerst, dann Zuzug-Rate, dann Organika-Zahlenabgleich (A3) | |
