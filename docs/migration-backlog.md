# Migration Backlog — Headless-Architektur (SvelteKit)

Stand: 2026-10-31  
Branch: `feat/headless-migration-prep`  
Kontext: Vorbereitung auf Auslagerung des Frontends in ein SvelteKit-Frontend (ADR 0006, Branch `ccr-48190b46-civgr7` als Konzeptbasis erhalten).

---

## Prämisse

Das Laravel-Backend bleibt canonical: Game-Logik, Tick-Verarbeitung, alle Services und die MySQL-8-Datenbank bleiben unverändert. Das SvelteKit-Frontend spricht gegen JSON-Endpunkte. Die bestehenden Blade-Views können parallel weiterlaufen, bis das SvelteKit-Frontend feature-complete ist.

---

## 1 — Pflicht-Checkliste: Was vor dem ersten API-Commit erledigt sein muss

Diese Tasks berühren das **Datenmodell oder die Spielmechanik** direkt. Wenn sie nach dem API-Entwurf kommen, entstehen Breaking Changes.

### 1.1 Blocking — muss committed sein (kein unfertiger Zustand)

| ID | Titel | Warum blocking | Empfehlung |
|----|-------|----------------|------------|
| **T42** | AP-Arten-Reste entfernen (`ap_type` in Configs, Controllern, Services, Views, Tests) | `ap_type` ist seit 2026-08-02 dead code, aber noch in `config/advisors.php`, `HangarService`, `OnboardingHintService`, `advisors.js`, `hangar.js` und Tests. Ein API-Entwurf mit `ap_type`-Feldern zwingt das SvelteKit-Frontend, totes Konzept umzusetzen. | Erst klären ob `ap_type` noch einen Codepfad steuert (wahrscheinlich nein), dann entfernen (TDD). Aufwand: Mittel |
| **T43-G7** | Organika-Modell finalisieren (`supply_per_eater = 1`) | Owner hat entschieden (2026-10-25): 1 Organika je Kolonist. Umsetzung und Produktions-Zahlenabgleich (T36) stehen noch aus. Solange `supply_per_eater = 4` im Code ist, stimmt jede Ressourcen-API-Response nicht mit dem Designziel überein. | Umsetzen nach Rückfrage Owner (Bot-Batch). Hängt an T36. Aufwand: Mittel–Groß |
| **T43-H10** | Ruinen-Pfad entscheiden (`mission_ruin_expedition`, `event_ruin`-Tiles ohne Generator) | Ghost-Routes und tote Mission-Keys ergeben im SvelteKit-Frontend leere/fehlerhafte Aktionskarten. Entweder jetzt sauber streichen oder als Stub mit `is_placeholder: true` markieren. | Entscheid: streichen (gemäß T31-Richtung, zweiter Harvester und Ruinen-Expeditionen geparkt) oder expliziter Stub. Aufwand: Klein |
| **T43-A3 / T36** | Organika-Rechnung (hängt an T43-G7) | Sol-Bericht und Resourcebar zeigen je nach `supply_per_eater`-Wert unterschiedliche Daten. Solange dieser Wert nicht final ist, ist jede API-Response zum Thema Organika/Versorgung vorläufig. | Zusammen mit T43-G7 lösen. |

### 1.2 Sauber committen oder sauber zurückrollen — kein halbfertiger Zustand

