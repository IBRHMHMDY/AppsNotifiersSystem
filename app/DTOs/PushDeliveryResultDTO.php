<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class PushDeliveryResultDTO
{
    /**
     * @param  int  $successCount  عدد الإشعارات التي تم تسليمها بنجاح
     * @param  int  $failureCount  عدد الإشعارات التي فشل تسليمها
     * @param  array<int, string>  $invalidTokens  قائمة التوكنات المنتهية أو غير المسجلة لحذفها
     * @param  array<int, string>  $errors  رسائل الأخطاء المسجلة للتشخيص
     */
    public function __construct(
        public int $successCount = 0,
        public int $failureCount = 0,
        public array $invalidTokens = [],
        public array $errors = [],
    ) {}

    public function isCompletelySuccessful(): bool
    {
        return $this->failureCount === 0 && $this->successCount > 0;
    }

    public function hasAnySuccess(): bool
    {
        return $this->successCount > 0;
    }
}