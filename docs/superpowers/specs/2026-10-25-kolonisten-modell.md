# Kolonisten-Modell: Wohnraum, lebende Kolonisten, Beschäftigte

Stand 2026-10-25 · Autor: game-designer · Status: entschieden (Richtung), Folgefragen A14 und Verpflegungszahl offen
Bezug: Owner-Wunsch vom 2026-10-25; berührt `docs/superpowers/specs/2026-10-25-t43-designfragen.md` (A2, A3), GDD §4a „Organika“, §6 „Supply“ und „Überkapazität“, `docs/game-reference.md` §10 und §10a.

Im Text steht **Fakt** für Nachgelesenes (Code, Config), **Annahme** und **Empfehlung** für meine Deutung. Beispielzahlen sind reine Veranschaulichung, keine Balancewerte (ADR 0004).

## Entschieden (Owner 2026-10-25)

- **Option 3 (wachsende Bewohner mit Zuzug) ist verworfen.** Gründe des Owners: nicht deterministisch, für den Spieler schwer erkennbar, ab wann wie viele Arbeitskräfte da sind, und eine kleine Kolonie soll nicht ständig wachsen.
- **Gewollt (Richtung von Option 1, deterministisch, passend zur Welt):** Ein gebautes Wohnhabitat macht eine feste Zahl neuer Kolonisten verfügbar (der Owner nannte 8 als Beispiel für den Neubau; die Zahl bleibt Config und Messung), **sofort bei Abschluss** (Neubau und Ausbau, umgesetzt als A2). Die lebenden Kolonisten ergeben sich direkt und vorhersagbar aus den Wohnhabitaten. **Beschäftigt** sind die Kolonisten, die Arbeitsplätze von Gebäuden besetzen; der Rest sind freie, unbeschäftigte Kolonisten. Kein Zuzug, kein Wachstum, kein Schwund durch Zufall.
- **Klarstellung:** Platz und Kolonisten sind damit **dieselbe Größe**. Es gibt weiter zwei Zahlen, nicht drei: Kolonisten (gleich Wohnraum) und davon beschäftigt (gleich besetzte Arbeitsplätze). Der Rest, „frei“, ist die Differenz.
- **Korrektur meiner früheren Annahme:** „anwesend gleich beschäftigt“ galt für den alten Code (die Kolonisten wurden aus den Arbeitsplätzen abgeleitet). Ab jetzt gilt: Kolonisten kommen aus dem Wohnraum, beschäftigt ist nur ein Teil davon.
- **Offen (Abschnitte unten):** Was aus der Obdachlosen-Frist (A14) wird, welche Zahl verpflegt wird, und die Liste der Folgen für Code und Anzeige.

Die folgenden Abschnitte „Kurzfassung“ bis „(c)“ sind der Stand vor der Entscheidung; maßgeblich sind dieser Block und die Abschnitte „Nach der Entscheidung“ am Ende.

## Kurzfassung (Stand vor der Entscheidung)

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

## Nach der Entscheidung

### Das Modell in einem Satz

Kolonisten gleich Wohnraum (feste Zahl aus Kommandozentrale und Wohnhabitaten, sofort bei Abschluss); davon beschäftigt gleich der kleinere Wert aus Kolonisten und Arbeitsplätzen; der Rest ist frei. Alles deterministisch.

### Anzeige: die klarste Form

Empfehlung: überall dieselbe Formulierung **„N Kolonisten, davon M beschäftigt“**. Begründung: Sie nennt nur zwei Zahlen, die der Spieler sofort versteht (wer lebt, wer arbeitet), und der Rest („frei“) folgt als Differenz, ohne dritte Zahl. „Wohnraum“ kommt nicht mehr als eigene Zahl vor, sondern nur als Wirkung des Gebäudes („+n Kolonisten“). Der Sol-Bericht-Text vom Quickwin („:cap Kolonisten, davon :used beschäftigt“) passt damit inhaltlich bereits, solange „:used“ die besetzten Arbeitsplätze meint.
- **Kopfleiste:** Chip „KOL N“, darunter oder im Popup „M beschäftigt“. Das Popup nennt: Kolonisten (aus Kommandozentrale und Wohnhabitaten), beschäftigt, frei (verfügbar für neue Gebäude), unbesetzte Arbeitsplätze (nur, wenn vorhanden), Verpflegung. Der heutige Chip zeigt „Arbeitsplätze / Wohnraum“; das wird ersetzt.
- **Sol-Bericht:** „N Kolonisten, davon M beschäftigt“ und die Organika-Zeile mit dem Bedarf.
- **Kommandozentrale:** Ein Block mit den zwei Zahlen und „frei: N−M“; bei unbesetzten Arbeitsplätzen ein Hinweis mit Ursache.
- **Gebäude-Seitenleiste:** Pro Gebäude „braucht n Kolonisten“ (Arbeitsplätze), beim Wohnhabitat „+n Kolonisten, sofort“, bei gesperrten Gebäuden der Grund „zu wenige freie Kolonisten“.

