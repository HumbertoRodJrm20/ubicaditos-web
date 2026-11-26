<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HU-027: orden y etiqueta de las rutas favoritas del pasajero.
 * Aprobada por el propietario el 2026-11-25 para SQLite y PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorite_routes', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable()->after('route_id');
            $table->string('label', 40)->nullable()->after('position');
            $table->index(['user_id', 'position']);
        });

        // Los favoritos ya guardados conservan su orden de inserción histórico.
        DB::table('favorite_routes')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id')
            ->each(function ($userId) {
                DB::table('favorite_routes')
                    ->where('user_id', $userId)
                    ->orderBy('id')
                    ->get(['id'])
                    ->each(fn ($favorite, $index) => DB::table('favorite_routes')
                        ->where('id', $favorite->id)
                        ->update(['position' => $index + 1]));
            });
    }

    public function down(): void
    {
        Schema::table('favorite_routes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'position']);
            $table->dropColumn(['position', 'label']);
        });
    }
};
