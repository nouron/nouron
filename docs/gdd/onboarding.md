# Onboarding

> Ausgelagert aus [`docs/GDD.md`](../GDD.md) am 2026-08-02. Hint-System, Trigger-Ränge und der Einstiegs-Flow für neue Spieler.
>
> Kapitelnummerierung und `§`-Verweise beziehen sich weiterhin auf das GDD.

---

## 16. Onboarding

### Designprinzipien für Onboarding

Vier Prinzipien leiten das gesamte Onboarding-Design und haben Vorrang vor allen konkreten Maßnahmen:

1. **Lernen durch Tun, nicht durch Lesen.** Erklärungen erscheinen genau dann, wenn sie relevant sind — nicht vorab und nicht als Pflichtlektüre.
2. **Kein separater Tutorial-Modus.** Onboarding passiert im echten Spielzustand. Tag 1 ist der echte Spielstart. Was im Onboarding gebaut wird, bleibt erhalten.
3. **Erfahrene Spieler werden nicht bevormundet.** Alle Hinweise sind schließbar, überspringbar oder deaktivierbar. Wer weiß was er tut, soll das sofort tun können.
4. **Minimaler Implementierungsaufwand.** Das System darf keine komplexe State-Maschine erfordern. Jede Maßnahme muss einzeln implementierbar und wartbar sein.

---

### Das Cold-Start-Problem

Ein neuer Spieler sieht nach dem ersten Login:

- Die **Koloniekarte** (Hex-Grid): CC Lv1 und Harvester sind gesetzt, der Rest der Kolonie-Zone ist leer.
- Den **Techtree-Screen**: 11 Gebäude-Kacheln, 7 Kenntnis-Kacheln, 3 Schiffstypen, 5 Berater-Typen — alle ohne Erklärung der Zusammenhänge.
- Die **Ressourcenleiste**: 3.000 Credits, 200 Regolith, 0 Werkstoffe, 0 Organika.

Das Problem: Der Spieler sieht viele Optionen, hat aber keinen Hinweis, welche davon den größten Effekt hat. Jede Option sieht gleichwertig aus. Das erzeugt Paralyse.

**Die Lösung ist nicht mehr Information — sie ist Fokussteuerung.** Eine einzige klar hervorgehobene "erste Aktion" beseitigt die Paralyse ohne den Spieler in eine Reihenfolge zu zwingen.

---

### § 16.1 — Der erste Bildschirm: Nexus-Briefing

**Mechanik:** Beim allerersten Login eines Runs erscheint eine **Nexus-Nachricht im INNN-Feed** (kein Popup, kein Modal). Die Nachricht ist bereits sichtbar ohne dass der Spieler aktiv etwas öffnen muss — sie ist der erste Eintrag im INNN-Feed, Absender "Nexus Command", Priorität "dringend".

**Inhalt der Nachricht (Ton: karg, professionell, Roguelike-Atmosphäre):**

> **Nexus Command — Einsatzüberblick**
>
> Direktor, Ihre Konzession ist aktiv. Folgendes liegt vor:
>
> — Kommandozentrale (Lv1): betriebsbereit
> — Harvester (Lv1): Regolith-Produktion läuft
> — Startkapital: 3.000 Cr. Regolith-Reserve: 200 Rg
>
> Erste Priorität: Kolonie lebensfähig machen. Dafür brauchen Sie Wohnraum.
> Zweite Priorität: Personal einstellen — Kolonien ohne Berater handeln langsam.
>
> Der Rest liegt bei Ihnen.

Diese Nachricht erfüllt vier Funktionen gleichzeitig:
- Erklärt den Startzustand narrativ (Frontier-Atmosphäre bleibt erhalten)
- Benennt die erste sinnvolle Aktion ("Wohnraum")
- Verankert den Berater-Mechanic als frühe Priorität
- Ist kein Popup — erfahrene Spieler überlesen sie ohne Unterbrechung

**Technisch:** Die Nachricht wird beim Erzeugen eines neuen Runs über `InnnService::createEvent()` mit `sender = 'nexus'` erzeugt. Kein neues Schema erforderlich.

---

### § 16.2 — "Nächste sinnvolle Aktion": das Hint-System

Das **Hint-System** zeigt zu jedem Zeitpunkt genau **einen** hervorgehobenen Hinweis an. Nie mehr als einen gleichzeitig. Der Hinweis verschwindet sobald die Aktion ausgeführt wurde.

**Darstellung:** Eine schmale, schließbare Hinweis-Leiste direkt unterhalb der Ressourcenleiste. Hintergrundfarbe: gedämpftes Gelb (Warnton, kein Alarm). Maximale Länge: eine Zeile Text + ein Aktions-Link.

Beispiel-Darstellung:
```
[!] Kein Wohnhabitat gebaut — Supply-Cap bleibt bei 10. → Jetzt bauen
                                                                          [×]
```

Der Aktions-Link führt direkt zum relevanten Screen oder zur entsprechenden Kachel — kein Suchen nötig.

**Priorisierung: Der jeweils dringendste Zustand gewinnt.** Die Hinweise sind nach Dringlichkeit geordnet; wenn mehrere Bedingungen gleichzeitig zutreffen, gewinnt der Eintrag mit dem höchsten Rang:

> **Überarbeitet (Playtest-Review 2026-07-14) — neue Rangfolge für die Sol-1→4-Rampe.** Owner-Befund: Die Hints führten in der falschen Reihenfolge (CC-Invest auf Sol 1, Pfadgebäude erst Sol 3, Beraterslot-Hint lief in die Sackgasse `path_building_missing`). Neue Ziellinie: **Sol 1 Agrardom → Sol 2/3 Pfadgebäude → Sol 3/4 CC Lv2 → sofort Berater 2** (Budget-Rechnung in §16.5). Tabelle entspricht dem implementierten Stand (`OnboardingHintService::buildHintList`).

| Rang | Key | Bedingung | Hinweistext (Kurzfassung) | Ziel-Link | Sol-Schwelle |
|------|-----|-----------|---------------------------|-----------|--------------|
| 1 | `hint_1` | Kein Baumeister-Berater aktiv | "Noch kein Baumeister eingestellt — Bau-AP bleibt beim Grundwert von 6." | `/advisors` | — (siehe Designentscheidung unten — bewusst ohne Schwelle/Alternative) |
| 2 | `hint_repair_urgent` | Gebäude (Level ≥ 1) auf/unter `hint_repair_urgent_sp` (3 von 20) — Leveldown-Gefahr | "Ein Gebäude steht kurz vor dem Stufenverlust — jetzt reparieren." | Colony-Screen | — |
| 3 | `hint_2` | Harvester steht auf `is_colony_zone=1`-Tile (Ring 1) | "Harvester steht noch in der Kolonie-Zone — verlegen." | Colony-Screen | — |
| 4 | `hint_agrardome` | Harvester ≥ Lv1, kein Agrardom platziert, bezahlbar — **erstes Bauprojekt der Kolonie**, Pflicht-Gate für CC Lv2 | "Erstes Bauprojekt: Agrardom — heute platzieren, restliche Bau-AP hineinstecken." | `/colony/view?build=41` | **Sol 1** (`hint_no_agrardome_after_tick=0`) |
| 5 | `hint_repair` | Gebäude (Level ≥ 1) **unter der Sichtbarkeits-Schwelle** (`game.repair.display_threshold`, 70 % der Max-SP) | "Ein Gebäude zeigt deutlichen Verschleiß — reparieren, bevor der Verfall teurer wird." | Colony-Screen | — (zustandsbasiert; greift durch Decay faktisch ab ~Sol 4, siehe §16.5) |
| 6 | `hint_invest_site` | Aktive Baustelle (platziertes Level-0-Gebäude oder begonnener CC-Ausbau `ap_spend>0`), Bau-AP übrig, CC < Lv2 (danach Rente) | "Bau-AP nicht verfallen lassen — in die laufende Baustelle investieren." | Colony-Screen | — (ersetzt das CC-fixierte `hint_cc_invest`) |
| 7 | `hint_advisor_slot2` | CC ≥ Lv2, freier Slot **und** Pfadgebäude ≥ Lv1 (sonst liefe der Hire in `path_building_missing`) — bewusst **vor** den Pfad-Hints, damit nach CC Lv2 zuerst "Berater einstellen" kommt | "Berater-Slot 2 ist offen und das Pfadgebäude steht — Berater einstellen." | `/advisors` | — (sofort nach CC2) |
| 8 | `hint_build_priority` | ≥ 2 von (Sciencelab/Hangar/Cantina) gleichzeitig baubar (Pfadwahl offen, s. u., bezahlbar) | "Mehrere Gebäude bereit — eines auswählen." | Colony-Screen | — |
| 9 | `hint_6` | Pfadwahl offen, Housing ≥ Lv1, keine Cantina, bezahlbar | "Cantina noch nicht gebaut." | `/colony/view?build=52` | **Sol 2** (`hint_no_cantina_after_tick=1`) — gleichrangig mit `hint_analytik` und `hint_hangar_path` |
| 10 | `hint_analytik` | Pfadwahl offen, kein Analytik-Labor, bezahlbar | "Analytik-Labor noch nicht gebaut." | `/colony/view?build=31` | **Sol 2** (`hint_no_analytik_after_tick=1`) |
| 11 | `hint_hangar_path` | Pfadwahl offen, kein Hangar, bezahlbar | "Hangar noch nicht gebaut." | `/colony/view?build=44` | **Sol 2** (`hint_no_hangar_after_tick=1`) |
| 12 | `hint_3` | **Zustandsbasiert:** Agrardom ≥ Lv1 **und** ein Pfadgebäude ≥ Lv1, CC < Lv2, CC-Ausbau noch nicht begonnen (`ap_spend=0` — sonst führt `hint_invest_site`) | "Agrardom und Pfadgebäude stehen — jetzt CC auf Level 2." | Colony-Screen | Sol 3 als Floor (`hint_cc_upgrade_after_tick=2`) |
| 13 | `hint_explore` | Sol ≤ `hint_explore_until_tick` (0 → nur Sol 1), unentdeckte Tiles vorhanden, < 6 Ring-≥2-Tiles erkundet, günstigstes Tile bezahlbar | "Umgebung erkunden — Navigations-AP nutzen." | Colony-Screen | nur Sol 1 |
| 14 | `hint_4` | Keine Kenntnis auf Level > 0 | "Noch keine Kenntnis erforscht." | `/techtree` | **Sol 9** (`hint_no_knowledge_after_tick=8`) |
| 15 | `hint_5` | Trust < -20 | "Vertrauen der Kolonie sinkt." | Colony-Screen | **Sol 6** (`hint_trust_min_ticks=5`) |
| 16 | `hint_spend_remaining_ap` | Mindestens ein **nutzbarer** AP-Pool ungenutzt (Forschung zählt nur mit gebautem Sciencelab, Wirtschaft nur mit gebauter Cantina, Navigation nur mit bezahlbarem Fog-Tile) — Pool mit den meisten Rest-AP gewinnt | "Noch AP übrig — investieren." | poolabhängig | — |
| 17 | `hint_end_sol` | Fallback — greift nur wenn **kein nutzbarer AP-Pool** mehr übrig ist (Playtest-Fix 2026-07-14: feuerte vorher trotz nutzbarer Nav-AP) | "Nichts mehr zu tun — Sol beenden." | Colony-Screen | jedes Sol (Universal-Floor) |

