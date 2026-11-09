# Datenarchiv: Evaluation des „Hades-Prinzips, abgeschwächt“ (Stand 2026-11-09)

Status: **Bewertung und Empfehlung, keine Entscheidung, nichts umgesetzt.** Gerechnete Werte sind als **Annahme (gerechnet)** markiert; es wurde nichts gemessen und kein Bot-Batch gestartet. Grundlage: Entwurf `2026-11-08-nexusdb-codex-zusammenfuehren-entwurf.md` (Owner-Kasten 1–9, „Entschieden 2026-11-09“), `app/Services/CharacterCodexService.php`, `app/Services/BarService.php`, `app/Console/Commands/GameTick.php`, `config/game.php` (Bereiche `bar`, `character_codex`), `config/characters.php`.

## Kurzfassung

- Die Idee passt zum Spiel, **wenn die Run-Schwelle der eigentliche Taktgeber ist und nicht der Zufall der Cantina-Anlässe.** Mit den heutigen Spawn-Raten sind die meisten Figuren schon in ein bis zwei Runs durch Anlässe allein „durch“. Ohne Schwelle wäre das 4–5-Runs-Budget nicht zu halten.
- Dabei gibt es drei Befunde im Ist-Stand, die vor der Umsetzung geklärt sein müssen (siehe Abschnitt 1): Orin hat keinen Auslöser (Einträge nie erreichbar), die drei Story-Figuren füllen sich in einem Lauf komplett, und Cantina-lose Läufe liefern null Fortschritt.
- Empfehlung: **Anpassen, nicht verwerfen.** „Ereignis UND Schwelle“, aber Anlässe müssen **angespart** werden (nicht verfallen), ein Lauf zählt ab Erreichen von Sol 20 oder bei regulärem Ende, Schwellen nur auf die Einträge 3–5.

## 1. Passt die Schwelle, und wie viele Anlässe schafft ein Lauf?

### 1.1 Warum ein Lauf-Zähler zu hoher Siegquote passt

Bei Hades ist der Tod die Währung. Hier wollen Menschen 70–80 % gewinnen; Niederlagen sind selten. Die Schwelle darf deshalb **nicht** auf Scheitern setzen, sondern auf „Lauf gespielt, Ausgang egal“ (genau so entschieden). Bei 75 % Siegquote sind in 5 Läufen im Schnitt nur etwa 1,25 Niederlagen zu erwarten. Der Zähler belohnt also faktisch Zeit und Wiederkehr, nicht Scheitern. Das ist in Ordnung, solange die Kommunikation nicht „Scheitern schaltet frei“ verspricht. (Annahme: Der Owner-Wunsch „anfangs scheitern Spieler oft“ trifft vor allem Erstspieler; die Schwelle funktioniert für sie genauso.)

### 1.2 Ableitung der Anlässe pro Lauf (Annahme, gerechnet)

Grundannahmen (offene Variablen, Bot-/Spielerverhalten): Cantina ab etwa Sol 15 gebaut (Phase 1 endet laut Pacing-Stand Sol 12–13, Phasenfrist Sol 20), Lauf endet etwa Sol 75–95, also **etwa 60 Cantina-Sols**; mittlere Cantina-Ausbaustufe 3; Spieler löst etwa 70 % der angebotenen Anlässe tatsächlich aus. Blockade-Korrektur: ein offener Slot sperrt neue Würfe für die Angebotsdauer (2 Sol), effektive Rate = p / (1 + 2p).

| Figur(en) | Quelle (Config/Code) | Effektive Rate je Sol | Anlässe je Lauf (Annahme) |
|---|---|---|---|
| Aldra, Sorel, Fremder (Story) | `bar.story_encounter` 0,30 je Sol, 1 von 3 Figuren, **zählt beim Würfeln, ohne Spielerhandlung** | je Figur 0,10 | **etwa 6 je Figur** (über dem Maximum 5!) |
| Zara (Wette), Voss (Auktion) | gemeinsamer Begegnungs-Slot `bar.encounter` 0,20–0,52 je Stufe, 3 Typen gleich wahrscheinlich (Kontrakt hat keine Figur) | Slot etwa 0,21; je Typ 0,07 | **etwa 3 je Figur** (Zählung bei Abschluss) |
| Deva, Lenn | `bar.information_pool` 0,12 je Sol flach, Split 50/50 | je Figur etwa 0,05 | **etwa 2–3 je Figur** |
| Dax, Vesper, Sarka, Maret, Fen, Juno (Handelsfiguren) | Zählung über namentlichen Gast-Handel (`acceptOffer`); Anliegen-Slot `bar.concern` etwa 0,11 je Sol, Gewichte 1–3 von 18 | **nicht ableitbar aus Config** (Anteil benannter Gäste am Gast-Strom unbekannt) | **Annahme 1–2 je Figur**, größte Unsicherheit |
| Tomas | Cantina-Ausbaustufen (`ColonyController`), keine Zufallskomponente | — | 2–4, begrenzt durch erreichte Cantina-Stufe |
| Orin | **kein Auslöser im Code** (laut Klassenkommentar) | 0 | **0** |

