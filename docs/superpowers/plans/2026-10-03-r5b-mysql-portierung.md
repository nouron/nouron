# R5b MySQL-Portierung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entwicklung, Tests, CI, PlaytestBot und Produktion laufen auf genau einer DB-Engine (MySQL); SQLite verschwindet aus dem Projekt.

**Architecture:** Zuerst alles dialektneutral machen, solange die Suite noch auf SQLite grün ist (portables SQL, idempotenter Referenzdaten-Seeder statt SQL-Dump, Migrations-Baseline). Erst danach wird die Suite auf MySQL umgestellt (ein kleiner, messbarer Schritt, Fehlerklassen werden dort getrennt abgearbeitet). Zuletzt bekommt der PlaytestBot eine gemeinsame DB `nouron_playtest`, in der jeder Lauf einen eigenen User samt Kolonie anlegt (wie mehrere Spieler in Produktion).

**Tech Stack:** PHP 8.4, Laravel 12, PHPUnit 11, MySQL 8 (nativ in WSL, kein Docker), GitHub Actions MySQL-Service.

**Spec:** `docs/adr/0005-produktions-datenbank-und-hosting.md` (Owner-Entscheidungen 2026-10-02/03: Laravel Cloud + MySQL, ein Dialekt, kein Docker, Bots teilen sich eine DB wie Spieler).

## Global Constraints

- Ein Dialekt: lokal, CI, Bot und Produktion alle MySQL. Kein SQLite-Fallback, kein Docker lokal (YAGNI, Owner 2026-10-03).
- Drei lokale Datenbanken auf einer Instanz: `nouron` (Dev), `nouron_test` (PHPUnit), `nouron_playtest` (PlaytestBot). Nur die beiden letzten dürfen von Tests/Bots zurückgesetzt werden.
- Agenten dürfen `migrate:fresh`/`db:seed` nie gegen `nouron` (Dev) ausführen — `DB_DATABASE` explizit setzen (Memory `feedback_agents_never_migrate_fresh_on_dev_db`).
- TDD: Test zuerst, rot sehen, dann Code (CLAUDE.md). Ausnahmen: reine Config-/Doku-Änderungen.
- Code/Kommentare Englisch; Doku, ROADMAP, CHANGELOG, ADRs Deutsch. Ein CHANGELOG-Block pro Tag, kurz.
- Nie auf `master` committen: Branch `feat/r5b-mysql`, Pre-commit-Hook (Pint) nie mit `--no-verify` umgehen, nie `git add -A`.
- Produktion wird nie mit Testdaten befüllt (R3/R4): Referenzdaten (Stammdaten) kommen aus dem idempotenten `ReferenceDataSeeder` (PHP-Datenstruktur + Config), Fixtures nur aus `data/sql/testdata.sql`.
- Parallele Tests: `php artisan test --parallel` (paratest), jeder Worker eigene Datenbank `nouron_test_test_N`.
- Schnelle Suite während der Arbeit: `bin/phpunit --testsuite=laravel-feature,laravel-unit`; volle Suite (+ `playtest`) vor dem PR.

- Scratchpad für Wegwerf-Skripte und Zwischen-DBs: `S=/tmp/claude-1000/-home-mg-workspace-nouron/48318171-30fb-453d-b495-f2ae3a3a041b/scratchpad` (nie committen). Der lokale `.env`-Wechsel auf MySQL erfolgt erst in Task 6; bis dahin laufen SQLite-Läufe mit explizitem `DB_CONNECTION=sqlite DB_DATABASE=…`, MySQL-Läufe mit `DB_CONNECTION=mysql DB_DATABASE=nouron_test`.

## Review Focus

Fehlerklassen, die der Wechsel SQLite → MySQL mit hoher Wahrscheinlichkeit aufdeckt und die kein bestehender Test gezielt prüft (die Tests dafür stehen in den genannten Tasks):

1. Referenzdaten werden auf einer DB mit Spielerdaten erneut eingespielt (jeder Deploy): `REPLACE INTO` würde Fremdschlüssel verletzen oder Kind-Zeilen löschen; der Seeder muss per Upsert idempotent sein und Spielerdaten unangetastet lassen (Task 4). Zusätzlich kollidiert der Unique-Index `(phase,row,column)` beim Upsert, wenn zwei Zeilen Positionen tauschen.
2. `SUM()`/`COUNT()`-Ergebnisse kommen unter MySQL als String (`"12"`) statt als int zurück; `assertSame(12, …)` und strikte Vergleiche in Services brechen (Task 6, Fehlerklasse B).
3. SQLite liefert ohne `ORDER BY` stabil nach rowid, MySQL nicht: Tests/Logik mit `first()`/`pluck()` ohne Sortierung werden flaky (Task 6, Fehlerklasse C).
4. Strict Mode: fehlende NOT-NULL-Werte, zu lange Strings und ungültige Datumswerte werden abgelehnt (SQLite akzeptiert alles) (Task 6, Fehlerklasse A).
5. `AUTO_INCREMENT` wird beim Transaktions-Rollback nicht zurückgesetzt: Tests, die konkrete neue IDs erwarten, hängen von der Reihenfolge ab (Task 6, Fehlerklasse D).
6. Parallele Bots auf derselben Kolonie: Die bisherigen festen Fixture-IDs (User 3, Kolonie 1) würden sich gegenseitig zurücksetzen (Task 7).

---

### Task 1: MySQL lokal einrichten und Umgebung dokumentieren

**Files:**
- Create: `docs/dev-setup-mysql.md`
- Modify: `.env.example:26-31` (DB-Block auf MySQL)

**Interfaces:**
- Produces: Instanz auf `127.0.0.1:3306`, Datenbanken `nouron`, `nouron_test`, `nouron_playtest`, User `nouron` mit allen Rechten auf diese drei; `.env` mit `DB_CONNECTION=mysql`. Spätere Tasks setzen das voraus.

Dieser Task enthält Schritte, die der Owner mit `sudo` selbst ausführt (`! <befehl>` im Prompt).

- [ ] **Step 1: Voraussetzungen prüfen**

Run: `php -m | grep -i pdo_mysql; which mysql mysqld`
Expected: aktuell leer (nicht installiert).

- [ ] **Step 2: Owner installiert Server und PHP-Extension**

```bash
sudo apt update && sudo apt install -y mysql-server php8.2-mysql
sudo service mysql start
```

(lokal läuft PHP 8.2; CI nutzt 8.4. Alternativ das fertige Skript: `sudo bash <scratchpad>/setup_mysql.sh`.) Prüfen: `php -m | grep pdo_mysql` zeigt `pdo_mysql`.

- [ ] **Step 3: Datenbanken und User anlegen**

```bash
sudo mysql <<'SQL'
CREATE DATABASE nouron CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nouron_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nouron_playtest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nouron'@'localhost' IDENTIFIED BY 'nouron';
GRANT ALL ON nouron.* TO 'nouron'@'localhost';
GRANT ALL ON `nouron\_test%`.* TO 'nouron'@'localhost';  -- nouron_test + Worker-DBs nouron_test_test_N (paratest)
GRANT ALL ON nouron_playtest.* TO 'nouron'@'localhost';
SQL
```

- [ ] **Step 4: `.env.example` und lokale `.env` anpassen**

