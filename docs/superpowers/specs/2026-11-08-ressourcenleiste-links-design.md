# Ressourcenleiste links — Verhaltensentwurf (ENTWURF / Vorschlag)

> **Hinweis 2026-11-08 (Hauptagent):** PR 1 führte `--app-header-h` und `--app-bottom-h` ein (gemessen, in `frontend/src/app.css`/`(game)/+layout.svelte`), nicht `--content-top`/`--content-bottom`. Beim Umsetzen die Namen angleichen. Die Seiten-Offsets sind noch nicht auf die Messvariablen umgestellt (PR 2, ändert die Optik).


Stand: 2026-11-08. **Alles hier ist ein Vorschlag des `ui-specialist`, nichts ist beschlossen oder umgesetzt.** Angaben mit „Annahme“ sind nicht geprüft und stehen zur Bestätigung.

Grundlage: Owner-Entscheidungen 4 und 5 in `docs/superpowers/specs/2026-11-08-ui-umbau-entwurf.md`:

- Ressourcenleiste **links, einheitlich auf Desktop und Mobil**, halbtransparent mit Blur (zum Ausprobieren).
- Auf der **Kolonieansicht** darf sie die Karte überlagern.
- Auf **allen anderen Screens eingeklappt** (überlagert nichts), auf Wunsch vom linken Rand ausklappbar.
- Hint-Leiste bleibt oben; schlanke Kopfzeile (Logo, SolButton, Nutzermenü); Navigation später unten (Bottom-Nav); Mobil-Sidebar wird Vollbild (3A).

Betroffene Dateien (nur gelesen): `frontend/src/lib/components/ResourceBar.svelte`, `AppHeader.svelte` (enthält heute `.resbar` + `HintBar`), `HintBar.svelte`, `frontend/src/routes/(game)/+layout.svelte`, `frontend/src/lib/styles/resources.css` (`.res-popup` ist heute `position: absolute; top: 100%; left: 50%`).

---

## 1. Zustände und Breiten

Es gibt zwei **stabile** Zustände und einen **temporären**:

| Zustand | Bedeutung | Layout-Wirkung |
|---|---|---|
| **Ausgeklappt** (expanded) | Panel mit allen Chips untereinander, halbtransparent + Blur | Overlay, schiebt nichts. Inhalt (`main`) behält seine volle Breite. |
| **Eingeklappt** (collapsed) | Nur ein Griff (Tab) am linken Rand | Überlagert praktisch nichts (siehe 2). |
| **Peek** | Eingeklappt, aber kurz ausgeklappt (Hover/Fokus auf den Griff, nur mit Maus/Tastatur) | Overlay wie „ausgeklappt“, schließt von selbst. |

Wichtig: Die Leiste ist **immer Overlay** (`position: fixed`), nie Teil des Grid-Flusses. Dadurch ändert Ein-/Ausklappen weder die Karten-/Techtree-Breite noch löst es `fit()`/Resize aus. (Das ersetzt die Rail-im-Grid-Idee aus dem Hauptentwurf, Variante 2A, die nur ohne Überlagerung funktionierte.)

### Breiten

| Zustand | 1400 px | 390 px |
|---|---|---|
| Ausgeklappt | 132 px Breite (Chip 116 px + Innenabstand 8 px), ca. 9 % | 112 px Breite (Chip 96 px + 8 px), ca. 29 % |
| Eingeklappt | Griff sichtbar 28 px, Touchfläche 44 px | gleich: sichtbar 28 px, Touchfläche 44 px |
| Peek | wie ausgeklappt | entfällt (Touch kennt kein Hover; Tippen = ausgeklappt) |

Begründung 112 px mobil: schmal genug, dass bei 390 px noch ca. 278 px Karte sichtbar bleiben; die Popups (siehe 2) brauchen rechts daneben ca. 260 px — das passt gerade so (390 − 112 − 8 = 270). Der Wert ist Annahme und im Browser bei 390 px zu prüfen; Fallback: Popup zeichnet über dem Panel (siehe 2).

