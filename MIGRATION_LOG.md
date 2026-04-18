# Nouron Migration Log

Schema changes are recorded here in reverse-chronological order.

---

## 2026-04-17 — Update ship costs and add Sonde (ID 85)

- What changed:
  - Renamed `ships.name` for ID 37 (`techs_fighter1` → `ship_korvette`) and ID 47 (`techs_smallTransporter` → `ship_frachter`) to the current internal naming convention.
  - Inserted new ship Sonde (ID 85, military, requires hangar level 1, row 6/col 6, speed 5).
  - Deleted `ship_costs` rows for deprecated ships (IDs 29, 49, 83, 84, 88).
  - Replaced `ship_costs` for active ships (37, 47, 85) with multi-resource costs covering Credits (1), Werkstoffe (4), Organika (5).
- Why: Aligns ship catalogue with GDD §5/§11 (ship redesign, Regolith era). Old techs_* keys were inconsistent with the established ship_* naming convention.
- Breaking: No — existing colony_ships / fleet_ships rows reference IDs only, not names.
- Rollback: `php artisan migrate:rollback --step=1` — reverts name renames, removes Sonde, restores Credits-only costs for active and deprecated ships.

---

## 2026-03-22 — Initial Laravel migration files for all Nouron tables and views

- What changed: Created 35 Laravel migration files covering all 34 tables and 6 views
  from `data/sql/schema.sqlite.sql`. Files live in `database/migrations/` using the
  naming convention `0001_01_01_NNNNNN_create_*.php`. Execution order is controlled
  entirely by the numeric suffix (000001–000034, then 999999 for views).
  A permanent `sqlite_test` connection was added to `config/database.php` pointing
  at `data/db/laravel_migrate_test.db` for future clean-room validation runs.
- Why: Establish a repeatable, version-controlled schema bootstrap path via Laravel
  Artisan so the schema can be applied to any fresh SQLite file with a single command
  (`php artisan migrate --database=sqlite_test`).
- Breaking: no — existing `nouron.db` and `test.db` are untouched; migrations target
  a separate test file only.
- Rollback: `php artisan migrate:rollback --database=sqlite_test` (all `down()` methods
  verified clean). Individual files can be deleted to remove specific tables.
- Notes:
  - `colony_buildings.building_id` FK corrected from `glx_colonies(id)` (original
    schema typo) to `buildings(id)`.
  - `user` self-referencing FK (`user_id REFERENCES user(user_id)`) intentionally
    omitted — it carries no semantic meaning.
  - `innn_news.topic` CHECK constraint implemented via SQLite triggers (Laravel 12
    has no native Blueprint CHECK support for SQLite).
  - `fleet_orders.order` column kept as-is; SQLite/Laravel quotes it automatically.
  - `trade_resources` has no PRIMARY KEY in the canonical schema — preserved as-is.
  - `personell_costs` has no PRIMARY KEY or UNIQUE in the canonical schema — preserved.
