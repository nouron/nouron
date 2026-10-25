# Onboarding

> Hint-System, Trigger-Ränge und der Einstiegs-Flow für neue Spieler.
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

> Die Rangfolge folgt der Ziellinie für die Sol-1→4-Rampe: **Sol 1 Agrardom → Sol 2/3 Pfadgebäude → Sol 3/4 CC Lv2 → sofort Berater 2** (Budget-Rechnung in §16.5). Die Tabelle entspricht dem implementierten Stand (`OnboardingHintService::buildHintList`).

| Rang | Key | Bedingung | Hinweistext (Kurzfassung) | Ziel-Link | Sol-Schwelle |
|------|-----|-----------|---------------------------|-----------|--------------|
| 1 | `hint_1` | Kein Baumeister-Berater aktiv | "Noch kein Baumeister eingestellt — die Kolonie arbeitet mit dem Grundwert." | `/advisors` | — (bewusst ohne Schwelle/Alternative, s. u.) |
| 2 | `hint_repair_urgent` | Gebäude (Level ≥ 1) auf/unter `hint_repair_urgent_sp` (3 von 20) — Leveldown-Gefahr | "Ein Gebäude steht kurz vor dem Stufenverlust — jetzt reparieren." | Colony-Screen | — |
| 3 | `hint_2` | Harvester steht auf `is_colony_zone=1`-Tile (Ring 1) | "Harvester steht noch in der Kolonie-Zone — verlegen." | Colony-Screen | — |
| 4 | `hint_agrardome` | Harvester ≥ Lv1, kein Agrardom platziert, bezahlbar — **erstes Bauprojekt der Kolonie**, Pflicht-Gate für CC Lv2 | "Erstes Bauprojekt: Agrardom — heute platzieren, restliche AP hineinstecken." | `/colony/view?build=41` | **Sol 1** (`hint_no_agrardome_after_tick=0`) |
| 5 | `hint_repair` | Gebäude (Level ≥ 1) **unter der Sichtbarkeits-Schwelle** (`game.repair.display_threshold`, 70 % der Max-SP) | "Ein Gebäude zeigt deutlichen Verschleiß — reparieren, bevor der Verfall teurer wird." | Colony-Screen | — (zustandsbasiert; greift durch Decay faktisch ab ~Sol 4, siehe §16.5) |
| 6 | `hint_invest_site` | Aktive Baustelle (platziertes Level-0-Gebäude oder begonnener CC-Ausbau `ap_spend>0`), AP übrig, CC < Lv2 (danach Rente) | "AP nicht verfallen lassen — in die laufende Baustelle investieren." | Colony-Screen | — |
| 7 | `hint_advisor_slot2` | CC ≥ Lv2, freier Slot **und** Pfadgebäude ≥ Lv1 (sonst liefe der Hire in `path_building_missing`) — bewusst **vor** den Pfad-Hints, damit nach CC Lv2 zuerst "Berater einstellen" kommt | "Berater-Slot 2 ist offen und das Pfadgebäude steht — Berater einstellen." | `/advisors` | — (sofort nach CC2) |
| 8 | `hint_build_priority` | ≥ 2 von (Sciencelab/Hangar/Cantina) gleichzeitig baubar (Pfadwahl offen, s. u., bezahlbar) | "Mehrere Gebäude bereit — eines auswählen." | Colony-Screen | — |
| 9 | `hint_6` | Pfadwahl offen, Housing ≥ Lv1, keine Cantina, bezahlbar | "Cantina noch nicht gebaut." | `/colony/view?build=52` | **Sol 2** (`hint_no_cantina_after_tick=1`) — gleichrangig mit `hint_analytik` und `hint_hangar_path` |
| 10 | `hint_analytik` | Pfadwahl offen, kein Analytik-Labor, bezahlbar | "Analytik-Labor noch nicht gebaut." | `/colony/view?build=31` | **Sol 2** (`hint_no_analytik_after_tick=1`) |
| 11 | `hint_hangar_path` | Pfadwahl offen, kein Hangar, bezahlbar | "Hangar noch nicht gebaut." | `/colony/view?build=44` | **Sol 2** (`hint_no_hangar_after_tick=1`) |
| 12 | `hint_3` | **Zustandsbasiert:** Agrardom ≥ Lv1 **und** ein Pfadgebäude ≥ Lv1, CC < Lv2, CC-Ausbau noch nicht begonnen (`ap_spend=0` — sonst führt `hint_invest_site`) | "Agrardom und Pfadgebäude stehen — jetzt CC auf Level 2." | Colony-Screen | Sol 3 als Floor (`hint_cc_upgrade_after_tick=2`) |
| 13 | `hint_explore` | Sol ≤ `hint_explore_until_tick` (0 → nur Sol 1), unentdeckte Tiles vorhanden, < 6 Ring-≥2-Tiles erkundet, günstigstes Tile bezahlbar | "Umgebung erkunden — AP nutzen." | Colony-Screen | nur Sol 1 |
| 14 | `hint_4` | Keine Kenntnis auf Level > 0 | "Noch keine Kenntnis erforscht." | `/techtree` | **Sol 9** (`hint_no_knowledge_after_tick=8`) |
| 15 | `hint_5` | Trust < -20 | "Vertrauen der Kolonie sinkt." | Colony-Screen | **Sol 6** (`hint_trust_min_ticks=5`) |
| 16 | `hint_spend_remaining_ap` | Mindestens ein **nutzbarer** AP-Pool ungenutzt (Forschung zählt nur mit gebautem Sciencelab, Wirtschaft nur mit gebauter Cantina, Navigation nur mit bezahlbarem Fog-Tile) — Pool mit den meisten Rest-AP gewinnt | "Noch AP übrig — investieren." | poolabhängig | — |
| 17 | `hint_end_sol` | Fallback — greift nur wenn **kein nutzbarer AP-Pool** mehr übrig ist | "Nichts mehr zu tun — Sol beenden." | Colony-Screen | jedes Sol (Universal-Floor) |

