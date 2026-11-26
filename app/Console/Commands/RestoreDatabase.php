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
 * Restauración local de un respaldo creado por backup:database.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'backup:restore
                            {file : Ruta del respaldo dentro del disco}
                            {--connection= : Conexión a restaurar; por defecto la configurada}
                            {--disk=local : Disco de Laravel donde está el respaldo}
                            {--force : Confirma la sobrescritura sin preguntar}';

    protected $description = 'Restaura una base de datos desde un respaldo local (operación destructiva)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('La restauración sobrescribirá la base actual. ¿Continuar?')) {
            $this->warn('Restauración cancelada.');

            return self::FAILURE;
        }

        $connection = (string) ($this->option('connection') ?: config('database.default'));
        $disk = (string) $this->option('disk');
        $file = (string) $this->argument('file');
        $temporary = null;

        try {
            $config = config("database.connections.{$connection}");

            if (! is_array($config)) {
                throw new RuntimeException("La conexión [{$connection}] no está configurada.");
            }

            if (! Storage::disk($disk)->exists($file)) {
                throw new RuntimeException("El respaldo [{$file}] no existe en el disco [{$disk}].");
            }

            $contents = Storage::disk($disk)->get($file);
            if ($contents === null) {
                throw new RuntimeException("No se pudo leer el respaldo [{$file}].");
            }

            $driver = (string) Arr::get($config, 'driver');

            if ($driver === 'sqlite') {
                $database = (string) Arr::get($config, 'database');

                if ($database === ':memory:') {
                    throw new RuntimeException('No se puede restaurar una base SQLite en memoria.');
                }

                DB::purge($connection);

                if (file_put_contents($database, $contents) === false) {
                    throw new RuntimeException('No se pudo escribir el archivo SQLite de destino.');
                }
            } elseif ($driver === 'pgsql') {
                $temporary = tempnam(sys_get_temp_dir(), 'ubicaditos-restore-');

                if ($temporary === false) {
                    throw new RuntimeException('No se pudo crear el archivo temporal de restauración.');
                }

                if (file_put_contents($temporary, $contents) === false) {
                    throw new RuntimeException('No se pudo preparar el respaldo para pg_restore.');
                }

                $arguments = array_values(array_filter([
                    'pg_restore',
                    Arr::get($config, 'host') ? '--host='.Arr::get($config, 'host') : null,
                    Arr::get($config, 'port') ? '--port='.Arr::get($config, 'port') : null,
                    Arr::get($config, 'username') ? '--username='.Arr::get($config, 'username') : null,
                    '--clean',
                    '--if-exists',
                    '--no-owner',
                    '--no-password',
                    '--dbname='.Arr::get($config, 'database'),
                    $temporary,
                ], fn ($argument) => $argument !== null));

                $process = new Process($arguments, null, [
                    'PGPASSWORD' => (string) Arr::get($config, 'password', ''),
                ]);
                $process->setTimeout(600);
                $process->run();

                if (! $process->isSuccessful()) {
                    throw new RuntimeException(trim($process->getErrorOutput()) !== ''
                        ? trim($process->getErrorOutput())
                        : 'pg_restore terminó con error.');
                }
            } else {
                throw new RuntimeException("El driver [{$driver}] no tiene un método de restauración soportado.");
            }

            DB::purge($connection);
            $this->info("Base restaurada desde {$file}.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::purge($connection);
            $this->error('La restauración falló: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($temporary !== null && is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
