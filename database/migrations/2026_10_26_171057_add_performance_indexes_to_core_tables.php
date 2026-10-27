<?php

// database/migrations/2026_10_26_171057_add_performance_indexes_to_core_tables.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            // فهرس مركب يطابق الأعمدة الفعلية لتسريع TargetAudienceService
            $table->index(
                ['client_app_id', 'os_type', 'app_version'],
                'idx_devices_targeting_lookup'
            );
        });

        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->index(
                ['status', 'scheduled_at'],
                'idx_notifications_dispatch_queue'
            );
            $table->index(
                ['client_app_id', 'status', 'created_at'],
                'idx_notifications_app_timeline'
            );
        });

        Schema::table('app_updates', function (Blueprint $table): void {
            // تعديل القيد الفريد ليسمح بنفس رقم الإصدار لنظامي تشغيل مختلفين (Android & iOS) لنفس التطبيق
            $table->dropUnique(['client_app_id', 'version']);
            $table->unique(
                ['client_app_id', 'version', 'target_os'],
                'unq_app_updates_app_version_os'
            );
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropIndex('idx_devices_targeting_lookup');
        });

        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->dropIndex('idx_notifications_dispatch_queue');
            $table->dropIndex('idx_notifications_app_timeline');
        });

        Schema::table('app_updates', function (Blueprint $table): void {
            $table->dropUnique('unq_app_updates_app_version_os');
            $table->unique(['client_app_id', 'version']);
        });
    }
};
