<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-025: contexto opcional y visibilidad pública de las incidencias.
 * Aprobada por el propietario el 2026-11-25 para SQLite y PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->after('description')
                ->constrained()->nullOnDelete();
            $table->foreignId('bus_id')->nullable()->after('route_id')
                ->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('bus_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->boolean('is_public')->default(false)->after('longitude');
            $table->index(['is_public', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'created_at']);
            $table->dropColumn([
                'route_id',
                'bus_id',
                'latitude',
                'longitude',
                'is_public',
            ]);
        });
    }
};
