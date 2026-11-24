# ADR 0001: Grafik-Asset-Format und Skalierungsstrategie
Datum: 2026-05-15
Status: Akzeptiert

## Kontext

Nouron benötigt illustrierte Spielgrafiken für Icons, Portraits, Tiles, Schiffe, Gebäude und Ressourcen.
Bisher war das Asset-Format nicht verbindlich festgelegt. Unterschiedliche Formate (PNG, SVG, unbekannte Auflösungen) würden zu inkonsistenter Darstellung auf verschiedenen Bildschirmen und erhöhtem Wartungsaufwand führen.

Das Spiel soll auf Desktop, Tablet und Smartphone nutzbar sein (responsive). Grafiken dürfen nicht pixelig wirken auf HiDPI-Displays.

## Entscheidung

### Grafikstil

Alle Spiel-Grafiken (Icons, Portraits, Tiles, Schiffe, Gebäude, Ressourcen, Forschungen) haben einen **gezeichneten/illustrierten Stil** — kein Pixel-Art, keine fotorealistischen Renderings.

### Asset-Format

- Format: **WebP** mit transparentem Hintergrund (Alpha-Kanal)
- Auflösung: **2× Zielgröße** — der Grafiker liefert doppelte Pixelzahl (HiDPI-ready)
- Kein SVG für Illustrations-Assets — SVG ist ausschließlich für UI-Struktur (Hex-Grid, strukturelle Icons) reserviert

### Richtwert-Größen (Zielgröße → Datei-Liefergröße)

| Asset-Typ | Zielgröße | Datei (2×) |
|---|---|---|
| Ressourcen-Icons | 24×24 px | 48×48 px |
| Gebäude-Icons (Sidebar) | 32×32 px | 64×64 px |
| Gebäude auf Kartenfeld (Nachtrag 2026-11-23) | ca. 1,4× Feldgröße | 192×192 px, freigestellt (`buildings/map/`) |
| Schiffs-Icons | 48×48 px | 96×96 px |
| Schiffs-Bild (Hangar-Karte, Nachtrag 2026-11-17) | ca. 400×280 px | 800×560 px, weißer Hintergrund statt Transparenz (Owner-Entscheidung) |
| Berater-Portraits | 128×128 px | 256×256 px |
| Hex-Feld-Szenen (Nachtrag 2026-11-22) | ca. 2× Feldradius | 256×256 px, Varianten `<art>_2` … |

### CSS-Integration

- Container-Größen in `em` oder `rem` — keine fixen `px`-Größen für Icons und Portraits
- Bilder füllen ihren Container vollständig: `width: 100%; height: 100%; object-fit: contain;`
- Responsiv by default — skaliert korrekt auf Desktop, Tablet und Smartphone

### Hex-Tiles (Sonderfall)

Tiles sind SVG-Polygone in `colony-hexgrid.js`. Illustrierte Tile-Texturen werden als `<image>` innerhalb eines SVG-`<clipPath>` eingebunden:

```svg
<clipPath id="hex-clip-q0-r0"><polygon points="..."/></clipPath>
<image href="/img/tiles/rocky.webp" clip-path="url(#hex-clip-q0-r0)" .../>
```

### Ablagestruktur (public/)

```
public/img/
  icons/         -- Ressourcen-Icons, UI-Icons
  buildings/     -- Gebäude-Illustrationen (1536 px, Detailansichten)
  buildings/map/ -- freigestellte Gebäude für die Kartenfelder (192 px, aus den Illustrationen erzeugt)
  ships/         -- Schiffs-Icons
  advisors/      -- Berater-Portraits
  tiles/         -- Feld-Szenen je Geländeart mit Varianten (256 px), Nebel, Kartenhintergrund
```

## Konsequenzen

**Positiv:**
- Einheitlicher illustrierter Stil stärkt visuelle Konsistenz
- WebP ist kompakter als PNG bei gleicher oder besserer Qualität
- 2×-Lieferung deckt HiDPI/Retina-Displays ab ohne zusätzlichen Aufwand
- `object-fit: contain` in `em`-Containern ist wartungsarm und responsiv

**Negativ / Trade-offs:**
- Grafiker muss verbindlich 2×-Dateien liefern — Briefing muss klar sein
- Hex-Tile-Größe ist erst berechenbar wenn SIZE-Konstante in `colony-hexgrid.js` feststeht
- Kein SVG für Illustrationen bedeutet: Texturschärfe hängt von gelieferter Auflösung ab

## Betrachtete Alternativen

**PNG statt WebP:** PNG hätte breitere Tool-Unterstützung, ist aber größer. WebP wird von allen modernen Browsern unterstützt — kein Fallback notwendig.

**SVG für alle Assets:** SVG ist für geometrische Icons gut geeignet, aber nicht für illustrierte Grafiken mit organischen Formen und Texturen. Für Tile-Texturen wäre SVG-Illustration zu komplex im Wartungsaufwand.

**1× Auflösung:** Würde auf HiDPI-Displays (Retina, moderne Smartphones) pixelig wirken. 2× ist der aktuelle Standard.

**CSS-Sprites:** Kein Vorteil gegenüber einzelnen WebP-Dateien bei modernem HTTP/2. Erschwertes Asset-Management.
