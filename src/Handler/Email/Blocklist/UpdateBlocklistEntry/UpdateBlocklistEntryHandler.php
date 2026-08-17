<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\UpdateBlocklistEntry;

use OpenDxp\Model\Tool;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;

final class UpdateBlocklistEntryHandler
{
    public function __invoke(BlocklistPayload $payload): UpdateBlocklistEntryResult
    {
        $address = Tool\Email\Blocklist::getByAddress($payload->data['address']);
        $address->setValues($payload->data);
        $address->save();

        return new UpdateBlocklistEntryResult($address->getObjectVars());
    }
}
