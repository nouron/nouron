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

## Regel: Tests und Bot nie gegen `nouron`

Tests laufen nur gegen `nouron_test`, der Playtest-Bot nur gegen `nouron_playtest`, nie gegen `nouron`. Sonst wird der Dev-Spielstand ueberschrieben (siehe Vorfall 2026-09-17). Agenten duerfen `migrate:fresh`/`db:seed` nie gegen `nouron` ausfuehren.

## Fehlerbilder

- `SQLSTATE[HY000] [2002] Connection refused`: Dienst laeuft nicht, `sudo service mysql start`.
- `could not find driver`: `php8.2-mysql` fehlt, siehe Installation.
- `Access denied` bei paratest-Worker-DBs: Grant `` `nouron\_test%` `` fehlt.
