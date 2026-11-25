<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_stop', function (Blueprint $table) {
            $table->boolean('is_frequent')->default(false)->after('estimated_time_from_previous');
        });
    }

    public function down(): void
    {
        Schema::table('route_stop', function (Blueprint $table) {
            $table->dropColumn('is_frequent');
        });
    }
};
