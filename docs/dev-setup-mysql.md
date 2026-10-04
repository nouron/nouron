# Lokale MySQL-Umgebung (nativ, ohne Docker)

Seit R5b (ADR 0005) laeuft Nouron gegen MySQL 8. Diese Anleitung richtet die lokale Entwicklungsumgebung unter Ubuntu/WSL2 ein.

## Installation

```bash
sudo apt update && sudo apt install -y mysql-server php8.2-mysql
sudo service mysql start
```

Lokal laeuft PHP 8.2, CI nutzt 8.4. Pruefen: `php -m | grep pdo_mysql` muss `pdo_mysql` zeigen.

WSL startet Dienste nicht automatisch: nach jedem WSL-Neustart `sudo service mysql start`.

## Datenbanken und User

Drei getrennte Datenbanken, ein User `nouron` (Passwort `nouron`, nur lokal):

| Datenbank | Zweck |
|---|---|
| `nouron` | Entwicklungsdatenbank (Spielstand im Browser) |
| `nouron_test` | PHPUnit-Tests |
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

Der Grant auf `` `nouron\_test%` `` ist noetig, weil `artisan test --parallel` (paratest) pro Worker eine eigene Datenbank `nouron_test_test_N` anlegt; ohne das Muster fehlen dem User dort die Rechte. Der Backslash escaped den Unterstrich (sonst Wildcard).

Ein fertiges Setup-Skript (Installation plus obige SQL, idempotent) existiert nur lokal im Scratchpad und ist nicht im Repo; der Inhalt entspricht exakt den Befehlen oben.

## `.env`

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nouron
DB_USERNAME=nouron
DB_PASSWORD=nouron
```

Diese Werte stehen in `.env.example`. Die lokale `.env` bleibt bis Task 6 des R5b-Plans auf SQLite und wird erst dann umgestellt.

Verbindung pruefen (ohne `.env` zu aendern):

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=nouron \
DB_USERNAME=nouron DB_PASSWORD=nouron php artisan db:show --database=mysql
```

Erwartet: `MySQL 8.x`, Datenbank `nouron`.

## Dev-DB zuruecksetzen

Erst nachdem `.env` auf MySQL zeigt (Task 6):

```bash
php artisan db:reset --force
```

## Tests

`phpunit.xml` erzwingt `DB_CONNECTION=mysql` und `DB_DATABASE=nouron_test`; Host, Port, User und Passwort sind dort nur Vorgaben (`127.0.0.1:3306`, `nouron`/`nouron`), die eine gesetzte Umgebungsvariable ueberschreibt (CI nutzt `root`). Die lokale `.env` spielt fuer Tests keine Rolle.

```bash
bin/phpunit                                   # seriell, ca. 9,5 min
php artisan test --parallel --processes=4     # paratest, ca. 3,5 min
```

Gemessen 2026-10-03 (WSL2, 24 Kerne): Ohne `--processes` startet paratest einen Worker pro Kern und wird durch die gleichzeitigen Migrationen langsamer (ca. 6 min); 4 Prozesse sind lokal am schnellsten. Jeder Worker migriert seine Datenbank `nouron_test_test_N` selbst.

Langsam ist vor allem DDL: `migrate:fresh` der Baseline dauert ca. 25 s, weil MySQL jede DDL-Anweisung auf die Platte synchronisiert. Optional (nur Entwicklungsrechner, nicht verifiziert, erfordert sudo) laesst sich das abschwaechen, in `/etc/mysql/mysql.conf.d/zz-dev.cnf`:

```ini
[mysqld]
innodb_flush_log_at_trx_commit = 0
sync_binlog = 0
skip-log-bin
```

Danach `sudo service mysql restart`. Ein Absturz kann dann die letzte Sekunde Schreibvorgaenge verlieren, fuer Dev/Test unkritisch.

## Playtest-Bot (`game:playtest`)

Alle Laeufe eines `game:playtest`-Aufrufs spielen parallel in **einer** Datenbank `nouron_playtest` (Config `game.playtest.database`, Env `PLAYTEST_DATABASE`), jeder Lauf mit eigenem User und eigener Kolonie. Der Elternprozess setzt sie einmal pro Aufruf zurueck (`migrate:fresh` + `ReferenceDataSeeder`) und uebergibt den PHPUnit-Kindprozessen `PLAYTEST_SHARED_DB=1`, `PLAYTEST_DATABASE` und die MySQL-Zugangsdaten. Weil `phpunit.xml` `DB_DATABASE=nouron_test` erzwingt, schaltet `PlaytestBotTest` die Verbindung zur Laufzeit um (`App\Console\Support\PlaytestDatabase::connect()`) und laesst den Transaktions-Rollback weg, die Daten bleiben committet. Eltern und Kinder verweigern jeden Datenbanknamen, der leer ist, `nouron` oder `nouron_test` heisst oder der Datenbank der Standard-/`mysql`-Verbindung entspricht; geprueft wird die tatsaechlich verbundene Datenbank (`select database()`). Der Elternprozess braucht MySQL-Zugangsdaten aus `.env` (oder der Umgebung), z. B. solange `.env` noch auf SQLite steht: `DB_CONNECTION=mysql DB_DATABASE=nouron DB_USERNAME=nouron DB_PASSWORD=nouron php artisan game:playtest …`.

## Regel: Tests und Bot nie gegen `nouron`

Tests laufen nur gegen `nouron_test`, der Playtest-Bot nur gegen `nouron_playtest`, nie gegen `nouron`. Sonst wird der Dev-Spielstand ueberschrieben (siehe Vorfall 2026-09-17). Agenten duerfen `migrate:fresh`/`db:seed` nie gegen `nouron` ausfuehren.

## Fehlerbilder

- `SQLSTATE[HY000] [2002] Connection refused`: Dienst laeuft nicht, `sudo service mysql start`.
- `could not find driver`: `php8.2-mysql` fehlt, siehe Installation.
- `Access denied` bei paratest-Worker-DBs: Grant `` `nouron\_test%` `` fehlt.