> **Rest-AP-Hinweis ehrlich:** `hint_spend_remaining_ap` empfiehlt „in ein Gebäude investieren“ nur, wenn das auch geht (Tages-Deckel nicht erreicht, Regolith für den nächsten Zyklus vorhanden oder schon bezahlt, Kommandozentrale-Stufe reicht). Sonst sagt er offen, was fehlt, oder verweist auf den Hangar-Pool. Ein Pool ohne irgendeine Verwendung gilt als blockiert und löst den Fallback `hint_end_sol` aus.
>
> **Fertigstellungs-Popup:** Wird erstmals ein Labor, Hangar, Cantina oder die Uplink-Station fertig, erscheint einmalig ein kurzes Popup, das erklärt, was das Gebäude jetzt freischaltet. Einmaligkeit läuft über die bestehende Liste der abgehakten Hints.

> **Bau-Gate-Hinweis (wichtig für Implementierung):** Sobald der Spieler eines der drei Pfad-Gebäude gebaut hat, dürfen `hint_6`/`hint_analytik`/`hint_hangar_path` für die jeweils noch nicht erreichte CC-Stufe nicht feuern — sie greifen erst wieder, wenn `Anzahl gebauter Pfad-Gebäude < CC-Level − 1` zutrifft (siehe §13). Beispiel: Bei CC Lv2 und bereits 1 gebautem Pfad-Gebäude zeigen die beiden übrigen Hints (für die anderen zwei Pfad-Gebäude) **nicht mehr** "jetzt bauen", weil das Bau-Gate sie blockiert — stattdessen sollte ein noch zu definierender Hint-Text "Bei CC Lv3 verfügbar" angezeigt werden (kein Aktions-Link, reine Information), damit der Spieler nicht auf ein Gebäude klickt, das der Server ablehnt.