In `.env.example` den DB-Block ersetzen:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nouron
DB_USERNAME=nouron
DB_PASSWORD=nouron
```

Dieselben Werte in die lokale `.env` übernehmen (nicht committet).

- [ ] **Step 5: Verbindung prüfen**

Run: `php artisan db:show --database=mysql`
Expected: Ausgabe mit `MySQL`, Datenbank `nouron`, 0 Tabellen.

- [ ] **Step 6: `docs/dev-setup-mysql.md` schreiben**

Inhalt (Deutsch): Installation (Step 2), Datenbanken/User (Step 3), `.env`-Werte, Reset der Dev-DB (`php artisan db:reset --force`), Hinweis „Tests und Bot laufen nur gegen `nouron_test`/`nouron_playtest`, nie gegen `nouron`", Fehlerbild „Connection refused" → `sudo service mysql start` (WSL startet Dienste nicht automatisch).

- [ ] **Step 7: Commit**

```bash
git checkout -b feat/r5b-mysql
git add docs/dev-setup-mysql.md .env.example
git commit -m "docs: R5b lokale MySQL-Umgebung (nativ, ohne Docker)"
```

---

### Task 2: `MAX(0, …)` portabel machen (HangarService)

**Files:**
- Modify: `app/Services/HangarService.php:751`
- Test: `tests/Feature/Hangar/HangarServiceTest.php` (neuer Test; Datei vorher lesen und die vorhandene Setup-Hilfe der Datei für Kolonie/Organika verwenden)

**Interfaces:**
- Produces: Der Abzug von Organika bei Anforderung eines Schiffs kippt nie unter 0, ohne die SQLite-Skalarfunktion `MAX(a, b)`.

- [ ] **Step 1: Kontext lesen**

Run: `sed -n 735,760p app/Services/HangarService.php` und `grep -n "organika\|Organika" tests/Feature/Hangar/HangarServiceTest.php | head`
Ziel: verstehen, welche Methode Zeile 751 enthält und wie ein Test den Zustand „Organika-Bestand < Kosten" herstellt.

- [ ] **Step 2: Failing Test schreiben**

Test in `HangarServiceTest`: Kolonie mit Organika-Bestand 3, Schiffsanforderung mit Organika-Kosten 10 auslösen (über den vorhandenen Pfad, der Zeile 751 erreicht; Bypass-Flags wie in den Nachbartests setzen), danach prüfen:

```php
$this->assertSame(0, (int) DB::table('colony_resources')
    ->where('colony_id', $colonyId)->where('resource_id', ResourceId::Organika->value) // Enum/ID wie in Nachbartests
    ->value('amount'));
```

Wenn der bestehende Test den Pfad schon abdeckt, nur diesen Floor-Assert ergänzen.

- [ ] **Step 3: Test laufen lassen**

Run: `bin/phpunit --filter <neuer Testname>`
Expected: PASS auf SQLite (das Verhalten ist heute korrekt) — der Test sichert das Verhalten für die Umstellung. Danach in Step 4 die Implementierung tauschen; der Test muss grün bleiben. (Rot-Phase entfällt bewusst: reine Refaktorierung hinter bestehendem Verhalten, der Test ist die Absicherung.)

- [ ] **Step 4: Portables SQL einsetzen**

Zeile 751 ersetzen durch:

```php
->update(['amount' => DB::raw("CASE WHEN amount > {$organikaCost} THEN amount - {$organikaCost} ELSE 0 END")]);
```

(`$organikaCost` ist ein int aus Config; falls nicht bereits `(int)`, vorher casten.)

- [ ] **Step 5: Test + Hangar-Suite**

Run: `bin/phpunit --filter HangarService`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/HangarService.php tests/Feature/Hangar/HangarServiceTest.php
git commit -m "refactor: Organika-Abzug im Hangar ohne SQLite-MAX() (R5b)"
```

---

### Task 3: Legacy-Bereinigung vor der Baseline (Owner-Freigabe 2026-10-03)

Quelle: Reviews `a4073b02…` (Tabellen) und `a66f9d38…` (Pfade/Spalten), jeweils per Grep belegt. Owner: Gruppe 1 + 2 freigegeben, Flotten-Altlasten „definitiv weg" (es gibt nur noch einzelne Schiffe), unklare Spalten bleiben. Reihenfolge der Plan-Tasks: 1, 2, **3a**, 3, 4, … (der Golden-Dump aus Task 4 entsteht erst nach Task 3). Jeder Unterschritt hat einen eigenen Commit und lässt die schnelle Suite grün (`bin/phpunit --testsuite=laravel-feature,laravel-unit`, noch SQLite). Vor jedem Löschen die Belege des Reviews mit `grep -rn` über `app routes resources public/js config database/seeders tests lang` selbst gegenprüfen; findet sich ein Leser, wird das Element **nicht** entfernt und im Bericht vermerkt.

**Bleibt ausdrücklich unangetastet (Owner):** `user.activated`/`activation_key` (E-Mail-Aktivierung geplant), `resources.abbreviation`, `personell.purpose/max_status_points`, `buildings.purpose`, `user_preferences.sol_report_skip`, `colony_building_discount_vouchers.granted_tick`, `glx_colonies.is_primary`, `ships.ap_for_levelup`, `researches.decay_rate/supply_cost/ap_for_levelup`, `config/ships.php`-Keys `trust_per_unit`, `nexus_cost`, `nexus_delivery_ticks`, `wear_per_sol`, `id`.

**Files (gesamt):**
- Modify: `app/Services/Techtree/{AbstractTechnologyService,ResearchService,TechtreeColonyService}.php`, `app/Providers/AppServiceProvider.php`, `app/Services/OnboardingService.php`, `app/Console/Commands/{ResetPlayer,GameSnapshot,SyncConfig,ColonySeedDemo}.php`, `app/Services/ColonyService.php`, `app/Models/{User,Resource,Colony,Building,Ship}.php` (soweit vorhanden), `database/factories/UserFactory.php`, `config/ships.php`, `data/sql/testdata.sqlite.sql`
- Delete: `app/Services/Techtree/ShipService.php`, `tests/Feature/Techtree/ShipServiceTest.php`, `app/Models/{Building,Research}.php` (nur wenn nach Gegenprüfung ohne Aufrufer)
- Create: `database/migrations/2026_10_03_000001_drop_legacy_tables_and_columns.php`
- Test: bestehende Suite + je Unterschritt die genannten Tests

**Interfaces:**
- Produces: Schema ohne Tabellen `trade_resources`, `personell_costs`, `colony_personell`, `research_costs`, `ship_costs`, View `v_trade_resources`; ohne Spalten `user.{faction_id,description,note,state,theme,tooltips_enabled,first_time_login,last_activity,registration,disabled}`, `glx_colonies.since_tick`, `resources.{start_amount,is_tradeable,trigger,icon}`, `buildings.prime_colony_only`, `ships.{prime_colony_only,required_research_id,required_research_level,moving_speed}` sowie — nur wenn die Gegenprüfung keinen Leser findet — `ships.decay_rate`, `ships.supply_cost` (Schiffe zerfallen nicht und haben keinen Supply-Unterhalt, GDD 2026-06-08). `ships.max_status_points` bleibt, solange `HangarService`/Reparatur es liest. Fixtures sind angepasst, die Suite grün.

- [ ] **Step 1 (3.1): Unerreichbaren Code entfernen**