Höhe: von `--content-top` (Unterkante Kopfzeile + Hint-Leiste) bis `--content-bottom` (Oberkante Bottom-Nav bzw. Debug-Leiste, sonst 0); bei Platzmangel `overflow-y: auto` im Panel. Beide Variablen gehören zu PR 1 „Layout-Variablen“ (Owner-Entscheidung 10) und werden per `ResizeObserver` auf Header/Hint gesetzt, da die Hint-Leiste dynamisch ist.

Platzbedarf ausgeklappt: 8 Chips (Sol, CR, KOL, Vertrauen, Rg, Or, Co, AP) à 44 px + Lücken/Trenner ≈ 410 px. Auf 390×844 bleiben ca. 600 px Platz, passt. Querformat Handy (390 px Höhe) scrollt im Panel.

---

## 2. Inhalt

### Eingeklappt: nur Griff (Empfehlung) oder schmale Icon-Rail (Alternative)

**Empfehlung: Griff.** Ein Tab am linken Rand, oben im Inhaltsbereich (`top: calc(var(--content-top) + 12px)`), Höhe 64 px, sichtbare Breite 28 px, Touchfläche 44 × 64 px (Fläche reicht transparent in den Inhalt hinein; nur dieser Bereich fängt Klicks). Inhalt des Griffs: Chevron `›` und darunter der **AP-Wert** (Zahl, 2 Stellen), weil AP die meistgeprüfte Größe ist — so bleibt eine Kerninformation sichtbar, ohne etwas zu überlagern. Der Griff trägt den Warnzustand mit (Rand `--color-warning`/`--color-danger`, wenn Vertrauen/Nexus-Schulden im roten Bereich, plus Text-Icon `!` — nicht nur Farbe).

**Alternative: schmale Icon-Rail (44 px)** mit Kürzel + Zahl gestapelt (wie Variante 2B im Hauptentwurf). Zeigt alles, kostet aber 44 px Breite dauerhaft und verletzt „überlagert nichts“, außer `main` bekommt `padding-left: 44px` (dann Reflow bei Wechsel, Techtree-`fit()` betroffen). Nicht empfohlen; Owner-Frage 1.

### Ausgeklappt

Chips untereinander, gleiche Reihenfolge wie heute (`chips` in `ResourceBar.svelte`: Sol, CR, KOL, Vertrauen, Rg/Or/Co, AP; Trenner `res-divider` bleiben als horizontale Linie). Jeder Chip: Kürzel links, Zahl rechts (ausgerichtet), Höhe 44 px (Mobil) / 36 px mit 44-px-Hit-Area (Desktop; Owner-Vorgabe ≥ 44 px gilt für Touch, siehe 5). Unten im Panel ein Einklapp-Button `‹` (derselbe Griff, bleibt an der Position des Griffs sichtbar, damit Auf- und Zuklappen an derselben Stelle liegen).

### Popups öffnen nach rechts

- Chip wird `<button>`; Klick/Tap togglet das Popup (heutige `open`-Logik), Hover öffnet es nur bei `(hover: hover)`.
- Position: `left: calc(100% + 8px)`; `top` an der Chip-Oberkante, vertikal in den Viewport geklemmt (neue Klemmlogik: heutiges `clamp()` korrigiert nur `margin-left`; neu `margin-top`, wenn `rect.bottom > innerHeight − content-bottom − 8`). Kein `translateX(-50%)` mehr.
- Breite: `min(280px, calc(100vw − var(--rail-w) − 16px))`. Auf 390 px ist das ca. 262 px.
- Fallback, falls Breite nicht reicht (z. B. Zoom): Popup überlagert das Panel (`left: 8px`), Hintergrund undurchsichtig. Annahme: Normalfall tritt nicht ein.
- Popup-Hintergrund bleibt **deckend** (`#fff`, wie heute), nicht transparent — es enthält Tabellenzeilen.
- Nur ein Popup gleichzeitig; Außenklick/Esc schließt (heutige `svelte:window onclick`).
- Im eingeklappten Zustand gibt es keine Popups (nur Panel-Aufklappen); Chip-Popups gibt es nur ausgeklappt/Peek.

### Optik

