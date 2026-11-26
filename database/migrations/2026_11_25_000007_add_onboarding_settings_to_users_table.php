<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('approval_status', 20)->default('approved')->after('is_active');
            $table->string('license_number', 50)->nullable()->unique()->after('approval_status');
            $table->string('license_photo_path', 255)->nullable()->after('license_number');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->string('locale', 5)->default('es')->after('avatar');
            $table->json('notification_preferences')->nullable()->after('locale');
            $table->index(['role', 'approval_status']);
        });

        DB::table('users')->where('role', 'driver')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role', 'approval_status']);
            $table->dropColumn([
                'approval_status',
                'license_number',
                'license_photo_path',
                'must_change_password',
                'locale',
                'notification_preferences',
            ]);
        });
    }
};
