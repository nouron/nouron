# Fund-Bergung ohne Sol-Deckel (Entscheidungsvorlage)

**Stand:** 2026-10-13, Fassung 2 (nach Owner-Entscheid) · **Autor:** game-designer (Analyse + Vorschlag, kein Code, keine Config-Änderung) · **Bezug:** T30 Spec `2026-10-05-t30-gleichwertige-eroeffnungen.md` §9, §11, §13 · Pläne `2026-10-09-t30-pool-v1-fundpool.md`, `2026-10-11-t30-schritt3-hangar-b1-bergungsflug.md`

**Owner-Wunsch (2026-10-13, sinngemäß):** Die Bergung in Pool v1 ist falsch umgesetzt. Ein mittlerer Fund braucht heute mindestens 4 Sole. Gewollt ist: Sondieren (2 AP) zeigt den Fund, dann eine Bergung mit deutlich weniger AP und **ohne Sol-Deckel**. Die Idee „Sockel/Deckel pro Sol" könnte eventuell bei Gebäudebau und Forschung greifen.

## Owner-Entscheid 2026-10-13 (verbindlich, ersetzt die Fassung 1 dieser Vorlage)

1. **Selbstbergung:** eine Sofort-Aktion zu **3 / 4 / 6 AP** (klein/mittel/groß). Kein Sol-Deckel, kein Projektlimit.
2. **Strenge Ablösung bleibt** wie am 2026-10-11 gebaut: Sobald die Hangar-Halle Stufe 1 hat, ersetzt der Bergungsflug die Selbstbergung. Begonnene Selbstbergungen fallen unter Bestandsschutz. Die Koexistenz-Variante H-A aus Fassung 1 ist **abgelehnt**.
3. **Kein Ertragsfaktor mehr:** `game.finds.flight.yield_factor` 1,5 → 1,0. Der Fund bringt per Flug genau so viel wie zu Fuß.
4. **Der Flug soll deutlich weniger AP kosten als die Selbstbergung.** Begründung des Owners: Die Selbstbergung wurde als Überbrückung der Leerlauf-Sole und als ausgeglichene Regolith-Quelle für alle drei Pfade eingeführt. Den Hangar mit Drohne erreicht man jetzt deutlich früher.

Zahlen in diesem Dokument sind **Config** (gelesen am 2026-10-13), **Messwert** (T30-Spec bzw. CHANGELOG) oder **Vorschlag**. Eigene Deutungen und Handrechnungen sind als **Annahme** markiert. Für das GDD gilt ADR 0004: Dort steht nur zahlenfreie Prosa, die Werte gehören in `config/game.php` und `docs/game-reference.md`.

---

## 1. Ist-Zustand und warum er sich falsch anfühlt

### 1.1 Mechanik heute (Code und Config gelesen)

| Element | Ist | Quelle |
|---|---|---|
| Start-Pool | 2 Fehlalarme, 3 klein, 1 mittel, 1 groß = 40 Rg | `game.finds.start_pool`, `types` |
| Tiefenscan | 2 AP (1 AP ab Uplink Lv2) | `finds.scan_ap`, `ColonyTileService::deepScanCost()` |
| Selbstbergung | Projekt: je Kachel **eine** Einzahlung pro Sol (`salvage_tick`), höchstens `salvage_cap_per_sol` = 4 AP, höchstens 2 offene Projekte. Das Rg kommt erst beim Abschluss. | `ColonyTileService::salvageFind()` |
| Lage | Nur der **große** Fund bevorzugt Ring 3. Mittel und klein liegen zufällig auf Ring 2/3, wegen des Feldangebots meist auf Ring 2. Die Annahme in §11.3, dass auch „mittel" Ring 3 bevorzugt, deckt sich nicht mit `FindPoolService::SIZE_ORDER`. | `FindPoolService` |
| Mit Hangar ≥ 1 | strenge Ablösung: nur Bergungsflug (heute ×1,5, kein Wurf). Begonnene Selbstbergung läuft zu Ende. Großer Fund gesperrt bis Stufe 2 und Frachter. | T30 §13.4, `findAction()` |
| Bergungsflug | Nav-AP = Entfernung × 2 (Ring 2: 2 AP, Ring 3: 4 AP), Proviant = Entfernung × 3 Or, Umlauf 2 bzw. 4 Sole. Verschleiß Drohne 1,5 SP/Sol, Frachter 1,0; Reparatur 1 AP = 2 SP. | `missions.php`, `ships.php`, `game.ship_repair`, `FindFlightService::costs()` |

### 1.2 Rechenbeispiel Selbstbergung (Config)

| Fund | Rg | Bergungs-AP | Mindestdauer (4 AP/Sol) | Rg je Bergungs-AP | Rg je AP inkl. Scan |
|---|---|---|---|---|---|
| klein | 4 | 12 | 3 Sole | 0,33 | 0,29 |
| mittel | 10 | 16 | 4 Sole | 0,63 | 0,56 |
| groß | 18 | 22 | 6 Sole | 0,82 | 0,75 |
| **Pool gesamt** | 40 | 74 + 14 Scan = 88 | **≥ 10 Sole** (19 Projekt-Sole auf 2 Spuren) | – | 0,45 |

