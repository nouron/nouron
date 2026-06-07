# Entity-Chip-System — GDD-Spezifikation

**Status:** Design-Entwurf (Stand: 2026-06-07)
**Betrifft:** Kolonieprotokoll (CommLog), alle Screens mit Entity-Referenzen in Fliesstext
**Prioritat:** Phase 3k (nach Phase 3j CommLog-Redesign)

---

## Problemstellung

`CommLogController::buildDescription()` baut fertige Strings wie:

> "80 Regolith gegen 200 Credits getauscht."
> "Kenntnis Agrarwissenschaften auf Level 2 gestiegen."
> "Korvette Eisfalke durch Verfall zerstört."

Entity-Namen stehen als reiner Plaintext im Fliesstext. Der Spieler sieht den Namen, aber keine weiteren Infos — kein Icon, kein aktueller Level, kein Kontext. Um mehr zu erfahren, muss er manuell zum entsprechenden Screen navigieren.

**Ziel:** Entity-Namen werden als visuell abgehobene Chips dargestellt, die im Fliesstext eingebettet sind. Hover (Desktop) oder Tap (Mobile) zeigt einen Tooltip mit Zusatzinfos. Optional klickbar als Link zum zugehörigen Screen.

---

## Scope

Dieses Dokument definiert das Design-Muster. Die konkrete Implementierung (Blade-Partial, CSS, Alpine.js-Komponente) obliegt dem UI-Spezialisten.

Chips erscheinen zunachst im Kolonieprotokoll. Das Muster soll aber wiederverwendbar sein: Tooltips auf Techtree-Karten, Berater-Screen, Cantina-Angebote — überall wo Entity-Namen im Fliesstext oder in kompakten Listenelementen auftauchen.

---

## Allgemeines Chip-Muster

Ein Entity-Chip ist ein `<span>` (oder `<a>` wenn klickbar) im Inline-Kontext mit:

- **Icon** links (Bootstrap Icon, 1em, passt zur Zeilenhöhe)
- **Label** = der lokalisierte Entity-Name
- **Visueller Stil**: Outline-Pill analog zu den bestehenden Resource Pills (Design Guide §5.6), aber mit entity-typ-spezifischer Akzentfarbe
- **Tooltip** bei Hover/Tap: via Alpine.js `x-data` + `x-show`, kein CSS-only `:hover` (Mobile-Anforderung)
- **Daten**: via `data-*`-Attribute auf dem Chip-Element vorgeladen — kein AJAX pro Chip

### HTML-Grundstruktur (konzeptuell)

```html
<span
    class="entity-chip entity-chip--building"
    data-entity-type="building"
    data-entity-key="harvester"
    data-entity-level="3"
    data-entity-desc="Fördert Regolith aus dem Untergrund."
    data-entity-link="/colony"
    x-data="entityChip()"
    @click="toggle()"
    @mouseenter="open()"
    @mouseleave="close()"
>
    <i class="bi bi-hammer" aria-hidden="true"></i>
    Harvester
    <span class="entity-chip-tooltip" x-show="visible" x-cloak>
        <!-- Tooltip-Inhalt -->
    </span>
</span>
```

Das Alpine-Komponenten-Muster (`entityChip()`) wird vom UI-Spezialisten definiert. Die Datenerzeugung liegt im PHP-Layer — `buildDescription()` (oder ein neues Blade-Partial) setzt die `data-*`-Attribute aus bereits vorhandenen PHP-Daten.

### Tooltip-Verhalten

- **Desktop**: `@mouseenter` öffnet, `@mouseleave` schliesst. Keine Verzögerung.
- **Mobile**: `@click`/`@touchend` togglet. Klick ausserhalb schliesst (globaler `@click.away`).
- **Positionierung**: Der Tooltip öffnet sich nach oben, wenn ausreichend Platz, sonst nach unten. Einfache CSS-Logik, kein JS-Positioning-Framework.
- **Z-index**: Tooltip überlappt umgebenden Text (`z-index: 100`), aber nicht die Navbar (`z-index: 1000`).

---

## Entity-Typen

### 1. `building` — Gebäude

**Chip-Aussehen**
- Icon: `bi-hexagon` (konsistent mit dem area-icon für `colony` im CommLog)
- Stil-Variante: `.entity-chip--building`
- Akzentfarbe: Dunkelgrau (`#4a4a58`) — neutral, strukturell
- Label: lokalisierter Gebäudename aus `lang/de/techtree.php`

