<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PushDeliveryResultDTO;

interface PushNotificationServiceInterface
{
    /**
     * إرسال إشعار لجهاز واحد عبر FCM Token.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToToken(
        string $token,
        ?string $title,
        ?string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;

    /**
     * إرسال إشعار لمجموعة أجهزة (Multicast).
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function sendMulticast(
        array $tokens,
        ?string $title,
        ?string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;

    /**
     * إرسال إشعار لموضوع محدد (Firebase Topic).
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToTopic(
        string $topic,
        ?string $title,
        ?string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;

    /**
     * اشتراك أجهزة في مجموعة مواضيع.
     *
     * @param  array<int, string>  $topics
     * @param  array<int, string>|string  $tokens
     */
    public function subscribeToTopics(array $topics, array|string $tokens): bool;

    /**
     * إلغاء اشتراك أجهزة من مجموعة مواضيع.
     *
     * @param  array<int, string>  $topics
     * @param  array<int, string>|string  $tokens
     */
    public function unsubscribeFromTopics(array $topics, array|string $tokens): bool;
}