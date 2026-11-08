# UI-Umbau — ENTWURF (kein Beschluss)

> **Owner-Entscheidungen 2026-11-08 (gelten vor dem Rest dieses Entwurfs):**
> 1. **Navigation 1B** (Gruppen mit aufklappender Subnav): *Kolonie* (Kommandozentrale, Karte, Berater), *Labor*, *Cantina*, *Hangar*, *Datenarchiv* (Log, Nexus-DB, Codex). Offene Idee: Nexus-DB und Codex zusammenführen — zu klären (Inhalte, Navigation).
> 2. Schlanke Kopfzeile oben: Logo, SolButton, Nutzermenü. SolButton **nicht** unten.
> 3. Gesperrte Ziele (Labor-Vorschau ausgenommen): grau, Voraussetzung im Tooltip.
> 4. **Ressourcenleiste links, einheitlich auf Desktop und Mobil**, transparenter Hintergrund; darf die Kolonieansicht überlagern. Auf den anderen Screens eingeklappt (überlagert nichts), auf Wunsch vom linken Rand ausklappbar. Der `ui-specialist` entwirft das Verhalten.
> 5. Halbtransparent (mit Blur) zum Ausprobieren.
> 6. Hint-Leiste bleibt oben.
> 7. Mobil-Sidebar: Vollbild (3A).
> 8. Debug-Leiste: ganz unten unter allem oder ganz oben über allem, und einklappbar.
> 9. Feedback-Button ins Nutzermenü.
> 10. PR 1 (CSS-Variablen für Layout-Höhen) sofort; Rest in kleinen PRs danach.


Stand: 2026-11-08. **Alles in diesem Dokument ist ein Vorschlag.** Nichts davon ist vom Owner entschieden oder im Code umgesetzt. Grundlage sind die Owner-Ideen vom 2026-11-05:

1. Navigation am unteren Bildschirmrand als Kacheln (Daumenbedienung), Desktop und Mobil gleich, ggf. klappt eine Subnav darüber auf.
2. Ressourcenleiste auf die linke Seite, transparenter Hintergrund.
3. Sidebar auf Mobil als Vollbild-Popup.

Verbindliche Vorgaben, die jede Variante respektieren muss: `docs/design-system/readme.md` (feste Breakpoints, keine neuen), `docs/frontend-conventions.md` (Live-Sync der Ressourcenleiste, Nav-Items nie umbrechen, Touch-Ziele).

---

## 0. IST-Zustand (gemeinsame Basis)

**Rahmen:** `frontend/src/routes/(game)/+layout.svelte` rendert `AppHeader` (nur wenn `$shell` da ist), dann `<main>` (Fade bei Seitenwechsel, neu gerendert bei Pfad-, Query- oder Sol-Änderung), danach `FeedbackButton` und für Admins `DebugBar`.

**`AppHeader.svelte`** ist ein `position: sticky; top: 0; z-index: 100` Block mit drei Zeilen:
- `nav` (60 px): Logo, `ul.nav-desktop` (8 Einträge: Kolonie, Kommandozentrale, Berater, Techtree, Cantina, Hangar, Log mit Badge, Nexus-DB; gesperrte Einträge ausgegraut mit Tooltip), rechts SolButton, Nutzermenü (Einstellungen, Codex, Läufe, Admin, Logout) bzw. Burger.
- `.resbar` (~40 px): `ResourceBar` — zentrierte, umbrechende Chip-Reihe (Sol, CR, KOL, Vertrauen, Rg/Or/Co, AP) mit Hover-/Klick-Popups (`.res-popup`, per `clamp()` im Viewport gehalten).
- `HintBar`: Encounter-Hinweise plus Hint-Stack (Hinweis mit Link und Schließen-Button).

**Breakpoints (Nav):** `<600` Burger-Dropdown (SolButton nur auf `/colony` sichtbar), `600–1099` Icon-only, `≥1100` Icons + Labels. Seiten-Breakpoints: 768/900 (Stapeln von Sidebar), Colony 899/1400.

