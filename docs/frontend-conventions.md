# Nouron — Frontend Engineering Conventions

**Verbindlich für:** Alle Seiten des SvelteKit-Frontends (`frontend/`) und die JSON-API dahinter (`/api/v1`, ADR 0006). Ergänzt `docs/design-system/` (Optik/Tokens/Komponenten) um technische Verträge, die kein Design-System-Export abdeckt — Backend-Response-Contracts, Screen-Kompositionsregeln, Breakpoint-Implementierungsdetails.

Migriert aus `docs/design-guide.md` (2026-08-01, abgelöst) — nur die Abschnitte ohne visuelles Gegenstück im Design System.

---

## 1. Live-Sync nach Aktionen (verbindlich)

Kopfzeile und Ressourcenleiste lesen den Store `shell` (`frontend/src/lib/stores/shell.ts`, Quelle `GET /api/v1/shell`). Sie aktualisieren sich nicht von selbst. **Jede Seite mit Aktionen, die AP oder Ressourcen verändern, ruft nach einer erfolgreichen Aktion `refreshShell()` auf** (oder `setAp()` mit dem AP-Wert aus der Antwort, wenn nur AP betroffen sind; `setHint()` für einen mitgelieferten Hinweis).

Eine Seite **ohne** diesen Sync gilt als unvollständig — Symptom: Ressourcenleiste hinkt hinterher und korrigiert sich erst beim nächsten Laden.

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

**Im Frontend:** Aktionen rufen `api(path, { method, body, allowError: true })` (`#lib/stores/auth.ts`) und verzweigen auf das JSON-Feld `res.ok`, nie auf den HTTP-Status — so behält jede Aufrufstelle ihre spezifische Meldung. Ohne `allowError` wirft `api()` bei Nicht-2xx einen `ApiError` (für Datenabrufe). 401 meldet ab. Anzeige immer als `res.message ?? res.error`.

**Ohne gestarteten Run** antworten die Spiel-Endpunkte mit 409 `{ "ok": false, "error": "run_not_started" }`; das Frontend schickt dann in die Lobby.

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

Gilt für: Kolonie, Kommandozentrale, Berater, Techtree, Hangar, Cantina, Protokoll, Nexus-DB (Routengruppe `(game)`).

**Schichtung (von oben nach unten):**
1. Navbar (`position: fixed; top: 0; width: 100%`, hell)
2. Resource Bar (direkt darunter, horizontale Leiste)
3. Subnav / Tabs (wenn der Screen interne Sektionen hat)
4. Seiteninhalt

- Kein eigener Page-Title als H1 nötig, wenn der Screen-Name bereits in der Navbar-Navigation aktiv ist
- H2 für Abschnitte innerhalb des Screens, Whitespace zwischen Sektionen: `lg` (32px)
- Meldungen (Erfolg/Fehler) nach Aktionen: als Banner/Toast oberhalb des Inhalts, zeitlich begrenzt (kein permanentes Element)

### 3.3 Carousel-Screens

Screens mit Carousel-Navigation (aktuell: Berater-Screen, Hangar-Screen).

