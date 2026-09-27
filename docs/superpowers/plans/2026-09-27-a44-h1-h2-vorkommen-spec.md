# A44 Schritt H1+H2: Regolith-Vorkommen-Redesign — Design-Spec

Stand 2026-09-27. Vorstufe für `game-developer` (TDD-Implementierung). Bezug: ROADMAP.md „A44 Mehr Variation der Startkarte" (Owner-Entscheidungen 2026-09-25), `docs/superpowers/plans/2026-09-27-a44-t9-handoff.md`. Schritt „RNG-Fix" (`App\Support\SeededRandom`) ist bereits gemergt und Voraussetzung für alles Folgende.

Diese Spec ändert **keinen Code**. Sie ist die Entscheidungsgrundlage, aus der `game-developer` TDD-Testfälle ableitet.

## 0. Ist-Zustand in einem Satz

`ColonyTileService::resolveTileType()` würfelt für Ring 3+ einen einzigen String `regolith_{poor,normal,rich}`, der **gleichzeitig** Ertrag/Sol (`config('game.harvester.fresh_yield')`) und Gesamtvorkommen (`config('game.harvester.resource_max')`) festlegt — ein Tile ist entweder in beidem schwach, mittel oder stark. H1 entkoppelt diese zwei Achsen; H2 ersetzt das heutige EINE zufällig vorerkundete Ring-3-Tile durch zwei bekannte, gegensätzliche Vorkommen.

## 1. Datenmodell-Entscheidung

**Empfehlung: Option A — `tile_type` bleibt der alleinige Träger, wird von 3 auf 8 Werte erweitert. Keine Schema-Migration.**

### Abgewogene Optionen

| Option | Beschreibung | Aufwand | Bewertung |
|---|---|---|---|
| **A (empfohlen)** | `tile_type` wird zu einem 8-wertigen Enum-String, der Gehalt- und Mächtigkeits-Tier direkt kodiert (z. B. `regolith_y3_d1`). `fresh_yield`/`resource_max` in `config/game.php` bekommen 8 statt 3 Einträge. | Klein — keine neue Spalte, `resourceMaxFor()`/`GameTick::harvesterYield()` bleiben unverändert (reine Config-Lookups per String-Key). | Geringstes Risiko, sofort TDD-fähig. Nachteil: kein direktes SQL-Filtern nach einzelner Achse (aktuell nirgends gebraucht). |
| B | Zwei neue Spalten `colony_tiles.regolith_yield_tier` / `regolith_depth_tier` (tinyint 1-3), `tile_type` wird zu einem generischen `regolith`-Marker. | Groß — Migration, Backfill bestehender Zeilen, UND Anpassung aller String-Vergleiche, die heute `str_starts_with($type, 'regolith_')` nutzen (bleibt zwar syntaktisch gültig, aber die Bedeutung von „danach kommt der Tier-Code" verschwindet), UND Anpassung von `resourceMaxFor()`/`harvesterYield()`-Signaturen (nehmen dann Tier-Ints statt String), UND aller 16 betroffenen Tests. | Sauberer für später (5 Planetentypen, GDD §4a, brauchen ohnehin eigene Spalten für Terraintyp — siehe `project_tile_panel_terrain_display_idea`-Idee), aber unnötiger Aufwand JETZT, wenn Option A dieselben vier Invarianten erfüllt. |
| C | Zwei gewürfelte Multiplikatoren auf einen Basiswert, `tile_type` bleibt 3-wertig als „Familie". | Mittel | Verschleiert die Zahlen eher, als sie zu zeigen (Owner-Präferenz: „angezeigte Zahl = wirkende Zahl") — verworfen. |

