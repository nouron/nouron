# Nouron — Roadmap

Stand: 2026-08-16

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
- [x] **System-View (12×12-Grid)** — SVG + plain JS, Objekte und Flotten, Flottenbefehl-Overlay
- [x] **Vertrauensanzeige im UI** — Vertrauens-Chip in Colony Hexview (grün/grau/rot); Trust in globaler Ressourcenleiste auf allen Seiten
- [x] **Händler-Modal** — Alpine-gesteuert, nativer `<dialog>`, 3 Items (Reparatur-Kit, Vertrauensschub, Systemkarte); MerchantService + MerchantController + GameTick-Integration; DB: `merchant_visits` + `merchant_items`
- [x] **Globale Ressourcenleiste** — Sol-Chip + Credits + Supply + Trust persistent auf allen Gameplay-Seiten (`layouts/app` + `layouts/colony`); Sol run-lokal via `since_tick`; deprecated Ressourcen (ENrg/LNrg/ANrg) gefiltert
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

### Phase 3i: Run-System — Abgeschlossen (Mai 2026, PR #141)

Roguelike-Run-Struktur mit zwei Phasen, 9 trackbaren Objectives und Nexus-Interventionssystem. Playtest-Voraussetzung für Phase-4-Entscheidungen.

#### Sprint A — Kern-Infrastruktur

- [x] DB: `runs`-Tabelle (`current_tick`, `status`, `phase`, `fail_reason`, `nexus_debt`, `phase2_start_tick`) + `run_objectives`-Tabelle (`task_key`, `target_value`, `current_value`, `streak_value`, `completed_at`)
- [x] `Run`- und `RunObjective`-Eloquent-Models
- [x] `RunProgressService`: Phase-1-Check (CC Lv3 + 2 Produktionsgebäude Lv2+ + 3 Berater), `drawObjectives()` mit Combo-Blacklist (max. 1 Economy-Task), 4 Objective-Typen (Phase 1 Sprint A)
- [x] GameTick-Integration: `updateObjectiveProgress`, `checkNexusInterventions`, `checkFailStates`, `endRun`, `calculateScore`
- [x] Fail-States: Vertrauen < −20, Zeitablauf (tick_limit), Nexus-Schulden > 12.000 Cr
- [x] Sieg-Bedingung: min. 2 von 3 Objectives erfüllt
- [x] Ergebnis-Screen (`/run/{id}/result`) mit Score, Fortschrittsbalken, Sieg/Niederlage-Feedback

#### Sprint B — Vollständige Objective-Suite + Nexus

- [x] 5 weitere Objective-Typen (9 insgesamt): `task_self_sufficiency`, `task_expedition_coverage`, `task_engineering_output`, `task_trade_volume`, `task_combat_record`
- [x] Nexus-Interventionen: Sol-30/50-Warnung, Sol-65-Berater-Sperre, Sol-80-Countdown, Schulden-Fail-State
- [x] UI: Highscore-Tabelle Lobby, Nexus-Kredit-Badge Navbar (grau/gelb/rot)
- [x] Vollständiger `newRun()`-Reset (Gebäude, Tiles, Forschungen, Advisors, Credits)
- [x] Score-Formel: `(abgeschlossen × 1000) + ((tick_limit − sol) × 10) + (credits / 10) + (vertrauen × 5)`, min. 0
- [x] Task-Keys englischsprachig gemäß CLAUDE.md-Konvention
- [x] 613 Tests grün (57 neue in Sprint B)

---

### Entwicklungswerkzeuge (Dev Tools)

Lokale Admin-Tools für den Entwickler — kein Spieler-Feature, kein Laravel-Stack nötig. Alle Tools liegen im `tools/`-Verzeichnis und starten per `php -S localhost:808x tools/<name>.php`.

- [x] **Dev Panel** (`tools/dev-panel.php`) — Kombiniertes Browser-Tool mit Tab-Navigation: **Resources** (Credits/Supply/Regolith/Werkstoffe/Organika/Vertrauen setzen) + **Techtree** (Drag-and-Drop-Editor für Techtree-Positionen). Löst `tools/techtree-editor.php` und `tools/resource-editor.php` ab. Start: `php -S localhost:8081 tools/dev-panel.php`
- [x] **Debug-Statusleiste** — Fixed Bottom-Bar, nur für `role=admin` sichtbar. Kompakte Zeile: Run-ID, Sol/Tick-Limit, Bypass-Flags (farbkodiert), App-Env. „Config ▾"-Toggle öffnet Detailpanel mit Run-, Tick-, Supply-, Credits-, Fleet-AP- und Moral-Event-Werten aus `config/game.php`. Alpine.js x-show, kein Bootstrap.
- [ ] **Berechnungs-Toggle** — Artisan-Kommando oder .env-Flag zum An-/Abschalten einzelner Berechnungen für Testzwecke: Ressourcenberechnung, AP-Berechnung, Decay, Moral-Multiplikator. Erlaubt isoliertes Testen einzelner Systeme ohne Interferenz.
- [x] **Tick-Simulator** (`game:tick-dry-run`) — Simuliert einen Tick und zeigt Credits-, Ressourcen- und Decay-Diff ohne DB-Schreibzugriff. `--colony=ID` filtert auf eine Kolonie. Ideal für Balancing-Checks.
- [x] **Playtest Dashboard** (`tools/playtest-dashboard.php`, 2026-08-16) — Browser-Viewer für `storage/logs/playtest/*.json`-Reports (von `game:playtest`/`PlaytestBotTest` erzeugt, keine neue Datenerhebung). Sidebar mit allen Läufen, Mehrfachauswahl, Chart.js-Liniendiagramme (Regolith/Credits/AP/Vertrauen/CC-Level über Sol) + Summary-Tabelle (Outcome, Objectives, Rejections). Teilt sich `tools/assets/dev-panel.css` mit Dev Panel (eigene `PLAYTEST DASHBOARD`-Sektion). Start: `php -S localhost:8082 tools/playtest-dashboard.php`

