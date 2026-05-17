# Nouron — Roadmap

## Phase 1b: Laminas → Laravel Migration

> **Status: Abgeschlossen (April 2026)** — Die vollständige Migration von Laminas MVC auf Laravel 12 wurde durchgeführt. Alle Module, Services, Controller, Views und Tests wurden migriert. Die App läuft auf Laravel mit Eloquent, Blade und Laravel Auth. Der folgende Plan dient als historisches Referenzdokument.

**Ziel:** Schrittweise Migration des gesamten Projekts von Laminas MVC auf Laravel.
**Prinzip:** Modul für Modul, Test-Suite muss vor und nach jedem Schritt grün sein.
**Kein Big Bang** — die App bleibt während der Migration lauffähig.

---

### Bestandsaufnahme (Analyse-Ergebnis)

| Kenngröße | Wert |
|---|---|
| PHP-Dateien | 373 |
| Module | 11 |
| Controller | ~18 |
| Services | ~13 |
| Table-Klassen (TableGateway) | 31 |
| Entities | ~43 |
| Factory-Klassen (DI) | 94 |
| View-Templates (.phtml) | 36 |
| Test-Dateien | 108 |
| Bekannte TODOs | 26 |

**Komplexität der Module (absteigend):**
1. Techtree — 35 Factories, 13 Tables, 17 Entities, 6 Services
2. Fleet — 15 Factories, 6 Tables, 10 Entities
3. INNN — 11 Factories, 2 Tables, 3 Entities
4. Galaxy / Trade / Resources — je 8 Factories
5. User / Colony / Application / Core / Map — einfacher

---

### Migrationsstrategie

#### Konzept: Feature-Folder statt Module
Laravel kennt keine Module im Laminas-Sinne. Empfohlene Struktur:

```
app/
  Http/
    Controllers/
      Colony/
      Fleet/
      Galaxy/
      INNN/
      Resources/
      Techtree/
      Trade/
      User/
  Models/          ← Eloquent (ersetzt TableGateway + Entity)
  Services/        ← bleibt, nur DI anders
  Providers/       ← ersetzt Factories + Module.php
resources/
  views/           ← Blade (ersetzt .phtml)
routes/
  web.php          ← ersetzt module.config.php-Routing
database/
  migrations/      ← neue Migration-Dateien
  seeders/
tests/
  Feature/
  Unit/
```

#### Laminas → Laravel Mapping

| Laminas | Laravel-Äquivalent |
|---|---|
| `module.config.php` Routing | `routes/web.php` |
| `Module.php` + Factory | Service Provider + `AppServiceProvider` |
| `TableGateway` + `AbstractTable` | Eloquent `Model` |
| `ClassMethods` Hydrator | Eloquent Model Casts / Accessors |
| `Core\Model\ResultSet` | Eloquent Collection |
| `IngameController::getServiceLocator()` | Constructor Injection |
| `AbstractService::getTable()` | Service mit injiziertem Repository/Model |
| `LmcUser` + `zfcrbac` | Laravel Auth + Gates/Policies |
| `ViewJsonStrategy` | `return response()->json(...)` |
| `.phtml` + View Helpers | Blade + Blade Directives / Components |
| `$this->url('route')` | `route('name')` |
| `$this->partial()` | `@include` |
| `laminas/laminas-form` | Laravel Form Requests / Validation |
| PHPUnit + `laminas-test` | PHPUnit + `Illuminate\Foundation\Testing` |

---

### Schritt-für-Schritt Migrationsplan

---

#### Schritt 0: Laravel-Projekt aufsetzen
- [ ] `composer create-project laravel/laravel` im Branch `laravel_migration`
- [ ] `composer.json` zusammenführen (PHP ≥8.2, bestehende Non-Laminas-Deps)
- [ ] SQLite als Standard-DB konfigurieren (`database/database.sqlite` oder `data/db/nouron.db`)
- [ ] `.env` für Dev und Test konfigurieren (zwei separate DB-Dateien)
- [ ] PHPUnit-Konfiguration anpassen (`phpunit.xml`)
- [ ] CI-fähigen Basis-Test aufsetzen: `php artisan test` muss laufen (0 Tests, 0 Failures)
- [ ] `public/index.php` ersetzen (Laravel Entry Point)
- [ ] Vorhandene statische Assets (`public/js/`, `public/css/`) übernehmen

---

#### Schritt 1: Datenbank-Schema migrieren
- [ ] `data/sql/schema.sqlite.sql` in Laravel-Migrations übersetzen (eine Datei pro Tabelle)
- [ ] Tabellen-Reihenfolge beachten (Foreign Keys: `user` → `glx_*` → `glx_colonies` → `colony_*` usw.)
- [ ] `PRAGMA foreign_keys = ON` in SQLite-Connection konfigurieren (`config/database.php`)
- [ ] `database/seeders/TestSeeder.php` aus `data/sql/testdata.sqlite.sql` erstellen
- [ ] `database/seeders/DevSeeder.php` aus `data/sql/data.sqlite.sql` erstellen
- [ ] `php artisan migrate` und `php artisan db:seed` testen

---

#### Schritt 2: Core-Schicht — Basis-Abstraktion
- [ ] `Core\Service\Tick` → Laravel Service `App\Services\TickService` (aus `config/game.php`)
- [ ] `Core\Table\AbstractTable` → Eloquent `Model` Basisklasse (sofern nötig; oft direkt Eloquent)
- [ ] `Core\Controller\IngameController` → Laravel `BaseController` mit Auth-Helper
- [ ] `getActive('user')` Controller-Plugin → Auth-Facade (`Auth::id()`) oder Middleware
- [ ] Custom `ResultSet` → Eloquent `Collection` (kein Ersatz nötig)
- [ ] `AbstractService` Hilfsmethoden (`_validateId`, `getTick`) in Trait oder Basisklasse
- [ ] `config/autoload/global.php` → `config/game.php` (tick, balance values)

