<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate;

use OpenDxp\Model\Element\Service;

final class UnlockPropagateHandler
{
    public function __invoke(UnlockPropagatePayload $payload): UnlockPropagateResult
    {
        $element = Service::getElementById($payload->type, $payload->id);
        if (!$element) {
            return new UnlockPropagateResult(success: false);
        }

        $element->unlockPropagate();

        return new UnlockPropagateResult(success: true);
    }
}