1. Absicherung zuerst: Existiert ein Test, dass `POST` auf die Techtree-Order-Route für `ship` mit `use_colony_view` abgelehnt wird (`TechtreeController::order`, ca. Z. 449–451)? Falls nein: Test schreiben (rot nicht möglich, da Verhalten existiert — Charakterisierungstest, muss grün sein und nach dem Löschen grün bleiben).
2. Löschen: `ShipService.php`, `ShipServiceTest.php`, Binding in `AppServiceProvider.php` (ca. Z. 18/83–87), die Methoden `getBuildings/getResearches/getShips/getPersonell` in `TechtreeColonyService` samt `test_get_buildings` (`test_get_techtree` bleibt), Models `Building`/`Research` falls ohne Aufrufer (Gegenprüfung: `grep -rn "Models.Building\b\|Building::\|Research::" app tests routes database`).
3. Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → grün; `vendor/bin/phpstan analyse` (bzw. `bin/phpstan`) → keine neuen Fehler.
4. Commit: `refactor: ShipService und tote Techtree-Getter entfernt (R5b/3)`

- [ ] **Step 2 (3.2): `colony_personell` und `personell_id`-Zweige**

1. Charakterisierungstest (muss vor und nach dem Umbau grün sein): In `tests/Feature/Techtree/TechtreeColonyServiceTest.php` (oder `TechtreeControllerTest`) prüfen, dass `getTechtree()` Personell-Knoten mit `level` 0 liefert (genau das heutige Verhalten, `TechtreeColonyService.php:88-92`). Test schreiben, grün sehen.
2. Umbau: In `_gatherTechtreeInformations` (`TechtreeColonyService.php` ca. Z. 68–75) die `personell`-Zeile von `colony_personell` lösen (Level fest 0, keine DB-Abfrage); Zweige `entityIdKey() === 'personell_id'` in `AbstractTechnologyService.php` (ca. Z. 180/487/565) entfernen. Löschaufrufe `colony_personell` in `OnboardingService.php:68`, `ResetPlayer.php:217`, Eintrag in `GameSnapshot.php:50`, Fixture-Zeilen `testdata.sqlite.sql:155-158` entfernen.
3. Run: schnelle Suite → grün. Commit: `refactor: colony_personell und Personell-Zweige entfernt (R5b/3)`

- [ ] **Step 3 (3.3): `research_costs` und `costsTable()`**

1. Charakterisierungstests (grün vor und nach dem Umbau): Kenntnis-Levelup zahlt nur AP/Credits aus `config/knowledge.php` (bestehender `KnowledgeServiceTest` deckt das ab — prüfen); neu: `repair` einer Kenntnis/eines Gebäudes ohne Zusatzkosten in `AbstractTechnologyService` (Pfad Z. ~373) schlägt nicht fehl.
2. Umbau: `ResearchService::costsTable()` (Z. ~39–43) entfernen; in `AbstractTechnologyService` den Kostenpfad (Z. ~110/167/373/475/534) so ändern, dass nur Dienste mit eigener Kostentabelle (`BuildingService` → `building_costs`) sie lesen und andere eine leere Kostenmenge bekommen (kleinste Änderung: `costsTable()` liefert `?string`, `null` → leere Collection).
3. Run: schnelle Suite → grün. Commit: `refactor: research_costs-Lesepfad entfernt (R5b/3)`

- [ ] **Step 4 (3.4): Spalten und Tabellen aus Code, Models, Factory und Config nehmen**

Je Gruppe zuerst Gegenprüfung (grep), dann Code:
- **user:** `faction_id, description, note, state, theme, tooltips_enabled, first_time_login, last_activity, registration, disabled` aus `User.php` (`$fillable`/`$casts`), `UserFactory.php:27-30` und den 5 Test-Setups (`faction_id` in `OnboardingE2ETest:58`, `OnboardingTriggerServiceTest:46/179`, `OnboardingTriggersTest:71`, `OnboardingHintServiceTest:41`) entfernen. `activated`/`activation_key` bleiben.
- **glx_colonies:** `since_tick` aus `ColonyService.php:107`, `Colony.php` (`@property`/cast) und zugehörigem Test entfernen; View `v_glx_colonies` wird in der Migration (Step 5) neu erzeugt.
- **resources:** `start_amount, is_tradeable, trigger, icon` aus `Resource.php:19/24` und aus den positionellen `INSERT INTO "resources"`-Zeilen in `testdata.sqlite.sql:1-6` (Spaltenreihenfolge beachten: nach dem Droppen stimmt die Spaltenzahl nicht mehr — Zeilen auf `INSERT INTO resources (id,name,abbreviation) VALUES …` umstellen; Task 4 ersetzt diese Zeilen später ohnehin durch `database/seeders/data/resources.php`).
- **buildings/ships:** `buildings.prime_colony_only` aus `Building.php` (falls Model bleibt), `ships.{prime_colony_only,required_research_id,required_research_level,moving_speed}`; `ships.decay_rate/supply_cost` nur nach Gegenprüfung (Lesestellen außer `SyncConfig`?). `SyncConfig::syncShips()` (Z. ~55–90) auf die verbleibenden Spalten kürzen oder, falls keine Spalte übrig bleibt, den Zweig entfernen; `config/ships.php` die Keys `moving_speed` (und ggf. `decay_rate`, `supply_cost`) samt Erklärungskommentar entfernen. Positionelle `INSERT INTO "ships"`-Zeilen `testdata.sqlite.sql:79-85` auf benannte Spalten umstellen.
- **Tabellen:** `trade_resources` (Löschzeilen `OnboardingService.php:69`, `ResetPlayer.php:217/218`, Namensliste `GameSnapshot.php:50-51`, Fixture-INSERTs `testdata.sqlite.sql:177-178`), `personell_costs` (Fixture-INSERTs `:95-104`), `ship_costs` (Fixture-INSERTs `:87-89`) aus Code und Fixtures nehmen.
- Run: schnelle Suite (Schema ist noch alt, Code nutzt die Spalten nicht mehr) → grün. Commit: `refactor: Legacy-Spalten und -Tabellen aus Code, Models und Fixtures entfernt (R5b/3)`

- [ ] **Step 5 (3.5): Drop-Migration**

`database/migrations/2026_10_03_000001_drop_legacy_tables_and_columns.php` (läuft nur zwischen den Tasks und geht mit dem Squash in Task 5 auf; sie validiert, dass die Drops auf SQLite funktionieren):

```php
public function up(): void
{
    // Views freezing SELECT * must go before their base columns/tables.
    DB::statement('DROP VIEW IF EXISTS v_trade_resources');
    DB::statement('DROP VIEW IF EXISTS v_glx_colonies');

    foreach (['trade_resources', 'personell_costs', 'colony_personell', 'research_costs', 'ship_costs'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::table('user', fn (Blueprint $t) => $t->dropColumn([
        'faction_id', 'description', 'note', 'state', 'theme', 'tooltips_enabled',
        'first_time_login', 'last_activity', 'registration', 'disabled',
    ]));
    Schema::table('glx_colonies', fn (Blueprint $t) => $t->dropColumn('since_tick'));
    Schema::table('resources', fn (Blueprint $t) => $t->dropColumn(['start_amount', 'is_tradeable', 'trigger', 'icon']));
    Schema::table('buildings', fn (Blueprint $t) => $t->dropColumn('prime_colony_only'));
    Schema::table('ships', fn (Blueprint $t) => $t->dropColumn(['prime_colony_only', 'required_research_id', 'required_research_level', 'moving_speed']));
    // + ships.decay_rate / ships.supply_cost, only if step 4 found no reader

    // Recreate the surviving view with the original definition (copy the CREATE VIEW text
    // from 0001_01_01_999999_create_views.php / its latest recreation).
    DB::statement(/* CREATE VIEW v_glx_colonies … JOIN glx_system_objects … */);
}

public function down(): void
{
    // Not reversible: legacy removal (use migrate:fresh).
}
```

