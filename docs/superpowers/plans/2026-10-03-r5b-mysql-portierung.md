# R5b MySQL-Portierung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entwicklung, Tests, CI, PlaytestBot und Produktion laufen auf genau einer DB-Engine (MySQL); SQLite verschwindet aus dem Projekt.

**Architecture:** Zuerst alles dialektneutral machen, solange die Suite noch auf SQLite grün ist (portables SQL, Stammdaten/Fixtures trennen, Migrations-Baseline). Erst danach wird die Suite auf MySQL umgestellt (ein kleiner, messbarer Schritt, Fehlerklassen werden dort getrennt abgearbeitet). Zuletzt bekommt der PlaytestBot eine gemeinsame DB `nouron_playtest`, in der jeder Lauf einen eigenen User samt Kolonie anlegt (wie mehrere Spieler in Produktion).

**Tech Stack:** PHP 8.4, Laravel 12, PHPUnit 11, MySQL 8 (nativ in WSL, kein Docker), GitHub Actions MySQL-Service.

**Spec:** `docs/adr/0005-produktions-datenbank-und-hosting.md` (Owner-Entscheidungen 2026-10-02/03: Laravel Cloud + MySQL, ein Dialekt, kein Docker, Bots teilen sich eine DB wie Spieler).

## Global Constraints

- Ein Dialekt: lokal, CI, Bot und Produktion alle MySQL. Kein SQLite-Fallback, kein Docker lokal (YAGNI, Owner 2026-10-03).
- Drei lokale Datenbanken auf einer Instanz: `nouron` (Dev), `nouron_test` (PHPUnit), `nouron_playtest` (PlaytestBot). Nur die beiden letzten dürfen von Tests/Bots zurückgesetzt werden.
- Agenten dürfen `migrate:fresh`/`db:seed` nie gegen `nouron` (Dev) ausführen — `DB_DATABASE` explizit setzen (Memory `feedback_agents_never_migrate_fresh_on_dev_db`).
- TDD: Test zuerst, rot sehen, dann Code (CLAUDE.md). Ausnahmen: reine Config-/Doku-Änderungen.
- Code/Kommentare Englisch; Doku, ROADMAP, CHANGELOG, ADRs Deutsch. Ein CHANGELOG-Block pro Tag, kurz.
- Nie auf `master` committen: Branch `feat/r5b-mysql`, Pre-commit-Hook (Pint) nie mit `--no-verify` umgehen, nie `git add -A`.
- Produktion wird nie mit Testdaten befüllt (R3/R4): Stammdaten und Fixtures bleiben getrennte Dateien.
- Schnelle Suite während der Arbeit: `bin/phpunit --testsuite=laravel-feature,laravel-unit`; volle Suite (+ `playtest`) vor dem PR.

## Review Focus

Fehlerklassen, die der Wechsel SQLite → MySQL mit hoher Wahrscheinlichkeit aufdeckt und die kein bestehender Test gezielt prüft (die Tests dafür stehen in den genannten Tasks):

1. `REPLACE INTO` löscht und fügt neu ein: Auf einer DB mit Spielerdaten würde ein erneutes Einspielen der Stammdaten in MySQL an Foreign Keys scheitern oder Kind-Zeilen mitlöschen. Erwartet: Stammdaten werden nur in eine leere DB geschrieben, nie über bestehende Daten (Task 3).
2. `SUM()`/`COUNT()`-Ergebnisse kommen unter MySQL als String (`"12"`) statt als int zurück; `assertSame(12, …)` und strikte Vergleiche in Services brechen (Task 5, Fehlerklasse B).
3. SQLite liefert ohne `ORDER BY` stabil nach rowid, MySQL nicht: Tests/Logik mit `first()`/`pluck()` ohne Sortierung werden flaky (Task 5, Fehlerklasse C).
4. Strict Mode: fehlende NOT-NULL-Werte, zu lange Strings und ungültige Datumswerte werden abgelehnt (SQLite akzeptiert alles) (Task 5, Fehlerklasse A).
5. `AUTO_INCREMENT` wird beim Transaktions-Rollback nicht zurückgesetzt: Tests, die konkrete neue IDs erwarten, hängen von der Reihenfolge ab (Task 5, Fehlerklasse D).
6. Parallele Bots auf derselben Kolonie: Die bisherigen festen Fixture-IDs (User 3, Kolonie 1) würden sich gegenseitig zurücksetzen (Task 6).

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
sudo apt update && sudo apt install -y mysql-server php8.4-mysql
sudo service mysql start
```

(PHP-Minor-Version an `php -v` anpassen.) Prüfen: `php -m | grep pdo_mysql` zeigt `pdo_mysql`.

- [ ] **Step 3: Datenbanken und User anlegen**

```bash
sudo mysql <<'SQL'
CREATE DATABASE nouron CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nouron_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nouron_playtest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nouron'@'localhost' IDENTIFIED BY 'nouron';
GRANT ALL ON nouron.* TO 'nouron'@'localhost';
GRANT ALL ON nouron_test.* TO 'nouron'@'localhost';
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