**Höhenannahmen im Seiten-CSS (Abhängigkeit vom Header!):**
- `colony-view.css`: `min-height: calc(100dvh - 84px)`; `hangar.css`/`advisors.css`/`cantina.css`: `calc(100dvh - 100/105/200/230px)` — alles feste Pixel-Annahmen über die Header-Höhe.
- Techtree: `fit()` in `(game)/techtree/+page.svelte` misst `stage.getBoundingClientRect().top` und setzt `stage.style.height = innerHeight - top - Legende - 2` (min. 320 px). Das ist dynamisch und übersteht einen anderen Header, kennt aber keinen unteren Balken.
- Colony: `.hex-layout` = Grid `1fr 320px` (Karte + `.tile-panel`-Sidebar); `<900` einspaltig, Sidebar unterhalb der Karte mit `max-height: 360px`.

**Fixe Overlays:** `DebugBar` (`position: fixed; bottom: 0; z-index: 9999`, Vollbreite), `FeedbackButton` (fixiert unten rechts; bei DebugBar wird er per CSS auf `bottom: 2.5rem` angehoben).

**Touch-Ziele heute:** Nav-Links 40 px Zeilenhöhe, im Icon-Modus 2,5 rem breit; Design-System verlangt nur 24×24 px.

---

## Idee 1 — Navigation unten als Kacheln

### Varianten

**Variante 1A — Feste Kachelleiste, alle Ziele direkt (8 Kacheln), Rest im „Mehr“-Sheet.**
Leiste von ~64 px (Icon + kurzes Label, `env(safe-area-inset-bottom)` beachten). Auf Mobil 5 Kacheln (Kolonie, Kommandozentrale, Berater, Log, „Mehr“); „Mehr“ öffnet ein Sheet mit Techtree, Cantina, Hangar, Nexus-DB, Einstellungen, Codex, Läufe, Logout. Desktop zeigt alle 8 plus Nutzer-Kachel.

```
Desktop 1400 px
+--------------------------------------------------------------+
| NOURON                                    [Sol 12][Mario ▾]  |  <- schlanke Kopfzeile
+--------------------------------------------------------------+
|                                                              |
|                       Inhalt                                 |
|                                                              |
+--------------------------------------------------------------+
| [Kol][KZ][Ber][Tech][Cant][Hang][Log●][DB]                   |  <- Kachelleiste 64px, zentriert
+--------------------------------------------------------------+

Mobil 390 px
+------------------------------+
| NOURON            [Sol 12]   |
+------------------------------+
|           Inhalt             |
|                              |
+------------------------------+
| [Kol] [KZ] [Ber] [Log●] [⋯]  |  <- 5 Kacheln à 78 px
+------------------------------+
```

**Variante 1B — Zweistufig: Hauptbereiche unten, Subnav klappt darüber auf.**
Unten nur 4 Gruppen: **Kolonie** (Karte, Kommandozentrale), **Leute** (Berater, Cantina), **Forschung & Flotte** (Techtree, Hangar), **Archiv** (Log, Nexus-DB). Tipp auf eine Gruppe klappt eine Reihe Kacheln direkt über der Leiste auf (Subnav, nutzt das bestehende `SubnavTabs`-Muster aus dem Design-System). Nutzermenü bleibt in der Kopfzeile.

```
Desktop 1400 px                       Mobil 390 px
+--------------------------+          +------------------------+
|  Inhalt                  |          |  Inhalt                |
|                          |          |                        |
|  [Berater][Cantina]      |<-Subnav  |  [Berater] [Cantina]   |
+--------------------------+          +------------------------+
| [Kolonie][Leute▲][Forsch][Archiv]|  | [Kol] [Leute▲] [F] [A] |
+--------------------------+          +------------------------+
```

