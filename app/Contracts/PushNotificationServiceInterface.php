<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PushDeliveryResultDTO;

interface PushNotificationServiceInterface
{
    /**
     * إرسال إشعار إلى جهاز واحد عبر FCM Token.
     *
     * @param  array<string, string>  $data
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;

    /**
     * إرسال إشعار مجمع (Multicast) لمجموعة من الأجهزة دفعة واحدة (حتى 500 جهاز في الدفعة).
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, string>  $data
     */
    public function sendMulticast(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;

    /**
     * إرسال إشعار إلى موضوع (Topic) مشترك فيه مجموعة أجهزة.
     *
     * @param  array<string, string>  $data
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;
}