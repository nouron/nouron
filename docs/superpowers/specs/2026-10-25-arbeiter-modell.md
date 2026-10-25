# Arbeiter-Modell: Gebäude ohne Kolonisten bauen, Arbeiter getrennt zuweisen?

Stand 2026-10-25 · Autor: game-designer · Status: entschieden (Option A2)
Bezug: Owner-Frage vom 2026-10-25; Ergebnis bestätigt die A14-Empfehlung in `docs/superpowers/specs/2026-10-25-kolonisten-modell.md`. Berührt GDD §6 („Supply als Bau-Gate“, „Überkapazität — Konsequenzen“), §11.5 (Rückbau), §13 (Berater/Slots), `docs/game-reference.md` §10 und §10a.

Im Text steht **Fakt** für Nachgelesenes (Code, GDD, Config), **Annahme** und **Empfehlung** für meine Deutung. Es stehen keine Balancewerte darin (ADR 0004).

## Entschieden (Owner 2026-10-25)

- **Option A2 wird übernommen, wie empfohlen.** Wohnraumverlust (zum Beispiel ein Wohnhabitat sinkt, Rückbau) senkt die Kolonisten sofort. Unbesetzte Arbeitsplätze mindern die Rohstoffproduktion anteilig. Das Bau-Gate heißt künftig „genug freie Kolonisten“ und sperrt Neubau und Ausbau bis zur Erholung. Der Rückbau-Dialog warnt vorab. Obdachlosen-Frist, Vertrauens-Malus-Streak, Abwanderung und „Wegschicken“ entfallen.
- **Das Wort „Arbeiter“ wird nicht eingeführt.** Es bleibt bei „Kolonisten“.
- **Das Arbeiter-Modell (Option B und C) wird nicht verfolgt.** Nach der Closed Beta neu prüfen, falls Spieler an der Sperre selbst scheitern und nicht an ihrer Anzeige.
- Die Folgen für Code und Anzeige stehen als Liste am Ende dieser Datei.

## Kurzfassung (Stand vor der Entscheidung)

Der Gedanke ist sinnvoll, weil er das Problem „Wohnraum geht verloren, aber die Gebäude sind schon gezählt“ von der Wurzel her löst. Er verkompliziert das Spiel aber insgesamt: Eine harte, leicht erklärbare Regel („bauen geht nur, wenn genug freie Kolonisten da sind“) würde durch eine weiche Regel mit vielen Folgen ersetzt („bauen geht immer, die Wirkung folgt der Besetzung“), und für jedes Gebäude müsste neu festgelegt werden, was „aktiv“ heißt. Ich empfehle, in Version 1 beim heutigen Modell zu bleiben und nur die **Regel für Wohnraumverlust klar zu machen**. Das Arbeiter-Modell bleibt eine gute Idee für nach der Closed Beta, falls der Playtest zeigt, dass das Bau-Gate selbst frustriert und nicht nur seine Anzeige.

## (a) Fakt: wie es heute ist