### Abwägung
- 1A: ein Tipp zum Ziel, flach, alle Ziele sichtbar (Desktop). Mobil ist „Mehr“ ein Versteck für Techtree/Cantina/Hangar — gerade die Spielkern-Screens. Die Gruppierung fürs Mobil-Set wäre eine Owner-Entscheidung.
- 1B: skaliert, wenn weitere Screens kommen; Mobil gut bedienbar. Zwei Tipps pro Wechsel, Gruppierung muss gelernt werden, aktive Seite ist verdeckt (Gruppe markiert, Seite nicht). Kombination mit gesperrten Zielen (Techtree/Cantina/Hangar vor Bau) braucht Regeln: Kachel grau mit Hinweis statt verschwinden.

### Auswirkungen
- **Hint-Leiste:** liegt heute im sticky Header. Vorschlag: bleibt oben direkt unter der Kopfzeile (Hinweise sind Kontext, keine Navigation). Alternative: oberhalb der Kachelleiste andocken — dann konkurriert sie mit der Subnav (1B). Empfehlung: oben lassen.
- **Debug-Leiste:** sitzt fix am unteren Rand und würde mit der Kachelleiste kollidieren. Vorschlag: DebugBar rückt per CSS-Variable `--bottom-nav-h` über die Kachelleiste (oder wandert in die Kopfzeile / wird einklappbar). Nur Admins betroffen.
- **Feedback-Button:** `bottom` ebenfalls um `--bottom-nav-h` (+ DebugBar-Höhe) anheben, oder als Eintrag ins Nutzermenü/„Mehr“ verschieben. Vorschlag: einmal zentrale CSS-Variablen `--bottom-nav-h` und `--debug-bar-h` einführen.
- **Techtree-Stage-Höhe:** `fit()` rechnet `innerHeight - top - Legende`. Der untere Balken muss abgezogen werden: `- bottomNavHeight` (aus `getComputedStyle` der CSS-Variable lesen oder per `getBoundingClientRect` der Leiste). Ohne Anpassung würde die untere Stage-Kante hinter der Leiste liegen. Auf Mobil zusätzlich dynamische Viewport-Höhe (`visualViewport`/`dvh`).
- **Colony-Karte:** `min-height: calc(100dvh - 84px)` gilt dann nicht mehr; alle Seiten-CSS mit festen `calc(100dvh - Npx)` (colony-view, hangar, advisors, cantina — ca. 10 Stellen) müssen auf Variablen umgestellt werden (`--header-h`, `--bottom-nav-h`). Der Seitenscroll braucht `padding-bottom: var(--bottom-nav-h)` am `<main>`, sonst wird der letzte Inhalt verdeckt.
- **Header-Reste:** `.nav-desktop`, `.nav-mobile`, Burger und Breakpoint-Staffel (`<600 / <1100`) entfallen; die Breakpoint-Regel in `frontend-conventions.md` §4 („Burger <600, Icon-only 600–1099, Labels ≥1100“) müsste neu geschrieben werden.

### Barrierefreiheit
- Kacheln mindestens 44×44 px Touch-Ziel (Leiste 64 px hoch, Kachelbreite ≥ 48 px; Mobil bei 5 Kacheln ~78 px). Ein Abstand ≥ 8 px zwischen Kacheln.
- Landmark `<nav aria-label=…>`, aktive Kachel `aria-current="page"`, gesperrte Kachel `aria-disabled="true"` mit erreichbarem Hinweistext (nicht nur `title`).
- Fokus-Reihenfolge: Die Leiste steht im DOM nach `<main>`, damit Screenreader/Tab zuerst den Inhalt erreichen; ein „Zur Navigation springen“-Link ist sinnvoll. Subnav (1B) steht im DOM direkt vor der Leiste und bekommt beim Öffnen den Fokus, Esc schließt und gibt den Fokus an die Gruppenkachel zurück.
- Badge (Log) mit Textalternative („3 ungelesen“), nicht nur Farbpunkt.
- `env(safe-area-inset-bottom)` für Geräte mit Gestenleiste.
- Wichtig: Text- und Icon-Kontrast der aktiven Kachel (Nouron-Rot nur sparsam, Design-System).