**Warum A und nicht B:** Der einzige Code, der heute nach `tile_type` „schaut", tut das über String-Prefix-Checks (`str_starts_with($type, 'regolith_')`, z. B. `computeColonyZoneCoords()`) oder direkte Config-Lookups per exaktem String (`resourceMaxFor()`, `GameTick::harvesterYield()`, `activeHarvesterRegolithTiles()`). Beides funktioniert mit einem breiteren Enum unverändert weiter — kein einziger dieser Call-Sites muss umgeschrieben werden, nur die Config-Tabellen wachsen von 3 auf 8 Zeilen. Eine Migration lohnt sich erst, wenn die 5 Planetentypen (GDD §4a, spätere Phase) wirklich strukturierte Terrain-Daten brauchen — das ist explizit „danach, nach Messung von H1/H2" (Owner-Reihenfolge). Option B jetzt vorwegzunehmen wäre Scope-Kriechen.

### Naming der 8 neuen `tile_type`-Werte

Zwei Achsen, je 3 Stufen, Kombination `y{Gehalt-Tier}_d{Mächtigkeits-Tier}` (Config-Keys englisch/kurz, Konvention CLAUDE.md):

```
regolith_y1_d1   regolith_y1_d2   regolith_y1_d3
regolith_y2_d1   regolith_y2_d2   regolith_y2_d3
regolith_y3_d1   regolith_y3_d2   [regolith_y3_d3 existiert NICHT — Invariante b]
```

`y1/y2/y3` = Gehalt niedrig/mittel/hoch (Ertrag pro Sol). `d1/d2/d3` = Mächtigkeit gering/mittel/groß (Gesamtvorkommen). Die Top-Top-Kombination `y3_d3` wird nie erzeugt — das ist Invariante (b) direkt im Enum abgebildet, nicht nur im Generator-Code.

### UI-Darstellung (Transparenz-Vorgabe, siehe `feedback_player_transparency_priority`)

Spieler sehen **die tatsächlichen Zahlen**, nicht nur Tier-Label — das Tile-Panel zeigt bereits `resource_amount`/`resource_max` als konkrete Regolith-Mengen und den `fresh_yield` implizit über den bestehenden „≈N Sole bis Erschöpfung"-Wert (`ColonyTileService::solsRemaining()`, unverändert nutzbar). Zusätzlich sollte pro Tile eine kurze Zwei-Wort-Einordnung stehen, z. B. „Ertrag hoch · Vorkommen gering", damit der Spieler den Tradeoff auch ohne Kopfrechnen erkennt. `public/js/colony-hexgrid.js` (Zeilen ~16-18 Labels, ~35-46 Farben) hat aktuell 3 hartcodierte Label/Farb-Einträge für `regolith_poor/normal/rich` — die müssten auf 8 Einträge erweitert werden (Aufgabe für `ui-specialist`, nicht Teil dieser Spec; hier nur als Abhängigkeit benannt). Empfehlung für die Farbcodierung: eine Achse über Farbton (Blauton wie heute, dunkler = mehr Mächtigkeit), die andere über ein kleines Icon/Badge (z. B. Pfeil-Symbol für Ertrag) statt eine zweite Farbdimension — Owner/`ui-specialist` entscheiden die genaue Umsetzung.

## 2. Konkrete Werte-Vorschläge

### Gehalt-Tiers (Ertrag/Sol, ersetzt `fresh_yield`)

| Tier | Wert (Rg/Sol) |
|---|---|
| y1 (niedrig) | 16 |
| y2 (mittel) | 23 |
| y3 (hoch) | 30 |

Bewusst identisch zu den heutigen poor/normal/rich-Werten (15→16 minimal geglättet für exakten Mittelwert) — Kontinuität zum bisherigen Spielgefühl, nur jetzt unabhängig von der Mächtigkeit.

### Mächtigkeits-Tiers (Gesamtvorkommen, ersetzt `resource_max`)

| Tier | Wert (Rg gesamt) |
|---|---|
| d1 (gering) | 160 |
| d2 (mittel) | 300 |
| d3 (groß) | 440 |

