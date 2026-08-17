<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions;

use OpenDxp\Model;

final class DeleteAllVersionsHandler
{
    public function __invoke(DeleteAllVersionsPayload $payload): void
    {
        $versions = new Model\Version\Listing();
        $versions->setCondition(
            'cid = ' . $versions->quote($payload->id) .
            ' AND date <> ' . $versions->quote($payload->date) .
            ' AND ctype = ' . $versions->quote($payload->type)
        );
        foreach ($versions->load() as $version) {
            $version->delete();
        }
    }
}