### Aufwand
**Mittel (1A) bis Groß (1B).** Neue Komponente `BottomNav.svelte`, Variablen, ~10 CSS-Stellen, Techtree `fit()`, DebugBar/Feedback-Anpassung, Doku-Update.

---

## Idee 2 — Ressourcenleiste links, transparent

### Varianten

**Variante 2A — Schmale vertikale Leiste links (Overlay/Rail), nur Desktop ≥ 900; Mobil bleibt horizontal oben.**
Chips untereinander in einer Spalte (~92 px), `position: fixed; left: 0`, Hintergrund transparent bzw. `rgba(255,255,255,0.6)` mit `backdrop-filter: blur`. Popups öffnen nach rechts statt nach unten. Der Inhalt wird nicht verschoben, sondern liegt darunter (Overlay) — oder bekommt `padding-left`.

```
Desktop 1400 px
+--------------------------------------------------------------+
| NOURON                                       [Sol 12][Mario] |
+--+-----------------------------------------------------------+
|Sol|                                                          |
|CR |                 Inhalt / Karte                           |
|KOL|                                                          |
|Vtr|                                                          |
|Rg |                                                          |
|Or |                                                          |
|Co |                                                          |
|AP |                                                          |
+--+-----------------------------------------------------------+
| [Kolonie][KZ][Berater]...                                    |
+--------------------------------------------------------------+

Mobil 390 px: wie heute, Chip-Reihe oben, transparent, 1–2 Zeilen
+------------------------------+
| NOURON            [Sol 12]   |
| CR 120 KOL 4/6 Rg 8 AP 5 ... |
+------------------------------+
```

**Variante 2B — Auch auf Mobil links, als eingeklappte Rail mit Kurzform (nur Icon/Zahl).**
Mobil: 44 px breite Rail am linken Rand mit Zahl + Kürzel gestapelt; Tipp öffnet das Popup mit Details (heute Hover → auf Touch Tap). Gewinnt vertikalen Platz oben, kostet ~44 px Breite von 390 px (≈11 %) — für Karte (Hexgrid) und Techtree spürbar.

```
Mobil 390 px
+------------------------------+
| NOURON            [Sol 12]   |
+----+-------------------------+
|CR  |                         |
|120 |        Inhalt           |
|KOL |                         |
|4/6 |                         |
|... |                         |
+----+-------------------------+
| [Kol] [KZ] [Ber] [Log] [⋯]   |
+------------------------------+
```

### Abwägung
- 2A ist der risikoärmere Weg: Desktop hat Breite im Überfluss, Höhe ist knapp (Techtree, Karte). Mobil bleibt unverändert. Das widerspricht aber dem Wunsch „links“ nur auf Desktop — Owner fragen.
- 2B bringt einheitliches Verhalten, verschlechtert aber Mobil-Breite; Transparenz über Inhalt ist bei Text dahinter lesbar zu halten.
- Transparenz: Chip-Text über Karte/Techtree-Canvas kann kontrastlos werden. Vorschlag: leichter Weißverlauf/Blur bleibt, sonst WCAG-Kontrast (4,5:1) nicht garantiert.

