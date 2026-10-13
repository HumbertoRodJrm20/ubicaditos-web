<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    public function test_development_and_testing_use_sqlite_driver(): void
    {
        // En desarrollo (APP_ENV=local/develop) y en las pruebas solo se usa SQLite.
        $this->assertEquals('sqlite', config('database.default'));
    }

    public function test_pgsql_connection_is_registered_and_ready_for_production(): void
    {
        // El driver pgsql debe estar registrado y apuntar al puerto estándar de PostgreSQL.
        $pgsql = config('database.connections.pgsql');

        $this->assertNotNull($pgsql, 'La conexión pgsql no está registrada en config/database.php');
        $this->assertEquals('pgsql', $pgsql['driver']);
        $this->assertEquals(5432, $pgsql['port']);
    }

    public function test_default_env_example_uses_sqlite_for_development(): void
    {
        $content = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^DB_CONNECTION=sqlite$/m', $content);
        $this->assertDoesNotMatchRegularExpression('/^DB_CONNECTION=pgsql$/m', $content);
    }

    public function test_production_env_example_configures_postgresql(): void
    {
        $file = base_path('.env.production.example');
        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertMatchesRegularExpression('/^APP_ENV=production$/m', $content);
        $this->assertMatchesRegularExpression('/^DB_CONNECTION=pgsql$/m', $content);
        $this->assertMatchesRegularExpression('/^DB_PORT=5432$/m', $content);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $content);
    }

    public function test_staging_env_example_configures_postgresql(): void
    {
        $file = base_path('.env.staging.example');
        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertMatchesRegularExpression('/^APP_ENV=staging$/m', $content);
        $this->assertMatchesRegularExpression('/^DB_CONNECTION=pgsql$/m', $content);
        $this->assertMatchesRegularExpression('/^DB_PORT=5432$/m', $content);
    }
}