---

#### Schritt 3: Authentifizierung — User-Modul
*Empfohlen als erstes vollständiges Modul, da alle anderen Module Auth voraussetzen.*

- [ ] Laravel Auth installieren (`php artisan make:auth` / Laravel Breeze ohne Frontend)
- [ ] `User\Entity\User` → Eloquent `App\Models\User` (Felder: username, email, bcrypt password, race_id, faction_id)
- [ ] Bestehende bcrypt-Passwörter sind Laravel-kompatibel (kein Reset nötig)
- [ ] `lmcuser.global.php` → Laravel Auth Config (`config/auth.php`)
- [ ] `zfcrbac.global.php` → Laravel Gates/Policies (admin/player/guest Rollen)
- [ ] `UserController`, `SettingsController`, `ContactsController` → Laravel Controller
- [ ] Login-Template (`zfc-user-mod/login.phtml`) → Blade-Template
- [ ] Routen: `/user/*` → `routes/web.php`
- [ ] Tests: User-Tests auf `Illuminate\Foundation\Testing\TestCase` umschreiben

---

#### Schritt 4: Colony-Modul
*Kleinstes Spielmodul, guter Einstieg für das TableGateway→Eloquent-Muster.*

- [ ] `Colony\Entity\Colony` → Eloquent `App\Models\Colony`
  - Relationen: `belongsTo(User)`, `hasMany(ColonyBuilding)`, `hasMany(ColonyResource)`, etc.
- [ ] `Colony\Table\ColonyTable` → aufgelöst in `Colony`-Model + Repository (optional)
- [ ] `Colony\Service\ColonyService` → `App\Services\ColonyService` (Constructor Injection)
- [ ] 3 Colony-Factories → Service Provider Binding
- [ ] Routing für Colony-Aktionen in `routes/web.php`
- [ ] Colony-Tests umschreiben (3 Dateien)

---

#### Schritt 5: Resources-Modul
*JSON-API-Endpunkt — zeigt Laravel JSON Response Pattern.*

- [ ] `Resources\Entity\*` → Eloquent Models (`Resource`, `UserResource`, `ColonyResource`)
- [ ] `Resources\Service\ResourcesService` → `App\Services\ResourcesService`
- [ ] `Resources\Controller\JsonController` → Laravel Controller mit `return response()->json(...)`
- [ ] Resource Bar View (`reloadresourcebar.phtml`) → Blade-Partial
- [ ] Routing (`/resources/json/*`) in `routes/web.php` (API-Gruppe)
- [ ] Resources-Tests umschreiben (2 Dateien)

---

#### Schritt 6: Galaxy-Modul
*Zeigt Read-only-Abfragen und komplexe Views.*

- [ ] `Galaxy\Entity\{System, SystemObject, Colony, ...}` → Eloquent Models mit Relationen
- [ ] `Galaxy\Table\{SystemTable, SystemObjectTable}` → aufgelöst
- [ ] `Galaxy\Service\GatewayService` → `App\Services\GalaxyService`
- [ ] 3 Galaxy-Controller → Laravel Controller
- [ ] 3 Views (index, system, layer-switch) → Blade
- [ ] Routen in `routes/web.php`
- [ ] Galaxy-Tests umschreiben (10 Dateien)

---

#### Schritt 7: INNN-Modul (Nachrichten & Ereignisse)
*Zeigt das v_innn_messages View-Pattern und Soft-Delete-ähnliches Marking.*

- [ ] `innn_messages` View (`v_innn_messages`) → Eloquent Scope oder Raw Query
- [ ] `INNN\Entity\{Message, Event, News}` → Eloquent Models
  - `Message`: snake_case Felder (`is_read`, `is_archived`, `is_deleted`)
  - Scopes: `scopeInbox()`, `scopeOutbox()`, `scopeArchived()`
- [ ] `INNN\Service\MessageService` → `App\Services\MessageService` (snake_case beibehalten!)
- [ ] `INNN\Service\EventService` → `App\Services\EventService`
- [ ] 3 INNN-Controller → Laravel Controller
- [ ] 5 Templates → Blade
- [ ] Flash Messenger (aktuell broken) → Laravel `session()->flash()` / `with()`
- [ ] Routen in `routes/web.php`
- [ ] INNN-Tests umschreiben (12 Dateien)

---

#### Schritt 8: Trade-Modul
*Zeigt das Formular-Pattern (Angebote hinzufügen).*

- [ ] `Trade\Entity\{TradeResource, TradeResearch, ...}` → Eloquent Models
- [ ] `Trade\Table\{TradeResourceTable, TradeResearchTable}` → aufgelöst
- [ ] Trade-Controller → Laravel Controller
- [ ] Trade-Forms (`SearchForm`, `NewOfferForm`) → Laravel Form Requests mit Validation
- [ ] 4 Templates → Blade
- [ ] Routen in `routes/web.php`
- [ ] Trade-Tests umschreiben (14 Dateien)

---

#### Schritt 9: Fleet-Modul
*Zweikomplexestes Modul — serialisierte fleet_orders.data besonders beachten.*

- [ ] `Fleet\Entity\{Fleet, FleetShips, FleetPersonell, ...}` → Eloquent Models mit Relationen
- [ ] `fleet_orders.data` (serialisierte PHP-Arrays) → JSON-Feld oder Cast (`castable`)
- [ ] `Fleet\Service\FleetService` → `App\Services\FleetService`
  - Bug: `ap_spend` manuell gelöscht (TODO-Kommentar) → sauber lösen
  - Bug: `TODO: Exception` statt return [] (Zeile 675) → lösen