### Auswirkungen
- **Hint-Leiste:** bleibt oben, ist dann nicht mehr „unter der Ressourcenleiste“ gebündelt; Reihenfolge Kopfzeile → Hint → Inhalt. Keine Konflikte mit der linken Rail, solange sie darunter beginnt (`top: var(--header-h) + Hint-Höhe` ist dynamisch — Rail besser als Teil eines Grid-Layouts statt `fixed`).
- **Debug-Leiste / Feedback-Button:** unverändert, sofern die Rail oberhalb endet (`bottom: var(--bottom-nav-h)`). Bei vielen Chips auf niedrigen Laptops (< 700 px) muss die Rail scrollen oder sich komprimieren.
- **Techtree-Stage:** `fit()` bekommt die Breite abzüglich Rail; Höhe gewinnt ~40 px (Resbar entfällt in der Kopfzeile, auf Desktop). Rendering-Breite des Canvas ändert sich → Resize-Handler/Viewport-Berechnung prüfen. Das Techtree-Panel (`--tt-top`) startet weiterhin an der Stage-Oberkante.
- **Colony-Karte:** Breite sinkt um ~92 px bei 1400 px (Karte + 320-px-Sidebar + Rail). Hex-Zeichner (`colony-hexgrid.js`) skaliert per SVG-`viewBox` (zu prüfen); `min-height`-Formel verliert die ~40 px Resbar.
- **Popups:** `clamp()` korrigiert nur `margin-left` — bei linker Rail müssen Popups nach rechts aufgehen (Position `left: 100%`), neue Klemmlogik nötig. Touch: Hover-Popups ersetzen durch Tap (Klick-Handler existiert bereits).
- **Live-Sync-Regel** (`refreshShell()`) bleibt unberührt — die Leiste liest weiter den `shell`-Store.

### Barrierefreiheit
- Chips sind heute `<span onclick>` ohne Rolle (svelte-ignore). Beim Umbau sinnvoll: `<button>` mit `aria-expanded`, `aria-describedby` auf Popup, 44×44 px Ziel, Esc schließt.
- Fokus-Reihenfolge: Leiste im DOM vor `<main>` (Info vor Inhalt) oder als `role="status"`-Region; Vorschlag: Rail nach der Kopfzeile, vor dem Inhalt, per `aria-label` benannt.
- Transparenz: ausreichender Kontrast, auch bei `prefers-reduced-transparency` / hohem Kontrast festen Hintergrund verwenden.
- Werte nicht nur über Farbe (Warn-/Danger-Chip) kodieren — Text oder Icon ergänzen.

### Aufwand
**Mittel (2A)**, **Groß (2B)** wegen Mobil-Breite, Popup-Logik und Hexkarten-Prüfung.

---

## Idee 3 — Sidebar auf Mobil als Vollbild-Popup

Betroffen: `.tile-panel` auf der Colony-Seite (Bau-Menü, Tile-Info, Aktionen) sowie ähnliche Seitenspalten bei Berater/Hangar/Cantina. Der Techtree nutzt schon ein „Sheet“ unter 600 px (`sheetMode`), das ist die Vorlage.

### Varianten

**Variante 3A — Vollbild-Dialog, bei Tipp auf ein Tile geöffnet.**
`<dialog>`/Overlay `100dvw × 100dvh`, Kopf mit Zurück/Schließen, Inhalt scrollt. Karte wird verdeckt; Zurück zeigt sie wieder. Bau-Aktion schließt oder bleibt offen (Owner).

```
Mobil 390 px
+------------------------------+      +------------------------------+
| Karte                        |      | [←]  Regolith-Harvester  [×] |
|   ⬡ ⬡ ⬡                      | Tap  |------------------------------|
|  ⬡ ⬡ ⬡ ⬡                     | ---> | Stufe 2, Zustand 80 %        |
|   ⬡ ⬡ ⬡                      |      | [Reparieren  1 AP]           |
| Tile-Info: Harvester (Tipp)  |      | [Ausbauen    3 AP]           |
+------------------------------+      +------------------------------+
| [Kol] [KZ] [Ber] [Log] [⋯]   |      |  (Bottom-Nav verdeckt/aktiv) |
+------------------------------+      +------------------------------+

Desktop 1400 px: unverändert (Sidebar rechts, 320 px)
```

**Variante 3B — Bottom-Sheet mit Zwischenstufen (Peek 25 % → halb → voll).**
Karte bleibt sichtbar, Sheet zieht per Griff hoch; Vollbild nur auf Wunsch (z. B. Bau-Menü). Mehr Interaktionscode (Drag, Snap-Punkte), bessere Orientierung.

```
Mobil 390 px
+------------------------------+
| Karte (sichtbar)             |
|  ⬡ ⬡ ⬡  ⬡                    |
+==============================+
|  ──  (Griff)                 |
| Harvester  St.2   [Repar. 1AP]|  <- Peek
|                          ▲   |
+------------------------------+
| [Kol] [KZ] [Ber] [Log] [⋯]   |
+------------------------------+
```