- Card-basierte Darstellung: eine Entität pro Card (ein Berater, eine Hangarbucht)
- Navigation: Swipe auf Mobile, Pfeil-Buttons auf Desktop, Dots-Pager unterhalb der Cards als Positionsanzeige
- Kein horizontales Scrolling der gesamten Seite — nur der Carousel-Bereich scrollt
- Umsetzung: Klasse `Carousel` in `frontend/src/lib/carousel.svelte.ts` (aktiver Index, Swipe, Pfeile, Dots) mit `lib/styles/carousel.css`.
- Card-States: jede Card zeigt genau einen Zustand an (leer/aktiv/inaktiv/abwesend). Übergänge reaktiv im Svelte-State, nicht durch Neuladen.

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
- **JS spiegelt CSS:** `innerWidth < 600` (Techtree `isMobile`), `< 768` (Carousel Full-width-Card), `< 900` (Carousel Arrow-Modus, `carousel.svelte.ts`).
- **Navigation (unten, `BottomNav.svelte`; Kopfzeile `AppHeader.svelte` nur Logo, SolButton, Nutzermenü):** Kachelleiste am unteren Rand, auf Desktop und Mobil gleich, keine Breakpoint-Staffel. Kacheln ≥ 44 px, Leiste ~64 px + `env(safe-area-inset-bottom)`; Labels niemals umbrechen (`white-space: nowrap`). Gruppen: Kolonie ▲ (Kommandozentrale, Karte, Berater), Labor, Cantina, Hangar, Datenarchiv ▲ (Log, Nexus-DB, Codex); ▲-Gruppen klappen eine Kachelreihe direkt über der Leiste auf (Esc schließt und gibt den Fokus zurück, Klick außerhalb schließt, Fokus auf erste Kachel). Aktive Seite `aria-current="page"`. Gesperrte Ziele: grau, `aria-disabled`, Voraussetzung im `title` und per `aria-describedby` für Screenreader. Die Admin-Debug-Leiste sitzt unter der Navigation (einklappbar, Zustand in localStorage).
- **Layout-Höhen:** `(game)/+layout.svelte` misst `.app-header` und `.bottom-chrome` (Navigation + Debug-Leiste) und setzt `--app-header-h` / `--app-bottom-h` auf `:root`. Seiten-Offsets (`--page-offset-*`, `app.css`) sind davon abgeleitet: `calc(100dvh - var(--app-header-h) - var(--app-bottom-h) - <Seitenpadding>)`; `<main>` hat `padding-bottom: var(--app-bottom-h)`. Keine festen Pixel-Annahmen über die Chrome-Höhe.
- **Touch-Targets:** Interaktive Elemente min. 24×24 px Hitbox (Dots: sichtbarer Punkt via `::before`, Hitbox über Elementgröße).

---

## 5. Technische Do / Don't (nicht-visuell)

| Do | Don't |
|---|---|
| Svelte-State + `api()` aus `#lib/stores/auth.ts` für alle Interaktionen | Eigene `fetch()`-Aufrufe am Token vorbei; jQuery/Alpine (entfernt) |
| Container in `em`/`rem` | Fixe `px`-Grössen für Icons und Portraits |
| Seitenspezifisches CSS im `<style>`-Block der Komponente (automatisch gescoped); geteilte Stylesheets in `lib/styles/` mit Wrapper-Präfix | Globale bare Klassen in geteilten Stylesheets — kollidieren seitenübergreifend |
| Sekundäre Detail-Zeilen (Werte neben Meta-Labels, Listeneinträge) klein + `font-weight: 500` halten, angelehnt an die Description-Textgröße (~0.8rem) | Body-Werte in Sidebars mit `font-weight: 600–700` + `~0.95rem` — wirkt in schmalen Panels zu groß/prägnant, nicht "modern/elegant/minimal" |

### Upgrade-Button mit segmentiertem AP-Balken (geteilte Komponente)

Kolonie „Ausbauen“, Techtree-Kenntnis-Button und Hangar-Training nutzen **eine** Komponente: `frontend/src/lib/components/ApUpgradeButton.svelte`, Logik `lib/js/ap-bar.js`, Styles `lib/styles/ap-upgrade-button.css`. Keine eigenen Balken-Klassen/-Logik in Seiten.

- Zeilen-Vertrag: `ap_spend`, `ap_for_levelup` Pflicht; `ap_cap: null | { cap, spent, remaining }` optional. `null` = schlichter Balken. Gesetzt = vier Zustände (früher gefüllt / heute gefüllt `--today` / heute frei `--free` / heute gesperrt `--locked`), Tageslimit-Marker `.btn-seg-limit`, Tooltip `colony.ap_cap_tooltip`; `remaining <= 0` sperrt den Button (`aria-disabled`).
- Neue Seiten mit Upgrade-Buttons nutzen diese Komponente; ein neues Backend-Feld `ap_cap` schaltet die Zustände automatisch frei.
