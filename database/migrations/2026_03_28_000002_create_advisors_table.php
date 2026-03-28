<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE advisors (
                id                     INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id                INTEGER NOT NULL REFERENCES user(user_id),
                personell_id           INTEGER NOT NULL REFERENCES personell(id),
                colony_id              INTEGER REFERENCES glx_colonies(id),
                fleet_id               INTEGER REFERENCES fleets(id),
                is_commander           INTEGER NOT NULL DEFAULT 0,
                rank                   INTEGER NOT NULL DEFAULT 1,
                active_ticks           INTEGER NOT NULL DEFAULT 0,
                unavailable_until_tick INTEGER,
                CHECK (colony_id IS NULL OR fleet_id IS NULL)
            )
        ');

        // Migrate colony_personell → advisors (one row per advisor unit)
        $colonyRows = DB::select('
            SELECT cp.colony_id, cp.personell_id, cp.level, c.user_id
            FROM colony_personell cp
            JOIN glx_colonies c ON c.id = cp.colony_id
            WHERE cp.level > 0
        ');

        foreach ($colonyRows as $row) {
            for ($i = 0; $i < $row->level; $i++) {
                DB::statement(
                    'INSERT INTO advisors (user_id, personell_id, colony_id, fleet_id, is_commander, rank, active_ticks)
                     VALUES (?, ?, ?, NULL, 0, 1, 0)',
                    [$row->user_id, $row->personell_id, $row->colony_id]
                );
            }
        }

        // Migrate fleet_personell → advisors (Kommandant only, is_commander=1)
        $fleetRows = DB::select('
            SELECT fp.fleet_id, fp.personell_id, fp.count, f.user_id
            FROM fleet_personell fp
            JOIN fleets f ON f.id = fp.fleet_id
            WHERE fp.personell_id = 89 AND fp.count > 0
        ');

        foreach ($fleetRows as $row) {
            for ($i = 0; $i < $row->count; $i++) {
                DB::statement(
                    'INSERT INTO advisors (user_id, personell_id, colony_id, fleet_id, is_commander, rank, active_ticks)
                     VALUES (?, ?, NULL, ?, 1, 1, 0)',
                    [$row->user_id, $row->personell_id, $row->fleet_id]
                );
            }
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS advisors');
    }
};