Wer den ganzen Pool selbst birgt, klickt also mindestens 10 Sole lang an jedem Sol bis zu zweimal. Das sind 19 Klick-Sole für 40 Rg.

### 1.3 Warum es sich falsch anfühlt

1. **Klicks ohne Entscheidung.** Die Entscheidung fällt einmal, beim ersten Einzahlen. Alle weiteren Sole sind Pflicht-Wartung.
2. **Späte Rückmeldung.** Der Spieler sieht „10 Rg" und bekommt sie frühestens 4 Sole später.
3. **Falsche Größenordnung.** Ein mittlerer Fund (16 AP) kostet mehr als eine Gebäudestufe (10 AP), ein großer (22 AP) mehr als ein CC-Ausbau.
4. **Schlechter Kurs.** Mit 0,3–0,8 Rg je AP ist der kleine Fund faktisch eine Falle.
5. **Herkunft des Deckels.** Den Deckel habe ich in §9.3 als Messhebel für K4 eingeführt, nicht wegen des Spielerlebnisses.
6. **Kacheln bleiben lange blockiert.** Ein ungeborgener Fund sperrt sein Feld 3–6 Sole lang.

---

## 2. Selbstbergung: Soll-Mechanik (vom Owner bestätigt)

### 2.1 Ablauf

1. Aufdecken (unverändert) zeigt das Signal.
2. **Sondieren** (Tiefenscan, unverändert, Scan-AP laut Config) zeigt Typ und Menge.
3. **Bergen ohne Hangar: eine Aktion, sofort.** Der Spieler zahlt 3/4/6 AP, das Regolith wird im selben Moment gutgeschrieben, die Kachel wird frei. Es gibt keinen Projektzustand, keinen Tagesdeckel und kein Projektlimit. Die Fundkarte zeigt die AP als Chip am Knopf (Konvention AP-Kosten-Chip). Das Eingabefeld „AP für die Bergung" und die Zeile „Deckel x AP pro Sol" entfallen.

**Bestandsschutz unter der neuen Regel (Annahme, Folgerung):** Eine Sofort-Bergung kann nicht mehr „begonnen" sein. Der Bestandsschutz greift deshalb nur noch für Altstände (Projekte mit `salvage_ap_spent` > 0 aus Läufen vor der Umstellung). Da Neustarts nach einem Baseline-Wechsel ohnehin üblich sind (`db:reset`), kann der Zweig `grandfathered` beim Umbau entfallen. Die Regel „mit Hangar ≥ 1 nur Flug" bleibt davon unberührt.

### 2.2 Staffel 3 / 4 / 6 im Vergleich

| Variante | Bergungs-AP klein/mittel/groß | Rg je Bergungs-AP | Rg je AP inkl. Scan | AP für den ganzen Pool (inkl. 7 Scans) | Pool-Kurs |
|---|---|---|---|---|---|
| Ist | 12 / 16 / 22 (+ Deckel) | 0,33 / 0,63 / 0,82 | 0,29 / 0,56 / 0,75 | 88 | 0,45 |
| **Owner 3 / 4 / 6** | 3 / 4 / 6 | 1,33 / 2,5 / 3,0 | 0,8 / 1,67 / 2,25 | 14 + 19 = 33 | 1,21 |

Der Kurs steigt mit der Fundgröße, „größer ist besser" bleibt lesbar.

### 2.3 Vergleich mit anderen Regolith-Quellen (Rg je AP)

| Quelle | Rechnung | Rg je AP | Einordnung |
|---|---|---|---|
| Harvester-Verlegung | ca. 6 AP (3 Hexe) → y2 26 Rg/Sol über die Lebensdauer der Kachel | ≈ 75 (passiv) | Grundversorgung, nicht vergleichbar |
| Geologie Stufe 1 | 20 AP → +3 Rg/Sol, bis Sol 25 ca. 57 Rg (diskontiert ≈ 50, Utility-Spec §2.5) | ≈ 2,5–2,9 | dauerhaft, wächst mit Stufen |
| Fen-Anliegen (Cantina) | 2 AP, 50 % × 20–30 Rg | ≈ 6 (Erwartung) | selten, zufällig |
| Regolith-Kauf (Bar/Import) | Credits statt AP, 25–35 Cr/Rg | – | Credits-Kanal |
| Rg-Missionen | Prospektion gestrichen, Versorgungsfahrt ohne Rg (E8) | – | nur Fernexpedition (Kartografie 3) |
| **Selbstbergung 3/4/6** | siehe 2.2 | **0,8 / 1,67 / 2,25** (inkl. Scan) | endlich, 40 Rg je Run |
| **Bergungsflug (Vorschlag FC, Abschnitt 3)** | 1 AP je Flug, ohne Verschleiß, Proviant 1 Or je Sol Entfernung | **1,33 / 3,33 / 6,0** (inkl. Scan) | endlich, dieselben 40 Rg |