- Hintergrund: `rgba(255,255,255,0.72)` + `backdrop-filter: blur(8px)`; Rand rechts `1px solid var(--color-border)`; Eckenradius rechts `var(--radius-…)` (Token prüfen), Schatten `var(--shadow-flyout)`. Werte sind Startwerte „zum Ausprobieren“ (Owner-Entscheidung 5): Deckkraft 0,72 ist Untergrenze für Lesbarkeit (siehe 5).
- Nur Tokens aus `docs/design-system/tokens/` verwenden; für Deckkraft/Blur existiert kein Token → zwei neue Variablen in `app.css`/Leisten-CSS (`--rail-bg`, `--rail-blur`), im Design-System nachzuziehen (Doku-PR).

---

## 3. Auslöser

| Eingabe | Verhalten |
|---|---|
| Klick/Tap auf Griff | Pinned umschalten (ausgeklappt ⇄ eingeklappt). Primärweg, funktioniert überall. |
| Hover Griff (nur `hover: hover`), 200 ms Verzögerung | Peek; schließt 300 ms nach Verlassen von Griff **und** Panel. Klick auf den Griff während Peek pinnt das Panel. |
| Tastatur: Griff fokussierbar, `Enter`/`Space` togglet; Fokus im Panel + `Esc` klappt ein (erst Popup schließen, dann Panel) und setzt Fokus zurück auf den Griff | Globale Taste (Annahme `R` für „Ressourcen“) nur, falls kein Eingabefeld fokussiert und kein Konflikt mit bestehenden Shortcuts; **in Owner-Frage 4**. |
| Swipe | Rechtswisch vom linken Rand (Zone 24 px) öffnet, Linkswisch auf dem Panel schließt. **Nur optionale Ergänzung**, weil der Rand-Swipe von iOS/Android als „Zurück“-Geste belegt ist und mit Karten-Gesten (Verschieben/Zoomen des Hexfelds, Annahme: heute keine, zu prüfen in `colony-hexgrid.js`) kollidieren kann. Der Griff bleibt der verlässliche Weg. |
| Outside-Tap auf Mobil | Auf Nicht-Kolonie-Screens klappt ausgeklapptes Panel bei Tippen außerhalb zu (es ist dort nur ein Nachschlagen). Auf der Kolonie bleibt es offen (siehe unten). |

### Merken des Zustands

- Standard je Screen-Art: **Kolonie = ausgeklappt, alle anderen = eingeklappt** (Owner-Vorgabe). Das ist der Default, nicht gespeichert.
- Abweichung durch den Nutzer wird als Komfort in `localStorage` gespeichert, **pro Screen-Gruppe** (`nouron.rail.colony`, `nouron.rail.other`), nicht pro Route: wer auf der Kolonie einklappt, bekommt sie dort eingeklappt, bleibt aber beim Wechsel auf andere Screens beim Default eingeklappt. Fehlt `localStorage` (Privatmodus, Fehler), gilt einfach der Default; nichts hängt davon ab. Kein Server-Speichern (Annahme: überflüssig).
- Beim Seitenwechsel innerhalb der Gruppe bleibt der Zustand; beim Wechsel Kolonie → andere Screens zählt der Default der Zielgruppe bzw. die dort gespeicherte Wahl.
- Peek wird nie gespeichert.
- Sehr schmale/kurze Viewports (Annahme): auf der Kolonie mobil klappt das Panel beim ersten **Tile-Antippen** (öffnet die Vollbild-Sidebar 3A) ein, damit die Rückkehr zur Karte nicht von der Leiste verdeckt wird; die gespeicherte Wahl wird dadurch nicht überschrieben (nur temporär).

---

## 4. Kollisionen und z-index

### Ebenenreihenfolge (Vorschlag, nur Ergänzung; bestehende Werte unverändert)