**Tooltip-Inhalt**
- Gebäudename (fett)
- Aktueller Level (sofern bekannt: "Level 3")
- Kurzbeschreibung (1 Satz, aus `lang/de/buildings.php` oder Tooltip-Key)
- Link-Hinweis: "Kolonie-Ansicht aufrufen"

**Link-Ziel**
- Klickbarer Link zur Kolonieansicht (`/colony`). Kein direkter Anker auf das Gebäude (Hex-Grid unterstützt das aktuell nicht).

**Datenquelle**
- `entity_key` (Config-Key wie `harvester`) → statisch aus Config-Array in PHP
- `level` → dynamisch aus DB (`colony_buildings.level`) — muss im Controller mitgeladen werden
- `desc` → statisch aus Lang-Datei

> Einschränkung: Level ist nur verfügbar wenn der Log-Eintrag einen Gebäude-Kontext hat (z.B. `building_invested`). Bei `level_down`-Einträgen ist `new_level` bereits im Params-Array vorhanden und kann direkt genutzt werden. Wenn Level nicht bekannt: Level-Zeile im Tooltip weglassen.

---

### 2. `knowledge` — Kenntnis

**Chip-Aussehen**
- Icon: `bi-book` (passt zu Wissen/Forschung)
- Stil-Variante: `.entity-chip--knowledge`
- Akzentfarbe: Blaugrau (`#3a5a7a`) — Wissens-Konnotation ohne aggressiv zu wirken
- Label: lokalisierter Name aus `lang/de/techtree.php`

**Tooltip-Inhalt**
- Kenntnisname (fett)
- Aktueller Level
- 1-Satz-Beschreibung des Nutzens (z.B. "Ermöglicht effizienteren Gebäudebau.")
- Hinweis auf zugehörigen Berater-Typ wenn vorhanden (z.B. "Baumeister-Kenntnis")

**Link-Ziel**
- Link zum Techtree-Screen (`/techtree`), idealerweise mit Anker/Filterparameter zum Kenntnistyp wenn technisch vorhanden.

**Datenquelle**
- Statisch: Name und Beschreibung aus Lang-Datei
- Dynamisch: aktueller Level aus DB (Techtree-Tabelle), analog zu Gebäuden

---

### 3. `resource` — Ressource

**Chip-Aussehen**
- Icon: ressourcen-spezifisch (analog zu den bestehenden Resource Pills aus Design Guide §5.6)
  - `credits` → `bi-coin`
  - `supply` → `bi-people`
  - `regolith` → `bi-layers`
  - `compounds` → `bi-grid-3x3`
  - `organics` → `bi-flower1`
  - `trust` → `bi-heart`
- Stil-Variante: `.entity-chip--resource` + ressourcen-spezifische Farbklasse (analog zu bestehenden res-chip-Klassen)
- Akzentfarbe: Ressourcen-spezifisch (bestehende Farbdefinitionen aus `resources.css` wiederverwenden)
- Label: lokalisierter Ressourcenname aus `lang/de/resources.php`

**Tooltip-Inhalt**
- Ressourcenname (fett)
- Aktueller Bestand der Kolonie (dynamisch — muss vorgeladen werden)
- Kurzbeschreibung (1 Satz)
- Handelbar / nicht handelbar (Kennzeichnung)

**Link-Ziel**
- Kein dedizierter Screen für Ressourcen. Kein Link, oder optionaler Link zur Kolonie-Ansicht wenn sinnvoll.

**Datenquelle**
- Name und Typ: statisch aus `resources`-DB-Tabelle / Lang-Datei
- Aktueller Bestand: dynamisch aus `colony_resources` — muss im Controller für alle im Text referenzierten Ressourcen vorgeladen werden (ein einziger DB-Call für alle Ressourcen der Kolonie, nicht pro Chip)

> Wichtig: Der Ressourcen-Chip ist der häufigste Chip-Typ (taucht in fast jeder Handelsnachricht auf). Die Tooltip-Daten müssen effizient sein — der Kolonie-Ressourcenstand wird einmalig geladen und als assoziatives Array (`resource_id => amount`) durch den Render-Layer gereicht.

---

### 4. `ship` — Schiff

**Chip-Aussehen**
- Icon: `bi-rocket-takeoff`
- Stil-Variante: `.entity-chip--ship`
- Akzentfarbe: Dunkelblau (`#1e3a5f`)
- Label: Schiffsname (Eigenname des Schiffs, nicht Typ-Name) oder Typ-Name falls kein Eigenname

