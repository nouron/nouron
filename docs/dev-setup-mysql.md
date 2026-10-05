# Lokale MySQL-Umgebung (nativ, ohne Docker)

Seit R5b (ADR 0005) läuft Nouron überall gegen MySQL 8: lokal, in CI und in Produktion. Diese Anleitung richtet die lokale Entwicklungsumgebung unter Ubuntu/WSL2 ein.

## Installation

```bash
sudo apt update && sudo apt install -y mysql-server php8.2-mysql
sudo service mysql start
```

Lokal läuft PHP 8.2, CI nutzt 8.4. Prüfen: `php -m | grep pdo_mysql` muss `pdo_mysql` zeigen.

WSL startet Dienste nicht automatisch: nach jedem WSL-Neustart `sudo service mysql start`.

## Datenbanken und User

Drei getrennte Datenbanken, ein User `nouron` (Passwort `nouron`, nur lokal):

| Datenbank | Zweck |
|---|---|
| `nouron` | Entwicklungsdatenbank (Spielstand im Browser) |
| `nouron_test` | PHPUnit-Tests (bei paratest zusätzlich `nouron_test_test_N`) |
| `nouron_playtest` | Playtest-Bot (`game:playtest`) |

```bash
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS nouron CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS nouron_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS nouron_playtest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'nouron'@'localhost' IDENTIFIED BY 'nouron';
GRANT ALL ON nouron.* TO 'nouron'@'localhost';
GRANT ALL ON nouron_playtest.* TO 'nouron'@'localhost';
-- nouron_test plus die Worker-DBs von paratest (nouron_test_test_1, ...)
GRANT ALL ON `nouron\_test%`.* TO 'nouron'@'localhost';
FLUSH PRIVILEGES;
SQL
```

Der Grant auf `` `nouron\_test%` `` ist nötig, weil `artisan test --parallel` (paratest) pro Worker eine eigene Datenbank `nouron_test_test_N` anlegt; ohne das Muster fehlen dem User dort die Rechte. Der Backslash escaped den Unterstrich (sonst Wildcard).

## `.env` auf MySQL umstellen

