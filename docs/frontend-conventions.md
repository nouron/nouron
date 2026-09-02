# Nouron — Frontend Engineering Conventions

**Verbindlich für:** Alle Screens mit AJAX-Interaktionen. Ergänzt `docs/design-system/` (Optik/Tokens/Komponenten) um technische Verträge, die kein Design-System-Export abdeckt — Backend-Response-Contracts, Screen-Kompositionsregeln, Breakpoint-Implementierungsdetails.

Migriert aus `docs/design-guide.md` (2026-08-01, abgelöst) — nur die Abschnitte ohne visuelles Gegenstück im Design System.

---

## 1. Live-Sync nach AJAX-Aktionen (verbindlich)

Die Resource Bar ist reines server-gerendertes Blade ohne eigenen reaktiven State — sie aktualisiert sich nicht von selbst. **Jeder Screen mit AJAX-Aktionen, die AP oder Ressourcen verändern, muss beides selbst erledigen:**

1. **Backend:** Die JSON-Antwort liefert die aktualisierten Werte mit — exakt diese Feldnamen (Referenz: `ColonyController::currentAp()`): `apNav`, `apConstruction`, `apResearch`, `apEconomy`, `apStrategy`, `regolith`, `werkstoffe`, `organika`, `credits`, `freeSupply`. Gleiche Feldnamen spielweit, damit ein künftiger gemeinsamer Store (siehe unten) beide Seiten ohne Umbenennung zusammenführen kann.
2. **Frontend:** Die zurückgegebenen Werte werden per DOM-Patch in die Resource-Bar-Chips geschrieben und bei Abnahme kurz aufgeblitzt. Kanonisches Referenzmuster: `public/js/colony-hexgrid.js` (`updateAp()`, `syncResbarAp()`/`syncResbarAmount()`, `flashApChip()`/`flashResChip()`) — Chips werden über ihre `#resbar-ap-*`-ID bzw. `.res-{{Abbr}}`-Klasse angesprochen (z.B. `.res-Or` für Organika).

Es gibt aktuell **keinen** globalen Store — das ist ein bewusstes Copy-Paste-Muster pro Screen (Zukunftsschritt „gemeinsamer Alpine-Store" ist im Code vermerkt, aber nicht Teil dieser Konvention). Ein neuer Screen mit AJAX-Aktionen **ohne** diesen Sync gilt als unvollständig — Symptom: Ressourcenleiste hinkt hinterher, korrigiert sich erst beim nächsten Reload.

**Abkürzungen sind fix und werden nie übersetzt:** `Cr`, `Rg`, `Co`, `Or`, `Sup`, `Nav`/`Con`/`Res`/`Eco`/`Str` + `AP` — jeder Kosten-/Ertrags-Chip verwendet dieselbe Abkürzung wie die Resource Bar, nie den ausgeschriebenen Ressourcennamen (auch nicht via `__()`/Lang-Key).

**„Sol" ohne Kontext ist reserviert** für den absoluten Tageszähler-Chip der Resource Bar. Relative Zeitspannen (Missionsdauer, Cooldowns, Lieferzeiten o.ä.) brauchen ein Präfix (z.B. „Dauer: X Sole"), nie bloß „X Sole" — sonst wirkt es wie derselbe Zähler.

---

## 2. Fehlerantworten aus AJAX-Aktionen (verbindlich)

Eine Aktion, die die Spielregeln verbieten, antwortet mit **HTTP 422** und diesem Body:

```json
{ "ok": false, "error": "resource_limit", "message": "Nicht genug Regolith." }
```

- **`error` ist immer ein stabiler Maschinencode** (snake_case), nie Anzeigetext. Er ist der Schlüssel, auf den Code verzweigen und zählen darf.
- **`message` ist immer der Spielertext** (via `__()`), nie ein Code. Nur er wird angezeigt.
- Zusatzkontext kommt als eigene Felder daneben (`ap_type`, `current`, `cost`).

Der umgekehrte Weg — den übersetzten Satz in `error` zu legen — macht das Feld als Schlüssel unbrauchbar: jede Übersetzungsänderung bricht dann still alles, was darauf verzweigt oder aggregiert. Referenz: `ColonyController::fail()`.

**Die JS-Helper ignorieren den HTTP-Status bewusst** (`public/js/colony-hexgrid.js`, `command_center.js`): sie machen `.then(r => r.json())` und die Call-Sites verzweigen auf das JSON-Feld `res.ok`, nie auf `response.ok`. Das ist load-bearing — ein globaler „non-2xx = throw"-Interceptor würde allen Call-Sites gleichzeitig ihre spezifische Meldung nehmen. Anzeige immer als `res.message ?? res.error`.

---

## 3. Screen-Typen (Kompositionsregeln)

### 3.1 Lobby

Einstiegspunkt vor und nach einem Run.

- Kein Subnav, keine Resource Bar
- Maximale Breite: `56rem`, zentriert (`margin: 0 auto`)
- Padding: `2rem` oben, `3rem` unten
- H1 (Libre Baskerville, uppercase) als Seiten-Titel, Untertitel als Muted-Text direkt darunter
- Sektionen (Aktive Runs, Ausstehende Runs, Abgeschlossene Runs, Highscore) durch H2-Überschriften mit Border-bottom getrennt
- Run-Cards im Card-Grid, Highscore-Tabelle am Ende
- Leerer Zustand: zentrierter Text in Muted-Farbe, padding `3rem 1rem`