**Ergänzende Hinweise zur Tabelle:**
- `hint_advisor_slot2` (Rang 7): Direktes Feedback auf den CC-Lv2-Ausbau — feuert garantiert in einen sofort besetzbaren Slot und **vor** den Pfad-Hints (sonst würde nach CC Lv2 zuerst "zweites Pfadgebäude bauen" genagt statt "Berater einstellen").
- **"Pfadwahl offen"** (Gate der Ränge 8–11): Agrardom platziert **und** (noch kein Pfadgebäude platziert **oder** CC ≥ Lv2). Die Rampe will genau EIN Pfadgebäude vor CC Lv2 — solange das erste gebaut wird oder der CC-Ausbau aussteht, schweigen die anderen Pfad-Hints. Ab CC Lv2 dürfen sie wieder feuern.
- `hint_invest_site` (Rang 6) und `hint_explore` (Rang 13): lenken verbleibende AP in die aktive Baustelle bzw. in Erkundung, statt sie verfallen zu lassen.
- `hint_build_priority` (Rang 8): Reine Strategie-Hinweisebene, kein Aktionslink zu einem einzelnen Gebäude — signalisiert nur, dass eine Wahl zwischen mehreren gleichwertig bereiten Gebäuden besteht.
- `hint_end_sol` (Rang 17): Universeller Fallback, der verhindert, dass die Hint-Leiste je leer bleibt.
- `canAffordBuildingPlacement()`-Gate auf den Bau-Hints (Ränge 4, 9–11): Alle Bau-Hints prüfen tatsächliche Bezahlbarkeit (AP, Regolith, Werkstoffe, Supply) bevor sie feuern — verhindert, dass der Hint auf ein Gebäude zeigt, das der Spieler in diesem Sol gar nicht bauen kann.

