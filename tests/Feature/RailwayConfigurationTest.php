<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Configuración portable para desplegar la rama staging en Railway.
 */
class RailwayConfigurationTest extends TestCase
{
    public function test_database_config_supports_railway_database_url(): void
    {
        $content = file_get_contents(config_path('database.php'));

        $this->assertStringContainsString("env('DATABASE_URL')", $content);
        $this->assertStringContainsString("env('DB_SSLMODE'", $content);
    }

    public function test_staging_env_template_is_railway_ready(): void
    {
        $content = file_get_contents(base_path('.env.staging.example'));

        $this->assertMatchesRegularExpression('/^DB_CONNECTION=pgsql$/m', $content);
        $this->assertMatchesRegularExpression('/^DB_SSLMODE=require$/m', $content);
        $this->assertMatchesRegularExpression('/^# DATABASE_URL=/m', $content);
        $this->assertDoesNotMatchRegularExpression('/^DB_PASSWORD=secret$/m', $content);
    }

    public function test_dockerfile_has_railway_final_stage(): void
    {
        $content = file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString('FROM web AS railway', $content);
        $this->assertStringContainsString('ubicaditos-railway-entrypoint', $content);
    }

    public function test_railway_entrypoint_uses_injected_port_and_migrations(): void
    {
        $content = file_get_contents(base_path('docker/entrypoint-railway.sh'));

        $this->assertStringContainsString('${PORT}', $content);
        $this->assertStringContainsString('php artisan migrate --force', $content);
        $this->assertStringContainsString('php-fpm -D', $content);
        $this->assertStringContainsString('exec nginx', $content);
    }

    public function test_railway_nginx_template_binds_injected_port(): void
    {
        $content = file_get_contents(base_path('docker/nginx/ubicaditos-railway.conf'));

        $this->assertStringContainsString('listen ${PORT}', $content);
        $this->assertStringContainsString('proxy_pass http://${REVERB_UPSTREAM}', $content);
    }
}
