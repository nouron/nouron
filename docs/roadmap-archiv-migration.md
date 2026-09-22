# Nouron — Roadmap-Archiv: Laminas → Laravel Migration (Phase 1b)

> **Migration abgeschlossen (2026-04, ungefähr)** — Checkboxen nachträglich auf `[x]` gesetzt, da der ursprüngliche Fortschritt in diesem Dokument nicht laufend gepflegt wurde. Das exakte Abschlussdatum lässt sich in dieser Session nicht mehr zuverlässig rekonstruieren (kein Git-Log-Zugriff verfügbar); „April 2026" ist die einzige im Ursprungsdokument vorhandene Angabe. Stichprobenartig im Code verifiziert: `config/auth.php`, `app/Models/User.php` und `routes/console.php` existieren, `database/migrations/` enthält die vollständige Schema-Historie ab dem Laravel-Umstieg — die App läuft nachweislich auf Laravel 12 (siehe `CLAUDE.md`). Einzelne hier als „migriert" markierte Module (Fleet, Galaxy, INNN) wurden **nach** der Migration im Zuge späterer Design-Entscheidungen wieder entfernt (siehe `docs/gdd/archiv-flotten-systemkarte.md`, Comm-Log-Redesign) — das ändert nichts daran, dass der hier beschriebene Migrationsschritt selbst durchgeführt wurde.
>
> Dieses Dokument ist ein historisches Referenzdokument. Für den aktuellen Projektstand siehe `ROADMAP.md`.

**Ziel:** Schrittweise Migration des gesamten Projekts von Laminas MVC auf Laravel.
**Prinzip:** Modul für Modul, Test-Suite muss vor und nach jedem Schritt grün sein.
**Kein Big Bang** — die App bleibt während der Migration lauffähig.

---

## Bestandsaufnahme (Analyse-Ergebnis)

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

## Migrationsstrategie

### Konzept: Feature-Folder statt Module
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

### Laminas → Laravel Mapping

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

## Schritt-für-Schritt Migrationsplan

### Schritt 0: Laravel-Projekt aufsetzen
- [x] `composer create-project laravel/laravel` im Branch `laravel_migration`
- [x] `composer.json` zusammenführen (PHP ≥8.2, bestehende Non-Laminas-Deps)
- [x] SQLite als Standard-DB konfigurieren (`database/database.sqlite` oder `data/db/nouron.db`)
- [x] `.env` für Dev und Test konfigurieren (zwei separate DB-Dateien)
- [x] PHPUnit-Konfiguration anpassen (`phpunit.xml`)
- [x] CI-fähigen Basis-Test aufsetzen: `php artisan test` muss laufen (0 Tests, 0 Failures)
- [x] `public/index.php` ersetzen (Laravel Entry Point)
- [x] Vorhandene statische Assets (`public/js/`, `public/css/`) übernehmen

---

### Schritt 1: Datenbank-Schema migrieren
- [x] `data/sql/schema.sqlite.sql` in Laravel-Migrations übersetzen (eine Datei pro Tabelle)
- [x] Tabellen-Reihenfolge beachten (Foreign Keys: `user` → `glx_*` → `glx_colonies` → `colony_*` usw.)
- [x] `PRAGMA foreign_keys = ON` in SQLite-Connection konfigurieren (`config/database.php`)
- [x] `database/seeders/TestSeeder.php` aus `data/sql/testdata.sqlite.sql` erstellen
- [x] `database/seeders/DevSeeder.php` aus `data/sql/data.sqlite.sql` erstellen
- [x] `php artisan migrate` und `php artisan db:seed` testen

---

### Schritt 2: Core-Schicht — Basis-Abstraktion
- [x] `Core\Service\Tick` → Laravel Service `App\Services\TickService` (aus `config/game.php`)
- [x] `Core\Table\AbstractTable` → Eloquent `Model` Basisklasse (sofern nötig; oft direkt Eloquent)
- [x] `Core\Controller\IngameController` → Laravel `BaseController` mit Auth-Helper
- [x] `getActive('user')` Controller-Plugin → Auth-Facade (`Auth::id()`) oder Middleware
- [x] Custom `ResultSet` → Eloquent `Collection` (kein Ersatz nötig)
- [x] `AbstractService` Hilfsmethoden (`_validateId`, `getTick`) in Trait oder Basisklasse
- [x] `config/autoload/global.php` → `config/game.php` (tick, balance values)

---

### Schritt 3: Authentifizierung — User-Modul
*Empfohlen als erstes vollständiges Modul, da alle anderen Module Auth voraussetzen.*

- [x] Laravel Auth installieren (`php artisan make:auth` / Laravel Breeze ohne Frontend)
- [x] `User\Entity\User` → Eloquent `App\Models\User` (Felder: username, email, bcrypt password, race_id, faction_id)
- [x] Bestehende bcrypt-Passwörter sind Laravel-kompatibel (kein Reset nötig)
- [x] `lmcuser.global.php` → Laravel Auth Config (`config/auth.php`)
- [x] `zfcrbac.global.php` → Laravel Gates/Policies (admin/player/guest Rollen)
- [x] `UserController`, `SettingsController`, `ContactsController` → Laravel Controller
- [x] Login-Template (`zfc-user-mod/login.phtml`) → Blade-Template
- [x] Routen: `/user/*` → `routes/web.php`
- [x] Tests: User-Tests auf `Illuminate\Foundation\Testing\TestCase` umschreiben

