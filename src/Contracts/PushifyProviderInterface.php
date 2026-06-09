<?php

namespace Badawy\Pushify\Contracts;

interface PushifyProviderInterface
{
    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array;

    public function sendToUser(
        array|string $userIds,
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array;
}