**Tooltip-Inhalt**
- Schiffsname (fett)
- Typ (Drohne / Frachter / Korvette)
- Aktueller Status (im System? Unterwegs? Auf Mission?)
- Level sofern vorhanden

**Link-Ziel**
- Hangar-Screen (`/hangar`)

**Datenquelle**
- Schiffsname: aus `ships`-Tabelle (Eigenname-Feld)
- Typ: aus `ships`-Tabelle
- Status: aus `fleet_orders` oder `ships.status` — dynamisch, muss vorgeladen werden
- Für CommLog-Kontext: Status ist zum Zeitpunkt des Log-Eintrags historical (Schiff könnte seither zerstört sein). Tooltip zeigt "Schiff nicht mehr aktiv" falls nicht mehr in DB vorhanden.

---

### 5. `advisor` — Berater

**Chip-Aussehen**
- Icon: typ-spezifisch:
  - `engineer` → `bi-wrench`
  - `scientist` → `bi-flask`
  - `pilot` → `bi-compass`
  - `trader` → `bi-briefcase`
  - `strategist` → `bi-shield`
- Stil-Variante: `.entity-chip--advisor`
- Akzentfarbe: Warmgrau (`#5a4a3a`) — menschliche/personelle Konnotation
- Label: lokalisierter Berater-Typname aus `lang/de/advisors.php` (kein Eigenname bei Beratern)

**Tooltip-Inhalt**
- Beratertypname (fett)
- AP-Typ (z.B. "Bau-AP")
- Anzahl aktiver Berater dieses Typs
- Aktueller Rang (Junior / Senior / etc.)

**Link-Ziel**
- Berater-Screen (`/advisors`)

**Datenquelle**
- Typname und AP-Typ: statisch aus `config/advisors.php`
- Anzahl und Rang: dynamisch aus `personell`-Tabelle — muss vorgeladen werden

---

### 6. `research` — Forschungsentität (Techtree-Knoten, der kein Gebäude/Kenntnis ist)

**Chip-Aussehen**
- Icon: `bi-diagram-3`
- Stil-Variante: `.entity-chip--research`
- Akzentfarbe: Dunkelgrün (`#2a5a3a`) — Forschungs-/Fortschritts-Konnotation
- Label: lokalisierter Name aus `lang/de/techtree.php`

**Tooltip-Inhalt**
- Name (fett)
- Typ-Hinweis: "Techtree-Entität"
- Aktueller Level
- Kurzbeschreibung

**Link-Ziel**
- Techtree-Screen (`/techtree`)

**Datenquelle**
- Statisch aus Lang-Datei / Config
- Level dynamisch aus `researches`-Tabelle

> Hinweis: `research` ist ein Fallback-Typ für Techtree-Knoten die weder als `building` noch als `knowledge` klassifiziert sind. Im aktuellen Stand ist dieser Typ dünn besetzt — taucht vor allem in Edge-Cases des Level-Up-Events auf.

---

## Datenlademodell

### Grundsatz: Kein AJAX pro Chip

Alle Tooltip-Daten werden serverseitig in den `data-*`-Attributen des Chips vorgeladen. Der Browser macht keine Requests wenn ein Tooltip geöffnet wird.

### Preloading-Strategie im CommLogController

Der Controller lädt beim Aufbau der Eintrags-Liste:

1. **Ressourcenstände** (einmalig): `SELECT resource_id, amount FROM colony_resources WHERE colony_id = ?` — Ergebnis als `$resourceAmounts[resource_id => amount]`.
2. **Gebäude-Level** (einmalig): `SELECT building_type, level FROM colony_buildings WHERE colony_id = ?` — Ergebnis als `$buildingLevels[type => level]`.
3. **Berater-Daten** (einmalig): Anzahl und Rang pro Beratertyp aus `personell`.
4. **Schiffs-Status** (einmalig): Alle Schiffe des Spielers mit Status.

Diese Daten werden als Arrays durch den `decorate()`-Aufruf gereicht und beim Chip-Aufbau in `data-*`-Attribute geschrieben. Kein N+1, kein AJAX.

### data-Attribut-Schema (pro Chip)

| Attribut | Inhalt | Beispiel |
|---|---|---|
| `data-chip-type` | Entity-Typ | `building` |
| `data-chip-key` | Config/DB-Key | `harvester` |
| `data-chip-label` | Anzeigename (lokalisiert) | `Harvester` |
| `data-chip-level` | Aktueller Level (wenn bekannt) | `3` |
| `data-chip-desc` | Kurzbeschreibung | `Fördert Regolith.` |
| `data-chip-link` | Ziel-URL (leer = kein Link) | `/colony` |
| `data-chip-meta` | Optionale Zusatzinfo (JSON-String) | `{"ap_type":"construction"}` |