- [ ] Fleet-Controller (Index, Config) → Laravel Controller
- [ ] Fleet-Forms → Form Requests
- [ ] 4 Templates (`fleets.js` bleibt, nur Template-Änderungen) → Blade
- [ ] Routen in `routes/web.php`
- [ ] Fleet-Tests umschreiben (18 Dateien)

---

#### Schritt 10: Techtree-Modul
*Komplexestes Modul — zuletzt migrieren.*

- [ ] `AbstractTechnologyService` → abstrakte Laravel-Basisklasse mit Constructor Injection
  - Locked-DB-Bug in Tests (auskommentiert) → sauber lösen mit Transactions
- [ ] 6 Services → Laravel Services (Building, Research, Ship, Personell, Colony, Abstract)
- [ ] 13 Tables → Eloquent Models (inkl. Colony-Varianten und Costs-Tabellen)
- [ ] 17 Entities → aufgelöst (Eloquent ersetzt Entity + Table)
- [ ] 35 Factories → Service Provider Bindings (drastische Reduktion)
- [ ] AP-System: `locked_actionpoints` → Eloquent Model + `PersonellService`
- [ ] Prerequisite-Checks (`checkRequiredBuildings` etc.) → Service-Methoden (1:1 übernehmen)
- [ ] 3 Techtree-Controller → Laravel Controller
- [ ] 10 Templates (inkl. AJAX-Modals) → Blade
  - `setTerminal(true)` → `return view('partial')` ohne Layout
- [ ] Routen (komplex, mit nested Segments) → `routes/web.php`
- [ ] Techtree-Tests umschreiben (40 Dateien — größter Block)

---

#### Schritt 11: Application-Modul & Navigation
*Layout, Navigation, Error-Pages — letzter Schritt.*

- [ ] `layout.phtml` → `resources/views/layouts/app.blade.php`
  - jQuery-Post-Processing für Navigation entfernen (Blade Components direkt rendern)
  - Bootstrap 5 CDN-Links beibehalten
- [ ] Laminas Navigation Helper → Blade-Komponente oder einfaches Array-gestütztes Nav-Partial
- [ ] Error-Pages (404, 500) → Laravel Error-Pages (`resources/views/errors/`)
- [ ] Pagination → Laravel Paginator mit Bootstrap 5 Preset
- [ ] `Application\Module::onBootstrap()` (Event Listeners) → Laravel Middleware
- [ ] `config/application.config.php` → `config/app.php` (kein Modul-System mehr nötig)

---

#### Schritt 12: Tests & Abschluss
- [ ] Alle 108 Test-Dateien sind auf `Illuminate\Foundation\Testing\TestCase` umgeschrieben
- [ ] `AbstractServiceTest::initDatabase()` Muster → `RefreshDatabase` Trait + Seeder
- [ ] `php artisan test` läuft durch (Ziel: gleiche Abdeckung wie PHPUnit 9.5 mit 261 Tests)
- [ ] Laminas-Pakete aus `composer.json` entfernen
- [ ] `lmcuser` / `zfcrbac` / `firephp` entfernen
- [ ] `CLAUDE.md` Techstack aktualisieren (Laravel statt Laminas)
- [ ] README.md aktualisieren

---

### Bekannte Risiken & offene TODOs (aus Code-Analyse)

| Problem | Ort | Aufwand |
|---|---|---|
| Flash Messenger broken | INNN\Controller\MessageController | Mittel |
| `ap_spend` manuell gelöscht | Fleet\Service\FleetService:328 | Klein |
| Locked DB-Errors in Tests | AbstractTechnologyService (auskommentiert) | Mittel |
| `$colony->save()` nicht implementiert | Colony\Service\ColonyService:76 | Klein |
| fleet_orders.data serialisierte PHP-Arrays | fleet_orders Tabelle | Mittel |
| Flash Messenger: $type nicht implementiert | INNN\Controller\MessageController:159 | Klein |
| ResourcesController: colonyId via Session | Resources\Controller\JsonController:51 | Klein |

---

### Nicht migrieren (beibehalten / extern)

| Was | Warum |
|---|---|
| `public/js/` (techtree.js, fleets.js, galaxy.js, trade.js) | Framework-unabhängig, bleibt unverändert |
| `public/css/` | Framework-unabhängig |
| `data/db/nouron.db` | SQLite-Datei, nur Pfad in `.env` anpassen |
| `data/sql/schema.sqlite.sql` | Wird in Schritt 1 in Migrations überführt |

---

## Phase 2: Spielablauf stabilisieren
*(nach Abschluss Phase 1b)*

**Designklarstellungen:**
- Jeder Spieler hat genau **eine Kolonie** — kein Kolonisierungsfeature
- Kämpfe finden ausschließlich als **PvP-Schiffskämpfe** statt (Schiffe vs. Schiffe)
- Alle anderen Interaktionen (Gebäude, Forschung, Produktion, Handel) sind **PvE** (Player vs. Environment)
- Es gibt keine Angriffe auf Kolonien

---

### Prio 1: Kritische Bugs beheben

