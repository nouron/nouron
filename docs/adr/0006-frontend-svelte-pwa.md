# ADR 0006: Frontend-Umstellung auf SvelteKit (PWA) mit Laravel als API-Backend

**Datum:** 2026-10-19
**Status:** Angenommen (Owner-Entscheidung 2026-10-31; Umsetzung nach der Closed Beta, siehe ROADMAP)
**Bezug:** ADR 0003 (Mehrspieler-Vorentscheidungen), ADR 0005 (Hosting), `docs/frontend-conventions.md`, `docs/design-system/`

## Kontext

Nouron ist kein Ogame-artiges Browsergame mehr, sondern ein rundenbasiertes Singleplayer-Roguelike (Colony Management) mit Fokus auf **Mobile**. Das Frontend (Blade + Alpine.js + PicoCSS, ~40 Templates / ~7k Zeilen, ~4,7k Zeilen eigenes JS) wirkt wie eine Website: volle Seitenwechsel, keine Übergänge, verteilter Client-State. Gewünscht ist die Anmutung eines flüssigen, modernen Mobile Games.

**Owner-Vorgaben (2026-10-19):**
- Ein Komplett-Wechsel des Stacks ist zu aufwendig; das Laravel-Backend (Services, Tick-Logik, MySQL) bleibt.
- Auslieferung zunächst als **PWA**; Store-Release (Capacitor) evtl. später.
- **Offline-Spielbarkeit** wünschenswert, eine Übergangslösung ist akzeptabel.
- **Rundenbasiert**: der Spieler beendet die Runde (Sol) selbst. Multiplayer später evtl. denkbar (ADR 0003).

## Optionen

| | A: Blade/Alpine behalten, aufpolieren | B: Inertia + Svelte | C: SvelteKit-SPA/PWA + JSON-API |
|---|---|---|---|
| Mobile-Game-Gefühl | begrenzt (Reloads) | mittel (Page-Transitions, persistente Layouts) | hoch (Client-State, Gesten, Animationen) |
| Offline | nein | kaum | möglich (Shell sofort, Daten gestuft) |
| Aufwand | gering | mittel (Controller liefern Props statt Views) | hoch (API-Schicht, Auth, i18n) |
| Betrieb | unverändert | unverändert (ein Deploy) | ein Deploy möglich (statischer Build in Laravel `public/`) |
| Store-Weg (Capacitor) | umständlich | umständlich | direkt |

## Entscheidung (Vorschlag)

**Option C**: SvelteKit mit `adapter-static` (reine SPA, kein Node-Server in Produktion), als PWA, ausgeliefert von Laravel unter einem festen Pfad. Laravel wird zur JSON-API.

- **Deployment:** SvelteKit auf eigenem Subdomain `play.nouron.de` (Vercel/Cloudflare Pages oder separater Laravel-Cloud-Dienst); Laravel bleibt pure API unter `api.nouron.de` oder `nouron.de/api`. Unabhängige Deploys, kein Build-Timeout-Risiko durch kombinierten Build. Gleiche SvelteKit-Codebasis wraps direkt in Capacitor. (Owner-Entscheidung 2026-10-31)
- **Auth:** Laravel Sanctum Token-Auth (Bearer-Token, kein Cookie-Session-Modus). Begründung: SvelteKit läuft auf eigenem Subdomain (`play.nouron.de`), Token-Auth ist cross-domain sauber und ist die direkte Basis für den späteren Capacitor-Build — kein zweiter Auth-Umbau nötig. (Owner-Entscheidung 2026-10-31)
- **API:** bestehende Controller-Logik bleibt in den Services; neue dünne API-Controller unter `/api/v1/…` liefern/akzeptieren JSON. Bestehende AJAX-Contracts (`docs/frontend-conventions.md`) sind die Ausgangsbasis. Blade-Routen bleiben bis zur Ablösung parallel lauffähig.
- **Rundenmodell:** `POST /sol/next` bleibt die einzige zustandsverändernde Rundenaktion; der Server bleibt maßgeblich für Spielstand und RNG (`runs.rng_seed`). Das hält die Option offen, ADR 0003 (simultane Auflösung) später ohne Client-Umbau umzusetzen.
- **Offline (gestuft):**
  1. *Stufe 1 (Übergang, mit PWA):* App-Shell und statische Assets per Service Worker gecacht; Lesezugriff auf den zuletzt geladenen Zustand; Aktionen offline gesperrt mit klarem Hinweis.
  2. *Stufe 2 (optional):* Aktionswarteschlange (Outbox), die beim Reconnect in Reihenfolge gesendet wird; Server validiert alles erneut. Voraussetzung: idempotente Aktions-Endpunkte (Request-ID).
  3. *Stufe 3 (nur falls nötig):* Tick-Logik clientseitig spiegeln. **Nicht empfohlen** (doppelte Spiellogik PHP/JS, Drift-Risiko, Balance-Config zweifach); vorerst ausgeschlossen.