| Ebene | z-index | Bemerkung |
|---|---|---|
| Seiteninhalt, Karte, Techtree-Canvas | 0–20 (seitenlokal, `techtree-view.css` bis 3/151 lokal) | unverändert |
| **Ressourcenleiste Panel / Griff** | **80** | über Inhalt, unter Kopfzeile |
| Chip-Popups (im Panel-Stacking-Context) | 81 (relativ) | |
| Kopfzeile (`.app-header`, sticky) | 100 | unverändert; Leiste beginnt darunter, überlappt nie |
| Bottom-Nav (später) | 110 | endet über Leiste; Leiste endet über Bottom-Nav (`bottom: var(--content-bottom)`) |
| Mobil-Vollbild-Sidebar (3A), Colony-Modale | 300–600 | verdecken die Leiste bewusst (Modal) |
| Nutzermenü | 500 (heute) | |
| Feedback-Button (heute 900) | entfällt (Nutzermenü) | |
| Debug-Leiste | 9999 | ganz unten/oben, klappt ein |

### Kollisionen im Einzelnen

- **Karte / Hexfeld (Kolonie):** Das Panel überlagert die linken 132 px (Mobil 112 px) der Karte und **fängt dort Klicks ab**. Gegenmittel: (a) halbtransparent, damit sichtbar bleibt, was dahinter liegt; (b) Griff-Hit-Area nur 44 px breit; (c) Nutzer kann einklappen; (d) Annahme/Prüfauftrag: `colony-hexgrid.js` zentriert und skaliert per SVG-`viewBox`; die Karte sollte beim Start so gesetzt sein, dass die Ringe 0–1 nicht unter dem Panel liegen (ggf. `padding-left` im Zeichner = Panelbreite, nur Zeichenlogik, kein Reflow des Layouts). Mobil ist die Überlagerung am stärksten (29 %) — bei Bedarf Panel in der Kolonie mobil standardmäßig eingeklappt (Owner-Frage 2).
- **Techtree / Canvas:** eingeklappt nur der Griff oben links; Peek/Ausklappen überlagert Knoten am linken Rand. Weil Overlay, ändert `fit()` nichts. Prüfen: ob links im Techtree-Stage Bedienelemente liegen, die der Griff verdeckt (Annahme: nein; Phase 1 ist links verankert → ggf. Griff vertikal in die Mitte, offen).
- **Hint-Leiste:** bleibt oben in der Kopfzeile; Leiste beginnt unter ihr (`--content-top` inkl. Hint-Höhe, wächst/schrumpft mit dem Hint → Panel `top` per Transition 150 ms folgen oder ohne Animation springen; Annahme: ohne Animation, einfacher).
- **Sidebar-Detailpanels rechts:** `.tile-panel` (320 px) und Techtree-Detailpanel liegen rechts, kollidieren bei ≥ 900 px mit dem linken Overlay nicht (1400 px: 132 + 320 = 452 von 1400; 900 px: 452 von 900, Karte bleibt bei Overlay ohnehin voll breit). Zwischen 600–899 px steht die Sidebar unter der Karte (einspaltig); Leiste überlagert nur die linke Kartenkante.
- **Bottom-Nav (später):** Leiste endet über der Bottom-Nav (`bottom: var(--content-bottom)`); bis zum Bottom-Nav-PR ist `--content-bottom` = 0 bzw. DebugBar-Höhe. Auf Mobil mit Safe-Area: `padding-bottom: env(safe-area-inset-bottom)` nur an der Bottom-Nav, nicht an der Leiste.
- **Kopfzeile oben:** Die heutige `.resbar` (ca. 40 px) verschwindet aus dem Header; die Höhenformeln im Seiten-CSS (`calc(100dvh − 84px)` etc.) gewinnen ca. 40 px — PR 1 muss das berücksichtigen (Reihenfolge: PR 1 vor der Verlegung).
- **Debug-Leiste (Admin):** Leiste endet über ihr (`--content-bottom` enthält deren Höhe).
- **Colony-Modale / Vollbild-Sidebar:** liegen darüber; Leiste wird für die Dauer nicht inert gesetzt, aber vom Modal-Backdrop abgedeckt. Fokusfalle des Modals hat Vorrang (Leiste liegt außerhalb → nicht erreichbar, gewollt).

---

## 5. Kontrast und Barrierefreiheit

