# Nouron Migration Log

Schema changes are recorded here in reverse-chronological order.

---

## 2026-05-04 — Phase 3e: user_preferences table (onboarding)

- What changed:
  - New table `user_preferences` — per-user settings; columns: `user_id` (FK → `user.user_id`), `onboarding_hints` BOOLEAN DEFAULT 1.
  - Added column `dismissed_hints` TEXT NULL to `user_preferences` — JSON array of hint keys the user has dismissed (e.g. `["hint_1","hint_3"]`).
- Why: Phase 3e Onboarding — persistent storage for hint visibility preference and per-hint dismiss state.
- Breaking: No — table is new; no existing data affected.
- Rollback: `php artisan migrate:rollback --step=2` drops `dismissed_hints` column, then drops `user_preferences` table.

---

## 2026-05-01 — Harvester marked as instanced building

- What changed:
  - `buildings`: set `is_instanced = 1` for Harvester (ID 27). Max instances = 1 (enforced via `max_level`).
  - `testdata.sqlite.sql`: all `buildings` INSERT statements updated with explicit `is_instanced` column.
- Why: Harvester uses the same instanced-building infrastructure as housingComplex/hangar; the existing relocation (Move-Action) workflow required it.
- Breaking: No — data model unchanged; `is_instanced` was already present.
- Rollback: `php artisan migrate:rollback --step=1` — resets `is_instanced = 0` for Harvester.

---

## 2026-04-30 — Phase 3d: rename colony_tiles.is_ring_unlocked → is_colony_zone

- What changed:
  - `colony_tiles`: column `is_ring_unlocked` renamed to `is_colony_zone`.
  - Semantic change: `is_colony_zone` means "this terrain tile is inside the colony zone and buildable", not just "its ring is unlocked for navigation". Exploration-zone tiles (regolith, impassable) remain `is_colony_zone = false`.
- Why: Phase 3d Colony Zone Expansion — tile-count-based unlock (not ring-based), making the old name misleading.
- Breaking: Yes — any raw SQL or code that referenced `is_ring_unlocked` must use `is_colony_zone` instead.
- Rollback: `php artisan migrate:rollback --step=1` — renames column back to `is_ring_unlocked`.

---

## 2026-04-25 — Buildings cleanup: 25 → 11 active buildings

- What changed:
  - `buildings`: removed 14 unused building rows (deprecated civilian buildings — temple, stadium, casino, etc.); normalized `name` keys to `building_*` convention; adjusted `required_building_level` values to match new AP costs.
  - `building_costs`: removed cost rows for deleted buildings.
  - `testdata.sqlite.sql`: updated to match reduced building catalogue.
- Why: Phase 3b buildings redesign — smaller, focused building catalogue aligned with GDD §4. Old `techs_*` keys retired.
- Breaking: Yes — hard-coded building IDs or names for removed buildings will fail.
- Rollback: `php artisan migrate:rollback --step=1` — re-inserts deleted rows and restores old names.

---

## 2026-04-23 — DS-4 schema: colony_tiles, planet/grid fields, instanced buildings

- What changed:
  - New table `colony_tiles` — hex-grid surface map per colony (axial q/r, ring, tile_type, event_type, resource counters, exploration flags). FK to `glx_colonies` with CASCADE DELETE.
  - `glx_system_objects`: added `planet_size` VARCHAR NULL, `planet_type` VARCHAR NULL, `grid_x` INTEGER NULL, `grid_y` INTEGER NULL. All nullable for backward compat.
  - `fleets`: removed `spot` column (legacy dock-slot integer), added `grid_x` / `grid_y` INTEGER NULL for system-grid placement. Table rebuilt via rename→create→copy.
  - `colony_buildings`: added `instance_id` INTEGER NOT NULL DEFAULT 1, `tile_x` / `tile_y` INTEGER NULL. UNIQUE constraint widened from (colony_id, building_id) to (colony_id, building_id, instance_id). Table rebuilt.
  - `buildings`: added `is_instanced` BOOLEAN NOT NULL DEFAULT 0. Set to 1 for housingComplex (ID 28) and hangar (ID 44).
  - New config file `config/tile_types.php` — canonical tile/event type catalogue.
  - Updated `data/sql/schema.sqlite.sql` — canonical schema brought fully in sync.
  - Updated `data/sql/testdata.sqlite.sql` — INSERT statements for `glx_system_objects`, `buildings`, `colony_buildings`, `fleets` converted to explicit column lists (future-proofing).
  - Application fixes: `Fleet::$fillable` and `Fleet::getCoords()` updated (removed spot), `FleetController::store()` and `hold` order updated, `FleetService::transferResource()` coordinate comparison fixed.
- Why: DS-4 (Tile Catalogue, Planet Types) and DS-2 (System Grid) design decisions. Instanced buildings required by housingComplex/hangar multi-instance model (GDD §4).
- Breaking: Yes — `fleets.spot` column removed. Any code still writing `fleets.spot` will fail. `Fleet::getCoords()` now returns `[x, y]` instead of `[x, y, spot]`.
- Rollback: `php artisan migrate:rollback --step=5` — reverts all 5 migrations in reverse order. `colony_tiles` is dropped; `fleets` restores `spot = 0` for all rows (grid_x/grid_y values are lost).

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
