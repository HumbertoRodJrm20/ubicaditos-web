<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El conductor de una calificación se deriva del viaje (`trip_id`).
     * Si hay datos que impiden derivarlo, la migración falla sin borrarlos.
     */
    public function up(): void
    {
        $withoutTrip = DB::table('ratings')->whereNull('trip_id')->count();

        if ($withoutTrip > 0) {
            throw new RuntimeException("No se puede normalizar ratings: {$withoutTrip} calificaciones no tienen viaje asociado.");
        }

        $mismatched = DB::table('ratings')
            ->join('trips', 'trips.id', '=', 'ratings.trip_id')
            ->where(function ($query): void {
                $query->whereColumn('ratings.driver_id', '!=', 'trips.driver_id')
                    ->orWhere(function ($nested): void {
                        $nested->whereNull('ratings.driver_id')
                            ->whereNotNull('trips.driver_id');
                    });
            })
            ->count();

        if ($mismatched > 0) {
            throw new RuntimeException("No se puede normalizar ratings: {$mismatched} calificaciones no coinciden con el conductor del viaje.");
        }

        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['trip_id']);
            $table->dropColumn('driver_id');
            $table->foreignId('trip_id')->nullable(false)->change();
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            $table->foreignId('driver_id')
                ->nullable()
                ->after('passenger_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->dropForeign(['trip_id']);
            $table->foreignId('trip_id')->nullable()->change();
            $table->foreign('trip_id')->references('id')->on('trips')->nullOnDelete();
        });

        DB::table('ratings')->orderBy('id')->chunkById(100, function ($ratings): void {
            foreach ($ratings as $rating) {
                $driverId = DB::table('trips')->where('id', $rating->trip_id)->value('driver_id');

                DB::table('ratings')->where('id', $rating->id)->update(['driver_id' => $driverId]);
            }
        });
    }
};
