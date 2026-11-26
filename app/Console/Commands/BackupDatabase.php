<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Respaldo local de la base configurada, con retención por número de archivos.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--connection= : Conexión a respaldar; por defecto la configurada}
                            {--disk=local : Disco de Laravel donde se guarda el respaldo}
                            {--path=backups : Directorio dentro del disco}
                            {--keep=7 : Número máximo de respaldos conservados}
                            {--name= : Etiqueta opcional para el nombre del archivo}';

    protected $description = 'Crea un respaldo de la base de datos en el disco configurado y aplica retención';

    public function handle(): int
    {
        $connection = (string) ($this->option('connection') ?: config('database.default'));
        $disk = (string) $this->option('disk');
        $directory = trim((string) $this->option('path'), '/');
        $keep = max(1, (int) $this->option('keep'));
        $backupPath = null;

        try {
            $config = config("database.connections.{$connection}");

            if (! is_array($config)) {
                throw new RuntimeException("La conexión [{$connection}] no está configurada.");
            }

            $driver = (string) Arr::get($config, 'driver');
            $extension = $driver === 'sqlite' ? 'sqlite' : 'dump';
            $contents = $this->dump($connection, $config, $driver);
            $stamp = now()->format('Ymd-His-u');
            $label = $this->sanitizeLabel((string) $this->option('name'));
            $fileName = $label === ''
                ? "{$connection}-{$stamp}.{$extension}"
                : "{$connection}-{$label}-{$stamp}.{$extension}";
            $backupPath = $directory === '' ? $fileName : "{$directory}/{$fileName}";

            if (! Storage::disk($disk)->put($backupPath, $contents)) {
                throw new RuntimeException("No se pudo escribir el respaldo en [{$backupPath}].");
            }

            $this->prune($disk, $directory, $connection, $extension, $keep);

            $this->info("Respaldo creado: {$backupPath}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ($backupPath !== null && Storage::disk($disk)->exists($backupPath)) {
                Storage::disk($disk)->delete($backupPath);
            }

            $this->error('El respaldo falló: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dump(string $connection, array $config, string $driver): string
    {
        if ($driver === 'sqlite') {
            $database = (string) Arr::get($config, 'database');

            if ($database === ':memory:') {
                throw new RuntimeException('No se puede respaldar una base SQLite en memoria.');
            }

            $temporary = tempnam(sys_get_temp_dir(), 'ubicaditos-sqlite-');

            if ($temporary === false) {
                throw new RuntimeException('No se pudo crear el archivo temporal del respaldo.');
            }

            unlink($temporary);
            $quoted = str_replace("'", "''", $temporary);
            DB::connection($connection)->getPdo()->exec("VACUUM INTO '{$quoted}'");
            $contents = file_get_contents($temporary);
            unlink($temporary);

            if ($contents === false) {
                throw new RuntimeException('No se pudo leer el respaldo SQLite temporal.');
            }

            return $contents;
        }

        if ($driver === 'pgsql') {
            $arguments = array_values(array_filter([
                'pg_dump',
                Arr::get($config, 'host') ? '--host='.Arr::get($config, 'host') : null,
                Arr::get($config, 'port') ? '--port='.Arr::get($config, 'port') : null,
                Arr::get($config, 'username') ? '--username='.Arr::get($config, 'username') : null,
                '--format=custom',
                '--no-password',
                '--dbname='.Arr::get($config, 'database'),
            ], fn ($argument) => $argument !== null));

            $process = new Process($arguments, null, [
                'PGPASSWORD' => (string) Arr::get($config, 'password', ''),
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()) !== ''
                    ? trim($process->getErrorOutput())
                    : 'pg_dump terminó con error.');
            }

            return $process->getOutput();
        }

        throw new RuntimeException("El driver [{$driver}] no tiene un método de respaldo soportado.");
    }

    private function prune(string $disk, string $directory, string $connection, string $extension, int $keep): void
    {
        $prefix = "{$connection}-";

        $backups = collect(Storage::disk($disk)->files($directory))
            ->filter(fn (string $file) => str_starts_with(basename($file), $prefix)
                && str_ends_with($file, ".{$extension}"))
            ->sortDesc()
            ->values();

        foreach ($backups->slice($keep) as $obsolete) {
            Storage::disk($disk)->delete($obsolete);
            $this->line("Respaldo antiguo eliminado: {$obsolete}");
        }
    }

    private function sanitizeLabel(string $label): string
    {
        $label = strtolower(trim($label));
        $label = preg_replace('/[^a-z0-9_-]+/', '-', $label) ?? '';

        return trim($label, '-');
    }
}
