# Nouron — Design Guide

**Version:** 1.0 (Mai 2026)
**Verbindlich für:** Alle neuen Screens ab Phase 3b (Alpine.js + PicoCSS). Legacy-Screens (Bootstrap 5) folgen diesem Guide bei der schrittweisen Migration.

---

## Inhaltsverzeichnis

1. [Prinzipien](#1-prinzipien)
2. [Farbpalette](#2-farbpalette)
3. [Typografie](#3-typografie)
4. [Spacing-System](#4-spacing-system)
5. [Komponenten](#5-komponenten)
   - 5.1 [Navbar (Haupt-Navigation)](#51-navbar-haupt-navigation)
   - 5.2 [Resource Bar](#52-resource-bar)
   - 5.3 [Subnav / Tabs](#53-subnav--tabs)
   - 5.4 [Cards und Panels](#54-cards-und-panels)
   - 5.5 [Buttons](#55-buttons)
   - 5.6 [Resource Pills (res-chip)](#56-resource-pills-res-chip)
   - 5.7 [Tabellen](#57-tabellen)
   - 5.8 [Status-Badges und Labels](#58-status-badges-und-labels)
6. [Screen-Typen](#6-screen-typen)
   - 6.1 [Lobby](#61-lobby)
   - 6.2 [In-Run Screens (Standard)](#62-in-run-screens-standard)
   - 6.3 [Carousel-Screens](#63-carousel-screens)
   - 6.4 [Cantina / Bar](#64-cantina--bar)
7. [Grafik-Assets](#7-grafik-assets)
8. [Responsive Breakpoints](#8-responsive-breakpoints)
9. [Do / Don't Kurzreferenz](#9-do--dont-kurzreferenz)

---

## 1. Prinzipien

Nouron ist ein Browserspiel mit dem Anspruch, elegant und konzentriert zu wirken — nicht überladen, nicht dramatisch dunkel. Die visuelle Sprache soll die Kern-Fantasie unterstützen: eine kleine Kolonie, sorgfältig verwaltet, unter knappen Bedingungen.

**Hell, fokussiert, viel Luft.** Weißraum ist ein aktives Gestaltungsmittel, keine Lücke. Screens sind nicht vollgepackt. Jedes Element rechtfertigt seinen Platz.

**Minimal Boxen.** Cards und Panels nur dort, wo sie semantisch Sinn ergeben — um eine Entität (einen Run, einen Berater, ein Gebäude) zu gruppieren. Kein Boxen-um-alles-Reflex.

**Serif für Identität, System-Sans für Funktion.** Das Nouron-Logo und Haupt-Überschriften tragen die Markenidentität via Libre Baskerville. Alles Funktionale — Navigation, Labels, Buttons, Tabellen — nutzt system-ui. Kein Mix innerhalb einer Ebene.

**Akzentfarbe sparsam.** Nouron-Rot (`#8c2030`) ist eine elegante Markenfarbe, kein Signal-Rot. Sie markiert aktive Zustände und primäre Aktionen — nicht Warnungen, nicht Fehler. Für Warnungen und Fehler gelten eigene semantische Farben.

**Keine Dark-Mode-Defaults.** Neue Screens sind hell by default. `data-theme="light"` explizit setzen wenn PicoCSS geladen wird, damit Systemeinstellungen nicht durchschlagen.

**Kein jQuery.** jQuery wurde vollständig entfernt (Mai 2026). Legacy-Screens (Bootstrap 5) nutzen Vanilla JS — kein jQuery mehr, auch nicht in Legacy-Screens. Neue Screens: ausschließlich Alpine.js und Vanilla `fetch()`. Interaktivität wird über `x-data`, `x-on`, `x-show` und `x-bind` realisiert.

---

## 2. Farbpalette

| Token | Wert | Verwendung |
|---|---|---|
| `--color-bg` | `#ffffff` | Seiten-Hintergrund |
| `--color-surface` | `#f7f7f5` | Subtile Sektionsflächen, alternating rows |
| `--color-text-primary` | `#1a1a1e` | Fliesstext, Headings, Labels |
| `--color-text-secondary` | `#6b6b7a` | Metadaten, Hints, Timestamps, Muted-Text |
| `--color-accent` | `#8c2030` | Primär-Buttons, aktive Nav-Links, Marken-Elemente |
| `--color-border` | `#e8e8ec` | Trennlinien, Card-Borders, Divider |
| `--color-navbar-bg` | `#ffffff` | Navbar-Hintergrund |
| `--color-navbar-border` | `#e8e8ec` | Navbar border-bottom |

### Semantische Zustände

Semantische Farben (Erfolg, Fehler, Warnung) sind von der Markenfarbe getrennt. Konkrete Werte werden bei der Implementierung aus dem PicoCSS-Custom-Property-System bezogen (`--pico-ins-color`, `--pico-del-color`), nicht als Hex-Konstanten hardcodiert.

---

## 3. Typografie

### Schriftarten

**Libre Baskerville** — ausschliesslich für H1 und H2, Logo-Text, Seiten-Titel.

```css
@import url("https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400&display=swap");
```

**system-ui** — für alles andere: H3, H4, Body, Labels, Buttons, Navigation.

```css
font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
```

### Hierarchie

| Ebene | Schrift | Weight | Transform | Letter-Spacing | Grösse |
|---|---|---|---|---|---|
| H1 (Seiten-Titel) | Libre Baskerville | 400 | uppercase | 0.45em | 2rem |
| H2 (Abschnitts-Titel) | Libre Baskerville | 400 | uppercase | 0.45em | 1.4rem |
| H3 (Card-Titel, Gruppen) | system-ui | 600 | keine | keine | 1.1rem |
| H4 (Sub-Gruppen) | system-ui | 600 | keine | keine | 1rem |
| Body | system-ui | 400 | keine | keine | 1rem |
| Meta / Muted | system-ui | 400 | keine | keine | 0.85rem |
| Button-Text | system-ui | 600 | keine | 0.02em | 0.9rem |
| Nav-Link | system-ui | 400 | keine | keine | 0.875rem |

H3 und H4 sind **nicht** uppercase. Die Serif-/Uppercase-Behandlung bleibt H1 und H2 vorbehalten.

### Farben im Text

- Primärer Text: `#1a1a1e`
- Muted / sekundärer Text: `#6b6b7a`
- Aktiver Link / aktiver Nav-State: `#8c2030`
- Regulärer Link (Inline-Text): `#1a1a1e`, underline on hover

---

## 4. Spacing-System

Basis: 8 px. Alle Abstände sind Vielfache dieser Basis.

| Token | Wert | Typische Verwendung |
|---|---|---|
| `xs` | 4 px | Intra-Element-Gaps (Icon zu Label) |
| `sm` | 8 px | Interne Padding, Gap zwischen Chips |
| `md` | 16 px | Standard-Padding, Abstand zwischen Elementen |
| `lg` | 32 px | Abstand zwischen Sektionen |
| `xl` | 64 px | Grosse vertikale Abstände, Hero-Bereiche |
| `2xl` | 96 px | Sehr grosser Whitespace, nur auf grossen Seiten |

In CSS: `rem`-Werte auf Basis 1rem = 16px. Konkret: `0.25rem` (4px), `0.5rem` (8px), `1rem` (16px), `2rem` (32px), `4rem` (64px), `6rem` (96px).

Padding in Cards und Panels: `1.5rem` innen.

---

## 5. Komponenten

### 5.1 Navbar (Haupt-Navigation)

Die Navbar ist immer hell.

**Struktur:**
- Hintergrund: `#ffffff`
- Border-bottom: `1px solid #e8e8ec`
- Position: `position: fixed; top: 0; width: 100%` (über Spielinhalt)

**Logo / Markenname "Nouron":**
- Schrift: Libre Baskerville
- Transform: uppercase
- Letter-Spacing: 0.45em
- Farbe: `#1a1a1e`
- Kein Icon, nur Text

**Nav-Links:**
- Schrift: system-ui, 0.875rem
- Farbe: `#4a4a58`
- Hover: `#1a1a1e`
- Aktiver Link: `#8c2030`, optional thin underline (kein Hintergrund-Highlight)

**Rechts in der Navbar (nur in-run Screens, nicht Lobby):**
- Nexus-Kredit Badge — zeigt aktuellen Schuldenstand
- Sol-Button — löst den nächsten Sol aus

**Dropdown (User-Menü):**
- Schrift: system-ui
- Hintergrund: `#ffffff`
- Border: `1px solid #e8e8ec`
- Items: reguläre Schriftgrösse, `#1a1a1e`

Die dunkle Bootstrap-Navbar (`navbar-dark bg-dark`) ist das Erbe des Legacy-Layouts und wird im Rahmen der Migration ersetzt. Neue Layouts verwenden ausschliesslich die helle Variante.

---

### 5.2 Resource Bar

Die Resource Bar erscheint unterhalb der Navbar als eigene horizontale Leiste. Sie ist **nicht** Teil der Navbar.

- Nicht auf der Lobby sichtbar
- Nicht auf Screens ohne aktiven Run sichtbar
- Enthält Resource Pills (siehe 5.6)
- Hintergrund: `#ffffff` oder `#f7f7f5` (subtil abgesetzt)
- Border-bottom: `1px solid #e8e8ec`

---

### 5.3 Subnav / Tabs

Modul-spezifische Tab-Navigation direkt unterhalb der Resource Bar.

- Hintergrund: `#ffffff`
- Border-bottom: `1px solid #e8e8ec`
- Tabs sind Textlinks (system-ui, 0.875rem)
- Aktiver Tab: `#8c2030`, Border-bottom-Highlight `2px solid #8c2030`
- Inaktive Tabs: `#6b6b7a`, Hover `#1a1a1e`
- Kein dunkler Subnav-Hintergrund

Gilt für: Colony, Berater, Handel, Nachrichten, Cantina.

---

### 5.4 Cards und Panels

Cards werden nur eingesetzt wenn eine klar abgegrenzte Entität dargestellt wird (z.B. ein Run in der Lobby, ein Berater, ein Gebäude).

**Grundregeln:**
- Hintergrund: `#ffffff`
- Border: `1px solid #e8e8ec` **oder** Box-Shadow `0 1px 4px rgba(0,0,0,0.06)`  — eines davon, nicht beides zusammen
- Border-Radius: `4px` (zurückhaltend)
- Padding innen: `1.5rem`
- Kein dunkler Card-Hintergrund auf hellem Screen
- Kein Gradient, keine dekorativen Hintergründe

**Card-Header:**
- Enthält Titel (H3) und optional ein Status-Badge
- Kein eigener Hintergrund innerhalb der Card
- Kein Separator-Border zwischen Header und Body (ausser wenn semantisch begründet)

**Card-Footer:**
- Enthält Aktionen (Buttons)
- Kein eigener Hintergrund
- Margin-top `1rem` zum Card-Body

**Card-Grid:**
- `display: grid`, `grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))`
- Gap: `1rem`

---

### 5.5 Buttons

Alle Buttons verwenden system-ui, font-weight 600, font-size 0.9rem, letter-spacing 0.02em, border-radius 4px.

**Primary:**
- Hintergrund: `#8c2030`
- Text: `#ffffff`
- Border: keine
- Hover: etwas dunkler (z.B. `#731a27`), kein Outline

**Secondary / Outline:**
- Hintergrund: transparent
- Border: `1px solid #1a1a1e`
- Text: `#1a1a1e`
- Hover: leichter Hintergrund `#f7f7f5`

**Ghost:**
- Kein Hintergrund, kein Border
- Text: `#1a1a1e`
- Hover: Underline

**Deaktiviert (disabled):**
- Opacity: 0.45
- cursor: not-allowed
- pointer-events: none

**Grösse:** Standard-Buttons ohne übertriebene Padding-Inflation. Keine XL-Hero-Buttons ausser in explizit dafür vorgesehenen leeren Zuständen.

**AP-Kosten-Chip (verbindlich):** Jeder Aktionsbutton mit **fixen AP-Kosten** zeigt die Kosten **vorab** als Chip rechts im Button — optisch identisch zu den AP-Chips der Resource Bar (`.ap-chip` + Variante). Bau-AP → `.ap-chip--build` (grün), Nav-AP → `.ap-chip--nav` (blau). Button-Layout: `display:flex; justify-content:space-between` — Label-Body links (ein- oder zweizeilig: `__main` + optional `__sub`), Chip rechts (`flex-shrink:0`). Partial: `@include('partials.ap-cost-chip', ['amount' => 1, 'type' => 'build'])` (oder `'label' => '1 AP/Feld'` für distanzabhängige Kosten).

Umgesetzt im Colony-Screen (Erkunden/Sondieren/Reparieren/Ausbauen/Bauen/Verlegen).

**Kein Chip** bei variabler/spielergewählter AP-Menge — dort macht das jeweilige Control die Kosten bereits sichtbar: Techtree investiert über eine Segment-Leiste (`x / y AP`, ein Klick = ein Segment), Hangar-Reparatur/-Dispatch über eine AP-Eingabe (Spieler wählt 1–10 / 0–20 AP). Cantina/Berater kosten Credits/Ressourcen, kein AP → kein Chip.

---

### 5.6 Resource Pills (res-chip)

Die Resource Pills sind ein bestehendes, gut funktionierendes Muster — sie werden unverändert behalten.

- Farbige Outline-Pills mit Abkürzung (Sol, Cr, Sup, TR, RG, ...)
- Jede Ressource hat eine eigene Akzentfarbe (CSS-Klassen per Ressource)
- Primäre Ressourcen immer sichtbar, sekundäre nur wenn Menge > 0
- Sol-Chip kommt zuerst, dann Trennlinie, dann Credits / Supply / Trust, dann Trennlinie, dann handelbare Ressourcen
- Chips sind `inline-flex`, font-size `0.85rem`, padding `xs sm`

---

### 5.7 Tabellen

- Volle Breite, `border-collapse: collapse`
- `font-size: 0.9rem`
- Zellen: padding `0.55rem 0.75rem`, `text-align: left`, `vertical-align: middle`
- Zeilen-Trennlinie: `1px solid #e8e8ec`
- Letzte Zeile ohne Border-bottom
- Thead: font-weight 600, font-size 0.8rem, uppercase, letter-spacing 0.04em, Farbe `#6b6b7a`
- Thead border-bottom: `2px solid #e8e8ec`
- Hover-Zeile: Hintergrund `#f7f7f5`
- Numerische Spalten: `text-align: right`, `font-variant-numeric: tabular-nums`

---

### 5.8 Status-Badges und Labels

**Status-Pills** (z.B. Run-Status: abgeschlossen / gescheitert):
- Inline-Block, padding `0.15em 0.6em`
- border-radius `0.3em`
- font-size `0.78rem`, font-weight 600, uppercase, letter-spacing 0.03em
- Farben: semantisch (`--pico-ins-color` für Erfolg, `--pico-del-color` für Fehler)

**Warn-Badges** (z.B. Bypass-Warnung):
- Wie Status-Pill, aber Hintergrund immer semantisch Rot
- Nur für tatsächliche Warnzustände, nicht für Kategorie-Labels

**AP-Chips / Typ-Chips:**
- Schlichter Stil: leichter Hintergrund `#f7f7f5`, Border `#e8e8ec`
- font-size `0.85rem`
- Kein kräftiger Farbhintergrund

---

## 6. Screen-Typen

### 6.1 Lobby

Der Lobby-Screen ist der Einstiegspunkt vor und nach einem Run.

**Layout-Regeln:**
- Kein Subnav, keine Resource Bar
- Maximale Breite: `56rem`, zentriert (`margin: 0 auto`)
- Padding: `2rem` oben, `3rem` unten

**Inhalt:**
- H1 (Libre Baskerville, uppercase) als Seiten-Titel
- Untertitel als Muted-Text direkt darunter
- Sektionen (Aktive Runs, Ausstehende Runs, Abgeschlossene Runs, Highscore) durch H2-Überschriften mit Border-bottom getrennt
- Run-Cards im Card-Grid
- Highscore-Tabelle am Ende

**Leerer Zustand:** Zentrierter Text in Muted-Farbe, padding `3rem 1rem`.

---

### 6.2 In-Run Screens (Standard)

Gilt für: Colony, Berater, Techtree, Handel, Nachrichten, Flotte, Galaxis, Systemkarte.

**Schichtung (von oben nach unten):**
1. Navbar (position: fixed; top: 0; width: 100%, hell)
2. Resource Bar (direkt darunter, horizontale Leiste)
3. Subnav / Tabs (wenn der Screen interne Sektionen hat)
4. Screen-Content (`container-fluid`, `margin-top: 1rem`)

**Content-Bereich:**
- Kein eigener Page-Title als H1 nötig wenn der Screen-Name bereits in der Navbar-Navigation aktiv ist
- H2 für Abschnitte innerhalb des Screens
- Whitespace zwischen Sektionen: `lg` (32px)

**Flash-Messages (Erfolg / Fehler):**
- Werden oberhalb des Contents als Alert-Banner eingeblendet
- Schliessbar, zeitlich begrenzt sichtbar (kein permanentes Element)

---

### 6.3 Carousel-Screens

Screens mit Carousel-Navigation (aktuell: Berater-Screen, Hangar-Screen) folgen einem eigenen Muster.

**Merkmale:**
- Card-basierte Darstellung: eine Entität pro Card (ein Berater, ein Hangar-Slot)
- Navigation: Swipe auf Mobile, Pfeil-Buttons auf Desktop
- Dots-Pager unterhalb der Cards als Positionsanzeige
- Kein horizontales Scrolling der gesamten Seite — nur der Carousel-Bereich scrollt

**Technischer Stack:**
- Alpine.js für State (aktiver Index, Swipe-Events)
- PicoCSS für Card-Grundlayout
- Carousel-Logik in `public/js/carousel.js`, Styles in `public/css/carousel.css`
- Kein jQuery, kein Bootstrap

**Card-States:** Jede Card zeigt genau einen Zustand an (leer / aktiv / inaktiv / abwesend). State-Übergänge werden durch Alpine.js `x-show` / `x-data` gesteuert, nicht durch DOM-Neuladen.

---

### 6.4 Cantina / Bar

Die Cantina hat einen eigenen visuellen Charakter: ein Bar-Hintergrundbild als atmosphärisches Element.

**Regeln:**
- Subnav und Resource Bar wie bei Standard In-Run Screens
- Hintergrundbild als dekoratives Layer, nicht als Content-Hintergrund
- Overlay oder Surface-Panel (`#ffffff` oder `rgba(255,255,255,0.92)`) damit Text lesbar bleibt
- NPC-Charaktere als Platzhalter-Grafiken, klickbar wenn handelbar (Händler-Interaktion)
- Händler-Auswahl führt zu einem Angebotsbereich (Implementierung ausstehend)
- Kein dunkles Theme für die Cantina — die Atmosphäre kommt durch das Bild, nicht durch dunkle Flächen

---

## 7. Grafik-Assets

Verbindlich gemäss ADR 0001.

**Stil:** Illustriert/gezeichnet. Kein Pixel-Art. Keine fotorealistischen Renderings.

**Format:** WebP mit transparentem Hintergrund (Alpha-Kanal). Kein PNG, kein SVG für Illustrationen.

**Auflösung:** Immer 2× Zielgrösse (HiDPI-ready).

| Asset-Typ | Zielgrösse | Datei-Liefergrösse |
|---|---|---|
| Ressourcen-Icons | 24×24 px | 48×48 px |
| Gebäude-Icons (Sidebar) | 32×32 px | 64×64 px |
| Gebäude/Schiff (Tile) | 48×48 px | 96×96 px |
| Berater-Portraits | 128×128 px | 256×256 px |

**CSS-Integration:**
- Container-Grössen in `em` oder `rem`, nie fixe `px`
- Bilder: `width: 100%; height: 100%; object-fit: contain;`

**SVG:** Nur für UI-Struktur (Hex-Grid, strukturelle Icons, Platzhalter-Silhouetten). Nicht für illustrierte Spielgrafiken.

**Ablage:**
```
public/img/icons/       -- Ressourcen-Icons, UI-Icons
public/img/buildings/   -- Gebäude-Icons
public/img/ships/       -- Schiffs-Icons
public/img/advisors/    -- Berater-Portraits
public/img/tiles/       -- Hex-Tile-Texturen
```

---

## 8. Responsive Breakpoints

Verbindliches Set — keine weiteren Schwellwerte einführen (Stand: Konsolidierung 2026-06-12):

| Stufe | CSS-Query | Geräteklasse |
|---|---|---|
| Mobile | `@media (max-width: 599px)` | Smartphones (Hochformat) |
| Tablet schmal | 600–767px (Basis bzw. `max-width: 767px`) | kleine Tablets, Phones quer |
| Desktop | `@media (min-width: 768px)` | Tablets quer, Laptops |
| Wide | `@media (min-width: 900px)` | volle Desktop-Breite |

**Regeln:**

- **Mobile-first:** Neue Styles mobil als Basis schreiben, Desktop additiv über `min-width`. Bestehende `max-width`-Blöcke werden bei Migrationen schrittweise umgedreht.
- **Komplemente:** `max-width` immer 599 / 767 / 899 — nie 600 / 768 / 900, sonst matchen `min`- und `max`-Query bei exakter Breite gleichzeitig.
- **Viewport-Höhen:** `dvh` statt `vh` (mobile Adressleiste); `vh`-Zeile als Fallback davor stehen lassen.
- **JS spiegelt CSS:** `innerWidth < 600` (Techtree `isMobile`), `< 768` (Carousel Full-width-Card), `< 900` (Carousel Arrow-Modus, `carousel.js` BREAKPOINT).
- **Navigation (Colony-Layout):** Burger-Menü < 600, Icon-only-Leiste 600–1099, Icons + Labels ≥ 1100. Nav-Items niemals intern umbrechen (`white-space: nowrap`); kein horizontales Scrollen, kein Mehrzeilen-Umbruch — bei Platzmangel fallen die Labels weg.
- **Touch-Targets:** Interaktive Elemente min. 24×24 px Hitbox (Dots: sichtbarer Punkt via `::before`, Hitbox über Elementgröße).

---

## 9. Do / Don't Kurzreferenz

| Do | Don't |
|---|---|
| Weisse/helle Navbar | Dunkle Navbar auf neuen Screens |
| Libre Baskerville nur für H1/H2 und Logo | Libre Baskerville für Buttons, Labels, Body |
| Akzentrot für aktive States und Primary-Buttons | Akzentrot für Warnungen oder Fehler |
| Whitespace aktiv einsetzen | Jeden Pixel mit Inhalt füllen |
| Cards nur für abgegrenzte Entitäten | Cards als generelles Layout-Werkzeug |
| Border oder Shadow — eines davon | Border und Shadow kombinieren |
| `data-theme="light"` bei PicoCSS setzen | Dark-Mode von Systemeinstellung erben lassen |
| Alpine.js + Vanilla fetch() für alle Interaktionen | jQuery verwenden (vollständig entfernt, Mai 2026) |
| Container in `em`/`rem` | Fixe `px`-Grössen für Icons und Portraits |
| WebP 2× für alle Illustrationen | PNG, SVG für illustrierte Spielgrafiken |
| system-ui für alle funktionalen Texte | Mixed Fonts innerhalb einer Ebene |
