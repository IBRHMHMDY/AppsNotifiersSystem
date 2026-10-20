<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PushDeliveryResultDTO;

interface PushNotificationServiceInterface
{
    /**
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
     * @param  array<string, string>  $data
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null
    ): PushDeliveryResultDTO;

    /**
     * اشتراك توكن (أو مجموعة توكنات) في عدة مواضيع على Firebase.
     *
     * @param  array<int, string>  $topics
     * @param  array<int, string>|string  $tokens
     */
    public function subscribeToTopics(array $topics, array|string $tokens): bool;

    /**
     * إلغاء اشتراك توكن (أو مجموعة توكنات) من عدة مواضيع على Firebase.
     *
     * @param  array<int, string>  $topics
     * @param  array<int, string>|string  $tokens
     */
    public function unsubscribeFromTopics(array $topics, array|string $tokens): bool;
}