### Bau-Gate und A14 (Fakt aus dem Code, dann Folgen)

- **Fakt Bau-Gate:** Platzieren und Ausbau erfordern freien Wohnraum (Wohnraum minus Arbeitsplätze) mindestens in Höhe des Platzbedarfs. Im neuen Modell heißt das schlicht: **genug freie Kolonisten**. Das Gate passt ohne Änderung und wird verständlicher. Ausnahmen (Kommandozentrale, Wohnhabitat, erster Harvester) können Arbeitsplätze über die Kolonisten hinaus erzeugen; dann sind Arbeitsplätze unbesetzt und die Produktion sinkt anteilig (bestehender Besetzungsanteil).
- **Fakt A14 heute:** „Obdachlos“ heißt: Arbeitsplätze größer als Wohnraum; es entsteht nur, wenn Wohnraum durch Verfall, Sturm oder Rückbau wegfällt. Dann läuft eine Frist mit Vertrauens-Malus, danach wandern die Obdachlosen ab, die Arbeitsplätze bleiben leer, Rückkehr automatisch bei freiem Wohnraum; „Wegschicken“ kostet AP. Gespeichert sind nur die Zahl der Abgewanderten und der Streak.
- **Folge im neuen Modell:** Wenn Kolonisten gleich Wohnraum sind, gibt es keine „Kolonisten über dem Wohnraum“, also keine Obdachlosen im bisherigen Sinn. Fällt Wohnraum weg, sind die Kolonisten sofort weg, und die Arbeitsplätze sind unbesetzt.
- **Annahme (Empfehlung, Owner-Entscheidung nötig):** A14 wird vereinfacht: Wohnraumverlust verringert die Kolonisten sofort und deterministisch; die Wirkung ist die schon vorhandene anteilige Produktionsminderung durch unbesetzte Arbeitsplätze, das Bau-Gate sperrt Neubau, bis wieder genug freie Kolonisten da sind, und ein einmaliges Vertrauens-Ereignis markiert den Verlust. Frist, Obdachlosen-Malus, Abwanderung und „Wegschicken“ entfielen. Alternative: Obdachlosen-Frist als Abfederung behalten; dann müssten Kolonisten als eigene gespeicherte Größe geführt werden (Rückschritt zu Option 3 im Kleinen) und der Owner-Wunsch nach „direkt aus den Wohnhabitaten“ wäre verletzt. Bis zur Entscheidung gilt der Code-Stand.

### Welche Zahl wird verpflegt? (Annahme)

