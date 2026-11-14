# T59 Vertrauen 0–100 — Skala, Stufen, Wirkung

Stand: 2026-11-14 · Basis: Branch `feat/ap-skala` (PR #473, Vertrauen und Seuche dort bereits feste AP) · Status: **entschieden** (Owner 2026-11-14: alle Empfehlungen F1–F6 angenommen, F3 mit Labor-Bedingung, siehe §7), nicht umgesetzt, nicht gemessen.

Kennzeichnung: **(Owner)** = Owner-Entscheidung 2026-11-14 · **(gerechnet)** = eigene Rechnung, ungemessen · **(Annahme)** = eigene Deutung, vom Owner zu bestätigen.

## 0. Owner-Entscheidungen (fest)

| Punkt | Entscheidung |
|---|---|
| Skala | 0 bis 100 (0 = kein Vertrauen, 100 = vollstes), Start 50 **(Owner)** |
| Stufen (untere Grenze inklusive) | 0–14 Aufruhr −2 AP · 15–39 Unruhig −1 AP · 40–59 Stabil ±0 · 60–79 Zufrieden +1 AP · 80–100 Euphorisch +2 AP **(Owner)** |
| Niederlage | Vertrauen unter 5 **(Owner)** |
| Anzeige | rohe Zahl verbergen, stattdessen Wort + Wirkung („Zufrieden · +1 AP“); Datenarchiv darf die Stufentabelle mit AP zeigen **(Owner)** |
| Regel | keine Faktoren, nur kleine absolute Zahlen, additiv **(Owner)** |
| Laufende Läufe | kein Thema, alles Entwicklung (Owner 2026-11-14) — keine Umrechnung, kein Migrationsentwurf |

## 1. Bestand

### 1.1 Wie Vertrauen heute entsteht (wichtig für alles Weitere)

Vertrauen ist **kein angesparter Bestand**, sondern wird jeden Sol neu als Summe berechnet (`TrustService::breakdown()`, `app/Services/TrustService.php:73-84`):

`Vertrauen = clamp(Gebäude + Kenntnisse + Schiffe (gedeckelt) + Ereignisse dieses Sols + Hunger, −100, +100)`

- Gebäude/Kenntnisse/Schiffe: `trust_per_lv` bzw. `trust_per_unit` × Stufe (`TrustService.php:220-284`), alle Werte ≥ 0.
- Ereignisse: Profil `instant` = wirken genau einen Sol (`config/game.php:769-776`, `TrustService.php:309-374`).
- Hunger: −min(2 + (Serie−1)·1, 8) (`TrustService.php:93-105`, `config/game.php:807-809`).
- Gespeichert in `colony_resources` (resource_id 12), überschrieben bei jedem Tick (`TrustService.php:46-56`, `GameTick.php:1668-1700`).

**Folge (Annahme):** Der Owner-„Startwert 50“ wird im bestehenden Modell zu einem **Grundwert 50**, der in jeder Summe steckt (neue Quelle `base` in `breakdown()`). Siehe Owner-Frage F2.

### 1.2 Was den Wertebereich −100..100 voraussetzt

| Stelle | Datei:Zeile | Heute |
|---|---|---|
| Klemme ±100 | `TrustService.php:83` | `max(-100, min(100, …))` |
| Stufenwörter (hartkodiert) | `TrustService.php:152-168` | ≥61 / ≥21 / ≥−20 / ≥−61 — **Widerspruch:** −61 gilt hier als Unruhig, im Config-Band als Aufruhr |
| Docblock | `TrustService.php:12-27` | Bänder, „AP multiplier“ (veraltet seit AP-Skala) |
| Produktionsfaktor | `config/game.php:750-756` | 1,20 / 1,10 / 1,00 / 0,85 / 0,70 |
| AP-Bänder | `config/game.php:759-765` | +1 / 0 / 0 / −1 / −1 |
| Schiffsdeckel | `config/game.php:748`, `TrustService.php:281` | ±30 |
| Ereignisbeträge | `config/game.php:780-795` | +1 … −5 |
| Ereignis-Deckel | `config/game.php:776` | `null` |
| Niederlage | `config/game.php:834`, `RunProgressService.php:635-643` | < −20 |
| Warnstufen | `config/game.php:837-840`, `ShellStateService.php:62`, `RunProgressService.php:594-604` | Chip rot < −10, Nexus-Warnung < −18 |
| Hinweis 5 | `config/game.php:1043`, `OnboardingHintService.php:922-933` | < −20 (= Niederlagengrenze, feuert also praktisch nie) |
| Seuchen-Auslöser | `config/game.php:403`, `GameTick.php:1547-1549` | Serie ≥ 3 oder Vertrauen < −20 (zweiter Zweig = Niederlage, also tot) |
| Onboarding-Auslöser | `GameTick.php:1673-1700` | Übergang von ≥ 0 auf < 0 |
| Siegziel Kolonieblüte | `config/game.php:886`, `RunProgressService.php:256-267`, `lang/de/run.php:11` | Vertrauen > 40, 12 Sole in Folge |
| Punktwertung | `config/game.php:936`, `RunProgressService.php:735-761` | Vertrauen am Ende × 5 |
| Händler-Vertrauensschub | `config/game.php:993`, `MerchantService.php:532-538` | +15 direkt auf den gespeicherten Wert — **Befund:** der nächste Tick überschreibt ihn, der Kauf wirkt höchstens bis zur nächsten Neuberechnung |
| Security-Hub-Dämpfung | `config/buildings.php:347`, `TrustService.php:337-350` | negative Ereignisse × 0,75, gerundet |
| Statische Quellen | `config/buildings.php:235,278,292,306,336`, `config/knowledge.php:67,76,113`, `config/ships.php:47` | Krankenstation 5, Cantina 3, Monument 3, Tempel 3, Leitstelle 2, Agronomie 2, Gesundheit 3, Verteidigung 2, Frachter 2 je Stufe/Einheit |
| Hunger | `config/game.php:806-809` | Basis 2, Schritt 1, Deckel 8 (`well_fed_trust` nur Doku) |
| Startwert | `OnboardingService.php:214`, `data/sql/testdata.sql:39` | 0 |
| Testszenarien | `ResetPlayer.php:35,69,515,555,568-591,655,750` | 25 / 25 / −15 („5 über Grenze −20“) / 60 / 60 — werden vom nächsten Tick ohnehin überschrieben |
| Kolonie-Prüfung | `ValidateColony.php:78` | Warnung < −50 |
| Trockenlauf | `GameTickDryRun.php:156-185` | Farbe nach ≥ 0, Ausgabe „production ×“ |
| Produktionsfaktor-Nutzer | `GameTick.php:956-958`, `ResourceFlowService.php:38`, `ColonyController.php:162-174`, `ColonyTileService.php:514-521`, `GameTickDryRun.php:157`, `tests/Feature/Playtest/BotStrategy.php:1521-1522` | Regolith (Harvester, Geologie) und Organika (Agrardom, Agronomie) |
| Frontend Chip | `frontend/src/lib/components/ResourceBar.svelte:128-159` | zeigt rohe Zahl; Farbe ≥ 20 / < chip_red / < 0 |
| Frontend Leiste | `frontend/src/lib/components/ResourceRail.svelte:113-116` | Warnmarke bei < chip_red |
| Frontend Store | `frontend/src/lib/stores/shell.ts:17,23,34-35` | `trust_chip_red`, `trust`, `trust_breakdown` |
| Bot | `tests/Feature/Playtest/Utility/BotContext.php:67`, `Utility/weights.php:18-21` | Gefahr, wenn Abstand zur Niederlage ≤ 10; Preis je Vertrauenspunkt 2,0 |
| Bot-Auswertung | `app/Support/OpeningComparison.php:224-233`, `tests/Feature/Playtest/RunReport.php:384` | nur Anstieg/Rohwert, skalenneutral |
| Texte | `lang/de/trust.php:5-9`, `lang/de/nexusdb.php:61,79`, `lang/de/resources.php:73`, `lang/de/colony.php:684,691`, `lang/de/comm_log.php:92-114`, `lang/de/run.php:11` | siehe §4 |
| Tests (Auswahl mit festen Werten) | `tests/Feature/TrustServiceTest.php`, `GameTick/GameTickTrustTest.php`, `RunProgressServiceTest.php`, `Resources/ResourcebarTrustChipTest.php`, `Onboarding/OnboardingTriggersTest.php`, `TrustAftereffectTest.php`, `SolReportTest.php`, `MerchantServiceTest.php`, `Colony/StipendTest.php`, `Unit/ColonyTileServiceSolsRemainingTest.php`, `Console/ValidateColonyTest.php`, `Console/ResetPlayerTest.php` | 59 Testdateien erwähnen `trust` |

### 1.3 Befund: Die untere Hälfte war schon bisher tot

Heute liegt die Niederlage (< −20) direkt an der Untergrenze von Stabil. Unruhig, Aufruhr, Produktion 0,85/0,70, AP −1 und der Vertrauens-Zweig der Seuche sind dadurch nie erreichbar, ohne den Lauf zu verlieren. Die größte negative Summe aus bestehenden Quellen ist etwa −18 (Hunger −8, Kolonie bedroht −5, Nexus-Kredit −5) **(gerechnet)**; die Niederlage ist praktisch nur in Kombination erreichbar. Vertrauen wirkt heute als reiner Wohlstandsmesser nach oben.

## 2. Umrechnung

### 2.1 Grundsatz

Neuer Wert = clamp(50 + Σ Quellen, 0, 100) **(Annahme, F2)**. Oberhalb des Grundwerts halbiert sich die Spanne genau (alt 0..100, neu 50..100), die neuen Stufengrenzen liegen ebenfalls bei der Hälfte (Zufrieden alt +21 / neu +10, Euphorisch alt +61 / neu +30). **Halbieren aller Quellen erhält damit die Lage der guten Stufen.**

Rundungsregel: halbieren, halb von null weg runden, Betrag mindestens 1; Stufenleitern (Zulage, Zwischenfälle) bleiben unterscheidbar **(gerechnet)**.

### 2.2 Schwellen

| Schwelle | Alt | Neu | Herleitung |
|---|---|---|---|
| Klemme | −100..100 | 0..100 | (Owner) |
| Grundwert / Start | 0 | 50 | (Owner) |
| Niederlage | < −20 | < 5 | (Owner) |
| Nexus-Warnung (`nexus_warning`) | < −18 | < 10 | Mitte zwischen Aufruhr-Beginn und Niederlage, wie heute knapp vor der Grenze (gerechnet) |
| Chip-Farbe (`chip_red`) | < −10 | entfällt | Farbe folgt der Stufe (§4) |
| Hinweis 5 (`hint_trust_threshold`) | < −20 | < 40 | Eintritt in Unruhig — erste spürbare Wirkung (−1 AP); heute feuert er nie (gerechnet) |
| Onboarding-Auslöser | ≥ 0 → < 0 | ≥ 40 → < 40 | gleiche Bedeutung: erste Stufe mit Abzug (Annahme) |
| Seuchen-Zweig Vertrauen | < −20 | < 15 (Aufruhr) | Seuche als Folge echter Unruhe; Serie ≥ 3 bleibt (Annahme) |
| Kolonieblüte (`task_colony_prosperity.threshold`) | > 40 | ≥ 60 (Zufrieden) | Ziel an Stufengrenze, weil die Zahl verborgen ist; siehe F4 |
| Kolonie-Prüfung (`ValidateColony`) | < −50 | < 15 | Aufruhr (gerechnet) |
| Bot-Gefahrenabstand (`trust_danger_margin`) | 10 über −20 | 10 über 5 | unverändert als Abstand; T58a kalibriert (Annahme) |
| Punktwertung (`trust_multiplier`) | × 5 | × 5 | bleibt; Bestenliste nicht vergleichbar, unkritisch (Entwicklung) |

### 2.3 Ereignisbeträge

| Ereignis | Alt | Neu (gerechnet) |
|---|---|---|
| `building_level_up` | +1 | +1 |
| `research_level_up` | +2 | +1 |
| `trade_success` | +2 | +1 |
| `treaty_signed` | +3 | +2 |
| `well_fed` | +2 | +1 |
| `encounter_won` | +2 | +1 |
| `stipend_small` / `_medium` / `_large` | +2 / +3 / +4 | +1 / +2 / +3 (Leiter erhalten, strikt halbiert wäre Mittel = Groß) |
| `story_concern_resolved` | +3 | +2 |
| `story_concern_failed` | −2 | −1 |
| `encounter_lost` | −4 | −2 |
| `colony_threatened` | −5 | −3 |
| `nexus_credit` (auch `economy.nexus_credit_trust_penalty`, `game.php:349`) | −5 | −3 |
| Händler-Vertrauensschub | +15 (verpufft) | als Ereignis `merchant_trust_boost` +8, siehe F5 |

### 2.4 Statische Quellen und Deckel

| Quelle | Alt je Stufe | Neu je Stufe (gerechnet) | Neu Maximum |
|---|---|---|---|
| Krankenstation (max 3) | 5 | 3 | 9 |
| Cantina (max 3) | 3 | 2 | 6 |
| Monument (max 1) | 3 | 2 | 2 |
| Tempel (max 1) | 3 | 2 | 2 |
| Leitstelle (max 3) | 2 | 1 | 3 |
| Agronomie (max 5) | 2 | 1 | 5 |
| Gesundheit (max 5) | 3 | 2 | 10 |
| Verteidigung (max 5) | 2 | 1 | 5 |
| Frachter (je Einheit) | 2 | 1 | – |
| Schiffsdeckel `ships_cap` | 30 | 15 | – |
| Hunger Basis / Schritt / Deckel | 2 / 1 / 8 | 1 / 1 / 4 | – |

Summe aller Gebäude- und Kenntnisquellen: alt 71, neu 42 **(gerechnet)**. Die Aufrundung ungerader Werte hebt die positive Seite um rund 10 % gegenüber exakter Halbierung; bewusst in Kauf genommen (kleine ganze Zahlen vor Genauigkeit).

Einordnung **(gerechnet aus Bot-Notizen `BotStrategy.php:1122-1126`, nicht gemessen)**: Bot ohne Vertrauensgebäude lag bei 17–20 → neu ≈ 59–60 (Kante Stabil/Zufrieden); mit Vertrauensgebäuden 45–55 → neu ≈ 72–78 (Zufrieden). Euphorisch (≥ 80) braucht +30 aus Quellen, also fast alles Vertrauensrelevante ausgebaut.

### 2.5 Folge für die untere Hälfte

Mit reiner Halbierung ist die größte negative Summe etwa −11 (Hunger −4, Kolonie bedroht −3, Nexus-Kredit −3, Anliegen gescheitert −1) **(gerechnet)**. Tiefster Wert ≈ 39: Unruhig für einen Sol, **Aufruhr und Niederlage sind unerreichbar** — wie heute, nur ehrlicher sichtbar. Das widerspricht vermutlich der Absicht hinter −2 AP und „Niederlage unter 5“. Vorschlag in F1: Hunger als einzige anhaltende Abwärtskraft, als getrennter, gemessener Schritt.

**Variante H1 (Hunger eskaliert), ein Hebel:** Basis 2, Schritt 4, kein Deckel außer der Klemme **(gerechnet)**. Verlauf bei Quellen 0 (früh): 48, 44, 40, 36 (Unruhig ab 4. Hungersol), … 12 (Aufruhr ab 10.), 4 (Niederlage ab 12.). Bei Quellen +20 (Mitte): Unruhig ab 9., Aufruhr ab 15., Niederlage ab 17. Hungersol. Erwartete Wirkung, die eine Messung widerlegen kann: Läufe mit langer Hungerserie erreichen Unruhig; Niederlagen durch Vertrauen < 10 % der Läufe **(Annahme)**.

## 3. Produktion

### 3.1 Heute

Faktor auf Regolith (Harvester, Geologie) und Organika (Agrardom, Agronomie), gerundet. Live sind nur 1,10 (alt ≥ 21 = neu ≥ 60) und 1,20 (alt ≥ 61 = neu ≥ 80); die Abzüge sind tot (§1.3). Größenordnung **(gerechnet)**: Harvester 16/23/30 Rg → +2/+2/+3 bzw. +3/+5/+6; Agrardom Stufe 3 (16+20+30 = 66 Org) → +7 bzw. +13.

### 3.2 Empfehlung: Produktions-Wirkung entfällt ganz (Variante P0)

1. **Doppelte Belohnung für denselben Zustand.** Die lebenden Faktor-Bänder fallen fast genau auf die neuen AP-Stufen Zufrieden/Euphorisch. Wer dort ist, bekäme AP und Rohstoffe — reich wird reicher.
2. **AP reicht als Hebel.** +1/+2 AP auf einem Pool von 4–9 AP sind 11–50 % der Tagesleistung **(gerechnet)**; das ist bereits der stärkste Einzeleffekt im Spiel.
3. **Keine Abwärtsspirale über Nahrung.** Ein Organika-Abzug in Unruhig/Aufruhr würde Hunger verlängern, Hunger senkt Vertrauen — mit H1 eine harte Spirale.
4. **Owner-Regel und Verständlichkeit.** Ein Faktor mit Rundung ist das Gegenteil von „angezeigte Zahl = wirkende Zahl“. Fünf Codepfade und die Erschöpfungsschätzung (`ColonyTileService::solsRemaining`) werden einfacher.

Preis: Gegenüber heute verliert eine zufriedene Kolonie etwa 2–3 Rg und bis zu 7 Org je Sol, eine euphorische bis 6 Rg und 13 Org; dafür gewinnt sie +1 AP (Zufrieden, heute 0) bzw. +2 AP (Euphorisch, heute +1) **(gerechnet)**. Netto vermutlich ein Gewinn; Organika-Vorrat in der Messung beobachten.

### 3.3 Rückfall (Variante P1), falls die Messung Organika-Knappheit zeigt

Fester Summand nur auf Organika, nur positiv: Zufrieden +2, Euphorisch +4 Org je Sol, unten 0 **(gerechnet)**. Keine Regolith-Wirkung (Regolith-Budget T31 bleibt unberührt), keine negativen Stufen (keine Spirale). Anzeige im Organika-Popup als eigene Zeile „Vertrauen +2“.

### 3.4 Weitere Faktoren rund um Vertrauen

| Faktor | Ort | Vorschlag |
|---|---|---|
| Security-Hub-Dämpfung × 0,75 | `config/buildings.php:347`, `TrustService.php:337-350` | Absolut: negative Ereignisse −1 näher an 0 (z. B. −3 → −2, −1 → 0). Mit halbierten Werten wäre × 0,75 nach Rundung meist wirkungslos (−2 → −2) **(gerechnet)** |
| Abklingprofil `fade5` in % | `config/game.php:771` | ungenutzt; falls belegt, als absolute Folge je Ereignis statt Prozent (bei Beträgen 1–3 rundet Prozent willkürlich) |
| Seuchen-Wahrscheinlichkeit 5 %/Sol, Krankenstation −8 %/Stufe relativ | `config/game.php:403`, `config/buildings.php:250-251` | Wahrscheinlichkeiten, kein Wirkungsfaktor; nicht Teil von T59. Optional später absolut: −1 Prozentpunkt je Stufe |
| Punktwertung × 5 | `config/game.php:936` | Wertungsformel, kein Spieleffekt; bleibt |
| Besetzungsanteil `staffingShare` | `GameTick.php:957-958` | Faktor, aber nicht vertrauensbezogen; eigener Punkt, falls die Owner-Regel auch dort gelten soll |

## 4. Anzeige und Texte

- **Eine Quelle für Stufen:** neue Config-Liste `trust.bands` (min, Wortschlüssel, AP). `getBand()`, `getApBonus()`, Chip-Farbe und Datenarchiv lesen nur sie; behebt den −61-Widerspruch.
- **Ressourcenleiste (Chip):** „Zufrieden · +1 AP“ statt Zahl. Farbe nach Stufe: Zufrieden/Euphorisch positiv, Stabil neutral, Unruhig Warnung, Aufruhr negativ. `chip_red` und `trust_chip_red` entfallen; Warnmarke der Leiste (`ResourceRail.svelte:116`) bei Aufruhr.
- **Popup:** Stufe mit Wirkung, darunter die Quellen wie heute (Grundwert, Gebäude, Kenntnisse, Schiffe, Ereignisse, Hunger) und eine Fünf-Felder-Leiste mit Markierung der aktuellen Stufe; Gesamtzahl siehe F3.
- **AP-Popup:** Zeile „Vertrauen“ zeigt den AP-Summanden und das Stufenwort („Vertrauen (Zufrieden) +1“).
- **Datenarchiv** (`nexusdb.concept_trust_body`): „0 bis 100“ stimmt dann; Text neu mit Stufentabelle (Spannen + AP), Grundwert 50, Niederlage unter 5; Satz zur „Produktionseffizienz“ streichen (P0). `concept_colonists_body` ebenso ohne Produktionsabzug.
- **Weitere Texte:** `resources.popup_trust_desc` (Produktionsandeutung → AP), `colony.sol_report_plague_detail_trust` (Wort statt Zahl), `run.task_colony_prosperity` („mindestens Zufrieden halten, :target Sole in Folge“), Nexus-Warnung unverändert inhaltlich. Zulage-Chip und Sol-Bericht zeigen weiterhin Punkte („+2 Vertrauen“) — Punkte sind die Bewegung, die Stufe der Zustand (Annahme).
- **GDD §14, `docs/game-reference.md`, GLOSSAR** (Eintrag „Vertrauensstufe“) mit der Umsetzung nachziehen.

## 5. Laufende Läufe

Kein Thema, alles Entwicklung (Owner 2026-11-14). Startwerte in `OnboardingService.php:214` und `testdata.sql:39` auf 50, Szenarien in `ResetPlayer.php` samt Kommentaren neu (Vorschlag: pre-phase2/phase2 62, near-fail-trust 8, near-deadline/objectives-done 75) **(gerechnet)**; Hinweis im Kommentar, dass der nächste Tick den Wert neu berechnet.

## 6. Risiken und Messvorschlag

1. **AP-Wirkung wird deutlich stärker.** Zufrieden bringt +1 AP, wo heute 0; Euphorisch +2 statt +1. Bei 4–9 AP Pool beschleunigt das Phase 2 spürbar **(gerechnet)**; Siegziel-Kalibrierung (Sieg Sol 70–85) kann kippen.
2. **Abwärtsspirale (nur mit H1).** Aufruhr −2 AP + Seuche −1 AP = −3 AP auf 4–9 AP, während Organika fehlt. Gegenmittel im Entwurf: kein Produktionsabzug (P0), Hunger-Serie endet sofort beim ersten satten Sol, Seuche nur 5 %/Sol.
3. **Ohne H1 sind Niederlage und Abzüge Dekoration** (§2.5) — Spieler sieht fünf Stufen, erlebt drei.
4. **Ereignisse wiegen kaum noch.** Beträge 1–3 für einen Sol gegen Stufenbreiten 20–25; Zulage für 600 Cr bringt +3 für einen Sol. Bestehendes Problem, durch Halbierung sichtbarer; Abklingprofile (T40) sind der vorgesehene Hebel, nicht Teil von T59.
5. **Bot-Abhängigkeit (offene Variable):** Ob Bots Vertrauensgebäude bauen, Hunger vermeiden und Zulagen kaufen, bestimmt jede Messung. T58a (Bot auf AP-Einheiten) muss zuerst fertig sein.

**Messvorschlag (nur mit Owner-Freigabe, nach T58a):** M-T1 = Umstellung mit P0 und H0 (reine Halbierung), 24 Läufe, Profil default. Kennzahlen aus `RunReport` (Vertrauen je Sol): Sole je Stufe, erster Sol Zufrieden/Euphorisch, Summe Vertrauens-AP je Lauf, Organika-Vorrat und längste Hungerserie, Niederlagen durch Vertrauen, Sol der Kolonieblüte. Hypothesen **(gerechnet)**: 0 Vertrauens-Niederlagen; Zufrieden bei Läufen mit Vertrauensgebäuden ab Phase 2; Vertrauens-AP 20–50 je Lauf. M-T2 = H1 allein dazu, gleiche Kennzahlen.

## 7. Owner-Fragen — entschieden 2026-11-14

Alle Empfehlungen angenommen. **F3 geändert (Owner):** Vertrauen als Zahl nur mit Analytik-Labor (Stufe ≥ 1). Ohne Labor: Chip und Popup zeigen Wort + AP-Wirkung und die Stufenleiste, die Quellen nur als Namen ohne Beträge, keine Summenzeile *(Annahme: Beträge der Quellen würden die Zahl verraten)*. Mit Labor: Quellen mit Beträgen und Summenzeile „62 Punkte, Zufrieden ab 60“. Muster wie Labor-Vorschau; der Owner hält es auch für andere Bereiche für denkbar (eigene Idee, nicht Teil von T59).


- **F1 Soll die untere Hälfte erreichbar sein?** Mit reiner Halbierung erreicht keine Kolonie Aufruhr oder die Niederlage (§2.5). *Empfehlung:* ja, über eskalierenden Hunger (H1), aber als eigener Schritt nach M-T1, damit die Messung Skala und Hunger trennt.
- **F2 „Startwert 50“ als Grundwert?** Vertrauen wird jeden Sol neu summiert; 50 steckt dann dauerhaft in jeder Summe. Ein angesparter Bestand wäre ein anderes, größeres Modell. *Empfehlung:* Grundwert 50, im Popup als eigene Zeile „Grundwert“.
- **F3 Gesamtzahl im Popup?** Rohe Zahl ist verborgen, die Quellen im Popup ergeben sie aber rechnerisch. *Empfehlung:* Chip nur Wort + AP; im Popup Quellen und Stufenleiste zeigen, die Summe als kleine Zeile „62 Punkte, Zufrieden ab 60“ — angezeigte Zahl = wirkende Zahl.
- **F4 Kolonieblüte an Stufe koppeln?** Alt „> 40“ entspricht neu 70, mitten in Zufrieden und für Spieler unsichtbar. *Empfehlung:* „mindestens Zufrieden, 12 Sole in Folge“ (≥ 60, leichter als heute), Serienlänge erst nach Messung anpassen.
- **F5 Händler-Vertrauensschub reparieren?** Heute verpufft er nach einem Tick. *Empfehlung:* als Ereignis `merchant_trust_boost` mit +8, wirkt 5 Sole (8, 6, 5, 3, 2), damit 600 Cr spürbar bleiben; Label „Vertrauensschub“ ohne Zahl.
- **F6 Produktion:** *Empfehlung* P0 (entfällt ganz), Rückfall P1 (Organika +2/+4) nur bei gemessener Knappheit.
