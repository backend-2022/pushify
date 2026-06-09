<?php

namespace Badawy\Pushify\Providers;

use Badawy\Pushify\Contracts\PushifyProviderInterface;
use Badawy\Pushify\Services\FirebaseService;

class FirebaseProvider implements PushifyProviderInterface
{
    public function __construct(private readonly FirebaseService $firebaseService) {}

    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array {
        return $this->firebaseService->sendToTopic($title, $body, $data, $image);
    }

    public function sendToUser(
        array|string $userIds,
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array {
        return $this->firebaseService->sendToUser($userIds, $title, $body, $data, $image);
    }
}