Vor dem Schreiben prüfen: Fremdschlüssel/Indizes auf den zu droppenden Spalten (`PRAGMA foreign_key_list(<table>)`, `PRAGMA index_list(<table>)`); SQLite verweigert `dropColumn` auf indizierten/FK-Spalten — dann zuerst `$t->dropForeign(...)`/`dropIndex(...)` (bei SQLite via Tabellen-Neuaufbau durch Laravel). `required_research_id`, `faction_id` und `since_tick` sind die wahrscheinlichen Kandidaten. Die `CREATE VIEW`-Definition für `v_glx_colonies` aus der **jüngsten** Migration kopieren (`grep -ln "v_glx_colonies" database/migrations/*.php`), nicht aus der ersten.

- [ ] **Step 6: Schema und Suite prüfen**

Run: `DB_CONNECTION=sqlite DB_DATABASE=$S/n3a.db php artisan migrate --force` (frische Datei) → 132 Migrationen ohne Fehler. `sqlite3 $S/n3a.db ".tables"` zeigt keine der entfernten Tabellen; `PRAGMA table_info(user)` usw. zeigen keine entfernten Spalten.
Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → grün (Fixtures aus Step 2–4 passen zum neuen Schema).
Run: `bin/phpunit --testsuite=playtest` → grün.

- [ ] **Step 7: Commit und ROADMAP**

```bash
git add database/migrations/2026_10_03_000001_drop_legacy_tables_and_columns.php
git commit -m "refactor: Migration entfernt Legacy-Tabellen und -Spalten (R5b/3)"
```

In `ROADMAP.md` einen T-Punkt für Rest-Altlasten anlegen („unklare Spalten: `resources.abbreviation`, `personell.purpose/max_status_points`, `buildings.purpose`, `sol_report_skip`, `granted_tick`, `ships.ap_for_levelup` prüfen; Hinweis: Anwerbungskosten kommen aus `config/advisors.php`, nicht aus DB").

---

### Task 4: Referenzdaten-Seeder (Upsert) statt SQL-Dump, Fixtures getrennt

Befund des Audits (2026-10-03, `ac5fdd75…`): Die Configs enthalten nur `id` plus Mechanikwerte (Zerfall, Supply, max. Stufe, Regolith-/Werkstoff-Kosten). `game:sync-config` aktualisiert nur bestehende Zeilen. Name, purpose, Voraussetzungen, Techtree-Position (`phase/row/column`), `ap_for_levelup`, Credits-/Supply-Kosten und alle Ressourcen stehen in **keiner** Config, sondern im SQL-Dump und in Migrationen. Referenzdaten = 6 Tabellen: `resources, buildings, building_costs, personell, researches, ships` (ca. 35 Einträge). Die Tabellen `trade_resources`, `personell_costs`, `research_costs`, `ship_costs`, `colony_personell` und die Spalte `resources.start_amount` sind nach Task 3 bereits entfernt.

**Files:**
- Create: `database/seeders/ReferenceDataSeeder.php`, `database/seeders/data/{resources,buildings,building_costs,personell,researches,ships}.php`
- Modify: `database/seeders/TestSeeder.php`, `database/seeders/MasterDataSeeder.php` (Inhalt in den neuen Seeder überführt, Datei danach löschen), `data/sql/testdata.sqlite.sql` → `data/sql/testdata.sql` (nur Fixtures)
- Modify (Referenzen auf den alten Dateinamen): `CLAUDE.md`, `.claude/agents/db-migration-agent.md`, `tests/Concerns/CreatesForeignColony.php`, Kommentare in `tests/Feature/*` (`grep -rn "testdata.sqlite" --include=*.php --include=*.md . | grep -v vendor`); `ROADMAP.md`/`CHANGELOG.md`/historische `docs/*` nicht anfassen.
- Test: `tests/Feature/Seeders/ReferenceDataSeederTest.php` (neu)
- Throwaway (Scratchpad, nicht committen): `golden_dump.php`, `extract_reference.php`

**Interfaces:**
- Produces: `ReferenceDataSeeder::run()` — schreibt per `DB::table($t)->upsert($rows, $uniqueBy, $updateCols)` die 6 Tabellen aus den Dateien in `database/seeders/data/` (jede Datei: `return [ ['id' => 25, …], … ];`), in Fremdschlüssel-Reihenfolge `resources → buildings → personell → researches → ships → building_costs`, in einer Transaktion. Danach `Artisan::call('game:sync-config')`, sodass Mechanikwerte weiter aus der Config kommen. Idempotent: zweiter Lauf ändert nichts und berührt keine Spielerdaten. `TestSeeder::run()` = `ReferenceDataSeeder` + Fixtures aus `data/sql/testdata.sql` (nur Fixture-Tabellen, plain `INSERT INTO`, kein `REPLACE`). Fixtures und `ReferenceDataSeeder` überschneiden sich in keiner Tabelle (Ausnahme: Forschung 9901 `test_decay_placeholder` bleibt Fixture in `testdata.sql`).
- Consumes: nichts aus anderen Tasks (arbeitet noch auf SQLite).

- [ ] **Step 1: Golden-Dump des Ist-Zustands (vor jeder Änderung)**

`$S/golden_dump.php` (Scratchpad `$S` wie in Task 5) schreibt aus einer DB, die mit dem **heutigen** `TestSeeder` befüllt ist, die 6 Tabellen als sortierte JSON-Datei:

```php
<?php
$tables = ['resources' => 'id', 'buildings' => 'id', 'building_costs' => 'building_id,resource_id', 'personell' => 'id',
    'researches' => 'id', 'ships' => 'id'];
$d = [];
foreach ($tables as $t => $order) {
    $q = DB::table($t);
    foreach (explode(',', $order) as $col) { $q->orderBy($col); }
    $d[$t] = $q->get()->map(fn ($r) => (array) $r)->all();
}
file_put_contents($argv[1], json_encode($d, JSON_PRETTY_PRINT));
```

Aufruf: frische SQLite-Datei migrieren (`DB_DATABASE=$S/g.db php artisan migrate --force`), `DB_DATABASE=$S/g.db php artisan db:seed --force` (TestSeeder), dann das Skript per `php artisan tinker $S/golden_dump.php $S/golden_before.json` (oder als Bootstrap-Skript wie in Task 5). Die Forschung 9901 (Fixture) aus dem Vergleich ausnehmen, indem sie im Dump übersprungen wird (`->where('id', '!=', 9901)` für `researches`).

- [ ] **Step 2: Datendateien erzeugen**

`$S/extract_reference.php` schreibt aus `$S/golden_before.json` je Tabelle eine Datei `database/seeders/data/<tabelle>.php` im Format `<?php

return [
    ['id' => 25, 'name' => '…', …],
];` (Spalten in Schemareihenfolge, `var_export` pro Wert, eine Zeile je Datensatz). Danach `bin/pint database/seeders/data` und stichprobenhaft prüfen, dass die Zahl der Einträge je Datei der Tabellengröße entspricht (resources 6, buildings 13, personell 5, researches 7 ohne 9901, ships 7, building_costs 37).

- [ ] **Step 3: Failing Test (Golden-Vergleich + Idempotenz + Spielerdaten)**

```php
<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferenceDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_reference_data_without_any_player_data(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $this->assertSame(13, DB::table('buildings')->count());
        $this->assertSame(6, DB::table('resources')->count());
        $this->assertSame(0, DB::table('user')->count());
        $this->assertSame(0, DB::table('glx_colonies')->count());
        // config values are applied (SyncConfig runs inside the seeder): command center
        $this->assertSame((int) config('buildings.commandCenter.max_level'), (int) DB::table('buildings')->where('id', 25)->value('max_level'));
    }

    public function test_second_run_is_idempotent_and_keeps_player_data(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $before = DB::table('building_costs')->orderBy('building_id')->orderBy('resource_id')->get()->toArray();
        DB::table('user')->insert([/* minimal valid user row, columns per Schema */]);
        DB::table('colony_buildings')->insert([/* one row referencing building 25 and an existing colony */]);

        $this->seed(ReferenceDataSeeder::class);

        $this->assertEquals($before, DB::table('building_costs')->orderBy('building_id')->orderBy('resource_id')->get()->toArray());
        $this->assertSame(1, DB::table('colony_buildings')->count());
        $this->assertSame(13, DB::table('buildings')->count());
    }
}
```

Die `insert([...])`-Platzhalter beim Schreiben mit den Pflichtspalten aus `Schema::getColumns('user')` bzw. `('colony_buildings')` ausfüllen (kein Platzhalter im Commit). Zusätzlich ein dritter Test, der `buildings.name` einer Zeile ändert und prüft, dass der zweite Seeder-Lauf den Wert aus der Datendatei wiederherstellt (Drift-Schutz).

- [ ] **Step 4: Rot bestätigen**

Run: `bin/phpunit tests/Feature/Seeders/ReferenceDataSeederTest.php` → FAIL (Klasse existiert nicht).

- [ ] **Step 5: Seeder implementieren**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent reference data (resources, buildings, techtree, ships, costs). Safe to run on every
 * deploy and on a database that already holds player data: upsert only, never delete/replace.
 * Mechanic values (decay, supply, max_level, regolith/werkstoffe cost) are applied afterwards from
 * config/*.php via game:sync-config — config stays the source of truth for those.
 */
class ReferenceDataSeeder extends Seeder
{
    /** table => unique key columns, in foreign-key order. */
    private const TABLES = [
        'resources' => ['id'],
        'buildings' => ['id'],
        'personell' => ['id'],
        'researches' => ['id'],
        'ships' => ['id'],
        'building_costs' => ['building_id', 'resource_id'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::TABLES as $table => $uniqueBy) {
                $rows = require database_path("seeders/data/{$table}.php");
                $update = array_values(array_diff(array_keys($rows[0]), $uniqueBy));
                DB::table($table)->upsert($rows, $uniqueBy, $update);
            }
        });

        Artisan::call('game:sync-config');
    }
}
```

Die verbliebenen Updates aus `MasterDataSeeder` (Schiffe 29/49/83/84, Forschungen 33..96: Zerfall/Supply/Max-Status-Punkte) sind im Golden-Dump bereits enthalten und damit in den Datendateien; `MasterDataSeeder.php` danach löschen. Upsert-Reihenfolge und der Unique-Index `(phase,row,column) WHERE phase>0`: Beim ersten Lauf auf leerer DB kollidiert nichts; bei späteren Positionstauschen (Balance) vorher die betroffenen `phase` in derselben Transaktion auf 0 setzen — als Kommentar in der Seeder-Klasse festhalten.

- [ ] **Step 6: TestSeeder und Fixtures**

`git mv data/sql/testdata.sqlite.sql data/sql/testdata.sql`; aus der Datei alle Zeilen der 6 Referenztabellen entfernen (Muster: `^(INSERT INTO|UPDATE) "?(resources|buildings|building_costs|personell|researches|ships)\b`, aber `INSERT INTO "researches"`-Zeile mit Id 9901 bleibt). Bezeichner entquoten (`sed -E 's/"([a-z_]+)"/\1/g'`, Vorher/Nachher-Diff prüfen, String-Literale nicht ändern). `TestSeeder::run()`:

