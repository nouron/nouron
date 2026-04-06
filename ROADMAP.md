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

## Phase 3: "Das Spiel zeigen"
*(nach Phase 2)*

**Ziel:** Das Spiel ist für externe Spieler zugänglich, verständlich und rund.

Dieser Schnitt macht Sinn, weil Phase 2 die Mechaniken implementiert und stabilisiert, Phase 3 aber das Spiel für Menschen lesbar und spielbar macht, die keinen Entwicklerhintergrund haben. Ohne diesen Schritt ist kein sinnvoller Playtest mit echten Spielern möglich — und ohne Playtest-Feedback sind Phase-4-Entscheidungen (Diplomatie, Rassen, Gruppen) zu unsicher, um sie zu committen.

---

### Phase 3a: Content & Balancing + Kernmechaniken

- [ ] **Gebäude- und Forschungskosten kalibrieren** — Kosten, Produktionswerte und Voraussetzungsketten aller 25 Gebäude und 10 Forschungen auf Basis des Decay- und AP-Systems aus Phase 2 überprüfen und anpassen
- [ ] **Supply-Kosten auf Plausibilität testen** — offene Frage: kann ein Anfänger einen Battlecruiser unterhalten? Supply-Cap-Modell (CC_flat 15 + HousingLevel × 8, max 200) gegen Schiffskosten abgleichen
- [ ] **Forschungshandel-Mechanik definieren und umsetzen** — Designfrage: Level-Transfer, Wissenstransfer oder Lizenz-Modell; ADR erforderlich vor Implementierung (`docs/adr/`)
- [ ] **Interstellare Flottenbewegung freischalten** — aktuell in `FleetController::storeOrder` explizit gesperrt; Wurmloch/Sternentor-Mechanik designen (ADR erforderlich vor Implementierung); `GalaxyService::getPath()` unterstützt systemübergreifende Pfade bereits

---

### Phase 3b: UI-Überarbeitung + Almanach

- [ ] **Frontend-Überarbeitung** — Navigation, Hauptscreens, Ressourcenleiste; Klarheit vor Artwork
- [ ] **Techtree visuell überarbeiten** — SVG-Baum ist schwer lesbar und nicht mobiloptimiert; Ziel: max. 3 Spalten, horizontales Scrollen, lesbarer auf kleinen Bildschirmen
- [ ] **Nav-Label "Techtree" → "Kolonie"** + Status-Panel neben Grid (laufende Bauten, AP-Budget, Top-3-Produktion) — falls in Phase 2 noch nicht umgesetzt
- [ ] **`/colony`-Screen als separater Bildschirm** — Aggregat-Übersicht (aktive Vorgänge, Planetenkontext), losgelöst vom Techtree-Grid
- [ ] **Moralanzeige im UI** — Mechanik ist in Phase 2 vorhanden, UI fehlt noch
- [ ] **Ingame-Almanach** — Nachschlagewerk für Gebäude, Forschungen, Schiffstypen und Spielregeln; initial einfache Blade-Seite mit Daten aus den Config-Dateien

---

### Phase 3c: Onboarding & Tutorial

- [ ] **Geführte Einführung für neue Spieler** — Form noch offen: interaktive Tour oder Tooltip-gestützte Einführung
- [ ] **Cold-Start-Problem lösen** — neuer Spieler sieht 25 leere Techtree-Kacheln ohne Orientierung; erster Schritt muss klar sein
- [ ] **Visuelle Hervorhebung des "nächsten sinnvollen Schritts"** — für Anfänger ohne Spielerfahrung; kein Bevormunden für erfahrene Spieler

---

### Bewusste Designentscheidungen (nicht umsetzen in Phase 3)

| Thema | Entscheidung | Begründung |
|---|---|---|
| **Modulare Schiffe** | Nicht implementieren | Die Kolonie steht im Vordergrund. Die 6 Schiffstypen + 4 Attribute erzeugen bereits sinnvolle Kompositionsentscheidungen. Bei 1 Tick/Tag wäre der Feedback-Loop für Modul-Fehler zu langsam. |
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

- [ ] **Diplomatie-System** — `innn_message_types.relationship_effect` auswerten; diplomatische Zustände (Krieg, Frieden, Allianz, Neutralität); Moral-Events `war_declared`/`treaty_signed` aktivieren; AP-Kosten gemäß Designprinzip (Kriegserklärung teurer als Handelsvertrag)
- [ ] **Gruppen/Gilden** — Datenmodell für Gruppen (kein Schema vorhanden); Grundlage für `restriction = 1` im Handelssystem; bewusst einfach gehalten: gründen, beitreten, verlassen
- [ ] **Rassen-System überarbeiten** — `race_id` ist im Schema, wird nicht ausgewertet; rassenspezifische Effekte definieren; Designfrage erst nach Phase-3-Playtest beantwortbar
- [ ] **Steuersystem** — `steuerfaktor` in Moral-Formel als Platzhalter (= 0); GDD-Design steht; Implementierung setzt stabile Moral-Balance aus Phase 3 voraus
- [ ] **Benannte Chef-Berater** — individuelle Charaktere mit Fähigkeiten und Namen; aktuelles Berater-Modell ist als Fundament ausgelegt (GDD §12)
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