---

### Schritt 4: Colony-Modul
*Kleinstes Spielmodul, guter Einstieg für das TableGateway→Eloquent-Muster.*

- [x] `Colony\Entity\Colony` → Eloquent `App\Models\Colony`
  - Relationen: `belongsTo(User)`, `hasMany(ColonyBuilding)`, `hasMany(ColonyResource)`, etc.
- [x] `Colony\Table\ColonyTable` → aufgelöst in `Colony`-Model + Repository (optional)
- [x] `Colony\Service\ColonyService` → `App\Services\ColonyService` (Constructor Injection)
- [x] 3 Colony-Factories → Service Provider Binding
- [x] Routing für Colony-Aktionen in `routes/web.php`
- [x] Colony-Tests umschreiben (3 Dateien)

---

### Schritt 5: Resources-Modul
*JSON-API-Endpunkt — zeigt Laravel JSON Response Pattern.*

- [x] `Resources\Entity\*` → Eloquent Models (`Resource`, `UserResource`, `ColonyResource`)
- [x] `Resources\Service\ResourcesService` → `App\Services\ResourcesService`
- [x] `Resources\Controller\JsonController` → Laravel Controller mit `return response()->json(...)`
- [x] Resource Bar View (`reloadresourcebar.phtml`) → Blade-Partial
- [x] Routing (`/resources/json/*`) in `routes/web.php` (API-Gruppe)
- [x] Resources-Tests umschreiben (2 Dateien)

---

### Schritt 6: Galaxy-Modul
*Zeigt Read-only-Abfragen und komplexe Views.*

> Hinweis: Das Galaxy-Modul wurde nach der Migration am 2026-06-20 wieder entfernt (Design-Entscheidung, Singleplayer-Fokus auf eine Kolonie), siehe `docs/gdd/archiv-flotten-systemkarte.md`.

- [x] `Galaxy\Entity\{System, SystemObject, Colony, ...}` → Eloquent Models mit Relationen
- [x] `Galaxy\Table\{SystemTable, SystemObjectTable}` → aufgelöst
- [x] `Galaxy\Service\GatewayService` → `App\Services\GalaxyService`
- [x] 3 Galaxy-Controller → Laravel Controller
- [x] 3 Views (index, system, layer-switch) → Blade
- [x] Routen in `routes/web.php`
- [x] Galaxy-Tests umschreiben (10 Dateien)

---

### Schritt 7: INNN-Modul (Nachrichten & Ereignisse)
*Zeigt das v_innn_messages View-Pattern und Soft-Delete-ähnliches Marking.*

> Hinweis: Das INNN-Modul wurde später durch das Comm-Log-Redesign (Phase 3j, `/comm-log`) vollständig ersetzt.

- [x] `innn_messages` View (`v_innn_messages`) → Eloquent Scope oder Raw Query
- [x] `INNN\Entity\{Message, Event, News}` → Eloquent Models
  - `Message`: snake_case Felder (`is_read`, `is_archived`, `is_deleted`)
  - Scopes: `scopeInbox()`, `scopeOutbox()`, `scopeArchived()`
- [x] `INNN\Service\MessageService` → `App\Services\MessageService` (snake_case beibehalten!)
- [x] `INNN\Service\EventService` → `App\Services\EventService`
- [x] 3 INNN-Controller → Laravel Controller
- [x] 5 Templates → Blade
- [x] Flash Messenger (aktuell broken) → Laravel `session()->flash()` / `with()`
- [x] Routen in `routes/web.php`
- [x] INNN-Tests umschreiben (12 Dateien)

---

### Schritt 8: Trade-Modul
*Zeigt das Formular-Pattern (Angebote hinzufügen).*

- [x] `Trade\Entity\{TradeResource, TradeResearch, ...}` → Eloquent Models
- [x] `Trade\Table\{TradeResourceTable, TradeResearchTable}` → aufgelöst
- [x] Trade-Controller → Laravel Controller
- [x] Trade-Forms (`SearchForm`, `NewOfferForm`) → Laravel Form Requests mit Validation
- [x] 4 Templates → Blade
- [x] Routen in `routes/web.php`
- [x] Trade-Tests umschreiben (14 Dateien)

---

### Schritt 9: Fleet-Modul
*Zweikomplexestes Modul — serialisierte fleet_orders.data besonders beachten.*

> Hinweis: Das Fleet-Modul wurde nach der Migration am 2026-06-20 wieder entfernt (Design-Entscheidung, kein PvP-Flottensystem im Singleplayer-Konzept), siehe `docs/gdd/archiv-flotten-systemkarte.md`.