```php
public function run(): void
{
    $this->call(ReferenceDataSeeder::class);

    $lines = array_filter(
        explode("\n", file_get_contents(base_path('data/sql/testdata.sql'))),
        fn (string $line) => (bool) preg_match('/^\s*(INSERT|UPDATE)\s/i', $line)
    );
    foreach ($lines as $line) {
        DB::statement(rtrim(trim($line), ';').';');
    }
}
```

(Kein `REPLACE`, keine Dialektumschreibung: Fixtures laufen immer auf einer frisch migrierten DB.) Die verbliebenen `INSERT OR REPLACE`-Hinweise im Kopfkommentar entfernen. Referenzen auf den alten Dateinamen aktualisieren (siehe „Files").

- [ ] **Step 7: Grün + Golden-Äquivalenz + Gesamtsuite auf SQLite**

Run: `bin/phpunit tests/Feature/Seeders/ReferenceDataSeederTest.php` → PASS.
Golden: frische SQLite-DB migrieren, **nur** `ReferenceDataSeeder` ausführen, `golden_dump.php` → `$S/golden_after.json`; `diff $S/golden_before.json $S/golden_after.json` muss leer sein (Tabellen, Zeilen, Spalten, Werte identisch).
Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → grün (144 Testdateien nutzen `TestSeeder`; IDs/Werte sind identisch).

- [ ] **Step 8: Commit**

```bash
git add database/seeders data/sql tests/Feature/Seeders tests/Concerns CLAUDE.md .claude/agents/db-migration-agent.md
git rm database/seeders/MasterDataSeeder.php
git commit -m "refactor: Referenzdaten per idempotentem Upsert-Seeder, Fixtures getrennt (R5b)"
```

### Task 5: Migrations-Squash auf eine Baseline

**Files:**
- Create: `database/migrations/0001_01_01_000000_baseline.php`
- Delete: alle 132 bisherigen Dateien in `database/migrations/` (bleiben in der Git-Historie)
- Throwaway (nicht committen, Scratchpad): `gen_baseline.php`, `compare_schema.php`

**Interfaces:**
- Produces: Eine Migration, die mit `Schema::create`/`DB::statement('CREATE VIEW …')` das heutige Endschema auf SQLite **und** MySQL erzeugt, in unveränderter Spaltenreihenfolge (die Fixtures nutzen positionelle `INSERT … VALUES(…)`). Sie fügt keine Daten ein. Spaltentypen: alle Integer signiert (`integer`, Auto-Increment-PKs als `$table->integer('id', true)`), damit Fremdschlüssel typgleich sind; Strings als `string(…, 255)`.
- Consumes: Task 4 (Referenzdaten kommen aus `ReferenceDataSeeder`, nicht aus Migrationen).

- [ ] **Step 1: Referenzschema bauen (alte Migrationen, SQLite)**

```bash
S=/tmp/claude-1000/-home-mg-workspace-nouron/48318171-30fb-453d-b495-f2ae3a3a041b/scratchpad
rm -f $S/old.db && touch $S/old.db
DB_CONNECTION=sqlite DB_DATABASE=$S/old.db php artisan migrate --force
```

Expected: 132 Migrationen (inkl. Drop-Migration aus Task 3), 39 Tabellen, 1 View `v_glx_colonies`.

- [ ] **Step 2: Generator schreiben und laufen lassen**

`$S/gen_baseline.php` (läuft über `php artisan tinker $S/gen_baseline.php` oder `php artisan <closure>`; einfachste Variante: als Skript mit `require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();` und `DB_DATABASE=$S/old.db`):

```php
<?php
use Illuminate\Support\Facades\{DB, Schema};

$out = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\DB;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration\n{\n    public function up(): void\n    {\n";

$map = fn (string $t, int $len = 255) => match (true) {
    str_contains($t, 'int') && $t === 'tinyint(1)' => 'boolean',
    str_contains($t, 'int') => 'integer',
    str_contains($t, 'varchar'), $t === 'string' => 'string',
    in_array($t, ['text', 'longtext', 'mediumtext']) => 'text',
    str_contains($t, 'datetime') => 'dateTime',
    $t === 'date' => 'date',
    str_contains($t, 'timestamp') => 'timestamp',
    str_contains($t, 'float'), str_contains($t, 'real'), str_contains($t, 'double') => 'double',
    str_contains($t, 'numeric'), str_contains($t, 'decimal') => 'decimal',
    default => throw new RuntimeException("unmapped type $t"),
};

foreach (collect(Schema::getTables())->pluck('name')->reject(fn ($n) => in_array($n, ['migrations'])) as $table) {
    $out .= "        Schema::create('$table', function (Blueprint \$table) {\n";
    $pk = collect(Schema::getIndexes($table))->firstWhere('primary', true)['columns'] ?? [];
    foreach (Schema::getColumns($table) as $c) {
        $m = $map(strtolower($c['type']));
        if ($c['auto_increment']) {
            $out .= "            \$table->integer('{$c['name']}', true);\n";
            continue;
        }
        $line = $m === 'string' ? "\$table->string('{$c['name']}', 255)" : "\$table->$m('{$c['name']}')";
        if ($c['nullable']) $line .= '->nullable()';
        if ($c['default'] !== null) $line .= '->default(DB::raw('.var_export($c['default'], true).'))';
        $out .= "            $line;\n";
    }
    $hasAi = collect(Schema::getColumns($table))->contains('auto_increment', true);
    if (! $hasAi && $pk) $out .= '            $table->primary('.var_export($pk, true).");\n";
    foreach (Schema::getIndexes($table) as $i) {
        if ($i['primary']) continue;
        $fn = $i['unique'] ? 'unique' : 'index';
        $out .= "            \$table->$fn(".var_export($i['columns'], true).', '.var_export($i['name'], true).");\n";
    }
    foreach (Schema::getForeignKeys($table) as $f) {
        $out .= '            $table->foreign('.var_export($f['columns'], true).')->references('.var_export($f['foreign_columns'], true).')->on('.var_export($f['foreign_table'], true).')'
            .($f['on_delete'] !== 'no action' ? "->onDelete('{$f['on_delete']}')" : '').";\n";
    }
    $out .= "        });\n\n";
}

foreach (DB::select("select name, sql from sqlite_master where type='view' order by name") as $v) {
    $out .= '        DB::statement('.var_export($v->sql, true).");\n";
}
$out .= "    }\n\n    public function down(): void\n    {\n        // Baseline: not reversible (use migrate:fresh).\n    }\n};\n";
file_put_contents('database/migrations/0001_01_01_000000_baseline.php', $out);
```

Der Generator ist ein Wegwerf-Werkzeug: Er darf an unbekannten Typen mit einer Exception abbrechen (`unmapped type …`) — dann die `match`-Zeile um den Typ ergänzen. Datums-Defaults wie `CURRENT_TIMESTAMP` bleiben durch `DB::raw` erhalten; Zeile prüfen.

- [ ] **Step 3: Alte Migrationen entfernen, Baseline auf frischer SQLite-DB ausführen**

```bash
git rm -q database/migrations/*.php   # die Baseline ist untracked und bleibt
rm -f $S/new.db && touch $S/new.db
DB_CONNECTION=sqlite DB_DATABASE=$S/new.db php artisan migrate --force
```

Expected: 1 Migration, ohne Fehler.

- [ ] **Step 4: Schemaäquivalenz beweisen**

`$S/compare_schema.php` liest beide DBs (je Aufruf mit `DB_DATABASE=$S/old.db` bzw. `new.db`, JSON-Dump nach `$S/old.json`/`new.json`) und vergleicht pro Tabelle: Spaltenname/-reihenfolge, `type`, `nullable`, `default`, `auto_increment`; Indizes (Spalten, unique); Fremdschlüssel (Spalten, Zieltabelle, `on_delete`); Views (SQL-Text).

```php
<?php
use Illuminate\Support\Facades\{DB, Schema};
$d = [];
foreach (Schema::getTables() as $t) {
    $n = $t['name']; if ($n === 'migrations') continue;
    $d[$n] = [
        'cols' => array_map(fn ($c) => [$c['name'], strtolower($c['type']), $c['nullable'], $c['default'], $c['auto_increment']], Schema::getColumns($n)),
        'idx' => collect(Schema::getIndexes($n))->map(fn ($i) => [$i['columns'], $i['unique'], $i['primary']])->sortBy(fn ($x) => json_encode($x))->values()->all(),
        'fk' => collect(Schema::getForeignKeys($n))->map(fn ($f) => [$f['columns'], $f['foreign_table'], $f['foreign_columns'], $f['on_delete']])->sortBy(fn ($x) => json_encode($x))->values()->all(),
    ];
}
$d['__views'] = collect(DB::select("select name, sql from sqlite_master where type='view' order by name"))->map(fn ($v) => [$v->name, preg_replace('/\s+/', ' ', $v->sql)])->all();
file_put_contents($argv[1] ?? 'php://stdout', json_encode($d, JSON_PRETTY_PRINT));
```

Dann `diff $S/old.json $S/new.json`. Expected: leer. Abweichungen im Generator beheben (z. B. Index-Namen, `varchar`-Längen sind in SQLite nicht erkennbar und dürfen nicht verglichen werden).

- [ ] **Step 5: Suite auf der neuen Baseline (noch SQLite)**

Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → alles grün. Falls Tests von Zeilen abhängen, die früher eine Migration eingefügt hat und die `database/seeders/data/*.php` nicht enthalten: Zeile in die Datendatei nachziehen (Referenz `$S/m.db`: `buildings` 3, `researches` 7, `building_costs` 21, `personell` 1, `personell_costs` 2, `ship_costs` 3, `ships` 1 Zeilen kamen vorab aus Migrationen; der Golden-Vergleich in Task 4 deckt die 6 Referenztabellen bereits ab).

- [ ] **Step 6: Auf MySQL ausführen**

Run: `DB_DATABASE=nouron_test php artisan migrate:fresh --force` (nur `nouron_test`!)
Expected: Baseline läuft durch (39 Tabellen, 1 View). Fehler (Typ, Indexlänge, View-SQL) im Generator bzw. in der Baseline-Datei beheben und Step 4–6 wiederholen.

- [ ] **Step 7: Commit**

```bash
git add database/migrations
git commit -m "refactor: Migrations-Squash auf Baseline (SQLite- und MySQL-fähig), 132 Altmigrationen entfernt (R5b)"
```

---

### Task 6: Test-Suite und CI auf MySQL umstellen

**Files:**
- Modify: `phpunit.xml:32-33`, `.github/workflows/ci.yml`, `config/database.php:20`
- Test: gesamte Suite

**Interfaces:**
- Consumes: Task 1 (Datenbanken), Task 5 (Baseline), Task 4 (`ReferenceDataSeeder`/`TestSeeder`).
- Produces: `bin/phpunit` und `php artisan test --parallel` laufen gegen MySQL (`nouron_test`, parallel `nouron_test_test_N`); CI gegen einen MySQL-Service.

- [ ] **Step 1: phpunit.xml umstellen**

```xml
<env name="DB_CONNECTION" value="mysql" force="true"/>
<env name="DB_DATABASE" value="nouron_test" force="true"/>
```

Host/User/Passwort kommen aus `.env`. In `config/database.php` den Default auf `env('DB_CONNECTION', 'mysql')` ändern.

- [ ] **Step 2: Erster Lauf, Fehler sammeln**

Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit 2>&1 | tee $S/mysql_run1.txt | tail -40`
Notiere Laufzeit (`Time:` am Ende) und Anzahl Fehler. Fehler nach Klassen gruppieren (Step 3).

- [ ] **Step 3: Fehlerklassen nacheinander beheben (je Klasse ein Commit)**

Je Klasse: Fehlschlag reproduzieren → Ursache beheben → betroffene Tests grün → Commit. Bekannte Kandidaten:

- **A — Strict Mode / Fixtures:** `Incorrect … value`, `Data too long`, `doesn't have a default value`, ungültige Datumsstrings in `data/sql/*.sql`. Fix in den SQL-Dateien oder der schreibenden Stelle (nicht den Strict Mode abschalten).
- **B — Aggregate als String:** `SUM(...)`/`COUNT(...)`-Ergebnisse (`ResourcesService:330`, `:419-420`, `TechtreeController:110`, `ColonyController:282`) mit `(int)` casten, wo der Aufrufer strikt vergleicht; Tests nicht auf Strings umbiegen.
- **C — fehlende Sortierung:** `first()`/`get()`/`pluck()` ohne `orderBy`, auf deren Reihenfolge sich Logik oder Test verlässt → `orderBy('id')` (oder fachlich passende Spalte) ergänzen.
- **D — AUTO_INCREMENT:** Tests mit festen neuen IDs (`assertSame(…, $new->id)`) auf relative Prüfung umstellen (`$new->id` aus dem Objekt lesen).
- **E — Foreign Keys:** `Cannot add or update a child row` / `Cannot delete … parent` → Fixture-Reihenfolge oder Testaufbau korrigieren, wenn die FK-Verletzung echt ist (MySQL erzwingt, was SQLite per Default großzügig übersah).
- **F — Sonstiges:** `tests/Feature/Console/DbResetTest.php`, `UserResetPasswordTest` und weitere Treffer von `grep -rln "sqlite" app tests` anpassen (Dateipfad-Annahmen entfernen).

- [ ] **Step 4: Suite komplett grün**

Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → 0 Fehler; zweiter Lauf unmittelbar danach ebenfalls grün (Reihenfolge-/Zustandsunabhängigkeit).
Run: `bin/phpunit --testsuite=playtest` → grün (noch mit den festen Fixture-IDs; Umbau erst in Task 7).

- [ ] **Step 4b: Parallele Tests einrichten**

Run: `composer require --dev brianium/paratest` (PHPUnit 11 → paratest 7.x; `composer.json`/`composer.lock` mitcommitten). Prüfen: `php artisan test --parallel --testsuite=laravel-feature,laravel-unit` legt pro Worker eine eigene Datenbank `nouron_test_test_N` an (Rechte stammen aus dem Grant `nouron\_test%` aus Task 1) und läuft grün. Gegenprobe: zweimal hintereinander (kein Zustand zwischen Läufen), plus `--processes=4`. Zeiten notieren (seriell vs. parallel). Tests, die nur wegen Parallelität scheitern (gemeinsamer Zustand außerhalb der DB, z. B. feste Dateipfade in `storage/`), beheben oder serialisieren. In CI `php artisan test --parallel` verwenden, falls der Runner mehr als 2 Kerne hat; sonst seriell lassen.

- [ ] **Step 5: Laufzeit bewerten**

Laufzeit mit dem SQLite-Wert vergleichen (`git stash`-frei: den SQLite-Wert aus einem Lauf auf `master` in einem zweiten Checkout oder aus dem CI-Log eines früheren Laufs nehmen, aktuell ca. 1–1,5 min in CI). Nur wenn die MySQL-Suite deutlich länger dauert (> ca. 3× oder > 5 min lokal): Datenverzeichnis auf tmpfs (`/etc/mysql/mysql.conf.d/`: `innodb_flush_log_at_trx_commit=0`, `sync_binlog=0`, `skip-log-bin`) und in `docs/dev-setup-mysql.md` festhalten. Sonst nichts tun (YAGNI).

- [ ] **Step 6: CI auf MySQL-Service**

In `.github/workflows/ci.yml` im Job `tests` ergänzen:

```yaml
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: nouron
          MYSQL_DATABASE: nouron_test
        ports: ['3306:3306']
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1 -pnouron"
          --health-interval=5s --health-timeout=5s --health-retries=20
```

`extensions: pdo_mysql` statt `pdo_sqlite, sqlite3`, und im Schritt „Prepare environment" nach `cp .env.example .env` die Verbindung auf den Service setzen:

```bash
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=root/; s/^DB_PASSWORD=.*/DB_PASSWORD=nouron/' .env
```

Auf einem Push-Branch laufen lassen und beide Matrix-Jobs grün sehen (`gh pr checks --watch`).

- [ ] **Step 7: Commit**

```bash
git add phpunit.xml config/database.php .github/workflows/ci.yml
git commit -m "feat: Test-Suite und CI laufen gegen MySQL (R5b)"
```

---

### Task 7: PlaytestBot in gemeinsamer DB, jeder Lauf mit eigenem User

**Files:**
- Modify: `tests/Feature/Playtest/BotSession.php:46-80` (`boot()`), `tests/Feature/Playtest/PlaytestBotTest.php:22` (Trait-Override), `app/Console/Commands/Playtest.php:25-80`
- Test: `tests/Feature/Playtest/BotSessionIsolationTest.php` (neu), `tests/Feature/Console/PlaytestCommandGuardTest.php` (neu)

**Interfaces:**
- Consumes: `OnboardingService::setupNewPlayer(int $userId, string $colonyName = ''): Colony` und `resetColonyToSol1(int $userId, int $colonyId, ?int $rngSeed = null): void` (existieren); Task 4 (`ReferenceDataSeeder`).
- Produces: `BotSession::boot()` legt pro Aufruf einen **neuen** User `bot_{profile}_{seed}_{uniqid}` mit eigener Kolonie an (keine festen IDs 3/1, kein `TestSeeder`). `game:playtest` setzt vor dem ersten Batch `nouron_playtest` per `migrate:fresh` zurück und übergibt den Kindern `DB_DATABASE=nouron_playtest` und `PLAYTEST_SHARED_DB=1`; mit dieser Variable verwendet `PlaytestBotTest` keine Transaktions-Rücksetzung (`RefreshDatabase`), die Daten werden committet und sind für parallele Läufe wie echte Spieler.

- [ ] **Step 1: Failing Test — zwei Bots stören sich nicht**

```php
<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_bot_sessions_get_separate_users_and_colonies(): void
    {
        $a = BotSession::boot($this, seed: 1);
        $b = BotSession::boot($this, seed: 2);

        $this->assertNotSame($a->userId, $b->userId);
        $this->assertNotSame($a->colonyId, $b->colonyId);
        $this->assertNotSame($a->runId, $b->runId);
    }
}
```

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/BotSessionIsolationTest.php`
Expected: FAIL (`userId` beider Sessions ist 3).

- [ ] **Step 3: `BotSession::boot()` umbauen**

Statt fester IDs und `TestSeeder`:

```php
public static function boot(TestCase $test, int $seed): self
{
    // Master data only once per DB; shared-DB mode seeds it in game:playtest before the batch.
    if (! getenv('PLAYTEST_SHARED_DB')) {
        app(ReferenceDataSeeder::class)->run();
    }

    $user = User::create([
        'username' => 'bot_'.$seed.'_'.bin2hex(random_bytes(4)),
        'display_name' => 'Bot '.$seed,
        'password' => bcrypt('bot'),
        'email' => 'bot_'.$seed.'_'.bin2hex(random_bytes(4)).'@example.invalid',
        'activated' => 1,
    ]);
    $userId = (int) $user->user_id;
    $colony = app(OnboardingService::class)->setupNewPlayer($userId);
    $colonyId = (int) $colony->id;
    // …ab hier unverändert (config bypass off, resetColonyToSol1($userId, $colonyId, rngSeed: $seed), …)
```

Pflichtspalten von `user` (`username`, `password`, `email`, …) gegen das Schema prüfen (`Schema::getColumns('user')`) und fehlende NOT-NULL-Spalten setzen; `User`-Model `$fillable`/`$primaryKey` beachten. Den Import `Database\Seeders\TestSeeder` durch `MasterDataSeeder` ersetzen.

- [ ] **Step 4: Grün, Playtest-Suite**

Run: `bin/phpunit tests/Feature/Playtest/BotSessionIsolationTest.php` → PASS.
Run: `bin/phpunit --testsuite=playtest` → grün (Hinweis: Tests, die Bart/Kolonie 1 voraussetzen, auf `$bot->userId`/`$bot->colonyId` umstellen).

- [ ] **Step 5: Failing Test — Guard gegen die Dev-DB**

```php
<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class PlaytestCommandGuardTest extends TestCase
{
    public function test_refuses_when_playtest_database_equals_default_database(): void
    {
        config(['database.connections.mysql.database' => 'nouron']);
        config(['game.playtest.database' => 'nouron']);

        $this->artisan('game:playtest', ['--seeds' => '1'])
            ->expectsOutputToContain('refuse')
            ->assertFailed();
    }
}
```

Run → FAIL (kein Guard, Config-Key `game.playtest.database` existiert nicht).

- [ ] **Step 6: Playtest-Kommando umbauen**

In `config/game.php` einen Eintrag `'playtest' => ['database' => env('PLAYTEST_DATABASE', 'nouron_playtest')]` ergänzen. In `Playtest::handle()` nach dem Production-Guard:

```php
$playtestDb = (string) config('game.playtest.database');
if ($playtestDb === '' || $playtestDb === config('database.connections.'.config('database.default').'.database')) {
    $this->error('Playtest database must differ from the default database — refusing to reset it.');

    return self::FAILURE;
}

// Fresh shared DB for the whole invocation; every bot run creates its own user + colony in it.
config(['database.connections.mysql.database' => $playtestDb]);
DB::purge('mysql');
$this->call('migrate:fresh', ['--force' => true]);
app(ReferenceDataSeeder::class)->run();
```

und in den `->env([...])`-Block der Kinder `'DB_CONNECTION' => 'mysql'`, `'DB_DATABASE' => $playtestDb`, `'PLAYTEST_SHARED_DB' => '1'` (statt `sqlite`/`:memory:`). Den Kommentar über der Env-Liste und den Klassen-Docblock (`:memory:`-Begründung) auf das neue Modell aktualisieren (jeder Lauf = eigener User in einer gemeinsamen DB).

- [ ] **Step 7: Trait-Override in `PlaytestBotTest`**

```php
use RefreshDatabase {
    refreshDatabase as private refreshDatabaseWithRollback;
}

public function refreshDatabase(): void
{
    if (getenv('PLAYTEST_SHARED_DB')) {
        return; // shared DB: committed data, parent command resets the DB per invocation
    }
    $this->refreshDatabaseWithRollback();
}
```

- [ ] **Step 8: Echte Prüfung — 8 parallele Läufe**

Run (nohup + Poll, Dauer ≈ 7 min laut Messung 2026-10-02):
`php artisan game:playtest --profiles=default --seeds=1,2,3,4,5,6,7,8 --concurrency=8`
Expected: 8 Reports, keine `Lock wait timeout`/`Deadlock`-Fehler. Reproduzierbarkeit prüfen: Sieg/Niederlage je Seed mit der Kontrollmessung vom 2026-10-02 vergleichen (`default` Seeds 1–8: 5/8 Siege; verloren Seed 5, 6, 8) — Abweichungen sind ein Befund (globaler Zustand), kein Rauschen.

- [ ] **Step 9: Commit**

```bash
git add app/Console/Commands/Playtest.php config/game.php tests/Feature/Playtest tests/Feature/Console/PlaytestCommandGuardTest.php
git commit -m "feat: PlaytestBot in gemeinsamer MySQL-DB, jeder Lauf mit eigenem User (R5b)"
```

---

### Task 8: Doku nachziehen, Abschluss

**Files:**
- Modify: `CLAUDE.md` (Abschnitt „Wichtige Korrekturen": SQLite → MySQL, Dateinamen), `docs/adr/0005-produktions-datenbank-und-hosting.md` (Annahmen 1+2 → bestätigt/Entscheidung, Messwerte), `ROADMAP.md` (R5b ✅, Folgepunkte), `CHANGELOG.md` (Block des Tages), `.claude/agents/db-migration-agent.md` (SQLite-Hinweise → MySQL), `docs/game-reference.md` nur falls DB-Schema-Verweis dort steht.
- Create: `docs/deployment-db.md` (Produktions-Seeding: jeder Deploy `php artisan migrate --force && php artisan db:seed --class=ReferenceDataSeeder --force` — idempotent, enthält `game:sync-config`; nie `DatabaseSeeder`/`TestSeeder`).

- [ ] **Step 1: CLAUDE.md**

`**Datenbank ist SQLite** (NICHT MySQL)` ersetzen durch: **Datenbank ist MySQL** (lokal `nouron` / `nouron_test` / `nouron_playtest`, Produktion Laravel Cloud, siehe `docs/dev-setup-mysql.md` und ADR 0005); „Schichtung … SQLite" → MySQL; `data/db/*.db`-Zeilen entfernen; TestSeeder-Satz anpassen (Referenzdaten per `ReferenceDataSeeder`, Fixtures in `data/sql/testdata.sql`).

- [ ] **Step 2: ADR 0005**

„Annahme / zu prüfen" Punkt 1 und 2 durch Ergebnis ersetzen (Baseline-Äquivalenz belegt, Suite läuft auf MySQL, gemessene Laufzeit, Entscheidung zu tmpfs ja/nein); neuer Absatz „Bot und Tests": gemeinsame DB, ein User pro Lauf, kein Docker.

- [ ] **Step 3: ROADMAP + CHANGELOG**

R5b als ✅ mit Datum und einer Zeile Ergebnis; neuer Folgepunkt: „R4: Laravel-Cloud-Deployment gemäß `docs/deployment-db.md`". CHANGELOG: ein kurzer Eintrag im Block des Tages (Deutsch, 1–2 Sätze).

- [ ] **Step 4: Volle Suite + Lint**

Run: `bin/phpunit` (alle Suiten) und `bin/pint --test` → grün.

- [ ] **Step 5: Push + PR**

```bash
git push -u origin feat/r5b-mysql
gh pr create --base master --title "feat: R5b MySQL-Portierung (ein Dialekt, Bot in gemeinsamer DB)"
```

PR-Beschreibung: Zusammenfassung je Task, Messwerte (Suite-Laufzeit SQLite vs. MySQL, Bot-Kontrolle Seeds 1–8), Hinweis „Migrations-Squash: Altmigrationen nur noch in der Git-Historie". Vor dem Merge die Pflicht-Checkliste (CHANGELOG, PR-Beschreibung, `game-reference.md`). Nicht mergen ohne Owner-Okay.