| ID | Titel | Status | Empfehlung |
|----|-------|--------|------------|
| **T43-H12** | Orin: neue Cantina-Mechanik festlegen | Orin ist Figur im Code, hat aber seit Entfernung des zweiten Harvesters keine Mechanik. Kann als `mechanic: null` dokumentiert in die API, aber game-designer sollte den Scope entscheiden. | Design-Entscheid (Klein), dann als explizites `TODO` in API-Kommentar wenn noch nicht implementiert. |
| **T39** | `lang/en`-Texte veraltet (AP-Typen, „Scientist", alte Hint-Keys) | Stale Keys im englischen i18n-File führen im SvelteKit-Frontend zu falschen englischen Labels. | Klein — lang/en-Dateien an lang/de angleichen. Blocker für i18n-API. |
| **T43-E4** | Labor-Tageslimit-Texte nach Merge von PR #414 | Keys tragen `// TODO content-writer`; nach Merge von #414 überarbeiten. | Klein — nach Merge von #414 erledigen. |
| **T43-H11** | `docs/game-reference.md` vollständig gegen Configs prüfen | Ressourcen-Tabelle, Harvester-Zeilen, Gebäude-Voraussetzungen. Kein Code-Blocker, aber API-Dokumentation-Qualität. | Klein — project-manager + qa-tester. |

### 1.3 Direkte Fixes (vor dem ersten Branch-Push empfohlen)

Diese TODOs stehen im eigenen Quellcode (nicht vendor) und können sofort behoben werden:

**`config/buildings.php`** — Vier Balance-TODOs (Zeilen 348, 370–373, 394–395):
- `trust_per_lv`, `event_mitigation_pct`, `recycle_pct`, `supply_cost` für Leitstelle noch nicht eingetragen.
- Merchant-Frequenz (Lv2-Marktplatz) und Run-End-Aktion (Lv3) sind als TODO markiert, weil das Merchant-System noch nicht existiert.
- CC-Gates für Marktplatz (Lv2→CC3, Lv3→CC5) sind noch nicht serverseitig geprüft.
- **Empfehlung:** Balance-Werte nach Messung eintragen; für Merchant-System und Run-End-Aktion explizit `// NOT YET IMPLEMENTED — see T33 / Phase 5` kommentieren.

**`lang/de/events.php` + `lang/en/events.php`** (Zeilen 17, 20):
- `TODO: trigger when a building first drops a status level` → Key `building_level_down`
- `TODO: trigger when colony trust first drops below threshold` → Key `trust_threshold_low`
- Beide Keys sind definiert aber nie gefeuert. **Empfehlung:** Als `// STUB: event key reserved, firing logic pending (T38)` markieren, damit API-Konsumenten wissen dass es sich um intentionale Stubs handelt.

**`lang/de/colony.php` + `lang/en/colony.php`** (Zeilen 788–789):
- `TODO content-writer: Lore klären (Fundnamen passen nicht zur Lore)`
- **Empfehlung:** Bleibt als TODO, ist in T43-E3 erfasst. Kein Blocker.

---

## 2 — API-Surface-Inventar (bestehende Routen → JSON-Endpunkte)

Die folgenden Blade-Routen müssen als JSON-API neu implementiert oder erweitert werden. Viele davon liefern bereits AJAX-Responses (Alpine.js-Calls), der Rest gibt HTML zurück.

### Bereits AJAX / partial JSON (Migrationspriority: Niedrig)

| Route | Controller | Typ |
|-------|-----------|-----|
| `GET /resources` | ResourcesController | JSON (Alpine.js resourcebar) |
| `GET /colony/buildings/available` | ColonyController | JSON |
| `GET /colony/building/leveldown-preview` | ColonyController | JSON |
| `GET /techtree` | TechtreeController | HTML (inlines JSON-Daten) |
| `GET /colony/hangar` | HangarController | HTML + AJAX |

### Brauchen JSON-Wrapper (Migrationspriority: Hoch)

| Route | Screen | Notizen |
|-------|--------|---------|
| `GET /colony/view` | Hexmap | Gibt Blade-Template; Tile-Daten, Gebäude-Status, AP-Budget müssen als JSON |
| `GET /colony/bar` | Cantina/Bar | Encounters, Merchant-Angebote, Figuren-State |
| `GET /colony/command-center` | Kommandozentrale | Dashboard (A1/D4 noch nicht implementiert) |
| `GET /run/{id}/result` | Run-Ergebnis | Statistiken, Ziele, Sieg/Niederlage |
| `GET /user/codex` | Charakter-Kodex | Figuren-Daten, permanent über Runs |
| `POST /colony/tile/explore` | Tile-Aktion | Response gibt derzeit HTML-Partial |
| `POST /colony/building/invest` | Invest | Response gibt JSON; prüfen ob vollständig |
| `POST /path-depth/{kind}/{key}/invest` | Pfad-Vertiefung (T41) | Neu (seit PR T41) — JSON-Format prüfen |

### Auth-Flow (Migrationspriority: Mittel)

- Login/Register/Logout: Standard Laravel — für SvelteKit braucht es entweder Session-Cookie-Auth (SPA-Mode) oder Token-Auth (Sanctum).
- Locale-Setting: `PATCH /user/settings/locale` bereits vorhanden.

---

## 3 — Migration Backlog: Features, die im SvelteKit-Frontend fertiggestellt werden

Alles hier ist **bewusst offen gelassen** — zu implementieren im neuen Frontend, nicht im Blade-Stack.

### 3.1 UI-Verbesserungen (direkt aus T43)

| ID | Beschreibung | Aufwand | Abhängigkeit |
|----|-------------|---------|-------------|
| **T43-C6** | Sidebar des freien Felds und der Gebäude optisch überarbeiten (Pkt 3, 7) | Mittel | — |
| **T43-C7** | Sol-Übersicht aufräumen; Details in Kommandozentrale ausklappbar | Mittel | — |
| **T43-E1** | Signal-Dialog „Signal entschlüsselt / Kleiner Fund" — Texte aufhübschen (Bild + Text) | Klein | content-writer |
| **T43-E2** | Texte `pathdepth.ui_*` überarbeiten (Labor-Texte, Gesprächsnamen in Cantina) | Klein | T43-G4 |
| **T43-E3** | Signal-Dialog `find_*_desc` — Lore klären, dann finaler Text + Bild | Klein | Lore-Entscheid |

### 3.2 Dashboard & Information Architecture

| ID | Beschreibung | Aufwand | Notizen |
|----|-------------|---------|---------|
| **A1 / D4** | Kommandozentrale-Dashboard: Restzeit je Baustelle, AP-Zufluss/-Verwendung, Regolith-Bilanz, Over-Cap-Warnung | Groß | Voraussetzung für D3 (Ratenmodell) |
| **R14** | Einstieg für neue Spieler — „So funktioniert ein Run"-Intro-Screen / Onboarding-Seite | Mittel | Kein Backend-Blocker |
| **R16** | UI-Durchgang: Stellen die auf nicht implementierte Mechaniken verweisen ausblenden oder als „in Arbeit" kennzeichnen | Klein | Nach Feature-Freeze vor Beta |
| **C17** | `gdd/onboarding.md` §16.5 Budget-Rechnung auf einen Pool und `ap.base = 12` — Hint-Texte nachziehen | Klein | — |

### 3.3 Game Features (Phase 5 — nach Beta)

| ID | Beschreibung | Aufwand | Design-Stand |
|----|-------------|---------|-------------|
| Progressive Discovery | Almanach, Objective-Enthüllung, Advisor-Dialoge (GDD §17) | Groß | Konzept in ROADMAP Phase 5 |
| Berater als Informationsebene | Decay-Prognosen, AP-Fluss, Händler-Einschätzung, Missionszeit | Mittel | Konzept GDD §13 |
| Kommandozentrale Detailgrad nach Beratern | Dashboard-Kacheln per Berater-Rang freischalten | Mittel | Owner-Idee 2026-10-23 |
| Cantina-Vereinbarungen (`treaty_signed`) | Vereinbarungen nach gelöstem Anliegen | Mittel | Konzept ROADMAP Phase 5 |
| Wartungsplan (Leitstelle) | Gebäude pro Sol vor Verfall schützen | Mittel | Design fehlt |
| Blockierte Tiles freiräumen (Geologie) | AP gegen Geröll-Tiles | Klein | Hängt an A44 |
| Berater-Vertiefung | Kandidaten-Auswahl, Traits, Portraits | Groß | Design fehlt |

### 3.4 Technische Schulden (nach Beta)

| ID | Beschreibung | Aufwand |
|----|-------------|---------|
| **T12** | Doppelter Codepfad Gebäude-Invest/-Reparatur in ColonyController | Mittel |
| **T27** | Rest-Altlasten im Schema prüfen (stale Spalten, tote Codepfade, RNG-Seed-Kollisionen) | Klein–Mittel |
| **D3** | `f(L)`-Kostenkurve statt flacher `ap_for_levelup` | Mittel |
| **D6** | Außenmissions-AP-Staffel, GDD Anhang B Drifts, ResetPlayer hartcodierte Werte | Klein |
| **D7** | Post-Phase-1-Ökonomie Credits-Einkommensquelle, Bar/Cantina Verkaufsrichtung | Mittel |
| **A7** | Nexus-Milestones verdrahten | Mittel |
| **A9** | Nexus-Boni ahead-of-curve | Klein |
| **A11** | Uplink-Station Lv2/Lv3 definieren | Mittel |
| **R21** | „Nexus-Vorschuss tilgen" als Siegziel | Mittel |

---

## 4 — Offene Pacing/Balance-Tasks (kein API-Blocker, aber Beta-relevant)

Diese Tasks blockieren die Headless-Migration nicht direkt, müssen aber vor der Closed Beta abgeschlossen sein:

| ID | Beschreibung | Nächster Schritt |
|----|-------------|----------------|
| **T43-B6** | Phase 2 beginnt zu früh (Sol 9); Pacing-Messung nötig | Skill `nouron-balance-pass` lesen, messen vor rechnen |
| **T40** | Vertrauens-Nachwirkung: `fade5`-Profil zuweisen, Deckelhöhe, Sol-Bericht | Bot-Batch nach Rückfrage Owner |
| **T41 P7** | Cantina: Stufenwirkung je Figur (game-designer) | Design-Schritt |
| **T43-H12** | Orin: neue Cantina-Mechanik festlegen | game-designer |
| **A45 / R2** | Run-Bogen Abnahme-Batch (Sieg-Median, alle Ziele erreichbar, keine Rg-Sackgasse) | Nach T9/T18/A44 |
| **T18** | Karten-Regolith um Sol ~44 erschöpft | Mit A44 Paket 2 (Geröll) |
| **T30** | Drei gleichwertige Eröffnungen: Referenzmessung nach Bergungs-Umbau | Owner: Laufzahl festlegen |
| **T31** | Regolith-Wirtschaft Budget-Modell: Werte-Paket, Hangar-Schürfflug, Cantina-Erzgäste | Nach Referenzmessung |

---

## 5 — Nicht wieder einführen (archiviert 2026-06-20)

Laut CLAUDE.md und `docs/gdd/archiv-flotten-systemkarte.md` dauerhaft gestrichen — kein Platz im SvelteKit-Frontend:

- Galaxie-/Systemkarte
- Flotten und Kommandanten
- INNN-Messaging

---

## 6 — Infrastruktur-Entscheide für das SvelteKit-Frontend ✅ entschieden 2026-10-31

| # | Frage | Entscheidung |
|---|---|---|
| 1 | Auth-Strategie | **Token** (Sanctum Bearer) — cross-domain sauber, direkte Basis für Capacitor |
| 2 | API-Versionierung | **`/api/v1/…` von Anfang an** |
| 3 | i18n-Ownership | **JSON-Endpoint** `GET /api/locale/{lang}`; `lang/de/*.php` bleibt canonical source |
| 4 | Real-time | **Polling** — kein Echo/Reverb für Beta |
| 5 | Asset-Host | **Eigener Subdomain `play.nouron.de`**; Laravel bleibt pure API |
| 6 | ADR-Basis | **ADR 0006** (`docs/adr/0006-frontend-svelte-pwa.md`) übernommen und angepasst |

Vollständige Begründungen und Migrationsplan → `docs/adr/0006-frontend-svelte-pwa.md`.
