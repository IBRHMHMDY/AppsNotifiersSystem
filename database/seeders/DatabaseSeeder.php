<?php

namespace Database\Seeders;

use App\Enums\NotificationStatus;
use App\Enums\OsType;
use App\Models\AppNotification;
use App\Models\AppUpdate;
use App\Models\ClientApp;
use App\Models\Device;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
        ]);

        // 2. إنشاء تطبيقات فعلية للاختبار
        $mainApp = ClientApp::query()->updateOrCreate(
            ['bundle_id' => 'com.ibrahim.leave_manager'],
            [
                'name' => 'تطبيق مدير إجازاتي',
                'app_key' => 'test_live_app_key_1234567890_main_app',
                'is_active' => true,
            ]
        );

        $secondApp = ClientApp::query()->updateOrCreate(
            ['bundle_id' => 'com.ibrahim.ahgzly_pos'],
            [
                'name' => 'نظام نقاط البيع POS',
                'app_key' => 'test_live_app_key_0987654321_pos_app',
                'is_active' => true,
            ]
        );

        // 3. توليد أجهزة موزعة على آخر 7 أيام لرسم المخططات البيانية (Charts)
        foreach (range(0, 6) as $daysAgo) {
            $devicesCount = random_int(3, 10);

            Device::factory()->count($devicesCount)->create([
                'client_app_id' => $mainApp->id,
                'os_type' => OsType::cases()[array_rand(OsType::cases())],
                'app_version' => fake()->randomElement(['1.0.0', '1.2.0', '1.3.2', '1.5.0']),
                'subscribed_topics' => collect(['offers', 'vip', 'general', 'updates'])->shuffle()->take(2)->all(),
                'created_at' => now()->subDays($daysAgo),
                'last_active_at' => now()->subDays(random_int(0, $daysAgo)),
            ]);
        }

        // 4. إضافة سياسة تحديث ذكية (مع إصدار محظور 1.3.2 للتجربة)
        AppUpdate::query()->updateOrCreate(
            [
                'client_app_id' => $mainApp->id,
                'version' => '2.0.0',
            ],
            [
                'target_os' => null,
                'min_supported_version' => '1.2.0',
                'blocked_versions' => ['1.3.2'],
                'store_url' => 'https://play.google.com/store/apps/details?id=com.ibrahim.leave_manager',
                'is_force_update' => false,
                'rollout_percentage' => 100,
                'is_active' => true,
                'release_notes' => "1. إطلاق الواجهة الجديدة كلياً.\n2. تحسين سرعة المزامنة وإصلاح الأخطاء.",
            ]
        );

        // 5. إنشاء إشعارات تجريبية لعرض إحصائيات التسليم والفتح
        AppNotification::factory()->create([
            'client_app_id' => $mainApp->id,
            'title' => 'تحديث جديد متاح الآن 🚀',
            'body' => 'قم بتحديث التطبيق إلى الإصدار 2.0.0 للاستمتاع بالمزايا الجديدة.',
            'status' => NotificationStatus::SENT,
            'sent_count' => 42,
            'failed_count' => 2,
            'delivered_count' => 39,
            'opened_count' => 27,
            'clicked_count' => 14,
            'sent_at' => now()->subHours(3),
        ]);

        AppNotification::factory()->create([
            'client_app_id' => $secondApp->id,
            'title' => 'عرض خاص لمشتركي VIP',
            'body' => 'اشترك الآن واحصل على خصم 30% على الباقة السنوية.',
            'topic' => 'vip',
            'status' => NotificationStatus::PENDING,
        ]);
    }
}
