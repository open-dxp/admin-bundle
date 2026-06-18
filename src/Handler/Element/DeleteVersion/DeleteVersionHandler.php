<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteVersion;

use OpenDxp\Model\Version;

final class DeleteVersionHandler
{
    public function __invoke(IdBodyPayload $payload): void
    {
        Version::getById($payload->id)?->delete();
    }
}
