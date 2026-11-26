<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('route_stop')
            ->select('route_id', 'stop_id')
            ->groupBy('route_id', 'stop_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException(
                "Cannot add route-stop uniqueness: duplicate route_id {$duplicate->route_id}, stop_id {$duplicate->stop_id}."
            );
        }

        Schema::table('route_stop', function (Blueprint $table) {
            $table->unique(['route_id', 'stop_id']);
        });
    }

    public function down(): void
    {
        Schema::table('route_stop', function (Blueprint $table) {
            $table->dropUnique(['route_id', 'stop_id']);
        });
    }
};
