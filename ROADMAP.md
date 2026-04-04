# Nouron — Roadmap

## Phase 1b: Laminas → Laravel Migration

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

| Problem | Ort | Aufwand |
|---|---|---|
| `PersonellService::hire` — `$this->resourcesService` nicht deklariert → Fatal Error wenn `dev_mode=false` | `app/Services/Techtree/PersonellService.php` | Klein |

---

### Prio 2: Fehlende UI für vorhandene Services

Die folgenden Services sind implementiert, aber ohne UI — Spieler können diese Funktionen nicht nutzen:

- [ ] **Advisor-Management-UI** — Route `/advisors`, Controller, Blade-View für hire/fire/assign
  - `PersonellService` (hire, fire, assignToFleet, unassignFromFleet) ist fertig
- [ ] **Colony-UI** — Route `/colony`, Controller, Blade-View für Koloniewechsel und Umbenennung
  - `ColonyService` ist fertig
- [ ] **Forschungshandel-View** — Route `/trade/researches`, Controller-Methoden, Blade-View
  - `TradeGateway::addResearchOffer/removeResearchOffer` ist fertig
- [ ] **User-Profil / Einstellungen** — Passwort ändern, Display Name ändern
  - aktuell nur TODO-Platzhalter in `resources/views/user/settings.blade.php`

---

### Prio 3: Spielmechaniken vervollständigen

- [ ] **Laravel Scheduler einrichten** — `game:tick` Artisan-Command muss automatisch ausgeführt werden
  - `app/Console/Kernel.php` um Schedule-Eintrag erweitern (täglich im Berechnungsfenster 3–4 Uhr)
  - `TickService::calculationIsRunning()` ist bereits implementiert
- [ ] **Interstellare Flottenbewegung freischalten** — aktuell explizit im `FleetController::storeOrder` gesperrt
  - `GalaxyService::getPath` unterstützt systemübergreifende Pfadberechnung bereits
- [ ] **Fleet-Orders im UI vervollständigen** — `hold`, `convoy`, `defend`, `join`, `devide` sind in `FleetService::addOrder` implementiert, aber `storeOrder`-Validator lässt nur `move|trade|attack` durch
- [ ] **Flotten auf Galaxiekarte** — Layer 3 in `GalaxyController::getMapData` ist vorbereitet, aber nie befüllt

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

## Phase 3: Neukonzeption
*(nach Phase 2, noch zu definieren)*

### Vorgemerkte Themen für Phase 3

Die folgenden Punkte sind bewusst noch nicht detailliert ausgearbeitet — sie erfordern entweder Konzeptarbeit oder setzen einen stabilen Spielablauf aus Phase 2 voraus.

#### Content & Balancing

- [ ] **Rework Gebäude & Forschungen** — Überprüfung und Überarbeitung von Kosten, Produktionswerten, Voraussetzungsketten und Balance aller 25 Gebäude und 10 Forschungen

#### UI & Darstellung

- [ ] **Frontend/UI-Umbau** — Grundsätzliche Überarbeitung der UI-Struktur und Navigation; konkrete Ziele noch zu definieren
- [ ] **UI-Details & Artwork** — Icons, Illustrationen, Hintergrundgrafiken, Fraktions-/Rassen-Artwork; konkrete Assets noch zu definieren
- [ ] **Ingame-Almanach** — Nachschlagewerk für Spielregeln, Gebäude, Forschungen, Einheiten, Fraktionen und Spielwelt-Lore; zugänglich aus dem Spiel heraus

#### Onboarding

- [ ] **Onboarding / Tutorial im UI** — Geführte Einführung für neue Spieler; erklärt Ressourcen, Techtree, Flotten und Handelsmechaniken schrittweise im Spiel; konkrete Form (interaktiv, Tooltip-gestützt, eigenständige Tour) noch zu entscheiden
