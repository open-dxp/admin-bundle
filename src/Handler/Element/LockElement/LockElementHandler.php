<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\LockElement;

use OpenDxp\Model\Element\Editlock;

final class LockElementHandler
{
    public function __invoke(LockElementPayload $payload): void
    {
        Editlock::lock($payload->id, $payload->type, $payload->sessionId);
    }
}
