<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_updates', function (Blueprint $table): void {
            $table->string('target_os', 30)->nullable()->after('client_app_id')->index();
            $table->string('min_supported_version', 50)->nullable()->after('version');
            $table->json('blocked_versions')->nullable()->after('min_supported_version');
            $table->unsignedTinyInteger('rollout_percentage')->default(100)->after('is_force_update');
            $table->boolean('is_active')->default(true)->after('rollout_percentage')->index();

            // فهرس مركب لتسريع استعلام فحص التحديثات عبر الـ API
            $table->index(['client_app_id', 'is_active', 'target_os'], 'idx_app_updates_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('app_updates', function (Blueprint $table): void {
            $table->dropIndex('idx_app_updates_lookup');
            $table->dropColumn([
                'target_os',
                'min_supported_version',
                'blocked_versions',
                'rollout_percentage',
                'is_active',
            ]);
        });
    }
};