Summe im ersten Lauf mit Cantina (auf 5 je Figur gedeckelt): Story 15 + Zara 3 + Voss 3 + Deva 2,5 + Lenn 2,5 + Tomas 3 + Handelsfiguren etwa 9 + Orin 0 ≈ **38 von 75 Einträgen**. Reiner Ereignisfortschritt füllt das Archiv also in **etwa 2–3 Läufen**, nicht in 4–5, und Orin nie.

### 1.3 Folgerungen

1. **Allein über Anlässe sind 4–5 Läufe nicht erreichbar.** Die Schwelle muss bei den schnellen Figuren (Story, Zara, Voss, Deva, Lenn) der bindende Faktor sein. Bei den langsamen (Handelsfiguren, Tomas) bleibt der Zufall der Taktgeber.
2. **Zwei Reglerzonen:** Schnelle Figuren: Rate 3–6 je Lauf, Schwelle bindet → Archiv läuft in genau 4–5 Läufen voll. Langsame Figuren: Rate 1–2 je Lauf, kumuliert 5 Anlässe erst nach 3–5 Läufen → Schwelle bindet kaum, Gesamtdauer eher 5–6 Läufe. Das widerspricht „alles nach 4–5 Läufen“ leicht; entweder akzeptieren oder die Rate der Handelsfiguren messen und nachziehen (Messung, nicht Rechnung).
3. **Cantina-lose Läufe** (Pfadwahl ist gleichrangig: Labor/Hangar/Cantina) liefern 0 Anlässe, zählen aber im globalen Lauf-Zähler. Schlechtester Fall: Spieler wählt in 2 von 5 Läufen einen anderen Pfad → Handelsfiguren erreichen die 5 nicht vor Lauf 7–8. Das ist Pfadwahl-Folge, aber es sollte ein Owner-Bewusstsein dafür geben (Frage 3).
4. **Das Sparen von Anlässen ist Pflicht.** Heute ist „Eintrag n = n-ter Anlass“ und `recordProgress()` schaltet sofort den nächsten Eintrag frei. Mit Schwelle muss ein gesperrter Anlass **zählen und warten** (Anlass-Zähler je Figur getrennt vom Freischalt-Stand), sonst gehen bei Sorel 5 von 6 Anlässen im ersten Lauf verloren und die Rechnung bricht.

## 2. Gestaltungsvorschlag

### 2.1 Schwellenmuster (Vorschlag, Annahme)

`runs_played` = Anzahl **beendeter, gezählter** Läufe des Spielers vor dem aktuellen Lauf (also im 1. Lauf 0, im 5. Lauf 4).

| Eintrag | Bedingung | Begründung |
|---|---|---|
| 1 | Anlass 1 (keine Schwelle) | Sofortiges Feedback: „Cantina-Gespräch bringt etwas“; verhindert, dass der erste Lauf leer bleibt |
| 2 | Anlass 2, Schwelle 0 | Ein Lauf liefert pro Figur 2 Einträge im Schnitt (nicht alle Figuren erreichen das) |
| 3 | Anlass 3, Schwelle 1 (ab Lauf 2) | Erster Grund, wiederzukommen |
| 4 | Anlass 4, Schwelle 2 oder 3 (je Figur gestreut) | Streuung verhindert, dass in Lauf 3 oder 4 15 Einträge gleichzeitig „aufspringen“ |
| 5 | Anlass 5, Schwelle 4 (ab Lauf 5) | Abschluss je Figur; Gesamtbudget 4–5 Läufe; bei Handelsfiguren faktisch durch Anlässe begrenzt |