- **Touchziele:** Griff 44 × 64 px; jeder Chip-Button mindestens 44 × 44 px auf Touch (`@media (pointer: coarse)`), auf Desktop sichtbar 36 px hoch mit 44-px-Hit-Area (Padding). Abstand zwischen Chips ≥ 4 px. Der Mindestwert 24 px des Design-Systems wird damit klar übertroffen (Owner-Wunsch ≥ 44 px).
- **Rollen/Semantik:** Panel = `<aside aria-label="…">` (neuer lang-Key); Griff = `<button aria-expanded aria-controls="rail-panel" aria-label="Ressourcenleiste ein-/ausklappen, AP 5">`; Chips = `<button aria-expanded aria-describedby="popup-id">` (heute `<span onclick>` mit `svelte-ignore` — der Umbau beseitigt das); Popup `role="tooltip"`/`region` mit Titel, Esc schließt.
- **Fokusreihenfolge:** im DOM nach der Kopfzeile, **vor** `main` (Information vor Inhalt). Eingeklappt: nur der Griff ist ein Tab-Stopp, Chips sind `inert`/`hidden` (kein Tab-Rauschen durch 8 versteckte Elemente). Ausgeklappt: Griff → Chips (Reihenfolge wie visuell) → `main`. Beim Einklappen per Tastatur bleibt der Fokus am Griff.
- **Live-Werte:** kein `aria-live` am ganzen Panel (zu geschwätzig). Der AP-Wert im `aria-label` des Griffs reicht; eine Änderung nach einer Aktion meldet die Seite selbst per Statusmeldung.
- **Kontrast:** Text auf Panel mindestens 4,5:1 (Chip-Text `--color-text-primary` auf effektiv ≥ `rgba(255,255,255,0.72)` über Karte; Worst Case dunkler Kartenhintergrund → Deckkraft 0,72 plus Blur; im Browser an Karten-/Techtree-Hintergrund messen). Danger/Warning nie nur per Farbe: Text-Icon `!` bzw. Wert im Popup (Chips tragen das heute schon über Texte/`res-chip--warning`; für den Griff neu).
- **Reduzierte Transparenz/Kontrast:** `@media (prefers-reduced-transparency: reduce), (prefers-contrast: more)` und `@supports not (backdrop-filter: blur(1px))` → fester Hintergrund `var(--color-bg)`, kein Blur.
- **Reduzierte Bewegung:** Auf-/Zuklappen mit `transform: translateX` 150 ms (`--duration-fast`); unter `prefers-reduced-motion: reduce` ohne Übergang.
- Popups/Panel dürfen Fokus nicht wegnehmen, wenn sie per Hover/Peek erscheinen.

---

## 6. Live-Sync (unverändert)

Die Regel aus `docs/frontend-conventions.md` bleibt: Aktionen, die AP/Ressourcen ändern, rufen danach `refreshShell()` bzw. `setAp()` auf; die Leiste liest weiter ausschließlich den `shell`-Store (`s`-Prop). Der Umzug aus `AppHeader` ins `(game)/+layout.svelte` (`{#if $shell}<ResourceRail s={$shell} />{/if}` zwischen Header und `main`) ändert daran nichts, weil `shell` eine globale Store ist. Der Griff-AP-Wert und die Chips stammen aus derselben Quelle. Keine Client-Uhr, keine eigenen Werte. Seitenwechsel (`{#key}` um `main`) rendert die Leiste nicht neu, weil sie außerhalb davon steht — Zustand (pinned/Popup offen) bleibt über Navigation erhalten, Popup wird bei Routenwechsel geschlossen.

---

## 7. ASCII-Skizzen

Legende: `▒` = halbtransparentes Panel mit Blur, `‹ ›` = Griff.

### Ausgeklappt

