<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rutas favoritas del pasajero (marcador personal, distinto del like).
     */
    public function up(): void
    {
        Schema::create('favorite_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_routes');
    }
};