- **Global je Eintragsposition, mit kleiner Streuung je Figur bei Eintrag 4**, nicht frei je Figur konfigurierbar. Grund: eine Regel („Eintrag 3 ab Lauf 2, Eintrag 5 ab Lauf 5“) ist Spielern und Tests erklärbar (Owner-Priorität Transparenz). Die Streuung bei Eintrag 4 ist ein einziger Wert pro Figur (2 oder 3), kein 15×5-Tabellenwust.
- **„Ereignis UND Schwelle“**, nicht ODER. ODER würde die Schwelle zum Alternativpfad machen, bei dem Nichtstun im Lauf schon Einträge liefert; das entwertet die Cantina als Ort der Begegnung (Ton: Aufbau, kleine lokale Begegnungen).
- Ein Anlass **füllt einen Wartevorrat**; mehrere Anlässe in einem Lauf können mehrere Einträge auf einmal freigeben, sobald die Schwelle fällt. Das ist ein bewusst gewünschter „Aha-Moment“ zu Beginn des neuen Laufs oder beim nächsten Anlass.

### 2.2 Frustrationsrisiko und UI ohne Gesamtzähler

Gefahr: Spieler hatte den Anlass, sieht aber nichts. Gegenmaßnahmen (Vorschläge, Texte macht `content-writer`):

1. **Zwei Sperrgründe, zwei Platzhalter.** „Noch nicht begegnet“ (kein Anlass) und „Du hast mehr erfahren, aber … später“ (Anlass angespart, Schwelle fehlt). Hinweistext wie vom Owner angedacht: „Mehr erfährst du in einem späteren Lauf.“ Dazu im Cantina-Dialog eine In-Fiction-Zeile im Moment des Anlasses („Sie schweigt zu dieser Sache. Vielleicht beim nächsten Mal.“), damit der Spieler nie ein Gespräch ohne erkennbare Folge hat.
2. **Kein Gesamtzähler, keine Schwellenzahlen.** Der Hinweis nennt nie „ab Lauf 3“. Pro Figur bleibt `x / max` (Owner-Entscheidung 3). Hinweis: Der Zähler pro Figur offenbart nur die Anzahl der Einträge je Figur, nicht die Schwellen; das ist vertretbar (Annahme).
3. **Kein Spoiler bei angespartem Anlass.** Nur der Platzhalter („hält etwas zurück“), nie Titel oder Inhalt des Eintrags.
4. **Protokollmeldung erst bei tatsächlicher Freischaltung** (Badge „Neu“, wie in GDD §17.3), nicht beim Anlass. Dadurch bleibt der Moment der Freischaltung gefühlt als Belohnung.
5. Eintrag freigeschaltet beim Lauf-Start oder bei Lauf-Ende? **Empfehlung:** Freischaltung sofort, wenn beide Bedingungen gelten, aber Schwelle wird erst bei **Lauf-Ende** erhöht. Dann ändert sich mitten im Lauf nichts an den Schwellen. Konsequenz: Ein Spieler, der seinen 4. Lauf beendet, sieht beim Nachschlagen am Ergebnisbildschirm schon die neu geöffneten Einträge (Anlässe lagen ja schon). Das ist ein guter Moment, die Ergebnisseite auf „Neue Einträge im Datenarchiv“ hinzuweisen (kein Zähler, nur Hinweis).

## 3. Risiken