```
Desktop 1400 px, Kolonie (Standard)
+----------------------------------------------------------------------+
| NOURON                                        [Sol 12 ▶][Mario ▾]    |
| (Hint-Leiste)                                                        |
+--------+-------------------------------------------------------------+
|▒ Sol 12▒        ⬡ ⬡ ⬡                                  | Tile-Panel |
|▒ CR 120▒      ⬡ ⬡ ⬡ ⬡     (Karte läuft unter dem       | 320 px     |
|▒ KOL4/6▒ ⌐Popup→  ⬡ ⬡ ⬡     Panel durch)                 |            |
|▒ Vtr +6▒                                                | [Bau 1 AP] |
|▒ Rg  40▒                                                |            |
|▒ Or  12▒                                                |            |
|▒ Co   8▒                                                |            |
|▒ AP   5▒ ‹                                              |            |
+--------+-------------------------------------------------------------+

Mobil 390 px, Kolonie
+------------------------------+
| NOURON        [Sol 12][≡]    |
| (Hint)                       |
+----------+-------------------+
|▒ Sol  12▒|     ⬡ ⬡          |
|▒ CR  120▒|    ⬡ ⬡ ⬡         |
|▒ KOL 4/6▒|     ⬡ ⬡          |
|▒ Vtr  +6▒|                   |
|▒ Rg   40▒|   (Karte)         |
|▒ Or   12▒|                   |
|▒ Co    8▒|                   |
|▒ AP    5▒‹|                  |
+----------+-------------------+
|   (Bottom-Nav, später)       |
+------------------------------+
```

### Eingeklappt (Standard auf allen Nicht-Kolonie-Screens)

```
Desktop 1400 px, z. B. Techtree
+----------------------------------------------------------------------+
| NOURON                                        [Sol 12 ▶][Mario ▾]    |
+----------------------------------------------------------------------+
|› AP                                                                  |
|  5                       Techtree-Canvas (volle Breite)              |
|                                                                      |
+----------------------------------------------------------------------+

Mobil 390 px, z. B. Hangar
+------------------------------+
| NOURON        [Sol 12][≡]    |
+------------------------------+
|› AP                          |
|  5        Inhalt (volle      |
|           Breite, nichts     |
|           überlagert außer   |
|           dem Griff)         |
+------------------------------+
```

### Peek (Hover/Fokus, nur Desktop mit Maus/Tastatur) und Popup nach rechts

```
Desktop 1400 px, Techtree, Maus am linken Rand (Peek) + Klick auf "CR"
+----------------------------------------------------------------------+
| NOURON                                        [Sol 12 ▶][Mario ▾]    |
+--------+------------------------------+------------------------------+
|▒ Sol 12▒                              |                              |
|▒ CR 120▒>[ Credits                  ] |   Techtree-Canvas            |
|▒ KOL4/6▒ [ Zahlungsmittel ...       ] |                              |
|▒ ...   ▒ [ Subvention  +10 / Unterh. ]|                              |
|▒ AP   5▒ [ Netto/Sol    +4          ] |                              |
+--------+------------------------------+------------------------------+

Mobil 390 px: kein Peek; Tippen auf den Griff = ausgeklappt (pinned),
Tippen auf Chip öffnet das Popup rechts (262 px), Außentippen schließt
erst das Popup, dann (auf Nicht-Kolonie) das Panel.
+------------------------------+
|▒ Sol  12▒                    |
|▒ CR  120▒>[ Credits         ]|
|▒ KOL 4/6▒ [ Zahlungsmittel  ]|
|▒ ...    ▒ [ Netto/Sol +4    ]|
+----------+-------------------+
```

---

## 8. Umsetzung in kleinen PRs (Aufwand: S ≈ ½ Tag, M ≈ 1 Tag, L ≈ 2 Tage)

