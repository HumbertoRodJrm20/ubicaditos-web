<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero agregamos las nuevas columnas
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('maternal_last_name')->nullable()->after('last_name');
        });

        // Migramos los datos existentes: dividimos el campo 'name' en los nuevos campos
        DB::table('users')->get()->each(function ($user) {
            $nameParts = explode(' ', $user->name, 3);
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $nameParts[0] ?? '',
                    'last_name' => $nameParts[1] ?? '',
                    'maternal_last_name' => $nameParts[2] ?? '',
                ]);
        });

        // Ahora hacemos las columnas NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });

        // Finalmente eliminamos la columna antigua 'name'
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restauramos la columna 'name'
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        // Combinamos los nombres de vuelta
        DB::table('users')->get()->each(function ($user) {
            $fullName = trim(
                ($user->first_name ?? '') . ' ' .
                ($user->last_name ?? '') . ' ' .
                ($user->maternal_last_name ?? '')
            );
            DB::table('users')
                ->where('id', $user->id)
                ->update(['name' => $fullName]);
        });

        // Eliminamos las nuevas columnas
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'maternal_last_name']);
        });
    }
};