Symmetrisch um 300 (heutiger „normal"-Wert), nicht mehr die alte Asymmetrie 160/300/500 — die Asymmetrie war nur ein Nebenprodukt der 1:1-Kopplung an Gehalt.

### Die 8 gültigen Kombinationen

| tile_type | Gehalt | Mächtigkeit | Sole bis Erschöpfung (ohne Geologie-Bonus) |
|---|---|---|---|
| `regolith_y1_d1` | 16 | 160 | ~10 |
| `regolith_y1_d2` | 16 | 300 | ~19 |
| `regolith_y1_d3` | 16 | 440 | ~28 |
| `regolith_y2_d1` | 23 | 160 | ~7 |
| `regolith_y2_d2` | 23 | 300 | ~13 (= heutiger „normal") |
| `regolith_y2_d3` | 23 | 440 | ~19 |
| `regolith_y3_d1` | 30 | 160 | ~5 |
| `regolith_y3_d2` | 30 | 300 | ~10 |
| ~~`regolith_y3_d3`~~ | ~~30~~ | ~~440~~ | **ausgeschlossen (Invariante b, „kein perfektes Vorkommen")** |

### Invarianten-Check

- **(a) Phase-1-Lücke gedeckt:** siehe §3 (H2) — das garantierte „Rush"-Vorkommen `y3_d1` deckt die Phase-1-Lücke (T9-Messung: ~535 Rg Bedarf) bei Sol 1 platziertem Harvester in ~18 Solen reinem Harvester-Ertrag (535/30), passt in den Zielkorridor Sol 15-20.
- **(b) Kein perfektes Tile:** `y3_d3` existiert nicht im Enum — strukturell garantiert, kein Generator-Bug kann es erzeugen.
- **(c) Mittelwert = heutiger „normal"-Wert:** bei gleichverteilten 8 Kombinationen (je 1/8 Wahrscheinlichkeit): Ø Gehalt = (3×16 + 3×23 + 2×30)/8 = 22,1 Rg/Sol; Ø Mächtigkeit = (3×160 + 3×300 + 2×440)/8 = 282,5 Rg. Beides knapp unter dem alten Normalwert (23/300) — die fehlende `y3_d3`-Zelle zieht den Mittelwert leicht nach unten. **Bewusst nicht nachkorrigiert** (würde die schöne Symmetrie 16/23/30 bzw. 160/300/440 zerstören); Abweichung von ~4% ist als Startwert akzeptabel und wie alle Balance-Werte hier ein Platzhalter, der nach dem nächsten Bot-Batch (8-12 Seeds, siehe `feedback_playtest_seed_sample_size`) verifiziert wird, nicht vorab exakt durchgerechnet.
- **(d) Karten-Regolith-Budget vergleichbar:** die Roll-Bänder für Ring 3+ bleiben **unverändert** (impassable <5, hazard <15, regolith 15-64 [50 breit], sonst empty) — nur INNERHALB des Regolith-Bandes wird jetzt zusätzlich eine Kombination gewürfelt, statt direkt poor/normal/rich zu bestimmen. Die Gesamtwahrscheinlichkeit „ist überhaupt Regolith" bleibt exakt 50/100, keine Erhöhung der Regolith-Menge auf der Karte insgesamt.

### Kartenbudget-Herleitung (zur Einordnung)

Nur Ring 3 kann Regolith enthalten (Ring 0/1 immer `terrain_empty`, Ring 2 nur `terrain_hazard`/`terrain_empty`, siehe `resolveTileType()`). Von den 18 Ring-3-Koordinaten werden zufällig 9 als „Frontier" für Sol 1 gewählt (`RING3_FRONTIER_COUNT`). Erwartungswert Regolith-Tiles unter diesen 9: 9 × 0,50 ≈ 4,5. Erwartetes Gesamtbudget ≈ 4,5 × Ø-Mächtigkeit (282,5) ≈ 1.270 Rg pro Karte — vergleichbar mit dem heutigen Budget (4,5 × 284 ≈ 1.278 Rg, siehe alte Gewichtung 40% poor/40% normal/20% rich). Damit ist Invariante (d) auch quantitativ erfüllt, nicht nur strukturell.

## 3. H2-Mechanik: zwei bekannte Start-Vorkommen mit echtem Zielkonflikt

### Vorschlag

Unter den 9 zufällig gewählten Ring-3-Frontier-Koordinaten werden **zwei verschiedene** Indizes zufällig gewählt (ohne Zurücklegen) und ihr `tile_type` **unconditional** auf zwei feste, gegensätzliche Kombinationen gesetzt, beide `is_explored = 1`:

- **„Rush"-Vorkommen: `regolith_y3_d1`** (30 Rg/Sol, 160 Rg gesamt, ~5 Sole bis Erschöpfung)
- **„Steady"-Vorkommen: `regolith_y1_d3`** (16 Rg/Sol, 440 Rg gesamt, ~28 Sole bis Erschöpfung)

Alle übrigen 7 Frontier-Tiles behalten ihren regulär gewürfelten `tile_type` und bleiben `is_explored = 0` (Fog of War) wie heute.

### Warum das ein echter Zielkonflikt ist

Der Spieler platziert den einzigen Sol-1-Harvester (kein Level-Up, siehe GDD §4c „Harvester … kein passives Einkommen, sondern aktives Spiel") auf genau eins der beiden bekannten Tiles:

- **Rush wählen:** hoher Ertrag sofort, deckt die Phase-1-Regolith-Lücke (T9: ~535 Rg) in ~18 Solen bei Dauerbetrieb — passt in den Zielkorridor Sol 15-20. Aber das Tile ist nach ~5 Solen leer; der Spieler muss aktiv umziehen (AP-Kosten `harvester.relocate_ap_per_hex`), entweder zum Steady-Tile oder zu einem erst noch zu erkundenden Tile — ein Timing-Risiko, wenn Erkundung/Umzug zu spät passiert.
- **Steady wählen:** kein Erschöpfungsdruck für lange Zeit (~28 Sole), aber allein reicht der Ertrag nicht für den Sol-15-20-Korridor (535/16 ≈ 33 Sole) — der Spieler braucht zusätzliche Regolith-Quellen (Handel, Missionen, Erkunden eines dritten Tiles) oder akzeptiert eine langsamere Phase-1.

Das ist keine „Trap vs. Free Lunch"-Entscheidung, sondern ein echter Pacing-Tradeoff mit Folgeentscheidungen (Umzug-Timing, Erkundungsreihenfolge) — passt zur Roguelike-Ausrichtung „Entscheidungen ohne Optimalpfad" (Catan-Prinzip, siehe CLAUDE.md Inspirationen).

### Warum „unconditional" statt der heutigen „nur überschreiben falls nicht schon Regolith"-Logik

Der heutige Code überschreibt das eine Zieltile nur, wenn es NICHT schon zufällig Regolith war (Varianzerhalt für den einen Slot). Für H2 ist die Pointe gerade, dass **exakt diese zwei markanten, gegensätzlichen Kombinationen** immer als Sol-1-Wissen vorhanden sind — die Variation kommt aus WELCHE der 9 Koordinaten getroffen werden und was der Rest der Karte bietet, nicht aus der Kombination selbst. Siehe Owner-Frage 3 unten für die Alternative (variierendes Paar).

## 4. Generator-Pseudocode

### `resolveTileType()` — neue Signatur, zweiter Wurf für die Kombination

```php
private function resolveTileType(int $ring, int $roll, int $comboRoll): string
{
    if ($ring <= 1) {
        return 'terrain_empty';
    }
    if ($ring === 2) {
        return $roll < 10 ? 'terrain_hazard' : 'terrain_empty';
    }

    // Ring 3+: Bänder unverändert (Invariante d)
    if ($roll < 5) {
        return 'terrain_impassable';
    }
    if ($roll < 15) {
        return 'terrain_hazard';
    }
    if ($roll < 65) {
        return $this->pickRegolithCombo($comboRoll);
    }

    return 'terrain_empty';
}

/** $comboRoll in [0,99], 8 gleich breite Buckets (~12-13 breit) über die 8 gültigen Kombinationen. */
private function pickRegolithCombo(int $comboRoll): string
{
    static $combos = [
        'regolith_y1_d1', 'regolith_y1_d2', 'regolith_y1_d3',
        'regolith_y2_d1', 'regolith_y2_d2', 'regolith_y2_d3',
        'regolith_y3_d1', 'regolith_y3_d2',
        // regolith_y3_d3 bewusst nicht enthalten — Invariante (b)
    ];

    $index = intdiv($comboRoll * count($combos), 100); // 0..7

    return $combos[$index];
}
```

`$comboRoll` wird **immer** gezogen, auch wenn `$roll` am Ende gar kein Regolith ergibt (feste Zieh-Reihenfolge pro Tile — vermeidet bedingte RNG-Stream-Längen, die spätere Refactorings fragil machen würden).

### Caller-Anpassungen

- **`randomizeOuterRingRows(seed)`** nutzt bereits einen zustandsbehafteten `$rng` (sequenzielle `getInt()`-Aufrufe) — pro Ring-3-Tile einfach `$rng->getInt(0, 99)` zweimal ziehen (Primärwurf, dann Komborwurf) statt einmal:
  ```php
  foreach ($ring3Coords as [$q, $r]) {
      $roll = $rng->getInt(0, 99);
      $comboRoll = $rng->getInt(0, 99);
      $ring3Rows[] = ['q' => $q, 'r' => $r, 'ring' => 3, 'tile_type' => $this->resolveTileType(3, $roll, $comboRoll)];
  }
  ```
  Ring-2-Schleife bekommt aus Konsistenzgründen ebenfalls einen (verworfenen) zweiten Wurf, falls Ring 2 später doch Regolith bekommen sollte — optional, aktuell nicht nötig, da Ring 2 nie regolith liefert.

- **H2-Block** ersetzt den heutigen Einzel-Override:
  ```php
  $idx1 = $rng->getInt(0, count($ring3Rows) - 1);
  $idx2 = $rng->getInt(0, count($ring3Rows) - 2);
  if ($idx2 >= $idx1) { $idx2++; } // zwei verschiedene Indizes ohne Zurücklegen

  $ring3Rows[$idx1]['tile_type'] = 'regolith_y3_d1'; // Rush
  $ring3Rows[$idx2]['tile_type'] = 'regolith_y1_d3'; // Steady

  foreach ($ring3Rows as $i => $row) {
      $row['is_colony_zone'] = 0;
      $row['is_explored'] = in_array($i, [$idx1, $idx2], true) ? 1 : 0;
      $resourceMax = $this->resourceMaxFor($row['tile_type']);
      $row['resource_amount'] = $resourceMax;
      $row['resource_max'] = $resourceMax;
      $rows[] = $row;
  }
  ```

- **`randomTileType(ring, seed)`** (Fallback-Codepfad, laut Docblock für `OnboardingService::seedStartingTiles()` — separat von `randomizeOuterRingRows`, prüfen ob heute überhaupt noch aufgerufen) braucht einen zweiten, unabhängigen Wurf aus demselben Seed, z. B. `SeededRandom::int($seed, 0, 99)` für den Primärwurf und `SeededRandom::int($seed + 1, 0, 99)` (oder ein dokumentierter Salt) für den Komborwurf — exakte Ableitung ist Sache von `game-developer`, solange sie deterministisch pro Seed bleibt.

- **`pickTileType(q, r, colonyId, ring)`** (deterministischer Hash für `generateDefaultTiles()`, Dev/Erstbesuch-Fallback) braucht einen zweiten Hash mit anderen Multiplikatoren, um nicht mit dem Primärhash zu korrelieren:
  ```php
  private function pickTileType(int $q, int $r, int $colonyId, int $ring = 3): string
  {
      $hash = abs($q * 7 + $r * 13 + $colonyId * 3) % 100;
      $comboHash = abs($q * 11 + $r * 17 + $colonyId * 5) % 100;

      return $this->resolveTileType($ring, $hash, $comboHash);
  }
  ```
  **`generateDefaultTiles()` implementiert H2 NICHT** (kein Ring-3-Frontier-Subset, kein „zwei bekannte Vorkommen"-Konzept — reiner Dev/Erstbesuch-Fallback, nicht der Live-Onboarding-Pfad). Das ist eine bewusste Lücke, kein Bug — nur zur Klarstellung für `game-developer`, damit dort nicht versehentlich H2-Logik dupliziert wird.

### Config-Änderung (`config/game.php`)

```php
'fresh_yield' => [
    'regolith_y3_d1' => 30, 'regolith_y3_d2' => 30,
    'regolith_y2_d1' => 23, 'regolith_y2_d2' => 23, 'regolith_y2_d3' => 23,
    'regolith_y1_d1' => 16, 'regolith_y1_d2' => 16, 'regolith_y1_d3' => 16,
],
'resource_max' => [
    'regolith_y3_d1' => 160, 'regolith_y3_d2' => 300,
    'regolith_y2_d1' => 160, 'regolith_y2_d2' => 300, 'regolith_y2_d3' => 440,
    'regolith_y1_d1' => 160, 'regolith_y1_d2' => 300, 'regolith_y1_d3' => 440,
],
```

`resourceMaxFor()` und `GameTick::harvesterYield()` brauchen dafür **keine** Code-Änderung — reine Config-Lookups per exaktem `tile_type`-String, wie heute.

## 5. Auswirkungen auf bestehenden Code (nicht Teil dieser Spec, nur Inventar für `game-developer`)

### Tests, die vermutlich rot werden (grep `regolith_poor|regolith_normal|regolith_rich`)

```
tests/Unit/ColonyTileMapDistributionTest.php        — testet direkt die alte Verteilung, muss komplett neu geschrieben werden (Kern-TDD-Ziel dieser Aufgabe)
tests/Unit/ColonyTileServiceSolsRemainingTest.php    — nutzt alte tile_type-Strings als Fixture
tests/Feature/GameTick/HarvesterDepletionTest.php
tests/Feature/GameTick/GameTickResourceGenerationTest.php
tests/Feature/GameTick/StaffingProductionTest.php
tests/Feature/Colony/ColonyViewTest.php
tests/Feature/Colony/PlacementPrepaysFirstLevelTest.php
tests/Feature/Colony/SupplyBuildGateTest.php
tests/Feature/Colony/HarvesterTransitTest.php
tests/Feature/Colony/HarvesterRelocateApCostTest.php
tests/Feature/Colony/HarvesterSecondInstanceTest.php
tests/Feature/Colony/ColonyZoneDecoupleTest.php
tests/Feature/Onboarding/OnboardingHintServiceTest.php
tests/Feature/Playtest/BotStrategyMissionChoiceTest.php
tests/Feature/Playtest/BotStrategyRepairTest.php
tests/Feature/Console/ColonySeedDemoTest.php
```
16 Dateien insgesamt — grobe Faustregel: alle, die einen konkreten `tile_type`-String als Fixture hardcoden, müssen auf einen der 8 neuen Werte umgestellt werden (i. d. R. `regolith_y2_d2` als 1:1-Ersatz für das alte `regolith_normal`, wenn der Test nur „irgendein Regolith-Tile" braucht).

### Doku (grep dieselben Strings)

```
docs/game-reference.md                                        — Lookup-Tabelle für fresh_yield/resource_max muss aktualisiert werden (ADR 0004, nach Merge)
docs/GDD.md                                                    — Zeile ~2256 nennt "rich/normal/poor" konkret in einer Tabellenzelle, ADR-0004-Prosa-Regel beachten (keine Zahlen, aber auch keine veralteten Kategorienamen)
docs/gdd/onboarding.md                                         — vermutlich Erwähnung des "einen vorerkundeten Tiles" (H2 ersetzt das)
docs/handoff-ap-ratenmodell.md
docs/superpowers/specs/2026-08-10-harvester-constant-yield-design.md — Ursprungsspec der heutigen 3-Tier-Logik, sollte auf diese Spec verweisen statt widersprechen
docs/lore/tiles.md                                             — Lore-Text zu den 3 Vorkommen-Namen, evtl. content-writer-Aufgabe für 8 neue Kurzbeschreibungen
```

### UI

```
public/js/colony-hexgrid.js   — Label-Map (Z. 16-18) und zwei Farb-Maps (Z. 35-37, 44-46) von 3 auf 8 Einträge erweitern (ui-specialist)
```

### Backfill-Migration für bestehende Spielstände (wichtiger Fund, siehe §6 unten)

## 6. Owner-Entscheidungspunkte (offen, nicht selbst entschieden)

1. **Backfill für laufende Kolonien:** Die `config/game.php`-Maps verlieren die alten Keys `regolith_poor`/`regolith_normal`/`regolith_rich`. Jede **bereits existierende** `colony_tiles`-Zeile mit einem dieser alten `tile_type`-Werte (aktive Runs, Dev-DB `data/db/nouron.db`) würde nach dem Merge auf `fresh_yield = 0` / `resource_max = 0` fallen (Config-Lookup-Miss) — ein stiller Produktions-Bug für jeden Run, der zum Zeitpunkt des Merges läuft. Vorschlag: eine kleine Backfill-Migration `UPDATE colony_tiles SET tile_type = CASE tile_type WHEN 'regolith_poor' THEN 'regolith_y1_d1' WHEN 'regolith_normal' THEN 'regolith_y2_d2' WHEN 'regolith_rich' THEN 'regolith_y3_d2' END WHERE tile_type IN (...)`. Der `rich → y3_d2`-Fall ist ein Designentscheid (siehe Frage 1) — `rich` war die alte Top-Top-Kombination, die es jetzt nicht mehr gibt; `y3_d2` erhält den hohen Ertrag, senkt nur die Mächtigkeit leicht. **Braucht Owner-OK**, weil es eine echte Backfill-Berechnung ist (kein reiner Spalten-Zusatz) — laut CLAUDE.md TDD-Pflicht mit eigenem Test.
2. **Ist die vorgeschlagene `rich → y3_d2`-Zuordnung richtig,** oder soll `rich` stattdessen zu `y2_d3` (Mächtigkeit statt Ertrag priorisieren) migriert werden? Reine Geschmacksfrage, aber mit Auswirkung auf laufende Runs zum Merge-Zeitpunkt.
3. **Soll das H2-Paar immer exakt `y3_d1`/`y1_d3` sein** (konsistentes, gut lehrbares Muster über alle Runs — "das Rush-Tile" und "das Steady-Tile" werden feste Begriffe, die Spieler nach 1-2 Runs wiedererkennen), **oder soll die Identität des Paares selbst variieren** (z. B. mal `y3_d1`+`y1_d3`, mal `y3_d2`+`y1_d1` — mehr Roguelike-Varianz, aber weniger konsistentes Lernmuster für Neueinsteiger)? Diese Spec empfiehlt das feste Paar für den ersten Wurf (einfacher zu balancieren, einfacher zu erklären im Onboarding-Hint-Text), aber das ist explizit eine Owner-Präferenzfrage.
4. **Label-/Farbschema im Hexgrid** (8 statt 3 Werte) — reine `ui-specialist`-Umsetzungsfrage, aber der Grad an Text vs. Icon vs. Farbkodierung sollte kurz vom Owner abgesegnet werden, bevor `ui-specialist` das umsetzt (Aufwand hängt stark davon ab, ob eine zweite visuelle Dimension nötig ist).
5. **Timing:** Soll H1+H2 in einem PR zusammen mit der T9-Regolith-Startbestand-Kalibrierung (nächster Schritt laut Roadmap-Reihenfolge) gemessen werden, oder H1+H2 zuerst isoliert per Bot-Batch verifizieren, bevor T9 angefasst wird? Roadmap sagt „danach T9" — nur zur Bestätigung, dass die Reihenfolge weiterhin so gewünscht ist.
