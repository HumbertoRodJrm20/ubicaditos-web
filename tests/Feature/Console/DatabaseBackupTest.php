<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cobertura TDD del respaldo y restauración local de bases de datos.
 */
class DatabaseBackupTest extends TestCase
{
    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ubicaditos-backup-'.uniqid().'.sqlite';
        touch($this->databaseFile);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->databaseFile,
        ]);
        DB::purge('sqlite');

        DB::connection('sqlite')->getSchemaBuilder()->create('backup_probe', function (Blueprint $table): void {
            $table->id();
            $table->string('value');
        });
        DB::connection('sqlite')->table('backup_probe')->insert(['value' => 'original']);

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        if (is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        parent::tearDown();
    }

    public function test_backup_command_creates_a_sqlite_snapshot_on_the_configured_disk(): void
    {
        $this->artisan('backup:database', [
            '--disk' => 'local',
            '--path' => 'backups',
            '--keep' => 7,
        ])->assertSuccessful();

        $files = Storage::disk('local')->files('backups');

        $this->assertCount(1, $files);
        $this->assertStringContainsString('sqlite-', $files[0]);
        $this->assertNotEmpty(Storage::disk('local')->get($files[0]));
    }

    public function test_backup_command_prunes_files_beyond_the_retention_limit(): void
    {
        $disk = Storage::disk('local');

        foreach (['20250101000000', '20250102000000', '20250103000000'] as $timestamp) {
            $disk->put("backups/sqlite-{$timestamp}.sqlite", 'respaldo anterior');
        }

        $this->artisan('backup:database', [
            '--disk' => 'local',
            '--path' => 'backups',
            '--keep' => 2,
        ])->assertSuccessful();

        $this->assertCount(2, $disk->files('backups'));
        $this->assertFalse($disk->exists('backups/sqlite-20250101000000.sqlite'));
    }

    public function test_restore_command_recovers_the_snapshot_after_a_later_change(): void
    {
        $this->artisan('backup:database', [
            '--disk' => 'local',
            '--path' => 'backups',
        ])->assertSuccessful();

        $backup = Storage::disk('local')->files('backups')[0];

        DB::connection('sqlite')->table('backup_probe')->update(['value' => 'cambio posterior']);
        $this->assertSame('cambio posterior', DB::connection('sqlite')->table('backup_probe')->value('value'));

        $this->artisan('backup:restore', [
            'file' => $backup,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('original', DB::connection('sqlite')->table('backup_probe')->value('value'));
    }

    public function test_backup_command_fails_loudly_for_an_in_memory_database(): void
    {
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $this->artisan('backup:database', [
            '--disk' => 'local',
            '--path' => 'backups',
        ])->assertFailed();

        $this->assertSame([], Storage::disk('local')->files('backups'));
    }
}
