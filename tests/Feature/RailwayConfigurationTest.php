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

    public function test_staging_defaults_fit_railway_free_service_limit(): void
    {
        $content = file_get_contents(base_path('.env.staging.example'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $content);
        $this->assertMatchesRegularExpression('/^QUEUE_CONNECTION=sync$/m', $content);
        $this->assertMatchesRegularExpression('/^BROADCAST_CONNECTION=log$/m', $content);
        $this->assertMatchesRegularExpression('/^SESSION_SECURE_COOKIE=true$/m', $content);
    }

    public function test_staging_compose_builds_the_railway_image_against_postgres(): void
    {
        $file = base_path('compose.staging.yaml');
        $this->assertFileExists($file);
        $content = file_get_contents($file);

        $this->assertStringContainsString('target: railway', $content);
        $this->assertStringContainsString('condition: service_healthy', $content);
        $this->assertStringContainsString('image: postgres:16-alpine', $content);
        $this->assertStringContainsString('127.0.0.1:8080:', $content);
    }

    public function test_railway_image_runs_as_non_root_and_ignores_environment_files(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $dockerignore = file_get_contents(base_path('.dockerignore'));

        $this->assertStringContainsString('USER www-data', $dockerfile);
        $this->assertStringContainsString('**/.env*', $dockerignore);
        $this->assertStringContainsString('.junie/', $dockerignore);
        $this->assertStringContainsString('bootstrap/cache/*.php', $dockerignore);
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
        $this->assertStringContainsString('REVERB_UPSTREAM:-127.0.0.1:8081', $content);
        $this->assertStringContainsString('php artisan migrate --force', $content);
        $this->assertStringContainsString('php-fpm -y /etc/php-fpm-railway.conf -D', $content);
        $this->assertStringContainsString('exec nginx', $content);
    }

    public function test_railway_nginx_template_binds_injected_port(): void
    {
        $content = file_get_contents(base_path('docker/nginx/ubicaditos-railway.conf'));

        $this->assertStringContainsString('listen ${PORT}', $content);
        $this->assertStringContainsString('proxy_pass http://${REVERB_UPSTREAM}', $content);
    }
}
