<?php

namespace Badawy\Pushify\Contracts;

use Badawy\Pushify\Models\PushifySubscription;

interface PushifySubscriptionsInterface
{

    public function subscribe(int $userId, string $token, array $data = []): PushifySubscription;

    public function unsubscribe(string $deviceToken): void;
}