`data-chip-meta` ist ein Escape-Hatch für typ-spezifische Zusatzdaten (z.B. Berater-AP-Typ, Ressourcen-Handelsbarkeit) die nicht in das flache Schema passen. Der Tooltip-Renderer liest ihn per `JSON.parse`.

---

## Integration in CommLogController

### Jetzt: Plaintext-String

```php
// buildDescription() gibt zurück:
"80 Regolith gegen 200 Credits getauscht."
```

### Künftig: HTML-String mit Chips

```php
// buildDescription() gibt zurück (vereinfacht):
'<span ...>80</span> '
. $this->renderResourceChip(3, $resourceAmounts)  // Ressource ID 3 = Regolith
. ' gegen '
. '<span ...>200</span> '
. $this->renderResourceChip(1, $resourceAmounts)  // Ressource ID 1 = Credits
. ' getauscht.'
```

Die `comm-entry-label`-Span im Blade-Template muss dann `{!! $entry['description'] !!}` (unescaped) statt `{{ $entry['description'] }}` verwenden — mit dem expliziten Bewusstsein, dass die Beschreibung controllerseitig gebaut wird und keine User-Eingaben enthält.

> Sicherheitsnotiz: Entity-Namen kommen aus der DB (admin-kontrolliert) oder Lang-Dateien (statisch). Keine User-generierten Strings in Chip-Labels. Die Render-Methoden müssen trotzdem `htmlspecialchars()` auf alle dynamischen Werte anwenden bevor sie in `data-*`-Attribute geschrieben werden.

### Render-Helper-Methode (Pseudocode)

```php
private function renderChip(
    string $type,
    string $label,
    string $icon,
    ?string $link,
    array $chipData = []
): string {
    $dataAttrs = 'data-chip-type="' . e($type) . '" '
        . 'data-chip-label="' . e($label) . '" '
        . 'data-chip-link="' . e($link ?? '') . '"';

    foreach ($chipData as $key => $value) {
        $dataAttrs .= ' data-chip-' . $key . '="' . e($value) . '"';
    }

    $tag = $link ? 'a href="' . e($link) . '"' : 'span';
    $closeTag = $link ? 'a' : 'span';

    return '<' . $tag . ' class="entity-chip entity-chip--' . $type . '" '
        . $dataAttrs
        . ' x-data="entityChip()" @mouseenter="open()" @mouseleave="close()" @click="toggle()">'
        . '<i class="bi ' . $icon . '" aria-hidden="true"></i> '
        . e($label)
        . '<span class="entity-chip-tooltip" x-show="visible" x-cloak><!-- ... --></span>'
        . '</' . $closeTag . '>';
}
```

Die genaue Tooltip-HTML-Struktur innerhalb des Chips wird im Blade-Partial definiert — nicht im Controller. Der Controller gibt einen Chip-Platzhalter aus; das Partial rendert den Tooltip-Inhalt aus den `data-*`-Attributen per Alpine.js.

---

## CSS-Design-Tokens (Chip-Stile)

Chips teilen eine gemeinsame Basis (`.entity-chip`) und erhalten typ-spezifische Varianten (`.entity-chip--building` etc.).

### Basis

```
display: inline-flex
align-items: center
gap: 0.25rem
padding: 0.1em 0.5em
border-radius: 999px
font-size: 0.875em   /* relativ zur umgebenden Schriftgrösse */
font-weight: 500
line-height: 1.4
white-space: nowrap
cursor: default      /* kein pointer ausser bei klickbaren Chips */
position: relative   /* Tooltip-Positionierung */
vertical-align: baseline
border: 1px solid <typ-akzentfarbe, 30% Opacity>
background: <typ-akzentfarbe, 8% Opacity>
color: <typ-akzentfarbe>
```

### Varianten (Beispiel)

| Variante | Farbe | Anwendung |
|---|---|---|
| `--building` | `#4a4a58` | Gebäude |
| `--knowledge` | `#3a5a7a` | Kenntnisse |
| `--resource` | ressourcen-spezifisch | Ressourcen |
| `--ship` | `#1e3a5f` | Schiffe |
| `--advisor` | `#5a4a3a` | Berater |
| `--research` | `#2a5a3a` | Sonstige Techtree-Entitäten |

Klickbare Chips (mit Link) erhalten `:hover { text-decoration: underline; cursor: pointer; }`.

