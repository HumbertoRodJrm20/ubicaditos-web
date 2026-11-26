<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-012: permite asociar una compartición activa al autobús en el que viaja
 * el pasajero. Sólo se expone un conteo agregado, nunca la identidad.
 * Aprobada por el propietario el 2026-11-25 para SQLite y PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_shares', function (Blueprint $table) {
            $table->foreignId('bus_id')->nullable()->after('user_id')->constrained('buses')->nullOnDelete();
            $table->index(['bus_id', 'revoked_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('location_shares', function (Blueprint $table) {
            $table->dropIndex(['bus_id', 'revoked_at', 'expires_at']);
            $table->dropForeign(['bus_id']);
            $table->dropColumn('bus_id');
        });
    }
};
