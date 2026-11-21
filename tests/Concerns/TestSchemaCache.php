<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T58d: remembers which migrations a test database was built from, so a new test process
 * can skip migrate:fresh (13 s alone, ~70 s with 12 processes at once) when nothing changed
 * and only empty the tables. A changed, added or removed migration, or a database whose
 * migrations table does not match, is rebuilt as before. CI starts from empty databases
 * without markers, so it always migrates.
 */
final class TestSchemaCache
{
    public function __construct(
        private readonly string $migrationsDir,
        private readonly string $markerDir,
        private readonly string $database,
        private readonly int $migrationRows,
    ) {}

    /** Cache for the current test connection (database name and its migrations table). */
    public static function forCurrentConnection(): self
    {
        $database = (string) DB::connection()->getDatabaseName();
        $rows = Schema::hasTable('migrations') ? (int) DB::table('migrations')->count() : 0;

        return new self(database_path('migrations'), storage_path('framework/testing'), $database, $rows);
    }

    public function isCurrent(): bool
    {
        $marker = $this->markerPath();

        return is_file($marker)
            && trim((string) file_get_contents($marker)) === $this->fingerprint()
            && $this->migrationRows === count($this->files());
    }

    public function remember(): void
    {
        if (! is_dir($this->markerDir)) {
            mkdir($this->markerDir, 0777, true);
        }
        file_put_contents($this->markerPath(), $this->fingerprint());
    }

    /** Deletes every row except the migrations bookkeeping (DML, so it also works inside a test transaction). */
    public static function emptyTables(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (Schema::getTables($database) as $table) {
                if ($table['name'] !== 'migrations' && DB::table($table['name'])->exists()) {
                    DB::table($table['name'])->delete();
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function fingerprint(): string
    {
        $hash = hash_init('sha256');
        foreach ($this->files() as $file) {
            hash_update($hash, basename($file)."\0".file_get_contents($file)."\0");
        }

        return hash_final($hash);
    }

    /** @return list<string> */
    private function files(): array
    {
        $files = glob($this->migrationsDir.'/*.php') ?: [];
        sort($files);

        return $files;
    }

    private function markerPath(): string
    {
        return $this->markerDir.'/schema-'.preg_replace('/[^A-Za-z0-9_]/', '_', $this->database).'.sha256';
    }
}