- **Arbeitsplätze:** Jedes Gebäude belegt je Ausbaustufe eine feste Zahl Plätze (Platzbedarf, im Spiel „KOL“ am Gebäude). Platzierte Gebäude auf Stufe 0 reservieren schon einen Platz. Die Summe sind die Arbeitsplätze der Kolonie.
- **Bau-Gate:** Platzieren und jeder weitere Ausbau verlangen, dass der freie Wohnraum (Wohnraum minus Arbeitsplätze) den Platzbedarf deckt. Ausgenommen sind Kommandozentrale, Wohnhabitat und der erste Harvester. Der Spieler kann sich also normalerweise nicht über den Wohnraum hinaus bauen. Laut GDD ist Supply die einzige Regel, die die **Tiefe** (hohe Stufen weniger Gebäude) begrenzt; Breite begrenzen Bauplatz und Instandhaltung.
- **Wohnraumverlust heute (A14):** Verliert ein Wohnhabitat eine Stufe (Verfall, Sturm, Rückbau), liegen die Arbeitsplätze über dem Wohnraum. Die Überzähligen heißen „obdachlos“. Es läuft eine Frist mit eskalierendem Vertrauens-Malus; danach wandern alle Obdachlosen ab, und ihre Arbeitsplätze bleiben leer (Unterbesetzung). Mit freiem Wohnraum kehren sie automatisch zurück. „Wegschicken“ kostet AP und löst die Abwanderung sofort aus. Der Rückbau-Dialog warnt vorher (§11.5).
- **Wirkt Unterbesetzung heute?** Ja, aber nur auf die **Rohstoffproduktion** (Regolith und Organika): Die Erträge werden gleichmäßig mit dem Besetzungsanteil multipliziert. Auf Wirkungen anderer Gebäude (Labor, Hangar, Cantina, Krankenstation und so weiter) hat sie keinen Einfluss. Zusätzlich sperrt das Bau-Gate Neubau und Ausbau, solange Arbeitsplätze unbesetzt sind (Ausnahme: Wohnraum-Gebäude, damit der Spieler den Weg heraus hat).
- **Stufenverlust eines Gebäudes** nimmt unbesetzte Arbeitsplätze still mit (sie verschwinden zusammen mit der Stufe).
- **Berater sind etwas anderes:** Berater (Baumeister, Analytiker, Raumfahrer, Konsul, Stratege) sind Personal mit Rang, Slots und Credits-Unterhalt und brauchen keinen Platz. Sie liefern AP. Arbeiter wären Kolonisten an Gebäuden. Um Verwechslung zu vermeiden, empfehle ich (Annahme), im Spiel weiter von **Kolonisten** zu sprechen und „Arbeiter“ nicht als zweiten Personalbegriff einzuführen.
- **Kolonisten-Modell (entschieden am selben Tag):** Kolonisten ergeben sich aus dem Wohnraum, beschäftigt ist der Teil an Arbeitsplätzen; alle Kolonisten werden verpflegt.

## (b) Optionen

### Option A: Heutiges Modell, dazu eine klare Regel für Wohnraumverlust (kleinste Änderung)

Bau-Gate und Arbeitsplätze bleiben. Neu ist eine eindeutige, gut sichtbare Regel für den Verlustfall. Zwei Ausprägungen:
- **A1: Code-Stand behalten** (Obdachlosen-Frist, Malus, Abwanderung, „Wegschicken“). Vorteil: nichts zu bauen, die Frist federt Schäden ab. Nachteil: Mit „Kolonisten gleich Wohnraum“ gibt es keine Kolonisten über dem Wohnraum mehr; die Frist bräuchte sonst einen gespeicherten Zusatzzustand und die Anzeige „Obdachlose“ widerspricht dem neuen Kolonisten-Begriff.
- **A2: sofort und deterministisch** (Annahme): Wohnraumverlust verringert die Kolonisten im selben Moment (symmetrisch zum sofortigen Gewinn beim Bauen). Die Arbeitsplätze, die dadurch unbesetzt sind, mindern die Rohstoffproduktion anteilig (bestehender Mechanismus); Pfadgebäude bleiben wirksam, wie heute. Das Bau-Gate sperrt Neubau und Ausbau, bis wieder genug freie Kolonisten da sind. Ein einmaliges Vertrauens-Ereignis markiert den Verlust. Frist, Malus-Streak, Abwanderung und „Wegschicken“ entfallen; der Rückbau-Dialog nennt vorab „n Arbeitsplätze unbesetzt“.
- **Spieler sieht:** „N Kolonisten, davon M beschäftigt“, bei Unterbesetzung zusätzlich „n Arbeitsplätze unbesetzt, Produktion gemindert“. Eine Zahl, eine Wirkung.
- **Vorteil:** Eine einzige harte Regel; angezeigte Zahl gleich wirkende Zahl; die Frust-Ursache (gesperrte Gebäude) wird ohnehin über T43-C8 („Grund erkennbar“) angegangen. Der Wohnraumverlust bleibt ein Schadensfall, auf den der Spieler reagieren kann (Verfall ist über die Zustandspunkte vorhersehbar, Annahme).
- **Nachteil:** Das Problem „gebaut, aber leer“ bleibt für die Ausnahmegebäude bestehen. A2 nimmt die abfedernde Frist weg (Risiko einer Verfalls-Kaskade, das GDD schon als Balance-Sorge nennt).
- **Aufwand:** A1 keiner, A2 klein bis mittel (A14 vereinfachen, Texte, Tests). **Messschritt:** für die Regel selbst nein (bestehende Mechanik); die ohnehin offene Organika-Messung bleibt.

