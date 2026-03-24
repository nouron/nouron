# Changelog

## 2026-03-24 (Techtree)

- **Techtree-Modul migriert (Schritt 10):** 10 Eloquent-Modelle erstellt (`Building`, `BuildingCost`, `ColonyBuilding`, `LockedActionpoint`, `Personell`, `PersonellCost`, `Research`, `ResearchCost`, `Ship`, `ShipCost`). `AbstractTechnologyService` als gemeinsame Basis für alle Techtree-Services implementiert (Prerequisite-Checks, AP-Investment, levelup/leveldown, Kostenzahlung). Konkrete Services `BuildingService`, `ResearchService`, `ShipService`, `PersonellService` (inkl. AP-Verwaltung, lockActionPoints, hire/fire) und `TechtreeColonyService` (Gesamtübersicht mit Merge aus Master- und Kolonie-Tabellen). `TechtreeController` mit 3 Routen unter `/techtree` (index, technology-Detail-Popup, order). Blade-Views `techtree/index.blade.php` und `techtree/technology.blade.php`. Services in AppServiceProvider registriert. 22 neue Feature-Tests, Gesamtstand: 185 Tests grün.

## 2026-03-23 (Fleet)

- **Fleet-Modul migriert (Schritt 9):** Eloquent-Modelle `Fleet`, `FleetShip`, `FleetResearch`, `FleetPersonell`, `FleetOrder`, `FleetResource` sowie `ColonyShip`, `ColonyResearch`, `ColonyPersonell` (für transferTechnology). `App\Services\FleetService` portiert alle Methoden: getFleet, saveFleet, saveFleetOrder, getFleetOrdersByFleetIds, transferShip/Research/Personell/Technology/Resource, getFleetShip/Research/Personell/Resource (Singular + plural), getOrders, getFleetsByUserId/EntityId/Coords, getFleetTechnologies. `App\Http\Controllers\Fleet\FleetController` mit 5 Routen unter `/fleet` (index, config, addtofleet, technologies, resources). Blade-Views `fleet/index.blade.php` und `fleet/config.blade.php`. `Colony::getCoords()` ergänzt (wurde für transferTechnology benötigt). 23 Feature-Tests grün (2 skipped: addOrder/transferResource wie im Original), Gesamtstand: 163 Tests grün.

## 2026-03-23 (Trade)

- **Trade-Modul migriert (Schritt 8):** Eloquent-Modelle `TradeResource`, `TradeResearch` (Basistabellen, composite PK, kein incrementing) sowie `TradeResourceView`, `TradeResearchView` (lesen aus `v_trade_resources` und `v_trade_researches`). `App\Services\TradeGateway` portiert alle Operationen: getResources, getResearches, addResourceOffer, addResearchOffer, removeResourceOffer, removeResearchOffer — mit Ownership-Check via ColonyService. `App\Http\Controllers\Trade\TradeController` mit 5 Routen unter `/trade`. Blade-Views unter `resources/views/trade/`. Service in AppServiceProvider registriert. 18 neue Feature-Tests, Gesamtstand: 140 Tests grün.

## 2026-03-23

- **INNN-Modul migriert (Schritt 7):** `InnnMessage`, `InnnMessageView`, `InnnEvent`, `InnnNews` (Eloquent). `MessageService` (getMessage, getInboxMessages, getOutboxMessages, getArchivedMessages, sendMessage, setMessageStatus) und `EventService` (getEvent, getEvents, createEvent). `MessageController` mit 8 Routen unter `/messages`. 4 Blade-Templates (inbox, outbox, archive, compose). 39 neue Feature-Tests, 122/122 grün.

## 2026-03-21

