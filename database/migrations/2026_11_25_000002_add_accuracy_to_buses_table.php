<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RNF-08: precisión opcional (metros) de la última posición del autobús.
 * Aprobada por el propietario el 2026-11-25 para SQLite y PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->unsignedSmallInteger('accuracy')->nullable()->after('last_location_update');
        });
    }

    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropColumn('accuracy');
        });
    }
};