| Risiko | Einschätzung | Maßnahme |
|---|---|---|
| **Erstspieler vs. Wiederholer** | Erstspieler sieht in Lauf 1 etwa 2 Einträge je getroffener Figur, Wiederholer wartet auf Schwellen. Wiederholer kann nur durch Spielen beschleunigen, nicht durch „Spielen können“. Wer 3 Läufe in einer Woche spielt, hat nach 3 Tagen ein Drittel; kein Problem, der Wissensbonus entfällt ja (Owner 2). | Regel: Lore gesperrt, Regeln offen (Owner 7), also nie spielentscheidend. Erwartung kommunizieren: Archiv ist ein Langzeitbonus. |
| **Grinding / Kurzläufe** | Läufe abbrechen, um `runs_played` zu erhöhen. Bei „UND“ liefert das allein nichts: die Anlässe fehlen. Reales Risiko bleibt klein, aber nicht null (Abbruch nach Anlass-Ernte, Neustart). | **Lauf zählt, wenn er Sol 20 erreicht hat oder regulär endet** (Sieg, Niederlage, Phasenfrist). Sol 20 ist der Phasenfrist-Zeitpunkt für Phase 1 (`phase1_deadline_sol`), ein Lauf, der dort scheitert, ist ein ernsthafter Fehlschlag und zählt; Abbruch vor Sol 20 nicht. (Annahme: `LobbyController::abandon` setzt `ended_at` und `fail_reason = abandoned`; ob ein Sol-Stand am Lauf liegt, ist zu prüfen.) |
| **Zählung „historisch“** | Spieler haben schon gespielte Läufe. | Annahme: `runs_played` lässt sich aus den beendeten Läufen des Spielers ableiten (kein Extra-Zähler, kein Backfill). Bereits freigeschaltete Einträge bleiben bestehen (Bestandsschutz). |
| **Onboarding** | Das Onboarding ist pro Lauf; das Archiv-Wissen ist spielerweit. Konflikt entsteht nur, wenn Onboarding-Hinweise auf gesperrte Archivartikel verweisen. | Hinweise verlinken nur Regelartikel (immer offen), nie Lore. Tomas’ Anliegen/Dialoge dürfen gesperrte Einträge nicht voraussetzen. |
| **Spoiler** | Figuren-Einträge der Spätphase könnten Siegziele oder Begegnungsausgänge verraten. | Spoiler-Regel aus Owner 8 gilt; Einträge 4–5 (hinter den späten Schwellen) sind der natürliche Ort für sensible Lore, weil sie spät kommen. |
| **Story-Figuren und Orin als Ausreißer** | Story-Figuren: etwa 6 Anlässe je Lauf, Orin: 0. | Orin braucht einen Auslöser (Owner-/Content-Frage, 6). Story-Rate drosseln ist **nicht** nötig, sobald Anlässe angespart werden und die Schwelle bindet. |
| **Tomas** | Fortschritt hängt an Cantina-Stufen (Bau-Entscheidung), nicht an Zufall. Stufe 5 ist im Lauf evtl. unerreichbar; nach Niederlage endet der Fortschritt dort. | Für Tomas Anlass = Stufenmeilenstein, Schwellen wie bei anderen. Eintrag 5 darf an Stufe 5 hängen und wird dann von der Schwelle nicht zusätzlich verzögert (Vorschlag: Tomas ohne Schwelle für Eintrag 5, weil schon die schwerste Anlassbedingung). Offene Entscheidung (Frage 5). |
| **Mehraufwand / Komplexität** | Anlass-Zähler getrennt vom Freischalt-Stand, zwei Platzhaltertexte, Lauf-Zähl-Regel. | Klein, aber nicht trivial; muss mit Tests (TDD) umgesetzt werden. |

## 4. Alternativen zum reinen Lauf-Zähler

| Alternative | Vorteil | Nachteil |
|---|---|---|
| **A: Ein neuer Eintrag je Figur und Lauf** (figurbezogener Zähler „Läufe mit Begegnung“; Eintrag n frühestens im n-ten Lauf, in dem die Figur vorkam) | Braucht keinen globalen Zähler, kein Mindestdauer-Problem, Cantina-lose Läufe zählen automatisch nicht, ruhiger Rhythmus „in jedem Lauf etwas Neues“, sehr leicht erklärbar | Erster Lauf nur 1 Eintrag je Figur (mager); strikt ≥ 5 Läufe mit Begegnung für alles; Figuren, die ein Lauf nicht zeigt, verzögern sich |
| **B: Meilensteine im Lauf** (z. B. Phase 3 erreicht, Siegziel gewählt) | Ereignisse im Lauf geben der Freischaltung Bedeutung; belohnt Fortschritt | Koppelt Lore an Erfolg im Lauf, steht im Widerspruch zu „auch gescheiterte Läufe zählen“ und „kein Eintrag nur durch Sieg“; frühe Niederlage = nichts |
| **C: Zufall** (jeder Anlass schaltet mit p frei) | Variabilität, jeder Lauf fühlt sich anders an | Unvorhersehbar, Archiv-Abschluss nach 4–5 Läufen nicht garantiert (Pech-Serien); verletzt Transparenzpriorität |
| **D: Beziehungsstufen** (Vertrauen je Figur wächst mit Gesprächen über Läufe hinweg) | Spielerisch gut erklärt („du kennst sie besser“), passt zu Cantina | Im Kern schon der heutige Anlass-Zähler; ohne Zeitbremse kein 4–5-Läufe-Budget; bräuchte zusätzliche Deckelung pro Lauf |
| **E: Pro-Lauf-Deckel statt Schwelle** (max. 2 neue Einträge je Figur und Lauf) | Einfachste Regel, keine globale Zählung, Anlässe verfallen nicht | 75 Einträge / (2 je Lauf) = 3 Läufe, also zu kurz; mit Deckel 1 gleichbedeutend mit A |

Bewertung: Der globale Lauf-Zähler mit Schwellen bleibt die flexibelste Lösung für das Budget; **A** ist die robusteste Alternative, falls Grinding oder Cantina-lose Läufe in der Praxis stören. Ein Wechsel ist später möglich, weil beide Varianten dasselbe Datenmodell (Anlass-Zähler + Freischalt-Stand) brauchen.

