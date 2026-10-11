<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebasePushService
{
    public function __construct(
        private readonly Messaging $messaging
    ) {}

    /**
     * إرسال الإشعار لعدة أجهزة (FCM Tokens) دفعة واحدة.
     *
     * @param array<string> $tokens
     * @param string $title
     * @param string $body
     * @param string|null $imageUrl
     * @return array<string, mixed>
     */
    public function sendMulticast(array $tokens, string $title, string $body, ?string $imageUrl = null): array
    {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        $notification = Notification::create($title, $body, $imageUrl);

        // إضافة صورة للإشعار إن وجدت
        $message = CloudMessage::new()->withNotification($notification);

        try {
            // إرسال الإشعار كدفعة واحدة (Multicast) لأقصى كفاءة
            $report = $this->messaging->sendMulticast($message, $tokens);

            // يمكنك هنا معالجة الـ Tokens غير الصالحة (التي قام أصحابها بحذف التطبيق)
            // $invalidTokens = $report->unknownTokens();
            
            return [
                'success' => $report->successes()->count(),
                'failure' => $report->failures()->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Firebase Push Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}