### Option B: Owner-Modell mit automatischer Verteilung

Gebäude kosten beim Bauen **keine** Kolonisten mehr; die Zonengröße (Bauplätze) begrenzt die Zahl weiterhin. Die vorhandenen Kolonisten verteilen sich automatisch auf die Arbeitsplätze der gebauten Gebäude; wie aktiv ein Gebäude ist, hängt von seiner Besetzung ab.
- **Offene Teilfragen (je Annahme):**
  - **Verteilung:** gleichmäßig anteilig (entspricht dem heutigen Besetzungsanteil, einfach zu erklären) oder nach fester Rangfolge (zum Beispiel Nahrung zuerst; wirkt „klug“, ist aber eine versteckte Regel).
  - **„Inaktiv“:** stufenlos (Wirkung proportional) oder alles-oder-nichts (unter einer Mindestbesetzung aus). Alles-oder-nichts ist verständlicher („das Labor steht still“), aber härter und kaskadenanfälliger.
  - **Für jedes Gebäude neu zu klären, was „aktiv“ heißt.** Heute wirkt Unterbesetzung nur auf Rohstoffe. Für Labor, Hangar, Cantina, Krankenstation, Depot, Tempel und Denkmal gäbe es keine Definition (zum Beispiel: sinken Kenntnis-Fortschritt, Missionen, Gesprächstakt?). Das sind mehrere neue Regeln, nicht eine.
- **Zusammenspiel:** AP-Deckel (T35) und die AP-Kosten bleiben unberührt. Die Zonengröße ist weiter die Breiten-Bremse. Die **Tiefen-Bremse** (Supply als Bau-Gate) entfällt; ob die Stufen-Gate-Regel (Gebäudestufe höchstens Kommandozentralen-Stufe) sie ersetzen kann, ist offen. A14 (Obdachlose, Frist, Abwanderung) verschwindet vollständig und wird durch Besetzung ersetzt. Kolonisten-Verpflegung bleibt unverändert.
- **Spieler sieht:** Pro Gebäude „Besetzung 3 von 6“ (Beispiel, keine Balancewerte) und die Wirkung („läuft mit halber Leistung“), in der Kopfleiste „N Kolonisten, davon M beschäftigt“, in der Kommandozentrale eine Liste der Gebäude nach Besetzung.
- **Lore und Roguelike:** Passt zu „Gebäude steht, Betrieb braucht Leute“ und macht Wohnraumverlust durch Sturm oder Verfall dramatisch, ohne einen Sonderzustand wie Obdachlose. Spannung: ein Fehler beim Bauen führt nicht zur Sperre, sondern zu einem schleichenden Leistungsverlust (schwerer lesbar).
- **Nachteil:** Ersetzt eine harte durch eine weiche Regel, bringt ein „Aktiv“-Konzept für alle Gebäude, nimmt die Tiefen-Bremse und verlangt neue Anzeigen. Das ist das Gegenteil von „Regeln vereinfachen statt stapeln“.
- **Aufwand:** Groß. **Messschritt:** Ja, groß (Tempo von Phase 1 und 2, Tiefe gegen Breite, Organika); Bot-Batch nur nach Rückfrage und Dauerschätzung. **Bot-Abhängigkeit:** Der Bot baut heute mit Rücksicht auf das Gate; seine Regeln müssten neu geschrieben werden.

### Option C: Owner-Modell mit manueller Zuweisung je Gebäude

Wie B, aber der Spieler verteilt die Kolonisten selbst (Regler oder Klick je Gebäude), ähnlich der Energieverteilung in FTL.
- **Vorteil:** Echte Entscheidung ohne Optimalpfad (was bekommt Leute, was steht still); passt zur Inspiration FTL und Catan.
- **Nachteil:** Klickaufwand jeden Sol (oder nach jedem Wohnraumverlust), Verwaltungsspiel statt Aufbau, Regeln für Zuweisungskosten (kostenlos oder AP?), Frage, was bei neuen Gebäuden automatisch passiert. Alle Nachteile von B kommen hinzu. Passt schlecht zu „kleine Kolonie, ein Run in kurzer Zeit“.
- **Aufwand:** Sehr groß (UI, Regeln, Bot). **Messschritt:** Ja.

