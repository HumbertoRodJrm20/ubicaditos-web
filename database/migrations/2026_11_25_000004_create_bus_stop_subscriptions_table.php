<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-012: suscripciones in-app "avisame cuando el bus X llegue a la parada Y".
 * Aprobada por el propietario el 2026-11-25 para SQLite y PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_stop_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained('buses')->cascadeOnDelete();
            $table->foreignId('stop_id')->constrained('stops')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'bus_id', 'stop_id']);
            $table->index(['bus_id', 'stop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_stop_subscriptions');
    }
};