Heute: Bedarf aus den besetzten Arbeitsplätzen (abzüglich Abgewanderter). Mit „1 Organika je Kolonist“ gibt es zwei Lesarten.
- **Alle Kolonisten (gleich Wohnraum).** Annahme und Empfehlung. Vorteil: Passt wörtlich zur Grundregel, ist in einem Satz erklärt („jeder Kolonist isst“), und Wohnraum bekommt einen Preis (mehr Kolonisten, mehr Futter), was „kleine, ressourcenarme Kolonie“ trägt. Nachteil: Der Verbrauch steigt **stark**, weil der Grundstock der Kommandozentrale und jedes Wohnhabitat sofort mitessen, auch wenn noch wenige Gebäude arbeiten. Fakt zur Größenordnung: Die Produktionskurve des Agrardoms liegt in der Config auf deutlich kleineren Werten als der Wohnraum zu Beginn; ohne Messung ist das ein Indiz, kein Beweis (Annahme: Phase 1 wäre mit der heutigen Produktion nicht tragfähig).
- **Nur beschäftigte Kolonisten.** Vorteil: Verbrauch bleibt nah am heutigen Wert (heute zählen schon die Arbeitsplätze), Organika wächst mit dem Aufbau, freier Wohnraum ist kostenlos. Nachteil: Freie Kolonisten essen nicht — erklärungsbedürftig („warum essen die Arbeitslosen nichts?“), und Hunger lässt sich durch Entlassen nicht, durch Wohnraum nur fehlerhaft lenken.
- **Folge für die Balance:** In beiden Fällen ein Messschritt nach Skill `nouron-balance-pass`, ein Hebel pro Messung: zuerst Produktion des Agrardoms, nur falls das nicht reicht der Grundstock der Kommandozentrale oder die Zahl je Wohnhabitat. Kein Bot-Batch ohne Rückfrage und Dauerschätzung. Zahlenabgleich offen; im GDD steht nur die Regel.

### Code- und Anzeige-Folgen (Stichpunkte für die ROADMAP, G7/G8)

- Kolonisten-Zahl aus dem Wohnraum ableiten (nicht mehr aus den Arbeitsplätzen); eine zentrale Stelle im Ressourcen-Dienst, die Kolonisten, beschäftigt, frei und unbesetzte Arbeitsplätze liefert.
- Beschäftigt gleich der kleinere Wert aus Kolonisten und Arbeitsplätzen; Besetzungsanteil für die Produktion daraus.
- Sofortige Neuberechnung des Wohnraums beim Abschluss eines Wohnhabitats (A2) für alle Anzeigen und das Bau-Gate prüfen.
- Verpflegung auf die Kolonisten umstellen (Config-Regel 1 je Kolonist, Teiler `supply_per_eater` entfällt oder wird 1); Abrundungsloch entfällt.
- A14: Entscheidung abwarten; je nach Ausgang Frist, Streak-Spalten, Abwanderung, „Wegschicken“ und zugehörige Texte, Ereignisse und Tests entfernen oder anpassen.
- Kopfleisten-Chip, Popup, Sol-Bericht-Zeile, Kommandozentrale und Gebäude-Seitenleiste auf „N Kolonisten, davon M beschäftigt“ umstellen; Texte in `lang/de` (Kolonisten, nicht Wohnraum, nicht „Supply“).
- Config-Kommentare und Schlüssel mit „Supply“ und „Wohnraum“ bereinigen; Prüfpunkt: restlicher Kenntnis-Anteil im „freien Supply“ (laut GDD seit 2026-10-15 nicht mehr vorhanden).
- Szenarien in `game:reset-player` (Ressourcenwerte, Wohnraum-Herleitung) und `docs/game-reference.md` §10 und §10a nachziehen; Onboarding-Hinweise, die Wohnraum oder Arbeitsplätze nennen, prüfen.
- Bot (PlaytestBot) liest Wohnraum und Arbeitsplätze; Regeln prüfen, bevor gemessen wird.
- Glossar: Zeile „Kolonisten (KOL)“ („kein eigenes Bevölkerungssystem“) und „Supply-Kappe“ anpassen, sobald umgesetzt.

## Entscheidungsübersicht

| Frage | Empfehlung | Owner-Entscheidung |
|---|---|---|
| Wie werden Wohnraum, Kolonisten und Beschäftigte verankert? | Kolonisten gleich Wohnraum, beschäftigt gleich Minimum aus Kolonisten und Arbeitsplätzen, Rest frei; kein Zuzug | Entschieden (Option 3 verworfen) |
| Anzeige | „N Kolonisten, davon M beschäftigt“ überall | Offen |
| Welche Zahl wird verpflegt? | Alle Kolonisten (Annahme), Messschritt bestätigt | Offen |
| A14 (Obdachlosen-Frist) | Vereinfachen: Wohnraumverlust senkt Kolonisten sofort, unbesetzte Arbeitsplätze mindern die Produktion | Offen |
| Reihenfolge der Messung | Modell umsetzen, dann Organika-Produktion abgleichen (A3), ein Hebel pro Messung | Offen |