0. **Voraussetzung (bereits beschlossen, Owner-Entscheidung 10): PR 1 Layout-Variablen** inkl. `--content-top` / `--content-bottom` (Header + Hint, Debug/Bottom-Nav). **S–M** (steht im Hauptentwurf).
1. **PR A — Verlegung ohne Verhaltensänderung (S):** `ResourceBar` aus `AppHeader.resbar` in neue `ResourceRail.svelte` im `(game)/+layout.svelte`, vorerst immer ausgeklappt, vertikale Chips, deckender Hintergrund. Kopfzeilenhöhe-Variablen anpassen. Dient dazu, Höhenformeln zu validieren.
2. **PR B — Popups nach rechts + Chips als Buttons (M):** neue Klemmlogik, `aria-*`, Tap-Verhalten, Esc, 44-px-Ziele (`pointer: coarse`).
3. **PR C — Zustände und Griff (M):** ausgeklappt/eingeklappt/Peek, Default je Route (Kolonie vs. Rest), Griff mit AP-Wert, Transition, `prefers-reduced-motion`, Fokus-/Inert-Regeln, Outside-Tap mobil.
4. **PR D — Transparenz/Blur + Fallbacks (S):** `--rail-bg`/`--rail-blur`, `prefers-reduced-transparency`/`prefers-contrast`/`@supports`-Fallback; Werte im Browser vor Karte/Techtree feinjustieren.
5. **PR E — Merken + Komfort (S):** `localStorage` pro Screen-Gruppe, optionale Taste, optionaler Rand-Swipe (nur wenn Owner es will).
6. **PR F — Kolonie-Feinschliff (M, Prüfarbeit):** `colony-hexgrid.js` Startausschnitt gegen Panelbreite prüfen, mobil Auto-Einklappen beim Tile-Tippen (Zusammenspiel mit Vollbild-Sidebar 3A).
7. **PR G — Doku (S):** `docs/frontend-conventions.md` (Navigation/Breakpoints/Z-Index/Ressourcenleiste), `docs/design-system/readme.md` (Layout-Absatz „resource bar at the top“), `CHANGELOG.md`, `ROADMAP.md`; lang-Keys (`aria-label`s) mit `TODO content-writer`.

Prüfung je PR: `cd frontend && npm run check` (0/0), Browser mit `tools/uicheck/uicheck` auf 1400 px und 390 px (Kolonie, Techtree, Hangar), keine Konsolenfehler; Kontrast gegen echten Kartenhintergrund messen.

Neue lang-Keys (Vorschlag, deutsche Werte macht content-writer): `resources.rail_label`, `resources.rail_toggle`, `resources.rail_toggle_ap` (mit `:ap`), `resources.rail_close_popup`.

Serverseitig: **keine** neuen Daten nötig; alle Werte kommen weiter aus `GET /api/v1/shell`.

---

## 9. Owner-Fragen (mit Empfehlung)

1. **Eingeklappt nur Griff oder schmale Icon-Rail?** Empfehlung: nur Griff mit AP-Wert, weil „überlagert nichts“ sonst nur mit Reflow zu halten ist. Rail wäre ein Reflow-Risiko für Techtree/Karte.
2. **Kolonie mobil (390 px): standardmäßig ausgeklappt oder eingeklappt?** Das Panel deckt dort 29 % der Karte und fängt Klicks. Empfehlung: laut Vorgabe ausgeklappt starten, aber beim ersten Tile-Tippen automatisch einklappen und die Nutzerwahl merken; nach Playtest auf eingeklappt umstellen, falls es stört.
3. **Breite mobil 112 px / Desktop 132 px passend?** Empfehlung: erst so bauen und im Browser prüfen; Zahlen sind Startwerte.
4. **Tastaturkürzel (`R`) und Rand-Swipe** — gewollt? Empfehlung: Kürzel ja (klein, nur ohne fokussiertes Eingabefeld), Swipe zunächst nein (Kollision mit Zurück-Geste, Griff genügt).
5. **Merken pro Screen-Gruppe** (Kolonie / übrige) statt pro Route? Empfehlung: ja, nur `localStorage`-Komfort, Defaults gelten ohne.
6. **Sol-Chip in der Leiste behalten**, obwohl der SolButton in der Kopfzeile steht? Empfehlung: ja (Spielzeit ist die wichtigste Zahl; SolButton ist eine Aktion, nicht Anzeige) — gerade mobil, wo der SolButton laut heutigem CSS außerhalb der Kolonie ausgeblendet ist.
7. **Peek per Hover** (Desktop) gewünscht oder stört es am linken Rand? Empfehlung: ja mit 200 ms Verzögerung; falls es nervt, per Klick-only abschalten.
8. **Hint-Leiste dynamisch:** darf die Leiste mit dem Hint-Auftauchen springen (Annahme: ja, ohne Animation)? Empfehlung: ja.
9. **Techtree-Griff-Position:** oben links (Empfehlung) oder vertikal mittig, falls dort Bedienelemente liegen?
10. **Transparenz-Startwert 0,72 / Blur 8 px** übernehmen? Empfehlung: ja, danach mit echten Screenshots (Karte, Techtree) feinjustieren; Fallback fest/deckend bei reduzierter Transparenz bleibt Pflicht.