### Auswirkungen
- **Hint-Leiste:** im Vollbild-Popup (3A) nicht sichtbar — gewollt oder Hint-Link ins Popup spiegeln? Vorschlag: Hint bleibt unter dem Popup, kein Spiegeln.
- **Debug-/Feedback:** Popup liegt über Feedback-Button (z-index); Feedback unter Popup verstecken ist okay. DebugBar (z 9999) bleibt sichtbar, überdeckt unten.
- **Bottom-Nav (Idee 1):** Vollbild-Popup deckt sie entweder ab (Schließen-Button nötig) oder endet darüber (`height: calc(100dvh - var(--bottom-nav-h))`). Vorschlag: endet über der Leiste, damit Navigation immer erreichbar bleibt.
- **Techtree-Stage:** Techtree hat bereits ein Mobil-Sheet — Komponente vereinheitlichen (`MobileSheet.svelte`), `fit()` bleibt bis auf den unteren Balken gleich.
- **Colony-Karte:** gewinnt Platz — kein 360-px-Panel mehr unter der Karte (`max-height: 360px` entfällt), Karte wieder voll nutzbar. Zustand „Tile ausgewählt“ muss auch ohne sichtbares Panel erkennbar sein (Markierung auf der Karte, Chip „Tile-Info“).
- Die Mobil-Seiten Berater/Hangar/Cantina haben Listen-Detail-Splits (`<900` stapelnd); dasselbe Muster dort gesondert prüfen — nicht in demselben PR.

### Barrierefreiheit
- Echter Dialog: `role="dialog"`, `aria-modal="true"`, `aria-labelledby`, Fokus beim Öffnen ins Popup, Fokusfalle, Esc/Zurück-Taste schließt (Browser-Back via History-State, sonst verlässt der Nutzer versehentlich die Seite), Fokus kehrt zum auslösenden Tile zurück.
- Schließen-Button ≥ 44×44 px, oben und gut erreichbar; bei Daumenbedienung zusätzlich unten (Vorschlag: Primäraktion unten, Schließen oben).
- Hintergrund-Inhalt `inert`, Scroll-Lock auf `<body>`.
- Bei 3B: alternative Bedienung ohne Drag (Button „Erweitern“), `prefers-reduced-motion` beachten.

### Aufwand
**Mittel (3A)**, **Groß (3B)**.

---

## Gemeinsame Querschnittsarbeit (Vorschlag)

- CSS-Variablen `--header-h`, `--bottom-nav-h`, `--debug-bar-h`, `--rail-w` zentral im Layout setzen; alle festen `calc(100dvh - Npx)` in `colony-view.css`, `hangar.css`, `advisors.css`, `cantina.css` darauf umstellen. Das ist eine Voraussetzung für Idee 1 und 2 und kann als eigener, risikoarmer PR laufen.
- Doku: `docs/frontend-conventions.md` §4 (Navigation, Breakpoints) und `docs/design-system/readme.md` (Layout-Absatz „fixed navbar + resource bar + subnav at the top“) müssen mit dem Beschluss angepasst werden — erst nach Owner-Entscheidung.
- Browser-Prüfung: `tools/uicheck/uicheck` auf 1400 px und 390 px, dazu ein Gerät mit Safe-Area (iOS-Emulation); `npm run check` 0 Fehler.
- Neue `lang/`-Schlüssel (nur Vorschlag): Gruppennamen/„Mehr“-Label für die Bottom-Nav, `aria-label`s (Hauptnavigation, Ressourcenleiste, Detail-Dialog schließen). Deutsche Werte macht content-writer, bis dahin `TODO`-Platzhalter.

---

## Empfohlene Reihenfolge in kleinen PRs (Vorschlag)