> **Bau-Gate-Hinweis (wichtig für Implementierung):** Sobald der Spieler eines der drei Pfad-Gebäude gebaut hat, dürfen `hint_6`/`hint_analytik`/`hint_hangar_path` für die jeweils noch nicht erreichte CC-Stufe nicht feuern — sie greifen erst wieder, wenn `Anzahl gebauter Pfad-Gebäude < CC-Level − 1` zutrifft (siehe §13). Beispiel: Bei CC Lv2 und bereits 1 gebautem Pfad-Gebäude zeigen die beiden übrigen Hints (für die anderen zwei Pfad-Gebäude) **nicht mehr** "jetzt bauen", weil das Bau-Gate sie blockiert — stattdessen sollte ein neuer, noch zu definierender Hint-Text "Bei CC Lv3 verfügbar" angezeigt werden (kein Aktions-Link, reine Information), damit der Spieler nicht auf ein Gebäude klickt, das der Server ablehnt.

**Ergänzende Hinweise zur Tabelle:**
- `hint_advisor_slot2` (Rang 7): Direktes Feedback auf den CC-Lv2-Ausbau — feuert garantiert in einen sofort besetzbaren Slot und **vor** den Pfad-Hints (sonst würde nach CC Lv2 zuerst "zweites Pfadgebäude bauen" genagt statt "Berater einstellen").
- **"Pfadwahl offen"** (Gate der Ränge 8–11): Agrardom platziert **und** (noch kein Pfadgebäude platziert **oder** CC ≥ Lv2). Die Rampe will genau EIN Pfadgebäude vor CC Lv2 — solange das erste gebaut wird oder der CC-Ausbau aussteht, schweigen die anderen Pfad-Hints (Playtest-Befund 2026-07-14: "Kein Analytik-Labor"-Nag bei fertiger Cantina). Ab CC Lv2 dürfen sie wieder feuern.
- `hint_invest_site` (Rang 6) und `hint_explore` (Rang 13): lenken verbleibende Bau-AP in die aktive Baustelle bzw. Navigations-AP in Erkundung, statt sie verfallen zu lassen (siehe Punkt "Kein Leerlauf" unten).
- `hint_build_priority` (Rang 8): Reine Strategie-Hinweisebene, kein Aktionslink zu einem einzelnen Gebäude — signalisiert nur, dass eine Wahl zwischen mehreren gleichwertig bereiten Gebäuden besteht.
- `hint_end_sol` (Rang 17): Universeller Fallback, der verhindert, dass die Hint-Leiste je leer bleibt.
- `canAffordBuildingPlacement()`-Gate auf den Bau-Hints (Ränge 4, 9–11): Alle Bau-Hints prüfen tatsächliche Bezahlbarkeit (AP, Regolith, Werkstoffe, Supply) bevor sie feuern — verhindert, dass der Hint auf ein Gebäude zeigt, das der Spieler in diesem Sol gar nicht bauen kann.

