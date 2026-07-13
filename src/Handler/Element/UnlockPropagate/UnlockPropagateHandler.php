<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\Element\Service;

final class UnlockPropagateHandler
{
    public function __invoke(UnlockPropagatePayload $payload): void
    {
        $element = Service::getElementById($payload->type, $payload->id);
        if (!$element) {
            throw new AdminOperationFailedException();
        }

        $element->unlockPropagate();
    }
}
