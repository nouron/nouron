<?php

namespace Tests\Feature\Testing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\TestSchemaCache;
use Tests\TestCase;

/**
 * T58d (owner 2026-11-20): a test process rebuilt its whole database on every start (13 s
 * alone, ~70 s with 12 processes at once) although the schema had not changed. The cache
 * remembers the migrations a test database was built from; an unchanged schema is only
 * emptied, a changed one is rebuilt as before.
 */
class TestSchemaCacheTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    private string $migrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/schema-cache-test-'.uniqid();
        $this->migrations = $this->dir.'/migrations';
        mkdir($this->migrations, 0777, true);
        file_put_contents($this->migrations.'/0001_create_a.php', '<?php // a');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*/*') ?: []);
        array_map('rmdir', glob($this->dir.'/*', GLOB_ONLYDIR) ?: []);
        array_map('unlink', glob($this->dir.'/*') ?: []);
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function cache(): TestSchemaCache
    {
        return new TestSchemaCache($this->migrations, $this->dir, 'testdb', 1);
    }

    public function test_unknown_database_is_not_current(): void
    {
        $this->assertFalse($this->cache()->isCurrent());
    }

    public function test_remembered_schema_is_current(): void
    {
        $this->cache()->remember();

        $this->assertTrue($this->cache()->isCurrent());
    }

    public function test_a_changed_migration_makes_it_stale(): void
    {
        $this->cache()->remember();
        file_put_contents($this->migrations.'/0001_create_a.php', '<?php // a, edited');

        $this->assertFalse($this->cache()->isCurrent());
    }

    public function test_a_new_migration_makes_it_stale(): void
    {
        $this->cache()->remember();
        file_put_contents($this->migrations.'/0002_create_b.php', '<?php // b');

        $this->assertFalse($this->cache()->isCurrent());
    }

    public function test_a_database_whose_migration_rows_do_not_match_is_stale(): void
    {
        $this->cache()->remember();

        // Database dropped or rebuilt by hand: the migrations table no longer has the expected rows.
        $this->assertFalse((new TestSchemaCache($this->migrations, $this->dir, 'testdb', 0))->isCurrent());
    }

    public function test_empty_tables_removes_rows_but_keeps_the_schema(): void
    {
        DB::table('cantina_casts')->insert(['colony_id' => 1, 'tick' => 1, 'size' => 1, 'figures' => '[]']);

        TestSchemaCache::emptyTables();

        $this->assertSame(0, DB::table('cantina_casts')->count());
        $this->assertGreaterThan(0, DB::table('migrations')->count(), 'the migrations table stays');
    }
}
