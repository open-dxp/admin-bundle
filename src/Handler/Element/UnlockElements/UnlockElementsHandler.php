<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements;

use OpenDxp\Model\Element\Editlock;

final class UnlockElementsHandler
{
    /** @param array<array{id: int|string, type: string}> $elements */
    public function __invoke(UnlockElementsPayload $payload): void
    {
        foreach ($payload->elements as $element) {
            Editlock::unlock((int) $element['id'], $element['type']);
        }
    }
}