- **Code-Analyse:** Vollständige Analyse der Laminas-Codebasis durchgeführt (373 PHP-Dateien, 11 Module, 42 Controller, 35 Table-Klassen, 98 Tests). Migrationsoptionen Laravel, PHP-Microframework und Python/Flask gegenübergestellt — Laravel als empfohlener Migrationspfad identifiziert.
- **Branch-Management:** Branch `claude/analyze-test-coverage-3rtlh` in `laminas-migration` gemergt (Fast-Forward). Branch lokal und remote gelöscht.
- **Tagging:** Tag `laminas-migration-finished` auf den aktuellen Stand von `laminas-migration` gesetzt. Tag `legacy-zf2-final` auf den letzten ZF2-Commit (`b325183`) gesetzt, um das Ende der Legacy-Version zu kennzeichnen.
- **README aktualisiert:** Quickstart auf PHP 8 / `composer install` / `./vendor/bin/phpunit` aktualisiert, Zend Framework 2 durch Laminas ersetzt, Google+ entfernt, Copyright-Jahr auf 2026 aktualisiert.
- **Pull Request erstellt:** PR von `laminas-migration` → `master` eröffnet, der die vollständige ZF2→Laminas+Bootstrap5-Migration zusammenfasst.
- **INNN Bugfix:** Schema-Inkonsistenz behoben — `nouron.db` verwendete camelCase-Spalten (`isRead`, `isArchived`, `isDeleted`) statt snake_case wie in `schema.sqlite.sql` und `test.db`. Tabelle und View in `nouron.db` neu erstellt, `MessageService.php` bleibt bei snake_case. Alle 19 INNN-Tests grün.
- **Testdaten:** Reiche Testdaten in `nouron.db` eingefügt: 3 neue Kolonien (Homer, Marge, Bart 2nd), 3 neue Flotten, 19 neue Nachrichten, 14 Events, 4 News-Einträge, 15 Handelsrouten für Ressourcen und Forschungen.
- **PR gemergt:** `laminas-migration` → `master` gemergt (Merge-Commit `7f3cac3`). Tag `laminas-migration-finished` auf den Merge-Commit aktualisiert.

## 2026-03-23 (INNN)

- **INNN-Modul migriert (Schritt 7):** Eloquent-Modelle `InnnMessage`, `InnnMessageView` (liest aus `v_innn_messages`-View mit Sender/Empfanger-Namen), `InnnEvent`, `InnnNews`. `App\Services\MessageService` portiert alle Methoden: getMessage, getInboxMessages, getOutboxMessages, getArchivedMessages, sendMessage, setMessageStatus. `App\Services\EventService` mit getEvent, getEvents, createEvent. `App\Http\Controllers\INNN\MessageController` vereint Inbox, Outbox, Archiv, Compose, Send, React, Remove. Blade-Views unter `resources/views/messages/`. Routen unter `/messages` (auth-geschützt). Services in AppServiceProvider registriert. 39 neue Feature-Tests, Gesamtstand: 122 Tests grün.

## 2026-03-23 (Galaxy)

- **Galaxy-Modul migriert (Schritt 6):** `App\Models\GlxSystem` (liest aus `v_glx_systems`-View), `App\Models\GlxSystemObject` (liest aus `v_glx_system_objects`-View). `App\Services\GalaxyService` portiert alle Methoden aus `Galaxy\Service\Gateway`: getSystems, getSystem, getSystemObjects, getSystemObject, getSystemObjectByColonyId, getSystemObjectByCoords, getObjectsByCoords, getColoniesByCoords, getSystemBySystemObject, getSystemByObjectCoords, getDistance, getDistanceTicks, getPath (Bresenham mit Speed). `GalaxyController` vereint IndexController, SystemController und JsonController (index, showSystem, getMapData). Blade-Views `galaxy/index.blade.php` und `galaxy/system.blade.php`. Galaxy-Routen unter `/galaxy` (auth-geschützt). Config-Werte `galaxy_view` und `system_view` in `config/game.php`. 36 neue Feature-Tests grün, Gesamtstand: 83 Tests grün.

## 2026-03-23