| Problem | Ort | Status |
|---|---|---|
| ~~`PersonellService::hire` — `$this->resourcesService` nicht deklariert → Fatal Error wenn `dev_mode=false`~~ | ~~`app/Services/Techtree/PersonellService.php`~~ | Behoben (PR #66) |

---

### Prio 2: Fehlende UI für vorhandene Services

Die folgenden Services sind implementiert, aber ohne UI — Spieler können diese Funktionen nicht nutzen:

- [x] **Advisor-Management-UI** — `/advisors` mit hire/fire, 4 Typ-Cards, AP-Summen, Supply-Kosten
- [x] **Colony-UI** — `/colony` mit Kolonie-Übersicht und Umbenennung (PATCH `/colony/name`)
- [x] **Forschungshandel-View** — `/trade/researches` implementiert; Ressourcenhandel `/trade/resources` ebenfalls überarbeitet (Chips, Restriktions-Badges, Farbcodierung)
- [x] **User-Profil / Einstellungen** — Passwort, Display Name und weitere Einstellungen implementiert

---

### Prio 3: Spielmechaniken vervollständigen

- [x] **`moving_speed` für Schiffe gesetzt** — `config/ships.php` enthält nun Werte (4/3/2/3/2/1); `FleetService::calcFleetSpeed()` war bereits korrekt implementiert
- [x] **`game:sync-techs` implementiert** — `app/Console/Commands/SyncTechs.php`; synct moving_speed, decay_rate, supply_cost, max_status_points aus config in ships/buildings-Tabellen; `--dry-run` Option vorhanden
- [x] **Laravel Scheduler eingerichtet** — `routes/console.php`: `Schedule::command('game:tick')->dailyAt('03:00')`
- [x] **Fleet-Orders im UI vervollständigt** — `hold`, `convoy`, `defend`, `join` sind im Validator, Controller und Blade-View mit Lokalisierung implementiert; AP-Kosten in `config/game.php` ergänzt
- [x] **Flotten auf Galaxiekarte** — `getMapData()` liefert Layer-3-Einträge für alle Flotten im Sichtbereich; eigene Flotten grün, fremde gelb; galaxy.js rendert bereits korrekt
- [x] **Galaxy-Koordinaten-Skalierung geprüft** — System-Radius 50 Einheiten; Speed 4 durchquert in ~12 Ticks, Speed 1 in ~50 Ticks — Unterschied ist für Spieler deutlich spürbar, keine Anpassung nötig

---

### Prio 4: Spielablauf testen & stabilisieren

- [ ] Tick-System und `fleet_orders`-Verarbeitung End-to-End testen
- [ ] AP-System vollständig testen (Vergabe, Verbrauch, Moral-Multiplikator)
- [ ] Handelsrouten (Ressourcen + Forschungen)
- [ ] Flottenoperationen (Bewegung, PvP-Schiffskampf)
- [ ] Flash-Messenger in Formularen
- [ ] Login/Registrierung und Auth-System

---

### Bekannte Lücken (kein Code vorhanden)

| System | Beschreibung |
|---|---|
| **Politiksystem / Diplomatie** | `innn_message_types.relationship_effect` ist im Schema vorhanden, wird aber nirgends ausgewertet. Allianz/Krieg/Frieden: keine Logik. Moral-Events `war_declared` und `treaty_signed` sind in `config/game.php` definiert, aber nie gefeuert. |
| **Aktionslog** | Kein persistentes Log über Spieleraktionen (Gebäude gebaut, Flotte bewegt, Handel abgeschlossen etc.) — weder im Backend noch im UI. |

---

## Phase 3: "Das Spiel zeigen" — Abgeschlossen (Mai 2026)
*(nach Phase 2)*

**Ziel:** Das Spiel ist für externe Spieler zugänglich, verständlich und rund.

Dieser Schnitt macht Sinn, weil Phase 2 die Mechaniken implementiert und stabilisiert, Phase 3 aber das Spiel für Menschen lesbar und spielbar macht, die keinen Entwicklerhintergrund haben. Ohne diesen Schritt ist kein sinnvoller Playtest mit echten Spielern möglich — und ohne Playtest-Feedback sind Phase-4-Entscheidungen (Diplomatie, Rassen, Gruppen) zu unsicher, um sie zu committen.

---

### Phase 3a: Design-Sprint — Abgeschlossen (April 2026)

Alle drei Design-Themen wurden entschieden und im GDD dokumentiert (PRs #78, #79, #80 gemergt).

- [x] **Kenntnisse-System redesignt** — Freischalt-Techtree (permanent, kein Decay); Dual-Effekt-Modell (Primär/Sekundär); Berater-Zuweisung mit Slots nach Rang; 7 Kenntnisse, Roguelike-Variabilität → PR #78
- [x] **Handel redesignt** — Bar als einziger Handelsort (0–2 Gäste/Tick); Nexus-Handelsschiffe als Fallback; Regolith als neue Ressource (lokal abbaubar); Werkstoffe nur via Handel/Events → PR #79
- [x] **Flottenbewegung redesignt** — interstellare Bewegung nicht implementiert; Flotten im eigenen System; Sprungtor als narratives Element → PR #80

---

### Phase 3a: Implementierung (Design-Sprint-Ergebnisse)

> **Stand PR #82 (April 2026):** Kern-Balancing und Ressourcensystem vollständig implementiert.

- [x] **Regolith als neue Ressource eingeführt** — resource_id 3, Startwert 200, Harvester produziert Regolith, OnboardingService angepasst (PR #81)
- [x] **Tradecenter entfernt** — config, MasterDataSeeder, Migration, Lang-Dateien, testdata; Trader + Wirtschafts-Forschung erfordern jetzt Bar (PR #81)
- [x] **Ressourcen umbenannt** — Ferum → Werkstoffe (Co), Silikate → Organika (Or); beide starten bei 0 (PR #82)
- [x] **Kenntnisse-System implementiert** — 7 Typen (IDs 90–96), kein Decay, steigende AP-Kosten per Level (5/10/18/28/40), Supply-Cap-Bonus; `ResearchService.resolveApForLevelup()` Hook (PR #82)
- [x] **Gebäude-Balancing kalibriert** — ap_for_levelup (CC=10, Standard=20, High-Tech=30), Regolith als Baukosten für alle Gebäude außer CC+Harvester (PR #82)
- [x] **Schiffssystem redesignt** — Sonde (85) in DB eingeführt; Korvette (37) + Frachter (47) umbenannt; Schiffskosten: Credits + Werkstoffe + Organika; deprecated ships costs entfernt (PR #82)
- [x] **Passive Credits + Berater-Upkeep** — GameTick: Nexus-Subvention 30 Cr/Tick + Kolonistensteuern 20 Cr/Tick pro Housing-Level; Upkeep 10/50/160 Cr je Rang (PR #82)
- [x] **Startzustand** — CC Lv1 + Harvester Lv1 vorgebaut; 3.000 Credits, 200 Regolith, 0 Werkstoffe/Organika (PR #82)
- [x] **Berater-Einstellungskosten kalibriert** — 50 Cr → 300–600 Cr je Typ; echter Day-1-Tradeoff (PR #82)
- [x] **Bar-Event-System** — 0–2 NPC-Gäste pro Tick, befristete Angebote (2 Ticks), Credits + Tausch; Konsul-Rang steuert Anzahl und Preise (PR #114)
- [x] **DB-Cleanup: überzählige Gebäude entfernt** — 25 → 11 aktive Gebäude; `building_*`-Keys eingeführt; Migration + Seed bereinigt (PR #92)
- [x] **Berater Rang 2/3 Beförderungskosten** — 150/400 Cr je Rang; Beförderung verschoben bei fehlenden Credits (PR #114)

---

### Phase 3b: Colony-View + Buildings-Cleanup — Abgeschlossen (April 2026, PR #92)

**Frontend-Stack:** Alpine.js + PicoCSS + SVG für neue Screens. Bestehende Screens (fleets, techtree, trade, innn) werden schrittweise migriert.

- [x] **Alpine.js + PicoCSS eingebunden** — Colony-Layout `layouts/colony.blade.php`; bestehende `app.blade.php` vorerst unangetastet
- [x] **DB-Migrationen** — `colony_tiles` (Hex-Grid, Rings, Fog-of-War), `instance_id` + `tile_x/y` auf `colony_buildings`, `planet_size/type` auf `glx_system_objects`
- [x] **Colony-View (Hex-Grid)** — SVG + Alpine.js, Axial-Koordinaten, Fog-of-War, Tile-Sidebar, Building-Badges, Signal-Indikator (PR #92)
- [x] **Demo-Seed** — `php artisan colony:seed-demo` befüllt Kolonie mit ~80%-Demo-State
- [ ] **System-View (12×12-Grid)** — SVG + plain JS, Objekte und Flotten, Flottenbefehl-Overlay
- [ ] **Vertrauensanzeige im UI** — Mechanik vorhanden, UI fehlt noch
- [ ] **Händler-Modal** — Alpine-gesteuert, nativer `<dialog>`, 3–4 Items, Credits-Kauf
- [ ] **Ingame-Almanach** — Nachschlagewerk für Gebäude, Forschungen, Schiffstypen; Blade-Seite mit Config-Daten
- [x] **jQuery-Migration (Schritt 1)** — galaxy.js, nouron.js, innn.js auf Vanilla JS migriert; techtree.js + leader-line.min.js aus layouts.app entfernt (dead code); Inline-$(document).ready → DOMContentLoaded
- [x] **jQuery-Migration (Schritt 2)** — fleets.js und trade.js auf Vanilla JS/fetch migriert; jQuery, bootbox, growl aus layouts.app entfernt; jQuery vollständig aus dem Projekt entfernt

---

### Phase 3c: Kolonieaktionen — Abgeschlossen (April 2026, PR #93)

- [x] **Erkunden** — unbekannte Exploration-Zone-Tiles aufdecken (1 Nav-AP); kontextsensitiver Button in Sidebar
- [x] **Sondieren (Deep Scan)** — Signal-Tiles mit Event untersuchen (2 Nav-AP); pulsierender SVG-Indikator
- [x] **Bauen** — globaler Button im Canvas-Header; Gebäude-Auswahlliste; Terrain-Tile wählen (1 Construction-AP); AP investieren bis Level-Up
- [x] **AP-Chips** — Nav-AP und Bau-AP werden nach jeder Aktion live aktualisiert

---

### Phase 3d: Colony Zone Expansion — Abgeschlossen (April 2026, PR #94 + PR #95)

- [x] **Tile-Count Unlock** — CC Lv1–5 schaltet 4/2/3/3/3 = max. 15 individuelle Terrain-Tiles frei (statt ganzer Ringe); konfigurierbar via `config/game.php → colony_zone_expansion`
- [x] **`is_ring_unlocked` → `is_colony_zone`** — DB-Umbenennung; Semantik: Terrain-Tile in Koloniezone (bebaubar)
- [x] **3-Ring-Karte als Default** — 37 Tiles statt 61; Kartengröße run-konfigurierbar (vorbereitet)
- [x] **CC Level-Up live** — Grid aktualisiert sich sofort wenn CC aufsteigt
- [x] **Mehrfach-Instanzen** — Wohnhabitat (max 6×) und Hangar mehrfach platzierbar

---

### Phase 3e: Onboarding & New-Player Experience — Abgeschlossen (Mai 2026)

GDD-Referenz: § 15 (Designprinzipien, §15.1–§15.7)

**Kernprinzipien (GDD § 15):** Lernen durch Tun — kein Pflicht-Tutorial — erfahrene Spieler nicht bevormunden — minimaler Implementierungsaufwand.

#### Schritt 1 — Infrastruktur & Konfiguration

- [x] [db-migration-agent] `user_preferences`-Tabelle + `onboarding_hints`-Spalte (2 Migrationen)
- [x] [game-developer] `config/game.php → onboarding`-Block: 5 Schwellwerte (`hint_supply_cap_threshold`, `hint_no_engineer_ticks`, `hint_no_knowledge_after_tick`, `hint_trust_threshold`, `hint_trust_min_ticks`)
- [x] [backend-coder] `UserController::updateOnboardingHints()` + Route `PATCH /user/settings/onboarding` + Toggle in `settings.blade.php`

#### Schritt 2 — Nexus-Briefing (§ 15.1)

- [x] [content-writer] Finalen Nachrichtentext für das Nexus-Briefing formulieren — `lang/de/colony.php → onboarding_nexus_briefing_title/body` (karg, lakonisch, Frontier-Ton)
- [x] [game-developer] `EventService::createNexusBriefing()` mit idempotent guard; `OnboardingService::setupNewPlayer()` ruft `createNexusBriefing()` — Event beim Erzeugen eines neuen Runs automatisch angelegt
- [x] [qa-tester] 6 Tests in `NexusBriefingTest.php` grün

#### Schritt 3 — Hint-System (§ 15.2)

- [x] [game-developer] `OnboardingHintService`: 5 Rang-Regeln (Rang 1: kein Wohnhabitat; Rang 2: kein Ingenieur; Rang 3: Harvester auf falschem Tile; Rang 4: keine Kenntnis; Rang 5: Vertrauen < -20); gibt `null` zurück wenn `onboarding_hints = false`
- [x] [backend-coder] Dismiss-Endpunkt `POST /colony/hint/dismiss`; AJAX-Aktionen liefern `activeHint` in Response; kein separater Poll-Endpunkt nötig
- [x] [ui-specialist] Reaktive Hint-Bar in `hexview.blade.php` — Alpine `x-show`, kein Page-Reload; AJAX-Aktionen aktualisieren Hinweis live
- [x] [qa-tester] 17 Tests in `OnboardingHintServiceTest.php` grün

#### Schritt 4 — Pulse-Indikator (§ 15.3)

- [x] [ui-specialist] CSS-Animation `onboarding-ring-pulse` (blau-weiß, 2s) in `colony.css`
- [x] [ui-specialist] Pulse auf Rang-1-Tiles (bebaubare Colony-Zone) und Rang-3-Tiles (Harvester-Tile) im SVG-Grid implementiert
- [x] [ui-specialist] Pulse für Rang 2/4/5 (Techtree-Kacheln) — `data-hint-rank` auf Container, CSS `@keyframes techtree-card-pulse` auf `.tech-personell/.tech-research/.tech-building.status-available`

#### Schritt 5 — Techtree-Kaltstart: Kachel-Sortierung (§ 15.4)

- [x] [backend-coder] `TechtreeController` / Techtree-API: Gruppierungsflag je Kachel (`available` / `locked` / `built`) — implementiert
- [x] [ui-specialist] Techtree-View: drei visuelle Gruppen, gesperrte Kacheln gedimmt (Opacity 0.55) mit Lock-Icon + Voraussetzungs-Hinweis

#### Schritt 6 — Inline-Erklärungen: 5 INNN-Trigger (§ 15.6)

- [x] [game-developer] Trigger 1 (Decay): Erstes Gebäude unter 80% Status-Points → einmaliges `innn_event` mit `event_type = 'onboarding_decay'`, Absender System, erklärt Reparatur-AP (einmalig pro Run)
- [x] [game-developer] Trigger 2 (Supply-Cap voll): `freies_supply = 0` → `fired_triggers → supply_cap_full` in `user_preferences`
- [x] [game-developer] Trigger 3 (Vertrauen erstmals negativ): `vertrauen` wird negativ → einmaliges `innn_event` mit `event_type = 'onboarding_trust'`, Absender Kolonist
- [x] [backend-coder] Trigger 4 (AP-Limit): Button-Handler gibt `error: 'ap_limit'` zurück; Frontend zeigt Inline-Meldung (kein Modal)
- [x] [ui-specialist] Trigger 5 (Harvester-Verlagerung): Beim ersten Klick auf "Verlegen" erscheint einmaliger Tooltip via `harvester_move_shown`-Flag
- [x] [db-migration-agent] Flag-Mechanismus: `fired_triggers` JSON-Spalte in `user_preferences`; `OnboardingTriggerService` mit idempotenten `hasFired`/`markFired`
- [x] [content-writer] Finale Texte für alle 5 Inline-Erklärungen in `lang/de/colony.php`
- [x] [qa-tester] 43 Tests in `OnboardingTriggersTest.php` + `OnboardingTriggerServiceTest.php` — alle grün

#### Schritt 7 — Integration & Einstellungen

- [x] [ui-specialist] Einstellungs-Toggle in User-Settings-Screen: "Onboarding-Hinweise anzeigen" (An/Aus) — implementiert (Schritt 1)
- [x] [qa-tester] End-to-End: Neuer Run → Nexus-Briefing im INNN → Hint-Leiste zeigt Rang-1-Hinweis → Wohnhabitat bauen → Hint-Rang wechselt auf Rang 2 → Onboarding-Hints deaktivieren → null — `OnboardingE2ETest.php` (4 Tests, 15 Assertions)

---

### Phase 3g: Neue Gebäude — Abgeschlossen (Mai 2026, PRs #104 + #105 + #112)

Drei neue Gebäude entworfen (GDD §4 + §11) und vollständig implementiert (DB-Migration, Service-Effekte, Sprachschlüssel).

- [x] **Sicherheits-Hub** (`securityHub`, CC Lv2, max 1 Instanz) — Verteidigung-Order kostet nur 1 Nav-AP; gibt ~10% der Stufenkosten als Ressourcen zurück beim Decay-Level-Down. Provisorisch: supply_cost 8, decay 30d.
- [x] **Uplink-Station** (`uplinkStation`, CC Lv2/3/5, max 1 Instanz, 3 Level) — Lv1: Aktive Nexus-Anfragen freischalten; Lv2: Tiefenscan −1 Tick + Händler häufiger; Lv3: Run-Abschluss-Aktion. Lv1-Baukosten ohne Werkstoffe (kein Zirkelrisiko). Provisorisch: supply_cost 6, decay 30d.
- [x] **Handelsposten** (`tradingPost`, CC Lv4, max 1 Instanz) — Händler-Economy-AP −1; Händlerpreise +10–15%. Provisorisch: supply_cost 6, decay 30d.

---

### Phase 3f: Berater-Screen Redesign — Abgeschlossen (Mai 2026, Branch feat/phase3f-advisor-carousel)

Der Berater-Screen war der logische nächste Schritt nach dem Onboarding (Phase 3e), da der Onboarding-Hinweis Rang 2 direkt auf das Einstellen eines Beraters verweist. Der Screen wurde von Bootstrap/jQuery auf Alpine.js + PicoCSS migriert und als Karussell neugestaltet.

- [x] [backend-coder] `AdvisorController::buildSlots()` — 5-Slot-Array mit Zustands-Logik (active/unavailable/empty/locked), CC-Level-Gating, Rang-Fortschritt in Prozent
- [x] [backend-coder] JSON-Branching in `hire()` und `fire()` — AJAX-Clients erhalten strukturiertes JSON (`{ok, slots, slotInfo}`), HTML-Clients erhalten weiterhin Redirect
- [x] [ui-specialist] `public/css/advisors.css` — Portrait-Karten (2:3-Verhältnis), Rang-Badges, Fortschrittsbalken, Status-Chips, Karussell-Track mit CSS-Transition, Arrows + Dots (Mobile only)
- [x] [ui-specialist] `public/js/advisors.js` — Alpine-Komponente: Swipe-Gesten (Touch-Events), Karussell-Navigation, AJAX hire/fire, native `<dialog>`-Steuerung
- [x] [ui-specialist] `resources/views/advisors/index.blade.php` — Komplett auf `layouts.colony` (PicoCSS + Alpine) umgestellt; `x-for` für Karten, `x-if` für Zustände, `@push`-Stacks für CSS/JS
- [x] [qa-tester] 22 Feature-Tests in `AdvisorControllerTest.php` — Index, Hire/Fire (Redirect + JSON), 404-Sicherheit, Auth-Guard; alle grün

---

### Phase 3h: Techtree Phase-Layout — Abgeschlossen (Mai 2026)

Techtree-Ansicht komplett überarbeitet. Fünf Sektionen (Phase 1–5), eine pro CC-Level. 3-Spalten-Grid je Sektion; SVG-Bézier-Pfeile für Abhängigkeiten innerhalb einer Phase. Mobile: horizontales Karussell mit Wisch-Geste und Dot-Navigation.

- [x] DB-Migration 000003 — `phase`-Spalte auf allen 4 Master-Tabellen; partielle `(phase, row, column)` Unique-Indizes ersetzen alte `(row, column)` Indizes
- [x] `TechtreeController` — pageData-Struktur mit Phase-Gruppen; Liniengenerierung phase-lokal
- [x] `resources/views/techtree/index.blade.php` — Alpine.js + PicoCSS, Phasen-Sektionen, Karussell (Mobile)
- [x] `public/js/techtree-view.js` — Bézier-SVG-Linien mit Scroll-Offset-Kompensation; Kategorie-Toggles (visibility:hidden, kein Grid-Reflow)
- [x] TestSeeder erweitert um UPDATE-Support; 3 neue Controller-Tests

---

### Entwicklungswerkzeuge (Dev Tools)

Lokale Admin-Tools für den Entwickler — kein Spieler-Feature, kein Laravel-Stack nötig. Alle Tools liegen im `tools/`-Verzeichnis und starten per `php -S localhost:808x tools/<name>.php`.

- [x] **Dev Panel** (`tools/dev-panel.php`) — Kombiniertes Browser-Tool mit Tab-Navigation: **Resources** (Credits/Supply/Regolith/Werkstoffe/Organika/Vertrauen setzen) + **Techtree** (Drag-and-Drop-Editor für Techtree-Positionen). Löst `tools/techtree-editor.php` und `tools/resource-editor.php` ab. Start: `php -S localhost:8081 tools/dev-panel.php`
- [ ] **Debug-Statusleiste** — Overlay im Spielbrowser (z.B. als Bookmarklet oder separates Tool): zeigt aktuelle Spielparameter auf einen Blick — Supply-Verbrauch/-Cap, AP-Pools, Moral-Wert, Tick-Nummer, aktive Flags.
- [ ] **Berechnungs-Toggle** — Artisan-Kommando oder .env-Flag zum An-/Abschalten einzelner Berechnungen für Testzwecke: Ressourcenberechnung, AP-Berechnung, Decay, Moral-Multiplikator. Erlaubt isoliertes Testen einzelner Systeme ohne Interferenz.
- [x] **Tick-Simulator** (`game:tick-dry-run`) — Simuliert einen Tick und zeigt Credits-, Ressourcen- und Decay-Diff ohne DB-Schreibzugriff. `--colony=ID` filtert auf eine Kolonie. Ideal für Balancing-Checks.

---

### Bewusste Designentscheidungen (nicht umsetzen in Phase 3)

| Thema | Entscheidung | Begründung |
|---|---|---|
| **Interstellare Bewegung** | Nicht implementieren | Bei einer Kolonie im Fokus findet alles im eigenen System statt. Sprungtor existiert als narratives Element. Gäste von außerhalb kommen via Events/Bar. Phase 4+ nachrüstbar. |
| **Modulare Schiffe** | Nicht implementieren | Die Kolonie steht im Vordergrund. Die 3 Schiffstypen erzeugen bereits sinnvolle Kompositionsentscheidungen. Bei 1 Tick/Tag wäre der Feedback-Loop für Modul-Fehler zu langsam. |
| **Angriffe auf Kolonien** | Nicht implementieren | Nur PvP-Schiffskämpfe (Schiff vs. Schiff). Kolonien sind kein Angriffsziel. |
| **Kolonisierung** | Nicht implementieren | Jeder Spieler hat genau eine Kolonie. |
| **Rassen-System** | Zurückstellen auf Phase 4 | `race_id` ist im Schema, wird nicht ausgewertet. Rassenspezifische Effekte zu definieren setzt Playtest-Daten voraus — sonst blind balancen. |
| **Gruppen/Gilden** | Zurückstellen auf Phase 4 | Kein Datenmodell vorhanden. Soziale Mechaniken entfalten erst Wert wenn eine aktive Spielerbasis existiert. |
| **Diplomatie** | Zurückstellen auf Phase 4 | `innn_message_types.relationship_effect` ist vorbereitet; vollständige Diplomatie setzt stabile Moral-Balance aus Phase 3 voraus. |
| **Außenposten** | Zurückstellen auf Phase 5 | Ob das Einzelkolonie-Konzept als zu einschränkend empfunden wird, lässt sich erst nach echtem Betrieb beurteilen. |
| **Benannte Chef-Berater** | Zurückstellen auf Phase 4 | Aktuelles Berater-Modell ist als Fundament ausgelegt (GDD §12); individuelle Charaktere erst nach Phase-3-Playtest sinnvoll. |
| **Steuersystem** | Zurückstellen auf Phase 4 | `steuerfaktor` in Moral-Formel ist Platzhalter (= 0). Implementierung setzt stabile Moral-Balance aus Phase 3 voraus. |

---

## Phase 4: "Das Spiel vertiefen"
*(nach Phase 3)*

**Ziel:** Spieler, die das Basisspiel kennen, bekommen neue Strategiepfade und Interaktionsebenen.

**Voraussetzung:** Phase-3-Playtest mit echten Spielern abgeschlossen. Ohne Playtest-Feedback sind die Design-Entscheidungen in Phase 4 zu unsicher — insbesondere Rassen-Effekte, Steuersystem und Diplomatie-Balance hängen von Beobachtungen aus dem echten Spielbetrieb ab.

- [ ] **Berater-Spezialfähigkeit (CC Lv4-Gate)** — Berater können ab CC Lv4 eine einmalige Spezialfähigkeit pro Tag aktivieren — sofort spürbare taktische Option (z.B. Baumeister: Notfall-Reparatur ohne AP-Kosten; Stratege: temporäre Kampfbonus-Runde); Design-Sprint nötig für konkrete Fähigkeiten je Beratertyp
- [ ] **Nexus-Außenposten-Slot (CC Lv5-Gate)** — CC Lv5 schaltet einen zweiten Außenposten-Slot frei — direkter Meilenstein in der Expansionsmechanik; Datenmodell noch nicht vorhanden (siehe Phase 5 Außenposten); hier konkret: CC Lv5 gibt die Möglichkeit einen zweiten Nexus-Kontakt-Knoten zu errichten, der eigene Handels- und Missionsoptionen bietet
- [ ] **Diplomatie-System** — `innn_message_types.relationship_effect` auswerten; diplomatische Zustände (Krieg, Frieden, Allianz, Neutralität); Moral-Events `war_declared`/`treaty_signed` aktivieren; AP-Kosten gemäß Designprinzip (Kriegserklärung teurer als Handelsvertrag)
- [ ] **Gruppen/Gilden** — Datenmodell für Gruppen (kein Schema vorhanden); Grundlage für `restriction = 1` im Handelssystem; bewusst einfach gehalten: gründen, beitreten, verlassen
- [ ] **Rassen-System überarbeiten** — `race_id` ist im Schema, wird nicht ausgewertet; rassenspezifische Effekte definieren; Designfrage erst nach Phase-3-Playtest beantwortbar
- [ ] **Steuersystem** — `steuerfaktor` in Moral-Formel als Platzhalter (= 0); GDD-Design steht; Implementierung setzt stabile Moral-Balance aus Phase 3 voraus
- [ ] **Berater-Vertiefung (Design-Sprint nötig)** — Beim Einstellen eine Auswahl aus mehreren Kandidaten (zufällig generiert pro Run); Berater haben positive und negative Traits (z.B. "Pragmatiker: +1 Bau-AP / −5% Moral", "Intrigant: +2 Strategie-AP / Vertrauensmalus"); individuelle Namen und Portrait-Grafiken; aktuelles Berater-Modell ist als Fundament ausgelegt (GDD §12)
- [ ] **Moral-Erweiterung** — Bevölkerungszufriedenheit als eigener Wert, Revolutionsrisiko, fraktionsspezifische Moralmodifikatoren (GDD §13)
- [ ] **Handelsbeschränkungen vollständig durchsetzen** — `restriction`-Feld Werte 1/2/3 korrekt auswerten (aktuell ignoriert)

---

## Phase 5: "Das Spiel erweitern"
*(nach Phase 4)*

**Ziel:** Strukturelle Erweiterungen auf Basis von echtem Spieler-Feedback aus dem Betrieb.

**Voraussetzung:** Phase-4-Betrieb mit echter Spielerbasis; Entscheidung ob das Einzelkolonie-Konzept erweitert werden soll. Phase 5 wird bewusst erst dann konkret ausgearbeitet — die Themen hier sind Hypothesen, keine Commitments.

- [ ] **Außenposten** — `home_colony_id` pro Flotte (GDD §12); ob Außenposten kommen, hängt davon ab ob das Einzelkolonie-Konzept als zu einschränkend empfunden wird; minimal halten (kein vollständiges Kolonie-System)
- [ ] **Neue Schiffstypen** — Scout/Sonde (Supply 1) und weitere; setzt stabiles Combat-Balancing aus Phase 4 voraus
- [ ] **Galaktische Politik** — über bilaterale Diplomatie hinaus: galaktische Institutionen, Abstimmungen, Fraktionspolitik; nur auf Basis von echtem Spielerverhalten definierbar
