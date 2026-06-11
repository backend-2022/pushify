<?php

namespace Badawy\Pushify\Concerns;

use Badawy\Pushify\Support\PushifyExternalIdGenerator;

trait HasPushifyExternalId
{
    public function pushifyExternalId(): string
    {
        return app(PushifyExternalIdGenerator::class)->forUserId((int) $this->getKey());
    }
}