### Task 3: Stammdaten und Test-Fixtures trennen, dialektneutral, nur in leere DB

**Files:**
- Create: `data/sql/masterdata.sql`
- Rename: `data/sql/testdata.sqlite.sql` → `data/sql/testdata.sql` (nur Fixtures)
- Modify: `database/seeders/MasterDataSeeder.php`, `database/seeders/TestSeeder.php`
- Modify (Referenzen auf den alten Dateinamen): `CLAUDE.md`, `.claude/agents/db-migration-agent.md`, `tests/Concerns/CreatesForeignColony.php`, Kommentare in `tests/Feature/*` (Treffer: `grep -rn "testdata.sqlite" --include=*.php --include=*.md . | grep -v vendor`); `ROADMAP.md`/`CHANGELOG.md`/`docs/*` historisch nicht anfassen.
- Test: `tests/Feature/Seeders/MasterDataSeederTest.php` (neu)

**Interfaces:**
- Produces: `MasterDataSeeder::run()` füllt Stammdaten (Tabellen `resources, buildings, building_costs, personell, personell_costs, researches, ships, ship_costs, trade_resources` inkl. der UPDATE-Zeilen dieser Tabellen) und schreibt **nichts**, wenn `resources` bereits Zeilen enthält. Danach führt es die bestehenden Updates und `game:sync-config` aus. `TestSeeder::run()` = `MasterDataSeeder` + Fixtures aus `testdata.sql` (Tabellen `user, user_resources, advisors, bar_offers, colony_*, glx_colonies`, UPDATEs auf `colony_ships`). Beide Dateien verwenden nur Syntax, die SQLite **und** MySQL verstehen: unquotierte Bezeichner, `REPLACE INTO`.
- Consumes: nichts aus anderen Tasks.

- [ ] **Step 1: Failing Test schreiben**

```php
<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_master_data_without_any_user_or_colony(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertGreaterThan(0, DB::table('buildings')->count());
        $this->assertGreaterThan(0, DB::table('resources')->count());
        $this->assertSame(0, DB::table('user')->count());
        $this->assertSame(0, DB::table('glx_colonies')->count());
    }

    public function test_second_run_does_not_touch_existing_data(): void
    {
        $this->seed(MasterDataSeeder::class);
        DB::table('buildings')->where('id', 25)->update(['name' => 'marker']);

        $this->seed(MasterDataSeeder::class);

        $this->assertSame('marker', DB::table('buildings')->where('id', 25)->value('name'));
    }
}
```

(Spaltenname `name` und Id 25 = `BuildingId::CommandCenter` gegen das Schema prüfen: `sqlite3 data/db/nouron.db ".schema buildings"`; passende Textspalte wählen.)

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit tests/Feature/Seeders/MasterDataSeederTest.php`
Expected: FAIL (`buildings` leer — Stammdaten kommen heute nur über TestSeeder/Fixtures).

- [ ] **Step 3: Dateien aufteilen**

`git mv data/sql/testdata.sqlite.sql data/sql/testdata.sql`. Danach die Zeilen der Stammdaten-Tabellen (Liste oben) aus `testdata.sql` nach `data/sql/masterdata.sql` verschieben, per Skript damit keine Zeile verloren geht:

```bash
cd data/sql
grep -E '^(INSERT INTO|UPDATE) "?(resources|buildings|building_costs|personell|personell_costs|researches|ships|ship_costs|trade_resources)\b' testdata.sql > masterdata.sql
grep -vE '^(INSERT INTO|UPDATE) "?(resources|buildings|building_costs|personell|personell_costs|researches|ships|ship_costs|trade_resources)\b' testdata.sql > testdata.tmp && mv testdata.tmp testdata.sql
wc -l masterdata.sql testdata.sql   # Summe = 243 (vorher), keine Zeile verloren
```

Dann in beiden Dateien Bezeichner entquoten und `INSERT INTO` bleibt (die Seeder wandeln um, siehe Step 4):

```bash
sed -i -E 's/"([a-z_]+)"/\1/g' masterdata.sql testdata.sql
grep -n '"' masterdata.sql testdata.sql | grep -v "'" | head   # darf nichts liefern; Treffer prüfen (Doppelte Anführungszeichen in String-Literalen NICHT ändern)
```

Wichtig: das `sed` darf nur Bezeichner treffen. Vorher `grep -c '"' data/sql/testdata.sql` vergleichen und stichprobenartig `git diff --stat` prüfen; enthält ein String-Literal ein `"`, die Zeile von Hand korrigieren.

