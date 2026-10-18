<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration moves live reference rows to the new band/cell layout without tripping the
 * (phase, row, column) unique indexes — also when two rows swap cells.
 */
class TechtreeGridLayoutMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_rows_from_the_old_layout_even_when_cells_collide(): void
    {
        $this->seed(\Database\Seeders\ReferenceDataSeeder::class);

        // Old layout: hangar at phase 3 (3,1,2), sciencelab at (2,1,2) -> hangar's NEW cell is (2,1,2).
        DB::table('buildings')->where('id', 44)->update(['phase' => 3, 'row' => 1, 'column' => 2]);
        DB::table('buildings')->where('id', 31)->update(['phase' => 2, 'row' => 1, 'column' => 2]);

        (require database_path('migrations/2026_10_18_000000_techtree_grid_layout.php'))->up();

        $this->assertSame([2, 1, 2], $this->cell('buildings', 44));
        $this->assertSame([2, 1, 3], $this->cell('buildings', 31));
        $this->assertSame([2, 3, 1], $this->cell('personell', 89));
        $this->assertSame([5, 1, 2], $this->cell('buildings', 50));
    }

    private function cell(string $table, int $id): array
    {
        $r = DB::table($table)->where('id', $id)->first();

        return [(int) $r->phase, (int) $r->row, (int) $r->column];
    }
}