## (c) Empfehlung

**Option A2**, also das heutige Modell mit sofortiger, klar angezeigter Regel für Wohnraumverlust: Die Kolonisten gehen im selben Moment, in dem der Wohnraum geht; unbesetzte Arbeitsplätze mindern die Rohstoffproduktion anteilig; das Bau-Gate sperrt bis zur Erholung; der Rückbau-Dialog warnt vorher. Begründung zur Frage „verkompliziert es das Spiel?“: Das Arbeiter-Modell tut es, weil es zwei harte Dinge (Platz bei Bau, Wirkung unabhängig von Besetzung) in ein weiches Geflecht aus Besetzung, Aktivität und Verteilung verwandelt und für jedes Gebäude eine eigene „aktiv“-Regel verlangt. Das heutige Modell kennt bereits alle nötigen Bausteine (Arbeitsplätze, Besetzungsanteil, Gate), sie müssen nur einheitlich und sichtbar werden. Das Problem des Owners („Kolonisten gehen, Gebäude waren gezählt“) ist damit gelöst, ohne eine zweite Zuweisungsebene.

**Wenn Ja (A2):** Eigener Auftrag für den game-developer (Test zuerst), GDD §6 „Überkapazität“ vereinfachen, `docs/game-reference.md` §10a nachziehen. **Wenn Nein, B oder C:** Eigene Spezifikation, Definition von „aktiv“ je Gebäude, danach Messschritt mit Skill `nouron-balance-pass`; Option B wäre der bessere Einstieg als C. **Nach der Beta neu prüfen,** wenn der Playtest zeigt, dass Spieler an der Sperre selbst scheitern, nicht an ihrer Anzeige.

## Entscheidungsübersicht

| Frage | Empfehlung | Owner-Entscheidung |
|---|---|---|
| Arbeiter-Modell (Gebäude ohne Kolonisten bauen, Arbeiter zuweisen) einführen? | Nein für Version 1; später prüfen, falls das Gate selbst frustriert | Entschieden: nein, nach der Beta ggf. neu prüfen (2026-10-25) |
| Regel bei Wohnraumverlust | A2: Kolonisten sofort weniger, unbesetzte Arbeitsplätze mindern Rohstoffproduktion, Gate sperrt bis zur Erholung, Rückbau-Dialog warnt; Frist und Abwanderung entfallen | Entschieden: A2 (2026-10-25) |
| Begriff „Arbeiter“ im Spiel | Nicht einführen (Verwechslung mit Beratern/Personal), bei „Kolonisten“ bleiben | Entschieden: nicht einführen (2026-10-25) |
| Messung | Für A2 keine eigene; Organika-Produktion bleibt der offene Messschritt | Wie empfohlen (2026-10-25) |

## Code- und Anzeige-Folgen von A2 (Stichpunkte für die ROADMAP)

Stand der Code-Suche (Fakt, `grep` im Arbeitsordner 2026-10-25); Umsetzung durch game-developer, Test zuerst (TDD).