- [ ] **Step 3b: Gemeinsame Lade-Hilfe**

Beide Seeder brauchen dieselbe Logik (Zeilen filtern, `INSERT INTO` → `REPLACE INTO`, ausführen). In `MasterDataSeeder` als `public static function runSqlFile(string $path): void` ablegen:

```php
public static function runSqlFile(string $path): void
{
    $lines = array_filter(
        explode("\n", file_get_contents($path)),
        fn (string $line) => (bool) preg_match('/^\s*(INSERT|UPDATE)\s/i', $line)
    );

    foreach ($lines as $line) {
        $statement = rtrim(trim($line), ';').';';
        // REPLACE INTO is valid in SQLite and MySQL; only ever run on an empty DB (see run()).
        $statement = preg_replace('/^INSERT INTO\b/i', 'REPLACE INTO', $statement);
        DB::statement($statement);
    }
}
```

- [ ] **Step 4: Seeder umbauen**

`MasterDataSeeder::run()` beginnt mit:

```php
if (DB::table('resources')->exists()) {
    return; // never overwrite master data on a DB that already has data (REPLACE would cascade into player rows)
}
self::runSqlFile(base_path('data/sql/masterdata.sql'));
```

danach die bestehenden Aufrufe (`seedShips()`, `seedResearches()`, …) und am Ende `Artisan::call('game:sync-config')` (Letzteres aus `TestSeeder` hierher verschieben, damit auch Produktion es bekommt).

`TestSeeder::run()` wird zu:

```php
public function run(): void
{
    $this->call(MasterDataSeeder::class);
    MasterDataSeeder::runSqlFile(base_path('data/sql/testdata.sql'));
}
```

Der Kommentarblock im Kopf von `TestSeeder` auf die neuen Dateinamen anpassen. Bestehende Migrationen, die Stammdaten vorab einfügen (`buildings` 3, `researches` 7, …), können dazu führen, dass `resources` noch leer, `buildings` aber schon befüllt ist — `REPLACE INTO` überschreibt diese Zeilen absichtlich (Stand heute genauso).

- [ ] **Step 5: Grün + Gesamtsuite auf SQLite**

Run: `bin/phpunit tests/Feature/Seeders/MasterDataSeederTest.php` → PASS.
Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → alles grün (Fixtures haben sich inhaltlich nicht geändert).

- [ ] **Step 6: Referenzen auf den alten Dateinamen aktualisieren**

Die in „Files" genannten Stellen auf `data/sql/testdata.sql` bzw. `data/sql/masterdata.sql` ändern; `CLAUDE.md` (Architektur-Block und „Technische Hinweise") bekommt zusätzlich eine Zeile: „Stammdaten: `data/sql/masterdata.sql` (auch Produktion); Fixtures: `data/sql/testdata.sql` (nur Tests/Dev)".

- [ ] **Step 7: Commit**

```bash
git add data/sql database/seeders tests/Feature/Seeders tests/Concerns CLAUDE.md .claude/agents/db-migration-agent.md
git commit -m "refactor: Stammdaten und Test-Fixtures getrennt, dialektneutral, Seeding nur in leere DB (R5b)"
```

---

### Task 4: Migrations-Squash auf eine Baseline

**Files:**
- Create: `database/migrations/0001_01_01_000000_baseline.php`
- Delete: alle 131 bisherigen Dateien in `database/migrations/` (bleiben in der Git-Historie)
- Throwaway (nicht committen, Scratchpad): `gen_baseline.php`, `compare_schema.php`

**Interfaces:**
- Produces: Eine Migration, die mit `Schema::create`/`DB::statement('CREATE VIEW …')` das heutige Endschema auf SQLite **und** MySQL erzeugt, in unveränderter Spaltenreihenfolge (die Fixtures nutzen positionelle `INSERT … VALUES(…)`). Sie fügt keine Daten ein. Spaltentypen: alle Integer signiert (`integer`, Auto-Increment-PKs als `$table->integer('id', true)`), damit Fremdschlüssel typgleich sind; Strings als `string(…, 255)`.
- Consumes: Task 3 (Stammdaten kommen aus `masterdata.sql`, nicht aus Migrationen).