Die Vorlage `.env.example` enthält bereits die MySQL-Werte. In einer bestehenden `.env`, die noch auf SQLite steht, diese Zeilen setzen (und `DB_DATABASE` mit dem alten Dateipfad ersetzen):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nouron
DB_USERNAME=nouron
DB_PASSWORD=nouron
```

Verbindung prüfen:

```bash
php artisan db:show
```

Erwartet: `MySQL 8.x`, Datenbank `nouron`.

Die Dev-DB `nouron` ist nach der Installation leer und wird einmalig aufgebaut:

```bash
php artisan db:reset --force
```

**Achtung:** `db:reset` löscht alle Tabellen der Datenbank in `.env` und baut sie neu auf (Baseline-Migration, `ReferenceDataSeeder`, Testspieler-Fixtures). Das ist nur für eine frische oder bewusst zu verwerfende Dev-DB gedacht. Der alte SQLite-Spielstand wurde nicht übernommen (die SQLite-Dateien sind entfernt).

Nach Branch-Wechseln zwischen Schema-Ständen, oder wenn die Baseline geändert wurde (vor dem Produktivgang wird sie noch direkt editiert), ist ebenfalls ein frischer Aufbau nötig: `php artisan db:reset --force`.

## Tests

`phpunit.xml` erzwingt `DB_CONNECTION=mysql` und `DB_DATABASE=nouron_test`; Host, Port, User und Passwort sind dort nur Vorgaben (`127.0.0.1:3306`, `nouron`/`nouron`), die eine gesetzte Umgebungsvariable überschreibt (CI nutzt `root`). Die lokale `.env` spielt für Tests keine Rolle.

```bash
bin/phpunit                                   # seriell, volle Suite
bin/phpunit --testsuite=laravel-feature,laravel-unit   # schnelle Suite (während der Entwicklung)
php artisan test --parallel --processes=4     # paratest
```

Jeder paratest-Worker migriert seine eigene Datenbank `nouron_test_test_N` (Grant `` `nouron\_test%` `` siehe oben). Ohne `--processes` startet paratest einen Worker pro Kern; auf dem Entwicklungsrechner (24 Kerne) war das durch die gleichzeitigen Migrationen langsamer als 4 Prozesse.

Gemessene Laufzeiten (WSL2, 24 Kerne), zur Einordnung (SQLite brauchte für die schnelle Suite ca. 1,5 min):

| Lauf | Laufzeit |
|---|---|
| schnelle Suite seriell, vor dem Performance-Tuning | ca. 9,5 min |
| schnelle Suite seriell, mit Tuning (unter Last) | 10:58–11:36 min |
| `--parallel` mit 24 Prozessen / `--processes=4` (vor dem Tuning) | 6:19 / 3:37 min |
| Playtest-Suite | ca. 20 min |

Langsam ist vor allem DDL: `migrate:fresh` der Baseline dauert ca. 25 s, weil MySQL jede DDL-Anweisung auf die Platte synchronisiert.

## Performance-Tuning (nur lokal)

Auf dem Entwicklungsrechner ist die Durability der MySQL-Instanz abgeschaltet, in `/etc/mysql/mysql.conf.d/zz-nouron-tests.cnf`:

```ini
[mysqld]
innodb_flush_log_at_trx_commit = 0
sync_binlog = 0
skip-log-bin
innodb_doublewrite = OFF
innodb_buffer_pool_size = 1G
max_connections = 200
```

Danach `sudo service mysql restart`. **Warnung:** Nur für eine lokale Test-/Dev-Instanz. Ein Absturz oder Stromausfall kann die letzte Sekunde Schreibvorgänge verlieren oder Seiten beschädigen (Doublewrite aus), und ohne Binlog gibt es keine Wiederherstellung. Nie auf eine Instanz mit Daten übertragen, die man behalten muss. `max_connections = 200` ist für parallele Bot-Läufe und paratest-Worker nötig.

## Playtest-Bot (`game:playtest`)

Alle Läufe eines `game:playtest`-Aufrufs spielen parallel in **einer** Datenbank `nouron_playtest` (Config `game.playtest.database`, Env `PLAYTEST_DATABASE`), jeder Lauf mit eigenem User und eigener Kolonie. Der Elternprozess setzt sie einmal pro Aufruf zurück (`migrate:fresh` + `ReferenceDataSeeder`) und übergibt den PHPUnit-Kindprozessen `PLAYTEST_SHARED_DB=1`, `PLAYTEST_DATABASE` und die MySQL-Zugangsdaten. Weil `phpunit.xml` `DB_DATABASE=nouron_test` erzwingt, schaltet `PlaytestBotTest` die Verbindung zur Laufzeit um (`App\Console\Support\PlaytestDatabase::connect()`) und lässt den Transaktions-Rollback weg, die Daten bleiben committet.

Der Guard verweigert (Eltern und Kinder) jeden Datenbanknamen, der leer ist, `nouron` heißt, mit `nouron_test` beginnt oder der Datenbank der Standard-/`mysql`-Verbindung entspricht; geprüft wird die tatsächlich verbundene Datenbank (`select database()`). Das Prozess-Timeout pro Batch beträgt 1620 s (Env `PLAYTEST_PROCESS_TIMEOUT`); ein einzelner Lauf dauert 8–14 min, acht parallele Läufe ca. 17–18 min.

Seeds sind reproduzierbar: Der Spielzufall hängt nur von `runs.rng_seed` ab, nicht von IDs oder der Startreihenfolge in der gemeinsamen DB. Vergleiche mit gleichem Seed gelten nur innerhalb desselben Code-Stands.

Der Elternprozess braucht MySQL-Zugangsdaten aus `.env` oder der Umgebung. Solange `.env` noch auf SQLite steht: `DB_CONNECTION=mysql DB_DATABASE=nouron DB_USERNAME=nouron DB_PASSWORD=nouron php artisan game:playtest …` (der Guard schützt `nouron` trotzdem, die Bot-Läufe landen in `nouron_playtest`).

## Regel: Tests und Bot nie gegen `nouron`

Tests laufen nur gegen `nouron_test`, der Playtest-Bot nur gegen `nouron_playtest`, nie gegen `nouron`. Sonst wird der Dev-Spielstand überschrieben. Agenten dürfen `migrate:fresh`, `db:seed` und `db:reset` nie gegen `nouron` ausführen; für Experimente `DB_DATABASE=nouron_test` o. ä. vorgeben.

## Fehlerbilder

- `SQLSTATE[HY000] [2002] Connection refused`: Dienst läuft nicht. Unter WSL `sudo service mysql start` (ein Neustart von WSL beendet den Dienst); Status mit `sudo service mysql status`.
- `could not find driver`: `php8.2-mysql` fehlt, siehe Installation.
- `Access denied` bei paratest-Worker-DBs: Grant `` `nouron\_test%` `` fehlt.
- `Too many connections`: `max_connections` zu niedrig (parallele Bots/Worker), siehe Performance-Tuning.
- `Lock wait timeout exceeded` oder `Deadlock found` (SQLSTATE 40001, Code 1213): Beim Onboarding fängt `App\Support\DeadlockRetry` Deadlocks ab und wiederholt die Transaktion; jeder Retry steht mit Versuch, SQLSTATE und Fehlercode in `storage/logs/laravel.log`. Taucht der Fehler trotzdem auf, zuerst dort nachsehen und prüfen, ob noch ein Test, Bot oder `artisan`-Lauf gegen dieselbe Datenbank läuft.