## 5. Empfehlung

**Anpassen (nicht verwerfen).** Das abgeschwächte Hades-Prinzip löst das Problem „Archiv in einem Lauf leer gespielt“ und gibt Wiederkehr einen sanften Grund, ohne Scheitern zu verlangen. Es funktioniert aber nur mit diesen Anpassungen:

1. Anlässe werden **angespart**, nicht verworfen (Pflicht).
2. Schwellen nur auf Einträge 3–5, Eintrag 1–2 sofort; Streuung für Eintrag 4.
3. Lauf zählt ab Sol 20 oder bei regulärem Ende (Sieg, Niederlage, Phasenfrist); Abbruch davor zählt nicht.
4. Orin braucht einen Auslöser, sonst bleibt eine ganze Figur leer.
5. Zwei Sperr-Platzhalter plus In-Fiction-Zeile beim Anlass, kein Gesamtzähler, keine Schwellenzahlen im UI.
6. Vor der Feinjustierung **eine einzige Messung**: Anteil benannter Handelsfiguren am Gast-Strom und Anlässe je Lauf (Bot-Läufe nur mit Freigabe des Owners; Dauerschätzung vorher abstimmen). Bis dahin sind die Handelsfiguren-Raten (Tabelle 1.2) nur Annahmen.

### Offene Entscheidungen für den Owner (Fragen mit Empfehlung)

1. **Was zählt als „gespielter Lauf“?** Empfehlung: Lauf erreicht Sol 20 oder endet regulär; Abbruch davor zählt nicht. (Alternative: jeder Lauf zählt; bei „UND“ ist Grinding trotzdem wirkungslos, aber verfälscht die Bedeutung von „4–5 Läufe“.)
2. **Verfallen angesparte Anlässe oder bleiben sie?** Empfehlung: bleiben (angespart). Ohne das ist Eintrag 5 für Story-Figuren nur durch Zufall erreichbar.
3. **Sollen Cantina-lose Läufe beim Lauf-Zähler mitzählen?** Empfehlung: ja (Lauf ist Lauf, Pfadwahl gleichrangig), und die Folge akzeptieren: Handelsfiguren brauchen dann evtl. 6–8 Läufe, weil Anlässe fehlen. Alternative: Variante A aus Abschnitt 4.
4. **Gleiche Schwellen für alle Figuren oder pro Figur?** Empfehlung: global je Eintragsposition, nur Eintrag 4 pro Figur gestreut (2 oder 3).
5. **Tomas:** Eintrag 5 ohne Schwelle (weil Cantina-Stufe 5 schon die Hürde ist)? Empfehlung: ja.
6. **Orin:** Welcher Anlass? Empfehlung (Annahme, Content/Owner): ein eigener Auslöser, etwa bei einem Abschluss eines Konzern-Handelsereignisses; zuerst mit `content-writer` klären, was Orin in der Cantina tut.
7. **Anzeige:** Zwei Platzhalter („noch nicht begegnet“ / „hält etwas zurück, später mehr“) und ein Hinweis auf der Ergebnisseite „Neue Einträge im Datenarchiv“ (ohne Zahl). Empfehlung: ja.
8. **Zielgröße „4–5 Läufe“ als Untergrenze oder Mittelwert?** Empfehlung: Untergrenze für schnelle Figuren, Mittelwert 5–6 für langsame akzeptieren, bis die Messung steht.

### Offene Variablen (Abhängigkeit von Spielerverhalten)

- Zeitpunkt des Cantina-Baus und erreichte Cantina-Stufe (verschiebt Raten um den Faktor 2).
- Anteil der Läufe ohne Cantina (Pfadwahl).
- Anteil der Anlässe, die der Spieler tatsächlich auslöst (AP-Kosten 2–4 je Annahme).
- Anteil benannter Gäste am Gast-Strom (Handelsfiguren, größte Unsicherheit).
- Echte Lauflänge (Sol 70–95 laut Owner-Pacing; Niederlagen enden früher, liefern entsprechend weniger Anlässe).

### Nicht Teil dieser Evaluation

Konkrete Eintragstexte, Config-Namen, Tabellenänderungen, Tests; GDD und `docs/game-reference.md` wurden nicht verändert (GDD-Änderung erst nach Owner-Entscheidung; Zahlen gehören dann in Config und `docs/game-reference.md`, nicht in die GDD-Prosa).