- [ ] **Step 1: Referenzschema bauen (alte Migrationen, SQLite)**

```bash
S=/tmp/claude-1000/-home-mg-workspace-nouron/48318171-30fb-453d-b495-f2ae3a3a041b/scratchpad
rm -f $S/old.db && touch $S/old.db
DB_CONNECTION=sqlite DB_DATABASE=$S/old.db php artisan migrate --force
```

Expected: 131 Migrationen, 44 Tabellen, Views `v_glx_colonies`, `v_trade_resources`.

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

Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → alles grün. Falls Tests von Stammdaten abhängen, die früher eine Migration eingefügt hat und `masterdata.sql` nicht enthält: Tabelle/Zeile in `masterdata.sql` nachziehen (Referenz: `$S/m.db`-Messung: `buildings` 3, `researches` 7, `building_costs` 21, `personell` 1, `personell_costs` 2, `ship_costs` 3, `ships` 1 Zeilen kamen vorab aus Migrationen).

- [ ] **Step 6: Auf MySQL ausführen**

Run: `DB_DATABASE=nouron_test php artisan migrate:fresh --force` (nur `nouron_test`!)
Expected: Baseline läuft durch (44 Tabellen, 2 Views). Fehler (Typ, Indexlänge, View-SQL) im Generator bzw. in der Baseline-Datei beheben und Step 4–6 wiederholen.

- [ ] **Step 7: Commit**

```bash
git add database/migrations
git commit -m "refactor: Migrations-Squash auf Baseline (SQLite- und MySQL-fähig), 131 Altmigrationen entfernt (R5b)"
```

---

### Task 5: Test-Suite und CI auf MySQL umstellen

**Files:**
- Modify: `phpunit.xml:32-33`, `.github/workflows/ci.yml`, `config/database.php:20`
- Test: gesamte Suite

**Interfaces:**
- Consumes: Task 1 (Datenbanken), Task 4 (Baseline), Task 3 (Seeder).
- Produces: `bin/phpunit` läuft gegen `nouron_test` auf MySQL; CI gegen einen MySQL-Service.

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
Run: `bin/phpunit --testsuite=playtest` → grün (noch mit den festen Fixture-IDs; Umbau erst in Task 6).

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

### Task 6: PlaytestBot in gemeinsamer DB, jeder Lauf mit eigenem User

**Files:**
- Modify: `tests/Feature/Playtest/BotSession.php:46-80` (`boot()`), `tests/Feature/Playtest/PlaytestBotTest.php:22` (Trait-Override), `app/Console/Commands/Playtest.php:25-80`
- Test: `tests/Feature/Playtest/BotSessionIsolationTest.php` (neu), `tests/Feature/Console/PlaytestCommandGuardTest.php` (neu)

**Interfaces:**
- Consumes: `OnboardingService::setupNewPlayer(int $userId, string $colonyName = ''): Colony` und `resetColonyToSol1(int $userId, int $colonyId, ?int $rngSeed = null): void` (existieren); Task 3 (`MasterDataSeeder`).
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
        app(MasterDataSeeder::class)->run();
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
app(MasterDataSeeder::class)->run();
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

### Task 7: Doku nachziehen, Abschluss

**Files:**
- Modify: `CLAUDE.md` (Abschnitt „Wichtige Korrekturen": SQLite → MySQL, Dateinamen), `docs/adr/0005-produktions-datenbank-und-hosting.md` (Annahmen 1+2 → bestätigt/Entscheidung, Messwerte), `ROADMAP.md` (R5b ✅, Folgepunkte), `CHANGELOG.md` (Block des Tages), `.claude/agents/db-migration-agent.md` (SQLite-Hinweise → MySQL), `docs/game-reference.md` nur falls DB-Schema-Verweis dort steht.
- Create: `docs/deployment-db.md` (Produktions-Seeding: einmalig `php artisan db:seed --class=MasterDataSeeder --force`, jeder Deploy `php artisan migrate --force && php artisan game:sync-config`).

- [ ] **Step 1: CLAUDE.md**

`**Datenbank ist SQLite** (NICHT MySQL)` ersetzen durch: **Datenbank ist MySQL** (lokal `nouron` / `nouron_test` / `nouron_playtest`, Produktion Laravel Cloud, siehe `docs/dev-setup-mysql.md` und ADR 0005); „Schichtung … SQLite" → MySQL; `data/db/*.db`-Zeilen entfernen; TestSeeder-Satz anpassen (Stammdaten/Fixtures).

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
