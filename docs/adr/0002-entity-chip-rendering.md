# ADR 0002: Entity-Chip-Rendering — Strukturierte Segmente statt Platzhalter-Strings
Datum: 2026-06-07
Status: Akzeptiert

## Kontext

Der `CommLogController::buildDescription()` baut derzeit fertige PHP-Strings für Log-Einträge, z.B. `"80 Regolith gegen 200 Credits getauscht."` — Entity-Namen stehen als Plaintext ohne visuelle Auszeichnung.

Ziel ist es, Entity-Namen (Ressourcen, Gebäude, Kenntnisse, Schiffe, Berater) überall in der UI als wiederverwendbare **Chips** darzustellen: kleine farbige Inline-Elemente mit Hover-/Tap-Tooltip (Zusatzinfos je Typ). Die Chips sollen konsistent über mehrere Screens hinweg funktionieren (Kolonieprotokoll, Berater-Screen, Kolonie-Screen, etc.).

Zwei Architekturansätze standen zur Wahl.

## Entscheidung

**Option A — Strukturierte Segmente (Server-Side Rendering)** wird umgesetzt.

`buildDescription()` gibt statt eines fertigen Strings ein strukturiertes Segment-Array zurück:

```php
[
    ['type' => 'text',     'value' => '80 '],
    ['type' => 'resource', 'key'   => 'res_regolith'],
    ['type' => 'text',     'value' => ' gegen 200 '],
    ['type' => 'resource', 'key'   => 'res_credits'],
    ['type' => 'text',     'value' => ' getauscht.'],
]
```

Die Blade-View iteriert über die Segmente und rendert `<x-entity-chip>` für alle Nicht-Text-Segmente. Tooltip-Daten werden als `data-*`-Attribute mitgegeben. Alpine.js ist ausschließlich für die Popover-Interaktion zuständig — kein Rendering in JS.

## Konsequenzen

**Positiv:**
- Rendering ist vollständig server-seitig — kein JS-Hydration-Problem, kein FOUC
- Segment-Arrays sind unit-testbar (PHPUnit, ohne Browser)
- `<x-entity-chip>` ist eine eigenständige Blade-Komponente — wiederverwendbar auf beliebigen Screens
- Chip-Stile per Entity-Typ (`resource`, `building`, `knowledge`, `ship`, `advisor`) sauber über CSS-Klassen steuerbar
- Tooltip-Daten kommen aus Config/Lang (statisch) oder inline als `data-*` — kein separater API-Aufruf nötig
- Konsistent mit dem bestehenden Stack (Blade-Komponenten, Alpine.js nur für Interaktion)

**Negativ / Trade-offs:**
- `buildDescription()` muss vollständig umgebaut werden — alle Aufrufer müssen angepasst werden
- Blade-View muss Segment-Typ-Verzweigung (`@if segment.type === 'text'`) abbilden
- Rückwärtskompatibilität zu bestehenden Log-Einträgen (gespeicherte String-Werte in `colony_log`) muss geklärt werden — entweder Migration oder doppelter Render-Pfad

## Betrachtete Alternativen

**Option B — Platzhalter-Strings mit clientseitigem JS-Rendering:**
Controller gibt `"80 [[resource:res_regolith]] gegen 200 [[resource:res_credits]] getauscht."` zurück. Alpine.js-Komponente ersetzt Platzhalter zur Laufzeit mit Chip-HTML.

Verworfen, weil:
- Rendering-Abhängigkeit von JS — Chips fehlen bei deaktiviertem JS oder SSR
- Schwerer unit-testbar (Rendering-Logik in JS, nicht in PHP)
- Hydration-Komplexität steigt mit zunehmender Anzahl Chip-Typen
- Widerspricht dem Architektur-Prinzip "Alpine.js nur für Interaktion, kein DOM-Rendering"
