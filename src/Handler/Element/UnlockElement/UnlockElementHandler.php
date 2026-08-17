<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElement;

use OpenDxp\Model\Element\Editlock;

final class UnlockElementHandler
{
    public function __invoke(UnlockElementPayload $payload): void
    {
        Editlock::unlock($payload->id, $payload->type);
    }
}
