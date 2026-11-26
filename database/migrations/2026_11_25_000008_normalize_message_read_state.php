<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `is_read` se derivaba de `read_at`; conservamos sólo el instante de lectura.
     */
    public function up(): void
    {
        DB::table('messages')
            ->where('is_read', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn('is_read');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->boolean('is_read')->default(false)->after('message');
        });

        DB::table('messages')
            ->whereNotNull('read_at')
            ->update(['is_read' => true]);
    }
};
