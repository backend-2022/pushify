<?php

namespace Badawy\Pushify\Contracts;

use Badawy\Pushify\Models\PushifySubscription;

interface PushifySubscriptionsInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function addUser(string $externalId, string $token, array $data = []): PushifySubscription;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addUserFor(int $userId, string $token, array $data = []): PushifySubscription;

    public function removeDevice(string $deviceToken): void;
}