### Tooltip-Popup

```
position: absolute
bottom: calc(100% + 0.4rem)
left: 0
min-width: 14rem
max-width: 22rem
background: #ffffff
border: 1px solid #e8e8ec
border-radius: 6px
box-shadow: 0 2px 8px rgba(0,0,0,0.10)
padding: 0.65rem 0.8rem
font-size: 0.8rem
color: #1a1a1e
z-index: 100
pointer-events: none  /* kein hover-Loop */
```

Auf Mobile (`max-width: 767px`): Tooltip positioniert sich zentriert, maximal volle Viewport-Breite minus Rand.

---

## Verhalten bei fehlenden Daten

Chips werden immer gerendert — auch wenn Tooltip-Daten unvollständig sind. Fehlende Werte werden nicht mit "?" aufgefüllt, sondern die entsprechende Zeile im Tooltip wird weggelassen.

Beispiel: Ein `level_down`-Eintrag für ein Schiff — Schiff existiert nicht mehr in der DB. Der Chip zeigt den gespeicherten Namen, der Tooltip zeigt "Nicht mehr aktiv." statt Level oder Status.

---

## Nicht in dieser Iteration

Die folgenden Punkte sind bewusst ausgeklammert und werden nach dem Playtest evaluiert:

- Chips ausserhalb des CommLog (Techtree, Cantina, Berater-Screen) — Pattern zuerst im CommLog validieren
- Klickbare Chips mit Deeplinks in Screens (z.B. direkter Anker auf ein Gebäude im Hex-Grid) — abhängig von Screen-Implementierung
- Animationen beim Tooltip-Öffnen — kein Aufwand bis Feedback vorliegt
- Chips in Nexus-Funk-Nachrichten — Nexus-Karten haben eigenes Layout; gesonderter Review nötig
- Touch-Verhalten auf Tablets (Mittelzone) — zunächst Desktop + Phone, Tablet folgt

---

## Offene Fragen

1. **Tooltip in Fliesstext**: Wenn mehrere Chips in einer Zeile stehen, und einer davon einen Tooltip öffnet, kann der Tooltip den Text dahinter verdecken. Akzeptabel? Oder Tooltip immer in einem fixen "detail panel" unterhalb der Zeile?

2. **Chip-Grösse in ellipsis-Kontext**: `comm-entry-label` hat `overflow: hidden; text-overflow: ellipsis`. Ein langer Chip-Name könnte abgeschnitten werden. Option: `white-space: normal` für die Label-Span wenn Chips enthalten sind.

3. **Sicherheitsgrenzen beim HTML-Output**: `buildDescription()` gibt künftig HTML zurück statt Plaintext. Das erfordert disziplinierte Trennung in der Controller-Logik. Sollte der HTML-Output in ein dediziertes Blade-Partial verlagert werden, das der Controller nur mit strukturierten Daten befüllt (statt HTML-Strings zu bauen)?

> Empfehlung zu Frage 3: Ja — Controller gibt strukturierte Daten (Array mit Text-Segmenten und Chip-Descriptors), Blade-Partial rendert das HTML. Damit bleibt der Controller testbar und die Template-Logik im Template. Das ist ein grösserer Refactor als ein reiner HTML-String-Ansatz, aber sauberer langfristig.

---

## Abhängigkeiten

- Bestehende Resource Pills (Design Guide §5.6) — Chip-Pattern ist deren Erweiterung, nicht Ersatz
- `CommLogController` — Haupt-Integrationsort (Phase 1)
- Alpine.js — für Tooltip-Toggle (bereits im Stack)
- Bootstrap Icons — bereits eingebunden (Chip-Icons)
- `public/css/comm_log.css` — Chip-CSS wird hier ergänzt oder in separates `entity-chips.css` ausgelagert

---

## Zusammenfassung Entscheidungen

| Thema | Entscheidung |
|---|---|
| Tooltip-Technik | Alpine.js `x-show`, kein CSS-only |
| Datenladen | Preload via `data-*`, kein AJAX |
| HTML-Ausgabe | Controller baut HTML-Strings (kurzfristig); Blade-Partial langfristig bevorzugt |
| Mobile | Tap-Toggle via `@click`, `@click.away` zum Schliessen |
| Klickbarkeit | Chips mit Link = `<a>`, ohne Link = `<span>` |
| Fehlertoleranz | Chip wird immer gerendert; fehlende Daten = weggelassene Tooltip-Zeile |
| Scope Phase 1 | Nur CommLog — andere Screens nach Playtest-Feedback |
