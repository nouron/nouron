# Tomas' Rat — Rückmeldung an den Spieler, stille Forschung

Stand: 2026-11-11 · Autor: game-designer · Status: **entschieden 2026-11-11** (Owner: „ja alles so wie vorgeschlagen“ zu allen fünf Fragen). Umsetzbar; offene Annahmen sind unten einzeln gekennzeichnet.

Anlass: Im Playtest hielt der Owner die Meldung „Tomas hat dir für heute schon einen Rat mitgegeben …“ für einen Fehler und vermutete das fehlende Labor als Ursache. Owner-Wunsch (sinngemäß): *Kenntnisse sollen im Hintergrund immer aktiv sein und hier durchaus eine Wirkung erzielen — aber ohne Labor kann man nicht aktiv daran forschen und den Fortschritt nicht gut einsehen.*

Verwandt: `2026-11-11-p7-figuren-stufenwirkung.md` (Frage 5: Gesprächslevel ersetzt Tomas' Gesprächszähler), `2026-11-06-labor-vorschau.md` (wird in einem Punkt geändert, s. §1), GDD §4b „Cantina“, §10, §12 „Barkeeper“, `docs/gdd/techtree.md` §11.4.

---

## 0. Befund im Code (gemessen, nicht gerechnet)

| # | Befund | Quelle |
|---|---|---|
| B1 | Gespräch einmal je Sol; der zweite Versuch scheitert erst **nach** dem Klick mit Fehlermeldung. Das Popup kennt den Zustand vorher nicht. | `app/Services/BarService.php:1439-1441`, `cantina/+page.svelte` |
| B2 | Die ersten Gespräche bringen keinen Bonus (Staffel nach Gesprächszahl, `game.bartender.ap_bonus_tiers`). Der Spieler sieht nur eine Tomas-Zeile, keine Zahl, keinen Hinweis, wann es mehr wird. | `config/game.php:715-720`, `BarService.php:1468-1480` |
| B3 | Bonus-AP landen ohne Labor-Prüfung im Fortschritt der gewählten Kenntnis. | `app/Services/Techtree/ResearchService.php:135-138` |
| B4 | **Bonus-AP lösen nie einen Stufenaufstieg aus.** `_invest('add')` erhöht nur den Fortschritt; aufgestiegen wird nur über den Techtree-Auftrag im Labor. Ohne Labor füllt Tomas höchstens den Balken bis zur Schwelle. | `AbstractTechnologyService.php:334-337`, `TechtreeController::order` |
| B5 | Ist der Balken voll, wird der Rest **still verworfen** (`min(…, $apForLevelup)`), das Popup meldet trotzdem Erfolg. Der Rückgabewert von `investBonus()` wird an keiner Aufrufstelle ausgewertet. | `AbstractTechnologyService.php:336`; Aufrufstellen s. B8 |
| B6 | Ohne Labor zeigt die Labor-Vorschau Kenntnisse gesperrt und ohne Zahlen — Tomas' Beitrag ist nirgends sichtbar. | GLOSSAR „Labor-Vorschau“, `docs/gdd/techtree.md` §11.4 |
| B7 | Tomas hat zwei Fortschrittsspuren: Gesprächszähler (`colony_bartender_state.interaction_count`, Bonus-Staffel) und Beziehungsstufe (`colony_path_levels`, „Beziehung pflegen“, im selben Popup). Sie wissen nichts voneinander. | `BarService.php:1443-1450`, `PathDepthService` |
| B8 | **Sarka, Deva und Lenn haben dieselbe Lücke** (Prüfung für Frage 5, s. §4). | s. Tabelle §4 |

---

## 1. Entscheidung 1 + 2 — Stille Forschung, Wirkung genau, Fortschritt grob

### Entscheidung (Owner 2026-11-11)

- **Stille Forschung:** Erreichen zweckgebundene Bonus-AP die Schwelle einer Kenntnis, steigt sie **von selbst** eine Stufe auf, auch ohne Labor. Alle übrigen Sperren gelten weiter (Kommandozentrale-Stufe, Voraussetzungen im Techtree, Höchststufe). Aktives Forschen (eigene AP investieren, Stufe zurückbauen) bleibt dem Labor vorbehalten.
- **Nicht wählbar:** Eine Kenntnis, die gesperrt oder auf Höchststufe ist, steht in der Auswahl als „wartet auf …“ (Grund in einem Wort) und lässt sich nicht wählen. Damit gehen keine Bonus-AP mehr still verloren.
- **Ohne Labor:** Fortschritt **grob** (drei Worte statt Zahl, z. B. „erste Ansätze“ / „nimmt Gestalt an“ / „kurz vor dem Durchbruch“). Erreichte Stufe und ihr Effekt **genau**. Mit Labor: genaue Zahl wie heute.
- **Rückmeldung an drei Orten:**
  1. *Popup direkt nach dem Gespräch:* Ergebniszeile unter Tomas' Zeile. Mit Labor „+N AP in <Kenntnis> (Tomas)“ mit Balken und Zahl; ohne Labor „Der Rat fließt in <Kenntnis> — <grobe Stufe>.“; bei Aufstieg (beide Fälle) „Durchbruch: <Kenntnis> erreicht Stufe N“ mit Effekt in einem Satz.
  2. *Protokoll:* ein Eintrag je Bonus mit Wirkung, bei Aufstieg hervorgehoben.
  3. *Labor-Vorschau (ohne Labor):* Kenntnisse mit Bonus-Fortschritt zeigen die grobe Stufe und ein Tomas-Zeichen; erreichte Stufen stehen genau da. Das setzt den in §12 „Barkeeper“ vorgesehenen Tomas-Anteil um.

**Änderung an der Labor-Vorschau-Spec (2026-11-06):** Dort zeigt die Vorschau Kenntnisse ohne Stufenzahl und ohne Fortschritt. Das gilt künftig nur, solange eine Kenntnis Stufe 0 und keinen Bonus-Fortschritt hat.

### Annahmen (nicht Owner-entschieden)

- **A2a:** Stille Forschung gilt für Bonus-AP **immer**, also auch mit Labor. Sonst gäbe es zwei Regeln für dieselben AP („ohne Labor steigt sie selbst auf, mit Labor muss ich klicken“). Eigene AP im Labor bleiben beim heutigen Ablauf.
- **A2b:** In der Labor-Vorschau zeigt eine erreichte Stufe ihren eigenen Effekt; Effekte künftiger Stufen und AP-Kosten bleiben verborgen.
- **A6 (Übertrag):** Übersteigt ein einzelner Bonus die Restschwelle (z. B. Devas Schub auf einen halb vollen Balken), trägt der Rest in die nächste Stufe, sofern diese nicht gesperrt ist. Ist sie gesperrt, wird der Rest gedeckelt und die Ergebniszeile nennt das („Rest verfällt — <Grund>“). Nie still.
- **A7:** Kann der Aufstieg die Kosten der Stufe nicht zahlen (`levelup()` zahlt Kosten; derzeit kostet eine Kenntnis-Stufe keine Rohstoffe), bleibt der Balken voll, und die Kenntnis gilt als „wartet auf …“.
- **A8:** Die drei groben Worte teilen den Balken in drei gleich große Bereiche. Bei Fortschritt 0 erscheint kein Wort.

> ⚠️ BALANCE CONCERN: Stille Forschung gibt Cantina-First einen Kenntnis-Pfad ohne Labor. Siehe §6 (Mess-Punkt).

---

## 2. Entscheidung 3 — Beziehungsstufe ersetzt den Gesprächszähler

### Entscheidung (Owner 2026-11-11)

- Tomas' Bonus richtet sich nach seiner **Beziehungsstufe** (Stufe 0–5: fremd, neu kennengelernt, bekannt, vertraut, befreundet, brüderlich), nicht mehr nach der Zahl der Gespräche.
- Bei **„fremd“** gibt Tomas keinen Rat. Das Popup sagt ausdrücklich, dass man erst die Beziehung pflegen muss („Tomas kennt dich noch kaum — pflege die Beziehung, dann rückt er mit Ratschlägen heraus.“); „Beziehung pflegen“ steht im selben Popup.
- Ab Stufe 1 nennt der Knopf die Höhe: „Mit Tomas reden (+N AP in eine Kenntnis)“. Die Zahl im Knopf ist die wirkende Zahl.
- Der Gesprächszähler bleibt höchstens als Statistik (Kodex o. Ä.). Die Tomas-Zeilen nach Stufe (`bartender_dialog_tier_*`) hängen künftig an der Beziehungsstufe (Content: nur Zuordnung ändern).

### Abgeleitet (Folge der Entscheidungen 3 + 4, keine neue Regel)

- Bei „fremd“ ist „Mit Tomas reden“ nicht bedienbar (Knopf aus, Hinweis statt Kenntnis-Auswahl). Damit verbraucht ein wirkungsloser Klick keinen Sol (frühere Annahme A3 entfällt).

### Annahmen

- **A9:** Tomas ist ab Bau der Cantina „getroffen“ (heute schon so, `PathDepthService::ALWAYS_MET`), seine Beziehung lässt sich also sofort pflegen.
- **A10:** Die Stufennamen kommen aus `lang/*/pathdepth.php` (`relationship_level_0…5`, Owner 2026-11-11, Branch der Cantina-Popup-Arbeit, noch nicht auf master).

---

## 3. Entscheidung 4 — „heute schon geredet“ vorab zeigen

### Entscheidung (Owner 2026-11-11)

- Die Cantina-Daten liefern, ob Tomas heute schon geredet hat und was dabei herauskam (Kenntnis, AP, Aufstieg ja/nein, verfallener Rest).
- **Am Tresen:** kleines Zeichen an Tomas, solange heute ein Rat offen ist. Nach dem Gespräch verschwindet es.
- **Im Popup nach dem Gespräch des Tages:** Tomas-Zeile „Für heute ist alles gesagt“, darunter das heutige Ergebnis erneut (gleiche Ergebniszeile wie §1). Knopf aus, Chip „wieder nächstes Sol“. „Beziehung pflegen“ bleibt bedienbar.
- Die heutige Fehlermeldung bleibt nur als Rückfall (z. B. zweiter Tab).

### Annahme

- **A11:** Das Zeichen am Tresen erscheint bei „fremd“ nicht (es gibt keinen Rat, der offen wäre). Stattdessen kein Zeichen.

---

## 4. Entscheidung 5 — gleiche Regel für Sarka, Deva und Lenn

**Entscheidung (Owner 2026-11-11):** Stille Forschung, „nicht wählbar“, Übertrag/Deckel-Meldung und Rückmeldung (Popup, Protokoll, Labor-Vorschau) gelten für alle zweckgebundenen Kenntnis-Boni.

### Code-Prüfung: alle vier Quellen laufen über `investBonus()` (gemessen, Stand Worktree 2026-11-11)

| Figur | Bonus | Aufruf | Zielwahl | Lücke |
|---|---|---|---|---|
| Tomas | Rat je Sol | `BarService.php:1456` (`talkToBartender`) | Spieler, alle 7 Kenntnisse | B4, B5; Rückgabe ignoriert |
| Sarka (`mechanic`, Anliegen) | einmaliger Schub | `BarService.php:1024` (`resolveMechanicConcern`, Weiche `:979`) | Spieler, alle 7 (`BarController.php:175-182`) | B4, B5; Rückgabe ignoriert, meldet `ap_bonus` trotzdem |
| Deva (`veteran`, Informations-Kanal) | einmaliger Schub | `BarService.php:1322` (`resolveVeteranKnowledgeBoostOutcome`, Weiche `:1290`) | Spieler, nur Geologie/Gesundheit (`config/game.php:608-609`) | B4, B5; Rückgabe ignoriert |
| Lenn (`ai_researcher`, Informations-Kanal) | einmaliger Schub | `BarService.php:1348` (`resolveAiResearcherKnowledgeBoostOutcome`, Weiche `:1292`) | **fest** Kartografie (`config/game.php:619-620`) | B4, B5; Rückgabe ignoriert; keine Ausweichwahl |

Gemeinsame Ursache: `ResearchService::investBonus()` (`ResearchService.php:135-138`) → `_invest(…, 'add', …, bypassPoolCheck: true)` (`AbstractTechnologyService.php:294-337`) deckelt bei der Schwelle (`:336`) und steigt nie auf. `investBlocker()` (`:440-460`) kehrt bei `bypassPoolCheck` vor jeder Prüfung außer ID/Modus/Existenz zurück — Höchststufe und CC-Sperre werden beim Einzahlen gar nicht geprüft. Die übrigen Ausgänge von Deva (Drill-Puffer) und Lenn (Navigations-Rabatt) berühren `investBonus()` nicht.

Wichtig: Devas und Lenns Schub ist groß im Verhältnis zur ersten Kenntnis-Schwelle (Config); der Übertrag (A6) betrifft sie am stärksten.

### Annahmen

- **A12 (Lenn, festes Ziel):** Ist Kartografie gesperrt oder auf Höchststufe, wird Lenns Kenntnis-Ausgang beim Ziehen übersprungen (er bleibt für später im Pool), statt verschenkt zu werden.
- **A13 (Deva):** Sind Geologie und Gesundheit beide nicht wählbar, gilt dasselbe für Devas Kenntnis-Ausgang.
- **A14 (Sarka):** Ist keine der sieben Kenntnisse wählbar (praktisch nur spät im Lauf), wird ihr Anliegen nicht gezogen.

---

## 5. Umsetzung — Verhaltensänderungen als testbare Sätze

Je Satz ein künftiger Test (TDD: Test zuerst, rot bestätigen). „Bonus“ meint jede Quelle aus §4.

### Backend (`game-developer`)

**Stille Forschung (`ResearchService` / `AbstractTechnologyService`)**
1. Erreicht ein Bonus die Schwelle einer Kenntnis ohne Labor, steigt die Kenntnis um eine Stufe, und ihr Fortschritt beginnt bei 0 (bzw. beim Übertrag).
2. Dasselbe gilt mit Labor (A2a).
3. Ein Bonus unterhalb der Schwelle erhöht nur den Fortschritt, ohne Aufstieg.
4. Fehlt die Kommandozentrale-Stufe für die nächste Kenntnis-Stufe, lehnt `investBonus()` ab, und Fortschritt und Stufe bleiben unverändert.
5. Steht die Kenntnis auf Höchststufe, lehnt `investBonus()` ab.
6. Übersteigt ein Bonus die Restschwelle und ist die Folgestufe offen, trägt der Rest in die Folgestufe (A6).
7. Übersteigt ein Bonus die Restschwelle und ist die Folgestufe gesperrt, wird der Rest gedeckelt, und das Ergebnis nennt die verfallene Menge und den Grund (A6).
8. Ein Bonus, der mehrere Schwellen auf einmal überschreiten würde, steigt höchstens so viele Stufen auf, wie die Sperren erlauben; der Rest folgt Satz 7 (Kantenfall zu 6).
9. `investBonus()` liefert statt `bool` ein Ergebnis mit: eingezahlte AP, Aufstieg (neue Stufe oder keiner), verfallener Rest, Sperrgrund.
10. Stille Forschung belastet weder den gemeinsamen AP-Pool noch den Sol-Deckel des Labors (wie heute).
11. Aktives Investieren eigener AP ohne Labor bleibt abgelehnt (Regressionstest).
12. Eine Kenntnis gilt als *wählbar*, wenn alle Sperren außer der AP-Schwelle erfüllt sind und sie nicht auf Höchststufe steht (gemeinsame Abfrage für alle vier Quellen).

**Tomas (`BarService::talkToBartender`)**
13. Bei Beziehungsstufe 0 lehnt das Gespräch mit eigenem Code ab (`relationship_too_low`), verbraucht keinen Sol und erhöht keinen Zähler.
14. Ab Stufe 1 entspricht der Bonus dem Config-Wert der aktuellen Beziehungsstufe (§7), unabhängig von der Zahl der Gespräche.
15. Ein Gespräch mit einer nicht wählbaren Kenntnis wird abgelehnt (`knowledge_not_selectable`) und verbraucht keinen Sol.
16. Das Ergebnis des heutigen Gesprächs (Kenntnis, AP, Aufstieg, Rest) wird gespeichert und ist bis zum nächsten Sol abrufbar.
17. Jedes Gespräch mit Wirkung schreibt einen Protokoll-Eintrag; bei Aufstieg mit eigener Kennung.

**Sarka, Deva, Lenn (`BarService`)**
18. Sarkas Anliegen mit nicht wählbarer Kenntnis wird abgelehnt, ohne AP des Anliegens abzuziehen.
19. Devas Kenntnis-Ausgang mit nicht wählbarer Kenntnis wird abgelehnt, und der Ausgang gilt nicht als verbraucht.
20. Lenns Kenntnis-Ausgang wird nicht gezogen, solange Kartografie nicht wählbar ist (A12); er bleibt im Pool.
21. Devas Kenntnis-Ausgang wird nicht gezogen, solange weder Geologie noch Gesundheit wählbar ist (A13).
22. Sarkas Anliegen wird nicht gezogen, solange keine Kenntnis wählbar ist (A14).
23. Alle drei melden das Ergebnis aus Satz 9 an das Frontend weiter und schreiben einen Protokoll-Eintrag (wie Satz 17).

**Cantina-Daten (`BarController`)**
24. Die Kenntnis-Auswahl liefert je Kenntnis „wählbar ja/nein“ und den Sperrgrund.
25. Die Cantina-Daten liefern für Tomas: Beziehungsstufe, aktueller Bonus, „heute schon geredet“ und das heutige Ergebnis.
26. Die Labor-Vorschau-Daten liefern ohne Labor je Kenntnis: erreichte Stufe, deren Effekt, grobe Fortschrittsstufe (A8) und ob Bonus-Fortschritt enthalten ist; keine AP-Zahlen.

### Frontend (`ui-specialist`)

27. Bei Beziehungsstufe 0 zeigt Tomas' Popup statt Kenntnis-Auswahl den Hinweis „Beziehung pflegen“, und der Gesprächsknopf ist aus.
28. Ab Stufe 1 nennt der Knopf die Bonushöhe aus den Cantina-Daten.
29. Nicht wählbare Kenntnisse stehen in der Auswahl als „wartet auf <Grund>“ und sind nicht anwählbar (Tomas, Sarka, Deva).
30. Nach dem Gespräch zeigt das Popup die Ergebniszeile: ohne Labor mit grobem Wort, mit Labor mit Zahl und Balken, bei Aufstieg die Durchbruch-Zeile, bei Rest den Verfall-Hinweis.
31. Ist heute schon geredet, zeigt das Popup beim Öffnen „Für heute ist alles gesagt“, das heutige Ergebnis, den Knopf aus und den Chip „wieder nächstes Sol“; „Beziehung pflegen“ bleibt bedienbar.
32. Am Tresen trägt Tomas ein Zeichen, solange heute ein Rat offen ist und die Beziehungsstufe mindestens 1 beträgt (A11).
33. Die Labor-Vorschau zeigt Kenntnisse mit Stufe > 0 mit genauer Stufe und deren Effekt, Kenntnisse mit Bonus-Fortschritt mit grobem Wort und Tomas-Zeichen, sonst wie bisher gesperrt.
34. Nach jeder Aktion mit Bonus werden Labor-Vorschau bzw. Techtree und Ressourcenleiste ohne Neuladen aktualisiert.
35. Sarkas, Devas und Lenns Ergebnisanzeige verwendet dieselbe Ergebniszeile wie Tomas.

### Content (`content-writer`)

Ergebniszeilen, drei grobe Worte, „fremd“-Hinweis, „Für heute ist alles gesagt“, Chip, Sperrgründe in einem Wort, Protokoll-Einträge; `bartender_dialog_tier_*` den Beziehungsstufen zuordnen.

### Szenarien / Doku

- `game:reset-player`-Szenarien prüfen, falls sie `colony_bartender_state` setzen (Bonus hängt nicht mehr am Zähler).
- `docs/game-reference.md` nachziehen, sobald §7 in die Config übernommen ist.

---

## 6. Offener Mess-Punkt (Balance)

> ⚠️ BALANCE CONCERN: Stille Forschung plus Beziehungsstufe macht Tomas zu einem Kenntnis-Kanal ohne Labor, und pflegen lässt sich mit AP aus dem gemeinsamen Pool beschleunigen.
>
> **Annahme (gerechnet aus Config, nicht gemessen):** Mit dem Vorschlag §7 und den heutigen Platzhaltern in `config/path_depth.php` reicht ein Sol Beziehungspflege für Stufe 1 (+1 AP/Sol); die erste Kenntnis-Stufe von Baukunde braucht dann rund 20 Sol Gespräche — also wie bisher rund 20 Sol nach Bau der Cantina. Wer Tomas auf „vertraut“ bringt (braucht Cantina Stufe 2), halbiert das. Die Pflege amortisiert sich gerechnet nach wenigen Sol (wenige Pool-AP gegen dauerhaft +1 AP/Sol), ist also ein starker Umtausch von Pool-AP in deckelfreie Kenntnis-AP.
>
> Offene Variablen (Bot-Verhalten): Pflegt der Bot Tomas überhaupt? Wählt er eine Kenntnis, redet er jeden Sol? Ohne diese Regeln misst ein Batch nichts.
>
> Hypothese für die Messung (ein Hebel): Mit §7 erreicht Cantina-First bis Sol 40 im Mittel eine Kenntnis-Stufe 1 und Labor-First bleibt bei der Kenntnis-Gesamtstufe deutlich vorn. Widerlegt, wenn Cantina-First bei der Kenntnis-Gesamtstufe gleichzieht. Erst messen, dann Werte anfassen.

---

## 7. Config-Vorschlag (Vorschlag — Quelle bleibt `config/game.php`)

Ersetzt `game.bartender.ap_bonus_tiers` (Schlüssel = Gesprächszahl):

```php
'bartender' => [
    'interaction_cooldown_ticks' => 1,
    // Tomas' earmarked AP bonus per conversation, keyed by his relationship
    // level (colony_path_levels, kind 'character', key 'bartender', 0–5).
    // Level 0 ("fremd") gives no advice at all. PROPOSAL, pending measurement.
    'ap_bonus_by_relationship_level' => [0 => 0, 1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 3],
],

// Next to knowledge_ap_cap_per_sol. Rough progress words shown without a lab:
// upper bounds (fraction of the levelup threshold) of word 1 and word 2; above
// that word 3; no word at 0. PROPOSAL.
'knowledge_rough_progress_bands' => [0.34, 0.67],
```

Begründung: Höchstwert bleibt der heutige (gleiches Ende), der Anstieg folgt den Beziehungsstufen mit Plateaus, damit nicht jede Pflegestufe den Bonus erhöht. Die Staffel ist ein Platzhalter bis zur Messung (§6).

---

## 8. Nicht in dieser Spec

- Konkrete Werte jenseits §7: Config bzw. P7, nach Messung.
- Was Beziehungsstufen anderer Figuren mechanisch bringen: P7.
