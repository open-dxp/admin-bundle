<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteDraft;

use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Model\Version;

final class DeleteDraftHandler
{
    public function __invoke(IdBodyPayload $payload): void
    {
        $version = Version::getById($payload->id);

        if ($version) {
            $version->delete();
        }
    }
}
