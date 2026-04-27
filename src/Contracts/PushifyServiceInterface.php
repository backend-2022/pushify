<?php

namespace Badawy\Pushify\Contracts;

use Badawy\Pushify\Models\Pushify;

interface PushifyServiceInterface
{
    public function create(array $payload): Pushify;

    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): Pushify;

    public function send(Pushify $notification): Pushify;

    public function markScheduledAsSent(): int;
}