> **Designentscheidung zu Rang 2 (Reparieren dringend):** Eigener Hint getrennt vom Lehr-Hint `hint_repair` (Rang 4). `hint_repair` wird beim ersten Reparieren-Klick dauerhaft dismissed (Lehrmoment „du kannst reparieren"). `hint_repair_urgent` warnt dagegen **wiederkehrend** vor dem einzigen irreversiblen Verlust (Leveldown bei SP 0): er ist nicht dismissbar (kein Eintrag in `dismissed_hints`), selbst-clearend sobald alle Gebäude wieder über `hint_repair_urgent_sp` liegen, und feuert bei jedem erneuten Verfall. Höchste Repair-Priorität, nur hinter `hint_1` (Baumeister liefert die Bau-AP). Schwelle 3/20 (≈15%) gibt selbst beim schnellsten Verfall (Cantina, 2 SP/Tick) noch >1 Sol Reaktionszeit.

> **Designentscheidung zu Rang 3 (Harvester):** Der Harvester startet auf Ring-1-Tile (1,0) = `regolith_normal, is_colony_zone=1`. Das ist technisch ein Regolith-Tile, liegt aber in der Kolonie-Zone — die für Gebäude reserviert ist. Der Hint motiviert, ihn auf Ring 2 zu verlegen. Ring-2-Tile (2,0) ist `regolith_normal, is_explored=1` (Nexus-Scout hat es bei Ankunft vorab erkundet, Nexus-Briefing erklärt das). Nach dem Verlegen ist das Ring-1-Tile für Gebäude frei.

> **Designentscheidung zu Rang 5 (Reparieren — Lehr-Hint, überarbeitet 2026-07-14):** Alle drei Startgebäude starten auf `status_points=16/20` (80 % — beschädigt aber funktionsfähig), doch dieser Schaden wird **nicht mehr angezeigt**: Schadens-Badge, Statusfärbung und der Lehr-Hint greifen erst unterhalb der Sichtbarkeits-Schwelle `game.repair.display_threshold = 0.70` (14/20). Die Startbeschädigung wird damit vom sichtbaren Lehrmaterial zum **unsichtbaren Pacing-Timer**: Durch die unterschiedlichen Decay-Raten fällt der Harvester ~Sol 4 unter die Schwelle, das Wohnhabitat ~Sol 6, das CC ~Sol 8 — Repair-Lehrmomente tröpfeln gestaffelt ein, genau nach Abschluss der Sol-1–4-Bau-Rampe, wenn AP-Slack existiert. Vorher würde der Hint nur zum Verbrennen knapper Bau-AP einladen (Owner-Playtest-Befund). Exakte SP bleiben in der Tile-Sidebar sichtbar — nichts wird versteckt, nur nicht aufgedrängt (§16.4-Transparenzprinzip). **Verschwindet beim ersten Reparieren-Klick** (dauerhaft dismissed). Der kritische Fall (Leveldown-Gefahr) wird weiterhin vom separaten, nicht dismissbaren `hint_repair_urgent` (Rang 2) abgedeckt. Ein mechanisches Repair-Gate an den Baumeister-Hire wurde geprüft und **verworfen**: Es verletzt die Designlinie "Hints sind Hinweise, keine Gates" und würde im Urgent-Fall ohne angestellten Baumeister die einzige Rettungsaktion blockieren.

> **Designentscheidung zu Rang 11 (CC-Lv2-Hint zustandsbasiert, 2026-07-14):** `hint_3` feuert nicht mehr rein tick-basiert ab Sol 2, sondern erst wenn Agrardom **und** ein Pfadgebäude fertig sind. Grund: CC Lv2 schaltet Beraterslot 2 frei, aber Anheuern in Slot 2–4 braucht ein gebautes Pfadgebäude — wer CC Lv2 vor dem Pfadgebäude baut, sitzt auf einem leeren Slot (Owner-Playtest-Befund: "CC ist zuerst fertig → Beraterslot frei → aber Berater kann nicht eingestellt werden"). Mit der neuen Reihenfolge zahlt sich CC Lv2 im selben Sol aus. `hint_cc_upgrade_after_tick=2` bleibt als reiner Floor.
>
> **Verifikation (2026-06-21) — Werte bestätigt, Werte verschoben:** `config('game.advisor.ap_per_rank')[1] = 4` bestätigt den Junior-Bonus von +4 AP. `ap_for_levelup=10` für CC bestätigt (per DB-Migration `2026_04_17_000003_calibrate_building_ap_costs.php`, nicht in `config/buildings.php` selbst — dort gibt es kein `ap_for_levelup`-Feld, das Feld lebt in der `buildings`-Tabelle). Die Sol-Schwelle selbst hat sich jedoch verschoben: `config('game.onboarding.hint_cc_upgrade_after_tick') = 1`, das entspricht weiterhin Sol 2 (Tick 1 = Sol 2) — hier kein Drift. **Drift gefunden bei anderen Schwellen:** `hint_no_knowledge_after_tick=8` → Sol 9, nicht Sol 8 wie in der alten Tabelle (§16.2) stand; `hint_trust_min_ticks=5` → Sol 6, nicht Sol 5; die alte Cantina-Schwelle "Sol 8" existiert in der neuen Implementierung gar nicht mehr — `hint_no_cantina_after_tick=5` → Sol 6. Alle drei Korrekturen sind in der Tabelle in § 16.2 oben eingearbeitet.

> **Designentscheidung (2026-06-21) zu Rang 1 — "Baumeister zuerst" ist gewollt, kein offener Punkt:** Der vorherige Befund 2 (siehe unten) kritisierte, dass `hint_1` ohne Tick-Schwelle und ohne Alternative ausschließlich auf den Baumeister verweist und damit faktisch die einzige vom Hint-System unterstützte Eröffnung erzwingt. Der Owner hat dazu entschieden: **Das ist beabsichtigt, nicht zu ändern.** Baumeister/Bau-AP ist die strukturell einzige Ressource, die in Sol 1 *alles* andere freischaltet — Wohnraum, Harvester-Verlegung, CC-Ausbau, später jedes Gebäude. Ohne Bau-AP-Hebel bleibt der Spieler in Sol 1 handlungsarm, unabhängig davon, welchen strategischen Pfad er danach einschlägt. Ein Spieler, der bewusst zuerst Konsul oder Analytiker einstellen will, kann das weiterhin jederzeit tun — `hint_1` ist dismissable (pro Hint-Typ) und blockiert keine Aktion, er ist lediglich der dauerhaft höchstrangige *Hinweis*, kein Gate. Die in Befund 2 vorgeschlagenen Alternativen (`hint_no_scientist`/`hint_no_trader` auf Rang 1, dynamischer Rang-1-Text) werden **nicht** umgesetzt. Befund 2 bleibt unten als Analyse-Dokumentation stehen, ist aber mit dieser Designentscheidung als abgeschlossen zu lesen — kein offener Implementierungsauftrag mehr an `game-developer`/`backend-coder`.

> **Designentscheidung (2026-07-14, ersetzt die Fassungen vom 2026-06-21/24) — Sol-1-Fokus: Agrardom zuerst, Pfadwahl ab Sol 2.** Die frühere Sol-1/2-Verengung ("nur Bau + Erkundung, Pfadwahl ab Sol 3") führte im Playtest dazu, dass Sol 1 Bau-AP in den CC versenkte und der Beraterslot nach CC Lv2 leer blieb. Neue Linie:
> - `hint_no_agrardome_after_tick = 0` → der Agrardom ist das **erste Bauprojekt der Kolonie** (Sol 1): platzieren + Rest-Bau-AP investieren, fertig Sol 2. Er ist Pflicht-Gate für CC Lv2 und serverseitige Voraussetzung für alle Pfadgebäude.
> - `hint_no_cantina/analytik/hangar_after_tick = 1` → **Pfadwahl ab Sol 2** (sobald der Agrardom platziert ist). Kein Pfad hat strukturellen Vorlauf.
> - CC Lv2 folgt **nach** dem ersten Pfadgebäude (zustandsbasiertes `hint_3`, s. o.) — so ist garantiert, dass der frisch freigeschaltete Beraterslot 2 sofort besetzbar ist.
> - `hint_4` (Kenntnis fehlt, Sol 9) und `hint_5` (Vertrauen kritisch, Sol 6) liegen weiterhin weit dahinter und tangieren die Rampe nicht.

> **Designentscheidung (2026-07-14, Schwelle aktualisiert; inhaltlich weiter gültig aus 2026-06-24) — echte, gleichwertige Wahl zwischen drei Pfaden (Sciencelab, Hangar, Cantina).** Alle drei Pfad-Hints stehen auf derselben Tick-Schwelle (jetzt 1 = Sol 2). Keiner hat strukturellen Vorlauf — alle drei werden, sobald ihre Prerequisites (Agrardom platziert für alle drei; Cantina zusätzlich Housing≥Lv1) erfüllt sind, gleichzeitig "bereit". Sobald der Spieler eines gebaut hat, verschwindet dessen Hint dauerhaft; `hint_build_priority` (Rang 7) signalisiert die Wahlsituation, solange ≥ 2 gleichzeitig bereit sind.
>
> **Standard-Empfehlung, keine Zwangsregel:** Wer Cantina zuerst priorisiert, sollte als nächsten Berater eher den **Konsul** anwerben. Wer Sciencelab zuerst priorisiert, eher den **Analytiker**. Wer Hangar zuerst priorisiert, eher den **Raumfahrer**. Diese Zuordnung ist die naheliegende Standardlinie und ergibt sich jetzt sogar **mechanisch** aus dem generischen Slot-System (§13): Der Slot, der durch das zuerst gebaute Pfad-Gebäude freigeschaltet wird, *ist* genau dieser passende Beratertyp — es gibt keine Möglichkeit mehr, "das falsche" Pendant in Slot 2 zu bekommen. Es bleibt weiterhin **explizit Raum für ausgefuchste Taktiken** (z. B. Pfad-Gebäude über Grund-AP abdecken, ohne den zugehörigen Slot sofort zu besetzen — der Slot bleibt einfach offen, bis der Spieler ihn füllen will). Das deckt sich mit §16.7 "Kein Pflicht-Reihenfolge".

> **Designentscheidung (2026-06-24, ersetzt "Agrardom ist unabhängig von der Sol-3-Wahlgruppe" vom 2026-06-21) — Agrardom ist Pflichtgebäude, kein Wahlgruppen-Mitglied mehr.** Frühere Fassung: Agrardom war von der Cantina-vs.-Analytik-Wahlgruppe entkoppelt, aber weiterhin optional. Neue Fassung: Agrardom ist jetzt **CC2-Bau-Voraussetzung** (siehe §4). Der `hint_agrardome`-Hint bleibt auf derselben Sol-2-Schwelle, ändert aber seine Funktion von "Empfehlung, weil keine vergleichbare strategische Verzweigung" zu "Pflicht-Warnung, weil CC-Ausbau sonst blockiert ist". `hint_build_priority` (Rang 11) bezieht Agrardom **nicht mehr** in die Eligibility-Zählung der Pfadwahl ein — die Wahlgruppe besteht jetzt ausschließlich aus den drei echten Pfaden Sciencelab/Hangar/Cantina.

**Deaktivierung:** Das Hint-System kann in den Einstellungen dauerhaft abgeschaltet werden (`onboarding_hints = false` in User-Preferences). Default: aktiviert. Schließen (`[×]`) eines Hinweises deaktiviert nur diesen spezifischen Hinweistyp bis zum Ende des Runs.

> **Designentscheidung:** Das System prüft Zustände, keine Sequenzen. Es gibt keine "abgehakten Tutorial-Schritte" — nur eine kontinuierliche Zustandsauswertung. Das ist wartungsarm und funktioniert ohne State-Maschine.

> **Designentscheidung:** Nur ein Hinweis gleichzeitig, nie eine Liste. Eine Liste erzeugt denselben Paralyseeffekt wie keine Hinweise. Der Spieler braucht eine klare Richtung, keine Aufgabenübersicht.

> ⚠️ BALANCE CONCERN: `hint_4` (Kenntnis-Hint, jetzt Rang 9) feuert ab Sol 9 (`hint_no_knowledge_after_tick=8`), während `hint_analytik` (Gebäude fehlt) bereits ab Sol 3 (`hint_no_analytik_after_tick=2`) feuert. Das Analytik-Labor hat damit in der Praxis schon 6 Sole Vorlauf, bevor `hint_4` überhaupt aktiv werden kann — der ursprüngliche Concern ("Kenntnis-Hint feuert vor dem Gebäude-Hint") ist mit der Sol-3-Anpassung von `hint_no_analytik_after_tick` hinfällig. Es bleibt aber sinnvoll, `hint_no_knowledge_after_tick` so zu belassen oder eher zu erhöhen als zu senken — er markiert keinen Eröffnungszwang, sondern eine späte Sicherheitswarnung für Spieler, die nach 9 Solen noch gar keine Kenntnis erforscht haben (unabhängig davon, ob sie den Cantina-, Analytik- oder Hangar-Pfad gewählt haben).

---

### § 16.3 — Visuelles Hervorheben: "Pulse"-Indikator

**Mechanik:** Wenn eine Techtree-Kachel oder ein Tile auf der Koloniekarte den ersten empfohlenen nächsten Schritt darstellt, erhält sie einen **Pulse-Indikator** — eine dezente, langsam pulsierende SVG-Umrandung (CSS animation `ring-pulse`, 2s Periode, ein Atemzug-Rhythmus, nicht aufdringlich).

**Trigger:** Der Pulse-Indikator wird ausschließlich durch denselben Zustandscheck wie das Hint-System gesteuert. Er zeigt auf genau die Kachel oder den Tile, auf den der aktive Hinweis verweist. Kein Pulse ohne zugehörigen Hint.

**Konkrete Darstellung (Phase 3e):**

| Hint-Rang | Pulsierendes Element |
|-----------|----------------------|
| 1 (kein Baumeister) | Baumeister-Slot im Berater-Screen |
| 3 (Harvester in Colony-Zone) | Harvester-Tile auf Koloniekarte + Ziel-Ring-2-Tile (2,0) |
| 5 (Reparieren, < 70 % SP) | betroffenes Gebäude-Tile (gleiche Schwelle wie die Schadensanzeige, `game.repair.display_threshold`) |
| 6 (Baustelle investieren) | aktive Baustelle (Level-0-Tile) bzw. CC-Tile bei begonnenem CC-Ausbau |
| 9 (keine Cantina) | Cantina-Kachel im Techtree |
| 11 (kein Hangar) | Hangar-Kachel im Techtree |
| 12 (CC Level < 2) | CC-Tile auf Koloniekarte |
| 14 (kein Wissen) | Analytik-Labor-Kachel im Techtree (wenn noch nicht gebaut) oder erste verfügbare Kenntnis-Kachel |
| 15 (Vertrauen < -20) | Erste verfügbare positive Vertrauensgebäude-Kachel |

**Deaktivierung:** Zusammen mit dem Hint-System (gleiche Einstellung).

> ⚠️ BALANCE CONCERN / DOKU-DRIFT: Diese Tabelle deckt nur 7 der jetzt 16 vorgesehenen Hint-Ränge ab (siehe § 16.2). Für die seit Phase 3g neuen Hints (`hint_repair`, `hint_repair_urgent`, `hint_advisor_slot2`, `hint_cc_invest`, `hint_explore`, `hint_build_priority`, `hint_agrardome`, `hint_analytik`, `hint_hangar_path`, `hint_end_sol`) ist nicht spezifiziert, welches Element pulsieren soll bzw. ob sie überhaupt einen Pulse erhalten. Muss vor dem nächsten UI-Pass mit `ui-specialist` geklärt werden — insbesondere `hint_end_sol` (Rang 15) sollte vermutlich KEINEN Pulse auf eine Kachel legen, sondern (falls überhaupt visuell hervorgehoben) auf den „Sol beenden"-Button.

**Abgrenzung zu bestehenden Indikatoren:** Der Tiefenscan-Pulse auf der Koloniekarte (bestehend, § 4a) ist ein anderer Indikator-Typ (orangefarbene Blitz-Animation). Onboarding-Pulse ist blau-weißlich — visuell eindeutig unterscheidbar.

> ⚠️ BALANCE CONCERN: Wenn zu viele Elemente gleichzeitig pulsierten (eigener Scan-Indicator, Onboarding-Pulse, zukünftige Event-Marker), wird die Karte visuell unruhig. Die Regel "nie mehr als ein Onboarding-Pulse gleichzeitig" muss auch auf UI-Ebene durchgesetzt werden.

---

### § 16.4 — Techtree-Kaltstart: Zugangshürde reduzieren

Der Techtree-Screen hat 11 Gebäude-Kacheln, 7 Kenntnisse, 3 Schiffe, 5 Berater — alle auf einmal sichtbar. Das ist für neue Spieler ein Orientierungsproblem.

**Maßnahme: Zustandsbasierte Kachel-Sortierung.**

Kacheln werden in drei Gruppen dargestellt, visuell getrennt durch einen Zwischenstrich und eine kleine Gruppenbezeichnung:

| Gruppe | Inhalt | Darstellung |
|--------|--------|-------------|
| **Jetzt verfügbar** | Gebäude, die Voraussetzungen erfüllt haben und sofort gebaut werden können | Normal hell, oben |
| **Voraussetzung fehlt** | Gebäude, die noch gesperrt sind | Gedimmt (Opacity 0.6), Tooltip zeigt was fehlt |
| **Bereits vorhanden** | Gebaute Gebäude | Grüner Statusring, unten oder ausgeblendet |

**Kein separater "Anfänger-Modus"** — das ist die Standarddarstellung für alle Spieler. Erfahrene Spieler profitieren ebenfalls von einer schnellen "Was ist gerade baubar?"-Übersicht.

**Tooltip bei gesperrten Kacheln:** Ein einzeiliger Hinweis direkt auf der Kachel (kein separates Modal): z.B. "Benötigt: CC Lv4" oder "Benötigt: Hangar". Der Tooltip erscheint nur on-hover — nicht dauerhaft als Text auf der Kachel.

> **Designentscheidung:** Die Kacheln werden nicht dauerhaft verborgen oder ausgeblendet — der Spieler sieht immer den gesamten Techtree. "Jetzt verfügbar" herauszuheben ist weniger invasiv als Inhalte zu verstecken. Transparenz über das gesamte System ist ein Nouron-Merkmal.

---

### § 16.5 — Die ersten 3–5 Aktionen: natürlicher Pfad

Der Startzustand (CC Lv1 beschädigt, Harvester Lv1 auf Ring-1, Housing Lv1 beschädigt, 3.000 Cr, 200 Rg) erzwingt einen natürlichen Pfad, wenn der Spieler dem Hint-System folgt. Der Pfad ist nicht zwingend — aber er ist der offensichtlich sinnvolle:

> **Startzustand (implementiert 2026-06-11, Sichtbarkeit überarbeitet 2026-07-14):** CC, Harvester und Wohnhabitat starten auf Level 1 mit `status_points=16/20` (80 % Zustand) — funktionsfähig und beschädigt, aber **nicht sichtbar beschädigt**: Schadensanzeige und Repair-Hint greifen erst unterhalb der Sichtbarkeits-Schwelle (`game.repair.display_threshold = 0.70`, siehe §16.2 Rang 5). Die Startbeschädigung wirkt als unsichtbarer Pacing-Timer — der Decay bringt Harvester ~Sol 4, Wohnhabitat ~Sol 6, CC ~Sol 8 unter die Schwelle, sodass Repair-Lehrmomente gestaffelt nach der Bau-Rampe eintreffen. Harvester startet auf Ring-1-Tile (1,0) = `regolith_normal, is_colony_zone=1`. Ein Ring-3-Regolith-Tile ist vorab erkundet (Nexus-Scout, randomisierte Koordinate).

**Aktion 1 — Baumeister einstellen (Berater-Screen)**

- Warum: Baumeister (+4 Construction-AP/Sol Junior) erhöht Bau-Tempo ab Sol 1; hint_1 zeigt auf `/advisors`
- Kosten: 300 Cr (Junior-Baumeister)
- Ergebnis: Construction-AP springt von 6 auf 10. AP-Chips aktualisieren sich sofort.
- Feedback-Loop klar: Berater-Card erscheint, Construction-AP-Anzeige springt hoch

**Aktion 2 — Harvester auf Ring-2-Regolith verlegen (Colony-Screen)**

- Warum: Harvester steht in der Kolonie-Zone (Ring 1) — dieser Slot ist für Gebäude reserviert. Nexus-Scout hat Ring-2-Tile (2,0) vorab erkundet; Ziel ist sichtbar.
- Kosten: 1 Construction-AP (Distanz 1 Hex)
- Ergebnis: Tile (1,0) in Ring 1 wird frei für Gebäude; Harvester produziert weiterhin Regolith
- Feedback-Loop klar: Harvester-Sprite bewegt sich auf neues Tile

**Aktion 3 — Agrardom platzieren + Rest-Bau-AP investieren (Sol 1, `hint_agrardome` → `hint_invest_site`)**

- Warum: Der Agrardom ist Pflicht-Gate für CC Lv2 und serverseitige Voraussetzung für alle Pfadgebäude — er ist das erste Bauprojekt der Kolonie. Ohne ihn bleibt Organika auf 0 (Verpflegungsmechanik §4a).
- Kosten: 40 Rg + 1 Bau-AP (Platzieren) + Rest-Bau-AP als Invest (Ziel 10 kumuliert)
- Ergebnis: Baustelle Sol 1 bei ~6/10, fertig Sol 2. `hint_invest_site` (Rang 6) lenkt die Rest-AP automatisch in die aktive Baustelle — der Rhythmus "platzieren → fertig investieren → nächstes platzieren" entsteht ohne Sequenz-Skript.
- Parallel: `hint_explore` (Rang 13) lenkt die ungenutzten 6 Navigations-AP in die Tile-Erkundung (Nav-Schiene, unabhängig von der Bau-Schiene).

**Aktion 4 — Pfadgebäude wählen und bauen (Sol 2/3, `hint_6`/`hint_analytik`/`hint_hangar_path`, ggf. `hint_build_priority`)**

- Warum: Ab Sol 2 (Agrardom platziert) stehen Cantina, Sciencelab und Hangar auf derselben Tick-Schwelle — die erste gleichwertige strategische Wahl: Handel, Forschung oder Flotte zuerst. Das Pfadgebäude muss **vor** CC Lv2 stehen, damit der freigeschaltete Beraterslot 2 sofort besetzbar ist (Slots 2–4 verlangen ein gebautes Pfadgebäude).
- Kosten: 70 (Cantina) / 80 (Sciencelab) / 80 (Hangar) Rg + 1 Bau-AP Platzieren + 10 Invest-AP
- Ergebnis: Pfadgebäude fertig ~Sol 3. Wer zuerst baut, bekommt automatisch den passenden Berater-Slot-Typ (Sciencelab → Analytiker, Hangar → Raumfahrer, Cantina → Konsul) — die Bau-Entscheidung *ist* die Berater-Entscheidung.
- **Kein permanenter Lockout:** Die anderen Pfade folgen bei höherem CC-Level — die Wahl bestimmt Reihenfolge, nicht endgültigen Zugang.

**Aktion 5 — CC auf Level 2 ausbauen (Sol 3/4, `hint_3`)**

- Warum: Jetzt zahlt sich CC Lv2 sofort aus — Agrardom (Pflicht-Gate) und Pfadgebäude (Slot-Gate) stehen. CC Lv2 schaltet den zweiten Berater-Slot frei + 6 neue Kolonie-Zone-Tiles.
- Kosten: 10 Construction-AP kumuliert + 40 Rg (CC-Upgrade = Ziel-Level × 20 Rg, gesenkt von ×30 am 2026-07-14 — siehe Balance-Notiz unten)
- Ergebnis: Koloniekarte aktualisiert sich live (Ring-Expansion §4a); `hint_advisor_slot2` (Rang 7) feuert sofort.

**Aktion 6 — Zweiten Berater einstellen (`hint_advisor_slot2`)**

- Der Slot ist generisch; der Typ ergibt sich aus dem gebauten Pfadgebäude (§13). Kosten 350–500 Cr — Credits sind zu diesem Zeitpunkt reichlich (~2.300+).
- An diesem Punkt hat der Spieler alle Kernsysteme berührt: Berater, Tile-Management, Bauen/Investieren, Pfadwahl, CC-Ausbau, Erkundung.

**Budget-Rechnung Sol 1–4** (10 Bau-AP/Sol ab Baumeister-Hire; Harvester-Move worst case 3 AP; Regolith-Start 200, +10/Sol ab Sol 2; Werte nach Balance-Anpassung 2026-07-14):

| Sol | Bau-AP | Regolith (Endstand) |
|-----|--------|---------------------|
| 1 | Hire (0) + Harvester-Move (3) + Agrardom platzieren (1) + Invest (6) = 10/10 | −40 → **160** |
| 2 | Agrardom-Invest 4 → **Lv1 ✓** + Pfadgebäude platzieren (1) + Invest (5) = 10/10 | +10 −10 −70/80 → **80–90** |
| 3 | Pfad-Invest 5 → **Lv1 ✓** + CC-Invest 5 = 10/10 | +10 −18/20 → **70–82** |
| 4 | CC-Invest 5 → **CC Lv2 ✓** → Berater 2 anheuern | +10 −40 → **37–52** |

> **Balance-Anpassung (2026-07-14) — Regolith-Klemme der Rampe entschärft:** Vor der Anpassung endete der Hangar-Pfad bei Sol 4 mit ~7 Rg Rest (de facto null Puffer für Repairs à 2 Rg/SP oder Housing-Ausbau). Zwei chirurgische Hebel statt globaler Inflation: `cc_upgrade_regolith_per_level: 30 → 20` (CC Lv2 = 40 statt 60 Rg — der größte Einzelposten der Rampe) und `hangar.build_cost: 90 → 80` Rg (Pfad-Gleichwertigkeit: Cantina 70 / Sciencelab 80 / Hangar 80). Ein höherer Harvester-Yield wurde verworfen (globaler Hebel, Trivialisierungsrisiko im Midgame); der Nexus-Kredit hilft nicht (liefert Credits, Engpass ist Regolith — einziger Wandlungsweg ist ein Cantina-Kaufangebot). **Offen (nach Playtest):** Nexus-Kredit als eigener Discovery-Moment, sobald die Cantina steht.

**Kein erzwungener Sequenz-Abschluss.** Der Spieler kann jederzeit von diesem Pfad abweichen. Die Hints verschwinden, wenn die jeweilige Bedingung nicht mehr zutrifft.

> ⚠️ BALANCE CONCERN: Baumeister-Kosten (300 Cr, Junior) müssen nach Playtest geprüft werden — 300 Cr von 3.000 Startguthaben ist 10%, sollte kein Problem sein. Einstellungskosten in `config/advisors.php` konfigurierbar.

> ⚠️ BALANCE CONCERN: Repair-Mechanik fehlt noch. Gebäude bei 80% sind 5–10 Sole lang funktionsfähig; sobald Verfall sie unter ~30% bringt, wirken sich Statusmalus-Effekte aus. Die Schwelle für "kritisch beschädigt" (aktuell: `80%`-Trigger in OnboardingTriggersService) soll nach erstem Playtest kalibriert werden.
>
> **Korrektur (2026-06-21):** Repair-Mechanik ist inzwischen implementiert (`hint_repair`, `hint_repair_urgent`, Reparieren-Button kostet 1 Bau-AP/Klick, `hint_repair_urgent_sp=3` von 20). Dieser Concern ist erledigt — verbleibt nur als Hinweis, dass die Schwelle `3/20` nach Playtest noch validiert werden sollte (siehe Designentscheidung zu Rang 2 in § 16.2).

---

#### Befund 1 — Leerlauf in den frühen Sols (AP- und Ressourcen-Sümpfe)

> ✅ **Gelöst durch die AP-Zusammenlegung (2026-08-02):** Die beiden AP-Sumpf-Befunde unten sind mit dem gemeinsamen AP-Pool (§13.1) strukturell erledigt. Es gibt keine separaten Economy- und Strategy-Pools mehr, die ungenutzt verfallen können — nicht abgerufene Kapazität steht automatisch für Bau, Kenntnisse und Erkundung zur Verfügung. Der ursprüngliche Befund bleibt als Begründung der Entscheidung dokumentiert. Die Regolith- und Credits-Befunde weiter unten sind davon **nicht** berührt und weiterhin offen.
>
> ⚠️ BALANCE CONCERN (Analyse 2026-06-21, historisch): Auch mit `hint_cc_invest` und `hint_explore` bleiben mehrere AP-Pools in frühen Sols strukturell ungenutzt, weil dafür **kein Hint existiert**:
>
> - **Economy-AP (Konsul) und Strategy-AP (Stratege):** Es gibt bis heute keinen einzigen Hint, der auf eine Verwendung dieser beiden AP-Pools hinweist. Die Basis-6-AP/Sol verfallen ungenutzt, solange kein Konsul/Stratege eingestellt ist UND solange keiner der beiden eingestellt wird, weil kein Hint dazu motiviert (`hint_1` deckt nur den Baumeister ab; `hint_advisor_slot2` ist berater-typ-agnostisch und damit zwar eine Wahlmöglichkeit, aber keine Aufforderung speziell für Economy/Strategy). Solange Cantina (Handelsangebote) nicht gebaut ist, ist Economy-AP ohnehin praktisch wirkungslos — das ist ein struktureller Sumpf von Sol 1 bis frühestens Sol 6.
> - **Strategy-AP** hat im aktuellen Frühspiel **gar keine Verwendung** (keine Begegnungen/Eskorte-Befehle vor Hangar/Korvette, Phase 3). Der Pool läuft potenziell viele Sole leer, ohne dass das im GDD irgendwo benannt wird. Das ist kein Hint-Problem, sondern ein strukturelles Pacing-Problem: Strategy-AP sollte entweder früher nutzbar sein (z.B. für Erkundungs-Risikobewertung) oder es sollte explizit dokumentiert sein, dass dieser Pool bewusst erst ab Phase 3 (Hangar/Korvette) relevant wird, damit niemand fälschlich einen fehlenden Hint als Bug einstuft.
> - **Regolith-Überschuss:** Harvester Lv1 produziert kontinuierlich Regolith; bei Bauprojekten, die Bau-AP-limitiert sind (nicht Regolith-limitiert), kann sich Regolith schon vor Sol 5 anhäufen.
> - **Credits:** Nach Aktion 1 (Baumeister, 300 Cr) bleiben ~2.700 Cr liegen. Vor `hint_advisor_slot2` (frühestens Sol 2/3) gibt es keine weitere Credits-Senke außer ggf. weiteren Berater-Anwerbungen — das ist beabsichtigt (Credits sind die "freie" Ressource, kein Hint nötig), aber sollte explizit als Designentscheidung benannt werden statt implizit zu bleiben.
>
> **Update (2026-06-21) — Depot-Hint geprüft, nicht umsetzbar: blockiert durch fehlendes Resource-Cap-System.** Die ursprüngliche Empfehlung unten ging davon aus, dass Depot einen Lager-Cap für Regolith durchsetzt und überschüssiges Regolith am Cap "stillschweigend verfällt". Codeprüfung (`app/Services/ResourcesService.php`, `app/Console/Commands/GameTick.php`) zeigt: Es gibt aktuell **kein Resource-Storage-Cap-System** — `cap` im Code bezeichnet ausschließlich den *Supply*-Cap (Entity-Limit für Gebäude/Berater/Schiffe), nicht ein Lagerlimit für Regolith/Credits/Werkstoffe. Depot (`building_id=30`) hatte im Code **keine Funktion** — es war in `config/buildings.php` definiert, aber ohne jede Spielwirkung.
>
> **Erledigt (2026-06-22) — Depot ersatzlos entfernt, statt Cap-System nachzuziehen.** Pro/Contra-Evaluation (siehe § "Errichten" oben) ergab: Das eigentliche Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit-System hätte aktive Produktion bestraft statt belohnt und stand quer zum Roguelike-Designprinzip. Owner-Entscheidung: Depot-Gebäude (`building_id=30`) komplett aus dem Spiel gestrichen (`config/buildings.php`, `lang/de+en/buildings.php`, `lang/de+en/techtree.php`, `MasterDataSeeder`, `ColonySeedDemo`, Migration `2026_06_22_000001_remove_depot_building.php`). Damit ist der Regolith-Überschuss-Punkt unten kein offener Hint-Blocker mehr, sondern erledigt durch Entfernung der betroffenen Mechanik-Idee. Bei Bedarf kann Depot + Cap-System später erneut eingeführt werden.
>
> **Erledigt (2026-06-21):** Toter Config-Key `hint_no_engineer_ticks` aus `config/game.php → onboarding` entfernt (war in `OnboardingHintService::checkHint1()` nicht mehr referenziert). Code-Kommentar-Defaults in `OnboardingHintService.php` (die `config(..., $default)`-Fallbacks) auf die tatsächlich aktiven Config-Werte synchronisiert — betraf `hint_cc_upgrade_after_tick` (2→1), `hint_explore_until_tick` (2→0), `hint_no_knowledge_after_tick` (10→8), `hint_no_cantina_after_tick` (5→2), `hint_no_agrardome_after_tick` (6→1). Bestehende Test-Suite (`tests/Feature/Onboarding/OnboardingHintServiceTest.php`, 53 Tests) bestätigt grün — reine Fallback-Korrektur ohne Verhaltensänderung, da die Config-Werte ohnehin immer gesetzt sind.
>
> **Weiterhin offen:** Economy-/Strategy-AP-Leerlauf (siehe oben) — keine Code-Änderung, aber die GDD-Notiz selbst (dieser Block) macht das Pacing jetzt bewusst/dokumentiert, statt implizit zu bleiben.

#### Befund 2 — Erzwungene Berater-Reihenfolge (Status: entschieden, kein offener Punkt mehr)

> **Erledigt durch Designentscheidung (2026-06-21), siehe § 16.2 "Designentscheidung zu Rang 1".** Die folgende Analyse bleibt als historische Dokumentation stehen, ist aber **nicht mehr als offener Balance-Concern zu behandeln** — der Owner hat "Baumeister zuerst" als bewusste, dauerhafte Designentscheidung bestätigt. Die unten stehenden Empfehlungen 1 und 4 (Rang 1 generalisieren bzw. zu einer Wahlgruppe umbauen) werden **nicht** umgesetzt. Empfehlung 3 war bereits zutreffend (kein Code-Änderungsbedarf). Die ursprüngliche Beobachtung selbst — dass die Hint-Priorisierung de facto eine nahegelegte Standardreihenfolge erzeugt — bleibt sachlich richtig, ändert aber nichts an der Design-Entscheidung: Eine *nahegelegte* Reihenfolge ist ausdrücklich erlaubt, solange sie nicht erzwungen wird (Hints bleiben dismissable, blockieren keine Aktion). Die echte Wahlfreiheit setzt laut Design bewusst erst **ab Sol 3** ein (Cantina vs. Analytik-Labor, siehe § 16.2) — Sol 1/2 sind als linearer Bau-/Erkundungs-Einstieg beabsichtigt, nicht als Wahlphase.
>
> Ursprüngliche Analyse (2026-06-21, unverändert zur Nachvollziehbarkeit erhalten):
>
> Rang 1 (`hint_1`, kein Baumeister) hat **keine Tick-Schwelle und keine Alternative** — er ist der einzige Hint, der einen bestimmten Berater-Typ (Engineer) namentlich verlangt, und er steht permanent an der höchsten Priorität, bis ein Baumeister eingestellt wird. In Kombination mit `hint_cc_invest`/`hint_explore` (die beide implizit voraussetzen, dass bereits ein Baumeister aktiv ist, weil sonst kaum Bau-AP für CC-Vorinvestition übrig bleibt) wird **"Baumeister zuerst" faktisch zur einzigen vom Hint-System unterstützten Eröffnung**. Ein Spieler, der z.B. zuerst einen Konsul (Handel) oder Analytiker (Forschung) einstellen möchte, bekommt:
> - Weiterhin `hint_1` als höchstrangigen, nicht wegklickbaren-bis-erledigt Hinweis (dismissable nur pro Hint-Typ, aber er kommt zurück solange kein Baumeister da ist und wird sofort wieder zum dringendsten Hint, wenn andere abgearbeitet sind)
> - Kein gleichwertiges Pendant `hint_no_scientist`/`hint_no_trader` o.ä. auf Rang 1 — die anderen vier Berater-Typen haben überhaupt keinen "fehlt noch"-Hint
> - Das Gefühl, "falsch" zu spielen, weil die Hint-Leiste beharrlich auf den Baumeister verweist, auch wenn der gewählte Build (z.B. früher Handel über Cantina) strukturell ebenso valide ist
>
> Das widerspricht dem in § 16.7 festgehaltenen Prinzip "Kein Pflicht-Reihenfolge" — de facto entsteht durch die Hint-Priorisierung trotzdem eine nahegelegte Standardreihenfolge (Baumeister → Harvester verlegen → CC-Vorinvestition/Erkunden → CC Lv2 → 2. Berater → Cantina/Agrardom/Analytik), die zwar nicht erzwungen, aber stark begünstigt ist, weil sie als einzige durchgängig durch Hints unterstützt wird.
>
> Ursprüngliche Empfehlungen (1, 2, 4 zurückgezogen — siehe oben; 3 weiterhin gültig als Bestandsbeschreibung):
> 1. ~~`hint_1` so erweitern, dass er nicht zwingend "Baumeister" verlangt~~ — verworfen, Baumeister-zuerst bleibt Designentscheidung.
> 2. ~~Alternativ: explizit als Designentscheidung dokumentieren~~ — umgesetzt, siehe § 16.2.
> 3. `hint_cc_invest`/`hint_explore` setzen den Engineer-Pfad nicht voraus, prüfen nur verbleibende Bau-AP bzw. Nav-AP — weiterhin korrekt, kein Änderungsbedarf.
> 4. ~~Ränge 1–8 (Sol 1–2) zu einer Wahlgruppe umbauen~~ — verworfen. Sol 1/2 bleiben linear (Bau + Erkundung); die Wahlgruppe wird stattdessen ab Sol 3 (Cantina vs. Analytik) eingeführt, siehe § 16.2.

---

#### Pfadwahl-Überarbeitung (2026-06-24) — Implementierungs-Checkliste

> Diese Checkliste fasst zusammen, was konkret geändert werden muss, damit Code und Config der hier dokumentierten Pfadwahl (§4 "Pfadwahl ab Sol 3", §13 "Slot-System: CC-Level als Gate, Pfadwahl ab Slot 2") entsprechen. Kein offener Designpunkt mehr — die Entscheidung ist getroffen; das Folgende ist Implementierungsauftrag an `game-developer`/`backend-coder`/`db-migration-agent`.

**1. Config-Änderungen:**

| Datei | Änderung |
|-------|----------|
| `config/buildings.php` | `hangar.cc_level_required` (oder äquivalentes Gate-Feld, falls ein solches Feld eingeführt wird — aktuell ist das CC-Gate nicht in `buildings.php` selbst kodiert, siehe Punkt 2) — Hangar von "CC Lv3" auf "CC Lv2" senken. `bioFacility` braucht ein neues Flag, das es als CC2-Pflichtvoraussetzung markiert (z. B. `'cc2_prerequisite' => true`), falls der CC-Levelup-Check generisch über Config laufen soll statt hartcodiert. |
| `config/advisors.php` | Keine Strukturänderung nötig — `ap_type`/`credits` bleiben pro Typ unverändert. Ggf. Kommentar ergänzen, dass `scientist`/`pilot`/`trader` jetzt über die generischen Pfad-Slots 2–4 gebunden werden, nicht mehr über feste CC-Level. |
| `config/game.php → onboarding` | Neuer Key `hint_no_hangar_after_tick => 2` ergänzen (siehe §16.7-Codeblock oben). |

**2. PHP-Dateien:**

| Datei | Änderung |
|-------|----------|
| `app/Http/Controllers/Techtree/AdvisorController.php` | `SLOT_ORDER`-Konstante ersetzen durch `FIXED_SLOTS` (Position 1 → engineer, Position 5 → strategist) + `PATH_BUILDINGS`-Mapping (building_id → advisor key, siehe §13-Pseudocode). `buildSlots()` umbauen: Slots 2–4 nicht mehr statisch aus einem Array, sondern dynamisch aus der Bau-Reihenfolge der drei Pfad-Gebäude auf der Kolonie ermittelt (siehe Punkt 3, Migration). Neuer UI-Zustand für "Slot wartet auf Pfad-Gebäude-Bau" nötig (unterscheidet sich vom bisherigen `locked`-Zustand, der nur CC-Level prüft). |
| `app/Services/Techtree/PersonellService.php` | `hire()` braucht **keine** Strukturänderung — die Methode ist bereits typ-agnostisch (CC-Level bestimmt nur die Slot-*Anzahl*, nicht den Typ). Zu prüfen: ob `hire()` zusätzlich validieren muss, dass der angefragte `personell_id` tatsächlich zu einem der drei bereits gebauten Pfad-Gebäude (oder den fixen Slots 1/5) gehört — aktuell gibt es keine Typ-Bindung in der Hire-Logik selbst, das wäre eine neue Geschäftsregel: Slot 2 darf nicht mit einem Typ besetzt werden, dessen Pfad-Gebäude noch nicht gebaut ist. |
| `app/Services/ColonyService.php` (oder wo `placeBuilding()` lebt) | Neues Bau-Gate für die drei Pfad-Gebäude (Sciencelab/Hangar/Cantina, building_id 31/44/52): `Anzahl bereits platzierter Pfad-Gebäude < CC-Level − 1` muss vor der Platzierung geprüft werden. Fehlerfall (analog zu bestehenden Gate-Fehlern wie `slot_full`) z. B. `'path_gate_locked'`. |
| CC-Levelup-Endpoint (vermutlich in `ColonyService` oder `ColonyController`) | Neue Voraussetzung für CC Lv1→Lv2: Agrardom (`building_id=41`) muss ≥ Lv1 sein. Fehlerfall z. B. `'agrardome_required'`. |
| `app/Services/OnboardingHintService.php` | Siehe Punkt 4 unten — eigener Abschnitt, da umfangreich. |

**3. Migration (DB-Schema):**

`colony_buildings` hat aktuell **keine** Zeitstempel-/Reihenfolge-Spalte (siehe `0001_01_01_000014_create_colony_buildings_table.php`) — die Bau-Reihenfolge der drei Pfad-Gebäude kann nicht rekonstruiert werden, ohne eine neue Spalte einzuführen. Empfehlung: neue nullable Spalte `placed_at_tick` (Analogie zu `pending_until_tick`, siehe `2026_06_11_200000_add_pending_until_tick_to_colony_buildings.php`), gesetzt beim ersten `placeBuilding()`-Aufruf für dieses `colony_id + building_id`-Paar. Slot-Zuordnung in `buildSlots()` sortiert dann die drei Pfad-Gebäude nach `placed_at_tick ASC`, Tie-Break bei Gleichstand nach `building_id ASC` (siehe §13 "Reihenfolge-Auflösung").

> ⚠️ Diese Migration ist die einzige tatsächliche Schema-Änderung dieser gesamten Design-Überarbeitung — alles andere ist Config + Service-Logik.

**4. `OnboardingHintService.php` — detaillierter Rework-Bedarf:**

- `allChoiceBuildingsPlaced()` (aktuell: Cantina + Agrardom + Analytik) → umstellen auf Sciencelab + Hangar + Cantina (Agrardom raus, weil jetzt Pflichtgebäude, kein Wahlgruppen-Mitglied mehr).
- `checkHintBuildPriority()` → dieselbe Korrektur (Agrardom raus aus der `$eligible`-Zählung, Hangar rein).
- Neue Methode `checkHintHangarPath()` + `hangarPrereqsMet()` — Analogie zu `checkHintAnalytik()`/`analytikPrereqsMet()`, prüft CC ≥ Lv2 + Bau-Gate frei + nicht gebaut + bezahlbar.
- `cantinaPrereqsMet()`, `analytikPrereqsMet()`, neue `hangarPrereqsMet()` müssen alle zusätzlich das Bau-Gate prüfen (`Anzahl gebauter Pfad-Gebäude < CC-Level − 1`), nicht nur CC-Level — aktuell prüfen sie nur CC-Level, was nach der Überarbeitung falsch-positive Hints erzeugen würde (Hint zeigt auf ein Gebäude, das der Server wegen Bau-Gate ablehnt).
- `checkHintAgrardome()`/`agrardomePrereqsMet()` bleiben strukturell gleich (Harvester ≥ Lv1, Sol-Schwelle), aber der Hinweistext (lang/de) muss von Empfehlung zu Pflicht-Warnung geändert werden — Aufgabe für `content-writer`, nicht `game-developer`.
- Neuer Hint-Zustand für "Bau-Gate aktuell gesperrt, bei höherem CC-Level verfügbar" — kein Aktions-Link, reine Information (siehe Hinweis in der Hint-Tabelle oben). Muss definiert werden, ob dieser Zustand überhaupt einen eigenen Hint-Rang bekommt oder nur eine Tooltip-Information auf der gedimmten Techtree-Kachel ist (letzteres vermutlich konsistenter mit §16.4 "Zustandsbasierte Kachel-Sortierung" — kein neuer Hint-Rang nötig, nur ein Tooltip-Text "Verfügbar ab CC Lv3").
- `checkHint3()` (CC-Levelup-Hinweis, Rang 5) muss den Agrardom-Pflicht-Check einbauen: Wenn CC < Lv2 UND Agrardom < Lv1, darf der Hinweistext nicht "CC ausbauen" lauten (der Button ist ja gesperrt), sondern muss auf Agrardom verweisen — sonst zeigt das Hint-System auf eine Aktion, die der Server ablehnt.

**5. Lang-Dateien (`content-writer`):**

- `lang/de/colony.php` (oder wo `onboarding_hint_*`-Keys liegen): Neuer Key für `hint_hangar_path`, Anpassung `hint_agrardome`-Text (Pflicht statt Empfehlung), Anpassung `hint_advisor_slot2`-Text (kein fester Beratertyp mehr nennbar).
- `lang/de/advisors.php`: Ggf. Anpassung der Slot-Beschreibungstexte im Berater-Screen, falls dort bisher "Slot 2 — Analytiker" o. ä. fest beschriftet war (zu prüfen mit `ui-specialist`).

**6. Tests (`qa-tester`):**

- Bestehende `tests/Feature/Onboarding/OnboardingHintServiceTest.php` (53 Tests, Stand 2026-06-21) muss für alle Tests angepasst werden, die `bioFacility`/Cantina/Analytik als gleichrangige Wahlgruppe annehmen.
- Neue Tests: Bau-Gate-Durchsetzung (2. Pfad-Gebäude bei CC2 ablehnen, bei CC3 erlauben), Agrardom-als-CC2-Voraussetzung, generische Slot-Zuordnung (Slot 2 = Typ des zuerst gebauten Pfad-Gebäudes), Tie-Break bei Gleichstand.

---

### § 16.6 — Inline-Erklärungen statt Handbuch

Bestimmte Konzepte sind für neue Spieler nicht intuitiv. Statt ein Handbuch anzubieten, gibt es **kontextsensitive Inline-Erklärungen** — kurze Einzeiler die genau dann erscheinen, wenn das Konzept zum ersten Mal relevant wird.

**Trigger-Punkte (einmalig pro Run, nicht wiederholend):**

| Konzept | Trigger | Anzeigeform |
|---------|---------|-------------|
| Decay (Verfall) | Erstes Gebäude fällt auf < 80% Status-Points | INNN-Ereignis: "Ihre [Gebäudename] zeigt erste Verfallserscheinungen. Reparatur-AP verlangsamen den Prozess." |
| Supply-Cap erreicht | `freies_supply` sinkt auf 0 | Inline-Banner (gelb) im Ressourcen-Header: "Supply-Cap erreicht — kein neues Schiff oder Berater baubar." |
| Vertrauen sinkt erstmals unter 0 | `vertrauen` wird negativ | INNN-Ereignis (Absender: Kolonist): "Die Stimmung in der Kolonie ist angespannt." |
| Erstes AP-Limit | Spieler versucht Aktion aber AP = 0 | Tooltip am Button: "Keine [Typ]-AP mehr heute. Berater erhöhen den täglichen Vorrat." |
| Harvester-Verlagerung | Erster Klick auf "Verlegen"-Aktion | Tooltip: "Harvester verlegen kostet 1 Bau-AP pro Hex Distanz — er kommt nächsten Sol an und produziert unterwegs nichts." |

**Format:** INNN-Ereignisse für narrative Konzepte (Verfall, Vertrauen), Inline-Banner für kritische Systemgrenzen (Supply-Cap), Tooltips für Aktions-Mechaniken. Kein Modal, kein Overlay.

**Technisch:** Alle fünf Trigger sind einmalige `innn_events`-Einträge mit einem Flag `is_onboarding_hint = true` (oder einem separaten `event_type`-Präfix `onboarding_*`). Sie werden beim Erzeugen markiert und nach dem Lesen nicht mehr wiederholt.

> **Designentscheidung:** INNN ist der natürliche Kanal für alle narrativen Erklärungen — der Spieler lernt früh, dass INNN wichtige Informationen liefert. Onboarding-Hinweise über denselben Kanal zu liefern stärkt diese Gewohnheit statt eine neue UI-Schicht einzuführen.

---

### § 16.7 — Was Onboarding bewusst nicht leistet

Explizit ausgeschlossen — diese Maßnahmen verletzen die Designprinzipien und werden nicht implementiert:

| Ausgeschlossen | Begründung |
|----------------|-----------|
| Pflicht-Reihenfolge (Story-Modus, "Schritt 1 von 5") | Macht den Roguelike-Start zur Lehrveranstaltung; verhindert eigene Erkundung |
| Gesperrte Screens bis Tutorial fertig | Bevormundet erfahrene Spieler; zerstört die "echter Spielstart von Tag 1"-Eigenschaft |
| Erklärungsmodal beim Laden des Spiels | Pop-up-Spam; wird weggeklickt; Informationen zu früh, nicht kontextsensitiv |
| Animierter Cursor-Zeiger ("Klick hier!") | Infantilisiert; passt nicht zum Direktor-Ton von Nouron |
| Permanente Sidebar-Erklärung aller Konzepte | Platzverschwendung; nach dem ersten Tag nicht mehr sinnvoll |
| Separater Sandbox-/Tutorial-Run | Hoher Implementierungsaufwand; Spieler wollen spielen, nicht üben |

---

### Technische Anforderungen (Zusammenfassung)

| Maßnahme | Implementierungsaufwand | Abhängigkeiten |
|----------|------------------------|----------------|
| Nexus-Briefing (INNN-Nachricht beim Run-Start) | Klein — `InnnService::createEvent()` erweitern | Run-Erzeugung muss Hook haben |
| Hint-System (Zustandscheck + Leiste unter Ressourcen) | Mittel — Alpine.js Komponente, 5 Bedingungsregeln | Ressourcenleiste-Layout, User-Preferences |
| Pulse-Indikator (CSS-Animation auf Kacheln/Tiles) | Klein — CSS-Klasse `ring-pulse` + Condition-Flag im Blade | Hint-System (welches Element pulsiert) |
| Kachel-Sortierung im Techtree | Mittel — Techtree-Controller liefert Gruppierungsflag | Techtree-Screen-Refactoring |
| Inline-Erklärungen (5 Trigger-Punkte) | Klein pro Trigger — INNN-Event + Flag | Run-State (Trigger darf nur einmal feuern) |
| User-Preference `onboarding_hints` | Klein — User-Settings-Tabelle oder Cookie | User-Settings-Screen |

**Konfiguration:** `config/game.php → onboarding` (Stand 2026-06-21, vollständig — die vorherige Fassung dieser Liste war veraltet und nannte teils nicht mehr existierende Keys):

```php
'onboarding' => [
    'hint_repair_urgent_sp'           => 3,   // Rang 2: SP-Schwelle (von max. 20) für Leveldown-Warnung
    'hint_supply_cap_threshold'       => 10,  // (aktuell ungenutzt im Hint-Ranking selbst — Supply-Cap-Banner läuft über §16.6, nicht §16.2)
    'hint_no_engineer_ticks'          => 3,   // Rang 1 referenziert dies nicht direkt mehr (checkHint1 prüft nur Advisor-Existenz) — TODO: toter Config-Wert?
    'hint_no_knowledge_after_tick'    => 8,   // Rang 9 (hint_4): Sol 9
    'hint_trust_threshold'            => -20, // Rang 10 (hint_5)
    'hint_trust_min_ticks'            => 5,   // Rang 10 (hint_5): Sol 6
    'hint_no_cantina_after_tick'      => 2,   // Rang 12 (hint_6) — Sol 3, gleichrangig mit hint_no_analytik_after_tick + hint_no_hangar_after_tick (Pfadwahl, siehe §13)
    'hint_no_agrardome_after_tick'    => 1,   // Rang 13 (hint_agrardome) — Sol 2; jetzt Pflicht-Gate-Hinweis, nicht mehr Wahlgruppen-Mitglied (siehe §4, §13)
    'hint_no_analytik_after_tick'     => 2,   // Rang 14 (hint_analytik) — Sol 3, gleichrangig mit hint_no_cantina_after_tick + hint_no_hangar_after_tick
    'hint_no_hangar_after_tick'       => 2,   // Rang 14b (hint_hangar_path) — NEU (2026-06-24), Sol 3, gleichrangig mit den beiden obigen — drittes Pfadwahl-Mitglied
    'hint_cc_upgrade_after_tick'      => 1,   // Rang 5 (hint_3): Sol 2
    'hint_explore_until_tick'         => 0,   // Rang 8 (hint_explore): nur Sol 1
    'hint_explore_max_explored_tiles' => 6,   // Rang 8 (hint_explore): Throttle
],
```

> ⚠️ BALANCE CONCERN / IMPLEMENTIERUNGSAUFTRAG (2026-06-24): `hint_no_hangar_after_tick` ist ein neuer Config-Key, noch nicht in `config/game.php` angelegt — Teil der Implementierungs-Checkliste am Ende dieses Abschnitts.
>
> ⚠️ BALANCE CONCERN / DOKU-DRIFT (2026-06-21, teilweise behoben): Die tatsächlichen Werte in `config/game.php` weichen weiterhin von den Code-Defaults in `OnboardingHintService.php` ab (z.B. `hint_no_agrardome_after_tick`: Config=1, Code-Default=6; `hint_no_analytik_after_tick`: Config=2, Code-Default=8). Das ist beabsichtigt (Config gewinnt immer; siehe `canAffordBuildingPlacement()`, die ohnehin die reale Bezahlbarkeit prüft) — die Code-Kommentare mit den höheren Default-Werten sollten dennoch zur Vermeidung von Verwirrung beim nächsten Code-Review aktualisiert werden (Aufgabe für `game-developer`, nicht `game-designer`). **Update (2026-06-21):** `hint_no_cantina_after_tick` wurde von `0` auf `2` korrigiert (war zuvor Sol 1, also zwei Sole vor `hint_no_analytik_after_tick`) — beide stehen jetzt auf identisch `2` (Sol 3), wie es die Designentscheidung "Pfadwahl" (§ 16.2, §13) verlangt. Diese eine Diskrepanz war kein reines Doku-Drift-Problem, sondern eine tatsächliche Balance-Lücke (Cantina hatte einen unbeabsichtigten Sol-Vorsprung) und wurde behoben.
>
> `hint_no_engineer_ticks` scheint im aktuellen `OnboardingHintService::checkHint1()` gar nicht mehr gelesen zu werden (die Methode prüft nur, ob ein Advisor-Datensatz existiert, ohne Tick-Schwelle). Falls korrekt: toter Config-Eintrag, sollte entfernt oder die Doku-Kommentare im Config korrigiert werden — zu klären mit `game-developer`/`backend-coder`.

> **TODO (Implementierung):** User-Preferences-Tabelle benötigt Spalte `onboarding_hints BOOLEAN DEFAULT 1`. Alternativ: Session-Storage für den ersten Run, persistente DB-Einstellung ab zweitem Run.

> **TODO (Design):** Nexus-Briefing-Text ist bisher nur als Entwurf definiert. Finale Formulierung mit dem content-writer abstimmen (Ton: karg, lakonisch, Frontier-Atmosphäre — kein Tutorial-Handbuch-Ton).

> **TODO (Design):** Reihenfolge der ersten freigeschalteten Kenntnis-Slots im Roguelike-Zufallssystem (§ 10) beeinflusst Onboarding — Hint Rang 4 muss prüfen ob das Analytik-Labor überhaupt Teil des laufenden Runs ist. Falls nicht: Hint anpassen auf "erste verfügbare Kenntnis".

---

