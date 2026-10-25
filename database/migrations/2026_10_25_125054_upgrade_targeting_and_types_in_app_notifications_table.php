<?php

declare(strict_types=1);

use App\Enums\NotificationActionType;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            // جعل العنوان والنص اختياريين لدعم الإشعارات الصامتة (Silent Data Notifications)
            $table->string('title')->nullable()->change();
            $table->text('body')->nullable()->change();

            // 1. تصنيف نوع الإشعار
            $table->string('type')
                ->default(NotificationType::General->value)
                ->after('client_app_id')
                ->index();

            // 2. تصنيف طريقة الاستهداف ومعايير الفلترة الإضافية
            $table->string('target_type')
                ->default(NotificationTargetType::AllDevices->value)
                ->after('status')
                ->index();

            $table->json('target_app_versions')
                ->nullable()
                ->after('topic')
                ->comment('إصدارات التطبيق المستهدفة في حال كان الاستهداف Segmented');

            $table->json('target_device_ids')
                ->nullable()
                ->after('target_app_versions')
                ->comment('قائمة معرفات الأجهزة في حال كان الاستهداف SpecificDevices');

            // 3. سلوك النقر والتوجيه (Deep Link / URL / Open App)
            $table->string('action_type')
                ->default(NotificationActionType::OpenApp->value)
                ->after('body');

            $table->string('action_value')
                ->nullable()
                ->after('action_type')
                ->comment('الرابط الخارجي أو مسار الشاشة الداخلية داخل التطبيق');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->dropColumn([
                'type',
                'target_type',
                'target_app_versions',
                'target_device_ids',
                'action_type',
                'action_value',
            ]);

            $table->string('title')->nullable(false)->change();
            $table->text('body')->nullable(false)->change();
        });
    }
};
