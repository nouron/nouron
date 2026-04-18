<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement("
            INSERT INTO personell (id, purpose, name, required_building_id, required_building_level,
                                   \"row\", \"column\", max_status_points, can_command_fleet)
            VALUES (93, 'military', 'techs_stratege', 25, 3, 9, 0, 10, 0)
        ");

        DB::statement("INSERT INTO personell_costs (personell_id, resource_id, amount) VALUES (93, 1, 7500)");
        DB::statement("INSERT INTO personell_costs (personell_id, resource_id, amount) VALUES (93, 2, 2)");

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement("DELETE FROM personell_costs WHERE personell_id = 93");
        DB::statement("DELETE FROM personell WHERE id = 93");
    }
};