- **Kernlogik:** `app/Services/OvercapService.php` (Streak, Abwanderung, Rückkehr, Wegschicken) wird ersetzt durch die Regel „Kolonisten = Wohnraum, beschäftigt = min(Kolonisten, Arbeitsplätze)“ ohne gespeicherten Zustand. Aufrufer anpassen: `app/Console/Commands/GameTick.php`, `GameTickDryRun.php`, `app/Services/ResourcesService.php`, `app/Services/SolReportService.php`, `app/Services/TrustService.php`, `app/Http/Controllers/Colony/ColonyController.php` (Wegschicken-Route und Gate), `app/Http/Controllers/CommLog/CommLogController.php`.
- **Besetzungsanteil:** Produktionsminderung läuft weiter über den Personalanteil; er wird künftig aus Kolonisten und Arbeitsplätzen abgeleitet statt aus „Abgewanderte“. Verpflegung rechnet auf alle Kolonisten (siehe Kolonisten-Modell).
- **Config entfernen (`config/game.php`):** Block `overcap` (`departure_after_sols`, `trust_base_malus`, `trust_step`, `trust_cap`, `dismiss_ap_cost`) und die Vertrauens-Ereignisse `trust.events.colonists_left` und `colonists_dismissed`. Entscheiden, ob ein einmaliges Ereignis für „Wohnraum verloren“ neu angelegt wird (Entwurf der Vorlage: ja, einmalig); Folge: Werte nur über Messung.
- **Datenbank:** Spalten `glx_colonies.overcap_departed` und `glx_colonies.overcap_streak` (Baseline-Migration `0001_01_01_000000_baseline.php`) entfernen; laut Projektregel eine Migration oder Anpassung der Baseline durch db-migration-agent, kein `migrate:fresh` gegen die Dev-DB.
- **Wegschicken:** Route, Controller-Aktion, AP-Kosten und Button entfernen (`ColonyController`, Views, JS).
- **Rückbau-Dialog (GDD §11.5):** Warnung vorab ändern von „drohende Obdachlose“ auf „n Kolonisten weniger, n Arbeitsplätze unbesetzt, Produktion gemindert, Neubau gesperrt bis zur Erholung“. Betrifft auch das Tile-Panel und die Texte in `lang/de/colony.php` (und `lang/en`).
- **Gate-Text:** Sperrgrund in Baumenü, Techtree und Gebäude-Seitenleiste auf „zu wenige freie Kolonisten“; Ausnahmen (Kommandozentrale, Wohnhabitat, erster Harvester) und die Sperre trotz unbesetzter Arbeitsplätze bleiben.
- **Anzeige:** Kopfleiste (`resources/views/resources/resourcebar.blade.php`) ohne Obdachlosen-Warnung und Fristzähler, dafür „n Arbeitsplätze unbesetzt, Produktion gemindert“; Sol-Bericht (`SolReportService`), Kolonieprotokoll (`lang/de/comm_log.php`, Einträge „abgewandert/zurückgekehrt/weggeschickt“), Dashboard-Hinweis; Vertrauens-Texte in `lang/de/trust.php` und `lang/en`.
- **Tests anpassen oder entfernen:** `tests/Feature/GameTick/OvercapDepartureTest.php`, `OvercapStreakTest.php`, `tests/Feature/Colony/OvercapDismissTest.php`, `tests/Feature/Trust/OvercapTrustPenaltyTest.php`, `tests/Feature/Resources/ResourcebarOvercapPopupTest.php`; prüfen und anpassen: `StaffingProductionTest.php`, `SolReportTest.php`, `CommLogControllerTest.php`, `ColonistStatusTest.php`, `ResourcebarColonistChipTest.php`, `GameTickDryRunTest.php`, `GameTickRunIsolationTest.php`, `BuildingLeveldownTest.php`, `SupplyBuildGateTest.php`. Neue Tests zuerst: Stufenverlust Wohnhabitat senkt Kolonisten im selben Sol, Produktion mindert sich anteilig, Gate sperrt bis zur Erholung und gibt frei, Rückbau-Dialog nennt die Folgen.
- **Doku:** `docs/game-reference.md` §10 und §10a (Überkapazität/Unterbesetzung) neu fassen, `docs/gdd/techtree.md` §11.5 (Rückbau-Dialog-Text) angleichen, `GLOSSAR.md` (Überkapazität, Obdachlos, Wegschicken streichen), Config-Kommentare, `game:reset-player`-Szenarien prüfen.
- **Bot (PlaytestBot):** liest Überkapazität/Abwanderung; Regeln prüfen, bevor gemessen wird. Kein Bot-Batch ohne Rückfrage und Dauerschätzung.
- **Balance-Hinweis (Annahme):** Ohne Frist fehlt die Abfederung bei Verfall von Wohnhabitaten; das Kaskadenrisiko im GDD bleibt als Concern stehen und gehört in den ersten Playtest.
