<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Techtree canvas screen: `phase` becomes the Command-Center band and (row, column) the cell inside
 * that band (column 1..4), so the screen's grid is drawn straight from the reference data.
 *
 * ReferenceDataSeeder cannot apply this itself: its upsert runs against the (phase, row, column)
 * unique indexes and a moved row can collide with a not-yet-moved one mid-statement. So every
 * affected row is first parked on a unique phase-0 cell, then set to its final position. The
 * mapping is frozen here on purpose (it must not drift with later seeder edits). On a fresh
 * database the tables are empty and this is a no-op.
 */
return new class extends Migration
{
    /** table => [id => [phase, row, column]] */
    private const LAYOUT = [
        'buildings' => [
            25 => [0, 0, 2],
            27 => [1, 1, 1], 28 => [1, 1, 2], 41 => [1, 2, 1],
            44 => [2, 1, 2], 31 => [2, 1, 3], 52 => [2, 1, 4], 53 => [2, 4, 1], 54 => [2, 4, 4],
            46 => [3, 1, 4],
            55 => [4, 1, 1], 32 => [4, 1, 4],
            50 => [5, 1, 2],
        ],
        'researches' => [
            93 => [2, 2, 1], 91 => [2, 2, 2], 90 => [2, 2, 3], 95 => [2, 2, 4],
            92 => [3, 1, 1], 94 => [3, 2, 4],
            96 => [4, 1, 3],
        ],
        'ships' => [
            85 => [2, 3, 2],
            47 => [3, 1, 2],
            37 => [4, 1, 2],
        ],
        'personell' => [
            35 => [1, 1, 3],
            89 => [2, 3, 1], 36 => [2, 3, 3], 92 => [2, 3, 4],
            93 => [3, 1, 3],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::LAYOUT as $table => $rows) {
                foreach (array_keys($rows) as $id) {
                    DB::table($table)->where('id', $id)->update(['phase' => 0, 'row' => 900 + $id, 'column' => 900]);
                }
                foreach ($rows as $id => [$phase, $row, $column]) {
                    DB::table($table)->where('id', $id)->update(['phase' => $phase, 'row' => $row, 'column' => $column]);
                }
            }
        });
    }

    public function down(): void
    {
        // Positions are reference data; the previous layout is restored by checking out the old seeder files.
    }
};
