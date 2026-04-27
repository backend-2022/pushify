<?php

namespace Badawy\Pushify\Factories;

use Badawy\Pushify\Contracts\PushifyProviderInterface;
use InvalidArgumentException;

class PushifyProviderFactory
{
    public function make(?string $provider = null): PushifyProviderInterface
    {
        $provider ??= config('pushify.provider', 'firebase');
        $providers = config('pushify.providers', []);

        if (! isset($providers[$provider])) {
            throw new InvalidArgumentException("Unsupported push provider: {$provider}");
        }

        return app($providers[$provider]);
    }
}