### 3.2 In-Run Screens (Standard)

Gilt für: Colony, Berater, Techtree, Handel, Nachrichten, Flotte, Galaxis, Systemkarte.

**Schichtung (von oben nach unten):**
1. Navbar (`position: fixed; top: 0; width: 100%`, hell)
2. Resource Bar (direkt darunter, horizontale Leiste)
3. Subnav / Tabs (wenn der Screen interne Sektionen hat)
4. Screen-Content (`container-fluid`, `margin-top: 1rem`)

- Kein eigener Page-Title als H1 nötig, wenn der Screen-Name bereits in der Navbar-Navigation aktiv ist
- H2 für Abschnitte innerhalb des Screens, Whitespace zwischen Sektionen: `lg` (32px)
- Flash-Messages (Erfolg/Fehler): oberhalb des Contents als Alert-Banner, schliessbar, zeitlich begrenzt sichtbar (kein permanentes Element)

### 3.3 Carousel-Screens

Screens mit Carousel-Navigation (aktuell: Berater-Screen, Hangar-Screen).

- Card-basierte Darstellung: eine Entität pro Card (ein Berater, ein Hangar-Slot)
- Navigation: Swipe auf Mobile, Pfeil-Buttons auf Desktop, Dots-Pager unterhalb der Cards als Positionsanzeige
- Kein horizontales Scrolling der gesamten Seite — nur der Carousel-Bereich scrollt
- Stack: Alpine.js für State (aktiver Index, Swipe-Events), PicoCSS für Card-Grundlayout, Carousel-Logik in `public/js/carousel.js` + `public/css/carousel.css`. Kein jQuery, kein Bootstrap.
- Card-States: jede Card zeigt genau einen Zustand an (leer/aktiv/inaktiv/abwesend). State-Übergänge über Alpine.js `x-show`/`x-data`, nicht durch DOM-Neuladen.

### 3.4 Cantina / Bar

- Subnav und Resource Bar wie bei Standard In-Run Screens
- Hintergrundbild als dekoratives Layer, nicht als Content-Hintergrund
- Overlay/Surface-Panel (`#ffffff` oder `rgba(255,255,255,0.92)`) damit Text lesbar bleibt
- NPC-Charaktere als klickbare Portraits (Hotspots) wenn handelbar
- Kein dunkles Theme für die Cantina — die Atmosphäre kommt durch das Bild, nicht durch dunkle Flächen

---

## 4. Responsive Breakpoints — Implementierungsdetails

Verbindliches Set — keine weiteren Schwellwerte einführen:

| Stufe | CSS-Query | Geräteklasse |
|---|---|---|
| Mobile | `@media (max-width: 599px)` | Smartphones (Hochformat) |
| Tablet schmal | 600–767px (Basis bzw. `max-width: 767px`) | kleine Tablets, Phones quer |
| Desktop | `@media (min-width: 768px)` | Tablets quer, Laptops |
| Wide | `@media (min-width: 900px)` | volle Desktop-Breite |

- **Mobile-first:** Neue Styles mobil als Basis schreiben, Desktop additiv über `min-width`. Bestehende `max-width`-Blöcke werden bei Migrationen schrittweise umgedreht.
- **Komplemente:** `max-width` immer 599 / 767 / 899 — nie 600 / 768 / 900, sonst matchen `min`- und `max`-Query bei exakter Breite gleichzeitig.
- **Viewport-Höhen:** `dvh` statt `vh` (mobile Adressleiste); `vh`-Zeile als Fallback davor stehen lassen.
- **JS spiegelt CSS:** `innerWidth < 600` (Techtree `isMobile`), `< 768` (Carousel Full-width-Card), `< 900` (Carousel Arrow-Modus, `carousel.js` BREAKPOINT).
- **Navigation (Colony-Layout):** Burger-Menü < 600, Icon-only-Leiste 600–1099, Icons + Labels ≥ 1100. Nav-Items niemals intern umbrechen (`white-space: nowrap`); kein horizontales Scrollen, kein Mehrzeilen-Umbruch — bei Platzmangel fallen die Labels weg.
- **Touch-Targets:** Interaktive Elemente min. 24×24 px Hitbox (Dots: sichtbarer Punkt via `::before`, Hitbox über Elementgröße).

---

## 5. Technische Do / Don't (nicht-visuell)

| Do | Don't |
|---|---|
| Alpine.js + Vanilla `fetch()` für alle Interaktionen | jQuery verwenden (vollständig entfernt, Mai 2026) |
| Container in `em`/`rem` | Fixe `px`-Grössen für Icons und Portraits |
| Neue Panel-/Card-Klassen mit dem Wrapper-Container prefixen (z.B. `.tech-panel .detail-row`) | Bare Klassen wie `.detail-row` ohne Scope — PicoCSS hat globale `aside li`/`aside ul`-Regeln, die bei gleicher Spezifität gewinnen und neue Styles unsichtbar machen (Bug in PR #305/#306, Techtree-Sidebar) |
| Sekundäre Detail-Zeilen (Werte neben Meta-Labels, Listeneinträge) klein + `font-weight: 500` halten, angelehnt an die Description-Textgröße (~0.8rem) | Body-Werte in Sidebars mit `font-weight: 600–700` + `~0.95rem` — wirkt in schmalen Panels zu groß/prägnant, nicht "modern/elegant/minimal" |