- **UI-Basis:** Design-System (`docs/design-system/`) bleibt verbindlich; Tokens werden als CSS-Variablen übernommen. PicoCSS kann bleiben oder entfallen (separate Entscheidung im Pilot). Animationen über Svelte-Transitions/CSS, SVG-Spielfelder als Svelte-Komponenten.
- **i18n:** `lang/de/*.php` bleibt Single Source; ein Export-Befehl erzeugt JSON für das Frontend. Keine hartcodierten Texte (Regel aus CLAUDE.md gilt weiter).

## Migrationsplan (Screen für Screen)

| Stufe | Inhalt |
|---|---|
| 0 | Spike (zeitboxiert): SvelteKit-Projekt in `frontend/`, Sanctum-SPA-Login, Build-Integration in Laravel Cloud, PWA-Manifest |
| 1 | **Pilot: Techtree** (SVG, interaktiv, bereits Canvas mit Daten per `pageData`); misst Aufwand, Gefühl, Performance auf echten Handys |
| 2 | App-Shell: Layout, Navigation, Ressourcenleiste, Sol-Button, Hinweisleiste, Transitions |
| 3 | Kolonie-Hexfeld (`colony-hexgrid.js`), Gebäude-Details/Ausbau |
| 4 | Berater, Hangar, Bar/Cantina, Kommandozentrale, Codex, Run-Lobby/Ergebnis |
| 5 | Auth-/Nebenseiten (Login, Registrierung, Einstellungen), Blade-Abbau |
| 6 | Offline Stufe 1/2, Feinschliff (Haptik, Sounds, Gesten) |

Nach jeder Stufe läuft das Spiel vollständig (Blade und Svelte koexistieren über Routen-Präfix). Tests: API-Endpunkte per PHPUnit (TDD wie üblich); Frontend-Komponenten per Vitest, Kernflüsse per Playwright.

## Risiken

- **Doppelte Pflege** während der Koexistenz (Blade + Svelte) – daher kurze Stufen, harte Abschaltliste.
- **API-Fläche:** ~76 Routen, davon ca. die Hälfte noch ohne JSON-Variante; Fehler-/Validierungsformat muss einheitlich werden (Contract in `frontend-conventions.md` ergänzen).
- **Beta-Fokus:** Umstellung darf die Closed-Beta-Prioritäten (ROADMAP P0) nicht verdrängen; Start frühestens nach Beta-Start, Spike ausgenommen.
- **Sicherheit:** CSRF/Cookie-Konfiguration (`SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`) sauber setzen; Service-Worker darf keine authentifizierten Antworten fremder Nutzer cachen.
- **Hosting:** Static-Build in `public/` über Laravel Cloud-Build prüfen (Build-Timeout 15 Min, ADR 0005).

## Entschiedene Punkte (2026-10-31, Owner)

| # | Frage | Entscheidung |
|---|---|---|
| 1 | Auth-Strategie | Token (Sanctum Bearer), kein Cookie-SPA-Modus |
| 2 | API-Versionierung | `/api/v1/…` von Anfang an |
| 3 | i18n-Ownership | `lang/de/*.php` bleibt Backend; `GET /api/locale/{lang}` liefert JSON ans Frontend |
| 4 | Real-time | Polling (kein Echo/Reverb); Sol-basiertes Spiel braucht kein Push für Beta |
| 5 | Asset-Host | SvelteKit auf eigenem Subdomain `play.nouron.de`; Laravel bleibt pure API |
| 6 | ADR-Basis | Dieser ADR (Branch `ccr-48190b46-civgr7`) wird als Basis übernommen |

## Offene Punkte

1. Entscheidung PicoCSS behalten/ersetzen (im Pilot, Stufe 0).
2. Zeitpunkt des Spikes relativ zur Closed Beta (R4) — frühestens nach Beta-Start.
3. Store-Release (Capacitor) — erst nach Stufe 4 zu bewerten.
4. Domain-Konfiguration CORS (`SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN` entfällt bei Token-Auth).