---

### Bewusste Designentscheidungen (nicht umsetzen in Phase 3)

| Thema | Entscheidung | Begründung |
|---|---|---|
| **Interstellare Bewegung** | Nicht implementieren | Bei einer Kolonie im Fokus findet alles im eigenen System statt. Sprungtor existiert als narratives Element. Gäste von außerhalb kommen via Events/Bar. Phase 4+ nachrüstbar. |
| **Modulare Schiffe** | Nicht implementieren | Die Kolonie steht im Vordergrund. Die 3 Schiffstypen erzeugen bereits sinnvolle Kompositionsentscheidungen. Bei 1 Tick/Tag wäre der Feedback-Loop für Modul-Fehler zu langsam. |
| **Angriffe auf Kolonien** | Nicht implementieren | Nur PvP-Schiffskämpfe (Schiff vs. Schiff). Kolonien sind kein Angriffsziel. |
| **Kolonisierung** | Nicht implementieren | Jeder Spieler hat genau eine Kolonie. |
| **Rassen-System** | Abgekündigt | Konzeptuell aufgegeben (GDD §3) — zusammen mit ENrg/LNrg/ANrg. `race_id` wird per DB-Cleanup entfernt (Phase 4), keine rassenspezifischen Effekte geplant. |
| **Gruppen/Gilden** | Zurückstellen auf Phase 4 | Kein Datenmodell vorhanden. Soziale Mechaniken entfalten erst Wert wenn eine aktive Spielerbasis existiert. |
| **Klassische Diplomatie** | Abgekündigt | Krieg/Allianz/Fraktionszustände inkompatibel mit Singleplayer-Roguelike ohne organisierte Gegner (GDD §1.1). Ersetzt durch NPC-Vereinbarungen (Phase 4) und `treaty_signed`-Events. |
| **Außenposten** | Zurückstellen auf Phase 5 | Ob das Einzelkolonie-Konzept als zu einschränkend empfunden wird, lässt sich erst nach echtem Betrieb beurteilen. |
| **Benannte Chef-Berater** | Zurückstellen auf Phase 4 | Aktuelles Berater-Modell ist als Fundament ausgelegt (GDD §12); individuelle Charaktere erst nach abgeschlossener Balance-Kalibrierung (PlaytestBot) sinnvoll. |
| **Steuersystem** | Abgekündigt | `steuerfaktor` in der Vertrauensformel ist entfernt (nicht mehr Platzhalter) — ersetzt durch die implementierte Kolonisten-Zulage (2026-07-10, GDD §14). Kein offener Punkt für Phase 4. |
| **Battlecruiser** | Abgekündigt | Schiffstypen auf Drohne/Frachter/Korvette reduziert. |
| **Fleet-Commander als separater Berater-Typ** | Abgekündigt | Entfernt im Zuge des Berater-Redesigns (GDD §12); Kommandanten-Zuweisung existiert als Fleet-Feature unabhängig davon (PR #139). |

---

## Phase 3 Balance — Bot-Kalibrierung & nächste Schritte

> **Fokus:** Singleplayer only. Multiplayer folgt erst in einer späteren Phase. **Kein menschlicher Playtest geplant** (CLAUDE.md, Owner-Entscheidung) — `PlaytestBot`/`game:playtest` ist das primäre und einzige vorgesehene Werkzeug für Balance-Analysen.

### Balance-Ziel

Ein automatisierter Run (Bot, ausschließlich über echte HTTP-Routen) soll die Kolonie von Beginn an auf etwa 80% ausbauen können — d.h. alle wesentlichen Gebäude bauen, Berater einstellen, Ressourcen managen, Schiffe über Nexus anfragen, Run-Objectives verfolgen und einen Run zu einem (erfolgreichen oder gescheiterten) Ende bringen, ohne an strukturellen (nicht spielerischen) Engpässen zu scheitern.

### Balance-Checkliste (Bot-Kalibrierung statt menschlichem Playtest)

- [x] **Onboarding-Hints weitgehend abgedeckt** — Sol-1–4-Rampe neu geordnet (GDD §16.2/16.3/16.5), 67 Hint-Tests grün (2026-07-14). Ein Punkt bewusst offen: `hint_2` soll von der Sol-1-Spezialformulierung zu einem generellen "Regolith-Tile erschöpft, Harvester verlegen"-Alert werden (Owner-Entscheidung 2026-08-04, noch nicht umgesetzt).
- [x] **Neuer Run spielbar?** — verifiziert per PlaytestBot statt menschlichem Spieler: Bot spielt komplette Runs ausschließlich über die echten HTTP-Routen; `phase2_start_sol` liegt aktuell (Stand 2026-08-13) bei 20–22 über 3 Test-Seeds. Startwerte seither mehrfach nachjustiert (Regolith-Startbestand 200→300→340). Läuft aktuell noch an `time_limit` aus (zu wenig Sole für Phase 2 übrig) statt an der strukturellen Blockade, die vorher bestand — siehe Phase 3o unten.
- [x] **Kritische Blocker?** — systematisch über Bot- und Owner-Playtests ausgeräumt (einheitlicher 422-Fehlercontract, automatischer Techtree-Levelup + Fehleranzeige, mehrere Hint-Sackgassen behoben). Kein bekannter offener Blocker.
- [x] **INNN/Nachrichten vereinfachen** — abgeschlossen, siehe Phase 3j

### Phase 3j: Kolonieprotokoll (INNN-Redesign) — Abgeschlossen

INNN-Nachrichtensystem vollständig ersetzt. Neuer Screen `/comm-log` mit zwei Tabs — "Protokoll" (chronologisches Aktions- + Ereignis-Log, mit `×N`-Kollaps bei Wiederholungen) und "Nexus-Funk" (story-generierte Nachrichten mit Ungelesen-Badge). Player-Messaging, Inbox/Outbox, Compose, Galaxy-News entfallen. Entity-Chips (Gebäude, Kenntnis, Schiff, Ressource, Berater) als farbige Pills mit Hover-Tooltip. `colony_log`-Tabelle ersetzt `innn_events`. 725 Tests grün.

### Weitere abgeschlossene Meilensteine (Juli/August 2026, chronologisch)

- **07-04/05 Hangar-Missionskatalog + Schiffs-Verschleiß** (GDD §8b/§7) — 12 Missionstypen, `wear_per_sol` je Schiffstyp, Missionsdialog statt Freitext-Dispatch (PR #210/#211).
- **Cantina-Redesign** — Bar-Hintergrund (`cantina-interior.webp`) + NPC-Charaktere via `config('characters')` + Hotspot-Portraits (vor dem 07-30/31-Verhandlungs-Redesign, das darauf aufsetzt).
- **content-writer-Tonalität + lang/en-Sync** — Drei-Stimmen-System (Kolonie/Nexus-Direktiven/NexusDB-Almanach), alle `lang/de/`-Beschreibungstexte neu geschrieben, `lang/en/` vollständig synchronisiert (12 neue Dateien); globales Sci-Fi-Dialog-System (`dialogs.css`, `sol-modal`).
- **07-10/11 Kolonisten-Zulage + Kommandozentrale-Screen** — neue Spieleraktion "Kolonisten-Zulage" (Credits → Vertrauen, 3 Stufen) ersetzt das nie implementierte Steuern-Konzept; eigener Kolonie-Dashboard-Screen (Run-Fortschritt, Wartungsstau, Berater-Kurzübersicht, Vertrauens-Ereignisse).
- **07-14 Onboarding-Rampe Sol 1–4 neu geordnet** — game-designer-Spezifikation mit Budget-Rechnung, 67 Hint-Tests, GDD §16.2/16.3/16.5 aktualisiert.
- **07-17/18 Playtest-Bot** — PHPUnit-basierter Bot unter `tests/Feature/Playtest/` spielt komplette Runs ausschließlich über echte HTTP-Routen (`BotSession`/`BotStrategy`/`RunReport`-JSON-Artefakt). Deckte dabei mehrere echte Bugs auf (Session-Hard-Default Kolonie 1, ungeseedete Ziel-Ziehung, nicht persistierter Score, 200er statt 422 bei Colony-Fehlern) und legte den strukturellen Credits-Ökonomie-Kollaps nach Phase 1 offen (PR #217/#218).
- **07-19/20 Credit-Ökonomie-Balance (2-Schritt-Ticket)** — Relaisvergütung Housing→Uplink-Station umgehängt, Advisor-Upkeep abgeflacht, Rang-Schwellen gestreckt, neue Handelsvertrag-Einkommensquelle (PR #219); Harvester/Agrardom-Grundproduktion von flacher Rate auf `production_curve`-Glockenkurve mit Deckel umgestellt (PR #220). Ergebnis: `phase2_start_sol` nie erreicht → 49 → 18 — vor der §13.7-Zahlensatz-Umstellung (unten) gemessen, mit dem heutigen Stand (20–22) nicht direkt vergleichbar.
- **07-21/24 Larastan (PHPStan Level 5) auf 0 Fehler** + PHPUnit-Coverage von 70,7% auf 89,9% gebracht.
- **07-30/31 Cantina-Verhandlung + Dialog-Redesign** — zweistufiger Verhandlungsablauf (Konsul-Rang-abhängige Erfolgschance), einheitliches Cantina-Dialog-Layout mit Charakter-Portrait.
- **08-01 Design System verbindlich verdrahtet** (`docs/design-system/`) + `docs/frontend-conventions.md` löst `docs/design-guide.md` ab.
- **08-02/03 GDD-Restrukturierung** — §4b "Die drei Pfade" (Paritäts-Anforderung), §4c "Instanzen oder Level" (Wachstumsachse je Gebäude), §13.1–13.7 (AP-Pool, Ratenmodell, Regolith-Zahlensatz), Anhang A (Balance-/TODO-Index) und Anhang B (Config/Code-Drifts) neu eingeführt.
- **08-05/06 Harvester-Zweitinstanz-Bezugswege + Corvan/Pfad-C** (§4c) — Sockel-Baseline auf 1 Harvester-Instanz umgestellt, zweite Instanz optional über Weg A (Orin/`corporate_rep`, Cantina-Kauf 400–800 Cr) oder Weg B (Bergungsmission `mission_harvester_salvage`); Corvan (Reisender Händler) übernimmt Alltagsgeschäft (Credits-Handel) als Pfad-C-Hebel, anonyme Bar-Gäste nur noch Tauschhandel.
- **08-10 AP-Pool-Konsolidierung** (Phase 3o Stufe 2, GDD §13.1) — die fünf getrennten AP-Domänen sind zu einem gemeinsamen Kolonie-Pool zusammengelegt; `strategist`-Beratertyp zurückgestellt, `advisor.max_slots` 5→4 (PR #240/#241).
- **08-12 Phase-1-Sol-30-Deadline** — vierter Fail-State (`RunProgressService`), eskalierende Nexus-Warnung ab Sol 22, eigener Fail-Screen-Ton.
- **08-15 Kenntnis-Effekte, erste Welle** (PR #253) — `construction`/`cartography`/`trade` erhalten additiven Bau-AP-Rabatt (`app/Services/ProjectBonusService.php`), `agronomy` erhält Organika-Produktionsbonus (Parität zu `geology`), `trade` zusätzlich Cantina-Angebotsslot-Bonus. GDD §13.3/§13.5-Nachträge.
- **08-16 GDD §9 „Begegnungen & Gefahren" implementiert** (Branch `design/encounters-and-defense`) — drei Gefahrentypen (Sturm, Geologische Instabilität, Seuchenausbruch) erstmals codiert (§9 war zuvor nur spezifiziert); neuer `app/Services/EncounterService.php`, Cooldown-Mechanismus gegen Ereignis-Spiralen, vollständige Kolonieprotokoll-Integration, Onboarding-Hint. `defense`-Kenntnis bekommt ihren ersten aktiven Effekt (Sturm-Risiko-Reduktion), `geology` bekommt einen zweiten Effekt (Instabilitäts-Risiko-Reduktion). GDD §9-Nachtrag.

---

## Laufend: Phase 3o — AP-Ratenmodell & Regolith-Balance

**Nicht abgeschlossen** trotz CLAUDE.md-Eintrag "AP-System-Konsolidierung (Phase 3o)" unter *Abgeschlossen* — das bezieht sich nur auf **Stufe 2** (AP-Pool zusammenlegen, 2026-08-10), nicht auf den gesamten Stufenplan. Design steht im GDD (§3, §4b, §4c, §6, §13.1–13.7, Anhang A/B). TDD ist verbindlich (CLAUDE.md): für jede Stufe mit Verhalten zuerst ein fehlschlagender Test, der das gewünschte Verhalten beschreibt.

Offene Stufen: 1b/1d (Supply-Achse-Herleitung, Pfad-C-Regolith-Hebel), 3 (Ratenmodell/Bonus-System vervollständigen), 4 (Kommandozentrale-Dashboard-Erweiterung), **5 (Instrumentierung/Kalibrierung — läuft, Details unten)**, 6 (Nachzieharbeiten).

### Stufe 0 — Klären (Owner) — Abgeschlossen (2026-08-03)

- [x] `ap_for_levelup` in der laufenden DB verifiziert: überall 10, nur Monument 20 (Migration `2026_04_17_000003` mit 10/20/30 ist nicht aktiv)
- [x] AP-Struktur freigegeben (§13.6): `ap.base = 12` statt 10, Berater 2/3/4, `f(1) = 0.5`, Boni additiv max. 42 %
- [x] Regolith-Zahlensatz freigegeben (§13.7): Harvester-Frischwert 18, Reparatur 1 Rg/SP, `decay_rate` 0,40/0,60/0,80/1,20, Errichtung 70/95/120, Level-Up flach 25; Instanz-Preisregel zurückgezogen (Instanzen zahlen vollen Errichtungspreis)
- [x] Harvester-Erschöpfung freigegeben (§4c): Ertragskurve fällt auf 50 %, `resource_max` 500/300/160, Verlegekosten 2 AP/Hex, zweite Instanz an CC Lv3 + 100 Rg
- [x] `max_instances` als eigenes Feld beschlossen (§4c)
- [x] `bar.base_prices` nach der Knappheitsordnung (§3): Rg 25 / Or 50 / Wk 110, `compound_import_price` 165
- [x] `geology`-Bonus: +3/3/2/2/2, kumuliert max 12 (§13.7)
- [x] Pfad A/Credits: `knowledge.credits` von 100 auf 0 statt vierter Einnahmequelle (§13.7)

### Stufe 1 — Zahlensatz in einem Zug — Abgeschlossen (PR #235, 2026-08-04)

- [x] Kompletter Zahlensatz aus §13.7 (Produktion, Reparatur, `decay_rate`, Bau-/Level-Up-Kosten, CC-Ausbau)
- [x] `harvester.max_level` 8 → 1
- [x] Harvester-Zweitinstanz-Gate (CC Lv3 + 100 Rg pauschal, `ColonyController::placeBuilding`) — die generische "Level-Up-Preis für jede weitere Instanz"-Regel für Hangar/Wohnhabitat bleibt offen (Bootstrap-Zirkel, siehe Stufe 1b)
- [x] `geology`-Effekt als hartverdrahteter Hook (erster von ursprünglich max. zwei erlaubten hartverdrahteten Kenntniseffekten — die Guard-Rail ist durch Owner-Entscheidung vom 2026-08-15 überholt, siehe `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md`: mittlerweile 6 von 7 Kenntnissen mit hartverdrahtetem Effekt — `construction`/`cartography`/`trade` Bau-AP-Rabatt, `agronomy` Organika-Bonus, `trade` zusätzlich Cantina-Slot-Bonus (PR #253, 2026-08-15), `geology` zusätzlich Instabilitäts-Risiko-Reduktion, `defense` erster eigener Effekt (Sturm-Risiko-Reduktion) (Branch `design/encounters-and-defense`, 2026-08-16); `health` bewusst ohne Zusatzeffekt)
- [x] `bar.base_prices` + `compound_import_price`, `knowledge.levelup_costs` + `credits` nachgezogen
- [ ] Wachstumsachsen-Umstellung (§4c) unvollständig: Agrardom Level→Instanz zurückgestellt auf Stufe 1d; Religiöse Stätte/Kolonialdenkmal je 1 Instanz/Lv1 offen; Hangar-Doppelachse (Instanzen = Schiffsplätze, Level 1–3 = Schiffsklasse) offen

### Stufe 1b — klein, danach — größtenteils abgeschlossen

- [x] `mission_supply_run.sol_distance` 2 → 1 (2026-08-04)
- [x] `mission_aid_transport` ungegatet — zweite Frachter-Mission ohne Kenntnis-Gate, schließt zugleich die Vertrauens-Lücke von Pfad B (2026-08-04)
- [x] Cantina-Losgröße an Zahlungsfähigkeit gebunden (höchstens ~35 % des Bestands, 2026-08-04)
- [x] Harvester-Zweitinstanz-Bezugsquelle entworfen und freigegeben (2026-08-05, §4c): Sockel-Baseline auf 1 Harvester-Instanz umgestellt (2. Instanz = optionaler Bonus, bewusst gegen Planbarkeit); Weg A = Orin (`corporate_rep`) verkauft Extraktionsrechte für 400–800 Cr; Weg B = Bergungsmission `mission_harvester_salvage` auf `ruin_tile`, kostenlos aber beschädigt ankommend — siehe Meilenstein 08-05/06 unten
- [ ] **Pfad-C-Regolith-Hebel neu denken** — der Organika→Regolith-Tausch fällt mit der Knappheitsordnung weg (§13.7). Die Rollenklärung Reisender Händler vs. Cantina-Gäste ist inzwischen entschieden (Corvan übernimmt das Alltagsgeschäft, siehe Meilenstein 08-05/06); offen ist nur noch, ob Pfad C überhaupt einen eigenen Regolith-Hebel braucht
- [ ] **Harvester-Erschöpfung** (§4c): Ertrag eines Regolith-Tiles soll über die Zeit sinken, damit der Harvester pro Run mehrfach umgesetzt werden muss. Schema-Grundlage existiert (`colony_tiles.resource_max`), ebenso die drei Ergiebigkeitsstufen und die Verlege-Vorschau. Zielbild: ein Tile trägt ~15–25 Sole. Rate gehört in die Regolith-Herleitung (§13.7)
- [ ] **Agrardom-Kurve am oberen Ende prüfen** (§3, §13.7): Verbrauch skaliert über `intdiv(usedSupply, 4)` mit der Ausbautiefe. Ab Lv4 (41 Or/Sol gegen max. ~31 Bedarf) ist das Rennen entschieden und Organika hört auf, eine Sorge zu sein — offen ist, ob die Kurve dort flacher auslaufen soll oder ob Missionen/Events genug Zusatzlast tragen

### Stufe 1c — Schema und Messbarkeit — Abgeschlossen (PR #234)

- [x] `max_level` aufgeteilt in `max_instances` und `max_level`
- [x] `config/buildings.php`: `harvester.max_level` angeglichen
- [x] `BotStrategy` repariert (Raumfahrer in `HIRE_ORDER`, Schiffskauf nicht mehr auf eine Drohne gedeckelt)
- [x] Instanz-Decay-Bug verifiziert und gefixt (`processBuildingDecay()` filterte nicht nach `instance_id`)
- [ ] Post-Phase-1-Ökonomie / Verkaufsrichtung in der Cantina — eigenes Ticket, kein Blocker mehr für den Zahlensatz

### Stufe 1d — Supply-Achse (nächste Design-Runde, offen)

Kein Implementierungsschritt, sondern die nächste zusammenhängende Herleitung — nach demselben Verfahren wie §13.7: von der Designabsicht her, ohne die Bestandswerte als Randbedingung. Anlass: Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war. Wird Bauen leichter, wird Supply relativ zum bindenderen Limiter — was §6 entspricht, aber verlangt, die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen.

- [ ] `supply_cost` je Gebäude und die Cap-Quellen (CC-Level, Wohnhabitat, Kenntnisse) neu herleiten
- [ ] **Level-Deckel für Cantina und Krankenstation** — beide heute `NULL` (unbegrenzt), im Widerspruch zum „kleine Kolonie"-Prinzip
- [ ] **Instanz-Deckel für den Agrardom** — mit der Umstellung auf Instanzen (§4c) offen; hängt am Organika-Rennen und am Tile-Budget
- [ ] Die übrigen `max_level = NULL`-Gebäude (Sciencelab, Temple, Hangar, Monument) mitentscheiden

### Stufe 2 — AP-Pool zusammenlegen (§13.1) — Abgeschlossen (PR #240/#241, 2026-08-10)

Kernumbau. `ap_spend` existierte bereits auf `colony_buildings`, `colony_research` und `colony_ships` — die Projekt-Investition über mehrere Sole funktionierte also schon, war nur typgebunden.

- [x] Ein gemeinsamer Pool, Berater aller Domänen zahlen ein, Locks verfallen zum Sol-Wechsel
- [x] `PersonellService` entkoppelt und zu `AdvisorService` umbenannt (nach `app/Services/` verschoben); vier Domänen-Getter entfallen, `$type`-Parameter entfernt
- [x] Callsites umgestellt: `AbstractTechnologyService`, `BarService`, `HangarService`, `ColonyTileService`, `OnboardingHintService`, Controller-Layer, `MerchantService::creditAp` (`FleetService` existiert nicht mehr)
- [x] `advisors.personell_type`-Enum ohne `strategy`; `strategist`-Beratertyp zurückgestellt, `advisor.max_slots` 5 → 4
- [x] `config/game.php`: `ap.base`, `advisor.ap_per_rank`; `config/advisors.php`: `strategist` entfernt
- [x] UI: AP-Chips, Ressourcenleiste, Berater-Screen auf einen Pool

### Stufe 3 — Ratenmodell vervollständigen (§13.2–13.3, §13.6) — offen

- [ ] `f(L)`-Kostenkurve statt flacher `ap_for_levelup` je Level; `f(1) = 0.5` fürs Errichten
- [x] Bonus-System, Domänen-Kenntnis-Teil (§13.3) — additive, glockenförmige Bau-AP-Kostenreduktion aus `construction`/`cartography`/`trade` (Σ15% je Kenntnis bei Lv5), wirkt auf alle Gebäude-Levelups; `app/Services/ProjectBonusService.php` (PR #253, 2026-08-15)
- [ ] Bonus-System, Rest offen: Berater-Rang- und Koloniereife-Kostenreduktion (§13.3-Tabelle) sind weiterhin nicht implementiert; `project_min_cost_factor` als Leitplanke bleibt ungenutzt, solange nur der Kenntnis-Bonus (max. 15%) aktiv ist
- [ ] Restzeit-Berechnung je Baustelle („noch 3 Sole bei aktueller Rate")
- [ ] Handlungs-AP nachziehen: `bar.ap_cost_accept` 1→2, `ap_cost_negotiate` 3→4
- [ ] `decay.overcap_factor` 2.0 → 1.5

### Stufe 4 — Kommandozentrale-Dashboard (§13.4) — offen

Tragende Voraussetzung des Ratenmodells, kein Komfort — es ersetzt die bewusst weggelassene Bodengarantie.

- [ ] AP-Zufluss und Verwendung, Restzeit je Baustelle, Instandhaltungsanteil
- [ ] Restertrag bis Run-Ende je Projekt (trägt den Late-Game-Kipppunkt ohne Zahlenänderung)
- [ ] Regolith-Bilanz, Over-Cap-Warnung, Konzessions-Prognose, Run-Aufgaben-Fortschritt

### Stufe 5 — Instrumentierung, Playtest, Kalibrierung (laufend)

Der Playtest-Bot (Phase 3n) ist die Messumgebung.

- [x] Determinismus-Bug `ColonyTileService::randomizeOuterRingRows()` **behoben** (2026-08-11, keine ungeseedete Tile-Erzeugung mehr — 980 Tests grün, 0 Skips, Stand 2026-08-13)
- [ ] Die neun Metriken aus GDD Anhang A.5 in `RunReport` aufnehmen (offen)
- [ ] Bot-Läufe, dann §13.6-Zahlen gegen die Zielkorridore nachziehen (laufend)

**Im Detail (laufend):** Bot-Läufe + Kalibrierung gegen die Zielkorridore laufend: **Kalibrierung des Regolith-Zahlensatzes (§13.7) via `PlaytestBot`** mehrfach neu hergeleitet — Sockel-Baseline auf 1-Harvester-Instanz umgestellt (08-05), Zahlensatz gegen diese Baseline neu gerechnet (08-06), Regolith-Startbestand mehrfach angehoben (200→300, dann 300→340, beides 08-13, inkl. zweier gefundener PlaytestBot-Bugs bei der Pfadgebäude-Bedarfsrechnung). Aktueller Stand: `phase2_start_sol` liegt bei 20–22 über 3 Test-Seeds (vorher: nie erreicht oder erst Sol 49–75). Runs scheitern jetzt an `time_limit` (zu wenig Sole für Phase 2 übrig), nicht mehr am `phase1_deadline`-Fail-State (Sol 30) — Phase 2 selbst hat damit weiterhin offene Balance-Probleme, weitere Iteration nötig.

### Stufe 6 — Nachzieharbeiten — offen

Kein Blocker, aber Teil der Definition-of-Done.

- [ ] Onboarding-Hinweistexte und Sol-1–4-Budgetrechnung (`gdd/onboarding.md` §16.2/§16.5) auf einen Pool und die neuen Grundwerte
- [ ] Außenmissions-AP-Staffel (§8b) gegen den neuen Pool neu kalibrieren
- [ ] Drifts aus GDD Anhang B abarbeiten (CC-Upgrade-Regolith, Decay-Richtwerte, `supply.ship_cost`, Kommentare in `knowledge.php`/`advisors.php`, `testdata.sqlite.sql`)
- [ ] `ResetPlayer.php`: hartcodierte `supply`/`regolith`-Werte in allen fünf Szenarien nachziehen
- [ ] `config/game.php → merchant.items.information.label` heißt noch "Systemkarte vollständig" (Restverweis auf die 2026-06-20 gestrichene Systemkarte) — rein kosmetisch, `MerchantService` setzt bereits korrekt `colony_tiles.is_explored`; Label-Text anpassen (siehe GDD §12 Kanal 3)
- [ ] Tick-Schritt-Nummerierung in `GameTick.php` (Docblock) ist lückenhaft/inkonsistent (springt 0→4→6, Food-Consumption-Schritt fehlt) und mehrere GDD-Stellen (§8b, §13, §14, §15) referenzieren daraus veraltete Schrittnummern — Docblock zuerst korrigieren, danach GDD-Referenzen in eigenem Pass nachziehen

### Offene Pfad-Paritäts-Fragen (Kenntnisse/Hangar/Cantina)

Design-Entscheidung vom 2026-07-20 bleibt gültig (Analytiker = passiver Multiplikator, Pilot = aktive Burst-Beschaffung, Konsul = aktive Konversion), jetzt in GDD §4b ausformuliert. 1b/1c haben einen Teil der ursprünglich als blockierend markierten Punkte bereits gelöst (Losgröße, Zweitinstanz-Bezugsquelle, `BotStrategy`-Fix, Instanz-Decay-Bug). Verbleibend offen:

- [x] Kenntnisse-Sekundäreffekt-Matrix, größter Teil — die Aussage „keine Ressourcen-/AP-Boni" ist überholt: 6 von 7 Kenntnissen haben inzwischen einen hartverdrahteten Primäreffekt (Bau-AP-Rabatt, Organika-/Regolith-Produktion, Cantina-Slots, Sturm-/Instabilitäts-Risiko — siehe Stufe 1 oben + Branch `design/encounters-and-defense`, 2026-08-16). Offen bleibt nur die feinere Kosten-Differenzierung je Kenntnis/Level (siehe GDD Anhang A.4 „Kenntnisse-Boni komplett ausarbeiten") — `config/knowledge.php → levelup_costs`/`credits` sind weiterhin für alle 7 Kenntnisse identisch
- [ ] Post-Phase-1-Ökonomie-Erholung (Kollaps bei mehreren Rang-2/3-Beratern gleichzeitig) — **Stand 2026-08-17, nach heutigen Fixes (Missions-Credits +40-45%, Regolith-Ertrag +25-30%, Bot-Berater-Deckel 3→4 gelöst) erneut durchgerechnet:** kein einzelner Zahlenwert ist die Ursache, sondern eine strukturelle Lücke zwischen den Pfaden. Pfad A (Cantina/Corvan-Organika-Verkauf) liefert ~180-320 Cr/Tick und überkompensiert (2/10 Läufe wachsen auf 1800-2800+ Cr); Pfad B (Sciencelab/Hangar ohne Cantina) hat **keinen** vergleichbaren Kanal — nur ~110-155 Cr/Tick Einnahmen gegen ~150-200 Cr/Tick Berater-Unterhalt bei 4 Beratern, strukturell negativ (8/10 Läufe kleben nahe 0 Cr fast die ganze Laufzeit). Zwei kleine, vom Owner freigegebene Zahlen-Fixes für die nächste Session: `config/game.php → advisor.upkeep[3]` 50→35 (Z.332, 4×Rang3 dann 140 statt 200 Cr/Tick), `relay_bonus_per_uplink_level` 35→45 (pfadneutral, stärkt gezielt Pfad B). **Owner-Entscheidung 2026-08-17: sowohl Analytik- (Sciencelab) als auch Hangar-Pfad brauchen ein EIGENES Credits-Einkommen**, unabhängig davon ob/wann die Cantina gebaut wird (Randfall: gar nicht oder erst spät) — eigener Design-Schritt, kein reiner Zahlen-Fix, in einer der nächsten Sessions zusammen mit den übrigen offenen Punkten zu besprechen.
- [ ] Bar/Cantina: Verkaufsrichtung als dritter Angebotstyp (eigene Owner-Entscheidung, revidiert die Handelsvertrag-Einführung vom 2026-07-19 teilweise) + Tauschrichtung nach Bestand wählen statt würfeln (Give = größter Überschuss, Get = knappste Ressource)
- [ ] Zweite Hangar-Instanz kostet den vollen `build_cost` (80 Rg) statt der 25 % Level-Up-Kosten (`ColonyController::placeBuilding`) — Bootstrap-Zirkel, den `harvester.max_level = 1` verschärft; betrifft ebenso Wohnhabitat-Instanzen
- [ ] Drohne hat 3 ungegatete Missionen, der Frachter genau 1 (`mission_supply_run`) — die anderen drei hängen an Kenntnissen, also am Analytik-Labor

---

## Phase 4: "Das Spiel vertiefen"
*(nach Phase 3)*

**Ziel:** Spieler, die das Basisspiel kennen, bekommen neue Strategiepfade und Interaktionsebenen.

**Voraussetzung:** Balance-Kalibrierung der Phase-3o-Ökonomie (via `PlaytestBot`) abgeschlossen — kein menschlicher Playtest geplant (CLAUDE.md, Owner-Entscheidung). Ohne belastbare Bot-Daten sind die Design-Entscheidungen in Phase 4 zu unsicher — insbesondere NPC-Vereinbarungs-Balance hängt von Beobachtungen aus stabilen Bot-Runs ab.

- [ ] **Progressive Discovery System** (GDD §17) — Drei miteinander verwandte Mechaniken die als roter Faden durch den Run laufen:
  - **Almanach-Grundstruktur:** Neue Tabellen `almanac_articles` + `run_almanac_unlocks`; Freischalt-Trigger-System; INNN-Benachrichtigung "Neuer Almanach-Artikel freigeschaltet"; Wissensbonus beim ersten Lesen (einmalig pro Run); Config-Block `config/almanac.php`. Erster Implementierungsschritt, keine Abhängigkeiten.
  - **Objective Discovery via Sol-Threshold:** Neue Spalten `revealed_at_tick` + `reveal_trigger` auf `run_objectives`; "Unbekannt"-Zustand im Objectives-Screen (Fragezeichen-Icon); gestaffelte Enthüllung der Phase-2-Objectives bis spätestens Sol +15 nach Phasenübergang (Fallback); Sol-Threshold-Fallback als Sicherheitsnetz. Zweiter Implementierungsschritt.
  - **Advisor Dialogs:** Neue Tabelle `advisor_dialogs`; Dialog-Lifecycle (pending → offered → accepted/declined/expired); AP-Kosten beim Annehmen; Config-Block `config/advisor_dialogs.php`; Tick-Schritt-7-Integration; erster Katalog: 3–5 Dialog-Definitionen je Berater-Typ. Dritter Implementierungsschritt, setzt Almanach + Objective Discovery voraus.
  - Schema-Erweiterung `runs.almanac_read_bonuses` (JSON) + `config/game.php → progressive_discovery`-Block.
  - Design-Voraussetzung: Almanach-Artikel-Texte via `content-writer` erstellen (mindestens 10 Artikel für Phase-4-Launch: 2 immer verfügbar, 4 fortschrittsabhängig, 4 entdeckungsabhängig).
- [ ] **Multiplayer-Lobby & Multi-Run-Support** — `game.run.allow_multiple` Config-Flag ist vorbereitet, der „Neuen Run starten"-Button im Lobby-Screen existiert (disabled). Für echtes Multi-Run-Support fehlt:
  - Run-Erstellungsflow: `OnboardingService::setupNewPlayer()` alloziert immer einen neuen Planeten + neue Kolonie; für einen zweiten Run muss das isoliert aufrufbar sein ohne Neu-Registrierung
  - `LobbyController::start()` muss konkrete `run_id` aus dem Formular auswerten (aktuell nimmt er einfach den ersten ausstehenden Run)
  - Session-Switching: wenn mehrere aktive Runs existieren, muss `activeIds.colonyId` beim Wechsel angepasst werden
  - Für echtes Multiplayer (mehrere User pro Run): `run_players`-Pivot-Tabelle (`run_id`, `user_id`, `joined_at`); Run-Status-Logik überarbeiten (tick feuert wenn alle Spieler bestätigt haben oder Timeout abläuft — `game.run.playbymailmode`)
- [ ] **Berater als Informationsebene** (GDD §13) — Jeder Berater liefert QoL-Informationen in seinem zugehörigen Screen: Baumeister → Decay-Prognosen in Colony-View; Analytiker → AP-Fluss-Prognose im Techtree; Konsul → kontextuelle Händler-Einschätzung in Cantina; Raumfahrer → Reisezeitprognose in Systemkarte; Stratege → Ziel-Erreichbarkeits-Prognose im Run-Ziel-Panel. Reine UI-Logik, keine neuen Datenpunkte nötig. Setzt abgeschlossene Phase-3o-Balance-Kalibrierung voraus.
- [ ] **Berater-Spezialfähigkeit (CC Lv4-Gate)** — Berater können ab CC Lv4 eine einmalige Spezialfähigkeit pro Tag aktivieren — sofort spürbare taktische Option (z.B. Baumeister: Notfall-Reparatur ohne AP-Kosten; Stratege: temporäre Kampfbonus-Runde); Design-Sprint nötig für konkrete Fähigkeiten je Beratertyp
- [ ] **NPC-Vereinbarungen** — `innn_message_types.relationship_effect` für Nexus-Beziehungsstufen auswerten; `treaty_signed`-Moral-Event für Handels-/Schutzabkommen mit NPC-Fraktionen (Händler, Schmuggler) aktivieren; kein Krieg/Allianz-System (inkompatibel mit Singleplayer-Roguelike-Konzept, GDD §1.1). `war_declared` als Moral-Event-Key deprecaten.
- [ ] **Gruppen/Gilden** — Datenmodell für Gruppen (kein Schema vorhanden); Grundlage für `restriction = 1` im Handelssystem; bewusst einfach gehalten: gründen, beitreten, verlassen
- [ ] **DB-Cleanup: `race_id` entfernen** — Rassen wurden konzeptuell abgekündigt (GDD §3, zusammen mit ENrg/LNrg/ANrg). `race_id` auf `users`-Tabelle ist historisches Schema ohne Auswertung → Migration zum Entfernen der Spalte.
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