**Lesart:** Ein hoher Kurs ist hier unkritisch, weil der Pool **endlich** ist. Der Kurs bestimmt nur, *wann* die 40 Rg ankommen und wie viele AP sie binden, nicht *wie viel* Regolith es gibt.

> ⚠️ BALANCE CONCERN: Das gilt nur, solange der Pool fest bleibt. Kommen Nachschub-Signale (T30 Schritt 6) dazu, wird jede Signalquelle zu einer billigen Rg-Rate, beim Flug sogar zu einer sehr billigen. Der Nachschub muss dann über die **Rg-Menge je Run** begrenzt werden, nicht über die AP.

---

## 3. Bergungsflug: AP-Staffel ohne Ertragsfaktor

### 3.1 Ausgangslage: Mit heutigen Flugkosten ist der Flug teurer als die neue Selbstbergung

Der Ertrag ist jetzt gleich (Faktor 1,0). Vergleicht man die heutigen Flugkosten mit 3/4/6, gilt (Lage: klein/mittel Ring 2 mit Drohne, groß Ring 3 mit Frachter):

| Fund | Selbst | Flug heute: Nav + Reparatur | Proviant | Flug gegen selbst |
|---|---|---|---|---|
| klein | 3 AP | 2 + 1,5 = 3,5 AP | 3 Or | **teurer** (+0,5 AP, +3 Or, 2 Sole Wartezeit) |
| mittel | 4 AP | 2 + 1,5 = 3,5 AP | 3 Or | etwa gleich (−0,5 AP, +3 Or) |
| groß | 6 AP | 4 + 2 = 6 AP | 6 Or | gleich AP, +6 Or, Stufe 2 + Frachter nötig |

Ohne neue Flugkosten wäre der Hangar unter der strengen Ablösung bei jedem Fund ein Nachteil. Die Flugkosten müssen also sinken. Die **Reparatur** ist dabei der größte versteckte Posten: Allein der Verschleiß eines Ring-2-Flugs kostet 1,5 AP, also die Hälfte einer kleinen Selbstbergung.

### 3.2 Varianten der Flug-Staffel

Alle Varianten: Ertrag = Fundmenge (Faktor 1,0), kein Wurf, Träger unverändert, Rückkehr-Garantie unverändert (bei FC für Bergungsflüge trivial erfüllt). Effektive AP enthalten die Reparatur des Verschleißes (1 AP = 2 SP).

| Variante | Regel | klein (Ring 2, Drohne) | mittel (Ring 2, Drohne) | groß (Ring 3, Frachter) | Pool-AP Flug (ohne Scans) | Ersparnis gegen 19 AP selbst |
|---|---|---|---|---|---|---|
| Heute | Nav = Entfernung × 2, Verschleiß, 3 Or/Sol | 3,5 AP + 3 Or | 3,5 AP + 3 Or | 6 AP + 6 Or | 20,5 | −1,5 (teurer) |
| FA „halbe Staffel" | Nav = 1/2/3 nach Fundgröße, Verschleiß und Proviant wie heute | 2,5 AP + 3 Or | 3,5 AP + 3 Or | 5 AP + 6 Or | 16 | 3 |
| FB „pauschal 1 AP" | Nav = 1 je Flug, Verschleiß wie heute, Proviant 1 Or je Sol Entfernung | 2,5 AP + 1 Or | 2,5 AP + 1 Or | 3 AP + 2 Or | 13 | 6 |
| **FC „pauschal 1 AP, ohne Verschleiß"** | Nav = 1 je Flug, Bergungsflüge verschleißen nicht, Proviant 1 Or je Sol Entfernung | **1 AP + 1 Or** | **1 AP + 1 Or** | **1 AP + 2 Or** | **5** | **14** |

Rg je AP (inkl. 2 AP Scan) zum Vergleich mit der Selbstbergung (0,8 / 1,67 / 2,25):

| Variante | klein | mittel | groß |
|---|---|---|---|
| FA | 0,89 | 1,82 | 2,57 |
| FB | 0,89 | 2,22 | 3,6 |
| **FC** | **1,33** | **3,33** | **6,0** |

