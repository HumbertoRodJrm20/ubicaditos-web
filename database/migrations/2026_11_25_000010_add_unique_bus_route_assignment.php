<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una pareja autobús/ruta repetida es una asignación duplicada, no un historial.
     */
    public function up(): void
    {
        $duplicate = DB::table('bus_route')
            ->select('bus_id', 'route_id')
            ->groupBy('bus_id', 'route_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException("No se puede normalizar bus_route: la pareja ({$duplicate->bus_id}, {$duplicate->route_id}) está repetida.");
        }

        Schema::table('bus_route', function (Blueprint $table): void {
            $table->unique(['bus_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bus_route', function (Blueprint $table): void {
            $table->dropUnique(['bus_id', 'route_id']);
        });
    }
};
