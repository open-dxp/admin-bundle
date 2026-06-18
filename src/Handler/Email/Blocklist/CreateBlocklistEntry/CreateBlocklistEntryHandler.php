<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\CreateBlocklistEntry;

use OpenDxp\Model\Tool;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;

final class CreateBlocklistEntryHandler
{
    public function __invoke(BlocklistPayload $payload): array
    {
        $data = $payload->data;
        unset($data['id']);

        $address = new Tool\Email\Blocklist();
        $address->setValues($data);
        $address->save();

        return $address->getObjectVars();
    }
}