> **Designentscheidung zu Rang 2 (Reparieren dringend):** Eigener Hint getrennt vom Lehr-Hint `hint_repair` (Rang 5). `hint_repair` wird beim ersten Reparieren-Klick dauerhaft dismissed (Lehrmoment „du kannst reparieren"). `hint_repair_urgent` warnt dagegen **wiederkehrend** vor dem einzigen irreversiblen Verlust (Leveldown bei SP 0): er ist nicht dismissbar (kein Eintrag in `dismissed_hints`), selbst-clearend sobald alle Gebäude wieder über `hint_repair_urgent_sp` liegen, und feuert bei jedem erneuten Verfall. Höchste Repair-Priorität, nur hinter `hint_1` (Baumeister liefert die AP). Die Schwelle gibt selbst beim schnellsten Verfall (Cantina) noch mehr als einen Sol Reaktionszeit und sollte nach Playtest validiert werden.

> **Designentscheidung zu Rang 3 (Harvester):** Der Harvester startet auf Ring-1-Tile (1,0) = `regolith_normal, is_colony_zone=1`. Das ist technisch ein Regolith-Tile, liegt aber in der Kolonie-Zone — die für Gebäude reserviert ist. Der Hint motiviert, ihn auf Ring 2 zu verlegen. Ring-2-Tile (2,0) ist `regolith_normal, is_explored=1` (Nexus-Scout hat es bei Ankunft vorab erkundet, Nexus-Briefing erklärt das). Nach dem Verlegen ist das Ring-1-Tile für Gebäude frei.

> **Designentscheidung zu Rang 5 (Reparieren — Lehr-Hint):** Alle drei Startgebäude starten beschädigt aber funktionsfähig. Dieser Schaden wird **nicht angezeigt**: Schadens-Badge, Statusfärbung und Lehr-Hint greifen erst unterhalb der Sichtbarkeits-Schwelle `game.repair.display_threshold`. Die Startbeschädigung ist damit ein **unsichtbarer Pacing-Timer**: Durch die unterschiedlichen Decay-Raten fällt der Harvester ~Sol 4 unter die Schwelle, das Wohnhabitat ~Sol 6, das CC ~Sol 8 — Repair-Lehrmomente tröpfeln gestaffelt ein, genau nach Abschluss der Sol-1–4-Bau-Rampe, wenn AP-Slack existiert. Ein Hint auf den Startschaden würde nur zum Verbrennen knapper AP einladen. Exakte SP bleiben in der Tile-Sidebar sichtbar — nichts wird versteckt, nur nicht aufgedrängt (§16.4-Transparenzprinzip). Der Hint **verschwindet beim ersten Reparieren-Klick** (dauerhaft dismissed). Der kritische Fall (Leveldown-Gefahr) wird vom separaten, nicht dismissbaren `hint_repair_urgent` (Rang 2) abgedeckt. Ein mechanisches Repair-Gate am Baumeister-Hire gibt es nicht: Es verletzt die Designlinie "Hints sind Hinweise, keine Gates" und würde im Urgent-Fall ohne angestellten Baumeister die einzige Rettungsaktion blockieren.

> **Designentscheidung zu Rang 12 (CC-Lv2-Hint zustandsbasiert):** `hint_3` feuert erst, wenn Agrardom **und** ein Pfadgebäude fertig sind. Grund: CC Lv2 schaltet Beraterslot 2 frei, aber Anheuern in Slot 2–4 braucht ein gebautes Pfadgebäude — wer CC Lv2 vor dem Pfadgebäude baut, sitzt auf einem leeren Slot. Mit dieser Reihenfolge zahlt sich CC Lv2 im selben Sol aus. `hint_cc_upgrade_after_tick` ist nur ein Floor.

> **Designentscheidung zu Rang 1 — "Baumeister zuerst" ist gewollt:** `hint_1` hat keine Tick-Schwelle und keine Alternative und verweist ausschließlich auf den Baumeister. Das ist beabsichtigt. Baumeister/AP ist die strukturell einzige Ressource, die in Sol 1 *alles* andere freischaltet — Wohnraum, Harvester-Verlegung, CC-Ausbau, später jedes Gebäude. Ohne AP-Hebel bleibt der Spieler in Sol 1 handlungsarm, unabhängig vom späteren Pfad. Ein Spieler, der bewusst zuerst Konsul oder Analytiker einstellen will, kann das jederzeit tun — `hint_1` ist dismissable (pro Hint-Typ) und blockiert keine Aktion, er ist der dauerhaft höchstrangige *Hinweis*, kein Gate. Eine *nahegelegte* Reihenfolge ist erlaubt, solange sie nicht erzwungen wird. Echte Wahlfreiheit setzt mit der Pfadwahl ab Sol 2 ein; Sol 1 ist ein linearer Bau-/Erkundungs-Einstieg.

> **Designentscheidung — Sol-1-Fokus: Agrardom zuerst, Pfadwahl ab Sol 2.** Sol 1 gehört nicht dem CC, sondern dem Agrardom; sonst versinken die AP im CC und der Beraterslot nach CC Lv2 bleibt leer.
> - `hint_no_agrardome_after_tick = 0` → der Agrardom ist das **erste Bauprojekt der Kolonie** (Sol 1): platzieren + Rest-AP investieren, fertig Sol 2. Er ist Pflicht-Gate für CC Lv2 und serverseitige Voraussetzung für alle Pfadgebäude.
> - `hint_no_cantina/analytik/hangar_after_tick = 1` → **Pfadwahl ab Sol 2** (sobald der Agrardom platziert ist). Kein Pfad hat strukturellen Vorlauf.
> - CC Lv2 folgt **nach** dem ersten Pfadgebäude (zustandsbasiertes `hint_3`, s. o.) — so ist garantiert, dass der frisch freigeschaltete Beraterslot 2 sofort besetzbar ist.
> - `hint_4` (Kenntnis fehlt, Sol 9) und `hint_5` (Vertrauen kritisch, Sol 6) liegen weit dahinter und tangieren die Rampe nicht.

> **Designentscheidung — echte, gleichwertige Wahl zwischen drei Pfaden (Sciencelab, Hangar, Cantina).** Alle drei Pfad-Hints stehen auf derselben Tick-Schwelle. Keiner hat strukturellen Vorlauf — alle drei werden, sobald ihre Prerequisites (Agrardom platziert für alle drei; Cantina zusätzlich Housing≥Lv1) erfüllt sind, gleichzeitig "bereit". Sobald der Spieler eines gebaut hat, verschwindet dessen Hint dauerhaft; `hint_build_priority` (Rang 8) signalisiert die Wahlsituation, solange ≥ 2 gleichzeitig bereit sind.
>
> **Standard-Empfehlung, keine Zwangsregel:** Wer Cantina zuerst priorisiert, sollte als nächsten Berater eher den **Konsul** anwerben. Wer Sciencelab zuerst priorisiert, eher den **Analytiker**. Wer Hangar zuerst priorisiert, eher den **Raumfahrer**. Diese Zuordnung ergibt sich **mechanisch** aus dem generischen Slot-System (§13): Der Slot, der durch das zuerst gebaute Pfad-Gebäude freigeschaltet wird, *ist* genau dieser passende Beratertyp. Es bleibt **explizit Raum für ausgefuchste Taktiken** (z. B. Pfad-Gebäude über Grund-AP abdecken, ohne den zugehörigen Slot sofort zu besetzen — der Slot bleibt offen, bis der Spieler ihn füllen will). Das deckt sich mit §16.7 "Kein Pflicht-Reihenfolge".

> **Designentscheidung — Agrardom ist Pflichtgebäude, kein Wahlgruppen-Mitglied.** Agrardom ist **CC2-Bau-Voraussetzung** (siehe §4). `hint_agrardome` ist eine Pflicht-Warnung, weil der CC-Ausbau sonst blockiert ist. `hint_build_priority` bezieht den Agrardom **nicht** in die Eligibility-Zählung der Pfadwahl ein — die Wahlgruppe besteht ausschließlich aus den drei echten Pfaden Sciencelab/Hangar/Cantina.

**Deaktivierung:** Das Hint-System kann in den Einstellungen dauerhaft abgeschaltet werden (`onboarding_hints = false` in User-Preferences). Default: aktiviert. Schließen (`[×]`) eines Hinweises deaktiviert nur diesen spezifischen Hinweistyp bis zum Ende des Runs.

> **Designentscheidung:** Das System prüft Zustände, keine Sequenzen. Es gibt keine "abgehakten Tutorial-Schritte" — nur eine kontinuierliche Zustandsauswertung. Das ist wartungsarm und funktioniert ohne State-Maschine.

> **Designentscheidung:** Nur ein Hinweis gleichzeitig, nie eine Liste. Eine Liste erzeugt denselben Paralyseeffekt wie keine Hinweise. Der Spieler braucht eine klare Richtung, keine Aufgabenübersicht.

> **Designnotiz zu `hint_4`:** Der Kenntnis-Hint feuert spät und markiert keinen Eröffnungszwang, sondern eine Sicherheitswarnung für Spieler, die nach vielen Solen noch gar keine Kenntnis erforscht haben — unabhängig davon, ob sie den Cantina-, Analytik- oder Hangar-Pfad gewählt haben. Die Schwelle sollte eher erhöht als gesenkt werden. Die Pfadgebäude-Hints haben deutlich mehr Vorlauf, sodass der Kenntnis-Hint nie vor dem Gebäude-Hint feuert.

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
| 9 (keine Cantina) | Bau-Einstieg für die Cantina in der Kolonieansicht (Ziel des Hint-Links) |
| 11 (kein Hangar) | Bau-Einstieg für den Hangar in der Kolonieansicht (Ziel des Hint-Links) |
| 12 (CC Level < 2) | CC-Tile auf Koloniekarte |
| 14 (kein Wissen) | Analytik-Labor: Bau-Einstieg in der Kolonieansicht (wenn noch nicht gebaut), sonst erste verfügbare Kenntnis-Kachel im Techtree |
| 15 (Vertrauen < -20) | Bau-Einstieg des ersten verfügbaren positiven Vertrauensgebäudes in der Kolonieansicht |

> Eine Gebäude-Kachel im Techtree hat keine Bauaktion (§11). Ein Pulse auf eine Gebäude-Kachel würde auf ein Element zeigen, das die empfohlene Aktion gar nicht ausführen kann. Gebäude-Pulse liegen deshalb in der Kolonieansicht. Im Techtree pulsieren nur Kenntnis-Kacheln, weil dort geforscht wird.

**Deaktivierung:** Zusammen mit dem Hint-System (gleiche Einstellung).

> ⚠️ BALANCE CONCERN / DOKU-DRIFT: Diese Tabelle deckt nur einen Teil der Hint-Ränge ab (siehe § 16.2). Für `hint_repair_urgent`, `hint_advisor_slot2`, `hint_explore`, `hint_build_priority`, `hint_agrardome`, `hint_analytik`, `hint_hangar_path` und `hint_end_sol` ist nicht spezifiziert, welches Element pulsieren soll bzw. ob sie überhaupt einen Pulse erhalten. Muss vor dem nächsten UI-Pass mit `ui-specialist` geklärt werden — insbesondere `hint_end_sol` sollte vermutlich KEINEN Pulse auf eine Kachel legen, sondern (falls überhaupt visuell hervorgehoben) auf den „Sol beenden"-Button.

**Abgrenzung zu bestehenden Indikatoren:** Der Tiefenscan-Pulse auf der Koloniekarte (§ 4a) ist ein anderer Indikator-Typ (orangefarbene Blitz-Animation). Onboarding-Pulse ist blau-weißlich — visuell eindeutig unterscheidbar.

> ⚠️ BALANCE CONCERN: Wenn zu viele Elemente gleichzeitig pulsierten (eigener Scan-Indicator, Onboarding-Pulse, zukünftige Event-Marker), wird die Karte visuell unruhig. Die Regel "nie mehr als ein Onboarding-Pulse gleichzeitig" muss auch auf UI-Ebene durchgesetzt werden.

---

### § 16.4 — Techtree-Kaltstart: Zugangshürde reduzieren

Der Techtree-Screen hat 11 Gebäude-Kacheln, 7 Kenntnisse, 3 Schiffe, 5 Berater — alle auf einmal sichtbar. Das ist für neue Spieler ein Orientierungsproblem.

**Maßnahme: Zustandsbasierte Kachel-Sortierung.**

Kacheln werden in drei Gruppen dargestellt, visuell getrennt durch einen Zwischenstrich und eine kleine Gruppenbezeichnung:

| Gruppe | Inhalt | Darstellung |
|--------|--------|-------------|
| **Jetzt verfügbar** | Gebäude, deren Voraussetzungen erfüllt sind und die sofort in der Kolonie errichtet werden können (Link „In der Kolonie errichten", §11.4), sowie sofort erforschbare Kenntnisse | Normal hell, oben |
| **Voraussetzung fehlt** | Gebäude, die noch gesperrt sind | Gedimmt (Opacity 0.6), Tooltip zeigt was fehlt |
| **Bereits vorhanden** | Gebaute Gebäude | Grüner Statusring, unten oder ausgeblendet |

**Kein separater "Anfänger-Modus"** — das ist die Standarddarstellung für alle Spieler. Erfahrene Spieler profitieren ebenfalls von einer schnellen "Was ist gerade baubar?"-Übersicht.

**Tooltip bei gesperrten Kacheln:** Ein einzeiliger Hinweis direkt auf der Kachel (kein separates Modal): z.B. "Benötigt: CC Lv4" oder "Benötigt: Hangar". Der Tooltip erscheint nur on-hover — nicht dauerhaft als Text auf der Kachel.

> **Designentscheidung:** Die Kacheln werden nicht dauerhaft verborgen oder ausgeblendet — der Spieler sieht immer den gesamten Techtree. "Jetzt verfügbar" herauszuheben ist weniger invasiv als Inhalte zu verstecken. Transparenz über das gesamte System ist ein Nouron-Merkmal.

---

### § 16.5 — Die ersten 3–5 Aktionen: natürlicher Pfad

Der Startzustand (CC Lv1 beschädigt, Harvester Lv1 auf Ring-1, Housing Lv1 beschädigt, 3.000 Cr, 200 Rg) erzwingt einen natürlichen Pfad, wenn der Spieler dem Hint-System folgt. Der Pfad ist nicht zwingend — aber er ist der offensichtlich sinnvolle:

> **Startzustand:** CC, Harvester und Wohnhabitat starten auf Level 1 mit `status_points=16/20` (80 % Zustand) — funktionsfähig und beschädigt, aber **nicht sichtbar beschädigt**: Schadensanzeige und Repair-Hint greifen erst unterhalb der Sichtbarkeits-Schwelle (`game.repair.display_threshold = 0.70`, siehe §16.2 Rang 5). Die Startbeschädigung wirkt als unsichtbarer Pacing-Timer — der Decay bringt Harvester ~Sol 4, Wohnhabitat ~Sol 6, CC ~Sol 8 unter die Schwelle, sodass Repair-Lehrmomente gestaffelt nach der Bau-Rampe eintreffen. Harvester startet auf Ring-1-Tile (1,0) = `regolith_normal, is_colony_zone=1`. Zwei Ring-3-Regolith-Tiles sind vorab erkundet (Nexus-Scout, randomisierte Koordinaten) — ein garantiertes Gegensatzpaar aus hohem Ertrag/geringer Mächtigkeit und niedrigem Ertrag/großer Mächtigkeit, damit der Spieler beim Verlegen des Harvesters eine echte Wahl statt eines einzelnen bekannten Ziels hat.

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

**Aktion 3 — Agrardom platzieren + Rest-AP investieren (Sol 1, `hint_agrardome` → `hint_invest_site`)**

- Warum: Der Agrardom ist Pflicht-Gate für CC Lv2 und serverseitige Voraussetzung für alle Pfadgebäude — er ist das erste Bauprojekt der Kolonie. Ohne ihn bleibt Organika auf 0 (Verpflegungsmechanik §4a).
- Kosten: 40 Rg + 1 AP (Platzieren) + Rest-AP als Invest (Ziel 10 kumuliert)
- Ergebnis: Baustelle Sol 1 bei ~6/10, fertig Sol 2. `hint_invest_site` (Rang 6) lenkt die Rest-AP automatisch in die aktive Baustelle — der Rhythmus "platzieren → fertig investieren → nächstes platzieren" entsteht ohne Sequenz-Skript.
- Parallel: `hint_explore` (Rang 13) lenkt die ungenutzten 6 AP in die Tile-Erkundung.

**Aktion 4 — Pfadgebäude wählen und bauen (Sol 2/3, `hint_6`/`hint_analytik`/`hint_hangar_path`, ggf. `hint_build_priority`)**

- Warum: Ab Sol 2 (Agrardom platziert) stehen Cantina, Sciencelab und Hangar auf derselben Tick-Schwelle — die erste gleichwertige strategische Wahl: Handel, Forschung oder Flotte zuerst. Das Pfadgebäude muss **vor** CC Lv2 stehen, damit der freigeschaltete Beraterslot 2 sofort besetzbar ist (Slots 2–4 verlangen ein gebautes Pfadgebäude).
- Kosten: 70 (Cantina) / 80 (Sciencelab) / 80 (Hangar) Rg + 1 AP Platzieren + 10 Invest-AP
- Ergebnis: Pfadgebäude fertig ~Sol 3. Wer zuerst baut, bekommt automatisch den passenden Berater-Slot-Typ (Sciencelab → Analytiker, Hangar → Raumfahrer, Cantina → Konsul) — die Bau-Entscheidung *ist* die Berater-Entscheidung.
- **Kein permanenter Lockout:** Die anderen Pfade folgen bei höherem CC-Level — die Wahl bestimmt Reihenfolge, nicht endgültigen Zugang.

**Aktion 5 — CC auf Level 2 ausbauen (Sol 3/4, `hint_3`)**

- Warum: Jetzt zahlt sich CC Lv2 sofort aus — Agrardom (Pflicht-Gate) und Pfadgebäude (Slot-Gate) stehen. CC Lv2 schaltet den zweiten Berater-Slot frei + 6 neue Kolonie-Zone-Tiles.
- Kosten: 10 Construction-AP kumuliert + 40 Rg (CC-Upgrade = Ziel-Level × 20 Rg)
- Ergebnis: Koloniekarte aktualisiert sich live (Ring-Expansion §4a); `hint_advisor_slot2` (Rang 7) feuert sofort.

**Aktion 6 — Zweiten Berater einstellen (`hint_advisor_slot2`)**

- Der Slot ist generisch; der Typ ergibt sich aus dem gebauten Pfadgebäude (§13). Kosten 350–500 Cr — Credits sind zu diesem Zeitpunkt reichlich (~2.300+).
- An diesem Punkt hat der Spieler alle Kernsysteme berührt: Berater, Tile-Management, Bauen/Investieren, Pfadwahl, CC-Ausbau, Erkundung.

**Budget-Rechnung Sol 1–4** (10 AP/Sol ab Baumeister-Hire; Harvester-Move worst case 3 AP; Regolith-Start 200, +10/Sol ab Sol 2):

| Sol | AP | Regolith (Endstand) |
|-----|--------|---------------------|
| 1 | Hire (0) + Harvester-Move (3) + Agrardom platzieren (1) + Invest (6) = 10/10 | −40 → **160** |
| 2 | Agrardom-Invest 4 → **Lv1 ✓** + Pfadgebäude platzieren (1) + Invest (5) = 10/10 | +10 −10 −70/80 → **80–90** |
| 3 | Pfad-Invest 5 → **Lv1 ✓** + CC-Invest 5 = 10/10 | +10 −18/20 → **70–82** |
| 4 | CC-Invest 5 → **CC Lv2 ✓** → Berater 2 anheuern | +10 −40 → **37–52** |

> Die Rampe lässt bewusst einen kleinen Regolith-Puffer für Repairs und Housing-Ausbau. Die Hebel dafür sind der Regolith-Satz je CC-Level und der Hangar-Baupreis (Pfad-Gleichwertigkeit: Cantina 70 / Sciencelab 80 / Hangar 80); ein höherer Harvester-Yield wäre ein globaler Hebel mit Trivialisierungsrisiko im Midgame. Der Nexus-Kredit liefert Credits, der Engpass ist aber Regolith. **Offen (nach Playtest):** Nexus-Kredit als eigener Discovery-Moment, sobald die Cantina steht.

**Kein erzwungener Sequenz-Abschluss.** Der Spieler kann jederzeit von diesem Pfad abweichen. Die Hints verschwinden, wenn die jeweilige Bedingung nicht mehr zutrifft.

> ⚠️ BALANCE CONCERN: Baumeister-Kosten (300 Cr, Junior) müssen nach Playtest geprüft werden — 300 Cr von 3.000 Startguthaben ist 10%, sollte kein Problem sein. Einstellungskosten in `config/advisors.php` konfigurierbar.

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
| Harvester-Verlagerung | Erster Klick auf "Verlegen"-Aktion | Tooltip: "Harvester verlegen kostet AP pro Hex Distanz — er kommt nächsten Sol an und produziert unterwegs nichts." |

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

**Konfiguration:** Alle Schwellen des Hint-Systems (Sol-Schwellen der Bau- und Kenntnis-Hints, Vertrauens-Schwelle, SP-Schwelle der Leveldown-Warnung, Erkundungs-Throttle) liegen in `config/game.php → onboarding` (Quelle der Wahrheit; Werte siehe `docs/game-reference.md`). Die drei Pfad-Hints (Cantina, Analytik-Labor, Hangar) teilen sich dieselbe Schwelle.

> **TODO (Implementierung):** User-Preferences-Tabelle benötigt Spalte `onboarding_hints BOOLEAN DEFAULT 1`. Alternativ: Session-Storage für den ersten Run, persistente DB-Einstellung ab zweitem Run.

> **TODO (Design):** Der Nexus-Briefing-Text ist als Entwurf definiert. Finale Formulierung mit dem content-writer abstimmen (Ton: karg, lakonisch, Frontier-Atmosphäre — kein Tutorial-Handbuch-Ton).

> **TODO (Design):** Reihenfolge der ersten freigeschalteten Kenntnis-Slots im Roguelike-Zufallssystem (§ 10) beeinflusst Onboarding — der Kenntnis-Hint muss prüfen ob das Analytik-Labor überhaupt Teil des laufenden Runs ist. Falls nicht: Hint anpassen auf "erste verfügbare Kenntnis".

---
