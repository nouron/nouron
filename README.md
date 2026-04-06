# Nouron — A Free Space Opera Browsergame

Nouron is a sci-fi strategy browsergame built with PHP 8.2, Laravel 12, SQLite and Bootstrap 5.

## Quickstart

Nouron uses an SQLite database delivered with the project (`data/db/nouron.db`), so no database setup is required for local testing.

**Requirements:** PHP 8.2+, Composer

> **Windows users:** The project runs under WSL2. Run all commands inside your WSL2 terminal.

```bash
# After cloning, install dependencies
composer install

# Copy environment file and adjust as needed
cp .env.example .env
php artisan key:generate

# Start local dev server
php artisan serve

# Run tests (Feature suite)
php artisan test --testsuite=Feature
```

### Test accounts

The development database (`data/db/nouron.db`) contains Simpsons test characters:

| Username | Password | Notes |
|----------|----------|-------|
| `Bart` | `test` | Colony "Springfield" |
| `Homer` | `test` | |
| `Marge` | `test` | |

### Resetting game state

To reset the development database to its original state:

```bash
cp data/db/nouron.db.bak data/db/nouron.db   # if a backup exists
# or re-run the seed:
php artisan db:seed --class=DevSeeder
```

### Database files

| File | Purpose |
|------|---------|
| `data/db/nouron.db` | Development database (used by `php artisan serve`) |
| `data/db/test.db` | Test database (used by the test suite, seeded automatically) |

### Useful Artisan commands

```bash
# Sync ship/building values from config files to the database
php artisan game:sync-techs

# Preview changes without writing
php artisan game:sync-techs --dry-run

# Manually trigger a game tick (normally runs via scheduler)
php artisan game:tick
```

### Scheduler setup

The game tick runs once per day at 03:00. To activate the Laravel scheduler on your server, add one cron entry:

```
* * * * * cd /path/to/nouron && php artisan schedule:run >> /dev/null 2>&1
```

## Environment & Dev Settings

Copy `.env.example` to `.env`. The most relevant settings for local development:

### Game Bypass Flags

These flags disable specific game rule checks so you can test individual systems freely. All default to `false` (rules enforced).

| Flag | What it bypasses |
|------|-----------------|
| `GAME_BYPASS_AP=true` | Navigation, Economy and Construction AP checks |
| `GAME_BYPASS_RESOURCES=true` | Resource cost checks when building/researching |
| `GAME_BYPASS_SUPPLY=true` | Supply capacity checks for buildings, ships and advisors |

**Common test scenarios:**

```bash
# Free-click everything (no checks at all) — fastest way to explore the game
GAME_BYPASS_AP=true
GAME_BYPASS_RESOURCES=true
GAME_BYPASS_SUPPLY=true

# Test AP behaviour with real checks active
GAME_BYPASS_AP=false
GAME_BYPASS_RESOURCES=true
GAME_BYPASS_SUPPLY=true

# Test Supply behaviour with real checks active
GAME_BYPASS_AP=true
GAME_BYPASS_RESOURCES=true
GAME_BYPASS_SUPPLY=false
```

> **Note:** All bypass flags are blocked in production — the app will refuse to start if any flag is `true` when `APP_ENV=production`.

> **Deprecated:** `GAME_DEV_MODE=true` still works as a shortcut (sets all three flags) but logs a deprecation warning. Use the individual flags instead.

## Bug Tracker

https://github.com/nouron/nouron/issues

## Community

* [Facebook](https://facebook.com/nouronbg)
* [X / Twitter](https://x.com/_nouron)

## Authors and Supporters

* Mario Gehnke — https://github.com/tector
* Thanks to Peter Wippermann (www.todoz.de) and Jacqueline Wiesenberg for some of the graphics.

## Copyright and License

Copyright 2012–2026 Mario Gehnke

The source code is licensed under the GNU General Public License V3. See:
* `LICENSE.txt`
* http://www.gnu.org/licenses/

All graphics and texts are licensed (unless otherwise noted) under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany (CC BY-NC-SA 3.0). See:
* (de) http://creativecommons.org/licenses/by-nc-sa/3.0/de
* (en) http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en

This project uses third-party frameworks and libraries with their own licenses:
* [Laravel](https://laravel.com/)
* [Bootstrap 5](https://getbootstrap.com/)
* [jQuery](https://jquery.com/)