- **Resources-Modul migriert (Schritt 5):** `App\Models\Resource`, `ColonyResource`, `UserResource` (Eloquent). `App\Services\ResourcesService` mit allen Methoden (getResources, getColonyResources, getUserResources, getPossessionsByColonyId, check, payCosts, increaseAmount, decreaseAmount). `JsonController` mit 3 Endpunkten (GET /resources, /resources/colony/{id}, /resources/resourcebar). Blade-Partial für Ressourceleiste. 15 Feature-Tests grün. Bugfix: Composite-PK-Problem bei ColonyResource-Updates gelöst via `DB::table('colony_resources')->updateOrInsert(...)` statt Eloquent-`save()`.
- **Colony-Modul migriert (Schritt 4):** `App\Models\Colony` (Eloquent, liest aus `v_glx_colonies`-View), `App\Services\ColonyService` (alle 8 Methoden aus Laminas-Port: getColonies, getColony, getColoniesByUserId, checkColonyOwner, getPrimeColony, setActiveColony, setSelectedColony, getColoniesByCoords, getColonyByCoords, getColoniesBySystemObjectId). 24 Feature-Tests grün.
- **Test-Infrastruktur verbessert:** `DB_FOREIGN_KEYS=false` in `.env.testing` — SQLite lässt `PRAGMA foreign_keys = OFF` innerhalb von Transaktionen nicht zu, daher wird FK-Enforcement global für Tests deaktiviert. Die Testdaten aus `testdata.sqlite.sql` sind bereits konsistent. Colony-Tests seeden via `$this->app->make(TestSeeder::class)->run()` in `setUp()` innerhalb der offenen Test-Transaktion.
- **User/Auth-Modul migriert (Schritt 3):** `App\Models\User` (Eloquent, `user_id` PK, bcrypt-kompatibel), `LoginController`/`RegisterController`/`UserController`, Blade-Views für Login/Register/User-Profil, `routes/web.php` mit Guest- und Auth-Routen, angepasste `UserFactory`.
- **Test-Infrastruktur komplett:** `TestSeeder` spielt die Simpsons-Testdaten aus `data/sql/testdata.sqlite.sql` in die `:memory:`-DB ein — Laravel Feature Tests nutzen dieselbe kanonische Testbasis wie die Laminas Unit Tests. `DatabaseSeeder` ruft `TestSeeder` auf. 8/8 Laravel Feature Tests grün.

## 2026-03-22

- **Agenten-Definitionen angereichert:** Alle 7 `.claude/agents/`-Definitionen mit projektspezifischem Wissen ergänzt (Testpfade, PHPUnit-Binary, Factory-Pattern, Base-Classes, SQLite-Limitierungen, JS-Module, Nouron-2026-Vision).
- **Phase 1b definiert:** Laminas → Laravel als neue Phase 1b in CLAUDE.md und ROADMAP.md aufgenommen. ROADMAP.md mit 12-stufigem Migrationsplan erstellt (Bestandsaufnahme: 373 PHP-Dateien, 94 Factories, 31 Tables, 108 Tests).
- **Laravel 12 aufgesetzt (Schritt 0):** `laravel/framework ^12.0` neben Laminas installiert, PHPUnit 9.5 → 11.5 angehoben, `laminas/laminas-log` wegen psr/log-Konflikt entfernt, `AbstractService` auf Noop-Logger umgestellt. Verzeichnisstruktur (app/, bootstrap/, routes/, database/, storage/) und Entry Point eingerichtet.
- **DB-Migrations erstellt (Schritt 1):** 35 Laravel-Migration-Dateien + 6 Views aus `schema.sqlite.sql` übersetzt, korrekte FK-Reihenfolge, `colony_buildings` FK-Fehler korrigiert, `MIGRATION_LOG.md` erstellt.
- **Core-Schicht implementiert (Schritt 2):** `TickService`, `ValidatesId`-Trait, `BaseController` als Laravel-Äquivalente für `Core\Service\Tick`, `AbstractService._validateId()` und `IngameController`. `config/game.php` für Spielkonfiguration angelegt.