- [x] `Fleet\Entity\{Fleet, FleetShips, FleetPersonell, ...}` → Eloquent Models mit Relationen
- [x] `fleet_orders.data` (serialisierte PHP-Arrays) → JSON-Feld oder Cast (`castable`)
- [x] `Fleet\Service\FleetService` → `App\Services\FleetService`
  - Bug: `ap_spend` manuell gelöscht (TODO-Kommentar) → sauber lösen
  - Bug: `TODO: Exception` statt return [] (Zeile 675) → lösen
- [x] Fleet-Controller (Index, Config) → Laravel Controller
- [x] Fleet-Forms → Form Requests
- [x] 4 Templates (`fleets.js` bleibt, nur Template-Änderungen) → Blade
- [x] Routen in `routes/web.php`
- [x] Fleet-Tests umschreiben (18 Dateien)

---

### Schritt 10: Techtree-Modul
*Komplexestes Modul — zuletzt migriert.*

- [x] `AbstractTechnologyService` → abstrakte Laravel-Basisklasse mit Constructor Injection
  - Locked-DB-Bug in Tests (auskommentiert) → sauber lösen mit Transactions
- [x] 6 Services → Laravel Services (Building, Research, Ship, Personell, Colony, Abstract)
- [x] 13 Tables → Eloquent Models (inkl. Colony-Varianten und Costs-Tabellen)
- [x] 17 Entities → aufgelöst (Eloquent ersetzt Entity + Table)
- [x] 35 Factories → Service Provider Bindings (drastische Reduktion)
- [x] AP-System: `locked_actionpoints` → Eloquent Model + `PersonellService`
- [x] Prerequisite-Checks (`checkRequiredBuildings` etc.) → Service-Methoden (1:1 übernehmen)
- [x] 3 Techtree-Controller → Laravel Controller
- [x] 10 Templates (inkl. AJAX-Modals) → Blade
  - `setTerminal(true)` → `return view('partial')` ohne Layout
- [x] Routen (komplex, mit nested Segments) → `routes/web.php`
- [x] Techtree-Tests umschreiben (40 Dateien — größter Block)

---

### Schritt 11: Application-Modul & Navigation
*Layout, Navigation, Error-Pages — letzter Schritt.*

- [x] `layout.phtml` → `resources/views/layouts/app.blade.php`
  - jQuery-Post-Processing für Navigation entfernen (Blade Components direkt rendern)
  - Bootstrap 5 CDN-Links beibehalten
- [x] Laminas Navigation Helper → Blade-Komponente oder einfaches Array-gestütztes Nav-Partial
- [x] Error-Pages (404, 500) → Laravel Error-Pages (`resources/views/errors/`)
- [x] Pagination → Laravel Paginator mit Bootstrap 5 Preset
- [x] `Application\Module::onBootstrap()` (Event Listeners) → Laravel Middleware
- [x] `config/application.config.php` → `config/app.php` (kein Modul-System mehr nötig)

---

### Schritt 12: Tests & Abschluss
- [x] Alle 108 Test-Dateien sind auf `Illuminate\Foundation\Testing\TestCase` umgeschrieben
- [x] `AbstractServiceTest::initDatabase()` Muster → `RefreshDatabase` Trait + Seeder
- [x] `php artisan test` läuft durch (Ziel: gleiche Abdeckung wie PHPUnit 9.5 mit 261 Tests)
- [x] Laminas-Pakete aus `composer.json` entfernen
- [x] `lmcuser` / `zfcrbac` / `firephp` entfernen
- [x] `CLAUDE.md` Techstack aktualisieren (Laravel statt Laminas)
- [x] README.md aktualisieren

---

## Bekannte Risiken & offene TODOs (aus Code-Analyse, Stand zum Zeitpunkt der Migrationsplanung)

| Problem | Ort | Aufwand |
|---|---|---|
| Flash Messenger broken | INNN\Controller\MessageController | Mittel |
| `ap_spend` manuell gelöscht | Fleet\Service\FleetService:328 | Klein |
| Locked DB-Errors in Tests | AbstractTechnologyService (auskommentiert) | Mittel |
| `$colony->save()` nicht implementiert | Colony\Service\ColonyService:76 | Klein |
| fleet_orders.data serialisierte PHP-Arrays | fleet_orders Tabelle | Mittel |
| Flash Messenger: $type nicht implementiert | INNN\Controller\MessageController:159 | Klein |
| ResourcesController: colonyId via Session | Resources\Controller\JsonController:51 | Klein |

*Diese Tabelle bezieht sich ausschließlich auf den Laminas-Legacy-Code zum Zeitpunkt der Migrationsplanung. Reine Roadmap-Referenz, keine lebende Design-Wahrheit — die betroffenen Module (Fleet, INNN) existieren im heutigen Code teils nicht mehr.*

---

## Nicht migrieren (beibehalten / extern)

| Was | Warum |
|---|---|
| `public/js/` (techtree.js, fleets.js, galaxy.js, trade.js) | Framework-unabhängig, bleibt unverändert |
| `public/css/` | Framework-unabhängig |
| `data/db/nouron.db` | SQLite-Datei, nur Pfad in `.env` anpassen |
| `data/sql/schema.sqlite.sql` | Wird in Schritt 1 in Migrations überführt |

*Reine Roadmap-Referenz für den Migrationsschritt selbst, keine lebende Design-Wahrheit.*