1. **PR 1 — Layout-Variablen (Klein):** `--header-h` etc. einführen, feste `calc(…px)` ersetzen, Techtree-`fit()` an Variablen koppeln. Optisch keine Änderung; reduziert das Risiko aller folgenden Schritte.
2. **PR 2 — Bottom-Nav Grundgerüst, Variante 1A (Mittel):** `BottomNav.svelte`, `<main>`-Padding, alte Header-Nav entfernen (Mobil und Desktop), DebugBar/Feedback anheben, Techtree-Höhe. Nutzermenü bleibt in der Kopfzeile.
3. **PR 3 — Subnav-Aufklappen (nur bei 1B) (Klein–Mittel):** nachträglich möglich, wenn 1A zu voll wird.
4. **PR 4 — Ressourcenleiste links, Variante 2A (Mittel):** Desktop-Rail, Popups nach rechts, Chips als Buttons mit ARIA; Mobil unverändert, aber transparent.
5. **PR 5 — Mobil-Sidebar als Vollbild, Variante 3A (Mittel):** gemeinsame `MobileSheet`-Komponente, zuerst nur Colony, danach Techtree angleichen; Berater/Hangar/Cantina als Folge-PRs.
6. **PR 6 — Doku-Anpassung** (Conventions, Design-System, CHANGELOG, ROADMAP) beim jeweiligen PR mitführen.

Begründung: erst Voraussetzungen (1), dann die Navigation (2), weil Ressourcenleiste und Sidebar-Popup sich auf deren Höhe beziehen. Idee 3 ist unabhängig und könnte vorgezogen werden, wenn Mobil-Colony am meisten stört.

---

## Owner-Fragen (jeweils mit Empfehlung, alles unverbindlich)

1. **Navigation: 1A oder 1B?** Empfehlung: 1A (flach), „Mehr“-Sheet nur auf Mobil. Wenn mehr Ziele hinzukommen, auf 1B ausbauen. Offen: Welche 4 Ziele gehören auf Mobil fest in die Leiste? Empfehlung: Kolonie, Kommandozentrale, Berater, Log + „Mehr“.
2. **Nutzermenü, Sol-Button, Logo?** Empfehlung: schlanke Kopfzeile oben mit Logo, SolButton, Nutzermenü (Logout, Einstellungen, Codex, Läufe). Soll der SolButton unten in die Leiste (Daumen!) statt oben? Er ist die häufigste Aktion — Empfehlung: ja prüfen, aber erst in einem Folge-PR.
3. **Gesperrte Ziele (Techtree/Cantina/Hangar)** vor dem Bau: grau mit Hinweis (Empfehlung) oder ausblenden?
4. **Ressourcenleiste links nur auf Desktop (2A) oder auch Mobil (2B)?** Empfehlung: nur Desktop; Mobil bleibt oben, aber transparent, weil 44 px Breite auf 390 px schmerzt.
5. **Wie transparent?** Empfehlung: halbtransparent mit Blur statt komplett durchsichtig, damit Kontrast gegen Karte/Techtree gesichert bleibt.
6. **Hint-Leiste** oben lassen (Empfehlung) oder unten über der Navigation?
7. **Mobil-Sidebar: Vollbild (3A, Empfehlung) oder Bottom-Sheet mit Zwischenstufen (3B)?** Soll das Popup nach einer Bauaktion schließen? Empfehlung: schließen, Tile bleibt auf der Karte markiert.
8. **Admin-DebugBar:** unten über der Nav, in die Kopfzeile verlegen oder einklappbar? Empfehlung: einklappbar, nur für Admins.
9. **Feedback-Button:** als schwebender Button behalten (über der Nav) oder ins Nutzermenü? Empfehlung: ins Nutzermenü (spart Kollisionen), solange Beta-Feedback trotzdem leicht erreichbar bleibt.
10. **Priorität/Zeitpunkt:** Vor oder nach dem Techtree-Feinschliff? Empfehlung: PR 1 (Variablen) sofort, da kleines Risiko; Rest nach dem Techtree-Feinschliff, damit `fit()` nicht zweimal angefasst wird.
