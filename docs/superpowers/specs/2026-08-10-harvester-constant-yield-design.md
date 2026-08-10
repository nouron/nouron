# Harvester: konstante Förderrate + Erschöpfungs-Countdown

**Status:** Entwurf, Owner-approved (Brainstorming 2026-08-10), bereit für Implementierungsplan.

## Kontext

Aktuell (GDD §4c „Erschöpfungskurve und Umzugstakt", freigegeben 2026-08-03) sinkt die Förderrate eines Harvesters kontinuierlich, während das Tile-Vorkommen abgebaut wird:

```
Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max)
```

Rampe von 100% auf 50% des Frischwerts, nie auf 0 solange Vorkommen da ist; erst bei vollständiger Erschöpfung (`Restvorkommen = 0`) fällt die Produktion auf 0 und die Verlegung ist erzwungen.

**Owner-Entscheidung (2026-08-10):** Das ist für den Spieler zu schwer zu durchschauen (unterschiedliche Förderrate pro Tick). Ziel: konstante Förderrate pro Tile-Qualität, bis das Tile leer ist (harter Cutoff), plus eine sichtbare Countdown-Anzeige im UI.

Die drei Tile-Qualitätsstufen (`regolith_rich`/`regolith_normal`/`regolith_poor`, aktuell 24/18/12 Rg/Sol Frischwert) bleiben unterschiedlich — das steuert weiterhin, ob sich eine Verlegung lohnt. Nur die **Abnahme innerhalb** eines einzelnen Tiles fällt weg.

Freiwilliges Verlegen vor vollständiger Erschöpfung bleibt möglich und unverändert (bereits heute so, keine Codeänderung nötig).

## Balance-Konsequenz (bewusst akzeptiert, nicht Teil dieses Scopes)

Mit konstanter Rate = heutiger Frischwert (statt Rampen-Durchschnitt) steigt der effektive Sockel-Durchschnitt spürbar (grob von 12,9 auf ~17 Rg/Sol bei `regolith_normal`, `resource_max` unverändert). Das reißt die gesamte §13.7-Bilanz (Bau-Preise 70/95/120, Instandhaltungs-%-Tabelle, Guard-Rails G2/G4/G6/G7) wieder auf.

**Explizite Owner-Entscheidung:** Wird nicht in diesem Schritt neu hergeleitet — Owner-Zitat: „müssen wir eh später nach mehreren Spielrun-Tests nochmal anpassen." §13.7 wird im GDD als veraltet markiert (Referenzwert 12,9 Rg/Sol stimmt nach dieser Änderung nicht mehr), aber nicht neu gerechnet. Eigenes, späteres Playtest-Vorhaben.

`resource_max`-Werte (500/300/160) bleiben unverändert — kein Retuning auf „rundere" Standzeiten in diesem Schritt (YAGNI, Owner hat das nicht verlangt).

## Design

### 1. Formel-Änderung (Backend)

`app/Console/Commands/GameTick.php`, `harvesterYield()` (aktuell Zeile ~780-796):

```php
public static function harvesterYield(string $tileType, int $remaining, int $resourceMax, int $geologyLevel): int
{
    if ($resourceMax <= 0 || $remaining <= 0) {
        return 0;
    }

    $fresh = (int) (config('game.harvester.fresh_yield', [])[$tileType] ?? 0);
    if ($fresh <= 0) {
        return 0;
    }

    $geologyBonus = self::cumulativeCurveYield(config('game.geology_harvester_bonus_per_level', []), $geologyLevel);

    return $fresh + $geologyBonus;
}
```

Entfernt: `$ratio`/`$base`-Rampen-Berechnung. `$remaining`/`$resourceMax`-Parameter bleiben in der Signatur (weiterhin für den `<= 0`-Cutoff-Check gebraucht), auch wenn sie sonst nicht mehr in die Rate einfließen — keine Signaturänderung, um Call-Sites nicht anzufassen.

Docblock (Zeilen 768-779) entsprechend umschreiben: keine Rampe mehr, konstante Rate bis Erschöpfung.

Aufrufer-Logik (`generateHarvesterYield()`, Zeilen 862-952) bleibt unverändert — Abzug von `resource_amount`, Geologie-Bonus-Zuteilung „einmal pro Kolonie", Trust-Multiplikator-Anwendung (`$credited = round($base * $multiplier)`) funktionieren identisch mit der neuen Formel, da nur `harvesterYield()`s interne Berechnung wechselt, nicht der Rückgabetyp oder die Aufrufkonvention.

### 2. „Sole bis leer"-Schätzung

Serverseitig berechnet, nicht clientseitig — die tatsächliche Abbaurate hängt vom Geologie-Bonus (levelbar) und Vertrauens-Multiplikator (schwankt) ab, beide sind nur serverseitig zuverlässig bekannt.

Neue Methode (Vorschlag: `ColonyTileService` oder wo `resource_amount`/`resource_max` bereits pro Tile serialisiert werden — exakte Stelle im Implementierungsplan lokalisieren):

```
solsRemaining(tile): int|null
  wenn resource_amount <= 0 → null (schon leer, kein Countdown nötig)
  effectiveRate = harvesterYield(tile_type, resource_amount, resource_max, aktuellerGeologyLevel) × aktuellerTrustMultiplier
  wenn effectiveRate <= 0 → null (keine Produktion, z.B. kein Vorkommen konfiguriert)
  return ceil(resource_amount / effectiveRate)
```

Als **Schätzung** kommunizieren (UI-Label „ca. N Sole"), nicht als Garantie — Trust-Multiplikator und Geologie-Level können sich ändern.

Feld `sols_remaining` (nullable int) wird an der Stelle ergänzt, an der `resource_amount`/`resource_max` heute schon an die View/den JSON-Response gereicht werden (Tile-Info-Endpoint bzw. `hexview`-Compact — im Implementierungsplan die exakte(n) Stelle(n) per Grep finden, da `resource_amount`/`resource_max` an mehreren Stellen seriealisiert werden könnten).

### 3. UI (Tile-Panel)

`resources/views/colony/hexview.blade.php`, Tile-Panel-Bereich (dort wo heute schon Harvester-Move-Aktionen angezeigt werden, ~Zeile 262ff, `selectedBuilding?.building_key === 'building_harvester'`).

Neue Zeile: „≈{{ sols_remaining }} Sole bis Erschöpfung" (exakter Lang-Key + Text: Aufgabe für `content-writer` im Implementierungsplan, GDD-Ton beachten).

Farbschwelle: `sols_remaining <= 3` → Warnfarbe. Exaktes CSS-Klassen-/Farbtoken muss im Implementierungsplan mit den bestehenden Decay-Status-Farben abgeglichen werden (Konsistenz mit Gebäude-Status-Anzeigen, `design-system`).

Kein Eintrag, wenn `sols_remaining === null` (Tile hat keinen Harvester oder ist bereits erschöpft — dort gilt weiterhin die bestehende „Umzug erzwungen"-Anzeige, unverändert).

### 4. GDD-Update

`docs/GDD.md`, Abschnitt „Erschöpfungskurve und Umzugstakt" (§4c, aktuell ~Zeile 828-849):

- Formel-Zeile ersetzen: konstante Rate statt Rampe.
- Tabelle „Tile-Stufe | Frischwert | resource_max | Ø über den Zyklus | Standzeit" — die „Ø über den Zyklus"-Spalte entfällt (kein Rampen-Durchschnitt mehr, Ø = Frischwert solange nicht erschöpft), Standzeit-Werte ändern sich (z.B. `regolith_normal`: 300/18 ≈ 17 statt 22 Sole — im Implementierungsplan exakt nachrechnen und rundend dokumentieren, kein neuer Festwert, nur Herleitung).
- Absatz „Der Sockel aus §13.7 ist ein Durchschnitt, kein Frischwert. […] liefert ein Harvester im Zyklusmittel ~12,9 Rg/Sol." — als **veraltet, §13.7-Neuherleitung nach Playtest ausstehend** markieren, nicht selbst neu rechnen.
- Tabelle „Phase | Harvester | Ø Sockel" (12,9/25,8) — gleiche Markierung: veraltet, wartet auf Neuherleitung.
- Neuer Hinweis-Block: warum die Änderung (Spieler-Verständlichkeit), Verweis auf diesen Spec.

Kein Antasten von §13.7 selbst (Bau-Preise, Guard-Rails) — nur der Verweis „veraltet, Neuherleitung ausstehend" an der Formel-Quelle.

### 5. Tests (TDD-Pflicht, CLAUDE.md)

- `GameTick::harvesterYield()`: Unit-Test, konstante Rate über den gesamten `remaining`-Bereich (nicht mehr abnehmend bei sinkendem `remaining`), weiterhin 0 bei `remaining <= 0` oder `resourceMax <= 0`.
- `generateHarvesterYield()`-Integrationstest: über mehrere Tick-Durchläufe simulieren, `resource_amount` sinkt linear (nicht mehr in abnehmenden Schritten), Cutoff auf 0 exakt bei Erschöpfung.
- Bestehende Tests, die die alte Rampen-Formel exercisen (`grep -rn "0.5 + 0.5\|harvesterYield" tests/` im Implementierungsplan finden) müssen umgeschrieben werden — TDD: erst rot (mit neuer Erwartung gegen alten Code), dann Formel-Fix, dann grün.
- Neuer Test für `sols_remaining`-Berechnung (verschiedene Restvorkommen/Geologie-Level/Trust-Kombinationen).
- Feature-/Browser-Test (falls im Projekt üblich, sonst manuelle Verifikation laut CLAUDE.md): Warnfarbe erscheint bei `sols_remaining <= 3`.

## Offene Punkte für den Implementierungsplan

- Exakte Stelle(n), an denen `resource_amount`/`resource_max` heute an View/JSON gereicht werden (Grep nötig, mehrere Call-Sites möglich: `ColonyTileService`, `ColonyController::hexview`).
- Exaktes Farbtoken für die Warnstufe (Abgleich mit `docs/design-system/`).
- Exakter Lang-Text (DE/EN) für die Countdown-Zeile (`content-writer`).
- Neue Standzeit-Werte exakt nachrechnen (300/18, 500/24, 160/12) und in der GDD-Tabelle dokumentieren.
