<?php

namespace Badawy\Pushify\Providers;

use Badawy\Pushify\Contracts\PushifyProviderInterface;
use Badawy\Pushify\Services\OneSignalService;

class OneSignalProvider implements PushifyProviderInterface
{
    public function __construct(private readonly OneSignalService $oneSignalService) {}

    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $scheduledAt = null
    ): array {
        return $this->oneSignalService->sendToAll($title, $body, $data, $image, $scheduledAt);
    }
}
