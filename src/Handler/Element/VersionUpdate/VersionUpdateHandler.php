<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate;

use OpenDxp\Model\Version;

final class VersionUpdateHandler
{
    public function __invoke(VersionUpdatePayload $payload): void
    {
        $data = $payload->data ?? [];
        $version = Version::getById($data['id']);

        if ($version && ($data['public'] != $version->getPublic() || $data['note'] != $version->getNote())) {
            $version->setPublic($data['public']);
            $version->setNote($data['note']);
            $version->save();
        }
    }
}