Bewertung:
- **FA** erfüllt „deutlich weniger AP" nicht. Die Reparatur frisst die Ersparnis auf, der kleine Fund bleibt fast gleich teuer.
- **FB** ist bei mittel und groß deutlich billiger, beim kleinen Fund nur knapp. Der Verschleiß bleibt als Risiko-Element, kostet aber pro Flug weiter Reparatur-AP und Klicks.
- **FC** erfüllt den Owner-Wunsch klar: Jeder Flug kostet 1 AP, unabhängig von Größe und Ring. Die Wirkung lässt sich in einem Satz erklären („Ein Bergungsflug kostet 1 AP und etwas Proviant"). Die Ringentfernung wirkt nur noch über die **Flugzeit** und den Proviant. Das ist der eigentliche Preis des Flugs: Das Rg kommt 2–4 Sole später, und das Schiff ist so lange belegt. **Empfehlung.**
  - Begründung für „ohne Verschleiß": Funde liegen im eigenen Nahbereich (Ring 2/3). Verschleiß bleibt das Risiko echter Außenmissionen (§8b). Nebenwirkung: Der Grund `ship_worn` tritt bei Funden praktisch nicht mehr auf, die Bot-Regel `repair_ship_for_flight` wird für Funde selten gebraucht, und K16 `no_ship_ready` wird ein reiner Bug-Detektor.
  - Nachteil: eine Sonderregel („Bergungsflüge verschleißen nicht"). Sie steht in der Fundkarte als Kostenzeile („1 AP · 1 Or · kein Verschleiß · zurück Sol N").

### 3.3 Wegfall des Ertragsfaktors

- `game.finds.flight.yield_factor` 1,5 → 1,0. **Vorschlag:** den Schlüssel ganz entfernen statt 1,0 stehen zu lassen. Eine Zahl, die nichts tut, verleitet zum Nachjustieren.
- `FindFlightService::findFlightYield()` liefert weiter additive Zeilen, aber ohne `flight_bonus`. Die Zeilenstruktur bleibt für den Gutschein aus Schritt 5 (§13.6, Cantina → Hangar) erhalten. Der Gutschein bleibt damit die einzige Quelle eines Flug-Mehrertrags. Ob er unter der neuen Logik Ertrag oder AP gutschreibt, entscheidet Schritt 5.
- Die Texte „bringt mehr Regolith heim als die Kolonie zu Fuß" (GDD §4a, `find_help`, `onboarding_hint_find_flight`) werden falsch. Neue Kernaussage: „Mit Hangar holen Schiffe die Funde: kaum AP, dafür Flugzeit und Proviant."
- Die Pool-Summe per Flug sinkt von 60 auf 40 Rg. Das senkt die Gesamt-Rg der Hangar-Läufe um bis zu 20 Rg gegenüber Schritt 3.

### 3.4 Sperre großer Funde bis Hangar Stufe 2

Ohne Ertragsfaktor ist die Sperre ein **reiner Nachteil**: Ein Spieler ohne Hangar birgt den großen Fund für 6 AP sofort. Ein Spieler mit Hangar Stufe 1 kommt erst nach Stufe 2 **und** dem Frachterkauf (Credits laut `config/ships.php`) an dieselben 18 Rg. Am stärksten trifft das Hangar-First, weil dort die Halle ab etwa Sol 3 steht.

| Option | Inhalt | Wirkung |
|---|---|---|
| L1 Sperre bleibt | wie gebaut | Hangar-First verliert bis zum Frachter 18 Rg (≈ 0,7 Harvester-Sole), der Frachter behält eine Fundrolle. Die Zonen-Blockade (K17) bleibt möglich. |
| **L1 + Generatorregel** | Sperre bleibt; der Generator legt große Funde nie auf eine Kachel, die je zur Kolonie-Zone gehören kann (§13.10 Option a) | Die K17-Blockade entfällt per Konstruktion, ohne Ausnahme in der Bergungsregel. Der Rg-Nachteil von Hangar-First bleibt (Wartezeit, kein Verlust). **Default.** |
| L2 Drohne trägt alles | `find_carry` der Drohne um `find_large` erweitern, `min_hangar_level.find_large` = 1 | Kein Nachteil mehr, einfachste Regel. Der Frachter hat dann keine Fundrolle mehr. Seine einzige Mission ist die Organika-Versorgungsfahrt, die laut §13.3 schon fast wertlos ist. Er wird damit überflüssig. |

Für L1 spricht die Owner-Entscheidung vom 2026-10-11 („gewollt") und die Rolle des Frachters. Für L2 spricht die Parität. Ich empfehle L1 mit Generatorregel und messe K16 (Wartesole `needs_level`/`needs_freighter`). Zieht sich die Wartezeit in Hangar-First über Sol 15 hinaus, folgt L2.

### 3.5 Hangar-Vorteil jetzt über AP-Ersparnis: reicht das für die T30-Parität?

**Nein, nicht allein.** Die Rechnung ist eindeutig:

- Die gesamte Selbstbergung des Start-Pools kostet nur noch **19 AP**. Selbst ein Flug zu 0 AP könnte nicht mehr als 19 AP je Run sparen. Mit FC sind es **14 AP**, also etwa ein Sol AP-Zufluss (14–16 AP/Sol).
- Bei λ_AP ≈ 0,5 Rg-Äq/AP (Utility-Spec, Warteblock) sind 14 AP etwa **7 Rg-Äquivalent je Run**. In den Regolith-Wartesolen sind AP ohnehin im Überschuss (K4), dort ist die Ersparnis fast nichts wert.
- Gegenwerte: Geologie (Labor) ca. 60–100 Rg bis Sol 25. Die Hangar-Pool-Wirkung lag in Schritt 3 bei ca. +20 Rg (Faktor) plus ca. 50 gesparte AP. Sie fällt jetzt auf ca. 14 gesparte AP, minus Proviant, minus Wartezeit und minus die Sperre des großen Funds.

> ⚠️ BALANCE CONCERN: Mit Faktor 1,0 und 3/4/6 trägt der Fundpool **keinen** nennenswerten Pfadvorteil mehr für den Hangar. Er wird ein pfadneutraler Startbonus (das entspricht der Owner-Begründung „ausgeglichene Quelle für alle drei Pfade"). Die Hangar-Parität (K15) muss deshalb aus anderen Quellen kommen: Missionen (Credits, Siegziel Expeditionen), Geschenk-Drohne (K1), Querverbindung Hangar → Labor (Feldproben, Schritt 5) und vor allem **Nachschub-Signale** (Schritt 6). Dort kann der Hangar seinen AP-Vorteil je Signal wiederholt ausspielen, oder es gibt „Außenfunde" außerhalb der Reichweite der Selbstbergung (Idee H-D aus Fassung 1, nur im Nachschub, nie im Start-Pool). Erst messen, dann entscheiden. Den Ertragsfaktor nicht wieder einführen (Owner).

Positiv: Der Hangar kostet den Spieler bei kleinen und mittleren Funden nichts mehr gegenüber der Selbstbergung. Das Wegnahme-Gefühl (Risiko R7, §13.9) beschränkt sich auf Wartezeit und den großen Fund.

---

## 4. Auswirkungen auf T30

### 4.1 Kennzahlen (Annahme, Handrechnung; Messung nötig)

| Kennzahl | Bisher (Messwert/Ziel) | Erwartung (3/4/6 + FC + Faktor 1,0 + L1) | Kommentar |
|---|---|---|---|
| K2 Hangar | Schritt 3: 2–4 erwartet | unverändert ca. 2–4 (erster Flug Ring 2, Rückkehr nach 2 Solen) | Fehlalarm als erstes Signal kann +2 kosten |
| **K4** ungenutzte AP Sol 4–12 | Pool v1: Hangar/Cantina 92/94 → 22/25; Schritt 3: Hangar 63 | Cantina und Labor-vor-Forschung steigen um ca. 40–55 AP (Pool bindet 33 statt 88 AP), also ca. 60–75. Hangar-First bindet nur noch ca. 14 + 5 = 19 AP am Pool und liegt bei ca. 63–70. | Der Pool ist kein Leerlauf-Sink mehr. Der Abstand zwischen den Eröffnungen bleibt vermutlich ≤ 20, absolut ist der Wert schlecht. Lösung über S6, Schritt 4 und Schritt 6. |
| K5 Leerlauf-Sole | 0,5 / 0 (Pool v1) | Cantina wieder ca. 2–4 | wie K4 |
| K11 Pool-Rg Sol 10 / 25 | Ziel ca. 20 / ca. 50 | ohne Hangar ca. 35–40 / 40; Hangar-First ca. 22 / 40 (großer Fund erst nach Frachter) | Warnregel „Hangar Sol 25 < Cantina Sol 25" aus §13.8 entfällt; neues Soll: alle Eröffnungen etwa gleich, Hangar Sol 10 darf um den großen Fund niedriger liegen |
| K12 Anteil per Schiff | Ziel Hangar-First ≥ 85 % | bleibt ≥ 85 % (strenge Ablösung) | Definition unverändert. Ohne Faktor misst K12 nur noch „Weg", keinen Ertragsvorteil. |
| K15 Pfad-Rg-Äquivalent Hangar | Schritt 3: Faktor + Spar-AP | nur noch Spar-AP (ca. 14 je Run) | siehe 3.5 |
| K16 Wartesole | `needs_level`, `needs_freighter`, `ship_busy`, `no_ship_ready` | `no_ship_ready` (ship_worn) fällt bei FC praktisch weg; `needs_level`/`needs_freighter` bleiben (L1) | Warnschwelle: großer Fund in Hangar-First nach Sol 15 noch gesperrt |
| K17 Zonen-Blockade | Concern R5 | 0 per Konstruktion mit Generatorregel; ohne sie unverändert möglich | §13.10/1 damit entschieden (Option a) |
| K3 Phase-2-Start | 17 / 17 / 16,5 (Schritt 1) | Labor/Cantina ca. −0,5 Sole (Rg kommt früher); Hangar-First etwa unverändert gegenüber Schritt 3 (−20 Rg Faktor, aber früher) | Untergrenze 15 beobachten |
| K6 Rg Sol 10 | 79 / 79 / 71,5 | Labor/Cantina ca. +15–20; Hangar-First ca. +5–10 | Spanne ≤ 25 sollte halten |
| K7 Siegquote | ca. 50 % | leicht sinkend in Hangar-Läufen (−20 Rg Pool) | Warnschwelle unverändert |

### 4.2 Pool-Größe, Zonen-Knappheit, Regolith-Inflation

- **Inflation:** keine. Die Gesamtmenge bleibt 40 Rg für alle Pfade (vorher 60 per Flug). Das sind ca. 4 % des Sockels bis Sol 25 (ca. 924 Rg, §11.3). Es verschiebt sich nur der Zeitpunkt nach vorn.
- **Zonen-Knappheit:** wird entschärft. Kleine und mittlere Funde geben ihre Kachel nach einem Klick (ohne Hangar) bzw. nach einem Umlauf (mit Hangar) frei. Der große Fund bleibt der einzige lange Blocker (L1), deshalb die Generatorregel.
- **Pool-Größe:** Der Pool ist jetzt ein kleiner, pfadneutraler Startbonus. Wer ihn wieder als Sink oder als Hangar-Hebel will, vergrößert ihn über **mehr Signale** (Nachschub), nicht über teurere Bergung. Dann gilt der Concern aus 2.3.
- **Utility-Bot:** Die Dichte der Selbstbergung steigt auf 1,3–3,0 Rg-Ä/AP, die des Flugs (FC) auf bis zu 18 Rg je AP ohne Scan. Beides steht damit vor der Forschung. Das ist unkritisch, weil der Pool endlich ist. S3-Messwerte zur Forschungsreihenfolge verschieben sich aber (siehe 8).

---

## 5. Betroffene Stellen (nur Auflistung)

**Config**
- `config/game.php` → `finds.types.*.ap` = 3/4/6; `salvage_cap_per_sol` und `max_open_projects` entfernen; `finds.flight.yield_factor` entfernen (oder 1,0); neu z. B. `finds.flight.nav_ap` (1) und `finds.flight.organika_per_sol` (1); Schalter „ohne Verschleiß" (z. B. `finds.flight.wear` = false); bei L2 `min_hangar_level.find_large`.
- `config/ships.php` `find_carry` (nur bei L2), `config/missions.php` Kommentare Z. 61–62 (Faktor, `max_open_projects`).
- `tests/Unit/FindFlightConfigTest` (Konsistenz `min_hangar_level` ↔ `find_carry`), `tests/Unit/FindsConfigTest`.

**Services / Controller / Tick**
- `app/Services/ColonyTileService.php`: `salvageFind()` (sofort, ohne Tick-/Deckel-/Projektprüfung, kein `ap`-Parameter), `findAction()` (Zweige `grandfathered`/`continue_salvage` nur noch für Altstände oder entfernen; `ship_worn` bei FC nicht mehr erreichbar), `transformTile()` (`ap_cap_per_sol`, `ap_spent` raus), `completeFind()`.
- `app/Services/FindFlightService.php`: `costs()` (pauschale Nav-AP, eigener Proviant-Satz), `findFlightYield()` (ohne `flight_bonus`).
- `app/Support/HangarBays::flightReadiness()` bzw. Aufrufer in `findAction`: Verschleiß 0 für Bergungsflüge (FC).
- `app/Console/Commands/GameTick.php`: Verschleiß im Flug für `mission_find_salvage` aussetzen (FC); `payMissionRewards` Details ohne `flight_bonus`.
- `app/Services/HangarService.php` (Dispatch-Kosten aus `FindFlightService`, Z. 1008 Zielbedingung), `ColonyController` (Salvage-Endpunkt), `CommLogController` (Detailzeilen ohne Flug-Bonus).
- `app/Services/FindPoolService.php`: Generatorregel „groß nie auf potenzieller Zonen-Kachel" (Default L1); eigener Seed-Strom bleibt, damit die gepaarten Baselines nicht kippen. Bei Kollision der Kandidatenliste vorher prüfen, ob Ring 3 außerhalb der Maximalzone genug leere Felder hat.
- `app/Models/ColonyTile.php` (`salvage_ap_spent` fillable).

**Schema (optional, Aufräumen)**
- Baseline: `salvage_ap_spent`, `salvage_tick` entfernen (danach `db:reset`), `BaselineSchemaTest`. Hängt an der Entscheidung, den Bestandsschutz-Zweig ganz zu streichen.

**UI / Lang**
- `resources/views/colony/hexview.blade.php`, `partials/tile-terrain.blade.php`, `public/js/colony-hexgrid.js`: Knopf mit AP-Chip statt AP-Eingabe; Flugzeile ohne Bonus; Resourcebar live syncen.
- `lang/de|en/colony.php`: entfallen `find_cap`, `find_cap_value`, `find_salvage_ap_label`, `error_salvage_cap`, `error_salvage_projects`, ggf. `find_reason_grandfathered`. Neu formulieren: `find_reason_flight` (ohne `:base + :bonus = :total`, stattdessen „Fund :rg Rg · 1 AP · :organika Or · zurück Sol :sol"), `find_help` („mehr ins Lager als zu Fuß" und „Schicht für Schicht" stimmen nicht mehr), `onboarding_hint_find_flight` („mehr Ertrag" stimmt nicht mehr), `find_effort`, Hinweiszeile auf der Hangar-Baukarte. → content-writer.
- `app/Services/OnboardingHintService.php` (Hint bei Hangar Stufe 1).

**Bot / Report**
- `tests/Feature/Playtest/BotStrategy.php`: `invest_find` → ein Klick je Fund (nur ohne Hangar); `dispatch_find_flight` mit neuen Kosten; `repair_ship_for_flight` wird für Funde praktisch nicht mehr ausgelöst (Test anpassen, Regel bleibt für andere Missionen).
- Utility: `Utility/Sources/FreighterSource.php` (Wert = `rg` ohne `yield_factor`), `Utility/Valuers.php`, `Utility/UtilityFixtures.php`, `Utility/AdapterGoldenTest.php` (Golden-Folge ändert sich zwingend).
- `tests/Feature/Playtest/RunReport.php` (Details ohne `flight_bonus`), `app/Support/OpeningComparison.php` (K11-Soll/Warnregel, K15 ohne Faktoranteil, K16, K17), `tools/playtest-dashboard.php` (nur prüfen).
- Tests: `SalvageFindTest`, `FindActionTest`, `FindFlightTest` (Ertrag = rg, Kosten pauschal, kein Verschleiß), `FindFlightUiTest`, `ColonyViewTest`, `BotStrategyInvestFindTest`, `BotStrategyFindFlightTest`, `BotStrategyInactiveShipTest`, `BotStrategyMissionChoiceTest`, `BotStrategyMissionsAndEventsTest`, `RunReportFindSourceTest`.

**Szenarien / Doku**
- `ResetPlayer.php`, `ColonySeedDemo.php`, `data/sql/testdata.sql`: nur prüfen (kein Fund-Bezug gefunden).
- `docs/game-reference.md` §17a (Staffel, Deckel/Projektlimit raus, Flugkosten, Faktor raus), GDD §4a Absatz „Signale, Tiefenscan, Bergung" (Z. 498: Sofort-Bergung; Flug spart AP, bringt nicht mehr Rg), GDD §8b-Katalogzeile `mission_find_salvage`, T30-Spec §13.3/§13.5 (Faktor) als überholt markieren, Utility-Spec §2.5 Tabelle.

**Aufwand (Annahme):** M, etwa 1 Entwicklertag inkl. TDD (mehr als in Fassung 1, weil Flugkosten, Verschleiß-Ausnahme und Generatorregel dazukommen).

**TDD-Kern (rot zuerst):**
- Selbstbergung bucht AP und Rg im selben Request.
- Mit Hangar ≥ 1 und ohne Altstand → `salvage_by_flight`.
- Flugkosten = 1 AP und Proviant je Sol Entfernung, Vorschau = Abzug.
- Flug-Ertrag = `rg`.
- Bergungsflug verringert keine SP.
- Generator: großer Fund nie auf einer Kachel der maximalen Kolonie-Zone, und die bestehenden Tile-Typen bleiben seed-identisch.
- Adversarial: Doppel-Submit bucht nicht doppelt.

---

## 6. Idee „AP-Deckel pro Sol" für Gebäudebau und Forschung (Kurzbewertung)

**Annahme zur Deutung:** „Sockel" = ein kleiner, automatischer Fortschritt je Sol ohne AP; „Deckel" = höchstens x aktive AP je Projekt und Sol.

**Nutzen**
- Tempo glätten: kein „30 AP in einen CC-Ausbau an einem Sol", stattdessen parallele Projekte. Breite wird zur Entscheidung.
- Thema: „Der Bautrupp schafft pro Sol so viel." Ein Kenntnis- oder Beraterbonus könnte den Deckel heben.
- Ein **Sockel** würde passives Spiel belohnen (Fun-Checkliste).

**Risiken**
- **Mehr Leerlauf statt weniger.** In Phase 1 ist Regolith der Engpass. Ab Sol 14 sind AP voll ausgelastet (Messwert), dort ließe ein Deckel AP verfallen. K4 steigt.
- **Labor-First wird geschwächt.** Forschung ist der einzige unbegrenzte AP-Abnehmer. Das widerspricht T30 („Labor nicht absenken").
- **Dieselbe Klickarbeit**, die der Owner gerade bei der Bergung abgeschafft hat.
- Wechselwirkungen mit K3, Bau-Rabatten, `project_min_cost_factor`, Bonus-AP über `investBonus`. Dazu kommt eine weitere Zahl in der UI, und der Utility-Bot (S3, S5) müsste neu kalibriert werden.

**Einordnung:** nicht jetzt. Frühestens nach Utility-Bot S6 und einer sauberen K4-Messung, als eigene Design-Runde. Falls überhaupt, dann eher der Sockel als der Deckel, und ein Deckel nur je Projekt.

---

## 7. Empfehlung und offene Owner-Fragen

**Empfehlung:**
- Selbstbergung als Sofort-Aktion 3/4/6 (entschieden).
- Bergungsflug nach Variante **FC**: 1 AP je Flug, kein Verschleiß, Proviant 1 Or je Sol Entfernung, Ertrag = Fundmenge (`yield_factor` entfällt). Damit kostet der ganze Start-Pool per Flug 5 statt 19 AP, und „deutlich weniger AP" gilt für jede Fundgröße.
- Sperre großer Funde bis Stufe 2 + Frachter beibehalten, ergänzt um die Generatorregel „groß nie auf einer Zonen-Kachel" (K17 entfällt).
- Offen akzeptieren, dass der Fundpool damit **kein Hangar-Paritätshebel** mehr ist (max. ca. 14 gesparte AP je Run). Die Hangar-Parität kommt aus Schritt 5/6 und wird gemessen.

**Offene Owner-Fragen (max. 3, mit Default):**

1. **Flug-Staffel:** FC (1 AP, kein Verschleiß, 1 Or je Sol Entfernung), FB (1 AP, Verschleiß bleibt, effektiv 2,5–3 AP mit Reparatur) oder FA (1/2/3 AP nach Größe)? **Default: FC.** Nur FC erfüllt „deutlich weniger" auch beim kleinen Fund, und die Fundkarte wird einfacher.
2. **Großer Fund bei Hangar Stufe 1:** Sperre bleibt plus Generatorregel (L1), oder die Drohne trägt auch große Funde (L2, der Frachter verliert damit seine letzte echte Rolle)? **Default: L1 + Generatorregel**, L2 nur, wenn K16 zeigt, dass der große Fund in Hangar-First über Sol 15 hinaus wartet.
3. **Hangar-Parität:** Akzeptierst du, dass der Fundpool nur noch pfadneutraler Startbonus ist und der Hangar seinen Vorsprung über Nachschub-Signale (Schritt 6, ggf. mit Außenfunden nur per Schiff) bekommen soll? **Default: ja.** Erst messen (24 Läufe), dann Schritt 6 vor Schritt 4/5 ziehen, falls die Hangar-Eröffnung in K3/K7 mehr als die Toleranz zurückfällt.

---

## 8. Reihenfolge relativ zur Utility-Bot-S3-Arbeit

**Befund:** S3 (`feat/bot-utility-s3`, uncommittete Änderungen vorhanden) baut auf den heutigen Fundregeln auf:
- `FreighterSource` reagiert auf `needs_freighter` und rechnet mit `yield_factor`.
- `AdapterGoldenTest` fixiert die Legacy-Aktionsfolge.
- Die S3-Messung (24 Läufe bis Sol 25 + K19) vergleicht Utility mit Legacy.

Eine Fund-Änderung mitten in S3 würde beide Engines zugleich verschieben, und das Ergebnis ließe sich nicht mehr dem Bot zuordnen.

**Vorschlag (Default):**
1. S3 fertig implementieren und **mit den heutigen Fundwerten** messen. An `game.finds` nichts ändern.
2. S3 mergen.
3. Eigener PR „Bergung ohne Sol-Deckel" (TDD):
   - Config, `ColonyTileService`, `FindFlightService`, Verschleiß-Ausnahme, Generatorregel
   - Fundkarte, Lang
   - Legacy-Bot und Utility-Quellen; Golden-Test neu einfrieren
   - Report-Kennzahlen K11/K15/K16/K17
   - `game-reference.md`, GDD §4a/§8b zahlenfrei
4. Neue Referenzmessung: 24 Läufe Utility-Engine (gleiche Seeds). Legacy nur bei Bedarf für einen Vorher/Nachher-Vergleich (+24 Läufe, ca. 51 min; der Owner wählt 8/16/24).
5. Danach S4–S6. S6 (Füller: Erkunden, Scan, Bergung) kalibriert direkt auf die neuen Kosten.

**Alternative**, falls die S3-Messung noch nicht begonnen hat: zuerst den Fund-PR, dann eine frische Legacy-Referenz (24 Läufe), dann die S3-Messung. Das kostet einen zusätzlichen Batch. Beides im selben Branch zu mischen, ist nicht empfohlen.

**Noch offen:** Die Utility-Spec verweist auf T30 §14 (Branch `feat/t30-schritt4-spec`). Dieser Abschnitt lag hier nicht vor. Die dort genannten Verlade-AP (§14.5) waren ein Gegenmittel gegen zu billige Flüge mit Ertragsfaktor. Unter FC sind sie gegenstandslos und widersprächen dem Owner-Entscheid.
